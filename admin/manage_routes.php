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
// === DOWNLOAD CSV OR EMAIL CSV HANDLER ===
if (isset($_POST['download_csv']) || isset($_POST['email_csv'])) {
    $csvFile = __DIR__ . "/../tmp/routes_" . date('Ymd_His') . ".csv";
    $fp = fopen($csvFile, 'w');

    // Headers
    fputcsv($fp, ['ID', 'Source', 'Destination', 'Fare (₹)', 'Distance (km)', 'Approx Duration']);

    // Fetch data again for CSV
    if ($admin_id == 3) {
        $csvQuery = "SELECT id, source, destination, fare, distance_km, approx_duration FROM routes ORDER BY id DESC";
        $csvStmt = $conn->prepare($csvQuery);
    } else {
        $csvQuery = "SELECT id, source, destination, fare, distance_km, approx_duration FROM routes WHERE admin_id = ? ORDER BY id DESC";
        $csvStmt = $conn->prepare($csvQuery);
        $csvStmt->bind_param("i", $admin_id);
    }
    $csvStmt->execute();
    $csvResult = $csvStmt->get_result();

    while ($r = $csvResult->fetch_assoc()) {
        fputcsv($fp, [
            $r['id'],
            $r['source'],
            $r['destination'],
            number_format($r['fare'], 2),
            $r['distance_km'],
            $r['approx_duration']
        ]);
    }
    fclose($fp);

    if (isset($_POST['download_csv'])) {
        // Force download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="routes_list.csv"');
        readfile($csvFile);
        unlink($csvFile);
        exit();
    }

    if (isset($_POST['email_csv'])) {
        $subject = "Routes List CSV File - Varahi Bus Booking";
        $body = "Dear {$adminFullName},<br><br>
                 Please find attached the latest list of your bus routes.<br><br>
                 Thank you,<br>Varahi Bus Booking System";

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
            $mail->addAddress($adminEmail, $adminFullName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->addAttachment($csvFile, 'routes_list.csv');

            $mail->send();
            unlink($csvFile);
            echo "<script>alert('CSV file has been sent successfully to your email.');</script>";
        } catch (Exception $e) {
            unlink($csvFile);
            echo "<script>alert('Failed to send email: " . addslashes($mail->ErrorInfo) . "');</script>";
        }
    }
}

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
.autocomplete-dropdown div:hover {
    background: #00bfff;
    color: #000;
}

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
.autocomplete-dropdown {
    position: absolute;
    background: #222;
    color: white;
    border: 1px solid #555;
    max-height: 150px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    border-radius: 5px;
}
.autocomplete-dropdown div {
    padding: 8px 12px;
    cursor: pointer;
    transition: background 0.2s;
}
.autocomplete-dropdown div:hover {
    background: #00bfff;
    color: #000;
}

/* ================= MOBILE RESPONSIVE FIXES ================= */
@media(max-width: 768px) {

    body {
        font-size: 14px;
    }

    .container {
        width: 94%;
        padding: 15px;
        margin-top: 90px;
        margin-bottom: 20px;
        border-radius: 8px;
        overflow-x: auto;
    }

    h2 {
        font-size: 22px;
        text-align: center;
    }

    /* Form becomes stacked */
    form {
        grid-template-columns: 1fr !important;
        gap: 10px;
    }

    form button {
        width: 100%;
        font-size: 15px;
        padding: 12px;
        grid-column: span 1 !important;
    }

    form input {
        width: 100%;
        padding: 10px;
        font-size: 14px;
    }

    /* CSV buttons stack */
    #emailCsvForm button,
    [name="download_csv"] {
        width: 100% !important;
        padding: 12px;
        font-size: 15px;
        margin-bottom: 10px;
    }

    /* Table scroll on mobile */
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
        font-size: 13px;
    }

    th, td {
        padding: 8px;
        font-size: 13px;
    }

    /* Action text wrap fix */
    td a {
        display: block;
        margin: 4px 0;
        font-size: 14px;
    }

    /* Delete | Update vertical instead of inline */
    td a:nth-child(2) {
        margin-top: 6px;
    }

    /* Dashboard button resize */
    #dashboardBtn {
        top: 10px;
        right: 10px;
        padding: 8px 14px;
        font-size: 13px;
    }

    /* Popup responsive */
    #updatePopup {
        width: 90%;
        max-width: 350px;
        padding: 15px;
    }

    #updatePopup input {
        font-size: 14px;
        padding: 10px;
    }

    #updatePopup button {
        width: 100%;
        margin-top: 6px;
        font-size: 14px;
        padding: 10px;
    }

    /* Loader scale down */
    #loader img {
        width: 90px;
    }
    #loader p {
        font-size: 14px;
    }
}

    </style>
<script>

// ✅ Toggle Profile Dropdown
function toggleDropdown() {
    const dropdown = document.getElementById("profileDropdown");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}

window.onclick = function(event) {
    if (!event.target.closest('.profile-container')) {
        const dropdown = document.getElementById("profileDropdown");
        if (dropdown) dropdown.style.display = "none";
    }
};

// ✅ Validate Add Route Form
function validateRouteForm() {
    let source = document.forms["routeForm"]["source"].value.trim();
    let destination = document.forms["routeForm"]["destination"].value.trim();

    if (source.toLowerCase() === destination.toLowerCase()) {
        alert("Source and destination cannot be the same.");
        return false;
    }
    return true;
}

