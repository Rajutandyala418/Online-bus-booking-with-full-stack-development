<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer files
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;

$username = $_POST['username'] ?? '';
if (!$username) { header("Location: forgot_password.php"); exit; }

// Fetch admin email
$stmt = $conn->prepare("SELECT email, first_name, last_name FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($email, $first_name, $last_name);
$stmt->fetch();
$stmt->close();

if (!$email) { header("Location: forgot_password.php"); exit; }

// Generate OTP
$otp = rand(100000, 999999);
$expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

// Save OTP in DB
$stmt = $conn->prepare("UPDATE admin SET otp_code=?, otp_expiry=? WHERE username=?");
$stmt->bind_param("sss", $otp, $expiry, $username);
$stmt->execute();
$stmt->close();

// Store attempts in session
$_SESSION['otp_attempts'] = 0;
$_SESSION['otp_user'] = $username;

// --- Send OTP Email ---
$mail = new PHPMailer(true);
$sent = false;
$error = '';

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'varahibusbooking@gmail.com';
    $mail->Password = 'pjhg nwnt haac nsiu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
    $mail->addAddress($email, $first_name.' '.$last_name);

    $mail->isHTML(true);
    $mail->Subject = "Your OTP for Password Reset";
    $mail->Body    = "<p>Dear $first_name,</p><p>Your OTP is: <b>$otp</b></p><p>It is valid for 10 minutes.</p>";

    $mail->send();
    $sent = true;
} catch (Exception $e) {
    $error = $mail->ErrorInfo;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sending OTP...</title>
    <style>
        body {
            margin:0; padding:0;
            font-family:'Poppins',sans-serif;
            display:flex; justify-content:center; align-items:center;
            height:100vh;
            background:rgba(0,0,0,0.6);
            color:white;
        }
        #loader {
            display:flex; flex-direction:column;
            justify-content:center; align-items:center;
        }
        #loader img { width:100px; height:100px; margin-bottom:20px; }
        #msg { font-size:1.2rem; }
    </style>
</head>
<body>
<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading...">
    <p id="msg">
        <?php if ($sent): ?>
            ✅ OTP sent to <b><?php echo htmlspecialchars($email); ?></b>. Redirecting...
        <?php else: ?>
            ❌ Failed to send OTP. <?php echo htmlspecialchars($error); ?>
        <?php endif; ?>
    </p>
</div>

<?php if ($sent): ?>
<script>
setTimeout(function(){
    window.location.href = "verify_otp.php";
}, 3000);
</script>
<?php else: ?>
<script>
setTimeout(function(){
    window.location.href = "forgot_password.php";
}, 4000);
</script>
<?php endif; ?>
</body>
</html>
