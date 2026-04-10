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
    <title>Analytics & Reports · TimetableGen</title>
    <!-- Font Awesome 6 & Chart.js 3 & simple heatmap library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --shadow-md: 0 12px 30px -8px rgba(0,0,0,0.1);
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
            font-size: 2.8rem;
            color: var(--navy);
        }
        .header-title p {
            color: var(--gray-600);
        }
        .date-export {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .date-picker {
            background: white;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            border: 1px solid var(--gray-300);
        }
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: var(--shadow-md);
        }

        /* tab bar */
        .tabs {
            display: flex;
            gap: 0.8rem;
            background: white;
            padding: 0.6rem;
            border-radius: 60px;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }
        .tab {
            padding: 0.7rem 1.8rem;
            border: none;
            background: transparent;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .tab.active {
            background: var(--navy);
            color: white;
        }

        /* report sections (hidden by default) */
        .report-section {
            display: none;
        }
        .report-section.active {
            display: block;
        }

        /* cards grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px,1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .card {
            background: white;
            border-radius: 2rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
        }
        .card-header {
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 1rem;
        }

        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
        }

        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 4px;
        }
        .heat-cell {
            background: #e2e8f0;
            height: 30px;
            border-radius: 6px;
        }

        .data-table {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-300);
        }

        .badge {
            background: var(--navy-light);
            color: white;
            padding: 0.2rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
        }

        .custom-builder {
            background: white;
            border-radius: 2.5rem;
            padding: 2rem;
            margin: 2rem 0;
        }

        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
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
            <h1>📊 Analytics & Reports</h1>
            <p>Data-driven insights for academic planning</p>
        </div>
        <div class="date-export">
            <input type="text" class="date-picker" value="1 Sep 2024 – 30 Sep 2024">
            <button class="btn-primary" id="exportAll"><i class="fas fa-download"></i> Export All</button>
        </div>
    </div>

    <!-- tabs -->
    <div class="tabs">
        <button class="tab active" data-tab="utilization">📈 Utilization</button>
        <button class="tab" data-tab="faculty">👨‍🏫 Faculty</button>
        <button class="tab" data-tab="student">👩‍🎓 Student</button>
        <button class="tab" data-tab="academic">📚 Academic</button>
        <button class="tab" data-tab="financial">💰 Financial</button>
    </div>

    <!-- UTILIZATION REPORT SECTION -->
    <div id="utilization" class="report-section active">
        <div class="stats-grid">
            <div class="card"><div class="card-header">🏛️ Room Utilization</div><div class="chart-container"><canvas id="roomChart"></canvas></div> <p>Top used: LH-101 (92%)</p></div>
            <div class="card"><div class="card-header">👨‍🏫 Faculty Workload</div><div class="chart-container"><canvas id="facultyLoadChart"></canvas></div></div>
            <div class="card"><div class="card-header">📚 Course Demand</div><canvas id="courseDemandChart"></canvas></div>
        </div>
        <div class="stats-grid">
            <div class="card"><div class="card-header">🔥 Peak Hours Heatmap</div><div class="heatmap-grid">
                <div class="heat-cell" style="background:#0a3b5b;"></div><div class="heat-cell" style="background:#1e4f6e;"></div><div class="heat-cell" style="background:#f4c542;"></div><div class="heat-cell" style="background:#cbd5e1;"></div>
            </div> 9-11 AM peak</div>
            <div class="card"><div class="card-header">⚠️ Underutilized Rooms</div> LH-104 (34%), Lab-202 (28%)</div>
        </div>
    </div>

    <!-- FACULTY REPORT SECTION -->
    <div id="faculty" class="report-section">
        <div class="stats-grid">
            <div class="card"><div class="card-header">📊 Teaching Load Distribution</div><canvas id="facultyDistChart"></canvas></div>
            <div class="card"><div class="card-header">🏖️ Leave Frequency</div><p>Avg 2.3 days/faculty this month</p><canvas id="leaveChart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header">👨‍🏫 Faculty Feedback Summary</div>
            <p>Avg rating: 4.5/5 · Course completion 94%</p>
        </div>
    </div>

    <!-- STUDENT REPORT SECTION -->
    <div id="student" class="report-section">
        <div class="stats-grid">
            <div class="card"><div class="card-header">📈 Enrollment Trends</div><canvas id="enrollChart"></canvas></div>
            <div class="card"><div class="card-header">📉 Attendance Patterns</div><canvas id="attendChart"></canvas></div>
        </div>
        <div class="card"><div class="card-header">📊 Performance Analytics</div><p>Average CGPA: 7.8 · Dropout rate 2.1%</p></div>
    </div>

    <!-- ACADEMIC REPORT SECTION -->
    <div id="academic" class="report-section">
        <div class="card"><div class="card-header">⚡ Timetable Efficiency</div><canvas id="efficiencyChart"></canvas></div>
        <div class="card"><div class="card-header">🔄 Conflict Resolution Rate: 96%</div></div>
    </div>

    <!-- FINANCIAL REPORT SECTION (placeholder) -->
    <div id="financial" class="report-section">
        <div class="card"><div class="card-header">💰 Budget vs Actual</div><canvas id="financeChart"></canvas></div>
    </div>

    <!-- Custom Report Builder -->
    <div class="custom-builder">
        <h3><i class="fas fa-sliders-h"></i> Custom Report Builder</h3>
        <div style="display:flex; gap:1rem; flex-wrap:wrap;">
            <select><option>Metric: Room utilization</option></select>
            <select><option>Dimension: Department</option></select>
            <select><option>Chart: Bar</option></select>
            <button class="btn-primary" id="generateCustom">Generate</button>
        </div>
    </div>

    <!-- Data Table (sample) -->
    <div class="card">
        <div class="card-header">📋 Detailed Data (sortable)</div>
        <div class="data-table">
            <table>
                <thead><tr><th>Department</th><th>Utilization</th><th>Faculty</th><th>Students</th></tr></thead>
                <tbody>
                    <tr><td>CSE</td><td>88%</td><td>32</td><td>780</td></tr>
                    <tr><td>ECE</td><td>76%</td><td>28</td><td>650</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Predictive Analytics & Scheduled Reports -->
    <div style="display:flex; gap:2rem; margin:2rem 0;">
        <div class="card" style="flex:1;"><i class="fas fa-chart-line"></i> Next sem forecast: Room demand +8%</div>
        <div class="card" style="flex:1;"><i class="far fa-clock"></i> Scheduled reports: Weekly to deans</div>
    </div>
</div>

<!-- toast -->
<div id="toast" class="toast">📊 Report exported</div>

<script>
    (function() {
        // Tab switching
        const tabs = document.querySelectorAll('.tab');
        const sections = document.querySelectorAll('.report-section');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                sections.forEach(s => s.classList.remove('active'));
                document.getElementById(target).classList.add('active');
            });
        });

        // Chart initializations (simplified, all using dummy data)
        new Chart(document.getElementById('roomChart'), { type:'bar', data: { labels:['LH-101','LH-102','Lab-201'], datasets:[{ data:[92,78,56], backgroundColor:'#0a3b5b' }] } });
        new Chart(document.getElementById('facultyLoadChart'), { type:'line', data: { labels:['Mon','Tue','Wed','Thu','Fri'], datasets:[{ data:[8,7,9,6,5] }] } });
        new Chart(document.getElementById('courseDemandChart'), { type:'doughnut', data: { labels:['CS301','CS311','MA201'], datasets:[{ data:[120,95,80] }] } });
        new Chart(document.getElementById('facultyDistChart'), { type:'bar', data: { labels:['CSE','ECE','MECH'], datasets:[{ data:[12,10,8] }] } });
        new Chart(document.getElementById('leaveChart'), { type:'line', data: { labels:['Jan','Feb','Mar'], datasets:[{ data:[2,3,2] }] } });
        new Chart(document.getElementById('enrollChart'), { type:'line', data: { labels:['2019','2020','2021'], datasets:[{ data:[1200,1300,1450] }] } });
        new Chart(document.getElementById('attendChart'), { type:'bar', data: { labels:['CSE','ECE'], datasets:[{ data:[87,82] }] } });
        new Chart(document.getElementById('efficiencyChart'), { type:'radar', data: { labels:['Room','Faculty','Student'], datasets:[{ data:[95,88,92] }] } });
        new Chart(document.getElementById('financeChart'), { type:'bar', data: { labels:['Budget','Actual'], datasets:[{ data:[100000,98000] }] } });

        // Export all button
        document.getElementById('exportAll').addEventListener('click', () => {
            const toast = document.getElementById('toast');
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 2000);
        });

        // Custom generate
        document.getElementById('generateCustom').addEventListener('click', () => {
            alert('Custom report generated (simulated)');
        });

        // Drill-down simulation (click on any chart)
        document.querySelectorAll('canvas').forEach(c => {
            c.addEventListener('click', () => alert('Drill down to details (simulated)'));
        });
    })();
</script>
</body>
</html>

