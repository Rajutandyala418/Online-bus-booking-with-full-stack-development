<?php
require __DIR__ . '/../fpdf/fpdf.php';

function generateTicketPDF($data, $traveller, $booking_id, $coupon) {
    // -------- Fetch seat numbers from booking_seats --------
    // Create a new MySQL connection (adjust credentials if needed)
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
    $data['seat_number'] = implode(", ", $seats); // Add seat numbers to $data

    $pdf = new FPDF();
    $pdf->AddPage();

    // Border
    $pdf->Rect(5, 5, 200, 287); 

    // Title
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(190,12,'Varahi Bus Ticket',0,1,'C');
    $pdf->Ln(5);

    // -------- Booking Info Row (ID, Date, Status) --------
    $pdf->SetFont('Arial','B',12);

    // Booking ID (Blue)
    $pdf->SetFillColor(173,216,230);
    $pdf->Cell(63,10,"Booking ID: $booking_id",1,0,'C',true);

    // Booking Date (Light Green)
    $pdf->SetFillColor(144,238,144);
    $pdf->Cell(63,10,"Date: {$data['booking_date']}",1,0,'C',true);

    // Status (Light Yellow)
    $pdf->SetFillColor(255,255,153);
    $pdf->Cell(64,10,"Status: {$data['status']}",1,1,'C',true);

    $pdf->Ln(5);

    // -------- Function to Draw Tables --------
    $drawTable = function($title, $dataArr) use ($pdf) {
        // Section title
        $pdf->SetFont('Arial','B',14);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(190,10,$title,1,1,'C',true);

        // Table rows
        $pdf->SetFont('Arial','',12);
        foreach ($dataArr as $key => $value) {
            $pdf->SetFillColor(220,220,255); // light purple
            $pdf->Cell(60,8,$key,1,0,'L',true);

            $pdf->SetFillColor(255,255,255); // white
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

    // -------- Traveller Details --------
    $drawTable("Traveller Details", [
        "Name"   => $traveller['name'],
        "Gender" => $traveller['gender'],
        "Email"  => $traveller['email'],
        "Phone"  => $traveller['phone']
    ]);

    // Close DB connection
    $conn->close();

    // Return PDF as string (for email attachment)
    return $pdf->Output("S");
}
