<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    die("Invalid schedule.");
}

$stmt = $conn->prepare("
    SELECT s.id, b.bus_name, r.source, r.destination, r.fare, s.travel_date, s.departure_time, s.arrival_time
    FROM schedules s
    JOIN buses b ON s.bus_id = b.id
    JOIN routes r ON s.route_id = r.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seat_number = $_POST['seat_number'];
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, seat_number) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $schedule_id, $seat_number);
    if ($stmt->execute()) {
        $success = "Your ticket has been booked!";
    } else {
        $success = "Error booking ticket.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Ticket</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: 'Poppins', sans-serif; }
        .bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .top-nav { position: absolute; top: 20px; right: 30px; display: flex; gap: 20px; }
        .top-nav a { text-decoration: none; color: white; font-weight: 600; background: rgba(0,0,0,0.5); padding: 10px 18px; border-radius: 5px; font-size: 1rem; transition: background 0.3s; }
        .top-nav a:hover { background: rgba(0,0,0,0.8); }
        .container {
            position: relative;
            top: 120px;
            margin: auto;
            width: 90%;
            max-width: 600px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        .container h1 { font-size: 2rem; margin-bottom: 20px; color: #ffde59; }
        .container p { margin: 10px 0; font-size: 1.1rem; }
        form input, form button { padding: 14px; font-size: 1.1rem; margin-top: 10px; border-radius: 5px; border: none; width: 100%; }
        form button { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; font-weight: bold; cursor: pointer; transition: transform 0.2s; }
        form button:hover { transform: scale(1.05); }
        .success { color: #00ff88; margin-top: 10px; }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <a href="index.php">Home</a>
    <a href="my_bookings.php">My Bookings</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h1>Book Your Ticket</h1>
    <?php if ($schedule): ?>
        <p><strong>Bus:</strong> <?php echo $schedule['bus_name']; ?></p>
        <p><strong>Route:</strong> <?php echo $schedule['source'] . " → " . $schedule['destination']; ?></p>
        <p><strong>Fare:</strong> ₹<?php echo $schedule['fare']; ?></p>
        <p><strong>Travel Date:</strong> <?php echo $schedule['travel_date']; ?></p>
        <p><strong>Departure:</strong> <?php echo $schedule['departure_time']; ?></p>
        <form method="post" action="">
            <input type="number" name="seat_number" placeholder="Enter Seat Number" required>
            <button type="submit">Confirm Booking</button>
        </form>
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p>No schedule found.</p>
    <?php endif; ?>
</div>

</body>
</html>
