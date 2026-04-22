<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ===============================
DEFAULT → PREVIOUS MONTH
=============================== */

if (isset($_GET['month'])) {

    $month  = $_GET['month'];
    $year   = $_GET['year'];
} else {

    $month =
        date('m', strtotime("first day of previous month"));

    $year  =
        date('Y', strtotime("first day of previous month"));
}

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
ON bills.hostel_roll =
student.hostel_roll

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
)

";

    $like = "%$search%";

    $params[] = $like;
    $params[] = $like;

    $types .= "ss";
}

$sql .= "

ORDER BY
bills.year DESC,
bills.month DESC,
CAST(bills.hostel_roll AS UNSIGNED)

";

/* EXECUTE */

$stmt = $conn->prepare($sql);

if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}

$stmt->execute();

$result = $stmt->get_result();

/* ===============================
SUMMARY
=============================== */

$total_records = 0;
$total_amount  = 0;

/* TOTAL REVENUE TILL NOW */

$totalRevenueQuery =
    $conn->query("

SELECT SUM(total_amount) as total
FROM bills

");

$rev =
    $totalRevenueQuery->fetch_assoc();

$total_till_now =
    $rev['total'] ?? 0;

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

        /* HEADER */

        .header {

            background: #007bff;
            color: white;

            padding: 15px;
            text-align: center;

            font-size: 22px;

        }

        /* FILTER */

        .filter {

            background: white;
            padding: 15px;

            text-align: center;

        }

        input,
        select {

            padding: 8px;
            margin: 4px;

        }

        button {

            padding: 8px 12px;

            background: #007bff;
            color: white;

            border: none;

            cursor: pointer;

        }

        /* TABLE */

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

        }

        tr:hover {

            background: #f1f1f1;

        }

        /* BUTTON */

        .view-btn {

            background: green;
            color: white;

            padding: 5px 10px;

            border-radius: 6px;
            text-decoration: none;

        }

        /* SUMMARY */

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

            box-shadow:
                0 4px 10px rgba(0, 0, 0, .1);

        }

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

</head>

<body>

    <div class="header">

        📜 Bill History

    </div>

    <!-- FILTER -->

    <div class="filter">

        <form method="GET">

            <select name="month">

                <option value="">
                    All Months
                </option>

                <?php

                for ($m = 1; $m <= 12; $m++) {

                    $sel =
                        ($month == $m)
                        ? "selected" : "";

                    $monthName =
                        date(
                            "F",
                            mktime(0, 0, 0, $m, 1)
                        );

                    echo "

<option
value='$m'
$sel>

$monthName

</option>

";
                }

                ?>

            </select>

            <select name="year">

                <option value="">
                    All Years
                </option>

                <?php

                for ($y = 2023; $y <= 2035; $y++) {

                    $sel =
                        ($year == $y)
                        ? "selected" : "";

                    echo "

<option
value='$y'
$sel>

$y

</option>

";
                }

                ?>

            </select>

            <input
                type="text"
                name="search"
                placeholder="Search Name / Roll"
                value="<?php
                        echo htmlspecialchars($search);
                        ?>">

            <button>

                🔍 Filter

            </button>

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
            <th>Date</th>
            <th>View</th>

        </tr>

        <?php

        while ($row =
            $result->fetch_assoc()
        ) {

            $total_records++;

            $total_amount
                += $row['total_amount'];

            $monthName =
                date(
                    "F",
                    mktime(
                        0,
                        0,
                        0,
                        $row['month'],
                        1
                    )
                );

        ?>

            <tr>

                <td>

                    <?php
                    echo $row['name'];
                    ?>

                </td>

                <td>

                    <?php
                    echo $row['hostel_roll'];
                    ?>

                </td>

                <td>

                    <?php
                    echo $row['room_number'];
                    ?>

                </td>

                <td>

                    <?php
                    echo $monthName;
                    ?>

                </td>

                <td>

                    <?php
                    echo $row['year'];
                    ?>

                </td>

                <td>

                    ₹ <?php
                        echo number_format(
                            $row['total_amount']
                        );
                        ?>

                </td>

                <td>

                    <?php

                    echo date(
                        "d M Y",
                        strtotime(
                            $row['generated_at']
                        )

                    );

                    ?>

                </td>

                <td>

                    <a
                        class="view-btn"

                        href="view_bill.php?
roll=<?php echo $row['hostel_roll']; ?>
&month=<?php echo $row['month']; ?>
&year=<?php echo $row['year']; ?>">

                        📄 View

                    </a>

                </td>

            </tr>

        <?php } ?>

    </table>

    <!-- SUMMARY -->

    <div class="summary">

        <div class="card">

            📄 Records

            <h2>

                <?php
                echo $total_records;
                ?>

            </h2>

        </div>

        <div class="card">

            💰 This Filter Total

            <h2>

                ₹ <?php
                    echo number_format(
                        $total_amount
                    );
                    ?>

            </h2>

        </div>

        <div class="card">

            📊 Revenue Till Now

            <h2>

                ₹ <?php
                    echo number_format(
                        $total_till_now
                    );
                    ?>

            </h2>

        </div>

    </div>

    <a
        href="admin_dashboard.php"
        class="back">

        ⬅ Back Dashboard

    </a>

</body>

</html>