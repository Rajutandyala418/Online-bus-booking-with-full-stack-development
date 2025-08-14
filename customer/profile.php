<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../include/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle update request
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $user_id);
    if ($stmt->execute()) {
        $message = "Profile updated successfully!";
        // Update session variables if needed
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
    } else {
        $message = "Update failed! Please try again.";
    }
    $stmt->close();
}

// Fetch user details for display
$stmt = $conn->prepare("SELECT username, first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$username = htmlspecialchars($user['username']);
$first_name = htmlspecialchars($user['first_name']);
$last_name = htmlspecialchars($user['last_name']);
$email = htmlspecialchars($user['email']);
$phone = htmlspecialchars($user['phone']);

$display_name = !empty($first_name) ? $first_name : $username;
$profile_initial = strtoupper(substr($first_name, 0, 1)) ?: strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Profile</title>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Poppins', sans-serif;
            color: white;
            background: #111;
            position: relative;
            min-height: 100vh;
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .top-nav {
            position: absolute;
            top: 20px; right: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #00bfff;
            font-weight: 600;
        }
        .profile-menu {
            position: relative;
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
            font-size: 1.2rem;
            color: white;
            user-select: none;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            background: rgba(0,0,0,0.85);
            border-radius: 6px;
            min-width: 150px;
            z-index: 10;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }
        .dropdown-content a {
            display: block;
            padding: 10px 12px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        .dropdown-content a:hover {
            background: rgba(255,255,255,0.1);
        }
        .container {
            max-width: 700px;
            margin: 100px auto 50px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 30px;
            box-sizing: border-box;
        }
        h1 {
            color: #00bfff;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 700;
        }
        form table {
            width: 100%;
            border-collapse: collapse;
            color: white;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }
        th {
            width: 30%;
            color: #00bfff;
        }
        input[type="text"], input[type="email"] {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
            box-sizing: border-box;
        }
        input[readonly] {
            background: rgba(255, 255, 255, 0.2);
            cursor: default;
            color: #ccc;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 14px;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 8px;
            border: none;
            background: linear-gradient(90deg, #00bfff, #1e90ff);
            color: white;
            cursor: pointer;
            transition: background 0.3s ease;
            user-select: none;
        }
        button:hover {
            background: linear-gradient(90deg, #1e90ff, #00bfff);
        }
        .message {
            margin-top: 10px;
            text-align: center;
            font-weight: 600;
            color: #0ff;
            text-shadow: 0 0 5px #0ff;
        }
        a.back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #00bfff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        a.back-link:hover {
            color: #1e90ff;
        }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4" />
</video>

<div class="top-nav">
    <span>Welcome, <?php echo htmlspecialchars($display_name); ?></span>
    <div class="profile-menu">
        <div class="profile-circle" id="profileBtn"><?php echo $profile_initial; ?></div>
        <div class="dropdown-content" id="dropdownMenu">
            <a href="update_password.php?username=<?php echo urlencode($username); ?>">Settings</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="container">
    <h1>Your Profile Details</h1>
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <table>
            <tr>
                <th>Username</th>
                <td><input type="text" name="username" value="<?php echo $username; ?>" readonly></td>
            </tr>
            <tr>
                <th>First Name</th>
                <td><input type="text" name="first_name" value="<?php echo $first_name; ?>" required></td>
            </tr>
            <tr>
                <th>Last Name</th>
                <td><input type="text" name="last_name" value="<?php echo $last_name; ?>" required></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><input type="email" name="email" value="<?php echo $email; ?>" required></td>
            </tr>
            <tr>
                <th>Phone</th>
                <td><input type="text" name="phone" value="<?php echo $phone; ?>" required pattern="[0-9]{10}" title="Enter 10-digit phone number"></td>
            </tr>
        </table>
        <button type="submit">Update Profile</button>
    </form>
    <a href="dashboard.php" class="back-link">Back to Dashboard</a>
</div>

<script>
    // Profile dropdown toggle
    const profileBtn = document.getElementById('profileBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');

    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function() {
        dropdownMenu.style.display = 'none';
    });
</script>

</body>
</html>
