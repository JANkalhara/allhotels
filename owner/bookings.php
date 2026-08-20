<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('owner');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $bookingId = (int) $_POST['booking_id'];
    $action = $_POST['action'] === 'confirm' ? 'confirmed' : 'cancelled';

    $stmt = $pdo->prepare("
        SELECT b.*, h.user_id AS owner_id, h.name AS hotel_name FROM bookings b
        JOIN hotels h ON h.id = b.hotel_id WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if ($booking && $booking['owner_id'] == $user['id']) {
        $upd = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $upd->execute([$action, $bookingId]);

        notify($pdo, $user['id'], 'booking_update', "Booking for \"{$booking['hotel_name']}\" marked as {$action}.", 'both');
    }
    redirect('/owner/bookings.php');
}

$stmt = $pdo->prepare("
    SELECT b.*, h.name AS hotel_name, u.full_name AS customer_name, ft.name AS function_name
    FROM bookings b
    JOIN hotels h ON h.id = b.hotel_id
    JOIN users u ON u.id = b.user_id
    LEFT JOIN function_types ft ON ft.id = b.function_type_id
    WHERE h.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$page_title = 'Bookings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="section-head"><div><h2>Bookings</h2><p>Reservations made through your Premium listings.</p></div></div>

    <div class="dash-layout">
        <?php include __DIR__ . '/_nav.php'; ?>
        <div class="panel">
            <?php if (empty($bookings)): ?>
                <p class="footer-note">No bookings yet.</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Hotel</th><th>Customer</th><th>Function</th><th>Date</th><th>Guests</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= h($b['hotel_name']) ?></td>
                        <td><?= h($b['customer_name']) ?></td>
                        <td><?= h($b['function_name'] ?? '—') ?></td>
                        <td><?= date('d M Y', strtotime($b['event_date'])) ?></td>
                        <td><?= (int)$b['guest_count'] ?></td>
                        <td><span class="status-pill status-<?= h($b['status']) ?>"><?= h($b['status']) ?></span></td>
                        <td class="table-actions">
                            <?php if ($b['status'] === 'pending'): ?>
                                <form method="POST"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="confirm"><button class="btn btn-primary btn-sm">Confirm</button></form>
                                <form method="POST"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="action" value="cancel"><button class="btn btn-outline btn-sm">Cancel</button></form>
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
