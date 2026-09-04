<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// PHPMailer
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

// Mail configuration
require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| GET EMAIL FROM SESSION
|--------------------------------------------------------------------------
*/

$email =
    $_SESSION['verify_email']
    ?? '';


/*
|--------------------------------------------------------------------------
| EMAIL NOT AVAILABLE
|--------------------------------------------------------------------------
*/

if ($email === '') {

    redirect(
        '../register/register.php'
    );
}


/*
|--------------------------------------------------------------------------
| FIND PENDING REGISTRATION
|--------------------------------------------------------------------------
*/

$stmt =
    $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            otp_code,
            otp_expires_at
         FROM pending_registrations
         WHERE email = ?
         LIMIT 1"
    );


$stmt->execute([
    $email
]);


$pending =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


/*
|--------------------------------------------------------------------------
| PENDING REGISTRATION NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$pending) {

    unset(
        $_SESSION['verify_email'],
        $_SESSION['verify_otp_sent_at'],
        $_SESSION['verify_otp_expires_at']
    );


    $_SESSION['flash_error'] =
        'Registration session not found. Please register again.';


    redirect(
        '../register/register.php'
    );
}


/*
|--------------------------------------------------------------------------
| RESEND COOLDOWN - 60 SECONDS
|--------------------------------------------------------------------------
*/

$lastSent =
    (int) (
        $_SESSION['verify_otp_sent_at']
        ?? 0
    );


if ($lastSent > 0) {

    $secondsPassed =
        time() - $lastSent;


    if ($secondsPassed < 60) {

        $wait =
            60 - $secondsPassed;


        $_SESSION['flash_otp_error'] =
            'Please wait '
            . $wait
            . ' seconds before requesting another OTP.';


        redirect(
            '../auth/verify-otp.php'
        );
    }
}


/*
|--------------------------------------------------------------------------
| GENERATE NEW OTP
|--------------------------------------------------------------------------
*/

