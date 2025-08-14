<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    die("Invalid booking ID.");
}

$stmt = $conn->prepare("
    SELECT b.id, u.name, bu.bus_name, r.source, r.destination, s.travel_date, s.departure_time, s.arrival_time, b.seat_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Ticket</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: 'Poppins', sans-serif; }
        .bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            width: 90%;
            max-width: 600px;
        }
        h1 { color: #ffde59; font-size: 2rem; margin-bottom: 20px; }
        p { font-size: 1.1rem; margin: 5px 0; }
        .btn { background: linear-gradient(90deg, #ff512f, #dd2476); padding: 12px 20px; border-radius: 5px; color: white; text-decoration: none; font-size: 1.1rem; }
        .btn:hover { background: linear-gradient(90deg, #dd2476, #ff512f); }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus.mp4" type="video/mp4">
</video>

<div class="container">
    <h1>Your Ticket</h1>
    <?php if ($ticket): ?>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($ticket['name']); ?></p>
        <p><strong>Bus:</strong> <?php echo htmlspecialchars($ticket['bus_name']); ?></p>
        <p><strong>Route:</strong> <?php echo htmlspecialchars($ticket['source']); ?> → <?php echo htmlspecialchars($ticket['destination']); ?></p>
        <p><strong>Date:</strong> <?php echo htmlspecialchars($ticket['travel_date']); ?></p>
        <p><strong>Departure:</strong> <?php echo htmlspecialchars($ticket['departure_time']); ?></p>
        <p><strong>Seat:</strong> <?php echo htmlspecialchars($ticket['seat_number']); ?></p>
        <a class="btn" href="my_bookings.php">Back to Bookings</a>
    <?php else: ?>
        <p>Ticket not found.</p>
    <?php endif; ?>
</div>

</body>
</html>
