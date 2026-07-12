<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
require_once __DIR__ . "/../config/db_connect.php";

/* ==========================================================================
   1. SESSION AUTHENTICATION CHECK
   ========================================================================== */
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/* ==========================================================================
   2. SYSTEM METRICS RETRIEVAL (PDO)
   ========================================================================== */
try {
    /* Total Enrolled Students */
    $studentStmt = $pdo->prepare("SELECT COUNT(*) as total FROM student");
    $studentStmt->execute();
    $student_count = $studentStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    /* Tomorrow's Meal Reservations Count */
    date_default_timezone_set("Asia/Kolkata");
    $tomorrow = date("Y-m-d", strtotime("+1 day"));
    $mealsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM meals WHERE date = :tomorrow");
    $mealsStmt->execute(['tomorrow' => $tomorrow]);
    $tomorrow_meals = $mealsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    /* Notice Board Announcements Count */
    $announceStmt = $pdo->prepare("SELECT COUNT(*) as total FROM announcements");
    $announceStmt->execute();
    $announcement_count = $announceStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    /* Total Mess Bills Count */
    $billsStmt = $pdo->prepare("SELECT COUNT(*) as total FROM bills");
    $billsStmt->execute();
    $bill_count = $billsStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
} catch (PDOException $e) {
    error_log("Admin dashboard metrics retrieval failure: " . $e->getMessage());
    die("❌ A system error occurred while loading dashboard statistics.");
}

/* ==========================================================================
   3. SYSTEM DATE & TIME VARIABLES
   ========================================================================== */
