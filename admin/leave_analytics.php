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
$today = date("Y-m-d");

// Generate Anti-CSRF Token for Admin Action Guarding
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ==========================================================================
   3. INTERACTIVE STATE TRANSITION ACTION CONTROLLER (POST HANDLER)
   ========================================================================== */
$status_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'], $_POST['leave_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $status_message = "❌ Security Token mismatch. Request denied.";
    } else {
        $target_id = intval($_POST['leave_id']);
        $new_status = ($_POST['action_type'] === 'approve') ? 'approved' : 'rejected';

        try {
            $update_stmt = $conn->prepare("UPDATE mess_leaves SET status = ? WHERE id = ?");
            if ($update_stmt->execute([$new_status, $target_id])) {
                $status_message = "🟢 Leave request updated to " . strtoupper($new_status) . " successfully.";
            }
        } catch (PDOException $e) {
            $status_message = "❌ Database error during update: " . $e->getMessage();
        }
    }
}

/* ==========================================================================
   4. DATA AGGREGATION & VIEWPORT FILTER PIPELINE
   ========================================================================== */
$view_filter = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';
$valid_filters = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($view_filter, $valid_filters)) {
    $view_filter = 'all';
}

$where_clause = "1";
if ($view_filter !== 'all') {
    $where_clause = "status = :filter_val";
}

