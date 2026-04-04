<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle Add/Delete requests via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'add') {
        $code = mysqli_real_escape_string($conn, trim($_POST['code']));
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $dept = mysqli_real_escape_string($conn, $_POST['dept']);
        $credits = (int)$_POST['credits'];
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if(empty($code) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Fields cannot be empty']);
            exit();
        }

        $check = $conn->query("SELECT id FROM courses WHERE course_code='$code'");
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Course code already exists!']);
            exit();
        }

        $sql = "INSERT INTO courses (course_code, course_name, credits, type, department, status) VALUES ('$code', '$name', $credits, '$type', '$dept', '$status')";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Course added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB Error: ' . $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM courses WHERE id=$id");
        echo json_encode(['success' => true]);
        exit();
    }
}

// Fetch DB Data
$coursesArray = [];
$res = $conn->query("SELECT * FROM courses ORDER BY course_name ASC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $coursesArray[] = [
            'id' => $row['id'],
            'code' => $row['course_code'],
            'name' => $row['course_name'],
            'dept' => $row['department'],
            'credits' => $row['credits'],
            'type' => $row['type'],
            // simulate these UI fields for now
            'sem' => isset($row['semester']) ? $row['semester'] : 3, 
            'hours' => $row['credits'], 
            'faculty' => 'Not Assigned', 
            'enrollment' => 50, 
            'status' => strtolower($row['status'])
        ];
    }
}

