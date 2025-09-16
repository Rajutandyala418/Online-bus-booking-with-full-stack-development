<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

include(__DIR__ . '/../include/db_connect.php'); // ← Add this

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$pending_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_requests WHERE status='Pending'");
$stmt->execute();
$result = $stmt->get_result();
if($row = $result->fetch_assoc()){
    $pending_count = $row['cnt'];
}
$stmt->close();

$admin_id = $_SESSION['admin_id'];
$admin_name = (isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name'])) 
    ? htmlspecialchars($_SESSION['admin_first_name']) . ' ' . htmlspecialchars($_SESSION['admin_last_name']) 
    : 'Admin';
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
            overflow-x: hidden;
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
/* Top Circles Container */
#topCircles {
    position: fixed;
    top: 20px;
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0 30px;
    z-index: 1000;
    pointer-events: none; /* let individual elements handle clicks */
}

/* User Requests Circle */
#userRequestsBtn {
    pointer-events: auto; /* enable clicks */
    width: 45px;
    height: 45px;
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
    transition: transform 0.2s;
}
#userRequestsBtn:hover {
    transform: scale(1.1);
    background: #ffd633;
}

/* Profile Circle */
.profile-menu {
    pointer-events: auto; /* enable clicks */
    position: relative;
}
.profile-circle {
    width: 45px;
    height: 45px;
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
    transition: transform 0.2s;
}
.profile-circle:hover {
    transform: scale(1.1);
    background: #ffd633;
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
#userRequestsBtn {
    position: relative;
    pointer-events: auto; /* enable clicks */
    width: 45px;
    height: 45px;
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
    transition: transform 0.2s;
}
#userRequestsBtn:hover {
    transform: scale(1.1);
    background: #ffd633;
}

/* Notification badge */
#userRequestsBtn .badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: red;
    color: white;
    font-size: 0.7rem;
    font-weight: bold;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
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

        /* Welcome message animation */
        .welcome-message {
            margin-top: 30px;
            font-size: 1.2rem;
            line-height: 1.6;
            color: #ffde59;
            font-weight: bold;
        }
      .word {
    opacity: 0;
    display: inline-block;
    transform: scale(0.5);
    animation: popIn 0.5s forwards;
    margin-right: 6px;   /* 👈 space between words */
}
.animated-text {
    position: relative;
    z-index: 1;   /* keep it below the nav */
    pointer-events: none; /* so clicks pass through */
}
.animated-text .word {
    pointer-events: auto; /* allow text to still be selectable */
}

        @keyframes popIn {
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<!-- Top Nav Circles -->
<div id="topCircles">
    <!-- User Requests Circle (Top-Left) -->
    <div id="userRequestsBtn" title="View User Requests">
    UR
 <span class="badge"><?php echo $pending_count; ?></span>

</div>

 
    </div>
</div>

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
        <a href="manage_users.php">Manage Users</a> <!-- Only admin 3 -->
        <a href="manage_admins.php">Manage Admins</a> <!-- Only admin 3 -->
    <?php endif; ?>
</div>


    <div class="welcome-message" id="welcomeMsg"></div>
</div>

<script>
    // Prevent going back to login page
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };

    // Animated welcome message
    const message = `Welcome <?php echo $admin_name; ?>! You are the owner of this system. You can add buses using Manage Buses, define routes using Manage Routes, set schedules with Manage Schedules, and monitor your bookings and payments effortlessly.`;
    const words = message.split(" ");
    const welcomeMsg = document.getElementById("welcomeMsg");

    words.forEach((word, i) => {
        const span = document.createElement("span");
        span.textContent = word + " ";
        span.classList.add("word");
        span.style.animationDelay = `${i * 0.3}s`;
        welcomeMsg.appendChild(span);
    });
// User Requests click
document.getElementById('userRequestsBtn').addEventListener('click', () => {
    window.location.href = 'user_requests.php';
});

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