try {
    // Metric 1: Count of students currently active on away status today (Prevents Mess Diet Fraud)
    $active_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT hostel_roll) 
        FROM mess_leaves 
        WHERE :today_val BETWEEN start_date AND end_date AND status = 'approved'
    ");
    $active_stmt->execute(['today_val' => $today]);
    $current_away_count = $active_stmt->fetchColumn();

    // Metric 2: Count of outstanding applications awaiting admin adjudication
    $pending_count_stmt = $conn->query("SELECT COUNT(*) FROM mess_leaves WHERE status = 'pending'");
    $total_pending_count = $pending_count_stmt->fetchColumn();

    // Check if parent_contact column exists to avoid SQL failure state crashes
    $has_parent_column = false;
    try {
        $check_col = $conn->query("SHOW COLUMNS FROM mess_leaves LIKE 'parent_contact'");
        if ($check_col->fetch()) {
            $has_parent_column = true;
        }
    } catch (Exception $col_err) {
        $has_parent_column = false;
    }

    // Dynamic Select Query adjustment
    if ($has_parent_column) {
        $query_str = "SELECT id, hostel_roll, start_date, end_date, reason, parent_contact, status FROM mess_leaves WHERE $where_clause ORDER BY start_date DESC LIMIT 50";
    } else {
        $query_str = "SELECT id, hostel_roll, start_date, end_date, reason, 'Not Added' as parent_contact, status FROM mess_leaves WHERE $where_clause ORDER BY start_date DESC LIMIT 50";
    }
    
    $list_stmt = $conn->prepare($query_str);

    if ($view_filter !== 'all') {
        $list_stmt->bindValue(':filter_val', $view_filter, PDO::PARAM_STR);
    }
    $list_stmt->execute();
    $all_leaves = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error loading leave system records: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Leave Registry & Analytics Console</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #F1F5F9;
            color: #0F172A;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .wrapper {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* EXECUTIVE STATUS METRIC CARDS */
        .metrics-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 28px;
        }

        @media (max-width: 600px) {
            .metrics-row {
                grid-template-columns: 1fr;
            }
        }

        .summary-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.02);
        }

        .summary-card.dark {
            background: #0F172A;
            color: #FFFFFF;
            border: none;
        }

        .card-meta h3 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            margin-bottom: 4px;
        }

        .summary-card.dark .card-meta h3 {
            color: #94A3B8;
        }

        .card-meta p {
            font-size: 13px;
            color: #64748B;
        }

        .summary-card.dark .card-meta p {
            color: #94A3B8;
        }

        .card-value {
            font-size: 36px;
            font-weight: 800;
        }

        /* INTERFACE ALERT NOTIFICATIONS */
        .toast-banner {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1E40AF;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        /* DATA REGISTRY WORKSPACE */
        .workspace-panel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.02);
        }

        .panel-header-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #F1F5F9;
            gap: 16px;
            flex-wrap: wrap;
        }

        .panel-header-strip h2 {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: #1E293B;
        }

        /* SEGMENTED FILTER BUTTON DOCK */
        .filter-dock {
            display: inline-flex;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 4px;
            border-radius: 8px;
            gap: 4px;
        }

        .filter-btn {
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            background: transparent;
            color: #64748B;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            text-transform: uppercase;
            transition: all 0.15s ease;
        }

        .filter-btn:hover {
            color: #0F172A;
        }

        .filter-btn.active {
            background: #FFFFFF;
            color: #0F172A;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            border: 1px solid #E2E8F0;
        }

        /* REGISTRY MASTER DATA TABLE */
        .table-viewport {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }

        th {
            padding: 12px 16px;
            font-weight: 700;
            color: #64748B;
            border-bottom: 2px solid #E2E8F0;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #F1F5F9;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* DATA PRESENTATION BADGES & ELEMENT STYLES */
        .roll-label {
            font-weight: 700;
            color: #0F172A;
            font-family: monospace;
            font-size: 14px;
        }

        .date-range-text {
            font-weight: 500;
            color: #334155;
        }

        .reason-subtext {
            font-size: 12px;
            color: #64748B;
            margin-top: 2px;
        }

        .parent-contact-text {
            font-size: 12px;
            color: #475569;
            margin-top: 4px;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .badge.status-approved {
            background: #DCFCE7;
            color: #15803D;
        }

        .badge.status-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .badge.status-rejected {
            background: #FEE2E2;
            color: #B91C1C;
        }

        /* ROW ACTION INTERACTION CONTROLS */
        .inline-action-form {
            display: inline-block;
            margin: 0;
        }

        .action-button-group {
            display: flex;
            gap: 6px;
        }

        .btn-control {
            border: 1px solid #E2E8F0;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.1s ease;
        }

        .btn-control.approve {
            background: #10B981;
            color: white;
            border: none;
        }

        .btn-control.approve:hover {
            background: #059669;
        }

        .btn-control.reject {
            background: #EF4444;
            color: white;
            border: none;
        }

        .btn-control.reject:hover {
            background: #DC2626;
        }

        .empty-indicator {
            text-align: center;
            color: #94A3B8;
            font-weight: 500;
            padding: 48px 0;
            font-size: 13px;
        }

        /* ==========================================================================
           MOBILE RESPONSIVE EXTENSION MODULE (Laptop Design Preserved)
           ========================================================================== */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px;
            }

            .workspace-panel {
                padding: 16px;
                border-radius: 12px;
            }

            .panel-header-strip {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding-bottom: 12px;
            }

            .panel-header-strip h2 {
                font-size: 16px;
            }

            .filter-dock {
                width: 100%;
                display: flex;
                overflow-x: auto;
                scrollbar-width: none; /* Hide scrollbar for clean app interface appearance */
            }
            .filter-dock::-webkit-scrollbar {
                display: none;
            }

            .filter-btn {
                flex: 1;
                text-align: center;
                padding: 8px 10px;
                font-size: 11px;
                white-space: nowrap;
            }

            /* Unroll tables into standard card views for natural mobile viewport navigation */
            table, thead, tbody, th, td, tr {
                display: block;
                width: 100%;
            }

            thead {
                display: none; /* Hide header rows safely on mobile view styles */
            }

            tr {
                background: #F8FAFC;
                border: 1px solid #E2E8F0;
                border-radius: 12px;
                margin-bottom: 14px;
                padding: 14px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.01);
            }

            td {
                padding: 6px 0 !important;
                border-bottom: none !important;
                text-align: left !important;
            }

            /* Prepend contextual identifier metadata tags dynamically */
            td:nth-of-type(1)::before {
                content: "STUDENT ROLL";
                display: block;
                font-size: 10px;
                font-weight: 700;
                color: #94A3B8;
                margin-bottom: 2px;
            }

            td:nth-of-type(2)::before {
                content: "TIMELINE & REASON";
                display: block;
                font-size: 10px;
                font-weight: 700;
                color: #94A3B8;
                margin-bottom: 2px;
            }

            td:nth-of-type(3) {
                display: inline-block;
                width: auto;
                margin-top: 4px;
            }

            td:nth-of-type(4) {
                border-t: 1px solid #E2E8F0 !important;
                margin-top: 10px;
                padding-top: 12px !important;
                width: 100%;
            }

            .action-button-group {
                justify-content: flex-start !important;
                width: 100%;
            }

            .inline-action-form {
                flex: 1;
            }

            .btn-control {
                width: 100%;
                padding: 10px;
                text-align: center;
                font-size: 13px;
            }
            
            .empty-indicator {
                padding: 32px 0;
            }
        }
    </style>
