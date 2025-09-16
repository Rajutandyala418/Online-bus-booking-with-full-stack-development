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

$status_filter = isset($_GET['status']) ? $_GET['status'] : "";

// Super Admin query
if ($admin_id == 3) {
  $query = "SELECT p.id, u.first_name, u.last_name, u.email, bs.bus_name, 
          b.source AS source, b.destination AS destination, 
          s.departure_time, s.arrival_time, p.amount, p.created_at, p.payment_status
          FROM payments p
          JOIN bookings b ON p.booking_id = b.id
          JOIN users u ON b.user_id = u.id
          JOIN schedules s ON b.schedule_id = s.id
          JOIN buses bs ON s.bus_id = bs.id";
if ($status_filter !== "") {
    $query .= " WHERE p.payment_status = ?";
}
$query .= " ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($query);
    if ($status_filter !== "") {
        $stmt->bind_param("s", $status_filter);
    }
} else {
$query = "SELECT p.id, u.first_name, u.last_name, u.email, bs.bus_name, 
          b.source AS source, b.destination AS destination, 
          s.departure_time, s.arrival_time, p.amount, p.created_at, p.payment_status
          FROM payments p
          JOIN bookings b ON p.booking_id = b.id
          JOIN users u ON b.user_id = u.id
          JOIN schedules s ON b.schedule_id = s.id
          JOIN buses bs ON s.bus_id = bs.id
          WHERE bs.admin_id = ?";
if ($status_filter !== "") {
    $query .= " AND p.payment_status = ?";
}
$query .= " ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($query);
    if ($status_filter !== "") {
        $stmt->bind_param("is", $admin_id, $status_filter);
    } else {
        $stmt->bind_param("i", $admin_id);
    }
}

if (!$stmt) die("Prepare failed (main query): (" . $conn->errno . ") " . $conn->error);

$stmt->execute();
$result = $stmt->get_result();

// Total payments
if ($admin_id == 3) {
    $total_query = "SELECT SUM(p.amount) AS total_amount
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.id
                    JOIN schedules s ON b.schedule_id = s.id
                    JOIN buses bs ON s.bus_id = bs.id
                    WHERE p.payment_status = 'Paid'";
    $total_stmt = $conn->prepare($total_query);
} else {
    $total_query = "SELECT SUM(p.amount) AS total_amount
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.id
                    JOIN schedules s ON b.schedule_id = s.id
                    JOIN buses bs ON s.bus_id = bs.id
                    WHERE bs.admin_id = ? AND p.payment_status = 'Paid'";
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->bind_param("i", $admin_id);
}

$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_payment = $total_row['total_amount'] ?? 0;

