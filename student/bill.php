<?php
session_start();
// Escapes the student/ directory and looks into the root config folder
include("../config/db_connect.php");

/* ==========================================================================
   PDO VARIABLE AUTOMATIC DETECTION LATCH
   ========================================================================== */
if (!isset($conn)) {
    if (isset($pdo)) {
        $conn = $pdo;
    } elseif (isset($db)) {
        $conn = $db;
    } elseif (isset($con)) {
        $conn = $con;
    } else {
        die("<div style='padding: 24px; text-align:center; font-family:sans-serif; color: #EF4444;'>
            ⚠ Database Variable Error: A valid PDO instance could not be found inside config/db_connect.php.
        </div>");
    }
}

/* ============================
   LOGIN CHECK
============================ */
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

// Pad single digits for date comparison safely
$selected_month = str_pad($selected_month, 2, "0", STR_PAD_LEFT);

$current_month = date("m");
$current_year  = date("Y");

$is_current_month = ($selected_month == $current_month && $selected_year == $current_year);

$today = date("d");
$month_start_date_str = "$selected_year-$selected_month-01";
$month_end_date_str   = date("Y-m-t", strtotime($month_start_date_str));
$total_days = date("t", strtotime($month_start_date_str));
$is_month_complete = (!$is_current_month) || ($today == $total_days);

/* =========================
   MESS POLICY VARIABLES
========================= */
$base_min_meals_required = 40;
$default_meal_price = 33;
$breakfast_price = 15;

/* ==========================================================================
   LEAVE ADJUSTMENT CALCULATION (Smart Vacation Feature)
   ========================================================================== */
$leave_days_count = 0;
try {
    $leave_sql = "
        SELECT start_date, end_date 
        FROM mess_leaves 
        WHERE hostel_roll = ? 
        AND status = 'approved'
        AND NOT (end_date < ? OR start_date > ?)
    ";
    $leave_stmt = $conn->prepare($leave_sql);
    $leave_stmt->execute([$hostel_roll, $month_start_date_str, $month_end_date_str]);
    $active_leaves = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($active_leaves as $leave) {
        // Clamp boundaries to the target month to maintain precise monthly scopes
        $span_start = max(strtotime($month_start_date_str), strtotime($leave['start_date']));
        $span_end   = min(strtotime($month_end_date_str), strtotime($leave['end_date']));

        $diff_seconds = $span_end - $span_start;
        if ($diff_seconds >= 0) {
            $leave_days_count += round($diff_seconds / (60 * 60 * 24)) + 1;
        }
    }
} catch (PDOException $e) {
    // Graceful fallback if analytics tables aren't perfectly prepared yet
}

// Deduct 2 commitment units for every day on approved leave status
$compensated_min_meals = max(0, $base_min_meals_required - ($leave_days_count * 2));
$min_amount = $compensated_min_meals * $default_meal_price;

/* =========================
   FETCH MONTHLY DATA ROWS
========================= */
$sql = "
    SELECT *
    FROM meals
    WHERE hostel_roll = ?
    AND MONTH(date) = ?
    AND YEAR(date) = ?
    ORDER BY date ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$hostel_roll, (int)$selected_month, (int)$selected_year]);
$meals_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_amount = 0;
$breakfast_count = 0;
$lunch_count = 0;
$dinner_count = 0;
$total_meals_taken = 0;
$meals_data = [];

