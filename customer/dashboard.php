<?php
$username = $_GET['username'] ?? '';
if (!$username) {
    die("Username not provided.");
}

include(__DIR__ . '/../include/db_connect.php');

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $phone);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    die("User not found.");
}

$customer_name = ($first_name && $last_name) ? htmlspecialchars($first_name . ' ' . $last_name) : 'Welcome Customer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Customer Dashboard</title>

<style>
html, body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: url('../images/image3.jpeg') no-repeat center center fixed;
    background-size: cover;
    color: white;
}

.top-nav {
    position: fixed;
    top: 10px;
    right: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    z-index: 999;
}

.profile-menu { position: relative; }

.profile-circle {
    width: 45px;
    height: 45px;
    background: #00bfff;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: bold;
    color: white;
    border: 2px solid white;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 55px;
    right: 0;
    background: rgba(0,0,0,0.9);
    border-radius: 8px;
    min-width: 180px;
    z-index: 999;
}

.dropdown-content button,
.dropdown-content a {
    display: block;
    padding: 10px;
    text-align: left;
    color: white;
    background: transparent;
    border: none;
    width: 100%;
    cursor: pointer;
}

.dropdown-content button:hover,
.dropdown-content a:hover {
    background: rgba(255,255,255,0.1);
}

.container {
    max-width: 1100px;
    width: 95%;
    background: rgba(255,255,255,0.15);
    padding: 25px;
    margin: 120px auto;
    border-radius: 10px;
    text-align: center;
}

h1 {
    color: red;
    font-size: 2.2rem;
    margin-bottom: 25px;
}

.button-row {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.button-row button {
    padding: 20px 40px;
    border-radius: 10px;
    border: none;
    min-width: 180px;
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.btn-search { background: linear-gradient(90deg, #00bfff, #1e90ff); }
.btn-bookings { background: linear-gradient(90deg, #ff7e5f, #feb47b); }
.btn-payments { background: linear-gradient(90deg, #7b2ff7, #f107a3); }

#filterForm {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
}

.field-box { width: 260px; }

label {
    display: block;
    font-weight: bold;
    background: white;
    color: black;
    padding: 5px;
    border-radius: 5px;
    margin-bottom: 5px;
    font-size: 18px;
}

input[type="text"],
input[type="date"] {
    width: 90%;
    padding: 12px;
    border: 2px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
}

.suggestions {
    position: absolute;
    max-height: 200px;
    overflow-y: scroll;
    z-index: 2000;
    background: black;
    border-radius: 6px;
}

.suggestions div {
    padding: 8px 12px;
    cursor: pointer;
    outline: none;
}

.suggestions div:hover,
.suggestions div:focus {
    background: black;
    color: white;
}

.date-buttons {
    margin-top: 8px;
    display: flex;
    gap: 10px;
    justify-content: center;
}

.date-buttons button {
    padding: 10px 20px;
    background: black;
    border: 1px solid white;
    border-radius: 5px;
    color: white;
    cursor: pointer;
    font-weight: bold;
}

.date-buttons button:hover {
    background: #00bfff;
    color: black;
}
@media (max-width: 768px) {
    .container {
        width: 90%;
        margin-top: 30px;
        padding: 15px;
    }

    h1 {
        font-size: 1.6rem;
        margin-bottom: 18px;
    }

    .button-row {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        gap: 6px;
        flex-wrap: nowrap;
    }

    .button-row form {
        width: 33.33%;
    }

    .button-row button {
        width: 100%;
        padding: 10px 6px;
        font-size: 0.8rem;
        min-width: unset;
        border-radius: 8px;
    }

    .field-box {
        width: 100%;
    }

    .field-box input {
        width: 92%;
        font-size: .95rem;
        padding: 10px;
    }

    label { font-size: 16px; }

    .date-buttons button {
        width: 48%;
        font-size: .9rem;
    }
}

#chatBot {
    position: fixed;
    bottom: 10px;
    right: 25px;
    cursor: pointer;
    z-index: 9999;
}

.icon-circle img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: white;
    padding: 5px;
}
</style>
</head>

<body>

<div class="top-nav">
    <span style="color:black;font-weight:bold;">Welcome, <?php echo $username; ?></span>
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
        <form action="search_buses.php" method="post">
            <input type="hidden" name="username" value="<?php echo $username; ?>">
            <button class="btn-search">Search Buses</button>
        </form>
        <form action="booking_history.php" method="post">
            <input type="hidden" name="username" value="<?php echo $username; ?>">
            <button class="btn-bookings">View Bookings</button>
        </form
        ><form action="payment_history.php" method="post">
            <input type="hidden" name="username" value="<?php echo $username; ?>">
            <button class="btn-payments">Payments</button>
        </form>
    </div>

    <form id="filterForm" method="POST" action="search_bus.php" autocomplete="off">
        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">

        <div class="field-box" style="position:relative;">
            <label>Source</label>
            <input type="text" id="source" name="source" placeholder="enter source" required>
            <div id="source-suggestions" class="suggestions"></div>
        </div>

        <div class="field-box" style="position:relative;">
            <label>Destination</label>
            <input type="text" id="destination" name="destination" placeholder="enter destination" required>
            <div id="destination-suggestions" class="suggestions"></div>
        </div>

        <div class="field-box">
            <label>Travel Date</label>
            <input type="date" id="travel_date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" required>
            <div class="date-buttons">
                <button type="button" onclick="setToday()">Today</button>
                <button type="button" onclick="setTomorrow()">Tomorrow</button>
            </div>
        </div>

        <div class="field-box">
            <br><br>
            <button type="submit" style="padding:15px 40px;background:linear-gradient(90deg,#ff512f,#dd2476);border:none;border-radius:8px;color:white;font-weight:bold;cursor:pointer;">
                Search Buses
            </button>
        </div>
    </form>
</div>

<div id="chatBot" onclick="location.href='support_chat.php?username=<?php echo urlencode($username); ?>'">
    <div class="icon-circle">
        <img src="https://cdn-icons-png.flaticon.com/512/4712/4712028.png">
    </div>
</div>

<script>
const profileBtn = document.getElementById('profileBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

profileBtn.onclick = e => {
    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    e.stopPropagation();
};
document.onclick = () => dropdownMenu.style.display = 'none';

function setToday() {
    document.getElementById("travel_date").value = new Date().toISOString().split("T")[0];
}
function setTomorrow() {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    document.getElementById("travel_date").value = d.toISOString().split("T")[0];
}

function setupAutocomplete(inputId, suggestionId, type) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(suggestionId);

    input.addEventListener("input", () => {
        const val = input.value.trim();
        if (!val) return box.style.display = "none";

        fetch(`autocomplete.php?term=${val}&type=${type}`)
            .then(r => r.json())
            .then(data => {
                box.innerHTML = "";
                if (!data.length) return box.style.display = "none";

                data.forEach(item => {
                    const div = document.createElement("div");
                    div.textContent = item;
                    div.setAttribute("tabindex", "0");
                    div.onclick = () => {
                        input.value = item;
                        box.style.display = "none";
                    };
                    box.appendChild(div);
                });
                box.style.display = "block";
            });
    });

    document.addEventListener("click", e => {
        if (e.target !== input) box.style.display = "none";
    });
}

setupAutocomplete("source", "source-suggestions", "source");
setupAutocomplete("destination", "destination-suggestions", "destination");
</script>

</body>
</html>
