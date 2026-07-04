<?php

/**
 * 📝 Hostel Management System - Student Registration Engine
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

// Redirect if student is already logged in
if (isset($_SESSION['hostel_roll'])) {
    header("Location: ../student/dashboard.php");
    exit();
}

// Generate an Anti-CSRF Token token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = false;
$student_name = "";
$student_roll = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 2. RUN CSRF SECURITY INTEGRITY CHECK
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Security error: Forms verification token mismatch.");
    }

    // 3. EXTRACT AND CLEAN INCOMING INPUT DATA
    $name            = ucwords(trim($_POST['name'] ?? '')); // Capitalizes words nicely
    $class           = trim($_POST['class'] ?? '');
    $department      = strtoupper(trim($_POST['department'] ?? ''));
    $university_roll = trim($_POST['university_roll'] ?? '');
    $hostel_roll     = strtoupper(trim($_POST['hostel_roll'] ?? ''));
    $phone           = trim($_POST['phone'] ?? '');
    $email           = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $room_number     = trim($_POST['room_number'] ?? '');
    $address         = trim($_POST['address'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 4. SERVER-SIDE FIELD RULES VALIDATION
    if (empty($name) || empty($hostel_roll) || empty($password) || empty($email)) {
        $error = "❌ Please complete all mandatory inputs marked as required.";
    } elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).{8,}$/", $password)) {
        $error = "❌ Weak Password: It must be at least 8 characters long and mix letters with numbers.";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email format.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "❌ Phone entry must contain exactly 10 digits.";
    }

    // 5. SECURE PICTURE FILE UPLOAD HANDLER
    $photo_path = "uploads/students/default.png"; // Fallback placeholder path
    if (empty($error) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed_ext  = ['jpg', 'jpeg', 'png'];
        $allowed_mime = ['image/jpeg', 'image/png'];

        $file_name    = $_FILES['photo']['name'];
        $file_tmp     = $_FILES['photo']['tmp_name'];
        $file_size    = $_FILES['photo']['size'];
        $ext          = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext, true)) {
            $error = "❌ Image type rejected. Please use a valid JPG, JPEG, or PNG format.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error = "❌ Image size limit exceeded. Upload must be under 2MB.";
        } else {
            // Read raw binary contents directly to check the real file MIME format type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file_tmp);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mime, true)) {
                $error = "❌ The image file contains corrupt or invalid data.";
            }
        }

        // Move valid photo out of temporary upload zones onto final disk directory paths
        if (empty($error)) {
            $target_dir = __DIR__ . "/../uploads/students/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true);
            }

            // Cryptographically unique signature names prevent collisions and URL guessing
            $new_name = bin2hex(random_bytes(16)) . "." . $ext;
            if (move_uploaded_file($file_tmp, $target_dir . $new_name)) {
                $photo_path = "uploads/students/" . $new_name;
            } else {
                $error = "❌ Failed to save the uploaded photo file on the hosting disk storage.";
            }
        }
    }

    // 6. DB ATOMIC TRANSACTION CONTROLS
    if (empty($error)) {
        try {
            // Establish an atomic workflow transaction boundary block
            $pdo->beginTransaction();

            // Check if student details already exist within data storage matrix ('FOR UPDATE' prevents race conditions)
            $checkStmt = $pdo->prepare("SELECT id FROM student WHERE hostel_roll = :hostel_roll OR email = :email LIMIT 1 FOR UPDATE");
            $checkStmt->execute(['hostel_roll' => $hostel_roll, 'email' => $email]);

            if ($checkStmt->fetch()) {
                $error = "❌ Account Conflict: This Hostel Roll or Email is already registered.";
                $pdo->rollBack();
            } else {
                // Cryptographic Engine Setup: Encrypt the profile hash key array via Argon2id standards
                $hashed_password = password_hash($password, PASSWORD_ARGON2ID);

                $insertStmt = $pdo->prepare("INSERT INTO student 
                    (name, class, department, university_roll, hostel_roll, phone, email, room_number, address, password, photo, status) 
                    VALUES (:name, :class, :department, :university_roll, :hostel_roll, :phone, :email, :room_number, :address, :password, :photo, 'active')");

                $insertStmt->execute([
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
                    'photo'           => $photo_path
                ]);

                $pdo->commit();
                $success = true;
                $student_name = $name;
                $student_roll = $hostel_roll;

                // Terminate form specific CSRF markers on success
                unset($_SESSION['csrf_token']);
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Database Registration Exception: " . $e->getMessage());
            $error = "⚠️ A critical system issue occurred while processing your account details.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex flex-col justify-center items-center p-6 antialiased">

    <div class="w-full max-w-xl bg-white border border-slate-200 p-8 rounded-3xl shadow-xl shadow-slate-200/50">

        <?php if ($success): ?>
            <div class="text-center py-6">
                <div class="text-6xl mb-4">✨</div>
                <h2 class="text-2xl font-bold text-teal-600 tracking-tight">Registration Complete</h2>
                <p class="text-slate-600 text-sm mt-2 max-w-sm mx-auto leading-relaxed">
                    Welcome to the system, <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($student_name); ?></span>. Your profile account configuration is now fully setup.
                </p>
                <div class="bg-slate-50 border border-slate-200 text-sm font-semibold p-4 rounded-xl my-6 inline-block tracking-wide">
                    Assigned Hostel Roll: <span class="text-indigo-600"><?php echo htmlspecialchars($student_roll); ?></span>
                </div>
                <a href="login.php" class="block w-full max-w-xs mx-auto bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3 rounded-xl transition-all shadow-md">
                    🔐 Proceed to Login Gateway
                </a>
            </div>
        <?php else: ?>

            <div class="mb-8">
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Create Student Account</h2>
                <p class="text-xs text-slate-500 font-medium mt-1">Please fill in your current academic records to register.</p>
            </div>

            <form id="registerForm" method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Full Name</label>
                    <input type="text" name="name" placeholder="John Doe" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Class / Year</label>
                        <input type="text" name="class" placeholder="B.Tech 3rd Year" value="<?php echo isset($_POST['class']) ? htmlspecialchars($_POST['class']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Department</label>
                        <input type="text" name="department" placeholder="CSE" value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">University Roll Number</label>
                        <input type="text" name="university_roll" placeholder="210110..." value="<?php echo isset($_POST['university_roll']) ? htmlspecialchars($_POST['university_roll']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Hostel Roll Number</label>
                        <input type="text" name="hostel_roll" placeholder="H-45" value="<?php echo isset($_POST['hostel_roll']) ? htmlspecialchars($_POST['hostel_roll']) : ''; ?>" required oninput="this.value = this.value.toUpperCase()" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Phone Number</label>
                        <input type="text" name="phone" id="phone" maxlength="10" placeholder="10-digit number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required oninput="this.value = this.value.replace(/[^0-9]/g,'')" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="name@domain.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Assigned Room Number</label>
                        <input type="text" name="room_number" placeholder="Room 302-B" value="<?php echo isset($_POST['room_number']) ? htmlspecialchars($_POST['room_number']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Permanent Address</label>
                        <input type="text" name="address" placeholder="City, State, Zip" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1 relative">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                        <input type="password" id="pass" name="password" placeholder="Min 8 characters" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                        <button type="button" id="toggleP" onclick="toggleField('pass', 'toggleP')" class="absolute right-4 top-8 text-sm select-none focus:outline-none">👁️</button>
                    </div>
                    <div class="space-y-1 relative">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Confirm Password</label>
                        <input type="password" id="cpass" name="confirm_password" placeholder="Re-enter password" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:border-indigo-600 focus:ring-4 focus:ring-indigo-50">
                        <button type="button" id="toggleCP" onclick="toggleField('cpass', 'toggleCP')" class="absolute right-4 top-8 text-sm select-none focus:outline-none">👁️</button>
                    </div>
                </div>

                <div class="bg-slate-50 p-4 border border-slate-200 rounded-2xl flex items-center gap-5">
                    <label class="bg-indigo-600 hover:bg-slate-900 text-white font-semibold text-xs py-2.5 px-4 rounded-xl cursor-pointer transition-colors shadow-sm">
                        <input type="file" name="photo" id="photoInput" accept="image/*" required class="hidden">
                        📷 Choose Profile Picture
                    </label>
                    <img id="preview" class="w-12 h-12 rounded-full border border-slate-300 object-cover hidden" alt="Thumbnail preview">
                </div>

                <button type="submit" id="submitBtn" class="w-full bg-slate-900 hover:bg-indigo-600 text-white font-semibold text-sm py-3.5 rounded-xl transition-colors shadow-md">
                    Register Account
                </button>
            </form>

            <?php if (!empty($error)): ?>
                <div class="bg-rose-50 border border-rose-100 text-rose-800 text-xs font-semibold p-3.5 rounded-xl mt-5 text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="mt-8 pt-5 border-t border-slate-100 text-center text-xs font-medium text-slate-500">
                Already have an active profile? <a href="login.php" class="text-indigo-600 font-semibold hover:underline">Login here</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleField(inputFieldId, clickTargetIndicatorId) {
            const field = document.getElementById(inputFieldId);
            const btn = document.getElementById(clickTargetIndicatorId);
            const isPass = field.type === "password";
            field.type = isPass ? "text" : "password";
            btn.textContent = isPass ? "🙈" : "👁️";
        }

        document.getElementById("photoInput").addEventListener("change", function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.startsWith("image/")) {
                    alert("❌ Please select a valid image file formatting.");
                    this.value = "";
                    return;
                }
                if (file.size > 2 * 1024 * 1024) {
                    alert("❌ File size limit hit. Image must be smaller than 2MB.");
                    this.value = "";
                    return;
                }
                const reader = new FileReader();
                reader.onload = () => {
                    const preview = document.getElementById("preview");
                    preview.src = reader.result;
                    preview.classList.remove("hidden");
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById("registerForm").addEventListener("submit", function(e) {
            const pass = document.getElementById("pass").value.trim();
            const cpass = document.getElementById("cpass").value.trim();
            const phone = document.getElementById("phone").value.trim();

            if (pass !== cpass) {
                alert("❌ Passwords do not match!");
                e.preventDefault();
                return;
            }
            if (!/(?=.*[A-Za-z])(?=.*\d).{8,}/.test(pass)) {
                alert("❌ Password must match rules: 8 characters minimum containing letters and numbers.");
                e.preventDefault();
                return;
            }
            if (!/^[0-9]{10}$/.test(phone)) {
                alert("❌ Phone number must contain exactly 10 numerical digits.");
                e.preventDefault();
                return;
            }

            const btn = document.getElementById("submitBtn");
            btn.disabled = true;
            btn.className = "w-full bg-slate-400 text-white font-semibold text-sm py-3.5 rounded-xl cursor-not-allowed text-center animate-pulse";
            btn.innerHTML = "Creating Account Profile...";
        });
    </script>
</body>

</html>