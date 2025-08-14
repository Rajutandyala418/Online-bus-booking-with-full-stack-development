<?php
session_start();
include(__DIR__ . '/../include/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch username from DB
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];

// Handle password update
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error_msg = "New password and confirmation do not match.";
    } else {
        // Update password
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        if ($stmt->execute()) {
            $success_msg = "Password updated successfully!";
        } else {
            $error_msg = "Error updating password. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #111;
            color: white;
            text-align: center;
        }

        .top-bar {
            display: flex;
            justify-content: flex-end;
            padding: 10px 20px;
        }

        .login-btn {
            padding: 10px 20px;
            font-size: 16px;
            border: none;
            color: #fff;
            cursor: pointer;
            background: #0ff;
            border-radius: 5px;
            box-shadow: 0 0 10px #0ff, 0 0 20px #0ff, 0 0 40px #0ff;
            transition: 0.3s;
        }

        .login-btn:hover {
            box-shadow: 0 0 20px #0ff, 0 0 40px #0ff, 0 0 80px #0ff;
        }

        .container {
            background: #222;
            padding: 20px;
            margin: 50px auto;
            width: 400px;
            border-radius: 10px;
            box-shadow: 0 0 20px #0ff;
        }

        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            outline: none;
        }

        input[readonly] {
            background-color: #333;
            color: #ccc;
        }

        .submit-btn {
            background: #0ff;
            color: black;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 95%;
        }

        .submit-btn:hover {
            background: #00cccc;
        }

        .message {
            margin-top: 10px;
            color: yellow;
        }

        .error {
            color: red;
        }

        .validation-msg {
            font-size: 12px;
            color: #ff6666;
            text-align: left;
            margin-left: 20px;
        }
    </style>
</head>
<body>

    <!-- Top Bar with Neon Login Button -->
    <div class="top-bar">
        <a href="login.php"><button class="login-btn">Login</button></a>
    </div>

    <!-- Change Password Form -->
    <div class="container">
        <h2>Change Password</h2>
        <form method="POST" onsubmit="return validatePassword()">
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
            <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
            <div id="password-requirements" class="validation-msg">
                Password must contain:
                <ul>
                    <li id="upper">At least one uppercase letter</li>
                    <li id="lower">At least one lowercase letter</li>
                    <li id="number">At least one number</li>
                    <li id="special">At least one special character</li>
                    <li id="length">Minimum 8 characters</li>
                </ul>
            </div>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" class="submit-btn">Update Password</button>
        </form>

        <?php if ($success_msg) echo "<p class='message'>$success_msg</p>"; ?>
        <?php if ($error_msg) echo "<p class='error'>$error_msg</p>"; ?>
    </div>

    <script>
        function validatePassword() {
            let password = document.getElementById("new_password").value;
            let confirm = document.getElementById("confirm_password").value;

            let upper = /[A-Z]/.test(password);
            let lower = /[a-z]/.test(password);
            let number = /[0-9]/.test(password);
            let special = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            let length = password.length >= 8;

            if (!upper || !lower || !number || !special || !length) {
                alert("Password does not meet the requirements.");
                return false;
            }

            if (password !== confirm) {
                alert("Passwords do not match.");
                return false;
            }

            return true;
        }
    </script>

</body>
</html>
