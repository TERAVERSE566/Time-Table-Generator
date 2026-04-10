<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['error'] = "Registration is intentionally disabled in the SaaS framework preview. Please login via existing admin root.";
    redirect('auth/login.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-5 mb-5 align-items-center">
    <div class="col-md-5">
        <div class="card p-5 shadow-lg border-0 border-top border-warning border-4 rounded-4 text-center">
            <i class="fa-solid fa-id-card fs-1 text-warning mb-3"></i>
            <h3 class="fw-bold text-dark">Register Admin</h3>
            <p class="text-muted mb-4">Registration module placeholder.</p>
            
            <form method="POST" action="">
                <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold">Disable Attempt</button>
            </form>
            <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-link text-muted mt-3">Back to Login</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
