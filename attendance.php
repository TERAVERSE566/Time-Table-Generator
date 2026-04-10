<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$role = $_SESSION['user_role'] ?? 'student';
$dashUrl = ($role === 'admin') ? 'admin.php' : (($role === 'faculty') ? 'facultyD.php' : 'studentD.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance · TimetableGen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg-light); padding: 2rem; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; }
        
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .back-btn { background: white; border: none; padding: 0.8rem 1.5rem; border-radius: 30px; cursor: pointer; font-weight: 600; color: var(--navy); box-shadow: var(--shadow-sm); text-decoration: none; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat { background: white; padding: 1.5rem; border-radius: 1.5rem; text-align: center; box-shadow: var(--shadow-sm); }
        .stat h2 { font-size: 2.5rem; color: var(--navy); margin-bottom: 0.5rem; }
        .progress-ring { width: 120px; height: 120px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; background: conic-gradient(var(--success) 85%, var(--gray-100) 0); font-size: 1.5rem; font-weight: bold; }
        
        .calendar-card { background: white; padding: 2rem; border-radius: 2rem; box-shadow: var(--shadow-md); }
        .cal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .cal-day-header { text-align: center; font-weight: 600; color: var(--gray-600); padding: 0.5rem 0; }
        .cal-day { aspect-ratio: 1; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 1.1rem; border: 2px solid transparent; cursor: pointer; transition: transform 0.2s; position: relative; }
        .cal-day:hover { transform: scale(1.1); }
        .cal-day.empty { background: transparent; cursor: default; }
        .cal-day.present { background: #d1fae5; color: #065f46; border-color: #34d399; }
        .cal-day.absent { background: #fee2e2; color: #991b1b; border-color: #f87171; }
        .cal-day.holiday { background: #f1f5f9; color: #475569; }
        .cal-day.today { font-weight: bold; border-color: var(--navy); }
        
        .filters select { padding: 0.8rem 1.5rem; border-radius: 30px; border: 1px solid var(--gray-300); font-size: 1rem; outline: none; }
        
        .legend { display: flex; gap: 1.5rem; justify-content: center; margin-top: 2rem; }
        .legend-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; }
        .dot { width: 12px; height: 12px; border-radius: 50%; }
        
        @media(max-width: 768px) {
            body { padding: 1rem; }
            .cal-day { font-size: 0.9rem; }
            .calendar-card { padding: 1rem; }
        }
    </style>
</head>
<body class="theme-<?= $role ?>">

<div class="container">
    <div class="header">
        <div style="display:flex; align-items:center; gap:1rem;">
            <a href="<?= $dashUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1><i class="fas fa-clipboard-check"></i> Attendance</h1>
        </div>
        <div class="filters">
            <select id="courseFilter">
                <option value="all">Overview (All Courses)</option>
                <option value="CS301">CS301 - Data Structures</option>
                <option value="MA201">MA201 - Mathematics</option>
            </select>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat">
            <div class="progress-ring">85%</div>
            <p>Overall Attendance</p>
        </div>
        <div class="stat">
            <h2>42</h2>
            <p>Classes Attended</p>
        </div>
        <div class="stat">
            <h2 style="color:var(--danger)">5</h2>
            <p>Classes Missed</p>
        </div>
    </div>

    <div class="calendar-card">
        <div class="cal-header">
            <button class="back-btn" style="padding: 0.5rem 1rem;"><i class="fas fa-chevron-left"></i></button>
            <h2>March 2026</h2>
            <button class="back-btn" style="padding: 0.5rem 1rem;"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="cal-grid" id="calendarGrid">
            <div class="cal-day-header">Sun</div><div class="cal-day-header">Mon</div><div class="cal-day-header">Tue</div>
            <div class="cal-day-header">Wed</div><div class="cal-day-header">Thu</div><div class="cal-day-header">Fri</div><div class="cal-day-header">Sat</div>
            <!-- generated by JS -->
        </div>
        
        <div class="legend">
            <div class="legend-item"><div class="dot" style="background:#34d399;"></div> Present</div>
            <div class="legend-item"><div class="dot" style="background:#f87171;"></div> Absent</div>
            <div class="legend-item"><div class="dot" style="background:#cbd5e1;"></div> Holiday/No Class</div>
        </div>
    </div>
</div>

<script>
// Generate a dummy calendar for March 2026
const grid = document.getElementById('calendarGrid');
// March 2026 starts on a Sunday (0)
const daysInMonth = 31;
const startDayOfWeek = 0;

for(let i=0; i<startDayOfWeek; i++) {
    grid.innerHTML += `<div class="cal-day empty"></div>`;
}

// Pseudo-random deterministic attendance tracker
for(let day=1; day<=daysInMonth; day++) {
    let cls = '';
    const dStr = `2026-03-${day.toString().padStart(2,'0')}`;
    const dayOfWeek = (startDayOfWeek + day - 1) % 7;
    
    // Sat/Sun are holidays mostly
    if (dayOfWeek === 0 || dayOfWeek === 6) {
        cls = 'holiday';
    } else {
        // Randomly assign present/absent
        // let's say mostly present
        if (day === 4 || day === 12 || day === 18 || day === 26) {
            cls = 'absent';
        } else if (day > 26) {
            cls = ''; // future days
        } else {
            cls = 'present';
        }
    }
    
    if (day === 26) cls += ' today'; // assume today is 26th
    
    grid.innerHTML += `<div class="cal-day ${cls}" title="${dStr}">${day}</div>`;
}
</script>
</body>
</html>
