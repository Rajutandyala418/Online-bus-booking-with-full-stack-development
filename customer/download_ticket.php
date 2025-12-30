<?php
include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../fpdf/fpdf.php';

// ------------------ Get booking_id ------------------
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if (!$booking_id) die("❌ Booking ID missing.");
$coupon = $_GET['coupon'] ?? 'None';

// ---------------- Fetch booking ----------------
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, b.booking_date, b.status, b.user_id,
           r.source, r.destination,
           s.travel_date, s.departure_time, s.arrival_time,
           bu.bus_name, bu.bus_number,
           p.payment_method, p.payment_status, GROUP_CONCAT(p.transaction_id) AS txn_ids, SUM(p.amount) AS total_amount,
           u.username, u.first_name
    FROM bookings b
    LEFT JOIN schedules s ON b.schedule_id = s.id
    LEFT JOIN routes r ON s.route_id = r.id
    LEFT JOIN buses bu ON s.bus_id = bu.id
    LEFT JOIN payments p ON b.id = p.booking_id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
    GROUP BY b.id
    LIMIT 1
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) die("❌ Booking not found.");
$data = $result->fetch_assoc();
$stmt->close();

// ---------------- Fetch seat numbers ----------------
$sStmt = $conn->prepare("SELECT seat_number FROM booking_seats WHERE booking_id = ?");
$sStmt->bind_param("i", $booking_id);
$sStmt->execute();
$seatResult = $sStmt->get_result();
$seatNumbers = [];
while ($row = $seatResult->fetch_assoc()) {
    $seatNumbers[] = $row['seat_number'];
}
$sStmt->close();
$seat_number = implode(', ', $seatNumbers);

// ---------------- Fetch all travellers ----------------
$tStmt = $conn->prepare("SELECT name, email, phone, gender FROM travellers WHERE booking_id = ?");
$tStmt->bind_param("i", $booking_id);
$tStmt->execute();
$travellerResult = $tStmt->get_result();
$travellers = [];
while ($row = $travellerResult->fetch_assoc()) {
    $travellers[] = $row;
}
$tStmt->close();

// Extract details
$bus_name       = $data['bus_name'];
$bus_number     = $data['bus_number'];
$route          = $data['source'] . " -- " . $data['destination'];
$travel_date    = $data['travel_date'];
$departure      = $data['departure_time'];
$arrival        = $data['arrival_time'];
$booking_status = $data['status'];
$fare           = (float)$data['total_amount'];
$payment_method = $data['payment_method'];
$payment_status = $data['payment_status'];
$transaction_id = $data['txn_ids'];
$username       = $data['username'];
$first_name     = $data['first_name'];
$booking_date   = $data['booking_date'];

// ---------------- PDF ----------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->Rect(5, 5, 200, 287); 

// Title
$pdf->SetFont('Arial','B',16);
$pdf->Cell(190,12,'Varahi Bus Ticket',0,1,'C');
$pdf->Ln(5);

// Booking Info
$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(173,216,230); 
$pdf->Cell(63,10,"Booking ID: $booking_id",1,0,'C',true);
$pdf->SetFillColor(144,238,144); 
$pdf->Cell(63,10,"Date: $booking_date",1,0,'C',true);
$pdf->SetFillColor(255,255,153); 
$pdf->Cell(64,10,"Status: $booking_status",1,1,'C',true);
$pdf->Ln(5);

// Function to Draw Table
function drawTable($pdf, $title, $dataArr) {
    $pdf->SetFont('Arial','B',14);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(190,10,$title,1,1,'C',true);

    $pdf->SetFont('Arial','',12);
    foreach ($dataArr as $key => $value) {
        $pdf->SetFillColor(220,220,255);
        $pdf->Cell(60,8,$key,1,0,'L',true);
        $pdf->SetFillColor(255,255,255);
        $pdf->Cell(130,8,$value,1,1,'L',true);
    }
    $pdf->Ln(5);
}

// Bus Details
$busDetails = [
    "Bus Name"      => $bus_name,
    "Bus Number"    => $bus_number,
    "Route"         => $route,
    "Travel Date"   => $travel_date,
    "Departure"     => $departure,
    "Arrival"       => $arrival,
    "Seats"         => $seat_number
];
drawTable($pdf,"Bus Details",$busDetails);

// Payment Details
$paymentDetails = [
    "Fare"          => "Rs. $fare",
    "Coupon"        => $coupon,
    "Method"        => $payment_method,
    "Status"        => $payment_status,
    "Transaction ID(s)"=> $transaction_id
];
drawTable($pdf,"Payment Details",$paymentDetails);

// Traveller Details - Combine all travellers in ONE table
// Traveller Details - Proper Table Format
$pdf->SetFont('Arial','B',14);
$pdf->SetFillColor(200,200,200);
$pdf->Cell(190,10,"Traveller Details",1,1,'C',true);

$pdf->SetFont('Arial','B',12);
$pdf->SetFillColor(173,216,230);
$pdf->Cell(47.5,10,"Name",1,0,'C',true);
$pdf->Cell(30,10,"Gender",1,0,'C',true);
$pdf->Cell(60,10,"Email",1,0,'C',true);
$pdf->Cell(52.5,10,"Phone",1,1,'C',true);

$pdf->SetFont('Arial','',12);
foreach ($travellers as $tr) {
    $pdf->SetFillColor(255,255,255);
    $pdf->Cell(47.5,10,$tr['name'],1,0,'C',true);
    $pdf->Cell(30,10,$tr['gender'],1,0,'C',true);
    $pdf->Cell(60,10,$tr['email'],1,0,'C',true);
    $pdf->Cell(52.5,10,$tr['phone'],1,1,'C',true);
}
$pdf->Ln(5);

// Output PDF
$pdf->Output("D","Bus_Ticket_$booking_id.pdf");
?>
