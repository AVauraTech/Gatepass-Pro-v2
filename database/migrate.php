<?php
// database/migrate.php - Auto-upgrade database schema to Next-Gen GetPass Pro
require_once __DIR__ . '/../db.php';

function runMigration($conn) {
    // 1. Add columns to passes table if not present
    $columns = [
        'vehicle_no' => "VARCHAR(50) DEFAULT 'N/A'",
        'security_clearance' => "VARCHAR(50) DEFAULT 'Standard'",
        'photo_url' => "TEXT DEFAULT NULL",
        'hmac_hash' => "VARCHAR(64) DEFAULT NULL",
        'checkin_time' => "DATETIME DEFAULT NULL",
        'checkout_time' => "DATETIME DEFAULT NULL",
        'overstay_alert' => "TINYINT(1) DEFAULT 0",
        'host_name' => "VARCHAR(255) DEFAULT 'Security Operations'",
        'host_department' => "VARCHAR(255) DEFAULT 'General'",
        'nda_signed' => "TINYINT(1) DEFAULT 0",
        'nda_signature_data' => "MEDIUMTEXT DEFAULT NULL"
    ];

    $existingCols = [];
    $res = mysqli_query($conn, "SHOW COLUMNS FROM passes");
    if ($res) {
        while ($col = mysqli_fetch_assoc($res)) {
            $existingCols[] = $col['Field'];
        }
    }

    foreach ($columns as $colName => $colDef) {
        if (!in_array($colName, $existingCols)) {
            mysqli_query($conn, "ALTER TABLE passes ADD `$colName` $colDef");
        }
    }

    // 2. Create checkpoint_logs table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `checkpoint_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pass_number` VARCHAR(255) NOT NULL,
        `visitor_name` VARCHAR(255) DEFAULT '',
        `action` VARCHAR(50) NOT NULL,
        `checkpoint_gate` VARCHAR(100) DEFAULT 'Gate-Alpha North',
        `guard_id` VARCHAR(100) DEFAULT 'SYS-AI-GUARD',
        `biometric_match_pct` DECIMAL(5,2) DEFAULT NULL,
        `alpr_plate` VARCHAR(100) DEFAULT NULL,
        `status_note` TEXT DEFAULT NULL,
        `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 3. Create security_alerts table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `security_alerts` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pass_number` VARCHAR(255) DEFAULT NULL,
        `visitor_name` VARCHAR(255) DEFAULT '',
        `alert_type` VARCHAR(100) NOT NULL,
        `severity` VARCHAR(50) DEFAULT 'MEDIUM',
        `details` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `resolved` TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 4. Create simulation_leaderboard table
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `simulation_leaderboard` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `agent_name` VARCHAR(100) NOT NULL,
        `score` INT DEFAULT 0,
        `level` INT DEFAULT 1,
        `accuracy_pct` DECIMAL(5,2) DEFAULT 0.00,
        `processed_count` INT DEFAULT 0,
        `breaches_prevented` INT DEFAULT 0,
        `throughput_rate` DECIMAL(5,2) DEFAULT 0.00,
        `date_played` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 5. Populate default HMAC for existing passes if missing or mismatched
    $passesRes = mysqli_query($conn, "SELECT id, pass_number, identity_card_no, to_date, category FROM passes");
    if ($passesRes) {
        while ($p = mysqli_fetch_assoc($passesRes)) {
            $hash = generatePassHMAC($p['pass_number'], $p['identity_card_no'], $p['to_date'], $p['category']);
            mysqli_query($conn, "UPDATE passes SET hmac_hash = '$hash' WHERE id = " . intval($p['id']));
        }
    }

    return true;
}

if (isset($conn) && $conn) {
    runMigration($conn);
    echo "Migration completed successfully.\n";
}
