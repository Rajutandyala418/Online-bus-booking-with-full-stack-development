<?php
include(__DIR__ . '/../include/db_connect.php');

// ✅ Accept username from POST or GET
$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) {
    die("Username not provided.");
}

// ✅ Fetch user_id from users table
$userQuery = "SELECT id, username FROM users WHERE username = ? LIMIT 1";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
$userStmt->close();

if (!$userRow) {
    die("User not found.");
}

$user_id = $userRow['id'];
$username = $userRow['username'];

// ✅ Query to get payment history
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
if (!$stmt) {
    die("SQL Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// ✅ Collect payment records
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
    <meta charset="UTF-8" />
    <title>Payment History</title>
    <style>
        body, html {
            margin: 0; padding: 0; font-family: 'Poppins', sans-serif;
            color: white;
        }
        .bg-video {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            object-fit: cover; z-index: -1;
        }
        .top-nav {
            position: absolute; top: 20px; right: 30px; display: flex; gap: 20px;
        }
        .top-nav a {
            text-decoration: none; color: #0ff; font-weight: 600;
            background: rgba(0,0,0,0.5); padding: 10px 18px; border-radius: 5px;
        }
        .top-nav a:hover {
            background: rgba(0,0,0,0.8); color: #fff;
        }
        .container {
            position: relative; top: 120px; margin: auto; width: 90%;
            max-width: 1000px; background: rgba(0,0,0,0.6); padding: 20px; border-radius: 10px;
        }
        h1 {
            text-align: center; color: #ffde59;
        }
        table {
            width: 100%; border-collapse: collapse; margin-top: 10px;
        }
        th, td {
            padding: 10px; text-align: center; border-bottom: 1px solid #ddd;
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
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4" />
</video>
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
                        <td><?= htmlspecialchars($payment['payment_id']) ?></td>
                        <td><?= htmlspecialchars($payment['booking_id']) ?></td>
                        <td><?= htmlspecialchars($payment['bus_name']) ?></td>
                        <td><?= htmlspecialchars($payment['source']) ?> → <?= htmlspecialchars($payment['destination']) ?></td>
                        <td><?= htmlspecialchars($payment['travel_date']) ?></td>
                        <td><?= number_format($payment['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($payment['payment_status']) ?></td>
                        <td><?= htmlspecialchars($payment['booking_date']) ?></td>
                        <td><?= htmlspecialchars($payment['payment_date']) ?></td>
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
