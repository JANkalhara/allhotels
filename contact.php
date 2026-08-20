<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
        $success = 'Thanks for reaching out! Our team will get back to you shortly.';
    }
}

$page_title = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container section static-page">
    <h1>Contact Us</h1>
    <p class="lead">Questions about a listing, a booking, or partnering with AllHotels.lk? Send us a message.</p>

    <div class="contact-layout">
        <div class="panel">
            <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
            <form method="POST">
                <div class="form-group"><label for="name">Your Name</label><input type="text" id="name" name="name" required></div>
                <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" required></div>
                <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" rows="5" required></textarea></div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
        <div class="panel">
            <h3>Reach Us Directly</h3>
            <div class="info-row"><div class="label">Email</div><div>support@allhotels.lk</div></div>
            <div class="info-row"><div class="label">Phone</div><div>+94 11 234 5678</div></div>
            <div class="info-row"><div class="label">Office</div><div>Colombo, Sri Lanka</div></div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
