<?php
/**
 * 🔒 auth/resend_otp.php
 * 
 * Secure One-Time Password Regeneration & Cooldown Controller
 */

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
    ]);
}

if (!isset($_SESSION['reset_student_id']) || !isset($_SESSION['reset_hostel_roll'])) {
    header("Location: forgot_password.php");
    exit();
}

require_once __DIR__ . '/../config/db_connect.php'; 
require_once __DIR__ . '/mail_config.php';   

$current_time = time();

// ENFORCE 60-SECONDS RATE LIMITING COOLDOWN
if (isset($_SESSION['last_otp_request_time'])) {
    $elapsed = $current_time - $_SESSION['last_otp_request_time'];
    if ($elapsed < 60) {
        header("Location: verify_otp.php");
        exit();
    }
}

try {
    $stmt = $pdo->prepare("SELECT name, email FROM student WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['reset_student_id']]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $new_otp = (string)random_int(100000, 999999);
        $new_expiry = date('Y-m-d H:i:s', $current_time + 600); // 10 Minutes Window

        $update_stmt = $pdo->prepare("UPDATE student SET reset_otp = :otp, otp_expiry = :expiry WHERE id = :id");
        $update_stmt->execute([
            ':otp'    => $new_otp,
            ':expiry' => $new_expiry,
            ':id'     => $_SESSION['reset_student_id']
        ]);

        $_SESSION['otp_attempts'] = 0;
        $_SESSION['last_otp_request_time'] = $current_time;

        sendOTPEmail($student['email'], $student['name'], $new_otp);
    }
} catch (PDOException $e) {
    error_log("Database Exception during OTP Regeneration: " . $e->getMessage());
}

header("Location: verify_otp.php");
exit();