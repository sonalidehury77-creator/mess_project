<?php
session_start();

/* ==========================================================================
   1. AUTHENTICATION & ACTIVE SESSION SECURITY LAYER
   ========================================================================== */
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/* ==========================================================================
   2. CENTRALIZED INSTANCE CONNECTIVITY LAYER (PDO ENGINE)
   ========================================================================== */
require_once __DIR__ . "/../config/db_connect.php";

// Automatic Connection Latch Fallback Matrix
if (!isset($conn)) {
    $conn = $pdo ?? $db ?? $con ?? null;
    if (!$conn) {
        die("❌ Database connectivity configuration instance missing.");
    }
}

date_default_timezone_set("Asia/Kolkata");

/* ==========================================================================
   3. DATE RANGE FILTRATION PIPELINE
   ========================================================================== */
$filter = isset($_GET['range']) ? trim($_GET['range']) : 'all';
$where_clause = "1";
$bindings = [];

switch ($filter) {
    case 'today':
        $where_clause = "DATE(submitted_at) = CURRENT_DATE()";
        break;
    case 'week':
        $where_clause = "submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where_clause = "submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

try {
    /* ==========================================================================
       4. DATA AGGREGATION ENGINE (MEAL REVIEWS)
       ========================================================================== */

    // [Metric A]: Core Statistical Summaries
    $metric_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_reviews, 
            AVG(rating) as avg_rating,
            SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) as positive_count
        FROM meal_reviews
        WHERE $where_clause
    ");
    $metric_stmt->execute($bindings);
    $metrics = $metric_stmt->fetch(PDO::FETCH_ASSOC);

    $total_reviews     = intval($metrics['total_reviews'] ?? 0);
    $global_average    = $metrics['avg_rating'] ? round(floatval($metrics['avg_rating']), 1) : 0.0;
    $satisfaction_rate = $total_reviews > 0 ? round((intval($metrics['positive_count']) / $total_reviews) * 100) : 0;

    // [Metric B]: Performance Breakdown by Meal Type
    $chart_stmt = $conn->prepare("
        SELECT meal_type, AVG(rating) as avg_sub_rating, COUNT(*) as vote_count 
        FROM meal_reviews 
        WHERE $where_clause
        GROUP BY meal_type
    ");
    $chart_stmt->execute($bindings);
    $chart_raw = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);

    $chart_data = ['breakfast' => 0, 'lunch' => 0, 'dinner' => 0];
    foreach ($chart_raw as $row) {
        $type_key = strtolower(trim($row['meal_type']));
        if (array_key_exists($type_key, $chart_data)) {
            $chart_data[$type_key] = round(floatval($row['avg_sub_rating']), 2);
        }
    }

    // [Metric C]: Contextual Student Comment Timeline Stream
    $comments_stmt = $conn->prepare("
        SELECT hostel_roll, date, meal_type, rating, comment, submitted_at 
        FROM meal_reviews 
        WHERE comment IS NOT NULL AND comment != '' AND $where_clause
        ORDER BY submitted_at DESC 
        LIMIT 10
    ");
    $comments_stmt->execute($bindings);
    $recent_reviews = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ==========================================================================
       5. INDEX.PHP CONTACT TICKETS EXTRACTION LAYER
       ========================================================================== */
    $tickets_stmt = $conn->prepare("
        SELECT name, hostel_roll, message, submitted_at 
        FROM contact_tickets 
        WHERE $where_clause
        ORDER BY submitted_at DESC 
        LIMIT 20
    ");
    $tickets_stmt->execute($bindings);
    $contact_tickets = $tickets_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Analytics Engine Processing Failure: " . $e->getMessage());
    die("❌ Error compiling historical food review scores.");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Management & Food Feedback Dashboard</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            background: #0F172A;
            border: 1px solid #334155;
            padding: 24px 32px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .title-block h2 {
            font-size: 22px;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
        }

        .title-block p {
            font-size: 13px;
            color: #94A3B8;
            margin-top: 4px;
            font-weight: 500;
        }

        .toolbar-strip {
            background: #FFFFFF;
            border-left: 1px solid #CBD5E1;
            border-right: 1px solid #CBD5E1;
            border-bottom: 2px solid #E2E8F0;
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .select-filter {
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #0F172A;
            border: 2px solid #CBD5E1;
            border-radius: 6px;
            background: #F8FAFC;
            outline: none;
        }

        .select-filter:focus {
            border-color: #2563EB;
        }

        .workspace-body {
            background: #FFFFFF;
            border-left: 1px solid #CBD5E1;
            border-right: 1px solid #CBD5E1;
            border-bottom: 1px solid #CBD5E1;
            border-radius: 0 0 16px 16px;
            padding: 36px;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.04);
        }

        .metrics-bento-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }

        .bento-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .bento-card.accent-amber {
            background: #FFFBEB;
            border-color: #FDE68A;
        }

        .bento-card.accent-emerald {
            background: #F0FDF4;
            border-color: #BBF7D0;
        }

        .badge-icon {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .meta-stack {
            display: flex;
            flex-direction: column;
        }

        .meta-stack .lbl {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748B;
            letter-spacing: 0.05em;
        }

        .meta-stack .val {
            font-size: 26px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 2px;
            line-height: 1.1;
        }

        .analytics-split-view {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 28px;
            margin-bottom: 36px;
        }

        @media (max-width: 900px) {
            .analytics-split-view {
                grid-template-columns: 1fr;
            }
        }

        .content-block-panel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
        }

        .content-block-panel h3 {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
            margin-bottom: 20px;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 12px;
        }

        .stream-wrapper {
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-height: 360px;
            overflow-y: auto;
            padding-right: 6px;
        }

        .stream-node-item {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 14px;
            transition: border-color 0.15s ease;
        }

        .stream-node-item:hover {
            border-color: #CBD5E1;
        }

        .node-meta-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .student-roll {
            font-size: 13px;
            font-weight: 700;
            color: #1E293B;
            background: #E2E8F0;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
        }

        .meal-tag {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .score-star-badge {
            font-weight: 700;
            font-size: 13px;
            color: #D97706;
        }

        .node-text-body {
            font-size: 13px;
            color: #334155;
            line-height: 1.4;
            font-style: italic;
            background: #FFFFFF;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #CBD5E1;
            margin-bottom: 6px;
        }

        .node-timestamp-bottom {
            font-size: 11px;
            color: #94A3B8;
            font-weight: 500;
            text-align: right;
        }

        /* CONTACT TICKETS STYLING ADDITIONS */
        .ticket-card {
            background: #F8FAFC;
            border-left: 4px solid #0F172A;
            border-top: 1px solid #E2E8F0;
            border-right: 1px solid #E2E8F0;
            border-bottom: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        .ticket-user-info h4 {
            font-size: 14px;
            font-weight: 700;
            color: #0F172A;
        }
        .ticket-user-info span {
            font-size: 11px;
            color: #64748B;
            font-weight: 600;
        }
        .ticket-message {
            font-size: 13px;
            color: #334155;
            background: #FFFFFF;
            padding: 10px 14px;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            line-height: 1.5;
        }

        .navigation-action-dock {
            text-align: center;
            margin-top: 32px;
        }

        .btn-return-hub {
            display: inline-flex;
            align-items: center;
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

        .btn-return-hub:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="dashboard-header">
            <div class="title-block">
                <h2>🍳 Kitchen Rating & Food Quality Analytics</h2>
                <p>Track student ratings and constructive feedback to monitor mess performance parameters</p>
            </div>
        </div>

        <div class="toolbar-strip">
            <div class="filter-group">
                <label for="range-selector">Analysis Window:</label>
                <select id="range-selector" class="select-input select-filter" onchange="location.href='?range=' + this.value;">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Saved Records</option>
                    <option value="today" <?php echo $filter === 'today' ? 'selected' : ''; ?>>Today (Past 24 Hours)</option>
                    <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Past 7 Days</option>
                    <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Past 30 Days</option>
                </select>
            </div>
        </div>

        <div class="workspace-body">

            <div class="metrics-bento-grid">
                <div class="bento-card">
                    <div class="badge-icon">📥</div>
                    <div class="meta-stack">
                        <span class="lbl">Total Reviews</span>
                        <span class="val"><?php echo $total_reviews; ?></span>
                    </div>
                </div>
                <div class="bento-card accent-amber">
                    <div class="badge-icon">⭐</div>
                    <div class="meta-stack">
                        <span class="lbl">Average Food Rating</span>
                        <span class="val" style="color: #D97706;"><?php echo number_format($global_average, 1); ?><span style="font-size:14px; color:#A16207; font-weight:500;"> /5.0</span></span>
                    </div>
                </div>
                <div class="bento-card accent-emerald">
                    <div class="badge-icon">📈</div>
                    <div class="meta-stack">
                        <span class="lbl">Overall Approval Rate</span>
                        <span class="val" style="color: #15803D;"><?php echo $satisfaction_rate; ?>%</span>
                    </div>
                </div>
            </div>

            <div class="analytics-split-view">

                <div class="content-block-panel">
                    <h3>📊 Score Distribution by Meal Type</h3>
                    <div style="position: relative; width: 100%; height: 320px;">
                        <canvas id="menuMetricsChart"></canvas>
                    </div>
                </div>

                <div class="content-block-panel">
                    <h3>💬 Recent Comments from Students</h3>
                    <div class="stream-wrapper">
                        <?php if (empty($recent_reviews)): ?>
                            <p style="color: #94A3B8; font-size: 13px; text-align: center; padding: 60px 0; font-weight: 500;">
                                📭 No student comments written yet for this filtering window.
                            </p>
                        <?php else: ?>
                            <?php foreach ($recent_reviews as $review): ?>
                                <div class="stream-node-item">
                                    <div class="node-meta-top">
                                        <div>
                                            <span class="student-roll"><?php echo htmlspecialchars($review['hostel_roll']); ?></span>
                                            <span class="meal-tag" style="margin-left: 6px;">• <?php echo htmlspecialchars($review['meal_type']); ?></span>
                                        </div>
                                        <span class="score-star-badge">★ <?php echo number_format($review['rating'], 0); ?></span>
                                    </div>
                                    <div class="node-text-body">
                                        "<?php echo htmlspecialchars($review['comment']); ?>"
                                    </div>
                                    <div class="node-timestamp-bottom">
                                        Logged: <?php echo date("d M, h:i A", strtotime($review['submitted_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- ==========================================================================
               🆕 CENTRALIZED INQUIRIES & CONTACT TICKETS WORKSPACE
               ========================================================================== -->
            <div class="content-block-panel" style="margin-top: 12px;">
                <h3 style="color: #1E3A8A; border-bottom: 1px solid #DBE3ED;">✉️ Submitted Help Desk Tickets & Inquiries (From Index Page)</h3>
                <div style="max-height: 400px; overflow-y: auto; padding-right: 4px; margin-top: 15px;">
                    <?php if (empty($contact_tickets)): ?>
                        <p style="color: #94A3B8; font-size: 13px; text-align: center; padding: 40px 0;">
                            📭 No contact inquiries or message tickets submitted during this window.
                        </p>
                    <?php else: ?>
                        <?php foreach ($contact_tickets as $ticket): ?>
                            <div class="ticket-card">
                                <div class="ticket-header">
                                    <div class="ticket-user-info">
                                        <h4><?php echo htmlspecialchars($ticket['name']); ?></h4>
                                        <span>Roll No: <b style="color: #0F172A; font-family: monospace;"><?php echo htmlspecialchars($ticket['hostel_roll']); ?></b></span>
                                    </div>
                                    <div class="node-timestamp-bottom">
                                        Sent: <?php echo date("d M Y, h:i A", strtotime($ticket['submitted_at'])); ?>
                                    </div>
                                </div>
                                <div class="ticket-message">
                                    <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="navigation-action-dock">
            <a href="dashboard.php" class="btn-return-hub">
                ⬅ Return to Main Admin Dashboard
            </a>
        </div>

    </div>

    <?php if ($total_reviews > 0): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById('menuMetricsChart').getContext('2d');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Breakfast Selections', 'Lunch Matrix', 'Dinner Distribution'],
                        datasets: [{
                            label: 'Average Score Metric',
                            data: [
                                <?php echo $chart_data['breakfast']; ?>,
                                <?php echo $chart_data['lunch']; ?>,
                                <?php echo $chart_data['dinner']; ?>
                            ],
                            backgroundColor: [
                                'rgba(37, 99, 235, 0.85)',
                                'rgba(16, 185, 129, 0.85)',
                                'rgba(245, 158, 11, 0.85)'
                            ],
                            borderColor: [
                                '#1D4ED8',
                                '#047857',
                                '#B45309'
                            ],
                            borderWidth: 1.5,
                            borderRadius: 6,
                            barThickness: 48
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                padding: 12,
                                cornerRadius: 8,
                                bodyFont: {
                                    family: 'Inter',
                                    weight: 600
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                grid: {
                                    color: '#F1F5F9'
                                },
                                ticks: {
                                    color: '#64748B',
                                    stepSize: 1,
                                    font: {
                                        family: 'Inter',
                                        size: 11,
                                        weight: 600
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#1E293B',
                                    font: {
                                        family: 'Inter',
                                        size: 12,
                                        weight: 700
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
    <?php endif; ?>

</body>
</html>