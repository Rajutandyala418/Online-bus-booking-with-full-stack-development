<?php
// search_buses.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include(__DIR__ . '/../include/db_connect.php');

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$username = $_SESSION['username'] ?? '';

// Fetch unique sources and destinations from routes table
$sources = [];
$destinations = [];
$src_res = $conn->query("SELECT DISTINCT source FROM routes ORDER BY source ASC");
while ($row = $src_res->fetch_assoc()) {
    $sources[] = $row['source'];
}
$dest_res = $conn->query("SELECT DISTINCT destination FROM routes ORDER BY destination ASC");
while ($row = $dest_res->fetch_assoc()) {
    $destinations[] = $row['destination'];
}

// Handle AJAX get buses
if (isset($_GET['action']) && $_GET['action'] === 'get_buses') {
    $source = $_GET['source'] ?? '';
    $destination = $_GET['destination'] ?? '';
    $travel_date = $_GET['travel_date'] ?? '';

    if ($travel_date && $source && $destination) {
        $query = "
            SELECT b.bus_name, b.bus_number, s.travel_date, s.departure_time, s.arrival_time, r.fare, r.distance_km
            FROM schedules s
            JOIN buses b ON s.bus_id = b.id
            JOIN routes r ON s.route_id = r.id
            WHERE s.travel_date = ? AND r.source = ? AND r.destination = ?
            ORDER BY s.departure_time ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $travel_date, $source, $destination);
        $stmt->execute();
        $result = $stmt->get_result();
        $buses = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode(['status' => 'success', 'buses' => $buses]);
        exit();
    } else {
        echo json_encode(['status' => 'success', 'buses' => []]);
        exit();
    }
}

