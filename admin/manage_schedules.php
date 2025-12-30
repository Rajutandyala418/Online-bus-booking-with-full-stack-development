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

// PHPMailer
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$admin_id = $_SESSION['admin_id'];

// Fetch admin details
$admin_stmt = $conn->prepare("SELECT first_name, last_name, email FROM admin WHERE id = ?");
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_stmt->bind_result($admin_first, $admin_last, $admin_email);
$admin_stmt->fetch();
$admin_stmt->close();

// Fetch buses
$buses_stmt = $conn->prepare("SELECT id, bus_name FROM buses WHERE admin_id = ?");
$buses_stmt->bind_param("i", $admin_id);
$buses_stmt->execute();
$buses_result = $buses_stmt->get_result();
$all_buses = $buses_result->fetch_all(MYSQLI_ASSOC);
$buses_stmt->close();

// Fetch routes
$routes_stmt = $conn->prepare("SELECT id, source, destination FROM routes WHERE admin_id = ?");
$routes_stmt->bind_param("i", $admin_id);
$routes_stmt->execute();
$routes_result = $routes_stmt->get_result();
$all_routes = $routes_result->fetch_all(MYSQLI_ASSOC);
$routes_stmt->close();

if ($admin_id == 3) { // Super Admin sees all schedules
    $schedules_stmt = $conn->prepare("
        SELECT s.id, s.bus_id, s.route_id, s.travel_date, s.departure_time, s.arrival_time, b.bus_name, r.source, r.destination 
        FROM schedules s 
        JOIN buses b ON s.bus_id=b.id 
        JOIN routes r ON s.route_id=r.id 
        ORDER BY s.travel_date, s.departure_time
    ");
    $schedules_stmt->execute();
} else { // Other admins see only their schedules
    $schedules_stmt = $conn->prepare("
        SELECT s.id, s.bus_id, s.route_id, s.travel_date, s.departure_time, s.arrival_time, b.bus_name, r.source, r.destination 
        FROM schedules s 
        JOIN buses b ON s.bus_id=b.id 
        JOIN routes r ON s.route_id=r.id 
        WHERE s.admin_id=? 
        ORDER BY s.travel_date, s.departure_time
    ");
    $schedules_stmt->bind_param("i", $admin_id);
    $schedules_stmt->execute();
}
$schedules_result = $schedules_stmt->get_result();


// Email function
function sendScheduleEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu'; // App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
    } catch (Exception $e) {
        // Log error if needed
    }
}

// Delete single schedule
if (isset($_GET['delete_id'])) { 
    $delete_id = intval($_GET['delete_id']); 
    $del_stmt = $conn->prepare("SELECT travel_date FROM schedules WHERE id=? AND admin_id=?");
    $del_stmt->bind_param("ii",$delete_id,$admin_id);
    $del_stmt->execute();
    $del_stmt->bind_result($delete_date);
    $del_stmt->fetch();
    $del_stmt->close();

    $del_stmt2 = $conn->prepare("DELETE FROM schedules WHERE id = ? AND admin_id = ?"); 
    $del_stmt2->bind_param("ii", $delete_id, $admin_id); 
    $del_stmt2->execute(); 

    sendScheduleEmail($admin_email, "$admin_first $admin_last", "Schedule Deleted", "<p>Hello <b>$admin_first</b>,</p><p>Schedule <b>ID $delete_id</b> on <b>$delete_date</b> was deleted on ".date("Y-m-d H:i:s").".</p>");

    echo "<script>showLoader();</script>";
    header("refresh:2;url=manage_schedules.php");
    exit(); 
}

// Delete all schedules
if (isset($_GET['delete_all'])) {
    $start = date("Y-m-d");
    $endDate = date("Y-m-d", strtotime("+30 days"));
    if ($admin_id == 3) { // Super Admin
        $stmt = $conn->prepare("DELETE FROM schedules");
        $stmt->execute();
        $msg = "All schedules deleted by Super Admin from $start to $endDate.";
    } else {
        $del_all_stmt = $conn->prepare("DELETE FROM schedules WHERE admin_id = ?");
        $del_all_stmt->bind_param("i", $admin_id);
        $del_all_stmt->execute();
        $msg = "All your schedules were deleted from $start to $endDate.";
    }

    sendScheduleEmail($admin_email, "$admin_first $admin_last", "Schedules Deleted", "<p>Hello <b>$admin_first</b>,</p><p>$msg (".date("Y-m-d H:i:s").").</p>");
    echo "<script>showLoader();</script>";
    header("refresh:2;url=manage_schedules.php");
    exit();
}

