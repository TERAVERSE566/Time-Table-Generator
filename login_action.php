<?php
// login_action.php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $role = $conn->real_escape_string($_POST['role']);

    $stmt = $conn->prepare("SELECT id, name, password_hash, role, profile_photo FROM users WHERE email=? AND role=?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];
            
            // Redirect based on role
            if ($role === 'admin') {
                header("Location: admin.php");
            } else if ($role === 'faculty') {
                header("Location: facultyD.php");
            } else {
                header("Location: studentD.php");
            }
            exit();
        } else {
            // Invalid password
            header("Location: login.php?error=invalid_password");
            exit();
        }
    } else {
        // User not found or incorrect role
        header("Location: login.php?error=user_not_found");
        exit();
    }
}
?>

