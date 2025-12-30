<?php
session_start();
include(__DIR__ . '/../include/db_connect.php');

$message = '';
$username = $_GET['username'] ?? '';

if (!$username) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        header("Location: send_otp.php?username=" . urlencode($username));
        exit();
    }

    $entered = trim($_POST['otp'] ?? '');

    $stmt = $conn->prepare("SELECT id, otp, otp_expiry, otp_attempts FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        $message = "User not found.";
    } else {
        $stmt->bind_result($user_id, $otp, $otp_expiry, $otp_attempts);
        $stmt->fetch();
        $stmt->close();

        $now = new DateTime('now');
        $expiry_dt = $otp_expiry ? new DateTime($otp_expiry) : null;

        if (!$otp || !$expiry_dt || $now > $expiry_dt) {
            $clear = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL, otp_attempts = 0 WHERE id = ?");
            $clear->bind_param("i", $user_id);
            $clear->execute();
            $clear->close();
            $message = "OTP expired. Please request a new code.";
        } else {
            if ($entered === $otp) {
                $clear = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL, otp_attempts = 0 WHERE id = ?");
                $clear->bind_param("i", $user_id);
                $clear->execute();
                $clear->close();
                header("Location: update_password.php?username=" . urlencode($username));
                exit();
            } else {
                $otp_attempts = (int)$otp_attempts + 1;
                $u = $conn->prepare("UPDATE users SET otp_attempts = ? WHERE id = ?");
                $u->bind_param("ii", $otp_attempts, $user_id);
                $u->execute();
                $u->close();

                if ($otp_attempts >= 5) {
                    $clear = $conn->prepare("UPDATE users SET otp = NULL, otp_expiry = NULL, otp_attempts = 0 WHERE id = ?");
                    $clear->bind_param("i", $user_id);
                    $clear->execute();
                    $clear->close();
                    $message = "Too many incorrect attempts. Redirecting to login...";
                    header("Refresh:3; url=login.php");
                } else {
                    $remaining = 5 - $otp_attempts;
                    $message = "Incorrect OTP. You have {$remaining} attempt(s) left.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP</title>
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
    text-align:center;
}
.container h2{
    margin-top:40px;
    margin-bottom:10px;
    font-weight:700;
    color:#1e3c57;
}
input,button{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:8px;
    border:1px solid #b9c7d8;
    font-size:15px;
}
input{background:#fafafa;}
input:focus{border-color:#0072ff;outline:none;}
button{
    background:#1e3c57;
    color:#fff;
    border:none;
    cursor:pointer;
    font-weight:600;
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
.message{color:#333;margin:10px 0;}
#loader{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.7);
    z-index:9999;
    align-items:center;
    justify-content:center;
    flex-direction:column;
    color:#fff;
}
#loader img{
    width:120px;
    margin-bottom:10px;
}
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

<h2>Enter OTP</h2>

<?php if ($message): ?>
<p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="post" onsubmit="showLoader()">
<input type="text" name="otp" placeholder="Enter 6-digit code" required pattern="\d{6}" maxlength="6">
<button type="submit">Verify</button>
</form>

<form method="post" style="margin-top:5px" onsubmit="showLoader()">
<input type="hidden" name="resend" value="1">
<button type="submit">Resend OTP</button>
</form>
</div>

<div id="loader">
<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
<p>Hold your breath...</p>
</div>

<script>
function showLoader(){document.getElementById('loader').style.display='flex'}
</script>

</body>
</html>
