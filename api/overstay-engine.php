<?php
// api/overstay-engine.php - Automated Temporal Tracker & Dwell Time Anomaly Engine
header('Content-Type: application/json');
$startTime = microtime(true);

require_once __DIR__ . '/../db.php';

$maxHours = isset($_GET['max_hours']) ? floatval($_GET['max_hours']) : 8.0;
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// Fetch all currently checked-in visitors
$sql = "SELECT id, pass_number, full_name, contact_number, category, from_date, to_date, checkin_time, vehicle_no, host_name, overstay_alert 
        FROM passes 
        WHERE status = 'Checked-In'";
$res = mysqli_query($conn, $sql);

$inspectedCount = 0;
$flaggedCount = 0;
$anomalies = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $inspectedCount++;
        $isOverstay = false;
        $reason = '';
        $dwellMinutes = 0;

        // 1. Check Date Expiration
        if ($row['to_date'] < $today) {
            $isOverstay = true;
            $reason = "Pass validity expired on {$row['to_date']}, but exit check-out was never registered.";
        }

        // 2. Check Dwell Time Exceeded (> max hours)
        if (!empty($row['checkin_time'])) {
            $checkinTimestamp = strtotime($row['checkin_time']);
            $currentTimestamp = time();
            $dwellMinutes = round(($currentTimestamp - $checkinTimestamp) / 60);

            if ($dwellMinutes > ($maxHours * 60)) {
                $isOverstay = true;
                $hoursElapsed = round($dwellMinutes / 60, 1);
                $reason = "Visitor dwell time of {$hoursElapsed} hrs exceeded facility threshold ({$maxHours} hrs). Host: {$row['host_name']}.";
            }
        }

        if ($isOverstay) {
            $flaggedCount++;
            $passId = intval($row['id']);
            $passNum = $row['pass_number'];
            $visitorName = $row['full_name'];

            // Update pass status
            mysqli_query($conn, "UPDATE passes SET overstay_alert = 1, status = 'Flagged' WHERE id = $passId");

            // Dispatch security alert
            triggerSecurityAlert($conn, $passNum, $visitorName, 'UNAUTHORIZED_PREMISE_OVERSTAY', 'CRITICAL', $reason);

            // Log event in audit stream
            logCheckpointEvent($conn, $passNum, $visitorName, 'OVERSTAY_ALERT_TRIGGERED', 'Anomaly Engine', 'SYS-MONITOR', null, $row['vehicle_no'], $reason);

            $anomalies[] = [
                'id' => $passId,
                'pass_number' => $passNum,
                'full_name' => $visitorName,
                'contact_number' => $row['contact_number'],
                'category' => $row['category'],
                'vehicle_no' => $row['vehicle_no'],
                'checkin_time' => $row['checkin_time'],
                'dwell_minutes' => $dwellMinutes,
                'reason' => $reason
            ];
        }
    }
}

$execMs = round((microtime(true) - $startTime) * 1000, 2);

echo json_encode([
    'success' => true,
    'engine' => 'TEMPORAL_DWELL_TRACKER_V2',
    'timestamp' => $now,
    'execution_time_ms' => $execMs,
    'inspected_active_visitors' => $inspectedCount,
    'anomalies_flagged_count' => $flaggedCount,
    'active_anomalies' => $anomalies
]);
