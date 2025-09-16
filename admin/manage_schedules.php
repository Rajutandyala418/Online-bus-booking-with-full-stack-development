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

// ✅ Include PHPMailer
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$admin_id = $_SESSION['admin_id'];

// Fetch admin details for email
$admin_stmt = $conn->prepare("SELECT first_name, last_name, email FROM admin WHERE id = ?");
$admin_stmt->bind_param("i", $admin_id);
$admin_stmt->execute();
$admin_stmt->bind_result($admin_first, $admin_last, $admin_email);
$admin_stmt->fetch();
$admin_stmt->close();

// ✅ Email sender function
function sendScheduleEmail($toEmail, $toName, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'varahibusbooking@gmail.com';
        $mail->Password   = 'pjhg nwnt haac nsiu';  // Gmail App Password
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
        // Log errors if needed
    }
}

// ------------------ Delete schedule ------------------
if (isset($_GET['delete_id'])) { 
    $delete_id = intval($_GET['delete_id']); 
    $del_stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND admin_id = ?"); 
    $del_stmt->bind_param("ii", $delete_id, $admin_id); 
    $del_stmt->execute(); 

    // ✅ Send email
    sendScheduleEmail(
        $admin_email, "$admin_first $admin_last",
        "Schedule Deleted",
        "<p>Hello <b>$admin_first</b>,</p><p>Schedule <b>ID $delete_id</b> was deleted on ".date("Y-m-d H:i:s").".</p>"
    );

    header("Location: manage_schedules.php"); 
    exit(); 
} 

// ------------------ Delete all schedules ------------------
if (isset($_GET['delete_all'])) {
    if ($admin_id == 3) {
        $conn->query("DELETE FROM schedules");
        $msg = "All schedules deleted by Super Admin.";
    } else {
        $del_all_stmt = $conn->prepare("DELETE FROM schedules WHERE admin_id = ?");
        $del_all_stmt->bind_param("i", $admin_id);
        $del_all_stmt->execute();
        $msg = "All your schedules were deleted.";
    }

    // ✅ Send email
    sendScheduleEmail(
        $admin_email, "$admin_first $admin_last",
        "Schedules Deleted",
        "<p>Hello <b>$admin_first</b>,</p><p>$msg (".date("Y-m-d H:i:s").").</p>"
    );

    header("Location: manage_schedules.php");
    exit();
}

// ------------------ Add schedule ------------------
if (isset($_POST['add_schedule'])) { 
    $bus_id = $_POST['bus_id']; 
    $route_id = $_POST['route_id']; 
    $departure_time = $_POST['departure_time']; 
    $arrival_time = $_POST['arrival_time']; 
    $travel_type = $_POST['travel_type']; 

    if ($travel_type === "by_date") {
        $travel_date = $_POST['travel_date']; 
        $query = "INSERT INTO schedules (bus_id, route_id, travel_date, departure_time, arrival_time, admin_id) VALUES (?, ?, ?, ?, ?, ?)"; 
        $stmt = $conn->prepare($query); 
        $stmt->bind_param("iisssi", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $admin_id); 
        $stmt->execute(); 
    } else if ($travel_type === "everyday") {
        $start_date = $_POST['start_date']; 
        $date = new DateTime($start_date);
        for ($i = 0; $i < 30; $i++) {
            $travel_date = $date->format("Y-m-d");
            $query = "INSERT INTO schedules (bus_id, route_id, travel_date, departure_time, arrival_time, admin_id) VALUES (?, ?, ?, ?, ?, ?)"; 
            $stmt = $conn->prepare($query); 
            $stmt->bind_param("iisssi", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $admin_id); 
            $stmt->execute(); 
            $date->modify('+1 day');
        }
    }

    // ✅ Send email
    sendScheduleEmail(
        $admin_email, "$admin_first $admin_last",
        "New Schedule Added",
        "<p>Hello <b>$admin_first</b>,</p><p>You added a new schedule (Bus ID: $bus_id, Route ID: $route_id, Departure: $departure_time, Arrival: $arrival_time) on ".date("Y-m-d H:i:s").".</p>"
    );

    header("Location: manage_schedules.php"); 
    exit(); 
}

// ------------------ Update schedule ------------------
if (isset($_POST['update_schedule'])) {
    $update_id = $_POST['update_schedule_id'];
    $bus_id = $_POST['update_bus_id'];
    $route_id = $_POST['update_route_id'];
    $travel_date = $_POST['update_travel_date'];
    $departure_time = $_POST['update_departure_time'];
    $arrival_time = $_POST['update_arrival_time'];

    $query = "UPDATE schedules SET bus_id=?, route_id=?, travel_date=?, departure_time=?, arrival_time=? WHERE id=? AND admin_id=?"; 
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisssii", $bus_id, $route_id, $travel_date, $departure_time, $arrival_time, $update_id, $admin_id);
    $stmt->execute();

    // ✅ Send email
    sendScheduleEmail(
        $admin_email, "$admin_first $admin_last",
        "Schedule Updated",
        "<p>Hello <b>$admin_first</b>,</p><p>Schedule <b>ID $update_id</b> was updated on ".date("Y-m-d H:i:s").".</p>"
    );

    header("Location: manage_schedules.php");
    exit();
}
?>


