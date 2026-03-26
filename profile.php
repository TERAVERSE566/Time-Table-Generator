<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$themeClass = isset($_SESSION['user_role']) ? 'theme-' . $_SESSION['user_role'] : '';

$stmt = $conn->prepare("SELECT name, email, role, phone, department, program_level, created_at FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

$name = $user['name'] ?? 'Unknown User';
$email = $user['email'] ?? '';
$role = $user['role'] ?? 'student';
$phone = $user['phone'] ?? '+1 555 0000';
$dept = $user['department'] ?? 'General';
$join_date = date("M Y", strtotime($user['created_at']));
$initials = strtoupper(substr($name, 0, 1));
if (strpos($name, ' ') !== false) {
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings · TimetableGen</title>
    <!-- Font Awesome 6 & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            font-size: 2.6rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
        }
        .progress-bar {
            width: 200px;
            height: 8px;
            background: var(--gray-300);
            border-radius: 20px;
        }
        .progress-fill {
            width: 75%;
            height: 8px;
            background: var(--navy);
            border-radius: 20px;
        }

        /* profile overview card */
        .profile-card {
            background: white;
            border-radius: 3rem;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            flex-wrap: wrap;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }
        .avatar-large {
            width: 120px;
            height: 120px;
            background: var(--navy);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            position: relative;
        }
        .upload-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--gold);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .profile-info h2 {
            font-size: 2rem;
            color: var(--navy);
        }
        .verified {
            color: var(--success);
            font-size: 1.2rem;
        }

        /* tabs */
        .settings-tabs {
            display: flex;
            gap: 0.8rem;
            background: white;
            padding: 0.6rem;
            border-radius: 60px;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 0.7rem 1.5rem;
            border: none;
            background: transparent;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .tab-btn.active {
            background: var(--navy);
            color: white;
        }

        /* tab content */
        .tab-pane {
            display: none;
            background: white;
            border-radius: 3rem;
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }
        .tab-pane.active {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px,1fr));
            gap: 1.5rem;
        }
        .form-group label {
            font-weight: 600;
            color: var(--navy);
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border-radius: 50px;
            border: 1px solid var(--gray-300);
            margin-top: 0.3rem;
        }

        .toggle-switch {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        }

        .badge {
            background: var(--navy-light);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
        }

        .action-bar {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            cursor: pointer;
        }
        .btn-outline {
            background: white;
            border: 1px solid var(--navy);
            color: var(--navy);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            cursor: pointer;
        }

        .strength-meter {
            display: flex;
            gap: 5px;
            margin: 0.5rem 0;
        }
        .strength-bar {
            height: 8px;
            width: 33%;
            background: var(--gray-300);
            border-radius: 10px;
        }
        .strength-bar.weak { background: var(--danger); }
        .strength-bar.medium { background: var(--warning); }
        .strength-bar.strong { background: var(--success); }

        .toast {
            position: fixed; bottom:30px; right:30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 2000;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .profile-card { flex-direction: column; text-align: center; }
            .bottom-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
        /* ===== PROPER DARK MODE ===== */
        body.dark-mode {
            background: #0f172a !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .profile-card, body.dark-mode .tab-pane, body.dark-mode .settings-tabs {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            color: #e2e8f0 !important;
            box-shadow: none !important;
        }
        /* Fix the heading colors explicitly so premium.css doesn't override */
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4, body.dark-mode h5 { color: #f8fafc !important; }
        body.dark-mode .header-title p, body.dark-mode .form-group label, body.dark-mode p, body.dark-mode li, body.dark-mode .verified, body.dark-mode span { color: #cbd5e1 !important; }
        
        body.dark-mode input, body.dark-mode select {
            background: #334155 !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
        }
        body.dark-mode .tab-btn { color: #94a3b8 !important; }
        body.dark-mode .tab-btn.active { background: #6366f1 !important; color: white !important; }
        body.dark-mode .btn-primary { background: #6366f1 !important; color: white !important; }
        body.dark-mode .btn-outline { background: transparent !important; color: #38bdf8 !important; border-color: #38bdf8 !important; }
        body.dark-mode .avatar-large { background: #6366f1 !important; }
        body.dark-mode .progress-bar { background: #475569 !important; }
        body.dark-mode .progress-fill { background: #38bdf8 !important; }
        body.dark-mode .badge { background: #475569 !important; color: #e2e8f0 !important; border:none; }
        body.dark-mode #toast { background: #6366f1 !important; color: white !important; }
    </style>
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">>
<div class="container">
    <!-- header -->
    <div class="header-row">
        <div class="header-title">
            <h1>⚙️ Profile Settings</h1>
            <p>Manage your account and preferences</p>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
            <span>Profile completeness</span>
            <div class="progress-bar"><div class="progress-fill"></div></div>
        </div>
    </div>

    <!-- profile overview card -->
    <div class="profile-card">
        <div class="avatar-large">
            <?= htmlspecialchars($initials) ?>
            <div class="upload-overlay"><i class="fas fa-camera"></i></div>
        </div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($name) ?> <span class="verified"><i class="fas fa-check-circle"></i> Verified</span></h2>
            <p><?= ucfirst(htmlspecialchars($role)) ?> · <?= htmlspecialchars($dept) ?></p>
            <p><i class="far fa-envelope"></i> <?= htmlspecialchars($email) ?> · <i class="fas fa-phone"></i> <?= htmlspecialchars($phone) ?></p>
            <p>Member since: <?= $join_date ?></p>
        </div>
    </div>

    <!-- settings tabs -->
    <div class="settings-tabs">
        <button class="tab-btn active" data-tab="personal"><i class="fas fa-user"></i> Personal</button>
        <button class="tab-btn" data-tab="academic"><i class="fas fa-graduation-cap"></i> Academic</button>
        <button class="tab-btn" data-tab="preferences"><i class="fas fa-sliders-h"></i> Preferences</button>
        <button class="tab-btn" data-tab="security"><i class="fas fa-lock"></i> Security</button>
        <button class="tab-btn" data-tab="notifications"><i class="fas fa-bell"></i> Notifications</button>
        <button class="tab-btn" data-tab="privacy"><i class="fas fa-shield-alt"></i> Privacy</button>
    </div>

    <!-- PERSONAL INFO TAB -->
    <div id="personal" class="tab-pane active">
        <h3>Personal Information</h3>
        <div class="form-grid">
            <div class="form-group"><label>Full name</label><input value="<?= htmlspecialchars($name) ?>"></div>
            <div class="form-group"><label>Email</label><input value="<?= htmlspecialchars($email) ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= htmlspecialchars($phone) ?>"></div>
            <div class="form-group"><label>Date of birth</label><input type="date" value="1985-06-15"></div>
            <div class="form-group"><label>Gender</label><select><option>Female</option></select></div>
        </div>
        <div class="action-bar"><button class="btn-primary">Save changes</button></div>
    </div>

    <!-- ACADEMIC TAB (role-specific) -->
    <div id="academic" class="tab-pane">
        <?php if ($role === 'admin'): ?>
        <h3>Administrative Details</h3>
        <div class="form-grid">
            <div class="form-group"><label>Role</label><input value="<?= ucfirst(htmlspecialchars($role)) ?>" readonly></div>
            <div class="form-group"><label>Department</label><input value="<?= htmlspecialchars($dept) ?>" readonly></div>
            <div class="form-group"><label>Member since</label><input value="<?= $join_date ?>" readonly></div>
        </div>
        <?php elseif ($role === 'faculty'): ?>
        <h3>Academic Details (Faculty)</h3>
        <div class="form-grid">
            <div class="form-group"><label>Department</label><input value="<?= htmlspecialchars($dept) ?>" readonly></div>
            <div class="form-group"><label>Designation</label><input value="Faculty Member"></div>
            <div class="form-group"><label>Member since</label><input value="<?= $join_date ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= htmlspecialchars($phone) ?>"></div>
        </div>
        <?php else: ?>
        <h3>Academic Details (Student)</h3>
        <div class="form-grid">
            <div class="form-group"><label>Program Level</label><input value="<?= htmlspecialchars($user['program_level'] ?? 'Not set') ?>" readonly></div>
            <div class="form-group"><label>Department</label><input value="<?= htmlspecialchars($dept) ?>" readonly></div>
            <div class="form-group"><label>Phone</label><input value="<?= htmlspecialchars($phone) ?>"></div>
            <div class="form-group"><label>Enrolled since</label><input value="<?= $join_date ?>" readonly></div>
        </div>
        <?php endif; ?>
        <div class="action-bar"><button class="btn-primary">Save changes</button></div>
    </div>

    <!-- PREFERENCES TAB -->
    <div id="preferences" class="tab-pane">
        <h3>Preferences</h3>
        <div class="form-grid">
            <div class="form-group"><label>Language</label><select><option>English</option></select></div>
            <div class="form-group"><label>Timezone</label><select><option>Asia/Kolkata</option></select></div>
            <div class="form-group"><label>Date format</label><select><option>DD/MM/YYYY</option></select></div>
            <div class="form-group"><label>Week start</label><select><option>Monday</option></select></div>
        </div>
        <div class="toggle-switch"><i class="fas fa-envelope"></i> Email notifications <input type="checkbox" checked></div>
        <div class="toggle-switch"><i class="fas fa-moon"></i> Dark mode <input type="checkbox" id="darkModeToggle"></div>
        <div class="action-bar"><button class="btn-primary">Save preferences</button></div>
    </div>

    <!-- SECURITY TAB -->
    <div id="security" class="tab-pane">
        <h3>Change password</h3>
        <div class="form-group"><label>Current password</label><input type="password"></div>
        <div class="form-group"><label>New password</label><input type="password"></div>
        <div class="strength-meter">
            <div class="strength-bar weak"></div><div class="strength-bar"></div><div class="strength-bar"></div>
        </div>
        <div class="form-group"><label>Confirm password</label><input type="password"></div>
        <p>Two-factor authentication: <button class="btn-outline">Enable</button></p>
        <p>Active sessions: 2 · <a href="feature_preview.php?feature=Log+Out+All+Sessions">Log out all</a></p>
        <div class="action-bar"><button class="btn-primary">Update security</button></div>
    </div>

    <!-- NOTIFICATIONS TAB -->
    <div id="notifications" class="tab-pane">
        <h3>Notification history</h3>
        <ul>
            <li>Schedule change: CS301 moved to LH-101 <span class="badge">1h ago</span></li>
            <li>Leave request approved <span class="badge">2d ago</span></li>
        </ul>
        <button class="btn-outline">Mark all read</button>
    </div>

    <!-- PRIVACY TAB -->
    <div id="privacy" class="tab-pane">
        <h3>Data & privacy</h3>
        <div class="form-grid" style="margin-top: 1.5rem; margin-bottom: 2rem;">
            <div class="form-group">
                <label>Profile visibility</label>
                <select id="profileVisibility">
                    <option value="public">Public - visible to everyone</option>
                    <option value="private">Private - visible only to connections</option>
                </select>
            </div>
        </div>
        <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
            <button class="btn-outline" id="downloadDataBtn"><i class="fas fa-download"></i> Download my data (.zip)</button>
            <button class="btn-primary" style="background:var(--danger); border:none;" id="deleteAccountBtn"><i class="fas fa-trash-alt"></i> Delete account</button>
        </div>
    </div>

    <!-- connected accounts & appearance -->
    <div class="bottom-grid">
        <div class="tab-pane" style="display:block;">
            <h4>🔗 Connected accounts</h4>
            <p style="margin:1rem 0;">Google Calendar <span class="badge">connected</span> <button class="btn-outline" style="padding:0.3rem 1rem; font-size:0.8rem; margin-left:0.5rem;">Sync now</button></p>
            <p>Microsoft 365 <span class="badge">connected</span></p>
        </div>
        <div class="tab-pane" style="display:block;">
            <h4>🎨 Appearance</h4>
            <div style="margin-top:1.5rem;">
                <label style="display:flex; align-items:center; gap:1rem; font-weight:600;">Accent color: <input type="color" id="accentColorPicker" value="#0a3b5b" style="width:60px; height:40px; padding:0; border:none; border-radius:10px; cursor:pointer; background:transparent;"></label>
            </div>
            <p style="margin-top:1rem; font-size:0.9rem; color:var(--gray-600);">Changes update immediately across the entire dashboard.</p>
        </div>
    </div>

    <!-- activity log -->
    <div class="tab-pane" style="display:block; margin-top:2rem;">
        <h3>📋 Recent activity</h3>
        <p>Changed password · 2 days ago</p>
        <p>Updated profile photo · 1 week ago</p>
    </div>
</div>

<!-- toast -->
<div id="toast" class="toast">✅ Changes saved</div>

<script>
    (function() {
        // tab switching
        const tabs = document.querySelectorAll('.tab-btn');
        const panes = document.querySelectorAll('.tab-pane');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                panes.forEach(p => p.classList.remove('active'));
                document.getElementById(target).classList.add('active');
            });
        });

        // save buttons simulate toast
        document.querySelectorAll('.btn-primary').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const toast = document.getElementById('toast');
                toast.style.display = 'flex';
                setTimeout(() => toast.style.display = 'none', 2000);
            });
        });

        // upload overlay alert
        document.querySelector('.upload-overlay').addEventListener('click', () => {
            alert('Image upload simulation (crop dialog)');
        });

        // password strength meter dummy
        // (static demo, no real logic)

        // Privacy Tab Actions
        const visibilitySelect = document.getElementById('profileVisibility');
        if(visibilitySelect) {
            visibilitySelect.addEventListener('change', () => {
                const toast = document.getElementById('toast');
                toast.innerText = '✅ Profile visibility strictly updated';
                toast.style.display = 'flex';
                setTimeout(() => { toast.style.display = 'none'; toast.innerText = '✅ Changes saved'; }, 2500);
            });
        }

        const downloadBtn = document.getElementById('downloadDataBtn');
        if(downloadBtn) {
            downloadBtn.addEventListener('click', () => {
                alert('📥 Compiling your personal data... A secure download link will be emailed to your registered address shortly.');
            });
        }

        const deleteBtn = document.getElementById('deleteAccountBtn');
        if(deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                const conf = confirm('⚠️ WARNING: This action is irreversible. Are you absolutely sure you want to permanently delete your account and all associated timetables/data?');
                if(conf) {
                    alert('Your account deletion request has been submitted to system administrators for processing.');
                }
            });
        }

        // Accent Color picker logic
        const colorPicker = document.getElementById('accentColorPicker');
        if (colorPicker) {
            colorPicker.value = localStorage.getItem('themeColor') || '#0a3b5b';
            colorPicker.addEventListener('input', (e) => {
                const val = e.target.value;
                document.documentElement.style.setProperty('--navy', val);
                document.documentElement.style.setProperty('--primary', val);
                document.documentElement.style.setProperty('--primary-color', val);
                localStorage.setItem('themeColor', val);
            });
        }

        // Dark mode logic
        const darkToggle = document.getElementById('darkModeToggle');
        if (darkToggle) {
            darkToggle.checked = localStorage.getItem('darkMode') === 'true';
            darkToggle.addEventListener('change', (e) => {
                const isDark = e.target.checked;
                document.body.classList.toggle('dark-mode', isDark);
                localStorage.setItem('darkMode', isDark);
                
                // Keep the top faculty dark mode toggle in sync if it exists (for other pages, purely defensive code here)
            });
        }
    })();
</script>
<script src="theme.js"></script>
</body>
</html>

