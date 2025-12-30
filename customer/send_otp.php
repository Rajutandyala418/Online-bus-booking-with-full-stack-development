<?php
include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['username'])) {
    $username = trim($_GET['username']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
} else {
    header("Location: login.php");
    exit();
}
$stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    header("Location: login.php");
    exit();
}
$stmt->bind_result($user_id, $email, $first_name, $last_name);
$stmt->fetch();
$stmt->close();
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expiry_dt = new DateTime('now');
$expiry_dt->modify('+10 minutes');
$expiry_str = $expiry_dt->format('Y-m-d H:i:s');
$update = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, otp_attempts = 0 WHERE id = ?");
$update->bind_param("ssi", $otp, $expiry_str, $user_id);
$ok = $update->execute();
$update->close();

if (!$ok) {
    error_log("Failed to save OTP for user_id: $user_id");
    header("Location: login.php");
    exit();
}
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'varahibusbooking@gmail.com';
    $mail->Password   = 'pjhg nwnt haac nsiu';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus');
    $mail->addAddress($email, ($first_name ?? '') . ' ' . ($last_name ?? ''));

    $mail->isHTML(true);
    $mail->Subject = "Your VarahiBus OTP (valid 10 minutes)";
    $mail->Body    = "
        <p>Dear " . htmlspecialchars($first_name . ' ' . $last_name) . ",</p>
        <p>Your one-time verification code is: <strong>{$otp}</strong></p>
        <p>This code is valid for 10 minutes. If you did not request this, please ignore.</p>
    ";
    $mail->AltBody = "Your VarahiBus OTP is: {$otp} (valid 10 minutes)";

    $mail->send();
    header("Location: verify_otp.php?username=" . urlencode($username));
    exit();
} catch (Exception $e) {
    error_log("Mailer Error (send_otp): " . $mail->ErrorInfo);
    $_SESSION['otp_error'] = "Failed to send OTP. Please try again.";
    header("Location: verify_otp.php?username=" . urlencode($username));
    exit();
}
