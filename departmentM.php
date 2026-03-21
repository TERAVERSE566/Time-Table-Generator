<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle Add/Edit/Delete requests via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'add') {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $code = mysqli_real_escape_string($conn, trim(strtoupper($_POST['code'])));
        $hod = mysqli_real_escape_string($conn, $_POST['hod']);
        $est_year = (int)$_POST['est_year'];
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $desc = mysqli_real_escape_string($conn, trim($_POST['desc']));

        if(empty($name) || empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Name and Code are required']);
            exit();
        }

        // Check duplicate code
        if(empty($_POST['id'])) {
            $check = $conn->query("SELECT id FROM departments WHERE code='$code'");
            if($check->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Department code already exists']);
                exit();
            }
            $sql = "INSERT INTO departments (name, code, hod, est_year, email, phone, status, description) VALUES ('$name', '$code', '$hod', $est_year, '$email', '$phone', '$status', '$desc')";
        } else {
            $id = (int)$_POST['id'];
            $sql = "UPDATE departments SET name='$name', code='$code', hod='$hod', est_year=$est_year, email='$email', phone='$phone', status='$status', description='$desc' WHERE id=$id";
        }

        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Department saved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM departments WHERE id=$id");
        echo json_encode(['success' => true]);
        exit();
    }
}

// Fetch DB Data
$deptsArray = [];
$res = $conn->query("SELECT * FROM departments ORDER BY name ASC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        // simulate relations that aren't hooked up yet
        $coursesRes = $conn->query("SELECT COUNT(*) as c FROM courses WHERE department='" . $row['code'] . "'");
        $cCount = $coursesRes ? $coursesRes->fetch_assoc()['c'] : 0;
        
        $deptsArray[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'code' => $row['code'],
            'hod' => $row['hod'],
            'status' => $row['status'],
            'est' => $row['est_year'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'description' => $row['description'],
            'faculty' => rand(15, 45), // simulated
            'students' => rand(300, 1000), // simulated
            'courses' => $cCount
        ];
    }
}

