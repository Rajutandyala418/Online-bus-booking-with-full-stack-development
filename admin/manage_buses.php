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

$admin_id = $_SESSION['admin_id'];
$admin_name = (isset($_SESSION['admin_first_name']) && isset($_SESSION['admin_last_name'])) 
    ? htmlspecialchars($_SESSION['admin_first_name']) . ' ' . htmlspecialchars($_SESSION['admin_last_name']) 
    : 'Welcome Admin';

// Session expiry (5 minutes)
if (!isset($_SESSION['session_expiry'])) {
    $_SESSION['session_expiry'] = time() + 300;
}
$remaining_time = $_SESSION['session_expiry'] - time();
if ($remaining_time <= 0) {
    header("Location: logout.php?timeout=1");
    exit();
}

require_once __DIR__ . '/../include/db_connect.php';

// ✅ PHPMailer
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Get admin email
$adminEmail = '';
$stmt = $conn->prepare("SELECT email, first_name, last_name FROM admin WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->bind_result($adminEmail, $firstName, $lastName);
$stmt->fetch();
$stmt->close();
$adminFullName = $firstName . ' ' . $lastName;

// ✅ Email function
function sendAdminEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu'; // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        // Silent fail
    }
}

$error_message = "";

// Handle bus deletion
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Fetch bus before deleting
    $stmt = $conn->prepare("SELECT bus_name, bus_number FROM buses WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($oldName, $oldNumber);
    $stmt->fetch();
    $stmt->close();

    if ($admin_id == 3) {
        $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM buses WHERE id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $delete_id, $admin_id);
    }
    $stmt->execute();
    $stmt->close();

    // ✅ Send email for delete
    $subject = "Bus Deleted Successfully";
    $body = "Dear {$adminFullName},<br><br>
             You have successfully deleted your bus.<br>
             <b>Bus Name:</b> {$oldName}<br>
             <b>Bus Number:</b> {$oldNumber}<br><br>
             Thank you,<br>Varahi Bus Booking";
    sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

    header("Location: manage_buses.php");
    exit();
}

// Add new bus
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bus_name'], $_POST['bus_number'])) {
    $bus_name = trim($_POST['bus_name']);
    $bus_number = trim($_POST['bus_number']);

    if ($bus_name !== "" && $bus_number !== "") {
        $stmt = $conn->prepare("SELECT id FROM buses WHERE bus_number = ?");
        $stmt->bind_param("s", $bus_number);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error_message = "Bus number already exists!";
            $stmt->close();
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO buses (bus_name, bus_number, admin_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $bus_name, $bus_number, $admin_id);
            $stmt->execute();
            $stmt->close();

            // ✅ Send email for add
            $subject = "New Bus Added Successfully";
            $body = "Dear {$adminFullName},<br><br>
                     You have successfully added a new bus.<br>
                     <b>Bus Name:</b> {$bus_name}<br>
                     <b>Bus Number:</b> {$bus_number}<br><br>
                     Thank you,<br>Varahi Bus Booking";
            sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

            header("Location: manage_buses.php");
            exit();
        }
    }
}

// Handle bus update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_bus_id'], $_POST['update_bus_name'], $_POST['update_bus_number'])) {
    $update_id = intval($_POST['update_bus_id']);
    $update_name = trim($_POST['update_bus_name']);
    $update_number = trim($_POST['update_bus_number']);

    // Fetch old details
    $stmt = $conn->prepare("SELECT bus_name, bus_number FROM buses WHERE id = ?");
    $stmt->bind_param("i", $update_id);
    $stmt->execute();
    $stmt->bind_result($oldName, $oldNumber);
    $stmt->fetch();
    $stmt->close();

    if ($update_name !== "" && $update_number !== "") {
        $stmt = $conn->prepare("SELECT id FROM buses WHERE bus_number = ? AND id != ?");
        $stmt->bind_param("si", $update_number, $update_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error_message = "Bus number already exists!";
        } else {
            $stmt->close();

            if ($admin_id == 3) {
                $stmt = $conn->prepare("UPDATE buses SET bus_name = ?, bus_number = ? WHERE id = ?");
                $stmt->bind_param("ssi", $update_name, $update_number, $update_id);
            } else {
                $stmt = $conn->prepare("UPDATE buses SET bus_name = ?, bus_number = ? WHERE id = ? AND admin_id = ?");
                $stmt->bind_param("ssii", $update_name, $update_number, $update_id, $admin_id);
            }
            $stmt->execute();
            $stmt->close();

            // ✅ Send email for update
            $subject = "Bus Details Updated Successfully";
            $body = "Dear {$adminFullName},<br><br>
                     You have successfully updated your bus details.<br><br>
                     <b>Old Details:</b><br>
                     Bus Name: {$oldName}<br>
                     Bus Number: {$oldNumber}<br><br>
                     <b>New Details:</b><br>
                     Bus Name: {$update_name}<br>
                     Bus Number: {$update_number}<br><br>
                     Thank you,<br>Varahi Bus Booking";
            sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

            header("Location: manage_buses.php");
            exit();
        }
    }
}

