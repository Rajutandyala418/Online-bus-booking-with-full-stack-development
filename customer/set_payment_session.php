<?php
include(__DIR__ . '/../include/db_connect.php');

session_start(); // keep booking_id in session if needed

// ------------------ Get POST data ------------------
$bus_name       = $_POST['bus_name'] ?? null;
$bus_number     = $_POST['bus_number'] ?? null;
$travel_date    = $_POST['travel_date'] ?? null;
$seats          = $_POST['seats'] ?? null; 
$travellers     = $_POST['travellers'] ?? []; 
$fare           = $_POST['fare'] ?? 0;
$total_amount   = $_POST['total_amount'] ?? 0;
$payment_method = $_POST['payment_method'] ?? 'CARD';
$user_id        = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$coupon         = $_POST['coupon'] ?? '';
$discount       = $_POST['discount'] ?? 0;
$username       = $_POST['username'] ?? '';
$schedule_id    = isset($_POST['schedule_id']) ? (int)$_POST['schedule_id'] : 0;

// Validate required data
if (!$bus_name || !$bus_number || !$travel_date || !$seats || empty($travellers) || !$user_id || !$schedule_id) {
    die("❌ Missing required booking data.");
}

// Ensure seats is an array
if (!is_array($seats)) $seats = explode(',', $seats);

// ------------------ Step 1: Get bus_id and admin_id ------------------
$stmt = $conn->prepare("SELECT id, admin_id FROM buses WHERE bus_name=? AND bus_number=? LIMIT 1");
$stmt->bind_param("ss", $bus_name, $bus_number);
$stmt->execute();
$stmt->bind_result($bus_id, $admin_id);
$stmt->fetch();
$stmt->close();
if (!$bus_id) die("❌ Bus not found.");

// ------------------ Step 2: Get route_id from schedules ------------------
$stmt = $conn->prepare("SELECT route_id FROM schedules WHERE id=? LIMIT 1");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$stmt->bind_result($route_id);
$stmt->fetch();
$stmt->close();
if (!$route_id) die("❌ Schedule not found.");

// ------------------ Step 3: Get route details ------------------
$stmt = $conn->prepare("SELECT source, destination FROM routes WHERE id=? LIMIT 1");
$stmt->bind_param("i", $route_id);
$stmt->execute();
$stmt->bind_result($source, $destination);
$stmt->fetch();
$stmt->close();

// ------------------ Step 4: Check seat availability ------------------
foreach ($seats as $seat) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings b
        JOIN booking_seats bs ON b.id = bs.booking_id
        WHERE b.schedule_id=? AND bs.seat_number=? AND b.status='booked'");
    $stmt->bind_param("is", $schedule_id, $seat);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) die("❌ Seat $seat is already booked. Choose another seat.");
}

// ------------------ Step 5: Insert ONE booking ------------------
$seat_numbers_str = implode(',', $seats); // store all seats in one column
$booking_date = date('Y-m-d H:i:s');
$status = 'booked';

$stmt = $conn->prepare("INSERT INTO bookings 
    (user_id, schedule_id, source, destination, seat_number, booking_date, status, admin_id, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param("iisssssi", $user_id, $schedule_id, $source, $destination, $seat_numbers_str, $booking_date, $status, $admin_id);
$stmt->execute();
$booking_id = $stmt->insert_id;
$stmt->close();

// ------------------ Step 6: Insert booking_seats ------------------
foreach ($seats as $seat) {
    $stmt = $conn->prepare("INSERT INTO booking_seats (booking_id, seat_number, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $booking_id, $seat);
    $stmt->execute();
    $stmt->close();
}

// ------------------ Step 7: Insert ONE payment ------------------
$transaction_id = strtoupper(uniqid("TXN"));
$payment_status = 'paid';

$stmt = $conn->prepare("INSERT INTO payments 
    (booking_id, amount, payment_method, payment_status, transaction_id, admin_id, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param("idsssi", $booking_id, $total_amount, $payment_method, $payment_status, $transaction_id, $admin_id);
$stmt->execute();
$stmt->close();

// ------------------ Step 8: Insert ALL travellers ------------------
foreach ($travellers as $traveller) {
    $n = $traveller['name'] ?? '';
    $e = $traveller['email'] ?? '';
    $p = $traveller['phone'] ?? '';
    $g = $traveller['gender'] ?? 'Other';

    $stmt = $conn->prepare("INSERT INTO travellers (booking_id, name, email, phone, gender, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("issss", $booking_id, $n, $e, $p, $g);
    $stmt->execute();
    $stmt->close();
}

// ------------------ Step 9: Save booking_id in session ------------------
$_SESSION['last_booking_id'] = $booking_id;

// ------------------ Step 10: Redirect ------------------
header("Location: booking_success.php?booking_id=$booking_id&coupon=" . urlencode($coupon));
exit;
?>
