<?php
session_start();

/* ==========================================================================
   1. DATABASE CONNECTIVITY LAYER (PDO INSTANCE)
   ========================================================================== */
require_once __DIR__ . "/../config/db_connect.php";

// Admin access verification check
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$msg = "";
$msg_type = ""; // Stores 'success' or 'error' classification for status banners

/* ==========================================================================
   2. UPDATE HANDLER FOR PROCESS POST REQUESTS
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $day = trim($_POST['day'] ?? '');

    $breakfast     = trim($_POST['breakfast'] ?? '');
    $lunch_veg     = trim($_POST['lunch_veg'] ?? '');
    $lunch_nonveg  = trim($_POST['lunch_nonveg'] ?? '');
    $dinner_veg    = trim($_POST['dinner_veg'] ?? '');
    $dinner_nonveg = trim($_POST['dinner_nonveg'] ?? '');

    // Validate if the chosen day is within our calendar array configuration
    if (!empty($day) && in_array($day, $days_order, true)) {
        $stmt = $pdo->prepare("
            UPDATE menu SET
                breakfast = :breakfast,
                lunch_veg = :lunch_veg,
                lunch_nonveg = :lunch_nonveg,
                dinner_veg = :dinner_veg,
                dinner_nonveg = :dinner_nonveg
            WHERE day = :day 
            AND is_special = 0
        ");

        $executed = $stmt->execute([
            'breakfast'     => $breakfast,
            'lunch_veg'     => $lunch_veg,
            'lunch_nonveg'  => $lunch_nonveg,
            'dinner_veg'    => $dinner_veg,
            'dinner_nonveg' => $dinner_nonveg,
            'day'           => $day
        ]);

        if ($executed) {
            $msg = "🎉 System Update: Weekly menu schedule for $day has been updated successfully.";
            $msg_type = "success";
        } else {
            $msg = "⚠️ Database Error: Unable to process data write changes at this moment.";
            $msg_type = "error";
        }
    }
}

/* ==========================================================================
   3. FETCH CURRENT MENU RECORDS & ROW ORDER MAPPING
   ========================================================================== */
// Generate look-up placeholders dynamically matching array size
$placeholders = implode(',', array_fill(0, count($days_order), '?'));

$query = "
    SELECT * FROM menu 
    WHERE day IN ($placeholders)
    AND is_special = 0
";
$stmt = $pdo->prepare($query);
$stmt->execute($days_order);
$raw_menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map rows by day text index to guarantee display sequence always follows $days_order
$menu_registry = [];
foreach ($raw_menu as $row) {
    $menu_registry[$row['day']] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Mess Menu Management Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #E2E8F0;
            color: #0F172A;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
        }

        /* DASHBOARD TOP CONTROL HEADER */
        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h2 {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 14px;
            color: #64748B;
            font-weight: 500;
            margin-top: 2px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            background: #FFFFFF;
            color: #475569;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }

        /* STATUS NOTIFICATION MESSAGES */
        .alert-box {
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: #DCFCE7;
            color: #14532D;
            border-color: #22C55E;
        }

        .alert-error {
            background: #FEE2E2;
            color: #7F1D1D;
            border-color: #EF4444;
        }

        /* MANAGEMENT CONTAINER LAYOUTS */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #FFFFFF;
            text-align: left;
        }

        th {
            background: #F8FAFC;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            padding: 16px;
            border-bottom: 2px solid #E2E8F0;
            letter-spacing: 0.05em;
        }

        td {
            padding: 12px 14px;
            border-bottom: 1px solid #E2E8F0;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #F8FAFC;
        }

        .day-label {
            font-size: 15px;
            font-weight: 700;
            color: #0F172A;
        }

        /* COMPACT INTERACTIVE FORMS AND FIELDS */
        input.menu-input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            outline: none;
            transition: all 0.15s ease-in-out;
            background: #FFFFFF;
        }

        input.menu-input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            color: #0F172A;
            background: #FFFFFF;
        }

        /* GRID FORM SUBMIT CONTROLS */
        .btn-save {
            padding: 10px 16px;
            background: #0F172A;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease;
            width: 100%;
            text-align: center;
        }

        .btn-save:hover {
            background: #1E293B;
        }
    </style>
</head>
<body>

    <div class="container">

        <div class="header-wrapper">
            <div class="header-title">
                <h2>📅 Weekly Mess Menu Configurator</h2>
                <p>Update the weekly standard menu items displayed on the student panels.</p>
            </div>
            <a href="dashboard.php" class="btn-back">⬅ Return to Dashboard</a>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-box alert-<?php echo $msg_type; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 140px;">Calendar Day</th>
                        <th>Morning Breakfast</th>
                        <th>Lunch (Vegetarian)</th>
                        <th>Lunch (Non-Vegetarian)</th>
                        <th>Dinner (Vegetarian)</th>
                        <th>Dinner (Non-Vegetarian)</th>
                        <th style="width: 100px; text-align: center;">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days_order as $target_day): 
                        // Assign base array layout fields if current day index records don't exist yet
                        $current = $menu_registry[$target_day] ?? [
                            'breakfast' => '', 'lunch_veg' => '', 'lunch_nonveg' => '', 'dinner_veg' => '', 'dinner_nonveg' => ''
                        ];
                    ?>
                        <tr>
                            <form method="POST" action="">
                                <td>
                                    <span class="day-label"><?php echo $target_day; ?></span>
                                    <input type="hidden" name="day" value="<?php echo htmlspecialchars($target_day); ?>">
                                </td>
                                <td>
                                    <input type="text" class="menu-input" name="breakfast" value="<?php echo htmlspecialchars($current['breakfast']); ?>" placeholder="Enter breakfast details...">
                                </td>
                                <td>
                                    <input type="text" class="menu-input" name="lunch_veg" value="<?php echo htmlspecialchars($current['lunch_veg']); ?>" placeholder="Enter vegetarian lunch...">
                                </td>
                                <td>
                                    <input type="text" class="menu-input" name="lunch_nonveg" value="<?php echo htmlspecialchars($current['lunch_nonveg']); ?>" placeholder="Enter non-vegetarian lunch...">
                                </td>
                                <td>
                                    <input type="text" class="menu-input" name="dinner_veg" value="<?php echo htmlspecialchars($current['dinner_veg']); ?>" placeholder="Enter vegetarian dinner...">
                                </td>
                                <td>
                                    <input type="text" class="menu-input" name="dinner_nonveg" value="<?php echo htmlspecialchars($current['dinner_nonveg']); ?>" placeholder="Enter non-vegetarian dinner...">
                                </td>
                                <td>
                                    <button type="submit" class="btn-save">💾 Save</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>