<?php
require_once 'includes/functions.php';
require_once 'config/db.php';
require_admin();
if (!isset($_SESSION['wizard'])) { redirect('wizard_step1.php'); }

$wiz = $_SESSION['wizard'];
$grid = [];
$generated = false;
$conflicts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_save'])) {
    
    // 1. Calculate Time Slots
    $start_ts = strtotime($wiz['slots']['start_time']);
    $end_ts = strtotime($wiz['slots']['end_time']);
    $duration = $wiz['slots']['duration'] * 60;
    
    $time_slots = [];
    for ($t = $start_ts; $t + $duration <= $end_ts; $t += $duration) {
        $time_slots[] = [
            'start' => date('H:i', $t),
            'end' => date('H:i', $t + $duration)
        ];
    }

    try {
        $pdo->beginTransaction();

        // 2. Insert Timetable Master
        $stmt_master = $pdo->prepare("INSERT INTO timetable_master (title, department, semester) VALUES (?, ?, ?)");
        $stmt_master->execute([$wiz['metadata']['title'], $wiz['metadata']['department'], $wiz['metadata']['semester']]);
        $tt_id = $pdo->lastInsertId();

        // 3. Insert Subjects & Build Map
        $sub_map = []; // session_id => db_id
        $stmt_sub = $pdo->prepare("INSERT INTO subjects (timetable_id, code, name, type, credits) VALUES (?, ?, ?, ?, ?)");
        foreach($wiz['subjects'] as $s) {
            $stmt_sub->execute([$tt_id, $s['code'], $s['name'], $s['type'], $s['credits']]);
            $sub_map[$s['id']] = $pdo->lastInsertId();
        }

        // 4. Insert Faculty & Build Map
        $fac_map = [];
        $stmt_fac = $pdo->prepare("INSERT INTO faculty (timetable_id, name, email, max_load) VALUES (?, ?, ?, ?)");
        foreach($wiz['faculty'] as $f) {
            $stmt_fac->execute([$tt_id, $f['name'], $f['email'], $f['max_load']]);
            $fac_map[$f['id']] = $pdo->lastInsertId();
        }

        // 5. Insert Rooms
        $room_map = [];
        $stmt_rm = $pdo->prepare("INSERT INTO classes (timetable_id, room_name, capacity) VALUES (?, ?, ?)");
        foreach($wiz['rooms'] as $r) {
            $stmt_rm->execute([$tt_id, $r['name'], $r['capacity']]);
            $room_map[$r['id']] = $pdo->lastInsertId();
        }

        // 6. Algorithm implementation (Save directly to DB)
        $stmt_sched = $pdo->prepare("INSERT INTO schedules (timetable_id, subject_id, faculty_id, class_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $faculty_busy = []; // [fac_db_id][day][time] = true
        $room_busy = [];    // [room_db_id][day][time] = true

        foreach($wiz['subjects'] as $s) {
            $credits_needed = $s['credits'];
            $s_db_id = $sub_map[$s['id']];
            
            // Find faculty who teaches this
            $f_db_id = null;
            $fac_name_cache = "TBA";
            foreach($wiz['faculty'] as $f) {
                if (in_array($s['id'], $f['subjects'])) {
                    $f_db_id = $fac_map[$f['id']];
                    $fac_name_cache = $f['name'];
                    break;
                }
            }

            if (!$f_db_id) {
                $conflicts[] = "No faculty assigned to teach " . $s['name'];
                continue; // Skip if no faculty
            }

            $assignments = 0;
            // Iterate over days and slots to find free spots
            foreach($wiz['slots']['days'] as $day) {
                foreach($time_slots as $ts) {
                    if ($assignments >= $credits_needed) break 2;
                    
                    $time_key = $ts['start'];

                    // Check Faculty Availability
                    if (isset($faculty_busy[$f_db_id][$day][$time_key])) continue;

                    // Find a free room
                    $assigned_room_db_id = null;
                    $room_name_cache = "";
                    foreach($wiz['rooms'] as $r) {
                        $r_db_id = $room_map[$r['id']];
                        if (!isset($room_busy[$r_db_id][$day][$time_key])) {
                            $assigned_room_db_id = $r_db_id;
                            $room_name_cache = $r['name'];
                            break;
                        }
                    }

                    if ($assigned_room_db_id) {
                        // Insert Schedule
                        $stmt_sched->execute([
                            $tt_id, $s_db_id, $f_db_id, $assigned_room_db_id, 
                            $day, $ts['start'], $ts['end']
                        ]);

                        // Mark as busy
                        $faculty_busy[$f_db_id][$day][$time_key] = true;
                        $room_busy[$assigned_room_db_id][$day][$time_key] = true;

                        // Save for UI Grid
                        $grid[$day][$ts['start']] = [
                            'subject' => $s['code'],
                            'faculty' => $fac_name_cache,
                            'room' => $room_name_cache
                        ];

                        $assignments++;
                    }
                }
            }

            if ($assignments < $credits_needed) {
                $conflicts[] = "Could not fit all classes for {$s['name']} - Not enough free rooms or slots.";
            }
        }

        $pdo->commit();
        $generated = true;
        // Optionally unset session if you want them to click "Done" to clear it.
        // unset($_SESSION['wizard']); 

    } catch (Exception $e) {
        $pdo->rollBack();
        $conflicts[] = "Database Error: " . $e->getMessage();
    }
}

