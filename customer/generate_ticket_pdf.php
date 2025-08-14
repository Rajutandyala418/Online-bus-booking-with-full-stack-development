<?php
require_once __DIR__ . '/../include/dompdf/autoload.inc.php';
include(__DIR__ . '/../include/db_connect.php');

use Dompdf\Dompdf;

$booking_id = $_GET['booking_id'] ?? null;

if (!$booking_id) {
    die("Invalid booking ID.");
}

$stmt = $conn->prepare("
    SELECT b.id, u.name, u.email, bu.bus_name, r.source, r.destination, r.fare, s.travel_date, s.departure_time, s.arrival_time, b.seat_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN buses bu ON s.bus_id = bu.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

if (!$ticket) {
    die("Ticket not found.");
}

$html = "
    <h1 style='color: #ff512f;'>Bus Ticket</h1>
    <p><strong>Booking ID:</strong> {$ticket['id']}</p>
    <p><strong>Name:</strong> {$ticket['name']}</p>
    <p><strong>Email:</strong> {$ticket['email']}</p>
    <p><strong>Bus:</strong> {$ticket['bus_name']}</p>
    <p><strong>Route:</strong> {$ticket['source']} → {$ticket['destination']}</p>
    <p><strong>Fare:</strong> ₹{$ticket['fare']}</p>
    <p><strong>Date:</strong> {$ticket['travel_date']}</p>
    <p><strong>Departure:</strong> {$ticket['departure_time']}</p>
    <p><strong>Arrival:</strong> {$ticket['arrival_time']}</p>
    <p><strong>Seat:</strong> {$ticket['seat_number']}</p>
";

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("ticket_{$ticket['id']}.pdf", ["Attachment" => true]);
exit();
?>
