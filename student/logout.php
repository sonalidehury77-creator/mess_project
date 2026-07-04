<?php
session_start();

/* ==========================================================================
   SECURE SESSION TEARDOWN
   ========================================================================== */

// Clear all active session variables
$_SESSION = [];

// Delete the session cookie on the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Completely destroy the session
session_destroy();

/* ==========================================================================
   SECURITY & BROWSER CACHE CONTROL
   ========================================================================== */

header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Successful</title>
    <meta http-equiv="refresh" content="5;url=../auth/login.php">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', -apple-system, sans-serif;
        }

        body {
            background-color: #0F172A;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
        }

        /* LOGOUT CARD LAYOUT */
        .logout-card {
            background: #FFFFFF;
            width: 100%;
            max-width: 420px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: cardReveal 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardReveal {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* ANIMATED STATUS COMPONENT */
        .icon-container {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 24px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .progress-ring {
            position: absolute;
            top: 0;
            left: 0;
            transform: rotate(-90deg);
        }

        .progress-ring__circle {
            transition: stroke-dashoffset 1s linear;
            stroke-dasharray: 226.19; /* 2 * pi * r (r=36) */
            stroke-dashoffset: 0;
        }

        .check-svg {
            width: 32px;
            height: 32px;
            color: #10B981;
            z-index: 2;
            animation: checkPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
        }

        @keyframes checkPop {
            from {
                opacity: 0;
                transform: scale(0.5);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        h2 {
            font-size: 20px;
            color: #0F172A;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.01em;
        }

        .message {
            font-size: 14px;
            color: #64748B;
            line-height: 1.5;
            margin-bottom: 28px;
        }

        #count {
            color: #3B82F6;
            font-weight: 700;
        }

        /* REDIRECT BUTTON */
        .btn-redirect {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 12px 24px;
            background: #3B82F6;
            color: #FFFFFF;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            transition: all 0.15s ease;
        }

        .btn-redirect:hover {
            background: #2563EB;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>

<body>

    <div class="logout-card">

        <div class="icon-container">
            <svg class="progress-ring" width="80" height="80">
                <circle class="progress-ring__circle-bg" stroke="#F1F5F9" stroke-width="4" fill="transparent" r="36" cx="40" cy="40" />
                <circle id="ticker-ring" class="progress-ring__circle" stroke="#10B981" stroke-width="4" fill="transparent" r="36" cx="40" cy="40" />
            </svg>
            <svg class="check-svg" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h2>Securely Logged Out</h2>
        <p class="message">
            Your login session has ended successfully.<br>
            Redirecting to the login screen in <span id="count">5</span> seconds...
        </p>

        <a href="../auth/login.php" class="btn-redirect">
            🔐 Go to Login Portal Now
        </a>

    </div>

    <script>
        /* ==========================================================================
           COUNTDOWN TIMER & VISUAL TICKER RING ENGINE
           ========================================================================== */
        let secondsLeft = 5;
        const totalDuration = 5;

        const countDisplay = document.getElementById("count");
        const circle = document.getElementById("ticker-ring");
        const radius = circle.r.baseVal.value;
        const circumference = radius * 2 * Math.PI;

        function updateProgress() {
            if (countDisplay) {
                countDisplay.innerHTML = secondsLeft;
            }

            // Animate progress ring smoothly
            const offset = circumference - (secondsLeft / totalDuration) * circumference;
            circle.style.strokeDashoffset = offset;

            if (secondsLeft <= 0) {
                window.location.href = "../auth/login.php";
            } else {
                secondsLeft--;
            }
        }

        // Initialize and fire interval track
        updateProgress();
        setInterval(updateProgress, 1000);
    </script>

</body>

</html>