<?php
// search_buses.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include(__DIR__ . '/../include/db_connect.php');

// 1️⃣ Get search filters from POST
$source      = isset($_POST['source']) ? trim($_POST['source']) : '';
$destination = isset($_POST['destination']) ? trim($_POST['destination']) : '';
$travel_date = isset($_POST['travel_date']) ? trim($_POST['travel_date']) : '';
$username    = isset($_POST['username']) ? trim($_POST['username']) : '';

if (!$username) {
    die("❌ Username not provided.");
}

// 2️⃣ Fetch full user details
$user_id = null;
$first_name = $last_name = $email = "";

$stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email);
$stmt->fetch();
$stmt->close();

if (!$user_id) {
    die("❌ User not found.");
}

// 3️⃣ Fetch buses based on search filters
$query = "SELECT b.*, r.source, r.destination, s.travel_date, s.departure_time, s.arrival_time, s.fare 
          FROM buses b
          JOIN routes r ON b.id = r.bus_id
          JOIN schedules s ON b.id = s.bus_id
          WHERE 1=1";

$params = [];
$types  = "";

if ($source !== '') {
    $query .= " AND r.source = ?";
    $params[] = $source;
    $types   .= "s";
}
if ($destination !== '') {
    $query .= " AND r.destination = ?";
    $params[] = $destination;
    $types   .= "s";
}
if ($travel_date !== '') {
    $query .= " AND s.travel_date = ?";
    $params[] = $travel_date;
    $types   .= "s";
}


// 3️⃣ Fetch buses only if all filters are set
$buses = [];
if (!empty($source) && !empty($destination) && !empty($travel_date)) {
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
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $buses[] = $row;
    }
    $stmt->close();
} else {
    // Redirect back to dashboard if filters not set
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Buses</title>
<style>
/* Your existing CSS here */
body {
    font-family: 'Poppins', sans-serif;
    margin: 0; 
    padding: 0;
    color: white;
    background: url('/y22cm171/bus_booking/images/image3.jpeg') no-repeat center center fixed;
    background-size: cover;
}

.top-nav { position: fixed; top: 15px; right: 30px; font-weight: 600; color: #0ff; z-index: 1000; }
table { width: 100%; border-collapse: collapse; margin-top: 100px; max-width: 1300px; margin-left: auto; margin-right: auto; background: rgba(0,0,0,0.6); border-radius: 10px; overflow: hidden; }
th, td { padding: 15px; text-align: center; border-bottom: 1px solid #444; color: white; font-size:20px;}
th { background: linear-gradient(90deg, #ff512f, #dd2476); }
.book-btn { background: #ff512f; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: background 0.3s ease; }
.book-btn:hover { background: #dd2476; }
.back-btn { display: block; width: 180px; margin: 30px auto 50px; padding: 12px 20px; background: linear-gradient(90deg, #ff512f, #dd2476); color: white; text-align: center; text-decoration: none; font-weight: 700; border-radius: 25px; cursor: pointer; transition: transform 0.2s ease; }
.back-btn:hover { transform: scale(1.05); }
</style>
</head>
<body>

<div class="top-nav">
</div>

<h1 style="text-align:center; margin-top: 60px; color : black; font-size : 30px;">
    Buses Available from <?php echo htmlspecialchars($source); ?> to <?php echo htmlspecialchars($destination); ?> on <?php echo htmlspecialchars($travel_date); ?>
</h1>

<?php if (!empty($buses)): ?>
<table>
    <thead>
        <tr>
            <th>Bus Name</th>
            <th>Bus Number</th>
            <th>Route</th>
            <th>Travel Date</th>
            <th>Departure</th>
            <th>Arrival</th>
            <th>Fare (₹)</th>
            <th>Distance (km)</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($buses as $bus): ?>
        <tr>
            <td><?php echo htmlspecialchars($bus['bus_name']); ?></td>
            <td><?php echo htmlspecialchars($bus['bus_number']); ?></td>
            <td><?php echo htmlspecialchars($bus['source'] . " → " . $bus['destination']); ?></td>
            <td><?php echo htmlspecialchars($bus['travel_date']); ?></td>
            <td><?php echo htmlspecialchars($bus['departure_time']); ?></td>
            <td><?php echo htmlspecialchars($bus['arrival_time']); ?></td>
            <td>₹<?php echo htmlspecialchars($bus['fare']); ?></td>
            <td><?php echo htmlspecialchars($bus['distance_km']); ?></td>
            <td>
  <a class="book-btn" href="<?php 
    echo 'booking_form.php?schedule_id='.$bus['schedule_id'].
         '&user_id='.$user_id.
         '&username='.urlencode($username).   // ✅ Added username here
         '&bus_name='.urlencode($bus['bus_name']).
         '&bus_number='.urlencode($bus['bus_number']).
         '&route='.urlencode($bus['source'].' → '.$bus['destination']).
         '&travel_date='.urlencode($bus['travel_date']).
         '&fare='.urlencode($bus['fare']).
         '&departure='.urlencode($bus['departure_time']).
         '&arrival='.urlencode($bus['arrival_time']);
?>">Book Now</a>


            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="text-align:center; margin-top: 60px; font-size: 1.1rem; color: #ffde59;">
    No buses available on this route for the selected date.
</p>
<?php endif; ?>

<a href="dashboard.php?username=<?php echo urlencode($username); ?>" 
   class="back-btn">← Back to Search</a>



</body>
</html>
