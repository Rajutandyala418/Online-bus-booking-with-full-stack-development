<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, post-check=0", false);
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
    $_SESSION['session_expiry'] = time() + 3000;
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

// ---------- AJAX HANDLING ----------
$is_ajax = isset($_POST['ajax']) || isset($_GET['ajax']);

if ($is_ajax) {
    header('Content-Type: application/json');

    try {
        // ----- Add Bus -----
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['bus_name'], $_POST['bus_number'])) {
            $bus_name = trim($_POST['bus_name']);
            $bus_number = trim($_POST['bus_number']);
            if ($bus_name == "" || $bus_number == "") throw new Exception("Fields are required.");

            $stmt = $conn->prepare("SELECT id FROM buses WHERE bus_number = ?");
            $stmt->bind_param("s", $bus_number);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) throw new Exception("Bus number already exists!");
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO buses (bus_name, bus_number, admin_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $bus_name, $bus_number, $admin_id);
            $stmt->execute();
            $stmt->close();

            $subject = "New Bus Added Successfully";
            $body = "Dear {$adminFullName},<br><br>
                     You have successfully added a new bus.<br>
                     <b>Bus Name:</b> {$bus_name}<br>
                     <b>Bus Number:</b> {$bus_number}<br><br>
                     Thank you,<br>Varahi Bus Booking";
            sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

            echo json_encode(['success' => true]);
            exit();
        }

        // ----- Delete Bus -----
        if (isset($_GET['action']) && $_GET['action'] == 'delete_bus' && isset($_GET['id'])) {
            $bus_id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT bus_name, bus_number FROM buses WHERE id=?");
            $stmt->bind_param("i", $bus_id);
            $stmt->execute();
            $stmt->bind_result($oldName, $oldNumber);
            if (!$stmt->fetch()) throw new Exception("Bus not found");
            $stmt->close();

            if ($admin_id == 3) {
                $stmt = $conn->prepare("DELETE FROM buses WHERE id=?");
                $stmt->bind_param("i", $bus_id);
            } else {
                $stmt = $conn->prepare("DELETE FROM buses WHERE id=? AND admin_id=?");
                $stmt->bind_param("ii", $bus_id, $admin_id);
            }
            $stmt->execute();
            $stmt->close();

            $subject = "Bus Deleted Successfully";
            $body = "Dear {$adminFullName},<br><br>
                     You have successfully deleted your bus.<br>
                     <b>Bus Name:</b> {$oldName}<br>
                     <b>Bus Number:</b> {$oldNumber}<br><br>
                     Thank you,<br>Varahi Bus Booking";
            sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

            echo json_encode(['success' => true]);
            exit();
        }

        // ----- Update Bus -----
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_bus_id'], $_POST['update_bus_name'], $_POST['update_bus_number'])) {
            $update_id = intval($_POST['update_bus_id']);
            $update_name = trim($_POST['update_bus_name']);
            $update_number = trim($_POST['update_bus_number']);

            $stmt = $conn->prepare("SELECT bus_name, bus_number FROM buses WHERE id=?");
            $stmt->bind_param("i", $update_id);
            $stmt->execute();
            $stmt->bind_result($oldName, $oldNumber);
            if (!$stmt->fetch()) throw new Exception("Bus not found");
            $stmt->close();

            $stmt = $conn->prepare("SELECT id FROM buses WHERE bus_number=? AND id!=?");
            $stmt->bind_param("si", $update_number, $update_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) throw new Exception("Bus number already exists!");
            $stmt->close();

            if ($admin_id == 3) {
                $stmt = $conn->prepare("UPDATE buses SET bus_name=?, bus_number=? WHERE id=?");
                $stmt->bind_param("ssi", $update_name, $update_number, $update_id);
            } else {
                $stmt = $conn->prepare("UPDATE buses SET bus_name=?, bus_number=? WHERE id=? AND admin_id=?");
                $stmt->bind_param("ssii", $update_name, $update_number, $update_id, $admin_id);
            }
            $stmt->execute();
            $stmt->close();

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

            echo json_encode(['success' => true]);
            exit();
        }
