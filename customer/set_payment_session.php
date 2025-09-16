<?php
include(__DIR__ . '/../include/db_connect.php');

// ------------------ Get POST data ------------------
$bus_name       = $_POST['bus_name'] ?? null;
$bus_number     = $_POST['bus_number'] ?? null;
$travel_date    = $_POST['travel_date'] ?? null;
$seats          = $_POST['seats'] ?? null; // comma-separated or array
$travellers     = $_POST['travellers'] ?? []; // array of ['name'=>'', 'email'=>'', 'phone'=>'', 'gender'=>'']
$fare           = $_POST['fare'] ?? 0;
$total_amount    = $_POST['total_amount'] ?? 0;
$payment_method = $_POST['payment_method'] ?? 'CARD';
$user_id        = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$coupon         = $_POST['coupon'] ?? '';
$discount       = $_POST['discount'] ?? 0;
$username = $_POST['username'] ?? '';

// Validate required data
if (!$bus_name || !$bus_number || !$travel_date || !$seats || empty($travellers) || !$user_id) {
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

// ------------------ Step 2: Get schedule_id ------------------
$stmt = $conn->prepare("SELECT id, route_id FROM schedules WHERE bus_id=? AND travel_date=? LIMIT 1");
$stmt->bind_param("is", $bus_id, $travel_date);
$stmt->execute();
$stmt->bind_result($schedule_id, $route_id);
$stmt->fetch();
$stmt->close();

if (!$schedule_id) die("❌ Schedule not found.");

// ------------------ Step 3: Get route details ------------------
$stmt = $conn->prepare("SELECT source, destination FROM routes WHERE id=? LIMIT 1");
$stmt->bind_param("i", $route_id);
$stmt->execute();
$stmt->bind_result($source, $destination);
$stmt->fetch();
$stmt->close();

// ------------------ Step 4: Check seat availability ------------------
foreach ($seats as $seat) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE schedule_id=? AND FIND_IN_SET(?, seat_number) > 0 AND status='booked'");
    $stmt->bind_param("is", $schedule_id, $seat);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) die("❌ Seat $seat is already booked. Choose another seat.");
}

// ------------------ Step 5: Generate SINGLE booking ID ------------------
$first_traveller = $travellers[0];
$phone_last4 = substr($first_traveller['phone'], -4);
$booking_id = rand(1000, 9999) . $phone_last4; // one booking ID for all seats

$booking_date = date('Y-m-d H:i:s');
$status = 'booked';
$per_seat_fare = round($fare / count($seats), 2);

// ------------------ Step 6: Insert bookings for each seat ------------------
foreach ($seats as $seat) {
 // ------------------ Step 6: Insert single booking ------------------
$b_id   = $booking_id;
$u_id   = $user_id;
$s_id   = $schedule_id;
$b_date = $booking_date;
$st     = $status;
$a_id   = $admin_id;

$stmt = $conn->prepare("INSERT INTO bookings 
    (id, user_id, schedule_id, source, destination, booking_date, status, created_at, updated_at, admin_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
$stmt->bind_param("iisssssi", $b_id, $u_id, $s_id, $source, $destination, $b_date, $st, $a_id);
$stmt->execute();
$stmt->close();

}
// ------------------ Step 7: Insert seats ------------------
foreach ($seats as $seat) {
    $stmt = $conn->prepare("INSERT INTO booking_seats (booking_id, seat_number, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $b_id, $seat); // seat_number is now varchar
    $stmt->execute();
    $stmt->close();
}


// ------------------ Step 7: Insert travellers ------------------
foreach ($travellers as $traveller) {
    $b_id = $booking_id;
    $n    = $traveller['name'] ?? '';
    $e    = $traveller['email'] ?? '';
    $p    = $traveller['phone'] ?? '';
    $g    = $traveller['gender'] ?? '';

    $stmt = $conn->prepare("INSERT INTO travellers (booking_id, name, email, phone, gender, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("issss", $b_id, $n, $e, $p, $g);
    $stmt->execute();
    $stmt->close();
}

// ------------------ Step 8: Insert single payment for total fare ------------------
$transaction_id = strtoupper(uniqid("TXN"));
$b_id           = $booking_id;
$amt            = $total_amount;
$pmethod        = $payment_method;
$pstatus        = 'paid';
$a_id           = $admin_id;
$txn_id         = $transaction_id;

$stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, admin_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
$stmt->bind_param("idsssi", $b_id, $amt, $pmethod, $pstatus, $txn_id, $a_id);
$stmt->execute();
$stmt->close();

// ------------------ Step 9: Redirect to booking success ------------------
header("Location: booking_success.php?" . http_build_query([
    'booking_id'  => $booking_id,
    'seats'       => implode(',', $seats),
    'total_fare'  => $fare,
    'source'      => $source,
    'destination' => $destination,
    'coupon'      => $coupon,
    'username'    => $username,   // ✅ added
    'user_id'     => $user_id     // ✅ added
]));
exit;

?>
