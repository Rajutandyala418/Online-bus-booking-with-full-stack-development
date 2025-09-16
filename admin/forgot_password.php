<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// FIXED include path so it works from admin folder
include(__DIR__ . '/../include/db_connect.php');

$message = '';
$admin_list = [];
$show_admins = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secret_key = trim($_POST['secret_key']);

    // Check secret key
    if ($secret_key === '051167') {
        $show_admins = true;
        $stmt = $conn->prepare("SELECT username, email, phone FROM admin");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $admin_list[] = $row;
        }
        $stmt->close();

        if (empty($admin_list)) {
            $message = "No admins found in the database.";
        }
    } else {
        $message = "Invalid secret key.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Admin</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
        }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -2;
        }
        .forgot-box {
            background: linear-gradient(
        135deg,
        #ff0000, #ff7f00, #ffff00, #7fff00, #00ff00
             );

            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 30px;
            color: white;
            width: 900px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            max-height: 80vh;
            overflow-y: auto;
            text-align: center;
        }
        h2 { margin-bottom: 30px; font-size:30px; color: black; }
        .form-row {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        label {
            font-weight: bold;
            color: blue;
        }
        input, button {
            padding: 10px;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
        input {
            background: rgba(255, 255, 255, 0.8);
            color: #333;
        }
        button {
            background: linear-gradient(90deg, #ff512f, #dd2476);
            color: white;
            cursor: pointer;
        }
        button:hover {
            background: linear-gradient(90deg, #dd2476, #ff512f);
        }
        .message { color: #ff8080; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }
        th {
            background: rgba(0, 0, 0, 0.4);
            color: yellow;
        }
        tr:nth-child(even) {
            background: rgba(0, 0, 0, 0.2);
        }
td{
color : blue;
font-size:20px;}
        .reset-btn {
            padding: 5px 10px;
            background: #00f7ff;
            color: black;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
        }
        .reset-btn:hover {
            background: #00eaff;
            color: #000;
        }
        a {
            display: block;
            margin-top: 10px;
            text-decoration: none;
            color: #ffde59;
            font-size: 0.9rem;
        }
        a:hover { text-decoration: underline; }
        .back-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            background: black;
            color: blue;
            padding: 10px 20px;
            border: 2px solid #00f7ff;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease-in-out;
        }
        .back-btn:hover {
            background: green;
            color: black;
        }
        .search-bar {
            margin-top: 15px;
            padding: 8px;
            width: 100%;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
    </style>
</head>
<body>
<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<a href="login.php" class="back-btn">Back to Login Page</a>

<div class="forgot-box">
    <h2>Forgot Password</h2>
    <?php if ($message): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Secret Key Form -->
    <?php if (!$show_admins): ?>
        <form method="post" autocomplete="off">
            <div class="form-row">
                <label for="secret_key">Secret Key:</label>
                <input type="password" name="secret_key" id="secret_key" placeholder="Enter Secret Key" required>
                <br><br>
<button type="submit">Validate</button>
            </div>
        </form>
    <?php endif; ?>

    <!-- Display Admin Table -->
    <?php if ($show_admins && !empty($admin_list)): ?>
        <input type="text" id="search" class="search-bar" placeholder="Search by username...">
        <table id="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admin_list as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td><?php echo htmlspecialchars($admin['phone']); ?></td>
                        <td>
                            <a class="reset-btn" href="reset_password.php?username=<?php echo urlencode($admin['username']); ?>">
                                Reset Password
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    // Search filter
    document.getElementById('search')?.addEventListener('keyup', function () {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('#admin-table tbody tr').forEach(function (row) {
            let username = row.cells[0].textContent.toLowerCase();
            row.style.display = username.includes(filter) ? '' : 'none';
        });
    });
</script>
</body>
</html>
