<?php
session_start();
include(__DIR__ . '/../include/db_connect.php');
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        $user_id = $user['id'];
        $first_name = $user['first_name'];

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Insert token into DB
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $token, $expires_at);
        $stmt->execute();

        // Send email with token link
        $reset_link = "http://yourdomain.com/reset_password.php?token=" . $token;

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com';  // Your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@example.com'; // SMTP username
            $mail->Password = 'your-email-password'; // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            //Recipients
            $mail->setFrom('no-reply@yourdomain.com', 'Your App Name');
            $mail->addAddress($email, $first_name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <p>Hi {$first_name},</p>
                <p>You requested a password reset. Click the link below to reset your password:</p>
                <p><a href='{$reset_link}'>{$reset_link}</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request this, please ignore this email.</p>
            ";

            $mail->send();
            $message = "Password reset email sent! Please check your inbox.";
        } catch (Exception $e) {
            $message = "Failed to send reset email: {$mail->ErrorInfo}";
        }
    } else {
        $message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Request Password Reset</title>
</head>
<body>
    <h2>Request Password Reset</h2>
    <?php if ($message) echo "<p>$message</p>"; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</body>
</html>