// Stats
$totalFaculty = 0; $totalStudents = 0; $totalCourses = 0;
foreach($deptsArray as $d) {
    $totalFaculty += $d['faculty'];
    $totalStudents += $d['students'];
    $totalCourses += $d['courses'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Department Management</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --bg-light: #f3f6fb;
            --white: #ffffff;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-md: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.02);
            --border-radius: 1.5rem;
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

        /* header area */
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
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: var(--shadow-md);
        }
        .btn-primary:hover {
            background: var(--navy-light);
            transform: scale(1.02);
        }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem 1.2rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: var(--shadow-md);
        }
        .stat-icon {
            font-size: 2.8rem;
        }
        .stat-info h3 {
            font-size: 1rem;
            font-weight: 400;
            color: var(--gray-600);
        }
        .stat-info .value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--navy);
        }

        /* search & filter bar */
        .search-section {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .search-box {
            background: white;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.02);
            flex: 1 1 300px;
        }
        .search-box i {
            color: var(--gray-600);
        }
        .search-box input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 1rem;
        }
        .filter-group {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group select, .filter-group button {
            padding: 0.6rem 1.5rem;
            border-radius: 40px;
            border: 1px solid var(--gray-300);
            background: white;
            font-weight: 500;
            cursor: pointer;
        }
        .export-btn {
            background: var(--navy);
            color: white;
            border: none;
        }

        /* departments grid (cards) */
        .dept-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.8rem;
        }
        .dept-card {
            background: white;
            border-radius: 2rem;
            padding: 1.6rem 1.5rem;
            box-shadow: var(--shadow-md);
            transition: 0.2s;
            border-left: 8px solid var(--gold);
            position: relative;
        }
        .dept-card.inactive {
            border-left-color: var(--gray-300);
            opacity: 0.8;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dept-icon {
            font-size: 2.5rem;
        }
        .status-badge {
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .active { background: #d1fae5; color: #065f46; }
        .inactive { background: #fee2e2; color: #991b1b; }

        .dept-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
            margin: 0.5rem 0 0.2rem;
        }
        .dept-code {
            color: var(--gold);
            font-weight: 600;
            background: #fef3c7;
            display: inline-block;
            padding: 0.2rem 1rem;
            border-radius: 30px;
            margin-bottom: 0.8rem;
        }
        .hod {
            font-weight: 500;
            margin-bottom: 0.8rem;
        }
        .hod i { color: var(--navy); }

        .metrics {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
            color: var(--gray-600);
        }
        .metric-item {
            text-align: center;
        }
        .metric-value {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--navy);
        }

        /* progress bar (capacity) */
        .capacity-bar {
            background: #e2e8f0;
            height: 8px;
            border-radius: 10px;
            margin: 1rem 0;
        }
        .capacity-fill {
            height: 8px;
            border-radius: 10px;
            background: var(--navy);
            width: 70%;
        }

        .action-icons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 0.8rem;
            font-size: 1.2rem;
            color: var(--gray-600);
        }
        .action-icons i {
            cursor: pointer;
            transition: 0.1s;
        }
        .action-icons i:hover { color: var(--navy); }

        /* MODAL (add/edit) + backdrop */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 3rem;
            padding: 2.5rem;
            max-width: 600px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        .modal-content h2 {
            font-size: 2rem;
            color: var(--navy);
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        .form-group label {
            font-weight: 500;
            color: var(--gray-600);
            display: block;
            margin-bottom: 0.3rem;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 1.5px solid var(--gray-300);
            border-radius: 50px;
            font-size: 1rem;
        }
        .form-group textarea { border-radius: 1.5rem; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        /* delete confirm modal (small) */
        .delete-modal .modal-content {
            max-width: 400px;
            text-align: center;
        }

        /* notification toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            box-shadow: var(--shadow-md);
            display: none;
            align-items: center;
            gap: 0.8rem;
            z-index: 2000;
        }
        .toast.show { display: flex; }

        /* details panel (quick view) */
        .detail-panel {
            background: white;
            border-radius: 2.5rem;
            padding: 2rem;
            margin-top: 2rem;
            display: none;
        }
        .detail-panel.active { display: block; }
        .detail-tabs {
            display: flex;
            gap: 1rem;
            border-bottom: 2px solid var(--gray-300);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .tab {
            padding: 0.5rem 1.5rem;
            cursor: pointer;
            font-weight: 600;
        }
        .tab.active { color: var(--navy); border-bottom: 3px solid var(--gold); }

        /* avatar group */
        .avatar-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        .avatar {
            width: 40px; height: 40px;
            background: var(--navy-light);
            border-radius: 50%;
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* responsiveness */
        @media (max-width: 600px) {
            body { padding: 1rem; }
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-section">
        <div class="header-title">
            <h1>🏛️ Department Management</h1>
            <p>Manage academic departments and their details</p>
        </div>
        <button class="btn-primary" id="addDeptBtn"><i class="fas fa-plus-circle"></i> Add New Department</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">🏛️</span><div class="stat-info"><span class="value"><?= count($deptsArray) ?></span><h3>Total Departments</h3></div></div>
        <div class="stat-card"><span class="stat-icon">👨‍🏫</span><div class="stat-info"><span class="value"><?= $totalFaculty ?></span><h3>Total Faculty</h3></div></div>
        <div class="stat-card"><span class="stat-icon">👩‍🎓</span><div class="stat-info"><span class="value"><?= number_format($totalStudents) ?></span><h3>Total Students</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📚</span><div class="stat-info"><span class="value"><?= $totalCourses ?></span><h3>Active Courses</h3></div></div>
    </div>

    <!-- search & filter -->
    <div class="search-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search by name or code...">
        </div>
        <div class="filter-group">
            <select id="statusFilter">
                <option value="all">All status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select id="sortSelect">
                <option value="name">Sort by name</option>
                <option value="faculty">Faculty count</option>
                <option value="students">Student count</option>
            </select>
            <button class="export-btn" id="exportCsv"><i class="fas fa-download"></i> Export CSV</button>
        </div>
    </div>

    <!-- departments grid (cards) -->
    <div id="deptGrid" class="dept-grid">
        <!-- filled via JS -->
    </div>

    <!-- quick detail panel (hidden initially) -->
    <div id="detailPanel" class="detail-panel">
        <div class="detail-tabs">
            <span class="tab active" data-tab="overview">Overview</span>
            <span class="tab" data-tab="faculty">Faculty</span>
            <span class="tab" data-tab="courses">Courses</span>
            <span class="tab" data-tab="students">Students</span>
            <span class="tab" data-tab="timetable">Timetable</span>
        </div>
        <div id="detailContent">Select a department to view details</div>
    </div>
</div>

<!-- ADD/EDIT MODAL -->
<div id="deptModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">➕ Add Department</h2>
        <form id="deptForm">
            <div class="form-group">
                <label>Department Name</label>
                <input type="text" id="deptName" placeholder="e.g. Computer Science" required>
            </div>
            <div class="form-group">
                <label>Department Code</label>
                <input type="text" id="deptCode" placeholder="CSE" maxlength="6" style="text-transform:uppercase" required>
            </div>
            <div class="form-group">
                <label>Head of Department</label>
                <select id="deptHod">
                    <option>Dr. Sarah Chen</option>
                    <option>Prof. James Miller</option>
                    <option>Dr. Emily Davis</option>
                    <option>Dr. Robert Brown</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="deptDesc" rows="2">Leading department with focus on AI</textarea>
            </div>
            <div class="form-group">
                <label>Establishment Year</label>
                <input type="date" id="deptEst" value="2010-01-01">
            </div>
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" id="deptEmail" value="cse@college.edu">
            </div>
            <div class="form-group">
                <label>Contact Phone</label>
                <input type="text" id="deptPhone" value="+1 555 1234">
            </div>
            <div class="form-group checkbox-group">
                <label>Status:</label>
                <input type="checkbox" id="deptStatus" checked> <span>Active</span>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-primary" id="saveDept">Save</button>
                <button type="button" class="btn-secondary" id="closeModal" style="background:#e2e8f0; padding:0.8rem 2rem; border-radius:60px;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" class="modal delete-modal">
    <div class="modal-content">
        <h3>🗑️ Confirm Delete</h3>
        <p>Are you sure you want to delete <span id="deleteDeptName"></span>?</p>
        <div class="modal-actions">
            <button id="confirmDelete" class="btn-primary" style="background:var(--danger);">Delete</button>
            <button id="cancelDelete" class="btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATION -->
<div id="toast" class="toast">✅ Department saved</div>

<script>
    (function() {
        let departments = <?= json_encode($deptsArray) ?>;

        // current edit id (null for add)
        let editingId = null;
        let deleteId = null;

        // DOM elements
        const deptGrid = document.getElementById('deptGrid');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const sortSelect = document.getElementById('sortSelect');
        const addBtn = document.getElementById('addDeptBtn');
        const modal = document.getElementById('deptModal');
        const closeModal = document.getElementById('closeModal');
        const modalTitle = document.getElementById('modalTitle');
        const deptForm = document.getElementById('deptForm');
        const saveBtn = document.getElementById('saveDept');
        const deleteModal = document.getElementById('deleteModal');
        const deleteDeptName = document.getElementById('deleteDeptName');
        const confirmDeleteBtn = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');
        const toast = document.getElementById('toast');
        const detailPanel = document.getElementById('detailPanel');
        const exportBtn = document.getElementById('exportCsv');

        // render department cards
        function renderDepts() {
            let filtered = [...departments];
            // search filter
            const searchTerm = searchInput.value.toLowerCase();
            if (searchTerm) {
                filtered = filtered.filter(d => d.name.toLowerCase().includes(searchTerm) || d.code.toLowerCase().includes(searchTerm));
            }
            // status filter
            const statusVal = statusFilter.value;
            if (statusVal !== 'all') {
                filtered = filtered.filter(d => d.status === statusVal);
            }
            // sort
            const sortBy = sortSelect.value;
            if (sortBy === 'faculty') filtered.sort((a,b) => b.faculty - a.faculty);
            else if (sortBy === 'students') filtered.sort((a,b) => b.students - a.students);
            else filtered.sort((a,b) => a.name.localeCompare(b.name));

            let html = '';
            filtered.forEach(dept => {
                const capacityPercent = Math.min(100, Math.round((dept.students / 1200) * 100)); // just for demo
                html += `
                <div class="dept-card ${dept.status === 'inactive' ? 'inactive' : ''}" data-id="${dept.id}">
                    <div class="card-header">
                        <span class="dept-icon">🏛️</span>
                        <span class="status-badge ${dept.status}">${dept.status === 'active' ? '● Active' : '○ Inactive'}</span>
                    </div>
                    <div class="dept-name">${dept.name}</div>
                    <div class="dept-code">${dept.code}</div>
                    <div class="hod"><i class="fas fa-user-tie"></i> ${dept.hod}</div>
                    <div class="metrics">
                        <div class="metric-item"><span class="metric-value">${dept.faculty}</span><br>Faculty</div>
                        <div class="metric-item"><span class="metric-value">${dept.students}</span><br>Students</div>
                        <div class="metric-item"><span class="metric-value">${dept.courses}</span><br>Courses</div>
                    </div>
                    <div class="capacity-bar"><div class="capacity-fill" style="width: ${capacityPercent}%;"></div></div>
                    <div class="action-icons">
                        <i class="fas fa-eye" title="View details" onclick="viewDept('${dept.id}')"></i>
                        <i class="fas fa-edit" title="Edit" onclick="editDept('${dept.id}')"></i>
                        <i class="fas fa-trash" title="Delete" onclick="openDeleteModal('${dept.id}','${dept.name}')"></i>
                    </div>
                </div>
                `;
            });
            deptGrid.innerHTML = html;
        }

        // show toast
        function showToast(msg, type = 'success') {
            toast.innerHTML = `<i class="fas fa-check-circle"></i> ${msg}`;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }

        // open modal for add
        addBtn.addEventListener('click', () => {
            editingId = null;
            modalTitle.innerText = '➕ Add Department';
            document.getElementById('deptName').value = '';
            document.getElementById('deptCode').value = '';
            document.getElementById('deptHod').value = 'Dr. Sarah Chen';
            document.getElementById('deptDesc').value = '';
            document.getElementById('deptEst').value = '2015-01-01';
            document.getElementById('deptEmail').value = '';
            document.getElementById('deptPhone').value = '';
            document.getElementById('deptStatus').checked = true;
            modal.classList.add('active');
        });

        // edit: fill form
        window.editDept = function(id) {
            const dept = departments.find(d => d.id === id);
            if (!dept) return;
            editingId = id;
            modalTitle.innerText = '✏️ Edit Department';
            document.getElementById('deptName').value = dept.name;
            document.getElementById('deptCode').value = dept.code;
            document.getElementById('deptHod').value = dept.hod;
            document.getElementById('deptDesc').value = dept.description || '';
            document.getElementById('deptEst').value = dept.est + '-01-01'; // dummy
            document.getElementById('deptEmail').value = dept.email;
            document.getElementById('deptPhone').value = dept.phone;
            document.getElementById('deptStatus').checked = dept.status === 'active';
            modal.classList.add('active');
        };

        // view details (dummy)
        window.viewDept = function(id) {
            const dept = departments.find(d => d.id === id);
            if (!dept) return;
            detailPanel.classList.add('active');
            document.getElementById('detailContent').innerHTML = `Showing details for <b>${dept.name}</b> (${dept.code}). <br> Head: ${dept.hod}. <br> Faculty: ${dept.faculty}, Students: ${dept.students}.`;
        };

            // Submit to PHP via fetch
            const fd = new FormData();
            fd.append('action', 'add');
            if(editingId) fd.append('id', editingId);
            fd.append('name', name);
            fd.append('code', code);
            fd.append('hod', hod);
            fd.append('desc', description);
            fd.append('est_year', est);
            fd.append('email', email);
            fd.append('phone', phone);
            fd.append('status', status);

            fetch('departmentM.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    modal.classList.remove('active');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            });

        confirmDeleteBtn.addEventListener('click', () => {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', deleteId);
            fetch('departmentM.php', { method: 'POST', body: fd })
            .then(() => {
                showToast('Department deleted');
                deleteModal.classList.remove('active');
                setTimeout(() => location.reload(), 1000);
            });
        });
        cancelDelete.addEventListener('click', () => deleteModal.classList.remove('active'));

        // search, filter listeners
        searchInput.addEventListener('input', renderDepts);
        statusFilter.addEventListener('change', renderDepts);
        sortSelect.addEventListener('change', renderDepts);

        // export CSV dummy
        exportBtn.addEventListener('click', () => {
            alert('Simulating CSV export of department list');
        });

        // bulk actions: ignore checkbox for simplicity

        // initial render
        renderDepts();

        // also set global for inline onclick
        window.deleteModal = deleteModal;
    })();
</script>
<!-- style for buttons -->
<style>.btn-secondary { padding:0.8rem 2rem; border-radius:60px; border:none; background:#e2e8f0; cursor:pointer; }</style>
</body>
</html>

