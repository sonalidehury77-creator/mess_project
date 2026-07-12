<?php
session_start();

/* ==========================================================================
   1. AUTHENTICATION & SECURITY LAYER
   ========================================================================== */
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/* ==========================================================================
   2. DATABASE CONNECTION & CONFIGURATION
   ========================================================================== */
require_once __DIR__ . "/../config/db_connect.php";

date_default_timezone_set("Asia/Kolkata");

/* ==========================================================================
   3. MONTH DYNAMIC FILTER ENGINE
   ========================================================================== */
$selected_month = isset($_GET['month']) ? trim($_GET['month']) : '';

$month_condition = "";
$bindings = [];

if ($selected_month !== "") {
    $month_condition = "AND MONTH(meals.date) = :selected_month";
    $bindings['selected_month'] = intval($selected_month);
}

try {
    /* ==========================================================================
       4. ANALYTICS DATA PIPELINE
       ========================================================================== */

    // [Metric A]: Active Student Count
    $studentQuery = "SELECT COUNT(DISTINCT hostel_roll) as total FROM meals WHERE 1 $month_condition";
    $stmt = $pdo->prepare($studentQuery);
    $stmt->execute($bindings);
    $students = intval($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // [Metric B]: Total Meal Servings Count
    $mealQuery = "
        SELECT 
            SUM(breakfast) as breakfast,
            SUM(lunch) as lunch,
            SUM(dinner) as dinner
        FROM meals 
        WHERE 1 $month_condition
    ";
    $stmt = $pdo->prepare($mealQuery);
    $stmt->execute($bindings);
    $meal = $stmt->fetch(PDO::FETCH_ASSOC);

    $total_meals = intval($meal['breakfast'] ?? 0) + intval($meal['lunch'] ?? 0) + intval($meal['dinner'] ?? 0);

    // [Metric C]: Menu Preference Distribution
    $typeQuery = "
        SELECT 
            SUM(lunch_type='veg') as veg_lunch,
            SUM(lunch_type='nonveg') as nonveg_lunch,
            SUM(dinner_type='veg') as veg_dinner,
            SUM(dinner_type='nonveg') as nonveg_dinner
        FROM meals 
        WHERE 1 $month_condition
    ";
    $stmt = $pdo->prepare($typeQuery);
    $stmt->execute($bindings);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

    $veg_total = intval($type['veg_lunch'] ?? 0) + intval($type['veg_dinner'] ?? 0);
    $nonveg_total = intval($type['nonveg_lunch'] ?? 0) + intval($type['nonveg_dinner'] ?? 0);
    $top_type = ($veg_total >= $nonveg_total) ? "Vegetarian" : "Non-Vegetarian";

    // [Metric D]: Daily Revenue Tracking & Menu Type Joins
    $timelineQuery = "
        SELECT 
            meals.date,
            SUM(
                IF(meals.breakfast=1, 15, 0) +
                IF(meals.lunch_type='veg', menu.lunch_veg_price, IF(meals.lunch_type='nonveg', menu.lunch_nonveg_price, 0)) +
                IF(meals.dinner_type='veg', menu.dinner_veg_price, IF(meals.dinner_type='nonveg', menu.dinner_nonveg_price, 0))
            ) as daily_total,
            COUNT(CASE WHEN meals.lunch_type='special' OR meals.dinner_type='special' THEN 1 END) as special_count,
            COUNT(CASE WHEN meals.lunch_type IN ('veg','nonveg') OR meals.dinner_type IN ('veg','nonveg') THEN 1 END) as regular_count
        FROM meals
        LEFT JOIN menu ON meals.day = menu.day
        WHERE 1 $month_condition
        GROUP BY meals.date
        ORDER BY meals.date ASC
    ";
    $stmt = $pdo->prepare($timelineQuery);
    $stmt->execute($bindings);

    $days = [];
    $daily_amount = [];
    $regular_meals_timeline = [];
    $special_meals_timeline = [];
    $total_money = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $days[] = date("d M", strtotime($row['date']));
        $daily_amount[] = floatval($row['daily_total']);
        $regular_meals_timeline[] = intval($row['regular_count']);
        $special_meals_timeline[] = intval($row['special_count']);
        $total_money += floatval($row['daily_total']);
    }

    $avg_meals = ($students > 0) ? round($total_meals / $students, 1) : 0;
} catch (PDOException $e) {
    error_log("Database Analytical Execution Error: " . $e->getMessage());
    die("❌ Error processing core dashboard analytical data metrics.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mess Analytics & System Performance Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: #E2E8F0;
            color: #0F172A;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .container {
            max-width: 1250px;
            margin: 0 auto;
        }

        /* HEADER CORE STYLES */
        .analytics-header {
            background: #0F172A;
            border: 1px solid #334155;
            padding: 24px 32px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .header-title h2 {
            font-size: 22px;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 13px;
            color: #94A3B8;
            margin-top: 4px;
            font-weight: 500;
        }

        /* FILTRATION ACTION PANEL STRIP */
        .filter-action-strip {
            background: #FFFFFF;
            border-left: 1px solid #CBD5E1;
            border-right: 1px solid #CBD5E1;
            border-bottom: 2px solid #E2E8F0;
            padding: 18px 32px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .select-input {
            padding: 9px 16px;
            font-size: 14px;
            font-weight: 600;
            color: #0F172A;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            background: #F8FAFC;
            outline: none;
            min-width: 160px;
        }

        .select-input:focus {
            border-color: #2563EB;
            background: #FFFFFF;
        }

        .btn-filter {
            background: #2563EB;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 700;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.12s ease;
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .btn-filter:hover {
            background: #1D4ED8;
        }

        /* ANALYTICS WORKSPACE BODY */
        .analytics-workspace {
            background: #FFFFFF;
            border-left: 1px solid #CBD5E1;
            border-right: 1px solid #CBD5E1;
            border-bottom: 1px solid #CBD5E1;
            border-radius: 0 0 16px 16px;
            padding: 36px;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.04);
        }

        /* GRID SYSTEMS METRICS SHEETS */
        .metrics-cards-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }

        .metric-card-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .metric-card-box.accented-green {
            background: #F0FDF4;
            border-color: #BBF7D0;
        }

        .metric-card-box.accented-blue {
            background: #EFF6FF;
            border-color: #BFDBFE;
        }

        .metric-card-box.accented-purple {
            background: #FAF5FF;
            border-color: #E9D5FF;
        }

        .icon-badge {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
            flex-shrink: 0;
        }

        .metric-card-box.accented-green .icon-badge {
            border-color: #86EFAC;
        }

        .metric-card-box.accented-blue .icon-badge {
            border-color: #93C5FD;
        }

        .metric-card-box.accented-purple .icon-badge {
            border-color: #D8B4FE;
        }

        .stack-data {
            display: flex;
            flex-direction: column;
        }

        .stack-data .label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748B;
            letter-spacing: 0.05em;
        }

        .stack-data .value {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            line-height: 1.2;
            margin-top: 2px;
        }

        /* VISUALIZATION DATA CONTAINERS */
        .charts-double-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-top: 12px;
        }

        .chart-wrapper-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 24px;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.02);
        }

        .chart-wrapper-card h4 {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .empty-state-notice {
            padding: 60px;
            text-align: center;
            border: 2px dashed #CBD5E1;
            border-radius: 12px;
            font-weight: 500;
            color: #64748B;
            font-size: 14px;
        }

        /* FOOTER CONTROLS ASSEMBLY */
        .footer-action-dock {
            text-align: center;
            margin-top: 32px;
        }

        .btn-return {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #FFFFFF;
            color: #475569;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            padding: 12px 24px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .btn-return:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }

        /* ==========================================================================
           📱 MODERN RESPONSIVE APP MEDIA ENGINE
           ========================================================================= */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px;
            }

            .analytics-header {
                padding: 20px 24px;
                border-radius: 12px 12px 0 0;
            }

            .filter-action-strip {
                padding: 16px 24px;
            }

            .filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            .filter-form label {
                margin-bottom: 2px;
            }

            .select-input, .btn-filter {
                width: 100%;
                min-width: unset;
            }

            .analytics-workspace {
                padding: 20px;
                border-radius: 0 0 12px 12px;
            }

            .metrics-cards-layout {
                grid-template-columns: 1fr;
                gap: 12px;
                margin-bottom: 24px;
            }

            .metric-card-box {
                padding: 16px;
            }

            .charts-double-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .chart-wrapper-card {
                padding: 16px;
            }

            .chart-wrapper-card h4 {
                font-size: 12px;
                margin-bottom: 16px;
            }
            
            /* Responsive chart viewport adjustments */
            .chart-wrapper-card > div {
                height: 280px !important; 
            }

            .btn-return {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="analytics-header">
            <div class="header-title">
                <h2>📊 Mess Operational Insights & Demand Analytics</h2>
                <p>System Consumption Summary & Revenue Performance Dashboard</p>
            </div>
        </div>

        <div class="filter-action-strip">
            <form method="GET" class="filter-form">
                <label for="month-select">Filter Month:</label>
                <select name="month" id="month-select" class="select-input">
                    <option value="">Full History (All Time)</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $val = str_pad($m, 2, "0", STR_PAD_LEFT);
                        $selected = ($selected_month === $val) ? "selected" : "";
                        echo "<option value='$val' $selected>" . date("F", mktime(0, 0, 0, $m, 1)) . "</option>";
                    }
                    ?>
                </select>
                <button type="submit" class="btn-filter">⚡ Apply Filters</button>
            </form>
        </div>

        <div class="analytics-workspace">

            <div class="metrics-cards-layout">
                <div class="metric-card-box">
                    <div class="icon-badge">👥</div>
                    <div class="stack-data">
                        <span class="label">Active Students</span>
                        <span class="value"><?php echo $students; ?></span>
                    </div>
                </div>
                <div class="metric-card-box">
                    <div class="icon-badge">🍽️</div>
                    <div class="stack-data">
                        <span class="label">Total Served Meals</span>
                        <span class="value"><?php echo $total_meals; ?></span>
                    </div>
                </div>
                <div class="metric-card-box accented-green">
                    <div class="icon-badge">💰</div>
                    <div class="stack-data">
                        <span class="label">Total Revenue</span>
                        <span class="value">₹<?php echo number_format($total_money, 2); ?></span>
                    </div>
                </div>
                <div class="metric-card-box accented-blue">
                    <div class="icon-badge">📊</div>
                    <div class="stack-data">
                        <span class="label">Avg. Meals per Student</span>
                        <span class="value"><?php echo $avg_meals; ?></span>
                    </div>
                </div>
                <div class="metric-card-box accented-purple">
                    <div class="icon-badge">🏆</div>
                    <div class="stack-data">
                        <span class="label">Top Diet Preference</span>
                        <span class="value" style="font-size:15px; font-weight:800; margin-top:6px;"><?php echo $top_type; ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($days)): ?>
                <div class="empty-state-notice">
                    🚫 No tracking logs or data records match the selected filter criteria.
                </div>
            <?php else: ?>
                <div class="charts-double-row">

                    <div class="chart-wrapper-card">
                        <h4>📈 Daily Revenue Collection & Meal Breakdown Trend</h4>
                        <div style="position: relative; width: 100%; height: 350px;">
                            <canvas id="complexMixChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-wrapper-card">
                        <h4>🥗 Dietary Menu Distribution Share</h4>
                        <div style="position: relative; width: 100%; height: 350px; display: flex; align-items: center; justify-content: center;">
                            <canvas id="dietaryDonutChart"></canvas>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </div>

        <div class="footer-action-dock">
            <a href="dashboard.php" class="btn-return">
                ⬅ Return to Dashboard
            </a>
        </div>

    </div>

    <?php if (!empty($days)): ?>
        <script>
            // Execution Block: Chart Rendering Configurations
            document.addEventListener("DOMContentLoaded", function() {

                // 1. Dual-Axis Mixed Chart (Revenue Line Chart overlaid on Count Bar Charts)
                const mixCtx = document.getElementById('complexMixChart').getContext('2d');
                new Chart(mixCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($days); ?>,
                        datasets: [{
                                type: 'line',
                                label: 'Daily Revenue (₹)',
                                data: <?php echo json_encode($daily_amount); ?>,
                                borderColor: '#2563EB',
                                borderWidth: 3,
                                pointBackgroundColor: '#FFFFFF',
                                pointBorderColor: '#2563EB',
                                pointHoverRadius: 6,
                                tension: 0.25,
                                fill: false,
                                yAxisID: 'yFinancial'
                            },
                            {
                                type: 'bar',
                                label: 'Standard Meals Count',
                                data: <?php echo json_encode($regular_meals_timeline); ?>,
                                backgroundColor: 'rgba(148, 163, 184, 0.3)',
                                borderColor: '#94A3B8',
                                borderWidth: 1,
                                borderRadius: 4,
                                yAxisID: 'yVolume'
                            },
                            {
                                type: 'bar',
                                label: 'Special / Feast Count',
                                data: <?php echo json_encode($special_meals_timeline); ?>,
                                backgroundColor: 'rgba(234, 179, 8, 0.7)',
                                borderColor: '#CA8A04',
                                borderWidth: 1,
                                borderRadius: 4,
                                yAxisID: 'yVolume'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: {
                                        weight: 600,
                                        family: 'Inter'
                                    }
                                }
                            },
                            tooltip: {
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            yFinancial: {
                                type: 'linear',
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Gross Revenue Collection (INR)',
                                    font: {
                                        weight: 700
                                    }
                                },
                                grid: {
                                    color: '#E2E8F0'
                                }
                            },
                            yVolume: {
                                type: 'linear',
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Active Meals Count Log',
                                    font: {
                                        weight: 700
                                    }
                                },
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });

                // 2. Dietary Menu Segment Breakdown Donut Chart
                const donutCtx = document.getElementById('dietaryDonutChart').getContext('2d');
                new Chart(donutCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Vegetarian Selection', 'Non-Vegetarian Selection'],
                        datasets: [{
                            data: [<?php echo $veg_total; ?>, <?php echo $nonveg_total; ?>],
                            backgroundColor: ['#10B981', '#EF4444'],
                            borderWidth: 2,
                            borderColor: '#FFFFFF',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 16,
                                    font: {
                                        weight: 700,
                                        family: 'Inter'
                                    }
                                }
                            },
                            tooltip: {
                                padding: 12,
                                cornerRadius: 6
                            }
                        },
                        cutout: '70%'
                    }
                });
            });
        </script>
    <?php endif; ?>

</body>

</html>