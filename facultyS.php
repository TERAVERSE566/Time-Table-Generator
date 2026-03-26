<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dr. Sharma's Schedule · Faculty · TimetableGen</title>
    <!-- Font Awesome 6 & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js for simple charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
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
        .header-row {
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
        }
        .view-btn.active {
            background: var(--navy);
            color: white;
        }

        /* schedule controls */
        .control-bar {
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
        }
        .date-range {
            font-weight: 600;
        }
        .filter-course {
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            border: 1px solid var(--gray-300);
        }

        /* main layout: calendar left, panels right */
        .main-grid {
            display: grid;
            grid-template-columns: 2.5fr 1.2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* weekly calendar (professional look) */
        .calendar-card {
            background: white;
            border-radius: 2.5rem;
            padding: 1.8rem;
            box-shadow: var(--shadow-md);
        }
        .week-grid {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .time-header, .day-header {
            font-weight: 700;
            padding: 0.8rem;
        }
        .day-header {
            background: var(--navy);
            color: white;
            text-align: center;
            border-radius: 1.5rem 1.5rem 0 0;
        }
        .time-slot {
            background: var(--gray-100);
            padding: 0.8rem;
            border-radius: 1rem;
            text-align: center;
            font-weight: 600;
        }
        .teaching-slot {
            background: #e0f2fe;
            border-left: 6px solid var(--navy);
            border-radius: 1.2rem;
            padding: 0.6rem;
            cursor: pointer;
            transition: 0.2s;
            font-size: 0.9rem;
        }
        .teaching-slot.lab {
            background: #fed7aa;
            border-left-color: var(--warning);
        }
        .office-hours {
            background: #d1fae5;
            border-left-color: var(--success);
        }
        .meeting {
            background: #f1f0ff;
            border-left-color: #8b5cf6;
        }

        /* right column panels */
        .right-panels {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .panel {
            background: white;
            border-radius: 2.2rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .task-item, .request-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px dashed var(--gray-300);
        }
        .badge {
            background: var(--navy);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-size: 0.8rem;
        }
        .btn-sm {
            background: var(--navy-light);
            color: white;
            border: none;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            cursor: pointer;
        }

        .chart-container {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .mini-chart {
            width: 100px;
            height: 100px;
        }

        .modal {
            display: none;
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 3rem;
            padding: 2rem;
            max-width: 400px;
        }

        .toast {
            position: fixed; bottom:30px; right:30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-row">
        <div class="header-title">
            <h1>📋 Dr. Sharma's Teaching Schedule</h1>
            <p>Department of Computer Science · 2024-25</p>
        </div>
        <div class="view-controls">
            <button class="view-btn active">Week</button>
            <button class="view-btn">Month</button>
            <button class="view-btn">Agenda</button>
        </div>
    </div>

    <!-- controls -->
    <div class="control-bar">
        <button class="nav-btn"><i class="fas fa-chevron-left"></i> Prev</button>
        <button class="nav-btn" id="todayBtn">Today</button>
        <button class="nav-btn">Next <i class="fas fa-chevron-right"></i></button>
        <span class="date-range">9 Sep – 13 Sep, 2024</span>
        <select class="filter-course">
            <option>All courses</option>
            <option>CS301</option>
            <option>CS311</option>
            <option>CS410</option>
        </select>
        <i class="fas fa-share-alt" style="margin-left:auto; cursor:pointer;" title="Share schedule"></i>
    </div>

    <!-- main grid -->
    <div class="main-grid">
        <!-- left: weekly calendar -->
        <div class="calendar-card">
            <h3><i class="far fa-calendar-alt"></i> Weekly Teaching Calendar</h3>
            <div class="week-grid">
                <div class="time-header"></div>
                <div class="day-header">Mon</div><div class="day-header">Tue</div><div class="day-header">Wed</div><div class="day-header">Thu</div><div class="day-header">Fri</div>

                <!-- time 8-9 -->
                <div class="time-slot">8:00-9:00</div>
                <div class="teaching-slot" data-course="CS301"><strong>CS301</strong> DSA<br>LH-101 · Sec A (72)<br><i class="fas fa-user-graduate"></i> TA: Rohan</div>
                <div class="office-hours"><i class="fas fa-mug-hot"></i> Office Hrs</div>
                <div></div>
                <div class="teaching-slot lab"><strong>CS311</strong> DBMS Lab<br>Lab-203 · Sec B (32)</div>
                <div></div>

                <!-- 9-10 -->
                <div class="time-slot">9:00-10:00</div>
                <div class="teaching-slot">CS301 (cont.)</div>
                <div></div>
                <div class="meeting"><i class="fas fa-users"></i> Dept Meeting</div>
                <div></div>
                <div class="teaching-slot lab">CS311 Lab</div>

                <!-- 10-11 -->
                <div class="time-slot">10:00-11:00</div>
                <div></div>
                <div class="teaching-slot"><strong>CS410</strong> ML (online)<br>Zoom · Sec C (65)</div>
                <div></div>
                <div class="teaching-slot">CS301</div>
                <div></div>

                <!-- 11-12 -->
                <div class="time-slot">11:00-12:00</div>
                <div></div><div></div><div></div><div></div><div class="teaching-slot lab">CS311 Lab</div>

                <!-- lunch -->
                <div class="time-slot">12:00-13:00</div>
                <div style="background:#f1f5f9; border-radius:1rem; padding:0.8rem;" colspan="5">🍽️ Lunch</div>
                <div></div><div></div><div></div><div></div><div></div>
            </div>
            <p style="margin-top:1rem;"><i class="fas fa-info-circle"></i> Click any class for details · drag to reschedule</p>
        </div>

        <!-- right panels -->
        <div class="right-panels">
            <!-- Course load overview -->
            <div class="panel">
                <h3>📊 Course Load</h3>
                <div class="chart-container">
                    <canvas id="hoursChart" width="100" height="100"></canvas>
                    <canvas id="pieChart" width="100" height="100"></canvas>
                </div>
                <p>Total hours: 14 | Theory 10, Lab 4</p>
            </div>

            <!-- Faculty tasks -->
            <div class="panel">
                <h3>✅ Pending Tasks</h3>
                <div class="task-item"><span>📝 Attendance (CS301)</span> <span class="badge">3 min</span></div>
                <div class="task-item"><span>📄 Grading – Quiz 2</span> <span class="badge">24 submissions</span></div>
                <div class="task-item"><span>🔬 Lab report review</span> <span class="badge">12</span></div>
            </div>

            <!-- Substitute requests -->
            <div class="panel">
                <h3>🔄 Substitute Requests</h3>
                <div class="request-item"><span>Dr. Lee requests CS410 on Fri</span> <button class="btn-sm">Accept</button></div>
                <div class="request-item"><span>Prof. Miller (sick) Wed 10am</span> <button class="btn-sm">Decline</button></div>
            </div>

            <!-- Room change requests -->
            <div class="panel">
                <h3>🚪 Room Change Requests</h3>
                <div class="request-item"><span>CS301: LH-101 → LH-102 (50 students)</span> <button class="btn-sm">Approve</button></div>
            </div>

            <!-- Schedule conflicts -->
            <div class="panel">
                <h3>⚠️ Conflicts</h3>
                <p>Double-booking: CS301 & meeting Wed 9am – <a href="#">swap</a></p>
            </div>
        </div>
    </div>

    <!-- personal events & print row -->
    <div style="display: flex; gap:2rem; margin-top:2rem; background:white; border-radius:2.5rem; padding:1.5rem;">
        <div><i class="fas fa-calendar-plus"></i> <input type="text" placeholder="Add personal event"></div>
        <div><i class="fas fa-print"></i> Print options: <select><option>Weekly schedule</option><option>Course-wise</option></select></div>
        <div><i class="fas fa-search-plus"></i> Zoom controls</div>
    </div>
</div>

<!-- class details modal -->
<div id="classModal" class="modal">
    <div class="modal-content">
        <h3>CS301: Data Structures</h3>
        <p>🕒 Mon 8-10, Wed 8-9 · LH-101</p>
        <p>👥 Section A (72 students) TA: Rohan</p>
        <p>📧 Quick email to students</p>
        <button class="btn-sm" id="markAttendance">Mark Attendance</button>
        <button id="closeModal">Close</button>
    </div>
</div>

<!-- toast -->
<div id="toast" class="toast">✅ Attendance recorded</div>

<script>
    (function() {
        // simple charts
        const ctxHours = document.getElementById('hoursChart').getContext('2d');
        new Chart(ctxHours, {
            type: 'bar',
            data: {
                labels: ['Mon','Tue','Wed','Thu','Fri'],
                datasets: [{
                    data: [4,2,3,2,3],
                    backgroundColor: '#0a3b5b'
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });

        const ctxPie = document.getElementById('pieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Theory','Lab'],
                datasets: [{
                    data: [10,4],
                    backgroundColor: ['#0a3b5b','#f4c542']
                }]
            },
            options: { responsive: true }
        });

        // modal on class click
        const modal = document.getElementById('classModal');
        const closeModal = document.getElementById('closeModal');
        document.querySelectorAll('.teaching-slot').forEach(slot => {
            slot.addEventListener('click', () => {
                modal.classList.add('active');
            });
        });
        closeModal.addEventListener('click', () => modal.classList.remove('active'));

        // attendance button inside modal
        document.getElementById('markAttendance').addEventListener('click', () => {
            modal.classList.remove('active');
            const toast = document.getElementById('toast');
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 1500);
        });

        // today button simulation
        document.getElementById('todayBtn').addEventListener('click', () => {
            alert('Today view: scrolling to current time (simulated)');
        });

        // drag reschedule simulation (just alert)
        document.querySelectorAll('.teaching-slot').forEach(slot => {
            slot.setAttribute('draggable', 'true');
            slot.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text', 'reschedule');
                alert('Drag to reschedule (simulation)');
            });
        });

        // right-click quick email
        document.querySelectorAll('.teaching-slot').forEach(slot => {
            slot.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                alert('Quick email to students (simulated)');
            });
        });

        // share schedule
        document.querySelector('.fa-share-alt').addEventListener('click', () => {
            alert('Shareable link copied (simulated)');
        });

        // approve/decline requests simulation
        document.querySelectorAll('.btn-sm').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                alert('Request updated');
            });
        });
    })();
</script>
</body>
</html>