<!DOCTYPE html> 
<html> 
<head> 
<title>Manage Schedules</title> 
<link rel="stylesheet" href="../css/styles.css"> 
<style> 
body { 
    margin: 0; 
    padding: 0; 
    color: white; 
    font-family: Arial, sans-serif; 
} 
video.bg-video { 
    position: fixed; 
    right: 0; 
    bottom: 0; 
    min-width: 100%; 
    min-height: 100%; 
    z-index: -1; 
    filter: brightness(0.4); 
} 
.header { 
    display: flex; 
    justify-content: flex-end; 
    padding: 10px 20px; 
    background: rgba(0, 0, 0, 0.7); 
    align-items: center; 
    position: relative; 
} 
/* Horizontal row for form fields */
#formFields {
    display: flex;
    flex-wrap: wrap; /* wrap if too many fields */
    gap: 15px;
    align-items: center;
    justify-content: flex-start;
}

/* Make each input/select flexible */
#formFields input,
#formFields select {
    flex: 1 1 150px;
    padding: 10px 12px;
    border-radius: 5px;
    border: 1px solid #555;
    background-color: white;
    color: blue;
    font-size: 14px;
    box-sizing: border-box;
}

/* Button row */
#formButton {
    display: flex;
    justify-content: center;
    margin-top: 20px; /* space from form fields */
}

/* Gradient button style */
#formButton button {
    padding: 12px 30px;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    color: blue;
    background: linear-gradient(135deg, #28a745, #00c6ff); /* green-blue gradient */
    transition: all 0.3s ease;
}

#formButton button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

/* Responsive adjustments */
@media (max-width: 900px) {
    #formFields {
        flex-direction: column; /* stack vertically on mobile */
    }
    #formFields input,
    #formFields select {
        width: 100%;
    }
}

.welcome { margin-right: 10px; } 
.profile-container { position: relative; cursor: pointer; } 
.profile { border-radius: 50%; width: 40px; height: 40px; } 
.profile-pic { 
    width: 40px; height: 40px; background: white; color: #007bff; 
    border-radius: 50%; display: flex; justify-content: center; 
    align-items: center; font-weight: bold; font-size: 20px; 
    user-select: none; 
} 
.dropdown { 
    display: none; position: absolute; right: 0; top: 50px; 
    background: rgba(0,0,0,0.85); border-radius: 5px; 
    overflow: hidden; min-width: 150px; z-index: 10; 
} 
.dropdown a { display: block; padding: 10px; color: white; text-decoration: none; } 
.dropdown a:hover { background: rgba(255,255,255,0.1); } 

.container { 
    padding: 20px; 
    margin-top: 50px; /* pushed slightly down */ 
} 

table { 
    width: 100%; background: rgba(0, 0, 0, 0.6); 
    border-collapse: collapse; margin-top: 20px; 
} 
/* Update Popup Styles */
#updatePopup {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(30, 30, 30, 0.95); /* dark background */
    padding: 30px 40px;
    border-radius: 15px;
    z-index: 1000;
    width: 500px; /* desktop width */
    max-width: 90%; /* responsive width for smaller screens */
    color: white;
    box-shadow: 0 5px 25px rgba(0,0,0,0.5);
    font-family: Arial, sans-serif;
    overflow-y: auto; /* scroll if content is tall */
    max-height: 90vh; /* popup won't exceed screen height */
}

/* Heading inside popup */
#updatePopup h3 {
    text-align: center;
    margin-bottom: 20px;
    color: #00ffcc;
}

/* Inputs and select fields */
#updatePopup input,
#updatePopup select {
    width: 100%;
    padding: 12px 15px;
    margin: 8px 0 15px 0;
    border-radius: 5px;
    border: 1px solid #555;
    background-color: #222;
    color: white;
    font-size: 16px;
    box-sizing: border-box;
}

/* Buttons inside popup */
#updatePopup button {
    padding: 10px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin: 5px;
    font-weight: bold;
    font-size: 16px;
}

/* Submit button (update) */
#updatePopup button[type="submit"] {
    background-color: #28a745; /* green */
    color: white;
}

/* Cancel button */
#updatePopup button[type="button"] {
    background-color: #dc3545; /* red */
    color: white;
}

