<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['hostel_roll'])) {
    header("Location: login.html");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$hostel_roll = $_SESSION['hostel_roll'];

/* =========================
MONTH SELECT FEATURE
========================= */

$selected_month = $_GET['month'] ?? date("m");
$selected_year  = $_GET['year'] ?? date("Y");

$current_month = date("m");
$current_year  = date("Y");

/* CHECK IF CURRENT MONTH */
$is_current_month =
    ($selected_month == $current_month &&
        $selected_year == $current_year);

$today = date("d");
$total_days = date("t");

$is_month_complete =
    (!$is_current_month) || ($today == $total_days);

/* =========================
SETTINGS
========================= */

$min_meals_required = 40;
$default_meal_price = 33;
$breakfast_price = 15;

$min_amount = $min_meals_required * $default_meal_price;

/* =========================
FETCH MEALS ONLY (NO JOIN)
========================= */

$sql = "
SELECT *
FROM meals
WHERE hostel_roll=?
AND MONTH(date)=?
AND YEAR(date)=?
ORDER BY date ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $hostel_roll, $selected_month, $selected_year);
$stmt->execute();
$result = $stmt->get_result();

/* =========================
VARIABLES
========================= */

$total_amount = 0;
$breakfast_count = 0;
$lunch_count = 0;
$dinner_count = 0;
$total_meals_taken = 0;
$meals_data = [];

/* =========================
LOOP
========================= */

while ($row = $result->fetch_assoc()) {

    $day_total = 0;

    /* =========================
    FETCH CORRECT MENU
    ========================= */

    $menu_sql = "
    SELECT *
    FROM menu
    WHERE 
        (is_special=1 AND special_date=?)
        OR
        (is_special=0 AND day=?)
    LIMIT 1
    ";

    $menu_stmt = $conn->prepare($menu_sql);
    $menu_stmt->bind_param("ss", $row['date'], $row['day']);
    $menu_stmt->execute();
    $menu = $menu_stmt->get_result()->fetch_assoc();

    if (!$menu) continue;

    /* =========================
    PRICE FIX
    ========================= */

    if ($menu['is_special'] == 1) {

        $lunch_veg_price =
            $menu['special_lunch_veg_price'] ?: $menu['lunch_veg_price'];

        $lunch_nonveg_price =
            $menu['special_lunch_nonveg_price'] ?: $menu['lunch_nonveg_price'];

        $dinner_veg_price =
            $menu['special_dinner_veg_price'] ?: $menu['dinner_veg_price'];

        $dinner_nonveg_price =
            $menu['special_dinner_nonveg_price'] ?: $menu['dinner_nonveg_price'];
    } else {

        $lunch_veg_price  = $menu['lunch_veg_price'];
        $lunch_nonveg_price = $menu['lunch_nonveg_price'];
        $dinner_veg_price  = $menu['dinner_veg_price'];
        $dinner_nonveg_price = $menu['dinner_nonveg_price'];
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
        $l = "Not Taken";
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
        $d = "Not Taken";
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
EMPTY CASE
========================= */

if (empty($meals_data)) {
    $no_data = true;
} else {
    $no_data = false;
}

/* =========================
FINAL BILL
========================= */

$remaining_meals =
    max(0, $min_meals_required - $total_meals_taken);

if ($total_meals_taken >= $min_meals_required) {

    $final_payable = $total_amount;
    $status_text = "✅ Minimum meals completed.";
    $status_class = "success";
} else {

    $final_payable = max($total_amount, $min_amount);
    $status_text = "⚠ Minimum 40 meals rule applied.";
    $status_class = "warning";
}

$month_name = date("F Y", strtotime("$selected_year-$selected_month-01"));
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

        .summary {
            width: 60%;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 12px;
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

        .btn {
            padding: 10px 15px;
            background: #007bff;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>

</head>

<body>

    <h2>📅 Monthly Bill — <?php echo $month_name; ?></h2>

    <!-- FILTER -->
    <form method="get">
        <select name="month">
            <?php for ($m = 1; $m <= 12; $m++) { ?>
                <option value="<?php echo $m; ?>" <?php if ($m == $selected_month) echo "selected"; ?>>
                    <?php echo date("F", mktime(0, 0, 0, $m, 1)); ?>
                </option>
            <?php } ?>
        </select>

        <select name="year">
            <?php for ($y = 2024; $y <= date("Y"); $y++) { ?>
                <option value="<?php echo $y; ?>" <?php if ($y == $selected_year) echo "selected"; ?>>
                    <?php echo $y; ?>
                </option>
            <?php } ?>
        </select>

        <button class="btn">View</button>
    </form>

    <?php if ($no_data) { ?>

        <h3>⚠ No meal data found for this month</h3>

    <?php } else { ?>

        <div class="summary">

            <p>🍽 Breakfast: <b><?php echo $breakfast_count; ?></b></p>
            <p>🍛 Lunch: <b><?php echo $lunch_count; ?></b></p>
            <p>🌙 Dinner: <b><?php echo $dinner_count; ?></b></p>

            <hr>

            <p>Total Meals: <b><?php echo $total_meals_taken; ?></b></p>
            <p>Remaining Meals: <b><?php echo $remaining_meals; ?></b></p>

            <hr>

            <p>Actual Amount: <b>₹ <?php echo $total_amount; ?></b></p>
            <p>Minimum Rule: <b>₹ <?php echo $min_amount; ?></b></p>

            <hr>

            <p class="final">Final Payable: ₹ <?php echo $final_payable; ?></p>

            <?php if ($is_current_month && $total_meals_taken < $min_meals_required) { ?>

                <div style="
        background:#fff3cd;
        border-left:5px solid #ff9800;
        padding:12px;
        margin-top:15px;
        border-radius:8px;
        color:#856404;
        font-weight:500;
    ">

                    ⚠ <b>Minimum Meal Policy Notice</b><br><br>

                    You have taken <b><?php echo $total_meals_taken; ?></b> meals so far this month.

                    <br><br>

                    As per hostel rules, a minimum of
                    <b><?php echo $min_meals_required; ?> meals</b>
                    is required each month.

                    <br><br>

                    If the minimum is not completed by the end of the month,
                    the bill will be calculated based on the minimum requirement amount
                    (₹ <?php echo $min_amount; ?>).

                </div>

            <?php } ?>

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
                    <td><?php echo date("d M", strtotime($meal['date'])); ?></td>
                    <td><?php echo $meal['day']; ?></td>
                    <td><?php echo $meal['breakfast']; ?></td>
                    <td><?php echo $meal['lunch']; ?></td>
                    <td><?php echo $meal['dinner']; ?></td>
                    <td>₹ <?php echo $meal['daily_total']; ?></td>
                </tr>
            <?php } ?>

        </table>

        <?php if (!$is_current_month || $today == $total_days) { ?>

            <a class="btn"
                href="student_print_bill.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>">
                📄 View Final Bill
            </a>

        <?php } ?>

    <?php } ?>

    <br><br>
    <a class="btn" href="student_dashboard.php">⬅ Back Dashboard</a>

</body>

</html>