</head>

<body>

    <div class="wrapper">

        <?php if (!empty($status_message)): ?>
            <div class="toast-banner"><?php echo $status_message; ?></div>
        <?php endif; ?>

        <div class="metrics-row">
            <div class="summary-card dark">
                <div class="card-meta">
                    <h3 style="color: #94A3B8;">Active Away Today</h3>
                    <p style="color: #94A3B8;">Approved students excused from kitchen serving counts to avoid waste and fraud.</p>
                </div>
                <div class="card-value"><?php echo intval($current_away_count); ?></div>
            </div>

            <div class="summary-card">
                <div class="card-meta">
                    <h3>Pending Review</h3>
                    <p>Applications requiring validation and emergency parent verification.</p>
                </div>
                <div class="card-value" style="color: <?php echo $total_pending_count > 0 ? '#D97706' : '#0F172A'; ?>;">
                    <?php echo intval($total_pending_count); ?>
                </div>
            </div>
        </div>

        <div class="workspace-panel">

            <div class="panel-header-strip">
                <h2>📋 Student Leave & Verification Dashboard</h2>

                <div class="filter-dock">
                    <?php foreach ($valid_filters as $f): ?>
                        <a href="?filter=<?php echo $f; ?>" class="filter-btn <?php echo ($view_filter === $f) ? 'active' : ''; ?>">
                            <?php echo $f; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="table-viewport">
                <table>
                    <thead>
                        <tr>
                            <th>Hostel Roll No</th>
                            <th>Excused Timeline & Reason</th>
                            <th>Active Status</th>
                            <th style="text-align: right;">Administrative Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_leaves)): ?>
                            <tr>
                                <td colspan="4" class="empty-indicator">
                                    📭 No verified leave log files matched the requested filter parameters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($all_leaves as $row): ?>
                                <tr>
                                    <td>
                                        <span class="roll-label"><?php echo htmlspecialchars($row['hostel_roll']); ?></span>
                                    </td>
                                    <td>
                                        <div class="date-range-text">
                                            <?php echo date("d M", strtotime($row['start_date'])); ?> ➔ <?php echo date("d M, Y", strtotime($row['end_date'])); ?>
                                        </div>
                                        <?php if (!empty($row['reason'])): ?>
                                            <div class="reason-subtext"><strong>Reason:</strong> "<?php echo htmlspecialchars($row['reason']); ?>"</div>
                                        <?php endif; ?>
                                        <?php if ($has_parent_column && !empty($row['parent_contact']) && $row['parent_contact'] !== 'Not Added'): ?>
                                            <div class="parent-contact-text">📞 Parent Contact: <?php echo htmlspecialchars($row['parent_contact']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (strtolower($row['status']) === 'pending'): ?>
                                            <div class="action-button-group" style="justify-content: flex-end;">
                                                <form method="POST" class="inline-action-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action_type" value="approve">
                                                    <button type="submit" class="btn-control approve">Approve</button>
                                                </form>
                                                <form method="POST" class="inline-action-form">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="leave_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action_type" value="reject">
                                                    <button type="submit" class="btn-control reject">Reject</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span style="font-size: 12px; color: #94A3B8; font-weight: 600; text-transform: uppercase;">
                                                Archived
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
        <div style="text-align: center; margin-top: 32px;">
            <a href="dashboard.php" style="display: inline-flex; align-items: center; gap: 8px; background: #FFFFFF; color: #475569; font-weight: 700; font-size: 14px; text-decoration: none; padding: 12px 24px; border: 2px solid #CBD5E1; border-radius: 8px; transition: all 0.15s ease;">
                ⬅ Return to Dashboard
            </a>
        </div>
    </div>

</body>

</html>