<?php
include(__DIR__ . '/../include/db_connect.php');

header('Content-Type: application/json');

$field = $_GET['field'] ?? '';
$value = trim($_GET['value'] ?? '');

$allowed = ['username', 'email', 'phone'];

if (!in_array($field, $allowed) || empty($value)) {
    echo json_encode(['exists' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM admin WHERE $field = ? LIMIT 1");
$stmt->bind_param("s", $value);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}
$stmt->close();
$conn->close();
