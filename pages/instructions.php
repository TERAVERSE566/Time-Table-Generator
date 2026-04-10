<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $_SESSION['wizard'] = [
        'metadata' => [
            'title' => sanitize($_POST['title']),
            'department' => sanitize($_POST['department']),
            'semester' => sanitize($_POST['semester'])
        ],
        'subjects' => [],
        'faculty' => [],
        'rooms' => [],
        'slots' => []
    ];
    redirect('pages/subjects.php');
}

require_once __DIR__ . '/../includes/header.php';
$current_step = 1;
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-lg-8">
        <!-- Minimal Progress Indicator -->
        <div class="mb-5 text-center">
            <h6 class="text-muted fw-bold tracking-wider text-uppercase mb-3">Step 1 of 6</h6>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: 16%;"></div>
            </div>
        </div>
        
        <div class="card p-5 shadow-sm border-0 rounded-4">
            <h2 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-flag-checkered text-primary me-2"></i> Initial Configuration</h2>
            <p class="text-muted mb-4 fs-5">Provide the fundamental details for your timetable. This information creates the master record for your specific department and term.</p>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label fw-bold">Master Title</label>
                    <input type="text" name="title" class="form-control form-control-lg bg-light" required placeholder="e.g. CS Sophomore Term">
                </div>
                <div class="row mb-5">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-bold">Department Group</label>
                        <select name="department" class="form-select bg-light" required>
                            <option value="">Select Department...</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Information Tech">Information Tech</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Arts & Science">Arts & Science</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Active Semester</label>
                        <select name="semester" class="form-select bg-light" required>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="4">Semester 4</option>
                            <option value="5">Semester 5</option>
                            <option value="6">Semester 6</option>
                            <option value="7">Semester 7</option>
                            <option value="8">Semester 8</option>
                        </select>
                    </div>
                </div>
                <div class="text-end border-top pt-4">
                    <button type="submit" name="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">Proceed to Subjects <i class="fa-solid fa-chevron-right ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
