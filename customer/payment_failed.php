<?php

include(__DIR__ . '/../include/db_connect.php');

// ------------------ Get GET/POST data ------------------
$user_id        = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
$bus_name       = $_GET['bus_name'] ?? $_POST['bus_name'] ?? null;
$bus_number     = $_GET['bus_number'] ?? $_POST['bus_number'] ?? null;
$route          = $_GET['route'] ?? $_POST['route'] ?? null;
$travel_date    = $_GET['travel_date'] ?? $_POST['travel_date'] ?? null;
$departure      = $_GET['departure'] ?? $_POST['departure'] ?? null;
$arrival        = $_GET['arrival'] ?? $_POST['arrival'] ?? null;
$seats          = $_GET['seats'] ?? $_POST['seats'] ?? null; // comma-separated or array
$seat_type      = $_GET['seat_type'] ?? $_POST['seat_type'] ?? null;
$fare           = $_GET['fare'] ?? $_POST['fare'] ?? 0;
$total_amount   = $_GET['total_amount'] ?? $_POST['total_amount'] ?? 0;
$coupon         = $_GET['coupon'] ?? $_POST['coupon'] ?? 'None';
$transaction_id = $_GET['transaction_id'] ?? $_POST['transaction_id'] ?? uniqid('txn_fail_');
$payment_method = $_GET['payment_method'] ?? $_POST['payment_method'] ?? 'unknown';
$travellers     = $_GET['travellers'] ?? $_POST['travellers'] ?? [];

// Ensure seats is an array
if (!is_array($seats)) $seats = explode(',', $seats);

// ------------------ Validate required data ------------------
if (!$bus_name || !$bus_number || !$travel_date || !$seats || empty($travellers) || !$user_id) {
    die("❌ Missing required booking data.");
}

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

// ------------------ Step 4: Generate booking ID ------------------
$first_traveller = $travellers[0];
$phone_last4 = substr($first_traveller['phone'], -4);
$booking_id = rand(1000, 9999) . $phone_last4;

$booking_date   = date('Y-m-d H:i:s');
$status         = 'cancelled';        // Booking cancelled
$payment_status = 'failed';           // Payment failed

