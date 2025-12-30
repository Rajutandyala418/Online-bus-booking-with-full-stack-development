<?php
include('./include/db_connect.php');
$u=$_GET['username'];

$r=$conn->query("SELECT online_status,last_seen FROM users WHERE username='$u'");
$d=$r->fetch_assoc();

$typingCheck=$conn->query("SELECT id FROM support_chat 
                           WHERE username='$u' AND message='typing...' 
                           AND sender='user' ORDER BY id DESC LIMIT 1");

echo json_encode([
    "online"=>$d['online_status'],
    "last_seen"=>$d['last_seen'],
    "typing"=>$typingCheck->num_rows > 0
]);
