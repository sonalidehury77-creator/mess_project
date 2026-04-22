<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

$success = false;

/* =========================
   ADD STUDENT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $class = trim($_POST['class']);
    $department = trim($_POST['department']);
    $university_roll = trim($_POST['university_roll']);
    $hostel_roll = strtoupper(trim($_POST['hostel_roll']));
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $room = trim($_POST['room_number']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);

    /* VALIDATION */

    if (empty($name) || empty($hostel_roll) || empty($password)) {
        echo "<script>alert('Required fields missing');</script>";
    }

    /* DUPLICATE CHECK */

    $check = $conn->prepare("
        SELECT id 
        FROM student 
        WHERE hostel_roll=? OR email=?
    ");

    $check->bind_param("ss", $hostel_roll, $email);
    $check->execute();

    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo "<script>alert('Student already exists');</script>";
    } else {

        /* PASSWORD HASH */

        $hashed_password =
            password_hash($password, PASSWORD_DEFAULT);

        /* IMAGE UPLOAD */

        $photo = "";

        if (!empty($_FILES['photo']['name'])) {

            $ext =
                strtolower(
                    pathinfo(
                        $_FILES['photo']['name'],
                        PATHINFO_EXTENSION
                    )
                );

            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {

                echo "<script>alert('Only JPG, PNG allowed');</script>";
            } else {

                if (!is_dir("uploads")) {
                    mkdir("uploads");
                }

                $photo =
                    "uploads/student_" .
                    time() .
                    "." .
                    $ext;

                move_uploaded_file(
                    $_FILES['photo']['tmp_name'],
                    $photo
                );
            }
        }

        /* INSERT */

        $stmt = $conn->prepare("
            INSERT INTO student
            (name,class,department,
             university_roll,hostel_roll,
             phone,email,room_number,
             address,password,photo,status)
            VALUES
            (?,?,?,?,?,?,?,?,?,?,?,'active')
        ");

        $stmt->bind_param(
            "sssssssssss",
            $name,
            $class,
            $department,
            $university_roll,
            $hostel_roll,
            $phone,
            $email,
            $room,
            $address,
            $hashed_password,
            $photo
        );

        if ($stmt->execute()) {

            $success = true;
        } else {

            echo "<script>alert('Database Error');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Add Student</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: linear-gradient(135deg, #4facfe, #8e44ad);
            margin: 0;
        }

        /* CONTAINER */

        .container {

            width: 600px;
            margin: 40px auto;

            background: white;

            padding: 25px;

            border-radius: 15px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* HEADINGS */

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* FORM GROUP */

        .form-group {

            margin-bottom: 15px;
        }

        /* LABEL */

        label {

            font-weight: 600;

            display: block;

            margin-bottom: 5px;
        }

        /* INPUT */

        input {

            width: 100%;

            padding: 10px;

            border-radius: 8px;

            border: 1px solid #ccc;

            font-size: 14px;
        }

        /* GRID SYSTEM */

        .row {

            display: flex;

            gap: 15px;
        }

        .row .form-group {

            flex: 1;
        }

        /* BUTTON */

        button {

            width: 100%;

            margin-top: 20px;

            padding: 12px;

            background:
                linear-gradient(135deg, #ff416c, #ff4b2b);

            border: none;

            color: white;

            font-size: 16px;

            border-radius: 25px;

            cursor: pointer;

            transition: 0.3s;
        }

        button:hover {

            transform: scale(1.02);
        }

        /* SUCCESS */

        .success {

            background: #d4edda;

            padding: 12px;

            border-radius: 8px;

            text-align: center;

            margin-bottom: 15px;
        }

        /* BACK LINK */

        .back {

            display: block;

            text-align: center;

            margin-top: 10px;
        }
    </style>

</head>

<body>

    <div class="container">

        <h2>➕ Add Student Admission Form</h2>

        <?php if ($success) { ?>

            <div class="success">
                ✅ Student Added Successfully!
            </div>

            <a class="back"
                href="admin_students.php">

                ⬅ Back to Students List

            </a>

        <?php } else { ?>

            <form method="POST"
                enctype="multipart/form-data">

                <!-- PERSONAL INFO -->

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text"
                        name="name"
                        required>
                </div>

                <div class="row">

                    <div class="form-group">
                        <label>Class</label>
                        <input type="text"
                            name="class"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text"
                            name="department"
                            required>
                    </div>

                </div>

                <div class="form-group">
                    <label>University Roll</label>
                    <input type="text"
                        name="university_roll"
                        required>
                </div>

                <div class="form-group">
                    <label>Hostel Roll (Login ID)</label>
                    <input type="text"
                        name="hostel_roll"
                        required>
                </div>

                <div class="row">

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text"
                            name="phone"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                            name="email">
                    </div>

                </div>

                <div class="row">

                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text"
                            name="room_number"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password"
                            name="password"
                            required>
                    </div>

                </div>

                <div class="form-group">
                    <label>Address</label>
                    <input type="text"
                        name="address"
                        required>
                </div>

                <div class="form-group">
                    <label>Upload Photo</label>
                    <input type="file"
                        name="photo">
                </div>

                <button type="submit">

                    Save Student

                </button>

            </form>

        <?php } ?>

    </div>

</body>

</html>