$deptsArray = [];
$dRes = $conn->query("SELECT code, name FROM departments");
if($dRes) {
    while($dRow = $dRes->fetch_assoc()) {
        $deptsArray[] = $dRow;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Course Management</title>
    <!-- Font Awesome 6 & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- simple vis.js for prerequisite graph -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="premium.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.css" />
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
            --shadow-md: 0 12px 30px -8px rgba(10,59,91,0.15);
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
            font-size: 2.6rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
        }
        .action-group {
            display: flex;
            gap: 1rem;
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
            box-shadow: var(--shadow-md);
        }
        .btn-primary:hover { background: var(--navy-light); transform: scale(1.02); }
        .btn-outline {
            background: white;
            border: 1.5px solid var(--navy);
            color: var(--navy);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
        }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
        }
        .stat-icon { font-size: 2.5rem; }
        .stat-info h3 { font-weight: 400; color: var(--gray-600); }
        .stat-info .value { font-size: 2.2rem; font-weight: 700; color: var(--navy); }

        /* filters */
        .filter-panel {
            background: white;
            border-radius: 3rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: center;
        }
        .filter-item {
            display: flex;
            flex-direction: column;
            min-width: 140px;
        }
        .filter-item label {
            font-weight: 600;
            color: var(--navy);
        }
        .filter-item input, .filter-item select {
            padding: 0.6rem 1rem;
            border-radius: 40px;
            border: 1px solid var(--gray-300);
            background: var(--gray-100);
        }

        /* course cards grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px,1fr));
            gap: 1.8rem;
            margin: 2rem 0;
        }
        .course-card {
            background: white;
            border-radius: 2.2rem;
            padding: 1.6rem;
            box-shadow: var(--shadow-md);
            border-left: 8px solid var(--gold);
            transition: 0.2s;
        }
        .course-card.inactive { opacity: 0.7; border-left-color: var(--gray-300); }

        .code {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--navy);
        }
        .name {
            font-size: 1.3rem;
            font-weight: 600;
        }
        .type-badge {
            display: inline-block;
            padding: 0.3rem 1.2rem;
            border-radius: 40px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
        }
        .lab-badge { background: #fed7aa; color: #92400e; }
        .meta {
            display: flex;
            gap: 1rem;
            margin: 0.8rem 0;
            color: var(--gray-600);
        }
        .enrollment {
            background: var(--gray-100);
            padding: 0.3rem 1rem;
            border-radius: 40px;
        }
        .action-icons {
            display: flex;
            gap: 1.2rem;
            justify-content: flex-end;
            margin-top: 1rem;
            color: var(--gray-600);
        }
        .action-icons i { cursor: pointer; }
        .action-icons i:hover { color: var(--navy); }

        /* modal */
        .modal {
            display: none;
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(3px);
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 3rem;
            padding: 2rem;
            max-width: 900px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        .tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--gray-300);
            margin: 1rem 0;
            overflow-x: auto;
        }
        .tab {
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
            color: var(--gray-600);
            white-space: nowrap;
        }
        .tab:hover { color: var(--navy); }
        .tab.active { color: var(--navy); border-bottom: 3px solid var(--gold); }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1.5rem;
        }
        .form-group label {
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 0.5rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border-radius: 12px;
            border: 1px solid var(--gray-300);
            background: var(--gray-100);
            font-size: 1rem;
            outline: none;
            transition: 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(10,59,91,0.1);
        }
        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .tab-pane.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* prerequisite graph */
        #graph-container {
            width: 100%;
            height: 300px;
            border: 1px solid var(--gray-300);
            border-radius: 2rem;
            background: #f9fbfd;
        }

        .toast {
            position: fixed;
            bottom: 30px; right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
        }

        /* curriculum map placeholder */
        .curriculum-row {
            display: flex;
            gap: 1.5rem;
            overflow-x: auto;
            padding: 1rem 0;
        }
        .sem-box {
            min-width: 200px;
            background: white;
            border-radius: 2rem;
            padding: 1rem;
            box-shadow: var(--shadow-md);
        }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .action-group { flex-wrap: wrap; width: 100%; }
            .action-group button { flex: 1; min-width: 120px; }
            .filter-bar { flex-direction: column; align-items: stretch; gap: 1rem; }
            .filter-item input, .filter-item select { width: 100%; }
            .modal-content { width: 95%; padding: 1.5rem; border-radius: 1.5rem; }
            .courses-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-section">
        <div class="header-title">
            <h1>📚 Course Management</h1>
            <p>Define and manage courses, prerequisites, and schedules</p>
        </div>
        <div class="action-group">
            <button class="btn-outline" onclick="window.location.href='admin.php'"><i class="fas fa-arrow-left"></i> Dashboard</button>
            <button class="btn-primary" id="addCourseBtn"><i class="fas fa-plus"></i> Add Course</button>
            <button class="btn-outline" id="importBtn"><i class="fas fa-file-import"></i> Import</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">📚</span><div class="stat-info"><span class="value"><?= count($coursesArray) ?></span><h3>Total Courses</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📖</span><div class="stat-info"><span class="value">--</span><h3>Theory</h3></div></div>
        <div class="stat-card"><span class="stat-icon">🔬</span><div class="stat-info"><span class="value">--</span><h3>Lab</h3></div></div>
        <div class="stat-card"><span class="stat-icon">⭐</span><div class="stat-info"><span class="value">--</span><h3>Electives</h3></div></div>
        <div class="stat-card"><span class="stat-icon">⚖️</span><div class="stat-info"><span class="value">2-4</span><h3>Credits range</h3></div></div>
    </div>

    <!-- filter panel -->
    <div class="filter-panel">
        <div class="filter-item"><label>Program Level</label><select id="programFilter"><option>All</option><option>Degree</option><option>Diploma</option></select></div>
        <div class="filter-item"><label>Department</label><select id="deptFilter"><option>All</option><option>CE</option><option>IT</option><option>ME</option><option>CL</option></select></div>
        <div class="filter-item"><label>Semester</label><select id="semFilter"><option>All</option><option>1</option><option>2</option><option>3</option><option>4</option></select></div>
        <div class="filter-item"><label>Type</label><select id="typeFilter"><option>All</option><option>Theory</option><option>Lab</option><option>Elective</option></select></div>
        <div class="filter-item"><label>Credits</label><input type="number" id="creditFilter" placeholder="≥"></div>
        <div class="filter-item"><label>Search</label><input type="text" id="searchInput" placeholder="Code/name"></div>
    </div>

    <!-- course grid -->
    <div id="coursesGrid" class="courses-grid"></div>

    <!-- prerequisite visualizer & curriculum map -->
    <div style="display: flex; gap: 2rem; margin: 2.5rem 0; flex-wrap: wrap;">
        <div style="flex:2; min-width:300px; background: white; border-radius:2.5rem; padding:1.5rem;">
            <h3>🔄 Prerequisite Graph</h3>
            <div id="graph-container"></div>
        </div>
        <div style="flex:1; background: white; border-radius:2.5rem; padding:1.5rem;">
            <h3>📅 Curriculum Map (Semester-wise)</h3>
            <div class="curriculum-row">
                <div class="sem-box"><h4>Sem 1</h4> CS101<br>MA101</div>
                <div class="sem-box"><h4>Sem 2</h4> CS102<br>PHY101</div>
                <div class="sem-box"><h4>Sem 3</h4> CS201 (←prereq)</div>
            </div>
        </div>
    </div>

    <!-- Course Assignment & Credits calculator -->
    <div style="display: flex; gap: 2rem; margin: 2rem 0;">
        <div style="background: white; border-radius:2rem; padding:1.5rem; flex:1;">
            <h3>👨‍🏫 Faculty Assignment</h3>
            <p>CS201: Dr. Chen (coordinator) + lab assistants</p>
            <p>EE101: Prof. Miller</p>
        </div>
        <div style="background: white; border-radius:2rem; padding:1.5rem; flex:1;">
            <h3>🧮 Credit calculator</h3>
            <p>Total credits this semester: 21</p>
            <progress value="21" max="25" style="width:100%; height:12px;"></progress>
        </div>
    </div>
</div>

