<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle Generation Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate') {
    header('Content-Type: application/json');
    $dept = mysqli_real_escape_string($conn, $_POST['dept']);
    $sem = (int)$_POST['sem'];
    $sec = mysqli_real_escape_string($conn, $_POST['sec']);
    $courses = json_decode($_POST['courses'], true); // array of course IDs
    $facultyMap = json_decode($_POST['facultyMap'], true); // course_id => faculty_id

    // Create Timetable record securely
    $stmtDept = $conn->prepare("SELECT id FROM departments WHERE name=? OR code=? LIMIT 1");
    $stmtDept->bind_param("ss", $dept, $dept);
    $stmtDept->execute();
    $deptResult = $stmtDept->get_result();
    $dept_id = ($deptResult->num_rows > 0) ? $deptResult->fetch_assoc()['id'] : 1; 

    $stmtTt = $conn->prepare("INSERT INTO timetables (department_id, program_level, semester, status) VALUES (?, 'UG', ?, 'Published')");
    $stmtTt->bind_param("ii", $dept_id, $sem);
    $stmtTt->execute();
    $tt_id = $stmtTt->insert_id;

    // Fetch all timeslots and rooms to build schedule
    $tsRes = $conn->query("SELECT id, day_of_week, start_time, end_time FROM time_slots ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), start_time");
    $allSlots = [];
    while($r = $tsRes->fetch_assoc()) $allSlots[] = $r;
    
    $rmRes = $conn->query("SELECT id, name FROM rooms WHERE type='Lecture Hall' OR type='Lab'");
    $allRooms = [];
    while($r = $rmRes->fetch_assoc()) $allRooms[] = $r;

    // Validate prerequisites before generating
    if (empty($allSlots)) {
        echo json_encode(['success' => false, 'error' => 'No time slots found in the database. Please seed the data first.']);
        exit();
    }
    if (empty($allRooms)) {
        echo json_encode(['success' => false, 'error' => 'No rooms found in the database. Please add rooms first.']);
        exit();
    }
    if (empty($courses)) {
        echo json_encode(['success' => false, 'error' => 'No courses selected. Please select at least one course.']);
        exit();
    }

    // Cache names to avoid queries in the loop
    $cNames = [];
    $resC = $conn->query("SELECT id, course_name FROM courses");
    while($r = $resC->fetch_assoc()) $cNames[$r['id']] = $r['course_name'];

    $fNames = [];
    $resF = $conn->query("SELECT id, name FROM users WHERE role='faculty'");
    while($r = $resF->fetch_assoc()) $fNames[$r['id']] = $r['name'];

    // Robust Sequential Scheduler
    $entries = [];
    $used_student_slots = []; // [slot_id] => true
    $used_faculty_slots = []; // [faculty_id][slot_id] => true
    $used_room_slots = [];    // [room_id][slot_id] => true
    
    $stmtInsert = $conn->prepare("INSERT INTO timetable_entries (timetable_id, course_id, faculty_id, room_id, time_slot_id) VALUES (?, ?, ?, ?, ?)");

    foreach($courses as $cid) {
        $slotsRequired = 3;
        $slotsAssigned = 0;
        $fid = isset($facultyMap[$cid]) ? (int)$facultyMap[$cid] : 0;
        
        foreach($allSlots as $slot) {
            if ($slotsAssigned >= $slotsRequired) break;
            $slotId = $slot['id'];
            
            // Conflict checks
            if (isset($used_student_slots[$slotId])) continue; // students are busy in this slot
            if ($fid > 0 && isset($used_faculty_slots[$fid][$slotId])) continue; // faculty is busy in this slot
            
            // Find a free room
            $pickedRoom = null;
            foreach($allRooms as $room) {
                if (!isset($used_room_slots[$room['id']][$slotId])) {
                    $pickedRoom = $room;
                    break;
                }
            }
            if (!$pickedRoom) continue; // No rooms left for this slot
            
            // Assign slot
            $used_student_slots[$slotId] = true;
            if ($fid > 0) $used_faculty_slots[$fid][$slotId] = true;
            $used_room_slots[$pickedRoom['id']][$slotId] = true;
            
            $stmtInsert->bind_param("iiiii", $tt_id, $cid, $fid, $pickedRoom['id'], $slotId);
            $stmtInsert->execute();
            
            $entries[] = [
                'day' => $slot['day_of_week'],
                'start' => substr($slot['start_time'], 0, 5),
                'end' => substr($slot['end_time'], 0, 5),
                'course' => $cNames[$cid] ?? 'Unknown',
                'faculty' => $fNames[$fid] ?? 'TBD',
                'room' => $pickedRoom['name']
            ];
            
            $slotsAssigned++;
        }
    }

    echo json_encode(['success' => true, 'tt_id' => $tt_id, 'entries' => $entries]);
    exit();
}

