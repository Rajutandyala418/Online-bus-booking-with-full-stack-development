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

$message = '';
$errors = [];
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must include at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) $errors[] = "Password must include at least one lowercase letter.";
    if (!preg_match('/\d/', $password)) $errors[] = "Password must include at least one digit.";
    if (!preg_match('/[\W_]/', $password)) $errors[] = "Password must include at least one special character.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT first_name, last_name, email FROM admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($first_name, $last_name, $email);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $hashed_password, $username);
        if ($stmt->execute()) {
            $message = "Password updated successfully!";
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
                $mail->addAddress($email, $first_name . ' ' . $last_name);
                $mail->isHTML(true);
                date_default_timezone_set('Asia/Kolkata');
                $resetTime = date("Y-m-d H:i:s");
                $mail->Subject = "Password Reset Successful - Bus Booking Admin Panel";
                $mail->Body = "
                    <p>Dear <b>$first_name $last_name</b>,</p>
                    <p>You have successfully updated your password on <b>$resetTime</b>.</p>
                    <p>If this wasn’t you, please reset immediately.</p>
                    <br><p>Regards,<br>Bus Booking System</p>
                ";
                $mail->send();
            } catch (Exception $e) {}
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Password - Admin</title>
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
h2{
text-align:center;
margin-top:40px;
margin-bottom:20px;
font-weight:700;
color:#1e3c57;
}
.form-row{
display:flex;
flex-direction:column;
margin-bottom:12px;
}
label{
font-weight:500;
font-size:14px;
color:#1e3c57;
margin-bottom:6px;
}
input{
width:100%;
padding:11px;
border-radius:8px;
border:1px solid #b9c7d8;
outline:none;
font-size:15px;
}
input:focus{border-color:#0072ff;}
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
.message{color:green;font-weight:bold;margin-bottom:10px;text-align:center;}
.errors{color:red;font-weight:bold;margin-bottom:10px;text-align:center;}
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
.modal-content button{
margin-top:15px;
background:#1e3c57;
color:#fff;
border:none;
padding:10px 18px;
border-radius:6px;
font-weight:600;
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
    <p>Updating your password...</p>
</div>

<div id="errorModal" class="modal">
<div class="modal-content">
<p><b>Password Update Failed</b></p>
<span id="errorText"></span>
<br>
<button onclick="closeErrorModal()">OK</button>
</div>
</div>

<div id="successModal" class="modal">
<div class="modal-content">
<p>Password updated successfully</p>
<p>Redirecting in <span id="countdown">5</span> seconds...</p>
</div>
</div>

<div class="container">
<a href="login.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h2>Reset Password</h2>

<?php if ($message): ?><p class="message"><?php echo $message; ?></p><?php endif; ?>
<?php if (!empty($errors)): ?><p class="errors"><?php echo implode('<br>', array_map('htmlspecialchars',$errors)); ?></p><?php endif; ?>

<form method="post" autocomplete="off">
<div class="form-row">
<label>Username</label>
<input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
</div>
<div class="form-row">
<label>New Password</label>
<input type="password" name="password" placeholder="Enter password" required>
</div>
<div class="form-row">
<label>Confirm Password</label>
<input type="password" name="confirm_password" placeholder="Confirm password" required>
</div>
<button type="submit">Update Password</button>
</form>
</div>

<script>
const resetForm=document.querySelector('form');
const loader=document.getElementById('loader');
const successModal=document.getElementById('successModal');
const errorModal=document.getElementById('errorModal');
const countdownElem=document.getElementById('countdown');

resetForm.addEventListener('submit',function(){
loader.style.display='flex';
});

function closeErrorModal(){
errorModal.style.display='none';
}

<?php if ($message): ?>
document.addEventListener('DOMContentLoaded',function(){
loader.style.display='none';
successModal.style.display='flex';
let timeLeft=5;
const timer=setInterval(()=>{
timeLeft--;
countdownElem.textContent=timeLeft;
if(timeLeft<=0){
clearInterval(timer);
window.location.href='login.php';
}
},1000);
});
<?php endif; ?>

<?php if (!empty($errors)): ?>
document.addEventListener('DOMContentLoaded',function(){
loader.style.display='none';
document.getElementById('errorText').innerHTML="<?php echo implode('<br>', array_map('htmlspecialchars',$errors)); ?>";
errorModal.style.display='flex';
});
<?php endif; ?>
</script>

</body>
</html>
