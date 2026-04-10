<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM timetable_master WHERE id = ?")->execute([(int)$_GET['delete']]);
    $_SESSION['success'] = "Timetable deleted.";
    redirect('pages/timetable.php');
}

// Global List View if no specific ID requested
if (!isset($_GET['id'])) {
    
    $stmt = $pdo->query("SELECT * FROM timetable_master ORDER BY id DESC");
    $timetables = $stmt->fetchAll();
    
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="row justify-content-center mt-4 mb-5">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i> Saved Schedules</h2>
                <a href="<?= BASE_URL ?>pages/instructions.php" class="btn btn-primary rounded-pill fw-bold shadow-sm"><i class="fa-solid fa-plus me-1"></i> New Generator</a>
            </div>
            
            <?php display_flash(); ?>
            <div class="card p-0 shadow-sm border-0 overflow-hidden rounded-4">
                <?php if(empty($timetables)): ?>
                    <div class="p-5 text-center text-muted"> <i class="fa-solid fa-folder-open fs-1 mb-3 text-light"></i> <h4>No timetables saved yet.</h4> </div>
                <?php else: ?>
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>ID</th><th>Title</th><th>Department</th><th>Semester</th><th>Date Generated</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($timetables as $t): ?>
                            <tr>
                                <td class="text-muted fw-bold">#<?= $t['id'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($t['title']) ?></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill"><?= htmlspecialchars($t['department']) ?></span></td>
                                <td>Sem <?= htmlspecialchars($t['semester']) ?></td>
                                <td class="text-muted small"><?= date('M j, Y h:i A', strtotime($t['created_at'])) ?></td>
                                <td class="text-end">
                                    <a href="?id=<?= $t['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm"><i class="fa-solid fa-eye me-1"></i> View Grid</a>
                                    <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger btn-pill rounded-circle ms-1" onclick="return confirm('Delete permanently?');"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}


// SPECIFIC GRID RENDERER ($id requested)
$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM timetable_master WHERE id = ?");
$stmt->execute([$id]);
$master = $stmt->fetch();

if(!$master) { $_SESSION['error'] = "Invalid sequence requested."; redirect('pages/timetable.php'); }

// Get Raw Schedules
$stmt_sched = $pdo->prepare("
    SELECT s.day_of_week, s.start_time, s.end_time,
           sub.code as subject_code, sub.name as subject_name,
           fac.name as faculty_name, c.room_name 
    FROM schedules s
    JOIN subjects sub ON s.subject_id = sub.id
    JOIN faculty fac ON s.faculty_id = fac.id
    JOIN classes c ON s.class_id = c.id
    WHERE s.timetable_id = ?
");
$stmt_sched->execute([$id]);
$raw_schedules = $stmt_sched->fetchAll();

// Compile UI Matrix => $grid[day][startTime] = block details
$grid = [];
$days_found = [];
$times_found = [];
foreach($raw_schedules as $rs) {
    if(!in_array($rs['day_of_week'], $days_found)) $days_found[] = $rs['day_of_week'];
    $st = date('H:i', strtotime($rs['start_time']));
    if(!in_array($st, $times_found)) $times_found[] = $st;
    
    $grid[$rs['day_of_week']][$st] = [
        'subject' => $rs['subject_code'],
        'faculty' => $rs['faculty_name'],
        'room' => $rs['room_name']
    ];
}
// Sort days and times
$day_order = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6,'Sunday'=>7];
usort($days_found, function($a,$b) use ($day_order) { return $day_order[$a] <=> $day_order[$b]; });
sort($times_found);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="<?= BASE_URL ?>pages/timetable.php" class="btn btn-outline-secondary fw-bold rounded-pill px-4 shadow-sm"><i class="fa-solid fa-arrow-left me-1"></i> Exit Grid</a>
            <button onclick="exportTimetablePDF('exportArea')" class="btn btn-danger fw-bold rounded-pill px-4 shadow-sm"><i class="fa-solid fa-file-pdf me-2"></i> Export PDF</button>
        </div>
        
        <?php display_flash(); ?>

        <!-- EXPLOITABLE PDF WRAPPER -->
        <div class="card p-5 shadow-lg border-0 rounded-4 timetable-grid" id="exportArea">
            <div class="text-center border-bottom pb-4 mb-4">
                <i class="fa-solid fa-calendar-check fs-1 text-primary mb-3"></i>
                <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($master['title']) ?></h2>
                <h5 class="text-muted"><?= htmlspecialchars($master['department']) ?> • Semester <?= htmlspecialchars($master['semester']) ?></h5>
            </div>

            <?php if(empty($grid)): ?>
                <div class="alert alert-warning text-center">Algorithm generated zero valid blocks for this permutation.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle text-center" style="min-width: 800px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 120px;" class="fw-bold text-primary">Time \ Day</th>
                                <?php foreach($days_found as $d): echo "<th class='fw-bold text-dark fs-5'>$d</th>"; endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($times_found as $t): ?>
                            <tr>
                                <td class="fw-bold text-muted bg-light fs-5"><?= $t ?></td>
                                <?php foreach($days_found as $day): ?>
                                    <?php if(isset($grid[$day][$t])): ?>
                                        <?php $cell = $grid[$day][$t]; ?>
                                        <td class="p-3">
                                            <div class="subj-card">
                                                <div class="subj-title"><?= $cell['subject'] ?></div>
                                                <div class="subj-sub"><i class="fa-solid fa-user-tie border-end pe-1 me-1"></i><?= $cell['faculty'] ?></div>
                                                <div class="subj-sub"><i class="fa-solid fa-location-dot border-end pe-1 me-1"></i><?= $cell['room'] ?></div>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <td class="text-muted"><small>-- Free --</small></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
