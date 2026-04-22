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

/* =========================
SETTINGS (MATCH ADMIN)
========================= */

$min_meals_required = 40;
$default_meal_price = 33;
$breakfast_price = 15;

$min_amount =
    $min_meals_required *
    $default_meal_price;

/* =========================
MONTH INFO
========================= */

$current_month = date("m");
$current_year  = date("Y");

$today       = date("d");
$total_days  = date("t");

$is_month_complete =
    ($today == $total_days);

/* =========================
FETCH MEALS + MENU
========================= */

$sql = "

SELECT

m.date,
m.day,
m.breakfast,
m.lunch_type,
m.dinner_type,

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

FROM meals m

LEFT JOIN menu
ON (

    (
        menu.is_special = 1
        AND m.date = menu.special_date
    )

    OR

    (
        menu.is_special = 0
        AND m.day = menu.day
    )

)

WHERE m.hostel_roll=?

AND MONTH(m.date)=?
AND YEAR(m.date)=?

ORDER BY m.date ASC

";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sii",
    $hostel_roll,
    $current_month,
    $current_year
);

$stmt->execute();

$result = $stmt->get_result();

/* =========================
VARIABLES
========================= */

$total_amount = 0;

$breakfast_count = 0;
$lunch_count     = 0;
$dinner_count    = 0;

$total_meals_taken = 0;

$meals_data = [];

/* =========================
CALCULATION LOOP
========================= */

while ($row = $result->fetch_assoc()) {

    $day_total = 0;

    /* =========================
       PRICE DETECTION
    ========================= */

    if ($row['is_special'] == 1) {

        $lunch_veg_price =
            !empty($row['special_lunch_veg_price'])
            ? $row['special_lunch_veg_price']
            : $row['lunch_veg_price'];

        $lunch_nonveg_price =
            !empty($row['special_lunch_nonveg_price'])
            ? $row['special_lunch_nonveg_price']
            : $row['lunch_nonveg_price'];

        $dinner_veg_price =
            !empty($row['special_dinner_veg_price'])
            ? $row['special_dinner_veg_price']
            : $row['dinner_veg_price'];

        $dinner_nonveg_price =
            !empty($row['special_dinner_nonveg_price'])
            ? $row['special_dinner_nonveg_price']
            : $row['dinner_nonveg_price'];
    } else {

        $lunch_veg_price  = $row['lunch_veg_price'];
        $lunch_nonveg_price = $row['lunch_nonveg_price'];

        $dinner_veg_price  = $row['dinner_veg_price'];
        $dinner_nonveg_price = $row['dinner_nonveg_price'];
    }

    /* =========================
       BREAKFAST
    ========================= */

    if (!empty($row['breakfast'])) {

        $day_total += $breakfast_price;

        $breakfast_count++;

        $b = "Yes";
    } else {

        $b = "No";
    }

    /* =========================
       LUNCH
    ========================= */

    if (!empty($row['lunch_type'])) {

        $lunch_count++;

        $l = ucfirst($row['lunch_type']);

        if ($row['lunch_type'] == "veg") {

            $day_total += (int)$lunch_veg_price;
        } else {

            $day_total += (int)$lunch_nonveg_price;
        }

        $total_meals_taken++;
    } else {

        $l = "No";
    }

    /* =========================
       DINNER
    ========================= */

    if (!empty($row['dinner_type'])) {

        $dinner_count++;

        $d = ucfirst($row['dinner_type']);

        if ($row['dinner_type'] == "veg") {

            $day_total += (int)$dinner_veg_price;
        } else {

            $day_total += (int)$dinner_nonveg_price;
        }

        $total_meals_taken++;
    } else {

        $d = "No";
    }

    $total_amount += $day_total;

    $meals_data[] = [

        'date' => $row['date'],
        'day' => $row['day'],
        'breakfast' => $b,
        'lunch' => $l,
        'dinner' => $d,
        'daily_total' => $day_total

    ];
}

