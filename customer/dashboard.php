<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$phone = $_SESSION['phone'] ?? '';

$customer_name = ($first_name && $last_name) ? htmlspecialchars($first_name) . ' ' . htmlspecialchars($last_name) : 'Welcome Customer';

// DB connection
$mysqli = new mysqli('localhost', 'root', '', 'bus_booking');
if ($mysqli->connect_errno) {
    die('Failed to connect to MySQL: ' . $mysqli->connect_error);
}

// Fetch distinct sources and destinations for dropdowns
$sources = [];
$destinations = [];
$res = $mysqli->query("SELECT DISTINCT source FROM routes ORDER BY source ASC");
while ($row = $res->fetch_assoc()) {
    $sources[] = $row['source'];
}
$res = $mysqli->query("SELECT DISTINCT destination FROM routes ORDER BY destination ASC");
while ($row = $res->fetch_assoc()) {
    $destinations[] = $row['destination'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Customer Dashboard</title>
<style>
    html, body {
        margin: 0; padding: 0;
        height: 100%;
        font-family: 'Poppins', sans-serif;
        background: black;
        color: white;
    }
     .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
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
        background: #00bfff;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: white;
        font-size: 1.2rem;
        user-select: none;
    }
    .dropdown-content {
        display: none;
        position: absolute;
        top: 55px;
        right: 0;
        background: rgba(0,0,0,0.6);
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
        position: relative;
        top: 120px;
        margin: auto;
        width: 90%; max-width: 850px;
       background: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
    }
    h1 {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #00bfff;
    }

    /* Button container below heading */
    .button-row {
        margin-bottom: 30px;
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .button-row button {
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        min-width: 140px;
        color: white;
        transition: background 0.3s ease;
    }
    .btn-search {
        background: linear-gradient(90deg, #00bfff, #1e90ff);
    }
    .btn-search:hover {
        background: linear-gradient(90deg, #1e90ff, #00bfff);
    }
    .btn-bookings {
        background: linear-gradient(90deg, #ff7e5f, #feb47b);
        color: white;
    }
    .btn-bookings:hover {
        background: linear-gradient(90deg, #feb47b, #ff7e5f);
    }
    .btn-payments {
        background: linear-gradient(90deg, #7b2ff7, #f107a3);
    }
    .btn-payments:hover {
        background: linear-gradient(90deg, #f107a3, #7b2ff7);
    }

    form#filterForm {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    form#filterForm select,
    form#filterForm input[type="date"] {
        padding: 10px;
        border-radius: 5px;
        border: none;
        font-size: 1rem;
        min-width: 150px;
        color: black;
    }
    form#filterForm button[type="submit"] {
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        background: linear-gradient(90deg, #ff512f, #dd2476);
        color: white;
        font-weight: 600;
        cursor: pointer;
        min-width: 140px;
        transition: background 0.3s ease;
    }
    form#filterForm button[type="submit"]:hover {
        background: linear-gradient(90deg, #dd2476, #ff512f);
    }
</style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <span style="color:#00bfff; font-weight:bold;"><?php echo "Welcome, " . $customer_name; ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn">
            <?php echo strtoupper(substr($first_name ?: $username, 0, 1)); ?>
        </div>
        <div class="dropdown-content" id="dropdownMenu">
		<a href="settings1.php">Settings</a>
            <a href="profile.php">Profile Details</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Customer Dashboard</h1>

    <div class="button-row">
        <button class="btn-search" onclick="window.location.href='search_buses.php'">Search Buses</button>
        <button class="btn-bookings" onclick="window.location.href='booking_history.php'">View Bookings</button>
        <button class="btn-payments" onclick="window.location.href='payment_history.php'">Payments</button>
    </div>

    <form id="filterForm" method="GET" action="search_bus.php" autocomplete="off">
        <select id="source" name="source" required>
            <option value="">Select Source</option>
            <?php foreach ($sources as $src): ?>
                <option value="<?php echo htmlspecialchars($src); ?>"><?php echo htmlspecialchars($src); ?></option>
            <?php endforeach; ?>
        </select>

        <select id="destination" name="destination" required>
            <option value="">Select Destination</option>
            <?php foreach ($destinations as $dest): ?>
                <option value="<?php echo htmlspecialchars($dest); ?>"><?php echo htmlspecialchars($dest); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="date" id="travel_date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" required />

        <button type="submit">Search Buses</button>
    </form>
</div>

<script>
    // Profile menu toggle
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    profileBtn.addEventListener('click', e => {
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        e.stopPropagation();
    });
    document.addEventListener('click', () => {
        dropdownMenu.style.display = 'none';
    });
</script>

</body>
</html>
