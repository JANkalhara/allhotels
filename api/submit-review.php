<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';


/*Only POST Requests*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect('/allhotels/index.php');
    exit;
}


/*Get Form Data*/

$hotelId = (int) ($_POST['hotel_id'] ?? 0);

$rating = (int) ($_POST['rating'] ?? 5);

$reviewerName = trim(
    $_POST['reviewer_name'] ?? ''
);

$comment = trim(
    $_POST['comment'] ?? ''
);


/*Validate Rating*/

if ($rating < 1) {
    $rating = 1;
}

if ($rating > 5) {
    $rating = 5;
}


/*Validate Hotel ID*/

if ($hotelId <= 0) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Invalid hotel.'
    ];

    redirect('/allhotels/index.php');
    exit;
}


/*Validate Reviewer Name*/

if ($reviewerName === '') {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please enter your name.'
    ];

    redirect(
        '/allhotels/hotel-details/hotel-details.php?id='
        . $hotelId
    );

    exit;
}


if (strlen($reviewerName) > 100) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Your name is too long.'
    ];

    redirect(
        '/allhotels/hotel-details/hotel-details.php?id='
        . $hotelId
    );

    exit;
}


/*Validate Comment*/

if ($comment === '') {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Please write a review.'
    ];

    redirect(
        '/allhotels/hotel-details/hotel-details.php?id='
        . $hotelId
    );

    exit;
}


if (strlen($comment) > 1000) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Your review is too long.'
    ];

    redirect(
        '/allhotels/hotel-details/hotel-details.php?id='
        . $hotelId
    );

    exit;
}


/*Check Hotel*/

$hotelStmt = $pdo->prepare("
    SELECT id, name
    FROM hotels
    WHERE id = ?
      AND status = 'approved'
    LIMIT 1
");

$hotelStmt->execute([$hotelId]);

$hotel = $hotelStmt->fetch(PDO::FETCH_ASSOC);


if (!$hotel) {

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Hotel not found.'
    ];

    redirect('/allhotels/index.php');
    exit;
}


/*
| Get Logged-in User ID. Review can be submitted without login.
| If logged in, save the user_id.
*/

$userId = null;

if (
    function_exists('is_logged_in')
    && is_logged_in()
) {

    $userId = $_SESSION['user_id'] ?? null;

    if ($userId !== null) {
        $userId = (int) $userId;

        if ($userId <= 0) {
            $userId = null;
        }
    }
}


/*Insert Review*/

try {

    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        INSERT INTO reviews
        (
            hotel_id,
            user_id,
            reviewer_name,
            rating,
            comment,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            NOW()
        )
    ");


    $stmt->execute([
        $hotelId,
        $userId,
        $reviewerName,
        $rating,
        $comment
    ]);


    $pdo->commit();


    /*Success*/

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Thank you! Your review has been submitted.'
    ];


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    /* Error*/

    $_SESSION['flash'] = [
        'type' => 'error',
        'message' => 'Unable to submit your review. Please try again.'
    ];

}


/*Redirect Back To Hotel*/

redirect(
    '/allhotels/hotel-details/hotel-details.php?id='
    . $hotelId
);

exit;