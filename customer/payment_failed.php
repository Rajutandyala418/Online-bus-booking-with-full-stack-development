<?php
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/ticket_pdf.php'; // function to generate PDF

session_start(); 

// ------------------ Step 0: Retrieve payment data from session ------------------
$data = $_SESSION['payment_data'] ?? null;
if (!$data) die("❌ No booking data found. Please try again.");

// Extract variables from session
$user_id        = $data['user_id'] ?? 0;
$schedule_id    = $data['schedule_id'] ?? 0;
$bus_name       = $data['bus_name'] ?? '';
$bus_number     = $data['bus_number'] ?? '';
$seat_type      = $data['seat_type'] ?? 'Seater';
$coupon         = $data['coupon'] ?? '';
$total_amount   = $data['total_amount'] ?? 0;
$payment_method = $data['payment_method'] ?? 'CARD';
$travellers     = $data['travellers'] ?? [];
$seats          = $data['seats'] ?? [];

// Validate essential fields
if (!$user_id || !$schedule_id || !$bus_name || !$bus_number || empty($travellers) || empty($seats)) {
    die("❌ Invalid booking data.");
}

// Ensure seats is array
if (!is_array($seats)) $seats = explode(',', $seats);
// --- Initialize safe defaults to prevent undefined variable warnings ---
$booking_date = date('Y-m-d H:i:s');
$status = 'cancelled';
$route = $data['route'] ?? 'N/A';
$departure = $data['departure_time'] ?? ($data['departure'] ?? 'N/A');
$arrival = $data['arrival_time'] ?? ($data['arrival'] ?? 'N/A');