<!-- ADD/EDIT COURSE MODAL -->
<div id="courseModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">➕ Add Course</h2>
        <div class="tabs">
            <span class="tab active" data-tab="basic">Basic</span>
            <span class="tab" data-tab="scheduling">Scheduling</span>
            <span class="tab" data-tab="prerequisites">Prerequisites</span>
            <span class="tab" data-tab="faculty">Faculty</span>
            <span class="tab" data-tab="syllabus">Syllabus</span>
        </div>
        <form id="courseForm">
            <!-- Basic Tab -->
            <div id="tab-basic" class="tab-pane active">
                <div class="form-grid">
                    <div class="form-group"><label>Course Code</label><input type="text" id="code" placeholder="CS501" required></div>
                    <div class="form-group"><label>Course Name</label><input type="text" id="cname" placeholder="Machine Learning" required></div>
                    <div class="form-group"><label>Department</label>
                        <select id="cdept">
                            <?php foreach($deptsArray as $d): ?>
                                <option value="<?= htmlspecialchars($d['code']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Scheduling Tab -->
            <div id="tab-scheduling" class="tab-pane">
                <div class="form-grid">
                    <div class="form-group"><label>Credits</label><input type="number" id="ccredits" value="4" min="1" max="10"></div>
                    <div class="form-group"><label>Hours/week</label><input value="3"></div>
                    <div class="form-group"><label>Type</label>
                        <select id="ctype">
                            <option value="Theory">Theory</option>
                            <option value="Lab">Lab</option>
                            <option value="Elective">Elective</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Semester</label><input value="5"></div>
                    <div class="form-group"><label>Status</label><select id="cstatus"><option value="active">Active</option><option value="inactive">Draft</option></select></div>
                </div>
            </div>

            <!-- Prerequisites Tab -->
            <div id="tab-prerequisites" class="tab-pane">
                <div class="form-group"><label>Prerequisites (multi-select)</label>
                    <select multiple size="5" style="border-radius:1rem; padding: 1rem;">
                        <option>CS101 - Introduction to Programming</option>
                        <option>CS201 - Data Structures</option>
                        <option>MA101 - Calculus</option>
                    </select>
                    <small style="color:var(--gray-600); margin-top:0.5rem; display:block;">Hold Ctrl/Cmd to select multiple prerequisites.</small>
                </div>
            </div>

            <!-- Faculty Tab -->
            <div id="tab-faculty" class="tab-pane">
                <div class="form-group"><label>Assigned Faculty / Coordinator</label>
                    <select>
                        <option>Dr. Chen</option>
                        <option>Prof. Miller</option>
                        <option>Dr. Ray</option>
                    </select>
                </div>
            </div>

            <!-- Syllabus Tab -->
            <div id="tab-syllabus" class="tab-pane">
                <div class="form-group"><label>Syllabus (PDF link)</label><input type="text" value="syllabus.pdf" placeholder="https://..."></div>
            </div>

            <div class="action-bar" style="margin-top:2.5rem; display:flex; gap:1rem; justify-content:flex-end; border-top: 1px solid var(--gray-300); padding-top: 1.5rem;">
                <button type="button" class="btn-outline" id="closeModal">Cancel</button>
                <button type="button" class="btn-primary" id="saveCourse">Save Course</button>
            </div>
        </form>
    </div>
</div>

<!-- COURSE DETAILS MODAL (view only) -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <h2>📘 Course Details</h2>
        <div class="tabs">
            <span class="tab active">Overview</span>
            <span class="tab">Syllabus</span>
            <span class="tab">Schedule</span>
            <span class="tab">Faculty</span>
            <span class="tab">Enrolled</span>
        </div>
        <div id="detailsContent">Course information goes here</div>
        <button class="btn-outline" id="closeDetails">Close</button>
    </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast">✅ Done</div>

