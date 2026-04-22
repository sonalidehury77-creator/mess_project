<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* =========================
   MONTH FILTER
========================= */

$selected_month = $_GET['month'] ?? '';

$month_condition = "";
$params = [];
$types = "";

if ($selected_month != "") {

    $month_condition =
        "AND MONTH(date)=?";

    $params[] = $selected_month;
    $types .= "i";
}

/* =========================
   TOTAL STUDENTS
========================= */

$query = "
SELECT COUNT(DISTINCT hostel_roll)
as total_students
FROM meals
WHERE 1 $month_condition
";

$stmt = $conn->prepare($query);

if ($types != "")
    $stmt->bind_param($types, ...$params);

$stmt->execute();

$students =
    $stmt->get_result()
        ->fetch_assoc()['total_students'] ?? 0;

/* =========================
   TOTAL MEALS
========================= */

$query = "
SELECT
SUM(breakfast) as breakfast,
SUM(lunch) as lunch,
SUM(dinner) as dinner
FROM meals
WHERE 1 $month_condition
";

$stmt = $conn->prepare($query);

if ($types != "")
    $stmt->bind_param($types, ...$params);

$stmt->execute();

$meal =
    $stmt->get_result()
    ->fetch_assoc();

$total_meals =
    ($meal['breakfast'] ?? 0) +
    ($meal['lunch'] ?? 0) +
    ($meal['dinner'] ?? 0);

/* =========================
   VEG vs NONVEG
========================= */

$query = "
SELECT
SUM(lunch_type='veg') as veg_lunch,
SUM(lunch_type='nonveg') as nonveg_lunch,
SUM(dinner_type='veg') as veg_dinner,
SUM(dinner_type='nonveg') as nonveg_dinner
FROM meals
WHERE 1 $month_condition
";

$stmt = $conn->prepare($query);

if ($types != "")
    $stmt->bind_param($types, ...$params);

$stmt->execute();

$type =
    $stmt->get_result()
    ->fetch_assoc();

$veg_total =
    ($type['veg_lunch'] ?? 0) +
    ($type['veg_dinner'] ?? 0);

$nonveg_total =
    ($type['nonveg_lunch'] ?? 0) +
    ($type['nonveg_dinner'] ?? 0);

/* =========================
   DAILY REVENUE
========================= */

$total_money = 0;

$query = "
SELECT
meals.date,

SUM(
    IF(meals.breakfast=1,15,0) +

    IF(meals.lunch_type='veg',
        menu.lunch_veg_price,
        IF(meals.lunch_type='nonveg',
            menu.lunch_nonveg_price,0)
    ) +

    IF(meals.dinner_type='veg',
        menu.dinner_veg_price,
        IF(meals.dinner_type='nonveg',
            menu.dinner_nonveg_price,0)
    )
) as daily_total

FROM meals
JOIN menu
ON meals.day = menu.day

WHERE 1 $month_condition

GROUP BY meals.date
ORDER BY meals.date
";

$stmt = $conn->prepare($query);

if ($types != "")
    $stmt->bind_param($types, ...$params);

$stmt->execute();

$result =
    $stmt->get_result();

$days = [];
$daily_amount = [];

while ($row =
    $result->fetch_assoc()
) {

    $days[] =
        date(
            "d M",
            strtotime($row['date'])
        );

    $daily_amount[] =
        $row['daily_total'];

    $total_money +=
        $row['daily_total'];
}

/* =========================
   AVERAGE MEALS
========================= */

$avg_meals = 0;

if ($students > 0) {

    $avg_meals =
        round(
            $total_meals / $students,
            2
        );
}

/* =========================
   MOST POPULAR TYPE
========================= */

$top_type =
    ($veg_total > $nonveg_total)
    ? "Veg"
    : "Non-Veg";

?>

<!DOCTYPE html>
<html>

<head>

    <title>Advanced Analytics</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            text-align: center;
        }

        .card {
            display: inline-block;
            width: 230px;
            margin: 15px;
            padding: 20px;
            border-radius: 12px;
            color: white;
        }

        .blue {
            background: #007bff;
        }

        .green {
            background: #28a745;
        }

        .orange {
            background: #fd7e14;
        }

        .red {
            background: #dc3545;
        }

        .purple {
            background: #6f42c1;
        }

        select,
        button {
            padding: 8px;
            margin: 5px;
        }

        .chart-box {
            width: 85%;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
        }

        .back {
            display: inline-block;
            margin: 20px;
            padding: 10px 15px;
            background: black;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }
    </style>

</head>

<body>

    <h2>📊 Advanced Analytics Dashboard</h2>

    <!-- FILTER -->

    <form method="GET">

        <label><b>Select Month:</b></label>

        <select name="month">

            <option value="">All</option>

            <?php
            for ($m = 1; $m <= 12; $m++) {

                $val =
                    str_pad($m, 2, "0", STR_PAD_LEFT);

                $selected =
                    ($selected_month == $val)
                    ? "selected" : "";

                echo "<option
value='$val'
$selected>

" . date(
                    "F",
                    mktime(0, 0, 0, $m, 1)
                ) . "

</option>";
            }
            ?>

        </select>

        <button>Filter</button>

    </form>

    <!-- CARDS -->

    <div class="card blue">
        <h3>👥 Students</h3>
        <h1><?php echo $students; ?></h1>
    </div>

    <div class="card green">
        <h3>🍽 Total Meals</h3>
        <h1><?php echo $total_meals; ?></h1>
    </div>

    <div class="card orange">
        <h3>🥗 Veg Meals</h3>
        <h1><?php echo $veg_total; ?></h1>
    </div>

    <div class="card red">
        <h3>🍗 NonVeg Meals</h3>
        <h1><?php echo $nonveg_total; ?></h1>
    </div>

    <div class="card purple">
        <h3>💰 Revenue</h3>
        <h1>₹ <?php echo $total_money; ?></h1>
    </div>

    <div class="card blue">
        <h3>📊 Avg Meals/Student</h3>
        <h1><?php echo $avg_meals; ?></h1>
    </div>

    <div class="card green">
        <h3>🏆 Popular Type</h3>
        <h1><?php echo $top_type; ?></h1>
    </div>

    <!-- GRAPH -->

    <div class="chart-box">

        <h3>📈 Daily Revenue</h3>

        <canvas id="chart"></canvas>

    </div>

    <script>
        new Chart(
            document.getElementById("chart"), {
                type: 'line',

                data: {

                    labels: <?php
                            echo json_encode($days);
                            ?>,

                    datasets: [{

                        label: 'Daily Revenue ₹',

                        data: <?php
                                echo json_encode($daily_amount);
                                ?>,

                        borderWidth: 3,
                        tension: 0.3

                    }]

                }

            });
    </script>

    <a
        href="admin_dashboard.php"
        class="back">

        ⬅ Back

    </a>

</body>

</html>