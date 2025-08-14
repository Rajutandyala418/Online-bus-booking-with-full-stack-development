<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('C:/xampp/htdocs/y22cm171/include/db_connect.php');

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = "";

// Get user name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Get upcoming bookings
$bookings = [];
$query = "
    SELECT bookings.id AS booking_id, bookings.seat_number, bookings.booking_date, bookings.status,
           buses.bus_name, routes.source, routes.destination, schedules.travel_date, schedules.departure_time
    FROM bookings
    JOIN schedules ON bookings.schedule_id = schedules.id
    JOIN buses ON schedules.bus_id = buses.id
    JOIN routes ON schedules.route_id = routes.id
    WHERE bookings.user_id = ? AND schedules.travel_date >= CURDATE()
    ORDER BY schedules.travel_date ASC, schedules.departure_time ASC
    LIMIT 5
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to RVR Travels</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero-dashboard {
            position: relative;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            overflow: hidden;
        }

        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .hero-content {
            background: rgba(0, 0, 0, 0.5);
            padding: 30px;
            border-radius: 10px;
            animation: fadeInDown 2s ease forwards;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #ffde59;
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .dashboard-links {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .dashboard-links .btn-large {
            text-decoration: none;
            padding: 15px 30px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: #fff;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            transition: transform 0.2s ease, background 0.3s;
        }

        .dashboard-links .btn-large:hover {
            transform: scale(1.05);
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .upcoming-section {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 8px;
            color: white;
            text-align: center;
        }

        .upcoming-section h2 {
            color: #ffde59;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Video Background Hero Section -->
<section class="hero-dashboard">
    <video autoplay muted loop playsinline class="hero-video">
        <source src="C:/xampp/htdocs/y22cm171/bus_booking/videos/bus.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    <div class="hero-content">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
        <p>Your travel companion for quick and easy bus bookings.</p>
        <div class="dashboard-links">
            <a href="search_bus.php" class="btn-large">Search Buses</a>
            <a href="my_bookings.php" class="btn-large">View My Bookings</a>
        </div>
    </div>
</section>

<!-- Upcoming Bookings Section -->
<div class="upcoming-section">
    <h2>Upcoming Bookings</h2>
    <?php if (!empty($bookings)): ?>
        <table class="bus-table">
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Seat No.</th>
                    <th>Travel Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['bus_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['source']); ?></td>
                        <td><?php echo htmlspecialchars($b['destination']); ?></td>
                        <td><?php echo $b['seat_number']; ?></td>
                        <td><?php echo htmlspecialchars($b['travel_date']); ?> (<?php echo htmlspecialchars($b['departure_time']); ?>)</td>
                        <td><?php echo ucfirst($b['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No upcoming bookings found.</p>
    <?php endif; ?>
</div>

<?php include('C:/xampp/htdocs/y22cm171/include/footer.php'); ?>

</body>
</html>