// ------------------ Step 1: Prevent duplicate failed insert ------------------
if(isset($_SESSION['last_failed_booking_id'])){
    $booking_id = $_SESSION['last_failed_booking_id'];
} else {

    // ------------------ Step 2: Get bus_id and admin_id ------------------
    $stmt = $conn->prepare("SELECT id, admin_id FROM buses WHERE bus_name=? AND bus_number=? LIMIT 1");
    $stmt->bind_param("ss", $bus_name, $bus_number);
    $stmt->execute();
    $stmt->bind_result($bus_id, $admin_id);
    $stmt->fetch();
    $stmt->close();
    if (!$bus_id) die("❌ Bus not found.");

    // ------------------ Step 3: Get route_id from schedules ------------------
   $stmt = $conn->prepare("SELECT route_id, departure_time, arrival_time FROM schedules WHERE id=? LIMIT 1");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$stmt->bind_result($route_id, $departure_time, $arrival_time);
$stmt->fetch();
$stmt->close();
$departure = $departure_time ?? $data['departure'] ?? 'N/A';
$arrival = $arrival_time ?? $data['arrival'] ?? 'N/A';

    if (!$route_id) die("❌ Schedule not found.");

    // ------------------ Step 4: Get route details ------------------
    $stmt = $conn->prepare("SELECT source, destination FROM routes WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $stmt->bind_result($source, $destination);
    $stmt->fetch();
    $stmt->close();
    $route = $source . " → " . $destination;

    // ------------------ Step 5: Insert booking ------------------
    $booking_date = date('Y-m-d H:i:s');
    $status = 'cancelled';
    $seat_string = implode(',', $seats);

    $stmt = $conn->prepare("INSERT INTO bookings 
        (user_id, schedule_id, source, destination, seat_number, booking_date, status, created_at, updated_at, admin_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
    $stmt->bind_param("iisssssi", $user_id, $schedule_id, $source, $destination, $seat_string, $booking_date, $status, $admin_id);
    if(!$stmt->execute()) die("❌ Booking insert failed: ".$stmt->error);
    $booking_id = $stmt->insert_id;
    $stmt->close();

    // ------------------ Step 6: Insert booking_seats ------------------
    foreach ($seats as $seat) {
        $stmt = $conn->prepare("INSERT INTO booking_seats (booking_id, seat_number, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $booking_id, $seat);
        if(!$stmt->execute()) die("❌ Booking seat insert failed: ".$stmt->error);
        $stmt->close();
    }

    // ------------------ Step 7: Insert travellers ------------------
    foreach ($travellers as $traveller) {
        $n = $traveller['name'] ?? '';
        $e = $traveller['email'] ?? '';
        $p = $traveller['phone'] ?? '';
        $g = $traveller['gender'] ?? 'Other';

        $stmt = $conn->prepare("INSERT INTO travellers (booking_id, name, email, phone, gender, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("issss", $booking_id, $n, $e, $p, $g);
        if(!$stmt->execute()) die("❌ Traveller insert failed: ".$stmt->error);
        $stmt->close();
    }

    // ------------------ Step 8: Insert payment ------------------
    $transaction_id = strtoupper(uniqid("TXN"));
    $payment_status = 'failed';

    $stmt = $conn->prepare("INSERT INTO payments 
        (booking_id, amount, payment_method, payment_status, transaction_id, admin_id, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("idsssi", $booking_id, $total_amount, $payment_method, $payment_status, $transaction_id, $admin_id);
    if(!$stmt->execute()) die("❌ Payment insert failed: ".$stmt->error);
    $stmt->close();

    // ------------------ Step 9: Store last failed booking ------------------
    $_SESSION['last_failed_booking_id'] = $booking_id;
}

// ------------------ Step 10: Fetch booking details for display ------------------
// Username
$stmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
$username = $username ?? 'User';

// Booking seats
$seat_array = [];
$stmt = $conn->prepare("SELECT seat_number FROM booking_seats WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($seat_number);
while($stmt->fetch()) $seat_array[] = $seat_number;
$stmt->close();

// Travellers
$traveller_list = [];
$stmt = $conn->prepare("SELECT name, email, phone, gender FROM travellers WHERE booking_id=?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($name, $email, $phone, $gender);
while($stmt->fetch()) $traveller_list[] = ['name'=>$name,'email'=>$email,'phone'=>$phone,'gender'=>$gender];
$stmt->close();

// Payment
$stmt = $conn->prepare("SELECT amount, payment_method, payment_status, transaction_id FROM payments WHERE booking_id=? LIMIT 1");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$stmt->bind_result($total_amount, $payment_method, $payment_status, $transaction_id);
$stmt->fetch();
$stmt->close();
// Ensure source & destination are always defined (even for existing failed booking)
if (!isset($source) || !isset($destination)) {
    $stmt = $conn->prepare("SELECT source, destination FROM bookings WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $stmt->bind_result($source, $destination);
    $stmt->fetch();
    $stmt->close();

    if (empty($source)) $source = 'N/A';
    if (empty($destination)) $destination = 'N/A';
}

// ------------------ Step 11: Prepare Email ------------------
// ------------------ Step 11: Prepare Email (send only once) ------------------
if (empty($_SESSION['email_sent_for_failed_booking']) || $_SESSION['email_sent_for_failed_booking'] !== $booking_id) {

    $travellerHTML = '';
    foreach ($traveller_list as $tr) {

    $travellerHTML .= "Name: {$tr['name']}\nEmail: {$tr['email']}\nPhone: {$tr['phone']}\nGender: {$tr['gender']}\n────────────────────────\n";
}

$ticketHTML = "<pre style='font-family:Arial,sans-serif; font-size:14px;'>
🎫 Bus Ticket - Payment Failed
────────────────────────
🆔 Booking ID: {$booking_id}
📅 Booking Date: {$booking_date}
❌ Status: {$status}
────────────────────────
🚌 Bus Details
Bus: {$bus_name} ({$bus_number})
Route: {$route}
Travel Date: {$data['travel_date']}
Departure: {$departure}
Arrival: {$arrival}
Seats: ".implode(',', $seat_array)."
────────────────────────
👤 Traveller Details
{$travellerHTML}
💰 Payment Details
Total Fare: ₹{$total_amount}
Method: {$payment_method}
Status: {$payment_status}
Txn ID: {$transaction_id}
Coupon: {$coupon}
────────────────────────
🙏 Thank you for using Bus Booking System!
</pre>";

// Send email to primary traveller
if (!empty($traveller_list)) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com','Bus Booking System');
        $mail->addAddress($traveller_list[0]['email'], $traveller_list[0]['name'] ?? 'Traveller');

        $mail->isHTML(true);
        $mail->Subject = "Bus Booking Failed - #$booking_id";
        $mail->Body    = $ticketHTML;
        $mail->AltBody = strip_tags($ticketHTML);

       $pdfContent = generateTicketPDF([
    'booking_date' => $booking_date,
    'status' => $status,
    'bus_name' => $bus_name,
    'bus_number' => $bus_number,
    'source' => $source,
    'destination' => $destination,
    'travel_date' => $data['travel_date'],
    'departure_time' => $departure,
    'arrival_time' => $arrival,
    'amount' => $total_amount,
    'payment_method' => $payment_method,
    'payment_status' => $payment_status,
    'transaction_id' => $transaction_id
], $traveller_list, $booking_id, $coupon);


        $mail->addStringAttachment($pdfContent, "BusTicket_Failed_$booking_id.pdf");
        $mail->send();
$_SESSION['email_sent_for_failed_booking'] = $booking_id;

    } catch (Exception $e) {
        // Ignore email failures
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Failed</title>
<style>
body {
    font-family:'Poppins',sans-serif;
    background:#111;
    color:#fff;
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:flex-start;
    padding:30px 10px;
    overflow-y:auto;
}

.container {
    width:100%;
    max-width:800px;
    background:rgba(0,0,0,0.45);
    border-radius:12px;
    padding:25px;
    box-shadow:0 4px 18px rgba(0,0,0,0.4);
    backdrop-filter:blur(4px);
}

table {
    width:100%;
    border-collapse:collapse;
    margin-bottom:25px;
    background:rgba(0,0,0,0.55);
    border-radius:10px;
    overflow:hidden;
}

th, td {
    padding:12px;
    border-bottom:1px solid #444;
    font-size:0.95rem;
}

th {
    background:#222;
    color:#ffde59;
    font-weight:600;
}

h2,h3 { color:#ff4d4d; margin-bottom:10px; }
.dashboard-btn {
    display:block;
    width:220px;
    margin:25px auto;
    padding:12px;
    text-align:center;
    color:#111;
    background:#ffde59;
    font-weight:700;
    border-radius:25px;
    text-decoration:none;
}
.dashboard-btn:hover {
    opacity:0.9;
}
@media (max-width: 480px) {

    .container {
        width:92%;
        padding:18px;
    }

    table, th, td {
        font-size:0.8rem;
    }

    h2, h3 {
        font-size:1.1rem;
    }

    .dashboard-btn {
        width:70%;
        padding:10px;
        font-size:0.9rem;
    }
}

</style>
</head>
<body>
<div class="container">
    <h2>❌ Payment Failed - Booking Cancelled</h2>
    <p><strong>Booking Date:</strong> <?php echo $booking_date; ?></p>
    <p><strong>Status:</strong> <span style="color:red;">Cancelled</span></p>

    <h3>Bus Details</h3>
    <table>
        <tr><th>Bus Name</th><td><?php echo htmlspecialchars($bus_name); ?></td></tr>
        <tr><th>Bus Number</th><td><?php echo htmlspecialchars($bus_number); ?></td></tr>
        <tr><th>Route</th><td><?php echo htmlspecialchars($route); ?></td></tr>
        <tr><th>Travel Date</th><td><?php echo htmlspecialchars($data['travel_date']); ?></td></tr>
        <tr><th>Departure</th><td><?php echo htmlspecialchars($departure); ?></td></tr>
        <tr><th>Arrival</th><td><?php echo htmlspecialchars($arrival); ?></td></tr>
        <tr><th>Seats</th><td><?php echo htmlspecialchars(implode(',',$seat_array)); ?></td></tr>
        <tr><th>Seat Type</th><td><?php echo htmlspecialchars($seat_type); ?></td></tr>
    </table>

    <h3>Traveller Details</h3>
    <table>
        <?php foreach($traveller_list as $traveller): ?>
        <tr><th>Name</th><td><?php echo htmlspecialchars($traveller['name']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($traveller['email']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($traveller['phone']); ?></td></tr>
        <tr><th>Gender</th><td><?php echo htmlspecialchars($traveller['gender']); ?></td></tr>
        <tr><td colspan="2"><hr style="border-color:#555;"></td></tr>
        <?php endforeach; ?>
    </table>

    <h3>Payment Details</h3>
    <table>
        <tr><th>Total Amount</th><td>₹<?php echo $total_amount; ?></td></tr>
        <tr><th>Payment Status</th><td style="color:red;">Failed</td></tr>
        <tr><th>Transaction ID</th><td><?php echo $transaction_id; ?></td></tr>
        <tr><th>Coupon</th><td><?php echo htmlspecialchars($coupon); ?></td></tr>
    </table>

    <a class="dashboard-btn" href="dashboard.php?username=<?= urlencode($username) ?>">Back to Dashboard</a>
</div>
</body>
</html>
