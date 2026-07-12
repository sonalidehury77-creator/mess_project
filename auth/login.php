<?php
/**
 * 🔐 Hostel Management System - Student Login Gateway
 * Features: Brute-Force Rate Limiting, Cross-Site Request Forgery (CSRF) Protection
 */

// 1. START SECURE SESSION HANDLING (Relaxed for Local Server Compatibility)
if (session_status() === PHP_SESSION_NONE) {
    session_start(); 
}

// 2. INCLUDE DATABASE CONNECTION (Corrected path to match the rest of the authentication workflow)
require_once __DIR__ . "/../config/db_connect.php";

// 3. IF STUDENT IS ALREADY LOGGED IN, REDIRECT TO DASHBOARD
if (isset($_SESSION['hostel_roll'])) {
    header("Location: ../student/dashboard.php");
    exit();
}

// 4. GENERATE CSRF SECURITY TOKEN FOR FORM VERIFICATION
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$blocked_message = "";

// 5. BRUTE-FORCE PROTECTION (RATE LIMITING SYSTEM EVALUATION)
$MAX_ATTEMPTS  = 5;
$LOCKOUT_TIME  = 600; // 10 Minutes in seconds

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt']   = time();
}

// Check if the student is currently locked out before processing form
if ($_SESSION['login_attempts'] >= $MAX_ATTEMPTS) {
    $time_passed = time() - $_SESSION['last_attempt'];
    if ($time_passed < $LOCKOUT_TIME) {
        $minutes_remaining = ceil(($LOCKOUT_TIME - $time_passed) / 60);
        http_response_code(429); // Status code for Too Many Requests
        $error = "🚫 <b>Access Suspended Temporarily:</b> Too many incorrect login attempts from this browser window. Please wait " . $minutes_remaining . " minute(s) before trying again.";
    } else {
        // Lockout period finished, reset the system counters
        $_SESSION['login_attempts'] = 0;
    }
}

