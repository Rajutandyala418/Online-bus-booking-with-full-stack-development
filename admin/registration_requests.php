<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../include/db_connect.php');

// PHPMailer
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
// Fetch the logged-in admin email
$admin_query = $conn->prepare("SELECT email FROM admin WHERE id = ?");
$admin_query->bind_param("i", $admin_id);
$admin_query->execute();
$admin_result = $admin_query->get_result();
$admin_row = $admin_result->fetch_assoc();
$admin_email = $admin_row ? $admin_row['email'] : 'varahibusbooking@gmail.com'; // fallback
	
// --- EMAIL FUNCTION ---
function sendRegistrationEmail($toEmail, $toName, $status) {
    $mail = new PHPMailer(true);

    if ($status === 'Approved') {
        $subject = "Registration Approved - Varahi Bus Booking";
        $messageText = "
        Hello {$toName},<br><br>
        🎉 Your registration request has been <b>approved</b> by the admin.<br>
        You can now log in using your credentials.<br><br>
        Thank you,<br><b>Varahi Bus Booking</b>";
    } else {
        $subject = "Registration Rejected - Varahi Bus Booking";
        $messageText = "
        Hello {$toName},<br><br>
        ❌ Sorry, your registration request has been <b>rejected</b> by the admin.<br>
        You can register again with valid credentials.<br><br>
        Thank you,<br><b>Varahi Bus Booking</b>";
    }

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $messageText;
        $mail->AltBody = strip_tags($messageText);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// === HANDLE CSV DOWNLOAD / EMAIL ===
if (isset($_POST['download_csv']) || isset($_POST['email_csv'])) {
    $filename = "registration_requests_" . date("Ymd_His") . ".csv";
    $filepath = __DIR__ . "/../tmp/" . $filename;

    $fp = fopen($filepath, 'w');
    fputcsv($fp, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status']);

    $status_filter = $_POST['status_filter'] ?? 'All';
    if ($status_filter === 'All') {
        $res = $conn->query("SELECT * FROM registration_requests ORDER BY id ASC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE status = ? ORDER BY id ASC");
        $stmt->bind_param("s", $status_filter);
        $stmt->execute();
        $res = $stmt->get_result();
    }

    while ($row = $res->fetch_assoc()) {
        fputcsv($fp, [$row['id'], $row['first_name'], $row['last_name'], $row['email'], $row['phone'], $row['status']]);
    }
    fclose($fp);

    // ---- DOWNLOAD CSV ----
    if (isset($_POST['download_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        readfile($filepath);
        unlink($filepath);
        exit;
    }

    // ---- EMAIL CSV ----
    if (isset($_POST['email_csv'])) {
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
           $mail->addAddress($admin_email);

            $mail->isHTML(true);
            $mail->Subject = "Registration Requests CSV File";
            $mail->Body = "Dear Admin,<br><br>Please find attached the latest registration requests CSV file.<br><br>Regards,<br><b>Varahi Bus Booking</b>";
            $mail->addAttachment($filepath);

            $mail->send();
            unlink($filepath);

            if (isset($_POST['ajax'])) {
                echo "✅ CSV file emailed successfully to $admin_email";
                exit();
            } else {
                echo "<script>alert('✅ CSV file emailed successfully to $admin_email');</script>";
            }
        } catch (Exception $e) {
            if (isset($_POST['ajax'])) {
                echo "❌ Failed to send email: " . $mail->ErrorInfo;
                exit();
            } else {
                echo "<script>alert('❌ Failed to send email: " . $mail->ErrorInfo . "');</script>";
            }
        }
    }
}

// --- HANDLE ACTIONS (Approve / Reject) ---
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $id = intval($_GET['request_id']);
    $action = $_GET['action'];

    $query = $conn->prepare("SELECT * FROM registration_requests WHERE id=?");
    $query->bind_param("i", $id);
    $query->execute();
    $res = $query->get_result();

    if ($row = $res->fetch_assoc()) {
        $toName = $row['first_name'] . ' ' . $row['last_name'];
        $toEmail = $row['email'];
        $password = $row['password'];

        if ($action === 'approve') {
            $insert = $conn->prepare("INSERT INTO admin (first_name, last_name, username, email, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->bind_param("ssssss", $row['first_name'], $row['last_name'], $row['username'], $row['email'], $row['phone'], $password);
            $insert->execute();

            $update = $conn->prepare("UPDATE registration_requests SET status='Approved' WHERE id=?");
            $update->bind_param("i", $id);
            $update->execute();

            sendRegistrationEmail($toEmail, $toName, 'Approved');
        } elseif ($action === 'reject') {
            $update = $conn->prepare("UPDATE registration_requests SET status='Rejected' WHERE id=?");
            $update->bind_param("i", $id);
            $update->execute();

            sendRegistrationEmail($toEmail, $toName, 'Rejected');
        }
    }

    header("Location: registration_requests.php?msg=done");
    exit;
}

// --- FETCH REQUESTS ---
$status_filter = $_GET['status'] ?? 'All';
if ($status_filter === 'All') {
    $result = $conn->query("SELECT * FROM registration_requests ORDER BY id ASC");
} else {
    $stmt = $conn->prepare("SELECT * FROM registration_requests WHERE status = ? ORDER BY id ASC");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registration Requests</title>
<style>
html, body { margin:0; padding:0; font-family:'Poppins',sans-serif; background:transparent; color:white; height:100%; overflow-x:hidden; }
.bg-video { position:fixed; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:-1; }
.container { position: relative; top:100px; margin:auto; width:90%; max-width:1600px; background: rgba(0,0,0,0.6); padding:30px; border-radius:10px; overflow-x:auto; }
h1 { text-align:center; color:#ffde59; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:12px; text-align:center; border-bottom:1px solid #ddd; white-space:nowrap; }
th { background: linear-gradient(90deg,#ff512f,#dd2476); color:white; }
tr:hover { background: rgba(255,255,255,0.1); }
.btn-status { padding:6px 12px; border:none; border-radius:4px; cursor:pointer; color:white; margin:2px; font-weight:bold; }
.btn-approve { background:#5cb85c; }
.btn-reject { background:#d9534f; }
#dashboardBtn { position: fixed; top: 20px; right: 30px; background: #ff512f; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: transform 0.2s, background 0.2s; z-index: 1001; }
#dashboardBtn:hover { transform: scale(1.05); background: #dd2476; }
#loader { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999; flex-direction:column; color:white; font-size:1.2rem; }
#loader img { width:100px; height:100px; margin-bottom:15px; }
.action-buttons { text-align:center; margin-top:15px; }
.action-buttons form { display:inline; }
.action-buttons button { background:#ff512f; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer; margin:0 5px; transition:0.3s; }
.action-buttons button:hover { background:#dd2476; transform:scale(1.05); }
/* ===== MOBILE RESPONSIVE IMPROVEMENTS ===== */
@media(max-width: 768px) {

    .container {
        width: 95%;
        padding: 15px;
        top: 70px;
        border-radius: 6px;
        overflow-x: auto;
    }

    h1 {
        font-size: 22px;
    }

    table {
        font-size: 13px;
        width: 100%;
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }

    th, td {
        padding: 8px;
    }

    /* Status buttons stack vertically */
    td button {
        width: 100%;
        margin: 3px 0;
        font-size: 14px;
        padding: 8px;
    }

    /* Filter dropdown full width */
    select {
        width: 100%;
        margin-top: 10px !important;
    }

    /* CSV buttons stacking */
    .action-buttons form {
        display: block;
        width: 100%;
        margin: 6px 0;
        text-align: center;
    }

    .action-buttons button {
        width: 100%;
        padding: 12px;
    }

    /* Dashboard button mobile adjust */
    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 8px 14px;
        font-size: 14px;
    }
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
    <h1>Registration Requests</h1>

    <form method="GET" style="text-align:center; margin-bottom:15px;">
        <label for="status" style="font-weight:bold; color:#ffde59;">Filter by Status:</label>
        <select name="status" id="status" onchange="this.form.submit()" 
        style="padding:10px 16px; border-radius:6px; border:none; background:#333; color:white; 
               margin-left:10px; font-size:16px; width:220px; cursor:pointer;">
            <option value="All" <?php echo ($status_filter==='All')?'selected':''; ?>>All</option>
            <option value="Pending" <?php echo ($status_filter==='Pending')?'selected':''; ?>>Pending</option>
            <option value="Approved" <?php echo ($status_filter==='Approved')?'selected':''; ?>>Approved</option>
            <option value="Rejected" <?php echo ($status_filter==='Rejected')?'selected':''; ?>>Rejected</option>
        </select>
    </form>

    <div class="action-buttons">
        <form method="POST">
            <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
            <button type="submit" name="download_csv">📥 Download CSV</button>
        </form>

        <form id="emailCsvForm">
            <input type="hidden" name="status_filter" value="<?php echo htmlspecialchars($status_filter); ?>">
            <button type="button" id="emailCsvBtn">✉️ Email CSV</button>
        </form>
    </div>

    <table>
        <tr>
            <th>ID</th><th>First Name</th><th>Last Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Action</th>
        </tr>
        <?php while($req = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $req['id']; ?></td>
            <td><?php echo htmlspecialchars($req['first_name']); ?></td>
            <td><?php echo htmlspecialchars($req['last_name']); ?></td>
            <td><?php echo htmlspecialchars($req['email']); ?></td>
            <td><?php echo htmlspecialchars($req['phone']); ?></td>
            <td><?php echo htmlspecialchars($req['status']); ?></td>
            <td>
                <?php if ($req['status'] === 'Pending'): ?>
                    <button class="btn-status btn-approve" data-id="<?php echo $req['id']; ?>" data-action="approve">Approve</button>
                    <button class="btn-status btn-reject" data-id="<?php echo $req['id']; ?>" data-action="reject">Reject</button>
                <?php else: ?>
                    <span style="color:<?php echo $req['status']=='Approved'?'#5cb85c':'#d9534f'; ?>; font-weight:bold;">
                        <?php echo $req['status']; ?>
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<script>
const loader = document.getElementById('loader');

document.querySelectorAll('.btn-status').forEach(btn => {
    btn.addEventListener('click', function(){
        const id = this.dataset.id;
        const action = this.dataset.action;
        loader.style.display = 'flex';
        fetch(`registration_requests.php?action=${action}&request_id=${id}`)
        .then(r => r.text())
        .then(() => location.reload())
        .catch(() => alert('Something went wrong. Please try again.'))
        .finally(() => loader.style.display = 'none');
    });
});

document.getElementById("emailCsvBtn").addEventListener("click", function () {
    loader.style.display = "flex";
    const formData = new URLSearchParams();
    formData.append("email_csv", "1");
    formData.append("ajax", "1");
    formData.append("status_filter", "<?php echo htmlspecialchars($status_filter); ?>");

    fetch(window.location.href, { method: "POST", body: formData })
    .then(res => res.text())
    .then(txt => {
        loader.style.display = "none";
        if (txt.includes("✅")) alert("✅ CSV emailed successfully!");
        else if (txt.includes("❌")) alert("❌ Failed to send email!");
        else alert("✅ Email sent successfully.");
    })
    .catch(err => {
        loader.style.display = "none";
        alert("⚠️ Error: " + err.message);
    });
});
</script>

</body>
</html>
