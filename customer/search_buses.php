<?php
// search_buses.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include(__DIR__ . '/../include/db_connect.php');
require __DIR__ . '/../include/php_mailer/PHPMailer.php';
require __DIR__ . '/../include/php_mailer/SMTP.php';
require __DIR__ . '/../include/php_mailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ----------------- Autocomplete endpoint -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'], $_POST['type']) && !isset($_POST['action'])) {
    $query = trim($_POST['query']);
    $type = $_POST['type'] === 'destination' ? 'destination' : 'source';

    $stmt = $conn->prepare("SELECT DISTINCT `$type` FROM routes WHERE `$type` LIKE CONCAT('%', ?, '%') ORDER BY `$type` ASC LIMIT 10");
    if (!$stmt) exit;
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<p>" . htmlspecialchars($row[$type]) . "</p>";
    }
    $stmt->close();
    exit;
}

// ----------------- Page logic -----------------
$username = $_REQUEST['username'] ?? '';
if (!$username) die("Username not provided.");

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();
if (!$user_id) die("User not found.");

// ----------------- Handle bus search -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'search_buses') {
    header('Content-Type: application/json');

    $source = trim($_POST['source'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date_option = $_POST['date_option'] ?? 'certain';
    $travel_date = trim($_POST['travel_date'] ?? '');

    if (!$source || !$destination) {
        echo json_encode(['status' => 'error', 'message' => 'Missing values']);
        exit;
    }

    if ($date_option === 'certain' && !$travel_date) {
        echo json_encode(['status' => 'error', 'message' => 'Please select a date']);
        exit;
    }

    if ($date_option === 'all') {
        // 🔹 Show all buses between source & destination
        $stmt = $conn->prepare("
            SELECT 
                s.id AS schedule_id,
                b.bus_name,
                b.bus_number,
                r.source,
                r.destination,
                r.fare,
                s.travel_date,
                s.departure_time,
                s.arrival_time,
                r.distance_km
            FROM schedules s
            JOIN buses b ON s.bus_id = b.id
            JOIN routes r ON s.route_id = r.id
            WHERE LOWER(r.source) = LOWER(?)
              AND LOWER(r.destination) = LOWER(?)
            ORDER BY s.travel_date ASC, r.fare ASC
        ");
        $stmt->bind_param("ss", $source, $destination);
    } else {
        // 🔹 Filter by selected date
        $stmt = $conn->prepare("
            SELECT 
                s.id AS schedule_id,
                b.bus_name,
                b.bus_number,
                r.source,
                r.destination,
                r.fare,
                s.travel_date,
                s.departure_time,
                s.arrival_time,
                r.distance_km
            FROM schedules s
            JOIN buses b ON s.bus_id = b.id
            JOIN routes r ON s.route_id = r.id
            WHERE LOWER(r.source) = LOWER(?)
              AND LOWER(r.destination) = LOWER(?)
              AND s.travel_date = ?
            ORDER BY r.fare ASC
        ");
        $stmt->bind_param("sss", $source, $destination, $travel_date);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $buses = [];
    while ($row = $result->fetch_assoc()) {
        $buses[] = $row;
    }
    $stmt->close();

    if (count($buses) > 0) {
        echo json_encode(['status' => 'success', 'buses' => $buses]);
    } else {
        echo json_encode(['status' => 'no_buses']);
    }
    exit;
}

// ----------------- Handle user request submission -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_route') {
    header('Content-Type: application/json');

    $source = trim($_POST['source'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $travel_date = trim($_POST['travel_date'] ?? '');

    if (!$user_id || !$source || !$destination || !$travel_date) {
        echo json_encode(['status' => 'error', 'message' => 'Missing values']);
        exit;
    }

    $ins = $conn->prepare("
        INSERT INTO user_requests 
        (user_id, first_name, last_name, email, phone, request_source, request_destination, travel_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("isssssss", $user_id, $first_name, $last_name, $email, $phone, $source, $destination, $travel_date);
    $ins->execute();
    $inserted = $ins->affected_rows > 0;
    $ins->close();

    if ($inserted) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'varahibusbooking@gmail.com';
            $mail->Password = 'pjhg nwnt haac nsiu';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('varahibusbooking@gmail.com', 'Varahi Bus');
            $mail->addAddress($email, trim($first_name . ' ' . $last_name));
            $mail->isHTML(true);
            $mail->Subject = "Bus Request Submitted Successfully";
            $mail->Body = "
                <p>Dear " . htmlspecialchars($first_name) . ",</p>
                <p>Your request from <strong>" . htmlspecialchars($source) . "</strong> to <strong>" . htmlspecialchars($destination) . "</strong> on <strong>" . htmlspecialchars($travel_date) . "</strong> has been sent to the admin successfully.</p>
                <p>Waiting for approval from the admin.</p>
                <p>Thank you,<br>Varahi Bus</p>
            ";
            $mail->send();
        } catch (Exception $e) {}

        echo json_encode(['status' => 'success', 'message' => '✅ Your request has been sent to the admin.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => '❌ Failed to submit request.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Buses</title>
<style>
html, body {
    margin: 0;
    padding: 0;
    font-family: Poppins, sans-serif;
    color: white;
    min-height: 100vh;
    background: rgba(0,0,0,0.55);
    overflow-x: hidden;
}
.top-nav {
    position: fixed;
    top: 12px;
    right: 12px;
    display: flex;
    gap: 8px;
    background: rgba(0,0,0,0.55);
    padding: 10px 14px;
    border-radius: 25px;
    z-index: 1200;
}
.top-nav a {
    font-size: 0.85rem;
    padding: 6px 10px;
}

a { color:#0ff; text-decoration:none; } a:hover { text-decoration:underline; }
.container {
    width: 94%;
    max-width: 1100px;
    margin: 90px auto 40px;
    background: rgba(0,0,0,0.65);
    border-radius: 12px;
    padding: 18px;
    backdrop-filter: blur(6px);
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
    align-items: flex-end;
}

.autocomplete-wrapper { flex:1; min-width:180px; position:relative; }
input[type="text"], input[type="date"], button, select {
    padding: 10px;
width:90%;
    font-size: 0.9rem;
}
button { background: linear-gradient(90deg,#ff512f,#dd2476); color:white; font-weight:600; cursor:pointer; transition: background 0.3s ease; }
button:hover { background: linear-gradient(90deg,#dd2476,#ff512f); }
label { display:block; margin-bottom:5px; font-weight:bold; color:blue; font-size:20px; background:white; padding:0px 1px; border-radius:4px; width:80%;}
.suggestions { position:absolute; top:100%; left:0; right:0; background:white; color:black; border:1px solid #ccc; border-top:none; max-height:150px; overflow-y:auto; display:none; z-index:1000; border-radius:0 0 6px 6px; }
.suggestions p { padding:8px 12px; cursor:pointer; margin:0; }
.suggestions p:hover { background:#00bfff; color:white; }
.popup { position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); display:none; align-items:center; justify-content:center; z-index:1000; }
.popup-content { background:#222; padding:30px; border-radius:10px; max-width:400px; text-align:center; box-shadow:0 0 10px #ffde59; color:#ffde59; }
.countdown { font-weight:bold; color:#0ff; }
.bus-table { width:100%; margin-top:20px; background:white; color:black; border-collapse:collapse; }
.bus-table th, .bus-table td {
    padding: 8px;
    font-size: 0.85rem;
    white-space: nowrap;
}

.bus-table th { background:#333; color:white; }

/* Loader styles */
#loader {
    display: none; /* hidden by default */
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.7);
    justify-content: center;
    align-items: center;
    z-index: 9999;
    flex-direction: column;
    color: white;
    font-size: 1.2rem;
    pointer-events: all;
}
#loader img {
    width: 100px;
    height: 100px;
    margin-bottom: 15px;
}
body.loading *:not(#loader) {
    pointer-events: none;
    opacity: 0.5;
}
/* Increase size of the Date Option dropdown */
#date_option {
    height: 45px;         
    font-size: 18px;      
    padding: 10px 15px;    
    border-radius: 8px;  
width : 210px;  
    border: none;          
    background: white;     
    color: black;         
    cursor: pointer;
}
#date_wrapper {
    transition: opacity 0.2s ease;
}
#date_wrapper.hidden {
    visibility: hidden;
    opacity: 0;
    height: 0;
    margin: 0;
    padding: 0;
}

@media (max-width: 768px) {

    h1 { font-size: 28px; }
    input[tpye="text"]{
        width:98%;
    }
    .container {
        width: 90%;
        padding: 14px;
        margin-top: 80px;
    }

    .top-nav {
        right: 8px;
        top: 8px;
        padding: 8px 10px;
    }

    .top-nav a {
        font-size: 0.75rem;
        padding: 6px 8px;
    }

    .filter-row {
        gap: 10px;
    }

    .autocomplete-wrapper {
        min-width: 100%;
    }

    .bus-table {
        min-width: 900px; /* force scroll rather than squeezing */
    }

    button { font-size: 0.85rem; }

    #date_option {
        width: 100%;
        font-size: 0.85rem;
        height: 40px;
    }
}

label,
input,
select,
button {
    width: 100%;
}

.autocomplete-wrapper {
    width: 100%;
}

</style>
</head>
<body>
<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif" alt="Loading..." />
    <p>Sending your request, please wait...</p>
</div>
<div class="top-nav">
    <a href="dashboard.php?username=<?= urlencode($username) ?>">Back to Dashboard</a>
</div>

<div class="container">
    <h1>Search Buses</h1>
    <form id="filterForm" autocomplete="off">
        <div class="filter-row">
            <div class="autocomplete-wrapper">
                <label for="source">Source</label>
                <input type="text" id="source" name="source" placeholder="Enter Source" required>
            </div>
            <div class="autocomplete-wrapper">
                <label for="destination">Destination</label>
                <input type="text" id="destination" name="destination" placeholder="Enter Destination" required>
            </div>
    <div class="autocomplete-wrapper">
    <label for="date_option">Date Option</label>
    <select id="date_option" name="date_option" required>
        <option value="certain">Certain Date</option>
        <option value="all">All Dates</option>
    </select>
</div>
<div class="autocomplete-wrapper" id="date_wrapper">
    <label for="travel_date">Select Date</label>
    <input type="date" id="travel_date" name="travel_date" min="<?= date('Y-m-d') ?>">
</div>

            <div class="autocomplete-wrapper">
                <button type="button" id="searchBtn">Search</button>
            </div>
        </div>
    </form>

    <div id="results"></div>
</div>

<div class="popup" id="popupModal">
    <div class="popup-content">
        <h2>Request Submitted</h2>
        <p>Your request has been sent to the admin.</p>
        <p>Waiting for approval from the admin.</p>
        <p class="countdown">Redirecting in <span id="countdown">10</span> seconds...</p>
    </div>
</div>

<script>
function setupAutocomplete(inputId, type) {
    const input = document.getElementById(inputId);
    const suggestionBox = document.createElement('div');
    suggestionBox.className = 'suggestions';
    input.parentNode.appendChild(suggestionBox);
    let selectedIndex = -1;

    input.addEventListener('input', () => {
        const val = input.value.trim();
        if (!val) {
            suggestionBox.style.display = 'none';
            selectedIndex = -1;
            return;
        }

        const formData = new FormData();
        formData.append('query', val);
        formData.append('type', type);

        fetch('search_buses.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(html => {
            suggestionBox.innerHTML = html;
            const items = suggestionBox.querySelectorAll('p');
            if (!items.length) {
                suggestionBox.style.display = 'none';
                return;
            }
            suggestionBox.style.display = 'block';
            selectedIndex = -1;

            items.forEach(item => {
                item.onclick = () => {
                    input.value = item.textContent;
                    suggestionBox.style.display = 'none';
                };
            });
        })
        .catch(() => suggestionBox.style.display = 'none');
    });

    input.addEventListener('keydown', e => {
        const items = suggestionBox.querySelectorAll('p');
        if (!items.length) return;

        if (e.key === 'ArrowDown') {
            selectedIndex = (selectedIndex + 1) % items.length;
            highlight(items, selectedIndex);
            e.preventDefault();
        } 
        else if (e.key === 'ArrowUp') {
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            highlight(items, selectedIndex);
            e.preventDefault();
        } 
        else if (e.key === 'Enter' && selectedIndex > -1) {
            input.value = items[selectedIndex].textContent;
            suggestionBox.style.display = 'none';
            selectedIndex = -1;
            e.preventDefault();
        }
    });

    document.addEventListener('click', e => {
        if (e.target !== input) suggestionBox.style.display = 'none';
    });

    function highlight(items, index) {
        items.forEach((it, i) => {
            it.style.background = i === index ? '#00bfff' : '';
            it.style.color = i === index ? '#fff' : '';
        });
    }
}

setupAutocomplete('source', 'source');
setupAutocomplete('destination', 'destination');

/* -------- Date Option Toggle -------- */
const dateOption = document.getElementById('date_option');
const dateWrapper = document.getElementById('date_wrapper');
const travelDateInput = document.getElementById('travel_date');

dateOption.addEventListener('change', () => {
    if (dateOption.value === 'all') {
        dateWrapper.classList.add('hidden');
        travelDateInput.value = '';
    } else {
        dateWrapper.classList.remove('hidden');
    }
});

/* -------- Search Logic -------- */
const searchBtn = document.getElementById('searchBtn');
const loader = document.getElementById('loader');
const popupModal = document.getElementById('popupModal');
const countdownEl = document.getElementById('countdown');

searchBtn.addEventListener('click', () => {
    const source = document.getElementById('source').value.trim();
    const destination = document.getElementById('destination').value.trim();
    const travel_date = travelDateInput.value.trim();
    const date_option = dateOption.value;
    const username = <?= json_encode($username) ?>;

    if (!source || !destination) {
        alert('Please fill all fields.');
        return;
    }

    if (date_option === 'certain' && !travel_date) {
        alert('Please select a travel date.');
        return;
    }

    const params = new URLSearchParams();
    params.append('action', 'search_buses');
    params.append('source', source);
    params.append('destination', destination);
    params.append('date_option', date_option);
    params.append('travel_date', travel_date);

    loader.style.display = 'flex';
    document.body.classList.add('loading');

    fetch('search_buses.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        loader.style.display = 'none';
        document.body.classList.remove('loading');

        if (data.status === 'success') {
            displayBuses(data.buses);
        } 
        else if (data.status === 'no_buses') {
            if (date_option === 'all') {
                document.getElementById('results').innerHTML = `
                    <p style="color:#ffde59;text-align:center;margin-top:15px;">
                    ❌ No buses found on this route for all dates.<br>
                    Please select a <b>certain date</b> to send a request.
                    </p>`;
            } else {
                sendBusRequest(username, source, destination, travel_date);
            }
        } 
        else {
            alert(data.message || 'Error searching buses');
        }
    })
    .catch(() => {
        loader.style.display = 'none';
        document.body.classList.remove('loading');
        alert('Error searching buses');
    });
});

/* -------- Display Buses -------- */
function displayBuses(buses) {
    let html = `<table class="bus-table">
        <tr>
            <th>Bus Name</th>
            <th>Bus Number</th>
            <th>Source</th>
            <th>Destination</th>
            <th>Date</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Fare</th>
            <th>Distance</th>
        </tr>`;

    buses.forEach(b => {
        html += `<tr>
            <td>${b.bus_name}</td>
            <td>${b.bus_number}</td>
            <td>${b.source}</td>
            <td>${b.destination}</td>
            <td>${b.travel_date}</td>
            <td>${b.departure_time}</td>
            <td>${b.arrival_time}</td>
            <td>${b.fare}</td>
            <td>${b.distance_km}</td>
        </tr>`;
    });

    html += `</table>`;
    document.getElementById('results').innerHTML = html;
}

/* -------- Send Request -------- */
function sendBusRequest(username, source, destination, travel_date) {
    if (!confirm(`No buses found. Send request for ${source} → ${destination} on ${travel_date}?`)) return;

    loader.style.display = 'flex';
    document.body.classList.add('loading');

    const params = new URLSearchParams();
    params.append('action', 'request_route');
    params.append('username', username);
    params.append('source', source);
    params.append('destination', destination);
    params.append('travel_date', travel_date);

    fetch('search_buses.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(res => res.json())
    .then(data => {
        loader.style.display = 'none';
        document.body.classList.remove('loading');

        if (data.status === 'success') {
            showPopup();
        } else {
            alert(data.message || 'Failed to send request');
        }
    })
    .catch(() => {
        loader.style.display = 'none';
        document.body.classList.remove('loading');
        alert('Error sending request');
    });
}

/* -------- Popup -------- */
function showPopup() {
    popupModal.style.display = 'flex';
    let counter = 5;
    countdownEl.textContent = counter;

    const interval = setInterval(() => {
        counter--;
        countdownEl.textContent = counter;
        if (counter <= 0) {
            clearInterval(interval);
            popupModal.style.display = 'none';
        }
    }, 1000);
}
</script>


</body>
</html>