// 6. PROCESS SUBMITTED LOGIN FORM
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Block logic processing immediately if client is currently in a locked out cycle
    if (empty($error)) {
        
        // Dynamic Token Healing: If session sync dropped, re-sync automatically instead of crashing
        if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            // Re-sync local storage tokens instantly
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $error = "🔒 Security session synchronized safely. Please re-enter your password and click Sign In again.";
        } else {

            $roll     = strtoupper(trim($_POST['hostel_roll'] ?? ''));
            $password = trim($_POST['password'] ?? '');

            if (!empty($roll) && !empty($password)) {
                try {
                    $stmt = $pdo->prepare("SELECT hostel_roll, name, password, photo, status, block_reason FROM student WHERE hostel_roll = :roll LIMIT 1");
                    $stmt->execute(['roll' => $roll]);
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);

                    $generic_error_msg = "❌ Invalid Hostel Roll Number or Password.";

                    if ($student) {
                        // Removed security exploit flaw (plain text matching fallback) to fully rely on secure password_verify hashes
                        if (password_verify($password, $student['password'])) {
                            
                            // Check if admin has restricted this student account AFTER matching password
                            if ($student['status'] === 'blocked') {
                                $reason = !empty($student['block_reason']) ? $student['block_reason'] : "Please contact the hostel administrative desk.";
                                $blocked_message = "🚫 <b>Account Access Suspended</b><br><br><b>Reason:</b> " . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . "<br><br>📧 <b>Support Email:</b> hosteloffice@email.com";
                                $_SESSION['login_attempts'] = 0;
                            } else {
                                // Reset tracking on clear login match
                                $_SESSION['login_attempts'] = 0;

                                // Save properties directly into active session matrix
                                $_SESSION['hostel_roll'] = $student['hostel_roll'];
                                $_SESSION['name']        = $student['name'];
                                $_SESSION['photo']       = $student['photo'] ?? '';
                                $_SESSION['login_time']  = time();

                                // Force redirection to dashboard path
                                header("Location: ../student/dashboard.php");
                                exit();
                            }
                        } else {
                            $error = $generic_error_msg;
                        }
                    } else {
                        $error = $generic_error_msg;
                    }

                    // Increment bad attempt variables if any credential execution mismatch hits
                    if (!empty($error) && !isset($_POST['csrf_error_triggered'])) {
                        $_SESSION['login_attempts']++;
                        $_SESSION['last_attempt'] = time();
                    }
                } catch (PDOException $e) {
                    error_log("Database Login Error: " . $e->getMessage());
                    $error = "⚠️ A critical database execution issue occurred on the server.";
                }
            } else {
                $error = "❌ Please fill out all required fields.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>

<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center p-6 antialiased">

    <div class="w-full max-w-md mb-4 text-left">
        <a href="../index.php" class="inline-flex items-center gap-2 bg-white border border-slate-200/80 px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-indigo-600 hover:border-indigo-100 shadow-sm transition-all duration-200">
            ✨ ← Back to Home
        </a>
    </div>

    <div class="w-full max-w-md bg-white border border-slate-200 p-8 rounded-3xl shadow-xl shadow-slate-200/50">

        <div class="flex flex-col items-center text-center mb-8">
            <div class="bg-indigo-50 text-indigo-600 w-12 h-12 flex items-center justify-center rounded-2xl font-bold text-xl mb-4">
                🎓
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Student Login</h2>
            <p class="text-xs text-slate-500 font-medium mt-1">Please enter your credentials to open your portal.</p>
        </div>

        <form method="POST" action="" autocomplete="off" id="loginForm" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

            <div class="space-y-1.5">
                <label for="hostel_roll" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Hostel Roll Number</label>
                <input
                    type="text"
                    id="hostel_roll"
                    name="hostel_roll"
                    placeholder="e.g., 415"
                    value="<?php echo isset($_POST['hostel_roll']) ? htmlspecialchars($_POST['hostel_roll'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                    required
                    maxlength="20"
                    oninput="this.value = this.value.toUpperCase()"
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
            </div>

            <div class="space-y-1.5">
                <label for="password" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="your password"
                        required
                        maxlength="50"
                        class="w-full pl-4 pr-12 py-3 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    <button type="button" id="toggleVisibility" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-lg select-none hover:opacity-70 focus:outline-none">👁️</button>
                </div>
                
                <div class="text-center pt-1">
                    <a href="forgot_password.php" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline transition-colors">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" id="loginBtn" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3.5 rounded-xl transition-all duration-200 shadow-md">
                Sign In
            </button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold p-3.5 rounded-xl mt-5 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($blocked_message)): ?>
            <div class="bg-amber-50 border border-amber-200/60 text-amber-900 text-xs p-4 rounded-xl mt-5 leading-relaxed">
                <?php echo $blocked_message; ?>
            </div>
        <?php endif; ?>

        <div class="mt-8 pt-5 border-t border-slate-100 text-center text-xs font-medium text-slate-500">
            Don't have an account yet? <a href="register.php" class="text-indigo-600 font-semibold hover:underline">Register New Account</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const p = document.getElementById("password");
            const btn = document.getElementById("toggleVisibility");
            const isPass = p.type === "password";
            p.type = isPass ? "text" : "password";
            btn.textContent = isPass ? "🙈" : "👁️";
        }

        document.getElementById("loginForm").addEventListener("submit", function(e) {
            const btn = document.getElementById("loginBtn");
            btn.innerHTML = "Verifying Credentials...";
            btn.className = "w-full bg-slate-400 text-white font-semibold text-sm py-3.5 rounded-xl text-center animate-pulse";
            
            // Safety release mechanism prevents lock states during minor environment hangs
            setTimeout(function() {
                btn.style.pointerEvents = "auto";
            }, 2500);
        });
    </script>
</body>
</html>