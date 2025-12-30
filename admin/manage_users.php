<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

// Only admin ID 3 can access this page
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_id'] != 3) {
    header("Location: dashboard.php");
    exit();
}

// ✅ PHPMailer Include
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$admin_id = $_SESSION['admin_id'];

// ✅ Download CSV
if (isset($_GET['download_csv'])) {
    $filename = "users_" . date("Ymd_His") . ".csv";
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=$filename");

    $output = fopen("php://output", "w");
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Username', 'Email', 'Phone', 'Created At', 'Updated At']);

    $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, created_at, updated_at FROM users ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// ✅ Send CSV via Email
if (isset($_POST['send_email'])) {
    // Fetch admin email
    $stmtAdmin = $conn->prepare("SELECT email FROM admin WHERE id = ?");
    $stmtAdmin->bind_param("i", $admin_id);
    $stmtAdmin->execute();
    $adminEmail = $stmtAdmin->get_result()->fetch_assoc()['email'] ?? 'admin@example.com';
    $stmtAdmin->close();

    // Create temp CSV
    $file_path = __DIR__ . "/temp_users.csv";
    $fp = fopen($file_path, 'w');
    fputcsv($fp, ['ID', 'First Name', 'Last Name', 'Username', 'Email', 'Phone', 'Created At', 'Updated At']);

    $stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, created_at, updated_at FROM users ORDER BY id ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }
    fclose($fp);

    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking System');
        $mail->addAddress($adminEmail, 'Admin');
        $mail->Subject = "User Details CSV Report";
        $mail->Body    = "Dear Admin,\n\nPlease find attached the latest user details CSV report.\n\nRegards,\nVarahi Bus Booking System";
        $mail->addAttachment($file_path);
        $mail->send();

        unlink($file_path);
        echo "<script>
            alert('✅ CSV file sent successfully to {$adminEmail}');
            window.location.href = 'manage_users.php';
        </script>";
        exit;
    } catch (Exception $e) {
        echo "<script>
            alert('❌ Email could not be sent: {$mail->ErrorInfo}');
            window.location.href = 'manage_users.php';
        </script>";
        exit;
    }
}

// ✅ Function to send deletion email
function sendDeletionEmail($toEmail, $toName) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com'; 
        $mail->Password   = 'pjhg nwnt haac nsiu';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking System');
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = "Your Varahi Bus Account Has Been Deleted";
        $mail->Body = "
            <p>Hello <b>$toName</b>,</p>
            <p>Your <b>Varahi Bus Booking</b> account has been deleted due to suspicious activity.</p>
            <p>If you wish to use our service again, please register again.</p>
            <p>Regards,<br>Varahi Team</p>
        ";
        $mail->send();
    } catch (Exception $e) {}
}

// ✅ Delete user logic (unchanged)
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmtUser = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $delete_id);
    $stmtUser->execute();
    $userData = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    if ($userData) {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM users WHERE id = $delete_id");
            $conn->commit();
            sendDeletionEmail($userData['email'], $userData['first_name']." ".$userData['last_name']);
            echo "<script>alert('User deleted and notified via email.'); window.location.href='manage_users.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
}

