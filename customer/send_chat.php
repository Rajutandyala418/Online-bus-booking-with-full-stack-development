<?php
include('../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$username = trim($_POST['username']);
$message  = trim($_POST['message']);

if (!$username || !$message) {
    echo json_encode(["status"=>"error","msg"=>"Invalid message"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO support_chat (username, sender, message, msg_status, is_read) 
VALUES (?, ?, ?, 'sent', 0)");
$sender = "user";
$stmt->bind_param("sss", $username, $sender, $message);
$stmt->execute();


// 2️⃣ Fetch last email notification time
$check = $conn->prepare("SELECT last_alert_time FROM support_users_alert WHERE username=? LIMIT 1");
$check->bind_param("s", $username);
$check->execute();
$res = $check->get_result();

$now = time();
$send_mail = false;

if ($res->num_rows == 0) {
    // first message and row not exist → create record
    $conn->query("INSERT INTO support_users_alert (username, last_alert_time) VALUES ('$username', 0)");
    $is_new = true;
} else {
    $row = $res->fetch_assoc();
    $last_alert_time = strtotime($row['last_alert_time']);
    $is_new = false;
}

// 3️⃣ Check if admin has unread messages from this user
$unread = $conn->query("SELECT id FROM support_chat WHERE username='$username' AND sender='user' AND is_read=0")->num_rows;

// IF unread exists
if ($unread > 0) {

    // first unread → send email now
    if ($is_new) {
        $send_mail = true;
    } 
    // if 5 minutes passed AND still unread
    else if (($now - $last_alert_time) >= 300) { // 300s = 5 mins
        $send_mail = true;
    }

    if ($send_mail) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'varahibusbooking@gmail.com';
            $mail->Password   = 'pjhg nwnt haac nsiu';  // Gmail App password
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Support');
            $mail->addAddress('rajutandyala369@gmail.com', 'System Admin');

            $mail->isHTML(true);
            $mail->Subject = "🚨 Support Alert: $username Needs Help";
            $mail->Body    = "
                <b>Hello Admin,</b><br><br>
                A customer has pending unread messages.<br><br>
                <b>User:</b> $username<br>
                <b>Latest Message:</b> $message<br><br>
                Please respond in admin panel.<br><br>
                <hr>
                <small>This alert is sent only if unread for more than 5 minutes.</small>
            ";

            $mail->send();
            $conn->query("UPDATE support_users_alert SET last_alert_time=NOW() WHERE username='$username'");

        } catch (Exception $e) {
            // optionally log errors
        }
    }
}

echo json_encode(["status"=>"success","msg"=>"sent"]);
?>
