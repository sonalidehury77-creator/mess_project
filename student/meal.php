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

/* ==========================================================================
   LOGIN PROTECTION (Cleaned & Modernized Error Inline Card)
   ========================================================================== */
if (!isset($_SESSION['hostel_roll'])) {
    echo "
    <div style='max-width: 450px; margin: 80px auto; padding: 40px 24px; background: #FFFFFF; text-align: center; border-radius: 16px; box-shadow: 0 10px 25px rgba(11, 19, 43, 0.05); font-family: \"Segoe UI\", sans-serif; border: 1px solid #E2E8F0;'>
        <div style='font-size: 48px; margin-bottom: 16px;'>⚠️</div>
        <h2 style='color: #0B132B; margin-bottom: 24px; font-weight: 700;'>Authentication Required</h2>
        <p style='color: #64748B; margin-bottom: 32px; font-size: 15px;'>Please log in to your account to manage or save your daily hostel meals.</p>
        <a href='login.html' style='display: inline-block; padding: 12px 32px; background: #10B981; color: white; text-decoration: none; font-weight: 600; border-radius: 8px; transition: background 0.2s;'>🔐 Go to Login</a>
    </div>";
    exit();
}

date_default_timezone_set("Asia/Kolkata");

/* ==========================================================================
   DATE LOGIC
   ========================================================================== */
$now = time();
$deadline_today = strtotime("22:00");

$today = date("l");
$day = date("l", strtotime("+1 day"));
$tomorrow_date = date("Y-m-d", strtotime("+1 day"));
$hostel_roll = $_SESSION['hostel_roll'];
$isExpired = ($now >= $deadline_today);

// Dynamic Greeting based on current time
$current_hour = (int)date('H');
if ($current_hour < 12) {
    $greeting = "Good Morning";
} elseif ($current_hour < 17) {
    $greeting = "Good Afternoon";
} else {
    $greeting = "Good Evening";
}

/* ==========================================================================
   FETCH NORMAL MENU (PDO Adjusted Syntax)
   ========================================================================== */
$stmt = $conn->prepare("SELECT * FROM menu WHERE day=? AND is_special=0 LIMIT 1");
$stmt->execute([$day]);
$normal_menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$normal_menu) {
    die("<div style='padding: 24px; text-align:center; font-family:sans-serif; color: #EF4444;'>⚠ Configuration Error: No standard menu configuration found mapping to: $day</div>");
}

/* ==========================================================================
   FETCH SPECIAL MENU (PDO Adjusted Syntax)
   ========================================================================== */
$stmt = $conn->prepare("SELECT * FROM menu WHERE special_date=? AND is_special=1 AND is_active=1 LIMIT 1");
$stmt->execute([$tomorrow_date]);
$special_menu = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$row = $normal_menu;

/* Apply Lunch overrides if custom special configuration properties exist */
if ($special_menu && !empty($special_menu['lunch_veg'])) {
    $row['lunch_veg'] = $special_menu['lunch_veg'];
    $row['lunch_nonveg'] = $special_menu['lunch_nonveg'];
    $row['has_lunch_nonveg'] = $special_menu['has_lunch_nonveg'];
    $row['lunch_veg_price'] = $special_menu['special_lunch_veg_price'] ?? $row['lunch_veg_price'];
    $row['lunch_nonveg_price'] = $special_menu['special_lunch_nonveg_price'] ?? $row['lunch_nonveg_price'];
}

/* Apply Dinner overrides if custom special configuration properties exist */
if ($special_menu && !empty($special_menu['dinner_veg'])) {
    $row['dinner_veg'] = $special_menu['dinner_veg'];
    $row['dinner_nonveg'] = $special_menu['dinner_nonveg'];
    $row['has_dinner_nonveg'] = $special_menu['has_dinner_nonveg'];
    $row['dinner_veg_price'] = $special_menu['special_dinner_veg_price'] ?? $row['dinner_veg_price'];
    $row['dinner_nonveg_price'] = $special_menu['special_dinner_nonveg_price'] ?? $row['dinner_nonveg_price'];
}

if ($special_menu) {
    $row['has_base_option'] = $special_menu['has_base_option'];
    $row['is_special'] = 1;
} else {
    $row['is_special'] = 0;
}

$lunch_veg_price = $row['lunch_veg_price'];
$lunch_nonveg_price = $row['lunch_nonveg_price'];
$dinner_veg_price = $row['dinner_veg_price'];
$dinner_nonveg_price = $row['dinner_nonveg_price'];

