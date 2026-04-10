<?php
require_once 'includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize empty arrays
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
    redirect('wizard_step2.php');
}

require_once 'includes/header.php';
$current_step = 1;
?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-8">
        <?php include 'includes/wizard_nav.php'; ?>
        
        <div class="card p-5 mt-4">
            <h2 class="fw-bold mb-3"><i class="fa-solid fa-flag-checkered text-primary me-2"></i> Initial Configuration</h2>
            <p class="text-muted mb-4">Start by providing basic information about the timetable you are generating. The information will be stored securely in your temporary session until the final generation task.</p>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Timetable Title</label>
                    <input type="text" name="title" class="form-control form-control-lg" required placeholder="e.g., Computer Engineering Fall 2026">
                </div>
                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label fw-bold">Department</label>
                        <select name="department" class="form-select" required>
                            <option value="">Select...</option>
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Semester</label>
                        <select name="semester" class="form-select" required>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                            <option value="3">3rd Semester</option>
                            <option value="4">4th Semester</option>
                            <option value="5">5th Semester</option>
                            <option value="6">6th Semester</option>
                        </select>
                    </div>
                </div>
                <div class="text-end border-top pt-3">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Next: Add Subjects <i class="fa-solid fa-chevron-right ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