// Fetch buses
if ($admin_id == 3) {
    $stmt = $conn->prepare("SELECT id, bus_name, bus_number FROM buses");
} else {
    $stmt = $conn->prepare("SELECT id, bus_name, bus_number FROM buses WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
}
$stmt->execute();
$result = $stmt->get_result();
$buses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Buses</title>
    <style>
        html, body { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; }
        .bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .top-nav { position: absolute; top: 20px; right: 30px; display: flex; gap: 15px; align-items: center; }
        .profile-menu { position: relative; display: inline-block; }
        .profile-circle { width: 45px; height: 45px; background: #ffde59; border-radius: 50%; cursor: pointer; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; color: black; font-size: 1.2rem; }
        .dropdown-content { display: none; position: absolute; top: 55px; right: 0; background: rgba(0,0,0,0.8); border-radius: 6px; min-width: 150px; z-index: 10; box-shadow: 0 4px 8px rgba(0,0,0,0.5); }
        .dropdown-content a { display: block; padding: 10px; color: white; text-decoration: none; transition: background 0.2s; }
        .dropdown-content a:hover { background: rgba(255,255,255,0.1); }
        .container {
            margin-top: 120px; width: 90%; max-width: 800px; margin-left: auto; margin-right: auto; background: rgba(0, 0, 0, 0.6); color: white; padding: 30px; border-radius: 10px; }
        h1 { color: #ffde59; text-align: center; }
        form { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        input[type="text"] { padding: 8px; border-radius: 4px; border: none; width: 200px; }
        button { padding: 8px 15px; background: linear-gradient(90deg, #ff512f, #dd2476); color: white; border: none; border-radius: 6px; cursor: pointer; }
        .error-message { color: #ff5050; text-align: center; margin-bottom: 10px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; background: white; color: black; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        th { background: #ffde59; }
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
        .delete-btn { background: red; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="container" style="margin-top:60px;">
    <h2>Add Bus</h2>
    <form method="POST" style="margin-bottom:20px;">
        <label>Bus Name:</label>
        <input type="text" name="bus_name" required 
               style="width:200px; padding:6px; margin-right:10px;" placeholder="BUS NAME">
        <label>Bus Number:</label>
        <input type="text" name="bus_number" required 
               style="width:200px; padding:6px; margin-right:10px;" placeholder="BUS NUMBER">
        <button type="submit" name="add_bus" 
                style="padding:6px 15px; background:blue; color:white; border:none; border-radius:5px;">
            Add Bus
        </button>
    </form>

    <h2>Manage Buses</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Bus Name</th>
                <th>Bus Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($buses as $bus): ?>
                <tr>
                    <td><?php echo $bus['id']; ?></td>
                    <td><?php echo $bus['bus_name']; ?></td>
                    <td><?php echo $bus['bus_number']; ?></td>
                    <td>
                        <!-- Delete -->
                        <a href="manage_buses.php?delete_id=<?php echo $bus['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this bus?');">
                           <button type="button" class="delete-btn">Delete</button>
                        </a>

                        <!-- Update -->
                        <button type="button" class="update-btn" 
                            onclick="openUpdatePopup('<?php echo $bus['id']; ?>',
                                                     '<?php echo htmlspecialchars($bus['bus_name']); ?>',
                                                     '<?php echo htmlspecialchars($bus['bus_number']); ?>')">
                            Update
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 🔽 Update Popup (Dark Theme) -->
    <div id="updatePopup" style="display:none; position:fixed; top:50%; left:50%;
         transform:translate(-50%,-50%); background:#1e1e1e; padding:20px; border-radius:10px;
         z-index:1000; width:320px; box-shadow:0 4px 12px rgba(0,0,0,0.8); color:white;">
        <h3 style="text-align:center; color:#00ffcc;">Update Bus</h3>
        <form method="POST">
            <input type="hidden" name="update_bus_id" id="update_bus_id">
            <label>Bus Name:</label>
            <input type="text" name="update_bus_name" id="update_bus_name" required 
                   style="width:100%; padding:8px; margin-bottom:10px; border:none; border-radius:5px; background:#2c2c2c; color:white;">

            <label>Bus Number:</label>
            <input type="text" name="update_bus_number" id="update_bus_number" required 
                   style="width:100%; padding:8px; margin-bottom:10px; border:none; border-radius:5px; background:#2c2c2c; color:white;">

            <div style="text-align:center; margin-top:10px;">
                <button type="submit" 
                        style="padding:8px 15px; background:#00cc66; color:white; border:none; border-radius:5px; cursor:pointer;">
                    Update
                </button>
                <button type="button" onclick="closeUpdatePopup()" 
                        style="padding:8px 15px; background:#cc0000; color:white; border:none; border-radius:5px; cursor:pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 🔽 JavaScript -->
<script>
function openUpdatePopup(id, name, number) {
    document.getElementById("update_bus_id").value = id;
    document.getElementById("update_bus_name").value = name;
    document.getElementById("update_bus_number").value = number;
    document.getElementById("updatePopup").style.display = "block";
}

function closeUpdatePopup() {
    document.getElementById("updatePopup").style.display = "none";
}
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    profileBtn.addEventListener('click', function (e) {
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        e.stopPropagation();
    });
    document.addEventListener('click', function () { dropdownMenu.style.display = 'none'; });
    function openUpdatePopup(id, name, number) {
    document.getElementById("update_bus_id").value = id;
    document.getElementById("update_bus_name").value = name;
    document.getElementById("update_bus_number").value = number;
    document.getElementById("updatePopup").style.display = "block";
}

function closeUpdatePopup() {
    document.getElementById("updatePopup").style.display = "none";
}

</script>
</body>
</html>
