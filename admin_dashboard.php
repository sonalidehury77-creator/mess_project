<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

include("config/db_connect.php");

date_default_timezone_set("Asia/Kolkata");

/* ===============================
   SAFE DASHBOARD STATS
=============================== */

/* Students */

$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM student
");

$stmt->execute();

$student_count =
    $stmt->get_result()
        ->fetch_assoc()['total'];


/* Tomorrow Meals */

$tomorrow =
    date("Y-m-d", strtotime("+1 day"));

$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM meals
WHERE date=?
");

$stmt->bind_param("s", $tomorrow);
$stmt->execute();

$today_meals =
    $stmt->get_result()
        ->fetch_assoc()['total'];


/* Announcements */

$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM announcements
");

$stmt->execute();

$announcement_count =
    $stmt->get_result()
        ->fetch_assoc()['total'];


/* Bills */

$stmt = $conn->prepare("
SELECT COUNT(*) as total
FROM bills
");

$stmt->execute();

$bill_count =
    $stmt->get_result()
        ->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html>

<head>

    <title>Admin Dashboard</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
        }

        /* HEADER */

        .header {

            background: #007bff;

            color: white;

            padding: 15px;

            text-align: center;

            font-size: 22px;

            font-weight: bold;

        }

        /* CONTAINER */

        .container {

            width: 95%;

            max-width: 900px;

            margin: 30px auto;

        }

        /* STATS */

        .stats {

            display: flex;

            justify-content: center;

            flex-wrap: wrap;

            gap: 15px;

            margin-bottom: 25px;

        }

        .stat-card {

            background: white;

            padding: 15px;

            border-radius: 12px;

            width: 180px;

            box-shadow:
                0 5px 15px rgba(0, 0, 0, 0.1);

            text-align: center;

        }

        .stat-card h2 {

            margin: 5px 0;

            color: #007bff;

        }

        /* MAIN CARD */

        .card {

            background: white;

            padding: 25px;

            border-radius: 15px;

            box-shadow:
                0px 8px 20px rgba(0, 0, 0, 0.1);

            text-align: center;

        }

        .welcome {

            font-size: 18px;

            margin-bottom: 20px;

            color: #333;

        }

        /* MENU */

        .menu a {

            display: block;

            margin: 10px 0;

            padding: 12px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 16px;

            transition: 0.3s;

        }

        .menu a:hover {

            background: #0056b3;

            transform: scale(1.03);

        }

        .logout {

            background: #dc3545 !important;

        }

        .logout:hover {

            background: #b52a37 !important;

        }

        /* NOTE */

        .note {

            margin-top: 15px;

            font-size: 13px;

            color: gray;

        }
    </style>

</head>

<body>

    <div class="header">

        🏠 Hostel Mess Admin Panel

    </div>

    <div class="container">

        <!-- ===============================
     DASHBOARD STATS
================================ -->

        <div class="stats">

            <div class="stat-card">

                👨‍🎓 Students
                <h2><?php echo $student_count; ?></h2>

            </div>

            <div class="stat-card">

                🍽 Today's Meals
                <h2><?php echo $today_meals; ?></h2>

            </div>

            <div class="stat-card">

                📢 Announcements
                <h2><?php echo $announcement_count; ?></h2>

            </div>

            <div class="stat-card">

                💰 Bills Generated
                <h2><?php echo $bill_count; ?></h2>

            </div>

        </div>

        <!-- ===============================
     MAIN MENU
================================ -->

        <div class="card">

            <div class="welcome">

                👋 Welcome, Admin:
                <b>
                    <?php echo $_SESSION['admin']; ?>
                </b>

            </div>

            <div class="menu">

                <a href="add_student.php"
                    style="background:#28a745; font-weight:bold;">
                    ➕ Add New Student Admission
                </a>

                <a href="admin_menu.php">
                    📅 Manage Weekly Menu
                </a>

                <a href="admin_students.php">
                    👨‍🎓 View Students
                </a>

                <a href="admin_report.php">
                    📊 Meal Reports
                </a>

                <a href="admin_analytics.php">
                    📈 Analytics Graph
                </a>

                <a href="admin_bill.php">
                    💰 Monthly Billing
                </a>

                <!-- NEW ANNOUNCEMENT FEATURES -->
                <a href="admin_manage_special_menu.php">
                    🎉 Manage Special Meals
                </a>

                <a href="admin_announcements.php">
                    📢 View / Edit Announcements
                </a>

                <!-- BILL SYSTEM -->

                <a href="admin_bill_history.php">
                    📄 Bill History
                </a>

                <a href="admin_logout.php"
                    class="logout">

                    🚪 Logout

                </a>

            </div>

            <div class="note">

                💡 Tip:
                Generate monthly bills after month ends
                and add announcements for students.

            </div>

        </div>

    </div>

</body>

</html>