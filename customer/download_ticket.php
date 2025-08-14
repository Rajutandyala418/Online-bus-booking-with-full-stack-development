<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require(__DIR__ . '/../fpdf/fpdf.php');

// ✅ Ensure required session variables exist
if (!isset($_SESSION['bus_details'], $_SESSION['traveller_details'], $_SESSION['booking_id'])) {
    die("Booking details not found. Please try again.");
}

$bus = $_SESSION['bus_details'];
$traveller = $_SESSION['traveller_details'];
$booking_id = $_SESSION['booking_id'];

// You may need to also store these in session in booking_success.php
$booking_date = date('Y-m-d H:i:s');
$status = 'Confirmed';

$fare = (float) $bus['fare'];
$base_fare = round($fare / 1.05, 2);
$gst = round($fare - $base_fare, 2);
$transaction_id = 'TXN-' . strtoupper(substr(md5($booking_id), 0, 8));

// ✅ Create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Title
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Booking Ticket', 0, 1, 'C');
$pdf->Ln(5);

// Booking Info
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Booking ID:', 1);
$pdf->Cell(140, 8, $booking_id, 1, 1);
$pdf->Cell(50, 8, 'Booking Date:', 1);
$pdf->Cell(140, 8, $booking_date, 1, 1);
$pdf->Cell(50, 8, 'Status:', 1);
$pdf->Cell(140, 8, $status, 1, 1);

$pdf->Ln(8);

// ----------------- BUS DETAILS -----------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'Bus Details', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->Cell(50, 8, 'Bus Name', 1);
$pdf->Cell(140, 8, $bus['bus_name'], 1, 1);
$pdf->Cell(50, 8, 'Bus Number', 1);
$pdf->Cell(140, 8, $bus['bus_number'], 1, 1);
$pdf->Cell(50, 8, 'Route', 1);
$pdf->Cell(140, 8, $bus['route'], 1, 1);
$pdf->Cell(50, 8, 'Travel Date', 1);
$pdf->Cell(140, 8, $bus['travel_date'], 1, 1);
$pdf->Cell(50, 8, 'Departure', 1);
$pdf->Cell(140, 8, $bus['departure'], 1, 1);
$pdf->Cell(50, 8, 'Arrival', 1);
$pdf->Cell(140, 8, $bus['arrival'], 1, 1);
$pdf->Cell(50, 8, 'Seats', 1);
$pdf->Cell(140, 8, $bus['seats'], 1, 1);

$pdf->Ln(8);

// ----------------- TRAVELLER DETAILS -----------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'Traveller Details', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->Cell(50, 8, 'Name', 1);
$pdf->Cell(140, 8, $traveller['name'], 1, 1);
$pdf->Cell(50, 8, 'Email', 1);
$pdf->Cell(140, 8, $traveller['email'], 1, 1);
$pdf->Cell(50, 8, 'Phone', 1);
$pdf->Cell(140, 8, $traveller['phone'], 1, 1);

$pdf->Ln(8);

// ----------------- PAYMENT DETAILS -----------------
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 8, 'Payment Details', 0, 1);
$pdf->SetFont('Arial', '', 12);

$pdf->Cell(50, 8, 'Base Fare', 1);
$pdf->Cell(140, 8, '' . number_format($base_fare, 2), 1, 1);
$pdf->Cell(50, 8, 'GST (5%)', 1);
$pdf->Cell(140, 8, '' . number_format($gst, 2), 1, 1);
$pdf->Cell(50, 8, 'Total Fare', 1);
$pdf->Cell(140, 8, '' . number_format($fare, 2), 1, 1);
$pdf->Cell(50, 8, 'Transaction ID', 1);
$pdf->Cell(140, 8, $transaction_id, 1, 1);
$pdf->Cell(50, 8, 'Payment Status', 1);
$pdf->Cell(140, 8, 'Paid', 1, 1);

// Remove spaces and special characters from traveller name
$safe_name = preg_replace('/[^A-Za-z0-9\-]/', '_', $traveller['name']);

$pdf->Output('D', $safe_name . '_' . $booking_id . '.pdf');

exit;
?>
