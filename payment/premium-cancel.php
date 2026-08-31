<?php

session_start();

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
        Payment Cancelled | AllHotels.lk
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

            font-family: Arial, sans-serif;

            background: #f5f7fa;

        }


        .card {

            width: 100%;

            max-width: 460px;

            background: #fff;

            padding: 40px 30px;

            border-radius: 22px;

            text-align: center;

            box-shadow:
                0 20px 60px
                rgba(0,0,0,.10);

        }


        .icon {

            width: 70px;

            height: 70px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #f3f4f6;

            color: #777;

            font-size: 32px;

        }


        h1 {

            margin: 0 0 12px;

            color: #222;

        }


        p {

            color: #666;

            line-height: 1.6;

            margin-bottom: 25px;

        }


        a {

            display: block;

            padding: 14px;

            border-radius: 11px;

            background: #111827;

            color: white;

            text-decoration: none;

            font-weight: 700;

        }

    </style>

</head>


<body>


<div class="card">


    <div class="icon">

        ×

    </div>


    <h1>

        Payment Cancelled

    </h1>


    <p>

        Your Premium payment was cancelled.
        Your hotel has not been upgraded.

    </p>


    <a
        href="/allhotels/index.php"
    >

        Back to AllHotels.lk

    </a>


</div>


</body>

</html>