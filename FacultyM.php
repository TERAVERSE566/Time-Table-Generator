<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle Add/Delete POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'add') {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $empId = mysqli_real_escape_string($conn, trim($_POST['empId']));
        $dept = mysqli_real_escape_string($conn, $_POST['dept']);
        $spec = mysqli_real_escape_string($conn, trim($_POST['spec']));
        $password = trim($_POST['password']);
        
        if (empty($name) || empty($email)) {
             echo json_encode(['success' => false, 'message' => 'Name and Email are required.']);
             exit();
        }

        if (empty($_POST['id'])) {
            // Check existing email
            $chk = $conn->query("SELECT id FROM users WHERE email='$email'");
            if($chk->num_rows > 0) {
                 echo json_encode(['success' => false, 'message' => 'Email already registered.']);
                 exit();
            }
            $hash = password_hash(empty($password) ? 'password123' : $password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (name, email, password_hash, role, phone, department, employee_id, specialization, availability) VALUES ('$name', '$email', '$hash', 'faculty', '$phone', '$dept', '$empId', '$spec', 'available')";
        } else {
            $id = (int)$_POST['id'];
            $sql = "UPDATE users SET name='$name', email='$email', phone='$phone', department='$dept', employee_id='$empId', specialization='$spec' WHERE id=$id";
        }

        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Faculty saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM users WHERE id=$id AND role='faculty'");
        echo json_encode(['success' => true]);
        exit();
    }
}

// Fetch Faculty Data
$facultyArray = [];
$res = $conn->query("SELECT * FROM users WHERE role='faculty' ORDER BY name ASC");
$avCount = 0; $lvCount = 0;
if ($res) {
    while($row = $res->fetch_assoc()) {
        $avail = $row['availability'] ?? 'available';
        if ($avail === 'available') $avCount++;
        else if ($avail === 'leave') $lvCount++;

        // Calculate initials
        $parts = explode(' ', trim($row['name']));
        $initials = '';
        if (count($parts) >= 2) {
            $first = str_replace(['Dr.','Prof.','Mr.','Ms.'], '', $parts[0]);
            if (empty($first) && isset($parts[1])) $first = $parts[1];
            $initials = strtoupper(substr(trim($first), 0, 1) . substr($parts[count($parts)-1], 0, 1));
        } else {
            $initials = strtoupper(substr($row['name'], 0, 2));
        }

        $facultyArray[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'empId' => $row['employee_id'] ?: 'FAC'.$row['id'],
            'dept' => $row['department'],
            'spec' => $row['specialization'] ?: 'General',
            'email' => $row['email'],
            'phone' => $row['phone'],
            'avail' => $avail,
            'load' => rand(3, 9) / 10, // Simulated course load
            'head' => false, // Simulated
            'initials' => $initials
        ];
    }
}

