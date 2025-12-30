<?php
include('../include/db_connect.php');
$username = $_GET['username'];
$conn->query("UPDATE support_chat SET is_read=1 WHERE username='$username' AND sender='admin'");
echo "done";
?>
