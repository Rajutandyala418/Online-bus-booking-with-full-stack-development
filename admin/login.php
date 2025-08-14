<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Updated path to DB connection (include is now directly inside bus_booking)
include(__DIR__ . '/../include/db_connect.php');

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch the admin record
    $stmt = $conn->prepare("SELECT id, first_name, last_name, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin_id, $first_name, $last_name, $hashed_password);
        $stmt->fetch();

        // Check password using password_verify
        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_first_name'] = $first_name;
            $_SESSION['admin_last_name'] = $last_name;

            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
            display: flex; justify-content: center; align-items: center;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
        .login-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            width: 320px;
            color: white;
            z-index: 1;
        }
        h2 { margin-bottom: 20px; color: black; }
        input, button {
            width: 100%; padding: 10px;
            margin: 10px 0; border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
        input {
            background: rgba(255, 255, 255, 0.8);
            color: #333;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white; cursor: pointer;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message { color: #ff8080; font-size: 0.9rem; }
        a {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: yellow;
            font-size: 0.9rem;
            text-shadow: 0 0 5px #00f7ff, 0 0 10px #00f7ff;
            transition: color 0.3s, text-shadow 0.3s;
        }
        a:hover {
            color: #00eaff;
            text-shadow: 0 0 10px #00eaff, 0 0 20px #00eaff;
        }
        .back-to-main {
            position: absolute;
            top: 20px;
            right: 30px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.6);
            color: yellow;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .back-to-main:hover {
            background: rgba(0, 0, 0, 0.8);
        }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<!-- Updated back link to new index.php -->
<a href="../index.php" class="back-to-main">Back to Main Page</a>

<div class="login-box">
    <h2>Admin Login</h2>
    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Enter Username" autocomplete="off" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit">Login</button>
    </form>
    <a href="forgot_password.php">Forgot Password?</a>
    <a href="register.php">Register</a>
</div>
</body>
</html>
