<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<div class="dash-nav">
    <div class="who">Admin Panel</div>
    <a href="../admin/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="../admin/hotels.php" class="<?= $current === 'hotels.php' ? 'active' : '' ?>">Hotels Control</a>
    <a href="../admin/users.php" class="<?= $current === 'users.php' ? 'active' : '' ?>">User Management</a>
    <a href="../admin/bookings-reviews.php" class="<?= $current === 'bookings-reviews.php' ? 'active' : '' ?>">Bookings &amp; Reviews</a>
    <a href="../admin/messages.php" class="<?= $current === 'messages.php' ? 'active' : '' ?>">Inquiries</a>
    <a href="../auth/logout.php" class="logout">Logout</a>
</div>
