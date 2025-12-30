<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$popupMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin_id, $first_name, $last_name, $email, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_first_name'] = $first_name;
            $_SESSION['admin_last_name'] = $last_name;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'varahibusbooking@gmail.com';
                $mail->Password = 'pjhg nwnt haac nsiu';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
                $mail->addAddress($email);
                $mail->isHTML(true);
                date_default_timezone_set('Asia/Kolkata');
                $mail->Subject = "Admin Login Alert";
                $mail->Body = "Login successful at ".date("Y-m-d H:i:s");
                $mail->send();
            } catch (Exception $e) {}

            header("Location: dashboard.php");
            exit();
        } else {
            $popupMessage = "Invalid username or password";
        }
    } else {
        $popupMessage = "Invalid username or password";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#e8f0f7;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:15px;}
.container{background:#ffffff;border:1px solid #d7e0ea;padding:25px;border-radius:14px;width:100%;max-width:380px;box-shadow:0 4px 15px rgba(0,0,0,0.06);position:relative;}
.container h2{text-align:center;margin-top:40px;margin-bottom:20px;font-weight:700;color:#1e3c57;}
.input-group{margin-bottom:15px;}
.input-group label{font-weight:500;font-size:14px;color:#1e3c57;}
.input-group input{width:100%;padding:11px;border-radius:8px;border:1px solid #b9c7d8;margin-top:6px;outline:none;font-size:15px;}
.input-group input:focus{border-color:#0072ff;}
.input-group input::placeholder{color:#9bb1c7;font-size:14px;}
.login-btn{background:#1e3c57;color:#fff;width:100%;padding:12px;border:none;border-radius:8px;cursor:pointer;margin-top:8px;font-weight:600;font-size:16px;}
.login-btn:hover{background:#264a6e;}
.back-btn{position:absolute;top:18px;right:20px;background:#1e3c57;color:#fff;border:none;font-size:16px;padding:10px 18px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;}
.back-btn i{font-size:17px;}
.back-btn:hover{background:#264a6e;}
.message{color:red;font-weight:bold;margin-bottom:10px;text-align:center;}
.options{display:flex;justify-content:space-between;margin-top:15px;}
.opt-btn{background:#ffffff;color:#1e3c57;border:1px solid #0072ff;padding:10px 12px;width:48%;border-radius:8px;font-size:13px;cursor:pointer;transition:0.3s;text-align:center;text-decoration:none;font-weight:600;}
.opt-btn:hover{background:#e8eef5;}
@media(max-width:480px){
    .container{padding:20px;border-radius:12px;}
    .login-btn{font-size:15px;padding:11px;}
    .opt-btn{font-size:12px;padding:9px;}
    .back-btn{padding:8px 15px;font-size:14px;}
}
#popup{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:10000;justify-content:center;align-items:center;}
.popup-box{background:#fff;padding:22px;border-radius:10px;text-align:center;width:90%;max-width:320px;}
.popup-box button{margin-top:15px;background:#1e3c57;color:#fff;border:none;padding:10px 18px;border-radius:6px;font-weight:600;}
#loader{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;justify-content:center;align-items:center;flex-direction:column;color:#fff;}
#loader img{width:70px;height:auto;margin-bottom:10px;}
</style>
</head>
<body>
<div class="container">
    <a href="../index.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>
    <h2>Admin Login</h2>
    <?php if ($popupMessage): ?>
        <p class="message"><?= htmlspecialchars($popupMessage) ?></p>
    <?php endif; ?>
    <form method="post" autocomplete="off" id="loginForm">
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="Enter username" required>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="login-btn">Login</button>
        <div class="options">
            <a href="forgot_password.php" class="opt-btn">Forgot Credentials</a>
            <a href="register.php" class="opt-btn">Register</a>
        </div>
    </form>
</div>
<div id="popup">
  <div class="popup-box">
    <p id="popupText"></p>
    <button onclick="closePopup()">OK</button>
  </div>
</div>
<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading..." />
    <p>Loading...</p>
</div>
<script>
const form = document.getElementById("loginForm");
const loader = document.getElementById("loader");
form.addEventListener("submit", () => {
  loader.style.display = "flex";
});
function closePopup(){
  document.getElementById("popup").style.display = "none";
}
<?php if($popupMessage): ?>
  loader.style.display = "none";
  document.getElementById("popupText").innerText = "<?php echo $popupMessage ?>";
  document.getElementById("popup").style.display = "flex";
<?php endif; ?>
</script>
</body>
</html>