$display_date_string = date("l, d M Y");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Dashboard</title>
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
            min-height: 100vh;
        }

        .dashboard-layout {
            display: grid;
            grid-template-columns: 290px 1fr;
            min-height: 100vh;
        }

        /* SIDEBAR COMPONENT UI */
        .sidebar-profile {
            background: #FFFFFF;
            border-right: 1px solid #E2E8F0;
            padding: 40px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 4px 0 24px rgba(15, 23, 42, 0.01);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .profile-avatar-wrapper {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #F1F5F9;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 44px;
            flex-shrink: 0;
        }

        .sidebar-profile h2 {
            font-size: 18px;
            color: #0F172A;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .student-meta {
            font-size: 13px;
            color: #EF4444;
            background: #FEF2F2;
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 24px;
        }

        .profile-details-box {
            width: 100%;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .profile-detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 10px 0;
            border-bottom: 1px dashed #E2E8F0;
        }

        .profile-detail-row:last-child {
            border-bottom: none;
        }

        .profile-detail-row span.lbl {
            color: #64748B;
            font-weight: 500;
        }

        .profile-detail-row span.val {
            color: #0F172A;
            font-weight: 600;
        }

        /* STRATEGIC ADMIN NOTICES SPACE COVER */
        .sidebar-instructions-container {
            width: 100%;
            text-align: left;
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: auto;
        }

        .sidebar-instructions-container h4 {
            font-size: 13px;
            color: #92400E;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sidebar-instructions-container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-instructions-container li {
            font-size: 11px;
            color: #78350F;
            line-height: 1.5;
            margin-bottom: 8px;
            position: relative;
            padding-left: 12px;
        }

        .sidebar-instructions-container li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: #D97706;
            font-weight: bold;
        }

        .sidebar-instructions-container li:last-child {
            margin-bottom: 0;
        }

        .btn-logout {
            width: 100%;
            margin-top: 24px;
            padding: 12px;
            background: #FEF2F2;
            color: #EF4444;
            border: 1px solid #FEE2E2;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
            text-align: center;
            flex-shrink: 0;
        }

        .btn-logout:hover {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* WORKSPACE COMPONENT UI */
        .workspace {
            padding: 40px;
            max-width: 1240px;
            width: 100%;
            justify-self: center;
        }

        .meta-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 16px;
            background: #FFFFFF;
            padding: 16px 24px;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
        }

        #clock {
            font-size: 16px;
            font-weight: 700;
            color: #0F172A;
        }

        .time-meta-block {
            font-size: 14px;
            color: #475569;
            font-weight: 600;
        }

        /* METRICS SUMMARY ROW */
        .metrics-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .metric-card h4 {
            font-size: 11px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-card p {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 2px;
        }

        /* INTERACTIVE LINK CARDS HUB */
        .navigation-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .action-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            padding: 24px;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.05);
            border-color: #CBD5E1;
        }

        .action-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #F1F5F9;
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .action-card:hover .action-icon-circle {
            background: #0F172A;
            color: #FFFFFF;
        }

        .action-card h3 {
            font-size: 15px;
            color: #0F172A;
            font-weight: 700;
        }

        .action-card p {
            font-size: 12px;
            color: #64748B;
            line-height: 1.4;
        }

        /* ADMISSION HIGHLIGHT BUTTON */
        .primary-action-card {
            background: #3B82F6 !important;
            border-color: #2563EB !important;
        }
        .primary-action-card h3, .primary-action-card p {
            color: #FFFFFF !important;
        }
        .primary-action-card .action-icon-circle {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #FFFFFF !important;
        }
        .primary-action-card:hover .action-icon-circle {
            background: #FFFFFF !important;
            color: #3B82F6 !important;
        }

        .double-width {
            grid-column: span 2;
        }

        /* ADVICE DESK LAYOUT */
        .help-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
        }

        .help-box h3 {
            font-size: 16px;
            color: #0F172A;
            margin-bottom: 16px;
        }

        .help-item {
            padding: 12px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.5;
            color: #475569;
            margin-bottom: 12px;
        }

        .help-item:last-child {
            margin-bottom: 0;
        }

        /* ==========================================================================
           RESPONSIVE MOBILE BREAKPOINTS MEDIA QUERY
           ========================================================================== */
        @media (max-width: 992px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
            }

            .sidebar-profile {
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid #E2E8F0;
                padding: 32px 20px 24px 20px;
            }

            .sidebar-instructions-container {
                margin-bottom: 16px;
            }

            .workspace {
                padding: 24px 16px;
            }

            .double-width {
                grid-column: span 1;
            }
        }

        @media (max-width: 576px) {
            .meta-header-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 14px 16px;
            }
            
            .metrics-summary-grid {
                grid-template-columns: 1fr;
            }

            .navigation-cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-layout">

        <!-- Left Admin Profile Sidebar Module -->
        <aside class="sidebar-profile">
            <div class="profile-avatar-wrapper">
                🛡️
            </div>
            <h2>Admin Portal</h2>
            <div class="student-meta">ID: <?php echo htmlspecialchars($_SESSION['admin']); ?></div>

            <div class="profile-details-box">
                <div class="profile-detail-row">
                    <span class="lbl">Role Profile</span>
                    <span class="val">System Admin</span>
                </div>
                <div class="profile-detail-row">
                    <span class="lbl">System Status</span>
                    <span class="val" style="color: #10B981;">Online</span>
                </div>
            </div>

            <!-- Important Instructions Covering Empty Space -->
            <div class="sidebar-instructions-container">
                <h4>🛡️ Compliance Protocols</h4>
                <ul>
                    <li>Cross-check active dining headcount variables prior to certifying automated batch transactions.</li>
                    <li>Always commit systematic log exits when leaving physical checkout terminals unattended.</li>
                    <li>Audit incoming database server connection reports weekly for system monitoring optimization.</li>
                </ul>
            </div>

            <a href="logout.php" class="btn-logout">🚪 Log Out Portal</a>
        </aside>

        <!-- Right Working Dashboard Panel -->
        <main class="workspace">

            <!-- Real-Time Information Tracker -->
            <div class="meta-header-bar">
                <div id="clock">🕒 --:--:-- --</div>
                <div class="time-meta-block">📅 <?php echo $display_date_string; ?></div>
            </div>

            <!-- Dashboard Analytics Quick Summary Cards Row -->
            <div class="metrics-summary-grid">
                <div class="metric-card">
                    <div class="metric-icon" style="background:#EFF6FF; color:#3B82F6;">🎓</div>
                    <div>
                        <h4>Total Students</h4>
                        <p><?php echo $student_count; ?></p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon" style="background:#ECFDF5; color:#10B981;">🍽️</div>
                    <div>
                        <h4>Tomorrow's Meals</h4>
                        <p><?php echo $tomorrow_meals; ?></p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon" style="background:#FFFBEB; color:#F59E0B;">📢</div>
                    <div>
                        <h4>Active Notices</h4>
                        <p><?php echo $announcement_count; ?></p>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-icon" style="background:#FEF2F2; color:#EF4444;">💵</div>
                    <div>
                        <h4>Bills Issued</h4>
                        <p><?php echo $bill_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Admin Action Core Map Grid -->
            <div class="navigation-cards-grid">
                
                <a href="scan_token.php" class="action-card" style="border: 2px solid rgba(16, 185, 129, 0.3);">
                    <div class="action-icon-circle" style="background:#ECFDF5; color:#10B981;">📷</div>
                    <h3>Scan Meal QR Code</h3>
                    <p>Scan incoming student check-in QR tokens live at the entrance desk.</p>
                </a>

                <a href="add_student.php" class="action-card primary-action-card">
                    <div class="action-icon-circle">➕</div>
                    <h3>Register New Student</h3>
                    <p>Enter details and files to register a newly admitted hosteller profile.</p>
                </a>

                <a href="feedback_analytics.php" class="action-card">
                    <div class="action-icon-circle">⭐</div>
                    <h3>Ratings & Feedback</h3>
                    <p>Read and track student submission feedback regarding food quality reviews.</p>
                </a>

                <a href="leave_analytics.php" class="action-card">
                    <div class="action-icon-circle">🌴</div>
                    <h3>Leave Trackers</h3>
                    <p>View, check, and authorize student requests for mess-off leave schedules.</p>
                </a>

                <a href="students.php" class="action-card">
                    <div class="action-icon-circle">👥</div>
                    <h3>Student Directory</h3>
                    <p>Look up names, verify registration records, or update room allocations.</p>
                </a>

                <a href="menu.php" class="action-card">
                    <div class="action-icon-circle">📅</div>
                    <h3>Manage Weekly Menu</h3>
                    <p>Edit or add menu adjustments for standard weekly item plans.</p>
                </a>

                <a href="manage_special_menu.php" class="action-card">
                    <div class="action-icon-circle">🎉</div>
                    <h3>Special Feast Menu</h3>
                    <p>Announce and update premium menus for upcoming festivals or events.</p>
                </a>

                <a href="announcements.php" class="action-card">
                    <div class="action-icon-circle">📢</div>
                    <h3>Hostel Announcements</h3>
                    <p>Broadcast alerts, reminders, and news to the student notice boards.</p>
                </a>

                <a href="bill.php" class="action-card double-width">
                    <div class="action-icon-circle">💵</div>
                    <h3>Mess Fee Billing & Records</h3>
                    <p>Calculate dynamic expenses, process due balances, and update monthly payment ledgers.</p>
                </a>

                <a href="report.php" class="action-card">
                    <div class="action-icon-circle">📊</div>
                    <h3>Consumption Reports</h3>
                    <p>Analyze overall raw dining data calculations and actual food usage metrics.</p>
                </a>

                <a href="analytics.php" class="action-card">
                    <div class="action-icon-circle">📈</div>
                    <h3>Visual Data Charts</h3>
                    <p>Review systemic visual graph summaries mapping out daily hostel operations.</p>
                </a>

            </div>

            <!-- Centralized Admin Management Guide Desk Module -->
            <div class="help-box">
                <h3>💡 Admin Operating Guide Desk</h3>
                <div class="help-item">
                    <strong style="color: #0F172A; display: block; margin-bottom: 2px;">QR Verification</strong>
                    Keep your dynamic camera device operating accurately at the counter area to read student profile voucher scans.
                </div>
                <div class="help-item">
                    <strong style="color: #0F172A; display: block; margin-bottom: 2px;">Monthly Billing Logs</strong>
                    Calculate and post updated fee balance entries at the conclusion of each active calendar cycle.
                </div>
            </div>

        </main>
    </div>

    <!-- Frontend Interactive Javascript Module Operations -->
    <script>
        // Synchronous Clock Module Engine
        function runRealTimeClock() {
            const clockNode = document.getElementById("clock");
            if (clockNode) {
                const now = new Date();
                clockNode.innerHTML = "🕒 " + now.toLocaleTimeString('en-US', {
                    hour12: true
                });
            }
        }
        setInterval(runRealTimeClock, 1000);
        window.onload = runRealTimeClock;
    </script>
</body>

</html>