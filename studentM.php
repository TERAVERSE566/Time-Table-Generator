<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle POST actions (add/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'add') {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $roll = mysqli_real_escape_string($conn, trim(strtoupper($_POST['roll'])));
        $dept = mysqli_real_escape_string($conn, $_POST['dept']);
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        
        if (empty($name) || empty($roll) || empty($email)) {
             echo json_encode(['success' => false, 'message' => 'Name, Roll and Email required.']);
             exit();
        }

        // Check existing email
        $chk = $conn->query("SELECT id FROM users WHERE email='$email'");
        if($chk->num_rows > 0) {
             echo json_encode(['success' => false, 'message' => 'Email already registered.']);
             exit();
        }

        $hash = password_hash('student123', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password_hash, role, department, roll_number, current_year, current_semester, student_status, cgpa, attendance_percent) 
                VALUES ('$name', '$email', '$hash', 'student', '$dept', '$roll', 1, 1, 'Active', 0.00, 100)";

        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Student saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role='student'");
        echo json_encode(['success' => true]);
        exit();
    }
}

// Fetch Students
$studentArray = [];
$res = $conn->query("SELECT * FROM users WHERE role='student' ORDER BY name ASC");

$yearCounts = [1=>0, 2=>0, 3=>0, 4=>0];
$gradCount = 0;

if ($res) {
    while($row = $res->fetch_assoc()) {
        $year = (int)($row['current_year'] ?? 1);
        if ($year >= 1 && $year <= 4) $yearCounts[$year]++;
        
        $status = $row['student_status'] ?? 'Active';
        if ($status === 'Graduated') $gradCount++;

        // Calculate initials
        $parts = explode(' ', trim($row['name']));
        $initials = '';
        if (count($parts) >= 2) {
            $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts)-1], 0, 1));
        } else {
            $initials = strtoupper(substr($row['name'], 0, 2));
        }

        $studentArray[] = [
            'id' => $row['id'],
            'roll' => $row['roll_number'] ?? 'N/A',
            'name' => $row['name'],
            'dept' => $row['department'] ?: 'N/A',
            'year' => $year,
            'sem' => (int)($row['current_semester'] ?? 1),
            'cgpa' => (float)($row['cgpa'] ?? 0.0),
            'attendance' => (int)($row['attendance_percent'] ?? 0),
            'status' => $status,
            'batch' => $row['batch_year'] ?? '2024',
            'section' => $row['section'] ?? 'A',
            'avatar' => $initials
        ];
    }
}

