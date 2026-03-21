<?php
// db.php - Database connection for TimetableGen
if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "127.0.0.1") {
    // Localhost XAMPP credentials
    $host = "localhost";
    $user = "root";
    $pass = "Anish566@@"; 
    $db   = "timetablegen";
} else {
    // InfinityFree Live credentials
    $host = "sql103.infinityfree.com";
    $user = "if0_41443046";
    $pass = "Anish002005"; 
    $db   = "if0_41443046_timetable";
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>

