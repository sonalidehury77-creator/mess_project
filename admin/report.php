<?php
session_start();

/* ==========================================================================
   1. AUTHENTICATION & SECURITY CONTROL LAYER
   ========================================================================= */
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/* ==========================================================================
   2. DATABASE CONNECTIVITY (PDO CONFIGURATION)
   ========================================================================= */
require_once __DIR__ . "/../config/db_connect.php";

date_default_timezone_set("Asia/Kolkata");

/* ==========================================================================
   3. DATE RESOLUTION SUB-ENGINE
   ========================================================================= */
// Defaults to tomorrow's date if no custom date is requested
$selected_date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d', strtotime('+1 day'));
$day_name      = date('l', strtotime($selected_date));

// Fast navigation calculations (Previous and Next Day URLs)
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));

// Determine date context badge
$today_str = date('Y-m-d');
$tomorrow_str = date('Y-m-d', strtotime('+1 day'));

if ($selected_date === $today_str) {
    $date_badge = '<span class="date-context-badge badge-today">Today</span>';
} elseif ($selected_date === $tomorrow_str) {
    $date_badge = '<span class="date-context-badge badge-tomorrow">Tomorrow</span>';
} else {
    $date_badge = '<span class="date-context-badge badge-archive">Logs</span>';
}

/* ==========================================================================
   4. DATA AGGREGATION & OPERATIONAL METRICS PIPELINE
   ========================================================================= */