// Add schedule
if (isset($_POST['add_schedule'])) { 
    $bus_id = $_POST['bus_id']; 
    $route_id = $_POST['route_id']; 
    $departure_time = $_POST['departure_time']; 
    $arrival_time = $_POST['arrival_time']; 
    $travel_type = $_POST['travel_type']; 

    if ($travel_type === "by_date") {
        $travel_date = $_POST['travel_date']; 
        $stmt = $conn->prepare("INSERT INTO schedules (bus_id, route_id, travel_date, departure_time, arrival_time, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssi", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $admin_id);
        $stmt->execute();
        $mailBody = "<p>Hello <b>$admin_first</b>,</p><p>You added a new schedule (Bus ID: $bus_id, Route ID: $route_id, Date: $travel_date, Departure: $departure_time, Arrival: $arrival_time) on ".date("Y-m-d H:i:s").".</p>";
    } else if ($travel_type === "everyday") {
        $start_date = $_POST['start_date']; 
        $date = new DateTime($start_date);
        $dates = [];
        for ($i = 0; $i < 30; $i++) {
            $travel_date = $date->format("Y-m-d");
            $stmt = $conn->prepare("INSERT INTO schedules (bus_id, route_id, travel_date, departure_time, arrival_time, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssi", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $admin_id);
            $stmt->execute();
            $dates[] = $travel_date;
            $date->modify('+1 day');
        }
        $mailBody = "<p>Hello <b>$admin_first</b>,</p><p>You added a new schedule (Bus ID: $bus_id, Route ID: $route_id, Departure: $departure_time, Arrival: $arrival_time) from <b>".$dates[0]."</b> to <b>".end($dates)."</b> on ".date("Y-m-d H:i:s").".</p>";
    }

    sendScheduleEmail($admin_email, "$admin_first $admin_last", "New Schedule Added", $mailBody);
    echo "<script>showLoader();</script>";
    header("refresh:2;url=manage_schedules.php"); 
    exit(); 
}

