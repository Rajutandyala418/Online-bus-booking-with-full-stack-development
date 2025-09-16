<?php
// ✅ Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Unset all session variables
$_SESSION = [];

// ✅ Destroy the session
session_destroy();

// ✅ Delete session cookie if exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ Prevent caching so back button won’t work
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// ✅ Redirect to login page
header("Location: login.php");
exit();
?>
