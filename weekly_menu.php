<?php
session_start();
include("config/db_connect.php");

/* ============================
   LOGIN CHECK
============================ */
if (!isset($_SESSION['hostel_roll'])) {
    header("Location: login.html");
    exit();
}

/* ============================
   TIME VARIABLES
============================ */
date_default_timezone_set("Asia/Kolkata");

$today = date("l");
$tomorrow = date("l", strtotime("+1 day"));

/* ============================
   FETCH WEEKLY MENU
============================ */

$sql = "
SELECT *
FROM menu
WHERE is_special = 0
ORDER BY FIELD(
    day,
    'Monday','Tuesday','Wednesday',
    'Thursday','Friday','Saturday','Sunday'
)";

$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("<h2 style='text-align:center;color:red;'>
        ❌ No Weekly Menu Found
    </h2>");
}

/* ============================
   FETCH ACTIVE SPECIAL MENU
============================ */

$today_date = date("Y-m-d");

$special_sql = "
SELECT *
FROM menu
WHERE is_special=1
AND is_active=1
AND special_date >= CURDATE()
ORDER BY special_date ASC
";

$special_result = $conn->query($special_sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Weekly Menu</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: linear-gradient(135deg, #74ebd5, #ACB6E5);
            margin: 0;
        }

        .container {
            width: 90%;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
        }

        /* SEARCH */

        #searchBox {
            width: 250px;
            padding: 8px;
            margin: 10px auto;
            display: block;
        }

        /* TABLE */

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background: #007bff;
            color: white;
            padding: 10px;
        }

        td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }

        /* DAY COLORS */

        .today {
            background: #d4edda;
            font-weight: bold;
        }

        .tomorrow {
            background: #d1ecf1;
            font-weight: bold;
        }

        /* TEXT COLORS */

        .veg {
            color: green;
            font-weight: bold;
        }

        .nonveg {
            color: red;
            font-weight: bold;
        }

        .note {
            font-size: 12px;
            color: gray;
        }

        /* BUTTON */

        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn:hover {
            background: #0056b3;
        }

        /* SPECIAL SECTION */

        .special-box {

            margin-top: 40px;

            background: linear-gradient(135deg,
                    #ff9800,
                    #ff5722);

            color: white;

            padding: 20px;

            border-radius: 15px;

        }

        .special-table th {

            background: #222;

        }

        .special-title {

            text-align: center;

            font-size: 22px;

            font-weight: bold;

        }
    </style>

    <script>
        function searchDay() {

            let input =
                document.getElementById("searchBox")
                .value.toLowerCase();

            let rows =
                document.querySelectorAll("tbody tr");

            rows.forEach(row => {

                let day =
                    row.cells[0].innerText
                    .toLowerCase();

                row.style.display =
                    day.includes(input) ?
                    "" :
                    "none";

            });

        }

        function printMenu() {
            window.print();
        }
    </script>

</head>

<body>

    <div class="container">

        <h2>📅 Weekly Menu</h2>

        <input
            type="text"
            id="searchBox"
            onkeyup="searchDay()"
            placeholder="Search day...">

        <table>

            <thead>

                <tr>

                    <th>Day</th>
                    <th>Breakfast</th>
                    <th>Lunch</th>
                    <th>Dinner</th>

                </tr>

            </thead>

            <tbody>

                <?php
                while ($row = $result->fetch_assoc()) {

                    $day_name =
                        htmlspecialchars($row['day']);

                    $class = "";

                    if ($day_name == $today)
                        $class = "today";

                    elseif ($day_name == $tomorrow)
                        $class = "tomorrow";
                ?>

                    <tr class="<?php echo $class; ?>">

                        <td>

                            <b><?php echo $day_name; ?></b>

                            <?php

                            if ($day_name == $today)
                                echo " 🟢 Today";

                            if ($day_name == $tomorrow)
                                echo " 🔵 Tomorrow";

                            ?>

                        </td>

                        <td>

                            <?php echo htmlspecialchars($row['breakfast']); ?>

                            <br>

                            <span class="note">

                                ₹<?php echo $row['breakfast_price']; ?>

                            </span>

                        </td>

                        <td>

                            <span class="veg">

                                🥬 Veg:

                                <?php echo htmlspecialchars($row['lunch_veg']); ?>

                                <br>

                                ₹<?php echo $row['lunch_veg_price']; ?>

                            </span>

                            <?php
                            if (!empty($row['has_lunch_nonveg'])) {
                            ?>

                                <br>

                                <span class="nonveg">

                                    🍗 Non-Veg:

                                    <?php echo htmlspecialchars($row['lunch_nonveg']); ?>

                                    <br>

                                    ₹<?php echo $row['lunch_nonveg_price']; ?>

                                </span>

                            <?php } ?>

                        </td>

                        <td>

                            <span class="veg">

                                🥬 Veg:

                                <?php echo htmlspecialchars($row['dinner_veg']); ?>

                                <br>

                                ₹<?php echo $row['dinner_veg_price']; ?>

                            </span>

                            <?php
                            if (!empty($row['has_dinner_nonveg'])) {
                            ?>

                                <br>

                                <span class="nonveg">

                                    🍗 Non-Veg:

                                    <?php echo htmlspecialchars($row['dinner_nonveg']); ?>

                                    <br>

                                    ₹<?php echo $row['dinner_nonveg_price']; ?>

                                </span>

                            <?php } ?>

                            <?php
                            if (!empty($row['has_base_option'])) {
                            ?>

                                <br>

                                <span class="note">

                                    🍞 Roti / 🍚 Rice Available

                                </span>

                            <?php } ?>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

        <!-- ============================
     SPECIAL MENU DISPLAY
============================ -->

        <?php
        if ($special_result->num_rows > 0) {
        ?>

            <div class="special-box">

                <div class="special-title">

                    🎉 Upcoming Special Meals

                </div>

                <table class="special-table">

                    <tr>

                        <th>Date</th>
                        <th>Lunch</th>
                        <th>Dinner</th>

                    </tr>

                    <?php
                    while ($sp = $special_result->fetch_assoc()) {
                    ?>

                        <tr>

                            <td>

                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($sp['special_date'])
                                );
                                ?>

                            </td>

                            <td>

                                <?php
                                echo $sp['lunch_veg'];

                                if ($sp['has_lunch_nonveg'])
                                    echo "<br>" . $sp['lunch_nonveg'];
                                ?>

                            </td>

                            <td>

                                <?php
                                echo $sp['dinner_veg'];

                                if ($sp['has_dinner_nonveg'])
                                    echo "<br>" . $sp['dinner_nonveg'];
                                ?>

                            </td>

                        </tr>

                    <?php } ?>

                </table>

            </div>

        <?php } ?>

        <div style="text-align:center;">

            <a href="meal.php"
                class="btn">

                ⬅ Back

            </a>

            <button
                onclick="printMenu()"
                class="btn"
                style="background:#28a745;">

                🖨 Print Menu

            </button>

        </div>

    </div>

</body>

</html>