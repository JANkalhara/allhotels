<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$user = current_user();

$error = null;
$success = null;


/*
|--------------------------------------------------------------------------
| Get Owner's Premium Hotels
|--------------------------------------------------------------------------
*/

$hotelsStmt = $pdo->prepare("
    SELECT *
    FROM hotels
    WHERE user_id = ?
      AND is_premium = 1
      AND status = 'approved'
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
    ?? $_POST['hotel_id']
    ?? ($premiumHotels[0]['id'] ?? 0)
);


/*
|--------------------------------------------------------------------------
| Validate Selected Hotel Belongs To Owner + Premium
|--------------------------------------------------------------------------
*/

$selectedHotel = null;

if ($selectedId > 0) {

    $hotelStmt = $pdo->prepare("
        SELECT *
        FROM hotels
        WHERE id = ?
          AND user_id = ?
          AND is_premium = 1
          AND status = 'approved'
        LIMIT 1
    ");

    $hotelStmt->execute([
        $selectedId,
        $user['id']
    ]);

    $selectedHotel = $hotelStmt->fetch(PDO::FETCH_ASSOC);

    if (!$selectedHotel) {
        $selectedId = 0;
    }
}


/*
|--------------------------------------------------------------------------
| Handle Image Upload
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hotelId = (int) ($_POST['hotel_id'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | Check Hotel
    |--------------------------------------------------------------------------
    */

    $checkStmt = $pdo->prepare("
        SELECT *
        FROM hotels
        WHERE id = ?
          AND user_id = ?
          AND is_premium = 1
          AND status = 'approved'
        LIMIT 1
    ");

    $checkStmt->execute([
        $hotelId,
        $user['id']
    ]);

    $hotel = $checkStmt->fetch(PDO::FETCH_ASSOC);


    if (!$hotel) {

        $error = 'Invalid Premium hotel selected.';

    } elseif (
        !isset($_FILES['gallery_image']) ||
        $_FILES['gallery_image']['error'] !== UPLOAD_ERR_OK
    ) {

        $error = 'Please select an image to upload.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | File Information
        |--------------------------------------------------------------------------
        */

        $file = $_FILES['gallery_image'];

        $originalName = $file['name'];
        $tmpName = $file['tmp_name'];
        $fileSize = (int) $file['size'];

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Allowed Extensions
        |--------------------------------------------------------------------------
        */

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        /*
        |--------------------------------------------------------------------------
        | Validate Extension
        |--------------------------------------------------------------------------
        */

        if (!in_array(
            $extension,
            $allowedExtensions,
            true
        )) {

            $error = 'Only JPG, JPEG, PNG and WEBP images are allowed.';

        }


        /*
        |--------------------------------------------------------------------------
        | Validate File Size
        |--------------------------------------------------------------------------
        */

        elseif ($fileSize > 5 * 1024 * 1024) {

            $error = 'Image size must be less than 5MB.';

        }


        /*
        |--------------------------------------------------------------------------
        | Validate Real Image
        |--------------------------------------------------------------------------
        */

        elseif (@getimagesize($tmpName) === false) {

            $error = 'The uploaded file is not a valid image.';

        }


        /*
        |--------------------------------------------------------------------------
        | Upload
        |--------------------------------------------------------------------------
        */

        if (!$error) {

            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |
            | Images will be stored in:
            |
            | allhotels/api/images/
            |--------------------------------------------------------------------------
            */

            $uploadDir = __DIR__ . '/../api/images/';


            /*
            |--------------------------------------------------------------------------
            | Create Folder If Not Exists
            |--------------------------------------------------------------------------
            */

            if (!is_dir($uploadDir)) {

                if (!mkdir(
                    $uploadDir,
                    0755,
                    true
                )) {

                    $error = 'Unable to create image upload folder.';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Generate Unique Filename
            |--------------------------------------------------------------------------
            */

            if (!$error) {

                $filename =
                    'hotel_' .
                    $hotelId .
                    '_gallery_' .
                    uniqid('', true) .
                    '.' .
                    $extension;


                $destination =
                    $uploadDir .
                    $filename;


                /*
                |--------------------------------------------------------------------------
                | Move File
                |--------------------------------------------------------------------------
                */

                if (!move_uploaded_file(
                    $tmpName,
                    $destination
                )) {

                    $error = 'Unable to save the uploaded image.';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Save Database Record
            |--------------------------------------------------------------------------
            */

            if (!$error) {

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Check whether hotel already has a main image
                    |--------------------------------------------------------------------------
                    */

                    $mainCheckStmt = $pdo->prepare("
                        SELECT id
                        FROM hotel_images
                        WHERE hotel_id = ?
                          AND is_main = 1
                        LIMIT 1
                    ");

                    $mainCheckStmt->execute([
                        $hotelId
                    ]);

                    $hasMainImage =
                        $mainCheckStmt->fetch();


                    /*
                    |--------------------------------------------------------------------------
                    | First image becomes Main Image
                    |--------------------------------------------------------------------------
                    */

                    $isMain = $hasMainImage
                        ? 0
                        : 1;


                    /*
                    |--------------------------------------------------------------------------
                    | Save Image
                    |--------------------------------------------------------------------------
                    |
                    | Database:
                    |
                    | id
                    | hotel_id
                    | image_path
                    | is_main
                    |
                    */

                    $insertStmt = $pdo->prepare("
                        INSERT INTO hotel_images
                        (
                            hotel_id,
                            image_path,
                            is_main
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?
                        )
                    ");

                    $insertStmt->execute([
                        $hotelId,
                        'api/images/' . $filename,
                        $isMain
                    ]);


                    $success =
                        'Image uploaded successfully!';


                } catch (PDOException $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Remove uploaded file if DB insert failed
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset($destination) &&
                        file_exists($destination)
                    ) {

                        unlink($destination);

                    }

                    $error =
                        'Image could not be saved to the database.';

                    error_log(
                        'Gallery Upload Error: ' .
                        $e->getMessage()
                    );

                }

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Keep Selected Hotel
    |--------------------------------------------------------------------------
    */

    $selectedId = $hotelId;

}


/*
|--------------------------------------------------------------------------
| Get Images For Selected Hotel
|--------------------------------------------------------------------------
*/

$images = [];

if ($selectedId > 0) {

    $imgStmt = $pdo->prepare("
        SELECT *
        FROM hotel_images
        WHERE hotel_id = ?
        ORDER BY
            is_main DESC,
            id DESC
    ");

    $imgStmt->execute([
        $selectedId
    ]);

    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = 'Hotel Gallery';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container section">


    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <div class="section-head">

        <div>

            <h2>
                Hotel Gallery
            </h2>

            <p>
                Upload photos for your Premium hotel listings.
            </p>

        </div>

    </div>


    <div class="dash-layout">


        <!-- =====================================================
             OWNER NAVIGATION
        ====================================================== -->

        <?php include __DIR__ . '/_nav.php'; ?>


        <!-- =====================================================
             CONTENT
        ====================================================== -->

        <div>


            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>


            <?php if ($success): ?>

                <div class="alert alert-success">
                    <?= h($success) ?>
                </div>

            <?php endif; ?>


            <!-- =================================================
                 NO PREMIUM HOTELS
            ================================================== -->

            <?php if (empty($premiumHotels)): ?>

                <div class="panel">

                    <h3>
                        Premium Gallery
                    </h3>

                    <p class="footer-note">

                        You don't have any approved Premium
                        hotels yet.

                    </p>

                    <p class="footer-note">

                        Gallery upload is available only for
                        approved Premium hotels.

                    </p>

                </div>


            <?php else: ?>


                <!-- =============================================
                     HOTEL SELECTOR
                ============================================== -->

                <div class="panel">

                    <div class="form-group">

                        <label for="hotelSelector">
                            Select Premium Hotel
                        </label>

                        <select
                            id="hotelSelector"
                            onchange="changeHotel(this.value)"
                        >

                            <?php foreach (
                                $premiumHotels
                                as $hotel
                            ): ?>

                                <option
                                    value="<?= (int) $hotel['id'] ?>"
                                    <?= (
                                        (int) $hotel['id']
                                        === $selectedId
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= h($hotel['name']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <?php if ($selectedHotel): ?>

                        <div
                            style="
                                padding:15px;
                                margin-top:15px;
                                border-radius:10px;
                                background:var(--sand-100);
                            "
                        >

                            <strong>
                                <?= h(
                                    $selectedHotel['name']
                                ) ?>
                            </strong>

                            <div class="footer-note">

                                ★ Premium Hotel

                                <?php if (
                                    !empty(
                                        $selectedHotel['district']
                                    )
                                ): ?>

                                    —
                                    <?= h(
                                        $selectedHotel['district']
                                    ) ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- =============================================
                     UPLOAD
                ============================================== -->

                <?php if ($selectedHotel): ?>

                    <div class="panel">

                        <h3>
                            Upload New Image
                        </h3>

                        <p class="footer-note">

                            Add photos of your hotel,
                            hall, wedding area, dining area,
                            rooms, etc.

                        </p>


                        <form
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            <input
                                type="hidden"
                                name="hotel_id"
                                value="<?= (int) $selectedId ?>"
                            >


                            <div class="form-group">

                                <label for="gallery_image">
                                    Select Image
                                </label>

                                <input
                                    type="file"
                                    id="gallery_image"
                                    name="gallery_image"
                                    accept=".jpg,.jpeg,.png,.webp,image/*"
                                    required
                                >

                                <small class="footer-note">

                                    JPG, JPEG, PNG or WEBP.
                                    Maximum 5MB.

                                </small>

                            </div>


                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Upload Image
                            </button>

                        </form>

                    </div>


                    <!-- =========================================
                         CURRENT IMAGES
                    ========================================== -->

                    <div class="panel">

                        <h3>
                            Current Gallery
                        </h3>


                        <?php if (empty($images)): ?>

                            <p class="footer-note">

                                No images uploaded for this
                                hotel yet.

                            </p>

                        <?php else: ?>


                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:
                                    repeat(
                                        auto-fill,
                                        minmax(180px, 1fr)
                                    );
                                    gap:18px;
                                    margin-top:20px;
                                "
                            >


                                <?php foreach (
                                    $images
                                    as $image
                                ): ?>


                                    <?php

                                    $imagePath =
                                        ltrim(
                                            $image['image_path'],
                                            '/'
                                        );

                                    ?>


                                    <div
                                        style="
                                            position:relative;
                                            border-radius:12px;
                                            overflow:hidden;
                                            background:#f5f5f5;
                                        "
                                    >

                                        <img
                                            src="/allhotels/<?= h($imagePath) ?>"
                                            alt="Hotel Gallery"
                                            style="
                                                width:100%;
                                                height:180px;
                                                object-fit:cover;
                                                display:block;
                                            "
                                        >


                                        <?php if (
                                            (int)
                                            $image['is_main']
                                            === 1
                                        ): ?>

                                            <div
                                                style="
                                                    position:absolute;
                                                    top:10px;
                                                    left:10px;
                                                    background:#000;
                                                    color:#fff;
                                                    padding:5px 9px;
                                                    border-radius:5px;
                                                    font-size:12px;
                                                "
                                            >
                                                ★ Main Image
                                            </div>

                                        <?php endif; ?>

                                    </div>


                                <?php endforeach; ?>


                            </div>

                        <?php endif; ?>


                    </div>

                <?php endif; ?>


            <?php endif; ?>


        </div>

    </div>

</div>


<script>

function changeHotel(hotelId) {

    if (!hotelId) {
        return;
    }

    window.location.href =
        '/allhotels/owner/gallery.php?hotel_id='
        + encodeURIComponent(hotelId);

}

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>