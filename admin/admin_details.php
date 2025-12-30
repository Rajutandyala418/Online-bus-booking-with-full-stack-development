<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../include/db_connect.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE admin SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $admin_id);

    if ($stmt->execute()) {
        $stmt->close();
        date_default_timezone_set('Asia/Kolkata');
        $time_now = date("Y-m-d H:i:s");

        if (!empty($email)) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com';
                $mail->Password   = 'pjhg nwnt haac nsiu';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
                $mail->addAddress($email, $first_name . ' ' . $last_name);
                $mail->isHTML(true);
                $mail->Subject = "Profile Updated Successfully";
                $mail->Body = "
                    <h3>Hello <b>{$first_name} {$last_name}</b>,</h3>
                    <p>Your profile has been updated successfully.</p>
                    <ul>
                        <li><b>First Name:</b> {$first_name}</li>
                        <li><b>Last Name:</b> {$last_name}</li>
                        <li><b>Email:</b> {$email}</li>
                        <li><b>Phone:</b> {$phone}</li>
                        <li><b>Updated On:</b> {$time_now}</li>
                    </ul>";
                $mail->send();
                echo "<script>window.location.href='admin_details.php';</script>";
                exit;
            } catch (Exception $e) {
                echo "<script>window.location.href='admin_details.php';</script>";
                exit;
            }
        } else {
            echo "<script>window.location.href='admin_details.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Update failed!');</script>";
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, phone FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<title>Admin Profile Details</title>
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
max-width:500px;
box-shadow:0 4px 15px rgba(0,0,0,0.06);
position:relative;
}
h1{
text-align:center;
margin-top:40px;
margin-bottom:15px;
font-weight:700;
color:#1e3c57;
font-size:24px;
}
table{
width:100%;
border-collapse:collapse;
margin-top:10px;
}
th,td{
padding:10px;
border:1px solid #cfcfcf;
text-align:center;
font-size:15px;
}
th{
background:#1e3c57;
color:#fff;
}
td input{
width:95%;
padding:8px;
border-radius:6px;
border:1px solid #b9c7d8;
}
button{
background:#1e3c57;
color:#fff;
padding:10px 18px;
border:none;
border-radius:8px;
cursor:pointer;
font-weight:600;
width:100%;
margin-top:10px;
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
#loader{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.6);
justify-content:center;
align-items:center;
flex-direction:column;
z-index:9999;
color:#fff;
font-size:1.1rem;
}
#loader img{width:90px;margin-bottom:10px;}
.modal{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.6);
justify-content:center;
align-items:center;
z-index:9999;
}
.modal-content{
background:white;
padding:28px;
border-radius:10px;
text-align:center;
font-size:1.1rem;
color:#2c3e50;
}
@media(max-width:768px){
.container{padding:20px;}
table{display:block;overflow-x:auto;}
.back-btn{padding:8px 14px;font-size:14px;}
td input{width:100%;}
}
@media(max-width:480px){
.container{padding:18px;}
button{font-size:14px;}
.back-btn{padding:7px 12px;font-size:13px;}
}
</style>
</head>
<body>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Updating profile...</p>
</div>

<div id="successModal" class="modal">
<div class="modal-content">
Profile updated successfully<br>
Redirecting in <span id="countdown">5</span> seconds...
</div>
</div>

<div class="container">
<a href="dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h1>Your Profile Details</h1>

<form method="post">
<table>
<tr><th>Field</th><th>Details</th></tr>
<tr><td>Username</td><td><?php echo htmlspecialchars($admin['username']); ?></td></tr>
<tr><td>First Name</td><td><input type="text" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>"></td></tr>
<tr><td>Last Name</td><td><input type="text" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>"></td></tr>
<tr><td>Email</td><td><input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>"></td></tr>
<tr><td>Phone</td><td><input type="text" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>"></td></tr>
<tr><td colspan="2"><button type="submit">Update</button></td></tr>
</table>
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

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
document.addEventListener('DOMContentLoaded',function(){
loader.style.display='none';
successModal.style.display='flex';
let timeLeft=5;
const timer=setInterval(function(){
timeLeft--;
countdownElem.textContent=timeLeft;
if(timeLeft<=0){
clearInterval(timer);
window.location.href='admin_details.php';
}
},1000);
});
<?php endif; ?>
</script>

</body>
</html>
