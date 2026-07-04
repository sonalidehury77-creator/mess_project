<?php
/**
 * 🔄 Hostel Management System - Password Reset Endpoint
 * Features: High-Stiffness Argon2id Encryption, Data Housekeeping Cleanup
 */

// 1. START SECURE SESSION HANDLING
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,                       // ◄ Fix this line right here
        'cookie_secure'   => isset($_SERVER['HTTPS']),   
        'use_strict_mode' => true,                       
        'cookie_samesite' => 'Strict'                    
    ]);
}

require_once __DIR__ . "/../config/db_connect.php";

// Block unauthorized users who haven't passed the OTP stage
if (!isset($_SESSION['reset_student_id']) || !isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    header("Location: forgot_password.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Security Exception Rule Break.");
    }

    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($password) && !empty($confirm_password)) {
        if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).{8,}$/", $password)) {
            $error = "❌ Weak Choice: Use at least 8 characters with a mix of letters and numbers.";
        } elseif ($password !== $confirm_password) {
            $error = "❌ Passwords do not match.";
        }

        if (empty($error)) {
            try {
                // Secure encryption deployment via Argon2id system mechanics
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

                // Update to the new password and clear out the single-use OTP fields
                $stmt = $pdo->prepare("UPDATE student SET password = :password, reset_otp = NULL, otp_expiry = NULL WHERE id = :id");
                $execution = $stmt->execute([
                    'password' => $hashed_password,
                    'id'       => $_SESSION['reset_student_id']
                ]);

                if ($execution) {
                    $success = true;
                    
                    // Clear the recovery data from the session state to prevent re-use vulnerabilities
                    unset($_SESSION['reset_student_id']);
                    unset($_SESSION['reset_hostel_roll']);
                    unset($_SESSION['otp_verified']);
                    unset($_SESSION['csrf_token']);
                } else {
                    $error = "❌ Failed to complete database password assignment operations.";
                }
            } catch (PDOException $e) {
                error_log("Password Reset Crash Module: " . $e->getMessage());
                $error = "⚠️ A core storage anomaly occurred updating transaction tables.";
            }
        }
    } else {
        $error = "❌ Please complete the mandatory security key entries.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Security Key Token Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center p-6 antialiased">

    <div class="w-full max-w-md bg-white border border-slate-200 p-8 rounded-3xl shadow-xl shadow-slate-200/50">

        <?php if ($success): ?>
            <div class="text-center py-4">
                <div class="text-5xl mb-4">🛡️</div>
                <h2 class="text-2xl font-bold text-emerald-600 tracking-tight">Password Updated</h2>
                <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                    Your profile security configurations have been successfully updated.
                </p>
                <a href="login.php" class="block w-full mt-6 bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3 rounded-xl transition-all shadow-md">
                    🔐 Access Login Portal
                </a>
            </div>
        <?php else: ?>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Set New Password</h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Establish a strong, distinct new system access key.</p>
            </div>

            <form id="resetForm" method="POST" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="space-y-1.5 relative">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">New Password</label>
                    <input type="password" id="pass" name="password" placeholder="Min 8 characters mixed" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    <button type="button" id="toggleP" onclick="toggleField('pass', 'toggleP')" class="absolute right-4 top-8 text-sm select-none focus:outline-none">👁️</button>
                </div>

                <div class="space-y-1.5 relative">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Confirm New Password</label>
                    <input type="password" id="cpass" name="confirm_password" placeholder="Confirm character sequence" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    <button type="button" id="toggleCP" onclick="toggleField('cpass', 'toggleCP')" class="absolute right-4 top-8 text-sm select-none focus:outline-none">👁️</button>
                </div>

                <button type="submit" id="submitBtn" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3.5 rounded-xl transition-colors shadow-md">
                    Update Password Account
                </button>
            </form>

            <?php if (!empty($error)): ?>
                <div class="bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold p-3.5 rounded-xl mt-5 text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function toggleField(inputFieldId, clickTargetIndicatorId) {
            const field = document.getElementById(inputFieldId);
            const btn = document.getElementById(clickTargetIndicatorId);
            const isPass = field.type === "password";
            field.type = isPass ? "text" : "password";
            btn.textContent = isPass ? "🙈" : "👁️";
        }

        document.getElementById("resetForm").addEventListener("submit", function(e) {
            const pass = document.getElementById("pass").value.trim();
            const cpass = document.getElementById("cpass").value.trim();

            if (pass !== cpass) {
                alert("❌ Passwords do not match!");
                e.preventDefault();
                return;
            }
            if (!/(?=.*[A-Za-z])(?=.*\d).{8,}/.test(pass)) {
                alert("❌ Password choice must have at least 8 characters mixing alphanumeric values.");
                e.preventDefault();
                return;
            }

            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.className = "w-full bg-slate-400 text-white font-semibold text-sm py-3.5 rounded-xl cursor-not-allowed text-center animate-pulse";
            btn.innerHTML = "Rewriting Security Credentials...";
        });
    </script>
</body>
</html>