// Update schedule
if (isset($_POST['update_schedule'])) {
    $update_id = $_POST['update_schedule_id'];
    $bus_id = $_POST['update_bus_id'];
    $route_id = $_POST['update_route_id'];
    $travel_date = $_POST['update_travel_date'];
    $departure_time = $_POST['update_departure_time'];
    $arrival_time = $_POST['update_arrival_time'];

    $stmt = $conn->prepare("UPDATE schedules SET bus_id=?, route_id=?, travel_date=?, departure_time=?, arrival_time=? WHERE id=? AND admin_id=?");
    $stmt->bind_param("iisssii", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $update_id, $admin_id);
    $stmt->execute();

    sendScheduleEmail($admin_email, "$admin_first $admin_last", "Schedule Updated", "<p>Hello <b>$admin_first</b>,</p><p>Schedule <b>ID $update_id</b> updated to Date: $travel_date, Departure: $departure_time, Arrival: $arrival_time on ".date("Y-m-d H:i:s").".</p>");
    echo "<script>showLoader();</script>";
    header("refresh:2;url=manage_schedules.php"); 
    exit();
}
// =================== CSV DOWNLOAD AND EMAIL ===================
if (isset($_POST['download_csv']) || isset($_POST['email_csv'])) {

    $filename = "schedules_" . date("Ymd_His") . ".csv";
    $filepath = __DIR__ . "/../tmp/" . $filename;

    $fp = fopen($filepath, 'w');
    fputcsv($fp, ['ID', 'Bus', 'Route', 'Travel Date', 'Departure Time', 'Arrival Time']);

    if ($admin_id == 3) {
        $csv_stmt = $conn->prepare("
            SELECT s.id, b.bus_name, CONCAT(r.source, ' to ', r.destination) AS route_name,
                   s.travel_date, s.departure_time, s.arrival_time
            FROM schedules s
            JOIN buses b ON s.bus_id = b.id
            JOIN routes r ON s.route_id = r.id
            ORDER BY s.travel_date, s.departure_time
        ");
    } else {
        $csv_stmt = $conn->prepare("
            SELECT s.id, b.bus_name, CONCAT(r.source, ' to ', r.destination) AS route_name,
                   s.travel_date, s.departure_time, s.arrival_time
            FROM schedules s
            JOIN buses b ON s.bus_id = b.id
            JOIN routes r ON s.route_id = r.id
            WHERE s.admin_id = ?
            ORDER BY s.travel_date, s.departure_time
        ");
        $csv_stmt->bind_param("i", $admin_id);
    }

    $csv_stmt->execute();
    $csv_result = $csv_stmt->get_result();
    while ($row = $csv_result->fetch_assoc()) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    $csv_stmt->close();

    if (isset($_POST['download_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filepath);
        unlink($filepath);
        exit();
    }
if (isset($_POST['email_csv'])) {
    $is_ajax = isset($_POST['ajax']);



        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'varahibusbooking@gmail.com';
            $mail->Password   = 'pjhg nwnt haac nsiu';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
            $mail->addAddress($admin_email, "$admin_first $admin_last");

            $mail->Subject = 'Schedules CSV Report';
            $mail->Body    = "<p>Hello <b>$admin_first</b>,</p><p>Attached is your current schedules report (".date('Y-m-d H:i:s').").</p>";
            $mail->addAttachment($filepath);

            $mail->send();
            unlink($filepath);
if ($is_ajax) {
    echo "✅ CSV file emailed successfully to $admin_email";
    exit();
} else {
    echo "<script>alert('✅ CSV file emailed successfully to $admin_email');</script>";
}

        } catch (Exception $e) {
            echo "<script>alert('❌ Failed to send email: ".$mail->ErrorInfo."');</script>";
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Schedules</title>
<link rel="stylesheet" href="../css/styles.css">
<style>
body { margin:0; padding:0; color:white; font-family:Arial,sans-serif; }
video.bg-video { position:fixed; right:0; bottom:0; min-width:100%; min-height:100%; z-index:-1; filter:brightness(0.4); pointer-events:none; }
.header { display:flex; justify-content:flex-end; padding:10px 20px; background:rgba(0,0,0,0.7); align-items:center; position:relative; }
.container { padding:20px; margin-top:50px; }
form { display:flex; flex-wrap:wrap; gap:20px; background:rgba(0,0,0,0.6); padding:20px; border-radius:10px; }
form label { flex:1 1 120px; align-self:center; }
form input, form select { flex:1 1 200px; padding:10px; border-radius:5px; border:none; }
form button { padding:12px 25px; border-radius:25px; border:none; cursor:pointer; color:white; font-weight:bold; transition:0.3s; }
table { width:100%; border-collapse:collapse; margin-top:20px; background:rgba(0,0,0,0.6); }
table, th, td { border:1px solid white; padding:8px; text-align:center; }
th { background:rgba(255,255,255,0.2); }
.btn-delete { color:red; text-decoration:none; } .btn-delete:hover { text-decoration:underline; }
#updatePopup { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:rgba(30,30,30,0.95); padding:30px 40px; border-radius:15px; z-index:1000; width:500px; max-width:90%; color:white; box-shadow:0 5px 25px rgba(0,0,0,0.5); overflow-y:auto; max-height:90vh; }
#updatePopup h3 { text-align:center; margin-bottom:20px; color:#00ffcc; }
#updatePopup input, #updatePopup select { width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:none; background:#222; color:white; }
#updatePopup button { padding:10px 25px; border-radius:5px; font-weight:bold; cursor:pointer; }
#updatePopup button[type="submit"] { background:linear-gradient(135deg,#28a745,#00c6ff); color:white; }
#updatePopup button[type="button"] { background:linear-gradient(135deg,#ff7f50,#ff4500); color:white; }
#dashboardBtn { position:fixed; top:20px; right:30px; background:#ff512f; color:white; padding:10px 20px; border-radius:6px; font-weight:bold; cursor:pointer; box-shadow:0 4px 8px rgba(0,0,0,0.3); transition:0.2s; z-index:1001; }
#dashboardBtn:hover { transform:scale(1.05); background:#dd2476; }
@media (max-width:900px){ form{flex-direction:column;} form input,form select,form button{width:100%;} }
#loader {
    display: none; /* hidden by default */
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    color: #fff;
    font-size: 1.5rem;
    text-align: center;
}
#loader img {
    width: 150px;
    margin-bottom: 20px;
}
/* ===================== FULL RESPONSIVE FIX ====================== */
@media (max-width: 1024px) {
    .container {
        width: 95%;
        padding: 15px;
        margin-top: 80px;
    }

    table {
        font-size: 14px;
    }

    form label {
        font-size: 14px;
    }
}

@media (max-width: 768px) {

    body {
        font-size: 14px;
    }

    /* Form becomes stacked */
    form {
        flex-direction: column !important;
        gap: 12px;
        width: 100%;
    }

    form input,
    form select,
    form button {
        width: 100% !important;
        font-size: 15px;
    }

    /* CSV buttons full width */
    #emailCsvBtn,
    [name="download_csv"] {
        width: 100% !important;
        padding: 12px;
        margin-bottom: 8px;
    }

    /* Table scroll & readable */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 13px;
        border-radius: 6px;
    }

    th, td {
        padding: 8px;
        font-size: 13px;
        text-align: center;
    }

    /* Action buttons vertical */
    td a,
    td button {
        display: block;
        width: 100%;
        margin: 6px 0;
        padding: 8px;
        font-size: 14px;
    }

    /* Dashboard button scale down */
    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 8px 12px;
        font-size: 13px;
        border-radius: 5px;
    }

    /* Popup full responsive */
    #updatePopup {
        width: 92% !important;
        max-height: 90vh;
        overflow-y: auto;
        font-size: 14px;
        padding: 15px;
    }

    #updatePopup input,
    #updatePopup select,
    #updatePopup button {
        width: 100% !important;
        font-size: 14px;
        padding: 10px;
    }

    /* Loader resize */
    #loader img {
        width: 80px;
    }
    #loader p {
        font-size: 14px;
    }
}

