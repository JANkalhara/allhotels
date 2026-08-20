<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/allhotels/index.php');
if (!is_logged_in() || current_user()['role'] !== 'customer') redirect('/auth/login.php');

$hotelId        = (int) ($_POST['hotel_id'] ?? 0);
$eventDate      = $_POST['event_date'] ?? '';
$functionTypeId = (int) ($_POST['function_type_id'] ?? 0);
$guestCount     = (int) ($_POST['guest_count'] ?? 0);
$userId         = $_SESSION['user_id'];

$hotelStmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ? AND status = 'approved'");
$hotelStmt->execute([$hotelId]);
$hotel = $hotelStmt->fetch();

if (!$hotel || !$hotel['is_premium']) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Online booking is only available for Premium hotels.'];
    redirect('/hotel-details.php?id=' . $hotelId);
}

if (!$eventDate || $guestCount <= 0 || $functionTypeId <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please complete all booking fields.'];
    redirect('/hotel-details.php?id=' . $hotelId);
}

$stmt = $pdo->prepare(
    "INSERT INTO bookings (hotel_id, user_id, function_type_id, event_date, guest_count, status)
     VALUES (?, ?, ?, ?, ?, 'pending')"
);
$stmt->execute([$hotelId, $userId, $functionTypeId, $eventDate, $guestCount]);

// Confirm Booking -> Instant Owner Alert (Email + WhatsApp)
notify(
    $pdo,
    $hotel['user_id'],
    'new_booking',
    "New booking request for \"{$hotel['name']}\" on {$eventDate} ({$guestCount} guests).",
    'both'
);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Booking request sent! The hotel owner has been notified.'];
redirect('/hotel-details.php?id=' . $hotelId);
