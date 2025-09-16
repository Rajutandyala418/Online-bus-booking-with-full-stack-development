<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php'); // same path style as login.php
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
    $secret_key = trim($_POST['secret_key']);

    // Password validations
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must include at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) $errors[] = "Password must include at least one lowercase letter.";
    if (!preg_match('/\d/', $password)) $errors[] = "Password must include at least one digit.";
    if (!preg_match('/[\W_]/', $password)) $errors[] = "Password must include at least one special character.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    // Secret key check
    if ($secret_key !== "051167") {
        $errors[] = "Secret key is invalid.";
    }

    if (empty($errors)) {

        // ✅ Added check for duplicates
        $check = $conn->prepare("SELECT id FROM admin WHERE username = ? OR email = ? OR phone = ?");
        $check->bind_param("sss", $username, $email, $phone);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "Username, Email, or Phone already exists. Please choose another.";
        }
        $check->close();
    }

    if (empty($errors)) {
        // Hash password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO admin (username, first_name, last_name, email, phone, password, secret_key)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss", $username, $first_name, $last_name, $email, $phone, $hashed_password, $secret_key);
        if ($stmt->execute()) {
            // --- Send congratulation email ---
            $toName = $first_name . ' ' . $last_name;
            $toEmail = $email;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com';
                $mail->Password   = 'pjhg nwnt haac nsiu';  // Gmail app password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus System');
                $mail->addAddress($toEmail, $toName);

                $mail->isHTML(true);
                $mail->Subject = " Welcome to VarahiBus - Admin Registration Successful!";
                $mail->Body    = "
                    <p>Dear <b>{$toName}</b>,</p>
                    <p>🎉 Congratulations! You have successfully registered as an <b>Admin</b> in <span style='color:#ff512f;'>VarahiBus</span>.</p>
                    <p>We sincerely appreciate your involvement and trust in our system. Please remember:</p>
                    <ul>
                        <li>Keep your login credentials safe and never share them with anyone.</li>
                        <li>Ensure you log out after each session, especially on public devices.</li>
                        <li>In case of any issues, contact our support team immediately.</li>
                    </ul>
                    <p>Thank you for joining VarahiBus. We look forward to your valuable contribution!</p>
                    <br>
                    <p style='color:#888;'>--<br>VarahiBus Team</p>
                ";
                $mail->AltBody = "Dear {$toName},\n\nCongratulations! You are successfully registered as an Admin in VarahiBus.\n\nPlease keep your credentials safe and logout after use.\n\nThank you for joining VarahiBus!\n\n-- VarahiBus Team";

                $mail->send();
            } catch (Exception $e) {
                // Fail silently but log if needed
                error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
            }
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

        } else {
            $errors[] = "Database Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Sign Up</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            overflow-y: auto;
        }
        .register-box {
             background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
             );

            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            width: 500px;
            color: white;
            margin: 80px auto 50px auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: black;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .form-group label {
            flex: 0 0 150px;
            color: blue;
            font-weight: 500;
            font-size: 1.2rem;
        }
        .form-group input {
            flex: 1;
            padding: 15px;
            border-radius: 10px;
            border: none;
            background: rgba(255,255,255,0.8);
            color: #333;
        }
        button {
            width: 100%;
            padding: 20px;
            margin-top: 15px;
            border-radius: 5px;
            border: none;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message { color: #00ff88; text-align: center; font-size: 0.9rem; }
        .errors { color: #ff8080; font-size: 0.9rem; }
        .back-btn {
            position: fixed; top: 20px; right: 20px;
            padding: 8px 16px; background: rgba(0, 0, 0, 0.6);
            color: #00f7ff; text-decoration: none; border-radius: 6px; font-weight: 600;
            box-shadow: 0 0 10px #00f7ff, 0 0 20px #00f7ff, 0 0 30px #00f7ff;
            transition: background 0.3s, box-shadow 0.3s; z-index: 10;
        }
        .back-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 15px #00f7ff, 0 0 30px #00f7ff, 0 0 45px #00f7ff;
        }
        .bg-video {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; object-fit: cover; z-index: -1;
        }
        .validation-msg { font-size: 0.85rem; margin-top: 3px; color: blue; text-align:center; }
        .validation-msg.valid { color: green; text-align : center;}
        /* Modal for success */
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.6);
            justify-content: center; align-items: center; z-index: 9999;
        }
        .modal-content {
            background: white; padding: 30px; border-radius: 10px;
            text-align: center; font-size: 1.2rem; color: black;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        /* Password rules list */
        #passwordRules {
            list-style: none;
            padding-left: 0;
            margin-top: 5px;
        }
        #passwordRules li {
            color: red;
            margin-bottom: 3px;
            font-size: 0.85rem;
            transition: color 0.2s, font-weight 0.2s;
        }
        #passwordRules li.valid {
            color: green;
            font-weight: 600;
        }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>
