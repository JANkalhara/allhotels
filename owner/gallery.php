<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$user = current_user();


/*
|--------------------------------------------------------------------------
| Handle Gallery Upload
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['hotel_id'])
) {

    $hotelId = (int) $_POST['hotel_id'];


    /*
    |--------------------------------------------------------------------------
    | Check Hotel Belongs To Owner
    |--------------------------------------------------------------------------
    */

    $check = $pdo->prepare("
        SELECT *
        FROM hotels
        WHERE id = ?
          AND user_id = ?
          AND is_premium = 1
    ");

    $check->execute([
        $hotelId,
        $user['id']
    ]);

    $hotel = $check->fetch(PDO::FETCH_ASSOC);


    if (
        $hotel &&
        isset($_FILES['gallery_image']) &&
        $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK
    ) {

        $uploadDir = __DIR__ . '/../api/images/';


        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }


        $ext = strtolower(
            pathinfo(
                $_FILES['gallery_image']['name'],
                PATHINFO_EXTENSION
            )
        );


        $allowed = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (in_array($ext, $allowed, true)) {

            $filename =
                'hotel_' .
                $hotelId .
                '_gallery_' .
                time() .
                '_' .
                bin2hex(random_bytes(4)) .
                '.' .
                $ext;


            $target =
                $uploadDir . $filename;


            if (
                move_uploaded_file(
                    $_FILES['gallery_image']['tmp_name'],
                    $target
                )
            ) {

                $stmt = $pdo->prepare("
                    INSERT INTO hotel_images
                    (
                        hotel_id,
                        image_path,
                        is_main
                    )
                    VALUES (?, ?, 0)
                ");


                $stmt->execute([
                    $hotelId,
                    'api/images/' . $filename
                ]);
            }
        }
    }


    redirect(
        '/allhotels/owner/gallery.php?hotel_id=' .
        $hotelId
    );
}


/*
|--------------------------------------------------------------------------
| Get Premium Hotels
|--------------------------------------------------------------------------
*/

$hotelsStmt = $pdo->prepare("
    SELECT *
    FROM hotels
    WHERE user_id = ?
      AND is_premium = 1
    ORDER BY created_at DESC
");

$hotelsStmt->execute([
    $user['id']
]);

$premiumHotels = $hotelsStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Selected Hotel
|--------------------------------------------------------------------------
*/

$selectedId = (int) (
    $_GET['hotel_id']
    ?? ($premiumHotels[0]['id'] ?? 0)
);


/*
|--------------------------------------------------------------------------
| Get Images
|--------------------------------------------------------------------------
*/

$images = [];

if ($selectedId) {

    $imgStmt = $pdo->prepare("
        SELECT *
        FROM hotel_images
        WHERE hotel_id = ?
        ORDER BY is_main DESC, id DESC
    ");

    $imgStmt->execute([
        $selectedId
    ]);

    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}


$page_title = 'Hotel Gallery';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container section">

    <div class="section-head">

        <div>

            <h2>Hotel Gallery</h2>

            <p>
                Upload multiple photos to your Premium listings.
            </p>

        </div>

    </div>


    <div class="dash-layout">

        <?php include __DIR__ . '/_nav.php'; ?>


        <div>

            <?php if (empty($premiumHotels)): ?>

                <div class="panel">

                    <p class="footer-note">

                        The photo gallery is a Premium feature.
                        Upgrade one of your hotels to Premium
                        to unlock it.

                    </p>

                </div>

            <?php else: ?>


                <div class="panel">


                    <!-- SELECT HOTEL -->

                    <form
                        method="GET"
                        style="margin-bottom:18px;"
                    >

                        <div
                            class="form-group"
                            style="max-width:320px;"
                        >

                            <label for="hotel_id">
                                Select Hotel
                            </label>

                            <select
                                id="hotel_id"
                                name="hotel_id"
                                onchange="this.form.submit()"
                            >

                                <?php foreach ($premiumHotels as $ph): ?>

                                    <option
                                        value="<?= (int) $ph['id'] ?>"
                                        <?= (
                                            $ph['id'] == $selectedId
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= h($ph['name']) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                    </form>


                    <!-- UPLOAD -->

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        style="
                            display:flex;
                            gap:12px;
                            align-items:flex-end;
                            margin-bottom:24px;
                        "
                    >

                        <input
                            type="hidden"
                            name="hotel_id"
                            value="<?= (int) $selectedId ?>"
                        >


                        <div
                            class="form-group"
                            style="flex:1;"
                        >

                            <label for="gallery_image">
                                Upload New Image
                            </label>

                            <input
                                type="file"
                                id="gallery_image"
                                name="gallery_image"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                required
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Upload
                        </button>

                    </form>


                    <!-- GALLERY -->

                    <div class="gallery-strip">

                        <?php foreach ($images as $img): ?>

                            <img
                                src="/allhotels/<?= h($img['image_path']) ?>"
                                alt="Hotel Gallery Image"
                                loading="lazy"
                            >

                        <?php endforeach; ?>


                        <?php if (empty($images)): ?>

                            <p class="footer-note">
                                No images uploaded yet.
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>