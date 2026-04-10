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
    <title>Grade Calculator · TimetableGen</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="premium.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg-light); padding: 2rem; min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; }
        
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .back-btn { background: white; border: none; padding: 0.8rem 1.5rem; border-radius: 30px; cursor: pointer; font-weight: 600; color: var(--navy); box-shadow: var(--shadow-sm); text-decoration: none; }
        
        .calc-card { background: white; padding: 2rem; border-radius: 2rem; box-shadow: var(--shadow-md); }
        .row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 1rem; align-items: center; margin-bottom: 1rem; }
        .row input { width: 100%; padding: 0.8rem 1rem; border-radius: 1rem; border: 1px solid var(--gray-300); font-size: 1rem; outline: none; }
        .row input:focus { border-color: var(--navy); }
        .row-header { font-weight: 600; color: var(--navy); padding-bottom: 0.5rem; border-bottom: 2px solid var(--gray-100); margin-bottom: 1rem; }
        
        .del-btn { background: #fee2e2; color: #dc2626; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: 0.2s; }
        .del-btn:hover { background: #fca5a5; }
        
        .add-btn { background: var(--gray-100); color: var(--navy); border: 2px dashed var(--gray-300); width: 100%; padding: 1rem; border-radius: 1rem; cursor: pointer; font-weight: 600; margin-top: 1rem; transition: 0.2s; }
        .add-btn:hover { background: #e2e8f0; border-color: var(--navy); }
        
        .result-panel { background: var(--navy); color: white; padding: 2rem; border-radius: 2rem; margin-top: 2rem; text-align: center; }
        .result-panel h2 { font-size: 3rem; margin-bottom: 0.5rem; color: var(--gold); }
        .result-panel p { font-size: 1.2rem; opacity: 0.9; }
        
        .warning { color: #dc2626; text-align: center; margin-top: 1rem; font-weight: 600; display: none; }

        @media(max-width: 768px) {
            body { padding: 1rem; }
            .calc-card { padding: 1rem; }
            .row { grid-template-columns: 1fr 1fr; }
            .row input:nth-child(1) { grid-column: 1 / -1; }
            .del-btn { grid-column: 1 / -1; width: auto; border-radius: 1rem; }
            .row-header { display: none; } /* Hide headers on mobile, rely on placeholders */
        }
    </style>
</head>
<body class="theme-<?= $role ?>">

<div class="container">
    <div class="header">
        <div style="display:flex; align-items:center; gap:1rem;">
            <a href="<?= $dashUrl ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1><i class="fas fa-calculator"></i> Grade Calculator</h1>
        </div>
        <div style="display:flex; gap:1rem;">
            <button class="back-btn" id="celebrateBtn" style="display:none; color:#10b981; border: 2px solid #10b981;" onclick="triggerConfetti()"><i class="fas fa-magic"></i> Celebrate!</button>
            <button class="back-btn" onclick="resetCalc()"><i class="fas fa-redo"></i> Reset</button>
        </div>
    </div>

    <div class="calc-card">
        <p style="margin-bottom: 1.5rem; color: var(--gray-600);">Enter your assignments, quizzes, and exams to calculate your weighted grade.</p>
        
        <div class="row row-header">
            <div>Assignment Name</div>
            <div>Your Score</div>
            <div>Max Score</div>
            <div>Weight (%)</div>
            <div style="width:40px;"></div>
        </div>

        <div id="rowsContainer">
            <!-- Initial row -->
        </div>
        
        <button class="add-btn" onclick="addRow()"><i class="fas fa-plus"></i> Add Assignment</button>
        <div class="warning" id="weightWarning">Total weight exceeds 100%!</div>
    </div>

    <div class="result-panel">
        <p>Current Expected Grade</p>
        <h2 id="finalGrade">--</h2>
        <p id="gradeLetter">Start entering scores...</p>
    </div>
</div>

<script>
const container = document.getElementById('rowsContainer');
let rowCount = 0;

function addRow(name='', score='', maxScore='100', weight='') {
    rowCount++;
    const id = `row_${rowCount}`;
    const div = document.createElement('div');
    div.className = 'row';
    div.id = id;
    
    div.innerHTML = `
        <input type="text" placeholder="e.g. Midterm" class="calc-input name" value="${name}">
        <input type="number" placeholder="Score" class="calc-input score" value="${score}" oninput="calculate()">
        <input type="number" placeholder="Max" class="calc-input max" value="${maxScore}" oninput="calculate()">
        <input type="number" placeholder="Weight %" class="calc-input weight" value="${weight}" oninput="calculate()">
        <button class="del-btn" onclick="removeRow('${id}')"><i class="fas fa-trash"></i></button>
    `;
    container.appendChild(div);
}

function removeRow(id) {
    if (document.querySelectorAll('.row').length <= 2) return; // Keep at least one row + header
    document.getElementById(id).remove();
    calculate();
}

function resetCalc() {
    container.innerHTML = '';
    addRow('Midterm', '', '100', '30');
    addRow('Assignments', '', '100', '20');
    addRow('Final Exam', '', '100', '50');
    calculate();
}

function calculate() {
    let totalWeight = 0;
    let earnedWeight = 0;
    
    const rows = document.querySelectorAll('#rowsContainer .row');
    rows.forEach(row => {
        const score = parseFloat(row.querySelector('.score').value);
        const max = parseFloat(row.querySelector('.max').value);
        const weight = parseFloat(row.querySelector('.weight').value);
        
        if (!isNaN(weight)) totalWeight += weight;
        
        if (!isNaN(score) && !isNaN(max) && !isNaN(weight) && max > 0) {
            earnedWeight += (score / max) * weight;
        }
    });
    
    const warning = document.getElementById('weightWarning');
    if (totalWeight > 100) warning.style.display = 'block';
    else warning.style.display = 'none';
    
    const gradeEl = document.getElementById('finalGrade');
    const letterEl = document.getElementById('gradeLetter');
    const celebrateBtn = document.getElementById('celebrateBtn');
    
    if (totalWeight === 0) {
        gradeEl.innerText = '--';
        letterEl.innerText = 'Start entering scores...';
        gradeEl.style.color = 'var(--gold)';
        letterEl.style.color = 'white';
        celebrateBtn.style.display = 'none';
        return;
    }
    
    const currentStanding = (earnedWeight / totalWeight) * 100;
    gradeEl.innerText = currentStanding.toFixed(2) + '%';
    
    let color = 'white';
    let msg = '';
    
    if (currentStanding > 75) {
        color = '#4ade80'; // Bright green for dark backgrounds
        msg = 'Great / Good 🌟';
    } else if (currentStanding >= 35) {
        color = '#fbbf24'; // Bright yellow
        msg = 'Pass 👍';
    } else {
        color = '#f87171'; // Bright red
        msg = 'Fail ⚠️';
    }
    
    gradeEl.style.color = color;
    letterEl.style.color = color;
    letterEl.innerText = msg;
    
    if (currentStanding > 90) {
        celebrateBtn.style.display = 'block';
    } else {
        celebrateBtn.style.display = 'none';
    }
}

function triggerConfetti() {
    confetti({
        particleCount: 150,
        spread: 80,
        origin: { y: 0.6 },
        colors: ['#f4c542', '#10b981', '#4f46e5', '#db2777', '#38bdf8']
    });
}

// Init
resetCalc();
</script>
</body>
</html>