if (isset($_GET['download_pdf'])) {
 require_once __DIR__ . '/../fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);

    // Header
    $pdf->Cell(0,10,'Payment Records',0,1,'C');
    $pdf->Ln(5);

    // Table Header
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $pdf->Cell(10,8,'ID',1,0,'C',true);
    $pdf->Cell(30,8,'User Name',1,0,'C',true);
    $pdf->Cell(40,8,'Email',1,0,'C',true);
    $pdf->Cell(30,8,'Bus Name',1,0,'C',true);
    $pdf->Cell(35,8,'Route',1,0,'C',true);
    $pdf->Cell(20,8,'Departure',1,0,'C',true);
    $pdf->Cell(20,8,'Arrival',1,0,'C',true);
    $pdf->Cell(20,8,'Amount',1,0,'C',true);
    $pdf->Cell(30,8,'Payment Date',1,0,'C',true);
    $pdf->Cell(20,8,'Status',1,1,'C',true);

    // Table Body
    $pdf->SetFont('Arial','',10);
    foreach ($result as $row) {
        $full_name = $row['first_name'].' '.$row['last_name'];
        $route = $row['source'].' → '.$row['destination'];
        $status = $row['payment_status'];

        $pdf->Cell(10,8,$row['id'],1,0,'C');
        $pdf->Cell(30,8,$full_name,1,0);
        $pdf->Cell(40,8,$row['email'],1,0);
        $pdf->Cell(30,8,$row['bus_name'],1,0);
        $pdf->Cell(35,8,$route,1,0);
        $pdf->Cell(20,8,$row['departure_time'],1,0);
        $pdf->Cell(20,8,$row['arrival_time'],1,0);
        $pdf->Cell(20,8,number_format($row['amount'],2),1,0,'R');
        $pdf->Cell(30,8,$row['created_at'],1,0,'C');
        if($status=='Paid'){
            $pdf->SetTextColor(0,128,0); // green
        } else {
            $pdf->SetTextColor(255,0,0); // red
        }
        $pdf->Cell(20,8,$status,1,1,'C');
        $pdf->SetTextColor(0,0,0); // reset to black
    }

    $pdf->Output('D','payment_records.pdf');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    require_once __DIR__ . '/../fpdf/fpdf.php';

    // Fetch admin email dynamically
    $admin_email_stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
    $admin_email_stmt->bind_param("i", $admin_id);
    $admin_email_stmt->execute();
    $admin_email_result = $admin_email_stmt->get_result();
    $admin_row = $admin_email_result->fetch_assoc();
    $admin_email = $admin_row['email'] ?? 'admin@example.com'; // fallback email

    // Generate PDF in landscape
    $pdf = new FPDF('L','mm','A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'Payment Records',0,1,'C');
    $pdf->Ln(5);

    // Table header
    $pdf->SetFont('Arial','B',10);
    $pdf->SetFillColor(200,200,200);
    $col_widths = [10, 25, 35, 30, 35, 20, 20, 20, 25, 20];
    $headers = ['ID','User Name','Email','Bus Name','Route','Departure','Arrival','Amount','Payment Date','Status'];
    foreach ($headers as $i => $header) {
        $pdf->Cell($col_widths[$i],8,$header,1,0,'C',true);
    }
    $pdf->Ln();

    // Table body
    $pdf->SetFont('Arial','',9);
    foreach ($result as $row) {
        $full_name = $row['first_name'].' '.$row['last_name'];
        $route = $row['source'].' → '.$row['destination'];
        $status = $row['payment_status'];

        $pdf->Cell($col_widths[0],8,$row['id'],1,0,'C');
        $pdf->Cell($col_widths[1],8,$full_name,1,0);
        $pdf->Cell($col_widths[2],8,$row['email'],1,0);
        $pdf->Cell($col_widths[3],8,$row['bus_name'],1,0);
        $pdf->Cell($col_widths[4],8,$route,1,0);
        $pdf->Cell($col_widths[5],8,$row['departure_time'],1,0);
        $pdf->Cell($col_widths[6],8,$row['arrival_time'],1,0);
        $pdf->Cell($col_widths[7],8,number_format($row['amount'],2),1,0,'R');
        $pdf->Cell($col_widths[8],8,$row['created_at'],1,0,'C');

        // Status color
        if ($status == 'Paid') $pdf->SetTextColor(0,128,0); // green
        else $pdf->SetTextColor(255,0,0); // red

        $pdf->Cell($col_widths[9],8,$status,1,1,'C');
        $pdf->SetTextColor(0,0,0); // reset
    }

    // Save PDF temporarily
    $file_path = __DIR__.'/temp_payment.pdf';
    $pdf->Output($file_path,'F');

    // Send email using PHPMailer
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
        $mail->addAddress($admin_email, 'Admin');

        $mail->Subject = "Payment Records PDF";
        $mail->Body = "Dear Admin,\n\nPlease find attached the payment records PDF.\n\nRegards,\nVarahi Team";
        $mail->addAttachment($file_path);

        $mail->send();
        unlink($file_path);
        echo "<script>alert('Email sent successfully to {$admin_email}');</script>";
    } catch (Exception $e) {
        echo "<script>alert('Email could not be sent: {$mail->ErrorInfo}');</script>";
    }
    header("Location: payment.php");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Records</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; color: white; background: rgba(0,0,0,0.8); }
        video.bg-video { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; z-index: -1; filter: brightness(0.4); }
        .header { display: flex; justify-content: flex-end; align-items: center; padding: 10px 20px; background: rgba(0,0,0,0.7); position: relative; }
        .welcome { margin-right: 10px; font-size: 18px; }
        .profile-container { position: relative; cursor: pointer; }
        .profile-pic { width: 40px; height: 40px; background: white; color: #007bff; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 20px; user-select: none; }
        .dropdown { display: none; position: absolute; right: 0; top: 50px; background: rgba(0,0,0,0.85); border-radius: 5px; min-width: 160px; z-index: 10; overflow: hidden; }
        .dropdown a { display: block; padding: 10px; color: white; text-decoration: none; }
        .dropdown a:hover { background: rgba(255,255,255,0.1); }
        .container { padding: 20px; max-width: 1100px; margin: 0 auto; }
        h2 { margin-bottom: 0; }
        .total-payment { margin: 10px 0 20px 0; font-size: 20px; font-weight: bold; color: #0f0; }
     table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(0,0,0,0.6);
}

/* Table header */
th {
    padding: 12px 8px;
    border: 1px solid white;
    text-align: center;
    background: rgba(255,255,255,0.2);
    color: white; /* header text stays white */
}

/* Table body cells */
/* Table body cells */
td {
    padding: 12px 8px;
    border: 1px solid white;
    text-align: center;
    color: white; /* default for other cells */
}

/* Status cells */
td.status-success { color: #00FF00 !important; font-weight: bold; } /* Paid = bright green */
td.status-failed { color: #FF4500 !important; font-weight: bold; }  /* Failed = bright red/orange */

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

    </style>
</head>
<body>
<video autoplay muted loop class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>

<div class="container">
    <h2>Payment Records</h2>
    <div class="total-payment">Total Paid Payments: ₹<?php echo number_format($total_payment, 2); ?></div>
<form method="get" style="margin-bottom: 20px;">
    <label for="status_filter" style="font-size:30px;">Filter by Status: </label>
    <select name="status" id="status_filter" style = "font-size : 30px;" onchange="this.form.submit()">
        <option value="">All</option>
        <option value="Paid" <?php if(isset($_GET['status']) && $_GET['status']=="Paid") echo "selected"; ?>>Paid</option>
        <option value="Failed" <?php if(isset($_GET['status']) && $_GET['status']=="Failed") echo "selected"; ?>>Failed</option>
    </select>
</form>
<div class="container">
    <!-- ✅ Buttons -->
    <div style="margin-bottom: 15px;">
        <a href="?download_pdf=1" style="padding:10px 20px; background:#ff512f; color:white; border-radius:5px; text-decoration:none;">Download PDF</a>
     <form method="post" style="display:inline;">
    <button type="submit" name="send_email" style="padding:10px 20px; background:#007bff; color:white; border-radius:5px;">Send Email</button>
</form>

    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Bus Name</th>
                <th>Route</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Amount (₹)</th>
                <th>Payment Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { 
                $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                $email = htmlspecialchars($row['email']);
               $status = trim($row['payment_status']); // remove spaces
$status_class = (strtolower($status) === 'paid') ? 'status-success' : 'status-failed';

            ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $full_name; ?></td>
                    <td><?php echo $email; ?></td>
                    <td><?php echo htmlspecialchars($row['bus_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['source'] . " → " . $row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['departure_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['arrival_time']); ?></td>
                    <td><?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                   <td class="<?php echo $status_class; ?>"><?php echo $status; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById("profileDropdown");
    dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
}
window.onclick = function(event) {
    if (!event.target.closest('.profile-container')) {
        document.getElementById("profileDropdown").style.display = "none";
    }
}
</script>
</body>
</html>
