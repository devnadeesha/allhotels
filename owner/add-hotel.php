<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$user = current_user();

$error = null;
$success = null;


/*
|--------------------------------------------------------------------------
| Get Function Types
|--------------------------------------------------------------------------
*/

try {

    $functionStmt = $pdo->query("
        SELECT id, name
        FROM function_types
        ORDER BY name ASC
    ");

    $functionTypes = $functionStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $functionTypes = [];

    $error = 'Unable to load function types. Please check your database.';

}


/*
|--------------------------------------------------------------------------
| Selected Function Types
|--------------------------------------------------------------------------
*/

$functions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $functions = $_POST['functions'] ?? [];

    if (!is_array($functions)) {
        $functions = [];
    }

}


/*
|--------------------------------------------------------------------------
| Handle Hotel Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | Get Form Values
    |--------------------------------------------------------------------------
    */

    $name = trim($_POST['name'] ?? '');

    $address = trim($_POST['address'] ?? '');

    $district = trim($_POST['district'] ?? '');

    $contact = trim($_POST['contact_number'] ?? '');

    $price = (float) ($_POST['starting_price'] ?? 0);

    $minGuests = (int) ($_POST['min_guests'] ?? 0);

    $maxGuests = (int) ($_POST['max_guests'] ?? 0);

    $desc = trim($_POST['description'] ?? '');


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


        /*
        |--------------------------------------------------------------------------
        | Start Transaction
        |--------------------------------------------------------------------------
        */

        try {

            $pdo->beginTransaction();


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


            /*
            |--------------------------------------------------------------------------
            | Get Hotel ID
            |--------------------------------------------------------------------------
            */

            $hotelId = (int) $pdo->lastInsertId();


            if ($hotelId <= 0) {

                throw new Exception(
                    'Hotel was not created correctly.'
                );

            }


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
                VALUES
                (?, ?)
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
                $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {


                /*
                |--------------------------------------------------------------------------
                | Upload Error Check
                |--------------------------------------------------------------------------
                */

                $uploadError = $_FILES['main_image']['error'];


                if ($uploadError !== UPLOAD_ERR_OK) {

                    $uploadErrors = [

                        UPLOAD_ERR_INI_SIZE =>
                            'The uploaded image is too large.',

                        UPLOAD_ERR_FORM_SIZE =>
                            'The uploaded image is too large.',

                        UPLOAD_ERR_PARTIAL =>
                            'The image upload was incomplete.',

                        UPLOAD_ERR_NO_TMP_DIR =>
                            'PHP temporary upload folder is missing.',

                        UPLOAD_ERR_CANT_WRITE =>
                            'PHP cannot write the uploaded image.',

                        UPLOAD_ERR_EXTENSION =>
                            'Image upload was stopped by a PHP extension.'

                    ];


                    $message =
                        $uploadErrors[$uploadError]
                        ?? 'Unknown image upload error.';


                    throw new Exception($message);

                }


                /*
                |--------------------------------------------------------------------------
                | Upload Directory
                |--------------------------------------------------------------------------
                |
                | Actual location:
                |
                | /allhotels/api/images/
                |
                */

                $uploadDir =
                    __DIR__ . '/../api/images/';


                /*
                |--------------------------------------------------------------------------
                | Create Directory
                |--------------------------------------------------------------------------
                */

                if (!is_dir($uploadDir)) {

                    if (!mkdir($uploadDir, 0775, true)) {

                        throw new Exception(
                            'Unable to create image folder: ' .
                            $uploadDir
                        );

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Check Folder Writable
                |--------------------------------------------------------------------------
                */

                if (!is_writable($uploadDir)) {

                    throw new Exception(
                        'Image folder is not writable: ' .
                        $uploadDir
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Get Uploaded File
                |--------------------------------------------------------------------------
                */

                $tmpName =
                    $_FILES['main_image']['tmp_name'];


                $originalName =
                    $_FILES['main_image']['name'];


                /*
                |--------------------------------------------------------------------------
                | Check Temporary File
                |--------------------------------------------------------------------------
                */

                if (!is_uploaded_file($tmpName)) {

                    throw new Exception(
                        'Uploaded image was not received correctly.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Get Extension
                |--------------------------------------------------------------------------
                */

                $ext = strtolower(
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


                if (
                    !in_array(
                        $ext,
                        $allowedExtensions,
                        true
                    )
                ) {

                    throw new Exception(
                        'Invalid image format. Please upload JPG, JPEG, PNG or WEBP.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Check MIME Type
                |--------------------------------------------------------------------------
                */

                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                $mimeType = '';


                if (function_exists('mime_content_type')) {

                    $mimeType =
                        mime_content_type($tmpName);

                }


                if (
                    $mimeType !== '' &&
                    !in_array(
                        $mimeType,
                        $allowedMimeTypes,
                        true
                    )
                ) {

                    throw new Exception(
                        'The uploaded file is not a valid image.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Generate Unique Filename
                |--------------------------------------------------------------------------
                */

                $filename =
                    'hotel_' .
                    $hotelId .
                    '_main_' .
                    date('Ymd_His') .
                    '_' .
                    bin2hex(random_bytes(5)) .
                    '.' .
                    $ext;


                /*
                |--------------------------------------------------------------------------
                | Full Destination
                |--------------------------------------------------------------------------
                */

                $destination =
                    $uploadDir . $filename;


                /*
                |--------------------------------------------------------------------------
                | Move Uploaded File
                |--------------------------------------------------------------------------
                */

                if (
                    !move_uploaded_file(
                        $tmpName,
                        $destination
                    )
                ) {

                    throw new Exception(
                        'Failed to save the hotel image. Check folder permissions.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Save Image Path
                |--------------------------------------------------------------------------
                */

                $imagePath =
                    'api/images/' . $filename;


                /*
                |--------------------------------------------------------------------------
                | Insert Image Record
                |--------------------------------------------------------------------------
                */

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


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Notification
            |--------------------------------------------------------------------------
            */

            try {

                notify(
                    $pdo,
                    $user['id'],
                    'hotel_submitted',
                    "\"$name\" has been submitted and is pending admin approval.",
                    'both'
                );

            } catch (Exception $notificationError) {

                /*
                | Notification failure should not
                | cancel successful hotel submission.
                */

            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            $success =
                'Hotel submitted successfully! It will appear publicly once approved by an administrator.';


            /*
            |--------------------------------------------------------------------------
            | Clear Form
            |--------------------------------------------------------------------------
            */

            $_POST = [];

            $functions = [];


        } catch (Exception $e) {


            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            if ($pdo->inTransaction()) {

                $pdo->rollBack();

            }


            $error =
                'Something went wrong while saving your hotel: ' .
                $e->getMessage();

        }

    }

}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = 'Add Hotel';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container section">


    <!-- ============================================================
         PAGE HEADER
    ============================================================= -->

    <div class="section-head">

        <div>

            <h2>
                Add a New Hotel
            </h2>

            <p>
                Submit your property for admin review.
                It goes live once approved.
            </p>

        </div>

    </div>



    <!-- ============================================================
         DASHBOARD LAYOUT
    ============================================================= -->

    <div class="dash-layout">


        <!-- OWNER NAV -->

        <?php include __DIR__ . '/_nav.php'; ?>



        <!-- ========================================================
             MAIN PANEL
        ========================================================= -->

        <div class="panel">


            <!-- ====================================================
                 ERROR MESSAGE
            ===================================================== -->

            <?php if ($error): ?>

                <div class="alert alert-error">

                    <?= h($error) ?>

                </div>

            <?php endif; ?>



            <!-- ====================================================
                 SUCCESS MESSAGE
            ===================================================== -->

            <?php if ($success): ?>

                <div class="alert alert-success">

                    <?= h($success) ?>

                </div>

            <?php endif; ?>



            <!-- ====================================================
                 ADD HOTEL FORM
            ===================================================== -->

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- =================================================
                     HOTEL DETAILS
                ================================================== -->

                <div class="form-row">


                    <!-- HOTEL NAME -->

                    <div class="form-group">

                        <label for="name">
                            Hotel Name
                        </label>

                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="<?= h($_POST['name'] ?? '') ?>"
                            placeholder="e.g. Ocean View Hotel"
                            required
                        >

                    </div>



                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label for="address">
                            Address
                        </label>

                        <input
                            type="text"
                            id="address"
                            name="address"
                            value="<?= h($_POST['address'] ?? '') ?>"
                            placeholder="e.g. Beach Road"
                            required
                        >

                    </div>



                    <!-- CONTACT -->

                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <input
                            type="text"
                            id="contact_number"
                            name="contact_number"
                            value="<?= h($_POST['contact_number'] ?? '') ?>"
                            placeholder="+94 77 123 4567"
                        >

                    </div>



                    <!-- DISTRICT -->

                    <div class="form-group">

                        <label for="district">
                            District / Location
                        </label>

                        <input
                            type="text"
                            id="district"
                            name="district"
                            value="<?= h($_POST['district'] ?? '') ?>"
                            placeholder="e.g. Galle"
                            required
                        >

                    </div>



                    <!-- STARTING PRICE -->
                    <div class="form-group">

                        <label for="starting_price">
                            Starting Price (Rs.)
                        </label>

                        <input
                            type="number"
                            id="starting_price"
                            name="starting_price"
                            value="<?= h($_POST['starting_price'] ?? '') ?>"
                            min="500"
                            step="500"
                            placeholder="e.g. 2500"
                            required
                        >

                    </div>



                    <!-- MIN GUESTS -->

                    <div class="form-group">

                        <label for="min_guests">
                            Min Guests
                        </label>

                        <input
                            type="number"
                            id="min_guests"
                            name="min_guests"
                            value="<?= h($_POST['min_guests'] ?? '') ?>"
                            min="0"
                            placeholder="e.g. 50"
                        >

                    </div>



                    <!-- MAX GUESTS -->

                    <div class="form-group">

                        <label for="max_guests">
                            Max Guests
                        </label>

                        <input
                            type="number"
                            id="max_guests"
                            name="max_guests"
                            value="<?= h($_POST['max_guests'] ?? '') ?>"
                            min="0"
                            placeholder="e.g. 500"
                        >

                    </div>


                </div>



                <!-- =================================================
                     FUNCTION TYPES
                ================================================== -->

                <div class="form-group function-section">


                    <label class="function-title">

                        Function Types

                        <span class="required-star">
                            *
                        </span>

                    </label>



                    <?php if (empty($functionTypes)): ?>


                        <!-- NO FUNCTION TYPES -->

                        <div class="function-empty">

                            <strong>
                                No function types available.
                            </strong>

                            <p>
                                Please add function types to the
                                <strong>function_types</strong>
                                table in your database.
                            </p>

                        </div>


                    <?php else: ?>


                        <!-- FUNCTION GRID -->

                        <div class="function-type-grid">


                            <?php

                            $selectedFunctionIds =
                                array_map(
                                    'intval',
                                    $functions
                                );

                            ?>


                            <?php foreach (
                                $functionTypes
                                as $ft
                            ): ?>


                                <?php

                                $functionId =
                                    (int) $ft['id'];

                                $isChecked =
                                    in_array(
                                        $functionId,
                                        $selectedFunctionIds,
                                        true
                                    );

                                ?>


                                <label
                                    class="function-type-item <?= $isChecked ? 'selected' : '' ?>"
                                >


                                    <input
                                        type="checkbox"
                                        name="functions[]"
                                        value="<?= $functionId ?>"
                                        <?= $isChecked ? 'checked' : '' ?>
                                    >


                                    <span class="function-check">

                                        <?= $isChecked ? '✓' : '' ?>

                                    </span>


                                    <span class="function-name">

                                        <?= h($ft['name']) ?>

                                    </span>


                                </label>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </div>



                <!-- =================================================
                     DESCRIPTION
                ================================================== -->

                <div class="form-group">

                    <label for="description">
                        Description
                    </label>


                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="Describe your hotel, facilities, services and venue..."
                    ><?= h($_POST['description'] ?? '') ?></textarea>

                </div>



                <!-- =================================================
                     MAIN HOTEL IMAGE
                ================================================== -->

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

                        Recommended:
                        JPG, JPEG, PNG or WEBP.
                        Maximum recommended size: 5MB.

                    </small>

                </div>



                <!-- =================================================
                     SUBMIT BUTTON
                ================================================== -->

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