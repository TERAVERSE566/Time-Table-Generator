<?php
require_once 'includes/functions.php';
require_once 'config/db.php';
require_admin();

// Fetch saved timetables
$stmt = $pdo->query("SELECT * FROM timetable_master ORDER BY id DESC");
$timetables = $stmt->fetchAll();

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM timetable_master WHERE id = ?")->execute([$id]);
    $_SESSION['success'] = "Timetable deleted.";
    redirect('timetables.php');
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i> Saved Schedules</h2>
            <a href="wizard_step1.php" class="btn btn-primary rounded-pill fw-bold"><i class="fa-solid fa-plus me-1"></i> New Timetable</a>
        </div>
        
        <?php display_flash(); ?>

        <div class="card p-0 shadow-sm border-0 overflow-hidden">
            <?php if(empty($timetables)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="fa-solid fa-folder-open fs-1 mb-3 text-light"></i>
                    <h4>No timetables saved yet.</h4>
                    <p>Launch the wizard to generate your first optimal schedule.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Date Generated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($timetables as $t): ?>
                        <tr>
                            <td class="text-muted fw-bold">#<?= $t['id'] ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($t['title']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($t['department']) ?></span></td>
                            <td>Sem <?= htmlspecialchars($t['semester']) ?></td>
                            <td class="text-muted small"><?= date('M j, Y h:i A', strtotime($t['created_at'])) ?></td>
                            <td class="text-end">
                                <!-- Viewing logic would re-render the grid, for now just placeholder or delete -->
                                <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this schedule permanently?');"><i class="fa-solid fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
