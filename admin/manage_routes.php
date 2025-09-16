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

// ✅ PHPMailer include
require __DIR__ . '/../include/php_mailer/Exception.php';
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$admin_id = $_SESSION['admin_id'];
$admin_first_name = $_SESSION['admin_first_name'] ?? '';
$admin_last_name = $_SESSION['admin_last_name'] ?? '';
$admin_name = trim($admin_first_name . ' ' . $admin_last_name);

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
        $mail->Password   = 'pjhg nwnt haac nsiu'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('varahibusbooking@gmail.com', 'Bus Booking System');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        // Fail silently
    }
}

// Handle Delete Route
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);

    // Fetch old details
    $stmt = $conn->prepare("SELECT source, destination, fare, distance_km, approx_duration FROM routes WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->bind_result($oldSource, $oldDest, $oldFare, $oldDist, $oldDur);
    $stmt->fetch();
    $stmt->close();

    if ($admin_id == 3) {
        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM routes WHERE id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $delete_id, $admin_id);
    }
    $stmt->execute();
    $stmt->close();

    // ✅ Send email
    $subject = "Route Deleted Successfully";
    $body = "Dear {$adminFullName},<br><br>
             You have deleted the following route:<br>
             <b>Source:</b> {$oldSource}<br>
             <b>Destination:</b> {$oldDest}<br>
             <b>Fare:</b> ₹{$oldFare}<br>
             <b>Distance:</b> {$oldDist} km<br>
             <b>Duration:</b> {$oldDur}<br><br>
             Thank you,<br>Varahi Bus Booking";
    sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

    header("Location: manage_routes.php");
    exit();
}

// Handle Add Route
if (isset($_POST['add_route'])) {
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);
    $fare = $_POST['fare'];
    $distance_km = $_POST['distance_km'] ?: null;
    $approx_duration = $_POST['approx_duration'] ?: null;

    if (strcasecmp($source, $destination) === 0) {
        echo "<script>alert('Source and destination cannot be the same.');</script>";
    } else {
        $query = "INSERT INTO routes (source, destination, fare, distance_km, approx_duration, admin_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssddsi", $source, $destination, $fare, $distance_km, $approx_duration, $admin_id);
        $stmt->execute();
        $stmt->close();

        // ✅ Send email
        $subject = "New Route Added Successfully";
        $body = "Dear {$adminFullName},<br><br>
                 You have successfully added a new route:<br>
                 <b>Source:</b> {$source}<br>
                 <b>Destination:</b> {$destination}<br>
                 <b>Fare:</b> ₹{$fare}<br>
                 <b>Distance:</b> {$distance_km} km<br>
                 <b>Duration:</b> {$approx_duration}<br><br>
                 Thank you,<br>Varahi Bus Booking";
        sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

        header("Location: manage_routes.php");
        exit();
    }
}

// Handle Update Route
if (isset($_POST['update_route'])) {
    $route_id = intval($_POST['update_route_id']);
    $source = trim($_POST['update_source']);
    $destination = trim($_POST['update_destination']);
    $fare = $_POST['update_fare'];
    $distance_km = $_POST['update_distance_km'] ?: null;
    $approx_duration = $_POST['update_approx_duration'] ?: null;

    // Fetch old details
    $stmt = $conn->prepare("SELECT source, destination, fare, distance_km, approx_duration FROM routes WHERE id = ?");
    $stmt->bind_param("i", $route_id);
    $stmt->execute();
    $stmt->bind_result($oldSource, $oldDest, $oldFare, $oldDist, $oldDur);
    $stmt->fetch();
    $stmt->close();

    if (strcasecmp($source, $destination) === 0) {
        echo "<script>alert('Source and destination cannot be the same.');</script>";
    } else {
        if ($admin_id == 3) {
            $query = "UPDATE routes SET source=?, destination=?, fare=?, distance_km=?, approx_duration=? WHERE id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssddsi", $source, $destination, $fare, $distance_km, $approx_duration, $route_id);
        } else {
            $query = "UPDATE routes SET source=?, destination=?, fare=?, distance_km=?, approx_duration=? WHERE id=? AND admin_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssddsii", $source, $destination, $fare, $distance_km, $approx_duration, $route_id, $admin_id);
        }
        $stmt->execute();
        $stmt->close();

        // ✅ Send email
        $subject = "Route Updated Successfully";
        $body = "Dear {$adminFullName},<br><br>
                 You have updated the route details.<br><br>
                 <b>Old Details:</b><br>
                 Source: {$oldSource}<br>
                 Destination: {$oldDest}<br>
                 Fare: ₹{$oldFare}<br>
                 Distance: {$oldDist} km<br>
                 Duration: {$oldDur}<br><br>
                 <b>New Details:</b><br>
                 Source: {$source}<br>
                 Destination: {$destination}<br>
                 Fare: ₹{$fare}<br>
                 Distance: {$distance_km} km<br>
                 Duration: {$approx_duration}<br><br>
                 Thank you,<br>Varahi Bus Booking";
        sendAdminEmail($adminEmail, $adminFullName, $subject, $body);

        header("Location: manage_routes.php");
        exit();
    }
}