/* =========================
FINAL BILL CALCULATION
========================= */

$remaining_meals =
    max(
        0,
        $min_meals_required
            - $total_meals_taken
    );

if (
    $total_meals_taken >=
    $min_meals_required
) {

    $final_payable =
        $total_amount;

    $status_text =
        "✅ Minimum meals completed.";

    $status_class = "success";
} else {

    $final_payable =
        max($total_amount, $min_amount);

    $status_text =
        "⚠ Minimum 40 meals rule applied.";

    $status_class = "warning";
}

/* =========================
MONTH STATUS
========================= */

if ($is_month_complete) {

    $month_status =
        "🧾 Final Monthly Bill Ready";
} else {

    $month_status =
        "📊 Running Bill Till Today";
}

$current_month_name =
    date("F Y");

?>

<!DOCTYPE html>
<html>

<head>

    <title>My Monthly Bill</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            text-align: center;
        }

        /* SUMMARY CARD */

        .summary {

            width: 60%;
            margin: 20px auto;

            background: white;

            padding: 20px;

            border-radius: 12px;

            box-shadow: 0 5px 15px rgba(0, 0, 0, .1);

            text-align: left;
        }

        .summary h3 {
            margin-top: 0;
        }

        .status {
            font-size: 18px;
            font-weight: bold;
        }

        .success {
            color: green;
        }

        .warning {
            color: red;
        }

        .final {
            font-size: 22px;
            font-weight: bold;
            color: green;
        }

        /* TABLE */

        table {

            width: 95%;
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

            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        /* BUTTON */

        .btn {

            display: inline-block;

            padding: 10px 15px;

            margin-top: 10px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 8px;
        }
    </style>

</head>

<body>

    <h2>
        📅 Monthly Bill —
        <?php echo $current_month_name; ?>
    </h2>

    <div class="summary">

        <p class="status">
            <?php echo $month_status; ?>
        </p>

        <hr>

        <p>🍽 Breakfast: <b><?php echo $breakfast_count; ?></b></p>

        <p>🍛 Lunch: <b><?php echo $lunch_count; ?></b></p>

        <p>🌙 Dinner: <b><?php echo $dinner_count; ?></b></p>

        <hr>

        <p>Total Meals:
            <b><?php echo $total_meals_taken; ?></b>
        </p>

        <p>Remaining Meals:
            <b><?php echo $remaining_meals; ?></b>
        </p>

        <hr>

        <p>Actual Amount:
            <b>₹ <?php echo $total_amount; ?></b>
        </p>

        <p>Minimum Rule:
            <b>₹ <?php echo $min_amount; ?></b>
        </p>

        <hr>

        <p class="final">

            Final Payable:
            ₹ <?php echo $final_payable; ?>

        </p>

        <p class="<?php echo $status_class; ?>">

            <?php echo $status_text; ?>

        </p>

    </div>

    <table>

        <tr>

            <th>Date</th>
            <th>Day</th>
            <th>Breakfast</th>
            <th>Lunch</th>
            <th>Dinner</th>
            <th>Amount ₹</th>

        </tr>

        <?php foreach ($meals_data as $meal) { ?>

            <tr>

                <td>
                    <?php echo date("d M", strtotime($meal['date'])); ?>
                </td>

                <td>
                    <?php echo $meal['day']; ?>
                </td>

                <td>
                    <?php echo $meal['breakfast']; ?>
                </td>

                <td>
                    <?php echo $meal['lunch']; ?>
                </td>

                <td>
                    <?php echo $meal['dinner']; ?>
                </td>

                <td>
                    ₹ <?php echo $meal['daily_total']; ?>
                </td>

            </tr>

        <?php } ?>

    </table>

    <a
        class="btn"
        href="student_print_bill.php">

        🖨 Print Bill

    </a>

    <br><br>

    <a
        class="btn"
        href="student_dashboard.php">

        ⬅ Back Dashboard

    </a>

</body>

</html>