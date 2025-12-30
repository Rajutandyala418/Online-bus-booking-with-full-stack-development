<?php
include(__DIR__ . '/../include/db_connect.php');

require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) die("Username not provided.");

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();

if (!$user_id) die("User not found.");

$message = "";
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $first_name_new = trim($_POST['first_name']);
    $last_name_new  = trim($_POST['last_name']);
    $email_new      = trim($_POST['email']);
    $phone_new      = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param("ssssi", $first_name_new, $last_name_new, $email_new, $phone_new, $user_id);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";

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
            $mail->addAddress($email_new, "$first_name_new $last_name_new");
            $mail->isHTML(true);
            $mail->Subject = "Profile Updated - VarahiBus Account";
            $mail->Body = "<p>Dear {$first_name_new} {$last_name_new},</p><p>Your profile has been updated.</p><p>If this wasn't you, please change your password immediately.</p>";
            $mail->send();
        } catch (Exception $e) {}

        $first_name = $first_name_new;
        $last_name  = $last_name_new;
        $email      = $email_new;
        $phone      = $phone_new;
    } else {
        $error_msg = "Update failed! Please try again.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Profile</title>
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
margin-bottom:15px;
font-weight:700;
color:#1e3c57
}
table{width:100%;border-collapse:collapse}
th,td{padding:8px;font-size:.95rem}
th{color:#1e3c57;text-align:left;width:40%}
input{
width:100%;
padding:10px;
border-radius:8px;
border:1px solid #b9c7d8;
background:#fafafa;
font-size:.95rem;
margin-top:4px
}
input:focus{border-color:#0072ff;outline:none;}
button[type=submit]{
width:100%;
margin-top:12px;
padding:12px;
font-size:1rem;
border:none;
border-radius:8px;
background:#1e3c57;
color:white;
cursor:pointer;
font-weight:600
}
button[type=submit]:hover{background:#264a6e}
.message{color:green;text-align:center;margin-bottom:8px}
.error{color:red;text-align:center;margin-bottom:8px}
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
#loader{
display:none;
position:fixed;
inset:0;
background:rgba(0,0,0,.7);
z-index:9999;
justify-content:center;
align-items:center;
flex-direction:column;
color:white
}
#loader img{width:130px}
@media(max-width:480px){
.container{padding:20px;border-radius:12px;}
button{font-size:15px;padding:11px;}
.back-btn{padding:8px 15px;font-size:14px;}
#loader img{width:95px;}
th{font-size:.9rem}
td{font-size:.9rem}
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

<h2>Your Profile</h2>

<?php if($message): ?>
<div class="message"><?=htmlspecialchars($message)?></div>
<?php elseif($error_msg): ?>
<div class="error"><?=htmlspecialchars($error_msg)?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="username" value="<?=htmlspecialchars($username)?>">

<table>
<tr><th>Username</th><td><input type="text" value="<?=htmlspecialchars($username)?>" readonly></td></tr>
<tr><th>First Name</th><td><input type="text" name="first_name" value="<?=htmlspecialchars($first_name)?>" required></td></tr>
<tr><th>Last Name</th><td><input type="text" name="last_name" value="<?=htmlspecialchars($last_name)?>" required></td></tr>
<tr><th>Email</th><td><input type="email" name="email" value="<?=htmlspecialchars($email)?>" required></td></tr>
<tr><th>Phone</th><td><input type="text" name="phone" value="<?=htmlspecialchars($phone)?>" pattern="[0-9]{10}" required></td></tr>
</table>

<button type="submit">Update Profile</button>
</form>
</div>

<script>
document.querySelector("form").addEventListener("submit",()=>{
document.getElementById("loader").style.display="flex";
});
</script>

</body>
</html>