// Fetch routes
if ($admin_id == 3) {
    $routes_query = "SELECT * FROM routes ORDER BY id DESC";
    $stmt = $conn->prepare($routes_query);
} else {
    $routes_query = "SELECT * FROM routes WHERE admin_id = ? ORDER BY id DESC";
    $stmt = $conn->prepare($routes_query);
    $stmt->bind_param("i", $admin_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Routes</title>
    <style>
        /* Reset & base */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #121212; color: white; min-height: 100vh; }
        video.bg-video { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; z-index: -1; filter: brightness(0.3); }
        .header { display: flex; justify-content: flex-end; align-items: center; padding: 12px 24px; background: rgba(0, 0, 0, 0.75); position: sticky; top: 0; z-index: 20; }
        .welcome { margin-right: 12px; font-size: 18px; font-weight: 600; }
        .profile-container { position: relative; cursor: pointer; }
        .profile-pic { background: #007bff; width: 40px; height: 40px; border-radius: 50%; color: white; font-weight: bold; font-size: 22px; display: flex; justify-content: center; align-items: center; user-select: none; }
        .dropdown { display: none; position: absolute; right: 0; top: 50px; background: rgba(0, 0, 0, 0.85); border-radius: 6px; min-width: 160px; z-index: 30; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.8); }
        .dropdown a { display: block; padding: 12px 16px; color: white; text-decoration: none; transition: background 0.2s; }
        .dropdown a:hover { background: rgba(255, 255, 255, 0.1); }
        .container { max-width: 1500px; margin: 30px auto 40px; background: rgba(0, 0, 0, 0.7); border-radius: 12px; padding: 25px 30px; box-shadow: 0 0 15px rgba(0,0,0,0.6); }
        h2 { margin-top: 0; margin-bottom: 20px; font-weight: 700; font-size: 28px; color: #00bfff; text-align: center; }
        form { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 30px; }
        label { font-size: 14px; margin-bottom: 4px; display: block; font-weight: 600; color: #00bfff; }
        form input, form button { font-size: 16px; padding: 12px 15px; border-radius: 6px; border: none; outline: none; width: 100%; }
        form input { background: pink; color: blue; border: 1px solid #444; transition: border-color 0.3s; }
        form input:focus { border-color: #00bfff; background: yellow; }
        form button { background: #007bff; color: white; cursor: pointer; width: 200px; align: center; transition: background 0.3s; font-weight: 600; border: 1px solid #007bff; grid-column: span 2; }
        form button:hover { background: #0056b3; border-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; background: #222; border-radius: 8px; overflow: hidden; }
        thead { background: #007bff; }
        th, td { padding: 14px 12px; text-align: center; border-bottom: 1px solid #333; }
        th { color: white; font-weight: 600; }
        tbody tr:hover { background: rgba(0, 191, 255, 0.1); }
        .btn-delete { color: #dc3545; font-weight: 600; text-decoration: none; cursor: pointer; transition: color 0.3s; }
        .btn-delete:hover { color: #a71d2a; }
        /* Popup */
        #updatePopup { display:none; position:fixed; top:55%; left:50%; transform:translate(-50%,-50%); background:#1e1e1e; padding:20px; border-radius:10px; z-index:1000; width:350px; box-shadow:0 4px 12px rgba(0,0,0,0.8); color:white; }
        #updatePopup input { width:100%; padding:8px; margin-bottom:10px; border:none; border-radius:5px; background:#2c2c2c; color:white; }
        #updatePopup h3 { text-align:center; color:#00ffcc; }
        #updatePopup button { padding:8px 15px; border:none; border-radius:5px; cursor:pointer; margin:5px; }
        #updatePopup .save-btn { background:#00cc66; color:white; width : 100%; }
        #updatePopup .cancel-btn { background:#cc0000; color:white; width:100%;}
        @media (max-width: 768px) { form { grid-template-columns: 1fr; } }
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
    </style>
    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById("profileDropdown");
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        }
        window.onclick = function(event) {
            if (!event.target.closest('.profile-container')) {
                const dropdown = document.getElementById("profileDropdown");
                if (dropdown.style.display === "block") {
                    dropdown.style.display = "none";
                }
            }
        }
        function validateRouteForm() {
            let source = document.forms["routeForm"]["source"].value.trim();
            let destination = document.forms["routeForm"]["destination"].value.trim();
            if (source.toLowerCase() === destination.toLowerCase()) {
                alert("Source and destination cannot be the same.");
                return false;
            }
            return true;
        }
        function openUpdatePopup(id, source, destination, fare, distance, duration) {
            document.getElementById("update_route_id").value = id;
            document.getElementById("update_source").value = source;
            document.getElementById("update_destination").value = destination;
            document.getElementById("update_fare").value = fare;
            document.getElementById("update_distance_km").value = distance;
            document.getElementById("update_approx_duration").value = duration;
            document.getElementById("updatePopup").style.display = "block";
        }
        function closeUpdatePopup() {
            document.getElementById("updatePopup").style.display = "none";
        }
    </script>
</head>
<body>
    <video autoplay muted loop playsinline class="bg-video">
        <source src="../videos/bus.mp4" type="video/mp4" />
    </video>

<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>

    <main class="container">
        <h2>Manage Routes</h2>
        <form name="routeForm" method="POST" onsubmit="return validateRouteForm()">
            <div>
                <label for="source">Source</label>
                <input type="text" name="source" id="source" placeholder="Enter source" required />
            </div>
            <div>
                <label for="destination">Destination</label>
                <input type="text" name="destination" id="destination" placeholder="Enter destination" required />
            </div>
            <div>
                <label for="fare">Fare (₹)</label>
                <input type="number" step="0.01" name="fare" id="fare" placeholder="Enter fare" required />
            </div>
            <div>
                <label for="distance_km">Distance (km)</label>
                <input type="number" step="0.01" name="distance_km" id="distance_km" placeholder="Enter distance" />
            </div>
            <div>
                <label for="approx_duration">Approx Duration</label>
                <input type="time" name="approx_duration" id="approx_duration" />
            </div>
            <button type="submit" name="add_route">Add Route</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Fare (₹)</th>
                    <th>Distance (km)</th>
                    <th>Approx Duration</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['source']); ?></td>
                        <td><?php echo htmlspecialchars($row['destination']); ?></td>
                        <td>₹<?php echo number_format($row['fare'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['distance_km']); ?></td>
                        <td><?php echo htmlspecialchars($row['approx_duration']); ?></td>
                        <td>
                            <a href="manage_routes.php?delete_id=<?php echo $row['id']; ?>" 
                               class="btn-delete"
                               onclick="return confirm('Are you sure you want to delete this route?');">Delete</a>
                            &nbsp; | &nbsp;
                            <a href="javascript:void(0)" style="color:#00ffcc; font-weight:600;"
                               onclick="openUpdatePopup('<?php echo $row['id']; ?>',
                                                         '<?php echo htmlspecialchars($row['source']); ?>',
                                                         '<?php echo htmlspecialchars($row['destination']); ?>',
                                                         '<?php echo $row['fare']; ?>',
                                                         '<?php echo $row['distance_km']; ?>',
                                                         '<?php echo $row['approx_duration']; ?>')">
                               Update
                            </a>
                        </td>
                    </tr>
                <?php } ?>
                <?php if ($result->num_rows === 0): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 15px;">No routes found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>

    <!-- Update Popup -->
    <div id="updatePopup">
        <h3>Update Route</h3>
        <form method="POST">
            <input type="hidden" name="update_route_id" id="update_route_id">
            <label>Source:</label>
            <input type="text" name="update_source" id="update_source" required>
            <label>Destination:</label>
            <input type="text" name="update_destination" id="update_destination" required>
            <label>Fare (₹):</label>
            <input type="number" step="0.01" name="update_fare" id="update_fare" required>
            <label>Distance (km):</label>
            <input type="number" step="0.01" name="update_distance_km" id="update_distance_km">
            <label>Approx Duration:</label>
            <input type="time" name="update_approx_duration" id="update_approx_duration">
            <div style="text-align:center; margin-top:10px;">
                <button type="submit" name="update_route" class="save-btn">Update</button>
                <button type="button" class="cancel-btn" onclick="closeUpdatePopup()">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
