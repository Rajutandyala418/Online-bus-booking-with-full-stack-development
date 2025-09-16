<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_name = (isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name'])) 
    ? htmlspecialchars($_SESSION['admin_first_name']) . ' ' . htmlspecialchars($_SESSION['admin_last_name']) 
    : 'Admin';

// Email function
function sendRequestEmail($toEmail, $toName, $source, $destination, $status) {
    $mail = new PHPMailer(true);

    $messageText = "Hello {$toName},<br><br>
        Your bus request from <b>{$source}</b> to <b>{$destination}</b> has been <b>{$status}</b>.<br><br>
        If you have any queries, please contact our customer support.<br><br>
        Thank you,<br>Varahi Bus Booking";

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';  // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "Your Bus Request Status";
        $mail->Body    = $messageText;
        $mail->AltBody = strip_tags($messageText);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Handle status update
if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['request_id']) && isset($_GET['status'])) {
    $id = intval($_GET['request_id']);
    $status = $_GET['status']; // Pending / Approved / Rejected

    // Update status in DB
    $update = $conn->prepare("UPDATE user_requests SET status=? WHERE request_id=?");
    $update->bind_param("si", $status, $id);
    $update->execute();

    // Fetch user info for email
    $result = $conn->prepare("SELECT first_name, last_name, email, request_source, request_destination FROM user_requests WHERE request_id=?");
    $result->bind_param("i", $id);
    $result->execute();
    $res = $result->get_result();
    if ($row = $res->fetch_assoc()) {
        $toName = $row['first_name'] . ' ' . $row['last_name'];
        $toEmail = $row['email'];
        $source = $row['request_source'];
        $destination = $row['request_destination'];

        // ✅ send email
        sendRequestEmail($toEmail, $toName, $source, $destination, $status);
    }

    // Redirect to same page
    header("Location: user_requests.php?msg=success");
    exit;
}

// Filters
$filter_source = $_GET['source'] ?? '';
$filter_destination = $_GET['destination'] ?? '';
$filter_status = $_GET['status_filter'] ?? '';

$where = [];
$params = [];
$types = '';

if ($filter_source) { $where[] = 'request_source=?'; $params[] = $filter_source; $types .= 's'; }
if ($filter_destination) { $where[] = 'request_destination=?'; $params[] = $filter_destination; $types .= 's'; }
if ($filter_status) { $where[] = 'status=?'; $params[] = $filter_status; $types .= 's'; }

$sql = "SELECT * FROM user_requests";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY request_id ASC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$sources_res = $conn->query("SELECT DISTINCT request_source FROM user_requests");
$destinations_res = $conn->query("SELECT DISTINCT request_destination FROM user_requests");
$sources = $sources_res->fetch_all(MYSQLI_ASSOC);
$destinations = $destinations_res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Requests</title>
    <style>
    html, body { margin:0; padding:0; font-family:'Poppins',sans-serif; background:transparent; color:white; height:100%; overflow-x:hidden; }
    .bg-video { position:fixed; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:-1; }
    .container { position: relative; top:100px; margin:auto; width:90%; max-width:1600px; background: rgba(0,0,0,0.6); padding:30px; border-radius:10px; overflow-x:auto; }
    h1 { text-align:center; color:#ffde59; }
    table { width:100%; border-collapse:collapse; margin-top:20px; }
    th, td { padding:12px; text-align:center; border-bottom:1px solid #ddd; white-space:nowrap; }
    th { background: linear-gradient(90deg,#ff512f,#dd2476); color:white; }
    tr:hover { background: rgba(255,255,255,0.1); }
    .btn-status { padding:5px 10px; border:none; border-radius:4px; cursor:pointer; color:white; margin:2px; }
    .btn-pending { background:#f0ad4e; }
    .btn-approved { background:#5cb85c; }
    .btn-rejected { background:#d9534f; }
    .filters { margin-bottom:20px; text-align:center; }
    .filters select { padding:8px; border-radius:4px; border:none; margin-right:10px; }
    .filters input[type="submit"] { padding:8px 16px; border-radius:4px; border:none; background:#ff512f; color:white; cursor:pointer; }
    #dashboardBtn { position: fixed; top: 20px; right: 30px; background: #ff512f; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: transform 0.2s, background 0.2s; z-index: 1001; }
    #dashboardBtn:hover { transform: scale(1.05); background: #dd2476; }
    </style>
</head>
<body>
<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="container">
    <h1>User Requests</h1>

    <!-- Filters -->
    <form method="get" class="filters">
        <select name="source">
            <option value="">All Sources</option>
            <?php foreach ($sources as $s) echo "<option value='".htmlspecialchars($s['request_source'])."'".($filter_source==$s['request_source']?' selected':'').">".htmlspecialchars($s['request_source'])."</option>"; ?>
        </select>
        <select name="destination">
            <option value="">All Destinations</option>
            <?php foreach ($destinations as $d) echo "<option value='".htmlspecialchars($d['request_destination'])."'".($filter_destination==$d['request_destination']?' selected':'').">".htmlspecialchars($d['request_destination'])."</option>"; ?>
        </select>
        <select name="status_filter">
            <option value="">All Status</option>
            <option value="Pending" <?php echo $filter_status=='Pending'?'selected':''; ?>>Pending</option>
            <option value="Approved" <?php echo $filter_status=='Approved'?'selected':''; ?>>Approved</option>
            <option value="Rejected" <?php echo $filter_status=='Rejected'?'selected':''; ?>>Rejected</option>
        </select>
        <input type="submit" value="Filter">
    </form>

    <table>
        <tr>
            <th>ID</th><th>User ID</th><th>Name</th><th>Email</th><th>Phone</th>
            <th>Source</th><th>Destination</th>
            <th>Status</th><th>Created At</th><th>Action</th>
        </tr>
        <?php while($req = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $req['request_id']; ?></td>
                <td><?php echo $req['user_id']; ?></td>
                <td><?php echo htmlspecialchars($req['first_name'].' '.$req['last_name']); ?></td>
                <td><?php echo htmlspecialchars($req['email']); ?></td>
                <td><?php echo htmlspecialchars($req['phone']); ?></td>
                <td><?php echo htmlspecialchars($req['request_source']); ?></td>
                <td><?php echo htmlspecialchars($req['request_destination']); ?></td>
                <td><?php echo $req['status']; ?></td>
                <td><?php echo $req['created_at']; ?></td>
                <td>
                    <a href="?action=update&request_id=<?php echo $req['request_id']; ?>&status=Pending" class="btn-status btn-pending">Pending</a>
                    <a href="?action=update&request_id=<?php echo $req['request_id']; ?>&status=Approved" class="btn-status btn-approved">Approved</a>
                    <a href="?action=update&request_id=<?php echo $req['request_id']; ?>&status=Rejected" class="btn-status btn-rejected">Rejected</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>
<?php $stmt->close(); ?>
