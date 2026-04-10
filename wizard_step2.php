<?php
require_once 'includes/functions.php';
require_admin();

if (!isset($_SESSION['wizard'])) { redirect('wizard_step1.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subj = [
        'id' => uniqid('sub_'),
        'code' => sanitize($_POST['code']),
        'name' => sanitize($_POST['name']),
        'type' => sanitize($_POST['type']),
        'credits' => (int)$_POST['credits']
    ];
    $_SESSION['wizard']['subjects'][] = $subj;
    $_SESSION['success'] = "Subject added!";
    redirect('wizard_step2.php'); // prevent form resubmission
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_sub'])) {
    $id = $_GET['delete_sub'];
    $_SESSION['wizard']['subjects'] = array_filter($_SESSION['wizard']['subjects'], function($s) use ($id) { return $s['id'] !== $id; });
    $_SESSION['wizard']['subjects'] = array_values($_SESSION['wizard']['subjects']); // Reindex
    $_SESSION['success'] = "Subject removed.";
    redirect('wizard_step2.php');
}

require_once 'includes/header.php';
$current_step = 2;
?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-9">
        <?php include 'includes/wizard_nav.php'; ?>
        
        <div class="card p-4 mt-4 shadow-sm border-0">
            <h3 class="fw-bold"><i class="fa-solid fa-book text-warning me-2"></i> Add Subjects</h3>
            <hr>
            <?php display_flash(); ?>
            <div class="row">
                <!-- Add form -->
                <div class="col-md-4 border-end pe-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. CS-101" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Data Structures" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="Lecture">Lecture</option>
                                <option value="Practical">Practical (Lab)</option>
                                <option value="Tutorial">Tutorial</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Weekly Slots (Credits)</label>
                            <input type="number" name="credits" class="form-control" value="3" min="1" max="10" required>
                        </div>
                        <button type="submit" name="add_subject" class="btn btn-outline-primary w-100 fw-bold"><i class="fa-solid fa-plus me-1"></i> Add Subject</button>
                    </form>
                </div>
                <!-- Listing -->
                <div class="col-md-8 ps-4">
                    <h5 class="fw-bold mb-3">Added Subjects (<?= count($_SESSION['wizard']['subjects']) ?>)</h5>
                    <?php if(empty($_SESSION['wizard']['subjects'])): ?>
                        <div class="alert alert-light text-center py-4 border">No subjects added yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr><th>Code</th><th>Name</th><th>Type</th><th>Slots</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($_SESSION['wizard']['subjects'] as $s): ?>
                                    <tr>
                                        <td class="fw-bold text-muted"><?= $s['code'] ?></td>
                                        <td><?= $s['name'] ?></td>
                                        <td><span class="badge bg-secondary"><?= $s['type'] ?></span></td>
                                        <td><?= $s['credits'] ?></td>
                                        <td class="text-end"><a href="?delete_sub=<?= $s['id'] ?>" class="btn btn-sm btn-danger rounded-circle"><i class="fa-solid fa-trash"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between border-top mt-4 pt-3">
                <a href="wizard_step1.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                <a href="wizard_step3.php" class="btn btn-primary fw-bold rounded-pill px-4">Next: Add Faculty <i class="fa-solid fa-chevron-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
