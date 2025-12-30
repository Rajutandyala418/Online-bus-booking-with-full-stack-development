<?php
include(__DIR__ . '/../include/db_connect.php');

require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$verifyData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'username') {
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if ($phone === '' && $email === '') {
            $message = "Please enter phone number or email.";
        } else {
            if ($phone !== '') {
                $stmt = $conn->prepare("SELECT username,email,first_name,last_name FROM users WHERE phone=?");
                $stmt->bind_param("s",$phone);
            } else {
                $stmt = $conn->prepare("SELECT username,email,first_name,last_name FROM users WHERE email=?");
                $stmt->bind_param("s",$email);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host='smtp.gmail.com';
                    $mail->SMTPAuth=true;
                    $mail->Username='varahibusbooking@gmail.com';
                    $mail->Password='pjhg nwnt haac nsiu';
                    $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port=587;
                    $mail->setFrom('varahibusbooking@gmail.com','VarahiBus');
                    $mail->addAddress($row['email'],$row['first_name'].' '.$row['last_name']);
                    $mail->isHTML(true);
                    $mail->Subject='Your VarahiBus Username';
                    $mail->Body="<p>Your username is <b>{$row['username']}</b></p>";
                    $mail->send();
                    $message="Username sent to registered email.";
                } catch(Exception $e){
                    $message="Unable to send email.";
                }
            } else {
                $message="No account found.";
            }
            $stmt->close();
        }
    }

    elseif ($action === 'password') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        $stmt = $conn->prepare("SELECT username,email,phone FROM users WHERE username=? AND email=? AND phone=?");
        $stmt->bind_param("sss",$username,$email,$phone);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $verifyData = $row;
        } else {
            $message="Details do not match.";
        }
        $stmt->close();
    }

    elseif ($action === 'confirm_reset') {
        header("Location: send_otp.php?username=".urlencode($_POST['username']));
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot</title>
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
select,input,button{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:8px;
    border:1px solid #b9c7d8;
    font-size:15px;
}
input:focus,select:focus{border-color:#0072ff;outline:none;}
button{
    background:#1e3c57;
    color:#fff;
    border:none;
    font-weight:600;
    cursor:pointer;
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
.form-section{display:none}
.active{display:block}
table{width:100%;border-collapse:collapse;margin-top:15px}
td{padding:8px;border:1px solid #ccc}
.popup{
    position:fixed;
    top:50%;left:50%;
    transform:translate(-50%,-50%);
    background:#222;
    color:#fff;
    padding:25px;
    border-radius:10px;
    text-align:center;
}
.popup button{
    margin-top:15px;
    padding:8px 16px;
    background:#ffde59;
    border:none;
    cursor:pointer;
    border-radius:6px;
}
#loader{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.75);
    z-index:9999;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    color:#fff;
}
#loader img{width:120px}
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

<h2>Forgot Username / Password</h2>

<select id="type">
<option value="username">Forgot Username</option>
<option value="password">Forgot Password</option>
</select>

<form method="post" id="fUser" class="form-section active" onsubmit="showLoader()">
<input type="hidden" name="action" value="username">
<input type="text" name="phone" placeholder="Enter registered phone (optional)">
<input type="email" name="email" placeholder="Enter registered email (optional)">
<button>Find Username</button>
</form>

<form method="post" id="fPass" class="form-section">
<input type="hidden" name="action" value="password">
<input type="text" name="username" placeholder="Enter username" required>
<input type="email" name="email" placeholder="Enter registered email" required>
<input type="text" name="phone" placeholder="Enter registered phone" required>
<button>Verify Account</button>
</form>

<?php if ($verifyData): ?>
<table>
<tr><td>Username</td><td><?=htmlspecialchars($verifyData['username'])?></td></tr>
<tr><td>Email</td><td><?=htmlspecialchars($verifyData['email'])?></td></tr>
<tr><td>Phone</td><td><?=htmlspecialchars($verifyData['phone'])?></td></tr>
</table>

<form method="post" onsubmit="showLoader()">
<input type="hidden" name="action" value="confirm_reset">
<input type="hidden" name="username" value="<?=$verifyData['username']?>">
<button style="margin-top:15px">Confirm & Send OTP</button>
</form>
<?php endif; ?>

</div>

<?php if ($message): ?>
<div class="popup">
<p><?=$message?></p>
<button onclick="this.parentElement.remove()">OK</button>
</div>
<?php endif; ?>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Verifying...</p>
</div>

<script>
const t=document.getElementById('type')
const u=document.getElementById('fUser')
const p=document.getElementById('fPass')
t.onchange=()=>{u.classList.toggle('active',t.value==='username');p.classList.toggle('active',t.value==='password')}
function showLoader(){document.getElementById('loader').style.display='flex'}
</script>

</body>
</html>
