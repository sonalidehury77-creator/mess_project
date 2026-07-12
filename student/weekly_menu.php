<?php
session_start();
// Look into the root config folder
include("../config/db_connect.php");

/* ==========================================================================
   1. DATABASE CONNECTION HANDLER
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
            ⚠ Database Connection Error: No active database connection found inside config/db_connect.php.
        </div>");
    }
}

/* ==========================================================================
   2. AUTHENTICATION PROTECTION CHECK
   ========================================================================== */
if (!isset($_SESSION['hostel_roll'])) {
    // Standard path across the system modules
    header("Location: ../auth/login.php");
    exit();
}

/* ==========================================================================
   3. SYSTEM TIME DEFINITIONS
   ========================================================================== */
date_default_timezone_set("Asia/Kolkata");

$today = date("l");
$tomorrow = date("l", strtotime("+1 day"));

/* ==========================================================================
   4. DATA FETCH ENGINE (WEEKLY & SPECIAL FEASTS)
   ========================================================================== */
$sql = "
    SELECT *
    FROM menu
    WHERE is_special = 0
    ORDER BY FIELD(
        day,
        'Monday','Tuesday','Wednesday',
        'Thursday','Friday','Saturday','Sunday'
    )
";
$stmt = $conn->query($sql);
$weekly_meals = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

if (empty($weekly_meals)) {
    die("<div style='padding: 40px; text-align:center; font-family:sans-serif; color:#EF4444;'><h2>❌ No Weekly Mess Menu Profile Found</h2></div>");
}

// Fetch active special events scheduled for today onwards
$special_sql = "
    SELECT *
    FROM menu
    WHERE is_special = 1
    AND is_active = 1
    AND special_date >= CURDATE()
    ORDER BY special_date ASC
