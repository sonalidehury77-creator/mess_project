<?php
/**
 * 📲 Hostel Management System - Password Recovery Gateway
 * Features: Identity Verification, Secure OTP Generation, Expiry Tracking
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

// Redirect if already logged in
if (isset($_SESSION['hostel_roll'])) {
    header("Location: ../student/dashboard.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success_message = "";

/**
 * Helper function to interface with your mobile SMS network api provider
 */
function sendSMSViaGateway($phoneNumber, $otpCode) {
    // Log OTP locally in server storage for testing/debugging purposes
    error_log("SMS Gateway Simulation to [$phoneNumber]: Your verification OTP code is $otpCode");
    
    /* // Industry implementation template using cURL:
    $apiUrl = "https://api.yourprovider.com/send?apiKey=YOUR_KEY&to=" . urlencode($phoneNumber) . "&message=" . urlencode("Your Hostel Portal password reset OTP is: " . $otpCode);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    */
    return true; 
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Security Validation Failed: Invalid CSRF Form Token.");
    }

    $roll  = strtoupper(trim($_POST['hostel_roll'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($roll) && !empty($phone)) {
        try {
            // Check if the combination of Roll Number and Phone Number exists
            $stmt = $pdo->prepare("SELECT id, hostel_roll, phone, status FROM student WHERE hostel_roll = :roll AND phone = :phone LIMIT 1");
            $stmt->execute(['roll' => $roll, 'phone' => $phone]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                if ($student['status'] === 'blocked') {
                    $error = "🚫 Access Restricted. This account is currently suspended.";
                } else {
                    // Generate a strong, secure 6-digit numerical OTP code
                    $otp = (string)random_int(100000, 999999);
                    
                    // Set an expiry window of exactly 10 minutes from now
                    $expiry_time = date('Y-m-d H:i:s', time() + 600);

                    // Alter database table schema to save OTP details dynamically
                    // ALTER TABLE student ADD COLUMN reset_otp VARCHAR(6) NULL, ADD COLUMN otp_expiry DATETIME NULL;
                    $updateStmt = $pdo->prepare("UPDATE student SET reset_otp = :otp, otp_expiry = :expiry WHERE id = :id");
                    $updateStmt->execute([
                        'otp'    => $otp,
                        'expiry' => $expiry_time,
                        'id'     => $student['id']
                    ]);

                    // Fire the SMS request payload framework link
                    sendSMSViaGateway($student['phone'], $otp);

                    // Track temporary verification data in user session state safely
                    $_SESSION['reset_student_id'] = $student['id'];
                    $_SESSION['reset_hostel_roll'] = $student['hostel_roll'];
                    $_SESSION['otp_verified'] = false;

                    header("Location: verify_otp.php");
                    exit();
                }
            } else {
                // Generic feedback prevents malicious attackers from crawling registered phone combinations
                $error = "❌ No matching account profile records discovered.";
            }
        } catch (PDOException $e) {
            error_log("Forgot Password Matrix Fault: " . $e->getMessage());
            $error = "⚠️ A structural system error happened while verifying your credentials.";
        }
    } else {
        $error = "❌ Please fulfill all input field metrics.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center p-6 antialiased">

    <div class="w-full max-w-md bg-white border border-slate-200 p-8 rounded-3xl shadow-xl shadow-slate-200/50">
        
        <div class="flex flex-col items-center text-center mb-8">
            <div class="bg-indigo-50 text-indigo-600 w-12 h-12 flex items-center justify-center rounded-2xl font-bold text-xl mb-4">
                🔑
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Recover Password</h2>
            <p class="text-xs text-slate-500 font-medium mt-1">We will send a 6-digit OTP verification code to your phone.</p>
        </div>

        <form method="POST" autocomplete="off" id="forgotForm" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="space-y-1.5">
                <label for="hostel_roll" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Hostel Roll Number</label>
                <input 
                    type="text" 
                    id="hostel_roll"
                    name="hostel_roll" 
                    placeholder="e.g., H-102" 
                    required 
                    oninput="this.value = this.value.toUpperCase()"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50"
                >
            </div>

            <div class="space-y-1.5">
                <label for="phone" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Registered Phone Number</label>
                <input 
                    type="text" 
                    id="phone"
                    name="phone" 
                    maxlength="10"
                    placeholder="Enter 10-digit mobile number" 
                    required 
                    oninput="this.value = this.value.replace(/[^0-9]/g,'')"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50"
                >
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3.5 rounded-xl transition-all duration-200 shadow-md">
                Send OTP Verification
            </button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold p-3.5 rounded-xl mt-5 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 pt-5 border-t border-slate-100 text-center text-xs font-medium text-slate-500">
            Remembered your access keys? <a href="login.php" class="text-indigo-600 font-semibold hover:underline">Back to Login</a>
        </div>
    </div>

    <script>
        document.getElementById("forgotForm").addEventListener("submit", function() {
            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.className = "w-full bg-slate-400 text-white font-semibold text-sm py-3.5 rounded-xl cursor-not-allowed text-center animate-pulse";
            btn.innerHTML = "Verifying Identity Profile...";
        });
    </script>
</body>
</html>