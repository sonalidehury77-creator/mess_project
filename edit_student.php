<?php
session_start();
include("config/db_connect.php");

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit();
}

/* =========================
   GET ID SAFELY
========================= */

if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$id = intval($_GET['id']);

/* =========================
   FETCH STUDENT
========================= */

$stmt = $conn->prepare("SELECT * FROM student WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Student not found");
}

$data = $result->fetch_assoc();

/* =========================
   UPDATE STUDENT
========================= */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $class = trim($_POST['class']);
    $department = trim($_POST['department']);
    $university_roll = trim($_POST['university_roll']);
    $hostel_roll = strtoupper(trim($_POST['hostel_roll']));
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $room_number = trim($_POST['room_number']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);

    /* =========================
       DUPLICATE CHECK
    ========================= */

    $check = $conn->prepare("
        SELECT id FROM student 
        WHERE (hostel_roll=? OR email=?) 
        AND id != ?
    ");

    $check->bind_param("ssi", $hostel_roll, $email, $id);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {

        echo "<script>
        alert('Hostel Roll or Email already exists');
        window.history.back();
        </script>";

        exit();
    }

    /* =========================
       PASSWORD UPDATE
    ========================= */

    if (!empty($password)) {

        if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d).{6,}$/", $password)) {

            echo "<script>
            alert('Password must contain letters and numbers');
            window.history.back();
            </script>";

            exit();
        }

        $hashed_password =
            password_hash($password, PASSWORD_DEFAULT);
    } else {

        $hashed_password =
            $data['password'];
    }

    /* =========================
       PHOTO UPDATE
    ========================= */

    $photo = $data['photo'];

    if (!empty($_FILES['photo']['name'])) {

        $ext =
            strtolower(
                pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION)
            );

        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {

            echo "<script>
            alert('Only JPG, JPEG, PNG allowed');
            window.history.back();
            </script>";

            exit();
        }

        if (!is_dir("uploads")) {
            mkdir("uploads");
        }

        $new_photo =
            "uploads/student_" . time() . "." . $ext;

        move_uploaded_file(
            $_FILES['photo']['tmp_name'],
            $new_photo
        );

        /* DELETE OLD PHOTO */

        if (!empty($photo) && file_exists($photo)) {
            unlink($photo);
        }

        $photo = $new_photo;
    }

    /* =========================
       UPDATE QUERY
    ========================= */

    $stmt = $conn->prepare("
        UPDATE student SET
            name=?,
            class=?,
            department=?,
            university_roll=?,
            hostel_roll=?,
            phone=?,
            email=?,
            room_number=?,
            address=?,
            password=?,
            photo=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sssssssssssi",
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
        $photo,
        $id
    );

    if ($stmt->execute()) {

        echo "<script>
        alert('Student updated successfully');
        window.location='admin_students.php';
        </script>";

        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Edit Student</title>

    <style>
        body {
            font-family: 'Segoe UI';
            background: linear-gradient(135deg, #4facfe, #8e44ad);
        }

        /* CONTAINER */

        .container {

            width: 700px;
            margin: 40px auto;

            background: white;

            padding: 30px;

            border-radius: 15px;

            box-shadow:
                0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* TITLE */

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* FORM GRID */

        .form-grid {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 15px;
        }

        /* FULL WIDTH */

        .full-width {
            grid-column: span 2;
        }

        /* LABEL */

        label {
            font-weight: 600;
        }

        /* INPUT */

        input {

            width: 100%;

            padding: 10px;

            border-radius: 8px;

            border: 1px solid #ccc;
        }

        /* IMAGE */

        img {

            width: 90px;

            height: 90px;

            border-radius: 50%;

            object-fit: cover;

            margin-top: 10px;
        }

        /* BUTTON */

        button {

            width: 100%;

            padding: 12px;

            background: linear-gradient(135deg, #007bff, #00c6ff);

            border: none;

            color: white;

            border-radius: 25px;

            font-size: 16px;

            cursor: pointer;

        }

        /* BACK */

        .back-btn {

            margin-top: 15px;

            display: block;

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

    <div class="container">

        <h2>✏ Edit Student</h2>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-grid">

                <div>
                    <label>Name</label>
                    <input type="text"
                        name="name"
                        value="<?= htmlspecialchars($data['name']) ?>"
                        required>
                </div>

                <div>
                    <label>Class</label>
                    <input type="text"
                        name="class"
                        value="<?= htmlspecialchars($data['class']) ?>"
                        required>
                </div>

                <div>
                    <label>Department</label>
                    <input type="text"
                        name="department"
                        value="<?= htmlspecialchars($data['department']) ?>"
                        required>
                </div>

                <div>
                    <label>University Roll</label>
                    <input type="text"
                        name="university_roll"
                        value="<?= htmlspecialchars($data['university_roll']) ?>"
                        required>
                </div>

                <div>
                    <label>Hostel Roll</label>
                    <input type="text"
                        name="hostel_roll"
                        value="<?= htmlspecialchars($data['hostel_roll']) ?>"
                        required>
                </div>

                <div>
                    <label>Phone</label>
                    <input type="text"
                        name="phone"
                        value="<?= htmlspecialchars($data['phone']) ?>"
                        required>
                </div>

                <div>
                    <label>Email</label>
                    <input type="email"
                        name="email"
                        value="<?= htmlspecialchars($data['email']) ?>">
                </div>

                <div>
                    <label>Room Number</label>
                    <input type="text"
                        name="room_number"
                        value="<?= htmlspecialchars($data['room_number']) ?>"
                        required>
                </div>

                <div class="full-width">
                    <label>Address</label>
                    <input type="text"
                        name="address"
                        value="<?= htmlspecialchars($data['address']) ?>"
                        required>
                </div>

                <div class="full-width">
                    <label>New Password (optional)</label>
                    <input type="password"
                        name="password"
                        placeholder="Leave blank to keep old password">
                </div>

                <div>
                    <label>Current Photo</label><br>

                    <?php if (!empty($data['photo'])) { ?>

                        <img src="<?= $data['photo'] ?>">

                    <?php } else { ?>

                        <img src="default.png">

                    <?php } ?>

                </div>

                <div>
                    <label>Change Photo</label>
                    <input type="file" name="photo">
                </div>

                <div class="full-width">
                    <button type="submit">
                        💾 Update Student
                    </button>
                </div>

            </div>

        </form>

        <a href="admin_students.php"
            class="back-btn">

            ⬅ Back to Students

        </a>

    </div>

</body>

</html>