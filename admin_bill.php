<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

/* ===============================
   FILTER (MONTH + SEARCH)
=============================== */

$month = $_GET['month'] ?? date('m', strtotime("first day of previous month"));
$year  = $_GET['year']  ?? date('Y', strtotime("first day of previous month"));
$search_roll = $_GET['roll'] ?? "";

/* ===============================
   SETTINGS
=============================== */

$breakfast_price = 15;
$meal_price      = 33;
$min_meals       = 40;

/* ===============================
   CURRENT MONTH CHECK
=============================== */

$currentMonth = date("m");
$currentYear  = date("Y");

$is_current_month =
    ($month == $currentMonth && $year == $currentYear);

/* ===============================
   BILL FUNCTION (CORRECTED)
=============================== */

function calculateBill($conn, $roll, $month, $year, $breakfast_price, $meal_price, $min_meals)
{
    $sql = "
    SELECT *
    FROM meals
    WHERE hostel_roll=?
    AND MONTH(date)=?
    AND YEAR(date)=?
    ORDER BY date ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $roll, $month, $year);
    $stmt->execute();

    $res = $stmt->get_result();

    $total_amount = 0;
    $meal_total   = 0;

    while ($row = $res->fetch_assoc()) {

        $day_total = 0;

        /* FETCH MENU (FIXED) */
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

        /* PRICE FIX */

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

        /* BREAKFAST */
        if (!empty($row['breakfast'])) {
            $day_total += $breakfast_price;
        }

        /* LUNCH */
        if (!empty($row['lunch_type'])) {

            $meal_total++;

            if ($row['lunch_type'] == "veg") {
                $day_total += (int)$lunch_veg_price;
            } else {
                $day_total += (int)$lunch_nonveg_price;
            }
        }

        /* DINNER */
        if (!empty($row['dinner_type'])) {

            $meal_total++;

            if ($row['dinner_type'] == "veg") {
                $day_total += (int)$dinner_veg_price;
            } else {
                $day_total += (int)$dinner_nonveg_price;
            }
        }

        $total_amount += $day_total;
    }

    return [
        'meals' => $meal_total,
        'actual' => $total_amount
    ];
}

/* ===============================
   FETCH STUDENTS (WITH SEARCH)
=============================== */

if (!empty($search_roll)) {

    $stmt = $conn->prepare("
        SELECT * FROM student
        WHERE hostel_roll LIKE ?
        ORDER BY hostel_roll
    ");
    $like = "%$search_roll%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $students = $stmt->get_result();
} else {

    $students = $conn->query("
        SELECT * FROM student
        ORDER BY CAST(hostel_roll AS UNSIGNED)
    ");
}

/* ===============================
   SUMMARY
=============================== */

$total_students = 0;
$total_revenue  = 0;
$below_min      = 0;

$month_name = date("F Y", strtotime("$year-$month-01"));

?>

<!DOCTYPE html>
<html>

<head>

    <title>Admin Billing Panel</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #eef2f7;
            margin: 0;
        }

        /* HEADER */

        .header {
            background: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 22px;
        }

        /* FILTER BOX */

        .filter-box {
            width: 90%;
            margin: 15px auto;
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
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
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        tr:hover {
            background: #f5f5f5;
        }

        /* STATUS */

        .good {
            color: green;
            font-weight: bold;
        }

        .bad {
            color: red;
            font-weight: bold;
        }

        /* BUTTON */

        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
        }

        .view-btn {
            background: green;
        }

        .disabled {
            background: grey;
            pointer-events: none;
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
            text-align: center;
        }

        /* BACK BUTTON */

        .back {
            display: block;
            width: 220px;
            margin: 20px auto;
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
        💰 Admin Billing — <?php echo $month_name; ?>
    </div>

    <div class="filter-box">

        <form method="get">

            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++) { ?>
                    <option value="<?php echo $m; ?>" <?php if ($m == $month) echo "selected"; ?>>
                        <?php echo date("F", mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php } ?>
            </select>

            <select name="year">
                <?php for ($y = 2024; $y <= date("Y"); $y++) { ?>
                    <option value="<?php echo $y; ?>" <?php if ($y == $year) echo "selected"; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php } ?>
            </select>

            <input type="text" name="roll" placeholder="Search Roll"
                value="<?php echo $search_roll; ?>">

            <button class="btn" style="background:#007bff;">Search</button>

        </form>

    </div>

    <table>

        <tr>
            <th>Name</th>
            <th>Roll</th>
            <th>Total Meals</th>
            <th>Status</th>
            <th>Amount ₹</th>
            <th>Action</th>
        </tr>

        <?php while ($stu = $students->fetch_assoc()) {

            $total_students++;

            $data = calculateBill(
                $conn,
                $stu['hostel_roll'],
                $month,
                $year,
                $breakfast_price,
                $meal_price,
                $min_meals
            );

            $meals = $data['meals'];
            $actual = $data['actual'];

            $minimum = $min_meals * $meal_price;

            if ($is_current_month) {

                $final_amount = $actual;

                $status = "<span class='good'>Running Bill</span>";
            } else {

                if ($meals >= $min_meals) {

                    $final_amount = $actual;
                    $status = "<span class='good'>Minimum Completed</span>";
                } else {

                    $final_amount = max($actual, $minimum);
                    $status = "<span class='bad'>Minimum Not Completed</span>";
                    $below_min++;
                }
            }

            $total_revenue += $final_amount;
        ?>

            <tr>

                <td><?php echo $stu['name']; ?></td>

                <td><?php echo $stu['hostel_roll']; ?></td>

                <td><?php echo $meals; ?></td>

                <td><?php echo $status; ?></td>

                <td>₹ <?php echo number_format($final_amount); ?></td>

                <td>

                    <?php if ($is_current_month) { ?>

                        <span class="btn disabled">Not Available</span>

                    <?php } else { ?>

                        <a class="btn view-btn"
                            href="student_print_bill.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&roll=<?php echo $stu['hostel_roll']; ?>">
                            📄 View Bill
                        </a>

                    <?php } ?>

                </td>

            </tr>

        <?php } ?>

    </table>

    <div class="summary">

        <div class="card">
            Students
            <h2><?php echo $total_students; ?></h2>
        </div>

        <div class="card">
            Below Minimum
            <h2><?php echo $below_min; ?></h2>
        </div>

        <div class="card">
            Total Revenue
            <h2>₹ <?php echo number_format($total_revenue); ?></h2>
        </div>

    </div>

    <a href="admin_dashboard.php" class="back">
        ⬅ Back to Dashboard
    </a>

</body>

</html>