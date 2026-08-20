<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotelId = (int) ($_POST['hotel_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
    $stmt->execute([$hotelId]);
    $hotel = $stmt->fetch();

    if ($hotel) {
        if ($action === 'approve') {
            $pdo->prepare("UPDATE hotels SET status='approved' WHERE id=?")->execute([$hotelId]);
            notify($pdo, $hotel['user_id'], 'hotel_approved', "\"{$hotel['name']}\" has been approved and is now live.", 'both');
        } elseif ($action === 'reject') {
            $pdo->prepare("UPDATE hotels SET status='rejected' WHERE id=?")->execute([$hotelId]);
            notify($pdo, $hotel['user_id'], 'hotel_rejected', "\"{$hotel['name']}\" was rejected by the admin team.", 'both');
        } elseif ($action === 'toggle_premium') {
            $newVal = $hotel['is_premium'] ? 0 : 1;
            $pdo->prepare("UPDATE hotels SET is_premium=? WHERE id=?")->execute([$newVal, $hotelId]);
            if ($newVal) {
                notify($pdo, $hotel['user_id'], 'premium_activated', "\"{$hotel['name']}\" has been upgraded to Premium.", 'both');
            }
        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM hotels WHERE id=?")->execute([$hotelId]);
        }
    }
    redirect('../admin/hotels.php');
}

$filter = $_GET['status'] ?? 'all';
$sql = "SELECT h.*, u.full_name AS owner_name, u.email AS owner_email FROM hotels h JOIN users u ON u.id = h.user_id";
if (in_array($filter, ['pending','approved','rejected'])) {
    $sql .= " WHERE h.status = " . $pdo->quote($filter);
}
$sql .= " ORDER BY h.created_at DESC";
$hotels = $pdo->query($sql)->fetchAll();

$page_title = 'Hotels Control';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Hotels Control</h2><p>Audit, approve, reject, or manage the Premium status of listings.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <div class="checkbox-grid" style="margin-bottom:20px;">
                <a class="tag" href="?status=all" style="<?= $filter==='all'?'background:var(--emerald-900);color:#fff;':'' ?>">All</a>
                <a class="tag" href="?status=pending" style="<?= $filter==='pending'?'background:var(--emerald-900);color:#fff;':'' ?>">Pending</a>
                <a class="tag" href="?status=approved" style="<?= $filter==='approved'?'background:var(--emerald-900);color:#fff;':'' ?>">Approved</a>
                <a class="tag" href="?status=rejected" style="<?= $filter==='rejected'?'background:var(--emerald-900);color:#fff;':'' ?>">Rejected</a>
            </div>

            <?php if (empty($hotels)): ?>
                <p class="footer-note">No hotels found for this filter.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Hotel</th><th>Owner</th><th>District</th><th>Plan</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($hotels as $h2): ?>
                    <tr>
                        <td><a href="/hotel-details.php?id=<?= (int)$h2['id'] ?>" target="_blank"><?= h($h2['name']) ?></a></td>
                        <td><?= h($h2['owner_name']) ?><br><span class="footer-note"><?= h($h2['owner_email']) ?></span></td>
                        <td><?= h($h2['district']) ?></td>
                        <td><?= $h2['is_premium'] ? '★ Premium' : 'Free' ?></td>
                        <td><span class="status-pill status-<?= h($h2['status']) ?>"><?= h($h2['status']) ?></span></td>
                        <td class="table-actions">
                            <?php if ($h2['status'] !== 'approved'): ?>
                                <form method="POST"><input type="hidden" name="hotel_id" value="<?= (int)$h2['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-primary btn-sm">Approve</button></form>
                            <?php endif; ?>
                            <?php if ($h2['status'] !== 'rejected'): ?>
                                <form method="POST"><input type="hidden" name="hotel_id" value="<?= (int)$h2['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-outline btn-sm">Reject</button></form>
                            <?php endif; ?>
                            <form method="POST"><input type="hidden" name="hotel_id" value="<?= (int)$h2['id'] ?>"><input type="hidden" name="action" value="toggle_premium"><button class="btn btn-terracotta btn-sm"><?= $h2['is_premium'] ? 'Revoke Premium' : 'Make Premium' ?></button></form>
                            <form method="POST" data-confirm="Delete this hotel permanently?"><input type="hidden" name="hotel_id" value="<?= (int)$h2['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-outline btn-sm">Delete</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
