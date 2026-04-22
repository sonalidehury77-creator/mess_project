<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* =========================
   DAYS ORDER
========================= */

$days_order = [
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday'
];

$days_in = "'" . implode("','", $days_order) . "'";

$msg = "";

/* =========================
   UPDATE MENU INLINE
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $day = $_POST['day'] ?? '';

    $breakfast = trim($_POST['breakfast'] ?? '');
    $lunch_veg = trim($_POST['lunch_veg'] ?? '');
    $lunch_nonveg = trim($_POST['lunch_nonveg'] ?? '');
    $dinner_veg = trim($_POST['dinner_veg'] ?? '');
    $dinner_nonveg = trim($_POST['dinner_nonveg'] ?? '');

    if (!empty($day)) {

        $stmt = $conn->prepare("
            UPDATE menu SET
                breakfast=?,
                lunch_veg=?,
                lunch_nonveg=?,
                dinner_veg=?,
                dinner_nonveg=?
            WHERE day=? 
            AND is_special=0
        ");

        $stmt->bind_param(
            "ssssss",
            $breakfast,
            $lunch_veg,
            $lunch_nonveg,
            $dinner_veg,
            $dinner_nonveg,
            $day
        );

        if ($stmt->execute()) {

            $msg = "✅ Menu Updated Successfully!";
        } else {

            $msg = "❌ Update Failed!";
        }
    }
}

/* =========================
   FETCH MENU AFTER UPDATE
========================= */

$result = $conn->query("
    SELECT * FROM menu 
    WHERE day IN ($days_in)
    AND is_special=0
    ORDER BY FIELD(day, $days_in)
");

?>
<!DOCTYPE html>
<html>

<head>
    <title>Smart Menu Manager</title>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        h2 {
            text-align: center;
            margin: 20px 0;
        }

        /* MESSAGE */

        .msg {
            text-align: center;
            color: green;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* BACK BUTTON */

        .back-btn {
            display: block;
            width: 200px;
            margin: 10px auto;
            text-align: center;
            padding: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 8px;
        }

        /* TABLE */

        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        th {
            background: #007bff;
            color: white;
            padding: 10px;
        }

        td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f1f7ff;
        }

        /* INPUT */

        input {
            width: 95%;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        /* BUTTON */

        .btn {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn:hover {
            background: #218838;
        }
    </style>
</head>

<body>

    <h2>📅 Smart Inline Menu Manager</h2>

    <a href="admin_dashboard.php"
        class="back-btn">

        ⬅ Back

    </a>

    <?php if ($msg != "") { ?>

        <div class="msg">
            <?php echo $msg; ?>
        </div>

    <?php } ?>

    <table>

        <tr>

            <th>Day</th>
            <th>Breakfast</th>
            <th>Lunch Veg</th>
            <th>Lunch Nonveg</th>
            <th>Dinner Veg</th>
            <th>Dinner Nonveg</th>
            <th>Action</th>

        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>

            <tr>

                <form method="POST">

                    <td>

                        <b><?php echo htmlspecialchars($row['day']); ?></b>

                        <input
                            type="hidden"
                            name="day"
                            value="<?php echo htmlspecialchars($row['day']); ?>">

                    </td>

                    <td>

                        <input
                            name="breakfast"
                            value="<?php echo htmlspecialchars($row['breakfast']); ?>">

                    </td>

                    <td>

                        <input
                            name="lunch_veg"
                            value="<?php echo htmlspecialchars($row['lunch_veg']); ?>">

                    </td>

                    <td>

                        <input
                            name="lunch_nonveg"
                            value="<?php echo htmlspecialchars($row['lunch_nonveg']); ?>">

                    </td>

                    <td>

                        <input
                            name="dinner_veg"
                            value="<?php echo htmlspecialchars($row['dinner_veg']); ?>">

                    </td>

                    <td>

                        <input
                            name="dinner_nonveg"
                            value="<?php echo htmlspecialchars($row['dinner_nonveg']); ?>">

                    </td>

                    <td>

                        <button class="btn">
                            💾 Save
                        </button>

                    </td>

                </form>

            </tr>

        <?php } ?>

    </table>

</body>

</html>