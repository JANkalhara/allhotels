<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('owner');

$user = current_user();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$account = $stmt->fetch();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $address  = trim($_POST['business_address'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';

    if ($fullName === '') {
        $error = 'Full name is required.';
    } else {
        if ($newPass !== '' && strlen($newPass) >= 6) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE users SET full_name=?, phone=?, whatsapp=?, business_address=?, password_hash=? WHERE id=?");
            $upd->execute([$fullName, $phone, $whatsapp, $address, $hash, $user['id']]);
        } else {
            $upd = $pdo->prepare("UPDATE users SET full_name=?, phone=?, whatsapp=?, business_address=? WHERE id=?");
            $upd->execute([$fullName, $phone, $whatsapp, $address, $user['id']]);
        }
        $_SESSION['user']['full_name'] = $fullName;
        $success = 'Account details updated successfully.';
        $account = array_merge($account, ['full_name' => $fullName, 'phone' => $phone, 'whatsapp' => $whatsapp, 'business_address' => $address]);
    }
}

$page_title = 'Account Settings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Account Settings</h2><p>Update your profile and contact information.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

            <form method="POST">
                <div class="form-group"><label for="full_name">Full Name</label><input type="text" id="full_name" name="full_name" value="<?= h($account['full_name']) ?>" required></div>
                <div class="form-group"><label>Email (fixed)</label><input type="email" value="<?= h($account['email']) ?>" disabled></div>
                <div class="form-group"><label for="phone">Contact Number</label><input type="text" id="phone" name="phone" value="<?= h($account['phone']) ?>"></div>
                <div class="form-group"><label for="whatsapp">WhatsApp Number</label><input type="text" id="whatsapp" name="whatsapp" value="<?= h($account['whatsapp']) ?>"></div>
                <div class="form-group"><label for="business_address">Business Address</label><input type="text" id="business_address" name="business_address" value="<?= h($account['business_address']) ?>"></div>
                <div class="form-group"><label for="new_password">New Password (leave blank to keep current)</label><input type="password" id="new_password" name="new_password" minlength="6"></div>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
