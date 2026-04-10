<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // Hardcoded simple admin check for demonstration or fetch from DB user table
    // For SaaS security demonstration:
    $admin_email = "admin@timetablegen.com";
    $admin_pass_hash = password_hash("admin123", PASSWORD_DEFAULT);

    if ($email === $admin_email && password_verify($password, $admin_pass_hash)) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['success'] = "Logged in successfully!";
        redirect("wizard_step1.php");
    } else {
        $_SESSION['error'] = "Invalid email or password.";
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card p-4">
            <h3 class="text-center fw-bold mb-4">Admin Access</h3>
            <?php display_flash(); ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Email address</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@timetablegen.com" value="admin@timetablegen.com">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="admin123" value="admin123">
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill fs-5">Sign In</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
