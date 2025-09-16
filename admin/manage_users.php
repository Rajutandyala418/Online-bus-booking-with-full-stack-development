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

// ✅ Function to send deletion email
function sendDeletionEmail($toEmail, $toName) {
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
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = "Your Varahi Bus Account Has Been Deleted";
        $mail->Body    = "
            <p>Hello <b>$toName</b>,</p>
            <p>Your <b>Varahi Bus Booking</b> account has been <span style='color:red; font-weight:bold;'>deleted by the admin</span> due to suspicious activity.</p>
            <p><b>Warning:</b> Please avoid such activities in the future.</p>
            <p>If you still wish to use our service, you can <a href='http://localhost/bus_booking/register.php'>create a new account</a> and continue booking buses.</p>
            <br>
            <p>Regards,<br>Varahi Bus Booking Team</p>
            <p><small>Time of Action: ".date("Y-m-d H:i:s")."</small></p>
        ";
        $mail->AltBody = "Hello $toName,\nYour Varahi Bus account has been deleted due to suspicious activity. You can register again if you wish to continue.";

        $mail->send();
    } catch (Exception $e) {
        // Log error if needed
    }
}

// ✅ Delete user (with prepared statement)
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Get user details before deletion (for email)
    $stmtUser = $conn->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmtUser->bind_param("i", $delete_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $userData = $resultUser->fetch_assoc();
    $stmtUser->close();

    if ($userData) {
        $conn->begin_transaction();

        try {
            // Delete from users table
            $stmt1 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt1->bind_param("i", $delete_id);
            $stmt1->execute();
            $stmt1->close();

            // Delete from user_requests table
            $stmt2 = $conn->prepare("DELETE FROM user_requests WHERE user_id = ?");
            $stmt2->bind_param("i", $delete_id);
            $stmt2->execute();
            $stmt2->close();

            // Commit transaction
            $conn->commit();

            // Send email to user
            sendDeletionEmail($userData['email'], $userData['first_name']." ".$userData['last_name']);

            // ✅ Reload page after delete
            echo "<script>
                alert('User deleted successfully and notified via email.');
                window.location.href = 'manage_users.php';
            </script>";
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error deleting user.";
        }
    } else {
        $message = "User not found.";
    }
}


// Filter/search by username
$search = isset($_GET['search']) ? $_GET['search'] : '';
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
        .container { position: relative; top: 120px; margin: auto; width: 90%; max-width: 1500px; background: rgba(0, 0, 0, 0.6); color: white; padding: 30px; border-radius: 10px; overflow-x: auto; }
        h1 { text-align: center; color: #ffde59; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; white-space: nowrap; }
        th { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
        tr:hover { background: rgba(255, 255, 255, 0.1); }
        .btn-delete { background: red; color: white; padding: 5px 10px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; }
        .btn-delete:hover { background: darkred; }
        .message { text-align: center; color: #00ff88; font-weight: bold; margin-top: 10px; }
        .error { text-align: center; color: #ff8080; font-weight: bold; margin-top: 10px; }
        form { text-align: center; margin-bottom: 20px; }
        input[type="text"] { padding: 8px; width: 250px; border-radius: 4px; border: none; }
        input[type="submit"] { padding: 8px 16px; border-radius: 4px; border: none; background: #ff512f; color: white; cursor: pointer; transition: background 0.3s; }
        input[type="submit"]:hover { background: #dd2476; }
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
            z-index: 1001;
        }
        #dashboardBtn:hover { transform: scale(1.05); background: #dd2476; }
    </style>
</head>
<body>
<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="container">
    <h1>Manage Users</h1>

    <form method="get" action="">
        <input type="text" name="search" placeholder="Search by username" value="<?php echo htmlspecialchars($search); ?>">
        <input type="submit" value="Search">
    </form>

    <?php if ($message): ?>
        <p class="<?php echo (strpos($message, 'successfully') !== false) ? 'message' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Action</th>
            </tr>
            <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['phone']); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($user['updated_at']); ?></td>
                    <td>
                        <a class="btn-delete" href="manage_users.php?delete=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php $stmt->close(); ?>
