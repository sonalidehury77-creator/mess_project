<?php
session_start();

/* ============================
   DESTROY SESSION SAFELY
============================ */

// Unset all session variables
$_SESSION = [];

// Destroy session cookie (VERY IMPORTANT)
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

// Destroy session completely
session_destroy();

/* ============================
   SECURITY HEADERS
============================ */

// Prevent caching (VERY IMPORTANT)
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Extra security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

?>

<!DOCTYPE html>
<html>

<head>

    <title>Logout</title>

    <meta http-equiv="refresh" content="5;url=login.html">

    <style>
        body {
            font-family: 'Segoe UI';
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .box {

            background: white;
            padding: 40px;
            border-radius: 15px;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);

            text-align: center;

            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {

            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

        h2 {
            color: #28a745;
        }

        .timer {
            font-size: 18px;
            color: #555;
            margin-top: 10px;
        }

        .btn {

            display: inline-block;

            margin-top: 20px;

            padding: 10px 18px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

        }

        .btn:hover {
            background: #0056b3;
        }
    </style>

    <script>
        /* ============================
   COUNTDOWN TIMER
============================ */

        let seconds = 5;

        function countdown() {

            let el = document.getElementById("count");

            if (seconds >= 0) {

                if (el) {
                    el.innerHTML = seconds;
                }

                seconds--;

            }

            if (seconds < 0) {

                window.location = "login.html";

            }

        }

        // Run timer
        setInterval(countdown, 1000);
    </script>

</head>

<body>

    <div class="box">

        <h2>
            ✅ Logged Out Successfully
        </h2>

        <p class="timer">

            Redirecting to login in

            <b id="count">5</b>

            seconds...

        </p>

        <a href="login.html" class="btn">
            🔐 Login Again
        </a>

    </div>

</body>

</html>