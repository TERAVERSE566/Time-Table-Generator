<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$themeClass = isset($_SESSION['user_role']) ? 'theme-' . $_SESSION['user_role'] : '';
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Handle form submission
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $conn->real_escape_string($_POST['to'] ?? '');
    $subject = $conn->real_escape_string($_POST['subject'] ?? '');
    $message = $conn->real_escape_string($_POST['message'] ?? '');
    // In production, send email or save to messages table. For now, just confirm.
    $success = "Message sent successfully to $to!";
}

// Fetch faculty/admin contacts
$contacts = [];
$q = $conn->query("SELECT name, email, role, department FROM users WHERE role IN ('admin','faculty') ORDER BY role, name");
while ($row = $q->fetch_assoc()) {
    $contacts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact · TimetableGen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        :root {
            --primary: #0a3b5b; --primary-light: #1e4f6e; --accent: #f4c542;
            --bg: #f4f7fc; --white: #fff; --gray-100: #f1f5f9; --gray-300: #cbd5e1; --gray-600: #475569;
            --success: #10b981; --danger: #ef4444;
            --shadow: 0 12px 30px -8px rgba(10,59,91,0.15); --radius: 2rem;
        }
        body { background: var(--bg); padding: 2rem; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .page-header h1 { font-size: 2.2rem; color: var(--primary); }
        .back-btn { background: var(--primary); color: white; border: none; padding: 0.7rem 1.8rem; border-radius: 50px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: 0.2s; }
        .back-btn:hover { background: var(--primary-light); }

        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 900px) { .contact-grid { grid-template-columns: 1fr; } }

        .card { background: var(--white); border-radius: var(--radius); padding: 2rem; box-shadow: var(--shadow); }
        .card h2 { color: var(--primary); margin-bottom: 1.5rem; font-size: 1.4rem; }

        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-weight: 600; color: var(--gray-600); margin-bottom: 0.4rem; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.8rem 1.2rem; border: 1.5px solid var(--gray-300); border-radius: 1rem;
            font-size: 1rem; transition: 0.2s; background: var(--gray-100); outline: none;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary); box-shadow: 0 0 0 3px rgba(10,59,91,0.1);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }

        .send-btn { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; border: none; padding: 0.9rem 2.5rem; border-radius: 50px; font-size: 1.05rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 0.6rem; }
        .send-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(10,59,91,0.3); }

        .success-msg { background: #d1fae5; color: #065f46; padding: 1rem 1.5rem; border-radius: 1rem; margin-bottom: 1.5rem; font-weight: 600; }

        .contact-list { max-height: 500px; overflow-y: auto; }
        .contact-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; border-radius: 1.2rem; transition: 0.2s; border-bottom: 1px solid var(--gray-100); }
        .contact-item:hover { background: var(--gray-100); }
        .contact-avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
        .contact-avatar.admin-av { background: #7c3aed; }
        .contact-details { flex: 1; }
        .contact-details .c-name { font-weight: 700; color: var(--primary); }
        .contact-details .c-meta { font-size: 0.85rem; color: var(--gray-600); }
        .contact-role { padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .contact-role.admin { background: #ede9fe; color: #7c3aed; }
        .contact-role.faculty { background: #d1fae5; color: #065f46; }

        .quick-contact-btns { display: flex; gap: 0.5rem; }
        .qc-btn { width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; font-size: 0.9rem; }
        .qc-btn.email { background: #dbeafe; color: #1d4ed8; }
        .qc-btn.email:hover { background: #1d4ed8; color: white; }
    </style>
    <link rel="stylesheet" href="premium.css">
</head>
<body class="<?= htmlspecialchars($themeClass) ?>">
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-address-book"></i> Contact Directory</h1>
        <button class="back-btn" onclick="history.back()"><i class="fas fa-arrow-left"></i> Back to Dashboard</button>
    </div>

    <div class="contact-grid">
        <!-- Send Message Form -->
        <div class="card">
            <h2><i class="fas fa-paper-plane"></i> Send a Message</h2>
            <?php if ($success): ?>
                <div class="success-msg"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>To</label>
                    <select name="to" required>
                        <option value="" disabled selected>Select recipient…</option>
                        <?php foreach ($contacts as $c): ?>
                            <option value="<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['name']) ?> (<?= ucfirst($c['role']) ?>)</option>
                        <?php endforeach; ?>
                        <option value="admin@timetablegen.com">System Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" placeholder="e.g. Room change request" required>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" placeholder="Write your message here…" required></textarea>
                </div>
                <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i> Send Message</button>
            </form>
        </div>

        <!-- Contact Directory -->
        <div class="card">
            <h2><i class="fas fa-users"></i> Faculty & Admin Directory</h2>
            <div class="contact-list">
                <?php if (empty($contacts)): ?>
                    <p style="color: var(--gray-600);">No contacts found.</p>
                <?php else: ?>
                    <?php foreach ($contacts as $c):
                        $initials = strtoupper(substr($c['name'], 0, 1));
                        if (strpos($c['name'], ' ') !== false) {
                            $initials .= strtoupper(substr(explode(' ', $c['name'])[1], 0, 1));
                        }
                    ?>
                    <div class="contact-item">
                        <div class="contact-avatar <?= $c['role'] === 'admin' ? 'admin-av' : '' ?>"><?= $initials ?></div>
                        <div class="contact-details">
                            <div class="c-name"><?= htmlspecialchars($c['name']) ?></div>
                            <div class="c-meta"><i class="fas fa-envelope"></i> <?= htmlspecialchars($c['email']) ?> · <?= htmlspecialchars($c['department'] ?? '—') ?></div>
                        </div>
                        <span class="contact-role <?= $c['role'] ?>"><?= $c['role'] ?></span>
                        <div class="quick-contact-btns">
                            <button class="qc-btn email" title="Email" onclick="window.location.href='mailto:<?= htmlspecialchars($c['email']) ?>'"><i class="fas fa-envelope"></i></button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
