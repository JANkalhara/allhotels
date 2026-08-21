<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$user = current_user();


/*
|--------------------------------------------------------------------------
| Confirm / Cancel Booking
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'])) {

    $bookingAction = $_POST['booking_action'] ?? '';
    $hotelId       = (int) ($_POST['hotel_id'] ?? 0);
    $eventDate     = trim($_POST['event_date'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Only confirm or cancel
    |--------------------------------------------------------------------------
    */

    if (!in_array($bookingAction, ['confirm', 'cancel'], true)) {

        redirect('/allhotels/owner/bookings.php');
    }


    $newStatus = $bookingAction === 'confirm'
        ? 'confirmed'
        : 'cancelled';


    /*
    |--------------------------------------------------------------------------
    | Make sure hotel belongs to current owner
    |--------------------------------------------------------------------------
    */

    $checkHotel = $pdo->prepare("
        SELECT id, name
        FROM hotels
        WHERE id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $checkHotel->execute([
        $hotelId,
        $user['id']
    ]);

    $ownerHotel = $checkHotel->fetch(PDO::FETCH_ASSOC);


    if (!$ownerHotel) {

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'You are not authorized to manage this booking.'
        ];

        redirect('/allhotels/owner/bookings.php');
    }


    /*
    |--------------------------------------------------------------------------
    | Find Booking
    |--------------------------------------------------------------------------
    |
    | No booking ID is used because your bookings table
    | does not have an "id" column.
    |
    | We identify the booking using:
    |
    | hotel_id
    | event_date
    | customer_email
    |
    */

    $findBooking = $pdo->prepare("
        SELECT *
        FROM bookings
        WHERE hotel_id = ?
          AND event_date = ?
          AND customer_email = ?
          AND status = 'pending'
        LIMIT 1
    ");

    $findBooking->execute([
        $hotelId,
        $eventDate,
        $customerEmail
    ]);

    $booking = $findBooking->fetch(PDO::FETCH_ASSOC);


    if (!$booking) {

        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Booking request was not found or has already been processed.'
        ];

        redirect('/allhotels/owner/bookings.php');
    }


    /*
    |--------------------------------------------------------------------------
    | Update Booking Status
    |--------------------------------------------------------------------------
    */

    $update = $pdo->prepare("
        UPDATE bookings
        SET status = ?
        WHERE hotel_id = ?
          AND event_date = ?
          AND customer_email = ?
          AND status = 'pending'
        LIMIT 1
    ");

    $update->execute([
        $newStatus,
        $hotelId,
        $eventDate,
        $customerEmail
    ]);


    /*
    |--------------------------------------------------------------------------
    | Success Message
    |--------------------------------------------------------------------------
    */

    if ($newStatus === 'confirmed') {

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Booking confirmed successfully.'
        ];

    } else {

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Booking cancelled successfully.'
        ];
    }


    redirect('/allhotels/owner/bookings.php');
}


/*
|--------------------------------------------------------------------------
| Get Owner's Bookings
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Do NOT JOIN users table.
|
| Customers do not login.
| Therefore user_id is NULL.
|
*/

$stmt = $pdo->prepare("
    SELECT
        b.hotel_id,
        b.user_id,
        b.customer_name,
        b.customer_email,
        b.customer_phone,
        b.function_type_id,
        b.event_date,
        b.guest_count,
        b.special_request,
        b.status,
        b.created_at,

        h.name AS hotel_name,

        ft.name AS function_name

    FROM bookings b

    INNER JOIN hotels h
        ON h.id = b.hotel_id

    LEFT JOIN function_types ft
        ON ft.id = b.function_type_id

    WHERE h.user_id = ?

    ORDER BY b.created_at DESC
");

$stmt->execute([
    $user['id']
]);

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = 'Bookings';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container section">

    <div class="section-head">

        <div>

            <h2>Bookings</h2>

            <p>
                Manage booking requests received for your hotels.
            </p>

        </div>

    </div>


    <div class="dash-layout">

        <?php include __DIR__ . '/_nav.php'; ?>


        <div class="panel">

            <?php if (!empty($_SESSION['flash'])): ?>

                <?php
                $flash = $_SESSION['flash'];
                unset($_SESSION['flash']);
                ?>

                <div
                    class="alert alert-<?= h($flash['type']) ?>"
                    data-autohide
                >
                    <?= h($flash['message']) ?>
                </div>

            <?php endif; ?>


            <?php if (empty($bookings)): ?>

                <div class="empty-state">

                    <h3>No bookings yet</h3>

                    <p>
                        Customer booking requests will appear here.
                    </p>

                </div>


            <?php else: ?>


                <div style="overflow-x:auto;">

                    <table class="data-table">

                        <thead>

                            <tr>

                                <th>Hotel</th>

                                <th>Customer</th>

                                <th>Contact</th>

                                <th>Function</th>

                                <th>Date</th>

                                <th>Guests</th>

                                <th>Request</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php foreach ($bookings as $b): ?>

                            <tr>

                                <!-- HOTEL -->

                                <td>

                                    <strong>
                                        <?= h($b['hotel_name']) ?>
                                    </strong>

                                </td>


                                <!-- CUSTOMER -->

                                <td>

                                    <strong>
                                        <?= h($b['customer_name']) ?>
                                    </strong>

                                    <br>

                                    <span class="footer-note">
                                        <?= h($b['customer_email']) ?>
                                    </span>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?= h(
                                        $b['customer_phone']
                                    ) ?>

                                </td>


                                <!-- FUNCTION -->

                                <td>

                                    <?= h(
                                        $b['function_name'] ?? '—'
                                    ) ?>

                                </td>


                                <!-- EVENT DATE -->

                                <td>

                                    <?= date(
                                        'd M Y',
                                        strtotime(
                                            $b['event_date']
                                        )
                                    ) ?>

                                </td>


                                <!-- GUESTS -->

                                <td>

                                    <?= (int) $b['guest_count'] ?>

                                </td>


                                <!-- SPECIAL REQUEST -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $b['special_request']
                                        )
                                    ): ?>

                                        <?= h(
                                            $b['special_request']
                                        ) ?>

                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="status-pill status-<?= h(
                                            $b['status']
                                        ) ?>"
                                    >
                                        <?= h(
                                            ucfirst(
                                                $b['status']
                                            )
                                        ) ?>
                                    </span>

                                </td>


                                <!-- ACTION -->

                                <td class="table-actions">

                                    <?php if (
                                        $b['status'] === 'pending'
                                    ): ?>


                                        <!-- CONFIRM -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="booking_action"
                                                value="confirm"
                                            >

                                            <input
                                                type="hidden"
                                                name="hotel_id"
                                                value="<?= (int) $b['hotel_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="event_date"
                                                value="<?= h($b['event_date']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="customer_email"
                                                value="<?= h($b['customer_email']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-primary btn-sm"
                                            >
                                                Confirm
                                            </button>

                                        </form>


                                        <!-- CANCEL -->

                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="booking_action"
                                                value="cancel"
                                            >

                                            <input
                                                type="hidden"
                                                name="hotel_id"
                                                value="<?= (int) $b['hotel_id'] ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="event_date"
                                                value="<?= h($b['event_date']) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="customer_email"
                                                value="<?= h($b['customer_email']) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-outline btn-sm"
                                            >
                                                Cancel
                                            </button>

                                        </form>


                                    <?php else: ?>

                                        —

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>