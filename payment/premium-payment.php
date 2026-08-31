<?php

/*
|--------------------------------------------------------------------------
| PREMIUM PAYMENT - PAYHERE
|--------------------------------------------------------------------------
|
| File:
| /allhotels/payment/premium-payment.php
|
| Flow:
|
| Premium Popup
|      ↓
| premium-payment.php
|      ↓
| PayHere Checkout
|      ↓
| Payment
|      ↓
| notify_url
|      ↓
| Verify payment
|      ↓
| is_premium = 1
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config/db.php';

require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId = (int) ($_SESSION['user_id'] ?? 0);


if ($userId <= 0) {

    http_response_code(403);

    exit('Please login first.');

}


/*
|--------------------------------------------------------------------------
| HOTEL ID
|--------------------------------------------------------------------------
*/

$hotelId = (int) ($_GET['hotel_id'] ?? 0);


if ($hotelId <= 0) {

    http_response_code(400);

    exit('Invalid hotel.');

}


/*
|--------------------------------------------------------------------------
| GET OWNER + HOTEL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT

        u.id AS user_id,
        u.full_name,
        u.email,
        u.phone,
        u.business_address,
        u.role,

        h.id AS hotel_id,
        h.name AS hotel_name,
        h.is_premium

    FROM users u

    INNER JOIN hotels h
        ON h.user_id = u.id

    WHERE u.id = ?
      AND h.id = ?

    LIMIT 1
");

$stmt->execute([
    $userId,
    $hotelId
]);

$data = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| VALIDATE OWNER
|--------------------------------------------------------------------------
*/

if (!$data) {

    http_response_code(403);

    exit('You are not authorized to pay for this hotel.');

}


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
*/

if (
    strtolower(trim($data['role'])) !== 'owner'
) {

    http_response_code(403);

    exit('Only hotel owners can activate Premium.');

}


/*
|--------------------------------------------------------------------------
| CHECK PREMIUM
|--------------------------------------------------------------------------
*/

