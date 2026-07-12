<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
require_once __DIR__ . "/../config/db_connect.php";

/* ==========================================================================
   1. SESSION AUTHENTICATION CHECK
   ========================================================================== */
if (!isset($_SESSION['hostel_roll']) || empty($_SESSION['hostel_roll'])) {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

$hostel_roll = $_SESSION['hostel_roll'];

/* ==========================================================================
   2. STUDENT PROFILE DATA RETRIEVAL (PDO)
   ========================================================================== */
try {
    $studentStmt = $pdo->prepare("SELECT * FROM student WHERE hostel_roll = :hostel_roll LIMIT 1");
    $studentStmt->execute(['hostel_roll' => $hostel_roll]);
    $user = $studentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: ../auth/login.php?error=not_found");
        exit();
    }
} catch (PDOException $e) {
    error_log("Dashboard Student Fetch Error: " . $e->getMessage());
    die("A system error occurred while loading your profile. Please check back shortly.");
}

/* ==========================================================================
   3. ACCOUNT RESTRICTION CHECK
   ========================================================================== */
if (($user['status'] ?? 'active') === 'blocked') {
    $reason = htmlspecialchars($user['block_reason'] ?? "Contact Hostel Administration Office");
    session_destroy();
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Account Restricted</title>
        <link rel="stylesheet" href="../css/login.css">
    </head>

    <body>
        <div class="container" style="max-width: 460px; margin: 80px auto; text-align: center; font-family: sans-serif;">
            <div style="font-size: 54px; margin-bottom: 12px;">🚫</div>
            <h2 style="color: #EF4444; margin-bottom: 12px;">Account Suspended</h2>
            <p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 20px;">
                Your hostel management account access has been temporarily restricted.
                <br><strong style="color: #1E293B;">Reason:</strong> "<?php echo $reason; ?>"
            </p>
            <a href="../auth/login.php" class="btn" style="background: #475569; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">Back to Login</a>
        </div>
    </body>

    </html>
<?php
    exit();
}

/* ==========================================================================
   4. SYSTEM DATE & TIME VARIABLES
   ========================================================================== */
date_default_timezone_set("Asia/Kolkata");
$today_date_raw       = date("Y-m-d");
$display_date_string = date("l, d M Y");

/* ==========================================================================
   5. FETCH LATEST ANNOUNCEMENTS (PDO)
   ========================================================================== */
try {
    $announceStmt = $pdo->prepare("SELECT * FROM announcements WHERE announce_date >= :today ORDER BY announce_date ASC LIMIT 5");
    $announceStmt->execute(['today' => $today_date_raw]);
    $announcements = $announceStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Dashboard Announcements Error: " . $e->getMessage());
    $announcements = [];
}

/* ==========================================================================
   6. FETCH TOMORROW'S SPECIAL MENU ITEMS (PDO)
   ========================================================================== */
try {
    $specialStmt = $pdo->prepare("SELECT * FROM menu WHERE special_date >= :today AND is_special = 1 AND is_active = 1 ORDER BY special_date ASC LIMIT 1");
    $specialStmt->execute(['today' => $today_date_raw]);
    $special_menu = $specialStmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (PDOException $e) {
    error_log("Dashboard Special Menu Error: " . $e->getMessage());
    $special_menu = null;
}

/* ==========================================================================
   7. FORM HANDLING ROUTINES
   ========================================================================== */
$status_msg = "";
$status_class = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_feedback'])) {
        $rating = (int)$_POST['rating'];
        $comments = trim($_POST['comments']);
        try {
            $fStmt = $pdo->prepare("INSERT INTO meal_feedback (hostel_roll, date, rating, comments) VALUES (?, ?, ?, ?)");
            $fStmt->execute([$hostel_roll, $today_date_raw, $rating, $comments]);
            $status_msg = "🎉 Thank you! Your dynamic feedback has been compiled successfully.";
            $status_class = "success-alert";
        } catch (PDOException $e) {
            $status_msg = "❌ Error: Feedback record collision, or you have already metrics for today.";
            $status_class = "danger-alert";
        }
    }

    if (isset($_POST['submit_leave'])) {
        $start_date = $_POST['leave_start'];
        $end_date = $_POST['leave_end'];
        if ($start_date >= $today_date_raw && $end_date >= $start_date) {
            try {
                $lStmt = $pdo->prepare("INSERT INTO vacation_leaves (hostel_roll, start_date, end_date, status) VALUES (?, ?, ?, 'Pending')");
                $lStmt->execute([$hostel_roll, $start_date, $end_date]);
                $status_msg = "📅 Leave matrix logged successfully! Awaiting warden operational sign-off.";
                $status_class = "success-alert";
            } catch (PDOException $e) {
                $status_msg = "❌ Infrastructure failure processing your calendar exception parameters.";
                $status_class = "danger-alert";
            }
        } else {
            $status_msg = "⚠️ Conflict: Calendar constraints must target active or future operational intervals.";
            $status_class = "warning-alert";
        }
    }
}

