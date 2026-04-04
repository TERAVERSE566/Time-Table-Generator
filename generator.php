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
    $tsRes = $conn->query("SELECT id, day_of_week, start_time, end_time FROM time_slots ORDER BY day_of_week, start_time");
    $allSlots = [];
    while($r = $tsRes->fetch_assoc()) $allSlots[] = $r;
    
    $rmRes = $conn->query("SELECT id, name FROM rooms WHERE type='Lecture' OR type='Lecture Hall'");
    $allRooms = [];
    while($r = $rmRes->fetch_assoc()) $allRooms[] = $r;

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Smart Generator</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        :root {
            --navy: #0a3b5b;
            --navy-light: #1e4f6e;
            --gold: #f4c542;
            --bg-light: #f4f7fc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-md: 0 20px 30px -10px rgba(10,59,91,0.2);
            --border-radius: 2rem;
        }

        body {
            background: var(--bg-light);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* header */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .header-title h1 {
            font-size: 2.8rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
            font-size: 1.2rem;
        }
        .year-selector {
            background: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            border: 1px solid var(--gray-300);
        }
        
        .dark-mode-toggle {
            background: var(--gray-300);
            border: none;
            border-radius: 30px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            margin-left: 1rem;
        }

        /* ===== PROPER DARK MODE ===== */
        body.dark-mode {
            background: #0f172a !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .step-panel, 
        body.dark-mode .progress-container,
        body.dark-mode .alt-card,
        body.dark-mode [style*="background: white"],
        body.dark-mode [style*="background:white"] {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .header-title h1,
        body.dark-mode h2, body.dark-mode h3 { color: #f1f5f9 !important; }
        body.dark-mode .form-group label { color: #94a3b8 !important; }
        body.dark-mode input, body.dark-mode select { background: #334155 !important; border-color: #475569 !important; color: white !important; }
        body.dark-mode .course-row { background: #334155 !important; }
        body.dark-mode .timetable-header { background: #334155 !important; }
        body.dark-mode .time-slot { background: #475569 !important; }
        body.dark-mode .class-cell { background: #1e4f6e !important; color: white !important; }
        body.dark-mode .class-cell[style*="background:#e0f2fe"] { background: #0c4a6e !important; }
        body.dark-mode .stat-card { background: #334155 !important; }

        /* wizard progress */
        .wizard-progress {
            display: flex;
            justify-content: space-between;
            margin: 2rem 0 3rem;
            position: relative;
        }
        .wizard-progress::before {
            content: '';
            position: absolute;
            top: 20px; left: 0; right: 0;
            height: 4px;
            background: var(--gray-300);
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            background: var(--bg-light);
            padding: 0 1rem;
            text-align: center;
        }
        .step .circle {
            width: 50px;
            height: 50px;
            background: white;
            border: 3px solid var(--gray-300);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 0.5rem;
            transition: 0.2s;
        }
        .step.active .circle {
            border-color: var(--gold);
            background: var(--navy);
            color: white;
        }
        .step.completed .circle {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }

        /* step panels */
        .step-panel {
            display: none;
            background: white;
            border-radius: 3rem;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        .step-panel.active {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
            gap: 1.5rem;
            margin: 1.5rem 0;
        }
        .form-group label {
            font-weight: 600;
            color: var(--navy);
            display: block;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border-radius: 50px;
            border: 1px solid var(--gray-300);
            background: var(--gray-100);
        }

        .course-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: var(--gray-100);
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            margin: 0.5rem 0;
        }

        .constraint-slider {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* generation progress */
        .progress-container {
            background: white;
            border-radius: 4rem;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: var(--shadow-md);
        }
        .progress-bar {
            height: 20px;
            background: var(--gray-300);
            border-radius: 30px;
            overflow: hidden;
        }
        .progress-fill {
            height: 20px;
            background: var(--gold);
            width: 0%;
            transition: width 0.3s;
        }

        /* timetable preview grid */
        .timetable-grid {
            display: grid;
            grid-template-columns: 100px repeat(5, 1fr);
            gap: 0.5rem;
            margin: 2rem 0;
            overflow-x: auto;
        }
        .timetable-header {
            font-weight: 700;
            padding: 0.8rem;
            background: var(--navy);
            color: white;
            border-radius: 1rem;
            text-align: center;
        }
        .time-slot {
            background: var(--gray-100);
            padding: 0.8rem;
            border-radius: 1rem;
            text-align: center;
            font-weight: 600;
        }
        .class-cell {
            background: #e0f2fe;
            padding: 0.8rem;
            border-radius: 1rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: 0.1s;
        }
        .class-cell:hover { transform: scale(1.02); background: #bae6fd; }

        .conflict-badge {
            background: var(--danger);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            display: inline-block;
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin: 2rem 0;
        }
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary:hover { background: var(--navy-light); }
        .btn-outline {
            background: white;
            border: 2px solid var(--navy);
            color: var(--navy);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
        }

        /* alternative thumbnails */
        .alternatives {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
        }
        .alt-card {
            background: white;
            border-radius: 2rem;
            padding: 1rem;
            box-shadow: var(--shadow-md);
            flex: 1;
            text-align: center;
        }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .wizard-progress { flex-direction: column; align-items: flex-start; gap: 1rem; padding-left: 2rem; }
            .wizard-progress::before { top: 0; bottom: 0; left: 45px; height: 100%; width: 4px; }
            .step { display: flex; align-items: center; gap: 1rem; text-align: left; }
            .step .circle { margin: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .timetable-grid { display: block; overflow-x: auto; white-space: nowrap; }
            .step-panel { padding: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-section">
        <div class="header-title">
            <h1>⚡ Smart Timetable Generator</h1>
            <p>Generate optimal timetables with AI-powered algorithms</p>
        </div>
        <div style="display: flex; align-items: center;">
            <select class="year-selector">
                <option>Academic Year 2024-25</option>
                <option>2025-26</option>
            </select>
            <button class="dark-mode-toggle" id="darkToggle"><i class="fas fa-moon"></i> Dark</button>
            <button class="btn-outline" style="margin-left: 1rem; border-radius: 30px; padding: 0.5rem 1rem;" onclick="window.location.href='admin.php'"><i class="fas fa-arrow-left"></i> Home</button>
        </div>
    </div>

    <!-- wizard steps -->
    <div class="wizard-progress">
        <div class="step active" data-step="1"><div class="circle">1</div><span>Basic</span></div>
        <div class="step" data-step="2"><div class="circle">2</div><span>Courses</span></div>
        <div class="step" data-step="3"><div class="circle">3</div><span>Faculty</span></div>
        <div class="step" data-step="4"><div class="circle">4</div><span>Constraints</span></div>
        <div class="step" data-step="5"><div class="circle">5</div><span>Advanced</span></div>
    </div>

    <!-- STEP 1: Basic Parameters -->
    <div id="step1" class="step-panel active">
        <h2>📋 Basic Parameters</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Department</label>
                <select id="selDept">
                    <?php foreach($deptArray as $d): ?>
                        <option value="<?= htmlspecialchars($d['code']) ?>"><?= htmlspecialchars($d['code']) ?> - <?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Semester</label><select id="selSem"><option value="1">1</option><option value="2">2</option><option value="3" selected>3</option><option value="4">4</option></select></div>
            <div class="form-group"><label>Section</label><select id="selSec"><option value="A">A</option><option value="B">B</option></select></div>
            <div class="form-group"><label>Term</label><select><option>Odd (Aug-Dec)</option><option>Even (Jan-Apr)</option></select></div>
        </div>
        <button class="btn-primary next-step" data-next="2">Next: Courses →</button>
    </div>

    <!-- STEP 2: Course Selection -->
    <div id="step2" class="step-panel">
        <h2>📚 Select Courses</h2>
        <div id="courseContainer">
            <?php foreach($courseArray as $c): ?>
            <div class="course-row">
                <input type="checkbox" class="course-chk" value="<?= $c['id'] ?>" data-name="<?= htmlspecialchars($c['course_name']) ?>" checked> 
                <?= htmlspecialchars($c['course_code']) ?> - <?= htmlspecialchars($c['course_name']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:2rem;">
            <button class="btn-outline prev-step">← Previous</button>
            <button class="btn-primary next-step" data-next="3" id="btnNextCourse">Next: Faculty →</button>
        </div>
    </div>

    <!-- STEP 3: Faculty Assignment -->
    <div id="step3" class="step-panel">
        <h2>👨‍🏫 Assign Faculty</h2>
        <div id="facultyContainer">
            <!-- Dynamically populated based on selected courses -->
        </div>
        <div>
            <button class="btn-outline prev-step" style="margin-top:1rem;">← Previous</button>
            <button class="btn-primary next-step" data-next="4" style="margin-top:1rem;">Next: Constraints →</button>
        </div>
    </div>

    <!-- STEP 4: Constraints -->
    <div id="step4" class="step-panel">
        <h2>⚙️ Constraints & Preferences</h2>
        <div class="form-group"><label>Max classes/day</label><input type="range" min="4" max="8" value="6"></div>
        <div class="form-group"><label>Break times</label><input value="12:00-13:00"></div>
        <div class="form-group"><label>Faculty unavailability</label><input placeholder="e.g., Dr. Chen unavailable Mon 10-12"></div>
        <button class="btn-primary next-step" data-next="5">Next: Advanced →</button>
    </div>

    <!-- STEP 5: Advanced Options -->
    <div id="step5" class="step-panel">
        <h2>🧠 Advanced AI Options</h2>
        <div class="form-group"><label>Algorithm</label><select><option>Genetic Algorithm</option><option>Backtracking</option><option>Hybrid</option></select></div>
        <div class="form-group"><label>Optimization priority</label><select><option>Faculty preference</option><option>Room utilization</option></select></div>
        <button class="btn-primary" id="generateBtn">✨ Generate Timetable</button>
    </div>

    <!-- Generation Progress (hidden initially) -->
    <div id="progressPanel" class="progress-container" style="display: none;">
        <h3>⏳ Generating schedule ...</h3>
        <div class="progress-bar"><div class="progress-fill" id="genProgress" style="width:0%"></div></div>
        <p id="genStatus">Initializing genetic algorithm...</p>
        <p>Estimated time: 5 seconds</p>
        <button class="btn-outline" id="cancelGen">Cancel</button>
    </div>

    <!-- Results & Timetable Preview (hidden after generation) -->
    <div id="resultsPanel" style="display: none;">
        <div style="background: white; border-radius: 3rem; padding:2rem; margin:2rem 0;">
            <h2>✅ Generation complete</h2>
            <div class="stats-grid" style="display:grid; grid-template-columns:repeat(4,1fr); gap:1rem;">
                <div class="stat-card">📊 Total classes: 245</div>
                <div class="stat-card">⚡ Conflicts resolved: 12</div>
                <div class="stat-card">📈 Utilization: 87%</div>
                <div class="stat-card">⏱️ Time: 3.2s</div>
            </div>

            <h3>📅 Timetable Preview (Week view)</h3>
            <div class="timetable-grid" id="genTimetableGrid">
                <div class="timetable-header">Time</div><div class="timetable-header">Monday</div><div class="timetable-header">Tuesday</div><div class="timetable-header">Wednesday</div><div class="timetable-header">Thursday</div><div class="timetable-header">Friday</div>
            </div>

            <!-- conflict report -->
            <div style="background:#fee2e2; border-radius:2rem; padding:1.5rem; margin:1.5rem 0;">
                <h4>⚠️ Unresolved Conflicts (2)</h4>
                <ul><li>Room LH-101 double-booked Wed 11-12 → suggest moving CS311 to Lab-201</li><li>Dr. Chen assigned to two classes at same time Fri 9-10</li></ul>
            </div>

            <!-- export options -->
            <div class="export-buttons">
                <button class="btn-primary" id="exportPdfBtn"><i class="fas fa-file-pdf"></i> Export PDF</button>
                <button class="btn-primary" id="exportExcelBtn"><i class="fas fa-file-excel"></i> Excel</button>
                <button class="btn-outline" id="printBtn"><i class="fas fa-print"></i> Print</button>
                <button class="btn-outline" id="shareBtn"><i class="fas fa-share"></i> Share</button>
            </div>

            <!-- alternative versions -->
            <h3>🔄 Alternative Timetables</h3>
            <div class="alternatives">
                <div class="alt-card">Version 2 (92% util)</div>
                <div class="alt-card">Version 3 (89% util, fewer conflicts)</div>
                <div class="alt-card">Version 4 (lab-optimized)</div>
            </div>
        </div>
    </div>
</div>

<!-- Toast for messages -->
<div id="toast" style="position:fixed; bottom:30px; right:30px; background:var(--navy); color:white; padding:1rem 2rem; border-radius:60px; display:none;">✅</div>

<script>
    (function() {
        const facList = <?= json_encode($facultyArray) ?>;
        
        // Step navigation
        const steps = document.querySelectorAll('.step');
        const panels = document.querySelectorAll('.step-panel');
        let currentStep = 1;

        function showStep(step) {
            panels.forEach(p => p.classList.remove('active'));
            document.getElementById(`step${step}`).classList.add('active');
            steps.forEach((s, idx) => {
                s.classList.remove('active', 'completed');
                if (idx+1 < step) s.classList.add('completed');
                else if (idx+1 === step) s.classList.add('active');
            });
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
                    <div class="course-row">
                        ${cName}: <select class="fac-select" data-course="${cId}">${htmlOptions}</select>
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
            const stepTexts = ['Analyzing courses','Assigning faculty','Optimizing rooms','Resolving conflicts','Rendering timetable'];
            
            const interval = setInterval(() => {
                width += 15;
                progressFill.style.width = width + '%';
                genStatus.innerText = stepTexts[Math.floor(width/20)] || 'Finalizing...';
                if(width > 90) clearInterval(interval);
            }, 300);

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
                progressFill.style.width = '100%';
                setTimeout(() => {
                    progressPanel.style.display = 'none';
                    resultsPanel.style.display = 'block';
                    renderPreview(data.entries);
                    showToast('✅ Timetable generated & saved!');
                }, 500);
            }).catch(e => {
                clearInterval(interval);
                alert("Generation failed: " + e);
                showStep(5);
            });
        });

        function renderPreview(entries) {
            const grid = document.getElementById('genTimetableGrid');
            grid.innerHTML = '<div class="timetable-header">Time</div><div class="timetable-header">Monday</div><div class="timetable-header">Tuesday</div><div class="timetable-header">Wednesday</div><div class="timetable-header">Thursday</div><div class="timetable-header">Friday</div>';
            
            const times = ["09:00", "10:00", "11:00", "13:00", "14:00", "15:00"];
            const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
            
            times.forEach(t => {
                grid.innerHTML += `<div class="time-slot">${t}</div>`;
                days.forEach(d => {
                    // find entry for this cell
                    const match = entries.find(e => e.day === d && e.start === t);
                    if(match) {
                        grid.innerHTML += `<div class="class-cell" style="background:#e0f2fe;"><b>${match.course}</b><br>${match.faculty}<br><small>${match.room}</small></div>`;
                    } else {
                        grid.innerHTML += `<div class="class-cell"></div>`;
                    }
                });
            });
        }

        // Export Actions
        const toast = document.getElementById('toast');
        function showToast(msg) {
             toast.innerText = msg;
             toast.style.display = 'flex';
             setTimeout(() => toast.style.display = 'none', 3000);
        }

        document.getElementById('exportPdfBtn').addEventListener('click', () => {
            const element = document.querySelector('.timetable-grid');
            html2pdf().from(element).save('timetable.pdf').then(()=>showToast('✅ PDF Exported!'));
        });
        document.getElementById('printBtn').addEventListener('click', () => window.print());
        
        document.getElementById('exportExcelBtn').addEventListener('click', () => {
            const table = document.getElementById('genTimetableGrid');
            if (table) {
                let rows = [];
                const headers = ['Time', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                rows.push(headers);
                const cells = Array.from(table.children).slice(6);
                let currentRowIdx = 0;
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
            showToast('✅ Link copied to clipboard');
        });

        // Dark mode setup
        const darkToggle = document.getElementById('darkToggle');
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkToggle.innerHTML = '<i class="fas fa-sun"></i> Light';
        }
        darkToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark);
            darkToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i> Light' : '<i class="fas fa-moon"></i> Dark';
        });

        showStep(1);
    })();
</script>
<script src="theme.js"></script>
</body>
</html>

