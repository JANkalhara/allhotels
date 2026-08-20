<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$activeListings = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status='approved'")->fetchColumn();
$pendingApprovals = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status='pending'")->fetchColumn();
$totalOwners = $pdo->query("SELECT COUNT(*) FROM users WHERE role='owner'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

$recentHotels = $pdo->query("SELECT h.*, u.full_name AS owner_name FROM hotels h JOIN users u ON u.id = h.user_id ORDER BY h.created_at DESC LIMIT 6")->fetchAll();

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Platform Overview</h2><p>System-wide analytics for AllHotels.lk.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/../admin/_nav.php'; ?>
        <div>
            <div class="stat-grid">
                <div class="stat-card"><div class="num"><?= $activeListings ?></div><div class="label">Active Listings</div></div>
                <div class="stat-card"><div class="num"><?= $pendingApprovals ?></div><div class="label">Pending Approvals</div></div>
                <div class="stat-card"><div class="num"><?= $totalOwners ?></div><div class="label">Hotel Owners</div></div>
                <div class="stat-card"><div class="num"><?= $totalCustomers ?></div><div class="label">Customers</div></div>
                <div class="stat-card"><div class="num"><?= $totalBookings ?></div><div class="label">Total Bookings</div></div>
                <div class="stat-card"><div class="num"><?= $totalReviews ?></div><div class="label">Total Reviews</div></div>
            </div>

            <div class="panel">
                <h3>Recently Submitted Hotels</h3>
                <table class="data-table">
                    <thead><tr><th>Hotel</th><th>Owner</th><th>Status</th><th>Submitted</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentHotels as $h2): ?>
                        <tr>
                            <td><?= h($h2['name']) ?></td>
                            <td><?= h($h2['owner_name']) ?></td>
                            <td><span class="status-pill status-<?= h($h2['status']) ?>"><?= h($h2['status']) ?></span></td>
                            <td><?= date('d M Y', strtotime($h2['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="../admin/hotels.php" class="btn btn-outline btn-sm" style="margin-top:14px;">Manage All Hotels</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