<a href="login.php" class="back-btn">Back to Login</a>
<div class="register-box">
    <h2>Admin Sign Up</h2>
    <?php if (!empty($errors)): ?>
        <div class="errors"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
    <?php endif; ?>
    <?php if ($message): ?><p class="message"><?php echo $message; ?></p><?php endif; ?>
    <form method="post" id="registerForm">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" required>
        </div>
        <div id="usernameMsg" class="validation-msg"></div>

        <div class="form-group">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" placeholder="Enter your first name" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" placeholder="Enter your last name" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder="Enter your email address" required>
        </div>
        <div id="emailMsg" class="validation-msg"></div>

        <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" name="phone" id="phone" placeholder="Enter your phone number" required>
        </div>
        <div id="phoneMsg" class="validation-msg"></div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Create a strong password" required>
        </div>
        <ul id="passwordRules">
            <li id="rule-length">At least 8 characters</li>
            <li id="rule-upper">One uppercase letter</li>
            <li id="rule-lower">One lowercase letter</li>
            <li id="rule-digit">One digit</li>
            <li id="rule-special">One special character</li>
        </ul>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" required>
        </div>

        <div class="form-group">
            <label for="secret_key">Secret Key</label>
            <input type="password" name="secret_key" id="secret_key" placeholder="Enter secret key" required>
        </div>
        <div id="secretMsg" class="validation-msg"></div>

        <button type="submit">Sign Up</button>
    </form>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        🎉 Congratulations! You are successfully registered.<br>
        Redirecting to login page in <span id="countdown">5</span> seconds...
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Live availability check ---
    function checkAvailability(field, value) {
        if (!value) return;
       fetch('validate.php?field=' + field + '&value=' + encodeURIComponent(value))
            .then(res => res.json())
            .then(data => {
                const msgElem = document.getElementById(field + 'Msg');
                if (data.exists) {
                    msgElem.textContent = field.charAt(0).toUpperCase() + field.slice(1) + " already exists.";
                    msgElem.classList.remove("valid");
                } else {
                    msgElem.textContent = field.charAt(0).toUpperCase() + field.slice(1) + " is available.";
                    msgElem.classList.add("valid");
                }
            })
            .catch(err => console.error('Error:', err));
    }

    // Attach blur events
    document.getElementById("username").addEventListener("blur", e => checkAvailability("username", e.target.value));
    document.getElementById("email").addEventListener("blur", e => checkAvailability("email", e.target.value));
    document.getElementById("phone").addEventListener("blur", e => checkAvailability("phone", e.target.value));

    // --- Password rules validation ---
    const passwordInput = document.getElementById("password");
    const passwordRules = {
        length: { regex: /.{8,}/, element: document.getElementById("rule-length") },
        upper:  { regex: /[A-Z]/, element: document.getElementById("rule-upper") },
        lower:  { regex: /[a-z]/, element: document.getElementById("rule-lower") },
        digit:  { regex: /\d/, element: document.getElementById("rule-digit") },
        special:{ regex: /[\W_]/, element: document.getElementById("rule-special") }
    };

    passwordInput.addEventListener("input", function() {
        const val = this.value;
        for (const key in passwordRules) {
            const rule = passwordRules[key];
            if (rule.regex.test(val)) {
                rule.element.classList.add("valid");
            } else {
                rule.element.classList.remove("valid");
            }
        }
    });

    // --- Secret key live validation ---
    const secretInput = document.getElementById("secret_key");
    const secretMsg = document.getElementById("secretMsg");

    secretInput.addEventListener("input", function() {
        if (this.value === "051167") {
            secretMsg.textContent = "Secret key is valid.";
            secretMsg.classList.add("valid");
        } else {
            secretMsg.textContent = "Secret key is invalid.";
            secretMsg.classList.remove("valid");
        }
    });
});
</script>
</body>
</html>
