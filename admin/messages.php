<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $pdo->prepare("UPDATE contact_messages SET is_handled = 1 WHERE id = ?")->execute([(int) $_POST['message_id']]);
    redirect('/admin/messages.php');
}

$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();

$page_title = 'Inquiries';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Inquiries &amp; Contact Messages</h2><p>Public submissions from the Contact Us page.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <?php if (empty($messages)): ?>
                <p class="footer-note">No inquiries yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><?= h($m['name']) ?></td>
                        <td><?= h($m['email']) ?></td>
                        <td><?= h($m['message']) ?></td>
                        <td><span class="status-pill status-<?= $m['is_handled'] ? 'approved' : 'pending' ?>"><?= $m['is_handled'] ? 'Handled' : 'Pending' ?></span></td>
                        <td>
                            <?php if (!$m['is_handled']): ?>
                                <form method="POST"><input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-primary btn-sm">Mark Handled</button></form>
                            <?php else: ?>—<?php endif; ?>
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
