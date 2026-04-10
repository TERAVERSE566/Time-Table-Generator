<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<div class="row align-items-center my-5 py-5">
    <div class="col-lg-6 mb-5 mb-lg-0">
        <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-3">Version 2.0 • SaaS Ready</span>
        <h1 class="display-4 fw-bold text-dark mb-4">
            Flawless Timetables <br><span class="text-primary">in Seconds.</span>
        </h1>
        <p class="lead text-muted mb-4">
            Simplify scheduling for your entire institution. An intelligent timetable management system built with modernized conflict-resolution logic. Say goodbye to spreadsheets.
        </p>
        <div class="d-flex gap-3">
            <?php if(isset($_SESSION['admin_auth'])): ?>
                <a href="wizard_step1.php" class="btn btn-primary btn-lg rounded-pill px-4 shadow">Launch Wizard <i class="fa-solid fa-arrow-right ms-2"></i></a>
            <?php else: ?>
                <a href="admin_login.php" class="btn btn-primary btn-lg rounded-pill px-4 shadow">Admin Login <i class="fa-solid fa-right-to-bracket ms-2"></i></a>
            <?php endif; ?>
            <a href="#features" class="btn btn-outline-secondary btn-lg rounded-pill px-4">See Features</a>
        </div>
    </div>
    <div class="col-lg-6 text-center">
        <!-- Dashboard Mockup Image Placeholder -->
        <div class="bg-white p-4 rounded-4 shadow-lg border">
            <h4 class="text-start border-bottom pb-2 mb-3 fw-bold"><i class="fa-regular fa-calendar-check text-success me-2"></i> CE-Semester 4 Schedule</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center">
                    <thead class="table-light">
                        <tr><th>Time</th><th>Mon</th><th>Tue</th><th>Wed</th></tr>
                    </thead>
                    <tbody>
                        <tr><td class="text-muted">09:00</td><td class="bg-primary bg-opacity-10">Math (Dr. A)</td><td>-</td><td class="bg-success bg-opacity-10">Phys (Dr. B)</td></tr>
                        <tr><td class="text-muted">10:00</td><td>-</td><td class="bg-warning bg-opacity-10">Chem (Dr. C)</td><td class="bg-primary bg-opacity-10">Math (Dr. A)</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-2">
                <span class="badge bg-success p-2"><i class="fa-solid fa-check me-1"></i> Zero Conflicts</span>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div id="features" class="py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold">Designed for Modern Academia</h2>
        <p class="text-muted">Everything you need to automate your institution's schedules.</p>
    </div>
    <div class="row g-4">
        <!-- Feature 1 -->
        <div class="col-md-4">
            <div class="card h-100 p-4 border-0 bg-white">
                <div class="text-primary mb-3"><i class="fa-solid fa-bolt fs-1"></i></div>
                <h4 class="fw-bold">Smart Algorithm</h4>
                <p class="text-muted">Automatically maps subjects, faculties, and rooms while completely avoiding time and room clashes.</p>
            </div>
        </div>
        <!-- Feature 2 -->
        <div class="col-md-4">
            <div class="card h-100 p-4 border-0 bg-white">
                <div class="text-primary mb-3"><i class="fa-solid fa-shoe-prints fs-1"></i></div>
                <h4 class="fw-bold">Step-by-Step UI</h4>
                <p class="text-muted">A beautiful 6-step wizard guides you to collect requirements before generating the grid.</p>
            </div>
        </div>
        <!-- Feature 3 -->
        <div class="col-md-4">
            <div class="card h-100 p-4 border-0 bg-white">
                <div class="text-primary mb-3"><i class="fa-solid fa-file-pdf fs-1"></i></div>
                <h4 class="fw-bold">PDF Export</h4>
                <p class="text-muted">Export your generated conflict-free grid into a neat print-ready PDF with a single click.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
