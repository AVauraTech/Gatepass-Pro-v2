<?php
// includes/crypto-utils.php - Cryptographic HMAC-SHA256, Audit Logging & Anomaly Utilities
if (!defined('GPMS_CRYPTO_INIT')) {
    define('GPMS_CRYPTO_INIT', true);
    define('GPMS_SECRET_KEY', 'GPMS_NEXUS_HMAC_SECRET_KEY_v2_2026_SECURE');

    /**
     * Generate HMAC-SHA256 signature for tamper-proof pass verification
     */
    function generatePassHMAC($passNumber, $identityCardNo, $toDate, $category) {
        $canonical = trim((string)$passNumber) . '|' . trim((string)$identityCardNo) . '|' . trim((string)$toDate) . '|' . trim((string)$category);
        return hash_hmac('sha256', $canonical, GPMS_SECRET_KEY);
    }

    /**
     * Verify HMAC-SHA256 signature
     */
    function verifyPassHMAC($passNumber, $identityCardNo, $toDate, $category, $hash) {
        $expected = generatePassHMAC($passNumber, $identityCardNo, $toDate, $category);
        return hash_equals($expected, (string)$hash);
    }

    /**
     * Generate secure QR Code JSON payload
     */
    function generateQRPayload($row) {
        $payload = [
            'v' => 2, // version
            'pn' => (string)$row['pass_number'],
            'fn' => (string)$row['full_name'],
            'ic' => (string)$row['identity_card_no'],
            'cat' => (string)$row['category'],
            'td' => (string)$row['to_date'],
            'clr' => (string)($row['security_clearance'] ?? 'Standard'),
            'veh' => (string)($row['vehicle_no'] ?? 'N/A'),
            'host' => (string)($row['host_name'] ?? 'Security Operations'),
            'sig' => (string)($row['hmac_hash'] ?? generatePassHMAC($row['pass_number'], $row['identity_card_no'], $row['to_date'], $row['category']))
        ];
        return json_encode($payload);
    }

    /**
     * Record an event in the checkpoint audit trail
     */
    function logCheckpointEvent($conn, $passNumber, $visitorName, $action, $gate = 'Gate-Alpha North', $guardId = 'ADMIN-01', $biometricMatch = null, $alprPlate = null, $note = '') {
        $passNumber = mysqli_real_escape_string($conn, $passNumber);
        $visitorName = mysqli_real_escape_string($conn, $visitorName);
        $action = mysqli_real_escape_string($conn, $action);
        $gate = mysqli_real_escape_string($conn, $gate);
        $guardId = mysqli_real_escape_string($conn, $guardId);
        $biometricMatch = $biometricMatch !== null ? floatval($biometricMatch) : 'NULL';
        $alprPlate = $alprPlate !== null ? "'" . mysqli_real_escape_string($conn, $alprPlate) . "'" : 'NULL';
        $note = mysqli_real_escape_string($conn, $note);

        $sql = "INSERT INTO checkpoint_logs (pass_number, visitor_name, action, checkpoint_gate, guard_id, biometric_match_pct, alpr_plate, status_note) 
                VALUES ('$passNumber', '$visitorName', '$action', '$gate', '$guardId', $biometricMatch, $alprPlate, '$note')";
        return mysqli_query($conn, $sql);
    }

    /**
     * Trigger a security anomaly alert
     */
    function triggerSecurityAlert($conn, $passNumber, $visitorName, $alertType, $severity = 'HIGH', $details = '') {
        $passNumber = mysqli_real_escape_string($conn, $passNumber);
        $visitorName = mysqli_real_escape_string($conn, $visitorName);
        $alertType = mysqli_real_escape_string($conn, $alertType);
        $severity = mysqli_real_escape_string($conn, $severity);
        $details = mysqli_real_escape_string($conn, $details);

        // Check if unresolved duplicate alert exists within last 1 hour
        $check = mysqli_query($conn, "SELECT id FROM security_alerts WHERE pass_number = '$passNumber' AND alert_type = '$alertType' AND resolved = 0 LIMIT 1");
        if ($check && mysqli_num_rows($check) > 0) {
            return false;
        }

        $sql = "INSERT INTO security_alerts (pass_number, visitor_name, alert_type, severity, details) 
                VALUES ('$passNumber', '$visitorName', '$alertType', '$severity', '$details')";
        return mysqli_query($conn, $sql);
    }

    /**
     * Check for overstay anomalies and flag them
     */
    function checkOverstayAnomalies($conn) {
        $overstays = mysqli_query($conn, "SELECT id, pass_number, full_name, to_date FROM passes WHERE status = 'Checked-In' AND to_date < CURDATE()");
        $flaggedCount = 0;
        if ($overstays) {
            while ($o = mysqli_fetch_assoc($overstays)) {
                mysqli_query($conn, "UPDATE passes SET overstay_alert = 1, status = 'Flagged' WHERE id = " . intval($o['id']));
                triggerSecurityAlert($conn, $o['pass_number'], $o['full_name'], 'OVERSTAY_EXCEEDED', 'CRITICAL', "Visitor pass expired on {$o['to_date']} but exit check-out was never registered.");
                $flaggedCount++;
            }
        }
        return $flaggedCount;
    }
}
