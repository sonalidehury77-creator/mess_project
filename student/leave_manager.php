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
$error_msg = "";
$success_msg = "";

// Generate CSRF Security token for safe submission
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ==========================================================================
   LEAVE SUBMISSION ROUTINE
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF Security Guard
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_msg = "Security session expired. Please refresh and try again.";
    } else {
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $parent_contact = trim($_POST['parent_contact'] ?? '');
        $today = date("Y-m-d");

        if (empty($start_date) || empty($end_date)) {
            $error_msg = "Please select a valid date timeline range.";
        } elseif ($start_date < $today) {
            $error_msg = "Leave application dates cannot be set in the past.";
        } elseif ($end_date < $start_date) {
            $error_msg = "End date cannot be earlier than your leave start date.";
        } elseif (empty($reason)) {
            $error_msg = "Please provide a valid reason for verification purposes.";
        } elseif (empty($parent_contact) || !preg_match('/^[0-9\-\+\s]{10,15}$/', $parent_contact)) {
            $error_msg = "Please provide a valid 10-digit parent or guardian contact phone number.";
        } else {
            try {
                // Check for overlapping active leaves to prevent duplication fraud
                $check_sql = "SELECT COUNT(*) FROM mess_leaves WHERE hostel_roll = ? AND status != 'rejected' AND NOT (end_date < ? OR start_date > ?)";
                $chk_stmt = $conn->prepare($check_sql);
                $chk_stmt->execute([$hostel_roll, $start_date, $end_date]);

                if ($chk_stmt->fetchColumn() > 0) {
                    $error_msg = "An active or pending leave entry already exists within this date range.";
                } else {
                    try {
                        $ins_stmt = $conn->prepare("INSERT INTO mess_leaves (hostel_roll, start_date, end_date, reason, parent_contact, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                        $ins_stmt->execute([$hostel_roll, $start_date, $end_date, $reason, $parent_contact]);
                    } catch (PDOException $e) {
                        if ($e->getCode() == '42S22') {
                            $ins_stmt = $conn->prepare("INSERT INTO mess_leaves (hostel_roll, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, 'pending')");
                            $ins_stmt->execute([$hostel_roll, $start_date, $end_date, $reason]);
                        } else {
                            throw $e;
                        }
                    }
                    $success_msg = "Leave request submitted! Mess count billing paused pending admin approval.";
                }
            } catch (PDOException $e) {
                $error_msg = "System error processing your request. Please try again later.";
            }
        }
    }
}

/* ==========================================================================
   FETCH STUDENT REGISTRY TIMELINES
   ========================================================================== */
