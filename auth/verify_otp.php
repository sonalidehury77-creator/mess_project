<?php
/**
 * 📲 Hostel Management System - OTP Validation Engine
 * Features: Chronological Expiry Control, Multi-Step Verification Checks
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

require_once __DIR__ . "/../config/db_connect.php";

// Enforce structured workflow; direct hits are pushed out immediately
if (!isset($_SESSION['reset_student_id']) || !isset($_SESSION['reset_hostel_roll'])) {
    header("Location: forgot_password.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Security Mismatch Failure.");
    }

    $input_otp = trim($_POST['otp'] ?? '');

    if (!empty($input_otp)) {
        try {
            // Fetch OTP parameters straight from the storage rows
            $stmt = $pdo->prepare("SELECT reset_otp, otp_expiry FROM student WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $_SESSION['reset_student_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student && !empty($student['reset_otp'])) {
                $current_time = time();
                $expiry_time  = strtotime($student['otp_expiry']);

                // Evaluate whether the verification window timing thresholds are clean
                if ($current_time > $expiry_time) {
                    $error = "❌ This OTP has expired. Please request a new verification code link.";
                } else {
                    // Direct balance evaluation matching checks
                    if (hash_equals($student['reset_otp'], $input_otp)) {
                        
                        // Set state flags confirming authorization for step 3
                        $_SESSION['otp_verified'] = true;
                        
                        header("Location: reset_password.php");
                        exit();
                    } else {
                        $error = "❌ Incorrect OTP Code. Please verify the code sent to your phone.";
                    }
                }
            } else {
                $error = "❌ Session invalid. Please initiate the pass recovery flow again.";
            }
        } catch (PDOException $e) {
            error_log("OTP Verification Pipeline Issue: " . $e->getMessage());
            $error = "⚠️ Critical backend infrastructure fault validating key logs.";
        }
    } else {
        $error = "❌ Please input the 6-digit code sequence.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP Code - Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center p-6 antialiased">

    <div class="w-full max-w-md bg-white border border-slate-200 p-8 rounded-3xl shadow-xl shadow-slate-200/50">
        
        <div class="flex flex-col items-center text-center mb-8">
            <div class="bg-teal-50 text-teal-600 w-12 h-12 flex items-center justify-center rounded-2xl font-bold text-xl mb-4">
                💬
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Enter OTP Code</h2>
            <p class="text-xs text-slate-500 font-medium mt-1">Provide the 6-digit authorization code received on your phone.</p>
            <div class="mt-2 text-xs font-semibold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full">
                Account ID: <?php echo htmlspecialchars($_SESSION['reset_hostel_roll']); ?>
            </div>
        </div>

        <form method="POST" autocomplete="off" id="otpForm" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="space-y-1.5">
                <label for="otp" class="text-xs font-bold text-slate-700 uppercase tracking-wider text-center block">6-Digit Verification Code</label>
                <input 
                    type="text" 
                    id="otp"
                    name="otp" 
                    maxlength="6"
                    placeholder="Enter Code" 
                    required 
                    oninput="this.value = this.value.replace(/[^0-9]/g,'')"
                    class="w-full text-center px-4 py-3.5 tracking-[0.5em] text-lg font-bold border border-slate-200 rounded-xl focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50"
                >
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3.5 rounded-xl transition-all duration-200 shadow-md">
                Verify OTP Sequence
            </button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold p-3.5 rounded-xl mt-5 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 pt-5 border-t border-slate-100 text-center text-xs font-medium text-slate-500">
            Did not receive any text code? <a href="forgot_password.php" class="text-indigo-600 font-semibold hover:underline">Resend New Request</a>
        </div>
    </div>

    <script>
        document.getElementById("otpForm").addEventListener("submit", function() {
            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.className = "w-full bg-slate-400 text-white font-semibold text-sm py-3.5 rounded-xl cursor-not-allowed text-center animate-pulse";
            btn.innerHTML = "Authenticating Code String...";
        });
    </script>
</body>
</html>