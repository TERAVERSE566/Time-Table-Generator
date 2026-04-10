<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="row align-items-center my-5 py-5">
    <div class="col-lg-6 mb-5 mb-lg-0 pe-lg-5">
        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 mb-3 border border-primary"><i class="fa-solid fa-rocket me-1"></i> SaaS Framework v3.0</span>
        <h1 class="display-4 fw-bold text-dark mb-4" style="line-height: 1.2;">
            Intelligent Timetables <br><span class="text-primary">Without Conflicts.</span>
        </h1>
        <p class="lead text-muted mb-4 fs-5 p-0">
            A comprehensive, conflict-free academic scheduling platform. Leverage advanced matrix-constraint algorithms to completely automate your institution's weekly timetables.
        </p>
        <div class="d-flex gap-3 mt-4">
            <?php if(isset($_SESSION['admin_auth'])): ?>
                <a href="<?= BASE_URL ?>pages/instructions.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow">Dashboard <i class="fa-solid fa-arrow-right ms-2"></i></a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow">Admin Login <i class="fa-solid fa-right-to-bracket ms-2"></i></a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-6">
        <!-- Dashboard Mockup Image Placeholder -->
        <div class="bg-white p-4 rounded-4 shadow-lg border" style="transform: perspective(1000px) rotateY(-5deg) rotateX(2deg);">
            <div class="d-flex justify-content-between border-bottom pb-2 mb-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-layer-group text-primary me-2"></i> Generated Grid</h5>
                <span class="badge bg-success"><i class="fa-solid fa-check"></i> 100% Conflict Free</span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center align-middle" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr><th>Time</th><th>Mon</th><th>Tue</th><th>Wed</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="text-muted fw-bold">09:00</td><td class="subj-card">CS-101<br><small>Dr. Smith</small></td><td class="text-muted">--</td><td class="subj-card">MT-202<br><small>Dr. Jane</small></td></tr>
                        <tr><td class="text-muted fw-bold">10:00</td><td class="text-muted">--</td><td class="subj-card">PHY-301<br><small>Dr. Cole</small></td><td class="subj-card">CS-101<br><small>Dr. Smith</small></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3 border-top pt-2">
                <button class="btn btn-sm btn-outline-danger px-4 rounded-pill"><i class="fa-regular fa-file-pdf"></i> PDF</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
