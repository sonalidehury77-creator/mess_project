<?php
/**
 * Hostel Management System - Admin Student Enrollment Engine
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
    header("Location: login.php");
    exit();
}

// Generate an Anti-CSRF Token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = false;
$error_msg = "";
$student_name = "";
$student_roll = "";

/* ==========================================================================
   2. ADD STUDENT REGISTRY ROUTINE
   ========================================================================== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // CSRF SECURITY INTEGRITY CHECK
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
    $room            = trim($_POST['room_number'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $password        = $_POST['password'] ?? '';

    // SERVER-SIDE FIELD RULES VALIDATION
    if (empty($name) || empty($hostel_roll) || empty($password) || empty($email)) {
        $error_msg = "❌ Please complete all mandatory fields marked as required.";
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).{8,}$/", $password)) {
        $error_msg = "❌ Weak Password: It must be at least 8 characters long and contain both letters and numbers.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "❌ Invalid email format.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error_msg = "❌ Phone number must contain exactly 10 digits.";
    }

    // SECURE PICTURE FILE UPLOAD HANDLER
    $photo_path = "uploads/students/default.png"; // Fallback placeholder path
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

        // Move valid photo out of temporary upload zones onto final disk paths
        if (empty($error_msg)) {
            $target_dir = __DIR__ . "/../uploads/students/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Cryptographically unique signature names prevent collisions and URL guessing
            $new_name = bin2hex(random_bytes(16)) . "." . $ext;
            if (move_uploaded_file($file_tmp, $target_dir . $new_name)) {
                $photo_path = "uploads/students/" . $new_name;
            } else {
                $error_msg = "❌ Failed to save the uploaded photo file onto storage.";
            }
        }
    }

    // DB ATOMIC TRANSACTION CONTROLS
    if (empty($error_msg)) {
        try {
            // Establish an atomic workflow transaction boundary block
            $pdo->beginTransaction();

            // Check if student details already exist ('FOR UPDATE' prevents race conditions)
            $checkStmt = $pdo->prepare("SELECT id FROM student WHERE hostel_roll = :hostel_roll OR email = :email LIMIT 1 FOR UPDATE");
            $checkStmt->execute([
                'hostel_roll' => $hostel_roll,
                'email'       => $email
            ]);

            if ($checkStmt->fetch()) {
                $error_msg = "❌ Account Conflict: This Hostel Roll or Email is already registered.";
                $pdo->rollBack();
            } else {
                // Securely hash password via Argon2id standards
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

                $insertStmt = $pdo->prepare("
                    INSERT INTO student 
                    (name, class, department, university_roll, hostel_roll, phone, email, room_number, address, password, photo, status) 
                    VALUES (:name, :class, :department, :university_roll, :hostel_roll, :phone, :email, :room, :address, :password, :photo, 'active')
                ");

                $insertStmt->execute([
                    'name'            => $name,
                    'class'           => $class,
                    'department'      => $department,
                    'university_roll' => $university_roll,
                    'hostel_roll'     => $hostel_roll,
                    'phone'           => $phone,
                    'email'           => $email,
                    'room'            => $room,
                    'address'         => $address,
                    'password'        => $hashed_password,
                    'photo'           => $photo_path
                ]);

                $pdo->commit();
                $success = true;
                $student_name = $name;
                $student_roll = $hostel_roll;

                // Terminate form specific CSRF tokens on success
                unset($_SESSION['csrf_token']);
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Student registration exception handle: " . $e->getMessage());
            $error_msg = "⚠️ A critical technical error occurred while saving the record.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Management Portal</title>
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
            min-height: 100vh;
            padding: 40px 24px;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05);
        }

        .form-header {
            text-align: center;
            margin-bottom: 32px;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 20px;
        }

        .form-header h2 {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
        }

        .form-header p {
            font-size: 14px;
            color: #475569;
            margin-top: 6px;
            font-weight: 500;
        }

        .avatar-preview-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            background: #F8FAFC;
            border: 2px dashed #CBD5E1;
            padding: 24px;
            border-radius: 12px;
            text-align: center;
        }

        .avatar-box {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            overflow: hidden;
            background: #E2E8F0;
            border: 4px solid #FFFFFF;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .avatar-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .avatar-box .placeholder-icon {
            font-size: 48px;
            color: #94A3B8;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1E293B;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #CBD5E1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            background: #FFFFFF;
            color: #0F172A;
            font-weight: 500;
            transition: all 0.15s ease;
            -webkit-appearance: none; /* Prevents default iOS input rendering issues */
        }

        input:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .custom-file-upload {
            display: inline-block;
            padding: 8px 16px;
            background: #0F172A;
            color: white;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.15s;
        }

        .custom-file-upload:hover {
            background: #1E293B;
        }

        input[type="file"] {
            display: none;
        }

        .row {
            display: flex;
            gap: 20px;
        }

        .row .form-group {
            flex: 1;
        }

        button[type="submit"] {
            width: 100%;
            padding: 14px;
            background: #0F172A;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease;
            margin-top: 12px;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.15);
            -webkit-appearance: none;
        }

        button[type="submit"]:hover {
            background: #1E293B;
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

        .alert-success {
            background: #D1FAE5;
            border: 2px solid #6EE7B7;
            color: #065F46;
            text-align: center;
        }

        .link-tray {
            text-align: center;
            margin-top: 24px;
        }

        .btn-link-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background: #FFFFFF;
            color: #334155;
            border: 2px solid #CBD5E1;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .btn-link-back:hover {
            background: #F8FAFC;
            border-color: #94A3B8;
            color: #0F172A;
        }

        /* Responsive Mobile Architecture Override Rules */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px;
            }

            .container {
                padding: 24px 16px;
                border-radius: 12px;
            }

            .form-header {
                margin-bottom: 24px;
                padding-bottom: 16px;
            }

            .form-header h2 {
                font-size: 20px;
            }

            .row {
                flex-direction: column;
                gap: 0;
            }

            .btn-link-back, button[type="submit"] {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <?php if ($success) { ?>

            <div class="alert alert-success">
                ✅ Student Registration Complete! Welcome, <strong><?php echo htmlspecialchars($student_name); ?></strong>.
            </div>
            <div class="link-tray">
                <p style="font-size: 14px; margin-bottom: 12px; color: #475569;">Assigned Hostel Roll: <span style="color:#2563EB; font-weight:700;"><?php echo htmlspecialchars($student_roll); ?></span></p>
                <a class="btn-link-back" href="students.php">⬅ Return to Student Registry</a>
            </div>

        <?php } else { ?>

            <div class="form-header">
                <h2>➕ New Admission Enrollment</h2>
                <p>Register new students into the system</p>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="avatar-preview-wrapper">
                    <div class="avatar-box" id="avatarBox">
                        <span class="placeholder-icon" id="avatarPlaceholder">👤</span>
                        <img id="avatarImg" src="#" alt="Student Preview">
                    </div>
                    <label for="photo" class="custom-file-upload">Choose Photo</label>
                    <input type="file" id="photo" name="photo" accept=".png, .jpg, .jpeg" onchange="previewIdentityPhoto(this)">
                    <span style="font-size:11px; color:#64748B; margin-top:6px; display:block;">Supported formats: JPG, JPEG, PNG</span>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Sonali Dehury" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label for="class">Class / Year</label>
                        <input type="text" id="class" name="class" placeholder="B.SC 2nd Year" value="<?php echo isset($_POST['class']) ? htmlspecialchars($_POST['class']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="department">Academic Department</label>
                        <input type="text" id="department" name="department" placeholder="Computer Science" value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="university_roll">University Roll Number</label>
                    <input type="text" id="university_roll" name="university_roll" placeholder="24DCS042" value="<?php echo isset($_POST['university_roll']) ? htmlspecialchars($_POST['university_roll']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="hostel_roll">Hostel Roll Number (Login ID)</label>
                    <input type="text" id="hostel_roll" name="hostel_roll" placeholder="415" style="text-transform: uppercase;" value="<?php echo isset($_POST['hostel_roll']) ? htmlspecialchars($_POST['hostel_roll']) : ''; ?>" required>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" maxlength="10" placeholder="10-digit mobile number" oninput="this.value = this.value.replace(/[^0-9]/g,'')" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="student@gmail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <label for="room_number">Allotted Room Number</label>
                        <input type="text" id="room_number" name="room_number" placeholder="324" value="<?php echo isset($_POST['room_number']) ? htmlspecialchars($_POST['room_number']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Account Access Password</label>
                        <input type="password" id="password" name="password" placeholder="Mix numbers and letters (Min 8 chars)" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Permanent Address</label>
                    <input type="text" id="address" name="address" placeholder="City, State, Zip Code" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required>
                </div>

                <button type="submit">Complete Enrollment Save</button>

            </form>

            <div class="link-tray" style="margin-top: 16px;">
                <a class="btn-link-back" style="padding: 10px 20px; font-size:13px;" href="dashboard.php">⬅ Exit to Dashboard</a>
            </div>

        <?php } ?>

    </div>

    <script>
        function previewIdentityPhoto(input) {
            const preview = document.getElementById('avatarImg');
            const placeholder = document.getElementById('avatarPlaceholder');
            const box = document.getElementById('avatarBox');

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    box.style.borderColor = '#2563EB';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
                placeholder.style.display = 'block';
                box.style.borderColor = '#FFFFFF';
            }
        }
    </script>
</body>

</html>