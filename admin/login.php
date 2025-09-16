<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../include/db_connect.php');

// PHPMailer files
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch the admin record with email
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($admin_id, $first_name, $last_name, $email, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // ✅ Session setup
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_first_name'] = $first_name;
            $_SESSION['admin_last_name'] = $last_name;

            // ✅ Send login email
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
                $mail->Subject = "Login Notification - Bus Booking Admin Panel";
                
                date_default_timezone_set('Asia/Kolkata');
$loginTime = date("Y-m-d H:i:s");

                $mail->Body = "
                    <p>Welcome <b>$first_name $last_name</b>,</p>
                    <p>You have successfully logged in to your admin account on <b>$loginTime</b>.</p>
                    <p>If this wasn’t you, please reset your password immediately or contact the system administrator.</p>
                    <br>
                    <p>Regards,<br>Bus Booking System</p>
                ";
                $mail->AltBody = "Welcome $first_name $last_name,\nYou logged in on $loginTime.\nIf this wasn’t you, please reset your password immediately.";

                $mail->send();
            } catch (Exception $e) {
                // Optional: log the error, but don’t block login
            }

            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Invalid username or password.";
        }
    } else {
        $message = "Invalid username or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
            background: rgba(0, 0, 0, 0.5);
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
        .login-box {
             background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
             );

            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            width: 95%;
            max-width: 520px;
            color: white;
            z-index: 1;
        }
        h2 { margin-bottom: 20px; font-size:30px; color: black; }
        input, button {
            width: 100%; padding: 10px;
            margin: 10px 0; border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
        input {
            background: rgba(255, 255, 255, 0.8);
            color: #333;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white; cursor: pointer;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message { color: blue; font-size: 0.9rem; }
        a {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: red;
            font-size: 1.2rem;
            text-shadow: 0 0 5px #00f7ff, 0 0 10px #00f7ff;
            transition: color 0.3s, text-shadow 0.3s;
        }
.form-group {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 12px 0;
}

.form-group label {
    flex: 0 0 100px;   /* fixed width for labels */
    text-align: left;
    font-weight: 600;
    color: black;
}

.form-group input {
    flex: 1;   /* input takes remaining space */
    padding: 10px;
}

        a:hover {
            color: blue;
            text-shadow: 0 0 10px #00eaff, 0 0 20px #00eaff;
        }
        .back-to-main {
            position: absolute;
            top: 20px;
            right: 30px;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.6);
            color: yellow;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .back-to-main:hover {
            background: rgba(0, 0, 0, 0.8);
        }
.extra-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 15px;
    gap: 10px;
}

.btn-link {
    flex: 1;
    text-align: center;
    padding: 10px;
    border-radius: 6px;
    background: linear-gradient(90deg, #36d1dc, #5b86e5);
    color: white;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.3s;
}

.btn-link:hover {
    background: linear-gradient(90deg, #5b86e5, #36d1dc);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .form-group {
        flex-direction: column;
        align-items: stretch;
    }
    .form-group label {
        flex: none;
        margin-bottom: 5px;
    }
    .extra-buttons {
        flex-direction: column;
    }
    .back-to-main {
        top: 10px;
        right: 10px;
        padding: 8px 15px;
        font-size: 0.9rem;
    }
}
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<a href="../index.php" class="back-to-main">Back to Main Page</a>

<div class="login-box">
    <h2>Admin Login</h2>
    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
   <form method="post">
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" placeholder = "enter username " autocomplete="off" required>
    </div>

    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder = "enter password " required>
    </div>

    <button type="submit">Login</button>
</form>

<div class="extra-buttons">
    <a href="forgot_password.php" class="btn-link">Forgot Password?</a>
    <a href="register.php" class="btn-link">Register</a>
</div>
</div>
</body>
</html>
