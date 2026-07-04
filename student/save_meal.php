<?php
session_start();
// Escapes the student/ directory and looks into the root config folder
include("../config/db_connect.php");

/* ==========================================================================
   PDO VARIABLE AUTOMATIC DETECTION LATCH
   ========================================================================== */
if (!isset($conn)) {
    if (isset($pdo)) { $conn = $pdo; }
    elseif (isset($db)) { $conn = $db; }
    elseif (isset($con)) { $conn = $con; }
    else {
        die("<div style='padding: 24px; text-align:center; font-family:sans-serif; color: #EF4444;'>
            ⚠ Database Variable Error: A valid PDO instance ($conn, $pdo, $db, or $con) could not be found inside config/db_connect.php.
        </div>");
    }
}

/* =========================
   LOGIN CHECK
========================= */
if (!isset($_SESSION['hostel_roll'])) {
    header("Location: login.html");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$hostel_roll = $_SESSION['hostel_roll'];
$now = time();
$deadline = strtotime("22:00");

/* =========================
   DATE VALUES
========================= */
$day  = date("l", strtotime("+1 day"));
$date = date("Y-m-d", strtotime("+1 day"));

/* =========================
   CHECK LOCK STATUS (PDO Style)
========================= */
$lockCheck = $conn->prepare("
    SELECT locked
    FROM meals
    WHERE hostel_roll = ? 
    AND date = ?
");
$lockCheck->execute([$hostel_roll, $date]);
$rowLock = $lockCheck->fetch(PDO::FETCH_ASSOC);

$isLocked = false;
if ($rowLock && !empty($rowLock['locked'])) {
    $isLocked = true;
}

/* =========================
   TIME OR LOCK BLOCK
========================= */
if ($now >= $deadline || $isLocked) {
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Access Denied - Meal Locked</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', -apple-system, sans-serif; }
            body { background-color: #F8FAFC; color: #1E293B; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
            .error-card { max-width: 480px; width: 100%; background: #FFFFFF; padding: 40px 30px; border-radius: 16px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.03); border: 1px solid #E2E8F0; }
            .icon { font-size: 54px; margin-bottom: 20px; color: #EF4444; }
            h2 { color: #0F172A; font-size: 22px; font-weight: 700; margin-bottom: 12px; }
            p { color: #64748B; font-size: 15px; line-height: 1.6; margin-bottom: 30px; }
            .btn-back { display: inline-block; padding: 12px 30px; background: #0F172A; color: #FFFFFF; text-decoration: none; font-weight: 600; border-radius: 8px; font-size: 14px; transition: background 0.2s; }
            .btn-back:hover { background: #1E293B; }
        </style>
    </head>
    <body>
        <div class='error-card'>
            <div class='icon'>🔏</div>
            <h2>Changes Finalized & Locked</h2>
            <p>The modification window for tomorrow's menu closes strictly at 10:00 PM daily. Your selection is secured and cannot be updated at this stage.</p>
            <a href='meal.php' class='btn-back'>⬅ Return to Menu</a>
        </div>
    </body>
    </html>";
    exit();
}

/* =========================
   FORM DATA PROCESSING
========================= */
$breakfast = isset($_POST['breakfast']) ? 1 : 0;
$lunch  = isset($_POST['take_lunch']) ? 1 : 0;
$dinner = isset($_POST['take_dinner']) ? 1 : 0;

/* =========================
   TYPE VALIDATION
========================= */
$lunch_type = NULL;
if ($lunch) {
    if (isset($_POST['lunch_type']) && in_array($_POST['lunch_type'], ['veg', 'nonveg'])) {
        $lunch_type = $_POST['lunch_type'];
    }
}

$dinner_type = NULL;
if ($dinner) {
    if (isset($_POST['dinner_type']) && in_array($_POST['dinner_type'], ['veg', 'nonveg'])) {
        $dinner_type = $_POST['dinner_type'];
    }
}

/* =========================
   BASE OPTION PREVENT NULL CRASH
========================= */
$base = 'none'; 
if ($dinner) {
    $allowed_bases = ($day === 'Sunday') ? ['rice', 'roti', 'none'] : ['rice', 'roti'];
    
    if (isset($_POST['base']) && in_array($_POST['base'], $allowed_bases)) {
        $base = $_POST['base'];
    } elseif ($day !== 'Sunday') {
        $base = 'rice'; 
    }
}

/* =========================
   CHECK EXISTING RECORD (PDO Style)
========================= */
$checkStmt = $conn->prepare("SELECT id FROM meals WHERE hostel_roll = ? AND date = ?");
$checkStmt->execute([$hostel_roll, $date]);
$existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   UPDATE OR INSERT CONFIGURATION (PDO Style)
========================= */
if ($existingRecord) {
    $stmt = $conn->prepare("
        UPDATE meals SET
            day = ?, breakfast = ?, lunch = ?, lunch_type = ?, 
            dinner = ?, dinner_type = ?, base = ?
        WHERE hostel_roll = ? AND date = ?
    ");
    $params = [$day, $breakfast, $lunch, $lunch_type, $dinner, $dinner_type, $base, $hostel_roll, $date];
} else {
    $stmt = $conn->prepare("
        INSERT INTO meals (hostel_roll, day, breakfast, lunch, lunch_type, dinner, dinner_type, base, date, locked)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $params = [$hostel_roll, $day, $breakfast, $lunch, $lunch_type, $dinner, $dinner_type, $base, $date];
}

/* =========================
   EXECUTE QUERY & ERROR HANDLING
========================= */
try {
    $success = $stmt->execute($params);
    $errorMessage = "";
} catch (PDOException $e) {
    $success = false;
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Status Sync</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', -apple-system, sans-serif;
        }

        body {
            background-color: #F1F5F9;
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .container {
            max-width: 520px;
            width: 100%;
            background: #FFFFFF;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(15, 23, 42, 0.04);
            border: 1px solid #E2E8F0;
            text-align: center;
        }

        .status-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 24px auto;
        }

        .status-icon.success {
            background-color: #ECFDF5;
            color: #10B981;
            border: 1px solid #D1FAE5;
        }

        .status-icon.error {
            background-color: #FEF2F2;
            color: #EF4444;
            border: 1px solid #FEE2E2;
        }

        h1 {
            font-size: 22px;
            color: #0F172A;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .meta-text {
            color: #64748B;
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 32px;
        }

        .meta-text b {
            color: #334155;
        }

        .summary-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            text-align: left;
            margin-bottom: 32px;
        }

        .summary-card h3 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 16px;
            font-weight: 700;
            border-bottom: 1px solid #E2E8F0;
            padding-bottom: 8px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 12px;
            color: #475569;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-row .label {
            font-weight: 500;
        }

        .summary-row .val {
            font-weight: 600;
            color: #0F172A;
        }

        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge.yes { background: #ECFDF5; color: #065F46; }
        .badge.no { background: #F1F5F9; color: #64748B; }
        .badge.variant { background: #EFF6FF; color: #1E40AF; }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            border-radius: 8px;
            transition: all 0.15s ease;
            text-align: center;
        }

        .btn-primary {
            background: #10B981;
            color: white;
            border: 1px solid #059669;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1);
        }

        .btn-primary:hover {
            background: #059669;
        }

        .btn-danger {
            background: #EF4444;
            color: white;
            border: 1px solid #DC3545;
        }

        .error-log {
            background: #FAF5F5;
            border: 1px solid #F5E3E3;
            color: #C53030;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            padding: 16px;
            border-radius: 8px;
            text-align: left;
            margin-bottom: 24px;
            overflow-x: auto;
        }
    </style>
</head>
<body>

    <div class="container">

        <?php if ($success) { ?>

            <div class="status-icon success">✓</div>

            <h1>Preferences Saved Successfully</h1>
            <p class="meta-text">
                Hi <b><?php echo htmlspecialchars($_SESSION['name']); ?></b>, your meal preferences for <b><?php echo date("l, d M Y", strtotime($date)); ?></b> have been securely updated.
            </p>

            <div class="summary-card">
                <h3>🍽 Selected Meal Summary</h3>
                
                <div class="summary-row">
                    <span class="label">Breakfast Choice</span>
                    <span class="val">
                        <?php echo $breakfast ? '<span class="badge yes">Opted In</span>' : '<span class="badge no">Opted Out</span>'; ?>
                    </span>
                </div>

                <div class="summary-row">
                    <span class="label">Lunch Choice</span>
                    <span class="val">
                        <?php echo $lunch ? '<span class="badge variant">'.ucfirst($lunch_type).'</span>' : '<span class="badge no">Opted Out</span>'; ?>
                    </span>
                </div>

                <div class="summary-row">
                    <span class="label">Dinner Choice</span>
                    <span class="val">
                        <?php echo $dinner ? '<span class="badge variant">'.ucfirst($dinner_type).'</span>' : '<span class="badge no">Opted Out</span>'; ?>
                    </span>
                </div>

                <div class="summary-row">
                    <span class="label">Base Option</span>
                    <span class="val"><?php echo ($base !== 'none') ? '<span class="badge variant">'.ucfirst($base).'</span>' : '<span class="val">-</span>'; ?></span>
                </div>
            </div>

            <div class="btn-group">
                <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            </div>

        <?php } else { ?>

            <div class="status-icon error">⚠</div>

            <h1>Failed to Save Choices</h1>
            <p class="meta-text">An error occurred while connecting to the server. Your selections were not saved.</p>

            <div class="error-log">
                <strong>System Error Details:</strong><br>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>

            <div class="btn-group">
                <a href="meal.php" class="btn btn-danger">Retry Selection</a>
            </div>

        <?php } ?>

    </div>

</body>
</html>