<?php
session_start();
include("config/db_connect.php");

$success = false;
$student_name = "";
$student_roll = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* ======================
   GET DATA
====================== */

    $name = trim($_POST['name'] ?? '');
    $class = trim($_POST['class'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $university_roll = trim($_POST['university_roll'] ?? '');

    $hostel_roll =
        strtoupper(trim($_POST['hostel_roll'] ?? ''));

    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $room_number = trim($_POST['room_number'] ?? '');
    $address = trim($_POST['address'] ?? '');

    $password = trim($_POST['password'] ?? '');
    $confirm_password =
        trim($_POST['confirm_password'] ?? '');


    /* ======================
   VALIDATION
====================== */

    if (
        empty($name) ||
        empty($hostel_roll) ||
        empty($password)
    ) {

        echo "<script>
alert('⚠ Required fields missing');
window.location='register.html';
</script>";
        exit();
    }

    /* Password Strength */

    if (!preg_match(
        "/^(?=.*[A-Za-z])(?=.*\d).{6,}$/",
        $password
    )) {

        echo "<script>
alert('Password must contain letter & number (min 6 chars)');
window.location='register.html';
</script>";
        exit();
    }

    if ($password !== $confirm_password) {

        echo "<script>
alert('❌ Password mismatch');
window.location='register.html';
</script>";
        exit();
    }

    /* Email */

    if (
        !empty($email) &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        echo "<script>
alert('⚠ Invalid Email');
window.location='register.html';
</script>";
        exit();
    }

    /* Phone */

    if (
        !empty($phone) &&
        !preg_match("/^[0-9]{10}$/", $phone)
    ) {

        echo "<script>
alert('⚠ Invalid Phone Number');
window.location='register.html';
</script>";
        exit();
    }


    /* ======================
   CHECK DUPLICATES
====================== */

    $stmt = $conn->prepare(
        "SELECT id FROM student
 WHERE hostel_roll=? OR email=?"
    );

    $stmt->bind_param(
        "ss",
        $hostel_roll,
        $email
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        echo "<script>
alert('❌ Email or Hostel Roll already registered');
window.location='register.html';
</script>";
        exit();
    }


    /* ======================
   PASSWORD HASH
====================== */

    $hashed_password =
        password_hash($password, PASSWORD_DEFAULT);


    /* ======================
   PHOTO UPLOAD
====================== */

    $photo = "";

    if (
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] == 0
    ) {

        $allowed_ext =
            ['jpg', 'jpeg', 'png'];

        $file_name =
            $_FILES['photo']['name'];

        $file_tmp =
            $_FILES['photo']['tmp_name'];

        $file_size =
            $_FILES['photo']['size'];

        $ext =
            strtolower(
                pathinfo($file_name, PATHINFO_EXTENSION)
            );

        /* Extension Check */

        if (!in_array($ext, $allowed_ext)) {

            echo "<script>
alert('❌ Only JPG, JPEG, PNG allowed');
window.location='register.html';
</script>";
            exit();
        }

        /* Size Check */

        if (
            $file_size >
            2 * 1024 * 1024
        ) {

            echo "<script>
alert('❌ File too large (Max 2MB)');
window.location='register.html';
</script>";
            exit();
        }

        /* MIME Check */

        $finfo =
            finfo_open(FILEINFO_MIME_TYPE);

        $mime =
            finfo_file($finfo, $file_tmp);

        $allowed_mime =
            ['image/jpeg', 'image/png'];

        if (!in_array($mime, $allowed_mime)) {

            echo "<script>
alert('❌ Invalid image file');
window.location='register.html';
</script>";
            exit();
        }

        /* Create Folder */

        if (!is_dir("uploads")) {

            mkdir("uploads", 0777, true);
        }

        /* Unique Name */

        $new_name =
            uniqid("img_", true) . "." . $ext;

        $folder =
            "uploads/" . $new_name;

        /* Upload */

        if (move_uploaded_file(
            $file_tmp,
            $folder
        )) {

            $photo = $folder;
        } else {

            echo "<script>
alert('❌ Photo upload failed');
window.location='register.html';
</script>";
            exit();
        }
    }


    /* ======================
   INSERT DATA
====================== */

    $stmt = $conn->prepare(

        "INSERT INTO student

(name,class,department,
university_roll,

hostel_roll,phone,email,

room_number,address,

password,photo)

VALUES
(?,?,?,?,?,?,?,?,?,?,?)"

    );

    $stmt->bind_param(

        "sssssssssss",

        $name,
        $class,
        $department,
        $university_roll,

        $hostel_roll,
        $phone,
        $email,

        $room_number,
        $address,

        $hashed_password,
        $photo

    );

    if ($stmt->execute()) {

        $success = true;

        $student_name = $name;
        $student_roll = $hostel_roll;
    } else {

        echo "<script>
alert('❌ Registration Failed');
window.location='register.html';
</script>";

        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Registration Success</title>

    <style>
        body {

            font-family: Arial;

            background:
                linear-gradient(135deg, #667eea, #764ba2);

            height: 100vh;

            display: flex;

            justify-content: center;

            align-items: center;

        }

        .card {

            background: white;

            padding: 30px;

            width: 400px;

            border-radius: 15px;

            text-align: center;

            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);

        }

        .success {

            font-size: 50px;
            color: green;

        }

        .btn {

            display: inline-block;

            margin-top: 20px;

            padding: 12px 20px;

            background: #007bff;

            color: white;

            text-decoration: none;

            border-radius: 8px;

            font-size: 16px;

        }

        .info {

            margin-top: 10px;

            font-size: 15px;

            color: #555;

        }
    </style>

</head>

<body>

    <?php if ($success) { ?>

        <div class="card">

            <div class="success">✅</div>

            <h2>
                Welcome
                <?php echo htmlspecialchars($student_name); ?>
                🎉
            </h2>

            <p class="info">
                Your registration was successful.
            </p>

            <p class="info">

                <b>Hostel Roll:</b>

                <?php
                echo htmlspecialchars($student_roll);
                ?>

            </p>

            <p class="info">

                You can now login and start using
                the Hostel Mess System.

            </p>

            <a href="login.php" class="btn">

                🔐 Go to Login

            </a>

        </div>

    <?php } ?>

</body>

</html>