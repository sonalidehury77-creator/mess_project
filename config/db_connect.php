<?php

/* ============================
   DATABASE CONFIG
============================ */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mess_system');

/* ============================
   MYSQLI SETTINGS
============================ */

// Show MySQL errors as exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Prevent hanging connection
ini_set('mysqli.connect_timeout', 5);

/* ============================
   CONNECT DATABASE
============================ */

try {

    $conn = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME
    );

    // Set charset (important for emoji & UTF text)
    $conn->set_charset("utf8mb4");

} catch (Exception $e) {

    // Log real error (hidden from user)
    error_log(
        "Database Connection Failed: "
        . $e->getMessage()
    );

    // Send proper server error
    http_response_code(500);

    // Show safe message
    die("❌ System temporarily unavailable. Please try again later.");
}

/* ============================
   TIMEZONE SETTINGS
============================ */

// PHP timezone
date_default_timezone_set("Asia/Kolkata");

// MySQL timezone
$conn->query("SET time_zone = '+05:30'");

?>