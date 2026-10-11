<?php
// db.php - Database Connection File with Auto-Provisioning & Crypto Support

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'getpassdb');

$conn = @mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn === false) {
    die("<div style='background:#101e2c; color:#ff6b6b; font-family:sans-serif; padding:30px; margin:40px auto; max-width:600px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.5);'>
        <h2>⚠️ GatePass Pro - Database Connection Error</h2>
        <p>Could not connect to MySQL server at <strong>" . DB_SERVER . "</strong>.</p>
        <p style='color:#a0c4d8;'>Please verify that MySQL is started in your XAMPP Control Panel.</p>
        <p style='font-size:0.85rem; color:#888;'>Details: " . htmlspecialchars(mysqli_connect_error()) . "</p>
    </div>");
}

mysqli_set_charset($conn, "utf8mb4");

// Include cryptographic & security helper functions
require_once __DIR__ . '/includes/crypto-utils.php';
?>
