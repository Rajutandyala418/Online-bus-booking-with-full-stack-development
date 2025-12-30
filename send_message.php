<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/include/php_mailer/Exception.php';
require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $userMessage = trim($_POST["message"] ?? "");
    if(empty($userMessage)) die("No message received");

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';  
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bot');
        $mail->addAddress('rajutandyala369@gmail.com', 'Raju');

        $mail->isHTML(true);
        $mail->Subject = 'New Chatbot Message from Varahi Website';
        $mail->Body    = "<h3>User Message:</h3><p>".htmlspecialchars($userMessage)."</p>";

        $mail->send();
        echo "Message sent successfully!";
    } catch (Exception $e) {
        echo "Error: {$mail->ErrorInfo}";
    }
}
?>
