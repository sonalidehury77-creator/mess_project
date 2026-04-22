<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ===============================
   AUTO SELECT PREVIOUS MONTH
=============================== */

if (isset($_GET['month'])) {

    $month = $_GET['month'];
    $year  = $_GET['year'];
} else {

    $month =
        date('m', strtotime("first day of previous month"));

    $year  =
        date('Y', strtotime("first day of previous month"));
}

/* ===============================
   SETTINGS
=============================== */

$breakfast_price = 15;
$meal_price      = 33;
$min_meals       = 40;

/* ===============================
   BLOCK CURRENT MONTH BILL
=============================== */

$currentMonth = date("m");
$currentYear  = date("Y");

$blockCurrent =
    ($month == $currentMonth &&
        $year  == $currentYear);

/* ===============================
   CALCULATE BILL FUNCTION
=============================== */

function calculateBill(
    $conn,
    $roll,
    $month,
    $year,
    $breakfast_price,
    $meal_price,
    $min_meals
) {

    $stmt = $conn->prepare("

SELECT
meals.*,

menu.lunch_veg_price,
menu.lunch_nonveg_price,
menu.dinner_veg_price,
menu.dinner_nonveg_price,

menu.special_lunch_veg_price,
menu.special_lunch_nonveg_price,
menu.special_dinner_veg_price,
menu.special_dinner_nonveg_price,

menu.is_special

FROM meals

LEFT JOIN menu
ON (
(menu.is_special=1 AND meals.date=menu.special_date)
OR
(menu.is_special=0 AND meals.day=menu.day)
)

WHERE meals.hostel_roll=?
AND MONTH(meals.date)=?
AND YEAR(meals.date)=?

GROUP BY meals.id

");

    $stmt->bind_param(
        "sii",
        $roll,
        $month,
        $year
    );

    $stmt->execute();

    $res = $stmt->get_result();

    $total_amount = 0;
    $meal_total   = 0;

    while ($row = $res->fetch_assoc()) {

        /* BREAKFAST */

        if ($row['breakfast']) {

            $total_amount += $breakfast_price;
        }

        /* LUNCH */

        if (!empty($row['lunch_type'])) {

            $meal_total++;

            if ($row['lunch_type'] == "veg") {

                $total_amount +=
                    $row['is_special']
                    ? ($row['special_lunch_veg_price']
                        ?: $row['lunch_veg_price'])
                    : $row['lunch_veg_price'];
            } else {

                $total_amount +=
                    $row['is_special']
                    ? ($row['special_lunch_nonveg_price']
                        ?: $row['lunch_nonveg_price'])
                    : $row['lunch_nonveg_price'];
            }
        }

        /* DINNER */

        if (!empty($row['dinner_type'])) {

            $meal_total++;

            if ($row['dinner_type'] == "veg") {

                $total_amount +=
                    $row['is_special']
                    ? ($row['special_dinner_veg_price']
                        ?: $row['dinner_veg_price'])
                    : $row['dinner_veg_price'];
            } else {

                $total_amount +=
                    $row['is_special']
                    ? ($row['special_dinner_nonveg_price']
                        ?: $row['dinner_nonveg_price'])
                    : $row['dinner_nonveg_price'];
            }
        }
    }

    /* APPLY MINIMUM RULE */

    if ($meal_total < $min_meals) {

        $required =
            $min_meals * $meal_price;

        if ($total_amount < $required) {

            $total_amount = $required;
        }
    }

    return [

        'meals' => $meal_total,
        'total' => $total_amount

    ];
}

/* ===============================
   FETCH STUDENTS
=============================== */

$students =
    $conn->query("

SELECT *
FROM student
ORDER BY CAST(hostel_roll AS UNSIGNED)

");

/* ===============================
   SUMMARY
=============================== */

$total_students = 0;
$total_revenue  = 0;
$below_min      = 0;

$month_name =
    date("F", mktime(0, 0, 0, $month, 1));

?>

<!DOCTYPE html>
<html>

<head>

    <title>Smart Monthly Billing</title>

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

        /* TABLE */

        table {

            width: 96%;
            margin: 20px auto;

            border-collapse: collapse;
            background: white;

        }

        th {

            background: #007bff;
            color: white;
            padding: 10px;

        }

        td {

            padding: 10px;
            border-bottom: 1px solid #ddd;

        }

        tr:hover {

            background: #f5f5f5;

        }

        /* STATUS */

        .low {

            background: #ffe6e6;

        }

        .ok {

            color: green;
            font-weight: bold;

        }

        .bad {

            color: red;
            font-weight: bold;

        }

        /* BUTTON */

        .view-btn {

            background: green;
            color: white;

            padding: 6px 10px;
            border-radius: 6px;

            text-decoration: none;

        }

        /* SUMMARY */

        .summary {

            display: flex;
            justify-content: center;
            flex-wrap: wrap;

        }

        .card {

            background: white;

            padding: 15px;
            margin: 10px;

            width: 200px;

            border-radius: 10px;

            box-shadow:
                0 4px 10px rgba(0, 0, 0, .1);

        }

        .back {

            display: block;

            width: 200px;
            margin: 20px auto;

            text-align: center;

            padding: 10px;

            background: #333;
            color: white;

            border-radius: 8px;

            text-decoration: none;

        }

        .warning {

            color: red;
            font-weight: bold;
            margin-top: 10px;

        }
    </style>

</head>

<body>

    <div class="header">

        💰 Billing For:
        <?php echo $month_name . " " . $year; ?>

    </div>

    <?php

    if ($blockCurrent) {

        echo "
<p class='warning'>
⚠ Current month billing blocked.
Please wait until month ends.
</p>";
    }

    ?>

    <table>

        <tr>

            <th>Name</th>
            <th>Roll</th>
            <th>Total Meals</th>
            <th>Status</th>
            <th>Amount ₹</th>
            <th>View</th>

        </tr>

        <?php

        while ($stu =
            $students->fetch_assoc()
        ) {

            $total_students++;

            $data =
                calculateBill(

                    $conn,
                    $stu['hostel_roll'],
                    $month,
                    $year,
                    $breakfast_price,
                    $meal_price,
                    $min_meals

                );

            $total_revenue +=
                $data['total'];

            if ($data['meals'] < 40)
                $below_min++;

            $rowClass =
                ($data['meals'] < 40)
                ? "low" : "";

            $status =
                ($data['meals'] < 40)
                ? "<span class='bad'>LOW</span>"
                : "<span class='ok'>OK</span>";

        ?>

            <tr class="<?php echo $rowClass; ?>">

                <td>
                    <?php echo $stu['name']; ?>
                </td>

                <td>
                    <?php echo $stu['hostel_roll']; ?>
                </td>

                <td>
                    <?php echo $data['meals']; ?>
                </td>

                <td>
                    <?php echo $status; ?>
                </td>

                <td>

                    ₹ <?php
                        echo number_format(
                            $data['total']
                        );
                        ?>

                </td>

                <td>

                    <a
                        class="view-btn"

                        href="view_bill.php?
roll=<?php echo $stu['hostel_roll']; ?>
&month=<?php echo $month; ?>
&year=<?php echo $year; ?>">

                        📄 View Bill

                    </a>

                </td>

            </tr>

        <?php } ?>

    </table>

    <!-- SUMMARY -->

    <div class="summary">

        <div class="card">

            👨‍🎓 Students

            <h2>
                <?php echo $total_students; ?>
            </h2>

        </div>

        <div class="card">

            ⚠ Below 40

            <h2>
                <?php echo $below_min; ?>
            </h2>

        </div>

        <div class="card">

            💰 Revenue

            <h2>

                ₹ <?php
                    echo number_format(
                        $total_revenue
                    );
                    ?>

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