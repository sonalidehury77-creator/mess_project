<?php
session_start();

/* ==========================================================================
   1. DATABASE CONNECTION & SESSION MANAGEMENT
   ========================================================================= */
require_once __DIR__ . "/../config/db_connect.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

/* ==========================================================================
   2. SYSTEM CONFIGURATION & BASELINE RULES
   ========================================================================= */
$breakfast_price = 15.00;
$meal_base_price = 33.00;
$min_meals_rule  = 40;
$min_fixed_cap   = $min_meals_rule * $meal_base_price; // Compulsory minimum money (1320)

// Set Active Filter (Defaults to Current Month if not selected)
$month  = isset($_GET['month']) ? str_pad($_GET['month'], 2, "0", STR_PAD_LEFT) : date('m');
$year   = isset($_GET['year'])  ? intval($_GET['year']) : intval(date('Y'));
$search = trim($_GET['search'] ?? "");

// Determine Time State (Past, Running, or Future)
$current_time_val = strtotime(date("Y-m-01"));
$selected_time_val = strtotime("$year-$month-01");

if ($selected_time_val === $current_time_val) {
    $period_state = 'running';
} elseif ($selected_time_val > $current_time_val) {
    $period_state = 'future';
} else {
    $period_state = 'past'; // Only past months are eligible for finalized billing
}

/* ==========================================================================
   3. CORE BILLING CALCULATION ENGINE
   ========================================================================= */
function calculateMonthlyBill($pdo, $roll, $target_month, $target_year, $breakfast_fixed, $meal_standby, $min_cap)
{
    try {
        $sql = "SELECT date, day, breakfast, lunch_type, dinner_type 
                FROM meals 
                WHERE hostel_roll = :roll AND MONTH(date) = :month AND YEAR(date) = :year";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['roll' => $roll, 'month' => intval($target_month), 'year' => $target_year]);
        $meal_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total_accumulated = 0.00;
        $meals_consumed_count = 0;
        $breakdown = ['bk' => 0, 'main' => 0];

        foreach ($meal_records as $record) {
            $day_subtotal = 0.00;

            // Fetch dynamic menu pricing
            $menu_sql = "SELECT is_special, lunch_veg_price, lunch_nonveg_price, dinner_veg_price, dinner_nonveg_price,
                                special_lunch_veg_price, special_lunch_nonveg_price, special_dinner_veg_price, special_dinner_nonveg_price
                         FROM menu
                         WHERE (is_special = 1 AND special_date = :date) OR (is_special = 0 AND day = :day)
                         ORDER BY is_special DESC LIMIT 1";
            $menu_stmt = $pdo->prepare($menu_sql);
            $menu_stmt->execute(['date' => $record['date'], 'day' => $record['day']]);
            $menu = $menu_stmt->fetch(PDO::FETCH_ASSOC);

            if ($menu) {
                if (intval($menu['is_special'] ?? 0) === 1) {
                    $l_v_p  = floatval(!empty($menu['special_lunch_veg_price'])  ? $menu['special_lunch_veg_price']  : ($menu['lunch_veg_price'] ?? $meal_standby));
                    $l_nv_p = floatval(!empty($menu['special_lunch_nonveg_price']) ? $menu['special_lunch_nonveg_price'] : ($menu['lunch_nonveg_price'] ?? $meal_standby));
                    $d_v_p  = floatval(!empty($menu['special_dinner_veg_price']) ? $menu['special_dinner_veg_price'] : ($menu['dinner_veg_price'] ?? $meal_standby));
                    $d_nv_p = floatval(!empty($menu['special_dinner_nonveg_price']) ? $menu['special_dinner_nonveg_price'] : ($menu['dinner_nonveg_price'] ?? $meal_standby));
                } else {
                    $l_v_p  = floatval(!empty($menu['lunch_veg_price'])     ? $menu['lunch_veg_price']     : $meal_standby);
                    $l_nv_p = floatval(!empty($menu['lunch_nonveg_price'])  ? $menu['lunch_nonveg_price']  : $meal_standby);
                    $d_v_p  = floatval(!empty($menu['dinner_veg_price'])    ? $menu['dinner_veg_price']    : $meal_standby);
                    $d_nv_p = floatval(!empty($menu['dinner_nonveg_price']) ? $menu['dinner_nonveg_price'] : $meal_standby);
                }
            } else {
                $l_v_p = $l_nv_p = $d_v_p = $d_nv_p = $meal_standby;
            }

            if (!empty($record['breakfast'])) {
                $day_subtotal += floatval($breakfast_fixed);
                $breakdown['bk']++;
            }
            if (!empty($record['lunch_type'])) {
                $meals_consumed_count++;
                $breakdown['main']++;
                $day_subtotal += ($record['lunch_type'] === "veg") ? $l_v_p : $l_nv_p;
            }
            if (!empty($record['dinner_type'])) {
                $meals_consumed_count++;
                $breakdown['main']++;
                $day_subtotal += ($record['dinner_type'] === "veg") ? $d_v_p : $d_nv_p;
            }

            $total_accumulated += $day_subtotal;
        }

        // Apply strict minimum rule: If below compulsory meals, apply fixed minimum money
        $final_payable_amount = max($total_accumulated, $min_cap);

        return [
            'meals' => $meals_consumed_count,
            'amount' => $final_payable_amount,
            'breakdown' => $breakdown
        ];
    } catch (PDOException $e) {
        return ['meals' => 0, 'amount' => $min_cap, 'breakdown' => ['bk' => 0, 'main' => 0]];
    }
}

