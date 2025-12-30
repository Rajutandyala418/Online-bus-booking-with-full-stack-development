<?php
if(!isset($_POST['message'])) exit;

$message = $_POST['message'];

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/include/php_mailer/PHPMailer.php';
require __DIR__ . '/include/php_mailer/SMTP.php';
require __DIR__ . '/include/php_mailer/Exception.php';

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
    $mail->addAddress('rajutandyala369@gmail.com'); 

    $mail->isHTML(true);
    $mail->Subject = "New User Query - Varahi Bus";
    $mail->Body    = "<b>Feedback / Query:</b> <br><br>" . nl2br($message);

    $mail->send();
} catch (Exception $e) {

}
?>
