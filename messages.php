<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'db.php';
$role = $_SESSION['user_role'] ?? 'student';
$dashUrl = ($role === 'admin') ? 'admin.php' : (($role === 'faculty') ? 'facultyD.php' : 'studentD.php');

$target = isset($_GET['faculty']) ? htmlspecialchars($_GET['faculty']) : 'Dr. Chen';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages · TimetableGen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg-light); padding: 1rem; height: 100vh; display: flex; flex-direction: column; overflow: hidden; }
        .header { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: white; border-radius: 1.5rem; margin-bottom: 1rem; box-shadow: var(--shadow-sm); }
        .back-btn { background: var(--gray-100); border: none; padding: 0.5rem 1rem; border-radius: 20px; cursor: pointer; font-weight: 600; color: var(--navy); text-decoration: none; }
        .chat-container { display: flex; flex: 1; min-height: 0; gap: 1rem; border-radius: 1.5rem; overflow: hidden; }
        
        .contacts-sidebar { width: 300px; background: white; border-radius: 1.5rem; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); }
        .search-br { padding: 1rem; border-bottom: 1px solid var(--gray-100); }
        .search-br input { width: 100%; padding: 0.7rem 1rem; border-radius: 30px; border: 1px solid var(--gray-300); background: var(--gray-100); outline: none; }
        .contact-list { flex: 1; overflow-y: auto; }
        .contact { padding: 1rem; display: flex; align-items: center; gap: 1rem; cursor: pointer; border-bottom: 1px solid var(--gray-100); transition: background 0.2s; }
        .contact:hover, .contact.active { background: var(--gray-100); border-left: 4px solid var(--navy); }
        .avatar { width: 45px; height: 45px; border-radius: 50%; background: var(--navy-light); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
        
        .chat-area { flex: 1; background: white; border-radius: 1.5rem; display: flex; flex-direction: column; box-shadow: var(--shadow-sm); position: relative; }
        .chat-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--gray-100); display: flex; align-items: center; gap: 1rem; font-weight: 600; font-size: 1.2rem; }
        .messages { flex: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; background: #fafafa; }
        .msg { max-width: 70%; padding: 0.8rem 1.2rem; border-radius: 1.5rem; line-height: 1.5; position: relative; }
        .msg.received { background: white; border: 1px solid var(--gray-300); align-self: flex-start; border-bottom-left-radius: 0.5rem; }
        .msg.sent { background: var(--navy); color: white; align-self: flex-end; border-bottom-right-radius: 0.5rem; }
        .msg-time { font-size: 0.7rem; color: var(--gray-600); margin-top: 0.3rem; display: block; text-align: right; }
        .msg.sent .msg-time { color: rgba(255,255,255,0.7); }
        
        .chat-input { padding: 1rem; border-top: 1px solid var(--gray-100); background: white; display: flex; gap: 0.5rem; align-items: center; }
        .chat-input input { flex: 1; padding: 0.8rem 1.5rem; border-radius: 30px; border: 1px solid var(--gray-300); outline: none; font-size: 1rem; }
        .send-btn { background: var(--navy); color: white; width: 45px; height: 45px; border-radius: 50%; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: transform 0.2s; }
        .send-btn:hover { transform: scale(1.05); }

        @media (max-width: 768px) {
            .chat-container { flex-direction: column; }
            .contacts-sidebar { width: 100%; height: 200px; flex: none; }
            .chat-area { flex: 1; }
        }
    </style>
</head>
<body class="theme-<?= $role ?>">

<div class="header">
    <a href="<?= $dashUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
    <h2><i class="fas fa-comments"></i> Messages</h2>
</div>

<div class="chat-container">
    <!-- Contacts Sidebar -->
    <div class="contacts-sidebar">
        <div class="search-br">
            <input type="text" placeholder="Search faculty or peers...">
        </div>
        <div class="contact-list">
            <div class="contact <?= ($target=='Prof. Evans') ? 'active':'' ?>" onclick="window.location.href='messages.php?faculty=Prof.+Evans'">
                <div class="avatar" style="background:#10b981;">E</div>
                <div>
                    <h4>Prof. Evans</h4>
                    <span style="font-size:0.8rem; color:gray;">CS301 Faculty</span>
                </div>
            </div>
            <div class="contact <?= ($target=='Dr. Chen' || $target=='') ? 'active':'' ?>" onclick="window.location.href='messages.php?faculty=Dr.+Chen'">
                <div class="avatar" style="background:#3b82f6;">C</div>
                <div>
                    <h4>Dr. Chen</h4>
                    <span style="font-size:0.8rem; color:gray;">MA201 Faculty</span>
                </div>
            </div>
            <div class="contact">
                <div class="avatar" style="background:#f59e0b;">G</div>
                <div>
                    <h4>Study Group A</h4>
                    <span style="font-size:0.8rem; color:gray;">3 members</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
        <div class="chat-header">
            <div class="avatar" style="background:var(--navy);"><?= strtoupper(substr($target, 0, 1)) ?></div>
            <div>
                <?= $target ?>
                <span style="display:block; font-size:0.8rem; color:gray; font-weight:normal;">Online</span>
            </div>
        </div>
        <div class="messages" id="msgArea">
            <div class="msg received">
                Hello! Please let me know if you have any questions regarding the upcoming midterm syllabus.
                <span class="msg-time">10:00 AM</span>
            </div>
            <div class="msg sent">
                Hi <?= $target ?>, thanks for the update. Will chapter 4 be included in the test?
                <span class="msg-time">10:05 AM</span>
            </div>
        </div>
        <div class="chat-input">
            <button class="btn-outline" style="border:none; background:transparent; font-size:1.2rem; color:gray;"><i class="fas fa-paperclip"></i></button>
            <input type="text" id="msgInput" placeholder="Type your message here..." onkeypress="if(event.key === 'Enter') sendMsg()">
            <button class="send-btn" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
function sendMsg() {
    const input = document.getElementById('msgInput');
    const text = input.value.trim();
    if (!text) return;
    
    const area = document.getElementById('msgArea');
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    const div = document.createElement('div');
    div.className = 'msg sent';
    div.innerHTML = text + `<span class="msg-time">${time}</span>`;
    
    area.appendChild(div);
    input.value = '';
    area.scrollTop = area.scrollHeight;

    // Simulate reply
    setTimeout(() => {
        const reply = document.createElement('div');
        reply.className = 'msg received';
        reply.innerHTML = "I am currently reviewing this and will get back to you shortly. <span class='msg-time'>" + new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + "</span>";
        area.appendChild(reply);
        area.scrollTop = area.scrollHeight;
    }, 1500);
}
</script>
</body>
</html>
