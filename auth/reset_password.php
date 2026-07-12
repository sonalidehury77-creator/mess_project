<?php
/**
 * 🔒 auth/reset_password.php
 * 
 * Secure Credential Modification & Session Finalization Interface
 */

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true, // Fixed the typo here
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
    ]);
}

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true || !isset($_SESSION['reset_student_id'])) {
    header("Location: forgot_password.php");
    exit();
}

require_once __DIR__ . '/../config/db_connect.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = 'Security footprint validation failure.';
    } else {
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $special   = preg_match('@[^\w]@', $password);

        if (!$uppercase || !$lowercase || !$number || !$special || strlen($password) < 8) {
            $error_message = 'Password parameters fail to fulfill structural complexity metrics.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Confirmation values do not match.';
        } else {
            try {
                $secure_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE student SET password = :password, reset_otp = NULL, otp_expiry = NULL WHERE id = :id");
                $stmt->execute([
                    ':password' => $secure_hash,
                    ':id'       => $_SESSION['reset_student_id']
                ]);

                // PURGE SYSTEM RECOVERY CONTEXT SESSIONS COMPLETELY
                unset($_SESSION['reset_student_id']);
                unset($_SESSION['reset_hostel_roll']);
                unset($_SESSION['otp_verified']);
                unset($_SESSION['otp_attempts']);
                unset($_SESSION['last_otp_request_time']);

                header("Location: login.php?reset=success");
                exit();
            } catch (PDOException $e) {
                error_log("Database Exception at Credential Mutation: " . $e->getMessage());
                $error_message = 'Internal storage allocation error.';
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
    <title>Reset Password | Campus Dining</title>
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
            <div class="mx-auto h-12 w-12 bg-univ-50 text-univ-900 rounded-xl flex items-center justify-center text-xl shadow-sm"><i class="fa-solid fa-lock-open"></i></div>
            <h2 class="mt-4 text-xl font-extrabold tracking-tight text-univ-900 uppercase">Update Password</h2>
            <p class="mt-1.5 text-xs text-slate-500 font-light">Set your new password below.</p>
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
                <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600">New Password</label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs"><i class="fa-solid fa-key"></i></div>
                    <input id="password" name="password" type="password" required placeholder="••••••••" class="block w-full pl-10 pr-10 py-3 border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-univ-900 focus:border-univ-900">
                    <button type="button" onclick="toggleVisibility('password', 'toggleIcon1')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 text-xs"><i id="toggleIcon1" class="fa-solid fa-eye"></i></button>
                </div>
                <div class="pt-1.5 space-y-1">
                    <div class="h-1 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div id="strengthBar" class="h-full w-0 transition-all duration-300 rounded-full bg-slate-200"></div>
                    </div>
                    <div class="flex justify-between items-center text-[10px] text-slate-400 font-medium">
                        <span id="strengthText">Strength Evaluator</span>
                        <span id="strengthPercent">0%</span>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="confirm_password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600">Confirm Password</label>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-xs"><i class="fa-solid fa-circle-check"></i></div>
                    <input id="confirm_password" name="confirm_password" type="password" required placeholder="••••••••" class="block w-full pl-10 pr-10 py-3 border border-slate-300 rounded-xl text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-univ-900 focus:border-univ-900">
                    <button type="button" onclick="toggleVisibility('confirm_password', 'toggleIcon2')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 text-xs"><i id="toggleIcon2" class="fa-solid fa-eye"></i></button>
                </div>
            </div>

            <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-3 text-[11px] text-slate-500 space-y-1.5 font-light">
                <div class="grid grid-cols-2 gap-x-2 gap-y-1">
                    <div id="req-len" class="flex items-center gap-1.5"><i class="fa-solid fa-circle text-[8px] text-slate-300"></i> Min 8 Chars</div>
                    <div id="req-up" class="flex items-center gap-1.5"><i class="fa-solid fa-circle text-[8px] text-slate-300"></i> Upper [A-Z]</div>
                    <div id="req-low" class="flex items-center gap-1.5"><i class="fa-solid fa-circle text-[8px] text-slate-300"></i> Lower [a-z]</div>
                    <div id="req-num" class="flex items-center gap-1.5"><i class="fa-solid fa-circle text-[8px] text-slate-300"></i> Number [0-9]</div>
                    <div id="req-spec" class="flex items-center gap-1.5 col-span-2"><i class="fa-solid fa-circle text-[8px] text-slate-300"></i> Special Symbol</div>
                </div>
            </div>
            <button type="submit" class="w-full flex justify-center py-3 px-4 text-xs font-bold uppercase tracking-wider rounded-xl text-white bg-univ-900 hover:bg-univ-800 shadow-md transition-all">Commit Password</button>
        </form>
    </div>

    <script>
        function toggleVisibility(fieldId, iconId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
            if (field && icon) {
                if (field.type === 'password') {
                    field.type = 'text';
                    icon.className = 'fa-solid fa-eye-slash';
                } else {
                    field.type = 'password';
                    icon.className = 'fa-solid fa-eye';
                }
            }
        }

        document.getElementById('password').addEventListener('input', function(e) {
            const pass = e.target.value;
            let score = 0;
            const checks = {
                len: pass.length >= 8,
                up: /[A-Z]/.test(pass),
                low: /[a-z]/.test(pass),
                num: /[0-9]/.test(pass),
                spec: /[^A-Za-z0-9]/.test(pass)
            };

            for (const key in checks) {
                const el = document.getElementById('req-' + key);
                const icon = el.querySelector('i');
                if (checks[key]) {
                    score++;
                    icon.className = 'fa-solid fa-circle-check text-emerald-500 text-[10px]';
                } else {
                    icon.className = 'fa-solid fa-circle text-[8px] text-slate-300';
                }
            }

            const bar = document.getElementById('strengthBar');
            const txt = document.getElementById('strengthText');
            const pct = document.getElementById('strengthPercent');
            const percentage = (score / 5) * 100;
            
            bar.style.width = percentage + '%';
            pct.innerText = percentage + '%';

            if (score === 0) {
                bar.className = 'h-full bg-slate-200 rounded-full';
                txt.innerText = 'Evaluation Metric';
            } else if (score <= 2) {
                bar.className = 'h-full bg-red-500 rounded-full';
                txt.innerText = 'Insecure';
            } else if (score <= 4) {
                bar.className = 'h-full bg-amber-500 rounded-full';
                txt.innerText = 'Moderate';
            } else {
                bar.className = 'h-full bg-emerald-500 rounded-full';
                txt.innerText = 'Secure';
            }
        });
    </script>
</body>
</html>