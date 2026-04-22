<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

/* ===========================
AUTO EXPIRE OLD SPECIAL MENUS
=========================== */

date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");

$expire = $conn->prepare("
UPDATE menu
SET is_active=0
WHERE is_special=1
AND special_date < ?
");

$expire->bind_param("s", $today);
$expire->execute();

/* ===========================
DELETE
=========================== */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("
    DELETE FROM menu
    WHERE id=? AND is_special=1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_manage_special_menu.php");
    exit();
}

/* ===========================
EDIT FETCH
=========================== */

$edit = null;

if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("
    SELECT *
    FROM menu
    WHERE id=? AND is_special=1
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $res = $stmt->get_result();

    if ($res->num_rows > 0) {

        $edit = $res->fetch_assoc();
    }
}

/* ===========================
ADD / UPDATE
=========================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id   = $_POST['id'] ?? "";
    $day  = $_POST['day'];
    $date = $_POST['special_date'];

    /* CHECK DUPLICATE DATE */

    $check = $conn->prepare("
    SELECT id
    FROM menu
    WHERE special_date=?
    AND is_special=1
    AND id!=?
    ");

    $check->bind_param("si", $date, $id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        $message = "❌ Special Menu already exists for this date!";
    } else {

        /* FLAGS */

        $enable_lunch =
            isset($_POST['enable_lunch']) ? 1 : 0;

        $enable_dinner =
            isset($_POST['enable_dinner']) ? 1 : 0;

        $enable_base =
            isset($_POST['enable_base']) ? 1 : 0;

        /* SAFE VALUES */

        $lunch_veg =
            $enable_lunch ? $_POST['lunch_veg'] : "";

        $lunch_nonveg =
            $enable_lunch && isset($_POST['lunch_nonveg'])
            ? trim($_POST['lunch_nonveg'])
            : null;

        $lunch_veg_price =
            $enable_lunch ? floatval($_POST['lunch_veg_price']) : 0;

        $lunch_nonveg_price =
            $enable_lunch ? floatval($_POST['lunch_nonveg_price']) : 0;

        $dinner_veg =
            $enable_dinner ? $_POST['dinner_veg'] : "";

        $dinner_nonveg =
            $enable_dinner && isset($_POST['dinner_nonveg'])
            ? trim($_POST['dinner_nonveg'])
            : null;

        $dinner_veg_price =
            $enable_dinner ? floatval($_POST['dinner_veg_price']) : 0;

        $dinner_nonveg_price =
            $enable_dinner ? floatval($_POST['dinner_nonveg_price']) : 0;

        /* UPDATE */

        if (!empty($id)) {

            $sql = "
UPDATE menu SET

day=?,
special_date=?,

lunch_veg=?,
lunch_nonveg=?,
has_lunch_nonveg=?,

dinner_veg=?,
dinner_nonveg=?,
has_dinner_nonveg=?,

has_base_option=?,

special_lunch_veg_price=?,
special_lunch_nonveg_price=?,

special_dinner_veg_price=?,
special_dinner_nonveg_price=?

WHERE id=?
";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssssissiiddddd",
                $day,
                $date,

                $lunch_veg,
                $lunch_nonveg,
                $enable_lunch,

                $dinner_veg,
                $dinner_nonveg,
                $enable_dinner,

                $enable_base,

                $lunch_veg_price,
                $lunch_nonveg_price,

                $dinner_veg_price,
                $dinner_nonveg_price,

                $id
            );

            $stmt->execute();

            $message = "✏️ Special Menu Updated";
        }

        /* INSERT */ else {

            $sql = "
INSERT INTO menu(

day,
special_date,

is_special,
is_active,

lunch_veg,
lunch_nonveg,
has_lunch_nonveg,

dinner_veg,
dinner_nonveg,
has_dinner_nonveg,

has_base_option,

special_lunch_veg_price,
special_lunch_nonveg_price,

special_dinner_veg_price,
special_dinner_nonveg_price

)

VALUES(
?,?,
1,1,

?,?,?,
?,?,?,
?,
?,?,
?,?
)
";

            $stmt = $conn->prepare($sql);

            $stmt->bind_param(
                "ssssissiidddd",

                $day,
                $date,

                $lunch_veg,
                $lunch_nonveg,
                $enable_lunch,

                $dinner_veg,
                $dinner_nonveg,
                $enable_dinner,

                $enable_base,

                $lunch_veg_price,
                $lunch_nonveg_price,

                $dinner_veg_price,
                $dinner_nonveg_price
            );

            $stmt->execute();

            $message = "✅ Special Menu Added";
        }
    }
}

/* ===========================
FETCH LIST
=========================== */

$result = $conn->query("
SELECT *
FROM menu
WHERE is_special=1
ORDER BY special_date DESC
");
?>

<!DOCTYPE html>
<html>

<head>

    <title>🎉 Special Menu Manager</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #eef2f7;
        }

        .container {
            width: 95%;
            margin: 30px auto;
        }

        /* CARD */

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .1);
            margin-bottom: 20px;
        }

        /* INPUT */

        input,
        select {
            width: 100%;
            padding: 10px;
            margin: 6px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        /* BUTTON */

        button {
            padding: 12px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
        }

        /* SECTION */

        .section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 12px;
            border-left: 5px solid #007bff;
        }

        /* TABLE */

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #007bff;
            color: white;
            padding: 12px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        /* BUTTONS */

        .edit-btn {
            background: orange;
            padding: 6px 10px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }

        .delete-btn {
            background: red;
            padding: 6px 10px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
        }

        /* DISABLED STYLE */

        input:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }
    </style>

    <script>
        function toggleLunch() {

            let c =
                document.getElementById("enable_lunch").checked;

            document.querySelectorAll(".lunch")
                .forEach(el => {

                    el.disabled = !c;

                });

        }

        function toggleDinner() {

            let c =
                document.getElementById("enable_dinner").checked;

            document.querySelectorAll(".dinner")
                .forEach(el => {

                    el.disabled = !c;

                });

        }

        window.onload = function() {

            toggleLunch();
            toggleDinner();

        }
    </script>

