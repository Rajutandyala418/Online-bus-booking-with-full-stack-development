<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['user_id'], $_SESSION['bus_details'], $_SESSION['traveller_details'])) {
    header('Location: dashboard.php');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
$bus_details = $_SESSION['bus_details'];
$traveller_details = $_SESSION['traveller_details'];

$user_id = $_SESSION['user_id'];
$schedule_id = $bus_details['schedule_id'];
$seat_number = $bus_details['seats'];
$status = 'Confirmed';
$booking_date = date('Y-m-d H:i:s');

// --- Insert booking into bookings table ---
$stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, seat_number, booking_date, status) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $user_id, $schedule_id, $seat_number, $booking_date, $status);
$stmt->execute();
$booking_id = $stmt->insert_id;
$stmt->close();

$_SESSION['booking_id'] = $booking_id;

// --- Insert seat details into booking_seats table ---
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

// --- Insert payment details into payments table ---
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
        input[type="text"] { padding: 8px; border-radius: 5px; border: none; width: 200px; }
    </style>
    <script>
      function downloadTicket() {
    window.location.href = "download_ticket.php?booking_id=<?php echo $booking_id; ?>";
}
        function sendWhatsApp() {
            const number = document.getElementById('whatsapp').value.trim();
            if (number) {
                alert("Ticket sent to WhatsApp: " + number);
            } else {
                alert("Please enter a WhatsApp number.");
            }
        }
        function sendEmail() {
            alert("Ticket sent to email: <?php echo $traveller_details['email']; ?>");
        }
    </script>
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
            <button class="btn" onclick="downloadTicket()">Download Ticket (PDF)</button>
            <input type="text" id="whatsapp" placeholder="WhatsApp Number">
            <button class="btn" onclick="sendWhatsApp()">Send to WhatsApp</button>
            <button class="btn" onclick="sendEmail()">Send to Email</button>
        </div>
    </div>
</body>
</html>