/* ==========================================================================
   4. HANDLE PAYMENT STATUS TOGGLES (ONLY FOR PAST MONTHS)
   ========================================================================= */
if (isset($_GET['action_toggle']) && isset($_GET['roll'])) {

    // Security check: Only allow marking bills as paid if the month is actually over
    if ($period_state !== 'past') {
        header("Location: bill.php?" . http_build_query(['month' => $month, 'year' => $year, 'search' => $search, 'err' => 'not_ended']));
        exit();
    }

    try {
        $target_roll = trim($_GET['roll']);
        $new_status  = ($_GET['action_toggle'] === 'paid') ? 'paid' : 'pending';

        $stmt = $pdo->prepare("SELECT id FROM bills WHERE hostel_roll = :roll AND month = :m AND year = :y");
        $stmt->execute(['roll' => $target_roll, 'm' => $month, 'y' => $year]);
        $existing_bill = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($new_status === 'paid') {
            $calc = calculateMonthlyBill($pdo, $target_roll, $month, $year, $breakfast_price, $meal_base_price, $min_fixed_cap);

            if ($existing_bill) {
                $upd = $pdo->prepare("UPDATE bills SET status = 'paid', paid_at = NOW(), total_amount = :amt WHERE id = :id");
                $upd->execute(['amt' => $calc['amount'], 'id' => $existing_bill['id']]);
            } else {
                $ins = $pdo->prepare("INSERT INTO bills (hostel_roll, month, year, total_amount, status, paid_at) VALUES (:roll, :m, :y, :amt, 'paid', NOW())");
                $ins->execute(['roll' => $target_roll, 'm' => $month, 'y' => $year, 'amt' => $calc['amount']]);
            }
        } else {
            if ($existing_bill) {
                $upd = $pdo->prepare("UPDATE bills SET status = 'pending', paid_at = NULL WHERE id = :id");
                $upd->execute(['id' => $existing_bill['id']]);
            }
        }

        header("Location: bill.php?" . http_build_query(['month' => $month, 'year' => $year, 'search' => $search]));
        exit();
    } catch (PDOException $e) {
        error_log("Payment status update failed: " . $e->getMessage());
    }
}

/* ==========================================================================
   5. FETCH ALL STUDENTS AND COMPILE FINANCIAL DATA
   ========================================================================= */
