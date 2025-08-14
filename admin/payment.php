<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../include/db_connect.php';
$admin_id = $_SESSION['admin_id'];

// Main query
if ($admin_id == 3) {
    // Super admin: show all payments
    $query = "SELECT p.id, u.first_name, u.last_name, u.email, bs.bus_name, r.source, r.destination, 
              s.departure_time, s.arrival_time, p.amount, p.created_at, p.payment_status
              FROM payments p
              JOIN bookings b ON p.booking_id = b.id
              JOIN users u ON b.user_id = u.id
              JOIN schedules s ON b.schedule_id = s.id
              JOIN buses bs ON s.bus_id = bs.id
              JOIN routes r ON s.route_id = r.id
              ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    // Normal admin: filter by admin_id
    $query = "SELECT p.id, u.first_name, u.last_name, u.email, bs.bus_name, r.source, r.destination, 
              s.departure_time, s.arrival_time, p.amount, p.created_at, p.payment_status
              FROM payments p
              JOIN bookings b ON p.booking_id = b.id
              JOIN users u ON b.user_id = u.id
              JOIN schedules s ON b.schedule_id = s.id
              JOIN buses bs ON s.bus_id = bs.id
              JOIN routes r ON s.route_id = r.id
              WHERE bs.admin_id = ?
              ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
}

if (!$stmt) {
    die("Prepare failed (main query): (" . $conn->errno . ") " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();

// Total payments query
if ($admin_id == 3) {
    $total_query = "SELECT SUM(p.amount) AS total_amount
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.id
                    JOIN schedules s ON b.schedule_id = s.id
                    JOIN buses bs ON s.bus_id = bs.id
                    WHERE p.payment_status = 'success'";
    $total_stmt = $conn->prepare($total_query);
} else {
    $total_query = "SELECT SUM(p.amount) AS total_amount
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.id
                    JOIN schedules s ON b.schedule_id = s.id
                    JOIN buses bs ON s.bus_id = bs.id
                    WHERE bs.admin_id = ? AND p.payment_status = 'success'";
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->bind_param("i", $admin_id);
}

if (!$total_stmt) {
    die("Prepare failed (total query): (" . $conn->errno . ") " . $conn->error);
}

$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_payment = $total_row['total_amount'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Records</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Styles remain exactly the same as your original */
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: white;
            background: rgba(0,0,0,0.8);
        }
        video.bg-video {
            position: fixed;
            right: 0; bottom: 0;
            min-width: 100%; min-height: 100%;
            z-index: -1;
            filter: brightness(0.4);
        }
        .header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 20px;
            background: rgba(0,0,0,0.7);
            position: relative;
        }
        .welcome {
            margin-right: 10px;
            font-size: 18px;
        }
        .profile-container {
            position: relative;
            cursor: pointer;
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            background: white;
            color: #007bff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 20px;
            user-select: none;
        }
        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: rgba(0,0,0,0.85);
            border-radius: 5px;
            min-width: 160px;
            z-index: 10;
            overflow: hidden;
        }
        .dropdown a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
        }
        .dropdown a:hover {
            background: rgba(255,255,255,0.1);
        }
        .container {
            padding: 20px;
            max-width: 1100px;
            margin: 0 auto;
        }
        h2 {
            margin-bottom: 0;
        }
        .total-payment {
            margin: 10px 0 20px 0;
            font-size: 20px;
            font-weight: bold;
            color: #0f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(0,0,0,0.6);
        }
        th, td {
            padding: 12px 8px;
            border: 1px solid white;
            text-align: center;
            color: white;
        }
        th {
            background: rgba(255,255,255,0.2);
        }
        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        .status-failed {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
<video autoplay muted loop class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="header">
    <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
    <div class="profile-container">
        <div class="profile-pic" onclick="toggleDropdown()">
            <?php echo strtoupper(substr($_SESSION['admin_first_name'], 0, 1)); ?>
        </div>
        <div class="dropdown" id="profileDropdown">
            <a href="dashboard.php">Dashboard</a>
            <a href="settings.php">Settings</a>
            <a href="profile.php">Profile Details</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h2>Payment Records</h2>
    <div class="total-payment">Total Successful Payments: ₹<?php echo number_format($total_payment, 2); ?></div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Bus Name</th>
                <th>Route</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Amount (₹)</th>
                <th>Payment Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { 
                $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                $email = htmlspecialchars($row['email']);
                $status = strtolower($row['payment_status']); // FIX: use correct field name
                $status_class = ($status === 'success') ? 'status-success' : 'status-failed';
            ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $full_name; ?></td>
                    <td><?php echo $email; ?></td>
                    <td><?php echo htmlspecialchars($row['bus_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['source'] . " → " . $row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['arrival_time']); ?></td>
                    <td><?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td> <!-- FIX: use created_at -->
                    <td class="<?php echo $status_class; ?>"><?php echo ucfirst($status); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById("profileDropdown");
    dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
}
window.onclick = function(event) {
    if (!event.target.closest('.profile-container')) {
        document.getElementById("profileDropdown").style.display = "none";
    }
}
</script>
</body>
</html>
