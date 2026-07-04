<?php
session_start();

/* ==========================================================================
   1. DATABASE CONNECTIVITY LAYER (PDO INFRASTRUCTURE)
   ========================================================================= */
require_once __DIR__ . "/../config/db_connect.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

/* ==========================================================================
   2. FILTER REQUEST PARAMETERS
   ========================================================================= */
$roll  = isset($_GET['roll'])  ? trim($_GET['roll'])  : '';
$month = isset($_GET['month']) ? str_pad($_GET['month'], 2, "0", STR_PAD_LEFT) : '';
$year  = isset($_GET['year'])  ? intval($_GET['year'])  : 0;

if (empty($roll) || empty($month) || empty($year)) {
    die("❌ Error: Missing required billing information (Student Roll Number, Month, or Year).");
}

// System Baseline Configurations
$breakfast_price = 15.00;
$meal_price      = 33.00;
$min_meals       = 40;

try {
    /* ==========================================================================
       3. FETCH STUDENT INFO
       ========================================================================= */
    $stmt = $pdo->prepare("SELECT * FROM student WHERE hostel_roll = :roll");
    $stmt->execute(['roll' => $roll]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("❌ Error: No active student found matching this Roll Number.");
    }

    /* ==========================================================================
       4. FETCH MEALS & DAILY PRICES
       ========================================================================= */
    $sql = "
        SELECT
            m.date,
            m.day,
            m.breakfast,
            m.lunch_type,
            m.dinner_type,
            mn.is_special,
            mn.lunch_veg_price,
            mn.lunch_nonveg_price,
            mn.dinner_veg_price,
            mn.dinner_nonveg_price,
            mn.special_lunch_veg_price,
            mn.special_lunch_nonveg_price,
            mn.special_dinner_veg_price,
            mn.special_dinner_nonveg_price
        FROM meals m
        LEFT JOIN menu mn ON (
            (mn.is_special = 1 AND m.date = mn.special_date)
            OR
            (mn.is_special = 0 AND m.day = mn.day)
        )
        WHERE m.hostel_roll = :roll
          AND MONTH(m.date) = :month
          AND YEAR(m.date) = :year
        ORDER BY m.date ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'roll'  => $roll,
        'month' => intval($month),
        'year'  => $year
    ]);
    $raw_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Database Error: Couldn't process the bill request. " . $e->getMessage());
}

/* ==========================================================================
   5. CALCULATE TOTALS AND ITEMIZED SUMMARY
   ========================================================================= */
$breakfast_count = 0;
$lunch_count     = 0;
$dinner_count    = 0;

$breakfast_total = 0.00;
$lunch_total     = 0.00;
$dinner_total    = 0.00;
$total_amount    = 0.00;

$itemized_rows = [];

foreach ($raw_records as $row) {
    $day_total = 0.00;

    if (intval($row['is_special'] ?? 0) === 1) {
        $l_v_p  = floatval(!empty($row['special_lunch_veg_price'])  ? $row['special_lunch_veg_price']  : ($row['lunch_veg_price'] ?? $meal_price));
        $l_nv_p = floatval(!empty($row['special_lunch_nonveg_price']) ? $row['special_lunch_nonveg_price'] : ($row['lunch_nonveg_price'] ?? $meal_price));
        $d_v_p  = floatval(!empty($row['special_dinner_veg_price'])  ? $row['special_dinner_veg_price']  : ($row['dinner_veg_price'] ?? $meal_price));
        $d_nv_p = floatval(!empty($row['special_dinner_nonveg_price']) ? $row['special_dinner_nonveg_price'] : ($row['dinner_nonveg_price'] ?? $meal_price));
    } else {
        $l_v_p  = floatval(!empty($row['lunch_veg_price'])     ? $row['lunch_veg_price']     : $meal_price);
        $l_nv_p = floatval(!empty($row['lunch_nonveg_price'])  ? $row['lunch_nonveg_price']  : $meal_price);
        $d_v_p  = floatval(!empty($row['dinner_veg_price'])    ? $row['dinner_veg_price']    : $meal_price);
        $d_nv_p = floatval(!empty($row['dinner_nonveg_price']) ? $row['dinner_nonveg_price'] : $meal_price);
    }

    if (!empty($row['breakfast'])) {
        $breakfast_count++;
        $breakfast_total += $breakfast_price;
        $day_total       += $breakfast_price;
        $b_status         = "🔹 Taken";
    } else {
        $b_status         = "-";
    }

    if (!empty($row['lunch_type'])) {
        $lunch_count++;
        $l_status = ($row['lunch_type'] === "veg") ? "🟢 Veg" : "🔴 Non-Veg";
        $assigned_cost = ($row['lunch_type'] === "veg") ? $l_v_p : $l_nv_p;
        $lunch_total += $assigned_cost;
        $day_total   += $assigned_cost;
    } else {
        $l_status = "-";
    }

    if (!empty($row['dinner_type'])) {
        $dinner_count++;
        $d_status = ($row['dinner_type'] === "veg") ? "🟢 Veg" : "🔴 Non-Veg";
        $assigned_cost = ($row['dinner_type'] === "veg") ? $d_v_p : $d_nv_p;
        $dinner_total += $assigned_cost;
        $day_total   += $assigned_cost;
    } else {
        $d_status = "-";
    }

    $total_amount += $day_total;

    $itemized_rows[] = [
        'date'      => $row['date'],
        'breakfast' => $b_status,
        'lunch'     => $l_status,
        'dinner'    => $d_status,
        'amount'    => $day_total
    ];
}

