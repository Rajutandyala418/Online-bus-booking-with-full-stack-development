<?php
include(__DIR__ . '/../include/db_connect.php');

$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) die("Username not provided.");

// Fetch user details
$userQuery = "SELECT id, username FROM users WHERE username = ? LIMIT 1";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
$userStmt->close();

if (!$userRow) die("User not found.");

$user_id = $userRow['id'];
$username = $userRow['username'];

// Fetch payment history
$query = "
    SELECT p.id AS payment_id, b.id AS booking_id, bu.bus_name, 
           b.source, b.destination, 
           s.travel_date, p.amount, p.payment_status, 
           b.created_at AS booking_date, p.created_at AS payment_date
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    WHERE b.user_id = ?
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total_payment = 0;
$payments = [];
while ($row = $result->fetch_assoc()) {
    $payments[] = $row;
    $total_payment += floatval($row['amount']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<title>Payment History</title>

<style>
body, html {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    color: white;
    min-height: 100vh;
    overflow-x: hidden;
}
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    border-radius: 8px;
    scrollbar-width: thin;
}


/* Removed video styling */
.top-nav {
    position: fixed;
    top: 10px;
    right: 10px;
    gap: 10px;
    z-index: 1000;
}
.top-nav a {
    padding: 7px 14px;
    font-size: 0.9rem;
    border-radius: 6px;
background:black;
    color:white;
}

.top-nav a:hover {
    color: #fff;
}

.container {
    margin: 110px auto 40px;
    width: 95%;
    max-width: 1300px;
    background: rgba(0,0,0,0.65);
    padding: 18px;
    border-radius: 12px;
    backdrop-filter: blur(6px);
}

h1 {
    text-align: center; color: #ffde59;
}

table {
    width: 100%; border-collapse: collapse; margin-top: 10px;
}
th, td {
    padding: 7px;
    font-size: 0.85rem;
    text-align: center;
    white-space: nowrap;
}

th {
    background: linear-gradient(90deg, #ff512f, #dd2476); color: white;
}
.no-data {
    text-align: center; color: #ffde59; margin: 15px 0;
}
.total-row td {
    font-weight: bold; background: rgba(255, 222, 89, 0.2); color: #ffde59;
    text-align: right; padding-right: 15px;
}
@media (max-width: 768px) {

    h1 { font-size: 24px; }

    .container {
        padding: 12px;
        width: 100%;
        max-width:1400px;
        margin-top: 90px;
    }

    th, td {
        font-size: 0.78rem;
        padding: 6px;
        max-width:1200px;
    }

    .top-nav a {
        padding: 6px 10px;
        font-size: 0.75rem;
    }

    table {
        min-width: 500px;  /* force scroll rather than squeezing */
    }
}
/* 🔥 AUTO ZOOM FIX FOR MOBILE WITHOUT META VIEWPORT */
@media (max-width: 768px) {
    body {
        zoom: 0.8;           /* Chrome, Edge, Android */
    }
}

</style>
</head>

<body>

<div class="top-nav">
    <a href="booking_history.php?username=<?= urlencode($username) ?>">Booking History</a>
    <a href="dashboard.php?username=<?= urlencode($username) ?>">Dashboard</a>
</div>

<div class="container">
    <h1>Payment History</h1>

    <table>
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Booking ID</th>
                <th>Bus</th>
                <th>Route</th>
                <th>Travel Date</th>
                <th>Amount (₹)</th>
                <th>Payment Status</th>
                <th>Booking Date</th>
                <th>Payment Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($payments) > 0): ?>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= $payment['payment_id'] ?></td>
                    <td><?= $payment['booking_id'] ?></td>
                    <td><?= $payment['bus_name'] ?></td>
                    <td><?= $payment['source'] ?> → <?= $payment['destination'] ?></td>
                    <td><?= $payment['travel_date'] ?></td>
                    <td><?= number_format($payment['amount'], 2) ?></td>
                    <td><?= $payment['payment_status'] ?></td>
                    <td><?= $payment['booking_date'] ?></td>
                    <td><?= $payment['payment_date'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="5" style="text-align:right;">Total Payment:</td>
                <td colspan="4" style="text-align:left;">₹ <?= number_format($total_payment, 2) ?></td>
            </tr>
        <?php else: ?>
            <tr><td colspan="9" class="no-data">No payment records found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
