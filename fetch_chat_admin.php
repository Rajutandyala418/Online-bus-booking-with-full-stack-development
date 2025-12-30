<?php
include('./include/db_connect.php');
$username = $_GET['username'];

$stmt = $conn->prepare("SELECT id, sender, message, timestamp, msg_status, is_read 
                        FROM support_chat 
                        WHERE username=? ORDER BY id ASC");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);