if ((int) $data['is_premium'] === 1) {

    header(
        'Location: /allhotels/index.php'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| PAYHERE CONFIGURATION
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| Replace these values with your PayHere credentials.
|
|--------------------------------------------------------------------------
*/

$merchant_id = 'YOUR_PAYHERE_MERCHANT_ID';

$merchant_secret = 'YOUR_PAYHERE_MERCHANT_SECRET';


/*
|--------------------------------------------------------------------------
| PAYHERE MODE
|--------------------------------------------------------------------------
|
| true  = Sandbox
| false = Live
|
|--------------------------------------------------------------------------
*/

$payhereSandbox = true;


/*
|--------------------------------------------------------------------------
| PAYHERE CHECKOUT URL
|--------------------------------------------------------------------------
*/

if ($payhereSandbox) {

    $payhereUrl =
        'https://sandbox.payhere.lk/pay/checkout';

} else {

    $payhereUrl =
        'https://www.payhere.lk/pay/checkout';

}


/*
|--------------------------------------------------------------------------
| PREMIUM PRICE
|--------------------------------------------------------------------------
|
| Change this amount to your actual Premium price.
|
|--------------------------------------------------------------------------
*/

$premiumAmount = 5000.00;

$currency = 'LKR';


/*
|--------------------------------------------------------------------------
| ORDER ID
|--------------------------------------------------------------------------
|
| Unique order ID for this payment.
|
|--------------------------------------------------------------------------
*/

$orderId =
    'PREMIUM-' .
    $hotelId .
    '-' .
    time();


/*
|--------------------------------------------------------------------------
| ITEM NAME
|--------------------------------------------------------------------------
*/

$itemName =
    'AllHotels.lk Premium - ' .
    $data['hotel_name'];


/*
|--------------------------------------------------------------------------
| CUSTOMER NAME
|--------------------------------------------------------------------------
|
| PayHere needs first_name and last_name separately.
|
|--------------------------------------------------------------------------
*/

$fullName =
    trim($data['full_name'] ?? 'Hotel Owner');


$nameParts =
    preg_split(
        '/\s+/',
        $fullName
    );


$firstName =
    $nameParts[0] ?? 'Hotel';


if (count($nameParts) > 1) {

    $lastName =
        implode(
            ' ',
            array_slice(
                $nameParts,
                1
            )
        );

} else {

    $lastName = 'Owner';

}


/*
|--------------------------------------------------------------------------
| CUSTOMER PHONE
|--------------------------------------------------------------------------
*/

$phone =
    trim($data['phone'] ?? '');


if ($phone === '') {

    $phone = '0770000000';

}


/*
|--------------------------------------------------------------------------
| CUSTOMER ADDRESS
|--------------------------------------------------------------------------
*/

$address =
    trim(
        $data['business_address'] ?? ''
    );


if ($address === '') {

    $address = 'Sri Lanka';

}


/*
|--------------------------------------------------------------------------
| CITY
|--------------------------------------------------------------------------
*/

$city = 'Colombo';


/*
|--------------------------------------------------------------------------
| COUNTRY
|--------------------------------------------------------------------------
*/

$country = 'Sri Lanka';


/*
|--------------------------------------------------------------------------
| RETURN URL
|--------------------------------------------------------------------------
|
| User comes here after PayHere payment.
|
|--------------------------------------------------------------------------
*/

$returnUrl =
    'http://localhost/allhotels/payment/premium-success.php'
    . '?order_id='
    . urlencode($orderId);


/*
|--------------------------------------------------------------------------
| CANCEL URL
|--------------------------------------------------------------------------
*/

$cancelUrl =
    'http://localhost/allhotels/payment/premium-cancel.php'
    . '?order_id='
    . urlencode($orderId);


/*
|--------------------------------------------------------------------------
| NOTIFY URL
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| PayHere server must be able to access this URL.
|
| localhost will NOT work for PayHere notifications.
|
| When your website is online, change this to:
|
| https://yourdomain.com/allhotels/payment/payment-notify.php
|
|--------------------------------------------------------------------------
*/

$notifyUrl =
    'http://localhost/allhotels/payment/payment-notify.php';


/*
|--------------------------------------------------------------------------
| FORMAT AMOUNT
|--------------------------------------------------------------------------
*/

$formattedAmount =
    number_format(
        $premiumAmount,
        2,
        '.',
        ''
    );


/*
|--------------------------------------------------------------------------
| GENERATE PAYHERE HASH
|--------------------------------------------------------------------------
|
| PayHere:
|
| hash =
| MD5(
|   merchant_id
|   + order_id
|   + amount
|   + currency
|   + MD5(merchant_secret)
| )
|
|--------------------------------------------------------------------------
*/

$hash =
    strtoupper(
        md5(
            $merchant_id .
            $orderId .
            $formattedAmount .
            $currency .
            strtoupper(
                md5(
                    $merchant_secret
                )
            )
        )
    );


?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Secure Payment | AllHotels.lk
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background:
                #f5f7fa;

            color: #1f2937;

        }


        .payment-card {

            width: 100%;

            max-width: 460px;

            background: #ffffff;

            border-radius: 22px;

            padding: 35px;

            text-align: center;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,0.10);

        }


        .payment-icon {

            width: 65px;

            height: 65px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 18px;

            background: #fff4d6;

            color: #d99a00;

            font-size: 30px;

        }


        h1 {

            margin: 0 0 8px;

            font-size: 25px;

        }


        .hotel-name {

            margin: 0 0 20px;

            color: #6b7280;

            font-weight: 600;

        }


        .amount {

            margin: 20px 0;

            font-size: 32px;

            font-weight: 800;

            color: #111827;

        }


        .description {

            color: #6b7280;

            font-size: 14px;

            line-height: 1.6;

            margin-bottom: 25px;

        }


        .secure {

            font-size: 12px;

            color: #6b7280;

            margin-top: 16px;

        }


        .loading {

            display: inline-flex;

            align-items: center;

            gap: 8px;

        }


        .spinner {

            width: 16px;

            height: 16px;

            border: 2px solid #ddd;

            border-top-color: #111827;

            border-radius: 50%;

            animation:
                spin 0.8s linear infinite;

        }


        @keyframes spin {

            to {
                transform: rotate(360deg);
            }

        }

    </style>

