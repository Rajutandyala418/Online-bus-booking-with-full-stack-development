<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

// ✅ Load PHPMailer at the top
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

    // Password validation
    $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (!preg_match($password_pattern, $password)) {
        $message = "Password must be at least 8 characters long, include upper and lower case letters, a number, and a special character.";
    } else {
        // Check if username, email or phone already exists
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
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $username, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $registration_success = true;
                $message = "Registration successful! You can now log in.";

                // ✅ Send welcome email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'varahibusbooking@gmail.com'; // your Gmail
                    $mail->Password   = 'pjhg nwnt haac nsiu';        // Gmail App Password
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
                    $mail->AltBody = "Dear {$first_name} {$last_name},\nWelcome to VarahiBus! Your account has been created.\nUsername: {$username}";

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

// ✅ AJAX username/email/phone availability check
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
    <title>Customer Registration</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            overflow-y: auto;
            padding-top: 40px;
            padding-bottom: 40px;
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
.register-box {
    background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
        );
    border-radius: 12px;
    padding: 30px;
    text-align: center;
    color: white;
    width: 450px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.7);
    margin-bottom: 40px;
}

        h2 { margin-bottom: 20px; color: black; }
        label {
            display: block;
            text-align: left;
            font-size: 0.9rem;
            margin: 5px 0 3px 0;
            color: blue;
        }
        input, button {
            width: 100%;
            padding: 12px;
            margin: 5px 0 15px 0;
            border-radius: 5px;
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
        .message { color: green; font-size: 0.9rem; }
        .success { color: #00ff88; font-size: 0.9rem; }
        .availability { font-size: 0.8rem; margin-top: -10px; margin-bottom: 10px; }
        .available { color: green; }
        .exists { color: red; }
        .back-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background: #111;
            padding: 10px 20px;
            color: #ffde59;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
                       transition: 0.3s ease;
        }
        .back-btn:hover {
            box-shadow: 0 0 20px #ff512f, 0 0 40px #dd2476, 0 0 60px #ff512f;
        }
        .password-rules {
            text-align: left;
            font-size: 0.8rem;
            color: blue;
            margin-top: -5px;
            margin-bottom: 10px;
        }
        .password-rules li { list-style: none; }
        .valid { color: blue; }
        .invalid { color: black; }
        .popup {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: #222;
            color: #fff;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 20px #000;
            z-index: 1000;
        }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<a href="login.php" class="back-btn">Back to Login Page</a>

<div class="register-box">
    <h2>Customer Registration</h2>
    <?php if ($message): ?>
        <p class="<?php echo (strpos($message, 'successful') !== false) ? 'success' : 'message'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>
    <form method="post" onsubmit="return validateForm()">
        <label for="first_name">First Name</label>
        <input type="text" id="first_name" name="first_name" placeholder="Enter First Name" required>

        <label for="last_name">Last Name</label>
        <input type="text" id="last_name" name="last_name" placeholder="Enter Last Name" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter Username" required>
        <div id="username_status" class="availability"></div>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter Email" required>
        <div id="email_status" class="availability"></div>

        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" placeholder="Enter Phone Number" required pattern="[0-9]{10}" title="Enter 10-digit phone number">
        <div id="phone_status" class="availability"></div>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter Password" required>
        <ul class="password-rules" id="passwordRules">
            <li id="length" class="invalid">• At least 8 characters</li>
            <li id="lowercase" class="invalid">• At least one lowercase letter</li>
            <li id="uppercase" class="invalid">• At least one uppercase letter</li>
            <li id="number" class="invalid">• At least one number</li>
            <li id="special" class="invalid">• At least one special character</li>
        </ul>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>

        <button type="submit">Sign Up</button>
    </form>
</div>

<?php if ($registration_success): ?>
<div class="popup" id="successPopup">
    <h3>🎉 Registration Successful!</h3>
    <p>Redirecting to login page in <span id="countdown">5</span> seconds...</p>
</div>
<script>
    let counter = 5;
    const countdownEl = document.getElementById('countdown');
    const interval = setInterval(() => {
        counter--;
        countdownEl.textContent = counter;
        if (counter <= 0) {
            clearInterval(interval);
            window.location.href = "login.php";
        }
    }, 1000);
</script>
<?php endif; ?>

<script>
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const rules = {
        length: document.getElementById('length'),
        lowercase: document.getElementById('lowercase'),
        uppercase: document.getElementById('uppercase'),
        number: document.getElementById('number'),
        special: document.getElementById('special')
    };

    passwordInput.addEventListener('input', function () {
        const value = passwordInput.value;
        rules.length.className = value.length >= 8 ? 'valid' : 'invalid';
        rules.lowercase.className = /[a-z]/.test(value) ? 'valid' : 'invalid';
        rules.uppercase.className = /[A-Z]/.test(value) ? 'valid' : 'invalid';
        rules.number.className = /\d/.test(value) ? 'valid' : 'invalid';
        rules.special.className = /[\W_]/.test(value) ? 'valid' : 'invalid';
    });

    function validateForm() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            alert("Passwords do not match!");
            return false;
        }
        const allValid = Object.values(rules).every(rule => rule.classList.contains('valid'));
        if (!allValid) {
            alert("Password does not meet all the requirements!");
            return false;
        }
        return true;
    }

    function checkAvailability(field, value) {
        if (!value) {
            document.getElementById(field + "_status").innerHTML = "";
            return;
        }
        fetch("?check_field=" + field + "&value=" + encodeURIComponent(value))
            .then(res => res.json())
            .then(data => {
                const statusEl = document.getElementById(field + "_status");
                if (data.status === "exists") {
                    statusEl.innerHTML = field.charAt(0).toUpperCase() + field.slice(1) + " already exists.";
                    statusEl.className = "availability exists";
                } else if (data.status === "available") {
                    statusEl.innerHTML = field.charAt(0).toUpperCase() + field.slice(1) + " is available.";
                    statusEl.className = "availability available";
                } else {
                    statusEl.innerHTML = "";
                }
            });
    }

    document.getElementById('username').addEventListener('blur', e => checkAvailability('username', e.target.value));
    document.getElementById('email').addEventListener('blur', e => checkAvailability('email', e.target.value));
    document.getElementById('phone').addEventListener('blur', e => checkAvailability('phone', e.target.value));
</script>
</body>
</html>
