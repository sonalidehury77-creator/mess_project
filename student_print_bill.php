<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['hostel_roll'])) {
    header("Location: login.html");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$roll = $_SESSION['hostel_roll'];

$breakfast_price = 15;
$min_meals = 40;
$default_meal_price = 33;

$month = isset($_GET['month']) ? intval($_GET['month']) : date("m");
$year  = isset($_GET['year'])  ? intval($_GET['year'])  : date("Y");

/* =========================
STUDENT INFO
========================= */

$stmt = $conn->prepare("
SELECT name, hostel_roll, room_number
FROM student
WHERE hostel_roll=?
");

$stmt->bind_param("s", $roll);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("<h2 style='text-align:center;'>⚠ Student record not found</h2>");
}

/* =========================
FETCH MEALS
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
$stmt->bind_param("sii", $roll, $month, $year);
$stmt->execute();

$result = $stmt->get_result();

/* =========================
NO DATA HANDLING
========================= */

if ($result->num_rows == 0) {

    echo "<div style='text-align:center;margin-top:80px;font-family:Arial;'>
            <h2>📭 No Meal Records Found</h2>
            <p>No meals selected for this month.</p>
            <a href='bill.php' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:6px;'>⬅ Back</a>
          </div>";
    exit();
}

/* =========================
CALCULATION
========================= */

$breakfast_count = 0;
$lunch_count = 0;
$dinner_count = 0;
$total_amount = 0;

$rows = [];

while ($row = $result->fetch_assoc()) {

    $day_total = 0;

    /* =========================
    CORRECT MENU FETCH (FIXED)
    ========================= */

    // First check SPECIAL menu
    $menu_stmt = $conn->prepare("
        SELECT *
        FROM menu
        WHERE is_special=1 AND special_date=?
        LIMIT 1
    ");
    $menu_stmt->bind_param("s", $row['date']);
    $menu_stmt->execute();
    $menu = $menu_stmt->get_result()->fetch_assoc();

    // If no special → use NORMAL menu
    if (!$menu) {

        $menu_stmt = $conn->prepare("
            SELECT *
            FROM menu
            WHERE is_special=0 AND day=?
            LIMIT 1
        ");
        $menu_stmt->bind_param("s", $row['day']);
        $menu_stmt->execute();
        $menu = $menu_stmt->get_result()->fetch_assoc();
    }

    if (!$menu) continue;

    /* =========================
    PRICE HANDLING
    ========================= */

    if ($menu['is_special'] == 1) {

        $lunch_veg_price =
            !empty($menu['special_lunch_veg_price'])
            ? $menu['special_lunch_veg_price']
            : $menu['lunch_veg_price'];

        $lunch_nonveg_price =
            !empty($menu['special_lunch_nonveg_price'])
            ? $menu['special_lunch_nonveg_price']
            : $menu['lunch_nonveg_price'];

        $dinner_veg_price =
            !empty($menu['special_dinner_veg_price'])
            ? $menu['special_dinner_veg_price']
            : $menu['dinner_veg_price'];

        $dinner_nonveg_price =
            !empty($menu['special_dinner_nonveg_price'])
            ? $menu['special_dinner_nonveg_price']
            : $menu['dinner_nonveg_price'];
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
        $breakfast_count++;
        $day_total += $breakfast_price;
        $b = "Yes";
    } else {
        $b = "-";
    }

    /* =========================
    LUNCH
    ========================= */

    if (!empty($row['lunch_type'])) {

        $lunch_count++;

        if ($row['lunch_type'] == "veg") {
            $day_total += (int)$lunch_veg_price;
        } else {
            $day_total += (int)$lunch_nonveg_price;
        }

        $l = ucfirst($row['lunch_type']);
    } else {
        $l = "-";
    }

    /* =========================
    DINNER
    ========================= */

    if (!empty($row['dinner_type'])) {

        $dinner_count++;

        if ($row['dinner_type'] == "veg") {
            $day_total += (int)$dinner_veg_price;
        } else {
            $day_total += (int)$dinner_nonveg_price;
        }

        $d = ucfirst($row['dinner_type']);
    } else {
        $d = "-";
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
FINAL BILL
========================= */

$total_meals = $lunch_count + $dinner_count;

$minimum_cost = $min_meals * $default_meal_price;

if ($total_meals >= $min_meals) {

    $final_total = $total_amount;
    $rule_message = "✅ Minimum meal requirement completed.";
    $class = "success";
} else {

    $remaining = $min_meals - $total_meals;

    $final_total = max($total_amount, $minimum_cost);

    $rule_message =
        "⚠ Minimum meal rule applied. You are short of $remaining meals. Minimum billing is applied.";

    $class = "warning";
}

$current_month = date("F Y", strtotime("$year-$month-01"));
?>

<!DOCTYPE html>
<html>

<head>
    <title>Student Bill</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
        }

        .invoice {
            width: 850px;
            margin: 20px auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        .header {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #007bff;
            color: white;
            padding: 8px;
        }

        td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .summary {
            margin-top: 20px;
            background: #f9f9f9;
            padding: 15px;
        }

        .buttons {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 18px;
            background: #007bff;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin: 5px;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .warning {
            color: red;
            font-weight: bold;
        }

        @media print {
            .buttons {
                display: none;
            }
        }
    </style>

</head>

<body>

    <div class="invoice">

        <div class="header">
            <h2>🏫 Hostel Mess Bill</h2>
            <h3><?php echo $current_month; ?></h3>
        </div>

        <p>
            <b>Name:</b> <?php echo $student['name']; ?><br>
            <b>Roll:</b> <?php echo $student['hostel_roll']; ?><br>
            <b>Room:</b> <?php echo $student['room_number']; ?>
        </p>

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
                    <td><?php echo date("d M", strtotime($r['date'])); ?></td>
                    <td><?php echo $r['breakfast']; ?></td>
                    <td><?php echo $r['lunch']; ?></td>
                    <td><?php echo $r['dinner']; ?></td>
                    <td>₹ <?php echo $r['amount']; ?></td>
                </tr>
            <?php } ?>

        </table>

        <div class="summary">

            <p>Total Meals: <b><?php echo $total_meals; ?></b></p>
            <p>Breakfast Count: <b><?php echo $breakfast_count; ?></b></p>

            <hr>

            <p>Actual Amount: <b>₹ <?php echo $total_amount; ?></b></p>
            <p>Minimum Rule: <b>₹ <?php echo $minimum_cost; ?></b></p>

            <hr>

            <h3>Final Payable: ₹ <?php echo $final_total; ?></h3>

            <p class="<?php echo $class; ?>">
                <?php echo $rule_message; ?>
            </p>

        </div>

        <div class="buttons">

            <button class="btn" onclick="window.print()">
                🖨 Print
            </button>

            <a href="bill.php" class="btn">
                ⬅ Back
            </a>

        </div>

    </div>

</body>

</html>