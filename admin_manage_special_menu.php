<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");

$message = "";

/* ===========================
AUTO EXPIRE OLD SPECIAL MENUS
=========================== */

$today = date("Y-m-d");

$stmt = $conn->prepare("
UPDATE menu
SET is_active=0
WHERE is_special=1
AND special_date < ?
");
$stmt->bind_param("s", $today);
$stmt->execute();

/* ===========================
DELETE (SAFE)
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
EDIT FETCH (SAFE)
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

    /* DUPLICATE CHECK */

    $check = $conn->prepare("
    SELECT id FROM menu
    WHERE special_date=? AND is_special=1 AND id!=?
    ");
    $check->bind_param("si", $date, $id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        $message = "❌ Special menu already exists for this date!";
    } else {

        /* FLAGS */

        $has_lunch  = isset($_POST['enable_lunch']) ? 1 : 0;
        $has_dinner = isset($_POST['enable_dinner']) ? 1 : 0;

        /* ✅ FIXED NONVEG LOGIC */
        $has_lunch_nonveg =
            ($has_lunch && !empty(trim($_POST['lunch_nonveg']))) ? 1 : 0;

        $has_dinner_nonveg =
            ($has_dinner && !empty(trim($_POST['dinner_nonveg']))) ? 1 : 0;

        $has_base = isset($_POST['enable_base']) ? 1 : 0;

        /* SAFE VALUES */

        $lunch_veg  = $has_lunch ? $_POST['lunch_veg'] : "";
        $lunch_nonveg = $has_lunch ? $_POST['lunch_nonveg'] : "";

        $dinner_veg  = $has_dinner ? $_POST['dinner_veg'] : "";
        $dinner_nonveg = $has_dinner ? $_POST['dinner_nonveg'] : "";

        $lunch_veg_price  = $has_lunch ? floatval($_POST['lunch_veg_price']) : 0;
        $lunch_nonveg_price = $has_lunch ? floatval($_POST['lunch_nonveg_price']) : 0;

        $dinner_veg_price  = $has_dinner ? floatval($_POST['dinner_veg_price']) : 0;
        $dinner_nonveg_price = $has_dinner ? floatval($_POST['dinner_nonveg_price']) : 0;

        /* ================= UPDATE ================= */

        if (!empty($id)) {

            $stmt = $conn->prepare("
            UPDATE menu SET
                day=?,
                special_date=?,

                has_lunch=?,
                has_lunch_nonveg=?,
                lunch_veg=?,
                lunch_nonveg=?,

                has_dinner=?,
                has_dinner_nonveg=?,
                dinner_veg=?,
                dinner_nonveg=?,

                has_base_option=?,

                special_lunch_veg_price=?,
                special_lunch_nonveg_price=?,
                special_dinner_veg_price=?,
                special_dinner_nonveg_price=?

            WHERE id=?
            ");

            /* ✅ FIXED TYPES */
            $stmt->bind_param(
                "ssiississsiddddi",
                $day,
                $date,

                $has_lunch,
                $has_lunch_nonveg,
                $lunch_veg,
                $lunch_nonveg,

                $has_dinner,
                $has_dinner_nonveg,
                $dinner_veg,
                $dinner_nonveg,

                $has_base,

                $lunch_veg_price,
                $lunch_nonveg_price,
                $dinner_veg_price,
                $dinner_nonveg_price,

                $id
            );

            $stmt->execute();

            $message = "✏️ Updated successfully";
        }

        /* ================= INSERT ================= */ else {

            $stmt = $conn->prepare("
            INSERT INTO menu(

                day,
                special_date,
                is_special,
                is_active,

                has_lunch,
                has_lunch_nonveg,
                lunch_veg,
                lunch_nonveg,

                has_dinner,
                has_dinner_nonveg,
                dinner_veg,
                dinner_nonveg,

                has_base_option,

                special_lunch_veg_price,
                special_lunch_nonveg_price,
                special_dinner_veg_price,
                special_dinner_nonveg_price

            ) VALUES (
                ?,?,1,1,
                ?,?,?,?,
                ?,?,?,?,
                ?,
                ?,?,?,?
            )
            ");

            /* ✅ FIXED TYPES */
            $stmt->bind_param(
                "ssiissiissidddd",
                $day,
                $date,

                $has_lunch,
                $has_lunch_nonveg,
                $lunch_veg,
                $lunch_nonveg,

                $has_dinner,
                $has_dinner_nonveg,
                $dinner_veg,
                $dinner_nonveg,

                $has_base,

                $lunch_veg_price,
                $lunch_nonveg_price,
                $dinner_veg_price,
                $dinner_nonveg_price
            );

            $stmt->execute();

            $message = "✅ Special menu added";
        }
    }
}

/* ===========================
FETCH LIST
=========================== */

$result = $conn->query("
SELECT * FROM menu
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
            margin: 0;
        }

        .container {
            width: 95%;
            margin: 30px auto;
        }

        h2 {
            text-align: center;
        }

        /* CARD */

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .1);
            margin-bottom: 20px;
        }

        /* FORM GRID */

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        label {
            font-weight: 600;
            margin-top: 8px;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
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
            margin-top: 10px;
        }

        button:hover {
            background: #0056b3;
        }

        /* SECTION */

        .section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border-left: 5px solid #007bff;
        }

        .section h3 {
            margin-top: 0;
        }

        /* TABLE */

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #007bff;
            color: white;
            padding: 12px;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        tr:hover {
            background: #f1f1f1;
        }

        /* STATUS */

        .active {
            color: green;
            font-weight: bold;
        }

        .expired {
            color: red;
            font-weight: bold;
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

        /* DISABLED */

        input:disabled {
            background: #e9ecef;
        }

        /* BACK */

        .back {
            display: block;
            width: 200px;
            margin: 20px auto;
            text-align: center;
            padding: 10px;
            background: #333;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>

    <script>
        function toggleLunch() {
            let c = document.getElementById("enable_lunch").checked;
            document.querySelectorAll(".lunch").forEach(el => el.disabled = !c);
        }

        function toggleDinner() {
            let c = document.getElementById("enable_dinner").checked;
            document.querySelectorAll(".dinner").forEach(el => el.disabled = !c);
        }

        window.onload = function() {
            toggleLunch();
            toggleDinner();
        };
    </script>

</head>

<body>

    <div class="container">

        <h2>🎉 Special Menu Manager</h2>

        <?php if ($message) { ?>
            <p style="color:green;font-weight:bold;text-align:center;">
                <?php echo $message; ?>
            </p>
        <?php } ?>

        <!-- ================= FORM ================= -->

        <div class="card">

            <form method="POST">

                <input type="hidden" name="id" value="<?php echo $edit['id'] ?? ''; ?>">

                <div class="grid">

                    <div>
                        <label>Day</label>
                        <select name="day" required>
                            <?php
                            $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                            foreach ($days as $d) {
                                $sel = (isset($edit['day']) && $edit['day'] == $d) ? "selected" : "";
                                echo "<option $sel>$d</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label>Special Date</label>
                        <input type="date" name="special_date" required
                            value="<?php echo $edit['special_date'] ?? ''; ?>">
                    </div>

                </div>

                <!-- LUNCH -->

                <div class="section">

                    <h3>🍽 Lunch Settings</h3>

                    <label>
                        <input type="checkbox" id="enable_lunch" name="enable_lunch"
                            <?php if (($edit['has_lunch'] ?? 0)) echo "checked"; ?>
                            onclick="toggleLunch()">
                        Enable Lunch Special
                    </label>

                    <div class="grid">
                        <input class="lunch" name="lunch_veg" placeholder="Veg Item"
                            value="<?php echo $edit['lunch_veg'] ?? ''; ?>">

                        <input class="lunch" name="lunch_nonveg" placeholder="NonVeg Item"
                            value="<?php echo $edit['lunch_nonveg'] ?? ''; ?>">

                        <input class="lunch" type="number" name="lunch_veg_price" placeholder="Veg Price"
                            value="<?php echo $edit['special_lunch_veg_price'] ?? ''; ?>">

                        <input class="lunch" type="number" name="lunch_nonveg_price" placeholder="NonVeg Price"
                            value="<?php echo $edit['special_lunch_nonveg_price'] ?? ''; ?>">
                    </div>

                </div>

                <!-- DINNER -->

                <div class="section">

                    <h3>🌙 Dinner Settings</h3>

                    <label>
                        <input type="checkbox" id="enable_dinner" name="enable_dinner"
                            <?php if (($edit['has_dinner'] ?? 0)) echo "checked"; ?>
                            onclick="toggleDinner()">
                        Enable Dinner Special
                    </label>

                    <div class="grid">
                        <input class="dinner" name="dinner_veg" placeholder="Veg Item"
                            value="<?php echo $edit['dinner_veg'] ?? ''; ?>">

                        <input class="dinner" name="dinner_nonveg" placeholder="NonVeg Item"
                            value="<?php echo $edit['dinner_nonveg'] ?? ''; ?>">

                        <input class="dinner" type="number" name="dinner_veg_price" placeholder="Veg Price"
                            value="<?php echo $edit['special_dinner_veg_price'] ?? ''; ?>">

                        <input class="dinner" type="number" name="dinner_nonveg_price" placeholder="NonVeg Price"
                            value="<?php echo $edit['special_dinner_nonveg_price'] ?? ''; ?>">
                    </div>

                    <label style="margin-top:10px;">
                        <input type="checkbox" name="enable_base"
                            <?php if (($edit['has_base_option'] ?? 0)) echo "checked"; ?>>
                        Enable Rice / Roti Option
                    </label>

                </div>

                <button>
                    <?php echo $edit ? "✏️ Update Special Menu" : "💾 Save Special Menu"; ?>
                </button>

            </form>

        </div>

        <!-- ================= TABLE ================= -->

        <div class="card">

            <h3>📋 Existing Special Menus</h3>

            <table>

                <tr>
                    <th>Day</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

                <?php while ($row = $result->fetch_assoc()) { ?>

                    <tr>

                        <td><?php echo $row['day']; ?></td>

                        <td>
                            <?php echo date("d M Y", strtotime($row['special_date'])); ?>
                        </td>

                        <td class="<?php echo $row['is_active'] ? 'active' : 'expired'; ?>">
                            <?php echo $row['is_active'] ? "🟢 Active" : "🔴 Expired"; ?>
                        </td>

                        <td>

                            <a class="edit-btn"
                                href="?edit=<?php echo $row['id']; ?>">
                                Edit
                            </a>

                            <a class="delete-btn"
                                href="?delete=<?php echo $row['id']; ?>"
                                onclick="return confirm('Delete Special Menu?')">
                                Delete
                            </a>

                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

        <a href="admin_dashboard.php" class="back">
            ⬅ Back Dashboard
        </a>

    </div>

</body>

</html>