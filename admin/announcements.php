<?php
session_start();

/* ==========================================================================
   1. DATABASE CONNECTIVITY & SESSION LAYER (PDO)
   ========================================================================== */
require_once __DIR__ . "/../config/db_connect.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$message = "";
$message_type = "";

/* ==========================================================================
   2. ATOMIC DELETION ROUTE (WITH FILE AUTO-CLEANUP)
   ========================================================================== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Retrieve filename first to unlink the file from storage disk safely
    $file_stmt = $pdo->prepare("SELECT attachment FROM announcements WHERE id = :id");
    $file_stmt->execute(['id' => $id]);
    $announcement_file = $file_stmt->fetch(PDO::FETCH_ASSOC);

    if ($announcement_file && !empty($announcement_file['attachment'])) {
        $filePath = "uploads/announcements/" . $announcement_file['attachment'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    $delete_stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
    $delete_stmt->execute(['id' => $id]);

    header("Location: announcements.php");
    exit();
}

/* ==========================================================================
   3. HYDRATE EDIT PATTERNS SAFELY
   ========================================================================== */
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = :id");
    $edit_stmt->execute(['id' => $id]);
    $edit = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

/* ==========================================================================
   4. TRANSACTION DISPATCH (POST INTERCEPTOR)
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id       = !empty($_POST['id']) ? intval($_POST['id']) : null;
    $title    = trim($_POST['title']);
    $msg      = trim($_POST['message']);
    $date     = $_POST['date'];
    $priority = $_POST['priority'];
    $expiry   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $popup    = isset($_POST['show_popup']) ? 1 : 0;

    /* FILE UPLOAD HOOKS */
    $fileName = $edit['attachment'] ?? ""; // Default fallback to original document pointer

    if (!empty($_FILES['attachment']['name'])) {
        $targetDir = "uploads/announcements/";

        // Form directory tree dynamically if absent safely
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Professional Addon: If editing and uploading a new file, remove the old one from the server
        if ($id !== null && !empty($edit['attachment'])) {
            $oldFilePath = $targetDir . $edit['attachment'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }

        $fileName = time() . "_" . basename($_FILES['attachment']['name']);
        move_uploaded_file($_FILES['attachment']['tmp_name'], $targetDir . $fileName);
    }

    if ($id !== null) {
        /* UPDATE SYSTEM STATE */
        $stmt = $pdo->prepare("
            UPDATE announcements 
            SET title = :title, message = :message, announce_date = :date, 
                priority = :priority, expiry_date = :expiry, attachment = :attachment, show_popup = :popup 
            WHERE id = :id
        ");
        $stmt->execute([
            'title' => $title,
            'message' => $msg,
            'date' => $date,
            'priority' => $priority,
            'expiry' => $expiry,
            'attachment' => $fileName,
            'popup' => $popup,
            'id' => $id
        ]);
        $message = "Announcement updated successfully.";
        $message_type = "success";
    } else {
        /* WRITE NEW CONFIGURATION BLOCK */
        $stmt = $pdo->prepare("
            INSERT INTO announcements (title, message, announce_date, priority, expiry_date, attachment, show_popup) 
            VALUES (:title, :message, :date, :priority, :expiry, :attachment, :popup)
        ");
        $stmt->execute([
            'title' => $title,
            'message' => $msg,
            'date' => $date,
            'priority' => $priority,
            'expiry' => $expiry,
            'attachment' => $fileName,
            'popup' => $popup
        ]);
        $message = "Announcement published successfully.";
        $message_type = "success";
    }

    // Clear state if adding new items
    if ($message_type === "success" && $id === null) {
        header("Location: announcements.php");
        exit();
    }
}

/* ==========================================================================
   5. BUFFERS INGEST STREAM DATA IMMEDIATELY
   ========================================================================== */
$list_stmt = $pdo->query("SELECT * FROM announcements ORDER BY announce_date DESC, id DESC");
$announcements = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
$today = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Management Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
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
        }

        /* CARD FRAMEWORK */
        .card {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
            margin-bottom: 24px;
        }

        .header-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-wrapper h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }

        /* GRID STRUCTURES */
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        label.field-title {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 6px;
            display: block;
        }

        input[type="text"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            background: #FFFFFF;
            transition: border-color 0.15s ease;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #2563EB;
        }

        textarea {
            resize: vertical;
            min-height: 110px;
        }

        /* INTERACTIVES */
        .switch-container {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            margin-top: 10px;
        }

        .file-input-wrapper {
            background: #F8FAFC;
            border: 2px dashed #CBD5E1;
            padding: 10px;
            border-radius: 8px;
        }

        /* TABLES */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #F8FAFC;
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 16px;
            border-bottom: 2px solid #E2E8F0;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 14px;
            vertical-align: top;
        }

        /* CONDITIONAL STATUS COLORS */
        tr.status-urgent {
            background: #FFF5F5;
        }

        tr.status-today {
            background: #FEFCE8;
        }

        tr.status-expired {
            opacity: 0.65;
            background: #F8FAFC;
        }

        /* BADGES */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-normal {
            background: #F1F5F9;
            color: #475569;
        }

        .badge-urgent {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .badge-popup {
            background: #E0F2FE;
            color: #0369A1;
            font-size: 10px;
            margin-top: 4px;
            display: inline-block;
        }

        .badge-expired {
            background: #E2E8F0;
            color: #64748B;
        }

        /* SYSTEM BUTTONS */
        .btn-submit {
            background: #0F172A;
            color: #FFFFFF;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-submit:hover {
            background: #1E293B;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 16px;
            background: #FFFFFF;
            color: #475569;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: #0F172A;
        }

        .action-link {
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            margin-right: 12px;
        }

        .act-edit {
            color: #D97706;
        }

        .act-delete {
            color: #DC2626;
        }

        .clip-link {
            color: #2563EB;
            font-weight: 600;
            text-decoration: none;
        }

        .toast-msg {
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .toast-success {
            background: #DCFCE7;
            color: #14532D;
            border-left: 4px solid #22C55E;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header-wrapper">
            <h2>📢 Announcement Management Center</h2>
            <a href="dashboard.php" class="btn-back">⬅ Return to Dashboard</a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="toast-msg toast-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit['id'] ?? ''); ?>">

                <div class="grid-2">
                    <div>
                        <label class="field-title">Announcement Title</label>
                        <input type="text" name="title" placeholder="e.g., Mess Operational Timings Shift" value="<?php echo htmlspecialchars($edit['title'] ?? ''); ?>" required>
                    </div>
                    <div>
                        <label class="field-title">Priority Level</label>
                        <select name="priority">
                            <option value="normal" <?php echo (isset($edit['priority']) && $edit['priority'] === 'normal') ? 'selected' : ''; ?>>Standard Notice</option>
                            <option value="urgent" <?php echo (isset($edit['priority']) && $edit['priority'] === 'urgent') ? 'selected' : ''; ?>>Urgent Alert / Warning</option>
                        </select>
                    </div>
                </div>

                <div class="grid-3">
                    <div>
                        <label class="field-title">Publish Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($edit['announce_date'] ?? date("Y-m-d")); ?>" required>
                    </div>
                    <div>
                        <label class="field-title">Expiry Date (Optional)</label>
                        <input type="date" name="expiry_date" value="<?php echo htmlspecialchars($edit['expiry_date'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="field-title">File Attachment (PDF/Image)</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="attachment">
                        </div>
                        <?php if (!empty($edit['attachment'])): ?>
                            <span style="font-size: 11px; color:#475569;">Current file: <?php echo htmlspecialchars($edit['attachment']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="field-title">Announcement Description / Content</label>
                    <textarea name="message" placeholder="Type the main announcement updates, rules, or details here..." required><?php echo htmlspecialchars($edit['message'] ?? ''); ?></textarea>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="switch-container">
                        <input type="checkbox" name="show_popup" <?php echo (!empty($edit['show_popup'])) ? "checked" : ""; ?>>
                        Force alert popup warning immediately on student login screen
                    </label>

                    <button type="submit" class="btn-submit">
                        <?php echo $edit ? "✏️ Update Announcement" : "➕ Publish Announcement"; ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="font-size: 16px; font-weight: 800; margin-bottom: 16px; color: #0F172A;">📋 All Active & Scheduled Announcements</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 25%;">Title</th>
                            <th style="width: 40%;">Content Message</th>
                            <th style="width: 12%;">Relevant Dates</th>
                            <th style="width: 10%;">Status</th>
                            <th style="width: 5%;">File</th>
                            <th style="text-align: right; width: 10%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($announcements) === 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748B; padding: 30px; font-weight: 500;">No records found within active memory framework maps.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($announcements as $row):
                                $rowClass = "";
                                $isExpired = (!empty($row['expiry_date']) && $row['expiry_date'] < $today);

                                if ($isExpired) {
                                    $rowClass = "status-expired";
                                } elseif ($row['priority'] === "urgent") {
                                    $rowClass = "status-urgent";
                                } elseif ($row['announce_date'] === $today) {
                                    $rowClass = "status-today";
                                }
                            ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td>
                                        <strong style="color: #0F172A; font-weight:700;"><?php echo htmlspecialchars($row['title']); ?></strong>
                                    </td>
                                    <td style="color: #334155; line-height: 1.4; font-size:13px; white-space: pre-wrap;"><?php echo htmlspecialchars($row['message']); ?></td>
                                    <td style="font-size: 13px; color: #475569;">
                                        <div>🚀 <?php echo date("d M Y", strtotime($row['announce_date'])); ?></div>
                                        <?php if (!empty($row['expiry_date'])): ?>
                                            <div style="margin-top:4px; font-size:11px; color:#94A3B8;">🛑 Exp: <?php echo date("d M Y", strtotime($row['expiry_date'])); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isExpired): ?>
                                            <span class="badge badge-expired">Expired</span>
                                        <?php else: ?>
                                            <span class="badge badge-<?php echo $row['priority']; ?>"><?php echo $row['priority']; ?></span>
                                        <?php endif; ?>

                                        <?php if ($row['show_popup'] && !$isExpired): ?>
                                            <br><span class="badge badge-popup">⚡ Login Popup</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['attachment'])): ?>
                                            <a href="uploads/announcements/<?php echo htmlspecialchars($row['attachment']); ?>" target="_blank" class="clip-link" title="Open attachment file link">📎 View</a>
                                        <?php else: ?>
                                            <span style="color:#94A3B8; font-size:12px;">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="?edit=<?php echo $row['id']; ?>" class="action-link act-edit">Edit</a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="action-link act-delete" onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>

</html>