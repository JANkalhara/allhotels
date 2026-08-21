<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$hotelId = (int) ($_GET['hotel_id'] ?? $_POST['hotel_id'] ?? 0);

$error = null;
$success = false;

$customerName = '';
$customerEmail = '';
$customerPhone = '';
$eventDate = '';
$functionId = 0;
$guestCount = 0;
$specialRequest = '';

/*
|--------------------------------------------------------------------------
| Validate Hotel ID
|--------------------------------------------------------------------------
*/

if ($hotelId <= 0) {

    http_response_code(404);

    $page_title = 'Booking';

    require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section">

    <div class="empty-state">

        <h2>Hotel not found</h2>

        <p>Invalid hotel ID.</p>

        <a href="/allhotels/index.php" class="btn btn-primary">
            Back to Home
        </a>

    </div>

</div>

<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Premium Approved Hotel
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


/*
|--------------------------------------------------------------------------
| Hotel Not Found
|--------------------------------------------------------------------------
*/

if (!$hotel) {

    http_response_code(404);

    $page_title = 'Booking Unavailable';

    require_once __DIR__ . '/../includes/header.php';
?>

<div class="container section">

    <div class="empty-state">

        <h2>Booking unavailable</h2>

        <p>
            Online booking is only available for Premium hotels.
        </p>

        <a href="/allhotels/index.php" class="btn btn-primary">
            Back to Home
        </a>

    </div>

</div>

<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Function Types
|--------------------------------------------------------------------------
*/

$funcStmt = $pdo->prepare("
    SELECT ft.*
    FROM hotel_function_types hft
    INNER JOIN function_types ft
        ON ft.id = hft.function_type_id
    WHERE hft.hotel_id = ?
    ORDER BY ft.name ASC
");

$funcStmt->execute([$hotelId]);

$functions = $funcStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Handle Booking Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $functionId = (int) ($_POST['function_type_id'] ?? 0);
    $guestCount = (int) ($_POST['guest_count'] ?? 0);
    $specialRequest = trim($_POST['special_request'] ?? '');


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($customerName === '') {

        $error = 'Please enter your name.';

    } elseif ($customerPhone === '') {

        $error = 'Please enter your contact number.';

    } elseif ($eventDate === '') {

        $error = 'Please select the event date.';

    } elseif ($functionId <= 0) {

        $error = 'Please select a function type.';

    } elseif ($guestCount <= 0) {

        $error = 'Please enter the number of guests.';

    } elseif ($eventDate < date('Y-m-d')) {

        $error = 'Please select today or a future date.';

    } elseif (
        !empty($hotel['min_guests']) &&
        $guestCount < (int) $hotel['min_guests']
    ) {

        $error =
            'Minimum guest count is ' .
            (int) $hotel['min_guests'] .
            '.';

    } elseif (
        !empty($hotel['max_guests']) &&
        $guestCount > (int) $hotel['max_guests']
    ) {

        $error =
            'Maximum guest count is ' .
            (int) $hotel['max_guests'] .
            '.';

    } elseif (
        $customerEmail !== '' &&
        !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)
    ) {

        $error = 'Please enter a valid email address.';
    }


    /*
    |--------------------------------------------------------------------------
    | Check Function Type Belongs To Hotel
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | hotel_function_types table does NOT use "id".
    | It uses hotel_id + function_type_id.
    |
    */

    if (!$error) {

        $checkFunc = $pdo->prepare("
            SELECT function_type_id
            FROM hotel_function_types
            WHERE hotel_id = ?
              AND function_type_id = ?
            LIMIT 1
        ");

        $checkFunc->execute([
            $hotelId,
            $functionId
        ]);

        if (!$checkFunc->fetchColumn()) {

            $error = 'Invalid function type selected.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Save Booking
    |--------------------------------------------------------------------------
    */

    if (!$error) {

        try {

            $bookingStmt = $pdo->prepare("
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

            $bookingStmt->execute([
                $hotelId,
                $customerName,
                $customerEmail !== '' ? $customerEmail : null,
                $customerPhone,
                $functionId,
                $eventDate,
                $guestCount,
                $specialRequest !== '' ? $specialRequest : null
            ]);

            $success = true;

        } catch (PDOException $e) {

            $error = 'Booking could not be submitted: ' . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = 'Book ' . $hotel['name'];

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container section">

<?php if ($success): ?>

    <!-- SUCCESS -->

    <div
        class="panel"
        style="
            max-width:700px;
            margin:0 auto;
            text-align:center;
        "
    >

        <div
            style="
                font-size:50px;
                margin-bottom:10px;
            "
        >
            ✓
        </div>

        <h2>
            Booking Request Submitted
        </h2>

        <p>
            Thank you,
            <strong><?= h($customerName) ?></strong>.
        </p>

        <p>
            Your booking request for
            <strong><?= h($hotel['name']) ?></strong>
            has been submitted successfully.
        </p>

        <p class="footer-note">

            Your request is currently
            <strong>Pending</strong>.

            The hotel owner will review your request
            and contact you using the details you provided.

        </p>

        <div
            style="
                margin-top:25px;
                display:flex;
                gap:10px;
                justify-content:center;
                flex-wrap:wrap;
            "
        >

            <a
                href="/allhotels/hotel-details/hotel-details.php?id=<?= (int) $hotel['id'] ?>"
                class="btn btn-primary"
            >
                Back to Hotel
            </a>

            <a
                href="/allhotels/index.php"
                class="btn btn-outline"
            >
                Back to Home
            </a>

        </div>

    </div>


<?php else: ?>

    <!-- HEADER -->

    <div class="section-head">

        <div>

            <h2>
                Book <?= h($hotel['name']) ?>
            </h2>

            <p>
                Complete the form below to send your booking request.
            </p>

        </div>

    </div>


    <!-- FORM -->

    <div
        class="panel"
        style="
            max-width:750px;
            margin:0 auto;
        "
    >

        <?php if ($error): ?>

            <div class="alert alert-error">
                <?= h($error) ?>
            </div>

        <?php endif; ?>


        <!-- HOTEL SUMMARY -->

        <div
            style="
                padding:15px;
                margin-bottom:25px;
                border-radius:8px;
                background:var(--sand-100);
            "
        >

            <strong>
                <?= h($hotel['name']) ?>
            </strong>

            <div class="footer-note">

                <?= h($hotel['district'] ?? '') ?>

                <?php if (!empty($hotel['address'])): ?>

                    — <?= h($hotel['address']) ?>

                <?php endif; ?>

            </div>

            <div
                class="price-line"
                style="margin-top:8px;"
            >

                Starting from Rs.
                <?= number_format(
                    (float) $hotel['starting_price']
                ) ?>

            </div>

        </div>


        <form method="POST">

            <input
                type="hidden"
                name="hotel_id"
                value="<?= (int) $hotel['id'] ?>"
            >


            <!-- NAME -->

            <div class="form-group">

                <label for="customer_name">
                    Your Name *
                </label>

                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    placeholder="Enter your full name"
                    maxlength="100"
                    required
                    value="<?= h($customerName) ?>"
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="customer_email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="customer_email"
                    name="customer_email"
                    placeholder="example@gmail.com"
                    maxlength="150"
                    value="<?= h($customerEmail) ?>"
                >

            </div>


            <!-- PHONE -->

            <div class="form-group">

                <label for="customer_phone">
                    Contact Number *
                </label>

                <input
                    type="text"
                    id="customer_phone"
                    name="customer_phone"
                    placeholder="+94 77 123 4567"
                    maxlength="30"
                    required
                    value="<?= h($customerPhone) ?>"
                >

            </div>


            <!-- DATE -->

            <div class="form-group">

                <label for="event_date">
                    Event Date *
                </label>

                <input
                    type="date"
                    id="event_date"
                    name="event_date"
                    min="<?= date('Y-m-d') ?>"
                    required
                    value="<?= h($eventDate) ?>"
                >

            </div>


            <!-- FUNCTION -->

            <div class="form-group">

                <label for="function_type_id">
                    Function Type *
                </label>

                <select
                    id="function_type_id"
                    name="function_type_id"
                    required
                >

                    <option value="">
                        Select Function Type
                    </option>

                    <?php foreach ($functions as $f): ?>

                        <option
                            value="<?= (int) $f['id'] ?>"
                            <?= $functionId === (int) $f['id']
                                ? 'selected'
                                : '' ?>
                        >
                            <?= h($f['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- GUESTS -->

            <div class="form-group">

                <label for="guest_count">
                    Number of Guests *
                </label>

                <input
                    type="number"
                    id="guest_count"
                    name="guest_count"
                    min="<?= max(
                        1,
                        (int) ($hotel['min_guests'] ?? 1)
                    ) ?>"
                    <?php if (!empty($hotel['max_guests'])): ?>

                        max="<?= (int) $hotel['max_guests'] ?>"

                    <?php endif; ?>

                    required

                    value="<?= $guestCount > 0
                        ? (int) $guestCount
                        : ''
                    ?>"
                >

            </div>


            <!-- SPECIAL REQUEST -->

            <div class="form-group">

                <label for="special_request">
                    Special Request
                </label>

                <textarea
                    id="special_request"
                    name="special_request"
                    rows="4"
                    maxlength="1000"
                    placeholder="Any special requirements or requests..."
                ><?= h($specialRequest) ?></textarea>

            </div>


            <div
                class="footer-note"
                style="margin-bottom:20px;"
            >

                <strong>Note:</strong>

                This is a booking request.
                The hotel owner will review your request
                and confirm or cancel it.

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="btn btn-terracotta btn-block"
            >
                Submit Booking Request
            </button>


            <!-- CANCEL -->

            <a
                href="/allhotels/hotel-details.php?id=<?= (int) $hotel['id'] ?>"
                class="btn btn-outline btn-block"
                style="margin-top:10px;"
            >
                Cancel
            </a>

        </form>

    </div>

<?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>