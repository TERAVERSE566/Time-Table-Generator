<?php
// setup_db.php - Initialization script using PDO
require_once 'config/db.php';

try {
    // 1. Timetable Master (To save multiple generated timetables)
    $pdo->exec("CREATE TABLE IF NOT EXISTS timetable_master (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        department VARCHAR(100),
        semester VARCHAR(10),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // 2. Subjects
    $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timetable_id INT NOT NULL,
        code VARCHAR(50) NOT NULL,
        name VARCHAR(150) NOT NULL,
        type ENUM('Lecture', 'Practical', 'Tutorial') DEFAULT 'Lecture',
        credits INT DEFAULT 3,
        FOREIGN KEY (timetable_id) REFERENCES timetable_master(id) ON DELETE CASCADE
    )");

    // 3. Faculty
    $pdo->exec("CREATE TABLE IF NOT EXISTS faculty (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timetable_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        max_load INT DEFAULT 15,
        FOREIGN KEY (timetable_id) REFERENCES timetable_master(id) ON DELETE CASCADE
    )");

    // 4. Classes / Rooms
    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timetable_id INT NOT NULL,
        room_name VARCHAR(50) NOT NULL,
        capacity INT NOT NULL,
        FOREIGN KEY (timetable_id) REFERENCES timetable_master(id) ON DELETE CASCADE
    )");

    // 5. Schedules (Timetable Entries)
    $pdo->exec("CREATE TABLE IF NOT EXISTS schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timetable_id INT NOT NULL,
        subject_id INT NOT NULL,
        faculty_id INT NOT NULL,
        class_id INT NOT NULL,
        day_of_week VARCHAR(20) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        FOREIGN KEY (timetable_id) REFERENCES timetable_master(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        UNIQUE (faculty_id, day_of_week, start_time), -- Prevent Faculty Double Booking
        UNIQUE (class_id, day_of_week, start_time) -- Prevent Room Double Booking
    )");

    echo "<h3>Database setup completed successfully. Tables normalized.</h3>";
    echo "<a href='index.php'>Return to Home</a>";

} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
