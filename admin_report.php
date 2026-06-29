<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

/* ============================
DATE SELECT
============================ */

$selected_date =
    $_GET['date']
    ?? date('Y-m-d', strtotime('+1 day'));

$day_name =
    date('l', strtotime($selected_date));

/* ============================
COUNT MEALS
============================ */

$stmt = $conn->prepare("

SELECT

COUNT(CASE WHEN breakfast=1 THEN 1 END) AS breakfast_count,

COUNT(CASE WHEN lunch_type='veg' THEN 1 END) AS veg_lunch,
COUNT(CASE WHEN lunch_type='nonveg' THEN 1 END) AS nonveg_lunch,

COUNT(CASE WHEN dinner_type='veg' THEN 1 END) AS veg_dinner,
COUNT(CASE WHEN dinner_type='nonveg' THEN 1 END) AS nonveg_dinner,

COUNT(CASE WHEN base='rice' THEN 1 END) AS rice_count,
COUNT(CASE WHEN base='roti' THEN 1 END) AS roti_count,

COUNT(*) AS total_students

FROM meals
WHERE date=?

");

$stmt->bind_param("s", $selected_date);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

/* SAFETY */
foreach ($data as $k => $v) {
    $data[$k] = $v ?? 0;
}

/* CHECK NO DATA */
$no_data = ($data['total_students'] == 0);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Daily Meal Report</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #eef2f7;
            margin: 0;
        }

        .header {
            background: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 22px;
        }

        .top {
            text-align: center;
            padding: 15px;
            background: white;
        }

        input {
            padding: 8px;
            font-size: 15px;
        }

        button {
            padding: 8px 14px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .container {
            width: 95%;
            margin: 20px auto;
        }

        .section-title {
            text-align: left;
            margin: 10px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
        }

        .card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,.1);
        }

        .card h2 {
            margin: 5px 0;
            color: #007bff;
        }

        .highlight {
            background: #d4edda;
        }

        .warning {
            text-align: center;
            color: red;
            margin-top: 20px;
            font-weight: bold;
        }

        .back {
            display: block;
            width: 220px;
            margin: 30px auto;
            text-align: center;
            padding: 10px;
            background: #333;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>

</head>

<body>

<div class="header">
    📊 Daily Meal Report — <?php echo date("d M Y", strtotime($selected_date)); ?> (<?php echo $day_name; ?>)
</div>

<div class="top">
    <form method="GET">
        <input type="date" name="date" value="<?php echo $selected_date; ?>">
        <button>🔍 Load Report</button>
    </form>
</div>

<div class="container">

<?php if ($no_data) { ?>

    <p class="warning">⚠ No meal data found for this date</p>

<?php } else { ?>

    <!-- BREAKFAST -->
    <div class="section-title">🍽 Breakfast</div>
    <div class="grid">
        <div class="card highlight">
            Total Breakfast
            <h2><?php echo $data['breakfast_count']; ?></h2>
        </div>
    </div>

    <!-- LUNCH -->
    <div class="section-title">🍛 Lunch</div>
    <div class="grid">
        <div class="card">
            Veg Meals
            <h2><?php echo $data['veg_lunch']; ?></h2>
        </div>

        <div class="card">
            Non-Veg Meals
            <h2><?php echo $data['nonveg_lunch']; ?></h2>
        </div>
    </div>

    <!-- DINNER -->
    <div class="section-title">🌙 Dinner</div>
    <div class="grid">
        <div class="card">
            Veg Meals
            <h2><?php echo $data['veg_dinner']; ?></h2>
        </div>

        <div class="card">
            Non-Veg Meals
            <h2><?php echo $data['nonveg_dinner']; ?></h2>
        </div>
    </div>

    <!-- BASE -->
    <div class="section-title">🍚 Food Base Requirement</div>
    <div class="grid">
        <div class="card">
            Rice Required
            <h2><?php echo $data['rice_count']; ?></h2>
        </div>

        <div class="card">
            Roti Required
            <h2><?php echo $data['roti_count']; ?></h2>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="section-title">📊 Overall Summary</div>
    <div class="grid">
        <div class="card highlight">
            Total Students
            <h2><?php echo $data['total_students']; ?></h2>
        </div>
    </div>

<?php } ?>

</div>

<a href="admin_dashboard.php" class="back">
    ⬅ Back Dashboard
</a>

</body>

</html>