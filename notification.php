<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Center · TimetableGen</title>
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
            --info: #3b82f6;
            --shadow-md: 0 12px 30px -8px rgba(10,59,91,0.15);
            --border-radius: 2rem;
        }

        body {
            background: var(--bg-light);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1300px;
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
            font-size: 2.8rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }
        .btn-outline {
            background: white;
            border: 1.5px solid var(--navy);
            color: var(--navy);
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
        }

        /* filter bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            background: white;
            padding: 0.8rem 1.5rem;
            border-radius: 5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        .filter-chip {
            padding: 0.5rem 1.5rem;
            border-radius: 40px;
            background: var(--gray-100);
            font-weight: 500;
            cursor: pointer;
            transition: 0.2s;
        }
        .filter-chip.active {
            background: var(--navy);
            color: white;
        }
        .filter-chip .count {
            background: white;
            color: var(--navy);
            border-radius: 20px;
            padding: 0.1rem 0.5rem;
            margin-left: 0.3rem;
        }
        .search-box {
            display: flex;
            align-items: center;
            margin-left: auto;
            background: var(--gray-100);
            padding: 0.3rem 1rem;
            border-radius: 40px;
        }
        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            padding: 0.4rem;
        }

        /* main layout: left notifications, right timeline mini? We'll keep as column */
        .notifications-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        /* notification group */
        .group {
            background: white;
            border-radius: 2.5rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        .group-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 1rem;
        }
        .notification-card {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-300);
            transition: 0.2s;
        }
        .notification-card:last-child {
            border-bottom: none;
        }
        .notification-card.unread {
            background: #f0f9ff;
            border-radius: 1.5rem;
            padding: 1rem;
        }
        .icon {
            width: 50px;
            height: 50px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .content {
            flex: 1;
        }
        .title {
            font-weight: 600;
        }
        .time {
            color: var(--gray-600);
            font-size: 0.8rem;
        }
        .badge-priority {
            font-size: 0.7rem;
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
        }
        .high { background: #fee2e2; color: #991b1b; }
        .medium { background: #fed7aa; color: #92400e; }
        .low { background: #dbeafe; color: #1e3a8a; }

        .action-icons {
            display: flex;
            gap: 1rem;
            color: var(--gray-600);
        }
        .action-icons i {
            cursor: pointer;
        }

        /* slide-out panel for settings */
        .settings-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 380px;
            height: 100%;
            background: white;
            box-shadow: var(--shadow-md);
            transition: right 0.3s;
            padding: 2rem;
            border-radius: 3rem 0 0 3rem;
            z-index: 2000;
        }
        .settings-panel.open {
            right: 0;
        }

        .overlay {
            display: none;
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.3);
            z-index: 1500;
        }
        .overlay.active { display: block; }

        .toast {
            position: fixed; bottom:30px; right:30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 3000;
        }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-row">
        <div class="header-title">
            <h1>🔔 Notification Center</h1>
            <p>Stay updated with your academic activities</p>
        </div>
        <div class="action-buttons">
            <button class="btn-primary" id="markAllBtn"><i class="fas fa-check-double"></i> Mark all as read</button>
            <button class="btn-outline" id="settingsBtn"><i class="fas fa-cog"></i> Settings</button>
        </div>
    </div>

    <!-- filter bar -->
    <div class="filter-bar">
        <span class="filter-chip active">All <span class="count">24</span></span>
        <span class="filter-chip">Unread <span class="count">7</span></span>
        <span class="filter-chip">Mentions</span>
        <span class="filter-chip">System</span>
        <span class="filter-chip">Schedule</span>
        <span class="filter-chip">Leave</span>
        <span class="filter-chip">Announcements</span>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search...">
        </div>
    </div>

    <!-- notifications grouped -->
    <div class="notifications-container">
        <!-- Today group -->
        <div class="group">
            <div class="group-title"><i class="far fa-clock"></i> Today</div>
            <div class="notification-card unread">
                <div class="icon" style="background:#e0f2fe;"><i class="fas fa-chalkboard-teacher" style="color:var(--navy);"></i></div>
                <div class="content">
                    <div class="title">⏰ Class starts in 30 minutes: CS501 in LH-101 <span class="badge-priority high">High</span></div>
                    <div class="time">5 min ago</div>
                </div>
                <div class="action-icons">
                    <i class="fas fa-check" title="Mark read"></i>
                    <i class="fas fa-bell-slash" title="Snooze"></i>
                    <i class="fas fa-ellipsis-v"></i>
                </div>
            </div>
            <div class="notification-card unread">
                <div class="icon" style="background:#fed7aa;"><i class="fas fa-pen"></i></div>
                <div class="content">
                    <div class="title">📝 Attendance pending for Machine Learning class</div>
                    <div class="time">15 min ago</div>
                </div>
                <div class="action-icons">
                    <i class="fas fa-check"></i>
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="notification-card">
                <div class="icon" style="background:#d1fae5;"><i class="fas fa-bullhorn"></i></div>
                <div class="content">
                    <div class="title">🔔 New announcement from CS Department</div>
                    <div class="time">1 hour ago</div>
                </div>
                <div class="action-icons">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <div class="notification-card">
                <div class="icon" style="background:#fef9c3;"><i class="fas fa-check-circle" style="color:green;"></i></div>
                <div class="content">
                    <div class="title">✅ Leave request approved (21 Mar)</div>
                    <div class="time">3 hours ago</div>
                </div>
            </div>
        </div>

        <!-- Yesterday group -->
        <div class="group">
            <div class="group-title">Yesterday</div>
            <div class="notification-card">
                <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="content">
                    <div class="title">📅 Timetable updated for next week</div>
                    <div class="time">yesterday</div>
                </div>
            </div>
            <div class="notification-card">
                <div class="icon"><i class="fas fa-user-slash"></i></div>
                <div class="content">
                    <div class="title">👨‍🏫 Dr. Sharma unavailable tomorrow</div>
                    <div class="time">yesterday</div>
                </div>
            </div>
        </div>

        <!-- Earlier -->
        <div class="group">
            <div class="group-title">Earlier</div>
            <div class="notification-card">
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <div class="content">
                    <div class="title">📊 Weekly report available</div>
                    <div class="time">3 days ago</div>
                </div>
            </div>
        </div>
    </div>

    <!-- bulk actions bar -->
    <div style="display:flex; gap:1rem; margin:2rem 0; background:white; padding:1rem 2rem; border-radius:5rem;">
        <span><i class="far fa-check-square"></i> Select</span>
        <span>Mark read</span>
        <span>Delete</span>
        <span>Archive</span>
    </div>

    <!-- notification timeline preview -->
    <div style="background:white; border-radius:2rem; padding:1.5rem;">
        <h4>📅 Notification timeline</h4>
        <p>Most notifications: Mon 10am · Response rate 87%</p>
    </div>

    <!-- empty state demo (hidden by default) -->
    <!-- <div class="group" style="text-align:center;">All caught up! 🎉</div> -->
</div>

<!-- settings slide-out panel -->
<div class="overlay" id="overlay"></div>
<div class="settings-panel" id="settingsPanel">
    <h3><i class="fas fa-sliders-h"></i> Notification Settings</h3>
    <div style="margin:1.5rem 0;">
        <label><input type="checkbox" checked> Schedule changes</label><br>
        <label><input type="checkbox" checked> Leave requests</label><br>
        <label><input type="checkbox" checked> Announcements</label><br>
        <label><input type="checkbox"> Email digest (daily)</label>
    </div>
    <h4>Quiet hours</h4>
    <p>22:00 – 07:00 <button class="btn-outline">Edit</button></p>
    <h4>Sound</h4>
    <select><option>Chime</option></select>
    <br><br>
    <button class="btn-primary" id="closeSettings">Save & Close</button>
</div>

<!-- toast -->
<div id="toast" class="toast">✅ Marked as read</div>

<script>
    (function() {
        // settings panel open/close
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsPanel = document.getElementById('settingsPanel');
        const overlay = document.getElementById('overlay');
        const closeSettings = document.getElementById('closeSettings');

        settingsBtn.addEventListener('click', () => {
            settingsPanel.classList.add('open');
            overlay.classList.add('active');
        });

        function closePanel() {
            settingsPanel.classList.remove('open');
            overlay.classList.remove('active');
        }
        closeSettings.addEventListener('click', closePanel);
        overlay.addEventListener('click', closePanel);

        // mark all as read (toast)
        document.getElementById('markAllBtn').addEventListener('click', () => {
            const toast = document.getElementById('toast');
            toast.innerText = '✅ All notifications marked as read';
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 2000);
        });

        // individual action icons: mark read (simulate)
        document.querySelectorAll('.action-icons .fa-check').forEach(icon => {
            icon.addEventListener('click', (e) => {
                e.stopPropagation();
                const toast = document.getElementById('toast');
                toast.innerText = '✅ Marked as read';
                toast.style.display = 'flex';
                setTimeout(() => toast.style.display = 'none', 1500);
                // simple visual: remove unread class
                const card = icon.closest('.notification-card');
                card.classList.remove('unread');
            });
        });

        // filter chip simulation
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                toast.innerText = '🔍 Filter applied';
                toast.style.display = 'flex';
                setTimeout(() => toast.style.display = 'none', 1000);
            });
        });

        // search simulation
        const searchInput = document.querySelector('.search-box input');
        searchInput.addEventListener('keypress', (e) => {
            if(e.key === 'Enter') alert('Search for: ' + searchInput.value);
        });
    })();
</script>
</body>
</html>