";
$sp_stmt = $conn->query($special_sql);
$special_meals = $sp_stmt ? $sp_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Mess Menu Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background-color: #F1F5F9; color: #1E293B; min-height: 100vh; padding: 40px 20px; }
        .container { width: 100%; max-width: 1100px; margin: 0 auto; background: #FFFFFF; padding: 40px; border-radius: 16px; box-shadow: 0 4px 25px rgba(15, 23, 42, 0.04); border: 1px solid #E2E8F0; }
        
        .header-wrapper { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #F1F5F9; padding-bottom: 24px; margin-bottom: 32px; gap: 20px; flex-wrap: wrap; }
        h2 { font-size: 24px; color: #0F172A; font-weight: 800; letter-spacing: -0.02em; }
        
        #searchBox { width: 280px; padding: 10px 16px; border-radius: 8px; border: 1px solid #CBD5E1; font-size: 14px; font-weight: 500; outline: none; transition: all 0.15s ease; }
        #searchBox:focus { border-color: #3B82F6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        /* DATA TABLES STYLE */
        table { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden; }
        th { background: #F8FAFC; color: #475569; padding: 14px 18px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; text-align: left; border-bottom: 1px solid #E2E8F0; }
        td { padding: 18px; text-align: left; font-size: 14px; border-bottom: 1px solid #E2E8F0; line-height: 1.6; vertical-align: top; }
        tr:last-child td { border-bottom: none; }

        /* VISUAL ROW TRACKING BACKGROUNDS */
        .tr-today { background-color: #F0FDF4 !important; }
        .tr-tomorrow { background-color: #EFF6FF !important; }
        
        .day-badge { display: inline-block; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; margin-top: 6px; text-transform: uppercase; letter-spacing: 0.02em; }
        .day-badge.today { background: #DCFCE7; color: #14532D; }
        .day-badge.tomorrow { background: #DBEAFE; color: #1E40AF; }

        /* MENU CONTENT BLOCKS */
        .menu-title { font-weight: 600; color: #0F172A; display: block; font-size: 14px; }
        .variant-block { margin-top: 8px; padding: 10px 14px; border-radius: 8px; background: #F8FAFC; border: 1px solid #E2E8F0; }
        .tr-today .variant-block, .tr-tomorrow .variant-block { background: rgba(255, 255, 255, 0.7); }
        
        .veg-text { color: #16A34A; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; display: inline-block; margin-bottom: 2px; }
        .nonveg-text { color: #DC2626; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.03em; display: inline-block; margin-bottom: 2px; }
        .price-tag { font-weight: 700; color: #475569; font-size: 13px; margin-top: 4px; display: block; }
        .note { font-size: 12px; color: #64748B; font-weight: 500; display: block; margin-top: 6px; }

        /* BUTTON CONTEXT CONTAINER */
        .footer-actions { margin-top: 40px; display: flex; justify-content: center; gap: 16px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 8px; transition: all 0.15s ease; cursor: pointer; border: none; }
        .btn-outline { background: #FFFFFF; color: #475569; border: 1px solid #CBD5E1; }
        .btn-outline:hover { background: #F8FAFC; border-color: #94A3B8; color: #0F172A; }
        .btn-success { background: #10B981; color: white; border: 1px solid #059669; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.1); }
        .btn-success:hover { background: #059669; }

        /* SPECIAL BANNER PRESENTATION BOX */
        .special-box { margin-top: 48px; background: linear-gradient(135deg, #FFF7ED, #FFEDD5); padding: 32px; border-radius: 16px; border: 1px solid #FED7AA; }
        .special-title { font-size: 18px; font-weight: 800; color: #C2410C; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .special-table { background: #FFFFFF; border: 1px solid #FED7AA; }
        .special-table th { background: #FFF7ED; color: #C2410C; border-bottom: 1px solid #FED7AA; }
        .special-table td { border-bottom: 1px solid #FED7AA; }

        /* 📱 RESPONSIVE MOBILE OVERRIDES (Does not affect laptop view) */
        @media screen and (max-width: 768px) {
            body { padding: 16px 8px; }
            .container { padding: 20px 16px; border-radius: 12px; }
            
            .header-wrapper { flex-direction: column; align-items: flex-start; gap: 14px; margin-bottom: 20px; }
            #searchBox { width: 100%; }

            /* Flatten the main table layout into cards */
            table, thead, tbody, th, td, tr { display: block; width: 100% !important; }
            thead { display: none; } /* Hide headers on mobile */
            
            .menu-table-body tr { 
                margin-bottom: 24px; 
                border: 1px solid #E2E8F0; 
                border-radius: 12px; 
                overflow: hidden; 
                background: #FFFFFF;
                box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            }
            
            .menu-table-body td { 
                padding: 14px 16px; 
                border-bottom: 1px dashed #E2E8F0; 
            }
            
            .menu-table-body td:first-child { 
                background: #F8FAFC; 
                border-bottom: 1px solid #E2E8F0;
                padding: 12px 16px;
            }
            
            .menu-table-body td:last-child { border-bottom: none; }

            /* Visual text labels before values on mobile view */
            .menu-table-body td:nth-child(2)::before { content: "🍳 Breakfast"; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; display: block; margin-bottom: 6px; letter-spacing: 0.03em; }
            .menu-table-body td:nth-child(3)::before { content: "☀️ Lunch Options"; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; display: block; margin-bottom: 2px; letter-spacing: 0.03em; }
            .menu-table-body td:nth-child(4)::before { content: "🌙 Dinner Options"; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; display: block; margin-bottom: 2px; letter-spacing: 0.03em; }

            /* Flatten the upcoming special feasts table into mobile cards */
            .special-box { padding: 20px 14px; margin-top: 32px; }
            .special-table { border: none; background: transparent; }
            .special-table tr { 
                background: #FFFFFF; 
                border: 1px solid #FED7AA; 
                border-radius: 12px; 
                margin-bottom: 16px; 
                padding: 4px 0;
            }
            .special-table td { padding: 12px 16px; border-bottom: 1px dashed #FFE4E6; }
            .special-table td:first-child { border-bottom: 1px solid #FED7AA; background: #FFF7ED; }
            .special-table td:last-child { border-bottom: none; }
            
            .special-table td:nth-child(2)::before { content: "☀️ Special Lunch"; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #C2410C; display: block; margin-bottom: 4px; }
            .special-table td:nth-child(3)::before { content: "🌙 Special Dinner"; font-size: 11px; text-transform: uppercase; font-weight: 700; color: #C2410C; display: block; margin-bottom: 4px; }

            /* Adjust actions block at base */
            .footer-actions { flex-direction: column-reverse; gap: 12px; margin-top: 24px; }
            .btn { width: 100%; padding: 14px; }
        }

        /* ACCESSIBLE SYSTEM PRINT RULES */
        @media print {
            body { background: white; padding: 0; color: #000; }
            .container { box-shadow: none; border: none; width: 100%; padding: 0; }
            #searchBox, .footer-actions { display: none !important; }
            th { background: #EEEEEE !important; color: black; }
            .tr-today, .tr-tomorrow, .special-box { background: transparent !important; color: black !important; border: 1px solid #CBD5E1; }
        }
    </style>

    <script>
        function searchDay() {
            let input = document.getElementById("searchBox").value.toLowerCase();
            let rows = document.querySelectorAll(".menu-table-body tr");
            rows.forEach(row => {
                let day = row.cells[0].innerText.toLowerCase();
                row.style.display = day.includes(input) ? "" : "none";
            });
        }

        function printMenu() {
            window.print();
        }
    </script>
</head>
<body>

    <div class="container">

        <div class="header-wrapper">
            <h2>📅 Weekly Mess Menu</h2>
            <input type="text" id="searchBox" onkeyup="searchDay()" placeholder="Search by day name...">
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Day</th>
                    <th style="width: 25%;">Breakfast</th>
                    <th style="width: 30%;">Lunch Options</th>
                    <th style="width: 30%;">Dinner Options</th>
                </tr>
            </thead>
            <tbody class="menu-table-body">
                <?php
                foreach ($weekly_meals as $row) {
                    $day_name = htmlspecialchars($row['day']);
                    $tr_class = "";

                    if ($day_name == $today) {
                        $tr_class = "tr-today";
                    } elseif ($day_name == $tomorrow) {
                        $tr_class = "tr-tomorrow";
                    }
                ?>
                    <tr class="<?php echo $tr_class; ?>">
                        <td>
                            <strong style="color: #0F172A; font-size: 15px;"><?php echo $day_name; ?></strong>
                            <?php if ($day_name == $today) echo '<br><span class="day-badge today">🟢 Today</span>'; ?>
                            <?php if ($day_name == $tomorrow) echo '<br><span class="day-badge tomorrow">🔵 Tomorrow</span>'; ?>
                        </td>

                        <td>
                            <span class="menu-title"><?php echo htmlspecialchars($row['breakfast']); ?></span>
                            <span class="price-tag">₹ <?php echo htmlspecialchars($row['breakfast_price']); ?></span>
                        </td>

                        <td>
                            <div class="variant-block">
                                <span class="veg-text">Vegetarian Menu</span><br>
                                <?php echo htmlspecialchars($row['lunch_veg']); ?>
                                <div class="price-tag">₹ <?php echo htmlspecialchars($row['lunch_veg_price']); ?></div>
                            </div>
                            <?php if (!empty($row['has_lunch_nonveg'])) { ?>
                                <div class="variant-block">
                                    <span class="nonveg-text">Non-Vegetarian Menu</span><br>
                                    <?php echo htmlspecialchars($row['lunch_nonveg']); ?>
                                    <div class="price-tag">₹ <?php echo htmlspecialchars($row['lunch_nonveg_price']); ?></div>
                                </div>
                            <?php } ?>
                        </td>

                        <td>
                            <div class="variant-block">
                                <span class="veg-text">Vegetarian Menu</span><br>
                                <?php echo htmlspecialchars($row['dinner_veg']); ?>
                                <div class="price-tag">₹ <?php echo htmlspecialchars($row['dinner_veg_price']); ?></div>
                            </div>
                            <?php if (!empty($row['has_dinner_nonveg'])) { ?>
                                <div class="variant-block">
                                    <span class="nonveg-text">Non-Vegetarian Menu</span><br>
                                    <?php echo htmlspecialchars($row['dinner_nonveg']); ?>
                                    <div class="price-tag">₹ <?php echo htmlspecialchars($row['dinner_nonveg_price']); ?></div>
                                </div>
                            <?php } ?>
                            <?php if (!empty($row['has_base_option'])) { ?>
                                <span class="note">🌾 Choice of Roti or Rice is available at the counter</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php if (!empty($special_meals)) { ?>
            <div class="special-box">
                <div class="special-title">🎉 Upcoming Special Feast Menus</div>
                <table class="special-table">
                    <thead>
                        <tr>
                            <th style="width: 20%;">Event Date</th>
                            <th style="width: 40%;">Special Lunch Menu</th>
                            <th style="width: 40%;">Special Dinner Menu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($special_meals as $sp) { ?>
                            <tr>
                                <td>
                                    <strong style="color: #C2410C;"><?php echo date("d M Y", strtotime($sp['special_date'])); ?></strong>
                                </td>
                                <td>
                                    <span class="menu-title"><?php echo htmlspecialchars($sp['lunch_veg']); ?></span>
                                    <?php if (!empty($sp['has_lunch_nonveg'])) { ?>
                                        <span class="note" style="color: #B45309;">🍗 Non-Veg Plan: <?php echo htmlspecialchars($sp['lunch_nonveg']); ?></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <span class="menu-title"><?php echo htmlspecialchars($sp['dinner_veg']); ?></span>
                                    <?php if (!empty($sp['has_dinner_nonveg'])) { ?>
                                        <span class="note" style="color: #B45309;">🍗 Non-Veg Plan: <?php echo htmlspecialchars($sp['dinner_nonveg']); ?></span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>

        <div class="footer-actions">
            <a href="meal.php" class="btn btn-outline">⬅ Back to Dashboard</a>
            <button onclick="printMenu()" class="btn btn-success">🖨 Print Menu Sheet</button>
        </div>

    </div>

</body>
</html>