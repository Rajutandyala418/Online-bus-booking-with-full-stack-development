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

$admin_id = $_SESSION['admin_id'];
$admin_name = (isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name'])) 
    ? htmlspecialchars($_SESSION['admin_first_name']) . ' ' . htmlspecialchars($_SESSION['admin_last_name']) 
    : 'Welcome Admin';

// Set session expiry time once (if not already set)
if (!isset($_SESSION['session_expiry'])) {
    $_SESSION['session_expiry'] = time() + 900; // 5 minutes from now
}

// Calculate remaining time
$remaining_time = $_SESSION['session_expiry'] - time();
if ($remaining_time <= 0) {
    header("Location: logout.php?timeout=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        html, body {
            margin: 0; padding: 0;
            height: 100%;
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
            position: relative;
            top: 120px;
            margin: auto;
            width: 90%; max-width: 800px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            color: #ffde59;
        }
        .links a {
            display: inline-block;
            margin: 10px;
            padding: 15px 25px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 1.1rem;
            transition: transform 0.2s;
        }
        .links a:hover {
            transform: scale(1.05);
        }
        .timer-box {
            margin-top: 20px;
            font-size: 1.2rem;
            font-weight: bold;
            background: rgba(0, 0, 0, 0.4);
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            color: #ffde59;
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <span style="color:blue; font-weight:bold;"><?php echo "Welcome  ". $admin_name; ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn">
            <?php echo strtoupper(substr($_SESSION['admin_first_name'], 0, 1)); ?>
        </div>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="settings.php">Settings</a>
            <a href="admin_details.php">Profile Details</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Admin Dashboard</h1>
    <div class="links">
        <a href="manage_buses.php">Manage Buses</a>
        <a href="manage_routes.php">Manage Routes</a>
        <a href="manage_schedules.php">Manage Schedules</a>
        <a href="view_bookings.php">View Bookings</a>
        <a href="payment.php">View Payments</a>
        <?php if ($admin_id == 3): ?>
            <a href="manage_admins.php">Manage Admins</a>
        <?php endif; ?>
    </div>

    <div class="timer-box">
        Session expires in: <span id="timer"><?php echo gmdate("i:s", $remaining_time); ?></span>
    </div>
</div>

<script>
    let timeLeft = <?php echo $remaining_time; ?>;
    const timerElement = document.getElementById('timer');
    function updateTimer() {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        timerElement.textContent = 
            (minutes < 10 ? '0' : '') + minutes + ':' + 
            (seconds < 10 ? '0' : '') + seconds;
        if (timeLeft <= 0) {
            window.location.href = 'logout.php?timeout=1';
        }
        timeLeft--;
    }
    setInterval(updateTimer, 1000);

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
