<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db.php';
$role = $_SESSION['user_role'] ?? 'student';
$dashUrl = ($role === 'admin') ? 'admin.php' : (($role === 'faculty') ? 'facultyD.php' : 'studentD.php');

$students = [];
$res = $conn->query("SELECT id, name FROM users WHERE role='student' AND id != " . (int)$_SESSION['user_id'] . " LIMIT 12");
while($r = $res->fetch_assoc()) $students[] = $r;

// If students table is empty or small, mock a few
if (count($students) < 4) {
    $mocks = ['Alex Johnson', 'Sarah Williams', 'Michael Brown', 'Emily Davis', 'David Wilson', 'Jessica Taylor'];
    foreach($mocks as $i => $m) {
        $students[] = ['id' => 900+$i, 'name' => $m];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Groups · TimetableGen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg-light); padding: 2rem; min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .back-btn { background: white; border: none; padding: 0.8rem 1.5rem; border-radius: 30px; cursor: pointer; font-weight: 600; color: var(--navy); box-shadow: var(--shadow-sm); text-decoration: none; }
        .btn-primary { background: var(--navy); color: white; border: none; padding: 0.8rem 2rem; border-radius: 30px; font-weight: 600; cursor: pointer; box-shadow: var(--shadow-md); transition: 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-primary:disabled { background: var(--gray-300); cursor: not-allowed; transform: none; box-shadow: none; }
        
        /* Grid */
        .peer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; }
        .peer-card { background: white; border-radius: 1.5rem; padding: 1.5rem; text-align: center; box-shadow: var(--shadow-sm); cursor: pointer; transition: 0.2s; border: 2px solid transparent; position: relative; }
        .peer-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
        .peer-card.selected { border-color: var(--navy); background: #f0f9ff; }
        
        .avatar { width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; background: linear-gradient(135deg, #10b981, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; }
        .peer-name { font-weight: 600; color: var(--navy); margin-bottom: 0.5rem; }
        .peer-course { font-size: 0.8rem; color: var(--gray-600); background: var(--gray-100); padding: 0.2rem 0.8rem; border-radius: 20px; display: inline-block; }
        
        .check-icon { position: absolute; top: 1rem; right: 1rem; width: 24px; height: 24px; border-radius: 50%; border: 2px solid var(--gray-300); display: flex; align-items: center; justify-content: center; color: white; font-size: 0.8rem; transition: 0.2s; }
        .peer-card.selected .check-icon { background: var(--navy); border-color: var(--navy); }
        
        /* Chat UI (Hidden initially) */
        #groupChat { display: none; background: white; border-radius: 2rem; box-shadow: var(--shadow-md); height: 600px; flex-direction: column; overflow: hidden; margin-top: 2rem; }
        .chat-header { background: var(--navy); padding: 1.5rem; color: white; display: flex; justify-content: space-between; align-items: center; }
        .chat-messages { flex: 1; padding: 1.5rem; overflow-y: auto; background: #fafafa; display: flex; flex-direction: column; gap: 1rem; }
        .chat-input { padding: 1rem 1.5rem; background: white; display: flex; gap: 1rem; border-top: 1px solid var(--gray-100); }
        .chat-input input { flex: 1; padding: 0.8rem 1.5rem; border-radius: 30px; border: 1px solid var(--gray-300); outline: none; font-size: 1rem; }
        .chat-input button { background: var(--navy); color: white; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; }
        
        .msg { max-width: 80%; padding: 1rem 1.5rem; border-radius: 1.5rem; line-height: 1.4; position: relative; }
        .msg.sys { background: transparent; color: var(--gray-600); text-align: center; align-self: center; font-size: 0.9rem; }
        .msg.sent { background: var(--navy); color: white; align-self: flex-end; border-bottom-right-radius: 0.5rem; }
        
        @media(max-width: 768px) {
            body { padding: 1rem; }
            .peer-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
            .avatar { width: 60px; height: 60px; font-size: 1.5rem; }
        }
    </style>
</head>
<body class="theme-<?= $role ?>">

<div class="container" id="mainView">
    <div class="header">
        <div style="display:flex; align-items:center; gap:1rem;">
            <a href="<?= $dashUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1><i class="fas fa-users"></i> Find Study Peers</h1>
        </div>
        <button class="btn-primary" id="startBtn" disabled onclick="startGroup()"><i class="fas fa-plus"></i> Start Group Session (<span id="selCount">0</span>)</button>
    </div>

    <p style="margin-bottom: 2rem; color: var(--gray-600);">Select classmates from your assigned courses to form a collaborative study group.</p>

    <div class="peer-grid">
        <?php foreach($students as $index => $s): ?>
        <div class="peer-card" onclick="toggleSelect(this, '<?= addslashes($s['name']) ?>')">
            <div class="check-icon"><i class="fas fa-check"></i></div>
            <?php 
                $colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899']; 
                $cArr = $colors[$index % count($colors)];
            ?>
            <div class="avatar" style="background: <?= $cArr ?>;"><?= strtoupper(substr($s['name'], 0, 1)) ?></div>
            <div class="peer-name"><?= htmlspecialchars($s['name']) ?></div>
            <div class="peer-course"><?= ($index % 2 == 0) ? 'CS301 (Mutual)' : 'MA201 (Mutual)' ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="container" id="groupChat">
    <div class="chat-header">
        <div>
            <h2 id="groupTitle">Study Group</h2>
            <p style="font-size:0.8rem; opacity:0.8;" id="groupMembers">You and others</p>
        </div>
        <button class="back-btn" style="padding:0.5rem 1rem;" onclick="location.reload()">Exit Group</button>
    </div>
    <div class="chat-messages" id="msgs">
        <div class="msg sys" id="sysMsg">Group created! Let the collaboration begin.</div>
    </div>
    <div class="chat-input">
        <input type="text" id="chatInput" placeholder="Say hello to the group..." onkeypress="if(event.key==='Enter') send()">
        <button onclick="send()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
let selected = [];
function toggleSelect(card, name) {
    if (card.classList.contains('selected')) {
        card.classList.remove('selected');
        selected = selected.filter(n => n !== name);
    } else {
        card.classList.add('selected');
        selected.push(name);
    }
    
    const count = selected.length;
    document.getElementById('selCount').innerText = count;
    document.getElementById('startBtn').disabled = count === 0;
}

function startGroup() {
    document.getElementById('mainView').style.display = 'none';
    const chat = document.getElementById('groupChat');
    chat.style.display = 'flex';
    
    document.getElementById('groupMembers').innerText = 'You, ' + selected.join(', ');
    
    // Simulate others saying hi after a delay
    setTimeout(() => {
        if(selected.length > 0) {
            const area = document.getElementById('msgs');
            const msg = document.createElement('div');
            msg.className = 'msg';
            msg.style.background = 'white';
            msg.style.alignSelf = 'flex-start';
            msg.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
            msg.innerHTML = `<strong>${selected[0]}</strong><br>Hey everyone! Ready to study?`;
            area.appendChild(msg);
        }
    }, 2000);
}

function send() {
    const input = document.getElementById('chatInput');
    const text = input.value.trim();
    if(!text) return;
    
    const area = document.getElementById('msgs');
    const msg = document.createElement('div');
    msg.className = 'msg sent';
    msg.innerText = text;
    area.appendChild(msg);
    
    input.value = '';
    area.scrollTop = area.scrollHeight;
}
</script>
</body>
</html>
