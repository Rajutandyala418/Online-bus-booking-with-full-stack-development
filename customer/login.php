<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Prevent back button from loading cached page after logout
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

include(__DIR__ . '/../include/db_connect.php');  // Adjust path as needed

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']); // Email or Username
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, first_name, last_name, password FROM users WHERE email = ? OR username = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $first_name, $last_name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Successful login: set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;

            header("Location: dashboard.php");
            exit();  // Important: stop script here after redirect
        } else {
            $message = "Invalid login credentials.";
        }
    } else {
        $message = "Invalid login credentials.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Customer Login</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: flex; align-items: center; justify-content: center;
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
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            color: #fff;
            width: 350px;
            text-align: center;
        }
        h2 { color: black; margin-bottom: 20px; }
        input, button {
            width: 100%; padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 6px;
        }
        input { background: #fff; color: #333; }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message { color: #ff8080; font-weight: bold; }
        a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            font-weight: bold;
            color: #0ff;
            text-shadow: 0 0 5px #0ff, 0 0 10px #0ff, 0 0 20px #0ff;
            transition: 0.3s;
        }
        a:hover {
            color: #fff;
            text-shadow: 0 0 10px #ff0, 0 0 20px #0ff, 0 0 30px #f0f;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background: rgba(0,0,0,0.6);
            padding: 10px 20px;
            color: white;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
        }
        .back-btn:hover {
            background: rgba(0,0,0,0.8);
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<a href="../index.php" class="back-btn">Back to Main Page</a>

<div class="login-box">
    <h2>Customer Login</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <label for="login_input" style="display:block; text-align:left; margin-bottom:4px; color:#fff; font-weight:bold;">
            Email or Username
        </label>
        <input id="login_input" type="text" name="login_input" placeholder="Email or Username" required />

        <label for="password" style="display:block; text-align:left; margin-bottom:4px; margin-top:12px; color:#fff; font-weight:bold;">
            Password
        </label>
        <input id="password" type="password" name="password" placeholder="Password" required />

        <button type="submit" style="margin-top:16px;">Login</button>
    </form>
    <a href="register.php">New User? Register</a><br />
    <a href="forgot.php">Forgot Password?</a>
</div>
</body>
</html>
