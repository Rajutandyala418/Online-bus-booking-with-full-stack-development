<?php
session_start();
include(__DIR__ . '/../include/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepare and execute query to get user info
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password (assuming passwords are hashed)
        if (password_verify($password, $user['password'])) {
            // Set user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Redirect to dashboard or wherever
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
    $stmt->close();
}
?>
<!-- Your HTML login form below -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>My Bookings</title>
    <style>
        body, html { margin: 0; padding: 0; font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        .top-nav { display: flex; justify-content: flex-end; padding: 15px 30px; background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
        .top-nav span { margin-right: auto; font-weight: bold; }
        .top-nav a {
            color: white; text-decoration: none; font-weight: 600; margin-left: 20px;
            background: rgba(0,0,0,0.3); padding: 8px 15px; border-radius: 5px;
        }
        .top-nav a:hover { background: rgba(0,0,0,0.5); }
        .container { width: 90%; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table th, table td {
            padding: 12px 15px; text-align: center; border: 1px solid #ddd;
        }
        table th {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
        }
        table tr:nth-child(even) { background: #f9f9f9; }
        .no-data { text-align: center; color: #666; font-size: 1rem; margin-bottom: 30px; }
        .back-btn {
            display: block; text-align: center; text-decoration: none;
            background: #444; color: white; padding: 10px; border-radius: 5px;
            width: 200px; margin: 0 auto 20px auto;
            transition: background 0.3s;
        }
        .back-btn:hover { background: #222; }
    </style>
</head>
<body>

<div class="top-nav">
    <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">

    <h2>Upcoming Bookings</h2>
    <?php if (!empty($upcoming)): ?>
        <table>
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Seat No.</th>
                    <th>Travel Date</th>
                    <th>Departure Time</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($upcoming as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['bus_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['source']); ?></td>
                        <td><?php echo htmlspecialchars($b['destination']); ?></td>
                        <td><?php echo htmlspecialchars($b['seat_number']); ?></td>
                        <td><?php echo htmlspecialchars($b['travel_date']); ?></td>
                        <td><?php echo htmlspecialchars(substr($b['departure_time'], 0, 5)); ?></td>
                        <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($b['status'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No upcoming bookings found.</p>
    <?php endif; ?>

    <h2>Past Bookings</h2>
    <?php if (!empty($past)): ?>
        <table>
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Seat No.</th>
                    <th>Travel Date</th>
                    <th>Departure Time</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($past as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['bus_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['source']); ?></td>
                        <td><?php echo htmlspecialchars($b['destination']); ?></td>
                        <td><?php echo htmlspecialchars($b['seat_number']); ?></td>
                        <td><?php echo htmlspecialchars($b['travel_date']); ?></td>
                        <td><?php echo htmlspecialchars(substr($b['departure_time'], 0, 5)); ?></td>
                        <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($b['status'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No past bookings found.</p>
    <?php endif; ?>

    <h2>Cancelled Bookings</h2>
    <?php if (!empty($cancelled)): ?>
        <table>
            <thead>
                <tr>
                    <th>Bus Name</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Seat No.</th>
                    <th>Travel Date</th>
                    <th>Departure Time</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cancelled as $b): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($b['bus_name']); ?></td>
                        <td><?php echo htmlspecialchars($b['source']); ?></td>
                        <td><?php echo htmlspecialchars($b['destination']); ?></td>
                        <td><?php echo htmlspecialchars($b['seat_number']); ?></td>
                        <td><?php echo htmlspecialchars($b['travel_date']); ?></td>
                        <td><?php echo htmlspecialchars(substr($b['departure_time'], 0, 5)); ?></td>
                        <td><?php echo htmlspecialchars($b['booking_date']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($b['status'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-data">No cancelled bookings found.</p>
    <?php endif; ?>

    <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
</div>

</body>
</html>
