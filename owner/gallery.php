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
| Validate Selected Hotel
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
| DELETE IMAGE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete_image'
) {

    $hotelId = (int) ($_POST['hotel_id'] ?? 0);
    $imageId = (int) ($_POST['image_id'] ?? 0);


    /*
    |--------------------------------------------------------------------------
    | Check Hotel Belongs To Current Owner
    |--------------------------------------------------------------------------
    */

    $checkHotel = $pdo->prepare("
        SELECT id
        FROM hotels
        WHERE id = ?
          AND user_id = ?
          AND is_premium = 1
          AND status = 'approved'
        LIMIT 1
    ");

    $checkHotel->execute([
        $hotelId,
        $user['id']
    ]);

    $hotelExists = $checkHotel->fetchColumn();


    if (!$hotelExists) {

        $error = 'Invalid hotel selected.';

    } else {


        /*
        |--------------------------------------------------------------------------
        | Get Image
        |--------------------------------------------------------------------------
        */

        $imageStmt = $pdo->prepare("
            SELECT *
            FROM hotel_images
            WHERE id = ?
              AND hotel_id = ?
            LIMIT 1
        ");

        $imageStmt->execute([
            $imageId,
            $hotelId
        ]);

        $image = $imageStmt->fetch(PDO::FETCH_ASSOC);


        if (!$image) {

            $error = 'Image not found.';

        } else {

            try {

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | Delete Database Record
                |--------------------------------------------------------------------------
                */

                $deleteStmt = $pdo->prepare("
                    DELETE FROM hotel_images
                    WHERE id = ?
                      AND hotel_id = ?
                ");

                $deleteStmt->execute([
                    $imageId,
                    $hotelId
                ]);


                /*
                |--------------------------------------------------------------------------
                | If Deleted Image Was Main
                |--------------------------------------------------------------------------
                */

                if ((int) $image['is_main'] === 1) {

                    $newMainStmt = $pdo->prepare("
                        SELECT id
                        FROM hotel_images
                        WHERE hotel_id = ?
                        ORDER BY id DESC
                        LIMIT 1
                    ");

                    $newMainStmt->execute([
                        $hotelId
                    ]);

                    $newMainId = $newMainStmt->fetchColumn();


                    if ($newMainId) {

                        $makeMainStmt = $pdo->prepare("
                            UPDATE hotel_images
                            SET is_main = 1
                            WHERE id = ?
                        ");

                        $makeMainStmt->execute([
                            $newMainId
                        ]);
                    }
                }


                $pdo->commit();


                /*
                |--------------------------------------------------------------------------
                | Delete Physical File
                |--------------------------------------------------------------------------
                */

                $relativePath = ltrim(
                    $image['image_path'],
                    '/'
                );

                $physicalPath =
                    dirname(__DIR__) .
                    '/' .
                    $relativePath;


                if (
                    file_exists($physicalPath)
                    && is_file($physicalPath)
                ) {

                    @unlink($physicalPath);

                }


                $success = 'Image deleted successfully.';


            } catch (Exception $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error =
                    'Unable to delete image: ' .
                    $e->getMessage();
            }
        }
    }


    $selectedId = $hotelId;
}


/*
|--------------------------------------------------------------------------
| HANDLE IMAGE UPLOAD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'upload_image'
) {

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
        !isset($_FILES['gallery_image'])
        || $_FILES['gallery_image']['error'] !== UPLOAD_ERR_OK
    ) {

        $error = 'Please select an image to upload.';

    } else {

        $file = $_FILES['gallery_image'];

        $originalName = $file['name'];
        $tmpName      = $file['tmp_name'];
        $fileSize     = (int) $file['size'];

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Validate Extension
        |--------------------------------------------------------------------------
        */

        $allowedExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];


        if (!in_array(
            $extension,
            $allowedExtensions,
            true
        )) {

            $error =
                'Only JPG, JPEG, PNG and WEBP images are allowed.';

        }


        /*
        |--------------------------------------------------------------------------
        | Validate Size
        |--------------------------------------------------------------------------
        */

        elseif ($fileSize > 5 * 1024 * 1024) {

            $error =
                'Image size must be less than 5MB.';

        }


        /*
        |--------------------------------------------------------------------------
        | Validate Real Image
        |--------------------------------------------------------------------------
        */

        elseif (@getimagesize($tmpName) === false) {

            $error =
                'The uploaded file is not a valid image.';

        }


        /*
        |--------------------------------------------------------------------------
        | Upload
        |--------------------------------------------------------------------------
        */

        if (!$error) {

            /*
            |--------------------------------------------------------------------------
            | Upload Directory
            |--------------------------------------------------------------------------
            */

            $uploadDir =
                __DIR__ .
                '/../api/images/';


            /*
            |--------------------------------------------------------------------------
            | Create Directory
            |--------------------------------------------------------------------------
            */

            if (!is_dir($uploadDir)) {

                if (!mkdir(
                    $uploadDir,
                    0755,
                    true
                )) {

                    $error =
                        'Unable to create image upload folder.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Check Writable
            |--------------------------------------------------------------------------
            */

            if (
                !$error
                && !is_writable($uploadDir)
            ) {

                $error =
                    'Image folder is not writable: ' .
                    $uploadDir;
            }


            /*
            |--------------------------------------------------------------------------
            | Generate Filename
            |--------------------------------------------------------------------------
            */

            if (!$error) {

                $filename =
                    'hotel_' .
                    $hotelId .
                    '_gallery_' .
                    time() .
                    '_' .
                    bin2hex(random_bytes(5)) .
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

                    $error =
                        'Unable to save the uploaded image.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Save Database
            |--------------------------------------------------------------------------
            */

            if (!$error) {

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Check Main Image
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
                        $mainCheckStmt->fetchColumn();


                    /*
                    |--------------------------------------------------------------------------
                    | First Image = Main Image
                    |--------------------------------------------------------------------------
                    */

                    $isMain =
                        $hasMainImage ? 0 : 1;


                    /*
                    |--------------------------------------------------------------------------
                    | Insert Image
                    |--------------------------------------------------------------------------
                    */

                    $insertStmt = $pdo->prepare("
                        INSERT INTO hotel_images
                        (
                            hotel_id,
                            image_path,
                            is_main
                        )
                        VALUES (?, ?, ?)
                    ");

                    $insertStmt->execute([
                        $hotelId,
                        'api/images/' . $filename,
                        $isMain
                    ]);


                    $success =
                        'Image uploaded successfully.';


                } catch (PDOException $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Remove Physical File If DB Failed
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset($destination)
                        && file_exists($destination)
                    ) {

                        @unlink($destination);
                    }


                    $error =
                        'Image could not be saved to database.';

                    error_log(
                        'Gallery Upload Error: ' .
                        $e->getMessage()
                    );
                }
            }
        }
    }


    $selectedId = $hotelId;
}


/*
|--------------------------------------------------------------------------
| Refresh Selected Hotel
|--------------------------------------------------------------------------
*/

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

    $selectedHotel =
        $hotelStmt->fetch(PDO::FETCH_ASSOC);
}


/*
|--------------------------------------------------------------------------
| Get Images
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

    $images =
        $imgStmt->fetchAll(PDO::FETCH_ASSOC);
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


    <!-- PAGE HEADER -->

    <div class="section-head">

        <div>

            <h2>Hotel Gallery</h2>

            <p>
                Manage photos and hotel information
                for your Premium hotels.
            </p>

        </div>

    </div>


    <div class="dash-layout">


        <!-- OWNER NAV -->

        <?php include __DIR__ . '/_nav.php'; ?>


        <div>


            <!-- ALERTS -->

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


            <?php if (empty($premiumHotels)): ?>


                <div class="panel">

                    <h3>Premium Gallery</h3>

                    <p class="footer-note">
                        You don't have any approved Premium
                        hotels yet.
                    </p>

                </div>


            <?php else: ?>


                <!-- HOTEL SELECTOR -->

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
                                <?= h($selectedHotel['name']) ?>
                            </strong>

                            <div class="footer-note">
                                ★ Premium Hotel
                                —
                                <?= h($selectedHotel['district']) ?>
                            </div>

                        </div>


                        <!-- EDIT HOTEL BUTTON -->

                        <div
                            style="
                                margin-top:15px;
                                display:flex;
                                gap:10px;
                                flex-wrap:wrap;
                            "
                        >

                            <a
                                href="/allhotels/owner/edit-hotel.php?id=<?= (int) $selectedId ?>"
                                class="btn btn-primary"
                            >
                                Edit Hotel Profile
                            </a>

                        </div>

                    <?php endif; ?>

                </div>


                <?php if ($selectedHotel): ?>


                    <!-- UPLOAD IMAGE -->

                    <div class="panel">

                        <h3>Upload New Image</h3>

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
                                name="action"
                                value="upload_image"
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


                    <!-- CURRENT IMAGES -->

                    <div class="panel">

                        <h3>Current Gallery</h3>


                        <?php if (empty($images)): ?>

                            <p class="footer-note">
                                No images uploaded yet.
                            </p>

                        <?php else: ?>


                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:
                                    repeat(
                                        auto-fill,
                                        minmax(200px,1fr)
                                    );
                                    gap:20px;
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
                                            box-shadow:
                                            0 3px 12px
                                            rgba(0,0,0,.08);
                                        "
                                    >


                                        <img
                                            src="/allhotels/<?= h($imagePath) ?>"
                                            alt="Hotel Image"
                                            style="
                                                width:100%;
                                                height:190px;
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
                                                    background:#111;
                                                    color:#fff;
                                                    padding:5px 9px;
                                                    border-radius:5px;
                                                    font-size:12px;
                                                "
                                            >
                                                ★ Main Image
                                            </div>

                                        <?php endif; ?>


                                        <!-- DELETE BUTTON -->

                                        <div
                                            style="
                                                padding:12px;
                                                background:#fff;
                                            "
                                        >

                                            <form
                                                method="POST"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to delete this image?'
                                                    );
                                                "
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete_image"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="hotel_id"
                                                    value="<?= (int) $selectedId ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="image_id"
                                                    value="<?= (int) $image['id'] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="btn btn-sm"
                                                    style="
                                                        width:100%;
                                                        background:#c0392b;
                                                        color:#fff;
                                                        border:none;
                                                    "
                                                >
                                                    🗑 Delete Image
                                                </button>

                                            </form>

                                        </div>

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