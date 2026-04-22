<?php
session_start();
include("config/db_connect.php");

/* ============================
LOGIN PROTECTION
============================ */

if (!isset($_SESSION['hostel_roll'])) {
    header("Location: login.html");
    exit();
}

$hostel_roll = $_SESSION['hostel_roll'];

/* ============================
FETCH STUDENT
============================ */

$stmt = $conn->prepare("
SELECT *
FROM student
WHERE hostel_roll=?
");

$stmt->bind_param("s", $hostel_roll);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {

    session_destroy();

    echo "<script>
    alert('User not found');
    window.location='login.html';
    </script>";

    exit();
}

$user = $result->fetch_assoc();

/* ============================
BLOCK CHECK
============================ */

if (($user['status'] ?? 'active') == 'blocked') {

    session_destroy();

    $reason =
        htmlspecialchars(
            $user['block_reason']
                ?? "Contact Hostel Office"
        );

    echo "<script>
    alert('🚫 Your account is BLOCKED. Reason: $reason');
    window.location='login.html';
    </script>";

    exit();
}

/* ============================
DATE SETTINGS
============================ */

date_default_timezone_set("Asia/Kolkata");

$today = date("l");

$tomorrow =
    date("l", strtotime("+1 day"));

$tomorrow_date =
    date("Y-m-d", strtotime("+1 day"));

/* ============================
📢 ANNOUNCEMENTS (FIXED)
============================ */

$today_date =
    date("Y-m-d");

$stmt = $conn->prepare("
SELECT *
FROM announcements
WHERE announce_date >= ?
ORDER BY announce_date ASC
LIMIT 5
");

$stmt->bind_param(
    "s",
    $today_date
);

$stmt->execute();

$announcements =
    $stmt->get_result();

/* ============================
🎉 SPECIAL MEAL CHECK
============================ */

$stmt = $conn->prepare("
SELECT *
FROM menu
WHERE special_date=?
AND is_special=1
AND is_active=1
LIMIT 1
");

$stmt->bind_param("s", $tomorrow_date);
$stmt->execute();

$special_result = $stmt->get_result();

$special_menu = null;

if ($special_result->num_rows > 0) {
    $special_menu = $special_result->fetch_assoc();
}


/* ============================
PROFILE PHOTO
============================ */

$photo = "uploads/default.png";

if (
    !empty($user['photo']) &&
    file_exists($user['photo'])
) {
    $photo = $user['photo'];
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Student Dashboard</title>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .container {
            padding: 30px;
            text-align: center;
        }

        .profile img {

            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid white;

        }

        .cards {

            display: grid;

            grid-template-columns:
                repeat(auto-fit, minmax(230px, 1fr));

            gap: 20px;

            margin-top: 30px;

        }

        .card {

            background: white;

            color: black;

            padding: 25px;

            border-radius: 15px;

            transition: .3s;

        }

        .card:hover {
            transform: translateY(-6px);
        }

        .btn {

            padding: 10px 15px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            display: inline-block;

            margin-top: 10px;

        }

        .alert {

            background: orange;

            color: black;

            padding: 10px;

            border-radius: 8px;

            margin-top: 15px;

        }

        /* SPECIAL BOX */

        .special-box {

            background: linear-gradient(135deg, #ff9800, #ff5722);

            color: white;

            padding: 15px;

            border-radius: 12px;

            margin-top: 20px;

        }

        /* MENU PREVIEW */

        .menu-preview {

            background: white;

            color: black;

            padding: 15px;

            border-radius: 12px;

            margin-top: 20px;

        }

        /* ANNOUNCEMENTS */

        .ann-box {

            background: white;

            color: black;

            padding: 15px;

            border-radius: 12px;

            margin-top: 20px;

            text-align: left;

        }

        .ann-item {

            border-bottom: 1px solid #ddd;

            padding: 10px;

        }
    </style>

    <script>
        function updateClock() {

            let now = new Date();

            document.getElementById("clock")
                .innerHTML = "🕒 " + now.toLocaleTimeString();

        }

        setInterval(updateClock, 1000);

        window.onload = updateClock;
    </script>

</head>

<body>

    <div class="container">

        <!-- PROFILE -->

        <div class="profile">

            <img src="<?php echo htmlspecialchars($photo); ?>">

            <h2>

                👋 <?php echo htmlspecialchars($user['name']); ?>

            </h2>

            <div>

                Roll:
                <?php echo htmlspecialchars($user['hostel_roll']); ?>

            </div>

        </div>

        <!-- CLOCK -->

        <div id="clock"></div>

        <div>

            📅 Today:
            <?php echo $today; ?>

            |

            Tomorrow:
            <?php echo $tomorrow; ?>

        </div>

        <div class="alert">

            ⏰ Select tomorrow's meal before 10 PM!

        </div>

        <!-- 🎉 SPECIAL MEAL ALERT -->

        <?php if ($special_menu) { ?>

            <div class="special-box">

                🎉 <b>Special Meal Tomorrow!</b><br>

               <?php if (!empty($special_menu['lunch_veg']) || !empty($special_menu['lunch_nonveg'])) { ?>
    <div>
        <b>Lunch:</b><br>

        <?php if (!empty($special_menu['lunch_veg'])) { ?>
            <?php echo htmlspecialchars($special_menu['lunch_veg']); ?>
        <?php } ?>

        <?php if (!empty($special_menu['lunch_nonveg'])) { ?>
            <br>Non-Veg: <?php echo htmlspecialchars($special_menu['lunch_nonveg']); ?>
        <?php } ?>
    </div>

    <br>
<?php } ?>


<?php if (!empty($special_menu['dinner_veg']) || !empty($special_menu['dinner_nonveg'])) { ?>
    <div>
        <b>Dinner:</b><br>

        <?php if (!empty($special_menu['dinner_veg'])) { ?>
            <?php echo htmlspecialchars($special_menu['dinner_veg']); ?>
        <?php } ?>

        <?php if (!empty($special_menu['dinner_nonveg'])) { ?>
            <br>Non-Veg: <?php echo htmlspecialchars($special_menu['dinner_nonveg']); ?>
        <?php } ?>
    </div>
<?php } ?>

            </div>

        <?php } ?>

        <!-- 📢 ANNOUNCEMENTS -->

        <?php
        if ($announcements->num_rows > 0) {
        ?>

            <div class="ann-box">

                <h3>📢 Announcements</h3>

                <?php
                while ($a = $announcements->fetch_assoc()) {
                ?>

                    <div class="ann-item">

                        <b>
                            <?php
                            echo htmlspecialchars($a['title']);
                            ?>
                        </b>

                        <br>

                        <?php
                        echo htmlspecialchars($a['message']);
                        ?>

                        <br>

                        <small>

                            📅
                            <?php
                            echo htmlspecialchars(
                                $a['announce_date']
                            );
                            ?>

                        </small>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

        <!-- CARDS -->

        <div class="cards">

            <div class="card">

                <h3>🍽 Meal Selection</h3>

                <a href="meal.php" class="btn">

                    Go

                </a>

            </div>

            <div class="card">

                <h3>📊 My Bill</h3>

                <a href="bill.php" class="btn">

                    View

                </a>

            </div>

            <div class="card">

                <h3>📈 Graph</h3>

                <a href="graph.php" class="btn">

                    View

                </a>

            </div>

            <div class="card">

                <h3>📅 Weekly Menu</h3>

                <a href="weekly_menu.php" class="btn">

                    View

                </a>

            </div>

        </div>

        <br>

        <a href="student_logout.php" class="btn">

            🚪 Logout

        </a>

    </div>

</body>

</html>