<script>
    (function() {
        // dynamic courses from PHP database query
        let courses = <?= json_encode($coursesArray) ?>;

        // render course cards
        function renderCourses() {
            const dept = document.getElementById('deptFilter').value;
            const sem = document.getElementById('semFilter').value;
            const type = document.getElementById('typeFilter').value;
            const creditMin = parseInt(document.getElementById('creditFilter').value) || 0;
            const search = document.getElementById('searchInput').value.toLowerCase();

            const filtered = courses.filter(c => 
                (dept === 'All' || c.dept === dept) &&
                (sem === 'All' || c.sem == sem) &&
                (type === 'All' || c.type === type) &&
                (c.credits >= creditMin) &&
                (c.code.toLowerCase().includes(search) || c.name.toLowerCase().includes(search))
            );

            let html = '';
            filtered.forEach(c => {
                let typeClass = c.type === 'Lab' ? 'lab-badge' : '';
                html += `
                <div class="course-card ${c.status !== 'active' ? 'inactive':''}">
                    <div class="code">${c.code}</div>
                    <div class="name">${c.name}</div>
                    <div><span class="type-badge ${typeClass}">${c.type}</span>  ${c.dept}</div>
                    <div class="meta"><span><i class="fas fa-star"></i> ${c.credits} cr</span> <span><i class="far fa-clock"></i> ${c.hours}h</span></div>
                    <div><i class="fas fa-chalkboard-teacher"></i> ${c.faculty}</div>
                    <div class="enrollment"><i class="fas fa-users"></i> ${c.enrollment} enrolled</div>
                    <div class="action-icons">
                        <i class="fas fa-eye" onclick="viewCourse('${c.id}')"></i>
                        <i class="fas fa-edit" onclick="editCourse('${c.id}')"></i>
                        <i class="fas fa-trash" onclick="deleteCourse('${c.id}')"></i>
                        <i class="fas fa-project-diagram" title="prerequisites"></i>
                    </div>
                </div>
                `;
            });
            document.getElementById('coursesGrid').innerHTML = html;
        }

        // prerequisites graph (vis.js)
        function drawGraph() {
            const nodes = new vis.DataSet([
                {id: 1, label: 'CS101'}, {id: 2, label: 'CS201'}, {id:3, label: 'MA101'},
                {id:4, label: 'CS210'}, {id:5, label: 'CS401'}, {id:6, label: 'EC301'}
            ]);
            const edges = new vis.DataSet([
                {from:1, to:2, arrows:'to'},   // CS101 -> CS201
                {from:3, to:2, arrows:'to'},   // MA101 -> CS201
                {from:2, to:5, arrows:'to'}     // CS201 -> CS401
            ]);
            const container = document.getElementById('graph-container');
            new vis.Network(container, {nodes, edges}, {});
        }

        // modal controls
        const modal = document.getElementById('courseModal');
        const addBtn = document.getElementById('addCourseBtn');
        const closeModal = document.getElementById('closeModal');
        const saveBtn = document.getElementById('saveCourse');
        const detailsModal = document.getElementById('detailsModal');
        const closeDetails = document.getElementById('closeDetails');
        const toast = document.getElementById('toast');

        addBtn.addEventListener('click', () => {
            document.getElementById('modalTitle').innerText = '➕ Add Course';
            modal.classList.add('active');
        });

        closeModal.addEventListener('click', () => modal.classList.remove('active'));
        saveBtn.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('code', document.getElementById('code').value);
            formData.append('name', document.getElementById('cname').value);
            formData.append('dept', document.getElementById('cdept').value);
            formData.append('credits', document.getElementById('ccredits').value);
            formData.append('type', document.getElementById('ctype').value);
            formData.append('status', document.getElementById('cstatus').value);

            // Fetch request to same page
            fetch('course.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    modal.classList.remove('active');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(e => {
                alert('Request failed');
            });
        });

        window.viewCourse = (id) => {
            detailsModal.classList.add('active');
            document.getElementById('detailsContent').innerHTML = `Showing details for course ID ${id}. Complete overview, syllabus, schedule, faculty, students list.`;
        };
        window.editCourse = (id) => {
            document.getElementById('modalTitle').innerText = '✏️ Edit Course';
            modal.classList.add('active');
            // Populate logic goes here (advanced)
        };
        window.deleteCourse = (id) => {
            if (confirm('Are you sure you want to completely delete this course?')) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);
                fetch('course.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                    if(data.success) {
                        showToast('Class Deleted Successfully');
                        setTimeout(() => location.reload(), 1000);
                    }
                });
            }
        };

        closeDetails.addEventListener('click', () => detailsModal.classList.remove('active'));

        function showToast(msg) {
            toast.innerHTML = msg;
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 2000);
        }

        // tab switching logic
        const courseTabs = document.querySelectorAll('#courseModal .tab');
        const coursePanes = document.querySelectorAll('#courseModal .tab-pane');
        courseTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                courseTabs.forEach(t => t.classList.remove('active'));
                coursePanes.forEach(p => p.classList.remove('active'));
                tab.classList.add('active');
                if (tab.dataset.tab) {
                    document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
                }
            });
        });

        // filter events
        document.getElementById('deptFilter').addEventListener('change', renderCourses);
        document.getElementById('semFilter').addEventListener('change', renderCourses);
        document.getElementById('typeFilter').addEventListener('change', renderCourses);
        document.getElementById('creditFilter').addEventListener('input', renderCourses);
        document.getElementById('searchInput').addEventListener('input', renderCourses);

        // import simulation
        document.getElementById('importBtn').addEventListener('click', () => alert('Import from template dialog'));

        // init
        renderCourses();
        drawGraph();

        // bulk operations simulation
        window.checkDependency = () => alert('Dependency validation: no cycles');
    })();
</script>
<script src="theme.js"></script>
</body>
</html>

