<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('owner');

$user = current_user();
$stmt = $pdo->prepare("
    SELECT r.*, h.name AS hotel_name, u.full_name AS customer_name
    FROM reviews r
    JOIN hotels h ON h.id = r.hotel_id
    JOIN users u ON u.id = r.user_id
    WHERE h.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$user['id']]);
$reviews = $stmt->fetchAll();

$page_title = 'Customer Reviews';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Customer Reviews</h2><p>Feedback left across all of your listed hotels.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <?php if (empty($reviews)): ?>
                <p class="footer-note">No reviews yet.</p>
            <?php else: foreach ($reviews as $r): ?>
                <div class="review">
                    <div class="stars"><?= star_html($r['rating']) ?></div>
                    <p><?= nl2br(h($r['comment'])) ?></p>
                    <div class="reviewer"><?= h($r['customer_name']) ?> on <strong><?= h($r['hotel_name']) ?></strong> <span class="date">— <?= date('d M Y', strtotime($r['created_at'])) ?></span></div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