@media (max-width: 480px) {

    h2 {
        font-size: 20px;
    }

    table {
        font-size: 12px;
    }

    td button,
    td a {
        font-size: 12px;
        padding: 7px;
    }

    #dashboardBtn {
        font-size: 12px;
        padding: 6px 10px;
    }
}

</style>
</head>
<body onload="toggleDateFields()">
<video autoplay muted loop class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>
<a id="dashboardBtn" href="dashboard.php">Dashboard</a>
<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading...">
    <p>Hold your breath...</p>
</div>

<div class="container">
    <h2>Manage Schedules</h2>
<div style="margin-top: 15px; margin-bottom: 20px;">
    <form method="POST" style="display:inline;">
        <button type="submit" name="download_csv" style="background:#007bff; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
            📥 Download CSV
        </button>
    </form>
<form id="emailCsvForm" style="display:inline;">
    <button type="button" id="emailCsvBtn" style="background:#ff6600; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
        ✉️ Email CSV
    </button>
</form>


</div>

   <form method="POST">

<div>
    <label>Bus:</label>
    <select name="bus_id" required>
        <option value="">Select Bus</option>
        <?php foreach($all_buses as $bus): ?>
        <option value="<?= $bus['id'] ?>"><?= htmlspecialchars($bus['bus_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Route:</label>
    <select name="route_id" required>
        <option value="">Select Route</option>
        <?php foreach($all_routes as $route): ?>
        <option value="<?= $route['id'] ?>"><?= htmlspecialchars($route['source'].' to '.$route['destination']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div>
    <label>Travel Type:</label>
    <select name="travel_type" id="travel_type" onchange="toggleDateFields()" required>
        <option value="by_date">By Date</option>
        <option value="everyday">Everyday</option>
    </select>

    <label id="travel_date_label">Travel Date:</label>
  <input type="date" name="travel_date" id="travel_date" required min="<?= date('Y-m-d') ?>">
<input type="date" name="start_date" id="start_date" style="display:none;" min="<?= date('Y-m-d') ?>">

</div>

<div>
    <label>Departure Time:</label>
    <input type="time" name="departure_time" required>
    <label>Arrival Time:</label>
    <input type="time" name="arrival_time" required>
</div>

<div>
    <button type="submit" name="add_schedule" style="background:#28a745;">Add Schedule</button>
    <button type="button" onclick="if(confirm('Delete all schedules?')){ window.location='?delete_all=1'; }" style="background:#ff4500;">Delete All</button>
</div>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Bus</th>
            <th>Route</th>
            <th>Travel Date</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row=$schedules_result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['bus_name']) ?></td>
            <td><?= htmlspecialchars($row['source'].' to '.$row['destination']) ?></td>
            <td><?= htmlspecialchars($row['travel_date']) ?></td>
            <td><?= htmlspecialchars($row['departure_time']) ?></td>
            <td><?= htmlspecialchars($row['arrival_time']) ?></td>
            <td>
                <a class="btn-delete" href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this schedule?')">Delete</a>
                <button type="button" onclick="openUpdatePopup('<?= $row['id'] ?>','<?= $row['bus_id'] ?>','<?= $row['route_id'] ?>','<?= $row['travel_date'] ?>','<?= $row['departure_time'] ?>','<?= $row['arrival_time'] ?>')">Update</button>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div id="updatePopup">
    <h3>Update Schedule</h3>
    <form method="POST">
        <input type="hidden" name="update_schedule_id" id="update_schedule_id">
        <label>Bus:</label>
        <select name="update_bus_id" id="update_bus_id" required>
            <?php foreach($all_buses as $bus): ?>
            <option value="<?= $bus['id'] ?>"><?= htmlspecialchars($bus['bus_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Route:</label>
        <select name="update_route_id" id="update_route_id" required>
            <?php foreach($all_routes as $route): ?>
            <option value="<?= $route['id'] ?>"><?= htmlspecialchars($route['source'].' to '.$route['destination']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Travel Date:</label>
        <input type="date" name="update_travel_date" id="update_travel_date" required>
        <label>Departure:</label>
        <input type="time" name="update_departure_time" id="update_departure_time" required>
        <label>Arrival:</label>
        <input type="time" name="update_arrival_time" id="update_arrival_time" required>

        <button type="submit" name="update_schedule">Update</button>
        <button type="button" onclick="closeUpdatePopup()">Cancel</button>
    </form>
</div>
</div>

<script>
function toggleDateFields() {
    let type = document.getElementById('travel_type').value;

    document.getElementById('travel_date').style.display = (type==='by_date') ? 'inline-block' : 'none';
    document.getElementById('start_date').style.display = (type==='everyday') ? 'inline-block' : 'none';

    document.getElementById('travel_date').required = (type==='by_date');
    document.getElementById('start_date').required = (type==='everyday');

    document.getElementById('travel_date_label').textContent = (type==='by_date') ? 'Travel Date:' : 'Start Date:';
}

function openUpdatePopup(id,bus,route,date,dep,arr){
    document.getElementById('update_schedule_id').value=id;
    document.getElementById('update_bus_id').value=bus;
    document.getElementById('update_route_id').value=route;
    document.getElementById('update_travel_date').value=date;
    document.getElementById('update_departure_time').value=dep;
    document.getElementById('update_arrival_time').value=arr;
    document.getElementById('updatePopup').style.display='block';
}
function closeUpdatePopup(){
    document.getElementById('updatePopup').style.display='none';
}
function showLoader() {
    document.getElementById('loader').style.display = 'flex';
}

// Add loader to Add Schedule form
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        showLoader();
    });
});

// Add loader to Delete buttons/links
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function() {
        showLoader();
    });
});

