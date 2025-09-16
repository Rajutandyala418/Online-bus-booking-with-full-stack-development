<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../include/db_connect.php'); // Updated include path
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE admin SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $admin_id);

    if ($stmt->execute()) {
        $stmt->close();
  date_default_timezone_set('Asia/Kolkata');
        // Get current system time
        $time_now = date("Y-m-d H:i:s");

        if (!empty($email)) {
            // Send email notification
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'varahibusbooking@gmail.com'; 
                $mail->Password   = 'pjhg nwnt haac nsiu'; // app password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
                $mail->addAddress($email, $first_name . ' ' . $last_name);

                $mail->isHTML(true);
                $mail->Subject = "Profile Updated Successfully";
                $mail->Body    = "
                    <h3>Hello <b>{$first_name} {$last_name}</b>,</h3>
                    <p>Your profile has been updated successfully with the following details:</p>
                    <ul>
                        <li><b>First Name:</b> {$first_name}</li>
                        <li><b>Last Name:</b> {$last_name}</li>
                        <li><b>Email:</b> {$email}</li>
                        <li><b>Phone:</b> {$phone}</li>
                        <li><b>Updated On:</b> {$time_now}</li>
                    </ul>
                    <p>If this was not you, please contact support immediately.</p>
                    <br><p>Regards,<br>Bus Booking System</p>";

                $mail->AltBody = "Your profile has been updated successfully on {$time_now}.";

                $mail->send();

                echo "<script>alert('Profile updated successfully! A confirmation email has been sent.'); 
                      window.location.href='admin_details.php';</script>";
                exit;
            } catch (Exception $e) {
                echo "<script>alert('Profile updated, but email could not be sent.'); 
                      window.location.href='admin_details.php';</script>";
                exit;
            }
        } else {
            echo "<script>alert('Profile updated successfully, but no email found.'); 
                  window.location.href='admin_details.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Update failed!');</script>";
        $stmt->close();
    }
}

// Fetch logged-in admin details
$stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, phone FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile Details</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            color: white;
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover; z-index: -1;
        }
        .container {
            margin: 50px auto;
            width: 90%;
            max-width: 1000px;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
        }
        h1 {
            text-align: center;
            color: #ffde59;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255, 255, 255, 0.05);
        }
        th, td {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        th {
            background: rgba(0, 0, 0, 0.5);
            color: #ffde59;
        }
        input[type="text"], input[type="email"] {
            width: 95%;
            padding: 6px;
            border-radius: 5px;
            border: none;
            font-size: 0.95rem;
        }
        button {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .back-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: rgba(0, 0, 0, 0.6);
            color: #00f7ff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }
        .back-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<a href="dashboard.php" class="back-btn">Back to Dashboard</a>

<div class="container">
    <h1>Your Profile Details</h1>
    <form method="post">
        <table>
            <tr>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <tr>
                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                <td><input type="text" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>"></td>
                <td><input type="text" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>"></td>
                <td><input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>"></td>
                <td><input type="text" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>"></td>
                <td><button type="submit">Update</button></td>
            </tr>
        </table>
    </form>
</div>
</body>
</html>
