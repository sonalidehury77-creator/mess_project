<?php
session_start();
include("../config/db_connect.php");

/* ==========================================================================
   1. DATABASE LINK PROTECTION & CONNECTION AUTODETECT
   ========================================================================== */
if (!isset($conn)) {
    $conn = $pdo ?? $db ?? $con ?? null;
    if (!$conn) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'System database link missing.']);
        exit();
    }
}

/* ==========================================================================
   2. DATA REPORT GENERATION EXPORT ENGINE
   ========================================================================== */
if (isset($_GET['export_today'])) {
    $today = date("Y-m-d");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Mess_Attendance_Report_' . $today . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Hostel Roll Number', 'Date', 'Breakfast Served Status', 'Lunch Served Status', 'Dinner Served Status']);
    
    try {
        $stmt = $conn->prepare("SELECT hostel_roll, date, breakfast_served, lunch_served, dinner_served FROM meals WHERE date = ?");
        $stmt->execute([$today]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['hostel_roll'],
                $row['date'],
                ($row['breakfast_served'] == 1 ? 'SERVED' : 'NOT TAKEN'),
                ($row['lunch_served'] == 1 ? 'SERVED' : 'NOT TAKEN'),
                ($row['dinner_served'] == 1 ? 'SERVED' : 'NOT TAKEN')
            ]);
        }
    } catch (PDOException $e) {
        // Suppress errors cleanly inside file download
    }
    fclose($output);
    exit();
}

