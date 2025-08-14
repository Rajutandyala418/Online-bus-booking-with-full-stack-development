<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Read JSON data from the request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!$data || !isset($data['bus_details']) || !isset($data['traveller_details'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data received.']);
    exit;
}

$bus_details = $data['bus_details'];
$traveller_details = $data['traveller_details'];

// Sanitize and set defaults
$_SESSION['bus_details'] = [
    'bus_name'     => trim($bus_details['bus_name'] ?? 'Unknown'),
    'bus_number'   => trim($bus_details['bus_number'] ?? 'N/A'),
    'route'        => trim($bus_details['route'] ?? ''),
    'travel_date'  => trim($bus_details['travel_date'] ?? ''),
    'departure'    => trim($bus_details['departure'] ?? ''),
    'arrival'      => trim($bus_details['arrival'] ?? ''),
    'seats'        => trim($bus_details['seats'] ?? ''),
    'fare'         => (float)($bus_details['fare'] ?? 0),
    'schedule_id'  => (int)($bus_details['schedule_id'] ?? 0),
    'seat_type'    => trim($bus_details['seat_type'] ?? 'Seater') // New: seat type
];

$_SESSION['traveller_details'] = [
    'name'  => trim($traveller_details['name'] ?? ''),
    'email' => trim($traveller_details['email'] ?? ''),
    'phone' => trim($traveller_details['phone'] ?? '')
];

// Save optional payment method (UPI/Card)
if (isset($data['payment_method'])) {
    $_SESSION['payment_method'] = trim($data['payment_method']);
}

http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Payment session data saved.']);
exit;