// Delete All button
document.querySelectorAll('button[onclick*="delete_all"]').forEach(btn => {
    btn.addEventListener('click', function() {
        showLoader();
    });
});

// Optional: show loader when Update popup form is submitted
document.querySelectorAll('#updatePopup form').forEach(form => {
    form.addEventListener('submit', function() {
        showLoader();
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById("loader");
    const emailForm = document.getElementById("emailCsvForm");

    if (emailForm) {
        emailForm.addEventListener("submit", function (e) {
            e.preventDefault();
            loader.style.display = "flex";
            setTimeout(() => {
                emailForm.submit();
            }, 300);
        });
    }
});
document.getElementById("emailCsvBtn").addEventListener("click", function () {
    const loader = document.getElementById("loader");
    loader.style.display = "flex";

    fetch(window.location.href, {
        method: "POST",
        body: new URLSearchParams({ email_csv: "1", ajax: "1" })
    })
    .then(response => response.text())
    .then(text => {
        loader.style.display = "none";
        if (text.includes("✅")) {
            alert("✅ CSV file emailed successfully!");
        } else if (text.includes("❌")) {
            alert("❌ Failed to send email. Please try again.");
        } else {
            alert("✅ Email sent successfully (response received).");
        }
    })
    .catch(err => {
        loader.style.display = "none";
        alert("⚠️ Error sending email: " + err.message);
    });
});
</script>
</body>
</html>
