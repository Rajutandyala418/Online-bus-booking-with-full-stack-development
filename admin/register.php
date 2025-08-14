<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php'); // same path style as login.php

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $secret_key = trim($_POST['secret_key']);

    // Check if username already exists
    $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Username already exists.";
    }
    $stmt->close();

    // Password validations
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must include at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) $errors[] = "Password must include at least one lowercase letter.";
    if (!preg_match('/\d/', $password)) $errors[] = "Password must include at least one digit.";
    if (!preg_match('/[\W_]/', $password)) $errors[] = "Password must include at least one special character.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // Hash password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO admin (username, first_name, last_name, email, phone, password, secret_key)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss", $username, $first_name, $last_name, $email, $phone, $hashed_password, $secret_key);
        if ($stmt->execute()) {
            $message = "Registration successful!";
        } else {
            $errors[] = "Database Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Sign Up</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            overflow-y: auto;
        }
        .register-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            width: 500px;
            color: white;
            margin: 80px auto 50px auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: black;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .form-group label {
            flex: 0 0 150px;
            color: blue;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .form-group input {
            flex: 1;
            padding: 15px;
            border-radius: 10px;
            border: none;
            background: rgba(255,255,255,0.8);
            color: #333;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            border: none;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message {
            color: #00ff88;
            text-align: center;
            font-size: 0.9rem;
        }
        .errors {
            color: #ff8080;
            font-size: 0.9rem;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: rgba(0, 0, 0, 0.6);
            color: #00f7ff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 0 10px #00f7ff, 0 0 20px #00f7ff, 0 0 30px #00f7ff;
            transition: background 0.3s, box-shadow 0.3s;
            z-index: 10;
        }
        .back-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 15px #00f7ff, 0 0 30px #00f7ff, 0 0 45px #00f7ff;
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover; z-index: -1;
        }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>
<a href="login.php" class="back-btn">Back to Login</a>
<div class="register-box">
    <h2>Admin Sign Up</h2>
    <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
    <?php endif; ?>
    <?php if ($message): ?><p class="message"><?php echo $message; ?></p><?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" required>
        </div>
        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required>
        </div>
        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" required>
        </div>
        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" name="phone" id="phone" placeholder="Enter your phone number" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Create a strong password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
        </div>
        <div class="form-group">
            <label for="secret_key">Secret Key</label>
            <input type="password" name="secret_key" id="secret_key" placeholder="Enter secret key" required>
        </div>
        <button type="submit">Sign Up</button>
    </form>
</div>
</body>
</html>
