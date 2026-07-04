<?php
/**
 * 📝 Hostel Management System - Admin Student Profile Update Engine
 * Features: Form Sanitization, File Verification, Anti-CSRF Protection, Argon2id Password Hashing, Atomic Transactions
 */

// 1. START SECURE SESSION HANDLING
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

require_once __DIR__ . "/../config/db_connect.php";

// Admin Authentication Check
if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

// Generate an Anti-CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ==========================================================================
   2. INITIAL RETRIEVAL / AUDIT OF TARGET STUDENT ID
   ========================================================================== */
if (!isset($_GET['id'])) {
    die("Error: Request missing mandatory student identifier.");
}

$id = intval($_GET['id']);

// Fetch initial profile records
$stmt = $pdo->prepare("SELECT * FROM student WHERE id = :id");
$stmt->execute(['id' => $id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Error: The requested student record does not exist in the system.");
}

$error_msg = "";
$success_msg = "";

/* ==========================================================================
   3. TRANSACTION HANDLING (POST REQUEST PROFILE UPDATE)
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // RUN CSRF SECURITY INTEGRITY CHECK
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Security error: Form verification token mismatch.");
    }

    // EXTRACT AND CLEAN INCOMING INPUT DATA
    $name            = ucwords(trim($_POST['name'] ?? '')); 
    $class           = trim($_POST['class'] ?? '');
    $department      = strtoupper(trim($_POST['department'] ?? ''));
    $university_roll = trim($_POST['university_roll'] ?? '');
    $hostel_roll     = strtoupper(trim($_POST['hostel_roll'] ?? ''));
    $phone           = trim($_POST['phone'] ?? '');
    $email           = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $room_number     = trim($_POST['room_number'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $password        = $_POST['password'] ?? '';

    // SERVER-SIDE FIELD RULES VALIDATION
    if (empty($name) || empty($hostel_roll) || empty($email)) {
        $error_msg = "❌ Please complete all mandatory fields marked as required.";
    } elseif (!empty($password) && !preg_match("/^(?=.*[A-Za-z])(?=.*\d).{8,}$/", $password)) {
        $error_msg = "❌ Weak Password: It must be at least 8 characters long and mix letters with numbers.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "❌ Invalid email format.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error_msg = "❌ Phone number must contain exactly 10 digits.";
    }

    /* --- SECURE PICTURE FILE UPLOAD HANDLER --- */
    $photo_path = $data['photo']; // Keep current image path as default

    if (empty($error_msg) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext  = ['jpg', 'jpeg', 'png'];
        $allowed_mime = ['image/jpeg', 'image/png'];

        $file_name    = $_FILES['photo']['name'];
        $file_tmp     = $_FILES['photo']['tmp_name'];
        $file_size    = $_FILES['photo']['size'];
        $ext          = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext, true)) {
            $error_msg = "❌ Image type rejected. Please use a valid JPG, JPEG, or PNG format.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error_msg = "❌ Image size limit exceeded. Upload must be under 2MB.";
        } else {
            // Read raw binary contents to verify image integrity
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mime, true)) {
                $error_msg = "❌ The image file contains corrupt or invalid data.";
            }
        }

        // Move valid photo onto final disk directories
        if (empty($error_msg)) {
            $target_dir = __DIR__ . "/../uploads/students/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            $new_name = bin2hex(random_bytes(16)) . "." . $ext;
            if (move_uploaded_file($file_tmp, $target_dir . $new_name)) {
                
                // Safe old file cleanup execution block
                if (!empty($data['photo']) && $data['photo'] !== "uploads/students/default.png") {
                    $old_photo_path = __DIR__ . "/../" . $data['photo'];
                    if (file_exists($old_photo_path) && is_file($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                $photo_path = "uploads/students/" . $new_name;
            } else {
                $error_msg = "❌ Failed to save the uploaded photo file on server storage.";
            }
        }
    }

    /* --- DB ATOMIC TRANSACTION CONTROLS --- */
    if (empty($error_msg)) {
        try {
            $pdo->beginTransaction();

            // Duplicate cross-check check via 'FOR UPDATE' block to avoid concurrency drift
            $checkStmt = $pdo->prepare("SELECT id FROM student WHERE (hostel_roll = :hostel_roll OR email = :email) AND id != :id LIMIT 1 FOR UPDATE");
            $checkStmt->execute([
                'hostel_roll' => $hostel_roll,
                'email'       => $email,
                'id'          => $id
            ]);

            if ($checkStmt->fetch()) {
                $error_msg = "❌ Account Conflict: This Hostel Roll or Email is already registered to another profile.";
                $pdo->rollBack();
            } else {
                // Determine whether to update password with Argon2id hash or fallback to existing
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
                } else {
                    $hashed_password = $data['password'];
                }

                $updateStmt = $pdo->prepare("
                    UPDATE student SET
                        name = :name,
                        class = :class,
                        department = :department,
                        university_roll = :university_roll,
                        hostel_roll = :hostel_roll,
                        phone = :phone,
                        email = :email,
                        room_number = :room_number,
                        address = :address,
                        password = :password,
                        photo = :photo
                    WHERE id = :id
                ");

                $updateStmt->execute([
                    'name'            => $name,
                    'class'           => $class,
                    'department'      => $department,
                    'university_roll' => $university_roll,
                    'hostel_roll'     => $hostel_roll,
                    'phone'           => $phone,
                    'email'           => $email,
                    'room_number'     => $room_number,
                    'address'         => $address,
                    'password'        => $hashed_password,
                    'photo'           => $photo_path,
                    'id'              => $id
                ]);

                $pdo->commit();
                
                // Clear state tokens and force-reload active dataset metrics
                unset($_SESSION['csrf_token']);
                
                echo "<script>
                    alert('Success: Student records successfully updated.');
                    window.location='students.php';
                </script>";
                exit();
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Database Profile Modification Exception: " . $e->getMessage());
            $error_msg = "⚠️ A critical system issue occurred while saving your changes.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Profile</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 760px;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
        }

        .header-box {
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .header-box h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }

        .header-box p {
            font-size: 14px;
            color: #64748B;
            margin-top: 2px;
            font-weight: 500;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        label {
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #0F172A;
            outline: none;
            transition: all 0.15s;
        }

        input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .avatar-manager-box {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #F8FAFC;
            padding: 16px;
            border-radius: 12px;
            border: 2px dashed #E2E8F0;
        }

        .current-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #FFFFFF;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            background: #CBD5E1;
        }

        input[type="file"] {
            font-size: 13px;
            color: #64748B;
            font-weight: 600;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .alert-danger {
            background: #FEE2E2;
            border: 2px solid #FCA5A5;
            color: #991B1B;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #0F172A;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #1E293B;
        }

        .btn-back {
            display: block;
            text-align: center;
            width: 100%;
            padding: 11px;
            background: #FFFFFF;
            color: #475569;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            margin-top: 12px;
            transition: all 0.15s;
        }

        .btn-back:hover {
            background: #F8FAFC;
            color: #0F172A;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="header-box">
            <h2>✏ Modify Student Profile</h2>
            <p>Update baseline records, room assignments, and profile configurations.</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

            <div class="form-grid">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Class / Year</label>
                    <input type="text" name="class" value="<?php echo htmlspecialchars($data['class']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Academic Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($data['department']); ?>" required>
                </div>

                <div class="form-group">
                    <label>University Roll Number</label>
                    <input type="text" name="university_roll" value="<?php echo htmlspecialchars($data['university_roll']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Hostel Roll Number</label>
                    <input type="text" name="hostel_roll" value="<?php echo htmlspecialchars($data['hostel_roll']); ?>" oninput="this.value = this.value.toUpperCase()" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g,'')" value="<?php echo htmlspecialchars($data['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Allocated Room Number</label>
                    <input type="text" name="room_number" value="<?php echo htmlspecialchars($data['room_number']); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label>Permanent Home Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($data['address']); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label>Account Password Override (Optional)</label>
                    <input type="password" name="password" placeholder="Leave empty to keep current secure password">
                </div>

                <div class="form-group full-width">
                    <label>Profile Identification Photo</label>
                    <div class="avatar-manager-box">
                        <?php
                        $avatarSource = !empty($data['photo']) ? "../" . $data['photo'] : "uploads/students/default.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($avatarSource); ?>" class="current-avatar" alt="Current Photo">
                        <div class="file-upload-interaction">
                            <input type="file" name="photo" accept=".png, .jpg, .jpeg">
                            <p style="font-size: 12px; color: #64748B; margin-top: 4px; font-weight: 500;">Supported file formats: .png, .jpg, .jpeg (Max 2MB)</p>
                        </div>
                    </div>
                </div>

                <div class="full-width">
                    <button type="submit" class="btn-submit">💾 Save Profile Modifications</button>
                </div>

            </div>
        </form>

        <a href="students.php" class="btn-back">⬅ Cancel and Return to Registry</a>

    </div>

</body>

</html>