/* Responsive adjustments */
@media (max-width: 600px) {
    #updatePopup {
        width: 90%; /* almost full width on mobile */
        padding: 20px;
    }

    #updatePopup input,
    #updatePopup select,
    #updatePopup button {
        font-size: 14px;
    }

    #updatePopup button {
        padding: 8px 15px;
    }
}
/* Apply flex layout to the container */
#container {
    display: flex;
    flex-wrap: wrap; /* allows wrapping if screen is too small */
    gap: 15px; /* space between fields */
    align-items: center; /* vertically center inputs */
    justify-content: flex-start; /* align items to the left */
}
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
/* Make each input/select/button flexible */
#container input,
#container select,
#container button {
    flex: 1 1 150px; /* grow/shrink with min width 150px */
    padding: 10px 12px;
    border-radius: 5px;
    border: 1px solid #555;
    background-color: #222;
    color: white;
    font-size: 14px;
    box-sizing: border-box;
}

/* Buttons stay fixed size */
#container button {
    flex: 0 0 auto;
    cursor: pointer;
}
#departureRow button:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

/* Responsive layout for smaller screens */
@media (max-width: 900px) {
    #container {
        flex-direction: column; /* stack vertically on small screens */
    }
    #container input,
    #container select,
    #container button {
        width: 100%;
    }
}


table, th, td { border: 1px solid white; padding: 8px; } 
th { background: rgba(255, 255, 255, 0.2); } 
form { background: rgba(0, 0, 0, 0.6); padding: 15px; border-radius: 5px; } 
select, input, button { padding: 20px; margin: 15px; color: green; } 
.btn-delete { color: red; text-decoration: none; } 
.btn-delete:hover { text-decoration: underline; } 
.btn-delete-all { color: orange; text-decoration: none; font-weight: bold; } 
.btn-delete-all:hover { text-decoration: underline; } 
</style> 
</head> 
<body> 
<video autoplay muted loop class="bg-video"> 
    <source src="../videos/bus.mp4" type="video/mp4"> 
</video> 
<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>

<div class="container"> 
    <h2>Manage Schedules</h2> 

    <form method="POST"> 
    <div id="formFields">
    <label>Select Bus:</label>
    <select name="bus_id" id="bus_id" required>
        <option value="">Select Bus</option>
        <?php 
        $buses->execute(); 
        $buses_result = $buses->get_result(); 
        while ($bus = $buses_result->fetch_assoc()) { ?> 
            <option value="<?php echo $bus['id']; ?>"><?php echo htmlspecialchars($bus['bus_name']); ?></option> 
        <?php } ?> 
    </select> 

    <label>Select Route:</label>
    <select name="route_id" id="route_id" required>
        <option value="">Select Route</option>
        <?php 
        $routes->execute(); 
        $routes_result = $routes->get_result(); 
        while ($route = $routes_result->fetch_assoc()) { ?> 
            <option value="<?php echo $route['id']; ?>"> 
                <?php echo htmlspecialchars($route['source'] . " to " . $route['destination']); ?> 
            </option> 
        <?php } ?> 
    </select>

    <label>Travel Type:</label>
    <select name="travel_type" id="travel_type" onchange="toggleDateFields()" required>
        <option value="by_date">By Date</option>
        <option value="everyday">Everyday</option>
    </select>

    <div id="byDateField">
        <label>Travel Date:</label>
        <input type="date" name="travel_date">
    </div>

    <div id="everydayFields" style="display:none;">
        <label>Start Date:</label>
        <input type="date" name="start_date">
    </div>
</div>

<!-- Second Row: Departure + Buttons -->
<div id="buttonRow" style="display:flex; align-items:center; gap:15px; margin-top:10px; flex-wrap:wrap;">
<label for="departure_time">Departure Time:</label>
<input type="time" name="departure_time" id="departure_time" required>

<label for="arrival_time">Arrival Time:</label>
<input type="time" name="arrival_time" id="arrival_time" required>


    <button type="submit" name="add_schedule" style="background: linear-gradient(135deg, #28a745, #00c6ff); color:white; font-weight:bold; padding:12px 25px; border:none; border-radius:25px; cursor:pointer; transition: all 0.3s ease;">
        Add Schedule
    </button>

    <button type="button" onclick="if(confirm('Are you sure you want to delete ALL schedules?')){ window.location='?delete_all=1'; }" style="background: linear-gradient(135deg, #ff7f50, #ff4500); color:white; font-weight:bold; padding:12px 25px; border:none; border-radius:25px; cursor:pointer; transition: all 0.3s ease;">
        Delete All Schedules
    </button>
