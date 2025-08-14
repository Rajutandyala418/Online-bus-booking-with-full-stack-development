<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$username = $_SESSION['username'] ?? 'User';

// Use bus and traveller details if available
$bus_details = $_SESSION['bus_details'] ?? [];
$traveller_details = $_SESSION['traveller_details'] ?? [];

$bus_name    = $bus_details['bus_name']    ?? 'Express Travels';
$bus_number  = $bus_details['bus_number']  ?? 'TN-10-1234';
$route       = $bus_details['route']       ?? 'Chennai → Bangalore';
$travel_date = $bus_details['travel_date'] ?? date('Y-m-d');
$departure   = $bus_details['departure']   ?? '08:00 AM';
$arrival     = $bus_details['arrival']     ?? '02:00 PM';
$seats       = $bus_details['seats']       ?? 'A1, A2';
$seat_type   = $bus_details['seat_type']   ?? 'Seater';
$fare        = 0; // Payment failed, so amount = 0

$traveller_name  = $traveller_details['name']  ?? 'John Doe';
$traveller_email = $traveller_details['email'] ?? 'john@example.com';
$traveller_phone = $traveller_details['phone'] ?? '9876543210';

$booking_date = date('Y-m-d H:i:s');
$status = 'Cancelled';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Failed</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #111;
            color: #fff;
            margin: 0;
            padding: 20px;
        }
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dashboard-btn {
            background: #ffde59;
            padding: 10px 20px;
            border-radius: 5px;
            color: #111;
            font-weight: bold;
            text-decoration: none;
        }
        .container {
            margin-top: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: rgba(0, 0, 0, 0.4);
        }
        table, th, td {
            border: 1px solid #555;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background: #222;
            color: #ffde59;
        }
        h2, h3 {
            color: #ff4d4d;
            margin-bottom: 10px;
        }
        .btn {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            margin-right: 10px;
            cursor: pointer;
        }
        .bottom-btns {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .timer {
            margin-top: 10px;
            color: #ffde59;
            font-size: 1rem;
        }
    </style>
    <script>
        let timeLeft = 5;
        function countdown() {
            document.getElementById("timer").textContent =
                "Redirecting to Dashboard in " + timeLeft + "s...";
            if (timeLeft <= 0) {
                window.location.href = "dashboard.php";
            } else {
                timeLeft--;
                setTimeout(countdown, 1000);
            }
        }
        window.onload = countdown;
    </script>
</head>
<body>
    <div class="top-bar">
        <a class="dashboard-btn" href="dashboard.php">Back to Dashboard</a>
        <div>Welcome, <?php echo htmlspecialchars($username); ?></div>
    </div>

    <div class="container">
        <h2>❌ Payment Failed - Booking Cancelled</h2>
        <p><strong>Booking Date:</strong> <?php echo $booking_date; ?></p>
        <p><strong>Status:</strong> <span style="color:red;">Cancelled</span></p>

        <h3>Bus Details</h3>
        <table>
            <tr><th>Bus Name</th><td><?php echo htmlspecialchars($bus_name); ?></td></tr>
            <tr><th>Bus Number</th><td><?php echo htmlspecialchars($bus_number); ?></td></tr>
            <tr><th>Route</th><td><?php echo htmlspecialchars($route); ?></td></tr>
            <tr><th>Travel Date</th><td><?php echo htmlspecialchars($travel_date); ?></td></tr>
            <tr><th>Departure</th><td><?php echo htmlspecialchars($departure); ?></td></tr>
            <tr><th>Arrival</th><td><?php echo htmlspecialchars($arrival); ?></td></tr>
            <tr><th>Seats</th><td><?php echo htmlspecialchars($seats); ?></td></tr>
            <tr><th>Seat Type</th><td><?php echo htmlspecialchars($seat_type); ?></td></tr>
        </table>

        <h3>Traveller Details</h3>
        <table>
            <tr><th>Name</th><td><?php echo htmlspecialchars($traveller_name); ?></td></tr>
            <tr><th>Email</th><td><?php echo htmlspecialchars($traveller_email); ?></td></tr>
            <tr><th>Phone</th><td><?php echo htmlspecialchars($traveller_phone); ?></td></tr>
        </table>

        <h3>Payment Details</h3>
        <table>
            <tr><th>Total Fare</th><td>₹0</td></tr>
            <tr><th>Status</th><td><span style="color:red;">Payment Not Paid</span></td></tr>
        </table>

        <div class="bottom-btns">
            <a class="btn" href="dashboard.php">Go to Dashboard</a>
        </div>
        <div class="timer" id="timer"></div>
    </div>
</body>
</html>