// ------------------ Step 5: Insert bookings for each seat ------------------
foreach ($seats as $seat) {
    $stmt = $conn->prepare("INSERT INTO bookings (id, user_id, schedule_id, seat_number, booking_date, status, created_at, updated_at, admin_id) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
    $stmt->bind_param("iiisssi", $booking_id, $user_id, $schedule_id, $seat, $booking_date, $status, $admin_id);
    $stmt->execute();
    $stmt->close();
}

// ------------------ Step 6: Insert travellers ------------------
foreach ($travellers as $traveller) {
    $stmt = $conn->prepare("INSERT INTO travellers (booking_id, name, email, phone, gender, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("issss", $booking_id, $traveller['name'], $traveller['email'], $traveller['phone'], $traveller['gender']);
    $stmt->execute();
    $stmt->close();
}

// ------------------ Step 7: Insert single payment ------------------
$txn_id = strtoupper(uniqid("TXN"));
$stmt = $conn->prepare("
    INSERT INTO payments 
    (id, booking_id, amount, payment_method, payment_status, transaction_id, created_at, updated_at, admin_id) 
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
");
$stmt->bind_param("idssssi", $booking_id, $booking_id, $total_amount, $payment_method, $payment_status, $txn_id, $admin_id);
$stmt->execute();
$stmt->close();
// ------------------ Step 8: Fetch username ------------------
$username = "User";
$stmt = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// ------------------ Step 9: Remove PHP redirect ------------------
// header("Location: dashboard.php?username=" . urlencode($username));
// exit;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Failed</title>
<style>
body { font-family:'Poppins',sans-serif; background:#111; color:#fff; margin:0; padding:20px; }
.top-bar { display:flex; justify-content:space-between; align-items:center; }
.dashboard-btn { background:#ffde59; padding:10px 20px; border-radius:5px; color:#111; font-weight:bold; text-decoration:none; }
.container { margin-top:30px; }
table { width:100%; border-collapse:collapse; margin-bottom:20px; background: rgba(0,0,0,0.4); }
table, th, td { border:1px solid #555; }
th, td { padding:10px; text-align:left; }
th { background:#222; color:#ffde59; }
h2,h3 { color:#ff4d4d; margin-bottom:10px; }
.btn { background: linear-gradient(90deg,#ff512f,#dd2476); padding:10px 20px; border-radius:5px; text-decoration:none; color:white; font-weight:bold; cursor:pointer; margin-right:10px; }
.bottom-btns { margin-top:20px; display:flex; gap:10px; flex-wrap:wrap; }
.timer { margin-top:10px; color:#ffde59; font-size:1rem; }
</style>
</head>
<body>

<div class="container">
    <h2>❌ Payment Failed - Booking Cancelled</h2>
    <p><strong>Booking Date:</strong> <?php echo $booking_date; ?></p>
    <p><strong>Status:</strong> <span style="color:red;">Cancelled</span></p>

    <!-- Bus Details Table -->
    <h3>Bus Details</h3>
    <table>
        <tr><th>Bus Name</th><td><?php echo htmlspecialchars($bus_name); ?></td></tr>
        <tr><th>Bus Number</th><td><?php echo htmlspecialchars($bus_number); ?></td></tr>
        <tr><th>Route</th><td><?php echo htmlspecialchars($route); ?></td></tr>
        <tr><th>Travel Date</th><td><?php echo htmlspecialchars($travel_date); ?></td></tr>
        <tr><th>Departure</th><td><?php echo htmlspecialchars($departure); ?></td></tr>
        <tr><th>Arrival</th><td><?php echo htmlspecialchars($arrival); ?></td></tr>
        <tr><th>Seats</th><td><?php echo htmlspecialchars(implode(',', $seats)); ?></td></tr>
        <tr><th>Seat Type</th><td><?php echo htmlspecialchars($seat_type); ?></td></tr>
    </table>

    <!-- Traveller Details Table -->
    <h3>Traveller Details</h3>
    <table>
        <?php foreach($travellers as $traveller): ?>
        <tr><th>Name</th><td><?php echo htmlspecialchars($traveller['name']); ?></td></tr>
        <tr><th>Email</th><td><?php echo htmlspecialchars($traveller['email']); ?></td></tr>
        <tr><th>Phone</th><td><?php echo htmlspecialchars($traveller['phone']); ?></td></tr>
        <tr><th>Gender</th><td><?php echo htmlspecialchars($traveller['gender']); ?></td></tr>
        <tr><td colspan="2"><hr style="border-color:#555;"></td></tr>
        <?php endforeach; ?>
    </table>

    <!-- Payment Details Table -->
    <h3>Payment Details</h3>
    <table>
        <tr><th>Total Amount</th><td>₹<?php echo $total_amount; ?></td></tr>
        <tr><th>Payment Status</th><td style="color:red;">Failed</td></tr>
        <tr><th>Transaction ID</th><td><?php echo $txn_id; ?></td></tr>
        <tr><th>Coupon</th><td><?php echo htmlspecialchars($coupon); ?></td></tr>
    </table>

    <div class="bottom-btns">
        <a class="dashboard-btn" href="dashboard.php?username=<?= urlencode($username) ?>">Back to Dashboard</a>
    </div>
    <div class="timer" id="timer"></div>
</div>

<script>
let timeLeft = 10; // 10 seconds countdown
function countdown() {
    document.getElementById("timer").textContent = "Redirecting to Dashboard in " + timeLeft + "s...";
    if(timeLeft <= 0){
        window.location.href = "dashboard.php?username=<?= urlencode($username) ?>";
    } else {
        timeLeft--;
        setTimeout(countdown, 1000);
    }
}
window.onload = countdown;
</script>

</body>
</html>
