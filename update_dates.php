<?php
$host = "sql100.infinityfree.com";
$user = "if0_39715641";
$pass = "Raju817946"; // ⚠️ Better to reset this password in vPanel
$db   = "if0_39715641_varahi_bus_booking";

// Connect DB
$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("DB Connection Failed: " . $mysqli->connect_error);
}

// Shift past dates forward by 32 days
$sql = "
    UPDATE schedules 
    SET travel_date = DATE_ADD(travel_date, INTERVAL 32 DAY)
    WHERE travel_date < CURDATE()
";

if ($mysqli->query($sql) === TRUE) {
    echo "✅ Dates updated successfully!";
} else {
    echo "❌ Error: " . $mysqli->error;
}

$mysqli->close();
?>
