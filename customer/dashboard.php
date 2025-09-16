<?php
$username = $_GET['username'] ?? '';
if (!$username) {
    die("Username not provided.");
}
$mysqli = new mysqli('localhost', 'root', '', 'bus_booking');
if ($mysqli->connect_errno) die("DB connection failed: " . $mysqli->connect_error);

$stmt = $mysqli->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    die("User not found.");
}

$customer_name = ($first_name && $last_name) ? htmlspecialchars($first_name . ' ' . $last_name) : 'Welcome Customer';

$sources = [];
$destinations = [];
$res = $mysqli->query("SELECT DISTINCT source FROM routes ORDER BY source ASC");
while ($row = $res->fetch_assoc()) {
    $sources[] = $row['source'];
}
$res = $mysqli->query("SELECT DISTINCT destination FROM routes ORDER BY destination ASC");
while ($row = $res->fetch_assoc()) {
    $destinations[] = $row['destination'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Customer Dashboard</title>
    <style>
        html, body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
            background: url('../images/image3.jpeg') no-repeat center center fixed;
            background-size: cover;
            color: white;
        }
        .top-nav {
            position: absolute;
            top: 20px; right: 30px;
            display: flex; gap: 15px;
            align-items: center;
        }
        .profile-menu {
            position: relative; display: inline-block;
        }
        .profile-circle {
            width: 45px; height: 45px;
            background: #00bfff;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: white; font-size: 1.2rem;
            user-select: none;
        }
        .suggestions {
            background: rgba(255,255,255,0.9);
            color: black;
            position: absolute;
            max-height: 150px;
            overflow-y: auto;
            width: 200px;
            border-radius: 5px;
            display: none;
            z-index: 100;
        }
        .filter-row {
            display: flex; gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-end;
        }
        .autocomplete-wrapper {
            flex: 1;
            min-width: 180px;
            position: relative;
        }
        input[type="text"], input[type="date"], button[type="submit"] {
            width: 120%; box-sizing: border-box;
        }
        button[type="submit"] { margin-top: 0; }
        .suggestions div { padding: 8px; cursor: pointer; }
        .suggestions div:hover { background: black; color: white; }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 55px; right: 0;
            background: black;
            border-radius: 6px;
            min-width: 180px;
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }
        .dropdown-content form button {
            display: block; width: 100%;
            text-align: left;
            padding: 10px; border: none;
            background: black; color: white;
            cursor: pointer;
        }
        .dropdown-content form button:hover {
            background: rgba(255,255,255,0.1);
        }
        .dropdown-content a {
            color: white; text-decoration: none;
            display: block; padding: 10px;
        }
        .dropdown-content a:hover { background: black; }
        .container {
            position: relative;
            top: 120px;
            margin: auto;
            width: 90%; max-width: 850px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 30px; border-radius: 10px;
            text-align: center;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: black;
        }
        .button-row {
            margin-bottom: 30px;
            display: flex; justify-content: center;
            gap: 60px;
            flex-wrap: wrap;
        }
        .button-row form button {
            padding: 20px 40px;
            border-radius: 10px; border: none;
            font-weight: 600; cursor: pointer;
            min-width: 190px;
            color: white;
            transition: background 0.3s ease;
        }
        .btn-search { background: linear-gradient(90deg, #00bfff, #1e90ff); }
        .btn-search:hover { background: linear-gradient(90deg, #1e90ff, #00bfff); }
        .btn-bookings { background: linear-gradient(90deg, #ff7e5f, #feb47b); }
        .btn-bookings:hover { background: linear-gradient(90deg, #feb47b, #ff7e5f); }
        .btn-payments { background: linear-gradient(90deg, #7b2ff7, #f107a3); }
        .btn-payments:hover { background: linear-gradient(90deg, #f107a3, #7b2ff7); }
        form#filterForm {
            display: flex; gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        form#filterForm select, form#filterForm input[type="date"] {
            padding: 20px; border-radius: 5px;
            border: none; font-size: 1rem;
            min-width: 190px; color: black;
        }
        label {
            display: block; margin-bottom: 5px;
            font-weight: bold; color: black;
        }
        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 10px 10px;
            border-radius: 7px;
            border: 11px solid #ccc;
            font-size: 1rem;
        }
        button[type="submit"] {
            padding: 15px 40px;
            border-radius: 5px; border: none;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white; font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button[type="submit"]:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .suggestions {
            position: absolute;
            top: 100%; left: 0; right: 0;
            background: white; color: black;
            border: 1px solid #ccc; border-top: none;
            max-height: 150px; overflow-y: auto;
            display: none; z-index: 1000;
            border-radius: 0 0 6px 6px;
        }
        .suggestions div { padding: 8px 12px; cursor: pointer; }
        .suggestions div:hover { background: #00bfff; color: white; }
        form#filterForm button[type="submit"] {
            padding: 20px 40px;
            border-radius: 5px; border: none;
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white; font-weight: 600;
            cursor: pointer; min-width: 140px;
            transition: background 0.3s ease;
        }
        form#filterForm button[type="submit"]:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        #filterForm label {
            display: block; margin: 10px 0 5px;
            font-weight: bold; color: blue;
        }
        #filterForm input, #filterForm button { margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="top-nav">
    <span style="color:black; font-weight:bold;"><?php echo "Welcome, " .$username; ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn">
            <?php echo strtoupper(substr($first_name ?: $username, 0, 1)); ?>
        </div>
        <div class="dropdown-content" id="dropdownMenu">
            <form action="settings1.php" method="post">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                <button type="submit">Settings</button>
            </form>
            <form action="profile.php" method="post">
                <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                <button type="submit">Profile Details</button>
            </form>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
<div class="container">
    <h1>Customer Dashboard</h1>
    <div class="button-row">
        <form action="search_buses.php" method="post" style="display:inline;">
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
            <button type="submit" class="btn-search">Search Buses</button>
        </form>
        <form action="booking_history.php" method="post" style="display:inline;">
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
            <button type="submit" class="btn-bookings">View Bookings</button>
        </form>
        <form action="payment_history.php" method="post" style="display:inline;">
            <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
            <button type="submit" class="btn-payments">Payments</button>
        </form>
    </div>
    <form id="filterForm" method="POST" action="search_bus.php" autocomplete="off">
        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
        <div class="filter-row">
            <div class="autocomplete-wrapper">
                <label for="source" style="font-size:25px; background : white";>Source</label>
                <input type="text" id="source" name="source" placeholder="Enter Source" autocomplete="off" required>
                <div id="source-suggestions" class="suggestions"></div>
            </div>
            <div class="autocomplete-wrapper">
                <label for="destination" style="font-size:25px; background : white";>Destination</label>
                <input type="text" id="destination" name="destination" placeholder="Enter Destination" autocomplete="off" required>
                <div id="destination-suggestions" class="suggestions"></div>
            </div>
            <div class="autocomplete-wrapper">
                <label for="travel_date" style="font-size:25px; background : white";>Travel Date</label>
                <input type="date" id="travel_date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="autocomplete-wrapper">
                <label>&nbsp;</label>
                <button type="submit">Search Buses</button>
            </div>
        </div>
    </form>
</div>
<script>
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
profileBtn.addEventListener('click', e => {
    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    e.stopPropagation();
});
document.addEventListener('click', () => {
    dropdownMenu.style.display = 'none';
});
function setupAutocomplete(inputId, suggestionId, type) {
    const input = document.getElementById(inputId);
    const suggestionBox = document.getElementById(suggestionId);
    let selectedIndex = -1;
    input.addEventListener('input', function() {
        const val = this.value;
        if (!val) {
            suggestionBox.style.display = 'none';
            selectedIndex = -1;
            return;
        }
        fetch(`autocomplete.php?term=${encodeURIComponent(val)}&type=${type}`)
        .then(res => res.json())
        .then(data => {
            suggestionBox.innerHTML = '';
            selectedIndex = -1;
            if (data.length === 0) {
                suggestionBox.style.display = 'none';
                return;
            }
            data.forEach((item, index) => {
                const div = document.createElement('div');
                div.textContent = item;
                div.addEventListener('click', () => {
                    input.value = item;
                    suggestionBox.style.display = 'none';
                });
                suggestionBox.appendChild(div);
            });
            suggestionBox.style.display = 'block';
        });
    });
    input.addEventListener('keydown', function(e) {
        const items = suggestionBox.querySelectorAll('div');
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
    document.addEventListener('click', e => {
        if (e.target !== input) suggestionBox.style.display = 'none';
    });
    function highlight(items, index) {
        items.forEach((item, i) => {
            item.style.background = i === index ? '#00bfff' : '';
            item.style.color = i === index ? 'white' : '';
        });
    }
}
setupAutocomplete('source', 'source-suggestions', 'source');
setupAutocomplete('destination', 'destination-suggestions', 'destination');
</script>
</body>
</html>
