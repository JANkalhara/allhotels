<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([(int) $_POST['delete_review']]);
    redirect('/admin/bookings-reviews.php');
}

$bookings = $pdo->query("
    SELECT b.*, h.name AS hotel_name, u.full_name AS customer_name
    FROM bookings b JOIN hotels h ON h.id=b.hotel_id JOIN users u ON u.id=b.user_id
    ORDER BY b.created_at DESC LIMIT 50
")->fetchAll();

$reviews = $pdo->query("
    SELECT r.*, h.name AS hotel_name, u.full_name AS customer_name
    FROM reviews r JOIN hotels h ON h.id=r.hotel_id JOIN users u ON u.id=r.user_id
    ORDER BY r.created_at DESC LIMIT 50
")->fetchAll();

$page_title = 'Bookings & Reviews';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Bookings &amp; Reviews Moderation</h2><p>Monitor reservations and moderate published feedback.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div>
            <div class="panel">
                <h3>Recent Bookings</h3>
                <table class="data-table">
                    <thead><tr><th>Hotel</th><th>Customer</th><th>Date</th><th>Guests</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= h($b['hotel_name']) ?></td>
                            <td><?= h($b['customer_name']) ?></td>
                            <td><?= date('d M Y', strtotime($b['event_date'])) ?></td>
                            <td><?= (int)$b['guest_count'] ?></td>
                            <td><span class="status-pill status-<?= h($b['status']) ?>"><?= h($b['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookings)): ?><tr><td colspan="5" class="footer-note">No bookings yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h3>Recent Reviews</h3>
                <?php foreach ($reviews as $r): ?>
                    <div class="review">
                        <div class="stars"><?= star_html($r['rating']) ?></div>
                        <p><?= nl2br(h($r['comment'])) ?></p>
                        <div class="reviewer"><?= h($r['customer_name']) ?> on <strong><?= h($r['hotel_name']) ?></strong></div>
                        <form method="POST" data-confirm="Remove this review?" style="margin-top:8px;">
                            <input type="hidden" name="delete_review" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-outline btn-sm">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?><p class="footer-note">No reviews yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
