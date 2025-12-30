<?php
include(__DIR__ . '/../include/db_connect.php');

$username = $_POST['username'] ?? $_GET['username'] ?? '';
if (!$username) die("Username not provided.");

// Fetch user details
$userQuery = "SELECT id, username, email, first_name, last_name FROM users WHERE username = ? LIMIT 1";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("s", $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
$userStmt->close();
if (!$userRow) die("User not found.");

$user_id = $userRow['id'];
$today = date('Y-m-d');

// Fetch Bookings
$query = "
SELECT b.id AS booking_id, b.status, b.created_at, s.travel_date,
       bu.bus_name, bu.bus_number, b.source, b.destination, 
       s.departure_time, s.arrival_time,
       b.seat_number, IFNULL(p.amount,0) AS amount, IFNULL(p.payment_status,'N/A') AS payment_status,
       IFNULL(p.payment_method,'N/A') AS payment_method, IFNULL(p.transaction_id,'N/A') AS txn_id
FROM bookings b
JOIN schedules s ON b.schedule_id = s.id
JOIN buses bu ON s.bus_id = bu.id
LEFT JOIN payments p ON b.id = p.booking_id
WHERE b.user_id = ?
ORDER BY s.travel_date DESC, b.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Booking History</title>


<style>
body, html {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    color: white;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    overflow-x: hidden;
}


/* Top Nav */
.top-nav {
    position: fixed;
    top: 10px;
    right: 12px;
    display: flex;
    gap: 10px;
    z-index: 1000;
    color:black;
background:black;

}
.top-nav a {
    padding: 8px 12px;
    font-size: 1.9rem;
    border-radius: 6px;
}


/* MAIN CONTAINER (LARGER & CLASSY) */
.container {
    width: 100%;
    max-width: 1200px;
    margin-top: 110px;
    background: rgba(0,0,0,0.6);
    border-radius: 12px;
    padding: 20px;
    backdrop-filter: blur(6px);
}


/* Titles */
h1 {
    text-align: center;
    color: #ffde59;
    font-size: 38px;
    margin-bottom: 25px;
}

/* Filter */
label {
    font-size: 18px;
}
select {
    padding: 10px 15px;
    border-radius: 6px;
    border: none;
    margin-bottom: 15px;
    font-size: 16px;
}

/* Table Wrapper (Mobile scroll) */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    scrollbar-width: thin;
}


/* TABLE */
/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    min-width: 900px;
}
th, td {
    padding: 8px;
    font-size: 0.85rem;
    text-align: center;
}

th {
    background: linear-gradient(90deg, #ff512f, #dd2476);
    color: white;
}

/* Increase width of Action Column */
.action-col {
    min-width: 330px; /* You can increase to 260 or 280 if needed */
}

.no-data {
    text-align: center;
    color: #ffde59;
    padding: 20px;
}

/* Actions */
button.action-btn {
    padding: 6px 10px;
    font-size: 0.75rem;
    border-radius: 6px;
}

.cancel { background: red; color: white; }
.send { background: green; color: white; }
.download { background: blue; color: white; }

td.action-col div { 
    display: flex; 
    gap: 5px; 

    justify-content: center; 
    flex-wrap: wrap;
}

/* Message box */
.message-box {
    padding:12px; 
    margin-bottom:15px;
    border-radius:5px;
    text-align:center;
    font-size: 18px;
    font-weight: 600;
}
.message-success { background:green; color:white; }
.message-error { background:red; color:white; }

/* Loader */
#loader {
    display: none; /* Hidden by default */
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.7);
    z-index:9999;
    justify-content:center;
    align-items:center;
    flex-direction:column;
}
#loader img {
    width: 150px;
    margin-bottom: 15px;
    border-radius: 8px;
}


@media (max-width: 480px) {

    h1 {
        font-size: 22px;
        margin-bottom: 15px;
    }

    table {
        min-width: 760px;
    }

    .top-nav a {
        font-size: 0.75rem;
        padding: 6px 10px;
    }

    button.action-btn {
        width: 80%;
        font-size: 0.75rem;
        padding: 6px;
        border-radius: 4px;
    }

    .container {
        padding: 12px;
        width: 95%;
        margin-top: 95px;
    }

    select {
        width: 100%;
        padding: 8px;
        font-size: 0.9rem;
    }

    th, td {
        font-size: 0.78rem;
        padding: 6px;
    }

}

</style>
</head>
<body>

<div id="loader">
    <img src="https://i0.wp.com/cdn.dribbble.com/users/3593902/screenshots/8852136/media/d3a23c17a7b22b92084e0b202b46fb72.gif">
    <p>Hold your breath...</p>
</div>

<div class="top-nav">
    <a href="dashboard.php?username=<?= urlencode($username) ?>">Dashboard</a>
</div>

<div class="container">

<?php
if (isset($_GET['mail_status'])) {
    $cls = $_GET['mail_status'] === 'success' ? 'message-success' : 'message-error';
    $msg = $_GET['mail_status'] === 'success' ? 'Email sent successfully!' : 'Failed to send email!';
    echo "<div class='message-box {$cls}'>{$msg}</div>";
}
?>

<h1>Booking History</h1>

<label for="filter">Filter by Status:</label>
<select id="filter" onchange="filterTable()">
    <option value="all">All</option>
    <option value="upcoming">Upcoming</option>
    <option value="past">Past</option>
    <option value="cancelled">Cancelled</option>
</select>

<div class="table-wrapper">
<table id="bookingTable">
<tr>
    <th>Booking ID</th>
    <th>Bus</th>
    <th>Route</th>
    <th>Travel Date</th>
    <th>Status</th>
    <th>Amount</th>
    <th>Payment</th>
    <th>Booked On</th>
    <th class="action-col">Action</th>
</tr>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            $statusClass = ($row['status'] == 'cancelled') ? 'cancelled' :
                           (($row['travel_date'] >= $today) ? 'upcoming' : 'past');
        ?>
        <tr class="row <?= $statusClass ?>">
            <td><?= $row['booking_id'] ?></td>
            <td><?= htmlspecialchars($row['bus_name']) ?></td>
            <td><?= htmlspecialchars($row['source']) ?> → <?= htmlspecialchars($row['destination']) ?></td>
            <td><?= $row['travel_date'] ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['amount'] ?></td>
            <td><?= $row['payment_status'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td class="action-col">
                <div>
                    <?php if ($row['status'] !== 'cancelled'): ?>
                    <form method="POST" action="cancel_ticket.php" style="margin:0;">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button class="action-btn cancel">Cancel</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" action="send_ticket.php" style="margin:0;">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                        <button class="action-btn send">Send</button>
                    </form>

                    <form method="GET" action="download_ticket1.php" style="margin:0;">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                        <button class="action-btn download">Download</button>
                    </form>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
<tr><td colspan="9" class="no-data">No bookings found.</td></tr>
<?php endif; ?>

</table>
</div>
</div>

<script>
// Filtering Function
function filterTable() {
    let value = document.getElementById('filter').value;
    document.querySelectorAll('#bookingTable .row').forEach(row => {
        row.style.display = (value === 'all' || row.classList.contains(value)) ? '' : 'none';
    });
}

document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");

    document.querySelectorAll('form[action="cancel_ticket.php"], form[action="send_ticket.php"]').forEach(form => {
        form.addEventListener("submit", () => {
            loader.style.display = "flex";
        });
    });
});
</script>

</body>
</html>
