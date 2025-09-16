<?php
include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/dompdf/autoload.inc.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;

$username = $_POST['username'] ?? '';
$booking_id = intval($_POST['booking_id'] ?? 0);

if (!$username || !$booking_id) {
    die("Invalid request.");
}

// Fetch user details
$userQuery = "SELECT id, email, first_name, last_name FROM users WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $username);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();
$stmt->close();

if (!$user) die("User not found.");

$user_email = $user['email'];
$user_name = $user['first_name'] . ' ' . $user['last_name'];
// Fetch traveller details
$tStmt = $conn->prepare("SELECT name, email, phone, gender FROM travellers WHERE booking_id = ?");
$tStmt->bind_param("i", $booking_id);
$tStmt->execute();
$travellerResult = $tStmt->get_result();
$traveller = $travellerResult->fetch_assoc();
$tStmt->close();

// Fetch booking details
$bookingQuery = "
    SELECT b.id AS booking_id, b.booking_date,
           s.travel_date, s.departure_time AS departure, s.arrival_time AS arrival,
           bu.bus_name, bu.bus_number, r.source, r.destination,
           p.amount, p.payment_method, p.payment_status, p.transaction_id,
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
if (!$stmt) die("SQL Prepare Error: " . $conn->error);

$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();


if (!$booking) die("Booking not found.");

// Generate ticket HTML
$route = $booking['source'] . " → " . $booking['destination'];
$ticketHTML = "<pre style='font-family:Arial,sans-serif; font-size:14px;'>
🎫 Bus Ticket
────────────────────────
🆔 Booking ID: {$booking['booking_id']}
📅 Booking Date: {$booking['booking_date']}
✅ Status: {$booking['payment_status']}
────────────────────────
🚌 Bus Details
Bus: {$booking['bus_name']} ({$booking['bus_number']})
Route: {$route}
Travel Date: {$booking['travel_date']}
Departure: {$booking['departure']}
Arrival: {$booking['arrival']}
Seats: {$booking['seat_number']}
────────────────────────
💰 Payment Details
Amount: ₹{$booking['amount']}
Method: {$booking['payment_method']}
Status: {$booking['payment_status']}
Txn ID: {$booking['transaction_id']}
────────────────────────
🙏 Thank you for booking with VarahiBus!
</pre>";

require __DIR__ . '/../fpdf/fpdf.php';

// ---------------- PDF ----------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->Rect(5,5,200,287);

// Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(190,12,'Varahi Bus Ticket',0,1,'C');
$pdf->Ln(5);

// Booking Info Row
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(173,216,230);
$pdf->Cell(63,10,"Booking ID: {$booking['booking_id']}",1,0,'C',true);
$pdf->SetFillColor(144,238,144);
$pdf->Cell(63,10,"Date: {$booking['booking_date']}",1,0,'C',true);
$pdf->SetFillColor(255,255,153);
$pdf->Cell(64,10,"Status: {$booking['payment_status']}",1,1,'C',true);
$pdf->Ln(5);

// Function to draw table
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

// Bus Details
$busDetails = [
    "Bus Name" => $booking['bus_name'],
    "Bus Number" => $booking['bus_number'],
    "Route" => $booking['source'] . " → " . $booking['destination'],
    "Travel Date" => $booking['travel_date'],
    "Departure" => $booking['departure'],
    "Arrival" => $booking['arrival'],
    "Seats" => $booking['seat_number']
];
drawTable($pdf,"Bus Details",$busDetails);

// Payment Details
$paymentDetails = [
    "Amount" => "Rs. {$booking['amount']}",
    "Method" => $booking['payment_method'],
    "Status" => $booking['payment_status'],
    "Transaction ID" => $booking['transaction_id']
];
drawTable($pdf,"Payment Details",$paymentDetails);
$travellerDetails = [
    "Name"  => $traveller['name'],
    "Gender"=> $traveller['gender'],
    "Email" => $traveller['email'],
    "Phone" => $traveller['phone']
];
drawTable($pdf,"Traveller Details",$travellerDetails);

// ---------------- Capture PDF as string ----------------
$pdfOutput = $pdf->Output('S'); // S = return as string

// ---------------- Send email ----------------
// ---------------- Send email ----------------
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'varahibusbooking@gmail.com';
    $mail->Password = 'pjhg nwnt haac nsiu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('varahibusbooking@gmail.com', 'VarahiBus');

    // ✅ Send to traveller email instead of user email
    $traveller_email = $traveller['email'];
    $traveller_name  = $traveller['name'];
    $mail->addAddress($traveller_email, $traveller_name);

    $mail->isHTML(true);
    $mail->Subject = "Your Bus Ticket - Booking #{$booking['booking_id']}";
    $mail->Body = "Dear {$traveller_name},<br><br>Please find your bus ticket attached.<br>Thank you for booking with VarahiBus!";
    $mail->AltBody = "Dear {$traveller_name}, Please find your bus ticket attached. Thank you for booking with VarahiBus!";

    $mail->addStringAttachment($pdfOutput, "BusTicket_{$booking['booking_id']}.pdf");

    $mail->send();
    $status = "email send to the".$traveller_email;
} catch (Exception $e) {
    $status = "error";
}


echo "<script>
    alert('Email status: {$status}');
    window.location.href='booking_history.php?username=" . urlencode($username) . "';
</script>";
exit();
