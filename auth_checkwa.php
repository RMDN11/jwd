<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Cek apakah user sudah login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: wa.php");
    exit();
}

// Security headers for mobile
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Optional: Tambahan security check
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Mobile detection (optional)
function isMobileDevice() {
    return preg_match("/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i", $_SERVER['HTTP_USER_AGENT']);
}
?>