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
$registration_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!preg_match($password_pattern, $password)) {
        $message = "Password must be at least 8 characters long, include upper and lower case letters, a number, and a special character.";
    } else {
        $stmt_check = $conn->prepare("SELECT username, email, phone FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt_check->bind_param("sss", $username, $email, $phone);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        $exists_username = $exists_email = $exists_phone = false;
        while ($row = $result_check->fetch_assoc()) {
            if ($row['username'] === $username) $exists_username = true;
            if ($row['email'] === $email) $exists_email = true;
            if ($row['phone'] === $phone) $exists_phone = true;
        }
        $stmt_check->close();

        if ($exists_username) {
            $message = "Username already exists. Please choose another.";
        } elseif ($exists_email) {
            $message = "Email already exists. Please use another email.";
        } elseif ($exists_phone) {
            $message = "Phone number already exists. Please use another phone number.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $username, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $registration_success = true;
                $message = "Registration successful!";

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
                    $mail->addAddress($email, $first_name . " " . $last_name);

                    $mail->isHTML(true);
                    $mail->Subject = "Welcome to VarahiBus";
                    $mail->Body    = "
                        <p>Dear {$first_name} {$last_name},</p>
                        <p>Welcome to <b>VarahiBus</b>! Your account has been successfully created.</p>
                        <p>Username: <b>{$username}</b></p>
                        <p>You can now log in and book your bus tickets.</p>
                        <p>Thank you for joining us!</p>
                    ";
                    $mail->AltBody = "Dear {$first_name} {$last_name}, Welcome to VarahiBus! Username: {$username}";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mailer Error: " . $mail->ErrorInfo);
                }
            } else {
                $message = "Error occurred during registration. Please try again.";
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['check_field']) && isset($_GET['value'])) {
    $field = $_GET['check_field'];
    $value = trim($_GET['value']);
    if (!in_array($field, ['username', 'email', 'phone'])) {
        echo json_encode(["status" => "invalid"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT $field FROM users WHERE $field = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode(["status" => $result->num_rows > 0 ? "exists" : "available"]);
    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Registration</title>
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
.container h2{
    text-align:center;
    margin-top:40px;
    margin-bottom:10px;
    font-weight:700;
    color:#1e3c57;
}
label{font-weight:500;font-size:14px;color:#1e3c57;}
input{width:100%;padding:11px;border-radius:8px;border:1px solid #b9c7d8;margin-top:6px;outline:none;font-size:15px;}
input:focus{border-color:#0072ff;}
input::placeholder{color:#9bb1c7;font-size:14px;}
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
    font-size:16px;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:6px;
}
.back-btn:hover{background:#264a6e;}
.availability{font-size:0.8rem;margin-bottom:10px;}
.available{color:green;}
.exists{color:red;}
.password-rules{font-size:12px;margin-top:5px;margin-bottom:10px;}
.valid{color:green;}
.invalid{color:red;}
.popup{
    position:fixed;
    top:50%;left:50%;
    transform:translate(-50%,-50%);
    background:#222;
    color:#fff;
    padding:30px;
    border-radius:10px;
    text-align:center;
}
.popup button{
    margin-top:15px;
    padding:8px 18px;
    background:#ffde59;
    border:none;
    border-radius:5px;
    cursor:pointer;
    font-weight:bold;
}
.message{color:red;text-align:center;margin-bottom:10px;font-weight:bold;}
.success{color:green;text-align:center;}
@media(max-width:480px){
.container{padding:20px;border-radius:12px;}
button{font-size:15px;padding:11px;}
.back-btn{padding:8px 15px;font-size:14px;}
}
</style>
</head>

<body>

<div class="container">
<a href="login.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h2>Customer Registration</h2>

<?php if($message && !$registration_success): ?>
<p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post" onsubmit="return validateForm()">

<label>First Name</label>
<input type="text" name="first_name" placeholder="Enter first name" required>

<label>Last Name</label>
<input type="text" name="last_name" placeholder="Enter last name" required>

<label>Username</label>
<input type="text" id="username" name="username" placeholder="Enter username" required>
<div id="username_status" class="availability"></div>

<label>Email</label>
<input type="email" id="email" name="email" placeholder="Enter email address" required>
<div id="email_status" class="availability"></div>

<label>Phone</label>
<input type="text" id="phone" name="phone" placeholder="Enter phone number" required pattern="[0-9]{10}">
<div id="phone_status" class="availability"></div>

<label>Password</label>
<input type="password" id="password" name="password" placeholder="Enter password" required>

<ul class="password-rules" id="passwordRules">
<li id="length" class="invalid">At least 8 characters</li>
<li id="lowercase" class="invalid">At least one lowercase</li>
<li id="uppercase" class="invalid">At least one uppercase</li>
<li id="number" class="invalid">At least one number</li>
<li id="special" class="invalid">At least one special character</li>
</ul>

<label>Confirm Password</label>
<input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>

<button type="submit">Sign Up</button>
</form>
</div>

<?php if ($registration_success): ?>
<div class="popup" id="successPopup">
<h3>Registration Successful!</h3>
<p>Redirecting to login in <span id="countdown">5</span> seconds...</p>
</div>
<script>
let counter=5;
const countdownEl=document.getElementById('countdown');
const interval=setInterval(()=>{
counter--;
countdownEl.textContent=counter;
if(counter<=0){
clearInterval(interval);
window.location.href="login.php";
}
},1000);
</script>
<?php endif; ?>

<script>
const passwordInput=document.getElementById('password');
const confirmPasswordInput=document.getElementById('confirm_password');
const rules={
length:document.getElementById('length'),
lowercase:document.getElementById('lowercase'),
uppercase:document.getElementById('uppercase'),
number:document.getElementById('number'),
special:document.getElementById('special')
};

passwordInput.addEventListener('input',()=>{
const value=passwordInput.value;
rules.length.className=value.length>=8?'valid':'invalid';
rules.lowercase.className=/[a-z]/.test(value)?'valid':'invalid';
rules.uppercase.className=/[A-Z]/.test(value)?'valid':'invalid';
rules.number.className=/\d/.test(value)?'valid':'invalid';
rules.special.className=/[\W_]/.test(value)?'valid':'invalid';
});

function validateForm(){
if(passwordInput.value!==confirmPasswordInput.value){
alert("Passwords do not match!");
return false;
}
const allValid=Object.values(rules).every(rule=>rule.classList.contains('valid'));
if(!allValid){
alert("Password does not meet all requirements!");
return false;
}
return true;
}

function checkAvailability(field,value){
if(!value){
document.getElementById(field+"_status").innerHTML="";
return;
}
fetch("?check_field="+field+"&value="+encodeURIComponent(value))
.then(res=>res.json())
.then(data=>{
const statusEl=document.getElementById(field+"_status");
if(data.status==="exists"){
statusEl.innerHTML=field+" already exists.";
statusEl.className="availability exists";
}else{
statusEl.innerHTML=field+" is available.";
statusEl.className="availability available";
}
});
}

document.getElementById('username').addEventListener('blur',e=>checkAvailability('username',e.target.value));
document.getElementById('email').addEventListener('blur',e=>checkAvailability('email',e.target.value));
document.getElementById('phone').addEventListener('blur',e=>checkAvailability('phone',e.target.value));
</script>

</body>
</html>
