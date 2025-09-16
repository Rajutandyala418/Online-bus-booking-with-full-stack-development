<?php
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/ticket_pdf.php';

// ------------------ Get booking_id from GET ------------------
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if (!$booking_id) die("❌ Booking ID missing.");
$coupon = $_GET['coupon'] ?? 'None';

// ---------------- Fetch booking + schedule + route + bus + user ----------------
$stmt = $conn->prepare("
    SELECT b.id AS booking_id, b.booking_date, b.status, b.user_id,
           b.source, b.destination,   -- ✅ taken from bookings
           s.travel_date, s.departure_time, s.arrival_time,
           bu.bus_name, bu.bus_number,
           p.amount, p.payment_method, p.payment_status, p.transaction_id,
           u.username, u.first_name
    FROM bookings b
    LEFT JOIN schedules s ON b.schedule_id = s.id
    LEFT JOIN buses bu ON s.bus_id = bu.id
    LEFT JOIN payments p ON b.id = p.booking_id
    LEFT JOIN users u ON b.user_id = u.id
    WHERE b.id = ?
    LIMIT 1
");

// ---------------- Fetch seats linked to booking_id ----------------
$sStmt = $conn->prepare("SELECT seat_number FROM booking_seats WHERE booking_id = ?");
$sStmt->bind_param("i", $booking_id);
$sStmt->execute();
$seatResult = $sStmt->get_result();
$seatNumbers = [];
while ($row = $seatResult->fetch_assoc()) {
    $seatNumbers[] = $row['seat_number'];
}
$sStmt->close();

$seat_number = implode(',', $seatNumbers); // ✅ final seat list

$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) die("❌ Booking not found.");
$data = $result->fetch_assoc();
$stmt->close();

// ---------------- Fetch traveller linked to booking_id ----------------
$tStmt = $conn->prepare("SELECT name, email, phone, gender FROM travellers WHERE booking_id = ?");
$tStmt->bind_param("i", $booking_id);
$tStmt->execute();
$travellerResult = $tStmt->get_result();
$traveller = $travellerResult->fetch_assoc();
$tStmt->close();

// ---------------- Extract details ----------------
$bus_name       = $data['bus_name'];
$bus_number     = $data['bus_number'];
$route          = $data['source'] . " → " . $data['destination'];
$travel_date    = $data['travel_date'];
$departure      = $data['departure_time'];
$arrival        = $data['arrival_time'];
$booking_status = $data['status'];
$fare           = (float)$data['amount'];
$payment_method = $data['payment_method'];
$payment_status = $data['payment_status'];
$transaction_id = $data['transaction_id'];
$username       = $data['username'];
$first_name     = $data['first_name'];
$booking_date   = $data['booking_date'];

// ---------------- Ticket HTML Message ----------------
$ticketHTML = "<pre style='font-family:Arial,sans-serif; font-size:14px;'>
🎫 Bus Ticket
────────────────────────
🆔 Booking ID: {$booking_id}
📅 Booking Date: {$booking_date}
✅ Status: {$booking_status}
────────────────────────
🚌 Bus Details
Bus: {$bus_name} ({$bus_number})
Route: {$route}
Travel Date: {$travel_date}
Departure: {$departure}
Arrival: {$arrival}
Seats: {$seat_number}
────────────────────────
👤 Traveller Details
Name: {$traveller['name']}
Email: {$traveller['email']}
Phone: {$traveller['phone']}
Gender: {$traveller['gender']}
────────────────────────
💰 Payment Details
Base Fare: ₹{$fare}
Method: {$payment_method}
Status: {$payment_status}
Txn ID: {$transaction_id}
Coupon: {$coupon}
────────────────────────
🙏 Thank you for booking with Bus Booking System!
</pre>";

// ---------------- Email Function with PDF Attachment ----------------
function sendTicketEmail($toEmail, $toName, $subject, $bodyHTML, $booking_id, $coupon) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';  
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com','Bus Booking System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHTML;
        $mail->AltBody = strip_tags($bodyHTML);

        global $data, $traveller;
        $pdfContent = generateTicketPDF($data, $traveller, $booking_id, $coupon);
        $mail->addStringAttachment($pdfContent, "BusTicket_$booking_id.pdf");

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ---------------- Auto-send email to traveller ----------------
// ---------------- Auto-send email to traveller (only once) ----------------
if (!isset($_GET['sent'])) {
    sendTicketEmail($traveller['email'], $traveller['name'], "Your Bus Ticket - Booking #$booking_id", $ticketHTML, $booking_id, $coupon);
    // Redirect to same page with sent=1 to prevent resending on refresh
    header("Location: booking_success.php?booking_id=$booking_id&coupon=".urlencode($coupon)."&sent=1");
    exit;
}

