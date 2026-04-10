<?php
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetablePro</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Extracted External CSS safely resolved -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-calendar-check me-2 text-primary"></i>TimetablePro</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navcol"><span class="navbar-toggler-icon"></span></button>
        
        <div class="collapse navbar-collapse" id="navcol">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>index.php">Overview</a></li>
                <?php if(isset($_SESSION['admin_auth'])): ?>
                <li class="nav-item"><a class="nav-link text-primary fw-bold" href="<?= BASE_URL ?>pages/instructions.php"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Dashboard</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <?php if(isset($_SESSION['admin_auth'])): ?>
                    <span class="me-3 text-muted fw-bold">Admin User</span>
                    <a href="<?= BASE_URL ?>auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3">Logout <i class="fas fa-sign-out-alt ms-1"></i></a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary rounded-pill px-4 shadow-sm">Secure Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="container my-4">
