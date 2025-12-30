<?php
require __DIR__ . '/../fpdf/fpdf.php';

function generateTicketPDF($data, $travellers, $booking_id, $coupon) {
    // -------- Fetch seat numbers from booking_seats --------
    $conn = new mysqli('localhost', 'root', '', 'bus_booking');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $seat_query = "SELECT seat_number FROM booking_seats WHERE booking_id = ?";
    $stmt = $conn->prepare($seat_query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $seat_result = $stmt->get_result();

    $seats = [];
    while ($row = $seat_result->fetch_assoc()) {
        $seats[] = $row['seat_number'];
    }
    $data['seat_number'] = implode(", ", $seats);

    // -------- Ensure all required keys exist --------
    $requiredKeys = [
        'booking_date', 'status', 'bus_name', 'bus_number', 'source', 'destination',
        'travel_date', 'departure_time', 'arrival_time', 'amount', 'payment_method',
        'payment_status', 'transaction_id'
    ];

    foreach ($requiredKeys as $key) {
        if (!isset($data[$key])) {
            $data[$key] = 'N/A';
        }
    }

    $pdf = new FPDF();
    $pdf->AddPage();

    // Border
    $pdf->Rect(5, 5, 200, 287);

    // Title
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(190,12,'Varahi Bus Ticket',0,1,'C');
    $pdf->Ln(5);

    // -------- Booking Info Row --------
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(173,216,230);
    $pdf->Cell(63,10,"Booking ID: $booking_id",1,0,'C',true);

    $pdf->SetFillColor(144,238,144);
    $pdf->Cell(63,10,"Date: {$data['booking_date']}",1,0,'C',true);

    $pdf->SetFillColor(255,255,153);
    $pdf->Cell(64,10,"Status: {$data['status']}",1,1,'C',true);
    $pdf->Ln(5);

    // -------- Helper: Draw Key-Value Table --------
    $drawTable = function($title, $dataArr) use ($pdf) {
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
    };

    // -------- Bus Details --------
    $drawTable("Bus Details", [
        "Bus Name"      => $data['bus_name'],
        "Bus Number"    => $data['bus_number'],
        "Route"         => $data['source'] . " → " . $data['destination'],
        "Travel Date"   => $data['travel_date'],
        "Departure"     => $data['departure_time'],
        "Arrival"       => $data['arrival_time'],
        "Seats"         => $data['seat_number']
    ]);

    // -------- Payment Details --------
    $drawTable("Payment Details", [
        "Fare"          => "Rs. {$data['amount']}",
        "Coupon"        => $coupon,
        "Method"        => $data['payment_method'],
        "Status"        => $data['payment_status'],
        "Transaction ID"=> $data['transaction_id']
    ]);

    // -------- Traveller Details (Updated Layout) --------
    $pdf->SetFont('Arial','B',14);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(190,10,"Traveller Details",1,1,'C',true);

    // Header
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(220,220,255);
    $pdf->Cell(47.5,8,"Name",1,0,'C',true);
    $pdf->Cell(30,8,"Gender",1,0,'C',true);
    $pdf->Cell(60,8,"Email",1,0,'C',true);
    $pdf->Cell(52.5,8,"Phone",1,1,'C',true);

    // Rows
    $pdf->SetFont('Arial','',12);
    if (!empty($travellers) && is_array($travellers)) {
        foreach ($travellers as $trav) {
            $pdf->Cell(47.5,8,($trav['name'] ?? 'N/A'),1,0,'C');
            $pdf->Cell(30,8,($trav['gender'] ?? 'N/A'),1,0,'C');
            $pdf->Cell(60,8,($trav['email'] ?? 'N/A'),1,0,'C');
            $pdf->Cell(52.5,8,($trav['phone'] ?? 'N/A'),1,1,'C');
        }
    } else {
        $pdf->Cell(190,8,"No traveller details available",1,1,'C');
    }

    $conn->close();

    return $pdf->Output("S");
}
?>
