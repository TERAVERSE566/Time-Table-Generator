<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!isset($_SESSION['wizard'])) { redirect('pages/instructions.php'); }

$wiz = $_SESSION['wizard'];
$conflicts = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    
    // 1. Calculate the available time slots Matrix
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

        // Save Master Record
        $stmt_master = $pdo->prepare("INSERT INTO timetable_master (title, department, semester) VALUES (?, ?, ?)");
        $stmt_master->execute([$wiz['metadata']['title'], $wiz['metadata']['department'], $wiz['metadata']['semester']]);
        $tt_id = $pdo->lastInsertId();

        // Mappings
        $sub_map = []; $fac_map = []; $room_map = [];
        
        $stmt_sub = $pdo->prepare("INSERT INTO subjects (timetable_id, code, name, type, credits) VALUES (?, ?, ?, ?, ?)");
        foreach($wiz['subjects'] as $s) {
            $stmt_sub->execute([$tt_id, $s['code'], $s['name'], $s['type'], $s['credits']]);
            $sub_map[$s['id']] = $pdo->lastInsertId();
        }

        $stmt_fac = $pdo->prepare("INSERT INTO faculty (timetable_id, name, email, max_load) VALUES (?, ?, ?, ?)");
        foreach($wiz['faculty'] as $f) {
            $stmt_fac->execute([$tt_id, $f['name'], $f['email'], $f['max_load']]);
            $fac_map[$f['id']] = $pdo->lastInsertId();
        }

        $stmt_rm = $pdo->prepare("INSERT INTO classes (timetable_id, room_name, capacity) VALUES (?, ?, ?)");
        foreach($wiz['rooms'] as $r) {
            $stmt_rm->execute([$tt_id, $r['name'], $r['capacity']]);
            $room_map[$r['id']] = $pdo->lastInsertId();
        }

        // ALGORITHM: Conflict-Free Scheduling (Constraint Satisfaction Approach)
        $faculty_schedule = []; // $faculty_schedule[fac_db_id][day][time] = true
        $room_schedule = [];    // $room_schedule[room_db_id][day][time] = true
        $class_schedule = [];   // Global blocker for this specific cohort/batch overlapping itself
        
        $stmt_sched = $pdo->prepare("INSERT INTO schedules (timetable_id, subject_id, faculty_id, class_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach($wiz['subjects'] as $s) {
            $credits_to_fill = $s['credits'];
            $s_db_id = $sub_map[$s['id']];
            
            // Find applicable faculty
            $f_db_id = null;
            foreach($wiz['faculty'] as $f) {
                if (in_array($s['id'], $f['subjects'])) {
                    $f_db_id = $fac_map[$f['id']];
                    break;
                }
            }

            if (!$f_db_id) {
                $conflicts[] = "Constraint Failure: No authorized faculty found for subject " . $s['name'];
                continue; 
            }

            $allocations = 0;
            $max_retryloops = 2; // Simple backtrack mechanism

            for($retry = 0; $retry < $max_retryloops; $retry++) {
                if ($allocations >= $credits_to_fill) break;
                
                foreach($wiz['slots']['days'] as $day) {
                    foreach($time_slots as $ts) {
                        if ($allocations >= $credits_to_fill) break 2;
                        $time_key = $ts['start'];

                        // CONSTRAINTS:
                        // 1. Is the Cohort busy? (Prevents scheduling two subjects for the SAME students at the same time)
                        if (isset($class_schedule[$day][$time_key])) { continue; }
                        
                        // 2. Is Faculty busy? (Double-booking protection)
                        if (isset($faculty_schedule[$f_db_id][$day][$time_key])) { continue; }

                        // 3. Finding an open room 
                        $assigned_room_db_id = null;
                        foreach($wiz['rooms'] as $r) {
                            $r_db_id = $room_map[$r['id']];
                            if (!isset($room_schedule[$r_db_id][$day][$time_key])) {
                                $assigned_room_db_id = $r_db_id;
                                break;
                            }
                        }

                        // Allocation
                        if ($assigned_room_db_id) {
                            $stmt_sched->execute([$tt_id, $s_db_id, $f_db_id, $assigned_room_db_id, $day, $ts['start'], $ts['end']]);
                            
                            $faculty_schedule[$f_db_id][$day][$time_key] = true;
                            $room_schedule[$assigned_room_db_id][$day][$time_key] = true;
                            $class_schedule[$day][$time_key] = true; // cohort is learning this subject now
                            
                            $allocations++;
                        }
                    } // end slots
                } // end days
            } // end retry loops

            if ($allocations < $credits_to_fill) {
                $conflicts[] = "Grid Overflow: Could only schedule $allocations out of {$s['credits']} slots for " . $s['code'];
            }
        } // end subjects

        $pdo->commit();
        $success = true;
        
        // Push ID to session and redirect to view
        $_SESSION['success'] = "Algorithm successfully synthesized the constraint map!";
        redirect("pages/timetable.php?id=" . $tt_id);

    } catch (Exception $e) {
        $pdo->rollBack();
        $conflicts[] = "System Database Exception: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center mt-3 mb-5">
    <div class="col-lg-8 text-center">
        <div class="mb-5">
            <h6 class="text-muted fw-bold tracking-wider text-uppercase mb-3">Final Step 6</h6>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: 100%;"></div>
            </div>
        </div>
        
        <div class="card p-5 shadow-lg border-0 rounded-4 mt-5">
            <i class="fa-solid fa-microchip fs-1 text-primary mb-4" style="font-size: 4rem;"></i>
            <h1 class="fw-bold mb-3">AI Matrix Optimizer</h1>
            <p class="lead text-muted px-4 mb-5">All constraints, faculty limits, and time segments have been loaded into memory. Click below to initiate the generation engine.</p>
            
            <?php if(count($conflicts) > 0): ?>
                <div class="alert alert-danger text-start">
                    <h5 class="fw-bold"><i class="fa-solid fa-bug me-2"></i> Allocation Bugs Detected</h5>
                    <ul class="mb-0">
                        <?php foreach($conflicts as $c): echo "<li>$c</li>"; endforeach; ?>
                    </ul>
                    <hr>
                    <small>These errors are usually caused by an impossible grid request (i.e. more subject credits requested than physical slots available).</small>
                </div>
            <?php endif; ?>

            <form method="POST">
                <button type="submit" name="generate" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow hover-grow w-100 fs-4">Execute Scheduling Sequence <i class="fa-solid fa-play ms-2"></i></button>
            </form>
            
            <a href="<?= BASE_URL ?>pages/select_days.php" class="btn btn-link text-muted mt-4 fw-bold"><i class="fa-solid fa-arrow-left me-1"></i> Return to Parameters</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
