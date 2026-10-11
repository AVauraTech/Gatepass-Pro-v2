<?php
// api/turnstile-webhook.php - High-Throughput IoT Turnstile & Gate Access Webhook Endpoint
header('Content-Type: application/json');
$startTime = microtime(true);

require_once __DIR__ . '/../db.php';

// Accept JSON payload from IoT Barrier / Turnstile Controller / RFID Scanner
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!$payload) {
    $payload = $_POST;
}

$turnstileId = trim($payload['turnstile_id'] ?? 'TURNSTILE-ALPHA-01');
$gate = trim($payload['gate'] ?? 'Gate-Alpha North');
$passNumber = trim($payload['pass_number'] ?? '');
$eventType = strtoupper(trim($payload['event_type'] ?? 'ENTRY_REQUEST')); // ENTRY_REQUEST, EXIT_REQUEST, BARRIER_PASSAGE, TAMPER_ALARM
$rfidUid = trim($payload['rfid_uid'] ?? '');
$qrSignature = trim($payload['signature'] ?? '');

if (empty($passNumber)) {
    echo json_encode([
        'status' => 'REJECTED',
        'code' => 400,
        'barrier_open' => false,
        'message' => 'Pass number is mandatory for turnstile dispatch.'
    ]);
    exit;
}

$passEsc = mysqli_real_escape_string($conn, $passNumber);
$q = mysqli_query($conn, "SELECT * FROM passes WHERE pass_number = '$passEsc' LIMIT 1");

if (!$q || mysqli_num_rows($q) === 0) {
    // Log intrusion attempt
    logCheckpointEvent($conn, $passNumber, 'UNKNOWN_INTRUDER', 'TURNSTILE_REJECT', $gate, $turnstileId, 0.0, null, 'Unregistered pass presented at turnstile');
    triggerSecurityAlert($conn, $passNumber, 'UNKNOWN', 'TURNSTILE_BREACH_ATTEMPT', 'HIGH', "Unregistered pass scanned at turnstile $turnstileId.");
    
    echo json_encode([
        'status' => 'DENIED',
        'barrier_open' => false,
        'reason' => 'UNREGISTERED_PASS',
        'message' => 'Access Denied: Pass not recognized by Access Registry.',
        'latency_ms' => round((microtime(true) - $startTime) * 1000, 2)
    ]);
    exit;
}

$pass = mysqli_fetch_assoc($q);
$currentDate = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// 1. HMAC Cryptographic Integrity Check
$expectedHmac = generatePassHMAC($pass['pass_number'], $pass['identity_card_no'], $pass['to_date'], $pass['category']);
$hmacValid = hash_equals($expectedHmac, (string)($pass['hmac_hash'] ?: ''));
if (!empty($qrSignature)) {
    $hmacValid = $hmacValid && hash_equals($expectedHmac, $qrSignature);
}

// 2. Temporal Validity Check
$isExpired = ($currentDate > $pass['to_date']);
$isNotYetActive = ($currentDate < $pass['from_date']);
$isFlagged = ($pass['status'] === 'Flagged' || $pass['overstay_alert'] == 1);

// Determine Turnstile Barrier Actuation
if (!$hmacValid) {
    triggerSecurityAlert($conn, $passNumber, $pass['full_name'], 'CRYPTOGRAPHIC_FORGERY', 'CRITICAL', "Forged HMAC signature detected at turnstile $turnstileId");
    logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'BARRIER_BLOCKED_FORGERY', $gate, $turnstileId, null, null, 'Cryptographic checksum failure');
    
    echo json_encode([
        'status' => 'LOCKDOWN',
        'barrier_open' => false,
        'reason' => 'CRYPTO_CHECKSUM_MISMATCH',
        'visitor' => $pass['full_name'],
        'message' => 'SECURITY LOCKDOWN: Counterfeit or tampered credential detected.',
        'latency_ms' => round((microtime(true) - $startTime) * 1000, 2)
    ]);
    exit;
}

if ($isExpired) {
    logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'BARRIER_BLOCKED_EXPIRED', $gate, $turnstileId, null, null, 'Expired pass at turnstile');
    echo json_encode([
        'status' => 'DENIED',
        'barrier_open' => false,
        'reason' => 'TEMPORAL_WINDOW_ELAPSED',
        'visitor' => $pass['full_name'],
        'expiry_date' => $pass['to_date'],
        'message' => 'Access Denied: Credential expired on ' . $pass['to_date'],
        'latency_ms' => round((microtime(true) - $startTime) * 1000, 2)
    ]);
    exit;
}

if ($isFlagged) {
    triggerSecurityAlert($conn, $passNumber, $pass['full_name'], 'FLAGGED_INDIVIDUAL_INTERCEPT', 'HIGH', "Flagged visitor attempted passage at $turnstileId");
    logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'BARRIER_BLOCKED_FLAGGED', $gate, $turnstileId, null, null, 'Flagged status intercept');
    echo json_encode([
        'status' => 'DENIED',
        'barrier_open' => false,
        'reason' => 'RECORD_FLAGGED',
        'visitor' => $pass['full_name'],
        'message' => 'Access Denied: Flagged profile requires security escort.',
        'latency_ms' => round((microtime(true) - $startTime) * 1000, 2)
    ]);
    exit;
}

// 3. Grant Barrier Passage
if ($eventType === 'EXIT_REQUEST') {
    mysqli_query($conn, "UPDATE passes SET status = 'Checked-Out', checkout_time = '$now', overstay_alert = 0 WHERE id = " . intval($pass['id']));
    logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'TURNSTILE_EXIT_GRANTED', $gate, $turnstileId, 99.0, $pass['vehicle_no'], 'Turnstile optical passage confirmed (Exit)');
    $actionMsg = 'Turnstile unlocked for exit passage.';
} else {
    mysqli_query($conn, "UPDATE passes SET status = 'Checked-In', checkin_time = '$now' WHERE id = " . intval($pass['id']));
    logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'TURNSTILE_ENTRY_GRANTED', $gate, $turnstileId, 99.0, $pass['vehicle_no'], 'Turnstile optical passage confirmed (Entry)');
    $actionMsg = 'Turnstile unlocked for entry passage.';
}

$latencyMs = round((microtime(true) - $startTime) * 1000, 2);

echo json_encode([
    'status' => 'AUTHORIZED',
    'barrier_open' => true,
    'signal' => 'PULSE_RELAY_OPEN_5000MS',
    'pass_number' => $pass['pass_number'],
    'visitor' => $pass['full_name'],
    'clearance' => $pass['security_clearance'] ?? 'Standard',
    'gate' => $gate,
    'turnstile_id' => $turnstileId,
    'timestamp' => $now,
    'latency_ms' => $latencyMs,
    'message' => $actionMsg
]);
