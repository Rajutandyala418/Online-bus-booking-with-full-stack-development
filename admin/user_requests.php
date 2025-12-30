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

// Fetch logged-in admin email
$admin_query = $conn->prepare("SELECT email FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin_row = $admin_result->fetch_assoc();
$admin_email = $admin_row ? $admin_row['email'] : 'varahibusbooking@gmail.com';

// --- Handle CSV download ---
if (isset($_POST['download_csv']) || isset($_POST['email_csv'])) {
    $filename = "user_requests_" . date("Ymd_His") . ".csv";
    $filepath = __DIR__ . "/../tmp/" . $filename;

    $fp = fopen($filepath, 'w');
    fputcsv($fp, ['ID','User ID','Name','Email','Phone','Source','Destination','Status','Created At']);

    $data = $conn->query("SELECT * FROM user_requests ORDER BY request_id ASC");
    while ($row = $data->fetch_assoc()) {
        fputcsv($fp, [
            $row['request_id'],
            $row['user_id'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['request_source'],
            $row['request_destination'],
            $row['status'],
            $row['created_at']
        ]);
    }
    fclose($fp);

    // --- Send Email ---
    if (isset($_POST['email_csv'])) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'varahibusbooking@gmail.com';
            $mail->Password   = 'pjhg nwnt haac nsiu';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking');
            $mail->addAddress($admin_email);
            $mail->isHTML(true);
            $mail->Subject = "User Requests CSV File";
            $mail->Body    = "Dear Admin,<br><br>Please find attached the latest <b>User Requests</b> CSV file.<br><br>Regards,<br><b>Varahi Bus Booking</b>";
            $mail->addAttachment($filepath);
            $mail->send();
            unlink($filepath);
            echo "<script>alert('📧 CSV file emailed successfully to admin!');window.location.href='user_requests.php';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('❌ Email sending failed!');window.location.href='user_requests.php';</script>";
            exit;
        }
    }

    // --- Direct download ---
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($filepath);
    unlink($filepath);
    exit;
}

// --- Email to user when status changes ---
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
        $mail->Password   = 'pjhg nwnt haac nsiu';
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

// --- Update status ---
if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($_GET['request_id']) && isset($_GET['status'])) {
    $id = intval($_GET['request_id']);
    $status = $_GET['status'];

    $update = $conn->prepare("UPDATE user_requests SET status=? WHERE request_id=?");
    $update->bind_param("si", $status, $id);
    $update->execute();

    $result = $conn->prepare("SELECT first_name, last_name, email, request_source, request_destination FROM user_requests WHERE request_id=?");
    $result->bind_param("i", $id);
    $result->execute();
    $res = $result->get_result();
    if ($row = $res->fetch_assoc()) {
        sendRequestEmail($row['email'], $row['first_name'].' '.$row['last_name'], $row['request_source'], $row['request_destination'], $status);
    }

    header("Location: user_requests.php?msg=success");
    exit;
}

// --- Filters ---
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
.btn-pending { background:#f0ad4e; font-size:20px;}
.btn-approved { background:#5cb85c; font-size:20px;}
.btn-rejected { background:#d9534f; }
.filters { margin-bottom:20px; text-align:center; }
.filters select { padding:8px; border-radius:4px; border:none; margin-right:10px; }
.filters input[type="submit"] { padding:8px 16px; border-radius:4px; border:none; background:#ff512f; color:white; cursor:pointer; }
#dashboardBtn { position: fixed; top: 20px; right: 30px; background: #ff512f; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: transform 0.2s, background 0.2s; z-index: 1001; }
#dashboardBtn:hover { transform: scale(1.05); background: #dd2476; }
#loader {
    display: none;
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
    z-index: 9999;
    flex-direction: column;
    color: white;
    font-size: 1.2rem;
}
#loader img { width:100px; height:100px; margin-bottom:15px; }
@media(max-width: 768px) {

    .container {
        width: 95%;
        padding: 15px;
        top: 60px;
        border-radius: 6px;
        overflow-x: auto;
    }

    table {
        font-size: 14px;
        width: 100%;
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    th, td {
        padding: 8px;
        font-size: 13px;
    }

    /* Status buttons stacking vertically in mobile */
    td button {
        width: 100%;
        margin: 3px 0;
        font-size: 14px;
        padding: 8px;
    }

    .filters select,
    .filters input[type="submit"] {
        width: 100%;
        margin-bottom: 10px;
        font-size: 14px;
        padding: 10px;
    }

    .filters {
        display: block;
        text-align: center;
    }

    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 8px 14px;
        font-size: 14px;
    }
}
table::-webkit-scrollbar {
    height: 6px;
}
table::-webkit-scrollbar-thumb {
    background: #ffde59;
    border-radius: 10px;
}

</style>
</head>
<body>
<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading..." />
    <p>Processing your request...</p>
</div>

<div class="container">
    <h1>User Requests</h1>

    <!-- CSV Buttons -->
    <form method="post" style="text-align:center; margin-bottom:15px;">
        <button type="submit" name="download_csv" class="btn-approved">⬇️ Download CSV</button>
        <button type="submit" name="email_csv" class="btn-pending" onclick="showLoader()">📧 Email CSV</button>
    </form>

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
            <th>Source</th><th>Destination</th><th>Status</th><th>Created At</th><th>Action</th>
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
                <button class="btn-status btn-pending" data-id="<?php echo $req['request_id']; ?>" data-status="Pending">Pending</button>
                <button class="btn-status btn-approved" data-id="<?php echo $req['request_id']; ?>" data-status="Approved">Approved</button>
                <button class="btn-status btn-rejected" data-id="<?php echo $req['request_id']; ?>" data-status="Rejected">Rejected</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<script>
const loader = document.getElementById('loader');
function showLoader() { loader.style.display = 'flex'; }
document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function(){
        const requestId = this.dataset.id;
        const status = this.dataset.status;
        loader.style.display = 'flex';
        fetch(`user_requests.php?action=update&request_id=${requestId}&status=${status}`)
        .then(() => { loader.style.display = 'none'; location.reload(); })
        .catch(() => { loader.style.display = 'none'; alert('Something went wrong.'); });
    });
});
</script>
</body>
</html>
<?php $stmt->close(); ?>
