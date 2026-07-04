<?php
session_start();

/* ==========================================================================
   1. DATABASE CONNECTIVITY LAYER (PDO CONFIGURATION)
   ========================================================================== */
require_once __DIR__ . "/../config/db_connect.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* ==========================================================================
   2. PAGINATION CONFIGURATION ENGINE
   ========================================================================== */
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

/* ==========================================================================
   3. ACCOUNT DELETION MANAGEMENT
   ========================================================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $stmt = $pdo->prepare("DELETE FROM student WHERE id = :id");
    $stmt->execute(['id' => $id]);

    header("Location: students.php?msg=deleted");
    exit();
}

/* ==========================================================================
   4. ACCOUNT STATUS TOGGLE SYSTEM (BLOCK / UNBLOCK)
   ========================================================================== */
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);

    $stmt = $pdo->prepare("
        UPDATE student 
        SET status = CASE WHEN status = 'active' THEN 'blocked' ELSE 'active' END 
        WHERE id = :id
    ");
    $stmt->execute(['id' => $id]);

    // Retain search parameter during toggle if it exists
    $search_back = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
    header("Location: students.php?msg=updated" . $search_back);
    exit();
}

/* ==========================================================================
   5. REPORT DATA EXPORT PIPELINE (CSV ENGINE)
   ========================================================================== */
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=student_registry_' . date('Y-m-d') . '.csv');

    $output = fopen("php://output", "w");
    fputcsv($output, ['Name', 'Hostel Roll', 'Room Number', 'Phone', 'Status']);

    $res = $pdo->query("SELECT name, hostel_roll, room_number, phone, status FROM student ORDER BY id DESC");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
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

/* ==========================================================================
   6. SEARCH AND RETRIEVAL ALGORITHMS
   ========================================================================== */
