<<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$current_admin_id = $_SESSION['admin_id'];
$message = '';

// ✅ Include PHPMailer
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Function to send deletion email
function sendAdminDeletionEmail($toEmail, $username) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking System');
        $mail->addAddress($toEmail, $username);
        $mail->isHTML(true);
        $mail->Subject = "Varahi Admin Account Deleted";

        date_default_timezone_set('Asia/Kolkata');
        $time = date("Y-m-d H:i:s");

        $mail->Body = "
            <p>Dear <b>$username</b>,</p>
            <p>Your <b>Varahi Bus Booking</b> admin account has been 
            <span style='color:red; font-weight:bold;'>deleted by the main admin</span> 
            due to suspicious activity.</p>
            <p><b>Warning:</b> Please avoid such activities in the future.</p>
            <p>If you want to register a new admin account, kindly contact the main administrator.</p>
            <br>
            <p>Regards,<br>Varahi Bus Booking Team</p>
            <p><small>Time of Action: $time</small></p>
        ";
        $mail->send();
    } catch (Exception $e) {
        // Optional logging
    }
}

// ✅ Delete admin and related data
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    if ($delete_id !== $current_admin_id) {
        $stmt = $conn->prepare("SELECT username, email FROM admin WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->bind_result($username, $email);
        $stmt->fetch();
        $stmt->close();

        if ($username && $email) {
            $conn->begin_transaction();
            try {
                $tables = ['routes', 'schedules', 'buses', 'payments', 'bookings'];
                foreach ($tables as $table) {
                    $stmt = $conn->prepare("DELETE FROM $table WHERE admin_id = ?");
                    $stmt->bind_param("i", $delete_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                sendAdminDeletionEmail($email, $username);

                echo "<script>
                    alert('Admin and all related data deleted successfully.');
                    window.location.href = 'manage_admins.php';
                </script>";
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error deleting admin and related data.";
            }
        } else {
            $message = "Admin not found.";
        }
    } else {
        $message = "You cannot delete your own account.";
    }
}

// ✅ Fetch admins
$admins = $conn->query("SELECT * FROM admin ORDER BY id ASC");

// ✅ CSV download / email logic
if (isset($_POST['download_csv']) || isset($_POST['email_csv'])) {
    $filename = "admins_" . date("Ymd_His") . ".csv";
    $filepath = __DIR__ . "/../tmp/" . $filename;

    $fp = fopen($filepath, 'w');
    fputcsv($fp, ['ID', 'Username', 'First Name', 'Last Name', 'Email', 'Phone', 'Created At', 'Updated At']);
    $admins_result = $conn->query("SELECT id, username, first_name, last_name, email, phone, created_at, updated_at FROM admin ORDER BY id ASC");
    while ($row = $admins_result->fetch_assoc()) fputcsv($fp, $row);
    fclose($fp);

    // ✅ Download CSV
    if (isset($_POST['download_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filepath);
        unlink($filepath);
        exit;
    }

    // ✅ Send CSV Email (Fixed)
    if (isset($_POST['email_csv'])) {
        // Get logged-in admin’s email
        $stmtAdmin = $conn->prepare("SELECT email, username FROM admin WHERE id = ?");
        $stmtAdmin->bind_param("i", $current_admin_id);
        $stmtAdmin->execute();
        $result = $stmtAdmin->get_result()->fetch_assoc();
        $adminEmail = $result['email'] ?? '';
        $adminUsername = $result['username'] ?? 'Admin';
        $stmtAdmin->close();

        if (!empty($adminEmail)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com';
                $mail->Password   = 'pjhg nwnt haac nsiu';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus Booking System');
                $mail->addAddress($adminEmail, $adminUsername);
                $mail->isHTML(true);
                $mail->Subject = "Admin Details CSV File";
                $mail->Body = "
                    <p>Hello <b>$adminUsername</b>,</p>
                    <p>Attached is the latest list of all registered admins in the system.</p>
                    <p>Time of Export: <b>" . date("Y-m-d H:i:s") . "</b></p>
                    <br><p>Regards,<br>Varahi Bus Booking Team</p>
                ";
                $mail->addAttachment($filepath);
                $mail->send();
                unlink($filepath);

                echo "<script>
                    alert('✅ CSV file sent successfully to your admin email ($adminEmail)!');
                    window.location.href = 'manage_admins.php';
                </script>";
                exit;
            } catch (Exception $e) {
                echo "<script>
                    alert('❌ Failed to send email: " . addslashes($e->getMessage()) . "');
                    window.location.href = 'manage_admins.php';
                </script>";
            }
        } else {
            echo "<script>
                alert('❌ Admin email not found. Please check your profile.');
                window.location.href = 'manage_admins.php';
            </script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins</title>
    <style>
html, body { 
    margin: 0; 
    padding: 0; 
    height: 100%; 
    font-family: 'Poppins', sans-serif; 
    color: white; /* ✅ Make all base text white */
}

.container { 
    position: relative; 
    top: 120px; 
    margin: auto; 
    width: 100%; 
    max-width: 1300px; 
    background: rgba(0, 0, 0, 0.6); 
    color: white; /* ✅ Text inside container white */
    padding: 30px; 
    border-radius: 10px; 
    overflow-x: auto; 
}

table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
    color: white; /* ✅ Ensure table text is white */
}

th, td { 
    padding: 12px; 
    text-align: center; 
    border-bottom: 1px solid #ddd; 
    white-space: nowrap; 
    color: white; /* ✅ Table cell text white */
}

tr:hover { 
    background: rgba(255, 255, 255, 0.1); 
}

.message, .error, .badge {
    color: white !important; /* ✅ All messages stay visible */
}

        html, body { margin: 0; padding: 0; height: 100%; font-family: 'Poppins', sans-serif; }
        .bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .top-nav { position: absolute; top: 20px; right: 30px; display: flex; gap: 20px; }
        .top-nav a { text-decoration: none; color: white; font-weight: 600; background: rgba(0,0,0,0.5); padding: 10px 18px; border-radius: 5px; font-size: 1rem; transition: background 0.3s; }
        .top-nav a:hover { background: rgba(0,0,0,0.8); }
        .container { position: relative; top: 120px; margin: auto; width: 100%; max-width: 1300px; background: rgba(0, 0, 0, 0.6); color: white; padding: 30px; border-radius: 10px; overflow-x: auto; }
        h1 { text-align: center; color: #ffde59; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; white-space: nowrap; }
        th { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
        tr:hover { background: rgba(255, 255, 255, 0.1); }
        .btn-delete { background: red; color: white; padding: 5px 10px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; }
        .btn-delete:hover { background: darkred; }
        .message { text-align: center; color: #00ff88; font-weight: bold; margin-top: 10px; }
        .error { text-align: center; color: #ff8080; font-weight: bold; margin-top: 10px; }
        .badge { background: #ffde59; color: black; padding: 2px 6px; border-radius: 4px; font-size: 0.9rem; }
        #loader { display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
            background: rgba(0,0,0,0.7); color:white; z-index:9999; justify-content:center; 
            align-items:center; flex-direction:column; font-size:1.5rem; }
/* ==================== RESPONSIVE MODE ==================== */

/* Tablets & Medium screens */
@media (max-width: 1024px) {
    .container {
        width: 95%;
        padding: 20px;
        margin-top: 90px;
    }
    table {
        font-size: 15px;
    }
    .top-nav a {
        padding: 8px 14px;
        font-size: 14px;
    }
}

/* Mobile screens */
@media (max-width: 768px) {
    html, body {
        font-size: 14px;
    }

    .container {
        width: 90%;
        padding: 15px;
        margin-top: 90px;
        border-radius: 8px;
    }

    /* CSV buttons stack vertically */
    #csvForm button {
        width: 100%;
        font-size: 15px;
        padding: 12px;
        margin-bottom: 10px;
        display: block;
    }

    /* Table scrollable */
    table {
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 13px;
        border-radius: 6px;
    }

    th, td {
        padding: 8px;
        font-size: 13px;
    }

    /* Delete button not oversized */
    .btn-delete {
        padding: 6px 8px;
        font-size: 13px;
    }

    /* Dashboard / Top buttons shrink */
    .top-nav {
        right: 10px;
        top: 10px;
        gap: 10px;
    }
    .top-nav a {
        padding: 6px 10px;
        font-size: 12px;
    }

    /* Loader responsive */
    #loader img {
        width: 75px;
    }
    #loader {
        font-size: 1rem;
    }
}

/* Extra small devices */
@media (max-width: 480px) {
    h1 {
        font-size: 20px;
    }
    table {
        font-size: 11px;
    }
    th, td {
        padding: 5px;
        font-size: 11px;
    }
    .btn-delete {
        font-size: 11px;
        padding: 4px 6px;
    }
    .top-nav a {
        font-size: 10px;
        padding: 5px 7px;
    }
}

    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" 
         alt="Loading..." style="width:100px; margin-bottom:15px;">
    Please wait... Sending email...
</div>

<div class="top-nav">
    <a href="dashboard.php">Dashboard</a>
</div>

<div class="container">
    <h1>Manage Admins</h1>
    <?php if ($message): ?>
        <p class="<?php echo (strpos($message, 'successfully') !== false) ? 'message' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="post" id="csvForm" style="text-align:center; margin-bottom:20px;">
        <button type="submit" name="download_csv" style="background:#ff512f; color:white; padding:8px 16px; border:none; border-radius:5px; cursor:pointer;">⬇️ Download CSV</button>
        <button type="submit" name="email_csv" id="sendEmailBtn" style="background:#dd2476; color:white; padding:8px 16px; border:none; border-radius:5px; cursor:pointer;">📧 Send CSV to Email</button>
    </form>

    <?php if ($admins && $admins->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Action</th>
            </tr>
            <?php while ($admin = $admins->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $admin['id']; ?></td>
                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                    <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td><?php echo htmlspecialchars($admin['phone']); ?></td>
                    <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($admin['updated_at']); ?></td>
                    <td>
                        <?php if ($admin['id'] !== $current_admin_id): ?>
                            <a class="btn-delete" href="manage_admins.php?delete=<?php echo $admin['id']; ?>" onclick="showLoaderOnDelete(event, this.href);">Delete</a>
                        <?php else: ?>
                            <span class="badge">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No admin accounts found.</p>
    <?php endif; ?>
</div>

<script>
function showLoaderOnDelete(event, url) {
    event.preventDefault();
    if (confirm('Are you sure you want to delete this admin?')) {
        document.getElementById('loader').style.display = 'flex';
        window.location.href = url;
    }
}

// ✅ Show loader when sending email
document.getElementById('sendEmailBtn').addEventListener('click', function() {
    document.getElementById('loader').style.display = 'flex';
});
</script>

</body>
</html>
