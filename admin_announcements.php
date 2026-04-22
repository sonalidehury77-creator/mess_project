<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$message = "";

/* ===========================
DELETE
=========================== */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("
    DELETE FROM announcements
    WHERE id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_announcements.php");
    exit();
}

/* ===========================
FETCH FOR EDIT
=========================== */

$edit = null;

if (isset($_GET['edit'])) {

    $id = intval($_GET['edit']);

    $stmt = $conn->prepare("
    SELECT *
    FROM announcements
    WHERE id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $edit = $stmt->get_result()->fetch_assoc();
}

/* ===========================
ADD / UPDATE
=========================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $_POST['title'];
    $msg = $_POST['message'];
    $date = $_POST['date'];

    $priority =
        $_POST['priority'];

    $expiry =
        $_POST['expiry_date'];

    $popup =
        isset($_POST['show_popup']) ? 1 : 0;

    /* FILE UPLOAD */

    $fileName = "";

    if (!empty($_FILES['attachment']['name'])) {

        $targetDir =
            "uploads/announcements/";

        $fileName =
            time() . "_" .
            basename($_FILES['attachment']['name']);

        move_uploaded_file(
            $_FILES['attachment']['tmp_name'],
            $targetDir . $fileName
        );
    }

    /* UPDATE */

    if (!empty($_POST['id'])) {

        $id = intval($_POST['id']);

        $sql = "
UPDATE announcements
SET
title=?,
message=?,
announce_date=?,
priority=?,
expiry_date=?,
attachment=?,
show_popup=?
WHERE id=?
";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssssii",
            $title,
            $msg,
            $date,
            $priority,
            $expiry,
            $fileName,
            $popup,
            $id
        );

        $stmt->execute();

        $message = "✏️ Updated Successfully";
    }

    /* INSERT */ else {

        $sql = "
INSERT INTO announcements
(title,message,announce_date,
priority,expiry_date,
attachment,show_popup)

VALUES (?,?,?,?,?,?,?)
";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "ssssssi",
            $title,
            $msg,
            $date,
            $priority,
            $expiry,
            $fileName,
            $popup
        );

        $stmt->execute();

        $message = "✅ Announcement Added";
    }
}

/* ===========================
FETCH DATA
=========================== */

$result =
    $conn->query("
SELECT *
FROM announcements
ORDER BY announce_date DESC
");

$today = date("Y-m-d");

?>

<!DOCTYPE html>
<html>

<head>

    <title>📢 Smart Announcement Manager</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #f4f6f9;
        }

        .container {

            width: 95%;
            margin: 30px auto;

        }

        .form-box {

            background: white;

            padding: 20px;

            border-radius: 12px;

            margin-bottom: 20px;

            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);

        }

        input,
        textarea,
        select {

            width: 100%;

            padding: 10px;

            margin: 8px 0;

            border-radius: 8px;

            border: 1px solid #ccc;

        }

        button {

            padding: 10px 15px;

            background: #007bff;

            color: white;

            border: none;

            border-radius: 8px;

            cursor: pointer;

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

            padding: 10px;

        }

        td {

            padding: 10px;

            border-bottom: 1px solid #ddd;

            text-align: center;

        }

        .urgent {

            background: #ffe5e5;

            font-weight: bold;

        }

        .today {

            background: #fff3cd;

        }

        .delete-btn {

            background: red;

            padding: 5px 8px;

            color: white;

            text-decoration: none;

            border-radius: 6px;

        }

        .edit-btn {

            background: orange;

            padding: 5px 8px;

            color: white;

            text-decoration: none;

            border-radius: 6px;

        }
    </style>

</head>

<body>

    <div class="container">

        <h2>📢 Smart Announcement Manager</h2>

        <?php if ($message) { ?>

            <p style="color:green;font-weight:bold;">
                <?php echo $message; ?>
            </p>

        <?php } ?>

        <!-- FORM -->

        <div class="form-box">

            <form method="POST" enctype="multipart/form-data">

                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $edit['id'] ?? ''; ?>">

                <input
                    type="text"
                    name="title"
                    placeholder="Title"
                    required
                    value="<?php echo $edit['title'] ?? ''; ?>">

                <textarea
                    name="message"
                    placeholder="Message"
                    required><?php echo $edit['message'] ?? ''; ?></textarea>

                <input
                    type="date"
                    name="date"
                    required
                    value="<?php echo $edit['announce_date'] ?? date("Y-m-d"); ?>">

                <label>Priority</label>

                <select name="priority">

                    <option value="normal">Normal</option>

                    <option value="urgent"
                        <?php
                        if (($edit['priority'] ?? '') == 'urgent')
                            echo "selected";
                        ?>>
                        Urgent
                    </option>

                </select>

                <label>Expiry Date</label>

                <input
                    type="date"
                    name="expiry_date"
                    value="<?php echo $edit['expiry_date'] ?? ''; ?>">

                <label>

                    <input
                        type="checkbox"
                        name="show_popup"
                        <?php
                        if (($edit['show_popup'] ?? 0))
                            echo "checked";
                        ?>>

                    Show Popup

                </label>

                <label>Attachment (PDF/Image)</label>

                <input
                    type="file"
                    name="attachment">

                <button>

                    <?php
                    echo $edit
                        ? "✏️ Update"
                        : "➕ Add";
                    ?>

                </button>

            </form>

        </div>

        <!-- TABLE -->

        <table>

            <tr>

                <th>Title</th>
                <th>Message</th>
                <th>Date</th>
                <th>Priority</th>
                <th>File</th>
                <th>Action</th>

            </tr>

            <?php

            while ($row =
                $result->fetch_assoc()
            ) {

                $class = "";

                if ($row['priority'] == "urgent")
                    $class = "urgent";

                if ($row['announce_date'] == $today)
                    $class .= " today";

            ?>

                <tr class="<?php echo $class; ?>">

                    <td><?php echo $row['title']; ?></td>

                    <td><?php echo $row['message']; ?></td>

                    <td>

                        <?php
                        echo date(
                            "d M Y",
                            strtotime($row['announce_date'])
                        );
                        ?>

                    </td>

                    <td>

                        <?php echo ucfirst($row['priority']); ?>

                    </td>

                    <td>

                        <?php
                        if (!empty($row['attachment'])) {
                        ?>

                            <a
                                href="uploads/announcements/<?php echo $row['attachment']; ?>"
                                target="_blank">

                                📎 View

                            </a>

                        <?php } ?>

                    </td>

                    <td>

                        <a
                            class="edit-btn"
                            href="?edit=<?php echo $row['id']; ?>">

                            Edit

                        </a>

                        <a
                            class="delete-btn"
                            href="?delete=<?php echo $row['id']; ?>"
                            onclick="return confirm('Delete?')">

                            Delete

                        </a>

                    </td>

                </tr>

            <?php } ?>

        </table>

        <br>

        <a href="admin_dashboard.php">

            ⬅ Back Dashboard

        </a>

    </div>

</body>

</html>