<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('owner');

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$page_title = 'Notifications Log';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Notifications Log</h2><p>Automated Email &amp; WhatsApp alerts sent to your account.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <?php if (empty($notifications)): ?>
                <p class="footer-note">No notifications yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Type</th><th>Message</th><th>Channel</th><th>Sent</th></tr></thead>
                <tbody>
                <?php foreach ($notifications as $n): ?>
                    <tr>
                        <td><?= h(ucwords(str_replace('_', ' ', $n['type']))) ?></td>
                        <td><?= h($n['message']) ?></td>
                        <td><?= h(ucfirst($n['channel'])) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
