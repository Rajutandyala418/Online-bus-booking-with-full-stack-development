<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../include/db_connect.php');

$username = $_SESSION['username'] ?? 'User';
$booking_id = null;
$booking_date = date('Y-m-d H:i:s');

// ✅ If booking already done, just load data without inserting again
if (isset($_SESSION['booking_done']) && $_SESSION['booking_done'] === true) {
    $booking_id = $_SESSION['booking_id'];
    $bus_details = $_SESSION['bus_details'];
    $traveller_details = $_SESSION['traveller_details'];
    $fare = (float) $bus_details['fare'];
    $base_fare = round($fare / 1.05, 2);
    $gst = round($fare - $base_fare, 2);
  if (!isset($_SESSION['transaction_id'])) {
    $_SESSION['transaction_id'] = uniqid('TXN');
}
$transaction_id = $_SESSION['transaction_id'];
} else {
    // Check required session variables
    if (!isset($_SESSION['user_id'], $_SESSION['bus_details'], $_SESSION['traveller_details'])) {
        header('Location: dashboard.php');
        exit;
    }

    $bus_details = $_SESSION['bus_details'];
    $traveller_details = $_SESSION['traveller_details'];

    $user_id = $_SESSION['user_id'];
    $schedule_id = $bus_details['schedule_id'];
    $seat_number = $bus_details['seats'];
    $status = 'Confirmed';

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, seat_number, booking_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $schedule_id, $seat_number, $booking_date, $status);
    $stmt->execute();
    $booking_id = $stmt->insert_id;
    $stmt->close();

    $_SESSION['booking_id'] = $booking_id;

    // Insert seat details
    $seatsArray = explode(',', $seat_number);
    foreach ($seatsArray as $seat) {
        $seat = trim($seat);
        if (!empty($seat)) {
            $stmt = $conn->prepare("INSERT INTO booking_seats (booking_id, seat_number, created_at) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $booking_id, $seat);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Insert payment details
    $fare = (float) $bus_details['fare'];
    $base_fare = round($fare / 1.05, 2);
    $gst = round($fare - $base_fare, 2);
    $payment_method = $_SESSION['payment_method'] ?? 'UPI/Card';
    $payment_status = 'Paid';
$transaction_id = uniqid('TXN');

    $stmt = $conn->prepare("INSERT INTO payments (booking_id, amount, payment_method, payment_status, transaction_id, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("idsss", $booking_id, $fare, $payment_method, $payment_status, $transaction_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['booking_done'] = true;
}

// ✅ WhatsApp sending
if (isset($_GET['send_whatsapp']) && !empty($_GET['phone'])) {
    $phone = preg_replace('/\D/', '', $_GET['phone']); // keep only numbers

    $ticketMessage = "
🎫 *Bus Ticket*
────────────────────────
🆔 Booking ID: {$booking_id}
📅 Booking Date: {$booking_date}
✅ Status: Confirmed
────────────────────────
🚌 *Bus Details*
Name: {$bus_details['bus_name']}
Number: {$bus_details['bus_number']}
Route: {$bus_details['route']}
Travel Date: {$bus_details['travel_date']}
Departure: {$bus_details['departure']}
Arrival: {$bus_details['arrival']}
Seats: {$bus_details['seats']}
────────────────────────
👤 *Traveller Details*
Name: {$traveller_details['name']}
Email: {$traveller_details['email']}
Phone: {$traveller_details['phone']}
────────────────────────
💰 *Payment Details*
Base Fare: ₹{$base_fare}
GST (5%): ₹{$gst}
Total Fare: ₹{$fare}
Status: Paid
Txn ID: {$transaction_id}
────────────────────────
Thank you for booking with Bus Booking System!
";

    header("Location: https://wa.me/{$phone}?text=" . rawurlencode($ticketMessage));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Success</title>
    <style>
        body { font-family: 'Poppins', sans-serif; background: #111; color: #fff; margin: 0; padding: 20px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; }
        .dashboard-btn { background: #ffde59; padding: 10px 20px; border-radius: 5px; color: #111; font-weight: bold; text-decoration: none; }
        .container { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: rgba(0, 0, 0, 0.4); }
        table, th, td { border: 1px solid #555; }
        th, td { padding: 10px; text-align: left; }
        th { background: #222; color: #ffde59; }
        h2, h3 { color: #ffde59; margin-bottom: 10px; }
        .btn { background: linear-gradient(90deg, #ff512f, #dd2476); padding: 10px 20px; border-radius: 5px; text-decoration: none; color: white; font-weight: bold; margin-right: 10px; cursor: pointer; }
        .bottom-btns { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        input[type="text"], input[type="email"] { padding: 8px; border-radius: 5px; border: none; width: 200px; }
    </style>
</head>
<body>
    <div class="top-bar">
        <a class="dashboard-btn" href="dashboard.php">Back to Dashboard</a>
        <div>Welcome, <?php echo htmlspecialchars($username); ?></div>
    </div>

    <div class="container">
        <h2>🎉 Booking Successful - ID: <?php echo $booking_id; ?></h2>
        <p><strong>Booking Date:</strong> <?php echo $booking_date; ?></p>
        <p><strong>Status:</strong> <span style="color:lime;">Confirmed</span></p>

        <h3>Bus Details</h3>
        <table>
            <tr><th>Bus Name</th><td><?php echo htmlspecialchars($bus_details['bus_name']); ?></td></tr>
            <tr><th>Bus Number</th><td><?php echo htmlspecialchars($bus_details['bus_number']); ?></td></tr>
            <tr><th>Route</th><td><?php echo htmlspecialchars($bus_details['route']); ?></td></tr>
            <tr><th>Travel Date</th><td><?php echo htmlspecialchars($bus_details['travel_date']); ?></td></tr>
            <tr><th>Departure</th><td><?php echo htmlspecialchars($bus_details['departure']); ?></td></tr>
            <tr><th>Arrival</th><td><?php echo htmlspecialchars($bus_details['arrival']); ?></td></tr>
            <tr><th>Seats</th><td><?php echo htmlspecialchars($bus_details['seats']); ?></td></tr>
        </table>

        <h3>Traveller Details</h3>
        <table>
            <tr><th>Name</th><td><?php echo htmlspecialchars($traveller_details['name']); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($traveller_details['email']); ?></td></tr>
            <tr><th>Phone</th><td><?php echo htmlspecialchars($traveller_details['phone']); ?></td></tr>
        </table>

        <h3>Payment Details</h3>
        <table>
            <tr><th>Base Fare</th><td>₹<?php echo $base_fare; ?></td></tr>
            <tr><th>GST (5%)</th><td>₹<?php echo $gst; ?></td></tr>
            <tr><th>Total Fare</th><td>₹<?php echo $fare; ?></td></tr>
            <tr><th>Status</th><td><span style="color:lime;">Paid</span></td></tr>
            <tr><th>Transaction ID</th><td><?php echo $transaction_id; ?></td></tr>
        </table>

        <div class="bottom-btns">
            <button class="btn" onclick="window.location.href='download_ticket.php?booking_id=<?php echo $booking_id; ?>'">Download Ticket (PDF)</button>

            <form method="GET" style="display:inline;">
                <input type="hidden" name="send_whatsapp" value="1">
                <input type="text" name="phone" placeholder="WhatsApp Number" value="<?php echo htmlspecialchars($traveller_details['phone']); ?>" required>
                <button type="submit" class="btn">Send to WhatsApp</button>
            </form>

            <form method="POST" action="send_email.php" style="display:inline;">
                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($traveller_details['email']); ?>" required>
                <button type="submit" class="btn">Send to Email</button>
            </form>
        </div>
    </div>
</body>
</html>
