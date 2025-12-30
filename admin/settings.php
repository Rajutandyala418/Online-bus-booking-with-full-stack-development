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

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$message = '';

$stmt = $conn->prepare("SELECT username, password, email FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($current_username, $current_password_hash, $current_email);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_username)) {
        $message = "Username cannot be empty.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $new_username, $admin_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "Username already taken. Please choose another.";
        }
        $stmt->close();
    }

    if (empty($message)) {
        if (!empty($new_password) || !empty($confirm_password)) {
            if ($new_password !== $confirm_password) {
                $message = "Passwords do not match.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            }
        } else {
            $hashed_password = $current_password_hash;
        }

        if (empty($message)) {
            $stmt = $conn->prepare("UPDATE admin SET username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_username, $hashed_password, $admin_id);
            if ($stmt->execute()) {
                $_SESSION['admin_username'] = $new_username;

                $stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $stmt->bind_result($admin_email);
                $stmt->fetch();
                $stmt->close();

                if (!empty($admin_email)) {
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
                        $mail->addAddress($admin_email, $new_username);
                        $mail->isHTML(true);
                        $mail->Subject = "Admin Settings Updated";
                        $mail->Body = "<p>Hello <b>{$new_username}</b>,</p>
                                      <p>Your admin settings have been updated successfully.</p>
                                      <p>If this was not you, please contact support immediately.</p>";
                        $mail->send();
                        echo "<script>window.location.href='dashboard.php';</script>";
                        exit;
                    } catch (Exception $e) {
                        echo "<script>window.location.href='settings.php';</script>";
                        exit;
                    }
                } else {
                    echo "<script>alert('Settings updated successfully, but no email found.');window.location.href='dashboard.php';</script>";
                    exit;
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Settings</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
background:#e8f0f7;
display:flex;
justify-content:center;
align-items:center;
min-height:100vh;
padding:15px;
}
.container{
background:#ffffff;
border:1px solid #d7e0ea;
padding:25px;
border-radius:14px;
width:100%;
max-width:420px;
box-shadow:0 4px 15px rgba(0,0,0,0.06);
position:relative;
}
h1{
text-align:center;
margin-top:40px;
margin-bottom:20px;
font-weight:700;
color:#1e3c57;
font-size:24px;
}
.form-group{
display:flex;
flex-direction:column;
margin-bottom:12px;
}
.form-group label{
font-weight:500;
font-size:14px;
color:#1e3c57;
margin-bottom:6px;
}
.form-group input{
width:100%;
padding:11px;
border-radius:8px;
border:1px solid #b9c7d8;
outline:none;
font-size:15px;
}
.form-group input:focus{border-color:#0072ff;}
button{
background:#1e3c57;
color:#fff;
width:100%;
padding:12px;
border:none;
border-radius:8px;
cursor:pointer;
margin-top:8px;
font-weight:600;
font-size:16px;
}
button:hover{background:#264a6e;}
.back-btn{
position:absolute;
top:18px;
right:20px;
background:#1e3c57;
color:#fff;
border:none;
font-size:15px;
padding:10px 18px;
border-radius:8px;
cursor:pointer;
display:flex;
align-items:center;
gap:6px;
text-decoration:none;
}
.back-btn:hover{background:#264a6e;}
.message{
color:red;
font-weight:bold;
margin-bottom:10px;
text-align:center;
}
#loader{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.85);
z-index:9999;
justify-content:center;
align-items:center;
flex-direction:column;
color:#fff;
}
#loader img{width:70px;height:auto;margin-bottom:10px;}
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.6);
z-index:10000;
justify-content:center;
align-items:center;
}
.modal-content{
background:#fff;
padding:22px;
border-radius:10px;
text-align:center;
width:90%;
max-width:320px;
}
@media(max-width:480px){
.container{padding:20px;border-radius:12px;}
button{font-size:15px;padding:11px;}
.back-btn{padding:8px 15px;font-size:14px;}
}
</style>
</head>
<body>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Updating...</p>
</div>

<div id="successModal" class="modal">
<div class="modal-content">
<p>Settings updated successfully</p>
<p>Redirecting in <span id="countdown">3</span> seconds...</p>
</div>
</div>

<div class="container">
<a href="dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h1>Admin Settings</h1>

<?php if ($message): ?>
<p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post">
<div class="form-group">
<label>Username</label>
<input type="text" name="username" value="<?php echo htmlspecialchars($current_username); ?>" required>
</div>
<div class="form-group">
<label>New Password</label>
<input type="password" name="password" placeholder="Leave blank to keep old">
</div>
<div class="form-group">
<label>Confirm Password</label>
<input type="password" name="confirm_password" placeholder="Leave blank to keep old">
</div>
<button type="submit">Update Settings</button>
</form>
</div>

<script>
const form=document.querySelector('form');
const loader=document.getElementById('loader');
const successModal=document.getElementById('successModal');
const countdownElem=document.getElementById('countdown');

form.addEventListener('submit',function(){
loader.style.display='flex';
});

<?php if ($message): ?>
document.addEventListener('DOMContentLoaded',function(){
loader.style.display='none';
successModal.style.display='flex';
let timeLeft=3;
const timer=setInterval(()=>{
timeLeft--;
countdownElem.textContent=timeLeft;
if(timeLeft<=0){
clearInterval(timer);
window.location.href='settings.php';
}
},1000);
});
<?php endif; ?>
</script>

</body>
</html>