</head>

<body>

    <div class="container">

        <h2>🎉 Special Menu Manager</h2>

        <?php if ($message) { ?>

            <p style="color:green;font-weight:bold;">
                <?php echo $message; ?>
            </p>

        <?php } ?>

        <div class="card">

            <form method="POST">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $edit['id'] ?? ''; ?>">

                <label>Day</label>

                <select name="day" required>

                    <?php

                    $days = [
                        "Monday",
                        "Tuesday",
                        "Wednesday",
                        "Thursday",
                        "Friday",
                        "Saturday",
                        "Sunday"
                    ];

                    foreach ($days as $d) {

                        $sel =
                            (isset($edit['day'])
                                && $edit['day'] == $d)
                            ? "selected"
                            : "";

                        echo "<option $sel>$d</option>";
                    }

                    ?>

                </select>

                <label>Special Date</label>

                <input
                    type="date"
                    name="special_date"
                    required
                    value="<?php
                            echo $edit['special_date']
                                ?? '';
                            ?>">

                <div class="section">

                    <label>

                        <input
                            type="checkbox"
                            id="enable_lunch"
                            name="enable_lunch"

                            <?php
                            if (($edit['has_lunch_nonveg'] ?? 0))
                                echo "checked";
                            ?>

                            onclick="toggleLunch()">

                        Enable Lunch

                    </label>

                    <input
                        class="lunch"
                        name="lunch_veg"
                        placeholder="Lunch Veg"
                        value="<?php
                                echo $edit['lunch_veg']
                                    ?? '';
                                ?>">

                    <input
                        class="lunch"
                        name="lunch_nonveg"
                        placeholder="Lunch NonVeg"
                        value="<?php
                                echo $edit['lunch_nonveg']
                                    ?? '';
                                ?>">

                    <input
                        class="lunch"
                        type="number"
                        name="lunch_veg_price"
                        placeholder="Veg Price"
                        value="<?php
                                echo $edit['special_lunch_veg_price']
                                    ?? '';
                                ?>">

                    <input
                        class="lunch"
                        type="number"
                        name="lunch_nonveg_price"
                        placeholder="NonVeg Price"
                        value="<?php
                                echo $edit['special_lunch_nonveg_price']
                                    ?? '';
                                ?>">

                </div>

                <div class="section">

                    <label>

                        <input
                            type="checkbox"
                            id="enable_dinner"
                            name="enable_dinner"

                            <?php
                            if (($edit['has_dinner_nonveg'] ?? 0))
                                echo "checked";
                            ?>

                            onclick="toggleDinner()">

                        Enable Dinner

                    </label>

                    <input
                        class="dinner"
                        name="dinner_veg"
                        placeholder="Dinner Veg"
                        value="<?php
                                echo $edit['dinner_veg']
                                    ?? '';
                                ?>">

                    <input
                        class="dinner"
                        name="dinner_nonveg"
                        placeholder="Dinner NonVeg"
                        value="<?php
                                echo $edit['dinner_nonveg']
                                    ?? '';
                                ?>">

                    <input
                        class="dinner"
                        type="number"
                        name="dinner_veg_price"
                        placeholder="Veg Price"
                        value="<?php
                                echo $edit['special_dinner_veg_price']
                                    ?? '';
                                ?>">

                    <input
                        class="dinner"
                        type="number"
                        name="dinner_nonveg_price"
                        placeholder="NonVeg Price"
                        value="<?php
                                echo $edit['special_dinner_nonveg_price']
                                    ?? '';
                                ?>">

                    <label>

                        <input
                            type="checkbox"
                            name="enable_base"

                            <?php
                            if (($edit['has_base_option'] ?? 0))
                                echo "checked";
                            ?>>

                        Enable Rice / Roti

                    </label>

                </div>

                <button>

                    <?php
                    echo $edit
                        ? "✏️ Update Special Menu"
                        : "💾 Save Special Menu";
                    ?>

                </button>

            </form>

        </div>

        <div class="card">

            <h3>📋 Existing Special Menus</h3>

            <table>

                <tr>
                    <th>Day</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

                <?php

                while ($row = $result->fetch_assoc()) {

                    $status =
                        $row['is_active']
                        ? "🟢 Active"
                        : "🔴 Expired";

                ?>

                    <tr>

                        <td><?php echo $row['day']; ?></td>

                        <td>
                            <?php
                            echo date(
                                "d M Y",
                                strtotime($row['special_date'])
                            );
                            ?>
                        </td>

                        <td><?php echo $status; ?></td>

                        <td>

                            <a
                                class="edit-btn"
                                href="?edit=<?php echo $row['id']; ?>">

                                Edit

                            </a>

                            <a
                                class="delete-btn"
                                href="?delete=<?php echo $row['id']; ?>"
                                onclick="return confirm('Delete Special Menu?')">

                                Delete

                            </a>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

        <a href="admin_dashboard.php">

            ⬅ Back Dashboard

        </a>

    </div>

</body>

</html>