// ✅ Fetch users for display
$search = $_GET['search'] ?? '';
$search_sql = $search ? "WHERE username LIKE ?" : "";
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, email, phone, created_at, updated_at FROM users $search_sql ORDER BY id ASC");
if ($search) {
    $like = "%$search%";
    $stmt->bind_param("s", $like);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users</title>
<style>
html, body { margin: 0; padding: 0; height: 100%; font-family: 'Poppins', sans-serif; }
.bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
.container { position: relative; top: 120px; margin: auto; width: 90%; max-width: 1500px; background: rgba(0,0,0,0.6); color: white; padding: 30px; border-radius: 10px; overflow-x: auto; }
h1 { text-align: center; color: #ffde59; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; white-space: nowrap; }
th { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
tr:hover { background: rgba(255,255,255,0.1); }
.btn-delete { background: red; color: white; padding: 5px 10px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; }
.btn-delete:hover { background: darkred; }
form { text-align: center; margin-bottom: 20px; }
input[type="text"] { padding: 8px; width: 250px; border-radius: 4px; border: none; }
input[type="submit"], .btn-action {
    padding: 8px 16px; border-radius: 4px; border: none;
    background: #ff512f; color: white; cursor: pointer;
    transition: background 0.3s;
}
input[type="submit"]:hover, .btn-action:hover { background: #dd2476; }
#dashboardBtn { position: fixed; top: 20px; right: 30px; background: #ff512f; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 8px rgba(0,0,0,0.3); transition: transform 0.2s, background 0.2s; z-index: 1001; }
#dashboardBtn:hover { transform: scale(1.05); background: #dd2476; }
#loader {
    display:none; position:fixed; top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.7); color:white; z-index:9999; justify-content:center;
    align-items:center; flex-direction:column; font-size:1.5rem;
}
/* ==================== RESPONSIVE OPTIMIZATION ==================== */

/* Tablet screens */
@media (max-width: 1024px) {
    .container {
        width: 95%;
        padding: 20px;
        margin-top: 80px;
    }
    table {
        font-size: 15px;
    }
    #dashboardBtn {
        font-size: 14px;
        padding: 8px 14px;
    }
}

/* Mobile screens */
@media (max-width: 768px) {
    html, body {
        font-size: 14px;
    }

    .container {
        padding: 15px;
        margin-top: 70px;
        border-radius: 8px;
    }

    /* Search input full width */
    input[type="text"] {
        width: 100%;
        font-size: 15px;
        margin-bottom: 10px;
    }

    input[type="submit"], .btn-action {
        width: 100% !important;
        display: block;
        font-size: 15px;
        padding: 12px;
        margin-bottom: 10px;
    }

    /* Table responsive scroll */
    table {
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 13px;
        border-radius: 8px;
    }

    th, td {
        padding: 9px 6px;
        font-size: 13px;
    }

    /* Delete button width full */
    .btn-delete {
        display: inline-block;
        padding: 8px;
        font-size: 13px;
    }

    /* Smaller dashboard button */
    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 4px;
    }

    /* Loader shrink */
    #loader img {
        width: 70px;
    }
    #loader {
        font-size: 1.1rem;
    }
}

/* Extra small phones */
@media (max-width: 480px) {
    h1 {
        font-size: 20px;
    }

    table {
        font-size: 11px;
    }

    th, td {
        padding: 6px;
        font-size: 11px;
    }

    input[type="text"] {
        font-size: 14px;
    }

    #dashboardBtn {
        font-size: 10px;
        padding: 6px 8px;
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
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" 
         alt="Loading..." style="width:100px; margin-bottom:15px;">
    Sending email, please wait...
</div>

<div class="container">
    <h1>Manage Users</h1>

    <form method="get" action="">
        <input type="text" name="search" placeholder="Search by username" value="<?php echo htmlspecialchars($search); ?>">
        <input type="submit" value="Search">
    </form>

    <div style="text-align:center; margin-bottom:15px;">
        <a href="?download_csv=1" class="btn-action">Download File</a>
        <form method="post" style="display:inline;" onsubmit="showLoader()">
            <button type="submit" name="send_email" class="btn-action">Send Email</button>
        </form>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th><th>First Name</th><th>Last Name</th><th>Username</th><th>Email</th>
                <th>Phone</th><th>Created At</th><th>Updated At</th><th>Action</th>
            </tr>
            <?php while ($user = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $user['id'] ?></td>
                <td><?= htmlspecialchars($user['first_name']) ?></td>
                <td><?= htmlspecialchars($user['last_name']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone']) ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td><?= htmlspecialchars($user['updated_at']) ?></td>
                <td><a class="btn-delete" href="manage_users.php?delete=<?= $user['id'] ?>" onclick="showLoaderOnDelete(event,this.href)">Delete</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</div>

<script>
function showLoader() {
    document.getElementById('loader').style.display = 'flex';
}
function showLoaderOnDelete(event, url) {
    event.preventDefault();
    if (confirm('Are you sure you want to delete this user?')) {
        document.getElementById('loader').style.display = 'flex';
        window.location.href = url;
    }
}
</script>
</body>
</html>
<?php $stmt->close(); ?>
