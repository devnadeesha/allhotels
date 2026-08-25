<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('owner');

$user = current_user();

$error = null;
$success = null;


/*
|--------------------------------------------------------------------------
| Get Hotel ID
|--------------------------------------------------------------------------
*/

$hotelId = (int) (
    $_GET['id']
    ?? $_POST['hotel_id']
    ?? 0
);


if ($hotelId <= 0) {

    http_response_code(404);

    die('Hotel not found.');

}


/*
|--------------------------------------------------------------------------
| Get Hotel
|--------------------------------------------------------------------------
*/

$hotelStmt = $pdo->prepare("
    SELECT *
    FROM hotels
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$hotelStmt->execute([
    $hotelId,
    $user['id']
]);

$hotel = $hotelStmt->fetch(PDO::FETCH_ASSOC);


if (!$hotel) {

    http_response_code(404);

    die('Hotel not found or you do not have permission to edit it.');

}


/*
|--------------------------------------------------------------------------
| Get Function Types
|--------------------------------------------------------------------------
*/

$functionStmt = $pdo->query("
    SELECT id, name
    FROM function_types
    ORDER BY name ASC
");

$functionTypes =
    $functionStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Current Function Types
|--------------------------------------------------------------------------
*/

$currentFunctionStmt = $pdo->prepare("
    SELECT function_type_id
    FROM hotel_function_types
    WHERE hotel_id = ?
");

$currentFunctionStmt->execute([
    $hotelId
]);

$currentFunctions =
    $currentFunctionStmt->fetchAll(
        PDO::FETCH_COLUMN
    );

$currentFunctions =
    array_map(
        'intval',
        $currentFunctions
    );


/*
|--------------------------------------------------------------------------
| Handle Update
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name =
        trim($_POST['name'] ?? '');

    $address =
        trim($_POST['address'] ?? '');

    $district =
        trim($_POST['district'] ?? '');

    $contact =
        trim($_POST['contact_number'] ?? '');

    $price =
        (float) ($_POST['starting_price'] ?? 0);

    $minGuests =
        (int) ($_POST['min_guests'] ?? 0);

    $maxGuests =
        (int) ($_POST['max_guests'] ?? 0);

    $description =
        trim($_POST['description'] ?? '');

    $functions =
        $_POST['functions'] ?? [];


    if (!is_array($functions)) {
        $functions = [];
    }


    $functions =
        array_values(
            array_unique(
                array_map(
                    'intval',
                    $functions
                )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $name === ''
        || $address === ''
        || $district === ''
        || $price <= 0
        || empty($functions)
    ) {

        $error =
            'Please complete all required fields and select at least one function type.';

    } elseif (
        $minGuests > 0
        && $maxGuests > 0
        && $minGuests > $maxGuests
    ) {

        $error =
            'Minimum guests cannot be greater than maximum guests.';

    } else {

        try {

            $pdo->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | Update Hotel
            |--------------------------------------------------------------------------
            */

            $updateStmt = $pdo->prepare("
                UPDATE hotels
                SET
                    name = ?,
                    address = ?,
                    district = ?,
                    contact_number = ?,
                    starting_price = ?,
                    min_guests = ?,
                    max_guests = ?,
                    description = ?
                WHERE id = ?
                  AND user_id = ?
            ");

            $updateStmt->execute([
                $name,
                $address,
                $district,
                $contact,
                $price,
                $minGuests,
                $maxGuests,
                $description,
                $hotelId,
                $user['id']
            ]);


            /*
            |--------------------------------------------------------------------------
            | Delete Existing Function Relationships
            |--------------------------------------------------------------------------
            */

            $deleteFunctionsStmt = $pdo->prepare("
                DELETE FROM hotel_function_types
                WHERE hotel_id = ?
            ");

            $deleteFunctionsStmt->execute([
                $hotelId
            ]);


            /*
            |--------------------------------------------------------------------------
            | Insert New Function Relationships
            |--------------------------------------------------------------------------
            */

            $insertFunctionStmt = $pdo->prepare("
                INSERT INTO hotel_function_types
                (
                    hotel_id,
                    function_type_id
                )
                VALUES (?, ?)
            ");


            foreach ($functions as $functionId) {

                if ($functionId <= 0) {
                    continue;
                }

                $insertFunctionStmt->execute([
                    $hotelId,
                    $functionId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            $pdo->commit();


            /*
            |--------------------------------------------------------------------------
            | Update Local Hotel Data
            |--------------------------------------------------------------------------
            */

            $hotel['name'] =
                $name;

            $hotel['address'] =
                $address;

            $hotel['district'] =
                $district;

            $hotel['contact_number'] =
                $contact;

            $hotel['starting_price'] =
                $price;

            $hotel['min_guests'] =
                $minGuests;

            $hotel['max_guests'] =
                $maxGuests;

            $hotel['description'] =
                $description;

            $currentFunctions =
                $functions;


            $success =
                'Hotel profile updated successfully.';


        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }


            $error =
                'Unable to update hotel: ' .
                $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

$page_title = 'Edit Hotel';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container section">


    <!-- HEADER -->

    <div class="section-head">

        <div>

            <h2>
                Edit Hotel Profile
            </h2>

            <p>
                Update your hotel information and
                function types.
            </p>

        </div>


        <a
            href="/allhotels/owner/gallery.php?hotel_id=<?= (int) $hotelId ?>"
            class="btn btn-outline"
        >
            ← Back to Gallery
        </a>

    </div>


    <div class="dash-layout">


        <!-- OWNER NAV -->

        <?php include __DIR__ . '/_nav.php'; ?>


        <div class="panel">


            <!-- ERROR -->

            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>


            <!-- SUCCESS -->

            <?php if ($success): ?>

                <div class="alert alert-success">
                    <?= h($success) ?>
                </div>

            <?php endif; ?>


            <form
                method="POST"
            >

                <input
                    type="hidden"
                    name="hotel_id"
                    value="<?= (int) $hotelId ?>"
                >


                <!-- HOTEL DETAILS -->

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
                            value="<?= h($hotel['name']) ?>"
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
                            value="<?= h($hotel['address']) ?>"
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
                            value="<?= h($hotel['contact_number'] ?? '') ?>"
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
                            value="<?= h($hotel['district']) ?>"
                            required
                        >

                    </div>


                    <!-- PRICE -->

                    <div class="form-group">

                        <label for="starting_price">
                            Starting Price (Rs.)
                        </label>

                        <input
                            type="number"
                            id="starting_price"
                            name="starting_price"
                            value="<?= h($hotel['starting_price']) ?>"
                            min="0"
                            step="500"
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
                            value="<?= h($hotel['min_guests'] ?? 0) ?>"
                            min="0"
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
                            value="<?= h($hotel['max_guests'] ?? 0) ?>"
                            min="0"
                        >

                    </div>

                </div>


                <!-- FUNCTION TYPES -->

                <div
                    class="form-group"
                    style="margin-top:20px;"
                >

                    <label
                        style="
                            display:block;
                            margin-bottom:12px;
                            font-weight:600;
                        "
                    >
                        Function Types
                        <span style="color:#c0392b;">*</span>
                    </label>


                    <?php if (empty($functionTypes)): ?>

                        <div class="alert alert-error">

                            No function types found.

                            Please add function types
                            to the
                            <strong>
                                function_types
                            </strong>
                            table.

                        </div>

                    <?php else: ?>


                        <div
                            style="
                                display:grid;
                                grid-template-columns:
                                repeat(
                                    auto-fill,
                                    minmax(180px,1fr)
                                );
                                gap:12px;
                            "
                        >


                            <?php foreach (
                                $functionTypes
                                as $ft
                            ): ?>


                                <?php

                                $fid =
                                    (int) $ft['id'];

                                $checked =
                                    in_array(
                                        $fid,
                                        $currentFunctions,
                                        true
                                    );

                                ?>


                                <label
                                    style="
                                        display:flex;
                                        align-items:center;
                                        gap:10px;
                                        padding:12px;
                                        border:1px solid
                                        #ddd;
                                        border-radius:8px;
                                        cursor:pointer;
                                    "
                                >

                                    <input
                                        type="checkbox"
                                        name="functions[]"
                                        value="<?= $fid ?>"
                                        <?= $checked
                                            ? 'checked'
                                            : ''
                                        ?>
                                    >

                                    <span>
                                        <?= h($ft['name']) ?>
                                    </span>

                                </label>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </div>


                <!-- DESCRIPTION -->

                <div
                    class="form-group"
                    style="margin-top:20px;"
                >

                    <label for="description">
                        Description
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="6"
                        placeholder="Describe your hotel..."
                    ><?= h($hotel['description'] ?? '') ?></textarea>

                </div>


                <!-- BUTTONS -->

                <div
                    style="
                        display:flex;
                        gap:10px;
                        flex-wrap:wrap;
                        margin-top:20px;
                    "
                >

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>


                    <a
                        href="/allhotels/owner/gallery.php?hotel_id=<?= (int) $hotelId ?>"
                        class="btn btn-outline"
                    >
                        Cancel
                    </a>

                </div>


            </form>


        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>