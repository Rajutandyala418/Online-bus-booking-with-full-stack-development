<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php'); // FIXED PATH

// PHPMailer files
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$errors = [];
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password validations
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must include at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) $errors[] = "Password must include at least one lowercase letter.";
    if (!preg_match('/\d/', $password)) $errors[] = "Password must include at least one digit.";
    if (!preg_match('/[\W_]/', $password)) $errors[] = "Password must include at least one special character.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ✅ Fetch admin details before updating
        $stmt = $conn->prepare("SELECT first_name, last_name, email FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($first_name, $last_name, $email);
        $stmt->fetch();
        $stmt->close();

        // ✅ Update password
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $username);
        if ($stmt->execute()) {
            $message = "Password updated successfully!";

            // ✅ Send email notification
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com';
                $mail->Password   = 'pjhg nwnt haac nsiu';  
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = "Password Reset Successful - Bus Booking Admin Panel";
date_default_timezone_set('Asia/Kolkata');
                $resetTime = date("Y-m-d H:i:s");
                $mail->Body = "
                    <p>Dear <b>$first_name $last_name</b>,</p>
                    <p>Congratulations! You have successfully updated your password on <b>$resetTime</b>.</p>
                    <p>You can now login with your new password.</p>
                    <p>If this wasn’t you, please reset your password again immediately or contact the system administrator.</p>
                    <br>
                    <p>Regards,<br>Bus Booking System</p>
                ";
                $mail->AltBody = "Dear $first_name $last_name,\n\nCongratulations! Your password was successfully updated on $resetTime.\nYou can now login with your new password.\n\nIf this wasn’t you, please reset it again immediately.";

                $mail->send();
            } catch (Exception $e) {
                // Optional: log error, don’t block success
            }

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
    <title>Reset Password - Admin</title>
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
        .reset-box {
            background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
             );

            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 30px;
            text-align: left;
            color: white;
            width: 420px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }
        h2 { margin-bottom: 20px; font-size: 35px; color: black; text-align: center; }
        label {
            display: inline-block;
            width: 140px;
            font-weight: bold;
            color: black;
        }
        input, button {
            padding: 8px;
            margin: 8px 0;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
        input {
            background: rgba(255, 255, 255, 0.8);
            color: #333;
            width: calc(100% - 150px);
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white; cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message { color: #00ff88; font-weight: bold; text-align: center; }
        .errors { color: #ff8080; font-weight: bold; font-size: 0.9rem; text-align: center; }
        .back-to-login {
            position: absolute;
            top: 20px;
            right: 30px;
            background: rgba(0, 0, 0, 0.6);
            color: yellow;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .back-to-login:hover { background: rgba(0, 0, 0, 0.8); }
        .form-row { display: flex; align-items: center; }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>
<a href="login.php" class="back-to-login">Back to Login</a>
<div class="reset-box">
    <h2>Reset Password</h2>
    <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
    <?php endif; ?>
    <?php if ($message): ?><p class="message"><?php echo $message; ?></p><?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="form-row">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
        </div>
        <div class="form-row">
            <label>New Password:</label>
            <input type="password" name="password" placeholder="Enter New Password" required autocomplete="new-password">
        </div>
        <div class="form-row">
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required autocomplete="new-password">
        </div>
        <button type="submit">Update Password</button>
    </form>
</div>
</body>
</html>
