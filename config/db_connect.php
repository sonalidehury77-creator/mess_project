<?php
/**
 * 🔒 Enterprise Core Configuration & Database Connection Engine
 * Version: 2.0.0-Production (Stable)
 * Focus: High security prepared configurations, billing matrix definitions, and core system global tokens.
 */

// Block direct URL entry execution for filesystem safety
if (basename($_SERVER['PHP_SELF']) === 'db_connect.php') {
    http_response_code(403);
    die('Direct system core access is strictly prohibited.');
}

/* ==========================================================================
   1. CORE DATABASE ENVIRONMENT CONFIGURATION VARIABLES
   ========================================================================== */
define('DB_HOST', '127.0.0.1'); 
define('DB_USER', 'root'); 
define('DB_PASS', ''); 
define('DB_NAME', 'mess_system');
/*
define('DB_HOST', 'sql112.infinityfree.com');
define('DB_USER', 'if0_42340314');
define('DB_PASS', 'HostelMess54321');
define('DB_NAME', 'if0_42340314_mess_system');*/

/* ==========================================================================
   2. SYSTEM SETTINGS & RUNTIME TIMEZONE MANAGEMENT
   ========================================================================== */
date_default_timezone_set("Asia/Kolkata"); // Sync PHP app space with IST
ini_set('default_socket_timeout', 5);      // Avoid hung DB thread resources

/* ==========================================================================
   3. ENTERPRISE CORE BUSINESS CONSTANTS & TIMELINE CUTOFFS
   ========================================================================== */
define('MEAL_DAILY_PRICE', 120.00);        // Base default billing daily sub-total charge
define('MEAL_CUTOFF_HOUR', 20);            // 20 = 8:00 PM lock threshold for next-day changes

// Paths & Fallbacks Matrix
define('DIR_UPLOADS_STUDENTS', __DIR__ . '/../uploads/students/');
define('PATH_DEFAULT_AVATAR', '../uploads/students/default.png');

/* ==========================================================================
   4. ESTABLISHING SECURE PDO RESOURCE TERMINAL
   ========================================================================== */
try {

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    $pdo->exec("SET time_zone = '+05:30'");

} catch (PDOException $e) {

    error_log("Database Connection Error : " . $e->getMessage());

    die("
    <h2 style='font-family:Arial;color:red'>
        Database Connection Failed
    </h2>
    <p>Please contact administrator.</p>
    ");

}

/* ==========================================================================
   5. CROSS-CONTEXT UTILITY GLOBAL ACCESS FUNCTIONS
   ========================================================================== */

/**
 * 🛡️ Anti-XSS (Cross-Site Scripting) Output Mitigation Shield
 */
if (!function_exists('escape_output')) {
    function escape_output(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * 🎫 Global CSRF (Cross-Site Request Forgery) Protection Management Tokens
 */
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }
}

/**
 * ⏱️ Real-time Meal Modification Deadline Evaluator
 * Returns true if user can still book/edit tomorrow's meal states.
 */
if (!function_exists('is_before_meal_deadline')) {
    function is_before_meal_deadline(): bool {
        $current_hour = (int)date('G');
        return $current_hour < MEAL_CUTOFF_HOUR;
    }
}