<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Handle Add/Delete requests via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'add') {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $building = mysqli_real_escape_string($conn, $_POST['building']);
        $floor = (int)$_POST['floor'];
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        $capacity = (int)$_POST['capacity'];
        $facilities = mysqli_real_escape_string($conn, $_POST['facilities']); // comma-separated
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if(empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Room name required']);
            exit();
        }

        $sql = "INSERT INTO rooms (name, building, floor, capacity, type, facilities, status) VALUES ('$name', '$building', $floor, $capacity, '$type', '$facilities', '$status')";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Room added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM rooms WHERE id=$id");
        echo json_encode(['success' => true]);
        exit();
    }
}

// Fetch DB Data
$roomsArray = [];
$res = $conn->query("SELECT * FROM rooms ORDER BY building ASC, name ASC");
$lec = 0; $lab = 0; $sem = 0; $seats = 0;
if ($res) {
    while($row = $res->fetch_assoc()) {
        $facArr = explode(',', $row['facilities']);
        if(empty($facArr[0])) $facArr = [];
        $roomsArray[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'building' => $row['building'],
            'floor' => $row['floor'],
            'type' => $row['type'],
            'capacity' => $row['capacity'],
            'facilities' => $facArr,
            'status' => $row['status'],
            'util' => rand(30, 95) // simulate utilization %
        ];
        
        if($row['type'] == 'Lecture Hall') $lec++;
        else if($row['type'] == 'Lab') $lab++;
        else if($row['type'] == 'Seminar') $sem++;
        $seats += $row['capacity'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimetableGen · Room Management</title>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- FullCalendar for schedule views -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1600px;
            margin: 0 auto;
        }

        /* header */
        .header-section {
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
        .btn-primary {
            background: var(--navy);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: var(--shadow-md);
        }
        .btn-primary:hover { background: var(--navy-light); transform: scale(1.02); }

        /* stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
        }
        .stat-icon { font-size: 2.5rem; }
        .stat-info h3 { font-weight: 400; color: var(--gray-600); }
        .stat-info .value { font-size: 2.2rem; font-weight: 700; color: var(--navy); }

        /* filter bar */
        .filter-bar {
            background: white;
            border-radius: 3rem;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
            box-shadow: var(--shadow-md);
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 140px;
        }
        .filter-group label {
            font-weight: 600;
            color: var(--navy);
        }
        .filter-group input, .filter-group select {
            padding: 0.6rem 1rem;
            border-radius: 40px;
            border: 1px solid var(--gray-300);
            background: var(--gray-100);
        }
        .facility-check {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        /* room grid */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.8rem;
            margin: 2rem 0;
        }
        .room-card {
            background: white;
            border-radius: 2.2rem;
            padding: 1.6rem;
            box-shadow: var(--shadow-md);
            border-left: 8px solid var(--gold);
        }
        .room-card.maintenance { border-left-color: var(--danger); }
        .room-card.inuse { border-left-color: var(--warning); }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .room-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--navy);
        }
        .type-icon { font-size: 2rem; }
        .building {
            color: var(--gray-600);
        }
        .capacity {
            font-weight: 600;
            margin: 0.5rem 0;
        }
        .facilities {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
            margin: 0.8rem 0;
        }
        .facility-badge {
            background: var(--gray-100);
            padding: 0.3rem 0.8rem;
            border-radius: 40px;
            font-size: 0.9rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 40px;
            font-weight: 600;
        }
        .available { background: #d1fae5; color: #065f46; }
        .inuse { background: #fee2e2; color: #991b1b; }
        .maintenance { background: #fed7aa; color: #92400e; }

        .util-bar {
            background: #e2e8f0;
            height: 8px;
            border-radius: 20px;
            margin: 1rem 0;
        }
        .util-fill {
            height: 8px;
            background: var(--navy);
            border-radius: 20px;
            width: 60%;
        }
        .action-icons {
            display: flex;
            gap: 1.2rem;
            justify-content: flex-end;
            color: var(--gray-600);
        }
        .action-icons i { cursor: pointer; }
        .action-icons i:hover { color: var(--navy); }

        /* modal */
        .modal {
            display: none;
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            backdrop-filter: blur(3px);
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 3rem;
            padding: 2rem;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        .form-group {
            flex: 1 1 200px;
        }
        .form-group label {
            font-weight: 600;
            display: block;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.7rem 1rem;
            border-radius: 50px;
            border: 1px solid var(--gray-300);
        }

        .toast {
            position: fixed; bottom: 30px; right: 30px;
            background: var(--navy);
            color: white;
            padding: 1rem 2rem;
            border-radius: 60px;
            display: none;
            z-index: 3000;
        }

        /* calendar for room schedule */
        #scheduleCalendar {
            max-width: 100%;
            height: 400px;
            background: white;
            border-radius: 2rem;
            padding: 1rem;
        }

        .util-dashboard {
            display: flex;
            gap: 1.5rem;
            margin: 2rem 0;
            flex-wrap: wrap;
        }
        .util-card {
            background: white;
            border-radius: 2rem;
            padding: 1.5rem;
            flex: 1 1 200px;
        }
        .floor-plan {
            background: #1e2b3c;
            color: white;
            border-radius: 2rem;
            padding: 2rem;
            text-align: center;
        }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .header-section { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .action-group { flex-wrap: wrap; width: 100%; }
            .action-group button { flex: 1; min-width: 120px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .filter-bar { flex-wrap: wrap; align-items: flex-start; gap: 0.5rem; }
            .filter-group { flex: 1; min-width: 100px; }
            .filter-group select, .filter-group input { width: 100%; }
            .modal-content { width: 95%; padding: 1.5rem; border-radius: 1.5rem; }
            .form-row { flex-direction: column; gap: 0.5rem; }
            .util-dashboard { flex-direction: column; }
            .floor-plan { max-width: 100vw; overflow-x: scroll; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header-section">
        <div class="header-title">
            <h1>🚪 Room Management</h1>
            <p>Manage lecture halls, labs, and seminar rooms</p>
        </div>
        <div class="action-group" style="display: flex; gap: 0.8rem;">
            <button class="btn-outline" onclick="window.location.href='admin.php'"><i class="fas fa-arrow-left"></i> Dashboard</button>
            <button class="btn-primary" id="addRoomBtn"><i class="fas fa-plus"></i> Add New Room</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><span class="stat-icon">🏛️</span><div class="stat-info"><span class="value"><?= count($roomsArray) ?></span><h3>Total Rooms</h3></div></div>
        <div class="stat-card"><span class="stat-icon">📖</span><div class="stat-info"><span class="value"><?= $lec ?></span><h3>Lecture Halls</h3></div></div>
        <div class="stat-card"><span class="stat-icon">🔬</span><div class="stat-info"><span class="value"><?= $lab ?></span><h3>Labs</h3></div></div>
        <div class="stat-card"><span class="stat-icon">🎤</span><div class="stat-info"><span class="value"><?= $sem ?></span><h3>Seminar Halls</h3></div></div>
        <div class="stat-card"><span class="stat-icon">👥</span><div class="stat-info"><span class="value"><?= number_format($seats) ?></span><h3>Total Seats</h3></div></div>
    </div>

    <!-- filter bar -->
    <div class="filter-bar">
        <div class="filter-group"><label>Building</label><select id="buildingFilter"><option>All</option><option>Main</option><option>Engineering</option><option>Science</option></select></div>
        <div class="filter-group"><label>Room type</label><select id="typeFilter"><option>All</option><option>Lecture Hall</option><option>Lab</option><option>Seminar</option></select></div>
        <div class="filter-group"><label>Capacity ≥</label><input type="number" id="capacityFilter" value="0"></div>
        <div class="filter-group"><label>Floor</label><input type="number" id="floorFilter" placeholder="any"></div>
        <div class="facility-check"><label><input type="checkbox" id="projFilter"> 📽️ Projector</label></div>
        <div class="facility-check"><label><input type="checkbox" id="acFilter"> ❄️ AC</label></div>
        <div class="filter-group"><label>Status</label><select id="statusFilter"><option>All</option><option>Available</option><option>In Use</option><option>Maintenance</option></select></div>
    </div>

    <!-- room grid -->
    <div id="roomsGrid" class="rooms-grid"></div>

    <!-- utilization dashboard & floor plan -->
    <div class="util-dashboard">
        <div class="util-card"><h3>📊 Utilization</h3><canvas id="utilChart" height="100"></canvas></div>
        <div class="util-card"><h3>⏲️ Peak hours</h3><p>Mon 10-12, Wed 2-4</p><div style="height:60px; background:#e2e8f0;"></div></div>
        <div class="util-card"><h3>🔍 Free slots finder</h3><p>Next free LH-101: 2:30 PM</p></div>
    </div>

    <div class="floor-plan">
        <i class="fas fa-building" style="font-size:4rem;"></i>  Interactive floor plan (clickable)
        <div>LH-101 (available)  |  LH-102 (in use)  |  Lab-201 (maintenance)</div>
    </div>

    <!-- maintenance scheduling & conflict checker -->
    <div style="display:flex; gap:2rem; margin:2rem 0; flex-wrap:wrap;">
        <div style="background:white; border-radius:2rem; padding:1.5rem; flex:1;">
            <h3>🔧 Maintenance scheduling</h3>
            <p>Block room LH-105 on Mar 25 (recurring)</p>
            <button class="btn-primary">Schedule maintenance</button>
        </div>
        <div style="background:white; border-radius:2rem; padding:1.5rem; flex:1;">
            <h3>⚠️ Conflict checker</h3>
            <p>Double bookings: none | Capacity vs enrollment: LH-201 (60 seats, 55 enrolled) ✅</p>
        </div>
    </div>
</div>

<!-- ADD/EDIT ROOM MODAL -->
<div id="roomModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">➕ Add Room</h2>
        <div class="form-row">
            <div class="form-group"><label>Room number</label><input type="text" id="roomNum" placeholder="LH-101" required></div>
            <div class="form-group"><label>Building</label><select id="roomBldg"><option value="Main">Main</option><option value="Engineering">Engineering</option><option value="Science">Science</option></select></div>
            <div class="form-group"><label>Floor</label><input type="number" id="roomFloor" value="1"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Type</label><select id="roomType"><option value="Lecture Hall">Lecture Hall</option><option value="Lab">Lab</option><option value="Seminar">Seminar</option></select></div>
            <div class="form-group"><label>Capacity</label><input type="number" id="roomCap" value="60"></div>
        </div>
        <div class="form-group"><label>Facilities (check all that apply)</label>
            <div>
                <label><input type="checkbox" class="fac-check" value="projector" checked> 📽️ Projector</label> 
                <label><input type="checkbox" class="fac-check" value="ac" checked> ❄️ AC</label> 
                <label><input type="checkbox" class="fac-check" value="wheelchair"> ♿ Wheelchair</label> 
                <label><input type="checkbox" class="fac-check" value="wifi"> 📶 WiFi</label>
            </div>
        </div>
        <div class="form-group"><label>Status</label><select id="roomStatus"><option value="Available">Available</option><option value="Maintenance">Maintenance</option><option value="Closed">Closed</option></select></div>
        <div class="form-group"><label>Notes</label><textarea rows="2">Smart board available</textarea></div>
        <div class="form-group"><label>Floor plan upload (simulated)</label><input type="file"></div>
        <div style="margin-top:2rem;">
            <button class="btn-primary" id="saveRoom">Save Room</button>
            <button class="btn-outline" id="closeModal">Cancel</button>
        </div>
    </div>
</div>

<!-- SCHEDULE VIEW MODAL (room calendar) -->
<div id="scheduleModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <h2>📅 Room Schedule: <span id="scheduleRoomName">LH-101</span></h2>
        <div id="scheduleCalendar"></div>
        <p><i class="fas fa-wrench"></i> <button class="btn-outline">Block for maintenance</button></p>
        <button class="btn-primary" id="closeSchedule">Close</button>
    </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast">✅ Done</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    (function() {
        // dynamic rooms from PHP database query
        let rooms = <?= json_encode($roomsArray) ?>;

        function renderRooms() {
            const building = document.getElementById('buildingFilter').value;
            const type = document.getElementById('typeFilter').value;
            const capMin = parseInt(document.getElementById('capacityFilter').value) || 0;
            const floor = document.getElementById('floorFilter').value;
            const proj = document.getElementById('projFilter').checked;
            const ac = document.getElementById('acFilter').checked;
            const statusF = document.getElementById('statusFilter').value;

            const filtered = rooms.filter(r => 
                (building === 'All' || r.building === building) &&
                (type === 'All' || r.type === type) &&
                (r.capacity >= capMin) &&
                (floor === '' || r.floor == floor) &&
                (statusF === 'All' || r.status === statusF) &&
                (!proj || r.facilities.includes('projector')) &&
                (!ac || r.facilities.includes('ac'))
            );

            let html = '';
            filtered.forEach(r => {
                let statusClass = r.status === 'Available' ? 'available' : (r.status === 'In Use' ? 'inuse' : 'maintenance');
                let cardClass = r.status === 'Maintenance' ? 'maintenance' : (r.status === 'In Use' ? 'inuse' : '');
                let facIcons = '';
                if (r.facilities.includes('projector')) facIcons += '📽️ ';
                if (r.facilities.includes('ac')) facIcons += '❄️ ';
                if (r.facilities.includes('wheelchair')) facIcons += '♿ ';
                if (r.facilities.includes('wifi')) facIcons += '📶 ';

                html += `
                <div class="room-card ${cardClass}" data-id="${r.id}">
                    <div class="room-header">
                        <span class="room-name">${r.name}</span>
                        <span class="type-icon">${r.type === 'Lab' ? '🔬' : (r.type==='Seminar'?'🎤':'🏛️')}</span>
                    </div>
                    <div class="building">${r.building}, Floor ${r.floor}</div>
                    <div class="capacity"><i class="fas fa-users"></i> ${r.capacity} seats</div>
                    <div class="facilities">${facIcons}</div>
                    <div><span class="status-badge ${statusClass}">${r.status}</span></div>
                    <div class="util-bar"><div class="util-fill" style="width:${r.util}%;"></div></div>
                    <div class="action-icons">
                        <i class="fas fa-eye" title="View" onclick="viewRoom('${r.id}')"></i>
                        <i class="fas fa-edit" title="Edit" onclick="editRoom('${r.id}')"></i>
                        <i class="fas fa-trash" title="Delete" onclick="deleteRoom('${r.id}')"></i>
                        <i class="fas fa-calendar-alt" title="Schedule" onclick="showSchedule('${r.name}')"></i>
                    </div>
                </div>
                `;
            });
            document.getElementById('roomsGrid').innerHTML = html;
        }

        // global functions
        window.viewRoom = (id) => { alert('View details for room '+id); };
        window.editRoom = (id) => {
            document.getElementById('modalTitle').innerText = '✏️ Edit Room';
            document.getElementById('roomModal').classList.add('active');
        };
        closeModal.addEventListener('click', () => roomModal.classList.remove('active'));
        saveRoom.addEventListener('click', () => {
            let selectedFacs = [];
            document.querySelectorAll('.fac-check:checked').forEach(cb => selectedFacs.push(cb.value));

            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('name', document.getElementById('roomNum').value);
            fd.append('building', document.getElementById('roomBldg').value);
            fd.append('floor', document.getElementById('roomFloor').value);
            fd.append('type', document.getElementById('roomType').value);
            fd.append('capacity', document.getElementById('roomCap').value);
            fd.append('status', document.getElementById('roomStatus').value);
            fd.append('facilities', selectedFacs.join(','));

            fetch('roomM.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast(data.message);
                    roomModal.classList.remove('active');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });
        window.deleteRoom = (id) => {
            if(confirm('Delete room?')) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('id', id);
                fetch('roomM.php', { method: 'POST', body: fd }).then(() => {
                    showToast('Room deleted');
                    setTimeout(() => location.reload(), 1000);
                });
            }
        };
        window.showSchedule = (roomName) => {
            document.getElementById('scheduleRoomName').innerText = roomName;
            scheduleModal.classList.add('active');
            // render calendar (simplified)
            setTimeout(() => {
                let calEl = document.getElementById('scheduleCalendar');
                if (calEl.innerHTML === '') {
                    let calendar = new FullCalendar.Calendar(calEl, {
                        initialView: 'timeGridWeek',
                        headerToolbar: {start:'title', center:'', end:'today prev,next'},
                        height: 350,
                        events: [
                            { title: 'CS101', start: '2025-03-20T09:00:00', end: '2025-03-20T10:30:00' },
                            { title: 'MA201', start: '2025-03-21T14:00:00', end: '2025-03-21T15:30:00' }
                        ]
                    });
                    calendar.render();
                }
            }, 100);
        };

        // modal controls
        const roomModal = document.getElementById('roomModal');
        const addBtn = document.getElementById('addRoomBtn');
        const closeModal = document.getElementById('closeModal');
        const saveRoom = document.getElementById('saveRoom');
        const scheduleModal = document.getElementById('scheduleModal');
        const closeSchedule = document.getElementById('closeSchedule');
        const toast = document.getElementById('toast');

        addBtn.addEventListener('click', () => {
            document.getElementById('modalTitle').innerText = '➕ Add Room';
            roomModal.classList.add('active');
        });
        closeSchedule.addEventListener('click', () => scheduleModal.classList.remove('active'));

        function showToast(msg) {
            toast.innerHTML = msg;
            toast.style.display = 'flex';
            setTimeout(() => toast.style.display = 'none', 2000);
        }

        // filter events
        document.querySelectorAll('#buildingFilter, #typeFilter, #capacityFilter, #floorFilter, #projFilter, #acFilter, #statusFilter').forEach(el => {
            el.addEventListener('input', renderRooms);
            el.addEventListener('change', renderRooms);
        });

        // utilization chart
        const ctx = document.getElementById('utilChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: { labels: ['LH-101','LH-102','Lab-201','Seminar-A'], datasets:[{ data:[75,90,20,45], backgroundColor:['#0a3b5b','#1e4f6e','#f4c542','#cbd5e1'] }] }
        });

        // initial render
        renderRooms();
    })();
</script>
<style>.btn-outline { border:1.5px solid var(--navy); background:white; padding:0.6rem 1.5rem; border-radius:50px; cursor:pointer; }</style>
</body>
</html>

