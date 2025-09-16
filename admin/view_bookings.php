<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../include/db_connect.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$admin_id = $_SESSION['admin_id'];
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// ------------- Handle Cancel Booking ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = intval($_POST['booking_id']);

    // 1. Update booking status
    $updateBooking = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=?");
    $updateBooking->bind_param("i", $booking_id);
    $updateBooking->execute();

    // 2. Update payment status
    $updatePayment = $conn->prepare("UPDATE payments SET payment_status='failed' WHERE booking_id=?");
    $updatePayment->bind_param("i", $booking_id);
    $updatePayment->execute();

    // 3. Fetch booking & user details for email
$detailsStmt = $conn->prepare("SELECT u.email, CONCAT(u.first_name,' ',u.last_name) AS user_name, 
    b.source, b.destination
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id=?");

    $detailsStmt->bind_param("i", $booking_id);
    $detailsStmt->execute();
    $res = $detailsStmt->get_result()->fetch_assoc();

    // 4. Send email to user
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'varahibusbooking@gmail.com';
        $mail->Password = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking');
        $mail->addAddress($res['email'], $res['user_name']);
        $mail->Subject = "Booking Cancelled - Varahi Bus";
        $mail->Body = "Dear {$res['user_name']},\n\nYour booking from {$res['source']} to {$res['destination']} has been cancelled by the admin as per your request. Please check your details and book again for your new journey.\n\nThank you,\nVarahi Bus Team";
        $mail->send();
    } catch (Exception $e) {
        // optional: log error
    }

    header("Location: view_bookings.php");
    exit();
}

// ------------- Handle PDF Download ----------------
if (isset($_GET['download_pdf']) && $_GET['download_pdf']==1) {
    require_once __DIR__ . '/../fpdf/fpdf.php';

    // Fetch fresh booking data
    $stmtPDF = null;
if ($admin_id == 3) {
    $queryPDF = "SELECT b.id AS booking_id, CONCAT(u.first_name,' ',u.last_name) AS user_name,
        bs.bus_name, b.source AS from_location, b.destination AS to_location,
        s.departure_time, s.arrival_time, b.seat_number, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bs ON s.bus_id = bs.id
        ORDER BY b.created_at DESC";
    $stmtPDF = $conn->prepare($queryPDF);
} else {
    $queryPDF = "SELECT b.id AS booking_id, CONCAT(u.first_name,' ',u.last_name) AS user_name,
        bs.bus_name, b.source AS from_location, b.destination AS to_location,
        s.departure_time, s.arrival_time, b.seat_number, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bs ON s.bus_id = bs.id
        WHERE bs.admin_id = ?
        ORDER BY b.created_at DESC";
    $stmtPDF = $conn->prepare($queryPDF);
    $stmtPDF->bind_param("i",$admin_id);
}

    $stmtPDF->execute();
    $resultPDF = $stmtPDF->get_result();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Booking Records',0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',10);
    $headers = ['ID','User Name','Bus Name','Route','Departure','Arrival','Seat','Booked At'];
    foreach($headers as $header){
        $pdf->Cell(25,8,$header,1,0,'C',true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial','',9);
    while($row = $resultPDF->fetch_assoc()){
        $pdf->Cell(25,8,$row['booking_id'],1,0,'C');
        $pdf->Cell(25,8,$row['user_name'],1,0);
        $pdf->Cell(25,8,$row['bus_name'],1,0);
        $pdf->Cell(30,8,$row['from_location']." → ".$row['to_location'],1,0);
        $pdf->Cell(20,8,$row['departure_time'],1,0);
        $pdf->Cell(20,8,$row['arrival_time'],1,0);
        $pdf->Cell(15,8,$row['seat_number'],1,0);
        $pdf->Cell(30,8,$row['created_at'],1,1);
    }
    $pdf->Output('D','booking_records.pdf');
    exit();
}

// ------------- Handle Send Email ----------------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send_email'])) {
    require_once __DIR__ . '/../fpdf/fpdf.php';

    // Fetch fresh booking data
    $stmtPDF = null;
   if ($admin_id == 3) {
    $queryPDF = "SELECT b.id AS booking_id, CONCAT(u.first_name,' ',u.last_name) AS user_name,
        bs.bus_name, b.source AS from_location, b.destination AS to_location,
        s.departure_time, s.arrival_time, b.seat_number, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bs ON s.bus_id = bs.id
        ORDER BY b.created_at DESC";
    $stmtPDF = $conn->prepare($queryPDF);
} else {
    $queryPDF = "SELECT b.id AS booking_id, CONCAT(u.first_name,' ',u.last_name) AS user_name,
        bs.bus_name, b.source AS from_location, b.destination AS to_location,
        s.departure_time, s.arrival_time, b.seat_number, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bs ON s.bus_id = bs.id
        WHERE bs.admin_id = ?
        ORDER BY b.created_at DESC";
    $stmtPDF = $conn->prepare($queryPDF);
    $stmtPDF->bind_param("i",$admin_id);
}

    $stmtPDF->execute();
    $resultPDF = $stmtPDF->get_result();

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Booking Records',0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','B',10);
    $headers = ['ID','User Name','Bus Name','Route','Departure','Arrival','Seat','Booked At'];
    foreach($headers as $header){
        $pdf->Cell(25,8,$header,1,0,'C',true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial','',9);
    while($row = $resultPDF->fetch_assoc()){
        $pdf->Cell(25,8,$row['booking_id'],1,0,'C');
        $pdf->Cell(25,8,$row['user_name'],1,0);
        $pdf->Cell(25,8,$row['bus_name'],1,0);
        $pdf->Cell(30,8,$row['from_location']." → ".$row['to_location'],1,0);
        $pdf->Cell(20,8,$row['departure_time'],1,0);
        $pdf->Cell(20,8,$row['arrival_time'],1,0);
        $pdf->Cell(15,8,$row['seat_number'],1,0);
        $pdf->Cell(30,8,$row['created_at'],1,1);
    }

    $file_path = __DIR__.'/temp_booking.pdf';
    $pdf->Output($file_path,'F');

 // 3. Fetch traveller email & name for this booking
$detailsStmt = $conn->prepare("
    SELECT u.email, CONCAT(u.first_name,' ',u.last_name) AS user_name,
           b.source, b.destination
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    WHERE b.id=?
");
$detailsStmt->bind_param("i",$booking_id);
$detailsStmt->execute();
$res = $detailsStmt->get_result()->fetch_assoc();
$detailsStmt->close();

// 4. Send email to traveller (not admin)
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'varahibusbooking@gmail.com';
    $mail->Password = 'pjhg nwnt haac nsiu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking');
    $mail->addAddress($res['email'], $res['user_name']);
    $mail->Subject = "Booking Cancelled - Varahi Bus";
    $mail->Body = "Dear {$res['user_name']},\n\nYour booking from {$res['source']} to {$res['destination']} has been cancelled by the admin as per your request.\n\nThank you,\nVarahi Bus Team";
    $mail->send();
} catch (Exception $e) {
    // optional: log error
}

    header("Location: view_bookings.php");
    exit();
}

