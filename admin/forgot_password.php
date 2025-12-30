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
$admin_list = [];
$show_admins = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $option = $_POST['option'] ?? '';
    $identifier = trim($_POST['identifier']);

    if ($option === 'username') {
        if (!empty($identifier)) {
            $stmt = $conn->prepare("SELECT username, email FROM admin WHERE email = ? OR phone = ?");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $username = $row['username'];
                $email = $row['email'];
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
                    $mail->Subject = "Your Username - Bus Booking System";
                    $mail->Body = "<p>Hello,</p><p>Your username is: <b>{$username}</b></p>";
                    $mail->send();
                    $_SESSION['msg'] = "Username has been sent to your email.";
                    header("Location: forgot_password.php");
                    exit;
                } catch (Exception $e) {
                    $message = "Error sending email.";
                }
            } else {
                echo "<script>
                document.addEventListener('DOMContentLoaded', function(){
                    showInfoPopup('No account found with this email or phone number.');
                });
                </script>";
            }
            $stmt->close();
        } else {
            $message = "Please enter your email or phone number.";
        }
    } elseif ($option === 'password') {
        if (!empty($identifier)) {
            $stmt = $conn->prepare("SELECT username, email, phone FROM admin WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $admin_list[] = $row;
            }
            $stmt->close();
            if (!empty($admin_list)) {
                $show_admins = true;
            } else {
                $message = "No admin found with that username or email.";
            }
        } else {
            $message = "Please enter a username or email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Forgot Password - Admin</title>
<style>
body{
margin:0;
padding:0;
font-family:'Poppins',sans-serif;
display:flex;
justify-content:center;
align-items:center;
min-height:100vh;
background:#f4f4f4;
overflow-x:hidden
}
.back-btn{position:absolute;top:18px;right:20px;background:#1e3c57;color:#fff;border:none;font-size:16px;padding:10px 18px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;}
.back-btn i{font-size:17px;}
.back-btn:hover{background:#264a6e;}
.bg-video{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
object-fit:cover;
z-index:-2;
opacity:.35
}
.forgot-box{
background:rgba(255,255,255,.9);
backdrop-filter:blur(8px);
border-radius:12px;
padding:25px;
color:black;
width:96%;
max-width:500px;
box-shadow:0 4px 15px rgba(0,0,0,.2);
text-align:center;
margin-top:60px;
position:relative
}
h2{
margin-top:40px;
margin-bottom:18px;
font-size:24px;
color:#2c3e50;
font-weight:700
}
.form-row{
display:flex;
flex-direction:column;
gap:6px;
align-items:flex-start;
width:100%
}
label{
font-weight:600;
color:#2c3e50
}
input,select{
padding:12px;
border-radius:8px;
border:1px solid #ccc;
font-size:.95rem;
width:92%
}
button{
padding:12px;
border-radius:8px;
border:none;
font-size:.95rem;
width:100%;
background:#1e3c57;
color:white;
font-weight:600;
cursor:pointer
}
button:hover{
background:#264a6e
}
table{
width:100%;
border-collapse:collapse;
margin-top:10px
}
th,td{
padding:8px;
border:1px solid #cfcfcf;
text-align:center;
font-size:.85rem
}
th{
background:#2c3e50;
color:white
}
tr:nth-child(even){
background:#f2f2f2
}
.reset-btn{
padding:6px 10px;
background:#00c9a7;
color:white;
border:none;
border-radius:6px;
font-size:.8rem;
cursor:pointer;
font-weight:600
}
.reset-btn:hover{
background:#009982
}
.back-inside{
position:absolute;
top:12px;
right:12px;
padding:10px 18px;
background:#1e3c57;
color:#fff;
text-decoration:none;
border-radius:8px;
font-weight:600;
font-size:.9rem;
display:inline-block
}
.back-inside:hover{
background:#264a6e
}
#loader,#loaderOverlay{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,.6);
z-index:9999;
flex-direction:column;
justify-content:center;
align-items:center;
color:white
}
#loader img{
width:95px
}
.modal{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,.6);
z-index:10000;
display:none;
justify-content:center;
align-items:center
}
.modal-content{
background:#fff;
padding:20px;
border-radius:10px;
text-align:center;
width:90%;
max-width:350px
}
.modal-content button{
margin-top:10px;
padding:10px 15px;
border:none;
border-radius:6px;
background:#1e3c57;
color:white;
font-weight:600;
cursor:pointer
}
@media(max-width:480px){
.forgot-box{
width:94%;
max-width:380px;
margin-top:55px;
padding:20px
}
h2{
font-size:20px
}
button{
font-size:.9rem
}
.reset-btn{
font-size:.75rem
}
}
</style>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
<source src="../videos/bus.mp4" type="video/mp4">
</video>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Sending username to your email...</p>
</div>

<div id="loaderOverlay">
<div style="border:6px solid #f3f3f3;border-top:6px solid #3498db;border-radius:50%;width:50px;height:50px;animation:spin 1s linear infinite;"></div>
<p>Sending OTP... Please wait</p>
</div>

<div class="forgot-box">
<a href="login.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>


<h2>Forgot Credentials</h2>

<?php 
if (isset($_SESSION['msg'])) {
echo "<script>
document.addEventListener('DOMContentLoaded', function(){
showInfoPopup('".$_SESSION['msg']."');
});
</script>";
unset($_SESSION['msg']);
}
?>

<?php if (!$show_admins): ?>
<form method="post" autocomplete="off">
<div class="form-row">
<label>Select Option:</label>
<select name="option" id="option" required>
<option value="">-- Choose --</option>
<option value="username">Forgot Username</option>
<option value="password">Forgot Password</option>
</select>
</div>
<br>
<div class="form-row">
<label id="identifier-label">Enter Email/Phone:</label>
<input type="text" name="identifier" id="identifier" placeholder="Enter value" required>
</div>
<br>
<button type="submit">Search</button>
</form>
<?php endif; ?>

<?php if ($show_admins && !empty($admin_list)): ?>
<table>
<thead>
<tr>
<th>Username</th>
<th>Email</th>
<th>Phone</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($admin_list as $admin): ?>
<tr>
<td><?php echo htmlspecialchars($admin['username']); ?></td>
<td><?php echo htmlspecialchars($admin['email']); ?></td>
<td><?php echo htmlspecialchars($admin['phone']); ?></td>
<td>
<form method="post" action="send_otp.php">
<input type="hidden" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>">
<button type="submit" class="reset-btn">Reset Password</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<script>
document.getElementById('option')?.addEventListener('change', function() {
let label = document.getElementById('identifier-label');
if (this.value === 'username') label.textContent = "Enter Email or Phone:";
else if (this.value === 'password') label.textContent = "Enter Username or Email:";
else label.textContent = "Enter Value:";
});
document.querySelectorAll('.reset-btn').forEach(function(btn) {
btn.addEventListener('click', function() {
document.getElementById('loaderOverlay').style.display = 'flex';
});
});
document.querySelector('form[method="post"]')?.addEventListener('submit', function() {
const option = document.getElementById('option')?.value;
if (option === 'username') document.getElementById('loader').style.display = 'flex';
});
function showInfoPopup(msg){
document.getElementById("infoText").innerText = msg;
document.getElementById("infoPopup").style.display = "flex";
}
function closeInfoPopup(){
document.getElementById("infoPopup").style.display = "none";
}
</script>

<div id="infoPopup" class="modal">
<div class="modal-content">
<p id="infoText"></p>
<button onclick="closeInfoPopup()">OK</button>
</div>
</div>

<style>
@keyframes spin {
0%{transform:rotate(0deg)}
100%{transform:rotate(360deg)}
}
</style>

</body>
</html>
