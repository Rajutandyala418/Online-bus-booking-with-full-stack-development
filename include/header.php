<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current = basename($_SERVER['PHP_SELF']); // Get current page
?>
<header>
    <div class="nav">
        <h1>Bus Booking System</h1>
        <nav>
            <ul>
                <li><a href="/y22cm171/bus_booking/index.php" class="<?= $current == 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="/y22cm171/bus_booking/search_bus.php" class="<?= $current == 'search_bus.php' ? 'active' : '' ?>">Search Bus</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/y22cm171/bus_booking/booking_history.php" class="<?= $current == 'booking_history.php' ? 'active' : '' ?>">My Bookings</a></li>
                    <li><a href="/y22cm171/bus_booking/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/y22cm171/bus_booking/login.php" class="<?= $current == 'login.php' ? 'active' : '' ?>">Login</a></li>
                    <li><a href="/y22cm171/bus_booking/register.php" class="<?= $current == 'register.php' ? 'active' : '' ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
