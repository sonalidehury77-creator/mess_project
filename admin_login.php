<?php
session_start();
include("config/db_connect.php");

/* =========================
   IF ALREADY LOGGED IN
========================= */

if (isset($_SESSION['admin'])) {
    header("Location: admin_dashboard.php");
    exit();
}

/* =========================
   LOGIN ATTEMPT PROTECTION
========================= */

if (!isset($_SESSION['admin_attempt'])) {
    $_SESSION['admin_attempt'] = 0;
}

if ($_SESSION['admin_attempt'] >= 5) {

    die("<h2 style='text-align:center;color:red;'>
        🚫 Too many failed login attempts.<br>
        Try again later.
    </h2>");
}

/* =========================
   LOGIN PROCESS
========================= */

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    /* EMPTY CHECK */

    if (empty($username) || empty($password)) {

        $error = "⚠ Please fill all fields";
    } else {

        /* PREPARED STATEMENT */

        $stmt = $conn->prepare(
            "SELECT * FROM admin 
             WHERE username=? 
             LIMIT 1"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        /* USER EXISTS */

        if ($result && $result->num_rows === 1) {

            $admin = $result->fetch_assoc();

            /* PASSWORD VERIFY */

            if (password_verify(
                $password,
                $admin['password']
            )) {

                session_regenerate_id(true);

                $_SESSION['admin'] =
                    $admin['username'];

                /* RESET ATTEMPT */

                $_SESSION['admin_attempt'] = 0;

                /* SESSION TIME */

                $_SESSION['admin_login_time'] =
                    time();

                header(
                    "Location: admin_dashboard.php"
                );
                exit();
            } else {

                $_SESSION['admin_attempt']++;

                $error = "❌ Wrong password";
            }
        } else {

            $_SESSION['admin_attempt']++;

            $error = "❌ Admin not found";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Admin Login</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg,
                    #667eea,
                    #764ba2);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {

            background: white;
            padding: 30px;
            width: 350px;
            border-radius: 15px;

            box-shadow:
                0px 10px 30px rgba(0, 0, 0, 0.3);

            text-align: center;

        }

        .input-box {

            text-align: left;
            margin-bottom: 15px;

        }

        .input-box input {

            width: 100%;
            padding: 10px;

            border-radius: 8px;
            border: 1px solid #ccc;

        }

        button {

            width: 100%;
            padding: 10px;

            background:
                linear-gradient(135deg,
                    #007bff,
                    #00c6ff);

            color: white;

            border: none;
            border-radius: 10px;

            font-size: 16px;
            cursor: pointer;

        }

        .error {

            color: red;
            margin-top: 10px;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>👩‍💼 Admin Login</h2>

        <form method="POST">

            <div class="input-box">

                <label>Username</label>

                <input
                    type="text"
                    name="username"
                    required>

            </div>

            <div class="input-box">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    required>

            </div>

            <button type="submit">

                Login

            </button>

        </form>

        <?php if (!empty($error)) { ?>

            <p class="error">

                <?php echo $error; ?>

            </p>

        <?php } ?>

        <p style="margin-top:10px;color:#555;">

            🔒 Secure Admin Access

        </p>

    </div>

</body>

</html>