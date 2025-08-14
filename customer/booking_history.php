<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Fetch all bookings (with JOINs)
$query = "
    SELECT b.id AS booking_id, b.status, b.created_at,
           s.travel_date, bu.bus_name, r.source, r.destination,
           IFNULL(p.amount, 0) AS amount,
           IFNULL(p.payment_status, 'N/A') AS payment_status
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY s.travel_date DESC, b.created_at DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking History</title>
    <style>
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif;  color: white; }
            .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
        .top-nav { position: absolute; top: 20px; right: 30px; display: flex; gap: 20px; }
        .top-nav a { text-decoration: none; color: #0ff; font-weight: 600; background: rgba(0,0,0,0.5); padding: 10px 18px; border-radius: 5px; }
        .top-nav a:hover { background: rgba(0,0,0,0.8); color: #fff; }
        .container { position: relative; top: 120px; margin: auto; width: 90%; max-width: 1000px; background: rgba(0,0,0,0.6); padding: 20px; border-radius: 10px; }
        h1 { text-align: center; color: #ffde59; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
        select { padding: 8px; border-radius: 5px; border: none; margin-bottom: 10px; }
        .no-data { text-align: center; color: #ffde59; margin: 15px 0; }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h1>Booking History</h1>

    <label for="filter">Filter by Status: </label>
    <select id="filter" onchange="filterTable()">
        <option value="all">All</option>
        <option value="upcoming">Upcoming</option>
        <option value="past">Past</option>
        <option value="cancelled">Cancelled</option>
    </select>

    <table id="bookingTable">
        <tr>
            <th>Booking ID</th>
            <th>Bus</th>
            <th>Route</th>
            <th>Travel Date</th>
            <th>Status</th>
            <th>Amount</th>
            <th>Payment Status</th>
            <th>Booked On</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $rowStatus = '';
                    if ($row['status'] == 'cancelled') {
                        $rowStatus = 'cancelled';
                    } elseif ($row['travel_date'] >= $today) {
                        $rowStatus = 'upcoming';
                    } else {
                        $rowStatus = 'past';
                    }
                ?>
                <tr class="row <?php echo $rowStatus; ?>">
                    <td><?php echo $row['booking_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['bus_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['source']) . " → " . htmlspecialchars($row['destination']); ?></td>
                    <td><?php echo $row['travel_date']; ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td><?php echo $row['amount']; ?></td>
                    <td><?php echo $row['payment_status']; ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="8" class="no-data">No bookings found.</td></tr>
        <?php endif; ?>
    </table>
</div>

<script>
function filterTable() {
    let filter = document.getElementById("filter").value;
    let rows = document.querySelectorAll("#bookingTable .row");

    rows.forEach(row => {
        if (filter === "all" || row.classList.contains(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
}
</script>

</body>
</html>