// ----- DOWNLOAD CSV -----
if (isset($_GET['action']) && $_GET['action'] == 'download_csv') {
    $filename = "buses_list.csv";
    $fp = fopen('php://output', 'w');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    // Fetch buses for this admin
    if ($admin_id == 3) {
        $query = "SELECT id, bus_name, bus_number FROM buses";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT id, bus_name, bus_number FROM buses WHERE admin_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $admin_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate dynamic column widths
    $buses = $result->fetch_all(MYSQLI_ASSOC);
    $headers = ['ID', 'Bus Name', 'Bus Number'];
    fputcsv($fp, $headers);

foreach ($buses as $bus) {
    fputcsv($fp, [
        str_pad($bus['id'], 5),
        str_pad($bus['bus_name'], 200),
        str_pad($bus['bus_number'], 100)
    ]);
}


    fclose($fp);
    exit;
}

// ----- SEND EMAIL WITH CSV -----
if (isset($_GET['action']) && $_GET['action'] == 'send_csv_email') {
    $csvFile = __DIR__ . "/buses_list_temp.csv";
    $fp = fopen($csvFile, 'w');

    // Fetch buses for this admin
    if ($admin_id == 3) {
        $query = "SELECT id, bus_name, bus_number FROM buses";
        $stmt = $conn->prepare($query);
    } else {
        $query = "SELECT id, bus_name, bus_number FROM buses WHERE admin_id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $admin_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $headers = ['ID', 'Bus Name', 'Bus Number'];
    fputcsv($fp, $headers);
while ($row = $result->fetch_assoc()) {
    fputcsv($fp, [
        str_pad($row['id'], 5),
        str_pad($row['bus_name'], 200),
        str_pad($row['bus_number'], 100)
    ]);
}

    fclose($fp);

    $subject = "Your Bus List from Varahi Bus Booking";
    $body = "Dear {$adminFullName},<br><br>
             Your buses are listed in the Varahi Bus Booking Application.<br>
             Please find the attached CSV file containing your bus details.<br><br>
             Thank you,<br>Varahi Bus Booking";

    // Send email with attachment
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
        $mail->addAddress($adminEmail, $adminFullName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->addAttachment($csvFile, 'buses_list.csv');
        $mail->send();

        unlink($csvFile); // cleanup

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Email failed: ' . $e->getMessage()]);
        exit;
    }
}

        throw new Exception("Invalid action");

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// ---------- FETCH BUSES ----------
if ($admin_id == 3) {
    $stmt = $conn->prepare("SELECT id, bus_name, bus_number FROM buses");
} else {
    $stmt = $conn->prepare("SELECT id, bus_name, bus_number FROM buses WHERE admin_id=?");
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
    /* Existing styles unchanged */
    html, body { margin:0; padding:0; font-family:'Poppins',sans-serif; }
    .bg-video { position:fixed; top:0; left:0; width:100%; height:100%; object-fit:cover; z-index:-1; }
    .top-nav { position:absolute; top:20px; right:30px; display:flex; gap:15px; align-items:center; }
    .profile-menu { position:relative; display:inline-block; }
    .profile-circle { width:45px; height:45px; background:#ffde59; border-radius:50%; cursor:pointer; border:2px solid #fff; display:flex; align-items:center; justify-content:center; font-weight:bold; color:black; font-size:1.2rem; }
    .dropdown-content { display:none; position:absolute; top:55px; right:0; background:rgba(0,0,0,0.8); border-radius:6px; min-width:150px; z-index:10; box-shadow:0 4px 8px rgba(0,0,0,0.5); }
    .dropdown-content a { display:block; padding:10px; color:white; text-decoration:none; transition:background 0.2s; }
    .dropdown-content a:hover { background: rgba(255,255,255,0.1); }
    .container { margin-top:120px; width:90%; max-width:800px; margin-left:auto; margin-right:auto; background: rgba(0,0,0,0.6); color:white; padding:30px; border-radius:10px; }
    h1 { color:#ffde59; text-align:center; }
    form { display:flex; justify-content:center; align-items:center; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    input[type="text"] { padding:8px; border-radius:4px; border:none; width:200px; }
    button { padding:8px 15px; background: linear-gradient(90deg,#ff512f,#dd2476); color:white; border:none; border-radius:6px; cursor:pointer; }
    .error-message { color:#ff5050; text-align:center; margin-bottom:10px; font-weight:bold; }
    table { width:100%; border-collapse:collapse; background:white; color:black; }
    th, td { padding:10px; border:1px solid #ddd; text-align:center; }
    th { background:#ffde59; }
    #dashboardBtn { position:fixed; top:20px; right:30px; background:#ff512f; color:white; padding:10px 20px; border-radius:6px; font-weight:bold; cursor:pointer; box-shadow:0 4px 8px rgba(0,0,0,0.3); transition: transform 0.2s, background 0.2s; z-index:1001; }
    #dashboardBtn:hover { transform:scale(1.05); background:#dd2476; }
    .delete-btn { background:red; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; }
/* ================= MOBILE RESPONSIVENESS ================== */
@media(max-width: 768px) {

    .container {
        width: 94%;
        padding: 15px;
        margin-top: 80px;
        border-radius: 8px;
        overflow-x: auto;
    }

    h1, h2 {
        font-size: 22px;
        text-align: center;
    }

    /* Table scroll for small screens */
    table {
        display: block;
        width: 100%;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 14px;
    }

    th, td {
        padding: 7px;
        font-size: 13px;
    }

    /* Add/Update Form inputs full width */
    form input[type="text"] {
        width: 100%;
        margin-bottom: 10px;
        font-size: 14px;
        padding: 10px;
    }

    form button {
        width: 100%;
        margin-top: 5px;
        padding: 12px;
        font-size: 14px;
    }

    /* Dashboard button adjust */
    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 8px 14px;
        font-size: 14px;
    }

    /* CSV buttons stack */
    #downloadCSV,
    #emailCSV {
        width: 100%;
        padding: 12px;
        margin-bottom: 10px;
        font-size: 15px;
    }

    /* Popup full width adjust */
    #updatePopup {
        width: 90%;
        left: 50%;
        transform: translateX(-50%);
        max-width: 350px;
    }

    #updatePopup input {
        width: 100%;
        margin-bottom: 8px;
        padding: 10px;
        font-size: 14px;
    }

    #updatePopup button {
        width: 48%;
        padding: 10px;
        font-size: 14px;
    }

    /* Delete & Update buttons responsive */
    .delete-btn,
    .update-btn {
        width: 100%;
        margin-bottom: 5px;
        padding: 10px;
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

<!-- Loader Overlay -->
<div id="loader" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background: rgba(0,0,0,0.7); color:white; z-index:9999; justify-content:center; align-items:center; 
    flex-direction:column; font-size:1.2rem;">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" 
         alt="Loading..." style="width:100px; margin-bottom:15px;">
    Please wait...
</div>


<div class="container" style="margin-top:60px;">
    <h2>Add Bus</h2>
    <form method="POST" id="addBusForm" style="margin-bottom:20px;">
        <label>Bus Name:</label>
        <input type="text" name="bus_name" required placeholder="BUS NAME">
        <label>Bus Number:</label>
        <input type="text" name="bus_number" required placeholder="BUS NUMBER">
        <button type="submit" name="add_bus">Add Bus</button>
    </form>

    <h2>Manage Buses</h2>
<div style="margin-bottom: 25px; text-align: left;">
    <button id="downloadCSV" style="background:green; font-size:20px;">Download CSV</button>
    <button id="emailCSV" style="background:blue; font-size:20px;">Send Email</button>
</div>

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
                        <button type="button" class="delete-btn" data-id="<?php echo $bus['id']; ?>">Delete</button>
                        <button type="button" class="update-btn" 
                            onclick="openUpdatePopup('<?php echo $bus['id']; ?>','<?php echo htmlspecialchars($bus['bus_name']); ?>','<?php echo htmlspecialchars($bus['bus_number']); ?>')">Update</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Update Popup -->
    <div id="updatePopup" style="display:none; position:fixed; top:50%; left:50%;
         transform:translate(-50%,-50%); background:#1e1e1e; padding:20px; border-radius:10px;
         z-index:1000; width:320px; box-shadow:0 4px 12px rgba(0,0,0,0.8); color:white;">
        <h3 style="text-align:center; color:#00ffcc;">Update Bus</h3>
        <form method="POST">
            <input type="hidden" name="update_bus_id" id="update_bus_id">
            <label>Bus Name:</label>
            <input type="text" name="update_bus_name" id="update_bus_name" required>
            <label>Bus Number:</label>
            <input type="text" name="update_bus_number" id="update_bus_number" required>
            <div style="text-align:center; margin-top:10px;">
                <button type="submit">Update</button>
                <button type="button" onclick="closeUpdatePopup()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const loader = document.getElementById('loader');

// Function to show loader with a message
function showLoader(message) {
    loader.querySelector('img').style.display = 'block';
    loader.querySelector('img').alt = message;
    loader.innerHTML = `<img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" 
                        alt="Loading..." style="width:100px; margin-bottom:15px;">
                        ${message}`;
    loader.style.display = 'flex';
}

// Function to hide loader
function hideLoader() {
    loader.style.display = 'none';
}

// OPEN/CLOSE UPDATE POPUP
function openUpdatePopup(id, name, number) {
    document.getElementById('update_bus_id').value = id;
    document.getElementById('update_bus_name').value = name;
    document.getElementById('update_bus_number').value = number;
    document.getElementById('updatePopup').style.display = 'block';
}
function closeUpdatePopup() {
    document.getElementById('updatePopup').style.display = 'none';
}

// ---------- AJAX HANDLING ----------

// Add Bus
document.getElementById('addBusForm').addEventListener('submit', function(e){
    e.preventDefault();
    showLoader("Adding bus...");
    const formData = new FormData(this);
    formData.append('ajax','1');
    fetch('manage_buses.php', {method:'POST', body:formData})
    .then(res => res.json())
    .then(data => {
        hideLoader();
        if(data.success){ location.reload(); } 
        else { alert(data.message); }
    })
    .catch(err=>{ hideLoader(); alert('Something went wrong'); });
});

// Delete Bus
document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        if(!confirm('Are you sure you want to delete this bus?')) return;
        const id = this.dataset.id;
        showLoader("Deleting bus...");
        fetch('manage_buses.php?action=delete_bus&id='+id+'&ajax=1')
        .then(res=>res.json())
        .then(data=>{
            hideLoader();
            if(data.success){ location.reload(); } 
            else { alert(data.message); }
        })
        .catch(err=>{ hideLoader(); alert('Something went wrong'); });
    });
});

// Update Bus
document.querySelector('#updatePopup form').addEventListener('submit', function(e){
    e.preventDefault();
    showLoader("Updating bus...");
    const formData = new FormData(this);
    formData.append('ajax','1');
    fetch('manage_buses.php', {method:'POST', body:formData})
    .then(res=>res.json())
    .then(data=>{
        hideLoader();
        if(data.success){ location.reload(); } 
        else { alert(data.message); }
    })
    .catch(err=>{ hideLoader(); alert('Something went wrong'); });
});
document.getElementById('downloadCSV').addEventListener('click', function() {
    showLoader("Preparing CSV...");
    fetch('manage_buses.php?action=download_csv&ajax=1')
    .then(response => response.blob())
    .then(blob => {
        hideLoader();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'buses_list.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
    })
    .catch(err => {
        hideLoader();
        alert('Error while downloading file.');
    });
});

// Send Email CSV
document.getElementById('emailCSV').addEventListener('click', function() {
    if(!confirm("Send your bus list CSV to your registered email?")) return;
    showLoader("Sending CSV to your email...");
    fetch('manage_buses.php?action=send_csv_email&ajax=1')
    .then(res => res.json())
    .then(data => {
        hideLoader();
        if(data.success) alert("Email sent successfully!");
        else alert(data.message);
    })
    .catch(err => {
        hideLoader();
        alert('Something went wrong');
    });
});
</script>

</body>
</html>
