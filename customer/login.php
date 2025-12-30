<?php
include(__DIR__ . '/../include/db_connect.php');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ? OR username = ? LIMIT 1");
    if (!$stmt) die("Prepare failed: " . $conn->error);

    $stmt->bind_param("ss", $login_input, $login_input);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $username, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            header("Location: dashboard.php?username=" . urlencode($username));
            exit();
        } else {
            $message = "Invalid login credentials.";
        }
    } else {
        $message = "Invalid login credentials.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#e8f0f7;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:15px;}
.container{background:#ffffff;border:1px solid #d7e0ea;padding:25px;border-radius:14px;width:100%;max-width:380px;box-shadow:0 4px 15px rgba(0,0,0,0.06);position:relative;}
.container h2{text-align:center;margin-top:40px;margin-bottom:20px;font-weight:700;color:#1e3c57;}
.input-group{margin-bottom:15px;}
.input-group label{font-weight:500;font-size:14px;color:#1e3c57;}
.input-group input{width:100%;padding:11px;border-radius:8px;border:1px solid #b9c7d8;margin-top:6px;outline:none;font-size:15px;}
.input-group input:focus{border-color:#0072ff;}
.input-group input::placeholder{color:#9bb1c7;font-size:14px;}
.login-btn{background:#1e3c57;color:#fff;width:100%;padding:12px;border:none;border-radius:8px;cursor:pointer;margin-top:8px;font-weight:600;font-size:16px;}
.login-btn:hover{background:#264a6e;}
.back-btn{position:absolute;top:18px;right:20px;background:#1e3c57;color:#fff;border:none;font-size:16px;padding:10px 18px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;text-decoration:none;}
.back-btn i{font-size:17px;}
.back-btn:hover{background:#264a6e;}
.message{color:red;font-weight:bold;margin-bottom:10px;text-align:center;}
.options{display:flex;justify-content:space-between;margin-top:15px;}
.opt-btn{background:#ffffff;color:#1e3c57;border:1px solid #0072ff;padding:10px 12px;width:48%;border-radius:8px;font-size:13px;cursor:pointer;transition:0.3s;text-align:center;text-decoration:none;font-weight:600;}
.opt-btn:hover{background:#e8eef5;}
@media(max-width:480px){
    .container{padding:20px;border-radius:12px;}
    .login-btn{font-size:15px;padding:11px;}
    .opt-btn{font-size:12px;padding:9px;}
    .back-btn{padding:8px 15px;font-size:14px;}
}
</style>
</head>
<body>
<div class="container">
    <a href="../index.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back</a>
    <h2>Customer Login</h2>
    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="input-group">
            <label for="login_input">Email or Username</label>
            <input id="login_input" type="text" name="login_input" placeholder="Enter email or username" required>
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="login-btn">Login</button>
        <div class="options">
            <a href="register.php" class="opt-btn">Register</a>
            <a href="forgot.php" class="opt-btn">Forgot Credentials</a>
        </div>
    </form>
</div>
</body>
</html>
