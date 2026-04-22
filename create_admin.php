<?php

include("config/db_connect.php");

$username = "admin";
$password_plain = "admin123";
$password = password_hash($password_plain, PASSWORD_DEFAULT);

/* CHECK IF ADMIN EXISTS */
$check = $conn->prepare("SELECT id FROM admin WHERE username=?");
$check->bind_param("s", $username);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    echo "⚠ Admin already exists. No action taken.";

} else {

    $stmt = $conn->prepare("
        INSERT INTO admin (username, password)
        VALUES (?, ?)
    ");

    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();

    echo "✅ Admin Created Successfully!";
}

?>