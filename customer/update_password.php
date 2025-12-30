<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$username = '';

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
} elseif (isset($_GET['username']) && !empty($_GET['username'])) {
    $username = trim($_GET['username']);
} else {
    die("No username provided.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $username = trim($_POST['username']);

    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!preg_match($password_pattern, $password)) {
        $message = "Password does not meet requirements.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($user_id, $user_email, $first_name, $last_name);

        if ($stmt->fetch()) {
            $stmt->close();
            $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt2->bind_param("si", $hashed_password, $user_id);
            if ($stmt2->execute()) {
                $message = "success";
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'varahibusbooking@gmail.com';
                    $mail->Password = 'pjhg nwnt haac nsiu';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;
                    $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus');
                    $mail->addAddress($user_email, $first_name.' '.$last_name);
                    $mail->isHTML(true);
                    $mail->Subject = "Password Updated";
                    $mail->Body = "<p>Your password has been updated successfully.</p>";
                    $mail->send();
                } catch (Exception $e) {}
            } else {
                $message = "Failed to update password.";
            }
            $stmt2->close();
        } else {
            $message = "User not found.";
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
background:#e8f0f7;
display:flex;
justify-content:center;
align-items:center;
min-height:100vh;
padding:15px
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
margin-bottom:10px;
font-weight:700;
color:#1e3c57
}
label{
display:block;
text-align:left;
margin-top:8px;
font-size:14px;
color:#1e3c57
}
input,button{
width:100%;
padding:12px;
margin:8px 0;
border-radius:8px;
border:1px solid #b9c7d8;
font-size:15px
}
input{background:#fafafa}
input:focus{border-color:#0072ff;outline:none;}
button{
background:#1e3c57;
color:#fff;
border:none;
cursor:pointer;
font-weight:600
}
button:hover{background:#264a6e}
.password-rules{
text-align:left;
font-size:.8rem;
margin-top:8px
}
.password-rules li{list-style:none}
.valid{color:green}
.invalid{color:red}
.back-btn{
position:absolute;
top:18px;
right:20px;
background:#1e3c57;
color:#fff;
border:none;
font-size:16px;
padding:10px 18px;
border-radius:8px;
cursor:pointer;
text-decoration:none;
display:flex;
align-items:center;
gap:6px
}
.back-btn:hover{background:#264a6e}
#popup{
display:none;
position:fixed;
top:0;left:0;
width:100%;
height:100%;
background:rgba(0,0,0,.7);
z-index:10000;
justify-content:center;
align-items:center
}
.popup-box{
background:#fff;
color:#222;
padding:28px;
border-radius:12px;
text-align:center;
width:85%;
max-width:340px
}
.popup-box button{
margin-top:15px;
padding:8px 16px;
border:none;
border-radius:6px;
background:#1e3c57;
color:#fff
}
#loader{
display:none;
position:fixed;
top:0;left:0;
width:100%;
height:100%;
background:rgba(0,0,0,.75);
z-index:9999;
flex-direction:column;
justify-content:center;
align-items:center;
color:#fff
}
#loader img{width:120px}
@media(max-width:480px){
.container{padding:20px;border-radius:12px;}
button{font-size:15px;padding:11px;}
.back-btn{padding:8px 15px;font-size:14px;}
#loader img{width:95px;}
}
</style>
</head>

<body>

<div class="container">
<a href="login.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h2>Update Password</h2>

<form method="post" onsubmit="return validateForm()">
<label>Username</label>
<input type="text" name="username" value="<?=htmlspecialchars($username)?>" readonly>

<label>New Password</label>
<input type="password" id="password" name="password" placeholder="enter password" required>

<ul class="password-rules">
<li id="length" class="invalid">• At least 8 characters</li>
<li id="lowercase" class="invalid">• One lowercase letter</li>
<li id="uppercase" class="invalid">• One uppercase letter</li>
<li id="number" class="invalid">• One number</li>
<li id="special" class="invalid">• One special character</li>
</ul>

<label>Confirm Password</label>
<input type="password" id="confirm_password" name="confirm_password" placeholder="confirm password" required>

<button type="submit">Update Password</button>
</form>
</div>

<div id="popup">
<div class="popup-box">
<p id="popup-msg"></p>
<button onclick="closePopup()">OK</button>
</div>
</div>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Updating password...</p>
</div>

<script>
const p=document.getElementById('password')
const c=document.getElementById('confirm_password')
const rules={
length:document.getElementById('length'),
lowercase:document.getElementById('lowercase'),
uppercase:document.getElementById('uppercase'),
number:document.getElementById('number'),
special:document.getElementById('special')
}
p.oninput=()=>{
const v=p.value
rules.length.className=v.length>=8?'valid':'invalid'
rules.lowercase.className=/[a-z]/.test(v)?'valid':'invalid'
rules.uppercase.className=/[A-Z]/.test(v)?'valid':'invalid'
rules.number.className=/\d/.test(v)?'valid':'invalid'
rules.special.className=/[\W_]/.test(v)?'valid':'invalid'
}
function validateForm(){
if(p.value!==c.value){showPopup("Passwords do not match");return false}
if(!Object.values(rules).every(r=>r.classList.contains('valid'))){
showPopup("Password does not meet requirements");return false}
document.getElementById('loader').style.display='flex'
return true
}
function showPopup(msg){
document.getElementById('popup-msg').innerText=msg
document.getElementById('popup').style.display='flex'
}
function closePopup(){
document.getElementById('popup').style.display='none'
}
<?php if($message==="success"): ?>
window.onload=()=>{
document.getElementById('loader').style.display='none'
showPopup("Password updated successfully. Redirecting...")
setTimeout(()=>location.href="login.php",3000)
}
<?php elseif($message): ?>
window.onload=()=>showPopup("<?=$message?>")
<?php endif; ?>
</script>

</body>
</html>
