<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* =========================
   PAGINATION SETTINGS
========================= */

$limit = 10;

$page =
    isset($_GET['page'])
    ? intval($_GET['page'])
    : 1;

if ($page < 1) $page = 1;

$offset =
    ($page - 1) * $limit;


/* =========================
   DELETE STUDENT
========================= */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);

    $stmt = $conn->prepare(
        "DELETE FROM student WHERE id=?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_students.php?msg=deleted");
    exit();
}


/* =========================
   BLOCK / UNBLOCK
========================= */

if (isset($_GET['toggle'])) {

    $id = intval($_GET['toggle']);

    $stmt = $conn->prepare("
        UPDATE student
        SET status =
        CASE
            WHEN status='active'
            THEN 'blocked'
            ELSE 'active'
        END
        WHERE id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_students.php?msg=updated");
    exit();
}


/* =========================
   EXPORT CSV
========================= */

if (isset($_GET['export'])) {

    header('Content-Type: text/csv');
    header(
        'Content-Disposition: attachment;
        filename=students.csv'
    );

    $output = fopen("php://output", "w");

    fputcsv($output, [
        'Name',
        'Roll',
        'Room',
        'Phone',
        'Status'
    ]);

    $res =
        $conn->query(
            "SELECT * FROM student"
        );

    while ($row = $res->fetch_assoc()) {

        fputcsv($output, [

            $row['name'],
            $row['hostel_roll'],
            $row['room_number'],
            $row['phone'],
            $row['status'] ?? 'active'

        ]);
    }

    fclose($output);
    exit();
}


/* =========================
   SEARCH + PAGINATION
========================= */

$search =
    $_GET['search'] ?? '';

if (!empty($search)) {

    $like = "%$search%";

    $stmt = $conn->prepare("
        SELECT * FROM student
        WHERE name LIKE ?
        OR hostel_roll LIKE ?
        ORDER BY id DESC
        LIMIT ?, ?
    ");

    $stmt->bind_param(
        "ssii",
        $like,
        $like,
        $offset,
        $limit
    );

    $stmt->execute();

    $result =
        $stmt->get_result();

    /* Count total */

    $countStmt =
        $conn->prepare("
        SELECT COUNT(*) as total
        FROM student
        WHERE name LIKE ?
        OR hostel_roll LIKE ?
    ");

    $countStmt->bind_param(
        "ss",
        $like,
        $like
    );

    $countStmt->execute();

    $total =
        $countStmt
            ->get_result()
            ->fetch_assoc()['total'];
} else {

    $result =
        $conn->query("
        SELECT * FROM student
        ORDER BY id DESC
        LIMIT $offset, $limit
    ");

    $total =
        $conn->query("
        SELECT COUNT(*) as total
        FROM student
    ")->fetch_assoc()['total'];
}

/* Pagination count */

$total_pages =
    ceil($total / $limit);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Student Management</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            text-align: center;
        }

        /* PRINT FIX */

        @media print {

            .top-bar,
            .button-bar {
                display: none;
            }

        }

        /* TABLE */

        table {

            width: 92%;
            margin: 20px auto;
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

        }

        tr:hover {

            background: #f1f1f1;

        }

        /* IMAGE */

        img {

            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;

        }

        /* BUTTONS */

        .btn {

            padding: 6px 12px;
            text-decoration: none;
            border-radius: 6px;
            color: white;
            margin: 3px;
            display: inline-block;

        }

        .edit {
            background: #28a745;
        }

        .delete {
            background: #dc3545;
        }

        .block {
            background: #ff9800;
        }

        .export {
            background: #6f42c1;
        }

        .print {
            background: #17a2b8;
        }

        .back {
            background: #333;
        }

        /* SEARCH */

        input {

            padding: 8px;
            width: 250px;

        }

        button {

            padding: 8px 12px;
            background: #007bff;
            color: white;
            border: none;

        }

        .pagination a {

            padding: 6px 10px;
            margin: 2px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;

        }
    </style>

    <script>
        function printTable() {

            window.print();

        }
    </script>

</head>

<body>

    <h2>👨‍🎓 Student Management Panel</h2>

    <div>
        Total Students: <b><?php echo $total; ?></b>
    </div>

    <!-- SEARCH -->

    <form method="GET" class="top-bar">

        <input
            type="text"
            name="search"
            placeholder="Search name / roll"
            value="<?php echo htmlspecialchars($search); ?>">

        <button>Search</button>

    </form>

    <!-- BUTTONS -->

    <div class="button-bar">

        <a href="?export=1" class="btn export">
            📥 Export CSV
        </a>

        <a href="#" onclick="printTable()" class="btn print">
            🖨 Print List
        </a>

        <a href="admin_dashboard.php" class="btn back">
            ⬅ Dashboard
        </a>

    </div>

    <table>

        <tr>

            <th>Photo</th>
            <th>Name</th>
            <th>Roll</th>
            <th>Room</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Action</th>

        </tr>

        <?php while ($row = $result->fetch_assoc()) {

            $photo =
                !empty($row['photo'])
                ? $row['photo']
                : 'default.png';

            $status =
                $row['status']
                ?? 'active';

        ?>

            <tr>

                <td>

                    <img src="<?php echo $photo; ?>">

                </td>

                <td>
                    <?php echo htmlspecialchars($row['name']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['hostel_roll']); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($row['room_number']); ?>
                </td>

                <td>

                    <a href="tel:<?php echo $row['phone']; ?>">

                        <?php echo htmlspecialchars($row['phone']); ?>

                    </a>

                </td>

                <td>

                    <?php echo ucfirst($status); ?>

                </td>

                <td>

                    <a
                        class="btn edit"
                        href="edit_student.php?id=<?php echo $row['id']; ?>">

                        Edit

                    </a>

                    <a
                        class="btn delete"
                        href="?delete=<?php echo $row['id']; ?>"
                        onclick="return confirm('Delete this student?')">

                        Delete

                    </a>

                    <?php if ($status == 'active') { ?>

                        <a
                            class="btn block"
                            href="?toggle=<?php echo $row['id']; ?>">

                            🚫 Block

                        </a>

                    <?php } else { ?>

                        <a
                            class="btn edit"
                            href="?toggle=<?php echo $row['id']; ?>">

                            ✅ Unblock

                        </a>

                    <?php } ?>

                </td>

            </tr>

        <?php } ?>

    </table>

    <!-- PAGINATION -->

    <div class="pagination">

        <?php
        for ($i = 1; $i <= $total_pages; $i++) {

            echo "<a href='?page=$i'>$i</a>";
        }
        ?>

    </div>

</body>

</html>