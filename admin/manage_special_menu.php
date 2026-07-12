<?php
session_start();

/* ==========================================================================
   1. DATABASE CONNECTIVITY & SESSION CHECK
   ========================================================================== */
require_once __DIR__ . "/../config/db_connect.php";

// Verify admin login state
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$message = "";
$message_type = "";

/* ==========================================================================
   2. AUTOMATED REMOVAL OF EXPIRED SPECIAL MENUS
   ========================================================================== */
$today = date("Y-m-d");
$expiry_stmt = $pdo->prepare("UPDATE menu SET is_active = 0 WHERE is_special = 1 AND special_date < :today");
$expiry_stmt->execute(['today' => $today]);

/* ==========================================================================
   3. DELETE ENTRY ROUTE
   ========================================================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_stmt = $pdo->prepare("DELETE FROM menu WHERE id = :id AND is_special = 1");
    $delete_stmt->execute(['id' => $id]);
    
    header("Location: manage_special_menu.php");
    exit();
}

/* ==========================================================================
   4. FETCH RECORD FOR EDIT MODE
   ========================================================================== */
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_stmt = $pdo->prepare("SELECT * FROM menu WHERE id = :id AND is_special = 1");
    $edit_stmt->execute(['id' => $id]);
    $edit = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================================================================
   5. SAVE OR UPDATE DATA PROCESSING (POST INTERCEPTOR)
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id   = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $day  = $_POST['day'] ?? date('l', strtotime($_POST['special_date']));
    $date = $_POST['special_date'];

    /* PREVENT DUPLICATE CONFIGURATIONS FOR THE SAME DATE */
    $check_stmt = $pdo->prepare("SELECT id FROM menu WHERE special_date = :date AND is_special = 1 AND id != :id");
    $check_stmt->execute(['date' => $date, 'id' => $id ?? 0]);
    
    if ($check_stmt->fetch()) {
        $message = "❌ Schedule Conflict: A special menu configuration has already been saved for this date.";
        $message_type = "error";
    } else {
        // Evaluate input check toggles
        $has_lunch  = isset($_POST['enable_lunch']) ? 1 : 0;
        $has_dinner = isset($_POST['enable_dinner']) ? 1 : 0;
        $has_base   = isset($_POST['enable_base']) ? 1 : 0;

        $lunch_veg     = $has_lunch ? trim($_POST['lunch_veg']) : "";
        $lunch_nonveg  = $has_lunch ? trim($_POST['lunch_nonveg']) : "";
        $dinner_veg    = $has_dinner ? trim($_POST['dinner_veg']) : "";
        $dinner_nonveg = $has_dinner ? trim($_POST['dinner_nonveg']) : "";

        $has_lunch_nonveg  = ($has_lunch && !empty($lunch_nonveg)) ? 1 : 0;
        $has_dinner_nonveg = ($has_dinner && !empty($dinner_nonveg)) ? 1 : 0;

        $lunch_veg_price     = $has_lunch ? floatval($_POST['lunch_veg_price']) : 0.00;
        $lunch_nonveg_price  = $has_lunch ? floatval($_POST['lunch_nonveg_price']) : 0.00;
        $dinner_veg_price    = $has_dinner ? floatval($_POST['dinner_veg_price']) : 0.00;
        $dinner_nonveg_price = $has_dinner ? floatval($_POST['dinner_nonveg_price']) : 0.00;

        if ($id !== null) {
            /* UPDATE EXISTING CONFIGURATION */
            $stmt = $pdo->prepare("
                UPDATE menu SET
                    day = :day, special_date = :date,
                    has_lunch = :has_lunch, has_lunch_nonveg = :has_lunch_nonveg,
                    lunch_veg = :lunch_veg, lunch_nonveg = :lunch_nonveg,
                    has_dinner = :has_dinner, has_dinner_nonveg = :has_dinner_nonveg,
                    dinner_veg = :dinner_veg, dinner_nonveg = :dinner_nonveg,
                    has_base_option = :has_base,
                    special_lunch_veg_price = :l_v_p, special_lunch_nonveg_price = :l_nv_p,
                    special_dinner_veg_price = :d_v_p, special_dinner_nonveg_price = :d_nv_p
                WHERE id = :id
            ");
            $params = [
                'day' => $day, 'date' => $date, 'has_lunch' => $has_lunch, 'has_lunch_nonveg' => $has_lunch_nonveg,
                'lunch_veg' => $lunch_veg, 'lunch_nonveg' => $lunch_nonveg, 'has_dinner' => $has_dinner,
                'has_dinner_nonveg' => $has_dinner_nonveg, 'dinner_veg' => $dinner_veg, 'dinner_nonveg' => $dinner_nonveg,
                'has_base' => $has_base, 'l_v_p' => $lunch_veg_price, 'l_nv_p' => $lunch_nonveg_price,
                'd_v_p' => $dinner_veg_price, 'd_nv_p' => $dinner_nonveg_price, 'id' => $id
            ];
            $stmt->execute($params);
            $message = "✏️ Menu Updated: Your changes to the special menu items were saved successfully.";
            $message_type = "success";
        } else {
            /* SAVE NEW CONFIGURATION ENTRY */
            $stmt = $pdo->prepare("
                INSERT INTO menu (
                    day, special_date, is_special, is_active,
                    has_lunch, has_lunch_nonveg, lunch_veg, lunch_nonveg,
                    has_dinner, has_dinner_nonveg, dinner_veg, dinner_nonveg,
                    has_base_option,
                    special_lunch_veg_price, special_lunch_nonveg_price,
                    special_dinner_veg_price, special_dinner_nonveg_price
                ) VALUES (
                    :day, :date, 1, 1,
                    :has_lunch, :has_lunch_nonveg, :lunch_veg, :lunch_nonveg,
                    :has_dinner, :has_dinner_nonveg, :dinner_veg, :dinner_nonveg,
                    :has_base, :l_v_p, :l_nv_p, :d_v_p, :d_nv_p
                )
            ");
            $params = [
                'day' => $day, 'date' => $date, 'has_lunch' => $has_lunch, 'has_lunch_nonveg' => $has_lunch_nonveg,
                'lunch_veg' => $lunch_veg, 'lunch_nonveg' => $lunch_nonveg, 'has_dinner' => $has_dinner,
                'has_dinner_nonveg' => $has_dinner_nonveg, 'dinner_veg' => $dinner_veg, 'dinner_nonveg' => $dinner_nonveg,
                'has_base' => $has_base, 'l_v_p' => $lunch_veg_price, 'l_nv_p' => $lunch_nonveg_price,
                'd_v_p' => $dinner_veg_price, 'd_nv_p' => $dinner_nonveg_price
            ];
            $stmt->execute($params);
            $message = "🎉 Menu Added: The new special food items have been published to the system.";
            $message_type = "success";
        }
    }
}

/* ==========================================================================
   6. EXTRACT ALL REGISTERED ENTRIES
   ========================================================================== */
$list_stmt = $pdo->query("SELECT * FROM menu WHERE is_special = 1 ORDER BY special_date DESC");
$existing_menus = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Menu Management Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        body {
            background: #E2E8F0;
            color: #0F172A;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* CARD CONTAINERS */
        .card {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
            margin-bottom: 24px;
        }

        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-wrapper h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }

        /* FLEX AND INTERACTION ROW GRIDS */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 14px;
        }

        label.field-title {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 6px;
            display: block;
        }

        input[type="text"], input[type="date"], input[type="number"], select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus, select:focus {
            border-color: #2563EB;
        }

        /* CARD SEGMENTS AND SWITCH OPTIONS */
        .section-block {
            background: #F8FAFC;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            border-left: 4px solid #2563EB;
            margin-top: 20px;
        }

        .section-block h3 {
            font-size: 15px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .switch-container {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
        }

        /* GRID TABLES SETUP */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
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
            padding: 16px;
            border-bottom: 2px solid #E2E8F0;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 14px;
        }

        /* STATUS BADGES & INTERACTION CONTROLS */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-active { background: #DCFCE7; color: #14532D; }
        .badge-expired { background: #FEE2E2; color: #7F1D1D; }

        .action-link {
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            margin-right: 12px;
        }
        .act-edit { color: #D97706; }
        .act-delete { color: #DC2626; }

        .btn-submit {
            background: #0F172A;
            color: #FFFFFF;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            margin-top: 20px;
            width: auto;
        }
        .btn-submit:hover { background: #1E293B; }

        .btn-back {
            display: inline-block;
            padding: 10px 16px;
            background: #FFFFFF;
            color: #475569;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }
        .btn-back:hover { background: #F8FAFC; color: #0F172A; }

        .toast-msg {
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .toast-success { background: #DCFCE7; color: #14532D; border-left: 4px solid #22C55E; }
        .toast-error { background: #FEE2E2; color: #7F1D1D; border-left: 4px solid #EF4444; }

        input:disabled { background: #E2E8F0; cursor: not-allowed; }

        /* ==========================================================================
           7. TARGETED MOBILE LAYOUT OPTIMIZATIONS (Laptop Layout Remains Intact)
           ========================================================================== */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px;
            }

            .card {
                padding: 16px;
                border-radius: 12px;
            }

            .header-wrapper {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .header-wrapper h2 {
                font-size: 18px;
            }

            .btn-back, .btn-submit {
                width: 100%;
                text-align: center;
            }

            .grid-2 {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .grid-4 {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            /* Responsive Visual Table Reconstruction Engine */
            .table-responsive {
                border: none;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                background: #F8FAFC;
                border: 1px solid #E2E8F0;
                border-radius: 12px;
                margin-bottom: 16px;
                padding: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            }

            td {
                border-bottom: 1px dashed #E2E8F0;
                padding: 10px 0;
                position: relative;
                padding-left: 40%;
                text-align: left;
                font-size: 13px;
            }

            td:last-child {
                border-bottom: none;
                text-align: right;
                padding-left: 0;
                margin-top: 6px;
            }

            /* Dynamic CSS Data Label Injections */
            td::before {
                position: absolute;
                left: 0;
                width: 35%;
                white-space: nowrap;
                font-weight: 700;
                color: #475569;
                text-transform: uppercase;
                font-size: 11px;
                letter-spacing: 0.02em;
            }

            td:nth-of-type(1)::before { content: "Weekday"; }
            td:nth-of-type(2)::before { content: "Date"; }
            td:nth-of-type(3)::before { content: "Lunch"; }
            td:nth-of-type(4)::before { content: "Dinner"; }
            td:nth-of-type(5)::before { content: "Status"; }
            td:nth-of-type(6)::before { content: ""; }
            
            .action-link {
                display: inline-block;
                padding: 6px 14px;
                border-radius: 6px;
                background: #FFF;
                border: 1px solid #CBD5E1;
            }
            .act-edit { color: #D97706; border-color: #FCD34D; }
            .act-delete { color: #DC2626; border-color: #FCA5A5; }
        }
    </style>
    <script>
        function processSyncStates() {
            let lunchChecked = document.getElementById("enable_lunch").checked;
            document.querySelectorAll(".lunch-field").forEach(field => field.disabled = !lunchChecked);

            let dinnerChecked = document.getElementById("enable_dinner").checked;
            document.querySelectorAll(".dinner-field").forEach(field => field.disabled = !dinnerChecked);
        }

        window.onload = function() {
            processSyncStates();
            document.getElementById("enable_lunch").addEventListener("change", processSyncStates);
            document.getElementById("enable_dinner").addEventListener("change", processSyncStates);
        };
    </script>
</head>
<body>

    <div class="container">

        <div class="header-wrapper">
            <h2>🎉 Special Menu Management Panel</h2>
            <a href="dashboard.php" class="btn-back">⬅ Return to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="toast-msg toast-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit['id'] ?? ''); ?>">

                <div class="grid-2">
                    <div>
                        <label class="field-title">Day of the Week</label>
                        <select name="day" required>
                            <?php
                            $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                            foreach ($days as $d) {
                                $selected = (isset($edit['day']) && $edit['day'] === $d) ? "selected" : "";
                                echo "<option value=\"$d\" $selected>$d</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-title">Target Calendar Date</label>
                        <input type="date" name="special_date" value="<?php echo htmlspecialchars($edit['special_date'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="section-block">
                    <h3>🍽 Special Lunch Configurations</h3>
                    <label class="switch-container">
                        <input type="checkbox" id="enable_lunch" name="enable_lunch" <?php echo (!empty($edit['has_lunch'])) ? "checked" : ""; ?>>
                        Enable Special Lunch Event
                    </label>

                    <div class="grid-4">
                        <div>
                            <label class="field-title">Vegetarian Dishes</label>
                            <input type="text" class="lunch-field" name="lunch_veg" value="<?php echo htmlspecialchars($edit['lunch_veg'] ?? ''); ?>" placeholder="e.g., Shahi Paneer Special">
                        </div>
                        <div>
                            <label class="field-title">Non-Vegetarian Dishes</label>
                            <input type="text" class="lunch-field" name="lunch_nonveg" value="<?php echo htmlspecialchars($edit['lunch_nonveg'] ?? ''); ?>" placeholder="e.g., Murgh Korma">
                        </div>
                        <div>
                            <label class="field-title">Veg Price (INR)</label>
                            <input type="number" step="0.01" class="lunch-field" name="lunch_veg_price" value="<?php echo htmlspecialchars($edit['special_lunch_veg_price'] ?? ''); ?>" placeholder="0.00">
                        </div>
                        <div>
                            <label class="field-title">Non-Veg Price (INR)</label>
                            <input type="number" step="0.01" class="lunch-field" name="lunch_nonveg_price" value="<?php echo htmlspecialchars($edit['special_lunch_nonveg_price'] ?? ''); ?>" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="section-block">
                    <h3>🌙 Special Dinner Configurations</h3>
                    <label class="switch-container">
                        <input type="checkbox" id="enable_dinner" name="enable_dinner" <?php echo (!empty($edit['has_dinner'])) ? "checked" : ""; ?>>
                        Enable Special Dinner Event
                    </label>

                    <div class="grid-4">
                        <div>
                            <label class="field-title">Vegetarian Dishes</label>
                            <input type="text" class="dinner-field" name="dinner_veg" value="<?php echo htmlspecialchars($edit['dinner_veg'] ?? ''); ?>" placeholder="e.g., Kadhai Chaap Special">
                        </div>
                        <div>
                            <label class="field-title">Non-Vegetarian Dishes</label>
                            <input type="text" class="dinner-field" name="dinner_nonveg" value="<?php echo htmlspecialchars($edit['dinner_nonveg'] ?? ''); ?>" placeholder="e.g., Handi Chicken">
                        </div>
                        <div>
                            <label class="field-title">Veg Price (INR)</label>
                            <input type="number" step="0.01" class="dinner-field" name="dinner_veg_price" value="<?php echo htmlspecialchars($edit['special_dinner_veg_price'] ?? ''); ?>" placeholder="0.00">
                        </div>
                        <div>
                            <label class="field-title">Non-Veg Price (INR)</label>
                            <input type="number" step="0.01" class="dinner-field" name="dinner_nonveg_price" value="<?php echo htmlspecialchars($edit['special_dinner_nonveg_price'] ?? ''); ?>" placeholder="0.00">
                        </div>
                    </div>

                    <label class="switch-container" style="margin-top: 16px; padding-top: 12px; border-top: 1px dashed #CBD5E1;">
                        <input type="checkbox" name="enable_base" <?php echo (!empty($edit['has_base_option'])) ? "checked" : ""; ?>>
                        Allow Base Subsitutes (Options for Rice / Roti Alternates)
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    <?php echo $edit ? "✏️ Update Selected Menu" : "💾 Publish Special Menu Entry"; ?>
                </button>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 16px; color: #0F172A;">📋 Saved Special Menu Events</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Weekday</th>
                            <th>Calendar Date</th>
                            <th>Lunch Details & Pricing</th>
                            <th>Dinner Details & Pricing</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($existing_menus) === 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748B; padding: 30px; font-weight: 500;">No special menu logs found in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($existing_menus as $row): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['day']); ?></strong></td>
                                    <td><?php echo date("d M Y", strtotime($row['special_date'])); ?></td>
                                    <td>
                                        <?php if ($row['has_lunch']): ?>
                                            🟢 Veg: ₹<?php echo $row['special_lunch_veg_price']; ?> | Non-Veg: ₹<?php echo $row['special_lunch_nonveg_price']; ?>
                                        <?php else: ?>
                                            <span style="color: #94A3B8;">Not Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['has_dinner']): ?>
                                            🔵 Veg: ₹<?php echo $row['special_dinner_veg_price']; ?> | Non-Veg: ₹<?php echo $row['special_dinner_nonveg_price']; ?>
                                        <?php else: ?>
                                            <span style="color: #94A3B8;">Not Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $row['is_active'] ? 'badge-active' : 'badge-expired'; ?>">
                                            <?php echo $row['is_active'] ? 'Active' : 'Expired'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?edit=<?php echo $row['id']; ?>" class="action-link act-edit">Edit</a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-link act-delete" onclick="return confirm('Delete this special menu configuration? This cannot be undone.')">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>