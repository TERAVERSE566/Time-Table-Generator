<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Table Generator Pro</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #2b3452;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        main {
            flex: 1;
        }
        /* Navbar Styling */
        .navbar-brand {
            font-weight: 700;
            color: #0d6efd !important;
            letter-spacing: -0.5px;
        }
        .navbar-nav .nav-link {
            font-weight: 500;
        }
        /* Footer Styling */
        footer {
            background-color: #ffffff;
            border-top: 1px solid #eaeaea;
            padding: 2rem 0;
            margin-top: 4rem;
        }
        /* Refined Buttons */
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        /* Beautiful Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3 mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fa-solid fa-calendar-days me-2"></i>TimetableGen</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navcol">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navcol">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if(isset($_SESSION['admin_auth'])): ?>
                <li class="nav-item"><a class="nav-link text-primary fw-bold" href="wizard_step1.php"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Launch Wizard</a></li>
                <li class="nav-item"><a class="nav-link" href="timetables.php">Saved Schedules</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['admin_auth'])): ?>
                    <span class="me-3 text-muted fw-bold">Hi, Admin</span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout <i class="fas fa-sign-out-alt ms-1"></i></a>
                <?php else: ?>
                    <a href="admin_login.php" class="btn btn-primary rounded-pill px-4">Admin Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container">