/* ==========================================================================
   FETCH PREVIOUS SELECTION (PDO Adjusted Syntax)
   ========================================================================== */
$stmt = $conn->prepare("SELECT * FROM meals WHERE hostel_roll=? AND date=?");
$stmt->execute([$hostel_roll, $tomorrow_date]);
$db_selected = $stmt->fetch(PDO::FETCH_ASSOC);

$selected = [
    'breakfast' => 0,
    'lunch' => 0,
    'lunch_type' => '',
    'dinner' => 0,
    'dinner_type' => '',
    'base' => '',
    'locked' => 0
];

if ($db_selected) {
    $selected = $db_selected;
}

$isLocked = !empty($selected['locked']);

/* ==========================================================================
   AUTO LOCK AFTER 10PM (PDO Adjusted Syntax)
   ========================================================================== */
if ($isExpired && !$isLocked) {
    $lockStmt = $conn->prepare("UPDATE meals SET locked=1 WHERE hostel_roll=? AND date=?");
    $lockStmt->execute([$hostel_roll, $tomorrow_date]);
    $isLocked = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Selection Portal</title>
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
            padding: 40px 20px;
        }

        .main-wrapper {
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
        }

        .container {
            background: #FFFFFF;
            padding: 36px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(11, 19, 43, 0.04);
            border: 1px solid #E2E8F0;
        }

        .header-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid #F1F5F9;
        }

        .welcome-box h2 {
            font-size: 20px;
            color: #0B132B;
            font-weight: 700;
        }

        .welcome-box .name {
            color: #10B981;
        }

        .menu-btn {
            padding: 10px 18px;
            background: #0B132B;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: opacity 0.2s ease;
        }

        .menu-btn:hover {
            opacity: 0.9;
        }

        h3 {
            font-size: 18px;
            color: #0B132B;
            margin-bottom: 6px;
            font-weight: 700;
        }

        .sub-instructions {
            font-size: 14px;
            color: #64748B;
            margin-bottom: 24px;
        }

        .timer {
            font-weight: 700;
            color: #EF4444;
            background: rgba(239, 68, 68, 0.06);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 15px;
            margin-bottom: 20px;
            border: 1px dashed rgba(239, 68, 68, 0.2);
        }

        .card-alert {
            padding: 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-alert.locked {
            background: #FEF2F2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }

        .card-alert.special-banner {
            background: #ECFDF5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }

        .summary-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 28px;
        }

        .summary-box h5 {
            color: #0B132B;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            font-size: 14px;
            color: #475569;
        }

        .summary-grid span {
            font-weight: 600;
            color: #0B132B;
        }

        .section-title {
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            margin: 24px 0 12px 0;
            font-weight: 700;
        }

        .display-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.01);
        }

        .display-card-row {
            margin-top: 8px;
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
        }

        .price-tag {
            color: #10B981;
            font-weight: 700;
            background: rgba(16, 185, 129, 0.08);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
            display: inline-block;
            margin-left: 4px;
        }

        .badge-special {
            background: #EF4444;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: 700;
            margin-left: 6px;
            vertical-align: middle;
        }

        .selection-form-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 16px;
        }

        .selection-form-card h4 {
            color: #0B132B;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .interactive-label {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #FFFFFF;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            margin-top: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            transition: background 0.2s;
        }

        .interactive-label:hover {
            background: #F1F5F9;
        }

        .interactive-label input[type="checkbox"],
        .interactive-label input[type="radio"] {
            width: 16px;
            height: 16px;
            accent-color: #10B981;
        }

        .sub-options-group {
            margin-top: 10px;
            padding-left: 16px;
            border-left: 2px dashed #CBD5E1;
        }

        .base-title {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            margin: 12px 0 4px 0;
        }

        hr {
            border: 0;
            height: 1px;
            background: #E2E8F0;
            margin: 32px 0;
        }

        .btn-submit-save {
            width: 100%;
            padding: 14px;
            background: #10B981;
            color: #FFFFFF;
            border: 1px solid #059669;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }

        .btn-submit-save:hover {
            background: #059669;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.25);
        }

        .btn-submit-save:disabled {
            background: #94A3B8;
            border-color: #94A3B8;
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.6;
        }
    </style>

    <script>
        function toggleLunch() {
            let check = document.getElementById("lunch_check").checked;
            let options = document.getElementsByName("lunch_type");
            options.forEach(opt => {
                opt.disabled = !check;
                if (check && opt.value === "veg") {
                    opt.checked = true;
                }
            });
        }

        function toggleDinner() {
            let check = document.getElementById("dinner_check").checked;
            let options = document.getElementsByName("dinner_type");
            let base = document.getElementsByName("base");

            options.forEach(opt => {
                opt.disabled = !check;
                if (check && opt.value === "veg") {
                    opt.checked = true;
                }
            });

            base.forEach(opt => {
                opt.disabled = !check;
                if (check && opt.value === "rice") {
                    opt.checked = true;
                }
            });
        }

        function updateTimer() {
            let now = new Date();
            let deadline = new Date();
            deadline.setHours(22, 0, 0, 0);
            let diff = deadline - now;

            if (diff <= 0) {
                document.getElementById("timer").innerHTML = "⛔ Choice Modification Window Closed";
                let btn = document.getElementById("submit_button");
                if (btn) btn.disabled = true;
                return;
            }

            let hrs = Math.floor(diff / (1000 * 60 * 60));
            let mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            document.getElementById("timer").innerHTML = "⏳ Choice Window: " + hrs + "h " + mins + "m remaining to save choices";
        }

        setInterval(updateTimer, 1000);

        window.onload = function() {
            toggleLunch();
            toggleDinner();
            updateTimer();
        }
    </script>
</head>

<body>

    <div class="main-wrapper">
        <div class="container">

            <div class="header-navigation">
                <div class="welcome-box">
                    <h2>👋 <?php echo $greeting; ?>, <span class="name"><?php echo htmlspecialchars($_SESSION['name']); ?></span></h2>
                </div>
                <a href="dashboard.php" class="menu-btn">⬅ Dashboard</a>
            </div>

            <h3>Meal Preference Settings</h3>
            <p class="sub-instructions">Select your options for <b><?php echo date("l, d M Y", strtotime($tomorrow_date)); ?></b></p>

            <div id="timer" class="timer">⏳ Loading current window status...</div>

            <?php if ($isExpired || $isLocked) { ?>
                <div class="card-alert locked">
                    <span>🔒 Meal selections are locked for tomorrow. System locks automatically every day at 10:00 PM.</span>
                </div>
            <?php } ?>

            <?php if ($row['is_special'] == 1) { ?>
                <div class="card-alert special-banner">
                    <span>🎉 Special menu is active! Updated prices have been applied.</span>
                </div>
            <?php } ?>

            <?php if ($db_selected) { ?>
                <div class="summary-box">
                    <h5>📋 Your Current Saved Selections</h5>
                    <div class="summary-grid">
                        <div>Breakfast: <span><?php echo (!empty($selected['breakfast']) ? 'Opted In' : 'Not Selected'); ?></span></div>
                        <div>Lunch: <span><?php echo (!empty($selected['lunch']) ? ucfirst($selected['lunch_type']) : 'Not Selected'); ?></span></div>
                        <div>Dinner: <span><?php echo (!empty($selected['dinner']) ? ucfirst($selected['dinner_type']) : 'Not Selected'); ?></span></div>
                        <div>Main Option: <span><?php echo (!empty($selected['base']) ? ucfirst($selected['base']) : '-'); ?></span></div>
                    </div>
                </div>
            <?php } ?>

            <div class="section-title">Tomorrow's Menu Details</div>

            <div class="display-card">
                <strong>🍞 Breakfast</strong>
                <div class="display-card-row"><?php echo $row['breakfast']; ?></div>
            </div>

            <div class="display-card">
                <strong>
                    🍛 Lunch
                    <?php if ($special_menu) {
                        echo '<span class="badge-special">Special Event</span>';
                    } ?>
                </strong>
                <div class="display-card-row">
                    🟢 Veg: <?php echo $row['lunch_veg']; ?> <span class="price-tag">₹ <?php echo $lunch_veg_price; ?></span>
                    <?php if ($special_menu && !empty($special_menu['lunch_veg'])) {
                        echo " <small style='color:#EF4444; font-weight:600;'>★</small>";
                    } ?>

                    <?php if (!empty($row['has_lunch_nonveg'])) { ?>
                        <br>🔴 Non-Veg: <?php echo $row['lunch_nonveg']; ?> <span class="price-tag">₹ <?php echo $lunch_nonveg_price; ?></span>
                        <?php if ($special_menu && !empty($special_menu['lunch_veg'])) {
                            echo " <small style='color:#EF4444; font-weight:600;'>★</small>";
                        } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="display-card">
                <strong>
                    🌙 Dinner
                    <?php if ($special_menu) {
                        echo '<span class="badge-special">Special Event</span>';
                    } ?>
                </strong>
                <div class="display-card-row">
                    🟢 Veg: <?php echo $row['dinner_veg']; ?> <span class="price-tag">₹ <?php echo $dinner_veg_price; ?></span>
                    <?php if ($special_menu && !empty($special_menu['dinner_veg'])) {
                        echo " <small style='color:#EF4444; font-weight:600;'>★</small>";
                    } ?>

                    <?php if (!empty($row['has_dinner_nonveg'])) { ?>
                        <br>🔴 Non-Veg: <?php echo $row['dinner_nonveg']; ?> <span class="price-tag">₹ <?php echo $dinner_nonveg_price; ?></span>
                        <?php if ($special_menu && !empty($special_menu['dinner_veg'])) {
                            echo " <small style='color:#EF4444; font-weight:600;'>★</small>";
                        } ?>
                    <?php } ?>

                    <?php if (!empty($row['has_base_option'])) { ?>
                        <div style="margin-top: 8px; font-weight:600; font-size:13px; color:#475569;">🌾 Choice of Roti or Rice is available below.</div>
                    <?php } ?>
                </div>
            </div>

            <hr>

            <form method="post" action="save_meal.php" <?php if ($isExpired || $isLocked) {
                                                            echo "style='pointer-events:none; opacity:0.65;'";
                                                        } ?>>

                <input type="hidden" name="day" value="<?php echo $day; ?>">
                <input type="hidden" name="is_special" value="<?php echo $special_menu ? 1 : 0; ?>">

                <div class="section-title">Select Your Choice</div>

                <div class="selection-form-card">
                    <h4>🍞 Breakfast Selection</h4>
                    <label class="interactive-label">
                        <input type="checkbox" name="breakfast" <?php if (!empty($selected['breakfast'])) echo "checked"; ?>>
                        <span>Include Breakfast</span>
                    </label>
                </div>

                <div class="selection-form-card">
                    <h4>🍛 Lunch Selection</h4>
                    <label class="interactive-label">
                        <input type="checkbox" name="take_lunch" id="lunch_check" <?php if (!empty($selected['lunch'])) echo "checked"; ?> onclick="toggleLunch()">
                        <span>Include Lunch</span>
                    </label>

                    <div class="sub-options-group">
                        <label class="interactive-label">
                            <input type="radio" name="lunch_type" value="veg" <?php if ($selected['lunch_type'] == "veg" || empty($selected['lunch_type'])) echo "checked"; ?>>
                            <span>Vegetarian Variant</span>
                        </label>
                        <?php if ($row['has_lunch_nonveg']) { ?>
                            <label class="interactive-label">
                                <input type="radio" name="lunch_type" value="nonveg" <?php if ($selected['lunch_type'] == "nonveg") echo "checked"; ?>>
                                <span>Non-Vegetarian Variant</span>
                            </label>
                        <?php } ?>
                    </div>
                </div>

                <div class="selection-form-card">
                    <h4>🌙 Dinner Selection</h4>
                    <label class="interactive-label">
                        <input type="checkbox" name="take_dinner" id="dinner_check" <?php if (!empty($selected['dinner'])) echo "checked"; ?> onclick="toggleDinner()">
                        <span>Include Dinner</span>
                    </label>

                    <div class="sub-options-group">
                        <label class="interactive-label">
                            <input type="radio" name="dinner_type" value="veg" <?php if ($selected['dinner_type'] == "veg" || empty($selected['dinner_type'])) echo "checked"; ?>>
                            <span>Vegetarian Variant</span>
                        </label>
                        <?php if ($row['has_dinner_nonveg']) { ?>
                            <label class="interactive-label">
                                <input type="radio" name="dinner_type" value="nonveg" <?php if ($selected['dinner_type'] == "nonveg") echo "checked"; ?>>
                                <span>Non-Vegetarian Variant</span>
                            </label>
                        <?php } ?>

                        <?php if (!empty($row['has_base_option'])) { ?>
                            <div class="base-title">Choose your Main Item:</div>
                            <label class="interactive-label">
                                <input type="radio" name="base" value="rice" <?php if ($selected['base'] == "rice" || empty($selected['base'])) echo "checked"; ?>>
                                <span>Steamed Rice</span>
                            </label>
                            <label class="interactive-label">
                                <input type="radio" name="base" value="roti" <?php if ($selected['base'] == "roti") echo "checked"; ?>>
                                <span>Handmade Roti</span>
                            </label>
                        <?php } ?>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <button type="submit" id="submit_button" class="btn-submit-save" <?php if ($isExpired || $isLocked) echo "disabled"; ?>>
                        ✅ Save Meal Selections
                    </button>
                </div>

            </form>
        </div>
    </div>

</body>

</html>