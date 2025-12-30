<?php
require_once __DIR__ . '/../include/db_connect.php';

$term = $_GET['term'] ?? '';
$type = $_GET['type'] ?? 'source'; // 'source' or 'destination'

if (!in_array($type, ['source', 'destination'])) {
    echo json_encode([]);
    exit;
}

$term = "%$term%";
$stmt = $conn->prepare("SELECT DISTINCT $type FROM routes WHERE $type LIKE ? LIMIT 10");
$stmt->bind_param("s", $term);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row[$type];
}

echo json_encode($suggestions);
?>

