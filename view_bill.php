<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* =========================
   GET PARAMETERS
========================= */

$roll  = $_GET['roll'];
$month = $_GET['month'];
$year  = $_GET['year'];

$breakfast_price = 15;
$meal_price = 33;
$min_meals = 40;

/* =========================
   FETCH STUDENT
========================= */

$stmt = $conn->prepare("
SELECT * FROM student
WHERE hostel_roll=?
");

$stmt->bind_param("s", $roll);
$stmt->execute();

$student =
    $stmt->get_result()->fetch_assoc();

/* =========================
   FETCH MEALS + MENU
========================= */

$stmt = $conn->prepare("

SELECT

meals.date,
meals.day,
meals.breakfast,
meals.lunch_type,
meals.dinner_type,

menu.is_special,

menu.lunch_veg_price,
menu.lunch_nonveg_price,
menu.dinner_veg_price,
menu.dinner_nonveg_price,

menu.special_lunch_veg_price,
menu.special_lunch_nonveg_price,
menu.special_dinner_veg_price,
menu.special_dinner_nonveg_price,

menu.special_date

FROM meals

LEFT JOIN menu
ON (
(menu.is_special = 1 AND meals.date = menu.special_date)
OR
(menu.is_special = 0 AND meals.day = menu.day)
)

WHERE meals.hostel_roll=?
AND MONTH(meals.date)=?
AND YEAR(meals.date)=?

ORDER BY meals.date ASC

");

$stmt->bind_param("sii", $roll, $month, $year);
$stmt->execute();

$result = $stmt->get_result();

/* =========================
   CALCULATIONS
========================= */

$breakfast_count = 0;
$lunch_count = 0;
$dinner_count = 0;

$breakfast_total = 0;
$lunch_total = 0;
$dinner_total = 0;

$total_amount = 0;

$rows = [];

while ($row = $result->fetch_assoc()) {

    $day_total = 0;

    /* SPECIAL PRICE SUPPORT */

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

    /* BREAKFAST */

    if ($row['breakfast']) {

        $breakfast_count++;
        $breakfast_total += $breakfast_price;

        $day_total += $breakfast_price;

        $b = "Yes";
    } else {

        $b = "No";
    }

    /* LUNCH */

    if (!empty($row['lunch_type'])) {

        $lunch_count++;

        $l = ucfirst($row['lunch_type']);

        if ($row['lunch_type'] == "veg") {

            $lunch_total += $lunch_veg_price;
            $day_total += $lunch_veg_price;
        } else {

            $lunch_total += $lunch_nonveg_price;
            $day_total += $lunch_nonveg_price;
        }
    } else {

        $l = "No";
    }

    /* DINNER */

    if (!empty($row['dinner_type'])) {

        $dinner_count++;

        $d = ucfirst($row['dinner_type']);

        if ($row['dinner_type'] == "veg") {

            $dinner_total += $dinner_veg_price;
            $day_total += $dinner_veg_price;
        } else {

            $dinner_total += $dinner_nonveg_price;
            $day_total += $dinner_nonveg_price;
        }
    } else {

        $d = "No";
    }

    $total_amount += $day_total;

    $rows[] = [

        'date' => $row['date'],
        'breakfast' => $b,
        'lunch' => $l,
        'dinner' => $d,
        'amount' => $day_total

    ];
}

/* =========================
   MINIMUM RULE
========================= */

$meal_total =
    $lunch_count + $dinner_count;

if ($meal_total < $min_meals) {

    $required_amount =
        $min_meals * $meal_price;

    if ($total_amount < $required_amount) {

        $final_total =
            $required_amount;

        $penalty_meals =
            $min_meals - $meal_total;
    } else {

        $final_total =
            $total_amount;

        $penalty_meals = 0;
    }
} else {

    $final_total =
        $total_amount;

    $penalty_meals = 0;
}

$month_name =
    date("F", mktime(0, 0, 0, $month, 1));

?>

<!DOCTYPE html>
<html>

<head>

    <title>Student Bill</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #eef2f7;
            margin: 0;
        }

        .invoice {

            width: 900px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .1);

        }

        .header {

            text-align: center;
            margin-bottom: 20px;

        }

        .info-grid {

            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;

        }

        table {

            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;

        }

        th {

            background: #007bff;
            color: white;
            padding: 10px;

        }

        td {

            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: center;

        }

        .summary {

            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;

        }

        .card {

            background: #f4f6f9;
            padding: 12px;
            border-radius: 8px;
            text-align: center;

        }

        .total-box {

            margin-top: 20px;
            font-size: 18px;
            text-align: right;

        }

        .action-bar {

            text-align: center;
            margin: 20px;

        }

        .btn {

            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;

        }

        .print {

            background: green;
            color: white;

        }

        .back {

            background: #333;
            color: white;

        }
    </style>

</head>

<body>

    <div class="invoice">

        <div class="header">

            <h2>🏫 Hostel Mess Invoice</h2>

            <h3>
                <?php echo $month_name . " " . $year; ?>
            </h3>

        </div>

        <div class="info-grid">

            <div>

                <b>Name:</b>
                <?php echo $student['name']; ?>

            </div>

            <div>

                <b>Roll:</b>
                <?php echo $student['hostel_roll']; ?>

            </div>

            <div>

                <b>Room:</b>
                <?php echo $student['room_number']; ?>

            </div>

            <div>

                <b>Total Meals:</b>
                <?php echo $meal_total; ?>

            </div>

        </div>

        <!-- DAILY TABLE -->

        <table>

            <tr>

                <th>Date</th>
                <th>Breakfast</th>
                <th>Lunch</th>
                <th>Dinner</th>
                <th>Amount ₹</th>

            </tr>

            <?php foreach ($rows as $r) { ?>

                <tr>

                    <td>
                        <?php echo date(
                            "d M",
                            strtotime($r['date'])
                        ); ?>
                    </td>

                    <td><?php echo $r['breakfast']; ?></td>

                    <td><?php echo $r['lunch']; ?></td>

                    <td><?php echo $r['dinner']; ?></td>

                    <td><?php echo $r['amount']; ?></td>

                </tr>

            <?php } ?>

        </table>

        <!-- SUMMARY -->

        <div class="summary">

            <div class="card">

                🍳 Breakfast
                <h3><?php echo $breakfast_count; ?></h3>

                ₹ <?php echo $breakfast_total; ?>

            </div>

            <div class="card">

                🍛 Lunch
                <h3><?php echo $lunch_count; ?></h3>

                ₹ <?php echo $lunch_total; ?>

            </div>

            <div class="card">

                🌙 Dinner
                <h3><?php echo $dinner_count; ?></h3>

                ₹ <?php echo $dinner_total; ?>

            </div>

        </div>

        <!-- TOTAL -->

        <div class="total-box">

            <p>

                ⚠ Minimum Meals:
                <?php echo $min_meals; ?>

            </p>

            <p>

                ❌ Penalty Meals:
                <?php echo $penalty_meals; ?>

            </p>

            <hr>

            <h2>

                Total Payable:

                <span style="color:green;">

                    ₹ <?php echo $final_total; ?>

                </span>

            </h2>

        </div>

    </div>

    <!-- ACTION BUTTONS -->

    <div class="action-bar">

        <button
            class="btn print"
            onclick="window.print()">

            🖨 Print Bill

        </button>

        <a
            href="admin_bill.php"
            class="btn back">

            ⬅ Back to Billing

        </a>

    </div>

</body>

</html>