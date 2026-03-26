<?php
// setup_db.php - Initialization script for database and tables
die('Setup is disabled for security.');
require_once 'db.php';

try {

    // 1. Users Table
    $sql_users = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin', 'faculty', 'student') NOT NULL,
        phone VARCHAR(20) NULL,
        program_level VARCHAR(50) NULL,
        department VARCHAR(50) NULL,
        current_semester INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql_users);
    echo "Users table created.\n";

    // 2. Courses Table
    $sql_courses = "
    CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_code VARCHAR(50) UNIQUE NOT NULL,
        course_name VARCHAR(150) NOT NULL,
        credits INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        status ENUM('active','inactive') DEFAULT 'active'
    )";
    $conn->query($sql_courses);
    echo "Courses table created.\n";

    // 3. Departments Table
    $sql_depts = "
    CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(150) NOT NULL,
        hod VARCHAR(100) DEFAULT 'Not Assigned',
        status ENUM('active','inactive') DEFAULT 'active',
        est_year INT DEFAULT 2000,
        email VARCHAR(100) DEFAULT '',
        phone VARCHAR(50) DEFAULT '',
        description TEXT
    )";
    $conn->query($sql_depts);
    echo "Departments table created.\n";

    // Insert GTU Departments
    $depts = [
        ['CE', 'Computer Engineering'],
        ['IT', 'Information Technology'],
        ['ME', 'Mechanical Engineering'],
        ['EE', 'Electrical Engineering'],
        ['CL', 'Civil Engineering'],
        ['EC', 'Electronics & Comm.']
    ];
    $stmt = $conn->prepare("INSERT IGNORE INTO departments (code, name) VALUES (?, ?)");
    foreach ($depts as $d) {
        $stmt->bind_param("ss", $d[0], $d[1]);
        $stmt->execute();
    }
    echo "Default departments seeded.\n";

    // 4. Timetables Master
    $sql_timetable = "
    CREATE TABLE IF NOT EXISTS timetables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department_id INT NOT NULL,
        program_level VARCHAR(50) NOT NULL,
        semester INT NOT NULL,
        status VARCHAR(50) DEFAULT 'Draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    )";
    $conn->query($sql_timetable);
    echo "Timetables table created.\n";

    // 5. Rooms Table
    $sql_rooms = "
    CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        building VARCHAR(100) DEFAULT 'Main',
        floor INT DEFAULT 1,
        capacity INT NOT NULL,
        type ENUM('Lecture Hall', 'Lab', 'Seminar') DEFAULT 'Lecture Hall',
        facilities VARCHAR(255) DEFAULT '',
        status ENUM('Available', 'In Use', 'Maintenance') DEFAULT 'Available'
    )";
    $conn->query($sql_rooms);
    echo "Rooms table created.\n";

    // 6. Faculty Courses (Allocations)
    $sql_fac_crs = "
    CREATE TABLE IF NOT EXISTS faculty_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        faculty_id INT NOT NULL,
        course_id INT NOT NULL,
        FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE(faculty_id, course_id)
    )";
    $conn->query($sql_fac_crs);
    echo "Faculty Courses table created.\n";

    // 7. Time Slots
    $sql_time = "
    CREATE TABLE IF NOT EXISTS time_slots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week VARCHAR(20) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        UNIQUE(day_of_week, start_time, end_time)
    )";
    $conn->query($sql_time);
    echo "Time slots table created.\n";

    // 8. Timetable Entries (The actual generated schedule limit)
    $sql_entries = "
    CREATE TABLE IF NOT EXISTS timetable_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timetable_id INT NOT NULL,
        course_id INT NOT NULL,
        faculty_id INT NOT NULL,
        room_id INT NOT NULL,
        time_slot_id INT NOT NULL,
        FOREIGN KEY (timetable_id) REFERENCES timetables(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
        FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE CASCADE
    )";
    $conn->query($sql_entries);
    echo "Timetable entries table created.\n";

    // Optionally create default admin
    $admin_email = "admin@timetablegen.com";
    $result = $conn->query("SELECT id FROM users WHERE email='$admin_email'");
    if ($result->num_rows == 0) {
        $hash = password_hash("admin123", PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, email, password_hash, role) VALUES ('System Admin', '$admin_email', '$hash', 'admin')");
        echo "Default admin user created (admin@timetablegen.com / admin123).\n";
    }

    echo "Setup finished successfully!\n";

}
catch (Exception $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
