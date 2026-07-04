<?php
session_start();
// Look into the root config folder
include("../config/db_connect.php");

/* ==========================================================================
   PDO VARIABLE AUTOMATIC DETECTION LATCH
   ========================================================================== */
if (!isset($conn)) {
    $conn = $pdo ?? $db ?? $con ?? null;
    if (!$conn) {
        die("<div style='padding: 24px; text-align:center; font-family:sans-serif; color: #EF4444;'>
            ⚠ Database Connection Error.
        </div>");
    }
}

if (!isset($_SESSION['hostel_roll'])) {
    header("Location: ../auth/login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$hostel_roll = $_SESSION['hostel_roll'];
$today = date("Y-m-d");

/* ==========================================================================
   FETCH TODAY'S MEAL BOOKING STATUS
   ========================================================================== */
$breakfast = 0;
$lunch = 0;
$dinner = 0;
$status_text = "No Meals Booked Today";
$badge_style = "background: #F1F5F9; color: #64748B;";

try {
    $stmt = $conn->prepare("SELECT breakfast, lunch, dinner FROM meals WHERE hostel_roll = ? AND date = ?");
    $stmt->execute([$hostel_roll, $today]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $breakfast = (int)$booking['breakfast'];
        $lunch = (int)$booking['lunch'];
        $dinner = (int)$booking['dinner'];

        // Determine clear badge text based on configurations
        $booked_meals = [];
        if ($breakfast) $booked_meals[] = "Breakfast";
        if ($lunch) $booked_meals[] = "Lunch";
        if ($dinner) $booked_meals[] = "Dinner";

        if (count($booked_meals) === 3) {
            $status_text = "All Meals Booked";
            $badge_style = "background: #ECFDF5; color: #065F46;";
        } elseif (count($booked_meals) > 0) {
            $status_text = implode(" & ", $booked_meals);
            $badge_style = "background: #EFF6FF; color: #1E40AF;";
        }
    }
} catch (PDOException $e) {
    // Fallback quietly if table reading fails
}

// Secure Token Generation (Uses Roll + System Salt)
$secret_salt = "HostelMessSecureSalt2026";
$secure_checksum = hash_hmac("sha256", $hostel_roll, $secret_salt);
$final_qr_payload = $hostel_roll . "|" . substr($secure_checksum, 0, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Meal Token</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: #0F172A; color: #FFFFFF; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .pass-card { background: #FFFFFF; color: #0F172A; width: 100%; max-width: 380px; border-radius: 24px; padding: 36px 32px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4); }
        .badge { display: inline-block; padding: 6px 14px; font-weight: 700; font-size: 12px; border-radius: 999px; text-transform: uppercase; margin-bottom: 20px; letter-spacing: 0.05em; }
        .qr-wrapper { background: #F8FAFC; padding: 24px; border-radius: 20px; display: inline-block; margin: 15px 0; border: 2px dashed #CBD5E1; }
        h2 { font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.01em; }
        p.desc { font-size: 14px; color: #64748B; margin-top: 6px; line-height: 1.5; }
        .meta-info { margin-top: 24px; padding-top: 20px; border-top: 1px dashed #E2E8F0; text-align: left; font-size: 14px; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 12px; color: #475569; }
        .meta-row:last-child { margin-bottom: 0; }
        .meta-row span { color: #64748B; font-weight: 500; }
        .meta-row strong { color: #0F172A; font-weight: 700; }
        .meal-indicator { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 6px; }
        .btn-back { display: block; margin-top: 28px; padding: 14px; background: #F1F5F9; color: #475569; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; transition: all 0.15s ease; }
        .btn-back:hover { background: #E2E8F0; color: #0F172A; }
    </style>
</head>
<body>
    <div class="pass-card">
        <span class="badge" style="<?php echo $badge_style; ?>"><?php echo $status_text; ?></span>
        <h2>Today's Meal Token</h2>
        <p class="desc">Scan this code at the food counter during the active food hours to verify your serving.</p>

        <div class="qr-wrapper">
            <?php if ($breakfast || $lunch || $dinner) { ?>
                <div id="qrcode"></div>
            <?php } else { ?>
                <div style="width:220px; height:220px; display:flex; align-items:center; justify-content:center; color:#EF4444; font-weight:700; font-size:15px;">
                    ❌ No meals selected for today.
                </div>
            <?php } ?>
        </div>

        <div class="meta-info">
            <div class="meta-row"><span>Student Roll No:</span> <strong><?php echo htmlspecialchars($hostel_roll); ?></strong></div>
            <div class="meta-row">
                <span>Active Track:</span> 
                <div>
                    <span style="font-size:12px; font-weight:600; color: <?php echo $breakfast ? '#10B981':'#94A3B8'; ?>;">B</span> | 
                    <span style="font-size:12px; font-weight:600; color: <?php echo $lunch ? '#10B981':'#94A3B8'; ?>;">L</span> | 
                    <span style="font-size:12px; font-weight:600; color: <?php echo $dinner ? '#10B981':'#94A3B8'; ?>;">D</span>
                </div>
            </div>
        </div>
        <a href="dashboard.php" class="btn-back">Return to Student Desk</a>
    </div>

    <?php if ($breakfast || $lunch || $dinner) { ?>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $final_qr_payload; ?>",
            width: 220,
            height: 220,
            colorDark: "#0F172A",
            colorLight: "#F8FAFC",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
    <?php } ?>
</body>
</html>