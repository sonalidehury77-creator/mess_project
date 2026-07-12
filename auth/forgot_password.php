<?php
/**
 * 🔒 auth/forgot_password.php
 * 
 * Account Recovery & Verification Token Generation Interface
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

require_once __DIR__ . '/../config/db_connect.php'; 
require_once __DIR__ . '/mail_config.php';   

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Security token invalid. Re-authenticate access.';
    } else {
        $hostel_roll = filter_input(INPUT_POST, 'hostel_roll', FILTER_UNSAFE_RAW);
        $email       = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!$hostel_roll || !$email) {
            $error_message = 'Provide a valid Hostel Roll and properly formatted email.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, name, email, hostel_roll, status FROM student WHERE hostel_roll = :hostel_roll AND email = :email LIMIT 1");
                $stmt->execute([
                    ':hostel_roll' => trim($hostel_roll),
                    ':email'       => trim($email)
                ]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                

                if (!$student) {
                    $error_message = 'No match found for the requested credentials.';
                } elseif (isset($student['status']) && (string)$student['status'] === 'blocked') {
                    $error_message = 'Your resident profile is locked. Contact the Chief Warden office.';
                } else {
                    $otp = (string)random_int(100000, 999999);
                    $otp_expiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

                    $update_stmt = $pdo->prepare("UPDATE student SET reset_otp = :otp, otp_expiry = :expiry WHERE id = :id");
                    $update_stmt->execute([
                        ':otp'    => $otp,
                        ':expiry' => $otp_expiry,
                        ':id'     => $student['id']
                    ]);

                    if (sendOTPEmail($student['email'], $student['name'], $otp)) {
                        $_SESSION['reset_student_id']  = $student['id'];
                        $_SESSION['reset_hostel_roll'] = $student['hostel_roll'];
                        $_SESSION['otp_verified']      = false;
                        $_SESSION['otp_attempts']      = 0;
                        $_SESSION['last_otp_request_time'] = time();

                        header("Location: verify_otp.php");
                        exit();
                    } else {
                        $error_message = 'The validation routing pipeline failed. Try again later.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Database Account Recovery Runtime Failure: " . $e->getMessage());
                $error_message = 'Internal application transaction crash.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Recovery | Hostel Mess Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { univ: { 900: '#1e3a8a', 800: '#1e40af', 50: '#f0f4f8' } }
                }
            }
        }
    </script>
</head>
<body class="h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 antialiased">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-5 pointer-events-none bg-[radial-gradient(#1e3a8a_1px,transparent_0)] [background-size:16px_16px]"></div>
        <div class="relative z-10 text-center">
            <div class="mx-auto h-12 w-12 bg-univ-50 text-univ-900 rounded-xl flex items-center justify-center text-xl shadow-sm"><i class="fa-solid fa-shield-halved"></i></div>
            <h2 class="mt-4 text-xl font-extrabold tracking-tight text-univ-900 uppercase">Forgot Password</h2>
            <p class="mt-1.5 text-xs text-slate-500 font-light">Enter your student credentials to receive a verification OTP token.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border border-red-200/60 rounded-xl p-4 flex gap-3 text-xs text-red-700 relative z-10">
                <i class="fa-solid fa-circle-exclamation text-base text-red-500 shrink-0 mt-0.5"></i>
                <p class="font-medium leading-relaxed"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <form class="mt-6 space-y-5 relative z-10" action="" method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="space-y-1.5">
                <label for="hostel_roll" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600">Hostel Roll Number</label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs"><i class="fa-solid fa-id-card"></i></div>
                    <input id="hostel_roll" name="hostel_roll" type="text" required placeholder="e.g. H2026-99" class="block w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-univ-900 focus:border-univ-900 font-mono">
                </div>
            </div>
            <div class="space-y-1.5">
                <label for="email" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600">Registered Email Address</label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs"><i class="fa-solid fa-envelope"></i></div>
                    <input id="email" name="email" type="email" required placeholder="resident@university.edu" class="block w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-univ-900 focus:border-univ-900">
                </div>
            </div>
            <div>
                <button type="submit" class="w-full flex justify-center py-3 px-4 text-xs font-bold uppercase tracking-wider rounded-xl text-white bg-univ-900 hover:bg-univ-800 shadow-md transition-all">Send Verification OTP</button>
            </div>
        </form>
        <div class="text-center relative z-10 border-t border-slate-100 pt-4">
            <a href="login.php" class="text-xs font-semibold text-univ-900 hover:text-univ-800 transition-colors inline-flex items-center gap-1.5"><i class="fa-solid fa-arrow-left-long text-[10px]"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>