// ---------------- WhatsApp message ----------------
$whatsappMessage = urlencode(strip_tags($ticketHTML));
$phoneNumber = preg_replace('/[^0-9]/','',$traveller['phone']);
$whatsappLink = "https://wa.me/{$phoneNumber}?text={$whatsappMessage}";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Success</title>
<style>
body { font-family:'Poppins',sans-serif; background:#111; color:#fff; margin:0; padding:20px; }
.top-bar { display:flex; justify-content:space-between; align-items:center; }
.dashboard-btn { background:#ffde59; padding:10px 20px; border-radius:5px; color:#111; font-weight:bold; text-decoration:none; }
.container { margin-top:30px; }
table { width:100%; border-collapse:collapse; margin-bottom:20px; background:rgba(0,0,0,0.4); }
table, th, td { border:1px solid #555; }
th, td { padding:10px; text-align:left; }
th { background:#222; color:#ffde59; width:30%; }
h2,h3 { color:#ffde59; margin-bottom:10px; }
.btn { background:linear-gradient(90deg,#ff512f,#dd2476); padding:10px 20px; border-radius:5px; text-decoration:none; color:white; font-weight:bold; margin-right:10px; cursor:pointer; border:none; }
.bottom-btns { margin-top:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
input[type=email] { padding:8px; border-radius:5px; border:none; width:220px; }
</style>
</head>
<body>

<div class="top-bar">
<a class="dashboard-btn" href="dashboard.php?username=<?= urlencode($username) ?>">Back to Dashboard</a>
</div>

<div class="container">
<h2>🎉 Booking Successful - ID: <?php echo $booking_id; ?></h2>

<h3>🚌 Bus Details</h3>
<table>
<tr><th>Bus Name</th><td><?php echo htmlspecialchars($bus_name); ?></td></tr>
<tr><th>Bus Number</th><td><?php echo htmlspecialchars($bus_number); ?></td></tr>
<tr><th>Route</th><td><?php echo htmlspecialchars($route); ?></td></tr>
<tr><th>Travel Date</th><td><?php echo htmlspecialchars($travel_date); ?></td></tr>
<tr><th>Departure</th><td><?php echo htmlspecialchars($departure); ?></td></tr>
<tr><th>Arrival</th><td><?php echo htmlspecialchars($arrival); ?></td></tr>
<tr><th>Seats</th><td><?php echo htmlspecialchars($seat_number); ?></td></tr>
<tr><th>Status</th><td><?php echo htmlspecialchars($booking_status); ?></td></tr>
</table>

<h3>💰 Payment Details</h3>
<table>
<tr><th>Total Fare</th><td>₹<?php echo $fare; ?></td></tr>
<tr><th>Coupon</th><td><?php echo htmlspecialchars($coupon); ?></td></tr>
<tr><th>Payment Method</th><td><?php echo htmlspecialchars($payment_method); ?></td></tr>
<tr><th>Payment Status</th><td><?php echo htmlspecialchars($payment_status); ?></td></tr>
<tr><th>Transaction ID</th><td><?php echo htmlspecialchars($transaction_id); ?></td></tr>
</table>

<h3>👤 Traveller Details</h3>
<table>
<tr><th>Name</th><td><?php echo htmlspecialchars($traveller['name']); ?></td></tr>
<tr><th>Email</th><td><?php echo htmlspecialchars($traveller['email']); ?></td></tr>
<tr><th>Phone</th><td><?php echo htmlspecialchars($traveller['phone']); ?></td></tr>
<tr><th>Gender</th><td><?php echo htmlspecialchars($traveller['gender']); ?></td></tr>
</table>

<div class="bottom-btns">
   <a class="btn" href="download_ticket.php?booking_id=<?= $booking_id ?>&coupon=<?= urlencode($coupon) ?>" target="_blank">
    Download Ticket (PDF)
   </a>

    <form method="post" onsubmit="return sendManualEmail(event);">
        <input type="email" name="manual_email" placeholder="Enter email to send" required>
        <button type="submit" class="btn">Send Email</button>
    </form>

    <a class="btn" href="<?= $whatsappLink ?>" target="_blank">Send WhatsApp</a>
</div>

<script>
function sendManualEmail(e){
    e.preventDefault();
    let email = e.target.manual_email.value;
    fetch("", {
        method:"POST",
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'manual_email='+encodeURIComponent(email)
    }).then(res => res.text()).then(()=> {
        alert("Email sent successfully to " + email);
    }).catch(()=>{ alert("Failed to send email"); });
    return false;
}
</script>

<?php
// Manual email send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['manual_email'])) {
    $manual_email = $_POST['manual_email'];
    sendTicketEmail($manual_email,$traveller['name'],"Your Bus Ticket - Booking #$booking_id",$ticketHTML,$booking_id,$coupon);
    exit;
}
?>
</body>
</html>
