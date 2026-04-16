<?php
// seed_data.php - Populate courses, rooms, time_slots, and faculty_courses
// Run once to seed the database with sample data for the timetable generator
require_once 'db.php';

echo "<h2>🌱 Database Seeder for TimetableGen</h2><pre>\n";
mysqli_report(MYSQLI_REPORT_OFF); // Handle errors manually for INSERT IGNORE

// =============================================
// 1. SEED TIME SLOTS (Mon-Fri, 6 periods/day)
// =============================================
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = [
    ['09:00:00', '10:00:00'],
    ['10:00:00', '11:00:00'],
    ['11:00:00', '12:00:00'],
    // 12:00-13:00 is lunch break
    ['13:00:00', '14:00:00'],
    ['14:00:00', '15:00:00'],
    ['15:00:00', '16:00:00'],
];

$slotCount = 0;
$stmtSlot = $conn->prepare("INSERT IGNORE INTO time_slots (day_of_week, start_time, end_time) VALUES (?, ?, ?)");
foreach ($days as $day) {
    foreach ($periods as $p) {
        $stmtSlot->bind_param("sss", $day, $p[0], $p[1]);
        $stmtSlot->execute();
        if ($stmtSlot->affected_rows > 0) $slotCount++;
    }
}
echo "✅ Time Slots seeded: $slotCount new slots inserted (Mon-Fri, 6 periods/day)\n";

// =============================================
// 2. SEED ROOMS (Lecture Halls + Labs)
// =============================================
$rooms = [
    ['LH-101', 'Main',  1, 60, 'Lecture Hall', 'Projector, Whiteboard, AC'],
    ['LH-102', 'Main',  1, 60, 'Lecture Hall', 'Projector, Whiteboard'],
    ['LH-201', 'Main',  2, 80, 'Lecture Hall', 'Projector, Whiteboard, AC, Mic'],
    ['LH-202', 'Main',  2, 50, 'Lecture Hall', 'Projector, Whiteboard'],
    ['LH-301', 'Main',  3, 120,'Lecture Hall', 'Projector, Whiteboard, AC, Mic'],
    ['Lab-101','Science',1, 30, 'Lab',          'Computers, Projector'],
    ['Lab-102','Science',1, 30, 'Lab',          'Computers, Projector'],
    ['Lab-201','Science',2, 40, 'Lab',          'Computers, Projector, AC'],
    ['Sem-101','Main',   1, 25, 'Seminar',      'Projector, Whiteboard, AC'],
];

$roomCount = 0;
$stmtRoom = $conn->prepare("INSERT IGNORE INTO rooms (name, building, floor, capacity, type, facilities) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($rooms as $r) {
    $stmtRoom->bind_param("ssiiss", $r[0], $r[1], $r[2], $r[3], $r[4], $r[5]);
    $stmtRoom->execute();
    if ($stmtRoom->affected_rows > 0) $roomCount++;
}
echo "✅ Rooms seeded: $roomCount new rooms inserted\n";

// =============================================
// 3. SEED COURSES (GTU-aligned engineering)
// =============================================
$courses = [
    ['CE301',  'Data Structures',              4, 'Theory', 'CE'],
    ['CE302',  'Database Management Systems',   4, 'Theory', 'CE'],
    ['CE303',  'Operating Systems',             4, 'Theory', 'CE'],
    ['CE304',  'Computer Networks',             3, 'Theory', 'CE'],
    ['CE305',  'Web Technology',                3, 'Theory', 'CE'],
    ['CE306',  'Software Engineering',          3, 'Theory', 'CE'],
    ['IT301',  'Object Oriented Programming',   4, 'Theory', 'IT'],
    ['IT302',  'Data Mining',                   3, 'Theory', 'IT'],
    ['ME301',  'Thermodynamics',               4, 'Theory', 'ME'],
    ['ME302',  'Fluid Mechanics',               3, 'Theory', 'ME'],
    ['EE301',  'Power Systems',                 4, 'Theory', 'EE'],
    ['EE302',  'Control Engineering',           3, 'Theory', 'EE'],
    ['MA301',  'Engineering Mathematics III',   4, 'Theory', 'CE'],
    ['PH301',  'Applied Physics',               3, 'Theory', 'CE'],
];