$display_roster = [];
$stats = [
    'total_students' => 0,
    'total_expected' => 0.00,
    'total_collected' => 0.00,
    'total_pending_amt' => 0.00,
    'paid_count' => 0,
    'unpaid_count' => 0
];

try {
    if ($search !== "") {
        $stmt = $pdo->prepare("SELECT name, hostel_roll, room_number FROM student WHERE hostel_roll LIKE :s OR name LIKE :s ORDER BY hostel_roll ASC");
        $stmt->execute(['s' => "%" . $search . "%"]);
    } else {
        $stmt = $pdo->query("SELECT name, hostel_roll, room_number FROM student ORDER BY hostel_roll ASC");
    }
    $students_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT hostel_roll, total_amount, status, paid_at FROM bills WHERE month = :m AND year = :y");
    $stmt->execute(['m' => $month, 'y' => $year]);
    $raw_bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $saved_bills = [];
    foreach ($raw_bills as $b) {
        $saved_bills[$b['hostel_roll']] = $b;
    }

    foreach ($students_list as $stu) {
        $roll = $stu['hostel_roll'];
        $stats['total_students']++;

        $calc = calculateMonthlyBill($pdo, $roll, $month, $year, $breakfast_price, $meal_base_price, $min_fixed_cap);
        $stu['meal_info'] = $calc;

        if (isset($saved_bills[$roll])) {
            $stu['status']  = $saved_bills[$roll]['status'];
            $stu['amount']  = floatval($saved_bills[$roll]['total_amount']);
            $stu['paid_at'] = $saved_bills[$roll]['paid_at'];
        } else {
            $stu['status']  = 'pending';
            $stu['amount']  = floatval($calc['amount']);
            $stu['paid_at'] = null;
        }

        // Only count towards total if not a future month
        if ($period_state !== 'future') {
            $stats['total_expected'] += $stu['amount'];
            if ($stu['status'] === 'paid') {
                $stats['total_collected'] += $stu['amount'];
                $stats['paid_count']++;
            } else {
                $stats['total_pending_amt'] += $stu['amount'];
                $stats['unpaid_count']++;
            }
        }
        $display_roster[] = $stu;
    }
} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage());
}

$display_month_name = date("F Y", mktime(0, 0, 0, $month, 1, $year));
$collection_percent = ($stats['total_expected'] > 0) ? round(($stats['total_collected'] / $stats['total_expected']) * 100) : 0;

