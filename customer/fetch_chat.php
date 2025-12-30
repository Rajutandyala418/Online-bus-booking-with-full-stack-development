<?php
include('../include/db_connect.php');
$username = $_GET['username'];

$query = $conn->prepare("SELECT sender, message, timestamp, is_read, msg_status 
                         FROM support_chat WHERE username = ? ORDER BY id ASC");

$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();

$messages = [];
while($row = $result->fetch_assoc()){
    $messages[] = $row;
}
echo json_encode($messages);
?>