/* ==========================================================================
   3. INLINE DYNAMIC TIME-WINDOW CHECKER & SCAN PROCESSING
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_scan'])) {
    header('Content-Type: application/json');

    $raw_payload = isset($_POST['payload']) ? trim($_POST['payload']) : '';
    $parts = explode("|", $raw_payload);
    $parts = array_map('trim', $parts);

    if (count($parts) !== 2) {
        echo json_encode(['success' => false, 'message' => 'Invalid QR token layout format.']);
        exit();
    }

    list($roll, $received_hash) = $parts;
    $today = date("Y-m-d");

    // Dynamic Food Time Window Logic
    $current_time = date("H:i");
    $active_meal = "inactive";

    if ($current_time >= "08:30" && $current_time <= "09:30") {
        $active_meal = "breakfast";
    } elseif ($current_time >= "11:30" && $current_time <= "13:30") {
        $active_meal = "lunch";
    } elseif ($current_time >= "21:00" && $current_time <= "22:30") {
        $active_meal = "dinner";
    }

    if ($active_meal === "inactive") {
        echo json_encode([
            'success' => false, 
            'message' => '🚫 SCANNING CLOSED. Counter hours: Breakfast (8:30-9:30 AM), Lunch (11:30 AM-1:30 PM), Dinner (9:00-10:30 PM).'
        ]);
        exit();
    }

    // Hash Key Verification
    $secret_salt = "HostelMessSecureSalt2026";
    $computed_hash = substr(hash_hmac("sha256", $roll, $secret_salt), 0, 12);

    if ($received_hash !== $computed_hash) {
        echo json_encode(['success' => false, 'message' => 'Security token check failed. Bad signature.']);
        exit();
    }

    try {
        // Check Booking Status
        $stmt = $conn->prepare("SELECT * FROM meals WHERE hostel_roll = ? AND date = ?");
        $stmt->execute([$roll, $today]);
        $meal_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$meal_record) {
            echo json_encode(['success' => false, 'message' => "No mess registration record found for Roll: $roll today."]);
            exit();
        }

        // Match choices smoothly with active timeframe variables
        $booking_valid = false;
        if ($active_meal === 'breakfast' && (!empty($meal_record['breakfast']) && $meal_record['breakfast'] == 1)) $booking_valid = true;
        if ($active_meal === 'lunch' && (!empty($meal_record['lunch']) && $meal_record['lunch'] == 1)) $booking_valid = true;
        if ($active_meal === 'dinner' && (!empty($meal_record['dinner']) && $meal_record['dinner'] == 1)) $booking_valid = true;

        if (!$booking_valid) {
            echo json_encode(['success' => false, 'message' => "Student did not choose to take $active_meal for today."]);
            exit();
        }

        // Prevent Duplicate Claims
        $served_column = $active_meal . "_served";
        if (isset($meal_record[$served_column]) && (int)$meal_record[$served_column] === 1) {
            echo json_encode(['success' => false, 'message' => "❌ DUPLICATE DENIED! Roll $roll already collected this $active_meal."]);
            exit();
        }

        // Mark as Served
        $update_stmt = $conn->prepare("UPDATE meals SET $served_column = 1 WHERE hostel_roll = ? AND date = ?");
        $update_stmt->execute([$roll, $today]);

        echo json_encode([
            'success' => true,
            'message' => "🟢 APPROVED! Roll $roll verified for " . strtoupper($active_meal) . ".",
            'student' => [
                'roll' => $roll,
                'meal' => ucfirst($active_meal),
                'time' => date('h:i:s A')
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Internal database connection issue.']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Verification Desk</title>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: #F8FAFC; color: #0F172A; padding: 40px 20px; min-height: 100vh; }
        .container { max-width: 1100px; margin: 0 auto; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .btn-back { text-decoration: none; display: inline-flex; align-items: center; gap: 8px; color: #475569; font-size: 14px; font-weight: 700; background: #FFFFFF; padding: 10px 18px; border-radius: 8px; border: 1px solid #E2E8F0; transition: all 0.15s ease; }
        .btn-back:hover { background: #F1F5F9; color: #0F172A; }
        .btn-excel { text-decoration: none; display: inline-flex; align-items: center; gap: 8px; color: #FFFFFF; font-size: 14px; font-weight: 700; background: #16A34A; padding: 10px 18px; border-radius: 8px; transition: background 0.15s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .btn-excel:hover { background: #15803D; }
        .dashboard-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start; }
        @media (max-width: 850px) { .dashboard-layout { grid-template-columns: 1fr; } }
        .panel-box { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
        h1 { font-size: 22px; font-weight: 800; margin-bottom: 6px; letter-spacing: -0.02em; }
        p.subtitle { font-size: 14px; color: #64748B; margin-bottom: 24px; }
        #reader { border-radius: 12px; overflow: hidden; border: 2px solid #E2E8F0 !important; background: #F8FAFC; }
        #reader__dashboard_section_csr button { background: #2563EB !important; color: white !important; border: none !important; padding: 10px 20px !important; border-radius: 8px !important; font-size: 14px !important; font-weight: 600 !important; cursor: pointer; }
        #reader__dashboard_section_csr button:hover { background: #1D4ED8 !important; }
        #console-log { margin-top: 20px; padding: 16px; border-radius: 10px; font-size: 14px; font-weight: 700; text-align: center; display: none; animation: popIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes popIn { 0% { transform: scale(0.96); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .log-success { background: #DCFCE7; color: #15803D; border: 1px solid #BBF7D0; }
        .log-error { background: #FEE2E2; color: #B91C1C; border: 1px solid #FCA5A5; }
        .audit-heading { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .live-indicator { background: #10B981; width: 8px; height: 8px; border-radius: 50%; display: inline-block; animation: blink 1.5s infinite; }
        @keyframes blink { 0%, 100% { opacity: 0.4; transform: scale(0.9); } 50% { opacity: 1; transform: scale(1.1); } }
        .audit-list { display: flex; flex-direction: column; gap: 12px; max-height: 480px; overflow-y: auto; }
        .audit-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 10px; animation: slideDown 0.25s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .student-badge { font-weight: 700; color: #0F172A; font-size: 14px; }
        .meal-badge { background: #EFF6FF; color: #2563EB; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; text-transform: uppercase; }
        .time-badge { font-size: 12px; color: #64748B; font-weight: 600; }
        .empty-state { text-align: center; color: #94A3B8; font-size: 14px; padding: 50px 0; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-actions">
            <a href="dashboard.php" class="btn-back">⬅ Back to Dashboard</a>
            <a href="?export_today=1" class="btn-excel">📊 Download Today's Report</a>
        </div>
        
        <div class="dashboard-layout">
            <div class="panel-box">
                <h1>📸 Mess Entrance Terminal</h1>
                <p class="subtitle">Scan dynamic student tokens to track and verify scheduled dining entries.</p>
                <div id="reader"></div>
                <div id="console-log"></div>
            </div>

            <div class="panel-box">
                <div class="audit-heading">
                    <span>📋 Live Serving Activity</span>
                    <span style="display:flex; align-items:center; gap:6px; font-size:12px; color:#64748B;">
                        <span class="live-indicator"></span>DESK ACTIVE
                    </span>
                </div>
                <div class="audit-list" id="auditStream">
                    <div class="empty-state" id="emptyState">No scans recorded during this monitor cycle yet.</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const logger = document.getElementById("console-log");
        const auditStream = document.getElementById("auditStream");
        const emptyState = document.getElementById("emptyState");
        let scanningActive = true;

        function onScanSuccess(decodedText) {
            if (!scanningActive) return;
            scanningActive = false;

            let formData = new FormData();
            formData.append('ajax_scan', '1');
            formData.append('payload', decodedText);

            fetch('scan_token.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    logger.style.display = "block";
                    logger.className = data.success ? "log-success" : "log-error";
                    logger.innerHTML = data.message;

                    const audioCtx = new(window.AudioContext || window.webkitAudioContext)();
                    const osc = audioCtx.createOscillator();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(data.success ? 880 : 220, audioCtx.currentTime);
                    osc.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.12);

                    if (data.success && data.student) {
                        if (emptyState) emptyState.remove();

                        const newRow = document.createElement("div");
                        newRow.className = "audit-row";
                        newRow.innerHTML = `
                            <div>
                                <span class="student-badge">${data.student.roll}</span>
                                <span class="meal-badge" style="margin-left:10px;">${data.student.meal}</span>
                            </div>
                            <span class="time-badge">${data.student.time}</span>
                        `;
                        auditStream.insertBefore(newRow, auditStream.firstChild);
                    }
                    setTimeout(() => { scanningActive = true; }, 2000);
                })
                .catch(err => {
                    console.error(err);
                    scanningActive = true;
                });
        }

        const html5QrcodeScanner = new Html5QrcodeScanner("reader", {
            fps: 15,
            qrbox: { width: 260, height: 260 },
            rememberLastUsedCamera: true
        });
        html5QrcodeScanner.render(onScanSuccess);
    </script>
</body>
</html>