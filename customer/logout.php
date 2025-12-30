<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_write_close();
session_regenerate_id(true);

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

setcookie("PHPSESSID", "", time() - 3600, "/", "", true, true);
setcookie("remember_me", "", time() - 3600, "/", "", true, true);

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_destroy();
ob_clean();

header("Location: login.php?logout=1");
exit();
?>
