<?php
session_start();

// Added ../ to escape the student/ directory and look into the root config folder
include("../config/db_connect.php");

/* ==========================================================================
   1. DATABASE VARIABLE AUTOMATIC ROUTING FALLBACK
   ========================================================================= */
if (!isset($conn)) {
    if (isset($pdo)) {
        $conn = $pdo;
    } elseif (isset($db)) {
        $conn = $db;
    } elseif (isset($con)) {
        $conn = $con;
    } else {
        die("<div style='padding: 24px; text-align:center; font-family:sans-serif; color: #EF4444;'>
            ⚠ Database Connection Error: A valid PDO instance ($conn, $pdo, $db, or $con) could not be located inside config/db_connect.php.
        </div>");
    }
}

/* ==========================================================================
   2. AUTHENTICATION CROSS-CHECK (SUPPORTS ADMIN VIEWING & STUDENT SESSIONS)
   ========================================================================= */
// If an admin requests this page, they supply the roll number via GET URL parameters
if (isset($_SESSION['admin']) && isset($_GET['roll'])) {
    $roll = trim($_GET['roll']);
} elseif (isset($_SESSION['hostel_roll'])) {
    $roll = $_SESSION['hostel_roll'];
} else {
    header("Location: login.html");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$breakfast_price = 15;
$min_meals = 40;
$default_meal_price = 33;

$month = isset($_GET['month']) ? intval($_GET['month']) : date("m");
$year  = isset($_GET['year'])  ? intval($_GET['year'])  : date("Y");

// Pad single digits for formatting consistency
$month = str_pad($month, 2, "0", STR_PAD_LEFT);

/* ==========================================================================
   3. FETCH STUDENT INFO
   ========================================================================= */
$stmt = $conn->prepare("SELECT name, hostel_roll, room_number FROM student WHERE hostel_roll = ?");
$stmt->execute([$roll]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("<h2 style='text-align:center; font-family:sans-serif; margin-top:50px;'>⚠ Student record profile not found</h2>");
}

/* ==========================================================================
   4. FETCH MONTHLY MEALS RECORDS
   ========================================================================= */
$sql = "
    SELECT *
    FROM meals
    WHERE hostel_roll = ?
    AND MONTH(date) = ?
    AND YEAR(date) = ?
    ORDER BY date ASC
";
$stmt = $conn->prepare($sql);
$stmt->execute([$roll, $month, $year]);
$meals_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================================
   5. NO DATA HANDLING FALLBACK
   ========================================================================= */
if (empty($meals_rows)) {
    echo "<div style='text-align:center; margin-top:80px; font-family: system-ui, sans-serif;'>
            <h2 style='color:#64748B; margin-bottom:12px;'>📭 No Meal Records Located</h2>
            <p style='color:#94A3B8; margin-bottom:24px;'>No billing histories or structural meal logs were found for this cycle.</p>
            <a href='javascript:window.close();' style='padding:10px 20px; background:#0F172A; color:white; text-decoration:none; border-radius:6px; font-weight:600; font-size:14px; margin-right: 10px;'>❌ Close Window</a>
          </div>";
    exit();
}

/* ==========================================================================
   6. CALCULATION & PRICING PARSER ENGINE
   ========================================================================= */
$breakfast_count = 0;
$lunch_count = 0;
$dinner_count = 0;
$total_amount = 0;
$rows = [];

foreach ($meals_rows as $row) {
    $day_total = 0;

    // Unified menu state rule verification sequence
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

    // Breakfast Tracking
    if (!empty($row['breakfast'])) {
        $breakfast_count++;
        $day_total += $breakfast_price;
        $b = "🔹 Yes";
    } else {
        $b = "-";
    }

    // Lunch Tracking
    if (!empty($row['lunch_type'])) {
        $lunch_count++;
        $day_total += ($row['lunch_type'] == "veg") ? (int)$lunch_veg_price : (int)$lunch_nonveg_price;
        $l = ($row['lunch_type'] == "veg") ? "🟢 Veg" : "🔴 Non-Veg";
    } else {
        $l = "-";
    }

    // Dinner Tracking
    if (!empty($row['dinner_type'])) {
        $dinner_count++;
        $day_total += ($row['dinner_type'] == "veg") ? (int)$dinner_veg_price : (int)$dinner_nonveg_price;
        $d = ($row['dinner_type'] == "veg") ? "🟢 Veg" : "🔴 Non-Veg";
    } else {
        $d = "-";
    }

    $total_amount += $day_total;

    $rows[] = [
        'date'      => $row['date'],
        'breakfast' => $b,
        'lunch'     => $l,
        'dinner'    => $d,
        'amount'    => $day_total
    ];
}

/* ==========================================================================
   7. MINIMUM POLICY CALCULATION ANALYSIS
   ========================================================================= */
$total_meals = $lunch_count + $dinner_count;
$minimum_cost = $min_meals * $default_meal_price;
$penalty_meals = 0;

if ($total_meals >= $min_meals) {
    $final_total = $total_amount;
    $rule_message = "Minimum meal requirement fulfilled successfully.";
    $class = "success";
} else {
    $penalty_meals = $min_meals - $total_meals;
    $final_total = max($total_amount, $minimum_cost);
    $rule_message = "Minimum policy applied (Shortfall of -" . $penalty_meals . " meals added).";
    $class = "warning";
}

$current_month = date("F Y", strtotime("$year-$month-01"));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Bill Invoice_<?php echo $roll . "_" . $month; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', -apple-system, sans-serif;
        }

        body {
            background: #F8FAFC;
            color: #1E293B;
            padding: 40px 20px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-container {
            width: 100%;
            max-width: 842px;
            margin: 0 auto;
            background: #FFFFFF;
            padding: 45px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.02);
        }

        /* INVOICE SPLIT TOP ROW HEADER */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #F1F5F9;
            padding-bottom: 28px;
            margin-bottom: 28px;
        }

        .brand-section h2 {
            font-size: 24px;
            color: #0F172A;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .brand-section p {
            font-size: 14px;
            color: #64748B;
            margin-top: 4px;
            font-weight: 500;
        }

        .meta-section {
            text-align: right;
        }

        .meta-section h3 {
            font-size: 14px;
            color: #3B82F6;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .meta-section p {
            font-size: 16px;
            color: #0F172A;
            margin-top: 4px;
            font-weight: 700;
        }

        /* PROFILE SPECIFICATIONS DISPLAY */
        .profile-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .profile-block .lbl {
            font-size: 11px;
            color: #64748B;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .profile-block .val {
            font-size: 14px;
            color: #0F172A;
            font-weight: 700;
            margin-top: 4px;
        }

        /* POLICY METRIC BADGE CARD */
        .policy-badge-box {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .policy-badge-box.success {
            background-color: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #16A34A;
        }

        .policy-badge-box.warning {
            background-color: #FFFBEB;
            border: 1px solid #FDE68A;
            color: #D97706;
        }

        /* DATA MATRIX LEDGER TABLE */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 32px;
        }

        th {
            background: #0F172A;
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 14px;
            text-align: left;
        }

        th:first-child {
            border-top-left-radius: 6px;
            border-bottom-left-radius: 6px;
        }

        th:last-child {
            border-top-right-radius: 6px;
            border-bottom-right-radius: 6px;
            text-align: right;
        }

        td {
            padding: 12px 14px;
            font-size: 14px;
            color: #334155;
            border-bottom: 1px solid #E2E8F0;
            text-align: left;
        }

        td:last-child {
            text-align: right;
            font-weight: 700;
            color: #0F172A;
        }

        tbody tr:hover td {
            background-color: #F8FAFC;
        }

        /* BOTTOM PRICING BREAKDOWN MODULE */
        .financial-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .calculation-box {
            width: 100%;
            max-width: 380px;
        }

        .calc-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
            color: #475569;
            font-weight: 500;
        }

        .calc-row strong {
            color: #0F172A;
        }

        .calc-row.total-payable {
            border-top: 2px dashed #CBD5E1;
            margin-top: 12px;
            padding-top: 14px;
            font-size: 18px;
            font-weight: 800;
            color: #0F172A;
        }

        /* PERSISTENT ACTIONS BAR */
        .action-tray {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-dark {
            background: #0F172A;
            color: white;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .btn-dark:hover {
            background: #1E293B;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: white;
            color: #475569;
            border: 2px solid #CBD5E1;
        }

        .btn-outline:hover {
            background: #F8FAFC;
            color: #0F172A;
        }

        /* CLEAN INTERFACE PRINT CONFIGURATION */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
            }

            .action-tray {
                display: none !important;
            }

            th {
                background: #1E293B !important;
                color: white !important;
            }
        }
    </style>
