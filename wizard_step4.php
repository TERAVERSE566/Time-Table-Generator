<?php
require_once 'includes/functions.php';
require_admin();
if (!isset($_SESSION['wizard'])) { redirect('wizard_step1.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $_SESSION['wizard']['rooms'][] = [
        'id' => uniqid('rm_'),
        'name' => sanitize($_POST['name']),
        'capacity' => (int)$_POST['capacity']
    ];
    $_SESSION['success'] = "Room added!";
    redirect('wizard_step4.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_rm'])) {
    $id = $_GET['delete_rm'];
    $_SESSION['wizard']['rooms'] = array_filter($_SESSION['wizard']['rooms'], function($r) use ($id) { return $r['id'] !== $id; });
    $_SESSION['wizard']['rooms'] = array_values($_SESSION['wizard']['rooms']);
    $_SESSION['success'] = "Room removed.";
    redirect('wizard_step4.php');
}

require_once 'includes/header.php';
$current_step = 4;
?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-8">
        <?php include 'includes/wizard_nav.php'; ?>
        
        <div class="card p-4 mt-4 shadow-sm border-0">
            <h3 class="fw-bold"><i class="fa-solid fa-door-open text-info me-2"></i> Add Classrooms</h3>
            <hr>
            <?php display_flash(); ?>
            <div class="row">
                <!-- Add form -->
                <div class="col-md-5 border-end pe-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Room Name/Number</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. LH-301" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Seating Capacity</label>
                            <input type="number" name="capacity" class="form-control" placeholder="60" required>
                        </div>
                        <button type="submit" name="add_room" class="btn btn-outline-info text-dark w-100 fw-bold"><i class="fa-solid fa-plus me-1"></i> Add Room</button>
                    </form>
                </div>
                <!-- Listing -->
                <div class="col-md-7 ps-4">
                    <h5 class="fw-bold mb-3">Available Rooms (<?= count($_SESSION['wizard']['rooms']) ?>)</h5>
                    <?php if(empty($_SESSION['wizard']['rooms'])): ?>
                        <div class="alert alert-light text-center py-4 border">No rooms added yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr><th>Room</th><th>Capacity</th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach($_SESSION['wizard']['rooms'] as $r): ?>
                                    <tr>
                                        <td class="fw-bold"><?= $r['name'] ?></td>
                                        <td><?= $r['capacity'] ?> seats</td>
                                        <td class="text-end"><a href="?delete_rm=<?= $r['id'] ?>" class="btn btn-sm btn-danger rounded-circle"><i class="fa-solid fa-trash"></i></a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-flex justify-content-between border-top mt-4 pt-3">
                <a href="wizard_step3.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                <a href="wizard_step5.php" class="btn btn-primary fw-bold rounded-pill px-4">Next: Time Slots <i class="fa-solid fa-chevron-right ms-1"></i></a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
