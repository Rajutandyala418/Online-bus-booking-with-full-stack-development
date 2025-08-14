<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'C:/xampp/htdocs/y22cm171/include/php_mailer/Exception.php';
require 'C:/xampp/htdocs/y22cm171/include/php_mailer/PHPMailer.php';
require 'C:/xampp/htdocs/y22cm171/include/php_mailer/SMTP.php';

function sendTicketEmail($name, $email, $schedule, $seat_number, $pdf_path = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rajutandyala7890@gmail.com';
        $mail->Password = 'Raju@123';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('rajutandyala7890@gmail.com', 'Bus Booking System');
        $mail->addAddress($email, $name);

        if ($pdf_path && file_exists($pdf_path)) {
            $mail->addAttachment($pdf_path);
        }

        $mail->isHTML(true);
        $mail->Subject = 'Your Bus Ticket Confirmation';
        $mail->Body = "
            <h2>Hello $name,</h2>
            <p>Your bus ticket has been confirmed.</p>
            <p><strong>Bus:</strong> {$schedule['bus_name']} ({$schedule['bus_number']})</p>
            <p><strong>Route:</strong> {$schedule['source']} → {$schedule['destination']}</p>
            <p><strong>Travel Date:</strong> {$schedule['travel_date']} at {$schedule['departure_time']}</p>
            <p><strong>Seat Number:</strong> $seat_number</p>
            <p>Thank you for booking with us.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
