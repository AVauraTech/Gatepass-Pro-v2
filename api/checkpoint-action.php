<?php
// api/checkpoint-action.php - Handle Check-in, Check-out, and Security Flagging
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!$data) {
    $data = $_POST;
}

$passNumber = trim($data['pass_number'] ?? '');
$action = trim(strtoupper($data['action'] ?? '')); // CHECK_IN, CHECK_OUT, FLAG, APPROVE
$gate = trim($data['gate'] ?? 'Gate-Alpha North');
$guardId = trim($data['guard_id'] ?? 'ADMIN-01');
$biometricMatch = isset($data['biometric_match']) ? floatval($data['biometric_match']) : null;
$alprPlate = trim($data['alpr_plate'] ?? '');
$note = trim($data['note'] ?? '');

if (empty($passNumber) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Pass number and action are required.']);
    exit;
}

$passEscaped = mysqli_real_escape_string($conn, $passNumber);
$q = mysqli_query($conn, "SELECT * FROM passes WHERE pass_number = '$passEscaped' LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    echo json_encode(['success' => false, 'message' => 'Pass not found.']);
    exit;
}

$pass = mysqli_fetch_assoc($q);
$now = date('Y-m-d H:i:s');
$newStatus = $pass['status'];

switch ($action) {
    case 'CHECK_IN':
        $newStatus = 'Checked-In';
        mysqli_query($conn, "UPDATE passes SET status = 'Checked-In', checkin_time = '$now' WHERE id = " . intval($pass['id']));
        logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'CHECK_IN', $gate, $guardId, $biometricMatch, $alprPlate, $note ?: 'Visitor checked into facility');
        break;

    case 'CHECK_OUT':
        $newStatus = 'Checked-Out';
        mysqli_query($conn, "UPDATE passes SET status = 'Checked-Out', checkout_time = '$now', overstay_alert = 0 WHERE id = " . intval($pass['id']));
        logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'CHECK_OUT', $gate, $guardId, $biometricMatch, $alprPlate, $note ?: 'Visitor departed facility');
        break;

    case 'FLAG':
        $newStatus = 'Flagged';
        mysqli_query($conn, "UPDATE passes SET status = 'Flagged', overstay_alert = 1 WHERE id = " . intval($pass['id']));
        logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'FLAGGED', $gate, $guardId, $biometricMatch, $alprPlate, $note ?: 'Security anomaly detected');
        triggerSecurityAlert($conn, $passNumber, $pass['full_name'], 'GUARD_FLAGGED', 'HIGH', $note ?: 'Visitor manually flagged by checkpoint officer for anomaly.');
        break;

    case 'APPROVE':
        $newStatus = 'Approved';
        mysqli_query($conn, "UPDATE passes SET status = 'Approved' WHERE id = " . intval($pass['id']));
        logCheckpointEvent($conn, $passNumber, $pass['full_name'], 'APPROVED', $gate, $guardId, $biometricMatch, $alprPlate, $note ?: 'Pass approved by admin');
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        exit;
}

echo json_encode([
    'success' => true,
    'action' => $action,
    'new_status' => $newStatus,
    'timestamp' => $now,
    'message' => "Successfully processed $action for pass #$passNumber"
]);
