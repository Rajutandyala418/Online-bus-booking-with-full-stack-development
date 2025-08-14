<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Correct include path for db_connect.php
include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$message = '';

// Fetch current admin details
$stmt = $conn->prepare("SELECT username, password FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($current_username, $current_password_hash);
$stmt->fetch();
$stmt->close();

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate username
    if (empty($new_username)) {
        $message = "Username cannot be empty.";
    } else {
        // Check if username already exists for another admin
        $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $new_username, $admin_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Username already taken. Please choose another.";
        }
        $stmt->close();
    }

    // Password logic
    if (empty($message)) {
        if (!empty($new_password) || !empty($confirm_password)) {
            if ($new_password !== $confirm_password) {
                $message = "Passwords do not match.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            }
        } else {
            // Keep old password if both fields are empty
            $hashed_password = $current_password_hash;
        }

        // Update DB
        if (empty($message)) {
            $stmt = $conn->prepare("UPDATE admin SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_username, $hashed_password, $admin_id);
            if ($stmt->execute()) {
                $_SESSION['admin_username'] = $new_username;
                header("Location: dashboard.php?msg=" . urlencode("Settings updated successfully."));
                exit();
            } else {
                $message = "Error updating settings. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Settings</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: 'Poppins', sans-serif; }
        .bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .container { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.6); color: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; }
        h1 { font-size: 2rem; margin-bottom: 20px; color: #ffde59; text-align: center; }
        .form-group { display: flex; align-items: center; margin-bottom: 15px; }
        .form-group label { flex: 1; text-align: right; margin-right: 10px; font-weight: bold; }
        .form-group input { flex: 2; padding: 10px; border: none; border-radius: 5px; font-size: 1rem; }
        button { width: 100%; padding: 12px; border: none; border-radius: 5px; font-size: 1rem; background: linear-gradient(90deg, #ff512f, #dd2476); color: white; cursor: pointer; transition: transform 0.3s; }
        button:hover { transform: scale(1.05); }
        .back-btn { display: block; text-align: center; text-decoration: none; color: white; background: #444; padding: 10px; margin-top: 10px; border-radius: 5px; transition: background 0.3s; }
        .back-btn:hover { background: #222; }
        .message { color: #ff8080; margin-bottom: 10px; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="container">
    <h1>Admin Settings</h1>
    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form method="post">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
        </div>
        <div class="form-group">
            <label>New Password:</label>
            <input type="password" name="password" placeholder="Leave blank to keep old">
        </div>
        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" placeholder="Leave blank to keep old">
        </div>
        <button type="submit">Update Settings</button>
    </form>
    <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
</div>

</body>
</html>
