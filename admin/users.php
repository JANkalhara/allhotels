<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($userId !== (int)current_user()['id']) {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$userId]);
        }
    }
    redirect('/admin/users.php');
}

$roleFilter = $_GET['role'] ?? 'all';
$sql = "SELECT * FROM users";
if (in_array($roleFilter, ['owner','customer','admin'])) {
    $sql .= " WHERE role = " . $pdo->quote($roleFilter);
}
$sql .= " ORDER BY created_at DESC";
$users = $pdo->query($sql)->fetchAll();

$page_title = 'User Management';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>User Management</h2><p>Accounts for hotel owners and customers.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <div class="checkbox-grid" style="margin-bottom:20px;">
                <a class="tag" href="?role=all" style="<?= $roleFilter==='all'?'background:var(--emerald-900);color:#fff;':'' ?>">All</a>
                <a class="tag" href="?role=owner" style="<?= $roleFilter==='owner'?'background:var(--emerald-900);color:#fff;':'' ?>">Owners</a>
                <a class="tag" href="?role=customer" style="<?= $roleFilter==='customer'?'background:var(--emerald-900);color:#fff;':'' ?>">Customers</a>
                <a class="tag" href="?role=admin" style="<?= $roleFilter==='admin'?'background:var(--emerald-900);color:#fff;':'' ?>">Admins</a>
            </div>

            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u2): ?>
                    <tr>
                        <td><?= h($u2['full_name']) ?></td>
                        <td><?= h($u2['email']) ?></td>
                        <td><?= h(ucfirst($u2['role'])) ?></td>
                        <td><?= date('d M Y', strtotime($u2['created_at'])) ?></td>
                        <td>
                            <?php if ($u2['role'] !== 'admin'): ?>
                                <form method="POST" data-confirm="Delete this account permanently?">
                                    <input type="hidden" name="user_id" value="<?= (int)$u2['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-outline btn-sm">Delete</button>
                                </form>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
