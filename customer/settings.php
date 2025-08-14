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
$message = '';

// Fetch current user details
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($current_name);
$stmt->fetch();
$stmt->close();

// Update password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (!empty($new_password) && !empty($confirm_password)) {
        if ($new_password !== $confirm_password) {
            $message = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_password, $user_id);
            if ($stmt->execute()) {
                $message = "Password updated successfully!";
            } else {
                $message = "Error updating password.";
            }
            $stmt->close();
        }
    } else {
        $message = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Settings</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
        }
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.6);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            width: 90%;
            max-width: 400px;
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #ffde59;
        }
        h2 {
            margin-bottom: 20px;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
            transition: transform 0.3s;
        }
        button:hover {
            transform: scale(1.05);
        }
        .message {
            color: #ff8080;
            margin-top: 10px;
            font-weight: bold;
        }
        .success {
            color: #00ff88;
        }
        /* Dropdown Menu */
        .top-nav {
            position: absolute;
            top: 20px;
            right: 30px;
        }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropbtn {
            background: rgba(0,0,0,0.5);
            color: #0ff;
            padding: 10px 15px;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            text-shadow: 0 0 5px #0ff, 0 0 10px #0ff;
            border-radius: 5px;
        }
        .dropbtn:hover {
            background: rgba(0,0,0,0.8);
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: rgba(0, 0, 0, 0.8);
            min-width: 180px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            border-radius: 5px;
            z-index: 1;
        }
        .dropdown-content a {
            color: #0ff;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            text-shadow: 0 0 5px #0ff;
            transition: background 0.3s;
        }
        .dropdown-content a:hover {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <div class="dropdown">
        <button class="dropbtn">Menu</button>
        <div class="dropdown-content">
            <a href="settings.php">Update Password</a>
            <a href="booking_history.php">Booking History</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Hello, <?php echo htmlspecialchars($current_name); ?></h1>
    <h2>Change Your Password</h2>
    <?php if ($message): ?>
        <p class="message <?php echo ($message === 'Password updated successfully!') ? 'success' : ''; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>
    <form method="post">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit">Update Password</button>
    </form>
</div>

</body>
</html>
