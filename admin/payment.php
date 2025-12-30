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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {

    // Fetch admin email dynamically
    $admin_email_stmt = $conn->prepare("SELECT email FROM admin WHERE id = ?");
    $admin_email_stmt->bind_param("i", $admin_id);
    $admin_email_stmt->execute();
    $admin_email_result = $admin_email_stmt->get_result();
    $admin_row = $admin_email_result->fetch_assoc();
    $admin_email = $admin_row['email'] ?? 'admin@example.com';

    // === Create temporary CSV file ===
    $file_path = __DIR__ . '/temp_payment.csv';
    $fp = fopen($file_path, 'w');
    fputcsv($fp, ['ID', 'User Name', 'Email', 'Bus Name', 'Route', 'Departure', 'Arrival', 'Amount (₹)', 'Payment Date', 'Status']);

    mysqli_data_seek($result, 0);
    while ($row = $result->fetch_assoc()) {
        $full_name = $row['first_name'] . ' ' . $row['last_name'];
        $route = $row['source'] . ' → ' . $row['destination'];
        fputcsv($fp, [
            $row['id'],
            $full_name,
            $row['email'],
            $row['bus_name'],
            $route,
            $row['departure_time'],
            $row['arrival_time'],
            $row['amount'],
            $row['created_at'],
            $row['payment_status']
        ]);
    }
    fclose($fp);

    // === Send email with CSV attached ===
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

        $mail->Subject = "Payment Records CSV";
        $mail->Body = "Dear Admin,\n\nPlease find attached the payment records CSV.\n\nRegards,\nVarahi Team";
        $mail->addAttachment($file_path);

        $mail->send();
        unlink($file_path); // delete temp file after sending
        echo "<script>alert('✅ Email sent successfully to {$admin_email} (CSV attached)');</script>";
    } catch (Exception $e) {
        echo "<script>alert('❌ Email could not be sent: {$mail->ErrorInfo}');</script>";
    }

    header("Location: payment.php");
    exit();
}

// ------------------- DOWNLOAD CSV -------------------
if (isset($_GET['download_file'])) {
    $filename = "payment_records_" . date("Ymd_His") . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen("php://output", "w");
    fputcsv($output, ['ID', 'User Name', 'Email', 'Bus Name', 'Route', 'Departure', 'Arrival', 'Amount (₹)', 'Payment Date', 'Status']);

    mysqli_data_seek($result, 0); // reset pointer
    while ($row = $result->fetch_assoc()) {
        $full_name = $row['first_name'] . ' ' . $row['last_name'];
        $route = $row['source'] . ' → ' . $row['destination'];
        fputcsv($output, [
            $row['id'],
            $full_name,
            $row['email'],
            $row['bus_name'],
            $route,
            $row['departure_time'],
            $row['arrival_time'],
            $row['amount'],
            $row['created_at'],
            $row['payment_status']
        ]);
    }
    fclose($output);
    exit;
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
/* ==================== RESPONSIVE MODE (MOBILE + TABLET + DESKTOP) ==================== */

@media (max-width: 1024px) {
    .container {
        width: 95%;
        padding: 15px;
    }
    table {
        font-size: 15px;
    }
    #dashboardBtn {
        font-size: 14px;
        padding: 8px 14px;
    }
}

@media (max-width: 768px) {
    body {
        font-size: 14px;
    }

    .container {
        padding: 12px;
        margin-top: 70px;
        width:95%;
        border-radius: 8px;
    }

    /* Filter dropdown full width */
    #status_filter {
        width: 100% !important;
        padding: 12px;
        font-size: 16px !important;
        margin-top: 6px;
    }

    label[for="status_filter"] {
        display: block;
        margin-bottom: 5px;
        font-size: 16px !important;
    }

    /* Download + Send Email buttons stacked */
    .container a,
    .container button {
        width: 100% !important;
        display: block;
        text-align: center;
        margin-bottom: 10px;
        font-size: 16px !important;
        padding: 12px !important;
    }

    /* Table responsive scroll */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 13px;
        border-radius: 8px;
        width:95%;
    }

    th, td {
        padding: 8px 6px;
        font-size: 13px;
    }

    /* Dashboard button smaller */
    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 7px 12px;
        font-size: 12px;
        border-radius: 5px;
    }

    /* Loader shrink */
    #loader img {
        width: 70px;
    }
    #loader {
        font-size: 1.1rem;
    }
}

@media (max-width: 480px) {
    h2 {
        font-size: 18px;
    }

    table {
        font-size: 11px;
        width:95%;
    }

    th, td {
        padding: 6px;
        font-size: 11px;
    }

    #dashboardBtn {
        font-size: 10px;
        padding: 6px 8px;
    }

    #status_filter {
        font-size: 14px !important;
    }

    .total-payment {
        font-size: 16px;
        text-align: center;
    }
}
thead {
    position: sticky;
    top: 0;
    z-index: 99;
}

    </style>
</head>
<body>
<video autoplay muted loop class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>
<div id="loader" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background: rgba(0,0,0,0.7); color:white; z-index:9999; justify-content:center; 
    align-items:center; flex-direction:column; font-size:1.2rem;">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" 
         alt="Loading..." style="width:100px; margin-bottom:15px;">
    Please wait...
</div>



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
<a href="?download_file=1" style="padding:10px 20px; background:#ff512f; color:white; border-radius:5px; text-decoration:none;">Download File</a>
     <form method="post" style="display:inline;" onsubmit="showLoader()">
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

function showLoader() {
    document.getElementById('loader').style.display = 'flex';
}


</script>
</body>
</html>