// Fetch Departments
$deptsArray = [];
$dRes = $conn->query("SELECT code, name FROM departments");
if ($dRes) {
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
    <title>TimetableGen · Faculty Management</title>
    <!-- Font Awesome 6 (free) & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- FullCalendar (for availability & leave calendar) -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
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
            --bg-light: #f8fafc;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-md: 0 12px 30px -10px rgba(10,59,91,0.15);
            --border-radius: 1.8rem;
        }

        body {
            background: var(--bg-light);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1440px;
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
            font-size: 2.4rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
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
            padding: 0.8rem 1.8rem;
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
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .btn-outline:hover { background: #e6edf5; }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: var(--shadow-md);
        }
        .stat-icon { font-size: 2.5rem; }
        .stat-info h3 { font-weight: 400; color: var(--gray-600); }
        .stat-info .value { font-size: 2.2rem; font-weight: 700; color: var(--navy); }

        /* filter bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            background: white;
            padding: 1.2rem 1.8rem;
            border-radius: 5rem;
            margin-bottom: 2.5rem;
            box-shadow: var(--shadow-md);
        }
        .search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gray-100);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            flex: 2 1 250px;
        }
        .search-box i { color: var(--gray-600); }
        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            width: 100%;
        }
        .filter-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            flex: 3;
        }
        .filter-group select, .filter-group .toggle {
            background: var(--gray-100);
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-weight: 500;
            color: var(--navy);
            cursor: pointer;
        }

        /* faculty grid */
        .faculty-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.8rem;
        }
        .faculty-card {
            background: white;
            border-radius: 2.2rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            transition: 0.2s;
            border-left: 6px solid var(--gold);
        }
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
        .name h3 { font-size: 1.4rem; color: var(--navy); }
        .name p { color: var(--gray-600); font-size: 0.9rem; }

        .details {
            margin: 0.8rem 0;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            font-size: 0.95rem;
        }
        .badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .available { background: #d1fae5; color: #065f46; }
        .busy { background: #fee2e2; color: #991b1b; }
        .leave { background: #fed7aa; color: #92400e; }

        .course-load {
            margin: 1rem 0;
        }
        .progress-bar {
            background: #e2e8f0;
            height: 8px;
            border-radius: 20px;
        }
        .progress-fill {
            height: 8px;
            background: var(--navy);
            border-radius: 20px;
            width: 60%;
        }
        .action-icons {
            display: flex;
            gap: 1.2rem;
            justify-content: flex-end;
            margin-top: 0.8rem;
            color: var(--gray-600);
        }
        .action-icons i {
            cursor: pointer;
            font-size: 1.2rem;
            transition: 0.1s;
        }
        .action-icons i:hover { color: var(--navy); }

        /* MODALS (Add/Edit, Details, Leave) */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
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
            padding: 2.2rem;
            max-width: 800px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-content h2 { font-size: 2rem; color: var(--navy); }

        .tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid var(--gray-300);
            margin-bottom: 1.5rem;
        }
        .tab {
            padding: 0.6rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
        }
        .tab.active { color: var(--navy); border-bottom: 3px solid var(--gold); }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label { font-weight: 500; display: block; margin-bottom: 0.3rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1px solid var(--gray-300);
            border-radius: 50px;
        }

        .calendar-placeholder {
            background: #f1f5f9;
            border-radius: 2rem;
            padding: 2rem;
            text-align: center;
        }

        .toast {
            position: fixed;
            bottom: 30px; right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 3000;
        }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .action-group { flex-wrap: wrap; width: 100%; }
            .action-group button { flex: 1; min-width: 120px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .filter-bar { flex-direction: column; align-items: stretch; gap: 1rem; margin-top: 1rem; }
            .filter-group, .search-box { width: 100%; box-sizing: border-box; }
            .filter-group select { width: 100%; box-sizing: border-box; }
            .modal-content { width: 95%; padding: 1.5rem; border-radius: 1.5rem; }
            .form-grid { grid-template-columns: 1fr; }
            .tabs { flex-wrap: wrap; }
            .detail-panel { padding: 1rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-section">
        <div class="header-title">
            <h1>👨‍🏫 Faculty Management</h1>
            <p>Manage faculty members, their availability, and assignments</p>
        </div>
        <div class="action-group">
            <button class="btn-outline" onclick="window.location.href='admin.php'"><i class="fas fa-arrow-left"></i> Dashboard</button>
            <button class="btn-primary" id="addFacultyBtn"><i class="fas fa-plus"></i> Add Faculty</button>
            <button class="btn-outline" id="importCsv"><i class="fas fa-upload"></i> Import CSV</button>
            <button class="btn-outline" id="exportCsv"><i class="fas fa-download"></i> Export List</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">👨‍🏫</span><div class="stat-info"><span class="value"><?= count($facultyArray) ?></span><h3>Total Faculty</h3></div></div>
        <div class="stat-card"><span class="stat-icon">✅</span><div class="stat-info"><span class="value"><?= $avCount ?></span><h3>Available Today</h3></div></div>
        <div class="stat-card"><span class="stat-icon">🏖️</span><div class="stat-info"><span class="value"><?= $lvCount ?></span><h3>On Leave</h3></div></div>
        <div class="stat-card"><span class="stat-icon">👑</span><div class="stat-info"><span class="value">--</span><h3>Department Heads</h3></div></div>
    </div>

    <!-- filter bar -->
    <div class="filter-bar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search name or ID...">
        </div>
        <div class="filter-group">
            <select id="deptFilter">
                <option value="">All departments</option>
                <?php foreach($deptsArray as $d): ?>
                    <option value="<?= htmlspecialchars($d['code']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select id="specializationFilter">
                <option value="">Specialization</option>
                <option value="AI">AI</option>
                <option value="Algebra">Algebra</option>
            </select>
            <select id="availabilityFilter">
                <option value="">Availability</option>
                <option value="available">Available</option>
                <option value="busy">Busy</option>
                <option value="leave">Leave</option>
            </select>
            <div class="toggle"><i class="fas fa-toggle-off"></i> Active only</div>
        </div>
    </div>

    <!-- faculty grid (card based) -->
    <div id="facultyGrid" class="faculty-grid"></div>
</div>

<!-- ADD/EDIT FACULTY MODAL (wizard style) -->
<div id="facultyModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">➕ Add Faculty</h2>
        <div class="tabs">
            <span class="tab active">Personal</span>
            <span class="tab">Professional</span>
            <span class="tab">Account & pref</span>
            <span class="tab">Documents</span>
        </div>
        <form id="facultyForm">
            <!-- Personal (simplified) -->
            <div><h4>👤 Personal</h4></div>
            <div class="form-grid">
                <div class="form-group"><label>Full name</label><input id="fName" placeholder="John Doe" value="Dr. Sarah Chen"></div>
                <div class="form-group"><label>Email</label><input type="email" id="fEmail" value="sarah.chen@college.edu"></div>
                <div class="form-group"><label>Phone</label><input id="fPhone" value="+1 555 1234"></div>
                <div class="form-group"><label>DOB</label><input type="date" id="fDob" value="1980-01-01"></div>
            </div>
            <div><h4>💼 Professional</h4></div>
            <div class="form-grid">
                <div class="form-group"><label>Employee ID</label><input id="fEmpId" placeholder="FAC102"></div>
                <div class="form-group"><label>Department</label>
                    <select id="fDept">
                        <?php foreach($deptsArray as $d): ?>
                            <option value="<?= htmlspecialchars($d['code']) ?>"><?= htmlspecialchars($d['code']) ?> - <?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Designation</label><input id="fDesig" value="Professor"></div>
                <div class="form-group"><label>Specialization</label><input id="fSpec" value="Machine Learning"></div>
                <div class="form-group"><label>Qualification</label><input id="fQual" value="PhD"></div>
                <div class="form-group"><label>Experience</label><input id="fExp" value="12 years"></div>
            </div>
            <div><h4>🔑 Account & availability</h4></div>
            <div class="form-grid">
                <div class="form-group"><label>Username</label><input id="fUsername" value="sarah.chen"></div>
                <div class="form-group"><label>Password</label><input type="password" id="fPassword" value="dummy"><button type="button" class="btn-outline" style="padding:0.3rem 0.8rem;">Auto‑generate</button></div>
            </div>
            <div><h4>📂 Documents</h4> <i>Upload placeholders</i> </div>
            <div class="modal-actions" style="display:flex; gap:1rem; margin-top:2rem;">
                <button type="button" class="btn-primary" id="saveFaculty">Save Faculty</button>
                <button type="button" class="btn-outline" id="closeModal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- DETAILS MODAL (with tabs) -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <h2>👁️ Faculty details</h2>
        <div class="tabs" id="detailTabs">
            <span class="tab active" data-tab="personal">Personal</span>
            <span class="tab" data-tab="courses">Courses</span>
            <span class="tab" data-tab="schedule">Schedule Preview</span>
            <span class="tab" data-tab="leave">Leave History</span>
            <span class="tab" data-tab="metrics">Metrics</span>
        </div>
        <div id="detailContent">
            <!-- dynamic content -->
            <p>Selected faculty details appear here.</p>
        </div>
        <button class="btn-outline" style="margin-top:1.5rem;" id="closeDetails">Close</button>
    </div>
</div>

<!-- LEAVE REQUEST SECTION -->
<div style="margin-top: 3rem; background: white; border-radius: 2.5rem; padding: 1.8rem; box-shadow: var(--shadow-md);">
    <h3>🏖️ Pending Leave Requests <span style="font-size:0.9rem; background:var(--gold); padding:0.3rem 1rem; border-radius:40px;">3 requests</span></h3>
    <div style="display:flex; gap:2rem; flex-wrap:wrap; align-items:center;">
        <div><i class="fas fa-user"></i> Prof. Miller <span class="badge warning" style="background:#fed7aa;">sick leave</span> <button class="btn-outline" style="padding:0.2rem 1rem;">✓ Approve</button> <button class="btn-outline">✗ Reject</button></div>
        <div><i class="fas fa-user"></i> Dr. Evans <span class="badge warning">conference</span> <button>Approve</button></div>
        <div><i class="fas fa-user"></i> Ms. Davis <span class="badge warning">personal</span> <button>Approve</button></div>
    </div>
    <div id="leaveCalendar" style="max-width:600px; margin-top:1.5rem; background: #f1f5f9; border-radius: 2rem; padding:1rem;">📅 Leave calendar would show here (FullCalendar)</div>
</div>

<!-- AVAILABILITY CALENDAR (weekly) -->
<div style="margin-top: 2rem; background: white; border-radius: 2rem; padding: 1.8rem;">
    <h3>⏰ Faculty Availability (weekly view demo)</h3>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <span class="badge available" style="background:#d1fae5;">Available</span>
        <span class="badge busy">Busy</span>
        <span class="badge leave">Leave</span>
        <span style="margin-left:auto;"><i class="fas fa-arrows-alt"></i> Drag to mark unavailable</span>
    </div>
    <div style="background: #f1f5f9; height: 180px; border-radius: 2rem; margin-top: 1rem; padding: 1.5rem; display: flex; gap: 0.3rem;">
        <div style="flex:1; background:#d1fae5; border-radius:1rem;">Mon<br>9-12</div>
        <div style="flex:1; background:#d1fae5;">Mon<br>1-4</div>
        <div style="flex:1; background:#fee2e2;">Tue<br>busy</div>
        <div style="flex:1; background:#fed7aa;">Wed<br>leave</div>
        <div style="flex:1; background:#d1fae5;">Thu<br>free</div>
    </div>
    <button class="btn-primary" style="margin-top:1rem;">Save availability</button>
</div>

<!-- toast -->
<div id="toast" class="toast">✅ Faculty updated</div>

<script>
    (function() {
    (function() {
        // dynamic faculty from PHP database query
        let facultyList = <?= json_encode($facultyArray) ?>;

        // render grid
        const grid = document.getElementById('facultyGrid');
        function renderGrid(filtered = facultyList) {
            let html = '';
            filtered.forEach(f => {
                let availClass = f.avail === 'available' ? 'available' : (f.avail === 'busy' ? 'busy' : 'leave');
                let loadPercent = Math.round(f.load * 100);
                html += `
                <div class="faculty-card" data-id="${f.id}">
                    <div class="card-header">
                        <div class="avatar">${f.initials}</div>
                        <div class="name"><h3>${f.name}</h3><p>${f.empId} · ${f.dept}</p></div>
                    </div>
                    <div class="details">
                        <div><i class="fas fa-flask"></i> ${f.spec}</div>
                        <div><i class="fas fa-envelope"></i> ${f.email}</div>
                        <div><i class="fas fa-phone"></i> ${f.phone}</div>
                        <div><span class="badge ${availClass}">${f.avail}</span> ${f.head ? '👑 Dept Head' : ''}</div>
                    </div>
                    <div class="course-load">Course load <span>${loadPercent}%</span><div class="progress-bar"><div class="progress-fill" style="width:${loadPercent}%;"></div></div></div>
                    <div class="action-icons">
                        <i class="fas fa-eye" title="View" onclick="viewFaculty('${f.id}')"></i>
                        <i class="fas fa-edit" title="Edit" onclick="editFaculty('${f.id}')"></i>
                        <i class="fas fa-trash" title="Delete" onclick="deleteFaculty('${f.id}')"></i>
                        <i class="fas fa-calendar-alt" title="Schedule"></i>
                    </div>
                </div>
                `;
            });
            grid.innerHTML = html;
        }

        // filter function
        function filterFaculty() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const dept = document.getElementById('deptFilter').value;
            const spec = document.getElementById('specializationFilter').value;
            const avail = document.getElementById('availabilityFilter').value;
            let filtered = facultyList.filter(f => 
                (f.name.toLowerCase().includes(search) || f.empId.toLowerCase().includes(search)) &&
                (dept === '' || f.dept === dept) &&
                (avail === '' || f.avail === avail)
            );
            renderGrid(filtered);
        }

        document.getElementById('searchInput').addEventListener('input', filterFaculty);
        document.getElementById('deptFilter').addEventListener('change', filterFaculty);
        document.getElementById('availabilityFilter').addEventListener('change', filterFaculty);

        // modals
        const facultyModal = document.getElementById('facultyModal');
        const modalTitle = document.getElementById('modalTitle');
        const facultyForm = document.getElementById('facultyForm');
        const addBtn = document.getElementById('addFacultyBtn');
        const closeModal = document.getElementById('closeModal');
        const saveFaculty = document.getElementById('saveFaculty');
        const deleteModal = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDeleteBtn = document.getElementById('cancelDelete');
        const detailsModal = document.getElementById('detailsModal');
        const closeDetails = document.getElementById('closeDetails');
        const toast = document.getElementById('toast');

        addBtn.addEventListener('click', () => {
            editingId = null;
            modalTitle.innerText = '➕ Add Faculty';
            if(facultyForm) facultyForm.reset();
            facultyModal.classList.add('active');
        });

        closeModal.addEventListener('click', () => facultyModal.classList.remove('active'));
        closeDetails.addEventListener('click', () => detailsModal.classList.remove('active'));

        let editingId = null;
        window.editFaculty = function(id) {
            const fac = facultyList.find(f => f.id == id);
            if (!fac) return;
            editingId = id;
            document.getElementById('modalTitle').innerText = '✏️ Edit Faculty';
            document.getElementById('fName').value = fac.name;
            document.getElementById('fEmail').value = fac.email;
            document.getElementById('fPhone').value = fac.phone;
            document.getElementById('fEmpId').value = fac.empId;
            document.getElementById('fDept').value = fac.dept;
            document.getElementById('fSpec').value = fac.spec;
            document.getElementById('fPassword').value = '********';
            document.getElementById('fPassword').disabled = true; // don't edit password here
            facultyModal.classList.add('active');
        };

        window.viewFaculty = function(id) {
            detailsModal.classList.add('active');
            document.getElementById('detailContent').innerHTML = `<p>Faculty ID ${id} – detailed information with courses, schedule preview, leave history and metrics would appear here.</p>`;
        };

        window.deleteFaculty = function(id) {
            if (confirm('Are you sure you want to permanently delete this faculty member?')) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);
                fetch('FacultyM.php', { method: 'POST', body: fd }).then(() => {
                    showToast('Faculty deleted');
                    setTimeout(() => location.reload(), 1000);
                });
            }
        };

        saveFaculty.addEventListener('click', () => {
            const fd = new FormData();
            fd.append('action', 'add');
            if (editingId) fd.append('id', editingId);
            fd.append('name', document.getElementById('fName').value);
            fd.append('email', document.getElementById('fEmail').value);
            fd.append('phone', document.getElementById('fPhone').value);
            fd.append('empId', document.getElementById('fEmpId').value);
            fd.append('dept', document.getElementById('fDept').value);
            fd.append('spec', document.getElementById('fSpec').value);
            fd.append('password', document.getElementById('fPassword').value);

            fetch('FacultyM.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    facultyModal.classList.remove('active');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        function showToast(msg) {
            toast.innerHTML = `<i class="fas fa-check-circle"></i> ${msg}`;
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 2500);
        }

        // Import/Export simulation
        document.getElementById('importCsv').addEventListener('click', ()=> alert('CSV import simulation'));
        document.getElementById('exportCsv').addEventListener('click', ()=> alert('CSV export simulation'));

        // initial render
        renderGrid();

        // Leave calendar dummy
        // (FullCalendar minimal placeholder)
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof Calendar !== 'undefined') {
                var calendarEl = document.getElementById('leaveCalendar');
                if(calendarEl) {
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        height: 250,
                        events: [
                            { title: 'Miller sick', start: '2025-03-20' },
                            { title: 'Evans conf', start: '2025-03-22' }
                        ]
                    });
                    calendar.render();
                }
            } else {
                document.getElementById('leaveCalendar').innerHTML = '📅 FullCalendar not loaded (simplified)';
            }
        });
    })();
</script>

<!-- load FullCalendar (already included) -->
<script src="theme.js"></script>
</body>
</html>

