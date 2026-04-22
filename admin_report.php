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

COUNT(CASE WHEN breakfast=1 THEN 1 END)
AS breakfast_count,

COUNT(CASE WHEN lunch_type='veg' THEN 1 END)
AS veg_lunch,

COUNT(CASE WHEN lunch_type='nonveg' THEN 1 END)
AS nonveg_lunch,

COUNT(CASE WHEN dinner_type='veg' THEN 1 END)
AS veg_dinner,

COUNT(CASE WHEN dinner_type='nonveg' THEN 1 END)
AS nonveg_dinner,

COUNT(CASE WHEN base='roti' THEN 1 END)
AS roti_count,

COUNT(CASE WHEN base='rice' THEN 1 END)
AS rice_count,

COUNT(*) AS total_students

FROM meals

WHERE date=?

");

$stmt->bind_param("s", $selected_date);
$stmt->execute();

$data =
    $stmt->get_result()->fetch_assoc();

/* SAFETY */

foreach ($data as $k => $v) {

    $data[$k] = $v ?? 0;
}

/* ============================
COST CALCULATION
SPECIAL SUPPORT
============================ */

$total_cost = 0;

$stmt2 = $conn->prepare("

SELECT

meals.*,

menu.is_special,

menu.lunch_veg_price,
menu.lunch_nonveg_price,

menu.dinner_veg_price,
menu.dinner_nonveg_price,

menu.special_lunch_veg_price,
menu.special_lunch_nonveg_price,

menu.special_dinner_veg_price,
menu.special_dinner_nonveg_price

FROM meals

LEFT JOIN menu

ON (

(menu.is_special=1
AND meals.date=menu.special_date)

OR

(menu.is_special=0
AND meals.day=menu.day)

)

WHERE meals.date=?

");

$stmt2->bind_param("s", $selected_date);
$stmt2->execute();

$res = $stmt2->get_result();

while ($row = $res->fetch_assoc()) {

    /* BREAKFAST */

    if (!empty($row['breakfast'])) {

        $total_cost += 15;
    }

    /* SPECIAL PRICE */

    if ($row['is_special']) {

        $lunch_veg_price =
            $row['special_lunch_veg_price']
            ?: $row['lunch_veg_price'];

        $lunch_nonveg_price =
            $row['special_lunch_nonveg_price']
            ?: $row['lunch_nonveg_price'];

        $dinner_veg_price =
            $row['special_dinner_veg_price']
            ?: $row['dinner_veg_price'];

        $dinner_nonveg_price =
            $row['special_dinner_nonveg_price']
            ?: $row['dinner_nonveg_price'];
    } else {

        $lunch_veg_price =
            $row['lunch_veg_price'];

        $lunch_nonveg_price =
            $row['lunch_nonveg_price'];

        $dinner_veg_price =
            $row['dinner_veg_price'];

        $dinner_nonveg_price =
            $row['dinner_nonveg_price'];
    }

    /* LUNCH */

    if (!empty($row['lunch_type'])) {

        $total_cost +=
            ($row['lunch_type'] == 'veg')
            ? $lunch_veg_price
            : $lunch_nonveg_price;
    }

    /* DINNER */

    if (!empty($row['dinner_type'])) {

        $total_cost +=
            ($row['dinner_type'] == 'veg')
            ? $dinner_veg_price
            : $dinner_nonveg_price;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Meal Analytics</title>

    <style>
        body {

            font-family: Arial;
            background: #f4f6f9;
            text-align: center;

        }

        .container {

            width: 95%;
            margin: auto;

        }

        /* HEADER */

        .top {

            margin: 20px;

        }

        /* DATE SELECT */

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

        /* CARDS */

        .card {

            display: inline-block;

            width: 200px;

            margin: 10px;

            padding: 18px;

            background: white;

            border-radius: 12px;

            box-shadow: 0 5px 15px rgba(0, 0, 0, .1);

        }

        .card h3 {

            margin: 5px;

        }

        /* COST CARD */

        .cost {

            background: #d4edda;

        }

        /* BACK */

        .back {

            display: inline-block;

            margin: 25px;

            padding: 10px 16px;

            background: #333;

            color: white;

            text-decoration: none;

            border-radius: 8px;

        }
    </style>

</head>

<body>

    <h2>

        📊 Meal Analytics —
        <?php echo date("d M Y", strtotime($selected_date)); ?>

    </h2>

    <div class="top">

        <form method="GET">

            <input
                type="date"
                name="date"
                value="<?php echo $selected_date; ?>">

            <button>

                🔍 Load

            </button>

        </form>

    </div>

    <div class="container">

        <div class="card">

            🍽 Breakfast
            <h3><?php echo $data['breakfast_count']; ?></h3>

        </div>

        <div class="card">

            🥗 Veg Lunch
            <h3><?php echo $data['veg_lunch']; ?></h3>

        </div>

        <div class="card">

            🍗 NonVeg Lunch
            <h3><?php echo $data['nonveg_lunch']; ?></h3>

        </div>

        <div class="card">

            🌙 Veg Dinner
            <h3><?php echo $data['veg_dinner']; ?></h3>

        </div>

        <div class="card">

            🍗 NonVeg Dinner
            <h3><?php echo $data['nonveg_dinner']; ?></h3>

        </div>

        <div class="card">

            🍚 Rice
            <h3><?php echo $data['rice_count']; ?></h3>

        </div>

        <div class="card">

            🍞 Roti
            <h3><?php echo $data['roti_count']; ?></h3>

        </div>

        <div class="card">

            👨‍🎓 Total Students
            <h3><?php echo $data['total_students']; ?></h3>

        </div>

        <div class="card cost">

            💰 Estimated Cost

            <h2>
                ₹ <?php echo $total_cost; ?>
            </h2>

        </div>

    </div>

    <a
        href="admin_dashboard.php"
        class="back">

        ⬅ Back Dashboard

    </a>

</body>

</html>