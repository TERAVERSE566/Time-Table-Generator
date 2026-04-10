<?php
// config/db.php
// Centralized Database Connection using setup PDO
if (session_status() === PHP_SESSION_NONE) session_start();

$is_local = ($_SERVER['HTTP_HOST'] == "localhost" || $_SERVER['HTTP_HOST'] == "127.0.0.1");

// Compute dynamic absolute URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); 
// In dev environments, it might be deep, let's just hardcode or reliably detect root
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . '/');

$host = $is_local ? "localhost" : "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
$db   = $is_local ? "timetablegen" : "test";
$user = $is_local ? "root" : "uGHbY7uvVYrgr8U.root";
$pass = $is_local ? "Anish566@@" : "Fgcy7tO0NrLVWC87";
$port = $is_local ? 3306 : 4000;

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    if (!$is_local) { $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false; }
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    if ($is_local) throw new \PDOException($e->getMessage(), (int)$e->getCode());
    else die("Database connection failed. Please check logs.");
}
?>
