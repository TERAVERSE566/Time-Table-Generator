<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: login.php");
    exit();
}
include 'db.php';
$themeClass = isset($_SESSION['user_role']) ? 'theme-' . $_SESSION['user_role'] : '';
$user_name = $_SESSION['user_name'];
$initials = strtoupper(substr($user_name, 0, 1) . (strpos($user_name, ' ') ? substr(explode(' ', $user_name)[1], 0, 1) : ''));

// Fetch department from DB
$user_dept = 'Student';
$stmt = $conn->prepare("SELECT department FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $user_dept = $row['department'] ?: 'Student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard · TimetableGen</title>
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
            --primary: #0a3b5b;
            --primary-light: #1e4f6e;
            --accent: #f4c542;
            --accent-soft: #fbe9b1;
            --bg-light: #f9f7f0;
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
            background: linear-gradient(145deg, #f4f2ee, #faf7f2);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* welcome row */
        .welcome-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        .greeting h1 {
            font-size: 2.8rem;
            color: var(--primary);
        }
        .id-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background: white;
            padding: 0.8rem 2rem 0.8rem 1.5rem;
            border-radius: 80px;
            box-shadow: var(--shadow-md);
        }
        .avatar {
            width: 65px;
            height: 65px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 700;
        }
        .student-info {
            line-height: 1.4;
        }
        .student-info .name {
            font-weight: 700;
            font-size: 1.3rem;
        }
        .student-info .detail {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        .weather-date {
            background: var(--accent-soft);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
        }

        /* quick stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.2rem 1rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
        }
        .stat-icon { font-size: 2.2rem; }
        .stat-info h3 { font-weight: 400; color: var(--gray-600); }
        .stat-info .value { font-size: 1.8rem; font-weight: 700; color: var(--primary); }

        /* main grid: left 2/3, right 1/3 */
        .main-dash {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        /* left column */
        .left-col {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* today's timeline */
        .timeline-card {
            background: white;
            border-radius: 2.5rem;
            padding: 1.8rem;
            box-shadow: var(--shadow-md);
        }
        .timeline {
            position: relative;
            margin-top: 1rem;
        }
        .timeline-item {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.2rem;
        }
        .time-tag {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            font-weight: 600;
            min-width: 100px;
            text-align: center;
        }
        .class-badge {
            background: #e0f2fe;
            border-radius: 2rem;
            padding: 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex:1;
        }
        .class-badge.lab { background: #fed7aa; }
        .break {
            background: #f1f5f9;
            padding: 0.6rem 1.5rem;
            border-radius: 2rem;
        }

        /* weekly timetable mini */
        .week-grid {
            display: flex;
            gap: 0.3rem;
            margin-top: 1rem;
        }
        .week-day {
            flex:1;
            background: var(--gray-100);
            border-radius: 1.2rem;
            padding: 0.6rem 0.2rem;
            text-align: center;
            font-size: 0.75rem;
        }
        .class-dot {
            background: var(--accent);
            border-radius: 1rem;
            padding: 0.2rem;
            margin: 0.2rem 0;
        }

        /* attendance overview */
        .subject-attend {
            margin: 0.8rem 0;
        }
        .attend-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .progress {
            background: #e2e8f0;
            height: 8px;
            border-radius: 20px;
            flex: 1;
        }
        .progress-fill {
            height: 8px;
            background: var(--primary);
            border-radius: 20px;
            width: 85%;
        }
        .warning-fill { background: var(--danger); width: 65%; }

        /* right column */
        .right-col {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .deadline-card, .exam-card, .faculty-card, .resources-card {
            background: white;
            border-radius: 2.5rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .deadline-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed var(--gray-300);
            padding: 0.8rem 0;
        }
        .countdown {
            background: var(--accent-soft);
            padding: 0.2rem 1rem;
            border-radius: 30px;
            font-weight: 600;
        }

        .badge-achievement {
            background: var(--accent);
            border-radius: 30px;
            padding: 0.2rem 1rem;
            font-size: 0.8rem;
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin: 1.5rem 0;
        }
        .action-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 2000;
        }

        /* mobile */
        @media (max-width: 900px) {
            .main-dash { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
<div class="container">
    <!-- welcome header -->
    <div class="welcome-row">
        <div class="greeting">
            <h1>Hey, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>! Ready for classes? 🎓</h1>
            <p class="weather-date"><i class="fas fa-cloud-sun"></i> 24°C · <?php echo date("l, d M Y"); ?></p>
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="id-card" onclick="window.location.href='profile.php'" style="cursor:pointer;">
                <div class="avatar"><?= $initials ?></div>
                <div class="student-info">
                    <div class="name"><?= htmlspecialchars($user_name) ?></div>
                    <div class="detail">Student · <?= htmlspecialchars($user_dept) ?></div>
                </div>
            </div>
            <button style="background: white; border: none; padding: 0.8rem 1.5rem; border-radius: 40px; font-weight: 600; cursor: pointer; box-shadow: var(--shadow-md);" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
        </div>
    </div>

    <!-- quick overview cards -->
    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">📚</span><div class="stat-info"><span class="value">6</span><h3>Enrolled Courses</h3></div></div>
        <div class="stat-card"><span class="stat-icon">⏰</span><div class="stat-info"><span class="value">10:00 AM</span><h3>Next Class</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📍</span><div class="stat-info"><span class="value">LH-101</span><h3>Current Location</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📊</span><div class="stat-info"><span class="value">87%</span><h3>Attendance</h3></div></div>
    </div>

    <!-- main dash grid -->
    <div class="main-dash">
        <!-- LEFT COLUMN -->
        <div class="left-col">
            <!-- today's schedule timeline -->
            <div class="timeline-card">
                <h2><i class="far fa-clock"></i> Today's Schedule</h2>
                <div class="timeline">
                    <div class="timeline-item">
                        <span class="time-tag">09:00-10:00</span>
                        <div class="class-badge"><i class="fas fa-chalkboard-teacher"></i> <strong>CS301</strong> DSA · LH-101 · Prof. Chen <span class="badge-achievement">upcoming</span></div>
                    </div>
                    <div class="timeline-item">
                        <span class="time-tag">10:00-11:30</span>
                        <div class="class-badge lab"><i class="fas fa-flask"></i> <strong>CS311</strong> DBMS Lab · Lab-203 · Prof. Evans <span class="badge-achievement">lab</span></div>
                    </div>
                    <div class="timeline-item">
                        <span class="time-tag">11:30-12:00</span>
                        <div class="break"><i class="fas fa-mug-hot"></i> Break ☕</div>
                    </div>
                    <div class="timeline-item">
                        <span class="time-tag">12:00-13:30</span>
                        <div class="class-badge"><i class="fas fa-calculator"></i> <strong>MA201</strong> Discrete Math · LH-105 · Dr. Ray</div>
                    </div>
                </div>
            </div>

            <!-- weekly timetable interactive mini -->
            <div class="timeline-card">
                <h3>🗓️ Weekly Timetable <button class="action-btn" style="margin-left:1rem;"><i class="fas fa-calendar-plus"></i> add to calendar</button></h3>
                <div class="week-grid">
                    <div class="week-day">Mon <div class="class-dot">CS301</div><div class="class-dot">CS311</div></div>
                    <div class="week-day">Tue <div class="class-dot">MA201</div><div class="class-dot">CS307</div></div>
                    <div class="week-day">Wed <div class="class-dot">CS301</div><div class="class-dot">Lab</div></div>
                    <div class="week-day">Thu <div class="class-dot">MA201</div></div>
                    <div class="week-day">Fri <div class="class-dot">CS307</div><div class="class-dot">Tutorial</div></div>
                </div>
            </div>

            <!-- attendance overview + streak -->
            <div class="timeline-card">
                <h3>📈 Attendance Overview <i class="fas fa-fire" style="color:orange;"></i> Streak: 12 days</h3>
                <div class="subject-attend">
                    <div class="attend-row"><span>CS301</span> <div class="progress"><div class="progress-fill" style="width:92%"></div></div> 92%</div>
                    <div class="attend-row"><span>CS311 Lab</span> <div class="progress"><div class="progress-fill" style="width:78%"></div></div> 78%</div>
                    <div class="attend-row"><span>MA201</span> <div class="progress"><div class="progress-fill warning-fill" style="width:65%"></div></div> <span style="color:red;">65% (below 75%)</span></div>
                </div>
                <p><i class="fas fa-exclamation-triangle" style="color:red;"></i> Warning: MA201 attendance low!</p>
            </div>

            <!-- recent grades & leaderboard snippet -->
            <div class="timeline-card">
                <h3>📝 Recent Grades</h3>
                <p>Quiz 2: 18/20 (above class avg 16) <i class="fas fa-trophy" style="color:gold;"></i></p>
                <p>Assignment 3: 85/100</p>
                <p>Class rank: 7 / 72</p>
            </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="right-col">
            <!-- upcoming deadlines with countdown -->
            <div class="deadline-card">
                <h3>⏳ Upcoming Deadlines</h3>
                <div class="deadline-item"><span>📘 CS301 Assignment 4</span> <span class="countdown">2d left</span></div>
                <div class="deadline-item"><span>📐 MA201 Problem Set</span> <span class="countdown">5h left</span></div>
                <div class="deadline-item"><span>🔬 CS311 Lab report</span> <span class="countdown">tomorrow</span></div>
            </div>

            <!-- exam schedule -->
            <div class="exam-card">
                <h3>📋 Exam Schedule</h3>
                <p><i class="fas fa-pencil-alt"></i> Midterm CS301: 2 Apr, 10am, LH-101 (seat 45) <a href="feature_preview.php?feature=Syllabus">syllabus</a></p>
                <p><i class="fas fa-pencil-alt"></i> MA201: 5 Apr, 2pm, LH-105</p>
            </div>

            <!-- faculty contacts -->
            <div class="faculty-card">
                <h3>👨‍🏫 Faculty Contacts</h3>
                <p><i class="fas fa-envelope"></i> Dr. Chen · chen@college.edu (office hours Mon 3pm)</p>
                <p><i class="fas fa-envelope"></i> Prof. Evans · evans@college.edu</p>
                <button class="action-btn" style="padding:0.4rem 1rem;" onclick="window.location.href='messages.php?faculty=Prof.+Evans'"><i class="fas fa-comment"></i> Message</button>
            </div>

            <!-- study resources -->
            <div class="resources-card">
                <h3>📚 Study Resources</h3>
                <ul>
                    <li><i class="fas fa-download"></i> DSA lecture slides</li>
                    <li><i class="fas fa-download"></i> Previous year papers</li>
                    <li><i class="fas fa-link"></i> Python reference</li>
                </ul>
            </div>

            <!-- campus announcements / gamification -->
            <div class="deadline-card">
                <h3>🎉 Campus Buzz</h3>
                <p><i class="fas fa-bullhorn"></i> Hackathon this weekend! Register now.</p>
                <p><i class="fas fa-medal"></i> Your study hours: 28 this week (top 10%)</p>
                <div>🏆 Achievements: <span class="badge-achievement">early bird</span> <span class="badge-achievement">perfect attend</span></div>
            </div>

            <!-- quick actions -->
            <div class="quick-actions">
                <button class="action-btn" id="qrBtn" onclick="window.location.href='attendance.php'"><i class="fas fa-clipboard-check"></i> Attendance View</button>
                <button class="action-btn" onclick="window.location.href='table_view.php'"><i class="fas fa-calendar-week"></i> Full Schedule</button>
                <button class="action-btn" onclick="window.location.href='grade_calc.php'"><i class="fas fa-calculator"></i> Grade Calc</button>
                <button class="action-btn" onclick="window.location.href='study_group.php'"><i class="fas fa-users"></i> Study Group</button>
            </div>
        </div>
    </div>

    <!-- footer mini: friend comparison & map -->
    <div style="display: flex; gap:2rem; margin-top:2rem; flex-wrap:wrap; background:white; border-radius:2rem; padding:1.5rem;">
        <div><i class="fas fa-user-friends"></i> Friend's next class: <strong>Sam</strong> also in CS301 at 10am</div>
        <div><i class="fas fa-map-marked-alt"></i> Campus map: <a href="feature_preview.php?feature=Campus+Map">LH-101 is near library</a></div>
        <div><i class="fas fa-bell"></i> Notifications <span class="badge-achievement">3</span></div>
    </div>
</div>

<!-- toast for QR simulation -->
<div id="toast" class="toast">✅ Attendance marked via QR!</div>

<script>
    (function() {
        // QR code scanner simulation
        document.getElementById('qrBtn').addEventListener('click', function() {
            const toast = document.getElementById('toast');
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 2000);
        });

        // grade calculator popup simulation
        document.querySelectorAll('.action-btn')[2].addEventListener('click', function() {
            alert('📊 Grade calculator: enter marks to predict GPA');
        });

        // friend schedule / map links simulation
        document.querySelectorAll('a[href="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                alert('🔍 Syllabus preview (simulated)');
            });
        });

        // dynamic date and greeting (optional)
        const dateEl = document.querySelector('.weather-date');
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const today = new Date().toLocaleDateString(undefined, options);
        // update if needed
    })();
</script>
</body>
</html>