/* =========================
   PROCESS RECORD LOOP
========================= */
foreach ($meals_rows as $row) {
    $day_total = 0;

    $menu_sql = "
        SELECT *
        FROM menu
        WHERE (is_special = 1 AND special_date = ?)
        OR (is_special = 0 AND day = ?)
        ORDER BY is_special DESC
        LIMIT 1
    ";
    $menu_stmt = $conn->prepare($menu_sql);
    $menu_stmt->execute([$row['date'], $row['day']]);
    $menu = $menu_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) continue;

    if ($menu['is_special'] == 1) {
        $lunch_veg_price      = $menu['special_lunch_veg_price'] ?: $menu['lunch_veg_price'];
        $lunch_nonveg_price   = $menu['special_lunch_nonveg_price'] ?: $menu['lunch_nonveg_price'];
        $dinner_veg_price     = $menu['special_dinner_veg_price'] ?: $menu['dinner_veg_price'];
        $dinner_nonveg_price  = $menu['special_dinner_nonveg_price'] ?: $menu['dinner_nonveg_price'];
    } else {
        $lunch_veg_price      = $menu['lunch_veg_price'];
        $lunch_nonveg_price   = $menu['lunch_nonveg_price'];
        $dinner_veg_price     = $menu['dinner_veg_price'];
        $dinner_nonveg_price  = $menu['dinner_nonveg_price'];
    }

    // Process Breakfast
    if (!empty($row['breakfast'])) {
        $day_total += $breakfast_price;
        $breakfast_count++;
        $b = "Taken";
    } else {
        $b = "-";
    }

    // Process Lunch
    if (!empty($row['lunch_type'])) {
        $lunch_count++;
        $l = ucfirst($row['lunch_type']);
        $day_total += ($row['lunch_type'] == "veg") ? (int)$lunch_veg_price : (int)$lunch_nonveg_price;
        $total_meals_taken++;
    } else {
        $l = "-";
    }

    // Process Dinner
    if (!empty($row['dinner_type'])) {
        $dinner_count++;
        $d = ucfirst($row['dinner_type']);
        $day_total += ($row['dinner_type'] == "veg") ? (int)$dinner_veg_price : (int)$dinner_nonveg_price;
        $total_meals_taken++;
    } else {
        $d = "-";
    }

    $total_amount += $day_total;

    $meals_data[] = [
        'date'        => $row['date'],
        'day'         => $row['day'],
        'breakfast'   => $b,
        'lunch'       => $l,
        'dinner'      => $d,
        'daily_total' => $day_total
    ];
}

$no_data = empty($meals_data);

/* =========================
   FINAL BILLING LOGIC & PROGRESS BAR
========================= */
$remaining_meals = max(0, $compensated_min_meals - $total_meals_taken);
$progress_percentage = ($compensated_min_meals > 0) ? min(100, round(($total_meals_taken / $compensated_min_meals) * 100)) : 100;

if ($total_meals_taken >= $compensated_min_meals) {
    $final_payable = $total_amount;
    $status_text   = "Target Met: Minimum meal requirement fulfilled.";
    $status_class  = "success";
} else {
    // If there's a shortfall, pay actual consumption + penalty differences up to min_amount
    $final_payable = max($total_amount, $min_amount);
    $status_text   = "Minimum Charge Applied: Based on your adjusted target.";
    $status_class  = "warning";
}