/* ==========================================================================
   6. EXPORT TO CSV FEATURE
   ========================================================================= */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Hostel_Billing_' . $display_month_name . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Roll Number', 'Room', 'Billing Month', 'Total Meals Eaten', 'Bill Amount (Rs)', 'Payment Status', 'Paid Date']);

    foreach ($display_roster as $row) {
        $status_text = ($row['status'] === 'paid') ? 'Paid' : (($period_state === 'running') ? 'Running' : 'Unpaid');
        $date_text = $row['paid_at'] ? date("d-M-Y", strtotime($row['paid_at'])) : 'N/A';
        fputcsv($output, [
            $row['name'],
            $row['hostel_roll'],
            $row['room_number'],
            $display_month_name,
            $row['meal_info']['meals'],
            $row['amount'],
            $status_text,
            $date_text
        ]);
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Hostel Billing</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: #F1F5F9;
            color: #0F172A;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .card {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.05);
            margin-bottom: 24px;
        }

        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-wrapper h2 {
            font-size: 24px;
            font-weight: 800;
        }

        .header-wrapper p {
            color: #64748B;
            font-weight: 500;
            font-size: 14px;
            margin-top: 4px;
        }

        .toolbar-box {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .filters {
            display: flex;
            gap: 12px;
        }

        select,
        input[type="text"] {
            padding: 10px 14px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            outline: none;
        }

        select:focus,
        input:focus {
            border-color: #2563EB;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            background: #FFFFFF;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #F8FAFC;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 14px 16px;
            border-bottom: 2px solid #E2E8F0;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 14px;
            color: #334155;
            font-weight: 500;
            vertical-align: middle;
        }

        tr:hover td {
            background: #F8FAFC;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-success {
            background: #DCFCE7;
            color: #14532D;
        }

        .badge-alert {
            background: #FEE2E2;
            color: #7F1D1D;
        }

        .badge-running {
            background: #E0F2FE;
            color: #0369A1;
        }

        .badge-future {
            background: #F3F4F6;
            color: #4B5563;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-search {
            background: #0F172A;
            color: #FFFFFF;
        }

        .btn-export {
            background: #10B981;
            color: #FFFFFF;
        }

        .btn-view {
            background: #F1F5F9;
            color: #334155;
            border: 1px solid #CBD5E1;
        }

        .btn-view:hover {
            background: #E2E8F0;
        }

        .btn-settle {
            background: #2563EB;
            color: #FFFFFF;
        }

        .btn-settle:hover {
            background: #1D4ED8;
        }

        .btn-revert {
            background: #F97316;
            color: #FFFFFF;
        }

        .btn-revert:hover {
            background: #EA580C;
        }

        .btn-disabled {
            background: #F1F5F9;
            color: #94A3B8;
            cursor: not-allowed;
            border: 1px solid #E2E8F0;
        }

        .metrics-board {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .metric-mini-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #64748B;
        }

        .metric-mini-box span {
            font-size: 12px;
            color: #64748B;
            text-transform: uppercase;
            font-weight: 700;
        }

        .metric-mini-box h3 {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 4px;
        }

        /* Progress Bar */
        .progress-container {
            width: 100%;
            background-color: #E2E8F0;
            border-radius: 8px;
            margin-top: 12px;
            height: 8px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: #10B981;
            border-radius: 8px;
            transition: width 0.5s ease;
        }

        .sub-text {
            font-size: 11px;
            color: #64748B;
            display: block;
            margin-top: 2px;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header-wrapper">
            <div>
                <h2>💵 Financial Operations Dashboard</h2>
                <p>Manage monthly billing, enforce compulsory minimum limits, and export financial records.</p>
            </div>
            <a href="dashboard.php" class="btn btn-view" style="padding: 10px 16px; font-size: 14px;">⬅ Return to Dashboard</a>
        </div>

        <?php if ($period_state !== 'future'): ?>
            <div class="metrics-board">
                <div class="metric-mini-box" style="border-left-color: #3B82F6;">
                    <span>Total Expected Revenue</span>
                    <h3>₹ <?php echo number_format($stats['total_expected'], 2); ?></h3>
                    <span class="sub-text"><?php echo $stats['total_students']; ?> Students Enrolled</span>
                </div>
                <div class="metric-mini-box" style="border-left-color: #10B981;">
                    <span>Revenue Collected</span>
                    <h3>₹ <?php echo number_format($stats['total_collected'], 2); ?></h3>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?php echo $collection_percent; ?>%;"></div>
                    </div>
                    <span class="sub-text"><?php echo $collection_percent; ?>% Collected (<?php echo $stats['paid_count']; ?> Paid)</span>
                </div>
                <div class="metric-mini-box" style="border-left-color: #EF4444;">
                    <span>Pending Balance</span>
                    <h3 style="color: #EF4444;">₹ <?php echo number_format($stats['total_pending_amt'], 2); ?></h3>
                    <span class="sub-text"><?php echo $stats['unpaid_count']; ?> Invoices Unpaid</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="GET" action="" class="toolbar-box">
                <div class="filters">
                    <select name="month">
                        <?php for ($m = 1; $m <= 12; $m++) {
                            $padded_m = str_pad($m, 2, "0", STR_PAD_LEFT);
                            $selected = ($padded_m === $month) ? "selected" : "";
                        ?>
                            <option value="<?php echo $padded_m; ?>" <?php echo $selected; ?>>
                                <?php echo date("F", mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="year">
                        <?php for ($y = 2024; $y <= intval(date("Y")) + 1; $y++) {
                            $selected = ((string)$y === (string)$year) ? "selected" : "";
                        ?>
                            <option value="<?php echo $y; ?>" <?php echo $selected; ?>><?php echo $y; ?></option>
                        <?php } ?>
                    </select>

                    <input type="text" name="search" placeholder="Search roll no or name..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-search">Apply Filter</button>
                </div>

                <div>
                    <a href="bill.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&search=<?php echo urlencode($search); ?>&export=csv" class="btn btn-export">📥 Export to CSV</a>
                </div>
            </form>
        </div>

        <?php if (isset($_GET['err']) && $_GET['err'] === 'not_ended'): ?>
            <div style="background: #FEF2F2; color: #991B1B; padding: 16px; border-radius: 8px; border: 1px solid #FCA5A5; margin-bottom: 24px; font-weight: 600;">
                ⚠️ You cannot mark bills as paid for a month that is still currently running. Wait until the month concludes.
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student Info</th>
                        <th>Meal Activity</th>
                        <th>Billing Month</th>
                        <th>Calculated Bill</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($display_roster) === 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #64748B; padding: 40px;">📭 No student records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($display_roster as $row):
                            $is_paid = ($row['status'] === 'paid');
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                    <span class="sub-text">Roll: <?php echo htmlspecialchars($row['hostel_roll']); ?> | Rm: <?php echo htmlspecialchars($row['room_number']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo $row['meal_info']['meals']; ?> Total Meals</strong><br>
                                    <span class="sub-text"><?php echo $row['meal_info']['breakdown']['main']; ?> Main / <?php echo $row['meal_info']['breakdown']['bk']; ?> Brkfst</span>
                                </td>
                                <td><span style="color:#64748B; font-weight:700;"><?php echo $display_month_name; ?></span></td>
                                <td style="font-size: 15px; font-weight: 800; color: #0F172A;">
                                    ₹ <?php echo number_format($row['amount'], 2); ?>
                                </td>
                                <td>
                                    <?php
                                    if ($is_paid) {
                                        echo "<span class='badge badge-success'>✅ Paid On " . date("d M", strtotime($row['paid_at'])) . "</span>";
                                    } else {
                                        if ($period_state === 'running') {
                                            echo "<span class='badge badge-running'>🔄 Running (Est.)</span>";
                                        } elseif ($period_state === 'future') {
                                            echo "<span class='badge badge-future'>⏳ Upcoming</span>";
                                        } else {
                                            echo "<span class='badge badge-alert'>⚠️ Unpaid</span>";
                                        }
                                    }
                                    ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <?php if ($period_state === 'running' || $period_state === 'future'): ?>
                                            <button class="btn btn-disabled" disabled title="Bill not generated until month end">View Locked</button>
                                        <?php else: ?>
                                            <a class="btn btn-view" href="view_bill.php?roll=<?php echo urlencode($row['hostel_roll']); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" target="_blank">📄 View</a>
                                        <?php endif; ?>
                                        <?php if ($period_state === 'future'): ?>
                                            <button class="btn btn-disabled" disabled>Unavailable</button>
                                        <?php elseif ($period_state === 'running'): ?>
                                            <button class="btn btn-disabled" disabled title="Bills finalize at the end of the month">Closes at Month End</button>
                                        <?php else: ?>
                                            <?php if ($is_paid): ?>
                                                <a class="btn btn-revert" href="bill.php?action_toggle=pending&roll=<?php echo urlencode($row['hostel_roll']); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&search=<?php echo urlencode($search); ?>" onclick="return confirm('Change status back to unpaid?');">Mark Unpaid</a>
                                            <?php else: ?>
                                                <a class="btn btn-settle" href="bill.php?action_toggle=paid&roll=<?php echo urlencode($row['hostel_roll']); ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&search=<?php echo urlencode($search); ?>">Mark Paid</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>

</html>