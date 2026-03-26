<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}
$themeClass = isset($_SESSION['user_role']) ? 'theme-' . $_SESSION['user_role'] : '';
$user_name = $_SESSION['user_name'];
$initials = strtoupper(substr($user_name, 0, 1) . (strpos($user_name, ' ') ? substr(explode(' ', $user_name)[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard · TimetableGen</title>
    <!-- Font Awesome 6 & Google Fonts -->
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

        /* header row */
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .greeting h1 {
            font-size: 2.5rem;
            color: var(--navy);
        }
        .profile-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background: white;
            padding: 0.8rem 1.8rem 0.8rem 1rem;
            border-radius: 80px;
            box-shadow: var(--shadow-md);
        }
        .avatar {
            width: 60px;
            height: 60px;
            background: var(--navy);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
        }
        .profile-info {
            line-height: 1.4;
        }
        .profile-info .name {
            font-weight: 700;
            font-size: 1.2rem;
        }
        .profile-info .detail {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        .notification {
            position: relative;
            font-size: 1.8rem;
            color: var(--navy);
            margin-left: 0.5rem;
        }
        .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
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
        .stat-info .value { font-size: 2rem; font-weight: 700; color: var(--navy); }

        /* two column layout */
        .main-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* today's schedule card */
        .schedule-card, .tasks-card, .course-card, .announce-card {
            background: white;
            border-radius: 2.5rem;
            padding: 1.8rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        .schedule-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-300);
        }
        .time-badge {
            background: var(--navy);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
        }
        .class-info {
            flex: 1;
        }
        .status {
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .upcoming { background: #d1fae5; color: #065f46; }
        .ongoing { background: #fed7aa; color: #92400e; }
        .completed { background: #e2e8f0; color: #334155; }

        .btn-sm {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.4rem 1.2rem;
            border-radius: 40px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        /* weekly timetable mini */
        .week-grid {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .day-col {
            flex: 1;
            text-align: center;
            background: var(--gray-100);
            border-radius: 1rem;
            padding: 0.5rem;
        }
        .class-block {
            background: #e0f2fe;
            border-radius: 1rem;
            padding: 0.4rem;
            margin: 0.3rem 0;
            font-size: 0.7rem;
        }

        /* leave balances */
        .leave-balance {
            display: flex;
            justify-content: space-between;
            background: var(--gray-100);
            padding: 1rem;
            border-radius: 2rem;
            margin: 1rem 0;
        }
        .balance-item {
            text-align: center;
        }
        .balance-value {
            font-weight: 700;
            color: var(--navy);
        }

        /* course cards */
        .course-mini {
            background: var(--gray-100);
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .action-icons {
            display: flex;
            gap: 0.5rem;
        }
        .action-icons button {
            background: white;
            border: 1px solid var(--gray-300);
            color: var(--navy);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            font-size: 1.2rem;
        }
        .action-icons button:hover {
            background: var(--navy);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        /* quick actions */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 2rem 0;
        }
        .action-btn {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 1.8rem;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .action-btn:hover { background: var(--navy-light); }

        /* dark mode toggle */
        .dark-mode-toggle {
            background: var(--gray-100);
            border: none;
            border-radius: 30px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            margin-left: 1rem;
            font-weight: 600;
            transition: 0.3s;
        }

        /* ===== PROPER DARK MODE ===== */
        body.dark-mode {
            background: #0f172a !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .schedule-card,
        body.dark-mode .tasks-card,
        body.dark-mode .course-card,
        body.dark-mode .announce-card,
        body.dark-mode .stat-card,
        body.dark-mode .profile-card,
        body.dark-mode [style*="background: white"],
        body.dark-mode [style*="background:white"] {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .greeting h1,
        body.dark-mode .stat-info .value,
        body.dark-mode h2, body.dark-mode h3 {
            color: #f1f5f9 !important;
        }
        body.dark-mode .stat-info h3,
        body.dark-mode .profile-info .detail,
        body.dark-mode p, body.dark-mode li {
            color: #94a3b8 !important;
        }
        body.dark-mode .course-mini {
            background: #334155 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .leave-balance {
            background: #334155 !important;
        }
        body.dark-mode .day-col,
        body.dark-mode .week-grid .day-col {
            background: #334155 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .class-block {
            background: #475569 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .schedule-item {
            border-color: #334155 !important;
        }
        body.dark-mode .avatar {
            background: #6366f1 !important;
        }
        body.dark-mode .dark-mode-toggle {
            background: #334155 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .action-btn {
            background: #334155 !important;
            border: 1px solid #475569 !important;
        }
        body.dark-mode .action-btn:hover {
            background: #475569 !important;
        }
        body.dark-mode .btn-sm {
            background: #6366f1 !important;
        }
        body.dark-mode input,
        body.dark-mode select,
        body.dark-mode textarea {
            background: #334155 !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .badge {
            background: #ef4444 !important;
        }

        /* ===== ATTENDANCE MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            z-index: 5000; justify-content: center; align-items: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white; border-radius: 2rem; width: 95%; max-width: 1100px;
            max-height: 90vh; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            display: flex; flex-direction: column;
            animation: modalIn 0.3s ease;
        }
        @keyframes modalIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-header {
            padding: 1.5rem 2rem; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h2 { color: var(--navy); font-size: 1.5rem; }
        .modal-close { background: none; border: none; font-size: 1.8rem; cursor: pointer; color: #64748b; transition: 0.2s; }
        .modal-close:hover { color: #ef4444; transform: rotate(90deg); }
        .modal-body { padding: 1.5rem 2rem; overflow-y: auto; flex: 1; }
        .modal-footer { padding: 1rem 2rem; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }

        .class-selector { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .class-selector select {
            padding: 0.6rem 1.2rem; border: 1.5px solid #cbd5e1; border-radius: 1rem;
            font-size: 0.95rem; background: #f8fafc; outline: none;
        }
        .class-selector select:focus { border-color: var(--navy); }

        .class-info-bar {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.8rem;
            background: #f1f5f9; border-radius: 1.2rem; padding: 1rem 1.5rem; margin-bottom: 1.5rem;
        }
        .class-info-bar .info-item { font-size: 0.85rem; }
        .class-info-bar .info-item strong { color: var(--navy); display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }

        .attendance-table { width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 1rem; overflow: hidden; border: 1px solid #e2e8f0; }
        .attendance-table th { background: var(--navy); color: white; padding: 0.8rem 1rem; text-align: left; font-size: 0.85rem; font-weight: 600; }
        .attendance-table td { padding: 0.7rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }
        .attendance-table tr:nth-child(even) { background: #f8fafc; }
        .attendance-table tr:hover { background: #eef2ff; }
        .attendance-table .roll { color: var(--navy); font-weight: 600; }
        .attend-check { width: 20px; height: 20px; accent-color: var(--success); cursor: pointer; }
        .attend-absent { accent-color: var(--danger); }

        .mark-all-bar { display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; }
        .mark-all-btn { padding: 0.4rem 1rem; border-radius: 30px; border: none; font-weight: 600; cursor: pointer; font-size: 0.85rem; transition: 0.2s; }
        .mark-all-btn.present { background: #d1fae5; color: #065f46; }
        .mark-all-btn.present:hover { background: #10b981; color: white; }
        .mark-all-btn.absent { background: #fee2e2; color: #991b1b; }
        .mark-all-btn.absent:hover { background: #ef4444; color: white; }
        .attend-count { margin-left: auto; font-weight: 600; color: var(--navy); font-size: 0.95rem; }

        .submit-attend-btn { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 0.8rem 2rem; border-radius: 50px; font-weight: 700; font-size: 1rem; cursor: pointer; transition: 0.2s; }
        .submit-attend-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(16,185,129,0.4); }

        /* dark mode modal */
        body.dark-mode .modal { background: #1e293b !important; }
        body.dark-mode .modal-header { border-color: #334155 !important; }
        body.dark-mode .modal-header h2 { color: #f1f5f9 !important; }
        body.dark-mode .modal-close { color: #94a3b8 !important; }
        body.dark-mode .modal-footer { border-color: #334155 !important; }
        body.dark-mode .class-info-bar { background: #334155 !important; }
        body.dark-mode .class-info-bar .info-item strong { color: #38bdf8 !important; }
        body.dark-mode .class-info-bar .info-item { color: #e2e8f0 !important; }
        body.dark-mode .attendance-table th { background: #334155 !important; }
        body.dark-mode .attendance-table td { border-color: #334155 !important; color: #e2e8f0 !important; }
        body.dark-mode .attendance-table tr:nth-child(even) { background: #1e293b !important; }
        body.dark-mode .attendance-table tr:hover { background: #334155 !important; }
        body.dark-mode .class-selector select { background: #334155 !important; border-color: #475569 !important; color: #e2e8f0 !important; }

        /* responsive */
        @media (max-width: 900px) {
            .main-grid { grid-template-columns: 1fr; }
            .modal { width: 98%; }
        }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .course-mini { flex-direction: column; align-items: flex-start; }
            .action-icons { width: 100%; justify-content: space-around; margin-top: 1rem; }
            .header-row { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr !important; }
            .profile-card { flex-wrap: wrap; }
        }

        /* toast */
        .toast {
            position: fixed; bottom: 30px; right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 2000;
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
<div class="container">
    <!-- header -->
    <div class="header-row">
        <div class="greeting">
            <h1 id="greetingMsg">Good day, <?= htmlspecialchars($user_name) ?>! 👋</h1>
            <p id="currentDate" style="color: var(--gray-600);"><?= date("l, d M Y") ?></p>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="profile-card" onclick="window.location.href='profile.php'" style="cursor:pointer;">
                <div class="avatar"><?= $initials ?></div>
                <div class="profile-info">
                    <div class="name"><?= htmlspecialchars($user_name) ?></div>
                    <div class="detail">Faculty Member</div>
                </div>
            </div>
            <div class="notification">
                <i class="far fa-bell"></i>
                <span class="badge">3</span>
            </div>
            <button class="dark-mode-toggle" id="darkToggle"><i class="fas fa-moon"></i> Dark</button>
            <button class="dark-mode-toggle" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <!-- stats cards -->
    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">📚</span><div class="stat-info"><span class="value">3</span><h3>Courses Teaching</h3></div></div>
        <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-info"><span class="value">156</span><h3>Total Students</h3></div></div>
        <div class="stat-card"><span class="stat-icon">⏰</span><div class="stat-info"><span class="value">14</span><h3>Hours This Week</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📅</span><div class="stat-info"><span class="value">10:00 AM</span><h3>Next Class</h3></div></div>
    </div>

    <!-- main grid: left column (schedule) / right column (tasks, leave) -->
    <div class="main-grid">
        <!-- left column -->
        <div>
            <!-- Today's schedule -->
            <div class="schedule-card">
                <h2><i class="far fa-calendar-check"></i> Today's Schedule</h2>
                <div class="schedule-item">
                    <span class="time-badge">10:00 – 11:30</span>
                    <div class="class-info"><strong>CS501</strong> – Machine Learning · LH-101 · CSE A (72 students)</div>
                    <span class="status upcoming">Upcoming</span>
                    <button class="btn-sm start-class">Start</button>
                </div>
                <div class="schedule-item">
                    <span class="time-badge">12:00 – 13:30</span>
                    <div class="class-info"><strong>CS410</strong> – Deep Learning · Lab-203 · CSE B (68 students)</div>
                    <span class="status upcoming">Upcoming</span>
                    <button class="btn-sm start-class">Start</button>
                </div>
                <div class="schedule-item">
                    <span class="time-badge">14:00 – 15:30</span>
                    <div class="class-info"><strong>CS307</strong> – Database Systems · LH-105 · CSE A (70 students)</div>
                    <span class="status upcoming">Upcoming</span>
                    <button class="btn-sm start-class">Start</button>
                </div>
            </div>

            <!-- Weekly timetable mini -->
            <div class="schedule-card">
                <h3>🗓️ Weekly Timetable <button class="btn-sm" onclick="window.location.href='table_view.php'" style="margin-left:1rem;">Full View</button></h3>
                <div class="week-grid">
                    <div class="day-col">Mon <div class="class-block">CS501 10am</div><div class="class-block">CS410 12pm</div></div>
                    <div class="day-col">Tue <div class="class-block">CS307 2pm</div></div>
                    <div class="day-col">Wed <div class="class-block">CS501 10am</div><div class="class-block">Lab</div></div>
                    <div class="day-col">Thu <div class="class-block">CS410 12pm</div></div>
                    <div class="day-col">Fri <div class="class-block">CS307 2pm</div></div>
                </div>
            </div>

            <!-- Course management cards -->
            <div class="schedule-card">
                <h3>📚 My Courses</h3>
                <div class="course-mini">
                    <div><strong>CS501</strong> Machine Learning (CSE A) · 72 students</div>
                    <div class="action-icons">
                        <button onclick="document.getElementById('attendanceModal').classList.add('active')" title="Take Attendance"><i class="fas fa-check-circle"></i></button>
                        <button onclick="window.location.href='feature_preview.php?feature=Upload+Materials'" title="Upload Material"><i class="fas fa-upload"></i></button>
                        <button onclick="window.location.href='feature_preview.php?feature=Manage+Students'" title="Students List"><i class="fas fa-users"></i></button>
                    </div>
                </div>
                <div class="course-mini">
                    <div><strong>CS410</strong> Deep Learning (CSE B) · 68 students</div>
                    <div class="action-icons">
                        <button onclick="document.getElementById('attendanceModal').classList.add('active')" title="Take Attendance"><i class="fas fa-check-circle"></i></button>
                        <button onclick="window.location.href='feature_preview.php?feature=Upload+Materials'" title="Upload Material"><i class="fas fa-upload"></i></button>
                        <button onclick="window.location.href='feature_preview.php?feature=Manage+Students'" title="Students List"><i class="fas fa-users"></i></button>
                    </div>
                </div>
                <div class="course-mini">
                    <div><strong>CS307</strong> Database Systems (CSE A) · 70 students</div>
                    <div class="action-icons">
                        <button onclick="document.getElementById('attendanceModal').classList.add('active')" title="Take Attendance"><i class="fas fa-check-circle"></i></button>
                        <button onclick="window.location.href='feature_preview.php?feature=Upload+Materials'" title="Upload Material"><i class="fas fa-upload"></i></button>
                        <button onclick="window.location.href='feature_preview.php?feature=Manage+Students'" title="Students List"><i class="fas fa-users"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- right column -->
        <div>
            <!-- Upcoming tasks -->
            <div class="tasks-card">
                <h3>⏰ Upcoming Tasks</h3>
                <ul style="list-style-type: none;">
                    <li><i class="fas fa-chalkboard-teacher"></i> Faculty meeting (2:30 PM today)</li>
                    <li><i class="fas fa-file-alt"></i> Assignment deadline (CS501) – tomorrow</li>
                    <li><i class="fas fa-clock"></i> Leave request pending approval</li>
                    <li><i class="fas fa-calendar"></i> Dept meeting Wed 11am</li>
                </ul>
            </div>

            <!-- Leave request section -->
            <div class="tasks-card">
                <h3>🏖️ Leave & Attendance</h3>
                <div class="leave-balance">
                    <div class="balance-item"><span class="balance-value">8/12</span><br>Sick</div>
                    <div class="balance-item"><span class="balance-value">6/10</span><br>Casual</div>
                    <div class="balance-item"><span class="balance-value">4/15</span><br>Earned</div>
                </div>
                <div style="display: flex; gap:0.5rem;">
                    <input type="text" placeholder="Reason for leave" style="flex:1; padding:0.6rem; border-radius:50px; border:1px solid var(--gray-300);">
                    <button class="btn-sm" id="requestLeaveBtn">Request</button>
                </div>
            </div>

            <!-- Student alerts -->
            <div class="tasks-card">
                <h3>⚠️ Student Alerts</h3>
                <p><span class="badge" style="position:static;">3 students</span> low attendance (CS501)</p>
                <p><i class="fas fa-pen"></i> Pending assignments: 5 submissions</p>
                <p><i class="fas fa-bell"></i> Exam schedule published</p>
            </div>

            <!-- Department announcements -->
            <div class="announce-card">
                <h3>📢 Announcements</h3>
                <marquee behavior="scroll" direction="up" scrollamount="2" style="height:100px;">🔹 Lab timings changed for CS410<br>🔹 Guest lecture on Friday 11am<br>🔹 Submit grades by 30th</marquee>
            </div>

            <!-- Quick actions -->
            <div class="quick-actions">
                <button class="action-btn" id="attendanceBtn"><i class="fas fa-check-double"></i> Take Attendance</button>
                <button class="action-btn" onclick="window.location.href='analysis.php'"><i class="fas fa-chart-line"></i> Analytics</button>
                <button class="action-btn" onclick="window.location.href='contact.php'"><i class="fas fa-envelope"></i> Contact</button>
                <button class="action-btn" onclick="window.location.href='substitute.php'"><i class="fas fa-user-clock"></i> Substitute</button>
            </div>
        </div>
    </div>

    <!-- zoom/meet integration -->
    <div style="background: white; border-radius:2rem; padding:1.5rem; margin-top:1rem;">
        <h3><i class="fas fa-video"></i> Upcoming Online Sessions</h3>
        <p>CS501 · Meet link: <a href="feature_preview.php?feature=Google+Meet+Integration">https://meet.google.com/abc</a> (10:00 AM) <button class="btn-sm" onclick="window.location.href='feature_preview.php?feature=Google+Meet+Integration'">Join</button></p>
    </div>
</div>

<!-- toast notification -->
<div id="toast" class="toast">✅ Attendance marked</div>

<!-- ===== ATTENDANCE MODAL ===== -->
<div class="modal-overlay" id="attendanceModal">
  <div class="modal">
    <div class="modal-header">
      <h2><i class="fas fa-clipboard-check"></i> Take Attendance</h2>
      <button class="modal-close" id="closeModal">&times;</button>
    </div>
    <div class="modal-body">
      <!-- Class selector -->
      <div class="class-selector">
        <select id="courseSelect">
          <option value="CS501">CS501 – Machine Learning</option>
          <option value="CS410">CS410 – Deep Learning</option>
          <option value="CS307">CS307 – Database Systems</option>
        </select>
        <select id="timeSlotSelect">
          <option>10:00 – 11:30</option>
          <option>12:00 – 13:30</option>
          <option>14:00 – 15:30</option>
        </select>
      </div>

      <!-- Class info bar -->
      <div class="class-info-bar">
        <div class="info-item"><strong>Course</strong><span id="infoCourseName">CS501 – Machine Learning</span></div>
        <div class="info-item"><strong>Department</strong>Computer Science</div>
        <div class="info-item"><strong>Faculty</strong><?= htmlspecialchars($user_name) ?></div>
        <div class="info-item"><strong>Class Time</strong><span id="infoTime">10:00 – 11:30</span></div>
        <div class="info-item"><strong>Room Type</strong>Lecture Hall</div>
        <div class="info-item"><strong>Room No.</strong>LH-101</div>
      </div>

      <!-- Mark all bar -->
      <div class="mark-all-bar">
        <button class="mark-all-btn present" id="markAllPresent"><i class="fas fa-check-circle"></i> Mark All Present</button>
        <button class="mark-all-btn absent" id="markAllAbsent"><i class="fas fa-times-circle"></i> Mark All Absent</button>
        <span class="attend-count" id="attendCount">Present: 0 / 12</span>
      </div>

      <!-- Attendance table -->
      <table class="attendance-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Roll No.</th>
            <th>Student Name</th>
            <th>Department</th>
            <th style="text-align:center;">Present</th>
            <th style="text-align:center;">Absent</th>
          </tr>
        </thead>
        <tbody id="attendanceBody">
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <span style="color:#64748b; font-size:0.9rem;"><i class="fas fa-calendar"></i> <?= date('l, d M Y') ?></span>
      <button class="submit-attend-btn" id="submitAttendance"><i class="fas fa-save"></i> Submit Attendance</button>
    </div>
  </div>
</div>

<script>
(function() {
    // ===== GREETING =====
    const greetingEl = document.getElementById('greetingMsg');
    const hour = new Date().getHours();
    let greet = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    const existingText = greetingEl.innerText;
    const nameMatch = existingText.match(/,\s*(.+?)!/);
    const userName = nameMatch ? nameMatch[1] : 'Professor';
    greetingEl.innerText = `${greet}, ${userName}! 👋`;

    // ===== CLOCK =====
    const dateEl = document.getElementById('currentDate');
    function updateDate() {
        const d = new Date();
        dateEl.innerText = d.toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
    updateDate();

    // ===== NOTIFICATION BELL =====
    document.querySelector('.notification').addEventListener('click', () => {
        alert('🔔 3 notifications:\n• Leave request approved\n• Faculty meeting at 2:30 PM\n• Student query from Rahul Patel');
    });

    // ===== START CLASS =====
    document.querySelectorAll('.start-class').forEach(btn => {
        btn.addEventListener('click', (e) => { e.preventDefault(); showToast('📋 Class started – attendance sheet opened'); });
    });

    // ===== LEAVE REQUEST =====
    document.getElementById('requestLeaveBtn').addEventListener('click', () => showToast('Leave request submitted'));

    // ===== DARK MODE (proper, with persistence) =====
    const darkToggle = document.getElementById('darkToggle');
    // Restore dark mode from localStorage
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

    // ===== TOAST =====
    function showToast(msg) {
        const toast = document.getElementById('toast');
        toast.innerText = '✅ ' + msg;
        toast.style.display = 'flex';
        setTimeout(() => toast.style.display = 'none', 2500);
    }

    // ===== ATTENDANCE MODAL =====
    const modal = document.getElementById('attendanceModal');
    const closeBtn = document.getElementById('closeModal');
    const attendBtn = document.getElementById('attendanceBtn');
    const tbody = document.getElementById('attendanceBody');
    const countSpan = document.getElementById('attendCount');

    // Sample student data
    const students = [
        { roll: 'CE201', name: 'Aarav Mehta', dept: 'CSE' },
        { roll: 'CE202', name: 'Priya Shah', dept: 'CSE' },
        { roll: 'CE203', name: 'Rahul Patel', dept: 'CSE' },
        { roll: 'CE204', name: 'Sneha Desai', dept: 'CSE' },
        { roll: 'CE205', name: 'Karan Singh', dept: 'CSE' },
        { roll: 'CE206', name: 'Ananya Gupta', dept: 'CSE' },
        { roll: 'CE207', name: 'Vikram Joshi', dept: 'CSE' },
        { roll: 'CE208', name: 'Meera Nair', dept: 'CSE' },
        { roll: 'CE209', name: 'Arjun Reddy', dept: 'CSE' },
        { roll: 'CE210', name: 'Divya Sharma', dept: 'CSE' },
        { roll: 'CE211', name: 'Rohit Kumar', dept: 'CSE' },
        { roll: 'CE212', name: 'Ishita Verma', dept: 'CSE' },
    ];

    function renderStudents() {
        tbody.innerHTML = '';
        students.forEach((s, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td class="roll">${s.roll}</td>
                <td>${s.name}</td>
                <td>${s.dept}</td>
                <td style="text-align:center;"><input type="radio" name="attend_${i}" value="present" class="attend-check attend-radio" data-idx="${i}" checked></td>
                <td style="text-align:center;"><input type="radio" name="attend_${i}" value="absent" class="attend-check attend-absent attend-radio" data-idx="${i}"></td>
            `;
            tbody.appendChild(tr);
        });
        updateCount();
    }

    function updateCount() {
        const presentCount = document.querySelectorAll('.attend-radio[value="present"]:checked').length;
        countSpan.textContent = `Present: ${presentCount} / ${students.length}`;
    }

    // Open modal
    attendBtn.addEventListener('click', () => {
        renderStudents();
        modal.classList.add('active');
    });

    // Close modal
    closeBtn.addEventListener('click', () => modal.classList.remove('active'));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.classList.remove('active'); });

    // Delegate radio changes for count
    tbody.addEventListener('change', updateCount);

    // Mark all present / absent
    document.getElementById('markAllPresent').addEventListener('click', () => {
        document.querySelectorAll('.attend-radio[value="present"]').forEach(r => r.checked = true);
        updateCount();
    });
    document.getElementById('markAllAbsent').addEventListener('click', () => {
        document.querySelectorAll('.attend-radio[value="absent"]').forEach(r => r.checked = true);
        updateCount();
    });

    // Update info bar when course changes
    document.getElementById('courseSelect').addEventListener('change', function() {
        const courseMap = {
            'CS501': { name: 'CS501 – Machine Learning', room: 'LH-101', type: 'Lecture Hall' },
            'CS410': { name: 'CS410 – Deep Learning', room: 'Lab-203', type: 'Lab' },
            'CS307': { name: 'CS307 – Database Systems', room: 'LH-105', type: 'Lecture Hall' }
        };
        const c = courseMap[this.value];
        if (c) {
            document.getElementById('infoCourseName').textContent = c.name;
            document.querySelector('.class-info-bar .info-item:nth-child(5) span, .class-info-bar .info-item:nth-child(5)').lastChild.textContent = c.type;
            document.querySelector('.class-info-bar .info-item:nth-child(6)').lastChild.textContent = c.room;
        }
    });
    document.getElementById('timeSlotSelect').addEventListener('change', function() {
        document.getElementById('infoTime').textContent = this.value;
    });

    // Submit attendance
    document.getElementById('submitAttendance').addEventListener('click', () => {
        const presentCount = document.querySelectorAll('.attend-radio[value="present"]:checked').length;
        modal.classList.remove('active');
        showToast(`Attendance submitted: ${presentCount}/${students.length} present for ${document.getElementById('courseSelect').value}`);
    });

    // ===== DRAG-DROP (demo) =====
    document.querySelectorAll('.class-block').forEach(el => {
        el.setAttribute('draggable', 'true');
        el.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', 'Reschedule');
            showToast('Drag-drop rescheduling (demo)');
        });
    });
})();
</script>
<script src="theme.js"></script>
</body>
</html>

