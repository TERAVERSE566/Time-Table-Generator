<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!isset($_SESSION['wizard'])) { redirect('pages/instructions.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $max_load = (int)$_POST['max_load'];
    $mapped_subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];

    if (empty($name) || empty($mapped_subjects)) {
        $_SESSION['error'] = "Name and at least one mapped subject are required.";
    } else {
        $_SESSION['wizard']['faculty'][] = [
            'id' => uniqid('fac_'),
            'name' => $name,
            'email' => $email,
            'max_load' => $max_load > 0 ? $max_load : 15,
            'subjects' => $mapped_subjects
        ];
        $_SESSION['success'] = "Faculty profile added securely.";
        redirect('pages/faculty.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $_SESSION['wizard']['faculty'] = array_filter($_SESSION['wizard']['faculty'], function($f) use ($id) { return $f['id'] !== $id; });
    $_SESSION['wizard']['faculty'] = array_values($_SESSION['wizard']['faculty']);
    $_SESSION['success'] = "Faculty removed.";
    redirect('pages/faculty.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-lg-10">
        <div class="mb-5 text-center">
            <h6 class="text-muted fw-bold tracking-wider text-uppercase mb-3">Step 3 of 6</h6>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: 50%;"></div>
            </div>
        </div>
        
        <div class="card p-4 shadow-sm border-0 rounded-4">
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-chalkboard-user text-success me-2"></i> Assign Faculty</h3>
            <p class="text-muted border-bottom pb-3">Register teachers and map them to the subjects they are capable of teaching.</p>
            
            <?php display_flash(); ?>
            <div class="row mt-3">
                <div class="col-md-5 border-end pe-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Faculty Name</label>
                            <input type="text" name="name" class="form-control bg-light" placeholder="e.g. Dr. Robert House" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contact Email</label>
                            <input type="email" name="email" class="form-control bg-light" placeholder="robert@inst.edu">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Weekly Load Horizon (Max Slots)</label>
                            <input type="number" name="max_load" class="form-control bg-light" value="15" min="1" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Map Core Competencies (Subjects)</label>
                            <select name="subjects[]" class="form-select bg-light" multiple required style="height: 120px;">
                                <?php if(empty($_SESSION['wizard']['subjects'])): ?>
                                    <option disabled>No subjects exist to map</option>
                                <?php else: ?>
                                    <?php foreach($_SESSION['wizard']['subjects'] as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= $s['code'] ?> - <?= $s['name'] ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <small class="text-xs text-muted d-block mt-1">Use CTRL / CMD to select multiple.</small>
                        </div>
                        <button type="submit" name="submit" class="btn btn-outline-success w-100 fw-bold rounded-pill shadow-sm"><i class="fa-solid fa-plus me-1"></i> Add Faculty</button>
                    </form>
                </div>
                
                <div class="col-md-7 ps-4">
                    <h5 class="fw-bold mb-3">Enrolled Faculty (<?= count($_SESSION['wizard']['faculty']) ?>)</h5>
                    <?php if(empty($_SESSION['wizard']['faculty'])): ?>
                        <div class="p-5 text-center text-muted border rounded bg-light">
                            <i class="fa-solid fa-users fs-1 mb-2"></i>
                            <p class="mb-0">Queue is currently empty.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border">
                                <thead class="table-light">
                                    <tr><th>Name</th><th>Capacity</th><th>Mappings</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($_SESSION['wizard']['faculty'] as $f): ?>
                                    <tr>
                                        <td class="fw-bold"><?= $f['name'] ?></td>
                                        <td><span class="badge bg-primary rounded-pill"><?= $f['max_load'] ?> slots</span></td>
                                        <td class="small text-muted fw-bold">
                                            <?php 
                                            // Map subject IDs back to their UI Codes
                                            $codes = [];
                                            foreach($f['subjects'] as $fid) {
                                                foreach($_SESSION['wizard']['subjects'] as $ws) {
                                                    if ($ws['id'] === $fid) $codes[] = $ws['code'];
                                                }
                                            }
                                            echo implode(", ", $codes);
                                            ?>
                                        </td>
                                        <td class="text-end"><a href="?delete=<?= $f['id'] ?>" class="btn btn-sm btn-danger rounded-circle shadow-sm"><i class="fa-solid fa-xmark"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between border-top mt-4 pt-4">
                <a href="<?= BASE_URL ?>pages/subjects.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                <?php if(count($_SESSION['wizard']['faculty']) > 0): ?>
                    <a href="<?= BASE_URL ?>pages/classes.php" class="btn btn-primary fw-bold rounded-pill px-5 shadow-sm">Proceed to Rooms <i class="fa-solid fa-chevron-right ms-1"></i></a>
                <?php else: ?>
                    <button class="btn btn-secondary fw-bold rounded-pill px-5" disabled>Proceed to Rooms <i class="fa-solid fa-chevron-right ms-1"></i></button>
                <?php endif; ?>            
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
