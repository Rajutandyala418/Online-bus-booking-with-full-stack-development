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
// Session expiry (5 minutes)
if (!isset($_SESSION['session_expiry'])) {
    $_SESSION['session_expiry'] = time() + 3000;
}
$remaining_time = $_SESSION['session_expiry'] - time();
if ($remaining_time <= 0) {
    header("Location: logout.php?timeout=1");
    exit();
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
$reg_pending_count = 0;
    $stmt2 = $conn->prepare("SELECT COUNT(*) AS cnt FROM registration_requests WHERE status='Pending'");
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($row2 = $result2->fetch_assoc()) {
        $reg_pending_count = $row2['cnt'];
    }
$admin_id = $_SESSION['admin_id'];
$admin_name = (isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name'])) 
    ? htmlspecialchars($_SESSION['admin_first_name']) . ' ' . htmlspecialchars($_SESSION['admin_last_name']) 
    : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

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
    font-size: 1.0rem;
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
/* Registration Requests Circle (Bottom-Left) */
#registrationRequestsBtn {
    position: fixed;
    bottom: 30px;
    left: 30px;
    width: 55px;
    height: 55px;
    background: #ffde59;
    border-radius: 50%;
    border: 2px solid #fff;
    color: black;
    font-weight: bold;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1000;
    transition: transform 0.2s, background 0.3s;
}
#registrationRequestsBtn:hover {
    transform: scale(1.1);
    background: #ffd633;
}
#registrationRequestsBtn .badge {
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
/* ================= RESPONSIVE DASHBOARD FIXES ================= */

/* Tablets & Medium Screens */
@media (max-width: 992px) {
    .container {
        width: 90%;
        top: 50px;
        padding: 20px;
    }

    h1 {
        font-size: 1.8rem;
    }

    .links a {
        padding: 12px 18px;
        font-size: 1rem;
        display: block;
        margin: 12px auto;
        width: 80%;
        text-align: center;
    }

    #topCircles {
        padding: 0 15px;
        justify-content: space-between;
    }

    .profile-circle, #userRequestsBtn {
        width: 42px;
        height: 42px;
        font-size: 1rem;
    }

    .badge {
        width: 16px;
        height: 16px;
        font-size: 0.65rem;
    }
}

/* Mobile Screens */
@media (max-width: 768px) {
    .container {
        width: 92%;
        top: 100px;
        padding: 18px;
    }

    .top-nav {
        top: 15px;
        right: 15px;
        font-size: 0.85rem;
        gap: 10px;
    }

    .profile-circle, #userRequestsBtn {
        width: 40px;
        height: 40px;
        font-size: 0.95rem;
    }

    .links a {
        width: 100%;
        font-size: 0.95rem;
        padding: 12px;
        margin: 10px 0;
    }

    #registrationRequestsBtn {
        bottom: 20px;
        left: 20px;
        width: 50px;
        height: 50px;
        font-size: 1rem;
    }

    #registrationRequestsBtn .badge {
        width: 16px;
        height: 16px;
        font-size: 0.65rem;
    }

    .welcome-message {
        font-size: 0.95rem;
        padding: 0 5px;
    }
}

/* Super Small Screens */
@media (max-width: 480px) {
    .container {
        width: 90%;
        top: 70px;
        padding: 14px;
    }

    h1 {
        font-size: 1.5rem;
    }
 .links a {
    display: inline-block;
    margin: 6px;
    padding: 8px 14px;
    background: linear-gradient(90deg, #ff512f, #dd2476);
    color: white;
     width:90%;
    border-radius: 3px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: transform 0.2s;
}
.links a:hover {
    transform: scale(1.0);
}



    .top-nav span {
        font-size: 0.75rem;
    }

    .profile-circle, #userRequestsBtn {
        width: 36px;
        height: 36px;
        font-size: 0.85rem;
    }

    .links a {
        font-size: 0.85rem;
        padding: 10px;
    }

    #registrationRequestsBtn {
        width: 45px;
        height: 45px;
        font-size: 0.9rem;
    }

    #registrationRequestsBtn .badge,
    #userRequestsBtn .badge {
        width: 14px;
        height: 14px;
        font-size: 0.6rem;
    }

    .welcome-message {
        font-size: 0.85rem;
        line-height: 1.4;
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
<?php if ($admin_id == 3): ?>
<!-- Registration Requests Circle (Bottom-Left) -->
<div id="registrationRequestsBtn" title="View Registration Requests">
    RR
    <span class="badge"><?php echo $reg_pending_count; ?></span>
</div>
<?php endif; ?>
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
// Registration Requests click (only for admin_id=3)
const regBtn = document.getElementById('registrationRequestsBtn');
if (regBtn) {
    regBtn.addEventListener('click', () => {
        window.location.href = 'registration_requests.php';
    });
}

</script>


</body>
</html>