$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $like = "%$search%";

    // Fetch matching filtered records
    $stmt = $pdo->prepare("
        SELECT * FROM student 
        WHERE name LIKE :search_name OR hostel_roll LIKE :search_roll 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset
    ");

    $stmt->bindValue(':search_name', $like, PDO::PARAM_STR);
    $stmt->bindValue(':search_roll', $like, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute total found records match count
    $countStmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM student 
        WHERE name LIKE :search_name OR hostel_roll LIKE :search_roll
    ");
    $countStmt->execute([
        'search_name' => $like,
        'search_roll' => $like
    ]);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
} else {
    // Standard default fetch list
    $stmt = $pdo->prepare("SELECT * FROM student ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = $pdo->query("SELECT COUNT(*) as total FROM student")->fetch(PDO::FETCH_ASSOC)['total'];
}

$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registry Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', -apple-system, sans-serif;
        }

        body {
            background: #E2E8F0;
            color: #0F172A;
            padding: 40px 24px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
        }

        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 20px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title h2 {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }

        .header-title p {
            font-size: 14px;
            color: #64748B;
            font-weight: 500;
            margin-top: 2px;
        }

        /* ACTIONS TOOLBAR SECTION */
        .toolbar-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-form {
            display: flex;
            gap: 8px;
        }

        .search-form input {
            padding: 10px 16px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            width: 280px;
            font-weight: 500;
            transition: all 0.15s;
        }

        .search-form input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .search-form button {
            padding: 10px 20px;
            background: #0F172A;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .search-form button:hover {
            background: #1E293B;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        /* GENERAL DESIGN BUTTON REFACTORING */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-add {
            background: #2563EB;
            color: white;
        }

        .btn-add:hover {
            background: #1D4ED8;
            transform: translateY(-1px);
        }

        .btn-export {
            background: #7C3AED;
            color: white;
        }

        .btn-export:hover {
            background: #6D28D9;
            transform: translateY(-1px);
        }

        .btn-print {
            background: #0EA5E9;
            color: white;
        }

        .btn-print:hover {
            background: #0284C7;
            transform: translateY(-1px);
        }

        .btn-back {
            background: #FFFFFF;
            color: #334155;
            border: 2px solid #CBD5E1;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: #0F172A;
            border-color: #94A3B8;
        }

        /* DATA TABLE MODULE */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #FFFFFF;
            text-align: left;
        }

        th {
            background: #F8FAFC;
            color: #475569;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            padding: 16px;
            border-bottom: 2px solid #E2E8F0;
            letter-spacing: 0.05em;
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 14px;
            color: #334155;
            font-weight: 500;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #F8FAFC;
        }

        /* STUDENT AVATAR CONFIGURATION */
        .student-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #E2E8F0;
            background: #F1F5F9;
        }

        /* ACTION HOVER BADGES */
        .action-link {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            margin-right: 4px;
            transition: opacity 0.15s, transform 0.15s;
        }

        .action-link:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }

        .act-edit {
            background: #DCFCE7;
            color: #166534;
        }

        .act-delete {
            background: #FEE2E2;
            color: #991B1B;
        }

        .act-block {
            background: #FEF3C7;
            color: #92400E;
        }

        .act-unblock {
            background: #DBEAFE;
            color: #1E40AF;
        }

        /* STATUS BADGE STYLING */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .badge-active {
            background: #D1FAE5;
            color: #065F46;
        }

        .badge-blocked {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* PAGINATION ELEMENTS */
        .pagination-container {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
        }

        .pagination-container a {
            padding: 8px 14px;
            background: #FFFFFF;
            border: 2px solid #CBD5E1;
            color: #475569;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.15s;
        }

        .pagination-container a:hover,
        .pagination-container a.active {
            background: #0F172A;
            border-color: #0F172A;
            color: #FFFFFF;
        }

        /* PRINT MEDIA STYLES SHEET */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }

            .toolbar-box,
            .pagination-container,
            th:last-child,
            td:last-child {
                display: none !important;
            }

            th,
            td {
                padding: 10px 6px;
                border-bottom: 1px solid #000;
                font-size: 12px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header-wrapper">
            <div class="header-title">
                <h2>👨‍🎓 Student Profiles Registry</h2>
                <p>View, filter, and manage registered student accounts active within the hostel system database.</p>
            </div>
            <div class="button-group">
                <a href="add_student.php" class="btn btn-add">➕ Add New Student</a>
            </div>
        </div>

        <div class="toolbar-box">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" placeholder="Search by name or roll number..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
            </form>

            <div class="button-group">
                <a href="?export=1" class="btn btn-export">📥 Export CSV File</a>
                <button onclick="window.print()" class="btn btn-print">🖨 Print This Report</button>
                <a href="dashboard.php" class="btn btn-back">⬅ Back to Dashboard</a>
            </div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px; text-align: center;">Photo</th>
                        <th>Student Information</th>
                        <th>Hostel Roll No.</th>
                        <th>Room No.</th>
                        <th>Contact Number</th>
                        <th>Account Status</th>
                        <th style="text-align: right; width: 260px;">Management Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $row):
                            $photoPath = !empty($row['photo']) ? '../' . $row['photo'] : 'default.png';
                            $status = $row['status'] ?? 'active';
                        ?>
                            <tr>
                                <td style="text-align: center;">
                                    <img src="<?php echo htmlspecialchars($photoPath); ?>" class="student-avatar" alt="Avatar">
                                </td>
                                <td>
                                    <strong style="color: #0F172A; display: block; font-size: 15px;"><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <span style="font-size: 12px; color: #64748B;"><?php echo htmlspecialchars($row['email'] ?? 'No Registered Email'); ?></span>
                                </td>
                                <td><code style="background: #F1F5F9; padding: 3px 6px; border-radius: 4px; font-weight:600; color:#334155; font-size:13px;"><?php echo htmlspecialchars($row['hostel_roll']); ?></code></td>
                                <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($row['phone']); ?>" style="color: #2563EB; text-decoration: none; font-weight: 600;">
                                        <?php echo htmlspecialchars($row['phone']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $status; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a class="action-link act-edit" href="edit_student.php?id=<?php echo $row['id']; ?>">Modify</a>

                                    <?php if ($status === 'active'): ?>
                                        <a class="action-link act-block" href="?toggle=<?php echo $row['id'] . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>">🚫 Block</a>
                                    <?php else: ?>
                                        <a class="action-link act-unblock" href="?toggle=<?php echo $row['id'] . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>">✅ Unblock</a>
                                    <?php endif; ?>

                                    <a class="action-link act-delete" href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this student profile?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #64748B; font-weight: 600;">
                                📭 No student profile records found matching your active search criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>