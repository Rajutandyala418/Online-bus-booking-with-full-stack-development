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

// Fetch bookings
if ($admin_id == 3) {
    // Super admin sees all bookings
    $query = "SELECT 
      b.id AS booking_id,
      b.user_id, 
      CONCAT(u.first_name, ' ', u.last_name) AS user_name,
      b.schedule_id,
      s.departure_time,
      s.arrival_time,
      r.source AS from_location,
      r.destination AS to_location,
      bs.bus_name,
      b.seat_number,
      b.status,
      b.created_at
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bs ON s.bus_id = bs.id
    JOIN routes r ON s.route_id = r.id
    ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    // Normal admin sees only their bookings
    $query = "SELECT 
      b.id AS booking_id,
      b.user_id, 
      CONCAT(u.first_name, ' ', u.last_name) AS user_name,
      b.schedule_id,
      s.departure_time,
      s.arrival_time,
      r.source AS from_location,
      r.destination AS to_location,
      bs.bus_name,
      b.seat_number,
      b.status,
      b.created_at
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bs ON s.bus_id = bs.id
    JOIN routes r ON s.route_id = r.id
    WHERE bs.admin_id = ?
    ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $admin_id);
}

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: white;
        }
        video.bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            filter: brightness(0.4);
        }
        .header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.7);
            position: relative;
        }
        .welcome {
            margin-right: 10px;
        }
        .profile-container {
            position: relative;
            cursor: pointer;
        }
        .profile {
            border-radius: 50%;
            width: 40px;
            height: 40px;
        }
        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: rgba(0,0,0,0.85);
            border-radius: 5px;
            overflow: hidden;
            min-width: 150px;
            z-index: 10;
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
    cursor: pointer;
    user-select: none;
}

        .main-content {
            padding: 20px;
            max-width: 1100px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 10px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        th, td {
            border: 1px solid white;
            padding: 8px;
            text-align: center;
        }
        th {
            background: rgba(0, 123, 255, 0.8);
        }
        .status-confirmed { color: #28a745; font-weight: bold; }    /* Green */
        .status-cancelled { color: #dc3545; font-weight: bold; }    /* Red */
        .status-failed { color: #6c757d; font-weight: bold; }       /* Gray */
        .status-completed { color: #007bff; font-weight: bold; }    /* Blue */
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
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="main-content">
    <h2>Manage Bookings</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>Bus Name</th>
                <th>Route</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Seat</th>
                <th>Status</th>
                <th>Booked At</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) {
                $status_class = '';
                switch (strtolower($row['status'])) {
                    case 'confirmed': $status_class = 'status-confirmed'; break;
                    case 'cancelled': $status_class = 'status-cancelled'; break;
                    case 'failed': $status_class = 'status-failed'; break;
                    case 'completed': $status_class = 'status-completed'; break;
                    default: $status_class = '';
                }
            ?>
                <tr>
                    <td><?php echo $row['booking_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['from_location'] . " → " . $row['to_location']); ?></td>
                    <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['arrival_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['seat_number']); ?></td>
                    <td class="<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

window.onclick = function(event) {
    if (!event.target.closest('.profile-container')) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
}
</script>

</body>
</html>
