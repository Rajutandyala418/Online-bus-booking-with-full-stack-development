<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../include/db_connect.php');

$message = '';
$username = $_SESSION['otp_user'] ?? '';
$max_attempts_reached = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp']);
    $username = $_SESSION['otp_user'] ?? '';

    $stmt = $conn->prepare("SELECT otp_code, otp_expiry FROM admin WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($otp_code, $otp_expiry);
    $stmt->fetch();
    $stmt->close();

    if (!$otp_code) {
        $message = "Invalid request.";
    } elseif (new DateTime() > new DateTime($otp_expiry)) {
        $message = "OTP expired. Please try again.";
        header("refresh:2;url=forgot_password.php");
        exit;
    } elseif ($entered_otp === $otp_code) {
        unset($_SESSION['otp_attempts']);
        header("Location: reset_password.php?username=" . urlencode($username));
        exit;
    } else {
        $_SESSION['otp_attempts']++;
        if ($_SESSION['otp_attempts'] >= 3) {
            $max_attempts_reached = true;
        } else {
            $message = "Invalid OTP. Attempts left: " . (3 - $_SESSION['otp_attempts']);
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP</title>
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
max-width:380px;
box-shadow:0 4px 15px rgba(0,0,0,0.06);
position:relative;
text-align:center;
}
h2{
margin-top:40px;
margin-bottom:20px;
font-weight:700;
color:#1e3c57;
}
input{
width:85%;
padding:12px;
border-radius:8px;
border:1px solid #b9c7d8;
margin-top:10px;
outline:none;
font-size:18px;
text-align:center;
letter-spacing:4px;
}
input:focus{border-color:#0072ff;}
.verify-btn,.resend-btn{
background:#1e3c57;
color:#fff;
width:100%;
padding:12px;
border:none;
border-radius:8px;
cursor:pointer;
margin-top:12px;
font-weight:600;
font-size:16px;
}
.verify-btn:hover,.resend-btn:hover{background:#264a6e;}
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
.msg{color:red;font-weight:bold;margin-top:10px;}
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
#loader .spinner{
border:8px solid #ddd;
border-top:8px solid #1e3c57;
border-radius:50%;
width:60px;
height:60px;
animation:spin 1s linear infinite;
}
@keyframes spin{100%{transform:rotate(360deg);}}
@media(max-width:480px){
.container{padding:20px;border-radius:12px;}
.verify-btn,.resend-btn{font-size:15px;padding:11px;}
.back-btn{padding:8px 15px;font-size:14px;}
input{width:90%;}
}
</style>
</head>
<body>

<div class="container">
<a href="login.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>

<h2>Enter OTP</h2>

<?php if ($message): ?>
<div class="msg"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post">
<input type="text" name="otp" maxlength="6" placeholder="Enter OTP" required>
<button type="submit" class="verify-btn">Verify OTP</button>
</form>

<form id="resendForm" action="send_otp.php" method="post">
<input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
<button type="submit" class="resend-btn">Resend OTP</button>
</form>
</div>

<div id="loader">
<div class="spinner"></div>
<p>Processing...</p>
</div>

<div id="attemptsModal" class="modal">
<div class="modal-content">
<p>Maximum Attempts Reached</p>
<button onclick="window.location.href='login.php'">Go to Login</button>
</div>
</div>

<?php if ($max_attempts_reached): ?>
<script>
document.getElementById("attemptsModal").style.display = "flex";
</script>
<?php endif; ?>

<script>
document.getElementById("resendForm").addEventListener("submit", function() {
document.getElementById("loader").style.display = "flex";
});
</script>
</body>
</html>
