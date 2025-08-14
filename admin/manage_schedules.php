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

// Delete schedule
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $del_stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND admin_id = ?");
    $del_stmt->bind_param("ii", $delete_id, $admin_id);
    $del_stmt->execute();
    header("Location: manage_schedules.php");
    exit();
}

// Add schedule
if (isset($_POST['add_schedule'])) {
    $bus_id = $_POST['bus_id'];
    $route_id = $_POST['route_id'];
    $travel_date = $_POST['travel_date'];
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];

    $query = "INSERT INTO schedules (bus_id, route_id, travel_date, departure_time, arrival_time, admin_id) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisssi", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $admin_id);
    $stmt->execute();

    header("Location: manage_schedules.php");
    exit();
}

// Fetch admin's buses
$buses = $conn->prepare("SELECT id, bus_name FROM buses WHERE admin_id = ?");
$buses->bind_param("i", $admin_id);
$buses->execute();
$buses_result = $buses->get_result();

// Fetch admin's routes
$routes = $conn->prepare("SELECT id, source, destination FROM routes WHERE admin_id = ?");
$routes->bind_param("i", $admin_id);
$routes->execute();
$routes_result = $routes->get_result();

// Fetch schedules
if ($admin_id == 3) {
    // Admin 3 can see all schedules
    $schedules_query = "SELECT s.*, b.bus_name, r.source, r.destination 
                        FROM schedules s
                        JOIN buses b ON s.bus_id = b.id
                        JOIN routes r ON s.route_id = r.id";
    $stmt = $conn->prepare($schedules_query);
} else {
    // Other admins can see only their schedules
    $schedules_query = "SELECT s.*, b.bus_name, r.source, r.destination 
                        FROM schedules s
                        JOIN buses b ON s.bus_id = b.id
                        JOIN routes r ON s.route_id = r.id
                        WHERE s.admin_id = ?";
    $stmt = $conn->prepare($schedules_query);
    $stmt->bind_param("i", $admin_id);
}

$stmt->execute();
$schedules_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Schedules</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            color: white;
            font-family: Arial, sans-serif;
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
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.7);
            align-items: center;
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
        .container {
            padding: 20px;
        }
        table {
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid white;
            padding: 8px;
        }
        th {
            background: rgba(255, 255, 255, 0.2);
        }
        form {
            background: rgba(0, 0, 0, 0.6);
            padding: 15px;
            border-radius: 5px;
        }
        select, input, button {
            padding: 20px;
            margin: 15px;
		
		color : green;
        }
        .btn-delete {
            color: red;
            text-decoration: none;
        }
        .btn-delete:hover {
            text-decoration: underline;
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
    <h2>Manage Schedules</h2>
<form method="POST">
    <select name="bus_id" id="bus_id" required>
        <option value="">Select Bus</option>
        <?php
        $buses->execute();
        $buses_result = $buses->get_result();
        while ($bus = $buses_result->fetch_assoc()) { ?>
            <option value="<?php echo $bus['id']; ?>"><?php echo htmlspecialchars($bus['bus_name']); ?></option>
        <?php } ?>
    </select>
    <select name="route_id" id="route_id" required>
        <option value="">Select Route</option>
        <?php
        $routes->execute();
        $routes_result = $routes->get_result();
        while ($route = $routes_result->fetch_assoc()) { ?>
            <option value="<?php echo $route['id']; ?>">
                <?php echo htmlspecialchars($route['source'] . " to " . $route['destination']); ?>
            </option>
        <?php } ?>
    </select>
    <label for="travel_date">Travel Date:</label>
    <input type="date" name="travel_date" id="travel_date" required>
    <label for="departure_time">Departure Time:</label>
    <input type="time" name="departure_time" id="departure_time" required>

    <label for="arrival_time">Arrival Time:</label>
    <input type="time" name="arrival_time" id="arrival_time" required>

    <button type="submit" name="add_schedule">Add Schedule</button>
</form>


    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Bus</th>
                <th>Route</th>
                <th>Travel Date</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $schedules_result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['bus_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['source'] . " to " . $row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['travel_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['arrival_time']); ?></td>
                    <td><a class="btn-delete" href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this schedule?')">Delete</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function toggleDropdown() {
    document.getElementById("profileDropdown").style.display =
        document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
}
window.onclick = function(event) {
    if (!event.target.closest('.profile-container')) {
        document.getElementById("profileDropdown").style.display = "none";
    }
}
</script>

</body>
</html>