<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];

    // Hardcoded SaaS admin for demonstration purposes. In real-world, fetch from PDO "users" table.
    $admin_email = "admin@timetablegen.com";
    $admin_pass_hash = password_hash("admin123", PASSWORD_DEFAULT);

    if ($email === $admin_email && password_verify($password, $admin_pass_hash)) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['success'] = "Authentication successful. Welcome!";
        redirect("pages/instructions.php");
    } else {
        $_SESSION['error'] = "Invalid credentials. Unauthorized access prevented.";
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5 mb-5 align-items-center" style="min-height: 60vh;">
    <div class="col-md-5">
        <div class="card p-5 shadow-lg border-0 border-top border-primary border-4" style="border-radius: 1rem;">
            <div class="text-center mb-4">
                <i class="fa-solid fa-shield-halved fs-1 text-primary mb-3"></i>
                <h3 class="fw-bold mb-1">Administrative Login</h3>
                <p class="text-muted">Enter credentials to access the SaaS generator.</p>
            </div>
            
            <?php display_flash(); ?>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">Account Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control border-start-0 ps-0" required placeholder="admin@timetablegen.com" value="admin@timetablegen.com">
                    </div>
                </div>
                <div class="mb-5">
                    <label class="form-label fw-bold text-dark">Secure Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-0" required placeholder="admin123" value="admin123">
                    </div>
                </div>
                <button type="submit" name="submit" class="btn btn-primary w-100 rounded-pill fs-5 shadow"><i class="fa-solid fa-right-to-bracket me-2"></i> Log In to Dashboard</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