// ------------- Fetch Bookings for Display ----------------
// ------------- Fetch Bookings for Display ----------------
$stmt = null;
if ($admin_id == 3) {
    $query = "SELECT b.id AS booking_id, b.user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name,
        b.schedule_id, s.departure_time, s.arrival_time,
        b.source AS from_location, b.destination AS to_location,
        bs.bus_name, b.seat_number, b.status, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bs ON s.bus_id = bs.id";
    if ($statusFilter) {
        $query .= " WHERE b.status=?";
        $query .= " ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s",$statusFilter);
    } else {
        $query .= " ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($query);
    }
} else {
    $query = "SELECT b.id AS booking_id, b.user_id, CONCAT(u.first_name,' ',u.last_name) AS user_name,
        b.schedule_id, s.departure_time, s.arrival_time,
        b.source AS from_location, b.destination AS to_location,
        bs.bus_name, b.seat_number, b.status, b.created_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN schedules s ON b.schedule_id = s.id
        JOIN buses bs ON s.bus_id = bs.id
        WHERE bs.admin_id=?";
    if ($statusFilter) {
        $query .= " AND b.status=?";
        $query .= " ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is",$admin_id,$statusFilter);
    } else {
        $query .= " ORDER BY b.created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i",$admin_id);
    }
}

if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            color: white;
        }
        video.bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            z-index: -1;
            filter: brightness(0.4);
        }
        .header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 20px;
            background: rgba(0, 0, 0, 0.7);
            position: relative;
        }
        .welcome {
            margin-right: 10px;
        }
        .profile-container {
            position: relative;
            cursor: pointer;
        }
        .profile {
            border-radius: 50%;
            width: 40px;
            height: 40px;
        }
        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: rgba(0,0,0,0.85);
            border-radius: 5px;
            overflow: hidden;
            min-width: 150px;
            z-index: 10;
        }
        .dropdown a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
        }
        .dropdown a:hover {
            background: rgba(255,255,255,0.1);
        }
