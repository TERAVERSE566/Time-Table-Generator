<?php
// db.php - Database connection for TimetableGen
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "127.0.0.1") {
    // Localhost XAMPP credentials
    $host = "localhost";
    $user = "root";
    $pass = "Anish566@@"; 
    $db   = "timetablegen";
    
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        $conn->set_charset("utf8mb4");
    } catch(Exception $e) {
        die("Database Connection failed: " . $e->getMessage());
    }
} else {
    // TiDB Live credentials for Render
    $host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
    $user = "uGHbY7uvVYrgr8U.root";
    $pass = "Fgcy7tO0NrLVWC87"; 
    $db   = "test";
    $port = 4000;
    
    try {
        $conn = mysqli_init();
        mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
        $conn->set_charset("utf8mb4");
    } catch(Exception $e) {
        die("Database Connection failed: " . $e->getMessage());
    }
}
?>