// Handle request submission when no buses found
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_route') {
    $source = trim($_POST['source']);
    $destination = trim($_POST['destination']);

    // Insert into user_requests table
    $stmt = $conn->prepare("INSERT INTO user_requests (user_id, first_name, last_name, email, phone, request_source, request_destination) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $email = $_SESSION['email'] ?? '';
    $phone = $_SESSION['phone'] ?? '';
    $stmt->bind_param("issssss", $user_id, $first_name, $last_name, $email, $phone, $source, $destination);
    $stmt->execute();
    $inserted = $stmt->affected_rows > 0;
    $stmt->close();

    if ($inserted) {
        echo json_encode(['status' => 'success', 'message' => 'Your request has been sent to the admin.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit request. Please try again.']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Search Buses</title>
    <style>
        /* Reset & base */
        body { margin:0; padding:0; background: #111; color: white; font-family: 'Poppins', sans-serif; }
        a { color: #0ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        /* Background video */
        .bg-video { position: fixed; top:0; left:0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }

        /* Top nav */
        .top-nav {
            position: fixed;
            top: 20px; right: 30px;
            display: flex; align-items: center; gap: 15px;
            background: rgba(0,0,0,0.6);
            padding: 5px 10px;
            border-radius: 30px;
            user-select: none;
            z-index: 10;
        }
        .profile-menu {
            position: relative;
            display: inline-block;
        }
        .profile-circle {
            width: 45px; height: 45px;
            background: #00bfff;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            background: rgba(0,0,0,0.85);
            border-radius: 6px;
            min-width: 150px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.7);
            z-index: 20;
        }
        .dropdown-content a {
            display: block;
            padding: 10px 12px;
            color: white;
            font-weight: 600;
        }
        .dropdown-content a:hover {
            background: rgba(255,255,255,0.1);
        }
        .welcome-text {
            font-weight: 600;
            color: #00bfff;
            font-size: 1.1rem;
        }

        /* Container */
        .container {
            margin: 100px auto 50px;
            max-width: 800px;
            background: rgba(0,0,0,0.7);
            padding: 25px 30px;
            border-radius: 8px;
            text-align: center;
        }

        /* Form layout: source, destination, date, button in one line */
        form#filterForm {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        form#filterForm select,
        form#filterForm input[type="date"] {
            padding: 10px;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
            min-width: 150px;
        }
        form#filterForm button {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            font-weight: 600;
            cursor: pointer;
            min-width: 140px;
        }
        form#filterForm button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }

        /* Message box */
        #messageBox {
            margin: 15px 0;
            color: #0ff;
            min-height: 24px;
            font-weight: 600;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            color: #fff;
        }
        table th, table td {
            border: 1px solid #444;
            padding: 10px;
            text-align: center;
        }
        table th {
            background: #222;
        }
        /* Popup */
        .popup {
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.8);
            display: none; align-items: center; justify-content: center;
            z-index: 1000;
        }
        .popup-content {
            background: #222;
            padding: 30px;
            border-radius: 10px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 0 10px #ffde59;
            color: #ffde59;
        }
        .popup-content p {
            margin: 10px 0;
        }
        .countdown {
            font-weight: bold;
            color: #0ff;
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4" />
</video>

<div class="top-nav">
    <span class="welcome-text">Welcome, <?php echo htmlspecialchars($first_name ?: $username); ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn"><?php echo strtoupper(substr($first_name ?: $username, 0, 1)); ?></div>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="profile.php">Profile Details</a>
		<a href="dashboard.php">Dashboard</a>

            <a href="settings1.php">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Search Buses</h1>
    <form id="filterForm" autocomplete="off">
        <select id="source" name="source" required>
            <option value="">Select Source</option>
            <?php foreach ($sources as $src): ?>
                <option value="<?php echo htmlspecialchars($src); ?>"><?php echo htmlspecialchars($src); ?></option>
            <?php endforeach; ?>
        </select>

        <select id="destination" name="destination" required>
            <option value="">Select Destination</option>
            <?php foreach ($destinations as $dest): ?>
                <option value="<?php echo htmlspecialchars($dest); ?>"><?php echo htmlspecialchars($dest); ?></option>
            <?php endforeach; ?>
        </select>

        <input type="date" id="travel_date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" required />

        <button type="button" id="searchBtn">Search Buses</button>
    </form>

    <div id="messageBox"></div>

    <table id="busesTable" style="display:none;">
        <thead>
            <tr>
                <th>Bus Name</th>
                <th>Bus Number</th>
                <th>Travel Date</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Fare (₹)</th>
                <th>Distance (km)</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Popup Modal -->
<div class="popup" id="popupModal">
    <div class="popup-content">
        <h2>Request Submitted</h2>
        <p>Your request has been sent to the admin.</p>
        <p>We will notify you once the route is available.</p>
        <p class="countdown">Redirecting in <span id="countdown">10</span> seconds...</p>
    </div>
</div>

<script>
    // Profile menu toggle
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    profileBtn.addEventListener('click', e => {
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        e.stopPropagation();
    });
    document.addEventListener('click', () => {
        dropdownMenu.style.display = 'none';
    });

    const searchBtn = document.getElementById('searchBtn');
    const source = document.getElementById('source');
    const destination = document.getElementById('destination');
    const travelDate = document.getElementById('travel_date');
    const messageBox = document.getElementById('messageBox');
    const busesTable = document.getElementById('busesTable');
    const busesTableBody = busesTable.querySelector('tbody');
    const popupModal = document.getElementById('popupModal');
    const countdownEl = document.getElementById('countdown');

    searchBtn.addEventListener('click', () => {
        const srcVal = source.value.trim();
        const destVal = destination.value.trim();
        const dateVal = travelDate.value;

        if (!srcVal || !destVal || !dateVal) {
            alert('Please select source, destination, and travel date.');
            return;
        }

        fetch(`search_buses.php?action=get_buses&travel_date=${encodeURIComponent(dateVal)}&source=${encodeURIComponent(srcVal)}&destination=${encodeURIComponent(destVal)}`)
            .then(res => res.json())
            .then(data => {
                busesTableBody.innerHTML = '';
                if (data.buses && data.buses.length > 0) {
                    busesTable.style.display = 'table';
                    messageBox.textContent = '';
                    data.buses.forEach(bus => {
                        busesTableBody.innerHTML += `
                            <tr>
                                <td>${bus.bus_name}</td>
                                <td>${bus.bus_number}</td>
                                <td>${bus.travel_date}</td>
                                <td>${bus.departure_time}</td>
                                <td>${bus.arrival_time}</td>
                                <td>${bus.fare}</td>
                                <td>${bus.distance_km}</td>
                            </tr>
                        `;
                    });
                } else {
                    busesTable.style.display = 'none';
                    messageBox.textContent = 'No buses found for this route. You can request this route to be added.';
                    showRequestPrompt(srcVal, destVal);
                }
            })
            .catch(() => {
                messageBox.textContent = 'Error fetching bus data. Please try again later.';
                busesTable.style.display = 'none';
            });
    });

    function showRequestPrompt(source, destination) {
        if (confirm(`No buses found for ${source} to ${destination}. Would you like to send a request to add this route?`)) {
            sendRequest(source, destination);
        }
    }

    function sendRequest(source, destination) {
        const formData = new URLSearchParams();
        formData.append('action', 'request_route');
        formData.append('source', source);
        formData.append('destination', destination);

        fetch('search_buses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showPopup();
            } else {
                alert(data.message || 'Failed to send request.');
            }
        })
        .catch(() => {
            alert('Error sending request. Please try again.');
        });
    }

    function showPopup() {
        popupModal.style.display = 'flex';
        let counter = 10;
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
