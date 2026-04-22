<?php
session_start();
include("config/db_connect.php");

/* =========================
   IF ALREADY LOGGED IN
========================= */

if (isset($_SESSION['hostel_roll'])) {

    header("Location: student_dashboard.php");
    exit();
}

/* =========================
   LOGIN LIMIT SYSTEM
========================= */

if (!isset($_SESSION['login_attempts'])) {

    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

/* Lock after 5 attempts */

if ($_SESSION['login_attempts'] >= 5) {

    $wait =
        (time() - $_SESSION['last_attempt']);

    if ($wait < 600) { // 10 minutes

        die("<h2 style='color:red;text-align:center;'>
⛔ Too many login attempts.<br>
Try again after 10 minutes.
</h2>");
    } else {

        $_SESSION['login_attempts'] = 0;
    }
}

$error = "";
$blocked_message = "";

/* =========================
   LOGIN PROCESS
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $roll =
        strtoupper(
            trim($_POST['hostel_roll'])
        );

    $password =
        trim($_POST['password']);

    /* =========================
   FETCH STUDENT
========================= */

    $stmt = $conn->prepare(

        "SELECT *

FROM student

WHERE hostel_roll=?"

    );

    $stmt->bind_param("s", $roll);

    $stmt->execute();

    $result =
        $stmt->get_result();

    /* =========================
   STUDENT EXISTS
========================= */

    if ($result->num_rows > 0) {

        $student =
            $result->fetch_assoc();

        /* =========================
   BLOCK CHECK
========================= */

        if (($student['status']
            ?? 'active') == 'blocked') {

            $reason =
                !empty($student['block_reason'])

                ? $student['block_reason']

                : "Please contact hostel office.";

            $blocked_message = "

🚫 <b>Your Account is BLOCKED</b><br><br>

<b>Reason:</b> $reason <br><br>

📞 <b>Contact Hostel Office</b><br>

Office Time: 9:00 AM – 5:00 PM<br>

Phone: +91-9178422033<br>

Email: hosteloffice@email.com

";
        } else {

            /* =========================
   PASSWORD CHECK
========================= */

            if (password_verify(
                $password,
                $student['password']
            )) {

                /* Reset attempts */

                $_SESSION['login_attempts'] = 0;

                /* Secure Session */

                session_regenerate_id(true);

                $_SESSION['hostel_roll']
                    = $student['hostel_roll'];

                $_SESSION['name']
                    = $student['name'];

                $_SESSION['photo']
                    = $student['photo'] ?? '';

                $_SESSION['login_time']
                    = time();

                /* Redirect */

                header(
                    "Location: student_dashboard.php"
                );

                exit();
            } else {

                $error = "❌ Wrong Password";

                $_SESSION['login_attempts']++;

                $_SESSION['last_attempt'] = time();
            }
        }
    } else {

        $error = "❌ Student not found";

        $_SESSION['login_attempts']++;

        $_SESSION['last_attempt'] = time();
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Student Login</title>

    <style>
        body {

            margin: 0;

            font-family: 'Segoe UI';

            background:
                linear-gradient(135deg,
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

            width: 360px;

            border-radius: 15px;

            box-shadow:
                0px 10px 30px rgba(0, 0, 0, 0.3);

            text-align: center;

        }

        h2 {

            margin-bottom: 20px;

        }

        .input-box {

            text-align: left;

            margin-bottom: 15px;

        }

        .input-box label {

            font-weight: bold;

            font-size: 14px;

        }

        .input-box input {

            width: 100%;

            padding: 10px;

            margin-top: 5px;

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

        button:hover {

            transform: scale(1.05);

        }

        .error {

            background: #ffe6e6;

            color: #b30000;

            padding: 10px;

            margin-top: 10px;

            border-radius: 8px;

            font-weight: bold;

        }

        .blocked {

            background: #fff3cd;

            color: #856404;

            padding: 15px;

            margin-top: 15px;

            border-radius: 10px;

            border: 1px solid #ffeeba;

            text-align: left;

            font-size: 14px;

        }

        .note {

            margin-top: 10px;

            font-size: 13px;

            color: #555;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>🎓 Student Login</h2>

        <form method="POST">

            <div class="input-box">

                <label>Hostel Roll</label>

                <input
                    type="text"
                    name="hostel_roll"
                    required
                    oninput="
this.value=
this.value.toUpperCase()
">

            </div>

            <div class="input-box">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    required>

            </div>

            <button type="submit">

                🔐 Login

            </button>

        </form>

        <?php if (!empty($error)) { ?>

            <div class="error">

                <?php echo $error; ?>

            </div>

        <?php } ?>

        <?php if (!empty($blocked_message)) { ?>

            <div class="blocked">

                <?php echo $blocked_message; ?>

            </div>

        <?php } ?>

        <p class="note">

            💡 If your account is blocked,
            please contact hostel office.

        </p>

    </div>

</body>

</html>