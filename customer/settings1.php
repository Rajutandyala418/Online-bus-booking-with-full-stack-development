<?php
include(__DIR__ . '/../include/db_connect.php');

require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) die("Username not provided.");

$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email);
$stmt->fetch();
$stmt->close();

if (!$user_id) die("User not found.");

$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_password'])) {
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (
        preg_match('/[A-Z]/', $new) &&
        preg_match('/[a-z]/', $new) &&
        preg_match('/[0-9]/', $new) &&
        preg_match('/[\W]/', $new) &&
        strlen($new) >= 8 &&
        $new === $confirm
    ) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $user_id);
        if ($stmt->execute()) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'varahibusbooking@gmail.com';
                $mail->Password = 'pjhg nwnt haac nsiu';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus Team');
                $mail->addAddress($email, "$first_name $last_name");
                $mail->isHTML(true);
                $mail->Subject = "Password Updated";
                $mail->Body = "<p>Password updated successfully.</p>";
                $mail->send();
            } catch (Exception $e) {}
            $success = true;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings</title>
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
h2{text-align:center;margin-top:40px;margin-bottom:10px;font-weight:700;color:#1e3c57;}
label{display:block;text-align:left;margin-top:8px;font-size:14px;color:#1e3c57;}
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
.validation-msg ul{padding-left:18px;font-size:13px}
.validation-msg li{color:red}
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
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.7);
justify-content:center;
align-items:center;
z-index:1000
}
.modal-box{
background:#fff;
color:#222;
padding:20px;
border-radius:10px;
text-align:center;
width:85%;
max-width:320px
}
.modal-box button{
margin-top:12px;
background:#1e3c57;
color:#fff;
border:none;
padding:8px 14px;
border-radius:6px
}
#loader{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.75);
justify-content:center;
align-items:center;
z-index:2000;
flex-direction:column;
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

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Updating...</p>
</div>

<div class="container">
<a href="dashboard.php?username=<?=urlencode($username)?>" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h2>Change Password</h2>

<form id="pwdForm" method="POST">
<input type="hidden" name="username" value="<?=htmlspecialchars($username)?>">
<label>Username</label>
<input type="text" value="<?=htmlspecialchars($username)?>" readonly>

<label>New Password</label>
<input type="password" id="new_password" name="new_password" placeholder="enter password" required>

<div class="validation-msg">
<ul>
<li id="u">Uppercase</li>
<li id="l">Lowercase</li>
<li id="n">Number</li>
<li id="s">Special</li>
<li id="len">Min 8 chars</li>
</ul>
</div>

<label>Confirm Password</label>
<input type="password" id="confirm_password" name="confirm_password" placeholder="confirm password" required>

<button type="submit">Update Password</button>
</form>
</div>

<div id="errorModal" class="modal">
<div class="modal-box">
<p id="errorText"></p>
<button onclick="closeError()">OK</button>
</div>
</div>

<div id="successModal" class="modal">
<div class="modal-box">
<p>Password updated successfully</p>
<p>Redirecting in <span id="sec">5</span></p>
</div>
</div>

<script>
const p=document.getElementById("new_password");
const c=document.getElementById("confirm_password");
const rules={u:/[A-Z]/,l:/[a-z]/,n:/[0-9]/,s:/[\W]/,len:/.{8,}/};
p.oninput=()=>{for(let k in rules)document.getElementById(k).style.color=rules[k].test(p.value)?"green":"red";}
document.getElementById("pwdForm").onsubmit=e=>{
let ok=true;
for(let k in rules) if(!rules[k].test(p.value)) ok=false;
if(p.value!==c.value) ok=false;
if(!ok){
e.preventDefault();
document.getElementById("errorText").innerText="Password requirements not met";
document.getElementById("errorModal").style.display="flex";
return;
}
document.getElementById("loader").style.display="flex";
};
function closeError(){document.getElementById("errorModal").style.display="none";}
<?php if($success): ?>
document.getElementById("successModal").style.display="flex";
let s=5;
setInterval(()=>{
document.getElementById("sec").innerText=--s;
if(s<=0) location.href="dashboard.php?username=<?=urlencode($username)?>";
},1000);
<?php endif; ?>
</script>

</body>
</html>
