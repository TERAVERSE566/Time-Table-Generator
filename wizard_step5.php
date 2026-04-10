<?php
require_once 'includes/functions.php';
require_admin();
if (!isset($_SESSION['wizard'])) { redirect('wizard_step1.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['wizard']['slots'] = [
        'days' => $_POST['days'] ?? [],
        'start_time' => $_POST['start_time'], // e.g. 09:00
        'end_time' => $_POST['end_time'], // e.g. 17:00
        'duration' => (int)$_POST['duration'] // e.g. 60 mins
    ];
    redirect('wizard_step6.php');
}

require_once 'includes/header.php';
$current_step = 5;

// Default values
$def_days = $_SESSION['wizard']['slots']['days'] ?? ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$def_start = $_SESSION['wizard']['slots']['start_time'] ?? '09:00';
$def_end = $_SESSION['wizard']['slots']['end_time'] ?? '16:00';
$def_dur = $_SESSION['wizard']['slots']['duration'] ?? 60;
?>

<div class="row justify-content-center mt-3">
    <div class="col-lg-8">
        <?php include 'includes/wizard_nav.php'; ?>
        
        <div class="card p-5 mt-4 shadow-sm border-0">
            <h3 class="fw-bold"><i class="fa-regular fa-clock text-danger me-2"></i> Working Days & Time Slots</h3>
            <hr>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="form-label fw-bold">Select Working Days</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php 
                        $all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                        foreach($all_days as $day): 
                        ?>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="days[]" value="<?= $day ?>" id="day_<?= $day ?>" <?= in_array($day, $def_days) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="day_<?= $day ?>"><?= $day ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">College Start Time</label>
                        <input type="time" name="start_time" class="form-control" value="<?= $def_start ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">College End Time</label>
                        <input type="time" name="end_time" class="form-control" value="<?= $def_end ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Lecture Duration (Mins)</label>
                        <select name="duration" class="form-select" required>
                            <option value="45" <?= $def_dur==45 ? 'selected' : '' ?>>45 Minutes</option>
                            <option value="50" <?= $def_dur==50 ? 'selected' : '' ?>>50 Minutes</option>
                            <option value="60" <?= $def_dur==60 ? 'selected' : '' ?>>60 Minutes (1 Hour)</option>
                            <option value="90" <?= $def_dur==90 ? 'selected' : '' ?>>90 Minutes</option>
                        </select>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fa-solid fa-circle-info me-2"></i> The system will automatically divide the hours into slots based on the duration you selected.
                </div>

                <div class="d-flex justify-content-between border-top mt-4 pt-3">
                    <a href="wizard_step4.php" class="btn btn-light border fw-bold rounded-pill px-4"><i class="fa-solid fa-chevron-left me-1"></i> Back</a>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">Next: Review & Generate <i class="fa-solid fa-wand-magic-sparkles ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
