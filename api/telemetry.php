<?php
// api/telemetry.php - Live System Metrics & Telemetry Feed
header('Content-Type: application/json');

require_once __DIR__ . '/../db.php';

// Check overstay anomalies in background
checkOverstayAnomalies($conn);

// 1. Gather KPIs
$totalPasses = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes"));
$todayPasses = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes WHERE DATE(pass_creation_date) = CURDATE()"));
$activeInside = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes WHERE status = 'Checked-In'"));
$overstayAlerts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM passes WHERE overstay_alert = 1 OR status = 'Flagged'"));
$unresolvedAlerts = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM security_alerts WHERE resolved = 0"));

// Threat Level Calculation
$threatLevel = 'GREEN';
if ($unresolvedAlerts >= 3 || $overstayAlerts >= 3) {
    $threatLevel = 'RED';
} elseif ($unresolvedAlerts > 0 || $overstayAlerts > 0) {
    $threatLevel = 'AMBER';
}

// 2. Fetch Recent Checkpoint Logs
$recentLogsQuery = mysqli_query($conn, "SELECT id, pass_number, visitor_name, action, checkpoint_gate, biometric_match_pct, alpr_plate, status_note, timestamp 
                                        FROM checkpoint_logs ORDER BY id DESC LIMIT 10");
$recentLogs = [];
if ($recentLogsQuery) {
    while ($r = mysqli_fetch_assoc($recentLogsQuery)) {
        $recentLogs[] = $r;
    }
}

// 3. Fetch Active Security Alerts
$alertsQuery = mysqli_query($conn, "SELECT id, pass_number, visitor_name, alert_type, severity, details, created_at 
                                    FROM security_alerts WHERE resolved = 0 ORDER BY id DESC LIMIT 5");
$alerts = [];
if ($alertsQuery) {
    while ($a = mysqli_fetch_assoc($alertsQuery)) {
        $alerts[] = $a;
    }
}

echo json_encode([
    'success' => true,
    'metrics' => [
        'total_passes' => $totalPasses,
        'today_passes' => $todayPasses,
        'active_inside' => $activeInside,
        'overstay_alerts' => $overstayAlerts,
        'unresolved_alerts' => $unresolvedAlerts,
        'threat_level' => $threatLevel,
        'timestamp' => date('Y-m-d H:i:s')
    ],
    'recent_logs' => $recentLogs,
    'alerts' => $alerts
]);
