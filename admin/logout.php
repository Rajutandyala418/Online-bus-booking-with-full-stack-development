<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy the session
session_unset();
session_destroy();

// Redirect with timeout message if applicable
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    header("Location: login.php?timeout=1");
} else {
    header("Location: login.php");
}
exit();
?>
