<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<div class="dash-nav">
    <div class="who">Owner Dashboard</div>
    <a href="../owner/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">My Profile</a>
    <a href="../owner/my-hotels.php" class="<?= $current === 'my-hotels.php' ? 'active' : '' ?>">My Hotels</a>
    <a href="../owner/add-hotel.php" class="<?= $current === 'add-hotel.php' ? 'active' : '' ?>">Add Hotel</a>
    <a href="../owner/gallery.php" class="<?= $current === 'gallery.php' ? 'active' : '' ?>">Hotel Gallery <small>(Premium)</small></a>
    <a href="../owner/bookings.php" class="<?= $current === 'bookings.php' ? 'active' : '' ?>">Bookings <small>(Premium)</small></a>
    <a href="../owner/reviews.php" class="<?= $current === 'reviews.php' ? 'active' : '' ?>">Customer Reviews</a>
    <a href="../owner/notifications.php" class="<?= $current === 'notifications.php' ? 'active' : '' ?>">Notifications Log</a>
    <a href="../owner/account-settings.php" class="<?= $current === 'account-settings.php' ? 'active' : '' ?>">Account Settings</a>
    <a href="/allhotels/auth/logout.php" class="logout">Logout</a>
</div>
