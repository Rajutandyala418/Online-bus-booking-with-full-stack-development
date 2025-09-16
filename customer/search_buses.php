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

// ----------------- Autocomplete endpoint (must run before username-check) -----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'], $_POST['type']) && !isset($_POST['action'])) {
    // Return matching sources/destinations as <p> elements (JS expects <p>)
    $query = trim($_POST['query']);
    $type = $_POST['type'] === 'destination' ? 'destination' : 'source';

    // prepare statement with LIKE '%...%'
    $stmt = $conn->prepare("SELECT DISTINCT `$type` FROM routes WHERE `$type` LIKE CONCAT('%', ?, '%') ORDER BY `$type` ASC LIMIT 10");
    if (!$stmt) {
        // fail quietly (frontend will treat as no results)
        exit;
    }
    $stmt->bind_param("s", $query);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "<p>" . htmlspecialchars($row[$type]) . "</p>";
    }
    $stmt->close();
    exit; // important: do not render the rest of the page for autocomplete requests
}

// ----------------- Page logic -----------------
// Get username from GET or POST
$username = $_REQUEST['username'] ?? '';
if (!$username) die("Username not provided.");

// Fetch full user details (based on username)
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();
if (!$user_id) die("User not found.");

// Fetch distinct sources and destinations for graceful degradation (not strictly required for dynamic autocomplete)
$sources = [];
$destinations = [];
$src_res = $conn->query("SELECT DISTINCT source FROM routes ORDER BY source ASC");
while ($row = $src_res->fetch_assoc()) $sources[] = $row['source'];
$dest_res = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination ASC");
while ($row = $dest_res->fetch_assoc()) $destinations[] = $row['destination'];

// ----------------- Handle user request submission (AJAX) -----------------
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
    $inserted = $ins->affected_rows > 0 ? true : false;
    $ins->close();

    if ($inserted) {
        // Send email to user (best-effort; failure won't break response)
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'varahibusbooking@gmail.com';      // keep as your email
            $mail->Password = 'pjhg nwnt haac nsiu';             // keep as your app password / token
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
        } catch (Exception $e) {
            // fail silently - user request insertion succeeded, we still return success
        }

        echo json_encode(['status' => 'success', 'message' => '✅ Your request has been sent to the admin.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => '❌ Failed to submit request.']);
    }
    exit;
}

