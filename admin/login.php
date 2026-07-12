<?php

/**
 * 🔐 Hostel Management System - Admin Login Gateway
 * Features: Time-Based Brute-Force Lockout, CSRF Protection, Clean Messaging
 */

// 1. START SECURE SESSION HANDLING
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,                       // Blocks JavaScript access to session cookies
        'cookie_secure'   => isset($_SERVER['HTTPS']),   // Restricts sessions to HTTPS networks only
        'use_strict_mode' => true,                       // Rejects uninitialized session IDs
        'cookie_samesite' => 'Strict'                    // Mitigation defense against CSRF attacks
    ]);
}

// 2. INCLUDE DATABASE CONNECTION
require_once __DIR__ . "/../config/db_connect.php";

// 3. IF ADMIN IS ALREADY LOGGED IN, REDIRECT TO DASHBOARD
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

// 4. GENERATE CSRF SECURITY TOKEN FOR FORM VERIFICATION
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 5. BRUTE-FORCE PROTECTION (SMART TIMED LOCKOUT)
$MAX_ATTEMPTS = 5;
$LOCKOUT_TIME = 600; // 10 Minutes in seconds

if (!isset($_SESSION['admin_attempt'])) {
    $_SESSION['admin_attempt'] = 0;
    $_SESSION['admin_last_attempt'] = time();
}

// Check if the admin is currently locked out
if ($_SESSION['admin_attempt'] >= $MAX_ATTEMPTS) {
    $time_passed = time() - $_SESSION['admin_last_attempt'];
    if ($time_passed < $LOCKOUT_TIME) {
        $minutes_remaining = ceil(($LOCKOUT_TIME - $time_passed) / 60);
        http_response_code(429); // Status code: Too Many Requests
        die(sprintf("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Access Suspended</title><style>body{margin:0;padding:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F1F5F9;font-family:sans-serif;color:#0F172A;}.lockout-card{background:#FFFFFF;border:2px solid #EF4444;padding:40px;max-width:440px;width:100%;border-radius:16px;text-align:center;box-shadow:0 10px 25px -5px rgba(0,0,0,0.05);}h2{color:#DC2626;font-size:22px;margin-bottom:12px;font-weight:700;}p{color:#1E293B;font-size:15px;line-height:1.6;margin-bottom:16px;font-weight:500;}</style></head><body><div class='lockout-card'><div style='font-size:54px;margin-bottom:16px;'>🚫</div><h2>Admin Portal Locked</h2><p>Too many incorrect login attempts. For security reasons, this login gateway has been temporarily suspended.</p><div style='background:#FEF2F2;color:#991B1B;padding:0.75rem;border-radius:0.5rem;font-weight:600;font-size:0.875rem;'>Please wait %d minute(s) before trying again.</div></div></body></html>", $minutes_remaining));
    } else {
        // Lockout time expired, reset counters automatically
        $_SESSION['admin_attempt'] = 0;
    }
}

$error = "";

// 6. PROCESS SUBMITTED LOGIN FORM
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Verify CSRF Token to confirm request security
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Security Validation Failed: Invalid CSRF Token.");
    }

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "⚠️ Please fill in all fields completely.";
    } else {
        try {
            // Select only required rows instead of using '*' for maximum speed efficiency
            $stmt = $pdo->prepare("SELECT username, password FROM admin WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Identical error copy prevents attackers from guessing valid admin usernames
            $generic_error = "❌ Invalid Admin Username or Password.";

            if ($admin) {
                // Cryptographic password check
                if (password_verify($password, $admin['password'])) {

                    // Reset tracking counters on successful login
                    $_SESSION['admin_attempt'] = 0;

                    // Regenerate session ID to prevent session fixation exploits
                    session_regenerate_id(true);

                    $_SESSION['admin'] = $admin['username'];
                    $_SESSION['admin_login_time'] = time();

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $_SESSION['admin_attempt']++;
                    $_SESSION['admin_last_attempt'] = time();
                    $error = $generic_error;
                }
            } else {
                $_SESSION['admin_attempt']++;
                $_SESSION['admin_last_attempt'] = time();
                $error = $generic_error;
            }
        } catch (PDOException $e) {
            error_log("Admin Portal Database Failure: " . $e->getMessage());
            $error = "❌ A critical database error occurred on the server.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen flex flex-col justify-center items-center p-6 antialiased">
    <div class="w-full max-w-md mb-4 text-left">
        <a href="../index.php" class="inline-flex items-center gap-2 bg-white border border-slate-200/80 px-4 py-2 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:border-slate-300 shadow-sm transition-all duration-200">
            🔒 ← Back to Home
        </a>
    </div>

    <div class="w-full max-w-md bg-white border border-slate-200 p-8 rounded-3xl shadow-xl shadow-slate-200/50">

        <div class="flex flex-col items-center text-center mb-8">
            <div class="bg-slate-900 text-white w-12 h-12 flex items-center justify-center rounded-2xl font-bold text-xl mb-4 shadow-md">
                🛡️
            </div>
            <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Admin Portal</h2>
            <p class="text-xs text-slate-500 font-medium mt-1">Management Network Access Gateway</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold p-3.5 rounded-xl mb-5 text-center">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        

        <form method="POST" autocomplete="off" id="adminLoginForm" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="space-y-1.5">
                <label for="username" class="text-xs font-bold text-slate-700 uppercase tracking-wider">Admin Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your admin ID"
                    required
                    class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-slate-900 focus:ring-4 focus:ring-slate-100 transition-all">
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
                        class="w-full pl-4 pr-12 py-3 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-slate-900 focus:ring-4 focus:ring-slate-100 transition-all">
                    <button type="button" id="toggleVisibility" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-lg select-none hover:opacity-70 focus:outline-none">👁️</button>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold text-sm py-3.5 rounded-xl transition-all duration-200 shadow-md">
                Verify & Sign In
            </button>
        </form>

        <div class="mt-8 pt-4 border-t border-slate-100 text-center text-xs font-semibold text-slate-400 flex items-center justify-center gap-1.5">
            🔒 Secured Session Authorization
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

        document.getElementById("adminLoginForm").addEventListener("submit", function() {
            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.className = "w-full bg-slate-400 text-white font-semibold text-sm py-3.5 rounded-xl cursor-not-allowed text-center animate-pulse";
            btn.innerHTML = "Authenticating Admin Identity...";
        });
    </script>
</body>

</html>