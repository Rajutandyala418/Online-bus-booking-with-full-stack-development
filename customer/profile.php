<?php
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get username from GET or POST
$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) die("Username not provided.");

// Fetch user details
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();

if (!$user_id) die("User not found.");

$message = "";
$error_msg = "";

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $first_name_new = trim($_POST['first_name']);
    $last_name_new = trim($_POST['last_name']);
    $email_new = trim($_POST['email']);
    $phone_new = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $first_name_new, $last_name_new, $email_new, $phone_new, $user_id);

    if ($stmt->execute()) {
        $message = "Profile updated successfully!";

        // Send email to updated email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'varahibusbooking@gmail.com';
            $mail->Password   = 'pjhg nwnt haac nsiu'; // your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus Team');
            $mail->addAddress($email_new, $first_name_new . ' ' . $last_name_new);

            $mail->isHTML(true);
            $mail->Subject = "Profile Updated - VarahiBus Account";

            $mail->Body = "
                <p>Dear {$first_name_new} {$last_name_new},</p>
                <p>Your profile details have been <b>successfully updated</b> on your VarahiBus account:</p>
                <ul>
                    <li><b>Username:</b> {$username}</li>
                    <li><b>First Name:</b> {$first_name_new}</li>
                    <li><b>Last Name:</b> {$last_name_new}</li>
                    <li><b>Email:</b> {$email_new}</li>
                    <li><b>Phone:</b> {$phone_new}</li>
                </ul>
                <p style='color:red; font-weight:bold;'>⚠ If you did not perform this action, please change your password immediately.</p>
                <p>Thank you,<br><b>VarahiBus Team</b></p>
            ";
            $mail->send();
        } catch (Exception $e) {
            // Optional: log error
        }

        // Update local variables for display
        $first_name = $first_name_new;
        $last_name = $last_name_new;
        $email = $email_new;
        $phone = $phone_new;

    } else {
        $error_msg = "Update failed! Please try again.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
    <style>
        body { font-family:'Poppins',sans-serif; background:#111; color:white; text-align:center; margin:0; }
        .container { max-width:700px; margin:100px auto; background:rgba(0,0,0,0.7); padding:30px; border-radius:10px; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.15); }
        th { width:30%; color:#00bfff; }
        input[type=text], input[type=email] { width:100%; padding:10px; border-radius:6px; border:none; font-size:1rem; }
        input[readonly] { background: rgba(255,255,255,0.2); cursor:default; color:#ccc; }
        button { margin-top:20px; width:100%; padding:14px; font-weight:700; font-size:1.1rem; border-radius:8px; border:none; background:linear-gradient(90deg,#00bfff,#1e90ff); color:white; cursor:pointer; }
        button:hover { background:linear-gradient(90deg,#1e90ff,#00bfff); }
        .message { text-align:center; font-weight:600; color:#0ff; margin-bottom:10px; }
 .back-btn {
    margin-top: 10px;
    width: 100%;
    padding: 14px;
    font-weight: 700;
    font-size: 1.1rem;
    border-radius: 8px;
    border: none;
    background: linear-gradient(90deg, #28a745, #218838);
    color: white;
    cursor: pointer;
}
.back-btn:hover {
    background: linear-gradient(90deg, #218838, #28a745);
}

    </style>
</head>
<body>


<div class="container">
    <h2>Your Profile Details</h2>
    <?php if($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php elseif($error_msg): ?>
        <div class="message" style="color:red;"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
        <table>
            <tr><th>Username</th><td><input type="text" value="<?= htmlspecialchars($username) ?>" readonly></td></tr>
            <tr><th>First Name</th><td><input type="text" name="first_name" value="<?= htmlspecialchars($first_name) ?>" required></td></tr>
            <tr><th>Last Name</th><td><input type="text" name="last_name" value="<?= htmlspecialchars($last_name) ?>" required></td></tr>
            <tr><th>Email</th><td><input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required></td></tr>
            <tr><th>Phone</th><td><input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required pattern="[0-9]{10}" title="Enter 10-digit phone number"></td></tr>
        </table>
        <button type="submit">Update Profile</button>
<button type="button" class="back-btn"
        onclick="window.location.href='dashboard.php?username=<?= urlencode($username) ?>'">
    ⬅ Back to Dashboard
</button>

    </form>
</div>

</body>
</html>
