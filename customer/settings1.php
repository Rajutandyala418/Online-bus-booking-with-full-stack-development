<?php
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get username from POST or GET
$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) die("Username not provided.");

// Fetch user details
$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email);
$stmt->fetch();
$stmt->close();

if (!$user_id) die("User not found.");

$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error_msg = "New password and confirmation do not match.";
    } else {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);

        if ($stmt->execute()) {
            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com';
                $mail->Password   = 'pjhg nwnt haac nsiu';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus Team');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = "Password Updated - VarahiBus Account";
                $mail->Body = "
                    <p>Dear {$first_name} {$last_name},</p>
                    <p>You have <b>successfully updated your password</b> for your VarahiBus account.</p>
                    <p>You can now login with your new password.</p>
                    <p style='color:red; font-weight:bold;'>⚠ If this was <u>not you</u>, please change your password immediately.</p>
                    <br>
                    <p>Thank you,<br><b>VarahiBus Team</b></p>
                ";
                $mail->send();
            } catch (Exception $e) {}

            $success_msg = "Password updated successfully!";
        } else {
            $error_msg = "Error updating password. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Settings</title>
    <style>
        body { font-family: 'Poppins', sans-serif; background: black; color: white; text-align: center; margin:0; }
        .container {
            margin: 120px auto 50px auto; width: 400px; padding: 30px; border-radius: 10px;
            background: linear-gradient(135deg, #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00);
        }
        label { display: block; text-align: left; margin: 10px 0 5px; color: blue; }
        input[type="text"], input[type="password"] {
            width: 90%; padding: 10px; margin-bottom: 15px; border: none; border-radius: 5px;
        }
.back-btn {
    background: green;
    color: white;
    font-size: 16px;
    padding: 10px;
    border: none;
    border-radius: 5px;
    width: 100%;
    margin-top: 10px;
    cursor: pointer;
}
.back-btn:hover {
    background: darkgreen;
}

        input[readonly] { background: #333; color: #ccc; }
        .submit-btn { background: red; color: black; font-size: 18px; padding: 10px; border: none; border-radius: 5px; width: 100%; cursor: pointer; }
        .error { color: yellow; margin-top: 10px; }
        .top-bar { position: fixed; top: 10px; right: 20px; }
        .top-bar a { color:blue; font-weight:bold; text-decoration:none; font-size: 25px; background-color:green;}
        .validation-msg { text-align:left; font-size:13px; margin:5px 0; color : blue;}
        .validation-msg li { margin-left:20px; color:red; }

        /* Success Modal */
        .modal { display: none; position: fixed; z-index: 1000; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8); justify-content:center; align-items:center; }
        .modal-content { background:#222; padding:20px; border-radius:10px; text-align:center; font-size:18px; color:#0ff; box-shadow:0 0 20px #0ff; }
    </style>
</head>
<body>


<div class="container">
    <h2>Change Password</h2>
    <form method="POST" onsubmit="return validatePassword()">
        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">

        <label>Username</label>
        <input type="text" value="<?= htmlspecialchars($username) ?>" readonly>

        <label>New Password</label>
        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>

        <div class="validation-msg">
            Password must contain:
            <ul>
                <li id="upper">At least one uppercase letter</li>
                <li id="lower">At least one lowercase letter</li>
                <li id="number">At least one number</li>
                <li id="special">At least one special character</li>
                <li id="length">Minimum 8 characters</li>
            </ul>
        </div>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>

        <button type="submit" class="submit-btn">Update Password</button>
<button type="button" class="back-btn" 
        onclick="window.location.href='dashboard.php?username=<?= urlencode($username) ?>'">
    ⬅ Back to Dashboard
</button>

    </form>

    <?php if ($error_msg): ?>
        <p class="error"><?= $error_msg ?></p>
    <?php endif; ?>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        🎉 Password updated successfully!<br>
        Redirecting to login page in <span id="countdown">5</span> seconds...
    </div>
</div>

<script>
function validatePassword() {
    let password = document.getElementById("new_password").value;
    let confirm = document.getElementById("confirm_password").value;

    let upper = /[A-Z]/.test(password);
    let lower = /[a-z]/.test(password);
    let number = /[0-9]/.test(password);
    let special = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    let length = password.length >= 8;

    if (!upper || !lower || !number || !special || !length) {
        alert("Password does not meet requirements.");
        return false;
    }

    if (password !== confirm) {
        alert("Passwords do not match.");
        return false;
    }
    return true;
}

// Live password validation
document.getElementById("new_password").addEventListener("input", function() {
    let val = this.value;
    document.getElementById("upper").style.color = /[A-Z]/.test(val) ? "blue" : "red";
    document.getElementById("lower").style.color = /[a-z]/.test(val) ? "blue" : "red";
    document.getElementById("number").style.color = /[0-9]/.test(val) ? "blue" : "red";
    document.getElementById("special").style.color = /[!@#$%^&*(),.?\":{}|<>]/.test(val) ? "blue" : "red";
    document.getElementById("length").style.color = val.length >= 8 ? "blue" : "red";
});

// Show success modal if password updated
<?php if($success_msg): ?>
document.addEventListener("DOMContentLoaded", function() {
    let modal = document.getElementById("successModal");
    modal.style.display = "flex";

    let seconds = 5;
    let countdown = document.getElementById("countdown");
    let interval = setInterval(function(){
        seconds--;
        countdown.textContent = seconds;
        if(seconds <= 0){
            clearInterval(interval);
            window.location.href = 'logout.php';
        }
    }, 1000);
});
<?php endif; ?>
</script>

</body>
</html>
