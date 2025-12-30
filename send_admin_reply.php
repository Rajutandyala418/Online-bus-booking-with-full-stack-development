<?php
include('./include/db_connect.php');
$username = $_POST['username'];
$message  = $_POST['message'];
$sender   = $_POST['sender'] ?? 'admin';
$stmt = $conn->prepare("INSERT INTO support_chat (username, sender, message, is_read) VALUES (?, ?, ?, 1)");
$stmt->bind_param("sss", $username, $sender, $message);
$stmt->execute();
$conn->query("UPDATE support_chat SET is_read = 1 WHERE username='$username' AND sender='user'");
echo "sent";
