<?php
// register_action.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $conn->real_escape_string($_POST['role']);
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $phone = isset($_POST['phone']) ? $conn->real_escape_string($_POST['phone']) : NULL;
    
    // Role specific fields
    $dept = isset($_POST['department']) ? $conn->real_escape_string($_POST['department']) : NULL;
    $program = isset($_POST['program_level']) ? $conn->real_escape_string($_POST['program_level']) : NULL;

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: register.php?error=email_exists");
        exit();
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password_hash, role, phone, department, program_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $fullname, $email, $hash, $role, $phone, $dept, $program);
    
    if ($stmt->execute()) {
        header("Location: login.php?success=registered");
        exit();
    } else {
        header("Location: register.php?error=server_error");
        exit();
    }
}
?>

