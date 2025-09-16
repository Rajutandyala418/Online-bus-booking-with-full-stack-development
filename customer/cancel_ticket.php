<?php
include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../fpdf/fpdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$username   = $_POST['username'] ?? '';
$booking_id = intval($_POST['booking_id'] ?? 0);

if (!$username || !$booking_id) {
    die("Invalid request.");
}

// ✅ Step 1: Update Booking & Payment status
$update = $conn->prepare("
    UPDATE bookings b
    LEFT JOIN payments p ON b.id = p.booking_id
    SET b.status = 'cancelled', p.payment_status = 'failed'
    WHERE b.id = ?
");
$update->bind_param("i", $booking_id);
$update->execute();
$update->close();

// ✅ Step 2: Fetch user details
$userQuery = "SELECT id, email, first_name, last_name FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) die("User not found.");

// ✅ Step 3: Fetch traveller details
$tStmt = $conn->prepare("SELECT name, email, phone, gender FROM travellers WHERE booking_id = ?");
$tStmt->bind_param("i", $booking_id);
$tStmt->execute();
$travellerResult = $tStmt->get_result();
$traveller = $travellerResult->fetch_assoc();
$tStmt->close();

// ✅ Step 4: Fetch updated booking details
$bookingQuery = "
    SELECT b.id AS booking_id, b.booking_date, b.status,
           s.travel_date, s.departure_time AS departure, s.arrival_time AS arrival,
           bu.bus_name, bu.bus_number, r.source, r.destination,
           IFNULL(p.amount,0) AS amount, IFNULL(p.payment_method,'N/A') AS payment_method,
           IFNULL(p.payment_status,'N/A') AS payment_status, IFNULL(p.transaction_id,'N/A') AS transaction_id,
           GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number) AS seats
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    LEFT JOIN payments p ON b.id = p.booking_id
    LEFT JOIN booking_seats bs ON b.id = bs.booking_id
    WHERE b.id = ?
    GROUP BY b.id
    LIMIT 1
";


$stmt = $conn->prepare($bookingQuery);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) die("Booking not found.");

// ✅ Step 5: Create Ticket HTML (with updated details)
$route = $booking['source'] . " → " . $booking['destination'];
$ticketHTML = "
<h2 style='text-align:center;'>❌ Varahi Bus - Cancelled Ticket</h2>
<p><b>🆔 Booking ID:</b> {$booking['booking_id']}<br>
<b>📅 Booking Date:</b> {$booking['booking_date']}<br>
<b>🚫 Status:</b> {$booking['status']}</p>
<hr>
<h3>🚌 Bus Details</h3>
<ul>
<li><b>Bus:</b> {$booking['bus_name']} ({$booking['bus_number']})</li>
<li><b>Route:</b> {$route}</li>
<li><b>Travel Date:</b> {$booking['travel_date']}</li>
<li><b>Departure:</b> {$booking['departure']}</li>
<li><b>Arrival:</b> {$booking['arrival']}</li>
<li><b>Seats:</b> {$booking['seats']}</li>

</ul>
<hr>
<h3>💰 Payment Details</h3>
<ul>
<li><b>Amount:</b> ₹{$booking['amount']}</li>
<li><b>Method:</b> {$booking['payment_method']}</li>
<li><b>Status:</b> {$booking['payment_status']}</li>
<li><b>Txn ID:</b> {$booking['transaction_id']}</li>
</ul>
<hr>
<h3>👤 Traveller Details</h3>
<ul>
<li><b>Name:</b> {$traveller['name']}</li>
<li><b>Gender:</b> {$traveller['gender']}</li>
<li><b>Email:</b> {$traveller['email']}</li>
<li><b>Phone:</b> {$traveller['phone']}</li>
</ul>
<hr>
<p style='text-align:center; color:red;'>⚠️ Your ticket has been cancelled.<br>
Payment status updated to <b>{$booking['payment_status']}</b>.</p>
";

// ✅ Step 6: Generate PDF (with updated details)
$pdf = new FPDF();
$pdf->AddPage();
$pdf->Rect(5,5,200,287);

// Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(190,12,'Varahi Bus - Cancelled Ticket',0,1,'C');
$pdf->Ln(5);

// Booking Info Row
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(255,182,193); // light red for cancel
$pdf->Cell(63,10,"Booking ID: {$booking['booking_id']}",1,0,'C',true);
$pdf->Cell(63,10,"Date: {$booking['booking_date']}",1,0,'C',true);
$pdf->Cell(64,10,"Status: {$booking['status']}",1,1,'C',true);
$pdf->Ln(5);

function drawTable($pdf, $title, $dataArr){
    $pdf->SetFont('Arial','B',14);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(190,10,$title,1,1,'C',true);
    $pdf->SetFont('Arial','',12);
    foreach($dataArr as $k=>$v){
        $pdf->SetFillColor(220,220,255);
        $pdf->Cell(60,8,$k,1,0,'L',true);
        $pdf->SetFillColor(255,255,255);
        $pdf->Cell(130,8,$v,1,1,'L',true);
    }
    $pdf->Ln(5);
}

drawTable($pdf,"Bus Details",[
    "Bus Name" => $booking['bus_name'],
    "Bus Number" => $booking['bus_number'],
    "Route" => $route,
    "Travel Date" => $booking['travel_date'],
    "Departure" => $booking['departure'],
    "Arrival" => $booking['arrival'],
 "Seats" => $booking['seats']

]);

drawTable($pdf,"Payment Details",[
    "Amount" => "Rs. {$booking['amount']}",
    "Method" => $booking['payment_method'],
    "Status" => $booking['payment_status'],
    "Transaction ID" => $booking['transaction_id']
]);

drawTable($pdf,"Traveller Details",[
    "Name"  => $traveller['name'],
    "Gender"=> $traveller['gender'],
    "Email" => $traveller['email'],
    "Phone" => $traveller['phone']
]);

$pdfOutput = $pdf->Output('S');

// ✅ Step 7: Send Email
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'varahibusbooking@gmail.com';
    $mail->Password = 'pjhg nwnt haac nsiu'; // ⚠️ App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus');
    $mail->addAddress($traveller['email'], $traveller['name']);

    $mail->isHTML(true);
    $mail->Subject = " Cancelled Ticket - Booking #{$booking['booking_id']}";
    $mail->Body    = $ticketHTML;
    $mail->AltBody = strip_tags($ticketHTML);

    $mail->addStringAttachment($pdfOutput, "CancelledTicket_{$booking['booking_id']}.pdf");

    $mail->send();
    $status = "Email with cancelled ticket sent to {$traveller['email']}";
    $mailStatus = "success";
} catch (Exception $e) {
    $status = "Mailer Error: {$mail->ErrorInfo}";
    $mailStatus = "error";
}

// ✅ Step 8: Redirect back to booking history with message
echo "<script>
    alert('{$status}');
    window.location.href='booking_history.php?username=" . urlencode($username) . "&mail_status={$mailStatus}';
</script>";
exit;
?>