$meal_total = $lunch_count + $dinner_count;
$penalty_meals = 0;
$final_total = $total_amount;

if ($meal_total < $min_meals) {
    $required_amount = $min_meals * $meal_price;
    if ($total_amount < $required_amount) {
        $final_total = $required_amount;
        $penalty_meals = $min_meals - $meal_total;
    }
}

$month_name_display = date("F Y", strtotime("$year-$month-01"));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Bill Summary - <?php echo htmlspecialchars($student['name']); ?></title>
    <style>
        :root {
            --primary: #3B82F6;
            --bg-color: #F8FAFC;
            --card-bg: #FFFFFF;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: #E2E8F0;
            color: var(--text-main);
            padding: 40px 24px;
            min-height: 100vh;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
        }

        .invoice-card {
            background: var(--card-bg);
            border: 1px solid #CBD5E1;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #F1F5F9;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }

        .invoice-header h2 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .invoice-header p {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 600;
            margin-top: 4px;
        }

        .stamp-badge {
            background: #EEF2F6;
            color: #334155;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid #CBD5E1;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        .meta-item span {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: block;
        }

        .meta-item strong {
            font-size: 15px;
            color: var(--text-main);
            font-weight: 700;
            margin-top: 4px;
            display: block;
        }

        .policy-overview {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background-color: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 32px;
            font-size: 14px;
        }

        .policy-overview div {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-responsive {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: var(--bg-color);
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border-color);
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            color: #334155;
            font-weight: 500;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover td {
            background: var(--bg-color);
        }

        .metrics-board {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .metric-mini-box {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }

        .metric-mini-box span {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .metric-mini-box h4 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            margin: 4px 0;
        }

        .metric-mini-box p {
            font-size: 14px;
            color: #475569;
            font-weight: 700;
        }

        .footer-pricing-panel {
            display: flex;
            justify-content: flex-end;
            border-top: 2px solid #F1F5F9;
            padding-top: 24px;
        }

        .pricing-summary-box {
            width: 340px;
        }

        .pricing-line {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #475569;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .pricing-line.grand-total {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }

        .action-bar {
            text-align: center;
            margin-top: 24px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border-radius: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-print {
            background: var(--success);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .btn-print:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-back {
            background: #FFFFFF;
            color: #475569;
            border: 2px solid #CBD5E1;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: var(--text-main);
        }

        /* ==========================================================================
           6. ADVANCED PRINT & PDF GENERATION RULES
           ========================================================================= */
        @media print {
            body {
                background: #FFFFFF !important;
                padding: 0;
                color: #000000;
            }

            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0;
            }

            .action-bar {
                display: none !important; /* Completely removes control bars from output */
            }

            th {
                background-color: #0F172A !important;
                color: #FFFFFF !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .meta-grid, .policy-overview, .metric-mini-box {
                border: 1px solid #CBD5E1 !important;
                background-color: #F8FAFC !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="invoice-card">

            <div class="invoice-header">
                <div>
                    <h2>🏫 Student Meal Bill Summary</h2>
                    <p>Detailed daily records and monthly billing calculations</p>
                </div>
                <div class="stamp-badge">
                    🗓️ <?php echo $month_name_display; ?>
                </div>
            </div>

            <div class="meta-grid">
                <div class="meta-item">
                    <span>Student Name</span>
                    <strong><?php echo htmlspecialchars($student['name']); ?></strong>
                </div>
                <div class="meta-item">
                    <span>Hostel Roll Number</span>
                    <strong><code><?php echo htmlspecialchars($student['hostel_roll']); ?></code></strong>
                </div>
                <div class="meta-item">
                    <span>Room Number</span>
                    <strong>Room <?php echo htmlspecialchars($student['room_number']); ?></strong>
                </div>
                <div class="meta-item">
                    <span>Total Meals Recorded</span>
                    <strong><?php echo $meal_total; ?> Meals</strong>
                </div>
            </div>

            <div class="policy-overview">
                <div>
                    <span>📌 Minimum Meal Policy:</span>
                    <strong><?php echo $min_meals; ?> meals required per month</strong>
                </div>
                <div>
                    <span>Status:</span>
                    <?php if ($meal_total >= $min_meals) { ?>
                        <strong style="color: var(--success);">✅ Requirement Met</strong>
                    <?php } else { ?>
                        <strong style="color: var(--warning);">⚠️ Shortfall Applied</strong>
                    <?php } ?>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th style="text-align: center;">Breakfast</th>
                            <th style="text-align: center;">Lunch Plan</th>
                            <th style="text-align: center;">Dinner Plan</th>
                            <th style="text-align: right;">Daily Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itemized_rows as $r): ?>
                            <tr>
                                <td><strong><?php echo date("d M Y - l", strtotime($r['date'])); ?></strong></td>
                                <td style="text-align: center;"><?php echo $r['breakfast']; ?></td>
                                <td style="text-align: center;"><?php echo $r['lunch']; ?></td>
                                <td style="text-align: center;"><?php echo $r['dinner']; ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--text-main);">₹ <?php echo number_format($r['amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="metrics-board">
                <div class="metric-mini-box" style="border-top: 3px solid var(--primary);">
                    <span>🍳 Breakfasts Total</span>
                    <h4><?php echo $breakfast_count; ?></h4>
                    <p>₹ <?php echo number_format($breakfast_total, 2); ?></p>
                </div>
                <div class="metric-mini-box" style="border-top: 3px solid var(--success);">
                    <span>🍛 Lunches Total</span>
                    <h4><?php echo $lunch_count; ?></h4>
                    <p>₹ <?php echo number_format($lunch_total, 2); ?></p>
                </div>
                <div class="metric-mini-box" style="border-top: 3px solid #8B5CF6;">
                    <span>🌙 Dinners Total</span>
                    <h4><?php echo $dinner_count; ?></h4>
                    <p>₹ <?php echo number_format($dinner_total, 2); ?></p>
                </div>
            </div>

            <div class="footer-pricing-panel">
                <div class="pricing-summary-box">
                    <div class="pricing-line">
                        <span>Actual Consumption Cost:</span>
                        <span>₹ <?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <?php if ($penalty_meals > 0) { ?>
                        <div class="pricing-line" style="color: var(--danger);">
                            <span>Shortfall Target Charge (+<?php echo $penalty_meals; ?> meals):</span>
                            <span>₹ <?php echo number_format(($penalty_meals * $meal_price), 2); ?></span>
                        </div>
                    <?php } else { ?>
                        <div class="pricing-line" style="color: var(--success);">
                            <span>Shortfall Adjustment:</span>
                            <span>₹ 0.00</span>
                        </div>
                    <?php } ?>
                    <div class="pricing-line grand-total">
                        <span>Total Payable Amount:</span>
                        <span style="color: var(--success);">₹ <?php echo number_format($final_total, 2); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <div class="action-bar">
            <button onclick="window.print()" class="btn btn-print">
                🖨️ Print / Save as PDF
            </button>
            <a href="../admin/bill.php?view=archives&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-back">
                ⬅ Back
            </a>
        </div>

    </div>

</body>

</html>