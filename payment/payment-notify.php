<?php

/*
|--------------------------------------------------------------------------
| PAYHERE PAYMENT NOTIFICATION
|--------------------------------------------------------------------------
|
| File:
| /allhotels/payment/payment-notify.php
|
| PayHere payment success:
|
| PayHere
|    ↓
| This file
|    ↓
| Verify md5sig
|    ↓
| Check status_code = 2
|    ↓
| Activate hotel Premium
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| PAYHERE CONFIGURATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
| Use the SAME Merchant ID and Merchant Secret
| used in premium-payment.php
|
|--------------------------------------------------------------------------
*/

$merchant_id = 'YOUR_PAYHERE_MERCHANT_ID';

$merchant_secret = 'YOUR_PAYHERE_MERCHANT_SECRET';


/*
|--------------------------------------------------------------------------
| RECEIVE PAYHERE DATA
|--------------------------------------------------------------------------
*/

$merchant_id_received = $_POST['merchant_id'] ?? '';

$order_id = $_POST['order_id'] ?? '';

$payhere_amount = $_POST['payhere_amount'] ?? '';

$payhere_currency = $_POST['payhere_currency'] ?? '';

$status_code = (int) ($_POST['status_code'] ?? 0);

$md5sig = strtoupper(
    trim($_POST['md5sig'] ?? '')
);


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $merchant_id_received === '' ||
    $order_id === '' ||
    $payhere_amount === '' ||
    $payhere_currency === '' ||
    $md5sig === ''
) {

    http_response_code(400);

    exit('Invalid payment notification.');

}


/*
|--------------------------------------------------------------------------
| VERIFY MERCHANT
|--------------------------------------------------------------------------
*/

if (
    $merchant_id_received !== $merchant_id
) {

    http_response_code(403);

    exit('Invalid merchant.');

}


/*
|--------------------------------------------------------------------------
| GENERATE EXPECTED HASH
|--------------------------------------------------------------------------
|
| PayHere md5sig:
|
| MD5(
|   merchant_id
|   + order_id
|   + payhere_amount
|   + payhere_currency
|   + status_code
|   + MD5(merchant_secret)
| )
|
|--------------------------------------------------------------------------
*/

$merchant_secret_hash =
    strtoupper(
        md5(
            $merchant_secret
        )
    );


$expected_hash =
    strtoupper(
        md5(
            $merchant_id_received .
            $order_id .
            $payhere_amount .
            $payhere_currency .
            $status_code .
            $merchant_secret_hash
        )
    );


/*
|--------------------------------------------------------------------------
| VERIFY SIGNATURE
|--------------------------------------------------------------------------
*/

if (
    !hash_equals(
        $expected_hash,
        $md5sig
    )
) {

    http_response_code(403);

    exit('Invalid payment signature.');

}


/*
|--------------------------------------------------------------------------
| PAYMENT SUCCESS
|--------------------------------------------------------------------------
|
| PayHere status_code:
|
| 2 = Success
|
|--------------------------------------------------------------------------
*/

if ($status_code !== 2) {

    /*
    | Payment was not successful.
    |
    | Do not activate Premium.
    */

    http_response_code(200);

    exit('Payment not successful.');

}


/*
|--------------------------------------------------------------------------
| GET HOTEL ID
|--------------------------------------------------------------------------
|
| premium-payment.php sends:
|
| custom_1 = hotel_id
|
|--------------------------------------------------------------------------
*/

$hotelId = (int) (
    $_POST['custom_1'] ?? 0
);


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
|
| premium-payment.php sends:
|
| custom_2 = user_id
|
|--------------------------------------------------------------------------
*/

$userId = (int) (
    $_POST['custom_2'] ?? 0
);


/*
|--------------------------------------------------------------------------
| VALIDATE IDS
|--------------------------------------------------------------------------
*/

if (
    $hotelId <= 0 ||
    $userId <= 0
) {

    http_response_code(400);

    exit('Invalid hotel information.');

}


/*
|--------------------------------------------------------------------------
| CHECK HOTEL OWNER
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        user_id,
        is_premium
    FROM hotels
    WHERE id = ?
      AND user_id = ?
    LIMIT 1
");

$stmt->execute([
    $hotelId,
    $userId
]);

$hotel = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| HOTEL NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$hotel) {

    http_response_code(404);

    exit('Hotel not found.');

}


/*
|--------------------------------------------------------------------------
| ALREADY PREMIUM
|--------------------------------------------------------------------------
|
| This also makes the operation safe if PayHere
| sends the notification more than once.
|
|--------------------------------------------------------------------------
*/

if ((int) $hotel['is_premium'] === 1) {

    http_response_code(200);

    exit('Premium already activated.');

}


/*
|--------------------------------------------------------------------------
| ACTIVATE PREMIUM
|--------------------------------------------------------------------------
*/

try {

    $updateStmt = $pdo->prepare("
        UPDATE hotels

        SET is_premium = 1

        WHERE id = ?
          AND user_id = ?
          AND is_premium = 0

        LIMIT 1
    ");


    $updateStmt->execute([
        $hotelId,
        $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | CHECK RESULT
    |--------------------------------------------------------------------------
    */

    if ($updateStmt->rowCount() > 0) {

        http_response_code(200);

        echo 'Premium activated successfully.';

        exit;

    }


    /*
    |--------------------------------------------------------------------------
    | DOUBLE CHECK
    |--------------------------------------------------------------------------
    */

    $checkStmt = $pdo->prepare("
        SELECT is_premium
        FROM hotels
        WHERE id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $checkStmt->execute([
        $hotelId,
        $userId
    ]);

    $check = $checkStmt->fetch(PDO::FETCH_ASSOC);


    if (
        $check &&
        (int) $check['is_premium'] === 1
    ) {

        http_response_code(200);

        echo 'Premium already activated.';

        exit;

    }


    http_response_code(500);

    echo 'Unable to activate Premium.';

    exit;


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    echo 'Database error.';

    exit;

}