// ----------------- HTML output -----------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Buses</title>
<style>
/* (kept your original styles) */
html, body { margin:0; padding:0; font-family:Poppins,sans-serif; background:url('/y22cm171/bus_booking/images/image3.jpeg') no-repeat center center fixed; background-size:cover; color:white; }
.top-nav { position:fixed; top:20px; right:30px; display:flex; align-items:center; gap:15px; background: rgba(0,0,0,0.6); padding:15px 20px; border-radius:30px; z-index:10; }
a { color:#0ff; text-decoration:none; } a:hover { text-decoration:underline; }
.container { margin:100px auto 50px; max-width:900px; background: rgba(0,0,0,0.7); padding:25px 30px; border-radius:8px; text-align:center; }
h1 { font-size:45px; color:white; margin-bottom:30px; }
.filter-row { display:flex; gap:15px; flex-wrap:wrap; justify-content:center; align-items:flex-end; }
.autocomplete-wrapper { flex:1; min-width:180px; position:relative; }
input[type="text"], input[type="date"], button { width:100%; padding:12px; border-radius:7px; border:none; font-size:1rem; box-sizing:border-box; }
button { background: linear-gradient(90deg,#ff512f,#dd2476); color:white; font-weight:600; cursor:pointer; transition: background 0.3s ease; }
button:hover { background: linear-gradient(90deg,#dd2476,#ff512f); }
label { display:block; margin-bottom:5px; font-weight:bold; color:blue; font-size:20px; background:white; padding:2px 5px; border-radius:4px; }
.suggestions { position:absolute; top:100%; left:0; right:0; background:white; color:black; border:1px solid #ccc; border-top:none; max-height:150px; overflow-y:auto; display:none; z-index:1000; border-radius:0 0 6px 6px; }
.suggestions p { padding:8px 12px; cursor:pointer; margin:0; }
.suggestions p:hover { background:#00bfff; color:white; }
.popup { position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.8); display:none; align-items:center; justify-content:center; z-index:1000; }
.popup-content { background:#222; padding:30px; border-radius:10px; max-width:400px; text-align:center; box-shadow:0 0 10px #ffde59; color:#ffde59; }
.countdown { font-weight:bold; color:#0ff; }
</style>
</head>
<body>

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
                <label for="travel_date">Travel Date</label>
                <input type="date" id="travel_date" name="travel_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="autocomplete-wrapper">
             
                <button type="button" id="searchBtn">Send Request</button>
            </div>
        </div>
    </form>
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
// Autocomplete behaviour (matches dashboard style)
function setupAutocomplete(inputId, type) {
    const input = document.getElementById(inputId);
    const suggestionBox = document.createElement('div');
    suggestionBox.className = 'suggestions';
    input.parentNode.appendChild(suggestionBox);
    let selectedIndex = -1;

    input.addEventListener('input', () => {
        const val = input.value.trim();
        if (!val) { suggestionBox.style.display = 'none'; selectedIndex = -1; return; }

        // send AJAX request for suggestions (POST contains query & type)
        const formData = new FormData();
        formData.append('query', val);
        formData.append('type', type);

        fetch('search_buses.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(html => {
            suggestionBox.innerHTML = html; // server returns <p>..</p> items
            const items = suggestionBox.querySelectorAll('p');
            if (!items.length) { suggestionBox.style.display = 'none'; return; }
            suggestionBox.style.display = 'block';
            selectedIndex = -1;
            items.forEach((item, idx) => {
                item.addEventListener('click', () => {
                    input.value = item.textContent;
                    suggestionBox.style.display = 'none';
                });
            });
        })
        .catch(() => {
            suggestionBox.style.display = 'none';
        });
    });

    input.addEventListener('keydown', (e) => {
        const items = suggestionBox.querySelectorAll('p');
        if (!items.length) return;
        if (e.key === 'ArrowDown') {
            selectedIndex = (selectedIndex + 1) % items.length;
            highlight(items, selectedIndex);
            e.preventDefault();
        } else if (e.key === 'ArrowUp') {
            selectedIndex = (selectedIndex - 1 + items.length) % items.length;
            highlight(items, selectedIndex);
            e.preventDefault();
        } else if (e.key === 'Enter') {
            if (selectedIndex > -1) {
                input.value = items[selectedIndex].textContent;
                suggestionBox.style.display = 'none';
                selectedIndex = -1;
                e.preventDefault();
            }
        }
    });

    document.addEventListener('click', (e) => {
        if (e.target !== input) suggestionBox.style.display = 'none';
    });

    function highlight(items, index) {
        items.forEach((it, i) => {
            it.style.background = i === index ? '#00bfff' : '';
            it.style.color = i === index ? 'white' : '';
        });
    }
}

setupAutocomplete('source', 'source');
setupAutocomplete('destination', 'destination');

// Send request button
const searchBtn = document.getElementById('searchBtn');
const popupModal = document.getElementById('popupModal');
const countdownEl = document.getElementById('countdown');

searchBtn.addEventListener('click', () => {
    const source = document.getElementById('source').value.trim();
    const destination = document.getElementById('destination').value.trim();
    const travel_date = document.getElementById('travel_date').value.trim();
    const username = <?= json_encode($username) ?>;

    if (!source || !destination || !travel_date) {
        alert('Please fill all fields.');
        return;
    }

    if (!confirm(`Send request for ${source} to ${destination} on ${travel_date}?`)) return;

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
        if (data.status === 'success') {
            showPopup();
        } else {
            alert(data.message || 'Failed to send request');
        }
    })
    .catch(() => alert('Error sending request'));
});

function showPopup() {
    popupModal.style.display = 'flex';
    let counter = 10;
    countdownEl.textContent = counter;
    const interval = setInterval(() => {
        counter--;
        countdownEl.textContent = counter;
        if (counter <= 0) {
            clearInterval(interval);
            window.location.href = 'dashboard.php?username=' + encodeURIComponent(<?= json_encode($username) ?>);
        }
    }, 1000);
}
</script>

</body>
</html>
