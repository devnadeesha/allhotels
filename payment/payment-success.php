<?php

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';


$userId = (int) ($_SESSION['user_id'] ?? 0);

$orderId = trim(
    $_GET['order_id'] ?? ''
);


if ($userId <= 0) {

    header(
        'Location: /allhotels/index.php'
    );

    exit;

}


$stmt = $pdo->prepare("
    SELECT
        h.id,
        h.name,
        h.is_premium
    FROM hotels h
    WHERE h.user_id = ?
    ORDER BY h.id DESC
    LIMIT 1
");

$stmt->execute([
    $userId
]);

$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

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
        Premium Activated | AllHotels.lk
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
                Arial,
                sans-serif;

            background:
                #f5f7fa;

        }


        .success-card {

            width: 100%;

            max-width: 500px;

            padding: 40px 30px;

            background: #fff;

            border-radius: 22px;

            text-align: center;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,.10);

        }


        .success-icon {

            width: 75px;

            height: 75px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #e8f8ee;

            color: #168344;

            font-size: 38px;

            font-weight: bold;

        }


        h1 {

            margin: 0 0 10px;

            font-size: 28px;

            color: #222;

        }


        .hotel-name {

            color: #555;

            font-weight: 600;

            margin-bottom: 15px;

        }


        p {

            color: #666;

            line-height: 1.6;

        }


        .premium-status {

            display: inline-block;

            margin: 15px 0 25px;

            padding: 8px 16px;

            border-radius: 20px;

            background: #fff4d6;

            color: #b17a00;

            font-weight: 700;

        }


        .dashboard-btn {

            display: block;

            width: 100%;

            padding: 14px;

            border-radius: 11px;

            background: #111827;

            color: #fff;

            text-decoration: none;

            font-weight: 700;

        }


        .dashboard-btn:hover {

            background: #000;

        }

    </style>

</head>


<body>


<div class="success-card">


    <div class="success-icon">

        ✓

    </div>


    <?php if (
        $hotel &&
        (int) $hotel['is_premium'] === 1
    ): ?>


        <h1>

            Premium Activated!

        </h1>


        <div class="hotel-name">

            <?= h($hotel['name']) ?>

        </div>


        <p>

            Your payment was successful and
            your hotel has been upgraded to
            Premium.

        </p>


        <div class="premium-status">

            ★ PREMIUM HOTEL

        </div>


    <?php else: ?>


        <h1>

            Payment Received

        </h1>


        <p>

            Your payment has been received.
            Premium activation is being processed.

        </p>


    <?php endif; ?>


    <a
        href="/allhotels/index.php"
        class="dashboard-btn"
    >

        Back to AllHotels.lk

    </a>


</div>


</body>

</html>