try {
    $sql = "
    SELECT
        COUNT(CASE WHEN breakfast = 1 THEN 1 END) AS breakfast_count,
        COUNT(CASE WHEN lunch_type = 'veg' THEN 1 END) AS veg_lunch,
        COUNT(CASE WHEN lunch_type = 'nonveg' THEN 1 END) AS nonveg_lunch,
        COUNT(CASE WHEN dinner_type = 'veg' THEN 1 END) AS veg_dinner,
        COUNT(CASE WHEN dinner_type = 'nonveg' THEN 1 END) AS nonveg_dinner,
        COUNT(CASE WHEN base = 'rice' THEN 1 END) AS rice_count,
        COUNT(CASE WHEN base = 'roti' THEN 1 END) AS roti_count,
        -- Counts a student ONLY if they opted into breakfast OR lunch OR dinner
        COUNT(CASE WHEN breakfast = 1 OR lunch = 1 OR dinner = 1 THEN 1 END) AS total_students
    FROM meals
    WHERE date = :selected_date
";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['selected_date' => $selected_date]);
    $raw_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Dynamic Default Array Sanitization Array
    $data = [];
    if ($raw_data) {
        foreach ($raw_data as $key => $value) {
            $data[$key] = intval($value ?? 0);
        }
    }

    // Handle initial empty structure initialization fallback states
    if (empty($data) || $data['total_students'] === 0) {
        $data = [
            'breakfast_count' => 0,
            'veg_lunch'       => 0,
            'nonveg_lunch'    => 0,
            'veg_dinner'      => 0,
            'nonveg_dinner'   => 0,
            'rice_count'      => 0,
            'roti_count'      => 0,
            'total_students'  => 0
        ];
    }

    $no_data = ($data['total_students'] === 0);
    
    // Calculate layout percentages for visual feedback bars
    $total_lunch  = $data['veg_lunch'] + $data['nonveg_lunch'];
    $total_dinner = $data['veg_dinner'] + $data['nonveg_dinner'];
    $total_staple = $data['rice_count'] + $data['roti_count'];
    
    $lunch_veg_pct   = ($total_lunch > 0)  ? round(($data['veg_lunch'] / $total_lunch) * 100) : 0;
    $dinner_veg_pct  = ($total_dinner > 0) ? round(($data['veg_dinner'] / $total_dinner) * 100) : 0;
    $staple_rice_pct = ($total_staple > 0) ? round(($data['rice_count'] / $total_staple) * 100) : 0;

} catch (PDOException $e) {
    error_log("Database Execution Failure inside report analytics: " . $e->getMessage());
    die("❌ Systems Error: Unable to extract data records from the meal logs dataset.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Kitchen Operations Report</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }

        /* HEADER PANEL ASSEMBLY */
        .workspace-header {
            background: #0F172A;
            border: 1px solid #1E293B;
            padding: 24px 32px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .title-area h2 {
            font-size: 22px;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .title-area p {
            font-size: 13px;
            color: #94A3B8;
            margin-top: 4px;
            font-weight: 500;
        }

        /* BADGES FOR DATE CONTEXT */
        .date-context-badge {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            letter-spacing: 0.05em;
        }
        .badge-today { background: #2563EB; color: #FFFFFF; }
        .badge-tomorrow { background: #10B981; color: #FFFFFF; }
        .badge-archive { background: #475569; color: #FFFFFF; }

        /* ADVANCED TOOLBAR FILTER STRIP */
        .toolbar-filter-strip {
            background: #FFFFFF;
            border-left: 1px solid #CBD5E1;
            border-right: 1px solid #CBD5E1;
            border-bottom: 2px solid #E2E8F0;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .quick-nav-group {
            display: flex;
            gap: 8px;
        }

        .btn-nav-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #F8FAFC;
            color: #334155;
            border: 1px solid #CBD5E1;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.15s ease;
        }

        .btn-nav-arrow:hover {
            background: #E2E8F0;
            color: #0F172A;
            border-color: #94A3B8;
        }

        .control-form {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-input {
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #0F172A;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            background: #F8FAFC;
            outline: none;
            transition: border 0.15s ease;
        }

        .date-input:focus {
            border-color: #2563EB;
            background: #FFFFFF;
        }

        .btn-submit {
            background: #2563EB;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 700;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .btn-submit:hover {
            background: #1D4ED8;
        }

        .btn-print {
            background: #0F172A;
            color: #FFFFFF;
            border: 1px solid #334155;
        }
        .btn-print:hover {
            background: #1E293B;
        }

        /* ANALYTICS REPORT BODY WRAPPER */
        .report-workspace-body {
            background: #FFFFFF;
            border-left: 1px solid #CBD5E1;
            border-right: 1px solid #CBD5E1;
            border-bottom: 1px solid #CBD5E1;
            border-radius: 0 0 16px 16px;
            padding: 36px;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.02);
        }

        .section-header-title {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748B;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-header-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #E2E8F0;
        }

        /* GRID SYSTEM STRUCTURES */
        .analytic-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }

        .metric-card-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-core-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .metric-card-box:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
        }

        .metric-card-box.accent-blue {
            background: #EFF6FF;
            border-color: #BFDBFE;
        }

        .metric-card-box.accent-green {
            background: #F0FDF4;
            border-color: #BBF7D0;
        }

        .icon-badge-holder {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.01);
        }

        .metric-card-box.accent-blue .icon-badge-holder { border-color: #93C5FD; }
        .metric-card-box.accent-green .icon-badge-holder { border-color: #86EFAC; }

        .stack-data-display span {
            font-size: 13px;
            font-weight: 700;
            color: #64748B;
            display: block;
        }

        .metric-card-box.accent-blue .stack-data-display span { color: #1E40AF; }
        .metric-card-box.accent-green .stack-data-display span { color: #166534; }

        .stack-data-display h3 {
            font-size: 28px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 2px;
            line-height: 1.1;
        }

        /* VISUAL RATIO PROGRESS BAR */
        .ratio-bar-wrapper {
            width: 100%;
            background: #E2E8F0;
            height: 6px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 2px;
        }
        .ratio-bar-fill {
            height: 100%;
            border-radius: 10px;
            background: #64748B;
            transition: width 0.3s ease;
        }

        /* WARNING STYLES */
        .empty-state-warning-box {
            padding: 48px;
            text-align: center;
            background: #FEF2F2;
            border: 2px dashed #FCA5A5;
            border-radius: 12px;
            margin: 12px 0;
        }

        .empty-state-warning-box h4 {
            font-size: 16px;
            font-weight: 700;
            color: #991B1B;
            margin-bottom: 4px;
        }

        .empty-state-warning-box p {
            font-size: 13px;
            color: #EF4444;
            font-weight: 500;
        }

        /* ACTION NAVIGATION CONTROLS */
        .navigation-dock {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 32px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #FFFFFF;
            color: #475569;
             Moffat font-weight: 700;
            font-size: 13px;
            text-decoration: none;
            padding: 12px 24px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }

        /* PRINT INTERFACE optimization RULES */
        @media print {
            body { background: #FFFFFF; padding: 0; color: #000000; }
            .workspace-header { background: #FFFFFF; border: none; color: #000000; padding: 0; margin-bottom: 20px; }
            .title-area h2 { color: #000000; font-size: 24px; }
            .title-area p { color: #333333; }
            .toolbar-filter-strip, .navigation-dock, .btn-nav-arrow, .date-context-badge { display: none !important; }
            .report-workspace-body { border: none; padding: 0; box-shadow: none; }
            .metric-card-box { background: #FFFFFF !important; border: 1px solid #000000 !important; transform: none !important; }
            .ratio-bar-wrapper { border: 1px solid #000000; }
            .ratio-bar-fill { background: #000000 !important; }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="workspace-header">
            <div class="title-area">
                <h2>📊 Daily Kitchen Cooking Report <?php echo $date_badge; ?></h2>
                <p><?php echo date("l, d F Y", strtotime($selected_date)); ?> — Food Production Requirements</p>
            </div>
            <button onclick="window.print()" class="btn-submit btn-print">🖨️ Print Prep Sheet</button>
        </div>

        <div class="toolbar-filter-strip">
            <div class="quick-nav-group">
                <a href="?date=<?php echo $prev_date; ?>" class="btn-nav-arrow" title="Previous Day">◀ Previous Day</a>
                <a href="?date=<?php echo $next_date; ?>" class="btn-nav-arrow" title="Next Day">Next Day ▶</a>
            </div>
            
            <form method="GET" class="control-form">
                <input type="date" name="date" class="date-input" value="<?php echo $selected_date; ?>">
                <button type="submit" class="btn-submit">🔍 Change Date</button>
            </form>
        </div>

        <div class="report-workspace-body">

            <?php if ($no_data): ?>
                <div class="empty-state-warning-box">
                    <h4>⚠️ No Meal Records Found</h4>
                    <p>There are no student dining choices or bookings recorded for this selected date.</p>
                </div>
            <?php else: ?>

                <div class="section-header-title">🍳 Breakfast Requirements</div>
                <div class="analytic-cards-grid">
                    <div class="metric-card-box accent-blue">
                        <div class="card-core-info">
                            <div class="icon-badge-holder">🥞</div>
                            <div class="stack-data-display">
                                <span>Total Breakfast Count</span>
                                <h3><?php echo $data['breakfast_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-header-title">🍛 Lunch Choice Breakdown</div>
                <div class="analytic-cards-grid">
                    <div class="metric-card-box">
                        <div class="card-core-info">
                            <div class="icon-badge-holder" style="color: #10B981;">🟢</div>
                            <div class="stack-data-display">
                                <span>Vegetarian Lunch</span>
                                <h3><?php echo $data['veg_lunch']; ?></h3>
                            </div>
                        </div>
                        <div class="ratio-bar-wrapper">
                            <div class="ratio-bar-fill" style="background: #10B981; width: <?php echo $lunch_veg_pct; ?>%;"></div>
                        </div>
                    </div>
                    <div class="metric-card-box">
                        <div class="card-core-info">
                            <div class="icon-badge-holder" style="color: #EF4444;">🔴</div>
                            <div class="stack-data-display">
                                <span>Non-Vegetarian Lunch</span>
                                <h3><?php echo $data['nonveg_lunch']; ?></h3>
                            </div>
                        </div>
                        <div class="ratio-bar-wrapper">
                            <div class="ratio-bar-fill" style="background: #EF4444; width: <?php echo (100 - $lunch_veg_pct); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="section-header-title">🌙 Dinner Choice Breakdown</div>
                <div class="analytic-cards-grid">
                    <div class="metric-card-box">
                        <div class="card-core-info">
                            <div class="icon-badge-holder" style="color: #10B981;">🟢</div>
                            <div class="stack-data-display">
                                <span>Vegetarian Dinner</span>
                                <h3><?php echo $data['veg_dinner']; ?></h3>
                            </div>
                        </div>
                        <div class="ratio-bar-wrapper">
                            <div class="ratio-bar-fill" style="background: #10B981; width: <?php echo $dinner_veg_pct; ?>%;"></div>
                        </div>
                    </div>
                    <div class="metric-card-box">
                        <div class="card-core-info">
                            <div class="icon-badge-holder" style="color: #EF4444;">🔴</div>
                            <div class="stack-data-display">
                                <span>Non-Vegetarian Dinner</span>
                                <h3><?php echo $data['nonveg_dinner']; ?></h3>
                            </div>
                        </div>
                        <div class="ratio-bar-wrapper">
                            <div class="ratio-bar-fill" style="background: #EF4444; width: <?php echo (100 - $dinner_veg_pct); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="section-header-title">🌾 Staple Food Choices</div>
                <div class="analytic-cards-grid">
                    <div class="metric-card-box">
                        <div class="card-core-info">
                            <div class="icon-badge-holder">🍚</div>
                            <div class="stack-data-display">
                                <span>Total Rice Plates</span>
                                <h3><?php echo $data['rice_count']; ?></h3>
                            </div>
                        </div>
                        <div class="ratio-bar-wrapper">
                            <div class="ratio-bar-fill" style="background: #3B82F6; width: <?php echo $staple_rice_pct; ?>%;"></div>
                        </div>
                    </div>
                    <div class="metric-card-box">
                        <div class="card-core-info">
                            <div class="icon-badge-holder">🫓</div>
                            <div class="stack-data-display">
                                <span>Total Roti Plates</span>
                                <h3><?php echo $data['roti_count']; ?></h3>
                            </div>
                        </div>
                        <div class="ratio-bar-wrapper">
                            <div class="ratio-bar-fill" style="background: #F59E0B; width: <?php echo (100 - $staple_rice_pct); ?>%;"></div>
                        </div>
                    </div>
                </div>

                <div class="section-header-title">📊 Overall Mess Attendance</div>
                <div class="analytic-cards-grid">
                    <div class="metric-card-box accent-green">
                        <div class="card-core-info">
                            <div class="icon-badge-holder">👥</div>
                            <div class="stack-data-display">
                                <span>Total Active Diners Today</span>
                                <h3><?php echo $data['total_students']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>

        <div class="navigation-dock">
            <a href="dashboard.php" class="btn-back"><b>
                ⬅ Return to Dashboard</b>
            </a>
        </div>

    </div>

</body>
</html>