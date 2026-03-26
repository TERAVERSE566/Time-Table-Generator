<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';
$themeClass = isset($_SESSION['user_role']) ? 'theme-' . $_SESSION['user_role'] : '';

// Fetch stats
$facQuery = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='faculty'");
$facCount = $facQuery ? $facQuery->fetch_assoc()['c'] : 0;

$stuQuery = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'");
$stuCount = $stuQuery ? $stuQuery->fetch_assoc()['c'] : 0;

$crsQuery = $conn->query("SELECT COUNT(*) as c FROM courses");
$crsCount = $crsQuery ? $crsQuery->fetch_assoc()['c'] : 0;

$rmQuery = $conn->query("SELECT COUNT(*) as c FROM rooms WHERE status='available'");
$rmCount = $rmQuery ? $rmQuery->fetch_assoc()['c'] : 0;

$themeClass = 'theme-admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Admin Dashboard</title>
    <!-- Font Awesome 6 (free icons) & Chart.js 3 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        :root {
            --bg-dark: #0f172a;
            --bg-surface: #1e293b;
            --bg-card: #2d3a4f;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --accent-gold: #f4c542;
            --accent-blue: #38bdf8;
            --success: #4ade80;
            --warning: #fbbf24;
            --danger: #f87171;
            --sidebar-width: 260px;
            --header-height: 70px;
            --border-radius: 1.5rem;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            height: 100vh;
            overflow: hidden;
        }

        /* custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #1e2b3c; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #4b5e77; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #5f7595; }

        .dashboard {
            display: flex;
            height: 100vh;
        }

        /* ---------- SIDEBAR ---------- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-surface);
            padding: 1.8rem 1.2rem;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #334155;
            transition: width 0.2s;
            overflow-y: auto;
            box-shadow: 8px 0 20px rgba(0,0,0,0.5);
        }

        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar.collapsed .logo span,
        .sidebar.collapsed .menu-item span:not(.emoji),
        .sidebar.collapsed .logout span {
            display: none;
        }

        .logo {
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--accent-gold);
            margin-bottom: 3rem;
            white-space: nowrap;
        }

        .nav-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.9rem 1rem;
            border-radius: 50px;
            color: var(--text-secondary);
            transition: 0.2s;
            cursor: pointer;
            white-space: nowrap;
        }
        .menu-item:hover, .menu-item.active {
            background: #3b4b64;
            color: white;
        }
        .menu-item .emoji { font-size: 1.4rem; }

        .logout {
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: #fca5a5;
            border-top: 1px solid #334155;
            cursor: pointer;
        }

        /* ---------- MAIN CONTENT ---------- */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* header */
        .header {
            height: var(--header-height);
            background: var(--bg-surface);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            border-bottom: 1px solid #334155;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .collapse-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.8rem;
            cursor: pointer;
        }

        .search-bar {
            background: #1e2a3a;
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
        }
        .search-bar input {
            background: transparent;
            border: none;
            color: white;
            outline: none;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .notification {
            position: relative;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .badge {
            position: absolute;
            top: -6px;
            right: -8px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: #1e2a3a;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            cursor: pointer;
        }
        .admin-profile img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--accent-blue);
        }
        #currentDate {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* dashboard content */
        .content {
            padding: 1.8rem 2rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* welcome row */
        .welcome-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .welcome h1 { font-size: 2rem; }
        .year-selector select {
            background: #1e2a3a;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            font-weight: 500;
        }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: 0 8px 18px rgba(0,0,0,0.4);
        }
        .stat-icon { font-size: 2.8rem; }
        .stat-info h3 { font-weight: 400; font-size: 1rem; color: var(--text-secondary); }
        .stat-info .value { font-size: 2.2rem; font-weight: 700; }
        .trend { font-size: 0.9rem; margin-left: 0.5rem; color: var(--success); }

        /* charts grid */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.8rem;
        }
        .chart-card {
            background: var(--bg-card);
            border-radius: 2rem;
            padding: 1.2rem;
            box-shadow: 0 8px 18px rgba(0,0,0,0.4);
        }
        .chart-card canvas { max-height: 200px; width: 100% !important; }

        /* quick actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        .action-btn {
            background: #1e2a3a;
            padding: 1.5rem 0.5rem;
            border-radius: 2rem;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 500;
            transition: 0.2s;
            cursor: pointer;
            border: 1px solid #3d506e;
        }
        .action-btn:hover { background: #2d405b; transform: scale(1.02); }

        /* two column bottom */
        .bottom-flex {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .feed, .tasks, .system-health {
            background: var(--bg-card);
            border-radius: 2rem;
            padding: 1.5rem;
            flex: 1 1 280px;
        }
        .feed-item, .task-item {
            display: flex;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #3e506e;
            padding: 0.9rem 0;
        }
        .time { color: var(--text-secondary); font-size: 0.8rem; margin-left: auto; }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .success { background: #14532d; color: #bbf7d0; }
        .warning { background: #854d0e; color: #fef08a; }
        .danger { background: #7f1d1d; color: #fecaca; }

        /* data table */
        .table-section {
            background: var(--bg-card);
            border-radius: 2rem;
            padding: 1.2rem;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #2d3b4f; }
        th { color: var(--accent-gold); }

        /* modal dummy */
        .modal-placeholder { display: none; }

        /* responsiveness */
        @media (max-width: 900px) {
            .sidebar { width: 70px; }
            .sidebar .logo span, .sidebar .menu-item span:not(.emoji), .sidebar .logout span { display: none; }
            .main { width: calc(100% - 70px); }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .charts-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .sidebar { position: absolute; z-index: 1000; height: 100%; transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); width: 200px; }
            .sidebar.active .logo span, .sidebar.active .menu-item span:not(.emoji), .sidebar.active .logout span { display: inline; }
            .main { width: 100%; }
            .header-right .admin-profile span { display: none; }
            .welcome-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .bottom-flex { flex-direction: column; }
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
<div class="dashboard" id="dashboard">

    <!-- SIDEBAR (fixed left) -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">📅 TimetableGen <span>Admin</span></div>
        <div class="nav-menu">
            <div class="menu-item active" onclick="window.location.href='admin.php'"><span class="emoji">🏠</span> <span>Dashboard</span></div>
            <div class="menu-item" onclick="window.location.href='departmentM.php'"><span class="emoji">🏛️</span> <span>Departments</span></div>
            <div class="menu-item" onclick="window.location.href='FacultyM.php'"><span class="emoji">👨‍🏫</span> <span>Faculty</span></div>
            <div class="menu-item" onclick="window.location.href='studentM.php'"><span class="emoji">👩‍🎓</span> <span>Students</span></div>
            <div class="menu-item" onclick="window.location.href='course.php'"><span class="emoji">📚</span> <span>Courses</span></div>
            <div class="menu-item" onclick="window.location.href='roomM.php'"><span class="emoji">🚪</span> <span>Rooms</span></div>
            <div class="menu-item" onclick="window.location.href='generator.php'"><span class="emoji">⏰</span> <span>Time Slots</span></div>
            <div class="menu-item" onclick="window.location.href='generator.php'"><span class="emoji">📅</span> <span>Generate Timetable</span></div>
            <div class="menu-item" onclick="window.location.href='profile.php'"><span class="emoji">⚙️</span> <span>Settings</span></div>
        </div>
        <div class="logout" onclick="window.location.href='logout.php'">
            <span class="emoji">🚪</span> <span>Logout</span>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOP HEADER -->
        <header class="header">
            <div class="header-left">
                <button class="collapse-btn" id="toggleSidebar">☰</button>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search...">
                </div>
            </div>
            <div class="header-right">
                <div class="notification">
                    <i class="far fa-bell"></i>
                    <span class="badge">5</span>
                </div>
                <div class="admin-profile">
                    <img src="https://randomuser.me/api/portraits/men/41.jpg" alt="Admin">
                    <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div id="currentDate">Mon, 18 Mar 2025</div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <div class="content">

            <!-- welcome + year selector -->
            <div class="welcome-row">
                <div class="welcome">
                    <h1>Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>! 👋</h1>
                    <p style="color: var(--text-secondary)">Here’s what’s happening with your institution</p>
                </div>
                <div class="year-selector">
                    <select>
                        <option>Academic Year 2024-25</option>
                        <option>2025-26</option>
                    </select>
                </div>
            </div>

            <!-- quick stats cards -->
            <div class="stats-grid">
                <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-info"><span class="value"><?= number_format($facCount) ?></span> <span class="trend">↑8%</span><h3>Total Faculty</h3></div></div>
                <div class="stat-card"><span class="stat-icon">👩‍🎓</span><div class="stat-info"><span class="value"><?= number_format($stuCount) ?></span> <span class="trend">↑12%</span><h3>Total Students</h3></div></div>
                <div class="stat-card"><span class="stat-icon">📚</span><div class="stat-info"><span class="value"><?= number_format($crsCount) ?></span> <span class="trend">↑3%</span><h3>Active Courses</h3></div></div>
                <div class="stat-card"><span class="stat-icon">🚪</span><div class="stat-info"><span class="value"><?= number_format($rmCount) ?></span> <span class="trend">–2%</span><h3>Available Rooms</h3></div></div>
            </div>

            <!-- CHARTS (bar, pie, line) -->
            <div class="charts-grid">
                <div class="chart-card"><canvas id="barChart"></canvas></div>
                <div class="chart-card"><canvas id="pieChart"></canvas></div>
                <div class="chart-card"><canvas id="lineChart"></canvas></div>
            </div>

            <!-- Quick actions grid -->
            <div class="actions-grid">
                <div class="action-btn" onclick="window.location.href='FacultyM.php'">➕ Add Faculty</div>
                <div class="action-btn" onclick="window.location.href='course.php'">📝 Create Course</div>
                <div class="action-btn" onclick="window.location.href='roomM.php'">🚪 Register Room</div>
                <div class="action-btn" onclick="window.location.href='generator.php'">⚡ Generate Timetable</div>
            </div>

            <!-- bottom flex: feed, tasks, system health -->
            <div class="bottom-flex">
                <!-- recent activities feed -->
                <div class="feed">
                    <h3>📋 Recent Activities</h3>
                    <div class="feed-item"><i class="fas fa-user-edit" style="color:#38bdf8;"></i> New faculty added (Dr. Grey) <span class="time">5 min ago</span></div>
                    <div class="feed-item"><i class="fas fa-calendar-plus" style="color:#4ade80;"></i> Room B202 scheduled <span class="time">23 min ago</span></div>
                    <div class="feed-item"><i class="fas fa-exclamation-triangle" style="color:#fbbf24;"></i> Conflict: Math 101 <span class="time">1 hour ago</span></div>
                    <div class="feed-item"><i class="fas fa-trash-alt" style="color:#f87171;"></i> Course CS50 removed <span class="time">2 hours ago</span></div>
                </div>
                <!-- upcoming tasks -->
                <div class="tasks">
                    <h3>⏳ Pending Tasks</h3>
                    <div class="task-item"><span class="status-badge warning">urgent</span> Timetable generation (CS dept) <span class="time">due today</span></div>
                    <div class="task-item"><span class="status-badge success">leave</span> 3 faculty leave requests <span class="time">pending</span></div>
                    <div class="task-item"><span class="status-badge danger">conflict</span> Room overlap: PHY lab <span class="time">now</span></div>
                </div>
                <!-- system health -->
                <div class="system-health">
                    <h3>⚙️ System Health</h3>
                    <div>Database: <span class="status-badge success">operational</span></div>
                    <div>API response: <span class="status-badge success">210ms</span></div>
                    <div>Storage: 62% used (456GB/750GB) <progress value="62" max="100" style="width:100%; height:12px; border-radius:6px;"></progress></div>
                </div>
            </div>

            <!-- Data Table: recent generations -->
            <div class="table-section">
                <h3 style="margin-bottom:1rem;">📅 Recent Timetable Generations</h3>
                <table>
                    <thead><tr><th>Dept</th><th>Semester</th><th>Generated on</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <tr><td>Computer Science</td><td>4</td><td>2025-03-18</td><td><span class="status-badge success">active</span></td><td><i class="fas fa-eye"></i></td></tr>
                        <tr><td>Mathematics</td><td>2</td><td>2025-03-17</td><td><span class="status-badge success">active</span></td><td><i class="fas fa-eye"></i></td></tr>
                        <tr><td>Physics</td><td>6</td><td>2025-03-16</td><td><span class="status-badge warning">conflict</span></td><td><i class="fas fa-exclamation"></i></td></tr>
                        <tr><td>Chemistry</td><td>4</td><td>2025-03-15</td><td><span class="status-badge success">active</span></td><td><i class="fas fa-eye"></i></td></tr>
                    </tbody>
                </table>
            </div>

            <!-- dummy second table: faculty on leave -->
            <div class="table-section">
                <h3>🏖️ Faculty on Leave (today)</h3>
                <table>
                    <tr><th>Name</th><th>Department</th><th>Type</th></tr>
                    <tr><td>Prof. Carter</td><td>Mathematics</td><td>Sick</td></tr>
                    <tr><td>Dr. Evans</td><td>Physics</td><td>Conference</td></tr>
                    <tr><td>Ms. Davis</td><td>Computer Sci</td><td>Vacation</td></tr>
                </table>
            </div>
        </div> <!-- end content -->
    </div> <!-- end main -->
</div>

<!-- SIMULATED MODAL / NOTIFICATION DROPDOWN (just dummy) -->
<div style="display: none;">modals would appear here</div>

<script>
    (function() {
        // ------ SIDEBAR COLLAPSE ------
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 600) {
                sidebar.classList.toggle('active');
            } else {
                sidebar.classList.toggle('collapsed');
            }
        });

        // ------ CURRENT DATE & TIME UPDATE (real-time clock) ------
        const dateEl = document.getElementById('currentDate');
        function updateDate() {
            const d = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            dateEl.innerText = d.toLocaleDateString(undefined, options);
        }
        updateDate();
        setInterval(updateDate, 1000 * 60); // update every minute

        // ------ CHART.JS INTEGRATION (bar, pie, line) ------
        // Bar chart: Classes per department
        const ctxBar = document.getElementById('barChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['CS', 'Math', 'Physics', 'Chem', 'Bio'],
                datasets: [{
                    label: 'Classes',
                    data: [42, 28, 33, 21, 18],
                    backgroundColor: '#f4c542',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // Pie: Room utilization
        const ctxPie = document.getElementById('pieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['In use', 'Available', 'Maintenance'],
                datasets: [{
                    data: [62, 30, 8],
                    backgroundColor: ['#38bdf8', '#4ade80', '#f87171']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Line: Weekly schedule load
        const ctxLine = document.getElementById('lineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                datasets: [{
                    label: 'Classes',
                    data: [124, 136, 148, 152, 110],
                    borderColor: '#f4c542',
                    tension: 0.2,
                    fill: false
                }]
            },
            options: { responsive: true }
        });

        // ------ NOTIFICATION DROPDOWN (simple simulation) ------
        const bell = document.querySelector('.notification');
        bell.addEventListener('click', () => {
            alert('🔔 5 new notifications:\n• Room conflict at 14:30\n• 2 leave requests pending\n• Timetable generation completed');
        });

        // ------ QUICK ACTIONS (simulated modal) ------
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if(!btn.getAttribute('onclick')) {
                    alert(`🚀 Quick action: "${btn.innerText}" – demo modal (integration placeholder).`);
                }
            });
        });

        // ------ EXPORT BUTTON SIMULATION (can be attached to tables) ------
        // not required but could be dummy
    })();
</script>
<!-- dummy chart sizing fix -->
<style>.chart-card canvas { width: 100% !important; height: auto !important; }</style>
<script src="theme.js"></script>
</body>
</html>