</div> </form> 


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
            <?php while ($row = $schedules_result->fetch_assoc()) { ?> 
            <tr> 
                <td><?php echo $row['id']; ?></td> 
                <td><?php echo htmlspecialchars($row['bus_name']); ?></td> 
                <td><?php echo htmlspecialchars($row['source'] . " to " . $row['destination']); ?></td> 
                <td><?php echo htmlspecialchars($row['travel_date']); ?></td> 
                <td><?php echo htmlspecialchars($row['departure_time']); ?></td> 
                <td><?php echo htmlspecialchars($row['arrival_time']); ?></td> 
                <td>
                    <a class="btn-delete" href="?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this schedule?')">Delete</a>
                    <button type="button" onclick="openUpdatePopup('<?php echo $row['id']; ?>','<?php echo $row['bus_id']; ?>','<?php echo $row['route_id']; ?>','<?php echo $row['travel_date']; ?>','<?php echo $row['departure_time']; ?>','<?php echo $row['arrival_time']; ?>')">Update</button>
                </td> 
            </tr> 
            <?php } ?> 
        </tbody> 
    </table> 
<div id="updatePopup" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#1e1e1e; padding:20px; border-radius:10px; z-index:1000; width:350px; color:white;">
    <h3 style="text-align:center; color:#00ffcc;">Update Schedule</h3>
    <form method="POST">
        <input type="hidden" name="update_schedule_id" id="update_schedule_id">
        <label>Bus:</label>
        <select name="update_bus_id" id="update_bus_id" required>
            <?php
            $buses->execute();
            $buses_result = $buses->get_result();
            while ($bus = $buses_result->fetch_assoc()) {
                echo '<option value="'.$bus['id'].'">'.htmlspecialchars($bus['bus_name']).'</option>';
            }
            ?>
        </select>
        <label>Route:</label>
        <select name="update_route_id" id="update_route_id" onchange="setUpdateDuration()" required>
            <?php
            $routes->execute();
            $routes_result = $routes->get_result();
            while ($route = $routes_result->fetch_assoc()) {
                echo '<option value="'.$route['id'].'" data-duration="'.$route['duration'].'">'.htmlspecialchars($route['source'].' to '.$route['destination']).'</option>';
            }
            ?>
        </select>
        <label>Travel Date:</label>
        <input type="date" name="update_travel_date" id="update_travel_date" required>
        <label>Departure:</label>
        <input type="time" name="update_departure_time" id="update_departure_time" onchange="setUpdateArrivalTime()" required>
        <label>Arrival:</label>
        <input type="time" name="update_arrival_time" id="update_arrival_time" required>
        <div style="margin-top:10px; text-align:center;">
            <button type="submit" name="update_schedule" style="background:green; padding:5px 15px; border:none; color:white; border-radius:5px;">Update</button>
            <button type="button" onclick="closeUpdatePopup()" style="background:red; padding:5px 15px; border:none; color:white; border-radius:5px;">Cancel</button>
        </div>
    </form>
</div>
</div> 

<script> 
function toggleDropdown() { 
    document.getElementById("profileDropdown").style.display = document.getElementById("profileDropdown").style.display === "block" ? "none" : "block"; 
} 
window.onclick = function(event) { 
    if (!event.target.closest('.profile-container')) { 
        document.getElementById("profileDropdown").style.display = "none"; 
    } 
} 
function toggleDateFields() {
  let type = document.getElementById("travel_type").value;
  document.getElementById("byDateField").style.display = (type === "by_date") ? "block" : "none";
  document.getElementById("everydayFields").style.display = (type === "everyday") ? "block" : "none";
}
function setDuration(sel) {
    let duration = sel.options[sel.selectedIndex].getAttribute("data-duration");
    document.getElementById("route_duration").value = duration;
}

function openUpdatePopup(id, bus, route, date, dep, arr) {
    document.getElementById("update_schedule_id").value = id;
    document.getElementById("update_bus_id").value = bus;
    document.getElementById("update_route_id").value = route;
    document.getElementById("update_travel_date").value = date;
    document.getElementById("update_departure_time").value = dep;
    document.getElementById("update_arrival_time").value = arr;
    document.getElementById("updatePopup").style.display = "block";
}
function closeUpdatePopup() {
    document.getElementById("updatePopup").style.display = "none";
}
function openUpdatePopup(id, bus, route, date, dep, arr) {
    document.getElementById("update_schedule_id").value = id;
    document.getElementById("update_bus_id").value = bus;
    document.getElementById("update_route_id").value = route;
    document.getElementById("update_travel_date").value = date;
    document.getElementById("update_departure_time").value = dep;
    document.getElementById("update_arrival_time").value = arr;

    document.getElementById("updatePopup").style.display = "block";
}

// Update arrival time automatically

function closeUpdatePopup() {
    document.getElementById("updatePopup").style.display = "none";
}
</script> 
</body> 
</html>
