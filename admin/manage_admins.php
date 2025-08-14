<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . '/../include/db_connect.php');


if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$current_admin_id = $_SESSION['admin_id'];
$message = '';

// Delete admin (with prepared statement)
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    if ($delete_id !== $current_admin_id) {
        $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $message = "Admin account deleted successfully.";
        } else {
            $message = "Error deleting admin account.";
        }
        $stmt->close();
    } else {
        $message = "You cannot delete your own account.";
    }
}

// Fetch all admins ordered by id ascending
$admins = $conn->query("SELECT * FROM admin ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Admins</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; font-family: 'Poppins', sans-serif; }
        .bg-video { position: fixed; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .top-nav { position: absolute; top: 20px; right: 30px; display: flex; gap: 20px; }
        .top-nav a { text-decoration: none; color: white; font-weight: 600; background: rgba(0,0,0,0.5); padding: 10px 18px; border-radius: 5px; font-size: 1rem; transition: background 0.3s; }
        .top-nav a:hover { background: rgba(0,0,0,0.8); }
        .container { position: relative; top: 120px; margin: auto; width: 100%; max-width: 1300px; background: rgba(0, 0, 0, 0.6); color: white; padding: 30px; border-radius: 10px; overflow-x: auto; }
        h1 { text-align: center; color: #ffde59; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; white-space: nowrap; }
        th { background: linear-gradient(90deg, #ff512f, #dd2476); color: white; }
        tr:hover { background: rgba(255, 255, 255, 0.1); }
        .btn-delete { background: red; color: white; padding: 5px 10px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; }
        .btn-delete:hover { background: darkred; }
        .message { text-align: center; color: #00ff88; font-weight: bold; margin-top: 10px; }
        .error { text-align: center; color: #ff8080; font-weight: bold; margin-top: 10px; }
        .badge { background: #ffde59; color: black; padding: 2px 6px; border-radius: 4px; font-size: 0.9rem; }
    </style>
</head>
<body>

<video autoplay muted loop playsinline class="bg-video">
    <source src="../videos/bus.mp4" type="video/mp4">
</video>

<div class="top-nav">
    <a href="dashboard.php">Dashboard</a>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <h1>Manage Admins</h1>
    <?php if ($message): ?>
        <p class="<?php echo (strpos($message, 'successfully') !== false) ? 'message' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if ($admins && $admins->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Secret Key</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Action</th>
            </tr>
            <?php while ($admin = $admins->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $admin['id']; ?></td>
                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                    <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email']); ?></td>
                    <td><?php echo htmlspecialchars($admin['phone']); ?></td>
                    <td><?php echo htmlspecialchars($admin['secret_key']); ?></td>
                    <td><?php echo htmlspecialchars($admin['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($admin['updated_at']); ?></td>
                    <td>
                        <?php if ($admin['id'] !== $current_admin_id): ?>
                            <a class="btn-delete" href="manage_admins.php?delete=<?php echo $admin['id']; ?>" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                        <?php else: ?>
                            <span class="badge">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No admin accounts found.</p>
    <?php endif; ?>
</div>

</body>
</html>