$courseCount = 0;
$stmtCourse = $conn->prepare("INSERT IGNORE INTO courses (course_code, course_name, credits, type, department) VALUES (?, ?, ?, ?, ?)");
foreach ($courses as $c) {
    $stmtCourse->bind_param("ssiss", $c[0], $c[1], $c[2], $c[3], $c[4]);
    $stmtCourse->execute();
    if ($stmtCourse->affected_rows > 0) $courseCount++;
}
echo "✅ Courses seeded: $courseCount new courses inserted\n";

// =============================================
// 4. SEED FACULTY USERS (if none exist)
// =============================================
$facCheck = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='faculty'");
$facExists = $facCheck->fetch_assoc()['c'];

$facultySeeded = 0;
if ($facExists == 0) {
    $faculties = [
        ['Dr. Amit Patel',     'amit.patel@college.edu',    'CE', 'Data Structures, Algorithms'],
        ['Dr. Priya Sharma',   'priya.sharma@college.edu',  'CE', 'Database Systems, SQL'],
        ['Prof. Rajesh Kumar',  'rajesh.kumar@college.edu', 'CE', 'Operating Systems, Linux'],
        ['Dr. Sneha Desai',    'sneha.desai@college.edu',   'IT', 'Web Development, OOP'],
        ['Prof. Vikram Singh', 'vikram.singh@college.edu',  'ME', 'Thermodynamics, Heat Transfer'],
        ['Dr. Meera Iyer',     'meera.iyer@college.edu',    'EE', 'Power Systems, Control'],
    ];
    
    $hash = password_hash("faculty123", PASSWORD_DEFAULT);
    $stmtFac = $conn->prepare("INSERT IGNORE INTO users (name, email, password_hash, role, department, specialization) VALUES (?, ?, ?, 'faculty', ?, ?)");
    foreach ($faculties as $f) {
        $stmtFac->bind_param("sssss", $f[0], $f[1], $hash, $f[2], $f[3]);
        $stmtFac->execute();
        if ($stmtFac->affected_rows > 0) $facultySeeded++;
    }
    echo "✅ Faculty seeded: $facultySeeded new faculty accounts created (password: faculty123)\n";
} else {
    echo "⏭️ Faculty: $facExists faculty users already exist, skipping\n";
}

// =============================================
// 5. LINK FACULTY TO COURSES (faculty_courses)
// =============================================
// Get all faculty and courses, then assign round-robin
$facResult = $conn->query("SELECT id, name FROM users WHERE role='faculty' ORDER BY id");
$allFaculty = [];
while ($r = $facResult->fetch_assoc()) $allFaculty[] = $r;

$crsResult = $conn->query("SELECT id, course_code FROM courses WHERE status='active' ORDER BY id");
$allCourses = [];
while ($r = $crsResult->fetch_assoc()) $allCourses[] = $r;

$linkCount = 0;
if (count($allFaculty) > 0 && count($allCourses) > 0) {
    $stmtLink = $conn->prepare("INSERT IGNORE INTO faculty_courses (faculty_id, course_id) VALUES (?, ?)");
    foreach ($allCourses as $idx => $course) {
        $facIdx = $idx % count($allFaculty);
        $fid = $allFaculty[$facIdx]['id'];
        $cid = $course['id'];
        $stmtLink->bind_param("ii", $fid, $cid);
        $stmtLink->execute();
        if ($stmtLink->affected_rows > 0) $linkCount++;
    }
    echo "✅ Faculty-Course links: $linkCount assignments created\n";
} else {
    echo "⚠️ Cannot link faculty to courses: need at least 1 faculty and 1 course\n";
}

// =============================================
// SUMMARY
// =============================================
echo "\n--- FINAL ROW COUNTS ---\n";
$tables = ['time_slots', 'rooms', 'courses', 'users', 'faculty_courses'];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as c FROM $table");
    $count = $result->fetch_assoc()['c'];
    echo "  $table: $count rows\n";
}

echo "\n🎉 Seeding complete! The timetable generator should now work.\n";
echo "Navigate to generator.php to try it out.\n";
echo "</pre>";

// Restore strict error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>