.profile-pic {
    width: 40px;
    height: 40px;
    background: white;
    color: #007bff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
    font-size: 20px;
    cursor: pointer;
    user-select: none;
}

        .main-content {
            padding: 20px;
            max-width: 1100px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 10px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        th, td {
            border: 1px solid white;
            padding: 8px;
            text-align: center;
        }
        th {
            background: rgba(0, 123, 255, 0.8);
        }
        #dashboardBtn {
    position: fixed;
    top: 20px;
    right: 30px;
    background: #ff512f;
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    transition: transform 0.2s, background 0.2s;
    z-index: 1001; /* above other elements */
}
#dashboardBtn:hover {
    transform: scale(1.05);
    background: #dd2476;
}
        .status-confirmed { color: #28a745; font-weight: bold; font-size:20px; }    /* Green */
        .status-cancelled { color: #dc3545; font-weight: bold; font-size:20px;}    /* Red */
        .status-failed { color: #6c757d; font-weight: bold; font-size:20px;}       /* Gray */
        .status-completed { color: #007bff; font-weight: bold; font-size:20px;}    /* Blue */
    </style>
</head>
<body>
<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>
<video autoplay muted loop class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>


<div class="main-content">
    <h2>Manage Bookings</h2>
<div style="margin-bottom: 15px;">
    <a href="?download_pdf=1" style="padding:10px 20px; background:#ff512f; color:white; border-radius:5px; text-decoration:none;">Download PDF</a>
    <form method="post" style="display:inline;">
        <button type="submit" name="send_email" style="padding:10px 20px; background:#007bff; color:white; border-radius:5px;">Send Email</button>
    </form>
</div>

    <table>
<form method="get" style="margin-bottom: 15px; text-align: right;">
    <label for="statusFilter" style="color: white; font-size:20px; margin-right: 10px;">Filter by Status:</label>
    <select name="status" id="statusFilter" style = "font-size:30px;" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="booked" <?php if(isset($_GET['status']) && $_GET['status']=='booked') echo 'selected'; ?>>Confirmed</option>
        <option value="cancelled" <?php if(isset($_GET['status']) && $_GET['status']=='cancelled') echo 'selected'; ?>>Cancelled</option>
        </select>
</form>

        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>Bus Name</th>
                <th>Route</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Seat</th>
                <th>Status</th>
                <th>Booked At</th>
<th> Action </th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) {
                $status_class = '';
                switch (strtolower($row['status'])) {
                    case 'booked': $status_class = 'status-confirmed'; break;
                    case 'cancelled': $status_class = 'status-cancelled'; break;
                    case 'failed': $status_class = 'status-failed'; break;
                    case 'completed': $status_class = 'status-completed'; break;
                    default: $status_class = '';
                }
            ?>
                <tr>
                    <td><?php echo $row['booking_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['bus_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['from_location'] . " → " . $row['to_location']); ?></td>
                    <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['arrival_time']); ?></td>
                    <?php
// Fetch seats for this booking_id
$seatStmt = $conn->prepare("SELECT seat_number FROM booking_seats WHERE booking_id=?");
$seatStmt->bind_param("i",$row['booking_id']);
$seatStmt->execute();
$seatResult = $seatStmt->get_result();
$seatArr = [];
while($seatRow = $seatResult->fetch_assoc()){
    $seatArr[] = $seatRow['seat_number'];
}
$seat_number = implode(', ', $seatArr);
$seatStmt->close();
?>
<td><?php echo htmlspecialchars($seat_number); ?></td>

                    <td class="<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
<td>
    <?php if(strtolower($row['status']) != 'cancelled'): ?>
        <form method="post" style="display:inline;">
            <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
            <button type="submit" name="cancel_booking" style="padding:5px 10px; background:#dc3545; color:white; border-radius:5px;">Cancel</button>
        </form>
    <?php else: ?>
        Cancelled
    <?php endif; ?>
</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

window.onclick = function(event) {
    if (!event.target.closest('.profile-container')) {
        document.getElementById('profileDropdown').style.display = 'none';
    }
}
</script>

</body>
</html>
