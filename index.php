<?php
session_start();

// Central entry point for routing and sessions
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit();
}

$role = $_SESSION['user_role'] ?? '';

// Redirect to respective dashboard based on role
if ($role === 'admin') {
    header("Location: admin.php");
} elseif ($role === 'faculty') {
    header("Location: facultyD.php");
} elseif ($role === 'student') {
    header("Location: studentD.php");
} else {
    session_destroy();
    header("Location: login.php");
}
exit();
