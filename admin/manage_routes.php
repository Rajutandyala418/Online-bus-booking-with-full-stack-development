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
$admin_first_name = $_SESSION['admin_first_name'] ?? '';
$admin_last_name = $_SESSION['admin_last_name'] ?? '';
$admin_name = trim($admin_first_name . ' ' . $admin_last_name);

// Handle Delete Route
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($admin_id == 3) {
        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $delete_id, $admin_id);
    }
    $stmt->execute();
    header("Location: manage_routes.php");
    exit();
}

// Handle Add Route
if (isset($_POST['add_route'])) {
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $fare = $_POST['fare'];
    $distance_km = $_POST['distance_km'] ?: null;
    $approx_duration = $_POST['approx_duration'] ?: null;

    if (strcasecmp($source, $destination) === 0) {
        echo "<script>alert('Source and destination cannot be the same.');</script>";
    } else {
        $query = "INSERT INTO routes (source, destination, fare, distance_km, approx_duration, admin_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssddsi", $source, $destination, $fare, $distance_km, $approx_duration, $admin_id);
        $stmt->execute();
        header("Location: manage_routes.php");
        exit();
    }
}

// Fetch routes
if ($admin_id == 3) {
    $routes_query = "SELECT * FROM routes ORDER BY id DESC";
    $stmt = $conn->prepare($routes_query);
} else {
    $routes_query = "SELECT * FROM routes WHERE admin_id = ? ORDER BY id DESC";
    $stmt = $conn->prepare($routes_query);
    $stmt->bind_param("i", $admin_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Routes</title>
    <style>
        /* Reset & base */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #121212;
            color: white;
            min-height: 100vh;
        }
        /* Background video */
        video.bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            filter: brightness(0.3);
        }
        /* Header */
        .header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 12px 24px;
            background: rgba(0, 0, 0, 0.75);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .welcome {
            margin-right: 12px;
            font-size: 18px;
            font-weight: 600;
        }
        .profile-container {
            position: relative;
            cursor: pointer;
        }
        .profile-pic {
            background: #007bff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            font-weight: bold;
            font-size: 22px;
            display: flex;
            justify-content: center;
            align-items: center;
            user-select: none;
        }
        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: rgba(0, 0, 0, 0.85);
            border-radius: 6px;
            min-width: 160px;
            z-index: 30;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.8);
        }
        .dropdown a {
            display: block;
            padding: 12px 16px;
            color: white;
            text-decoration: none;
            transition: background 0.2s;
        }
        .dropdown a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        /* Main container */
        .container {
            max-width: 1100px;
            margin: 30px auto 40px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 0 15px rgba(0,0,0,0.6);
        }
        h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 28px;
            color: #00bfff;
            text-align: center;
        }
        /* Form */
        form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            justify-content: center;
        }
        form input, form button {
            font-size: 16px;
            padding: 10px 14px;
            border-radius: 6px;
            border: none;
            outline: none;
        }
        form input {
            background: pink;
            color: blue;
            min-width: 150px;
            flex: 1 1 200px;
            border: 1px solid #444;
            transition: border-color 0.3s;
        }
        form input:focus {
            border-color: #00bfff;
            background: yellow;
        }
        form button {
            background: #007bff;
            color: white;
            cursor: pointer;
            min-width: 140px;
            flex: 0 0 auto;
            transition: background 0.3s;
            font-weight: 600;
            border: 1px solid #007bff;
        }
        form button:hover {
            background: #0056b3;
            border-color: #0056b3;
        }
        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #222;
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background: #007bff;
        }
        th, td {
            padding: 14px 12px;
            text-align: center;
            border-bottom: 1px solid #333;
        }
        th {
            color: white;
            font-weight: 600;
        }
        tbody tr:hover {
            background: rgba(0, 191, 255, 0.1);
        }
        .btn-delete {
            color: #dc3545;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s;
        }
        .btn-delete:hover {
            color: #a71d2a;
        }
        /* Responsive */
        @media (max-width: 768px) {
            form {
                flex-direction: column;
                gap: 12px;
            }
            form input, form button {
                flex: 1 1 100%;
                min-width: unset;
            }
            th, td {
                padding: 10px 8px;
                font-size: 14px;
            }
        }
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("profileDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }
        window.onclick = function(event) {
            if (!event.target.closest('.profile-container')) {
                const dropdown = document.getElementById("profileDropdown");
                if (dropdown.style.display === "block") {
                    dropdown.style.display = "none";
                }
            }
        }
        function validateRouteForm() {
            let source = document.forms["routeForm"]["source"].value.trim();
            let destination = document.forms["routeForm"]["destination"].value.trim();
            if (source.toLowerCase() === destination.toLowerCase()) {
                alert("Source and destination cannot be the same.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <video autoplay muted loop playsinline class="bg-video">
        <source src="../videos/bus.mp4" type="video/mp4" />
    </video>

    <header class="header">
        <div class="welcome">Welcome, <?php echo htmlspecialchars($admin_name); ?></div>
        <div class="profile-container">
            <div class="profile-pic" onclick="toggleDropdown()">
                <?php echo strtoupper(substr($admin_first_name, 0, 1)); ?>
            </div>
            <div class="dropdown" id="profileDropdown">
                <a href="dashboard.php">Dashboard</a>
                <a href="settings.php">Settings</a>
                <a href="profile.php">Profile Details</a>
                <a href="logout.php" style="color:#dc3545;">Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <h2>Manage Routes</h2>
        <form name="routeForm" method="POST" onsubmit="return validateRouteForm()">
            <input type="text" name="source" placeholder="Source" required />
            <input type="text" name="destination" placeholder="Destination" required />
            <input type="number" step="0.01" name="fare" placeholder="Fare (₹)" required />
            <input type="number" step="0.01" name="distance_km" placeholder="Distance (km)" />
            <input type="time" name="approx_duration" placeholder="Approx Duration" />
            <button type="submit" name="add_route">Add Route</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Fare (₹)</th>
                    <th>Distance (km)</th>
                    <th>Approx Duration</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['source']); ?></td>
                        <td><?php echo htmlspecialchars($row['destination']); ?></td>
                        <td>₹<?php echo number_format($row['fare'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['distance_km']); ?></td>
                        <td><?php echo htmlspecialchars($row['approx_duration']); ?></td>
                        <td>
                            <a href="manage_routes.php?delete_id=<?php echo $row['id']; ?>" 
                               class="btn-delete"
                               onclick="return confirm('Are you sure you want to delete this route?');">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($result->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 15px;">No routes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
