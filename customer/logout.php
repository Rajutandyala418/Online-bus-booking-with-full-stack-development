<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1️⃣ Clear all session variables
$_SESSION = [];

// 2️⃣ Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3️⃣ Destroy the session
session_destroy();

// 4️⃣ Redirect to login.php
header("Location: login.php?logout=1");
exit();
?>
