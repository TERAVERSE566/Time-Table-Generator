<?php
// includes/functions.php
require_once __DIR__ . '/../config/db.php';

function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) $data[$key] = sanitize($value);
    } else {
        $data = htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

function redirect($url) {
    // If URL doesn't start with http, prepend BASE_URL
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = BASE_URL . ltrim($url, '/');
    }
    header("Location: $url");
    exit();
}

function require_admin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
        $_SESSION['error'] = "Authentication required.";
        redirect("auth/login.php");
    }
}

function display_flash() {
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i>' . $_SESSION['error'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show shadow-sm"><i class="fa-solid fa-circle-check me-2"></i>' . $_SESSION['success'] . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
        unset($_SESSION['success']);
    }
}
?>
