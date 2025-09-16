<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent browser caching of secure pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /y22cm171/bus_booking/login.php");  // Updated path
    exit();
}
