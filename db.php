<?php
// db.php - Database connection for TimetableGen
$host = "localhost";
$user = "root";
$pass = "Anish566@@"; 
$db   = "timetablegen";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die("Database Connection failed: " . $e->getMessage());
}
?>

