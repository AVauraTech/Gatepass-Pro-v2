<?php
// api/verify-pass.php - Cryptographic HMAC-SHA256 Pass Verification Endpoint
header('Content-Type: application/json');
$startTime = microtime(true);

require_once __DIR__ . '/../db.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$passNumber = trim($data['pass_number'] ?? $_GET['pass_number'] ?? '');
$qrPayload = trim($data['qr_payload'] ?? $_GET['qr_payload'] ?? '');

$payloadData = null;
if (!empty($qrPayload)) {
    // If QR payload is JSON, decode it
    $decoded = json_decode($qrPayload, true);
    if (is_array($decoded) && isset($decoded['pn'])) {
        $payloadData = $decoded;
        $passNumber = $decoded['pn'];
    }
}

if (empty($passNumber)) {
    echo json_encode([
        'success' => false,
        'message' => 'Pass number or QR payload is required.'
    ]);
    exit;
}

$passNumber = mysqli_real_escape_string($conn, $passNumber);
$query = mysqli_query($conn, "SELECT * FROM passes WHERE pass_number = '$passNumber' LIMIT 1");

if (!$query || mysqli_num_rows($query) === 0) {
    $execTimeMs = round((microtime(true) - $startTime) * 1000, 2);
    echo json_encode([
        'success' => false,
        'valid' => false,
        'reason' => 'PASS_NOT_FOUND',
        'message' => 'Pass number does not exist in registry.',
        'verification_time_ms' => $execTimeMs
    ]);
    exit;
}

$pass = mysqli_fetch_assoc($query);

// 1. Verify Cryptographic Signature
$expectedHash = generatePassHMAC($pass['pass_number'], $pass['identity_card_no'], $pass['to_date'], $pass['category']);
$signatureValid = true;
$tamperDetected = false;

if (!empty($pass['hmac_hash']) && !hash_equals($pass['hmac_hash'], $expectedHash)) {
    $signatureValid = false;
    $tamperDetected = true;
}

if ($payloadData && isset($payloadData['sig'])) {
    if (!hash_equals($payloadData['sig'], $expectedHash)) {
        $signatureValid = false;
        $tamperDetected = true;
    }
}

// 2. Check Temporal Validity
$currentDate = date('Y-m-d');
$isExpired = false;
$isNotYetActive = false;

if ($currentDate > $pass['to_date']) {
    $isExpired = true;
} elseif ($currentDate < $pass['from_date']) {
    $isNotYetActive = true;
}

// 3. Determine Overall Validation Status
$isValid = $signatureValid && !$isExpired && !$isNotYetActive && ($pass['status'] !== 'Flagged');

// If tampered, log security alert
if ($tamperDetected) {
    triggerSecurityAlert($conn, $pass['pass_number'], $pass['full_name'], 'CRYPTOGRAPHIC_TAMPER', 'CRITICAL', 'Pass payload signature mismatch. Possible counterfeit or manual DB tampering.');
}

// Fetch recent checkpoint logs for this pass
$logQuery = mysqli_query($conn, "SELECT action, checkpoint_gate, biometric_match_pct, alpr_plate, timestamp FROM checkpoint_logs WHERE pass_number = '$passNumber' ORDER BY id DESC LIMIT 5");
$logs = [];
if ($logQuery) {
    while ($l = mysqli_fetch_assoc($logQuery)) {
        $logs[] = $l;
    }
}

$execTimeMs = round((microtime(true) - $startTime) * 1000, 2);

echo json_encode([
    'success' => true,
    'valid' => $isValid,
    'tampered' => $tamperDetected,
    'expired' => $isExpired,
    'not_yet_active' => $isNotYetActive,
    'verification_time_ms' => $execTimeMs,
    'pass' => [
        'id' => (int)$pass['id'],
        'pass_number' => $pass['pass_number'],
        'full_name' => $pass['full_name'],
        'contact_number' => $pass['contact_number'],
        'email' => $pass['email'],
        'identity_type' => $pass['identity_type'],
        'identity_card_no' => $pass['identity_card_no'],
        'category' => $pass['category'],
        'security_clearance' => $pass['security_clearance'] ?? 'Standard',
        'vehicle_no' => $pass['vehicle_no'] ?? 'N/A',
        'from_date' => $pass['from_date'],
        'to_date' => $pass['to_date'],
        'reason' => $pass['reason'],
        'status' => $pass['status'],
        'photo_url' => $pass['photo_url'],
        'checkin_time' => $pass['checkin_time'],
        'checkout_time' => $pass['checkout_time'],
        'overstay_alert' => (bool)$pass['overstay_alert'],
        'host_name' => $pass['host_name'] ?? 'Security Operations',
        'host_department' => $pass['host_department'] ?? 'General',
        'nda_signed' => (bool)($pass['nda_signed'] ?? false),
        'hmac_hash' => $pass['hmac_hash']
    ],
    'logs' => $logs
]);