</head>

<body>

    <div class="invoice-container">

        <div class="invoice-header">
            <div class="brand-section">
                <h2>🏫 Hostel Mess Statement</h2>
                <p>Official Monthly Consumption & Bill Receipt</p>
            </div>
            <div class="meta-section">
                <h3>BILLING CYCLE</h3>
                <p><?php echo $current_month; ?></p>
            </div>
        </div>

        <div class="profile-summary">
            <div class="profile-block">
                <div class="lbl">Student Name</div>
                <div class="val"><?php echo htmlspecialchars($student['name']); ?></div>
            </div>
            <div class="profile-block">
                <div class="lbl">Roll Number</div>
                <div class="val"><code><?php echo htmlspecialchars($student['hostel_roll']); ?></code></div>
            </div>
            <div class="profile-block">
                <div class="lbl">Room Assignment</div>
                <div class="val">Room <?php echo htmlspecialchars($student['room_number']); ?></div>
            </div>
        </div>

        <div class="policy-badge-box <?php echo $class; ?>">
            <span>📋 Policy Check Status:</span>
            <strong><?php echo $rule_message; ?></strong>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Date</th>
                    <th style="text-align: center; width: 18%;">Breakfast</th>
                    <th style="text-align: center; width: 19%;">Lunch Option</th>
                    <th style="text-align: center; width: 19%;">Dinner Option</th>
                    <th style="text-align: right; width: 19%;">Daily Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r) { ?>
                    <tr>
                        <td><strong><?php echo date("d M Y - l", strtotime($r['date'])); ?></strong></td>
                        <td style="text-align: center;"><?php echo $r['breakfast']; ?></td>
                        <td style="text-align: center;"><?php echo $r['lunch']; ?></td>
                        <td style="text-align: center;"><?php echo $r['dinner']; ?></td>
                        <td style="text-align: right;">₹ <?php echo number_format($r['amount'], 2); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="financial-summary">
            <div class="calculation-box">
                <div class="calc-row">
                    <span>Total Breakfasts Taken</span>
                    <strong><?php echo $breakfast_count; ?></strong>
                </div>
                <div class="calc-row">
                    <span>Total Main Meals (Lunch + Dinner)</span>
                    <strong><?php echo $total_meals; ?> / <?php echo $min_meals; ?> (Min Target)</strong>
                </div>
                <div class="calc-row">
                    <span>Actual Food Consumption Cost</span>
                    <span>₹ <?php echo number_format($total_amount, 2); ?></span>
                </div>
                
                <?php if ($penalty_meals > 0) { ?>
                    <div class="calc-row" style="color: #D97706;">
                        <span>Shortfall Penalty Cost (+<?php echo $penalty_meals; ?> meals)</span>
                        <span>₹ <?php echo number_format(($penalty_meals * $default_meal_price), 2); ?></span>
                    </div>
                <?php } ?>

                <div class="calc-row total-payable">
                    <span>Total Amount Due</span>
                    <span style="color: #10B981;">₹ <?php echo number_format($final_total, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="action-tray">
            <button class="btn btn-dark" onclick="window.print()">🖨 Print Invoice Statement</button>
            <?php if (isset($_SESSION['admin'])) { ?>
    <button class="btn btn-outline" onclick="if(!window.close()){ window.location.href='bill.php'; }">❌ Close Preview</button>
<?php } else { ?>
                <a href="bill.php" class="btn btn-outline">⬅ Back to Dashboard</a>
            <?php } ?>
        </div>

    </div>

</body>

</html>