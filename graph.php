<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['hostel_roll'])) {
    header("Location: login.html");
    exit();
}

$hostel_roll = $_SESSION['hostel_roll'];

/* =============================
   SETTINGS
============================= */

$BREAKFAST_PRICE = 15;
$MEAL_PRICE = 33;
$MIN_MEALS = 40;

/* =============================
   MONTH SELECTOR
============================= */

$selected_month = $_GET['month'] ?? date("Y-m");

$month_start = date("Y-m-01", strtotime($selected_month));
$month_end   = date("Y-m-t", strtotime($selected_month));

$current_month = date("F Y", strtotime($selected_month));

/* =============================
   FETCH DATA
============================= */

$sql = "
SELECT *
FROM meals
WHERE hostel_roll=?
AND date BETWEEN ? AND ?
ORDER BY date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $hostel_roll, $month_start, $month_end);
$stmt->execute();
$result = $stmt->get_result();

/* =============================
   VARIABLES
============================= */

$days = [];
$amounts = [];

$total = 0;
$meal_count = 0;
$day_used = 0;

/* =============================
   PROCESS DATA
============================= */

while ($row = $result->fetch_assoc()) {

    $day_total = 0;
    $has_meal = false;

    // Breakfast
    if (!empty($row['breakfast'])) {
        $day_total += $BREAKFAST_PRICE;
        $meal_count++;
        $has_meal = true;
    }

    // Lunch
    if (!empty($row['take_lunch']) && !empty($row['lunch_type'])) {
        $day_total += $MEAL_PRICE;
        $meal_count++;
        $has_meal = true;
    }

    // Dinner
    if (!empty($row['take_dinner']) && !empty($row['dinner_type'])) {
        $day_total += $MEAL_PRICE;
        $meal_count++;
        $has_meal = true;
    }

    if ($has_meal) {
        $day_used++;
    }

    $total += $day_total;

    $days[] = date("d M", strtotime($row['date']));
    $amounts[] = $day_total;
}

/* =============================
   CALCULATIONS
============================= */

$remaining = max(0, $MIN_MEALS - $meal_count);

$average = ($day_used > 0)
    ? round($total / $day_used, 2)
    : 0;

$progress = min(100, ($meal_count / $MIN_MEALS) * 100);

/* =============================
   SMART MESSAGE
============================= */

if ($meal_count >= $MIN_MEALS) {
    $message = "🎉 Minimum meals completed! Great job.";
    $msg_class = "success";
} elseif ($meal_count >= 30) {
    $message = "⚠ Almost there! Complete remaining meals.";
    $msg_class = "warning";
} else {
    $message = "❗ You still need $remaining meals.";
    $msg_class = "danger";
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Smart Meal Insights</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            text-align: center;
        }

        .container {
            width: 90%;
            margin: auto;
        }

        .card {
            display: inline-block;
            background: white;
            padding: 15px;
            margin: 10px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 170px;
            font-size: 14px;
        }

        .progress {
            width: 60%;
            margin: 20px auto;
            background: #ddd;
            border-radius: 20px;
            overflow: hidden;
        }

        .progress-bar {
            height: 25px;
            background: #28a745;
            color: white;
            line-height: 25px;
            font-weight: bold;
        }

        canvas {
            background: white;
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
        }

        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .danger { color: red; font-weight: bold; }

        .back {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 15px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>📊 Smart Meal Insights — <?php echo $current_month; ?></h2>

    <!-- Month Selector -->
    <form method="GET">
        <input type="month" name="month" value="<?php echo $selected_month; ?>">
        <button>View Month</button>
    </form>

    <!-- Summary -->
    <div>

        <div class="card">💰 Total<br><b>₹ <?php echo $total; ?></b></div>

        <div class="card">📅 Days Used<br><b><?php echo $day_used; ?></b></div>

        <div class="card">🍽 Meals Taken<br><b><?php echo $meal_count; ?></b></div>

        <div class="card">⚠ Remaining<br><b><?php echo $remaining; ?></b></div>

        <div class="card">📊 Avg / Day<br><b>₹ <?php echo $average; ?></b></div>

    </div>

    <!-- Progress -->
    <div class="progress">
        <div class="progress-bar" style="width: <?php echo $progress; ?>%;">
            <?php echo round($progress); ?>% Completed
        </div>
    </div>

    <p class="<?php echo $msg_class; ?>">
        <?php echo $message; ?>
    </p>

    <!-- Chart -->
    <canvas id="dailyChart"></canvas>

    <a href="student_dashboard.php" class="back">⬅ Back Dashboard</a>

</div>

<script>
new Chart(document.getElementById("dailyChart"), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($days); ?>,
        datasets: [{
            label: 'Daily Spending ₹',
            data: <?php echo json_encode($amounts); ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0,123,255,0.2)',
            fill: true,
            tension: 0.3
        }]
    }
});
</script>

</body>
</html>