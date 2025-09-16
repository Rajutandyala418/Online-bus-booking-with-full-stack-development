<?php
include(__DIR__ . '/../include/db_connect.php');

$message = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'username') {
        $phone = trim($_POST['phone']);
        $stmt = $conn->prepare("SELECT username, email FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($username, $email);
            $stmt->fetch();
            $message = "Account Found: <br><strong>Username:</strong> " . htmlspecialchars($username) . 
                       "<br><strong>Email:</strong> " . htmlspecialchars($email);
        } else {
            $message = "No account found for this phone number.";
        }
        $stmt->close();
    } elseif ($action === 'password') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND email = ? AND phone = ?");
        $stmt->bind_param("sss", $username, $email, $phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            header("Location: update_password.php?username=" . urlencode($username));
            exit();
        } else {
            $message = "No account matches the provided details.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Username/Password</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #111;
            color: white;
            margin: 0;
        }
       .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
.forgot-box {
    background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
        );
    border-radius: 10px;
    width: 400px;
    margin: 100px auto;
    padding: 30px;
    text-align: center;
    box-shadow: 0 0 10px #0ff, 0 0 20px #0ff, 0 0 30px #0ff;
    color: white;
}

        h2 {
            margin-bottom: 20px;
            color: black;
            text-shadow: 0 0 5px #ffde59, 0 0 10px #ffde59;
        }
        select, input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
        }
        select, input {
            background: #fff;
            color: #333;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
            box-shadow: 0 0 10px #ff512f, 0 0 20px #dd2476;
            transition: 0.3s;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
            box-shadow: 0 0 20px #dd2476, 0 0 40px #ff512f;
        }
        .message {
            font-size: 1.5rem;
            margin-top: 10px;
            color: blue;
            
        }
        a {
            display: inline-block;
            margin-top: 10px;
		font-size : 20px;
            text-decoration: none;
            color: blue;
                       transition: 0.3s;
        }
        a:hover {
            color: blue;
            text-shadow: 0 0 10px #ff0, 0 0 20px #0ff, 0 0 30px #f0f;
        }
        .form-section {
            display: none;
        }
        .active {
            display: block;
        }
.back-btn{
color : red;
}
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>
<div class="forgot-box">
    <h2>Forgot Username / Password</h2>
    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <select id="forgotType">
        <option value="username" selected>Forgot Username</option>
        <option value="password">Forgot Password</option>
    </select>

    <!-- Forgot Username Form -->
    <form method="post" id="formUsername" class="form-section active">
        <input type="hidden" name="action" value="username">
        <input type="text" name="phone" placeholder="Enter Phone Number" required>
        <button type="submit">Find Username</button>
    </form>

    <!-- Forgot Password Form -->
    <form method="post" id="formPassword" class="form-section">
        <input type="hidden" name="action" value="password">
        <input type="text" name="username" placeholder="Enter Username" required>
        <input type="email" name="email" placeholder="Enter Email" required>
        <input type="text" name="phone" placeholder="Enter Phone Number" required>
        <button type="submit">Reset Password</button>
    </form>

    <a href="login.php" class="back-btn">Back to Login</a>
</div>

<script>
    const selectBox = document.getElementById('forgotType');
    const formUsername = document.getElementById('formUsername');
    const formPassword = document.getElementById('formPassword');

    selectBox.addEventListener('change', function() {
        if (this.value === 'username') {
            formUsername.classList.add('active');
            formPassword.classList.remove('active');
        } else {
            formPassword.classList.add('active');
            formUsername.classList.remove('active');
        }
    });
</script>
</body>
</html>
