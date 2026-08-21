<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$user = current_user();

$functionTypes = $pdo
    ->query("SELECT * FROM function_types ORDER BY name")
    ->fetchAll();

$error = null;
$success = null;


/*
|--------------------------------------------------------------------------
| Handle Hotel Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = trim($_POST['name'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $district   = trim($_POST['district'] ?? '');
    $contact    = trim($_POST['contact_number'] ?? '');
    $price      = (float) ($_POST['starting_price'] ?? 0);
    $minGuests  = (int) ($_POST['min_guests'] ?? 0);
    $maxGuests  = (int) ($_POST['max_guests'] ?? 0);
    $desc       = trim($_POST['description'] ?? '');
    $functions  = $_POST['functions'] ?? [];


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $name === '' ||
        $address === '' ||
        $district === '' ||
        $price <= 0 ||
        empty($functions)
    ) {

        $error =
            'Please complete all required fields and select at least one function type.';

    } elseif (
        $minGuests > 0 &&
        $maxGuests > 0 &&
        $minGuests > $maxGuests
    ) {

        $error =
            'Minimum guests cannot be greater than maximum guests.';

    } else {

        $pdo->beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Insert Hotel
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare("
                INSERT INTO hotels
                (
                    user_id,
                    name,
                    address,
                    district,
                    contact_number,
                    starting_price,
                    min_guests,
                    max_guests,
                    description,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $user['id'],
                $name,
                $address,
                $district,
                $contact,
                $price,
                $minGuests,
                $maxGuests,
                $desc
            ]);

            $hotelId = (int) $pdo->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | Save Function Types
            |--------------------------------------------------------------------------
            */

            $funcStmt = $pdo->prepare("
                INSERT INTO hotel_function_types
                (
                    hotel_id,
                    function_type_id
                )
                VALUES (?, ?)
            ");

            foreach ($functions as $fid) {

                $fid = (int) $fid;

                if ($fid > 0) {
                    $funcStmt->execute([
                        $hotelId,
                        $fid
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Main Image Upload
            |--------------------------------------------------------------------------
            */

            if (
                isset($_FILES['main_image']) &&
                $_FILES['main_image']['error'] === UPLOAD_ERR_OK
            ) {

                /*
                | Images are stored here:
                | /allhotels/api/images/
                */

            // Main hotel image upload
                if (
                    isset($_FILES['main_image']) &&
                    $_FILES['main_image']['error'] === UPLOAD_ERR_OK
                ) {

                    // Correct folder:
                    // allhotels/api/images/
                    $uploadDir = __DIR__ . '/../api/images/';

                    // Create folder if it does not exist
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $originalName = $_FILES['main_image']['name'];
                    $tmpName      = $_FILES['main_image']['tmp_name'];

                    $ext = strtolower(
                        pathinfo($originalName, PATHINFO_EXTENSION)
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
                            '_main_' .
                            time() .
                            '_' .
                            bin2hex(random_bytes(4)) .
                            '.' .
                            $ext;

                        $destination = $uploadDir . $filename;

                        if (move_uploaded_file($tmpName, $destination)) {

                            // Path saved in database
                            $imagePath = 'api/images/' . $filename;

                            $imgStmt = $pdo->prepare("
                                INSERT INTO hotel_images
                                (
                                    hotel_id,
                                    image_path,
                                    is_main
                                )
                                VALUES
                                (?, ?, 1)
                            ");

                            $imgStmt->execute([
                                $hotelId,
                                $imagePath
                            ]);
                        }
                    }
                }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Notification
            |--------------------------------------------------------------------------
            */

            notify(
                $pdo,
                $user['id'],
                'hotel_submitted',
                "\"$name\" has been submitted and is pending admin approval.",
                'both'
            );


            $success =
                'Hotel submitted successfully! It will appear publicly once approved by an administrator.';


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Something went wrong while saving your hotel: ' .
                $e->getMessage();
        }
    }
}


$page_title = 'Add Hotel';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container section">

    <div class="section-head">

        <div>

            <h2>Add a New Hotel</h2>

            <p>
                Submit your property for admin review.
                It goes live once approved.
            </p>

        </div>

    </div>


    <div class="dash-layout">

        <?php include __DIR__ . '/_nav.php'; ?>


        <div class="panel">

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


            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="form-row">


                    <div class="form-group">

                        <label for="name">
                            Hotel Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="address"
                            name="address"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <input
                            type="text"
                            id="contact_number"
                            name="contact_number"
                            placeholder="+94 77 123 4567"
                        >

                    </div>


                    <div class="form-group">

                        <label for="district">
                            District / Location
                        </label>

                        <input
                            type="text"
                            id="district"
                            name="district"
                            placeholder="e.g. Galle"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="starting_price">
                            Starting Price (Rs.)
                        </label>

                        <input
                            type="number"
                            id="starting_price"
                            name="starting_price"
                            min="0"
                            step="500"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="min_guests">
                            Min Guests
                        </label>

                        <input
                            type="number"
                            id="min_guests"
                            name="min_guests"
                            min="0"
                        >

                    </div>


                    <div class="form-group">

                        <label for="max_guests">
                            Max Guests
                        </label>

                        <input
                            type="number"
                            id="max_guests"
                            name="max_guests"
                            min="0"
                        >

                    </div>

                </div>


                <!-- FUNCTION TYPES -->

                <div class="form-group">

                    <label>
                        Function Types
                    </label>

                    <div class="checkbox-grid">

                        <?php foreach ($functionTypes as $ft): ?>

                            <label>

                                <input
                                    type="checkbox"
                                    name="functions[]"
                                    value="<?= (int) $ft['id'] ?>"
                                >

                                <?= h($ft['name']) ?>

                            </label>

                        <?php endforeach; ?>

                    </div>

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Describe your venue..."
                    ></textarea>

                </div>


                <!-- MAIN IMAGE -->

                <div class="form-group">

                    <label for="main_image">
                        Main Hotel Image
                    </label>

                    <input
                        type="file"
                        id="main_image"
                        name="main_image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small class="footer-note">
                        Recommended: JPG, PNG or WEBP.
                    </small>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Submit Hotel for Approval
                </button>

            </form>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>