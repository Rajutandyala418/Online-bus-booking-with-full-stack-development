<?php
include(__DIR__ . '/../include/db_connect.php');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']);
    $password = trim($_POST['password']);
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ? LIMIT 1");
    if (!$stmt) die("Prepare failed: " . $conn->error);
    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $username, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            header("Location: dashboard.php?username=" . urlencode($username));
            exit();
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
            background: linear-gradient(
                135deg,
                #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
            );
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
        .message { color: blue; font-weight: bold; }
        a {
            display: inline-block;
            margin-top: 10px;
            text-decoration: none;
            font-weight: bold;
            color: #0ff;
            transition: 0.3s;
        }
        a:hover {
            color: red;
            text-shadow: 0 0 10px #ff0, 0 0 20px #0ff, 0 0 30px #f0f;
        }
        .back-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background: rgba(0,0,0,0.6);
            padding: 10px 20px;
            color: orange;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
        }
        .back-btn:hover {
            background: rgba(0,0,0,0.8);
        }
        .extra-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            gap: 10px;
        }
        .btn-link {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            background: linear-gradient(90deg, #36d1dc, #5b86e5);
            color: #fff;
            font-weight: bold;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-link:hover {
            background: linear-gradient(90deg, #5b86e5, #36d1dc);
            transform: scale(1.05);
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
        <label for="login_input" style="display:block; text-align:left; margin-bottom:4px; color:blue; font-weight:bold;">
            Email or Username
        </label>
        <input id="login_input" type="text" name="login_input" placeholder="Email or Username" required />
        <label for="password" style="display:block; text-align:left; margin-bottom:4px; margin-top:12px; color:blue; font-weight:bold;">
            Password
        </label>
        <input id="password" type="password" name="password" placeholder="Password" required />
        <button type="submit" style="margin-top:16px; font-size : 20px;">Login</button>
    </form>
    <div class="extra-buttons">
        <a href="register.php" class="btn-link">New User? Register</a>
        <a href="forgot.php" class="btn-link">Forgot Password?</a>
    </div>
</div>
<script>
    localStorage.clear();
    sessionStorage.clear();
    document.cookie.split(";").forEach(function(c) { 
        document.cookie = c.trim().split("=")[0] + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
    });
    history.pushState(null, null, location.href);
    window.onpopstate = function () {
        history.go(1);
    };
</script>
</body>
</html>