</head>


<body>


<div class="payment-card">


    <div class="payment-icon">

        ★

    </div>


    <h1>

        Premium Upgrade

    </h1>


    <p class="hotel-name">

        <?= h($data['hotel_name']) ?>

    </p>


    <div class="amount">

        Rs.
        <?= number_format($premiumAmount, 2) ?>

    </div>


    <p class="description">

        You are being redirected to the secure
        PayHere payment gateway to activate
        Premium for your hotel.

    </p>


    <div class="loading">

        <span class="spinner"></span>

        Redirecting to PayHere...

    </div>


    <p class="secure">

        🔒 Secure payment powered by PayHere

    </p>


</div>


<!-- ============================================================
     PAYHERE FORM
============================================================ -->

<form
    method="POST"
    action="<?= h($payhereUrl) ?>"
    id="payhereForm"
>


    <!-- MERCHANT -->

    <input
        type="hidden"
        name="merchant_id"
        value="<?= h($merchant_id) ?>"
    >


    <!-- RETURN -->

    <input
        type="hidden"
        name="return_url"
        value="<?= h($returnUrl) ?>"
    >


    <!-- CANCEL -->

    <input
        type="hidden"
        name="cancel_url"
        value="<?= h($cancelUrl) ?>"
    >


    <!-- NOTIFY -->

    <input
        type="hidden"
        name="notify_url"
        value="<?= h($notifyUrl) ?>"
    >


    <!-- ORDER -->

    <input
        type="hidden"
        name="order_id"
        value="<?= h($orderId) ?>"
    >


    <!-- ITEMS -->

    <input
        type="hidden"
        name="items"
        value="<?= h($itemName) ?>"
    >


    <!-- CURRENCY -->

    <input
        type="hidden"
        name="currency"
        value="<?= h($currency) ?>"
    >


    <!-- AMOUNT -->

    <input
        type="hidden"
        name="amount"
        value="<?= h($formattedAmount) ?>"
    >


    <!-- HASH -->

    <input
        type="hidden"
        name="hash"
        value="<?= h($hash) ?>"
    >


    <!-- FIRST NAME -->

    <input
        type="hidden"
        name="first_name"
        value="<?= h($firstName) ?>"
    >


    <!-- LAST NAME -->

    <input
        type="hidden"
        name="last_name"
        value="<?= h($lastName) ?>"
    >


    <!-- EMAIL -->

    <input
        type="hidden"
        name="email"
        value="<?= h($data['email']) ?>"
    >


    <!-- PHONE -->

    <input
        type="hidden"
        name="phone"
        value="<?= h($phone) ?>"
    >


    <!-- ADDRESS -->

    <input
        type="hidden"
        name="address"
        value="<?= h($address) ?>"
    >


    <!-- CITY -->

    <input
        type="hidden"
        name="city"
        value="<?= h($city) ?>"
    >


    <!-- COUNTRY -->

    <input
        type="hidden"
        name="country"
        value="<?= h($country) ?>"
    >


    <!-- HOTEL ID -->

    <input
        type="hidden"
        name="custom_1"
        value="<?= (int) $hotelId ?>"
    >


    <!-- USER ID -->

    <input
        type="hidden"
        name="custom_2"
        value="<?= (int) $userId ?>"
    >

</form>


<script>

/*
|--------------------------------------------------------------------------
| AUTOMATICALLY SUBMIT TO PAYHERE
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'load',
    function () {

        document
            .getElementById('payhereForm')
            .submit();

    }
);

</script>


</body>

</html>