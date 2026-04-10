<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();
if (!isset($_SESSION['wizard'])) { redirect('pages/instructions.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    if (empty($_POST['days']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
        $_SESSION['error'] = "Select at least one day and valid operating hours.";
    } else {
        $_SESSION['wizard']['slots'] = [
            'days' => $_POST['days'],
            'start_time' => $_POST['start_time'], 
            'end_time' => $_POST['end_time'],
            'duration' => (int)$_POST['duration']
        ];
        redirect('pages/generate.php');
    }
}

require_once __DIR__ . '/../includes/header.php';

$def_days = $_SESSION['wizard']['slots']['days'] ?? ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$def_start = $_SESSION['wizard']['slots']['start_time'] ?? '09:00';
$def_end = $_SESSION['wizard']['slots']['end_time'] ?? '16:00';
$def_dur = $_SESSION['wizard']['slots']['duration'] ?? 60;
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-lg-8">
        <div class="mb-5 text-center">
            <h6 class="text-muted fw-bold tracking-wider text-uppercase mb-3">Step 5 of 6</h6>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: 83%;"></div>
            </div>
        </div>
        
        <div class="card p-5 shadow-sm border-0 rounded-4">
            <h3 class="fw-bold text-dark mb-4"><i class="fa-regular fa-calendar-days text-danger me-2"></i> Operating Windows</h3>
            
            <?php display_flash(); ?>
            <form method="POST" action="">
                <div class="mb-5 bg-light p-4 rounded border">
                    <label class="form-label fw-bold text-dark fs-5">Active Days of Week</label>
                    <div class="d-flex flex-wrap gap-4 mt-2">
                        <?php 
                        $all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach($all_days as $day): 
                            $checked = in_array($day, $def_days) ? 'checked' : '';
                        ?>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="days[]" value="<?= $day ?>" id="day_<?= $day ?>" <?= $checked ?>>
                            <label class="form-check-label fw-medium" for="day_<?= $day ?>"><?= $day ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fa-regular fa-clock me-1"></i> Start Time</label>
                        <input type="time" name="start_time" class="form-control form-control-lg bg-light border-0" value="<?= $def_start ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fa-solid fa-clock me-1"></i> End Time</label>
                        <input type="time" name="end_time" class="form-control form-control-lg bg-light border-0" value="<?= $def_end ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold"><i class="fa-solid fa-stopwatch me-1"></i> Slot Duration</label>
                        <select name="duration" class="form-select form-select-lg bg-light border-0" required>
                            <option value="45" <?= $def_dur==45 ? 'selected' : '' ?>>45 Minutes</option>
                            <option value="50" <?= $def_dur==50 ? 'selected' : '' ?>>50 Minutes</option>
                            <option value="60" <?= $def_dur==60 ? 'selected' : '' ?>>60 Minutes</option>
                            <option value="90" <?= $def_dur==90 ? 'selected' : '' ?>>90 Minutes</option>
                            <option value="120" <?= $def_dur==120 ? 'selected' : '' ?>>120 Minutes</option>
                        </select>
                    </div>
                </div>
                
                <div class="alert alert-primary border-primary border-opacity-25 bg-primary bg-opacity-10 text-dark">
                    <strong><i class="fa-solid fa-robot text-primary me-2"></i> Logic Note:</strong> Algorithm constraints will automatically slice the total duration into the specified slot segments and disperse the subjects.
                </div>

                <div class="d-flex justify-content-between border-top mt-4 pt-4">
                    <a href="<?= BASE_URL ?>pages/classes.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                    <button type="submit" name="submit" class="btn btn-primary fw-bold rounded-pill px-5 shadow-sm">Proceed to Algorithm <i class="fa-solid fa-gears ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