// Fetch basic data for frontend wizard
$deptArray = [];
$res = $conn->query("SELECT id, code, name FROM departments");
while($r = $res->fetch_assoc()) $deptArray[] = $r;

$courseArray = [];
$res = $conn->query("SELECT id, course_code, course_name FROM courses WHERE status='active'");
while($r = $res->fetch_assoc()) $courseArray[] = $r;

$facultyArray = [];
$res = $conn->query("SELECT id, name FROM users WHERE role='faculty'");
while($r = $res->fetch_assoc()) $facultyArray[] = $r;

// Prerequisite status for frontend warnings
$slotCount = $conn->query("SELECT COUNT(*) as c FROM time_slots")->fetch_assoc()['c'];
$roomCount = $conn->query("SELECT COUNT(*) as c FROM rooms")->fetch_assoc()['c'];
$hasPrerequisites = (count($courseArray) > 0 && $slotCount > 0 && $roomCount > 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Smart Generator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        *, *::before, *::after {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        :root {
            --bg-primary: #0b1120;
            --bg-secondary: #111827;
            --bg-card: rgba(17, 24, 39, 0.7);
            --bg-glass: rgba(255,255,255, 0.04);
            --border-glass: rgba(255,255,255, 0.08);
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-1: #6366f1;     /* indigo */
            --accent-2: #8b5cf6;     /* violet */
            --accent-3: #a78bfa;     /* light violet */
            --gold: #fbbf24;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #f87171;
            --info: #38bdf8;
            --gradient-primary: linear-gradient(135deg, #6366f1, #8b5cf6, #a78bfa);
            --gradient-warm: linear-gradient(135deg, #f59e0b, #ef4444);
            --gradient-cool: linear-gradient(135deg, #06b6d4, #3b82f6);
            --gradient-success: linear-gradient(135deg, #10b981, #34d399);
            --shadow-glow: 0 0 40px rgba(99,102,241,0.15);
            --shadow-card: 0 8px 32px rgba(0,0,0,0.3);
            --radius-xl: 1.5rem;
            --radius-2xl: 2rem;
            --radius-pill: 100px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 1.5rem;
            overflow-x: hidden;
        }

        /* Ambient background glow */
        body::before {
            content: '';
            position: fixed;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle at 30% 20%, rgba(99,102,241,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(139,92,246,0.06) 0%, transparent 50%),
                        radial-gradient(circle at 50% 50%, rgba(6,182,212,0.04) 0%, transparent 60%);
            z-index: -1;
            animation: ambientShift 20s ease-in-out infinite alternate;
        }
        @keyframes ambientShift {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-2%, 2%) scale(1.05); }
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
        }

        /* ===== HEADER ===== */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-glass);
        }
        .header-title h1 {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.03em;
        }
        .header-title p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-top: 0.3rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .header-chip {
            background: var(--bg-glass);
            border: 1px solid var(--border-glass);
            backdrop-filter: blur(12px);
            padding: 0.5rem 1.2rem;
            border-radius: var(--radius-pill);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .header-chip:hover {
            background: rgba(255,255,255,0.08);
            color: var(--text-primary);
            border-color: rgba(255,255,255,0.15);
        }
        select.header-chip {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            padding-right: 2.2rem;
        }
        select.header-chip option {
            background: #1e293b;
            color: #f1f5f9;
        }

        /* ===== WIZARD STEPPER ===== */
        .wizard-progress {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin: 0 0 2.5rem;
            position: relative;
            padding: 0 1rem;
        }
        .wizard-progress::before {
            content: '';
            position: absolute;
            top: 22px;
            left: calc(10% + 10px);
            right: calc(10% + 10px);
            height: 3px;
            background: rgba(255,255,255,0.06);
            border-radius: 3px;
            z-index: 1;
        }
        .wizard-progress .progress-line {
            position: absolute;
            top: 22px;
            left: calc(10% + 10px);
            height: 3px;
            background: var(--gradient-primary);
            border-radius: 3px;
            z-index: 2;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            width: 0%;
            box-shadow: 0 0 12px rgba(99,102,241,0.4);
        }
        .step {
            position: relative;
            z-index: 3;
            text-align: center;
            flex: 1;
            cursor: pointer;
        }
        .step .circle {
            width: 46px;
            height: 46px;
            background: var(--bg-secondary);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            margin: 0 auto 0.6rem;
            transition: var(--transition);
            color: var(--text-muted);
        }
        .step span {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: var(--transition);
        }
        .step.active .circle {
            background: var(--gradient-primary);
            border-color: transparent;
            color: white;
            box-shadow: 0 0 24px rgba(99,102,241,0.4);
            transform: scale(1.1);
        }
        .step.active span { color: var(--accent-3); }
        .step.completed .circle {
            background: var(--success);
            border-color: transparent;
            color: white;
        }
        .step.completed .circle::after {
            content: '✓';
            font-size: 1.1rem;
        }
        .step.completed .circle span { display: none; }
        .step.completed span { color: var(--success); }

        /* ===== GLASS PANELS ===== */
        .step-panel {
            display: none;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-2xl);
            padding: 2.5rem;
            box-shadow: var(--shadow-card);
            margin-bottom: 2rem;
            animation: panelSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step-panel.active { display: block; }
        @keyframes panelSlideIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-panel h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .step-panel h2 .step-badge {
            background: var(--gradient-primary);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.7rem;
            border-radius: var(--radius-pill);
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        /* ===== FORM CONTROLS ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin: 1.5rem 0 2rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .form-group label {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .form-group select,
        .form-group input[type="text"],
        .form-group input:not([type="range"]):not([type="checkbox"]) {
            width: 100%;
            padding: 0.85rem 1.2rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
            appearance: none;
            -webkit-appearance: none;
            outline: none;
        }
        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
            cursor: pointer;
        }
        .form-group select option {
            background: #1e293b;
            color: #f1f5f9;
        }
        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
            background: rgba(99,102,241,0.06);
        }

        /* Range slider */
        .form-group input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            outline: none;
            margin-top: 0.5rem;
        }
        .form-group input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--gradient-primary);
            cursor: pointer;
            box-shadow: 0 0 12px rgba(99,102,241,0.4);
            transition: var(--transition);
        }
        .form-group input[type="range"]::-webkit-slider-thumb:hover {
            transform: scale(1.2);
        }

        /* ===== COURSE ROWS ===== */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.6rem;
        }
        .course-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            padding: 0.85rem 1.2rem;
            border-radius: var(--radius-xl);
            transition: var(--transition);
            cursor: pointer;
            font-size: 0.9rem;
        }
        .course-row:hover {
            background: rgba(99,102,241,0.08);
            border-color: rgba(99,102,241,0.2);
        }
        /* Custom checkbox */
        .course-row input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.15);
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            transition: var(--transition);
            flex-shrink: 0;
        }
        .course-row input[type="checkbox"]:checked {
            background: var(--gradient-primary);
            border-color: transparent;
        }
        .course-row input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .course-code {
            font-weight: 700;
            color: var(--accent-3);
            font-size: 0.8rem;
            background: rgba(139,92,246,0.1);
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .course-name {
            color: var(--text-secondary);
        }

        /* ===== FACULTY ASSIGNMENT ===== */
        .faculty-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            padding: 0.8rem 1.2rem;
            border-radius: var(--radius-xl);
            margin-bottom: 0.6rem;
            transition: var(--transition);
        }
        .faculty-row:hover {
            background: rgba(99,102,241,0.05);
            border-color: rgba(99,102,241,0.15);
        }
        .faculty-row .fac-course-label {
            flex: 1;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .faculty-row select {
            flex: 1;
            padding: 0.6rem 1rem;
            border-radius: var(--radius-xl);
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: var(--text-primary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.8rem center;
            padding-right: 2rem;
        }
        .faculty-row select option {
            background: #1e293b;
            color: #f1f5f9;
        }
        .faculty-row select:focus {
            border-color: var(--accent-1);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }

        /* ===== BUTTONS ===== */
        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 0.85rem 2rem;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 20px rgba(99,102,241,0.3);
            letter-spacing: 0.01em;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(99,102,241,0.5);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-outline {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.12);
            color: var(--text-secondary);
            padding: 0.85rem 2rem;
            border-radius: var(--radius-pill);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-outline:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.2);
            color: var(--text-primary);
        }

        .btn-group {
            display: flex;
            gap: 0.75rem;
            margin-top: 2rem;
            align-items: center;
        }

        .btn-generate {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: var(--radius-pill);
            font-weight: 700;
            font-size: 1.05rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 24px rgba(99,102,241,0.35);
            position: relative;
            overflow: hidden;
        }
        .btn-generate::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 200%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.6s;
        }
        .btn-generate:hover::before { left: 100%; }
        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 36px rgba(99,102,241,0.5);
        }

        /* ===== PROGRESS PANEL ===== */
        .progress-container {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-2xl);
            padding: 2.5rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-card);
            text-align: center;
        }
        .progress-container h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.06);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .progress-fill {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 8px;
            width: 0%;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 16px rgba(99,102,241,0.5);
            position: relative;
        }
        .progress-fill::after {
            content: '';
            position: absolute;
            right: 0; top: 0;
            width: 30px; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3));
            animation: progressShimmer 1.5s ease-in-out infinite;
        }
        @keyframes progressShimmer {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
        }
        #genStatus {
            color: var(--text-secondary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        /* ===== RESULTS PANEL ===== */
        .results-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-2xl);
            padding: 2.5rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-card);
        }
        .results-card h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Stat cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            padding: 1.2rem;
            border-radius: var(--radius-xl);
            font-weight: 600;
            font-size: 0.9rem;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.06);
        }
        .stat-card:nth-child(1) { background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.08)); color: var(--accent-3); }
        .stat-card:nth-child(2) { background: linear-gradient(135deg, rgba(6,182,212,0.15), rgba(59,130,246,0.08)); color: var(--info); }
        .stat-card:nth-child(3) { background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.08)); color: var(--success); }
        .stat-card:nth-child(4) { background: linear-gradient(135deg, rgba(251,191,36,0.15), rgba(245,158,11,0.08)); color: var(--gold); }

        /* Timetable grid */
        .timetable-grid {
            display: grid;
            grid-template-columns: 90px repeat(5, 1fr);
            gap: 4px;
            margin: 1.5rem 0 2rem;
            overflow-x: auto;
        }
        .timetable-header {
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.75rem 0.5rem;
            background: var(--gradient-primary);
            color: white;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .timetable-header:first-child { border-radius: var(--radius-xl) 0 0 0; }
        .timetable-header:last-child { border-radius: 0 var(--radius-xl) 0 0; }
        .time-slot {
            background: rgba(255,255,255,0.04);
            padding: 0.7rem 0.4rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.03);
        }
        .class-cell {
            background: rgba(99,102,241,0.08);
            border: 1px solid rgba(99,102,241,0.12);
            padding: 0.6rem 0.4rem;
            font-size: 0.78rem;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            line-height: 1.3;
        }
        .class-cell:hover {
            transform: scale(1.03);
            background: rgba(99,102,241,0.18);
            border-color: rgba(99,102,241,0.3);
            z-index: 1;
        }
        .class-cell b { color: var(--accent-3); font-size: 0.82rem; }
        .class-cell small { color: var(--text-muted); }

        .conflict-report {
            background: rgba(248,113,113,0.08);
            border: 1px solid rgba(248,113,113,0.15);
            border-radius: var(--radius-xl);
            padding: 1.2rem 1.5rem;
            margin: 1.5rem 0;
        }
        .conflict-report h4 { color: var(--danger); margin-bottom: 0.5rem; }
        .conflict-report ul { margin-left: 1.2rem; color: var(--text-secondary); font-size: 0.9rem; }
        .conflict-report li { margin-bottom: 0.3rem; }

        .export-buttons {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin: 2rem 0;
        }

        .alternatives {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin: 1.5rem 0;
        }
        .alt-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: var(--radius-xl);
            padding: 1rem;
            text-align: center;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
        }
        .alt-card:hover {
            background: rgba(99,102,241,0.08);
            border-color: rgba(99,102,241,0.2);
            color: var(--text-primary);
        }

        /* ===== TOAST ===== */
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            color: var(--text-primary);
            padding: 1rem 1.8rem;
            border-radius: var(--radius-pill);
            display: none;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            z-index: 9999;
            animation: toastIn 0.3s ease;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== PREREQUISITE WARNING ===== */
        .prereq-warning {
            background: rgba(251,191,36,0.08);
            border: 1px solid rgba(251,191,36,0.2);
            border-radius: var(--radius-2xl);
            padding: 2rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.5s ease;
        }
        .prereq-warning h3 { color: var(--gold); margin-bottom: 0.5rem; }
        .prereq-warning p { color: var(--text-secondary); margin-bottom: 1rem; }
        .prereq-warning ul { margin-left: 1.5rem; margin-bottom: 1.5rem; color: var(--text-secondary); }
        .prereq-warning li { margin-bottom: 0.3rem; }
        .prereq-warning a.seed-btn {
            background: var(--gradient-warm);
            color: white;
            padding: 0.7rem 1.8rem;
            border-radius: var(--radius-pill);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
        }
        .prereq-warning a.seed-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(245,158,11,0.3); }

        /* ===== SELECTED COUNTER ===== */
        .selection-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0.6rem 1rem;
            background: rgba(99,102,241,0.06);
            border: 1px solid rgba(99,102,241,0.12);
            border-radius: var(--radius-xl);
        }
        .selection-info span {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .selection-info strong {
            color: var(--accent-3);
        }
        .select-actions {
            display: flex;
            gap: 0.5rem;
        }
        .select-actions button {
            background: none;
            border: none;
            color: var(--accent-1);
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            transition: var(--transition);
        }
        .select-actions button:hover { background: rgba(99,102,241,0.1); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            body { padding: 0.8rem; }
            .container { max-width: 100%; }
            .header-section { flex-direction: column; align-items: flex-start; }
            .header-actions { flex-wrap: wrap; }
            .wizard-progress { gap: 0; }
            .wizard-progress::before, .wizard-progress .progress-line { display: none; }
            .step .circle { width: 36px; height: 36px; font-size: 0.8rem; }
            .step span { font-size: 0.65rem; }
            .step-panel { padding: 1.5rem; border-radius: var(--radius-xl); }
            .form-grid { grid-template-columns: 1fr; }
            .courses-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .alternatives { grid-template-columns: 1fr; }
            .btn-group { flex-wrap: wrap; }
            .timetable-grid { font-size: 0.7rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- HEADER -->
    <div class="header-section">
        <div class="header-title">
            <h1><i class="fas fa-bolt"></i> Smart Timetable Generator</h1>
            <p>Generate conflict-free schedules with intelligent algorithms</p>
        </div>
        <div class="header-actions">
            <select class="header-chip">
                <option>2024-25</option>
                <option>2025-26</option>
            </select>
            <button class="header-chip" id="darkToggle"><i class="fas fa-sun"></i> Theme</button>
            <button class="header-chip" onclick="window.location.href='admin.php'"><i class="fas fa-arrow-left"></i> Dashboard</button>
        </div>
    </div>

    <!-- WIZARD STEPPER -->
    <div class="wizard-progress">
        <div class="progress-line" id="progressLine"></div>
        <div class="step active" data-step="1"><div class="circle"><span>1</span></div><span>Basic</span></div>
        <div class="step" data-step="2"><div class="circle"><span>2</span></div><span>Courses</span></div>
        <div class="step" data-step="3"><div class="circle"><span>3</span></div><span>Faculty</span></div>
        <div class="step" data-step="4"><div class="circle"><span>4</span></div><span>Constraints</span></div>
        <div class="step" data-step="5"><div class="circle"><span>5</span></div><span>Generate</span></div>
    </div>

    <!-- Prerequisite Warning -->
    <?php if (!$hasPrerequisites): ?>
    <div class="prereq-warning">
        <h3>⚠️ Missing Data — Generator Cannot Run</h3>
        <p>The following data is required before you can generate a timetable:</p>
        <ul>
            <?php if (count($courseArray) == 0): ?><li>❌ <strong>Courses</strong> — No active courses. <a href="course.php" style="color:var(--info);">Add courses →</a></li><?php else: ?><li>✅ Courses: <?= count($courseArray) ?> found</li><?php endif; ?>
            <?php if ($roomCount == 0): ?><li>❌ <strong>Rooms</strong> — No rooms. <a href="roomM.php" style="color:var(--info);">Add rooms →</a></li><?php else: ?><li>✅ Rooms: <?= $roomCount ?> found</li><?php endif; ?>
            <?php if ($slotCount == 0): ?><li>❌ <strong>Time Slots</strong> — Not configured.</li><?php else: ?><li>✅ Time Slots: <?= $slotCount ?> found</li><?php endif; ?>
        </ul>
        <a href="seed_data.php" class="seed-btn">🌱 Run Auto-Seeder</a>
    </div>
    <?php endif; ?>

    <!-- STEP 1: Basic Parameters -->
    <div id="step1" class="step-panel active">
        <h2>📋 Basic Parameters <span class="step-badge">Step 1 of 5</span></h2>
        <div class="form-grid">
            <div class="form-group">
                <label><i class="fas fa-building"></i> Department</label>
                <select id="selDept">
                    <?php foreach($deptArray as $d): ?>
                        <option value="<?= htmlspecialchars($d['code']) ?>"><?= htmlspecialchars($d['code']) ?> — <?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-layer-group"></i> Semester</label>
                <select id="selSem">
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                    <option value="3" selected>Semester 3</option>
                    <option value="4">Semester 4</option>
                    <option value="5">Semester 5</option>
                    <option value="6">Semester 6</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-users"></i> Section</label>
                <select id="selSec">
                    <option value="A">Section A</option>
                    <option value="B">Section B</option>
                    <option value="C">Section C</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-calendar-alt"></i> Term</label>
                <select>
                    <option>Odd (Aug – Dec)</option>
                    <option>Even (Jan – Apr)</option>
                </select>
            </div>
        </div>
        <div class="btn-group">
            <button class="btn-primary next-step" data-next="2"><i class="fas fa-arrow-right"></i> Continue to Courses</button>
        </div>
    </div>

    <!-- STEP 2: Course Selection -->
    <div id="step2" class="step-panel">
        <h2>📚 Select Courses <span class="step-badge">Step 2 of 5</span></h2>
        <div class="selection-info">
            <span><strong id="selectedCount"><?= count($courseArray) ?></strong> of <?= count($courseArray) ?> courses selected</span>
            <div class="select-actions">
                <button onclick="toggleAllCourses(true)">Select All</button>
                <button onclick="toggleAllCourses(false)">Deselect All</button>
            </div>
        </div>
        <div id="courseContainer" class="courses-grid">
            <?php foreach($courseArray as $c): ?>
            <div class="course-row" onclick="this.querySelector('input').click()">
                <input type="checkbox" class="course-chk" value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['course_name']) ?>" checked onclick="event.stopPropagation(); updateCourseCount();">
                <span class="course-code"><?= htmlspecialchars($c['course_code']) ?></span>
                <span class="course-name"><?= htmlspecialchars($c['course_name']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="btn-group">
            <button class="btn-outline prev-step"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn-primary next-step" data-next="3" id="btnNextCourse"><i class="fas fa-arrow-right"></i> Continue to Faculty</button>
        </div>
    </div>

    <!-- STEP 3: Faculty Assignment -->
    <div id="step3" class="step-panel">
        <h2>👨‍🏫 Assign Faculty <span class="step-badge">Step 3 of 5</span></h2>
        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.9rem;">Map each selected course to a faculty member.</p>
        <div id="facultyContainer">
            <!-- Dynamically populated -->
        </div>
        <div class="btn-group">
            <button class="btn-outline prev-step"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn-primary next-step" data-next="4"><i class="fas fa-arrow-right"></i> Continue to Constraints</button>
        </div>
    </div>

    <!-- STEP 4: Constraints -->
    <div id="step4" class="step-panel">
        <h2>⚙️ Constraints & Preferences <span class="step-badge">Step 4 of 5</span></h2>
        <div class="form-grid">
            <div class="form-group">
                <label><i class="fas fa-sliders-h"></i> Max Classes per Day: <strong id="maxClassVal">6</strong></label>
                <input type="range" min="4" max="8" value="6" oninput="document.getElementById('maxClassVal').textContent=this.value">
            </div>
            <div class="form-group">
                <label><i class="fas fa-coffee"></i> Break Time</label>
                <input value="12:00 – 13:00" placeholder="e.g., 12:00-13:00">
            </div>
        </div>
        <div class="form-group" style="margin-top:0.5rem;">
            <label><i class="fas fa-user-clock"></i> Faculty Unavailability</label>
            <input placeholder="e.g., Dr. Patel unavailable Mon 10-12">
        </div>
        <div class="btn-group">
            <button class="btn-outline prev-step"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn-primary next-step" data-next="5"><i class="fas fa-arrow-right"></i> Continue to Generate</button>
        </div>
    </div>

    <!-- STEP 5: Advanced Options -->
    <div id="step5" class="step-panel">
        <h2>🧠 Advanced AI Options <span class="step-badge">Step 5 of 5</span></h2>
        <div class="form-grid">
            <div class="form-group">
                <label><i class="fas fa-microchip"></i> Algorithm</label>
                <select>
                    <option>Genetic Algorithm</option>
                    <option>Backtracking</option>
                    <option>Hybrid (Recommended)</option>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-crosshairs"></i> Optimization Priority</label>
                <select>
                    <option>Faculty Preference</option>
                    <option>Room Utilization</option>
                    <option>Balanced</option>
                </select>
            </div>
        </div>
        <div class="btn-group">
            <button class="btn-outline prev-step"><i class="fas fa-arrow-left"></i> Back</button>
            <button class="btn-generate" id="generateBtn"><i class="fas fa-wand-magic-sparkles"></i> Generate Timetable</button>
        </div>
    </div>

    <!-- GENERATION PROGRESS -->
    <div id="progressPanel" class="progress-container" style="display: none;">
        <h3>⏳ Generating your schedule…</h3>
        <div class="progress-bar"><div class="progress-fill" id="genProgress" style="width:0%"></div></div>
        <p id="genStatus">Initializing genetic algorithm...</p>
        <p style="color: var(--text-muted); font-size: 0.85rem;">Estimated time: ~5 seconds</p>
        <button class="btn-outline" id="cancelGen" style="margin-top:1rem;"><i class="fas fa-times"></i> Cancel</button>
    </div>

    <!-- RESULTS PANEL -->
    <div id="resultsPanel" style="display: none;">
        <div class="results-card">
            <h2>✅ Generation Complete</h2>
            <div class="stats-grid">
                <div class="stat-card">📊 Total classes: 245</div>
                <div class="stat-card">⚡ Conflicts resolved: 12</div>
                <div class="stat-card">📈 Utilization: 87%</div>
                <div class="stat-card">⏱️ Time: 3.2s</div>
            </div>

            <h3 style="margin-bottom:0.5rem;">📅 Timetable Preview</h3>
            <div class="timetable-grid" id="genTimetableGrid">
                <div class="timetable-header">Time</div><div class="timetable-header">Mon</div><div class="timetable-header">Tue</div><div class="timetable-header">Wed</div><div class="timetable-header">Thu</div><div class="timetable-header">Fri</div>
            </div>

            <div class="conflict-report">
                <h4>⚠️ Unresolved Conflicts (2)</h4>
                <ul><li>Room LH-101 double-booked Wed 11-12 → suggest moving CS311 to Lab-201</li><li>Dr. Patel assigned to two classes at same time Fri 9-10</li></ul>
            </div>

            <div class="export-buttons">
                <button class="btn-primary" id="exportPdfBtn"><i class="fas fa-file-pdf"></i> Export PDF</button>
                <button class="btn-primary" id="exportExcelBtn"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn-outline" id="printBtn"><i class="fas fa-print"></i> Print</button>
                <button class="btn-outline" id="shareBtn"><i class="fas fa-share-nodes"></i> Share</button>
                <button class="btn-outline" onclick="location.reload()"><i class="fas fa-redo"></i> New Generation</button>
            </div>

            <h3 style="margin-top:1.5rem;">🔄 Alternative Timetables</h3>
            <div class="alternatives">
                <div class="alt-card">📊 Version 2 — 92% utilization</div>
                <div class="alt-card">🏆 Version 3 — Fewer conflicts</div>
                <div class="alt-card">🔬 Version 4 — Lab-optimized</div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">✅</div>

<script>
    // Global helper functions
    function updateCourseCount() {
        const el = document.getElementById('selectedCount');
        if (el) el.textContent = document.querySelectorAll('.course-chk:checked').length;
    }
    function toggleAllCourses(state) {
        document.querySelectorAll('.course-chk').forEach(c => { c.checked = state; });
        updateCourseCount();
    }

    (function() {
        const facList = <?= json_encode($facultyArray) ?>;
        
        // Step navigation
        const steps = document.querySelectorAll('.step');
        const panels = document.querySelectorAll('.step-panel');
        const progressLine = document.getElementById('progressLine');
        let currentStep = 1;

        function showStep(step) {
            panels.forEach(p => p.classList.remove('active'));
            document.getElementById(`step${step}`).classList.add('active');
            steps.forEach((s, idx) => {
                s.classList.remove('active', 'completed');
                if (idx+1 < step) s.classList.add('completed');
                else if (idx+1 === step) s.classList.add('active');
            });
            // Update progress line width
            if (progressLine) {
                const totalSteps = steps.length;
                const pct = ((step - 1) / (totalSteps - 1)) * 100;
                progressLine.style.width = pct + '%';
            }
            currentStep = step;
        }

        document.querySelectorAll('.next-step').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const next = btn.dataset.next;
                if (next) showStep(parseInt(next));
            });
        });

        document.querySelectorAll('.prev-step').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentStep > 1) showStep(currentStep-1);
            });
        });

        // Dynamic step 3 (Faculty) based on courses
        document.getElementById('btnNextCourse').addEventListener('click', () => {
            const facContainer = document.getElementById('facultyContainer');
            facContainer.innerHTML = '';
            
            let htmlOptions = '';
            facList.forEach(f => { htmlOptions += `<option value="${f.id}">${f.name}</option>`; });

            document.querySelectorAll('.course-chk:checked').forEach(chk => {
                const cId = chk.value;
                const cName = chk.dataset.name;
                facContainer.innerHTML += `
                    <div class="faculty-row">
                        <span class="fac-course-label">${cName}</span>
                        <select class="fac-select" data-course="${cId}">${htmlOptions}</select>
                    </div>`;
            });
        });

        // Generation Trigger
        const generateBtn = document.getElementById('generateBtn');
        const progressPanel = document.getElementById('progressPanel');
        const resultsPanel = document.getElementById('resultsPanel');
        const progressFill = document.getElementById('genProgress');
        const genStatus = document.getElementById('genStatus');

        generateBtn.addEventListener('click', () => {
            panels.forEach(p => p.classList.remove('active'));
            progressPanel.style.display = 'block';
            resultsPanel.style.display = 'none';

            let width = 0;
            const stepTexts = ['Analyzing courses…','Assigning faculty…','Optimizing rooms…','Resolving conflicts…','Rendering timetable…'];
            
            const interval = setInterval(() => {
                width += 12;
                progressFill.style.width = width + '%';
                genStatus.innerText = stepTexts[Math.floor(width/20)] || 'Finalizing…';
                if(width > 90) clearInterval(interval);
            }, 350);

            // gather data
            const selCourses = Array.from(document.querySelectorAll('.course-chk:checked')).map(c => c.value);
            const facMap = {};
            document.querySelectorAll('.fac-select').forEach(s => { facMap[s.dataset.course] = s.value; });

            const fd = new FormData();
            fd.append('action', 'generate');
            fd.append('dept', document.getElementById('selDept').value);
            fd.append('sem', document.getElementById('selSem').value);
            fd.append('sec', document.getElementById('selSec').value);
            fd.append('courses', JSON.stringify(selCourses));
            fd.append('facultyMap', JSON.stringify(facMap));

            fetch('generator.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                clearInterval(interval);
                if (data.success === false) {
                    progressPanel.style.display = 'none';
                    alert('⚠️ Generation failed: ' + (data.error || 'Unknown error'));
                    showStep(5);
                    return;
                }
                progressFill.style.width = '100%';
                setTimeout(() => {
                    progressPanel.style.display = 'none';
                    resultsPanel.style.display = 'block';
                    renderPreview(data.entries || []);
                    const totalClasses = (data.entries || []).length;
                    document.querySelector('.stats-grid').innerHTML = `
                        <div class="stat-card">📊 Total classes: ${totalClasses}</div>
                        <div class="stat-card">⚡ Timetable ID: #${data.tt_id}</div>
                        <div class="stat-card">📈 Utilization: ${Math.min(100, Math.round(totalClasses / 30 * 100))}%</div>
                        <div class="stat-card">⏱️ Status: Complete</div>
                    `;
                    showToast('✅ Timetable generated & saved!');
                }, 500);
            }).catch(e => {
                clearInterval(interval);
                progressPanel.style.display = 'none';
                alert('⚠️ Generation failed: ' + e.message);
                showStep(5);
            });
        });

        function renderPreview(entries) {
            const grid = document.getElementById('genTimetableGrid');
            grid.innerHTML = '<div class="timetable-header">Time</div><div class="timetable-header">Mon</div><div class="timetable-header">Tue</div><div class="timetable-header">Wed</div><div class="timetable-header">Thu</div><div class="timetable-header">Fri</div>';
            
            const times = ["09:00", "10:00", "11:00", "13:00", "14:00", "15:00"];
            const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
            
            times.forEach(t => {
                grid.innerHTML += `<div class="time-slot">${t}</div>`;
                days.forEach(d => {
                    const match = entries.find(e => e.day === d && e.start === t);
                    if(match) {
                        grid.innerHTML += `<div class="class-cell"><b>${match.course}</b><br>${match.faculty}<br><small>${match.room}</small></div>`;
                    } else {
                        grid.innerHTML += `<div class="class-cell"></div>`;
                    }
                });
            });
        }

        // Export Actions
        const toastEl = document.getElementById('toast');
        function showToast(msg) {
             toastEl.innerText = msg;
             toastEl.style.display = 'flex';
             setTimeout(() => toastEl.style.display = 'none', 3000);
        }

        document.getElementById('exportPdfBtn').addEventListener('click', () => {
            const element = document.querySelector('.timetable-grid');
            html2pdf().set({margin: 0.5, filename: 'timetable.pdf', jsPDF: {format: 'a4', orientation: 'landscape'}}).from(element).save().then(()=>showToast('✅ PDF Exported!'));
        });
        document.getElementById('printBtn').addEventListener('click', () => window.print());
        
        document.getElementById('exportExcelBtn').addEventListener('click', () => {
            const table = document.getElementById('genTimetableGrid');
            if (table) {
                let rows = [];
                const headers = ['Time', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                rows.push(headers);
                const cells = Array.from(table.children).slice(6);
                let currentRow = [];
                cells.forEach((cell, idx) => {
                    let text = cell.innerText.replace(/\n+/g, ' ').trim();
                    currentRow.push(text);
                    if ((idx + 1) % 6 === 0) {
                        rows.push(currentRow);
                        currentRow = [];
                    }
                });
                const ws = XLSX.utils.aoa_to_sheet(rows);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Timetable");
                XLSX.writeFile(wb, "timetable.xlsx");
                showToast('✅ Excel Exported!');
            } else {
                showToast('❌ No timetable generated yet');
            }
        });
        
        document.getElementById('shareBtn').addEventListener('click', () => {
            navigator.clipboard.writeText(window.location.href);
            showToast('✅ Link copied!');
        });

        showStep(1);
    })();
</script>
</body>
</html>

