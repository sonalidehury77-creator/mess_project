<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ===============================
MARK STATUS (PAID / PENDING)
=============================== */

if (isset($_GET['set_status']) && isset($_GET['id'])) {

    $id = intval($_GET['id']);
    $status = $_GET['set_status'] == 'paid' ? 'paid' : 'pending';

    if ($status == 'paid') {
        $stmt = $conn->prepare("UPDATE bills SET status='paid', paid_at=NOW() WHERE id=?");
    } else {
        $stmt = $conn->prepare("UPDATE bills SET status='pending', paid_at=NULL WHERE id=?");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_bill_history.php?month=$month&year=$year&search=$search");
    exit();
}

/* ===============================
FILTERS
=============================== */

$month  = $_GET['month'] ?? '';
$year   = $_GET['year'] ?? '';
$search = $_GET['search'] ?? '';

/* ===============================
QUERY
=============================== */

$sql = "
SELECT 
    bills.*,
    student.name,
    student.room_number
FROM bills
JOIN student 
ON bills.hostel_roll = student.hostel_roll
WHERE 1
";

$params = [];
$types  = "";

/* MONTH */
if ($month != "") {
    $sql .= " AND bills.month=? ";
    $params[] = $month;
    $types .= "i";
}

/* YEAR */
if ($year != "") {
    $sql .= " AND bills.year=? ";
    $params[] = $year;
    $types .= "i";
}

/* SEARCH */
if ($search != "") {

    $sql .= "
    AND (
        student.name LIKE ?
        OR bills.hostel_roll LIKE ?
    )";

    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$sql .= "
ORDER BY bills.year DESC, bills.month DESC, CAST(bills.hostel_roll AS UNSIGNED)
";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

/* ===============================
SUMMARY
=============================== */

$total_records = 0;
$total_amount  = 0;
$paid_count = 0;
$pending_count = 0;

/* TOTAL REVENUE */

$rev = $conn->query("SELECT SUM(total_amount) as total FROM bills")->fetch_assoc();
$total_till_now = $rev['total'] ?? 0;

?>

<!DOCTYPE html>
<html>

<head>
    <title>📜 Bill History</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: #eef2f7;
            margin: 0;
        }

        .header {
            background: #007bff;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 22px;
        }

        .filter {
            background: white;
            padding: 15px;
            text-align: center;
        }

        input,
        select {
            padding: 8px;
            margin: 5px;
        }

        button {
            padding: 8px 12px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        table {
            width: 96%;
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
            padding: 8px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .paid {
            color: green;
            font-weight: bold;
        }

        .pending {
            color: red;
            font-weight: bold;
        }

        .btn {
            padding: 6px 10px;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .green {
            background: green;
        }

        .orange {
            background: orange;
        }

        .blue {
            background: #007bff;
        }

        .summary {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }

        .card {
            background: white;
            padding: 15px;
            margin: 10px;
            width: 220px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
        }

        .back {
            display: block;
            width: 220px;
            margin: 20px auto;
            text-align: center;
            padding: 10px;
            background: #333;
            color: white;
            border-radius: 8px;
            text-decoration: none;
        }
    </style>

</head>

<body>

    <div class="header">
        📜 Advanced Bill History
    </div>

    <!-- FILTER -->
    <div class="filter">
        <form method="GET">

            <select name="month">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++) { ?>
                    <option value="<?php echo $m; ?>" <?php if ($month == $m) echo "selected"; ?>>
                        <?php echo date("F", mktime(0, 0, 0, $m, 1)); ?>
                    </option>
                <?php } ?>
            </select>

            <select name="year">
                <option value="">All Years</option>
                <?php for ($y = 2023; $y <= date("Y"); $y++) { ?>
                    <option value="<?php echo $y; ?>" <?php if ($year == $y) echo "selected"; ?>>
                        <?php echo $y; ?>
                    </option>
                <?php } ?>
            </select>

            <input type="text" name="search" placeholder="Search Name / Roll"
                value="<?php echo htmlspecialchars($search); ?>">

            <button>🔍 Filter</button>

        </form>
    </div>

    <!-- TABLE -->
    <table>

        <tr>
            <th>Name</th>
            <th>Roll</th>
            <th>Room</th>
            <th>Month</th>
            <th>Year</th>
            <th>Amount ₹</th>
            <th>Status</th>
            <th>Paid On</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) {

            $total_records++;
            $total_amount += $row['total_amount'];

            if ($row['status'] == 'paid') $paid_count++;
            else $pending_count++;

            $monthName = date("F", mktime(0, 0, 0, $row['month'], 1));
        ?>

            <tr>

                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['hostel_roll']; ?></td>
                <td><?php echo $row['room_number']; ?></td>
                <td><?php echo $monthName; ?></td>
                <td><?php echo $row['year']; ?></td>
                <td>₹ <?php echo number_format($row['total_amount']); ?></td>

                <td>
                    <?php if ($row['status'] == 'paid') { ?>
                        <span class="paid">✅ Paid</span>
                    <?php } else { ?>
                        <span class="pending">⏳ Pending</span>
                    <?php } ?>
                </td>

                <td>
                    <?php echo $row['paid_at'] ? date("d M Y", strtotime($row['paid_at'])) : "-"; ?>
                </td>

                <td>

                    <a class="btn blue"
                        href="view_bill.php?roll=<?php echo $row['hostel_roll']; ?>&month=<?php echo $row['month']; ?>&year=<?php echo $row['year']; ?>">
                        📄 View
                    </a>

                    <?php if ($row['status'] == 'paid') { ?>

                        <a class="btn orange"
                            href="admin_bill_history.php?set_status=pending&id=<?php echo $row['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&search=<?php echo $search; ?>">
                            Mark Pending
                        </a>

                    <?php } else { ?>

                        <a class="btn green"
                            href="admin_bill_history.php?set_status=paid&id=<?php echo $row['id']; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>&search=<?php echo $search; ?>">
                            Mark Paid
                        </a>

                    <?php } ?>

                </td>

            </tr>

        <?php } ?>

    </table>

    <!-- SUMMARY -->
    <div class="summary">

        <div class="card">
            📄 Records
            <h2><?php echo $total_records; ?></h2>
        </div>

        <div class="card">
            💰 Filter Total
            <h2>₹ <?php echo number_format($total_amount); ?></h2>
        </div>

        <div class="card">
            ✅ Paid
            <h2><?php echo $paid_count; ?></h2>
        </div>

        <div class="card">
            ⏳ Pending
            <h2><?php echo $pending_count; ?></h2>
        </div>

        <div class="card">
            📊 Total Revenue
            <h2>₹ <?php echo number_format($total_till_now); ?></h2>
        </div>

    </div>

    <a href="admin_dashboard.php" class="back">
        ⬅ Back Dashboard
    </a>

</body>

</html>