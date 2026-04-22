<?php
session_start();
include("config/db_connect.php");

/* ===========================
LOGIN PROTECTION
=========================== */

if (!isset($_SESSION['hostel_roll'])) {

    echo "<h2 style='text-align:center;'>⚠ Please login first</h2>";

    echo "<div style='text-align:center; margin-top:20px;'>
            <a href='login.html'
            style='padding:10px 20px;
            background:#007bff;
            color:white;
            text-decoration:none;
            border-radius:5px;'>
                🔐 Go to Login
            </a>
          </div>";

    exit();
}

date_default_timezone_set("Asia/Kolkata");

/* ===========================
DATE LOGIC
=========================== */

$now = time();
$deadline_today = strtotime("22:00");

$today = date("l");
$day = date("l", strtotime("+1 day"));

$tomorrow_date =
    date("Y-m-d", strtotime("+1 day"));

$hostel_roll =
    $_SESSION['hostel_roll'];

$isExpired =
    ($now >= $deadline_today);


/* ===========================
FETCH NORMAL MENU
=========================== */

$stmt = $conn->prepare("
SELECT *
FROM menu
WHERE day=? 
AND is_special=0
LIMIT 1
");

$stmt->bind_param("s", $day);
$stmt->execute();

$normal_result = $stmt->get_result();

if ($normal_result->num_rows == 0) {
    die("⚠ No normal menu found for $day");
}

$normal_menu = $normal_result->fetch_assoc();


/* ===========================
FETCH SPECIAL MENU
=========================== */

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

$special_menu = $special_result->fetch_assoc() ?? null;


$row = $normal_menu;

/* ===========================
   APPLY LUNCH ONLY IF EXISTS
=========================== */

if ($special_menu && !empty($special_menu['lunch_veg'])) {

    $row['lunch_veg'] = $special_menu['lunch_veg'];

    $row['lunch_nonveg'] = $special_menu['lunch_nonveg'];

    $row['has_lunch_nonveg'] = $special_menu['has_lunch_nonveg'];

    $row['lunch_veg_price'] =
        $special_menu['special_lunch_veg_price']
        ?? $row['lunch_veg_price'];

    $row['lunch_nonveg_price'] =
        $special_menu['special_lunch_nonveg_price']
        ?? $row['lunch_nonveg_price'];
}


/* ===========================
   APPLY DINNER ONLY IF EXISTS
=========================== */

if ($special_menu && !empty($special_menu['dinner_veg'])) {

    $row['dinner_veg'] = $special_menu['dinner_veg'];

    $row['dinner_nonveg'] = $special_menu['dinner_nonveg'];

    $row['has_dinner_nonveg'] = $special_menu['has_dinner_nonveg'];

    $row['dinner_veg_price'] =
        $special_menu['special_dinner_veg_price']
        ?? $row['dinner_veg_price'];

    $row['dinner_nonveg_price'] =
        $special_menu['special_dinner_nonveg_price']
        ?? $row['dinner_nonveg_price'];
}


/* BASE OPTION */
if ($special_menu) {
    $row['has_base_option'] = $special_menu['has_base_option'];
    $row['is_special'] = 1;
} else {
    $row['is_special'] = 0;
}

/* ===========================
PRICE VARIABLES
=========================== */

$lunch_veg_price =
    $row['lunch_veg_price'];

$lunch_nonveg_price =
    $row['lunch_nonveg_price'];

$dinner_veg_price =
    $row['dinner_veg_price'];

$dinner_nonveg_price =
    $row['dinner_nonveg_price'];


/* ===========================
FETCH PREVIOUS SELECTION
=========================== */

$stmt = $conn->prepare("

SELECT *
FROM meals
WHERE hostel_roll=?
AND date=?

");

$stmt->bind_param(
    "ss",
    $hostel_roll,
    $tomorrow_date
);

$stmt->execute();

$check =
    $stmt->get_result();


$selected = [

    'breakfast' => 0,
    'lunch' => 0,
    'lunch_type' => '',
    'dinner' => 0,
    'dinner_type' => '',
    'base' => '',
    'locked' => 0

];

if ($check->num_rows > 0) {

    $selected =
        $check->fetch_assoc();
}


/* ===========================
LOCK CHECK
=========================== */

$isLocked =
    !empty($selected['locked']);


/* ===========================
AUTO LOCK AFTER 10PM
=========================== */

if ($isExpired && !$isLocked) {

    $lockStmt = $conn->prepare("

    UPDATE meals
    SET locked=1
    WHERE hostel_roll=?
    AND date=?

    ");

    $lockStmt->bind_param(
        "ss",
        $hostel_roll,
        $tomorrow_date
    );

    $lockStmt->execute();

    $isLocked = true;
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Meal Selection</title>

    <style>
        body {

            font-family: 'Segoe UI', sans-serif;

            background:
                linear-gradient(135deg, #4facfe, #8e44ad, #ff6a00);

            background-size: 300% 300%;

            animation: gradientMove 8s ease infinite;

            margin: 0;
            padding: 0;

        }

        @keyframes gradientMove {

            0% {
                background-position: 0%
            }

            50% {
                background-position: 100%
            }

            100% {
                background-position: 0%
            }

        }

        .container {

            width: 600px;
            margin: 40px auto;

            background: white;

            padding: 25px;

            border-radius: 15px;

            box-shadow:
                0px 10px 30px rgba(0, 0, 0, 0.2);

        }

        h2,
        h3 {

            text-align: center;

            color: #333;

        }

        .card {

            background: #f9f9f9;

            padding: 15px;

            margin: 15px 0;

            border-radius: 12px;

            border-left: 5px solid #667eea;

        }

        .special {

            background:
                linear-gradient(135deg, #ff9800, #ff5722);

            color: white;

            font-weight: bold;

            text-align: center;

        }

        .timer {

            text-align: center;

            font-weight: bold;

            color: red;

        }

        .summary {

            background: #f1f1f1;

            padding: 15px;

            border-radius: 10px;

            margin-bottom: 15px;

        }

        button {

            width: 100%;

            padding: 12px;

            background:
                linear-gradient(135deg, #ff416c, #ff4b2b);

            color: white;

            border: none;

            border-radius: 30px;

            font-size: 16px;

            cursor: pointer;

        }

        .menu-btn {

            padding: 10px 15px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-weight: bold;

        }

        label {

            display: block;

            margin: 5px 0;

        }

        .price {

            color: green;

            font-weight: bold;

        }
    </style>

    <script>
        /* ===========================
   LUNCH DEFAULT VEG
=========================== */

        function toggleLunch() {

            let check =
                document.getElementById("lunch_check").checked;

            let options =
                document.getElementsByName("lunch_type");

            options.forEach(opt => {

                opt.disabled = !check;

                if (check && opt.value === "veg") {

                    opt.checked = true;

                }

            });

        }


        /* ===========================
           DINNER DEFAULT VEG + RICE
        =========================== */

        function toggleDinner() {

            let check =
                document.getElementById("dinner_check").checked;

            let options =
                document.getElementsByName("dinner_type");

            let base =
                document.getElementsByName("base");

            options.forEach(opt => {

                opt.disabled = !check;

                if (check && opt.value === "veg") {

                    opt.checked = true;

                }


            });

            base.forEach(opt => {

                opt.disabled = !check;

                if (check && opt.value === "rice") {

                    opt.checked = true;

                }

            });

        }


        /* TIMER */

        function updateTimer() {

            let now = new Date();

            let deadline = new Date();

            deadline.setHours(22, 0, 0, 0);

            let diff = deadline - now;

            if (diff <= 0) {

                document.getElementById("timer")
                    .innerHTML = "⛔ Time Over!";

                let btn =
                    document.querySelector("button[type='submit']");

                if (btn) btn.disabled = true;

                return;

            }

            let hrs = Math.floor(diff / (1000 * 60 * 60));

            let mins = Math.floor(
                (diff % (1000 * 60 * 60)) / (1000 * 60)
            );

            document.getElementById("timer")
                .innerHTML =
                "⏳ Time left: " +
                hrs + "h " + mins + "m";

        }

        setInterval(updateTimer, 1000);

        window.onload = function() {

            toggleLunch();
            toggleDinner();
            updateTimer();

        }
    </script>

</head>

<body>

    <div class="container">

        <h2>

            🍽️ Welcome,
            <?php echo htmlspecialchars($_SESSION['name']); ?>
            👋

        </h2>

        <div style="text-align:center;">

            <a href="student_dashboard.php"
                class="menu-btn">

                ⬅ Back Dashboard

            </a>

        </div>

        <h3>

            📅 Today:
            <?php echo $today; ?>

            <br>

            🍽 Tomorrow Meal:
            <?php echo $day; ?>

        </h3>

        <p id="timer"
            class="timer"></p>
        <?php
        if ($isExpired || $isLocked) {
        ?>

            <div class="card special">

                🔒 Meal Selection Locked

                <br>

                You can edit meals only before
                <b>10:00 PM</b>

            </div>

        <?php } ?>
        <!--special meal banner -->

        <?php
        if ($row['is_special'] == 1) {
        ?>

            <div class="card special">

                🎉 SPECIAL MENU AVAILABLE

                <?php
                if ($special_menu) {
                    echo date(
                        "d M Y",
                        strtotime($tomorrow_date)
                    );
                }
                ?>

                <br>

                💰 Special Pricing Applied

            </div>

        <?php } ?>

        <?php
        if ($check->num_rows > 0) {

            echo "<div class='summary'>

<b>✅ Previous Selection Found</b><br>

Breakfast: " .
                (!empty($selected['breakfast'])
                    ? 'Yes' : 'No') . "<br>

Lunch: " .
                (!empty($selected['lunch'])
                    ? ucfirst($selected['lunch_type'])
                    : 'No') . "<br>

Dinner: " .
                (!empty($selected['dinner'])
                    ? ucfirst($selected['dinner_type'])
                    : 'No') . "<br>

Base: " .
                (!empty($selected['base'])
                    ? ucfirst($selected['base'])
                    : '-') . "

</div>";
        }
        ?>


        <!-- BREAKFAST DISPLAY -->

        <div class="card">

            <b>🍞 Breakfast:</b><br>

            <?php echo $row['breakfast']; ?>

        </div>


        <!-- LUNCH DISPLAY -->

        <div class="card">

            <b>🍛 Lunch:</b><br>

            <?php
            if ($special_menu) {
            ?>

                <span style="color:red;font-weight:bold;">
                    🎉 SPECIAL LUNCH
                </span><br>

            <?php } ?>

            Veg:
            <?php echo $row['lunch_veg']; ?>

            <span class="price">

                (₹ <?php echo $lunch_veg_price; ?>)

                <?php
                if ($special_menu && !empty($special_menu['lunch_veg'])) {
                    echo "<span style='color:red;'> ★ Special</span>";
                }
                ?>

            </span>

            <br>

            <?php
            if (!empty($row['has_lunch_nonveg'])) {

                echo "Non-Veg: "
                    . $row['lunch_nonveg']

                    . " <span class='price'>
(₹ " . $lunch_nonveg_price . ")";

                if ($special_menu && !empty($special_menu['lunch_veg'])) {

                    echo "<span style='color:red;'> ★ Special</span>";
                }

                echo "</span>";
            }
            ?>

        </div>


        <!-- DINNER DISPLAY -->

        <div class="card">

            <b>🌙 Dinner:</b><br>

            <?php
            if ($special_menu) {
            ?>

                <span style="color:red;font-weight:bold;">
                    🎉 SPECIAL DINNER
                </span><br>

            <?php } ?>

            Veg:
            <?php echo $row['dinner_veg']; ?>

            <span class="price">

                (₹ <?php echo $dinner_veg_price; ?>)

                <?php
                if ($special_menu && !empty($special_menu['dinner_veg'])) {

                    echo "<span style='color:red;'> ★ Special</span>";
                }
                ?>

            </span>

            <br>

            <?php

            if (!empty($row['has_dinner_nonveg'])) {

                echo "Non-Veg: "
                    . $row['dinner_nonveg']

                    . " <span class='price'>
(₹ " . $dinner_nonveg_price . ")";

                if ($special_menu && !empty($special_menu['dinner_veg'])) {

                    echo "<span style='color:red;'> ★ Special</span>";
                }

                echo "</span>";
            }

            if (!empty($row['has_base_option'])) {

                echo "<br>🍞 Roti / 🍚 Rice Available";
            }

            ?>

        </div>


        <?php

        $estimate = 0;

        if (
            !empty($selected['breakfast'])
            && isset($row['breakfast_price'])
        ) {

            $estimate += $row['breakfast_price'];
        }

        if (!empty($selected['lunch'])) {

            if ($selected['lunch_type'] == "nonveg")
                $estimate += $lunch_nonveg_price;
            else
                $estimate += $lunch_veg_price;
        }

        if (!empty($selected['dinner'])) {

            if ($selected['dinner_type'] == "nonveg")
                $estimate += $dinner_nonveg_price;
            else
                $estimate += $dinner_veg_price;
        }

        ?>

        <div class="card">

            <b>💰 Estimated Cost:</b>

            ₹ <?php echo $estimate; ?>

        </div>
        <hr>

        <form method="post"
            action="save_meal.php"
            <?php
            if ($isExpired || $isLocked)
                echo "style='pointer-events:none;opacity:0.6;'";
            ?>>

            <input type="hidden"
                name="day"
                value="<?php echo $day; ?>">

            <input type="hidden"
                name="is_special"
                value="<?php echo $special_menu ? 1 : 0; ?>">


            <!-- BREAKFAST -->

            <div class="card">

                <h4>🍞 Breakfast</h4>

                <label>

                    <input type="checkbox"
                        name="breakfast"

                        <?php
                        if (!empty($selected['breakfast']))
                            echo "checked";
                        ?>>

                    Take Breakfast

                </label>

            </div>


            <!-- LUNCH -->

            <div class="card">

                <h4>🍛 Lunch</h4>

                <label>

                    <input type="checkbox"
                        name="take_lunch"
                        id="lunch_check"

                        <?php
                        if (!empty($selected['lunch']))
                            echo "checked";
                        ?>

                        onclick="toggleLunch()">

                    Take Lunch

                </label>


                <label>

                    <input type="radio"
                        name="lunch_type"
                        value="veg"

                        <?php
                        if (
                            $selected['lunch_type'] == "veg"
                            || empty($selected['lunch_type'])
                        )
                            echo "checked";
                        ?>>

                    Veg

                </label>


                <?php
                if ($row['has_lunch_nonveg']) {
                ?>

                    <label>

                        <input type="radio"
                            name="lunch_type"
                            value="nonveg"

                            <?php
                            if ($selected['lunch_type'] == "nonveg")
                                echo "checked";
                            ?>>

                        Non-Veg

                    </label>

                <?php } ?>

            </div>


            <!-- DINNER -->

            <div class="card">

                <h4>🌙 Dinner</h4>

                <label>

                    <input type="checkbox"
                        name="take_dinner"
                        id="dinner_check"

                        <?php
                        if (!empty($selected['dinner']))
                            echo "checked";
                        ?>

                        onclick="toggleDinner()">

                    Take Dinner

                </label>


                <label>

                    <input type="radio"
                        name="dinner_type"
                        value="veg"

                        <?php
                        if (
                            $selected['dinner_type'] == "veg"
                            || empty($selected['dinner_type'])
                        )
                            echo "checked";
                        ?>>

                    Veg

                </label>


                <?php
                if ($row['has_dinner_nonveg']) {
                ?>

                    <label>

                        <input type="radio"
                            name="dinner_type"
                            value="nonveg"

                            <?php
                            if ($selected['dinner_type'] == "nonveg")
                                echo "checked";
                            ?>>

                        Non-Veg

                    </label>

                <?php } ?>


                <?php
                if (!empty($row['has_base_option'])) {
                ?>

                    <br>

                    <b>Choose Base:</b>

                    <label>

                        <input type="radio"
                            name="base"
                            value="rice"

                            <?php
                            if (
                                $selected['base'] == "rice"
                                || empty($selected['base'])
                            )
                                echo "checked";
                            ?>>

                        Rice

                    </label>

                    <label>

                        <input type="radio"
                            name="base"
                            value="roti"

                            <?php
                            if ($selected['base'] == "roti")
                                echo "checked";
                            ?>>

                        Roti

                    </label>

                <?php } ?>

            </div>

            <button type="submit"
                <?php
                if ($isExpired || $isLocked)
                    echo "disabled";
                ?>>

                ✅ Save Meal

            </button>

        </form>

    </div>

</body>

</html>