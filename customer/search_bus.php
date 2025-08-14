<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$phone = $_SESSION['phone'] ?? '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "User";
$customer_name = ($first_name && $last_name) ? htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name) : 'Welcome Customer';
$source = isset($_GET['source']) ? trim($_GET['source']) : '';
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$travel_date = isset($_GET['travel_date']) ? trim($_GET['travel_date']) : '';

$buses = [];
if (!empty($source) && !empty($destination) && !empty($travel_date)) {
    $stmt = $conn->prepare("
        SELECT 
            s.id AS schedule_id,
            b.bus_name,
            b.bus_number,
            r.source,
            r.destination,
            r.fare,
            s.travel_date,
            s.departure_time,
            s.arrival_time,
            r.distance_km
        FROM schedules s
        JOIN buses b ON s.bus_id = b.id
        JOIN routes r ON s.route_id = r.id
        WHERE LOWER(r.source) = LOWER(?)
          AND LOWER(r.destination) = LOWER(?)
          AND s.travel_date = ?
        ORDER BY r.fare ASC
    ");
    $stmt->bind_param("sss", $source, $destination, $travel_date);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $buses[] = $row;
    }
    $stmt->close();
} else {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Available Buses</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        margin: 0; padding: 0; background: #111; color: white;
    }
    /* Top navigation/profile */
    .top-nav {
        position: fixed;
        top: 15px;
        right: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        z-index: 1000;
    }
    .welcome-text {
        color: #0ff;
        font-weight: 600;
    }
    .profile-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #ffde59;
        color: #111;
        font-weight: bold;
        border: none;
        cursor: pointer;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        justify-content: center;
        user-select: none;
        position: relative;
    }
    .dropdown {
        position: absolute;
        right: 0;
        top: 50px;
        background: rgba(0,0,0,0.9);
        border-radius: 8px;
        display: none;
        flex-direction: column;
        padding: 10px 0;
        min-width: 180px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.7);
    }
    .dropdown a {
        color: #0ff;
        padding: 12px 20px;
        text-decoration: none;
        border-radius: 0;
        transition: background 0.3s;
        display: block;
    }
    .dropdown a:hover {
        background: rgba(255,255,255,0.15);
        color: #fff;
    }

    /* Table styles */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 100px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        background: rgba(0,0,0,0.6);
        border-radius: 10px;
        overflow: hidden;
    }
    th, td {
        padding: 12px;
        text-align: center;
        border-bottom: 1px solid #444;
        color: white;
    }
    th {
        background: linear-gradient(90deg, #ff512f, #dd2476);
    }
    .book-btn {
        background: #ff512f;
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: background 0.3s ease;
    }
    .book-btn:hover {
        background: #dd2476;
    }

    /* Back to Search button */
    .back-btn {
        display: block;
        width: 180px;
        margin: 30px auto 50px;
        padding: 12px 20px;
        background: linear-gradient(90deg, #ff512f, #dd2476);
        color: white;
        text-align: center;
        text-decoration: none;
        font-weight: 700;
        border-radius: 25px;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .back-btn:hover {
        transform: scale(1.05);
    }
</style>
</head>
<body>

<div class="top-nav">
    <div class="welcome-text">Welcome, <?php echo htmlspecialchars($customer_name); ?></div>
    <button class="profile-btn" id="profileBtn"><?php echo strtoupper(substr($customer_name, 0, 1)); ?></button>
    <div class="dropdown" id="profileMenu">
        <a href="profile.php">Profile Details</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<h1 style="text-align:center; margin-top: 60px;">
    Buses Available from <?php echo htmlspecialchars($source); ?> to <?php echo htmlspecialchars($destination); ?> on <?php echo htmlspecialchars($travel_date); ?>
</h1>

<?php if (!empty($buses)): ?>
<table>
    <thead>
        <tr>
            <th>Bus Name</th>
            <th>Bus Number</th>
            <th>Route</th>
            <th>Travel Date</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Fare (₹)</th>
            <th>Distance (km)</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($buses as $bus): ?>
        <tr>
            <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
            <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
            <td><?php echo htmlspecialchars($bus['source'] . " → " . $bus['destination']); ?></td>
            <td><?php echo htmlspecialchars($bus['travel_date']); ?></td>
            <td><?php echo htmlspecialchars($bus['departure_time']); ?></td>
            <td><?php echo htmlspecialchars($bus['arrival_time']); ?></td>
            <td>₹<?php echo htmlspecialchars($bus['fare']); ?></td>
            <td><?php echo htmlspecialchars($bus['distance_km']); ?></td>
            <td>
                <a class="book-btn" href="booking_form.php?
                   schedule_id=<?php echo $bus['schedule_id']; ?>
                   &bus_name=<?php echo urlencode($bus['bus_name']); ?>
                   &bus_number=<?php echo urlencode($bus['bus_number']); ?>
                   &route=<?php echo urlencode($bus['source'].' → '.$bus['destination']); ?>
                   &travel_date=<?php echo urlencode($bus['travel_date']); ?>
                   &fare=<?php echo urlencode($bus['fare']); ?>
                   &departure=<?php echo urlencode($bus['departure_time']); ?>
                   &arrival=<?php echo urlencode($bus['arrival_time']); ?>">Book Now</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="text-align:center; margin-top: 60px; font-size: 1.1rem; color: #ffde59;">
    No buses available on this route for the selected date.
</p>
<?php endif; ?>

<a href="dashboard.php" class="back-btn">← Back to Search</a>

<script>
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    profileBtn.addEventListener('click', () => {
        if (profileMenu.style.display === 'flex') {
            profileMenu.style.display = 'none';
        } else {
            profileMenu.style.display = 'flex';
            profileMenu.style.flexDirection = 'column';
        }
    });

    // Close dropdown if clicked outside
    document.addEventListener('click', function(event) {
        if (!profileBtn.contains(event.target) && !profileMenu.contains(event.target)) {
            profileMenu.style.display = 'none';
        }
    });
</script>

</body>
</html>
