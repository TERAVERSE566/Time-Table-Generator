<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

$uid = (int)$_SESSION['user_id'];
$urole = $_SESSION['user_role'];

// Fetch the user's specific timetable entries
$entries = [];

if ($urole === 'faculty') {
    $q = $conn->query("
        SELECT te.day_of_week, ts.start_time, ts.end_time, c.course_name, c.course_code, r.name as room_name, c.type as course_type
        FROM timetable_entries te
        JOIN time_slots ts ON te.time_slot_id = ts.id
        JOIN courses c ON te.course_id = c.id
        JOIN rooms r ON te.room_id = r.id
        WHERE te.faculty_id = $uid
    ");
    if($q) { while($r = $q->fetch_assoc()) $entries[] = $r; }
} else if ($urole === 'student') {
    $uData = $conn->query("SELECT department, current_semester FROM users WHERE id=$uid")->fetch_assoc();
    $dept = $uData['department'] ?? '';
    $sem = $uData['current_semester'] ?? 1;
    
    // Find department_id
    $dIdReq = $conn->query("SELECT id FROM departments WHERE name='$dept' OR code='$dept' LIMIT 1");
    $dept_id = $dIdReq->num_rows > 0 ? $dIdReq->fetch_assoc()['id'] : 0;
    
    // Find the active timetable for this dept/sem
    $ttReq = $conn->query("SELECT id FROM timetables WHERE department_id=$dept_id AND semester=$sem ORDER BY id DESC LIMIT 1");
    if ($ttReq && $ttReq->num_rows > 0) {
        $tt_id = $ttReq->fetch_assoc()['id'];
        $q = $conn->query("
            SELECT te.day_of_week, ts.start_time, ts.end_time, c.course_name, c.course_code, r.name as room_name, c.type as course_type, u.name as faculty_name
            FROM timetable_entries te
            JOIN time_slots ts ON te.time_slot_id = ts.id
            JOIN courses c ON te.course_id = c.id
            JOIN rooms r ON te.room_id = r.id
            LEFT JOIN users u ON te.faculty_id = u.id
            WHERE te.timetable_id = $tt_id
        ");
        if($q) { while($r = $q->fetch_assoc()) $entries[] = $r; }
    }
} else if ($urole === 'admin') {
    // Admins can see the most recently generated overall timetable as a preview
    $ttReq = $conn->query("SELECT id FROM timetables ORDER BY id DESC LIMIT 1");
    if ($ttReq && $ttReq->num_rows > 0) {
        $tt_id = $ttReq->fetch_assoc()['id'];
        $q = $conn->query("
            SELECT te.day_of_week, ts.start_time, ts.end_time, c.course_name, c.course_code, r.name as room_name, c.type as course_type, u.name as faculty_name
            FROM timetable_entries te
            JOIN time_slots ts ON te.time_slot_id = ts.id
            JOIN courses c ON te.course_id = c.id
            JOIN rooms r ON te.room_id = r.id
            LEFT JOIN users u ON te.faculty_id = u.id
            WHERE te.timetable_id = $tt_id
        ");
        if($q) { while($r = $q->fetch_assoc()) $entries[] = $r; }
    }
}

$entriesJson = json_encode($entries);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Class Schedule · TimetableGen</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
            --bg-light: #f8fafc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-md: 0 12px 30px -8px rgba(0,0,0,0.1);
            --border-radius: 2rem;
        }

        body {
            background: linear-gradient(145deg, #eef2f6, #f5f9ff);
            padding: 1.8rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* header */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .header-title h1 {
            font-size: 2.8rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
        }
        .view-controls {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.4rem;
            border-radius: 60px;
            box-shadow: var(--shadow-md);
        }
        .view-btn {
            padding: 0.6rem 1.5rem;
            border: none;
            background: transparent;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .view-btn.active {
            background: var(--navy);
            color: white;
        }
        .export-group {
            display: flex;
            gap: 0.8rem;
        }
        .icon-btn {
            background: white;
            border: none;
            padding: 0.8rem 1.2rem;
            border-radius: 40px;
            box-shadow: var(--shadow-md);
            cursor: pointer;
            font-size: 1rem;
        }

        /* navigation bar */
        .nav-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1.5rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        .nav-btn {
            background: var(--gray-100);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
        }
        .week-selector {
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            border: 1px solid var(--gray-300);
        }
        .search-box {
            display: flex;
            align-items: center;
            background: var(--gray-100);
            padding: 0.3rem 1.2rem;
            border-radius: 40px;
            margin-left: auto;
        }
        .search-box input {
            border: none;
            background: transparent;
            padding: 0.5rem;
            outline: none;
        }

        /* filter bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            background: white;
            padding: 1rem 2rem;
            border-radius: 5rem;
            margin-bottom: 2rem;
            align-items: center;
        }
        .filter-check {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .legend {
            display: flex;
            gap: 1rem;
            margin-left: auto;
        }
        .legend-color {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        /* week view grid */
        .week-view {
            background: white;
            border-radius: 2.5rem;
            padding: 1.8rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            overflow-x: auto;
        }
        .timetable-grid {
            display: grid;
            grid-template-columns: 100px repeat(5, 1fr);
            gap: 0.5rem;
            min-width: 800px;
        }
        .time-header {
            font-weight: 700;
            padding: 0.8rem;
        }
        .day-header {
            background: var(--navy);
            color: white;
            padding: 1rem;
            text-align: center;
            border-radius: 1.5rem 1.5rem 0 0;
            font-weight: 700;
        }
        .time-slot {
            background: var(--gray-100);
            padding: 0.8rem;
            border-radius: 1rem;
            text-align: center;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }
        .class-cell {
            background: #e0f2fe;
            border-radius: 1.2rem;
            padding: 0.8rem;
            margin: 0.2rem 0;
            cursor: pointer;
            transition: 0.2s;
            border-left: 6px solid var(--navy);
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .class-cell.lab { border-left-color: var(--warning); background: #fed7aa; }
        .class-cell.online { border-left-color: var(--success); background: #d1fae5; }
        .class-cell:hover { transform: scale(1.02); box-shadow: var(--shadow-md); }
        .current-time {
            border-top: 2px dashed var(--danger);
            position: relative;
            top: -2px;
            margin: 4px 0;
        }

        /* upcoming week mini */
        .upcoming-panel {
            background: white;
            border-radius: 2.5rem;
            padding: 1.5rem;
            margin-top: 2rem;
            display: flex;
            gap: 2rem;
        }
        .mini-calendar {
            display: flex;
            gap: 0.5rem;
        }
        .mini-day {
            background: var(--gray-100);
            padding: 0.5rem;
            border-radius: 1rem;
            text-align: center;
        }

        /* modal */
        .modal {
            display: none;
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(4px);
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 3rem;
            padding: 2.5rem;
            max-width: 500px;
            width: 90%;
        }

        .toast {
            position: fixed; bottom:30px; right:30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 3000;
        }

        /* right-click context menu */
        .context-menu {
            position: absolute;
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            padding: 0.5rem;
            display: none;
        }
        .context-menu ul { list-style: none; }
        .context-menu li { padding: 0.5rem 1.5rem; cursor: pointer; border-radius: 0.5rem; }
        .context-menu li:hover { background: var(--gray-100); }

        /* accessibility toolbar */
        .access-toolbar {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            align-items: center;
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-row">
        <div class="header-title">
            <h1>📅 My Class Schedule</h1>
            <p>Fall Semester 2024</p>
        </div>
        <div style="display: flex; gap:1rem; align-items: center;">
            <div class="view-controls">
                <button class="view-btn active" id="weekViewBtn">Week</button>
                <button class="view-btn" id="monthViewBtn">Month</button>
                <button class="view-btn" id="listViewBtn">List</button>
            </div>
            <div class="export-group">
                <button class="icon-btn" id="exportPdfBtn" title="Export PDF"><i class="fas fa-file-pdf"></i></button>
                <button class="icon-btn" id="exportCalBtn" title="Export Calendar"><i class="fas fa-calendar-alt"></i></button>
                <button class="icon-btn" id="printBtn" title="Print"><i class="fas fa-print"></i></button>
            </div>
        </div>
    </div>

    <!-- navigation -->
    <div class="nav-bar">
        <button class="nav-btn"><i class="fas fa-chevron-left"></i> Prev</button>
        <button class="nav-btn" id="todayBtn">Today</button>
        <button class="nav-btn">Next <i class="fas fa-chevron-right"></i></button>
        <select class="week-selector">
            <option>Week 1 (2 Sep - 6 Sep)</option>
            <option selected>Week 2 (9 Sep - 13 Sep)</option>
            <option>Week 3 (16 Sep - 20 Sep)</option>
        </select>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search classes...">
        </div>
        <div class="access-toolbar">
            <i class="fas fa-adjust" id="highContrast"></i>
            <i class="fas fa-text-height" id="fontIncrease"></i>
        </div>
    </div>

    <!-- filter & legend -->
    <div class="filter-bar">
        <span class="filter-check"><input type="checkbox"> Hide electives</span>
        <span class="filter-check"><input type="checkbox"> Lab only</span>
        <span class="filter-check"><input type="checkbox"> Online only</span>
        <div class="legend">
            <span><span class="legend-color" style="background:#e0f2fe;"></span> Theory</span>
            <span><span class="legend-color" style="background:#fed7aa;"></span> Lab</span>
            <span><span class="legend-color" style="background:#d1fae5;"></span> Online</span>
        </div>
    </div>

    <!-- WEEK VIEW (default) -->
    <div id="weekView" class="week-view">
        <div class="timetable-grid" id="mainGrid">
            <!-- Rendered by JS -->
        </div>
        <p style="margin-top:1rem;"><i class="fas fa-clock" style="color:red;"></i> Dynamic viewing capabilities loaded.</p>
    </div>

    <!-- LIST VIEW (hidden) -->
    <div id="listView" style="display:none; background:white; border-radius:2.5rem; padding:2rem;">
        <h3>📋 Chronological List</h3>
        <ul>
            <li><strong>Mon 9 Sep</strong> 8:00 CS301 (LH-101) Dr.Chen</li>
            <li>9:00 CS410 online</li>
            <li>10:00 CS307 (LH-102)</li>
        </ul>
    </div>

    <!-- upcoming week preview & notes -->
    <div class="upcoming-panel">
        <div>
            <h4>📌 Personal Notes</h4>
            <textarea placeholder="Add notes to time slots..." rows="3" style="border-radius:1.5rem; padding:1rem;"></textarea>
        </div>
        <div>
            <h4>📅 Upcoming Week</h4>
            <div class="mini-calendar">
                <div class="mini-day">Mon 16 <br> <span class="badge">📘</span></div>
                <div class="mini-day">Tue 17 <br> <span class="badge">📕</span></div>
                <div class="mini-day">Wed 18 <br> <span class="badge">⚠️ exam</span></div>
            </div>
        </div>
    </div>

    <!-- class details modal (hidden) -->
    <div id="classModal" class="modal">
        <div class="modal-content">
            <h2 id="modalCourse">CS301: Data Structures</h2>
            <p><i class="fas fa-chalkboard-teacher"></i> Dr. Aarti Sharma · aarti@college.edu</p>
            <p><i class="fas fa-door-open"></i> LH-101 · Mon 8-10, Wed 8-9</p>
            <p>📚 Prerequisites: CS101</p>
            <p>📎 Materials: <a href="#">slides</a>, <a href="#">github</a></p>
            <p>📅 Next assignment due: 20 Sep</p>
            <button class="icon-btn" id="closeModal">Close</button>
        </div>
    </div>

    <!-- right-click context menu (simulated) -->
    <div id="contextMenu" class="context-menu">
        <ul>
            <li>Add note</li>
            <li>Resize event</li>
            <li>Add to calendar</li>
        </ul>
    </div>

    <!-- toast for notifications -->
    <div id="toast" class="toast">✅ Note added</div>
</div>

<script>
    (function() {
        const entries = <?= $entriesJson ?>;

        const grid = document.getElementById('mainGrid');
        
        function renderGrid() {
            grid.innerHTML = '<div class="time-header">Time</div><div class="day-header">Monday</div><div class="day-header">Tuesday</div><div class="day-header">Wednesday</div><div class="day-header">Thursday</div><div class="day-header">Friday</div>';
            
            const times = ["09:00:00", "10:00:00", "11:00:00", "13:00:00", "14:00:00", "15:00:00"];
            const days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
            
            times.forEach(t => {
                grid.innerHTML += `<div class="time-slot">${t.slice(0,5)}</div>`;
                days.forEach(d => {
                    const match = entries.find(e => e.day_of_week === d && e.start_time === t);
                    if(match) {
                        let cls = 'class-cell';
                        if(match.course_type === 'lab') cls += ' lab';
                        let inner = `<b>${match.course_code}</b><br>${match.course_name}<br><small>${match.room_name}</small>`;
                        if (match.faculty_name) inner += `<br><small>${match.faculty_name}</small>`;

                        grid.innerHTML += `<div class="${cls}">${inner}</div>`;
                    } else {
                        grid.innerHTML += `<div></div>`;
                    }
                });
            });
        }
        renderGrid();

        // view toggle
        const weekView = document.getElementById('weekView');
        const listView = document.getElementById('listView');
        const weekBtn = document.getElementById('weekViewBtn');
        const listBtn = document.getElementById('listViewBtn');

        weekBtn.addEventListener('click', () => {
            weekView.style.display = 'block';
            listView.style.display = 'none';
            weekBtn.classList.add('active');
            listBtn.classList.remove('active');
        });
        listBtn.addEventListener('click', () => {
            weekView.style.display = 'none';
            listView.style.display = 'block';
            listBtn.classList.add('active');
            weekBtn.classList.remove('active');
        });

        // class cell click -> modal
        const modal = document.getElementById('classModal');
        const closeModal = document.getElementById('closeModal');
        grid.addEventListener('click', (e) => {
            const cell = e.target.closest('.class-cell');
            if(cell) {
                document.getElementById('modalCourse').innerHTML = cell.innerHTML;
                modal.classList.add('active');
            }
        });
        closeModal.addEventListener('click', () => modal.classList.remove('active'));

        // today button simulation
        document.getElementById('todayBtn').addEventListener('click', () => {
            alert('Today view: scrolling (simulated)');
        });

        // right-click context menu simulation
        const contextMenu = document.getElementById('contextMenu');
        grid.addEventListener('contextmenu', (e) => {
            const cell = e.target.closest('.class-cell');
            if(cell) {
                e.preventDefault();
                contextMenu.style.display = 'block';
                contextMenu.style.left = e.pageX + 'px';
                contextMenu.style.top = e.pageY + 'px';
                setTimeout(() => contextMenu.style.display = 'none', 2000);
            }
        });

        // double-click add note
        const toast = document.getElementById('toast');
        grid.addEventListener('dblclick', (e) => {
            if(e.target.closest('.class-cell')) {
                toast.style.display = 'flex';
                setTimeout(() => toast.style.display = 'none', 1500);
            }
        });

        // high contrast toggle
        let highContrast = false;
        document.getElementById('highContrast').addEventListener('click', () => {
            document.body.style.filter = highContrast ? 'none' : 'contrast(1.3) brightness(1.1)';
            highContrast = !highContrast;
        });

        // font size increase
        let fontSize = 16;
        document.getElementById('fontIncrease').addEventListener('click', () => {
            fontSize += 2;
            if (fontSize > 24) fontSize = 16;
            document.body.style.fontSize = fontSize + 'px';
        });

        // filter simulation (just alert)
        document.querySelectorAll('.filter-check input').forEach(cb => {
            cb.addEventListener('change', () => alert('Filter applied (simulated)'));
        });

        // Export PDF and Print bindings
        document.getElementById('exportPdfBtn').addEventListener('click', () => {
            const element = document.getElementById('weekView');
            const opt = {
                margin:       0.5,
                filename:     'my_schedule.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
            };
            html2pdf().set(opt).from(element).save().then(() => {
                toast.style.display = 'flex';
                toast.innerText = '✅ PDF Exported!';
                setTimeout(() => toast.style.display = 'none', 3000);
            });
        });

        document.getElementById('printBtn').addEventListener('click', () => {
            window.print();
        });

        document.getElementById('exportCalBtn').addEventListener('click', () => {
             toast.style.display = 'flex';
             toast.innerText = '❌ Calendar Export not implemented yet';
             setTimeout(() => toast.style.display = 'none', 3000);
        });

    })();
</script>
</body>
</html>

