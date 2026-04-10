<?php
require_once 'includes/functions.php';
require_admin();
if (!isset($_SESSION['wizard'])) { redirect('wizard_step1.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
    // Collect mapped subjects (array of subject IDs)
    $mapped_subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];

    $_SESSION['wizard']['faculty'][] = [
        'id' => uniqid('fac_'),
        'name' => sanitize($_POST['name']),
        'email' => sanitize($_POST['email']),
        'max_load' => (int)$_POST['max_load'],
        'subjects' => $mapped_subjects
    ];
    $_SESSION['success'] = "Faculty added successfully!";
    redirect('wizard_step3.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_fac'])) {
    $id = $_GET['delete_fac'];
    $_SESSION['wizard']['faculty'] = array_filter($_SESSION['wizard']['faculty'], function($f) use ($id) { return $f['id'] !== $id; });
    $_SESSION['wizard']['faculty'] = array_values($_SESSION['wizard']['faculty']);
    $_SESSION['success'] = "Faculty removed.";
    redirect('wizard_step3.php');
}

require_once 'includes/header.php';
$current_step = 3;
?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-9">
        <?php include 'includes/wizard_nav.php'; ?>
        
        <div class="card p-4 mt-4 shadow-sm border-0">
            <h3 class="fw-bold"><i class="fa-solid fa-chalkboard-user text-success me-2"></i> Add Faculty</h3>
            <hr>
            <?php display_flash(); ?>
            <div class="row">
                <!-- Add form -->
                <div class="col-md-4 border-end pe-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Prof. Jane Doe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email (Optional)</label>
                            <input type="email" name="email" class="form-control" placeholder="jane.d@univ.edu">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Max Weekly Slots</label>
                            <input type="number" name="max_load" class="form-control" value="15" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Teaches Subjects</label>
                            <select name="subjects[]" class="form-select" multiple required style="height: 120px;">
                                <?php if(empty($_SESSION['wizard']['subjects'])): ?>
                                    <option disabled>No subjects added yet!</option>
                                <?php else: ?>
                                    <?php foreach($_SESSION['wizard']['subjects'] as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= $s['code'] ?> - <?= $s['name'] ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Hold CTRL to select multiple.</small>
                        </div>
                        <button type="submit" name="add_faculty" class="btn btn-outline-success w-100 fw-bold"><i class="fa-solid fa-plus me-1"></i> Add Faculty</button>
                    </form>
                </div>
                <!-- Listing -->
                <div class="col-md-8 ps-4">
                    <h5 class="fw-bold mb-3">Added Faculty (<?= count($_SESSION['wizard']['faculty']) ?>)</h5>
                    <?php if(empty($_SESSION['wizard']['faculty'])): ?>
                        <div class="alert alert-light text-center py-4 border">No faculty added yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr><th>Name</th><th>Max Load</th><th>Subjects Taught</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($_SESSION['wizard']['faculty'] as $f): ?>
                                    <tr>
                                        <td class="fw-bold"><?= $f['name'] ?></td>
                                        <td><?= $f['max_load'] ?> slots</td>
                                        <td class="small">
                                            <?php 
                                            $s_names = [];
                                            foreach($f['subjects'] as $sid) {
                                                foreach($_SESSION['wizard']['subjects'] as $ws) {
                                                    if ($ws['id'] === $sid) $s_names[] = $ws['code'];
                                                }
                                            }
                                            echo implode(", ", $s_names);
                                            ?>
                                        </td>
                                        <td class="text-end"><a href="?delete_fac=<?= $f['id'] ?>" class="btn btn-sm btn-danger rounded-circle"><i class="fa-solid fa-trash"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between border-top mt-4 pt-3">
                <a href="wizard_step2.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                <a href="wizard_step4.php" class="btn btn-primary fw-bold rounded-pill px-4">Next: Add Rooms <i class="fa-solid fa-chevron-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