/* ==========================================================================
   8. STUDENT PROFILE PHOTO COMPONENT
   ========================================================================== */
$photo = "../uploads/students/default.png";
if (!empty($user['photo'])) {
    $check_path = __DIR__ . "/../" . $user['photo'];
    if (file_exists($check_path)) {
        $photo = "../" . $user['photo'];
    }
}

/* ==========================================================================
   9. RENDER SETUP & CORE INTERACTION INTERFACE
   ========================================================================== */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Control Center - Dashboard Hub</title>
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
            grid-template-columns: 310px 1fr;
            min-height: 100vh;
        }

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
        }

        .profile-avatar-wrapper {
            position: relative;
            z-index: 10;
            width: 115px;
            height: 115px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #F1F5F9;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            background: #F8FAFC;
            cursor: pointer;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), border-radius 0.4s ease, border-color 0.2s;
        }

        .profile-avatar-wrapper:hover {
            border-color: #3B82F6;
        }

        /* ADD THIS: Handles the smooth scaling when clicked */
        .profile-avatar-wrapper.zoomed-view {
            transform: scale(2.2) translateY(20px);
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
            border-color: #3B82F6;
            z-index: 999;
        }

        .profile-avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .sidebar-profile h2 {
            font-size: 18px;
            color: #0F172A;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .student-meta {
            font-size: 13px;
            color: #3B82F6;
            background: #EFF6FF;
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
            margin-bottom: auto;
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

        .btn-logout {
            width: 100%;
            margin-top: 40px;
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
        }

        .btn-logout:hover {
            background: #FEE2E2;
            color: #991B1B;
        }

        .workspace {
            padding: 40px;
            max-width: 1320px;
            width: 100%;
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

        .hub-section-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navigation-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 36px;
        }

        .action-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            padding: 24px;
            border-radius: 14px;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: transparent;
            transition: background 0.2s;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 24px -6px rgba(15, 23, 42, 0.04);
            border-color: #CBD5E1;
        }

        .action-card.primary:hover::before {
            background: #3B82F6;
        }

        .action-card.emerald:hover::before {
            background: #10B981;
        }

        .action-card.amber:hover::before {
            background: #F59E0B;
        }

        .action-card.indigo:hover::before {
            background: #6366F1;
        }

        .action-card.rose:hover::before {
            background: #F43F5E;
        }

        .action-card.violet:hover::before {
            background: #8B5CF6;
        }

        .action-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #F1F5F9;
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: all 0.2s;
        }

        .action-card:hover .action-icon-circle {
            background: #0F172A;
            color: #FFFFFF;
        }

        .action-card h3 {
            font-size: 16px;
            color: #0F172A;
            font-weight: 700;
        }

        .action-card p {
            font-size: 13px;
            color: #64748B;
            line-height: 1.5;
        }

        .alert-banner {
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: #1E40AF;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-toast {
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .success-alert {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        .danger-alert {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FEE2E2;
        }

        .warning-alert {
            background: #FFFBEB;
            color: #92400E;
            border: 1px solid #FDE68A;
        }

        .special-menu-link {
            text-decoration: none;
            display: block;
            margin-bottom: 36px;
            transition: transform 0.2s;
        }

        .special-menu-link:hover {
            transform: translateY(-2px);
        }

        .special-menu-banner {
            background: linear-gradient(135deg, #059669, #047857);
            color: white;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.2);
            border: 1px solid #065F46;
        }

        .special-menu-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .special-meal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .announcement-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .announcement-box h3 {
            font-size: 16px;
            color: #0F172A;
            margin-bottom: 16px;
        }

        .announcement-item {
            padding: 16px 0;
            border-bottom: 1px solid #F1F5F9;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .notice-attachment-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 10px;
            padding: 6px 14px;
            background: #F1F5F9;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #E2E8F0;
            transition: all 0.2s;
        }

        .notice-attachment-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }
    </style>
</head>

<body>

    <div class="dashboard-layout">

        <aside class="sidebar-profile">
            <div class="profile-avatar-wrapper" onclick="toggleProfileZoom(event)">
                <img src="<?php echo htmlspecialchars($photo); ?>" alt="Student Profile Photo" id="profileImageSource">
            </div>
            <h2>👋 <?php echo htmlspecialchars($user['name']); ?></h2>
            <div class="student-meta">Roll No: <?php echo htmlspecialchars($user['hostel_roll']); ?></div>

            <div class="profile-details-box">
                <div class="profile-detail-row">
                    <span class="lbl">Room Assigned</span>
                    <span class="val"><?php echo htmlspecialchars($user['room_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="profile-detail-row">
                    <span class="lbl">Department</span>
                    <span class="val"><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></span>
                </div>
                <div class="profile-detail-row">
                    <span class="lbl">Account Status</span>
                    <span class="val" style="color: #10B981;">Active</span>
                </div>
            </div>

            <div class="profile-details-box" style="margin-top: 16px; border-color: #BFDBFE; background: #F0F9FF;">
                <div style="font-size: 12px; font-weight: 700; color: #0369A1; text-transform: uppercase; margin-bottom: 8px; text-align: left;">
                    📞 Mess Helpdesk
                </div>
                <div class="profile-detail-row">
                    <span class="lbl">Phone</span>
                    <span class="val"><a href="tel:+919876543210" style="color: inherit; text-decoration: none;">+91 98765 43210</a></span>
                </div>
                <div class="profile-detail-row">
                    <span class="lbl">Email</span>
                    <span class="val"><a href="mailto:mess.help@hostel.edu" style="color: inherit; text-decoration: none;">mess.help@hostel.edu</a></span>
                </div>
                <div style="font-size: 11px; color: #0369A1; font-weight: 600; line-height: 1.4; margin-top: 8px; text-align: left; background: #E0F2FE; padding: 6px; border-radius: 6px;">
                    ⏱️ Support Hours: 10:00 AM – 5:00 PM
                </div>
            </div>

            <a href="logout.php" class="btn-logout">🚪 Log Out Portal</a>
        </aside>

        <main class="workspace">

            <div class="meta-header-bar">
                <div id="clock">🕒 --:--:-- --</div>
                <div class="time-meta-block">📅 <?php echo $display_date_string; ?></div>
            </div>

            <?php if (!empty($status_msg)): ?>
                <div class="status-toast <?php echo $status_class; ?>">
                    <?php echo $status_msg; ?>
                </div>
            <?php endif; ?>

            <?php if ($special_menu): ?>
                <?php $meal_date = date("D, d M", strtotime($special_menu['special_date'])); ?>
                <a href="meal.php" class="special-menu-link" title="Manage preferences for this event">
                    <div class="special-menu-banner">
                        <div class="special-menu-title">🥳 Festive Food Alert! Special Menu Published for <?php echo $meal_date; ?>!</div>
                        <div class="special-meal-grid">
                            <div>
                                <strong style="color: #A7F3D0; font-size: 11px; text-transform: uppercase;">Lunch Specials</strong>
                                <p style="margin-top: 4px; font-size: 14px;">
                                    🟢 Veg: <?php echo !empty($special_menu['lunch_veg']) ? htmlspecialchars($special_menu['lunch_veg']) : 'Regular Menu Plan'; ?><br>
                                    🔴 Non-Veg: <?php echo !empty($special_menu['lunch_nonveg']) ? htmlspecialchars($special_menu['lunch_nonveg']) : 'Not Available'; ?>
                                </p>
                            </div>
                            <div>
                                <strong style="color: #A7F3D0; font-size: 11px; text-transform: uppercase;">Dinner Specials</strong>
                                <p style="margin-top: 4px; font-size: 14px;">
                                    🟢 Veg: <?php echo !empty($special_menu['dinner_veg']) ? htmlspecialchars($special_menu['dinner_veg']) : 'Regular Menu Plan'; ?><br>
                                    🔴 Non-Veg: <?php echo !empty($special_menu['dinner_nonveg']) ? htmlspecialchars($special_menu['dinner_nonveg']) : 'Not Available'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endif; ?>

            <h3 class="hub-section-title">⚡ Core Mess Operations</h3>
            <div class="navigation-cards-grid">
                <a href="meal.php" class="action-card primary">
                    <div class="action-icon-circle">🍽️</div>
                    <h3>Meal Selection</h3>
                    <p>Opt-in/out or tailor your upcoming daily meal preferences and track live calorie plans.</p>
                </a>
                <a href="generate_qr.php" class="action-card emerald">
                    <div class="action-icon-circle">📱</div>
                    <h3>Scan Check-In Token</h3>
                    <p>Access your digital verification QR token to unlock and authenticate your meal box at the table desk.</p>
                </a>
                <a href="leave_manager.php" class="action-card amber">
                    <div class="action-icon-circle">📅</div>
                    <h3>Mess Leave Manager</h3>
                    <p>File leave itineraries or freeze meal accounts to systematically deduct fee costs during vacations.</p>
                </a>
                <a href="weekly_menu.php" class="action-card violet">
                    <div class="action-icon-circle">📅</div>
                    <h3>Weekly Menu Matrix</h3>
                    <p>Examine the full weekly programmatic dietary timeline to plan your campus meals in advance.</p>
                </a>
            </div>

            <h3 class="hub-section-title">📊 Ledgers & Performance Insights</h3>
            <div class="navigation-cards-grid">
                <a href="submit_feedback.php" class="action-card indigo">
                    <div class="action-icon-circle">⭐</div>
                    <h3>Submit Food Review</h3>
                    <p>Submit culinary satisfaction rankings and communicate directly with management chefs.</p>
                </a>
                <a href="bill.php" class="action-card rose">
                    <div class="action-icon-circle">💳</div>
                    <h3>Account Billing Ledger</h3>
                    <p>Examine active balances, historical charge iterations, and itemized structural invoices.</p>
                </a>
                <a href="graph.php" class="action-card violet">
                    <div class="action-icon-circle">📈</div>
                    <h3>Consumption Analytics</h3>
                    <p>Review rich visual data distributions highlighting your attendance trends and habits over time.</p>
                </a>
            </div>

            <div class="alert-banner">
                💡 <strong>Operational Policy:</strong> Complete next-day dietary preferences before 10:00 PM lock window tonight!
            </div>

            <?php if (!empty($announcements)): ?>
                <div class="announcement-box">
                    <h3>📢 Institutional Mess Notice Board</h3>
                    <?php foreach ($announcements as $a): ?>
                        <div class="announcement-item">
                            <h4 style="font-size:14px; color:#0F172A; margin-bottom:4px;"><?php echo htmlspecialchars($a['title']); ?></h4>
                            <p style="font-size:13px; color:#475569; line-height:1.5;"><?php echo htmlspecialchars($a['message']); ?></p>

                            <?php
                            $file_field = $a['attachment_file'] ?? $a['file_path'] ?? $a['file'] ?? null;
                            if (!empty($file_field)):
                            ?>
                                <a href="../uploads/attachments/<?php echo htmlspecialchars($file_field); ?>" target="_blank" class="notice-attachment-btn">
                                    📂 View Official Attachment Clarification Document
                                </a>
                            <?php endif; ?>

                            <small style="color:#94A3B8; display:block; margin-top:6px;">📅 Dispatch Date: <?php echo htmlspecialchars($a['announce_date']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <script>
        // Synchronous Clock Module Engine
        function runRealTimeClock() {
            const clockNode = document.getElementById("clock");
            if (clockNode) {
                const now = new Date();
                clockNode.innerHTML = "🕒 " + now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                });
            }
        }

        // Execute immediately and set fallback interval routines
        runRealTimeClock();
        setInterval(runRealTimeClock, 1000);
        window.addEventListener('DOMContentLoaded', runRealTimeClock);

        // Clean In-Place Profile Photo Zoom Toggle
        function toggleProfileZoom(event) {
            // Prevent document body clicks from interfering instantly
            if (event) event.stopPropagation();

            const avatarWrapper = document.querySelector('.profile-avatar-wrapper');
            if (!avatarWrapper) return;

            // Toggle the zoomed view class on the normal profile container
            avatarWrapper.classList.toggle('zoomed-view');
        }

        // Close the zoom automatically if the student clicks anywhere else on the dashboard layout
        document.addEventListener('click', function() {
            const avatarWrapper = document.querySelector('.profile-avatar-wrapper');
            if (avatarWrapper && avatarWrapper.classList.contains('zoomed-view')) {
                avatarWrapper.classList.remove('zoomed-view');
            }
        });
    </script>
</body>

</html>