// Fetch Departments
$deptsArray = [];
$dRes = $conn->query("SELECT code FROM departments");
if ($dRes) {
    while($dRow = $dRes->fetch_assoc()) {
        $deptsArray[] = $dRow['code'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Student Management</title>
    <!-- Font Awesome 6 & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <!-- jQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="premium.css">
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
            font-size: 1.1rem;
        }
        .action-group {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-outline:hover { background: #eaf0f8; }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem 1rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
        }
        .stat-icon { font-size: 2.5rem; }
        .stat-info h3 { font-weight: 400; color: var(--gray-600); }
        .stat-info .value { font-size: 2rem; font-weight: 700; color: var(--navy); }

        /* advanced search panel */
        .search-panel {
            background: white;
            border-radius: 3rem;
            padding: 1.8rem 2rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-md);
        }
        .row {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .search-field {
            flex: 2 1 250px;
        }
        .search-field label {
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 0.2rem;
            display: block;
        }
        .search-field input, .search-field select {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1.5px solid var(--gray-300);
            border-radius: 40px;
            font-size: 1rem;
            background: var(--gray-100);
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* toggle between grid and table */
        .view-toggle {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        .view-btn {
            background: var(--gray-100);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            cursor: pointer;
            font-size: 1.1rem;
        }
        .view-btn.active {
            background: var(--navy);
            color: white;
        }

        /* students container (card grid) */
        .students-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .student-card {
            background: white;
            border-radius: 2.2rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            border-left: 8px solid var(--gold);
            transition: 0.15s;
        }
        .student-card.inactive { opacity: 0.7; border-left-color: var(--gray-300); }
        .student-card.graduated { border-left-color: var(--success); }

        .card-header {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        .avatar {
            width: 70px;
            height: 70px;
            background: var(--navy);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            text-transform: uppercase;
        }
        .roll {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        .badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .active-badge { background: #d1fae5; color: #065f46; }
        .inactive-badge { background: #fee2e2; color: #991b1b; }
        .graduated-badge { background: #e0f2fe; color: #0369a1; }

        .progress {
            background: #e2e8f0;
            height: 8px;
            border-radius: 20px;
            margin: 0.5rem 0;
        }
        .progress-fill {
            height: 8px;
            background: var(--navy);
            border-radius: 20px;
        }

        .cgpa-indicator {
            font-weight: 700;
            color: var(--navy);
        }

        .action-icons {
            display: flex;
            gap: 1.2rem;
            justify-content: flex-end;
            margin-top: 0.8rem;
            color: var(--gray-600);
        }
        .action-icons i { cursor: pointer; }
        .action-icons i:hover { color: var(--navy); }

        /* table view (hidden by default) */
        .table-view {
            display: none;
            background: white;
            border-radius: 2rem;
            padding: 1.5rem;
            overflow-x: auto;
        }
        .table-view table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--gray-300); }

        /* MODALS (Profile, Add, Bulk) */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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
            padding: 2.2rem;
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
        }
        .tab {
            padding: 0.7rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
        }
        .tab.active { color: var(--navy); border-bottom: 3px solid var(--gold); }

        .toast {
            position: fixed; bottom: 30px; right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 3000;
        }

        /* analytics cards */
        .analytics-row {
            display: flex;
            gap: 1.5rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
        .chart-card {
            background: white;
            border-radius: 2rem;
            padding: 1.5rem;
            flex: 1 1 250px;
        }
        canvas { max-height: 200px; width: 100% !important; }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .action-group { flex-wrap: wrap; width: 100%; }
            .action-group button { flex: 1; min-width: 120px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .filter-bar { flex-direction: column; align-items: stretch; gap: 1rem; }
            .filter-group, .search-box { width: 100%; box-sizing: border-box; }
            .filter-group select { width: 100%; box-sizing: border-box; }
            .modal-content { width: 95%; padding: 1.5rem; border-radius: 1.5rem; }
            .form-grid { grid-template-columns: 1fr; }
            .analytics-row { flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-section">
        <div class="header-title">
            <h1>👩‍🎓 Student Management</h1>
            <p>Manage student records, batches, and academic progress</p>
        </div>
        <div class="action-group">
            <button class="btn-outline" onclick="window.location.href='admin.php'"><i class="fas fa-arrow-left"></i> Dashboard</button>
            <button class="btn-primary" id="addStudentBtn"><i class="fas fa-user-plus"></i> Add Student</button>
            <button class="btn-outline" id="batchImport"><i class="fas fa-upload"></i> Batch Import</button>
            <button class="btn-outline" id="graduateBtn"><i class="fas fa-graduation-cap"></i> Graduate Students</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">👩‍🎓</span><div class="stat-info"><span class="value"><?= count($studentArray) ?></span><h3>Total Students</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-info"><span class="value"><?= $yearCounts[1] ?></span><h3>Year 1</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-info"><span class="value"><?= $yearCounts[2] ?></span><h3>Year 2</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-info"><span class="value"><?= $yearCounts[3] ?></span><h3>Year 3</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-info"><span class="value"><?= $yearCounts[4] ?></span><h3>Year 4</h3></div></div>
        <div class="stat-card"><span class="stat-icon">🎓</span><div class="stat-info"><span class="value"><?= $gradCount ?></span><h3>Graduating</h3></div></div>
    </div>

    <!-- advanced search panel -->
    <div class="search-panel">
        <div class="row">
            <div class="search-field">
                <label>Search by name / roll / email</label>
                <input type="text" id="searchText" placeholder="e.g. CS2201 or John">
            </div>
            <div class="search-field">
                <label>Department</label>
                <select id="deptFilter">
                    <option value="All">All</option>
                    <?php foreach($deptsArray as $dc): ?>
                        <option value="<?= htmlspecialchars($dc) ?>"><?= htmlspecialchars($dc) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-field">
                <label>Year / Batch</label>
                <select id="yearFilter"><option>All</option><option>1</option><option>2</option><option>3</option><option>4</option></select>
            </div>
            <div class="search-field">
                <label>Status</label>
                <select id="statusFilter">
                    <option>All</option><option>Active</option><option>Inactive</option><option>Graduated</option>
                </select>
            </div>
            <div class="view-toggle">
                <button class="view-btn active" id="gridViewBtn"><i class="fas fa-th-large"></i></button>
                <button class="view-btn" id="tableViewBtn"><i class="fas fa-table"></i></button>
            </div>
        </div>
        <div class="filter-row">
            <span><i class="fas fa-check-circle"></i> Batch year range: 2020-2024</span>
            <span><i class="fas fa-section"></i> Section filter: A, B, C</span>
        </div>
    </div>

    <!-- student display (grid) and table -->
    <div id="studentsGridContainer" class="students-grid"></div>
    <div id="tableView" class="table-view"></div>

    <!-- enrollment / bulk operations -->
    <div style="display:flex; gap:1rem; align-items:center; margin:1.5rem 0;">
        <span><i class="far fa-check-square"></i> <span id="selectedCount">0</span> selected</span>
        <button class="btn-outline" id="bulkPromote">📈 Promote to next sem</button>
        <button class="btn-outline" id="bulkGraduate">🎓 Graduate selected</button>
        <button class="btn-outline" id="exportSelected">📥 Export selected</button>
    </div>

    <!-- analytics row (charts) -->
    <div class="analytics-row">
        <div class="chart-card"><canvas id="deptChart"></canvas></div>
        <div class="chart-card"><canvas id="yearChart"></canvas></div>
        <div class="chart-card"><canvas id="performanceChart"></canvas></div>
    </div>

    <!-- quick enrollment section -->
    <div style="background: white; border-radius: 2rem; padding: 1.5rem; margin: 2rem 0;">
        <h3>📚 Course enrollment (drag & drop simulation)</h3>
        <div style="display:flex; gap:2rem; background:#f1f5f9; border-radius:2rem; padding:1.5rem;">
            <div style="flex:1;"><i class="fas fa-grip-vertical"></i> Available courses: Math101, CS202, PHY101</div>
            <div style="flex:1;"><i class="fas fa-grip-vertical"></i> Enrolled: CS201 (drop)</div>
        </div>
        <p style="margin-top:1rem;"><i class="fas fa-paper-plane"></i> Email/SMS notification simulation</p>
    </div>
</div>

<!-- STUDENT PROFILE MODAL (detailed) -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <h2 id="profileName">👤 Student Profile</h2>
        <div class="tabs">
            <span class="tab active">Personal</span>
            <span class="tab">Academic</span>
            <span class="tab">Schedule</span>
            <span class="tab">Attendance</span>
            <span class="tab">Fees</span>
        </div>
        <div id="profileDetail">Loading ...</div>
        <button class="btn-outline" id="closeProfile">Close</button>
    </div>
</div>

<!-- ADD STUDENT MODAL (simplified) -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <h2>➕ Add New Student</h2>
        <div class="form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
            <input placeholder="Full name" id="sName" value="John Doe">
            <input placeholder="Roll number" id="sRoll" value="CS2201">
            <select id="sDept">
                <?php foreach($deptsArray as $dc): ?>
                    <option value="<?= htmlspecialchars($dc) ?>"><?= htmlspecialchars($dc) ?></option>
                <?php endforeach; ?>
            </select>
            <input placeholder="Email" id="sEmail" value="john@college.edu">
        </div>
        <div style="margin-top:2rem;">
            <button class="btn-primary" id="saveStudent">Save</button>
            <button class="btn-outline" id="cancelAdd">Cancel</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast">✅ Done</div>

<script>
    (function() {
        // dynamic students array from PHP
        let students = <?= json_encode($studentArray) ?>;

        let selectedStudents = new Set();

        // render function for both grid and table
        function renderStudents() {
            const search = document.getElementById('searchText').value.toLowerCase();
            const dept = document.getElementById('deptFilter').value;
            const year = document.getElementById('yearFilter').value;
            const status = document.getElementById('statusFilter').value;

            let filtered = students.filter(s => 
                (s.name.toLowerCase().includes(search) || s.roll.toLowerCase().includes(search)) &&
                (dept === 'All' || s.dept === dept) &&
                (year === 'All' || s.year == year) &&
                (status === 'All' || s.status === status)
            );

            // update grid
            const gridContainer = document.getElementById('studentsGridContainer');
            let gridHtml = '';
            filtered.forEach(s => {
                let statusClass = s.status === 'Active' ? 'active-badge' : (s.status === 'Inactive' ? 'inactive-badge' : 'graduated-badge');
                let cardClass = s.status === 'Inactive' ? 'inactive' : (s.status === 'Graduated' ? 'graduated' : '');
                let checkedAttr = selectedStudents.has(s.id) ? 'checked' : '';
                gridHtml += `
                <div class="student-card ${cardClass}" data-id="${s.id}">
                    <div class="card-header">
                        <div class="avatar">${s.avatar}</div>
                        <div>
                            <h3>${s.name}</h3>
                            <div class="roll">${s.roll} · ${s.dept}</div>
                        </div>
                    </div>
                    <div><span class="badge ${statusClass}">${s.status}</span>  Sem ${s.sem} | Sec ${s.section || 'A'}</div>
                    <div style="margin:0.6rem 0;">CGPA <span class="cgpa-indicator">${s.cgpa}</span>  | Attendance ${s.attendance}%</div>
                    <div class="progress"><div class="progress-fill" style="width:${s.attendance}%;"></div></div>
                    <div class="action-icons">
                        <i class="fas fa-eye" onclick="viewStudent('${s.id}')"></i>
                        <i class="fas fa-edit" onclick="editStudent('${s.id}')"></i>
                        <i class="fas fa-trash" onclick="deleteStudent('${s.id}')"></i>
                        <i class="fas fa-calendar-alt"></i>
                        <input type="checkbox" class="select-student" data-id="${s.id}" ${checkedAttr}>
                    </div>
                </div>
                `;
            });
            gridContainer.innerHTML = gridHtml;

            // also build simple table
            let tableHtml = '<table><tr><th>Select</th><th>Roll</th><th>Name</th><th>Dept</th><th>Year</th><th>CGPA</th><th>Status</th></tr>';
            filtered.forEach(s => {
                tableHtml += `<tr><td><input type="checkbox" class="select-student" data-id="${s.id}" ${selectedStudents.has(s.id)?'checked':''}></td><td>${s.roll}</td><td>${s.name}</td><td>${s.dept}</td><td>${s.year}</td><td>${s.cgpa}</td><td>${s.status}</td></tr>`;
            });
            tableHtml += '</table>';
            document.getElementById('tableView').innerHTML = tableHtml;

            updateSelectedCount();

            // attach checkbox listeners
            document.querySelectorAll('.select-student').forEach(cb => {
                cb.addEventListener('change', function(e) {
                    let id = parseInt(this.dataset.id);
                    if (this.checked) selectedStudents.add(id);
                    else selectedStudents.delete(id);
                    updateSelectedCount();
                });
            });
        }

        function updateSelectedCount() {
            document.getElementById('selectedCount').innerText = selectedStudents.size;
        }

        // view / edit / delete functions
        window.viewStudent = function(id) {
            document.getElementById('profileModal').classList.add('active');
            document.getElementById('profileDetail').innerHTML = `Showing details for student ID ${id} – personal, academic, schedule, attendance, fees.`;
        };
        window.editStudent = function(id) {
            alert('Edit student ' + id);
        };

        const addModal = document.getElementById('addModal');
        document.getElementById('addStudentBtn').addEventListener('click', ()=> addModal.classList.add('active'));
        document.getElementById('cancelAdd').addEventListener('click', ()=> addModal.classList.remove('active'));

        document.getElementById('saveStudent').addEventListener('click', ()=> {
            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('name', document.getElementById('sName').value);
            fd.append('roll', document.getElementById('sRoll').value);
            fd.append('dept', document.getElementById('sDept').value);
            fd.append('email', document.getElementById('sEmail').value);

            fetch('studentM.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    addModal.classList.remove('active');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        window.deleteStudent = function(id) {
            if(confirm('Are you sure you want to permanently delete this student?')) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);
                fetch('studentM.php', { method: 'POST', body: fd }).then(() => {
                    showToast('Deleted');
                    setTimeout(() => location.reload(), 1000);
                });
            }
        };

        document.getElementById('closeProfile').addEventListener('click', ()=> {
            document.getElementById('profileModal').classList.remove('active');
        });

        // search debounce
        let timer;
        document.getElementById('searchText').addEventListener('input', ()=> {
            clearTimeout(timer);
            timer = setTimeout(renderStudents, 250);
        });
        document.getElementById('deptFilter').addEventListener('change', renderStudents);
        document.getElementById('yearFilter').addEventListener('change', renderStudents);
        document.getElementById('statusFilter').addEventListener('change', renderStudents);

        // grid / table toggle
        const gridView = document.getElementById('studentsGridContainer');
        const tableView = document.getElementById('tableView');
        document.getElementById('gridViewBtn').addEventListener('click', ()=> {
            gridView.style.display = 'grid';
            tableView.style.display = 'none';
            document.querySelectorAll('.view-btn').forEach(b=>b.classList.remove('active'));
            document.getElementById('gridViewBtn').classList.add('active');
        });
        document.getElementById('tableViewBtn').addEventListener('click', ()=> {
            gridView.style.display = 'none';
            tableView.style.display = 'block';
            document.querySelectorAll('.view-btn').forEach(b=>b.classList.remove('active'));
            document.getElementById('tableViewBtn').classList.add('active');
            renderStudents(); // refresh table
        });

        // bulk actions
        document.getElementById('bulkPromote').addEventListener('click', ()=> {
            alert(`Promote ${selectedStudents.size} students to next semester`);
        });
        document.getElementById('bulkGraduate').addEventListener('click', ()=> {
            alert(`Graduate ${selectedStudents.size} students`);
        });
        document.getElementById('exportSelected').addEventListener('click', ()=> {
            alert(`Export ${selectedStudents.size} records`);
        });

        // charts
        new Chart(document.getElementById('deptChart'), {
            type:'bar', data: { labels:['CSE','ECE','MECH'], datasets:[{ data:[34,28,22], backgroundColor:'#0a3b5b' }] }
        });
        new Chart(document.getElementById('yearChart'), {
            type:'doughnut', data: { labels:['Y1','Y2','Y3','Y4'], datasets:[{ data:[28,26,24,22], backgroundColor:['#0a3b5b','#1e4f6e','#f4c542','#cbd5e1'] }] }
        });
        new Chart(document.getElementById('performanceChart'), {
            type:'line', data: { labels:['<6','6-7','7-8','8-9','9-10'], datasets:[{ data:[5,18,40,30,7] }] }
        });

        function showToast(msg) {
            let t = document.getElementById('toast');
            t.innerHTML = msg;
            t.style.display = 'flex';
            setTimeout(()=> t.style.display = 'none', 2000);
        }

        // graduate simulation
        document.getElementById('graduateBtn').addEventListener('click', ()=> showToast('Graduate process initiated'));

        // initial render
        renderStudents();
        updateSelectedCount();
    })();
</script>
</body>
</html>

