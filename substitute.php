<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}
include 'db.php';
$themeClass = 'theme-faculty';
$user_name = $_SESSION['user_name'];

// Fetch other faculty members for substitute selection
$faculty_list = [];
$stmt = $conn->prepare("SELECT id, name, email, department FROM users WHERE role='faculty' AND id != ? ORDER BY name");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $faculty_list[] = $row;
}

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = htmlspecialchars($_POST['date'] ?? '');
    $course = htmlspecialchars($_POST['course'] ?? '');
    $time_slot = htmlspecialchars($_POST['time_slot'] ?? '');
    $substitute_id = htmlspecialchars($_POST['substitute_id'] ?? '');
    $reason = htmlspecialchars($_POST['reason'] ?? '');
    $success = "Substitute request submitted for $course on $date. Awaiting approval.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Substitute · TimetableGen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        :root {
            --primary: #0a3b5b; --primary-light: #1e4f6e; --accent: #f4c542;
            --bg: #f4f7fc; --white: #fff; --gray-100: #f1f5f9; --gray-300: #cbd5e1; --gray-600: #475569;
            --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
            --shadow: 0 12px 30px -8px rgba(10,59,91,0.15); --radius: 2rem;
        }
        body { background: var(--bg); padding: 2rem; min-height: 100vh; }
        .container { max-width: 1100px; margin: 0 auto; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .page-header h1 { font-size: 2.2rem; color: var(--primary); }
        .back-btn { background: var(--primary); color: white; border: none; padding: 0.7rem 1.8rem; border-radius: 50px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
        .back-btn:hover { background: var(--primary-light); }

        .sub-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 2rem; }
        @media (max-width: 900px) { .sub-grid { grid-template-columns: 1fr; } }

        .card { background: var(--white); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); }
        .card h2 { color: var(--primary); margin-bottom: 1.5rem; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 600; color: var(--gray-600); margin-bottom: 0.4rem; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.8rem 1.2rem; border: 1.5px solid var(--gray-300); border-radius: 1rem;
            font-size: 1rem; background: var(--gray-100); outline: none; transition: 0.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(10,59,91,0.1); }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

        .submit-btn { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; border: none; padding: 0.9rem 2.5rem; border-radius: 50px; font-size: 1.05rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 0.6rem; }
        .submit-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(10,59,91,0.3); }

        .success-msg { background: #d1fae5; color: #065f46; padding: 1rem 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; font-weight: 600; }

        /* History */
        .history-item { display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--gray-100); }
        .history-item:last-child { border: none; }
        .status-badge { padding: 0.25rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .h-course { font-weight: 700; color: var(--primary); }
        .h-meta { font-size: 0.85rem; color: var(--gray-600); }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-user-clock"></i> Request Substitute</h1>
        <button class="back-btn" onclick="history.back()"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
    </div>

    <div class="sub-grid">
        <!-- Request Form -->
        <div class="card">
            <h2><i class="fas fa-plus-circle"></i> New Request</h2>
            <?php if ($success): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?= $success ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Absence</label>
                        <input type="date" name="date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Time Slot</label>
                        <select name="time_slot" required>
                            <option value="" disabled selected>Select…</option>
                            <option>09:00 – 10:00</option>
                            <option>10:00 – 11:30</option>
                            <option>12:00 – 13:30</option>
                            <option>14:00 – 15:30</option>
                            <option>15:30 – 17:00</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Course</label>
                    <select name="course" required>
                        <option value="" disabled selected>Select course…</option>
                        <option>CS501 – Machine Learning</option>
                        <option>CS410 – Deep Learning</option>
                        <option>CS307 – Database Systems</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Preferred Substitute</label>
                    <select name="substitute_id" required>
                        <option value="" disabled selected>Select faculty…</option>
                        <?php foreach ($faculty_list as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['department'] ?? '—') ?>)</option>
                        <?php endforeach; ?>
                        <?php if (empty($faculty_list)): ?>
                            <option value="0">No other faculty found</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" placeholder="e.g. Medical appointment, conference, etc." required></textarea>
                </div>
                <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Request</button>
            </form>
        </div>

        <!-- Request History -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Request History</h2>
            <div class="history-item">
                <div>
                    <div class="h-course">CS501 – Machine Learning</div>
                    <div class="h-meta">15 Mar 2026 · 10:00–11:30 · Sub: Prof. Evans</div>
                </div>
                <span class="status-badge status-approved">Approved</span>
            </div>
            <div class="history-item">
                <div>
                    <div class="h-course">CS307 – Database Systems</div>
                    <div class="h-meta">10 Mar 2026 · 14:00–15:30 · Sub: Dr. Ray</div>
                </div>
                <span class="status-badge status-approved">Approved</span>
            </div>
            <div class="history-item">
                <div>
                    <div class="h-course">CS410 – Deep Learning</div>
                    <div class="h-meta">5 Mar 2026 · 12:00–13:30 · Sub: Prof. Chen</div>
                </div>
                <span class="status-badge status-rejected">Rejected</span>
            </div>
            <div class="history-item">
                <div>
                    <div class="h-course">CS501 – Machine Learning</div>
                    <div class="h-meta">1 Mar 2026 · 10:00–11:30 · Sub: Dr. Gupta</div>
                </div>
                <span class="status-badge status-pending">Pending</span>
            </div>
        </div>
    </div>
</div>
</body>
</html>