$month_name = date("F Y", strtotime("$selected_year-$selected_month-01"));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Meal Bill</title>
    <style>
        :root {
            --primary: #3B82F6;
            --primary-hover: #2563EB;
            --bg-color: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --success: #10B981;
            --success-light: #D1FAE5;
            --warning: #F59E0B;
            --warning-light: #FEF3C7;
            --danger: #EF4444;
            --danger-light: #FEE2E2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
            padding: 32px;
            margin-bottom: 28px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .header-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            background: linear-gradient(to right, #ffffff, #f1f5f9);
        }

        h2 {
            font-size: 24px;
            color: var(--text-main);
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        select {
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #CBD5E1;
            background-color: var(--card-bg);
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .metric-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }

        .metric-card .title {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .metric-card .value {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-main);
        }

        .billing-summary {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 28px;
            align-items: start;
        }

        .statement-box {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
        }

        .statement-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 15px;
            color: #475569;
        }

        .statement-row:last-of-type {
            border-bottom: none;
        }

        .statement-row .lbl {
            font-weight: 500;
        }

        .statement-row .val {
            font-weight: 700;
            color: var(--text-main);
        }

        .progress-container {
            margin-top: 16px;
            background: #E2E8F0;
            border-radius: 8px;
            height: 10px;
            width: 100%;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: var(--primary);
            border-radius: 8px;
            transition: width 0.5s ease-in-out;
        }

        .progress-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
        }

        .payable-card {
            background: linear-gradient(135deg, #0F172A, #1E293B);
            color: #FFFFFF;
            padding: 32px 24px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.2);
        }

        .payable-card .title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94A3B8;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .payable-card .price {
            font-size: 42px;
            font-weight: 800;
            color: var(--success);
            margin-bottom: 16px;
            line-height: 1;
        }

        .status-pill {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }

        .status-pill.success {
            background: rgba(16, 185, 129, 0.15);
            color: #34D399;
        }

        .status-pill.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #FBBF24;
        }

        .policy-alert {
            background: var(--warning-light);
            border: 1px solid #FDE68A;
            border-left: 4px solid var(--warning);
            padding: 16px 20px;
            border-radius: 8px;
            margin-top: 20px;
            color: #92400E;
            text-align: left;
            font-size: 14px;
            line-height: 1.6;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        th {
            background: var(--bg-color);
            color: var(--text-muted);
            padding: 16px 18px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        td {
            padding: 14px 18px;
            text-align: left;
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
            color: #334155;
            transition: background 0.2s;
        }

        tbody tr:hover td {
            background-color: #F1F5F9;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge.veg {
            background: var(--success-light);
            color: #065F46;
        }

        .badge.nonveg {
            background: var(--danger-light);
            color: #991B1B;
        }

        .badge.neutral {
            background: #F1F5F9;
            color: var(--text-muted);
        }

        .action-group {
            margin-top: 32px;
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-outline {
            background: #FFFFFF;
            color: #334155;
            border: 1px solid #CBD5E1;
        }

        .btn-outline:hover {
            background: #F1F5F9;
            border-color: #94A3B8;
        }

        /* 📱 EXCLUSIVE MOBILE OPTIMIZATIONS (Laptop styling remains untouched) */
        @media (max-width: 768px) {
            body {
                padding: 16px 8px;
            }
            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
            }
            .header-panel {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 16px;
            }
            h2 {
                justify-content: center;
                font-size: 20px;
            }
            .filter-form {
                flex-direction: column;
                width: 100%;
            }
            .filter-form select, 
            .filter-form .btn {
                width: 100%;
            }
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 16px;
            }
            .metric-card {
                padding: 12px 8px;
            }
            .metric-card .value {
                font-size: 20px;
            }
            .billing-summary {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .statement-box {
                padding: 16px;
            }
            table {
                min-width: 600px;
            }
            .action-group {
                flex-direction: column;
                gap: 12px;
                margin-top: 16px;
            }
            .btn {
                width: 100%;
            }
        }
        @media (max-width: 480px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="card header-panel">
            <h2>📅 Monthly Meal Summary</h2>

            <form method="get" class="filter-form">
                <select name="month">
                    <?php for ($m = 1; $m <= 12; $m++) {
                        $padded_m = str_pad($m, 2, "0", STR_PAD_LEFT);
                    ?>
                        <option value="<?php echo $padded_m; ?>" <?php if ($padded_m == $selected_month) echo "selected"; ?>>
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

                <button class="btn btn-primary" style="padding: 10px 20px;">View Bill</button>
            </form>
        </div>

        <?php if ($no_data) { ?>

            <div class="card" style="text-align: center; padding: 60px 20px;">
                <span style="font-size: 48px; display: block; margin-bottom: 16px;">🔍</span>
                <h3 style="color: var(--text-muted); font-weight: 600;">No meals or transactions found for <?php echo $month_name; ?>.</h3>
            </div>

        <?php } else { ?>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="title">Breakfasts Taken</div>
                    <div class="value"><?php echo $breakfast_count; ?></div>
                </div>
                <div class="metric-card">
                    <div class="title">Lunches Taken</div>
                    <div class="value"><?php echo $lunch_count; ?></div>
                </div>
                <div class="metric-card">
                    <div class="title">Dinners Taken</div>
                    <div class="value"><?php echo $dinner_count; ?></div>
                </div>
                <div class="metric-card">
                    <div class="title">Approved Leave Days</div>
                    <div class="value" style="color: var(--warning);"><?php echo $leave_days_count; ?></div>
                </div>
                <div class="metric-card">
                    <div class="title">Total Meals Taken</div>
                    <div class="value" style="color: var(--primary);"><?php echo $total_meals_taken; ?></div>
                </div>
            </div>

            <div class="card billing-summary">
                <div class="statement-box">
                    <div class="statement-row">
                        <span class="lbl">Total Cost of Meals Taken</span>
                        <span class="val">₹ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="statement-row">
                        <span class="lbl">Minimum Base Charge (for <?php echo $compensated_min_meals; ?> meals)</span>
                        <span class="val">₹ <?php echo number_format($min_amount, 2); ?></span>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <span class="lbl" style="font-weight: 600; font-size: 14px;">Minimum Meal Target Progress</span>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%; <?php echo ($progress_percentage >= 100) ? 'background-color: var(--success);' : ''; ?>"></div>
                        </div>
                        <div class="progress-text">
                            <span><?php echo $total_meals_taken; ?> Meals Eaten</span>
                            <span><?php echo $compensated_min_meals; ?> Required Target</span>
                        </div>
                    </div>

                    <?php if ($is_current_month && $total_meals_taken < $compensated_min_meals) { ?>
                        <div class="policy-alert">
                            <strong>⚠️ Minimum Meal Target Notice</strong><br>
                            You have eaten <strong><?php echo $total_meals_taken; ?> meals</strong> so far. Your target is <strong><?php echo $compensated_min_meals; ?> meals</strong> (adjusted for your <?php echo $leave_days_count; ?> leave days). If you end the month below the target, a minimum fee of <strong>₹ <?php echo number_format($min_amount, 2); ?></strong> will be applied.
                        </div>
                    <?php } ?>
                </div>

                <div class="payable-card">
                    <div class="title">Total Payable Amount</div>
                    <div class="price">₹ <?php echo number_format($final_payable, 2); ?></div>
                    <span class="status-pill <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
            </div>

            <div class="card" style="padding: 0; overflow: hidden;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Breakfast</th>
                            <th>Lunch</th>
                            <th>Dinner</th>
                            <th>Daily Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($meals_data as $meal) { ?>
                            <tr>
                                <td><strong><?php echo date("d M Y", strtotime($meal['date'])); ?></strong></td>
                                <td style="color: var(--text-muted); font-weight: 500;"><?php echo $meal['day']; ?></td>
                                <td>
                                    <span class="badge <?php echo ($meal['breakfast'] === 'Taken') ? 'veg' : 'neutral'; ?>">
                                        <?php echo $meal['breakfast']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($meal['lunch'] === 'Veg') ? 'veg' : (($meal['lunch'] === '-') ? 'neutral' : 'nonveg'); ?>">
                                        <?php echo $meal['lunch']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($meal['dinner'] === 'Veg') ? 'veg' : (($meal['dinner'] === '-') ? 'neutral' : 'nonveg'); ?>">
                                        <?php echo $meal['dinner']; ?>
                                    </span>
                                </td>
                                <td><strong>₹ <?php echo $meal['daily_total']; ?></strong></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <div class="action-group">
                <?php if ($is_month_complete) { ?>
                    <a class="btn btn-primary" href="print_bill.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>">📄 Download / Print Bill</a>
                <?php } ?>
            </div>

        <?php } ?>

        <div class="action-group" style="margin-top: 16px;">
            <a class="btn btn-outline" href="dashboard.php">⬅ Back to Dashboard</a>
        </div>

    </div>

</body>

</html>