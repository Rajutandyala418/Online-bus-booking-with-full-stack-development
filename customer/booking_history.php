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
$user_email = $userRow['email'];
$user_name = $userRow['first_name'] . ' ' . $userRow['last_name'];

$today = date('Y-m-d');




// ------------------- Fetch Bookings -------------------
// ------------------- Fetch Bookings -------------------
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
ORDER BY s.travel_date DESC, b.created_at DESC
";


$stmt = $conn->prepare($query);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking History</title>
<style>
body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif;  color: white; }
.bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -2; }
.top-nav { position: absolute; top: 20px; right: 30px; display: flex; gap: 20px; }
.top-nav a { text-decoration: none; color: #0ff; font-weight: 600; background: rgba(0,0,0,0.5); padding: 10px 18px; border-radius: 5px; }
.top-nav a:hover { background: rgba(0,0,0,0.8); color: #fff; }
.container { position: relative; top: 120px; margin: auto; width: 90%; max-width: 1000px; background: rgba(0,0,0,0.6); padding: 20px; border-radius: 10px; }
h1 { text-align: center; color: #ffde59; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
th { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
select { padding: 8px; border-radius: 5px; border: none; margin-bottom: 10px; }
.no-data { text-align: center; color: #ffde59; margin: 15px 0; }
button.action-btn { padding:5px 10px; margin:2px; border:none; border-radius:5px; cursor:pointer; font-weight:600; }
th.action-col { min-width: 300px; }
td.action-col div { display: flex; gap: 5px; justify-content: center; }
button.cancel { background:red; color:white; }
button.send { background:green; color:white; }
button.download { background:blue; color:white; }
.message-box { padding:10px; margin-bottom:15px; border-radius:5px; text-align:center; color:white; }
.message-success { background:green; }
.message-error { background:red; }
</style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <a href="dashboard.php?username=<?= urlencode($username) ?>">Dashboard</a>
</div>

<div class="container">

<?php
// Display email success/error message if exists
if (isset($_GET['mail_status'])) {
    $statusClass = $_GET['mail_status'] === 'success' ? 'message-success' : 'message-error';
    $statusMsg = $_GET['mail_status'] === 'success' ? '✅ Email sent successfully!' : '❌ Failed to send email!';
    echo "<div class='message-box {$statusClass}'>{$statusMsg}</div>";
}
?>

<h1>Booking History</h1>

<label for="filter">Filter by Status: </label>
<select id="filter" onchange="filterTable()">
    <option value="all">All</option>
    <option value="upcoming">Upcoming</option>
    <option value="past">Past</option>
    <option value="cancelled">Cancelled</option>
</select>

<table id="bookingTable">
<tr>
    <th>Booking ID</th>
    <th>Bus</th>
    <th>Route</th>
    <th>Travel Date</th>
    <th>Status</th>
    <th>Amount</th>
    <th>Payment Status</th>
    <th>Booked On</th>
    <th class="action-col">Action</th>
</tr>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <?php
            $rowStatus = '';
            if ($row['status'] == 'cancelled') $rowStatus = 'cancelled';
            elseif ($row['travel_date'] >= $today) $rowStatus = 'upcoming';
            else $rowStatus = 'past';
        ?>
        <tr class="row <?= $rowStatus ?>">
            <td><?= $row['booking_id'] ?></td>
            <td><?= htmlspecialchars($row['bus_name']) ?></td>
            <td><?= htmlspecialchars($row['source']) ?> → <?= htmlspecialchars($row['destination']) ?></td>
            <td><?= $row['travel_date'] ?></td>
            <td><?= ucfirst($row['status']) ?></td>
            <td><?= $row['amount'] ?></td>
            <td><?= $row['payment_status'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td class="action-col">
                <div style="display: flex; gap: 5px; justify-content: center;">
                    <?php if ($row['status'] != 'cancelled'): ?>
              <form method="POST" action="cancel_ticket.php" style="margin:0; display:inline;">
    <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
    <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
    <input type="hidden" name="action" value="cancel">
    <button type="submit" class="action-btn cancel">Cancel</button>
</form>

                    <?php endif; ?>

                    <form method="POST" action="send_ticket.php" style="margin:0; display:inline;">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                        <button type="submit" class="action-btn send">Send Ticket</button>
                    </form>

                    <form method="GET" action="download_ticket1.php" style="margin:0; display:inline;">
                        <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                        <button type="submit" class="action-btn download">Download Ticket</button>
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

<script>
function filterTable() {
    let filter = document.getElementById("filter").value;
    let rows = document.querySelectorAll("#bookingTable .row");
    rows.forEach(row => {
        row.style.display = (filter === "all" || row.classList.contains(filter)) ? "" : "none";
    });
}
</script>

</body>
</html>