$leaves = [];
try {
    $stmt = $conn->prepare("SELECT start_date, end_date, reason, status FROM mess_leaves WHERE hostel_roll = ? ORDER BY start_date DESC");
    $stmt->execute([$hostel_roll]);
    $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fail silently
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacation & Leave Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,400&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #F8FAFC;
            background-image: radial-gradient(#E2E8F0 1.1px, transparent 1.1px);
            background-size: 24px 24px;
            color: #0F172A;
            padding: 30px 24px 60px 24px;
            min-height: 100vh;
        }

        /* PREMIUM INTEGRATED BAR HEADER */
        .top-navbar {
            max-width: 1140px;
            margin: 0 auto 32px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FFFFFF;
            padding: 16px 28px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            box-shadow: 0 4px 10px -2px rgba(15, 23, 42, 0.02);
        }

        .brand-meta h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 600;
            color: #0F172A;
            letter-spacing: -0.01em;
        }

        .brand-meta p {
            font-size: 12px;
            color: #64748B;
            margin-top: 2px;
            font-weight: 500;
        }

        /* PROPER RETURN TO DESKTOP BUTTON */
        .btn-desktop-return {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0F172A;
            color: #FFFFFF;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        }

        .btn-desktop-return:hover {
            background: #334155;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.2);
        }

        .btn-desktop-return svg {
            width: 16px;
            height: 16px;
            transition: transform 0.2s ease;
        }

        .btn-desktop-return:hover svg {
            transform: translateX(-3px);
        }

        /* CORE GRID CONTAINER */
        .wrapper {
            max-width: 1140px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.1fr 1.3fr;
            gap: 32px;
            align-items: start;
        }

        @media (max-width: 915px) {
            .wrapper {
                grid-template-columns: 1fr;
            }
        }

        .panel-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.03), 0 8px 10px -6px rgba(15, 23, 42, 0.03);
        }

        .panel-box h2 {
            font-size: 18px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        p.desc {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 26px;
            line-height: 1.5;
        }

        /* MODERN FORM CONTROLS */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        @media (max-width: 480px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #CBD5E1;
            background: #F8FAFC;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0F172A;
            font-weight: 500;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-group input:focus, .form-group textarea:focus {
            border-color: #3B82F6;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: none;
        }

        .btn-submit {
            width: 100%;
            background: #3B82F6;
            color: #FFFFFF;
            border: none;
            padding: 13px;
            font-weight: 600;
            font-size: 13.5px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .btn-submit:hover {
            background: #2563EB;
        }
        
        .btn-submit:active {
            transform: scale(0.99);
        }

        /* SYSTEM STATUS NOTICES */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 24px;
            line-height: 1.4;
        }

        .alert-danger {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FEE2E2;
        }

        .alert-success {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }

        /* TIMELINE LAYOUT DESIGN */
        .timeline-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            text-align: left;
        }

        .timeline-table th {
            padding: 12px 14px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748B;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #E2E8F0;
            background: #F8FAFC;
        }

        .timeline-table td {
            padding: 18px 14px;
            border-bottom: 1px solid #F1F5F9;
            color: #1E293B;
            font-size: 13.5px;
        }

        .timeline-table tr:hover td {
            background: #FAFAFA;
        }

        .badge-status {
            padding: 4px 10px;
            font-size: 10.5px;
            font-weight: 700;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            display: inline-block;
        }

        .badge-status.approved {
            background: #DEF7EC;
            color: #03543F;
        }
        
        .badge-status.pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .badge-status.rejected {
            background: #FDE8E8;
            color: #9B1C1C;
        }
        
        .reason-render {
            font-size: 12px;
            color: #64748B;
            display: block;
            margin-top: 5px;
            font-style: italic;
            background: #F8FAFC;
            padding: 6px 10px;
            border-radius: 6px;
            border-left: 3px solid #CBD5E1;
        }

        .feedback-box {
            font-size: 12px;
            margin-top: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>

<body>

    <header class="top-navbar">
        <div class="brand-meta">
            <h1>Vacation & Leave Manager</h1>
            <p>Logged Account: <?php echo htmlspecialchars($hostel_roll); ?></p>
        </div>
        <a href="dashboard.php" class="btn-desktop-return">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Return to Desktop
        </a>
    </header>

    <div class="wrapper">

        <div class="panel-box">
            <h2>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path></svg>
                Apply for Mess Off
            </h2>
            <p class="desc">Going home or outside of town? Enter your travel dates to pause your mess food tracking accounts smoothly.</p>

            <?php if (!empty($error_msg)) {
                echo "<div class='alert alert-danger'>$error_msg</div>";
            } ?>
            <?php if (!empty($success_msg)) {
                echo "<div class='alert alert-success'>$success_msg</div>";
            } ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Commencement Date</label>
                        <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Termination Date</label>
                        <input type="date" name="end_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Parent / Guardian Contact Number</label>
                    <input type="tel" name="parent_contact" placeholder="e.g., 9876543210" required>
                </div>

                <div class="form-group">
                    <label>Reason for Leave</label>
                    <textarea name="reason" placeholder="State your travel reason clearly..." rows="3" required></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Submit Leave Request</button>
            </form>
        </div>

        <div class="panel-box">
            <h2>
                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                Your Leave History Log
            </h2>
            <p class="desc">History of requested dates showing whether they are approved or pending verification.</p>

            <table class="timeline-table">
                <thead>
                    <tr>
                        <th>Duration Span & Context</th>
                        <th style="width: 110px; text-align: center;">Status Flag</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaves)) { ?>
                        <tr>
                            <td colspan="2" style="color:#94A3B8; text-align:center; padding: 60px 0; font-size: 13px;">No scheduled leaves found in your registry.</td>
                        </tr>
                    <?php } else {
                        foreach ($leaves as $leave) { 
                            $status = strtolower($leave['status']);
                            
                            $status_note = "";
                            if ($status === 'approved') {
                                $status_note = "<div class='feedback-box' style='color: #10B981;'>✨ Your leave is approved! Enjoy your vacation.</div>";
                            } elseif ($status === 'rejected') {
                                $status_note = "<div class='feedback-box' style='color: #EF4444;'>❌ Request rejected. Contact office manager.</div>";
                            } else {
                                $status_note = "<div class='feedback-box' style='color: #D97706;'>⏳ Pending administrative verification...</div>";
                            }
                        ?>
                            <tr>
                                <td>
                                    <span style="font-size: 14px; font-weight: 600; color: #0F172A;">
                                        <?php echo date("d M", strokeToTime($leave['start_date'])); ?>
                                    </span> 
                                    <span style="color: #94A3B8; font-weight: 400; padding: 0 4px;">→</span> 
                                    <span style="font-size: 14px; font-weight: 600; color: #0F172A;">
                                        <?php echo date("d M, Y", strokeToTime($leave['end_date'])); ?>
                                    </span>
                                    
                                    <?php if(!empty($leave['reason'])): ?>
                                        <span class="reason-render">"<?php echo htmlspecialchars($leave['reason']); ?>"</span>
                                    <?php endif; ?>
                                    
                                    <?php echo $status_note; ?>
                                </td>
                                <td style="text-align: center; vertical-align: top; padding-top: 16px;">
                                    <span class="badge-status <?php echo $status; ?>"><?php echo htmlspecialchars($leave['status']); ?></span>
                                </td>
                            </tr>
                    <?php }
                    } ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>