try {

    $otp =
        str_pad(
            (string) random_int(
                0,
                999999
            ),
            6,
            '0',
            STR_PAD_LEFT
        );


    /*
    |--------------------------------------------------------------------------
    | NEW OTP EXPIRY - 5 MINUTES
    |--------------------------------------------------------------------------
    */

    $otpExpiresTimestamp =
        time() + 300;


    $otpExpires =
        date(
            'Y-m-d H:i:s',
            $otpExpiresTimestamp
        );


    /*
    |--------------------------------------------------------------------------
    | UPDATE PENDING REGISTRATION
    |--------------------------------------------------------------------------
    |
    | New OTP replaces the old OTP.
    |
    */

    $update =
        $pdo->prepare(
            "UPDATE pending_registrations
             SET
                otp_code = ?,
                otp_expires_at = ?
             WHERE id = ?"
        );


    $update->execute([
        $otp,
        $otpExpires,
        $pending['id']
    ]);


    /*
    |--------------------------------------------------------------------------
    | CREATE EMAIL
    |--------------------------------------------------------------------------
    */

    $mail =
        new PHPMailer(true);


    $mail->isSMTP();


    $mail->Host =
        MAIL_HOST;


    $mail->SMTPAuth =
        true;


    $mail->Username =
        MAIL_USERNAME;


    $mail->Password =
        MAIL_PASSWORD;


    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;


    $mail->Port =
        MAIL_PORT;


    /*
    |--------------------------------------------------------------------------
    | FROM
    |--------------------------------------------------------------------------
    */

    $mail->setFrom(
        MAIL_FROM_EMAIL,
        MAIL_FROM_NAME
    );


    /*
    |--------------------------------------------------------------------------
    | TO
    |--------------------------------------------------------------------------
    */

    $mail->addAddress(
        $pending['email'],
        $pending['full_name']
    );


    /*
    |--------------------------------------------------------------------------
    | EMAIL SETTINGS
    |--------------------------------------------------------------------------
    */

    $mail->isHTML(true);


    $mail->Subject =
        'AllHotels.lk - New Verification OTP';


    /*
    |--------------------------------------------------------------------------
    | SAFE NAME
    |--------------------------------------------------------------------------
    */

    $safeName =
        htmlspecialchars(
            $pending['full_name'],
            ENT_QUOTES,
            'UTF-8'
        );


    /*
    |--------------------------------------------------------------------------
    | EMAIL BODY
    |--------------------------------------------------------------------------
    */

    $mail->Body = '

    <div style="
        font-family:Arial,sans-serif;
        background:#f5f7f6;
        padding:40px 20px;
    ">

        <div style="
            max-width:540px;
            margin:auto;
            background:#ffffff;
            border:1px solid #e5ebe7;
            border-radius:18px;
            padding:35px;
        ">

            <div style="
                text-align:center;
                margin-bottom:25px;
            ">

                <h2 style="
                    margin:0;
                    color:#174c39;
                    font-size:28px;
                ">
                    AllHotels.lk
                </h2>

                <p style="
                    margin:8px 0 0;
                    color:#7a867f;
                    font-size:14px;
                ">
                    Email Verification
                </p>

            </div>


            <p style="
                color:#33463d;
                font-size:14px;
            ">

                Hello

                <strong>'
                    . $safeName .
                '</strong>,

            </p>


            <p style="
                color:#65736b;
                font-size:14px;
                line-height:1.7;
            ">

                You requested a new verification code.
                Use the code below to verify your
                AllHotels.lk Hotel Owner account.

            </p>


            <div style="
                margin:25px 0;
                padding:24px 15px;
                text-align:center;
                background:#f2f8f4;
                border:1px solid #d7e8dd;
                border-radius:14px;
            ">

                <div style="
                    margin-bottom:8px;
                    color:#829087;
                    font-size:11px;
                    font-weight:bold;
                    letter-spacing:1px;
                ">
                    NEW VERIFICATION CODE
                </div>


                <div style="
                    color:#174c39;
                    font-size:34px;
                    font-weight:700;
                    letter-spacing:8px;
                ">
                    '
                    . $otp .
                    '
                </div>

            </div>


            <p style="
                margin:0;
                text-align:center;
                color:#6c7971;
                font-size:13px;
            ">

                This code expires in
                <strong>5 minutes</strong>.

            </p>


            <p style="
                margin:22px 0 0;
                color:#909a94;
                font-size:11px;
                line-height:1.6;
                text-align:center;
            ">

                If you did not request this code,
                you can safely ignore this email.

            </p>


            <p style="
                margin-top:25px;
                color:#65736b;
                font-size:13px;
            ">

                Regards,<br>

                <strong>
                    AllHotels.lk Team
                </strong>

            </p>

        </div>

    </div>

    ';


    /*
    |--------------------------------------------------------------------------
    | PLAIN TEXT EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->AltBody =
        'Your new AllHotels.lk verification OTP is: '
        . $otp
        . '. This OTP expires in 5 minutes.';


    /*
    |--------------------------------------------------------------------------
    | SEND EMAIL
    |--------------------------------------------------------------------------
    */

    $mail->send();


    /*
    |--------------------------------------------------------------------------
    | UPDATE SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['verify_otp_sent_at'] =
        time();


    $_SESSION['verify_otp_expires_at'] =
        $otpExpiresTimestamp;


    /*
    |--------------------------------------------------------------------------
    | SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */

    $_SESSION['flash_otp'] =
        'A new OTP has been sent to your email address.';


} catch (Exception $e) {

    /*
    |--------------------------------------------------------------------------
    | EMAIL ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'Registration OTP Resend Email Error: '
        . $e->getMessage()
    );


    $_SESSION['flash_otp_error'] =
        'Unable to send OTP. Please try again later.';


} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | DATABASE ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'Registration OTP Resend Database Error: '
        . $e->getMessage()
    );


    $_SESSION['flash_otp_error'] =
        'Something went wrong. Please try again.';
}


/*
|--------------------------------------------------------------------------
| BACK TO VERIFY PAGE
|--------------------------------------------------------------------------
*/

redirect(
    '../auth/verify-otp.php'
);