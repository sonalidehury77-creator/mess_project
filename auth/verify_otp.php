<?php
/**
 * 🔒 auth/verify_otp.php
 * 
 * One-Time Password Validation Interface with Security Brute-Force Safeguards
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

// 1. DATABASE ROUTING CONNECTOR
require_once __DIR__ . '/../config/db_connect.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['otp_attempts'])) {
    $_SESSION['otp_attempts'] = 0;
}

$error_message = '';
$is_expired     = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Security token footprint manipulation detected.';
    } else {
        $submitted_otp = '';
        if (isset($_POST['otp']) && is_array($_POST['otp'])) {
            foreach ($_POST['otp'] as $digit) {
                $submitted_otp .= filter_var($digit, FILTER_SANITIZE_NUMBER_INT);
            }
        }

        if (strlen($submitted_otp) !== 6) {
            $error_message = 'Provide a complete 6-digit verification code token.';
        } elseif ($_SESSION['otp_attempts'] >= 5) {
            $error_message = 'Too many failed verification logs. Request a fresh OTP token.';
            $is_expired = true;
        } else {
            try {
                $stmt = $pdo->prepare("SELECT reset_otp, otp_expiry FROM student WHERE id = :id LIMIT 1");
                $stmt->execute([':id' => $_SESSION['reset_student_id']]);
                $student = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    $error_message = 'Session reference mismatch error occurred.';
                } else {
                    $current_time = time();
                    $expiry_time  = strtotime($student['otp_expiry']);

                    if ($current_time > $expiry_time) {
                        $error_message = 'The validation window has expired (10-minute limit exceeded).';
                        $is_expired = true;
                    } elseif (!hash_equals($student['reset_otp'], $submitted_otp)) {
                        $_SESSION['otp_attempts']++;
                        $remaining = 5 - $_SESSION['otp_attempts'];
                        
                        if ($remaining <= 0) {
                            $error_message = 'Maximum attempts exhausted. Re-initialize validation routing.';
                            $is_expired = true;
                        } else {
                            $error_message = "Invalid verification code sequence. {$remaining} attempts remaining.";
                        }
                    } else {
                        $_SESSION['otp_verified'] = true;
                        header("Location: reset_password.php");
                        exit();
                    }
                }
            } catch (PDOException $e) {
                error_log("Database verification validation error: " . $e->getMessage());
                $error_message = 'Internal framework error occurred.';
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
    <title>Token Verification | Campus Dining</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Poppins', 'sans-serif'] }, colors: { univ: { 900: '#1e3a8a', 800: '#1e40af', 50: '#f0f4f8' } } } } }
    </script>
</head>
<body class="h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 antialiased">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-5 pointer-events-none bg-[radial-gradient(#1e3a8a_1px,transparent_0)] [background-size:16px_16px]"></div>
        <div class="relative z-10 text-center">
            <div class="mx-auto h-12 w-12 bg-univ-50 text-univ-900 rounded-xl flex items-center justify-center text-xl shadow-sm"><i class="fa-solid fa-key"></i></div>
            <h2 class="mt-4 text-xl font-extrabold tracking-tight text-univ-900 uppercase">Verify Secure OTP</h2>
            <p class="mt-1.5 text-xs text-slate-500 font-light">An OTP has been dispatched to your mailbox.</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 border border-red-200/60 rounded-xl p-4 flex gap-3 text-xs text-red-700 relative z-10">
                <i class="fa-solid fa-triangle-exclamation text-base text-red-500 shrink-0 mt-0.5"></i>
                <p class="font-medium leading-relaxed"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($is_expired || $_SESSION['otp_attempts'] >= 5): ?>
            <div class="text-center pt-2 relative z-10 space-y-4">
                <a href="resend_otp.php" class="w-full inline-flex justify-center items-center py-3 px-4 text-xs font-bold uppercase tracking-wider rounded-xl text-white bg-univ-900 hover:bg-univ-800 shadow-md transition-all">
                    <i class="fa-solid fa-rotate-right mr-2"></i> Resend Verification OTP
                </a>
            </div>
        <?php else: ?>
            <form id="otpForm" class="mt-6 space-y-6 relative z-10" action="" method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="flex justify-between items-center gap-2" id="otpContainer">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" name="otp[]" maxlength="1" data-index="<?= $i; ?>" class="otp-box w-12 h-14 border border-slate-300 rounded-xl text-center text-xl font-bold font-mono text-univ-900 bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-univ-900 focus:border-univ-900 transition-all" <?= $i === 0 ? 'autofocus' : ''; ?> pattern="\d*">
                    <?php endfor; ?>
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 text-xs font-bold uppercase tracking-wider rounded-xl text-white bg-univ-900 hover:bg-univ-800 shadow-md transition-all">Authorize Token</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$is_expired && $_SESSION['otp_attempts'] < 5): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const boxes = document.querySelectorAll('.otp-box');
            if(boxes[0]) boxes[0].focus();
            boxes.forEach((box, idx) => {
                box.addEventListener('input', (e) => {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                    if (e.target.value.length > 0 && idx < boxes.length - 1) boxes[idx + 1].focus();
                });
                box.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && idx > 0) boxes[idx - 1].focus();
                });
                box.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const clipboardData = (e.clipboardData || window.clipboardData).getData('text');
                    const numerals = clipboardData.replace(/[^0-9]/g, '').substring(0, 6);
                    if (numerals.length > 0) {
                        const splitDigits = numerals.split('');
                        boxes.forEach((targetBox, targetIdx) => {
                            if (splitDigits[targetIdx]) targetBox.value = splitDigits[targetIdx];
                        });
                        boxes[Math.min(splitDigits.length, boxes.length - 1)].focus();
                    }
                });
            });
        });
    </script>
    <?php endif; ?>
</body>
</html>