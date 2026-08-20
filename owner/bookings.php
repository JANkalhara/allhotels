<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/allhotels/index.php');
}

$hotelId         = (int) ($_POST['hotel_id'] ?? 0);
$customerName    = trim($_POST['customer_name'] ?? '');
$customerEmail   = trim($_POST['customer_email'] ?? '');
$customerPhone   = trim($_POST['customer_phone'] ?? '');
$functionTypeId  = (int) ($_POST['function_type_id'] ?? 0);
$eventDate       = trim($_POST['event_date'] ?? '');
$guestCount      = (int) ($_POST['guest_count'] ?? 0);
$specialRequest  = trim($_POST['special_request'] ?? '');


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (
    $hotelId <= 0 ||
    $customerName === '' ||
    $customerEmail === '' ||
    $customerPhone === '' ||
    $functionTypeId <= 0 ||
    $eventDate === '' ||
    $guestCount <= 0
) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please complete all required booking details.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}


/*
|--------------------------------------------------------------------------
| Validate Email
|--------------------------------------------------------------------------
*/

if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please enter a valid email address.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}


/*
|--------------------------------------------------------------------------
| Get Hotel
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM hotels
    WHERE id = ?
      AND status = 'approved'
      AND is_premium = 1
    LIMIT 1
");

$stmt->execute([$hotelId]);

$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'This hotel is not available for online booking.'
    ];

    redirect('/allhotels/index.php');
}


/*
|--------------------------------------------------------------------------
| Validate Function Type
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT ft.id, ft.name
    FROM function_types ft
    INNER JOIN hotel_function_types hft
        ON hft.function_type_id = ft.id
    WHERE ft.id = ?
      AND hft.hotel_id = ?
    LIMIT 1
");

$stmt->execute([
    $functionTypeId,
    $hotelId
]);

$functionType = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$functionType) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid function type selected.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}


/*
|--------------------------------------------------------------------------
| Validate Event Date
|--------------------------------------------------------------------------
*/

if (strtotime($eventDate) === false) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid event date.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}

if ($eventDate < date('Y-m-d')) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Event date cannot be in the past.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}


/*
|--------------------------------------------------------------------------
| Validate Guest Count
|--------------------------------------------------------------------------
*/

$minGuests = (int) ($hotel['min_guests'] ?? 1);
$maxGuests = (int) ($hotel['max_guests'] ?? 0);

if ($guestCount < max(1, $minGuests)) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => "Minimum guest count is {$minGuests}."
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}

if ($maxGuests > 0 && $guestCount > $maxGuests) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => "Maximum guest count is {$maxGuests}."
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}


/*
|--------------------------------------------------------------------------
| Check Existing Booking
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM bookings
    WHERE hotel_id = ?
      AND event_date = ?
      AND status IN ('pending', 'confirmed')
    LIMIT 1
");

$stmt->execute([
    $hotelId,
    $eventDate
]);

$existingBooking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingBooking) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'This hotel already has a booking request for that date.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}


/*
|--------------------------------------------------------------------------
| Save Booking
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        INSERT INTO bookings
        (
            hotel_id,
            user_id,
            customer_name,
            customer_email,
            customer_phone,
            function_type_id,
            event_date,
            guest_count,
            special_request,
            status,
            created_at
        )
        VALUES
        (
            ?,
            NULL,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'pending',
            NOW()
        )
    ");

    $stmt->execute([
        $hotelId,
        $customerName,
        $customerEmail,
        $customerPhone,
        $functionTypeId,
        $eventDate,
        $guestCount,
        $specialRequest
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Booking request submitted successfully! The hotel owner will review your request.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);


} catch (PDOException $e) {

    error_log('Booking Insert Error: ' . $e->getMessage());

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Unable to submit your booking request. Please try again.'
    ];

    redirect('/allhotels/hotel-details.php?id=' . $hotelId);
}