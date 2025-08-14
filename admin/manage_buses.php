<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_name = (isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name'])) 
    ? htmlspecialchars($_SESSION['admin_first_name']) . ' ' . htmlspecialchars($_SESSION['admin_last_name']) 
    : 'Welcome Admin';

// Session expiry (5 minutes)
if (!isset($_SESSION['session_expiry'])) {
    $_SESSION['session_expiry'] = time() + 300;
}
$remaining_time = $_SESSION['session_expiry'] - time();
if ($remaining_time <= 0) {
    header("Location: logout.php?timeout=1");
    exit();
}

// Database connection
require_once __DIR__ . '/../include/db_connect.php';

// Handle bus deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM buses WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $delete_id, $admin_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_buses.php");
    exit();
}

// Add new bus
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bus_name'], $_POST['bus_number'])) {
    $bus_name = trim($_POST['bus_name']);
    $bus_number = trim($_POST['bus_number']);

    if ($bus_name !== "" && $bus_number !== "") {
        $stmt = $conn->prepare("INSERT INTO buses (bus_name, bus_number, admin_id) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $bus_name, $bus_number, $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: manage_buses.php");
    exit();
}
if ($admin_id == 3) {
    $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
} else {
    $stmt = $conn->prepare("DELETE FROM buses WHERE id = ? AND admin_id = ?");
    $stmt->bind_param("ii", $delete_id, $admin_id);
}

// Fetch buses
if ($admin_id == 3) {
    // Manager sees all buses
    $stmt = $conn->prepare("SELECT id, bus_name, bus_number FROM buses");
} else {
    // Other admins see only their buses
    $stmt = $conn->prepare("SELECT id, bus_name, bus_number FROM buses WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
}

$stmt->execute();
$result = $stmt->get_result();
$buses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Buses</title>
    <style>
        html, body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .top-nav {
            position: absolute;
            top: 20px; right: 30px;
            display: flex; gap: 15px; align-items: center;
        }
        .profile-menu {
            position: relative;
            display: inline-block;
        }
        .profile-circle {
            width: 45px; height: 45px;
            background: #ffde59;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: black;
            font-size: 1.2rem;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            background: rgba(0,0,0,0.8);
            border-radius: 6px;
            min-width: 150px;
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }
        .dropdown-content a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
            transition: background 0.2s;
        }
        .dropdown-content a:hover {
            background: rgba(255,255,255,0.1);
        }
        .container {
            margin-top: 120px;
            width: 90%; max-width: 800px;
            margin-left: auto; margin-right: auto;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 30px;
            border-radius: 10px;
        }
        h1 {
            color: #ffde59;
            text-align: center;
        }
        form {
            display: flex; 
            justify-content: center; 
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        input[type="text"] {
            padding: 8px;
            border-radius: 4px;
            border: none;
            width: 200px;
        }
        button {
            padding: 8px 15px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            color: black;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #ffde59;
        }
        .delete-btn {
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <span style="color:blue; font-weight:bold;"><?php echo "Welcome " . $admin_name; ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn">
            <?php echo strtoupper(substr($_SESSION['admin_first_name'], 0, 1)); ?>
        </div>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="dashboard.php">Dashboard</a>
            <a href="settings.php">Settings</a>
            <a href="admin_details.php">Profile Details</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Manage Buses</h1>
    <form method="POST">
        <label>Bus Name:</label>
        <input type="text" name="bus_name" required>
        <label>Bus Number:</label>
        <input type="text" name="bus_number" required>
        <button type="submit">Add Bus</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Bus Name</th>
            <th>Bus Number</th>
            <th>Action</th>
        </tr>
        <?php foreach ($buses as $bus): ?>
        <tr>
            <td><?php echo $bus['id']; ?></td>
            <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
            <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
            <td>
                <a href="manage_buses.php?delete_id=<?php echo $bus['id']; ?>" onclick="return confirm('Are you sure you want to delete this bus?');">
                    <button type="button" class="delete-btn">Delete</button>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    profileBtn.addEventListener('click', function (e) {
        dropdownMenu.style.display = 
            dropdownMenu.style.display === 'block' ? 'none' : 'block';
        e.stopPropagation();
    });
    document.addEventListener('click', function () {
        dropdownMenu.style.display = 'none';
    });
</script>

</body>
</html>
