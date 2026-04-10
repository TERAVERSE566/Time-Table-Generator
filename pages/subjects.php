<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!isset($_SESSION['wizard'])) { redirect('pages/instructions.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $code = sanitize($_POST['code']);
    $name = sanitize($_POST['name']);
    $type = sanitize($_POST['type']);
    $credits = (int)$_POST['credits'];

    if (empty($code) || empty($name) || empty($type) || $credits <= 0) {
        $_SESSION['error'] = "All fields are required and valid credits must be assigned.";
    } else {
        $_SESSION['wizard']['subjects'][] = [
            'id' => uniqid('sub_'),
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'credits' => $credits
        ];
        $_SESSION['success'] = "Subject [$code] added successfully!";
        redirect('pages/subjects.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $_SESSION['wizard']['subjects'] = array_filter($_SESSION['wizard']['subjects'], function($s) use ($id) { return $s['id'] !== $id; });
    $_SESSION['wizard']['subjects'] = array_values($_SESSION['wizard']['subjects']);
    $_SESSION['success'] = "Subject removed from active queue.";
    redirect('pages/subjects.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-lg-10">
        <!-- Minimal Progress Indicator -->
        <div class="mb-5 text-center">
            <h6 class="text-muted fw-bold tracking-wider text-uppercase mb-3">Step 2 of 6</h6>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: 32%;"></div>
            </div>
        </div>
        
        <div class="card p-4 shadow-sm border-0 rounded-4">
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-book-open text-warning me-2"></i> Register Subjects</h3>
            <p class="text-muted border-bottom pb-3">Map out the courses that require scheduling.</p>
            
            <?php display_flash(); ?>
            <div class="row mt-3">
                <div class="col-md-5 border-end pe-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject Code</label>
                            <input type="text" name="code" class="form-control bg-light" placeholder="e.g. CS101" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Full Subject Name</label>
                            <input type="text" name="name" class="form-control bg-light" placeholder="Introduction to Programming" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="type" class="form-select bg-light" required>
                                <option value="Lecture">Lecture</option>
                                <option value="Lab">Practical / Lab</option>
                                <option value="Seminar">Seminar</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Weekly Slots (Credits)</label>
                            <input type="number" name="credits" class="form-control bg-light" value="3" min="1" max="10" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-outline-primary w-100 fw-bold rounded-pill shadow-sm"><i class="fa-solid fa-plus me-1"></i> Add to Queue</button>
                    </form>
                </div>
                
                <div class="col-md-7 ps-4">
                    <h5 class="fw-bold mb-3">Active Subjects Queue (<?= count($_SESSION['wizard']['subjects']) ?>)</h5>
                    <?php if(empty($_SESSION['wizard']['subjects'])): ?>
                        <div class="p-5 text-center text-muted border rounded bg-light">
                            <i class="fa-solid fa-shapes fs-1 mb-2"></i>
                            <p class="mb-0">No subjects registered yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border">
                                <thead class="table-light">
                                    <tr><th>Code</th><th>Name</th><th>Type</th><th>Slots</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($_SESSION['wizard']['subjects'] as $s): ?>
                                    <tr>
                                        <td class="fw-bold text-muted"><?= $s['code'] ?></td>
                                        <td class="fw-bold"><?= $s['name'] ?></td>
                                        <td><span class="badge bg-secondary"><?= $s['type'] ?></span></td>
                                        <td><?= $s['credits'] ?></td>
                                        <td class="text-end"><a href="?delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger rounded-circle shadow-sm"><i class="fa-solid fa-xmark"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between border-top mt-4 pt-4">
                <a href="<?= BASE_URL ?>pages/instructions.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                <?php if(count($_SESSION['wizard']['subjects']) > 0): ?>
                    <a href="<?= BASE_URL ?>pages/faculty.php" class="btn btn-primary fw-bold rounded-pill px-5 shadow-sm">Proceed to Faculty <i class="fa-solid fa-chevron-right ms-1"></i></a>
                <?php else: ?>
                    <button class="btn btn-secondary fw-bold rounded-pill px-5" disabled>Proceed to Faculty <i class="fa-solid fa-chevron-right ms-1"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
