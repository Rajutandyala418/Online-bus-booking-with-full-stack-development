<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: /y22cm171/admin/login.php");
    exit();
}
$current = basename($_SERVER['PHP_SELF']);
?>
<header>
    <div class="nav">
        <h1>Admin Panel</h1>
        <nav>
            <ul>
                <li><a href="/y22cm171/admin/dashboard.php" class="<?= $current == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="/y22cm171/admin/manage_buses.php" class="<?= $current == 'manage_buses.php' ? 'active' : '' ?>">Buses</a></li>
                <li><a href="/y22cm171/admin/manage_routes.php" class="<?= $current == 'manage_routes.php' ? 'active' : '' ?>">Routes</a></li>
                <li><a href="/y22cm171/admin/manage_schedules.php" class="<?= $current == 'manage_schedules.php' ? 'active' : '' ?>">Schedules</a></li>
                <li><a href="/y22cm171/admin/view_bookings.php" class="<?= $current == 'view_bookings.php' ? 'active' : '' ?>">Bookings</a></li>
                <li><a href="/y22cm171/admin/payment.php" class="<?= $current == 'payment.php' ? 'active' : '' ?>">Payments</a></li>
                <li><a href="/y22cm171/admin/logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>
