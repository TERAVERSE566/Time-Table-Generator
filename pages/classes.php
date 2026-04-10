<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!isset($_SESSION['wizard'])) { redirect('pages/instructions.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = sanitize($_POST['name']);
    $capacity = (int)$_POST['capacity'];

    if (empty($name) || $capacity <= 0) {
        $_SESSION['error'] = "Valid room id format and capacity is required.";
    } else {
        $_SESSION['wizard']['rooms'][] = [
            'id' => uniqid('rm_'),
            'name' => $name,
            'capacity' => $capacity
        ];
        $_SESSION['success'] = "Room added successfully.";
        redirect('pages/classes.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $_SESSION['wizard']['rooms'] = array_filter($_SESSION['wizard']['rooms'], function($r) use ($id) { return $r['id'] !== $id; });
    $_SESSION['wizard']['rooms'] = array_values($_SESSION['wizard']['rooms']);
    $_SESSION['success'] = "Room removed.";
    redirect('pages/classes.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-lg-10">
        <div class="mb-5 text-center">
            <h6 class="text-muted fw-bold tracking-wider text-uppercase mb-3">Step 4 of 6</h6>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: 66%;"></div>
            </div>
        </div>
        
        <div class="card p-4 shadow-sm border-0 rounded-4">
            <h3 class="fw-bold text-dark"><i class="fa-solid fa-door-open text-info me-2"></i> Register Classrooms</h3>
            <p class="text-muted border-bottom pb-3">Available physical space is the final component for the constraint engine.</p>
            
            <?php display_flash(); ?>
            <div class="row mt-3">
                <div class="col-md-5 border-end pe-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Room Name/ID</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Block A - 105" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Max Seating Capacity</label>
                            <input type="number" name="capacity" class="form-control" placeholder="60" required>
                        </div>
                        <button type="submit" name="submit" class="btn btn-outline-info text-dark w-100 fw-bold rounded-pill shadow-sm"><i class="fa-solid fa-plus me-1"></i> Add Room</button>
                    </form>
                </div>
                
                <div class="col-md-7 ps-4">
                    <h5 class="fw-bold mb-3">Active Room Pool (<?= count($_SESSION['wizard']['rooms']) ?>)</h5>
                    <?php if(empty($_SESSION['wizard']['rooms'])): ?>
                        <div class="p-5 text-center text-muted border rounded bg-light">
                            <i class="fa-solid fa-house fs-1 mb-2"></i>
                            <p class="mb-0">No rooms have been provided yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle border">
                                <thead class="table-light">
                                    <tr><th>Room ID</th><th>Seat Capacity</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($_SESSION['wizard']['rooms'] as $r): ?>
                                    <tr>
                                        <td class="fw-bold text-muted"><?= $r['name'] ?></td>
                                        <td><span class="badge bg-secondary"><?= $r['capacity'] ?> Students</span></td>
                                        <td class="text-end"><a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger rounded-circle shadow-sm"><i class="fa-solid fa-xmark"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between border-top mt-4 pt-4">
                <a href="<?= BASE_URL ?>pages/faculty.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                <?php if(count($_SESSION['wizard']['rooms']) > 0): ?>
                    <a href="<?= BASE_URL ?>pages/select_days.php" class="btn btn-primary fw-bold rounded-pill px-5 shadow-sm">Proceed to Time Config <i class="fa-solid fa-chevron-right ms-1"></i></a>
                <?php else: ?>
                    <button class="btn btn-secondary fw-bold rounded-pill px-5" disabled>Proceed to Time Config <i class="fa-solid fa-chevron-right ms-1"></i></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