// ✅ Open Update Popup
function openUpdatePopup(id, source, destination, fare, distance, duration) {
    document.getElementById("update_route_id").value = id;
    document.getElementById("update_source").value = source;
    document.getElementById("update_destination").value = destination;
    document.getElementById("update_fare").value = fare;
    document.getElementById("update_distance_km").value = distance;
    document.getElementById("update_approx_duration").value = duration;
    document.getElementById("updatePopup").style.display = "block";
}

// ✅ Close Update Popup
function closeUpdatePopup() {
    document.getElementById("updatePopup").style.display = "none";
}

// ✅ Confirm delete + Loader
function confirmDelete(event) {
    const isConfirmed = confirm('Are you sure you want to delete this route?');
    if (isConfirmed) {
        showLoader();
        return true;
    }
    event.preventDefault();
    hideLoader();
    return false;
}

// ✅ Loader functions
function showLoader() {
    const loader = document.getElementById("loader");
    if (loader) loader.style.display = "flex";  // ✅ Correct for flex layout
}

function hideLoader() {
    const loader = document.getElementById("loader");
    if (loader) loader.style.display = "none";
}

// ✅ Autocomplete Setup
function setupAutocomplete(inputId, type) {
    const input = document.getElementById(inputId);
    let dropdown = document.createElement("div");
    dropdown.classList.add("autocomplete-dropdown");
    input.parentNode.style.position = "relative";
    input.parentNode.appendChild(dropdown);

    input.addEventListener("input", function () {
        const val = this.value.trim();
        if (!val) {
            dropdown.style.display = "none";
            return;
        }

        fetch(`fetch_locations.php?term=${encodeURIComponent(val)}&type=${type}`)
            .then(res => res.json())
            .then(data => {
                dropdown.innerHTML = "";
                if (!data.length) {
                    dropdown.style.display = "none";
                    return;
                }

                data.forEach(item => {
                    const option = document.createElement("div");
                    option.textContent = item;
                    option.addEventListener("click", function () {
                        input.value = item;
                        dropdown.style.display = "none";
                    });
                    dropdown.appendChild(option);
                });
                dropdown.style.display = "block";
            });
    });

    document.addEventListener("click", function (e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = "none";
        }
    });
}

// ✅ DOM Loaded
document.addEventListener("DOMContentLoaded", function () {

    const loader = document.getElementById("loader");

    // Autocomplete for all inputs
    setupAutocomplete("source", "source");
    setupAutocomplete("destination", "destination");
    setupAutocomplete("update_source", "source");
    setupAutocomplete("update_destination", "destination");

    // ✅ Loader on all form submits
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function () {
            loader.style.display = "flex";
        });
    });

    // ✅ Loader on all anchor clicks (except JS links)
    document.querySelectorAll("a").forEach(a => {
        a.addEventListener("click", function (e) {

            const href = a.getAttribute("href");

            // Allowed: Delete handler (loader already handled)
            if (a.onclick && a.onclick.toString().includes("confirmDelete")) return;

            if (href && href !== "#" && !href.startsWith("javascript:")) {
                loader.style.display = "flex";
            }
        });
    });

    // ✅ Email CSV — AJAX with loader
    const emailCsvForm = document.getElementById("emailCsvForm");
    if (emailCsvForm) {
        emailCsvForm.addEventListener("submit", function (e) {
            e.preventDefault();
            loader.style.display = "flex";

            const formData = new FormData(emailCsvForm);

            fetch(window.location.href, {
                method: "POST",
                body: formData
            })
                .then(res => res.text())
                .then(msg => alert(msg))
                .catch(() => alert("❌ Failed to send email."))
                .finally(() => loader.style.display = "none");
        });
    }
});

</script>


  </head>
<body>
    <video autoplay muted loop playsinline class="bg-video">
        <source src="../videos/bus.mp4" type="video/mp4" />
    </video>

<a id="dashboardBtn" href="dashboard.php" title="Go to Dashboard">Dashboard</a>
<!-- Loader -->
<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading..." />
    <p>Please wait Hold your breath...</p>
</div>

    <main class="container">
        <h2>Manage Routes</h2>
<div style="text-align:center; margin-bottom:15px;">
    <form method="POST" style="display:inline;">
        <button type="submit" name="download_csv" style="background:#00cc66; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
            ⬇️ Download CSV
        </button>
<form method="POST" id="emailCsvForm" style="display:inline;">
    <button type="submit" name="email_csv" style="background:#ff6600; color:white; padding:10px 20px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">
        ✉️ Email CSV
    </button>
</form>

</div>

      <form id="routeForm" name="routeForm" method="POST" onsubmit="return validateRouteForm()">

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
<a href="manage_routes.php?delete_id=<?= (int)$row['id']; ?>" onclick="return confirmDelete(event);">Delete</a>




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
    <form method="POST" id="updateRouteForm">
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
<!-- Loading animation -->
<div id="loading" style="
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(255, 255, 255, 0.7);
    z-index: 9999;
    text-align: center;
    justify-content: center;
    align-items: center;
">
    <div style="margin-top: 20%;">
        <div class="spinner" style="
            border: 6px solid #f3f3f3;
            border-top: 6px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
            margin: auto;
        "></div>
        <p style="font-size:18px; color:#333;">Sending email, please wait...</p>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

</body>
</html>
