<?php
include('../include/db_connect.php');

$username = $_POST['username'];

$stmt = $conn->prepare("DELETE FROM support_chat WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();

echo "cleared";
?>
