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
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $password_invalid = false;
    if (strlen($password) < 8) $password_invalid = true;
    if (!preg_match('/[A-Z]/', $password)) $password_invalid = true;
    if (!preg_match('/[a-z]/', $password)) $password_invalid = true;
    if (!preg_match('/\d/', $password)) $password_invalid = true;
    if (!preg_match('/[\W_]/', $password)) $password_invalid = true;
    if ($password !== $confirm_password) $password_invalid = true;
    if ($password_invalid) $errors[] = "Password does not meet requirements.";

    if (empty($errors)) {
        $check = $conn->prepare("
            SELECT id FROM admin WHERE username = ? OR email = ? OR phone = ?
            UNION
            SELECT id FROM registration_requests WHERE username = ? OR email = ? OR phone = ?
        ");
        $check->bind_param("ssssss", $username, $email, $phone, $username, $email, $phone);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) $errors[] = "Username, Email, or Phone already exists. Please choose another.";
        $check->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO registration_requests (username, first_name, last_name, email, phone, password)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $phone, $hashed_password);

        if ($stmt->execute()) {
            $toName = $first_name . ' ' . $last_name;
            $toEmail = $email;
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com';
                $mail->Password   = 'pjhg nwnt haac nsiu';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus System');
                $mail->addAddress($toEmail, $toName);
                $mail->isHTML(true);
                $mail->Subject = "Registration Received - Varahi Bus Booking";
                $mail->Body = "<p>Dear <b>{$toName}</b>,</p><p>Your registration request has been received and is pending approval.</p>";
                $mail->send();

                $adminQuery = $conn->query("SELECT email, first_name, last_name FROM admin WHERE id = 3 LIMIT 1");
                if ($adminQuery && $adminQuery->num_rows > 0) {
                    $admin = $adminQuery->fetch_assoc();
                    $adminEmail = $admin['email'];
                    $adminName = $admin['first_name'] . ' ' . $admin['last_name'];
                    $adminMail = new PHPMailer(true);
                    $adminMail->isSMTP();
                    $adminMail->Host = 'smtp.gmail.com';
                    $adminMail->SMTPAuth = true;
                    $adminMail->Username = 'varahibusbooking@gmail.com';
                    $adminMail->Password = 'pjhg nwnt haac nsiu';
                    $adminMail->SMTPSecure = 'tls';
                    $adminMail->Port = 587;
                    $adminMail->setFrom('varahibusbooking@gmail.com', 'VarahiBus System');
                    $adminMail->addAddress($adminEmail, $adminName);
                    $adminMail->isHTML(true);
                    $adminMail->Subject = "New Registration Request";
                    $adminMail->Body = "<p>New admin registration request submitted.</p>";
                    $adminMail->send();
                }
            } catch (Exception $e) {}

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const modal = document.getElementById('successModal');
                    const countdownElem = document.getElementById('countdown');
                    let timeLeft = 5;
                    modal.style.display = 'flex';
                    const timer = setInterval(function() {
                        timeLeft--;
                        countdownElem.textContent = timeLeft;
                        if (timeLeft <= 0) {
                            clearInterval(timer);
                            window.location.href = 'login.php';
                        }
                    }, 1000);
                });
            </script>";
        } else $errors[] = 'Database error: ' . $conn->error;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Sign Up</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif}
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
max-width:500px;
box-shadow:0 4px 15px rgba(0,0,0,0.06);
position:relative
}
h2{
text-align:center;
margin-top:40px;
margin-bottom:10px;
font-weight:700;
color:#1e3c57
}
.form-group{margin-top:10px}
label{
color:#1e3c57;
font-size:1rem;
font-weight:500
}
input{
width:100%;
padding:12px;
border-radius:8px;
border:1px solid #b9c7d8;
margin-top:6px;
font-size:.95rem;
background:#fafafa
}
input:focus{border-color:#0072ff;outline:none}
button{
width:100%;
padding:14px;
border-radius:8px;
border:none;
margin-top:10px;
background:#1e3c57;
color:white;
font-weight:600;
font-size:1rem;
cursor:pointer
}
button:hover{background:#264a6e}
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
.validation-msg{font-size:.8rem;text-align:center;margin-top:3px}
.errors{color:red;text-align:center}
.validation-msg.red{color:red}
.validation-msg.green{color:green}
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.6);
justify-content:center;
align-items:center;
z-index:9999
}
.modal-content{
background:white;
padding:30px;
border-radius:10px;
text-align:center;
font-size:1.1rem;
color:black
}
#loader{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,0.6);
justify-content:center;
align-items:center;
z-index:9999;
flex-direction:column;
color:white
}
#loader img{width:100px}
@media(max-width:500px){
.container{padding:20px}
.back-btn{padding:8px 14px;font-size:14px}
button{padding:12px;font-size:.95rem}
}
</style>
</head>
<body>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Registering your Account...</p>
</div>

<div class="container">
<a href="login.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h2>Admin Sign Up</h2>

<?php if(!empty($errors)): ?>
<div class="errors"><?php echo implode("<br>",$errors); ?></div>
<?php endif; ?>

<form method="post" id="registerForm">
<div class="form-group">
<label>Username</label>
<input type="text" name="username" id="username" placeholder="Enter username" required>
</div>
<div id="usernameMsg" class="validation-msg"></div>

<div class="form-group">
<label>First Name</label>
<input type="text" name="first_name" placeholder="Enter first name" required>
</div>

<div class="form-group">
<label>Last Name</label>
<input type="text" name="last_name" placeholder="Enter last name" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" id="email" placeholder="Enter email address" required>
</div>
<div id="emailMsg" class="validation-msg"></div>

<div class="form-group">
<label>Phone</label>
<input type="text" name="phone" id="phone" placeholder="Enter phone number" required>
</div>
<div id="phoneMsg" class="validation-msg"></div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password" id="password" placeholder="Create strong password" required>
</div>

<div class="form-group">
<label>Confirm Password</label>
<input type="password" name="confirm_password" placeholder="Re-enter password" required>
</div>

<button type="submit">Sign Up</button>
</form>
</div>

<div id="successModal" class="modal">
<div class="modal-content">
Registration successful. Redirecting in <span id="countdown">5</span> seconds...
</div>
</div>

<script>
function checkAvailability(field,value){
if(!value)return;
fetch('validate.php?field='+field+'&value='+encodeURIComponent(value))
.then(r=>r.json())
.then(d=>{
const el=document.getElementById(field+'Msg');
if(d.exists){
el.textContent=field+" already exists.";
el.classList.remove("green");
el.classList.add("red");
}else{
el.textContent=field+" is available.";
el.classList.remove("red");
el.classList.add("green");
}
});
}
document.getElementById("username").addEventListener("blur",e=>checkAvailability("username",e.target.value))
document.getElementById("email").addEventListener("blur",e=>checkAvailability("email",e.target.value))
document.getElementById("phone").addEventListener("blur",e=>checkAvailability("phone",e.target.value))

document.getElementById("registerForm").addEventListener("submit",()=>{
document.getElementById("loader").style.display="flex";
});
</script>
</body>
</html>
