<?php
session_start();
include("config/db_connect.php");

/* =========================
   LOGIN CHECK
========================= */

if (!isset($_SESSION['hostel_roll'])) {

    header("Location: login.html");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$hostel_roll = $_SESSION['hostel_roll'];

$now = time();
$deadline = strtotime("22:00");

/* =========================
   DATE VALUES
========================= */

$day  = date("l", strtotime("+1 day"));
$date = date("Y-m-d", strtotime("+1 day"));

/* =========================
   CHECK LOCK STATUS
========================= */

$lockCheck = $conn->prepare("
    SELECT locked
    FROM meals
    WHERE hostel_roll=? 
    AND date=?
");

$lockCheck->bind_param("ss", $hostel_roll, $date);
$lockCheck->execute();

$lockResult = $lockCheck->get_result();

$isLocked = false;

if ($lockResult->num_rows > 0) {

    $rowLock = $lockResult->fetch_assoc();

    if (!empty($rowLock['locked'])) {

        $isLocked = true;
    }
}

/* =========================
   TIME OR LOCK BLOCK
========================= */

if ($now >= $deadline || $isLocked) {

    die("
    <h2 style='color:red; text-align:center;'>
        ⛔ Meal Locked! Cannot modify.
    </h2>

    <div style='text-align:center; margin-top:20px;'>

        <a href='meal.php'
        style='padding:10px 20px;
        background:#007bff;
        color:white;
        text-decoration:none;
        border-radius:8px;'>

        ⬅ Go Back

        </a>

    </div>");
}

/* =========================
   FORM DATA
========================= */

$breakfast = isset($_POST['breakfast']) ? 1 : 0;

$lunch  = isset($_POST['take_lunch']) ? 1 : 0;
$dinner = isset($_POST['take_dinner']) ? 1 : 0;

/* =========================
   TYPE VALIDATION
========================= */

$lunch_type = NULL;

if ($lunch) {

    if (
        isset($_POST['lunch_type']) &&
        in_array($_POST['lunch_type'], ['veg', 'nonveg'])
    ) {

        $lunch_type = $_POST['lunch_type'];
    }
}

$dinner_type = NULL;

if ($dinner) {

    if (
        isset($_POST['dinner_type']) &&
        in_array($_POST['dinner_type'], ['veg', 'nonveg'])
    ) {

        $dinner_type = $_POST['dinner_type'];
    }
}

/* =========================
   BASE OPTION
========================= */

$base = NULL;

if ($dinner) {

    if (
        isset($_POST['base']) &&
        in_array($_POST['base'], ['rice', 'roti'])
    ) {

        $base = $_POST['base'];
    }
}

/* =========================
   CHECK EXISTING RECORD
========================= */

$checkStmt = $conn->prepare("
    SELECT id
    FROM meals
    WHERE hostel_roll=? 
    AND date=?
");

$checkStmt->bind_param("ss", $hostel_roll, $date);
$checkStmt->execute();

$result = $checkStmt->get_result();

/* =========================
   UPDATE EXISTING
========================= */

if ($result->num_rows > 0) {

    $stmt = $conn->prepare("
        UPDATE meals SET

            day=?,
            breakfast=?,
            lunch=?,
            lunch_type=?,
            dinner=?,
            dinner_type=?,
            base=?

        WHERE hostel_roll=? 
        AND date=?
    ");

    $stmt->bind_param(
        "siisissss",

        $day,
        $breakfast,
        $lunch,
        $lunch_type,
        $dinner,
        $dinner_type,
        $base,
        $hostel_roll,
        $date
    );
}

/* =========================
   INSERT NEW
========================= */ else {

    $stmt = $conn->prepare("
        INSERT INTO meals

        (
            hostel_roll,
            day,
            breakfast,
            lunch,
            lunch_type,
            dinner,
            dinner_type,
            base,
            date,
            locked
        )

        VALUES

        (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
    ");

    $stmt->bind_param(
        "ssiisisss",

        $hostel_roll,
        $day,
        $breakfast,
        $lunch,
        $lunch_type,
        $dinner,
        $dinner_type,
        $base,
        $date
    );
}

/* =========================
   EXECUTE QUERY
========================= */

$success = $stmt->execute();

?>

<!DOCTYPE html>
<html>

<head>

    <title>Meal Saved</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background:
                linear-gradient(135deg, #ff9a9e, #fad0c4);
        }

        .container {

            max-width: 500px;

            margin: 80px auto;

            background: white;

            padding: 30px;

            border-radius: 20px;

            text-align: center;

        }

        .btn {

            padding: 12px 25px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 10px;

        }

        .summary {

            background: #f9f9f9;

            padding: 20px;

            margin-top: 20px;

            border-radius: 10px;

            text-align: left;

        }
    </style>

</head>

<body>

    <div class="container">

        <?php if ($success) { ?>

            <h1>✅ Meal Saved Successfully!</h1>

            <p>

                Hi <b>
                    <?php echo htmlspecialchars($_SESSION['name']); ?>
                </b>,

                your meal for

                <b><?php echo $day; ?></b>

                saved.

            </p>

            <div class="summary">

                <h3>🍽 Meal Summary</h3>

                <p>
                    Breakfast:
                    <?php echo $breakfast ? "Yes" : "No"; ?>
                </p>

                <p>
                    Lunch:
                    <?php echo $lunch ? ucfirst($lunch_type) : "No"; ?>
                </p>

                <p>
                    Dinner:
                    <?php echo $dinner ? ucfirst($dinner_type) : "No"; ?>
                </p>

                <p>
                    Base:
                    <?php echo $base ? ucfirst($base) : "-"; ?>
                </p>

            </div>

            <br>

            <a href="student_dashboard.php"
                class="btn">

                ⬅ Back to Dashboard

            </a>

        <?php } else { ?>

            <h2 style="color:red;">
                ❌ Failed to save meal
            </h2>

            <p>

                <?php echo $stmt->error; ?>

            </p>

            <a href="meal.php"
                class="btn">

                ⬅ Go Back

            </a>

        <?php } ?>

    </div>

</body>

</html>