<?php
$mysqli = new mysqli('localhost', 'root', '', 'bus_booking');
if ($mysqli->connect_errno) exit;

$term = $_GET['term'] ?? '';
$type = $_GET['type'] ?? '';

if (!$term || !$type) exit;

$column = $type === 'source' ? 'source' : 'destination';
$stmt = $mysqli->prepare("SELECT DISTINCT $column FROM routes WHERE $column LIKE ? ORDER BY $column ASC LIMIT 10");
$like = $term . '%';
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row[$column];
}

echo json_encode($suggestions);
$stmt->close();
?>
