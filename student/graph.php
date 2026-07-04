<?php
session_start();
include("../config/db_connect.php");

/* ==========================================================================
   PDO AUTOMATIC DETECTION LATCH
   ========================================================================== */
if (!isset($conn)) {
    if (isset($pdo)) {
        $conn = $pdo;
    } elseif (isset($db)) {
        $conn = $db;
    } elseif (isset($con)) {
        $conn = $con;
    }
}

if (!isset($_SESSION['hostel_roll'])) {
    header("Location: ../auth/login.php");
    exit();
}

$hostel_roll = $_SESSION['hostel_roll'];

/* =============================
   SETTINGS CONFIGURATION
============================= */
$BREAKFAST_PRICE = 15;
$MEAL_PRICE = 33;
$MIN_MEALS = 40;

/* =============================
   MONTH SELECTOR PROCESSING
============================= */
$selected_month = $_GET['month'] ?? date("Y-m");

$month_start = date("Y-m-01", strtotime($selected_month));
$month_end   = date("Y-m-t", strtotime($selected_month));
$current_month = date("F Y", strtotime($selected_month));

/* =============================
   DATA ENGINE EXECUTION (PDO)
============================= */
$sql = "
    SELECT date, breakfast, lunch, lunch_type, dinner, dinner_type 
    FROM meals 
    WHERE hostel_roll = ? 
    AND date BETWEEN ? AND ? 
    ORDER BY date ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute([$hostel_roll, $month_start, $month_end]);
$meals_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days = [];
$amounts = [];
$total = 0;
$meal_count = 0;
$day_used = 0;

foreach ($meals_data as $row) {
    $day_total = 0;
    $has_meal = false;

    // Breakfast Computation
    if (!empty($row['breakfast'])) {
        $day_total += $BREAKFAST_PRICE;
        $meal_count++;
        $has_meal = true;
    }

    // Lunch Computation (Checks if lunch was taken and type is assigned)
    if (!empty($row['lunch']) && !empty($row['lunch_type'])) {
        $day_total += $MEAL_PRICE;
        $meal_count++;
        $has_meal = true;
    }

    // Dinner Computation (Checks if dinner was taken and type is assigned)
    if (!empty($row['dinner']) && !empty($row['dinner_type'])) {
        $day_total += $MEAL_PRICE;
        $meal_count++;
        $has_meal = true;
    }

    if ($has_meal) {
        $day_used++;
    }

    $total += $day_total;
    $days[] = date("d M", strtotime($row['date']));
    $amounts[] = $day_total;
}

/* =============================
   METRIC LOGIC SYSTEMS
============================= */
$remaining = max(0, $MIN_MEALS - $meal_count);
$average = ($day_used > 0) ? round($total / $day_used, 2) : 0;
$progress = min(100, ($meal_count / $MIN_MEALS) * 100);

if ($meal_count >= $MIN_MEALS) {
    $message = "🎉 Minimum required meals completed! Great job.";
    $msg_class = "success-alert";
    $bar_color = "#10B981";
} elseif ($meal_count >= 30) {
    $message = "⚠️ Closing in on minimum targets. Keep tracking.";
    $msg_class = "warning-alert";
    $bar_color = "#F59E0B";
} else {
    $message = "❗ Minimum meal limit shortfall. You need $remaining more meals to avoid a penalty.";
    $msg_class = "danger-alert";
    $bar_color = "#EF4444";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Meal Insights</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* HEADER & FILTER SYSTEMS */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 32px;
        }

        .title-section h2 {
            font-size: 24px;
            color: #0F172A;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .title-section p {
            font-size: 14px;
            color: #64748B;
            margin-top: 4px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
        }

        .filter-form input[type="month"] {
            padding: 10px 16px;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 14px;
            color: #0F172A;
            font-weight: 600;
            outline: none;
            background: white;
        }

        .filter-form button {
            padding: 10px 20px;
            background: #0F172A;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .filter-form button:hover {
            background: #1E293B;
        }

        /* GRID ANALYTICS CARDS */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: #FFFFFF;
            padding: 24px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        .metric-card .label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748B;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .metric-card .value {
            font-size: 22px;
            color: #0F172A;
            font-weight: 800;
            margin-top: 8px;
        }

        /* STATUS METRIC PLUGINS */
        .status-container {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 32px;
        }

        .progress-wrapper {
            margin-bottom: 16px;
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            margin-bottom: 8px;
        }

        .progress-track {
            width: 100%;
            height: 12px;
            background: #F1F5F9;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 999px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* BANNER ALERTS */
        .alert-box {
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .success-alert {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .warning-alert {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .danger-alert {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FEE2E2;
        }

        /* CANVAS CONTAINER PLUGINS */
        .chart-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 28px;
            margin-bottom: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        /* ACTIONS DISPLAY CONTROLS */
        .action-tray {
            display: flex;
            justify-content: flex-start;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            background: #FFFFFF;
            color: #475569;
            border: 1px solid #CBD5E1;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="page-header">
            <div class="title-section">
                <h2>📊 Smart Meal Insights</h2>
                <p>Meal Consumption Analytics for <?php echo htmlspecialchars($current_month); ?></p>
            </div>
            <form method="GET" class="filter-form">
                <input type="month" name="month" value="<?php echo htmlspecialchars($selected_month); ?>">
                <button type="submit">Filter View</button>
            </form>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="label">Total Monthly Cost</div>
                <div class="value">₹<?php echo number_format($total, 2); ?></div>
            </div>
            <div class="metric-card">
                <div class="label">Days Mess Used</div>
                <div class="value"><?php echo (int)$day_used; ?> Days</div>
            </div>
            <div class="metric-card">
                <div class="label">Total Meals Taken</div>
                <div class="value"><?php echo (int)$meal_count; ?> Meals</div>
            </div>
            <div class="metric-card">
                <div class="label">Target Shortfall</div>
                <div class="value" style="color: <?php echo ($remaining > 0) ? '#EF4444' : '#10B981'; ?>;"><?php echo (int)$remaining; ?></div>
            </div>
            <div class="metric-card">
                <div class="label">Daily Average Cost</div>
                <div class="value">₹<?php echo number_format($average, 2); ?></div>
            </div>
        </div>

        <div class="status-container">
            <div class="progress-wrapper">
                <div class="progress-meta">
                    <span>Minimum Target Progress Metric</span>
                    <span><?php echo round($progress); ?>%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-bar" style="width: <?php echo (float)$progress; ?>%; background-color: <?php echo htmlspecialchars($bar_color); ?>;"></div>
                </div>
            </div>
            <div class="alert-box <?php echo htmlspecialchars($msg_class); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        </div>

        <div class="chart-card">
            <canvas id="dailyChart" style="width:100%; height:320px;"></canvas>
        </div>

        <div class="action-tray">
            <a href="dashboard.php" class="btn-back">⬅ Return to Portal Dashboard</a>
        </div>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById("dailyChart").getContext("2d");

            const chartGradient = ctx.createLinearGradient(0, 0, 0, 300);
            chartGradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
            chartGradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($days); ?>,
                    datasets: [{
                        label: 'Daily Cost Breakdown',
                        data: <?php echo json_encode($amounts); ?>,
                        borderColor: '#3B82F6',
                        borderWidth: 3,
                        backgroundColor: chartGradient,
                        fill: true,
                        tension: 0.25,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#F1F5F9'
                            },
                            ticks: {
                                color: '#64748B',
                                font: {
                                    family: 'Inter',
                                    size: 11
                                },
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>

</html>