require_once 'includes/header.php';
$current_step = 6;
?>

<div class="row justify-content-center mt-3">
    <div class="col-12">
        <?php include 'includes/wizard_nav.php'; ?>
        
        <?php if(!$generated): ?>
        <div class="card p-5 mt-4 text-center shadow-sm border-0">
            <h2 class="fw-bold mb-4">You're All Set!</h2>
            <div class="row justify-content-center mb-4">
                <div class="col-md-2 text-primary fs-3"><i class="fa-solid fa-book"></i><br><h6><?= count($wiz['subjects']) ?> Subjects</h6></div>
                <div class="col-md-2 text-success fs-3"><i class="fa-solid fa-user-tie"></i><br><h6><?= count($wiz['faculty']) ?> Faculty</h6></div>
                <div class="col-md-2 text-info fs-3"><i class="fa-solid fa-door-open"></i><br><h6><?= count($wiz['rooms']) ?> Rooms</h6></div>
            </div>
            <p class="text-muted">Click the button below to allow the system's scheduling algorithm to map out a conflict-free timetable.</p>
            <form method="POST">
                <button type="submit" name="generate_save" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow"><i class="fa-solid fa-gears me-2"></i> Generate Timetable</button>
            </form>
        </div>
        <?php else: ?>
        
        <!-- Generated Results View -->
        <div class="card p-4 mt-4 shadow border-0" id="timetable_document">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                <div>
                    <h3 class="fw-bold text-primary mb-0"><?= htmlspecialchars($wiz['metadata']['title']) ?></h3>
                    <small class="text-muted"><?= htmlspecialchars($wiz['metadata']['department']) ?> | Sem <?= htmlspecialchars($wiz['metadata']['semester']) ?></small>
                </div>
                <button onclick="exportPDF()" class="btn btn-outline-danger fw-bold rounded-pill" data-html2canvas-ignore><i class="fa-regular fa-file-pdf me-2"></i> Export PDF</button>
            </div>

            <?php if(count($conflicts) > 0): ?>
                <div class="alert alert-warning">
                    <strong><i class="fa-solid fa-triangle-exclamation"></i> Limitations met:</strong>
                    <ul class="mb-0">
                        <?php foreach($conflicts as $c): echo "<li>$c</li>"; endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-sm text-center align-middle" style="min-width: 800px;">
                    <thead class="table-primary">
                        <tr>
                            <th style="width: 100px;">Time \ Day</th>
                            <?php foreach($wiz['slots']['days'] as $d): echo "<th>$d</th>"; endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Re-calculate timeslots for UI rendering
                        $time_slots = [];
                        $start_ts = strtotime($wiz['slots']['start_time']);
                        for ($t = $start_ts; $t + ($wiz['slots']['duration']*60) <= strtotime($wiz['slots']['end_time']); $t += ($wiz['slots']['duration']*60)) {
                            $time_slots[] = date('H:i', $t);
                        }
                        
                        foreach($time_slots as $t): 
                        ?>
                        <tr>
                            <td class="fw-bold text-muted bg-light"><?= $t ?></td>
                            <?php foreach($wiz['slots']['days'] as $day): ?>
                                <?php if(isset($grid[$day][$t])): ?>
                                    <?php $cell = $grid[$day][$t]; ?>
                                    <td class="p-2" style="background-color: #eef2ff;">
                                        <div class="fw-bold text-dark"><?= $cell['subject'] ?></div>
                                        <span class="badge bg-secondary"><?= $cell['faculty'] ?></span><br>
                                        <small class="text-muted fw-bold"><i class="fa-solid fa-location-dot"></i> <?= $cell['room'] ?></small>
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

            <div class="text-center mt-4" data-html2canvas-ignore>
                <a href="index.php" class="btn btn-success rounded-pill px-4 fw-bold">Finish & Return Home</a>
            </div>
        </div>
        
        <script>
        function exportPDF() {
            const element = document.getElementById('timetable_document');
            const opt = {
              margin:       0.5,
              filename:     'Timetable.pdf',
              image:        { type: 'jpeg', quality: 0.98 },
              html2canvas:  { scale: 2 },
              jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save();
        }
        </script>
        
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
