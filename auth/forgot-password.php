<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// PHPMailer
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../config/mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$error = null;

$email = '';


// Email OTP only
$otpMethod = 'email';


/*
|--------------------------------------------------------------------------
| HANDLE FORGOT PASSWORD
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | GET EMAIL
    |--------------------------------------------------------------------------
    */

    $email = trim(
        $_POST['email'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($email === '') {

        $error =
            'Please enter your email address.';

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                email
            FROM users
            WHERE email = ?
            LIMIT 1
        ");


        $stmt->execute([
            $email
        ]);


        $user =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        /*
        |--------------------------------------------------------------------------
        | USER NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            $error =
                'No account found with this email address.';

        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | GENERATE 6-DIGIT OTP
                |--------------------------------------------------------------------------
                */

                $otp = str_pad(
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
                | OTP EXPIRES IN 5 MINUTES
                |--------------------------------------------------------------------------
                */

                $expiresTimestamp =
                    time() + 300;


                $expires = date(
                    'Y-m-d H:i:s',
                    $expiresTimestamp
                );


                /*
                |--------------------------------------------------------------------------
                | SAVE OTP
                |--------------------------------------------------------------------------
                */

                $update = $pdo->prepare("
                    UPDATE users
                    SET
                        reset_otp = ?,
                        reset_otp_expires_at = ?
                    WHERE id = ?
                ");


                $update->execute([
                    $otp,
                    $expires,
                    $user['id']
                ]);


                /*
                |--------------------------------------------------------------------------
                | SAVE RESET SESSION
                |--------------------------------------------------------------------------
                */

                $_SESSION['reset_email'] =
                    $user['email'];


                $_SESSION['reset_otp_method'] =
                    'email';


                $_SESSION['reset_otp_sent_at'] =
                    time();


                $_SESSION['reset_otp_expires_at'] =
                    $expiresTimestamp;


                $_SESSION['reset_otp_attempts'] =
                    0;


                $_SESSION['reset_verified'] =
                    false;


                /*
                |--------------------------------------------------------------------------
                | CREATE EMAIL
                |--------------------------------------------------------------------------
                */

                $mail =
                    new PHPMailer(true);


                /*
                |--------------------------------------------------------------------------
                | SMTP
                |--------------------------------------------------------------------------
                */

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
                    $user['email'],
                    $user['full_name']
                );


                /*
                |--------------------------------------------------------------------------
                | EMAIL SETTINGS
                |--------------------------------------------------------------------------
                */

                $mail->isHTML(true);


                $mail->Subject =
                    'AllHotels.lk - Password Reset OTP';


                /*
                |--------------------------------------------------------------------------
                | SAFE NAME
                |--------------------------------------------------------------------------
                */

                $safeName =
                    htmlspecialchars(
                        $user['full_name'],
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
                        max-width:560px;
                        margin:auto;
                        background:#ffffff;
                        border-radius:18px;
                        padding:35px;
                    ">

                        <div style="
                            text-align:center;
                            margin-bottom:25px;
                        ">

                            <h2 style="
                                margin:0;
                                color:#14532d;
                                font-size:28px;
                            ">
                                AllHotels.lk
                            </h2>


                            <p style="
                                color:#64748b;
                                margin-top:8px;
                            ">
                                Password Reset Verification
                            </p>

                        </div>


                        <p style="
                            color:#334155;
                            font-size:15px;
                        ">

                            Hello

                            <strong>'
                                . $safeName .
                            '</strong>,

                        </p>


                        <p style="
                            color:#475569;
                            line-height:1.7;
                        ">

                            We received a request to reset your
                            AllHotels.lk account password.

                            Use the verification code below
                            to continue.

                        </p>


                        <div style="
                            background:#f0fdf4;
                            border:1px solid #bbf7d0;
                            padding:25px;
                            text-align:center;
                            border-radius:14px;
                            margin:28px 0;
                        ">

                            <div style="
                                font-size:12px;
                                color:#64748b;
                                margin-bottom:10px;
                            ">
                                YOUR VERIFICATION CODE
                            </div>


                            <span style="
                                font-size:34px;
                                font-weight:700;
                                letter-spacing:10px;
                                color:#14532d;
                            ">
                                '
                                . $otp .
                                '
                            </span>

                        </div>


                        <p style="
                            color:#475569;
                            text-align:center;
                        ">

                            This code expires in

                            <strong>
                                5 minutes
                            </strong>.

                        </p>


                        <div style="
                            margin-top:25px;
                            padding:15px;
                            border-radius:10px;
                            background:#f8fafc;
                            color:#64748b;
                            font-size:13px;
                            line-height:1.6;
                        ">

                            If you did not request a password reset,
                            you can safely ignore this email.

                        </div>


                        <p style="
                            margin-top:30px;
                            color:#475569;
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
                | PLAIN TEXT VERSION
                |--------------------------------------------------------------------------
                */

                $mail->AltBody =
                    'Your AllHotels.lk password reset OTP is: '
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
                | REDIRECT TO OTP PAGE
                |--------------------------------------------------------------------------
                */

                redirect(
                    '../auth/verify-reset-otp.php'
                );


            } catch (Exception $e) {

                /*
                |--------------------------------------------------------------------------
                | EMAIL ERROR
                |--------------------------------------------------------------------------
                */

                error_log(
                    'Password Reset Email Error: '
                    . $e->getMessage()
                );


                /*
                |--------------------------------------------------------------------------
                | CLEAR OTP IF EMAIL FAILED
                |--------------------------------------------------------------------------
                */

                try {

                    $clearOtp =
                        $pdo->prepare("
                            UPDATE users
                            SET
                                reset_otp = NULL,
                                reset_otp_expires_at = NULL
                            WHERE id = ?
                        ");


                    $clearOtp->execute([
                        $user['id']
                    ]);

                } catch (PDOException $clearException) {

                    error_log(
                        'OTP Clear Error: '
                        . $clearException->getMessage()
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CLEAR SESSION
                |--------------------------------------------------------------------------
                */

                unset(
                    $_SESSION['reset_email'],
                    $_SESSION['reset_otp_method'],
                    $_SESSION['reset_otp_sent_at'],
                    $_SESSION['reset_otp_expires_at'],
                    $_SESSION['reset_otp_attempts'],
                    $_SESSION['reset_verified']
                );


                $error =
                    'Unable to send OTP. Please try again later.';


            } catch (PDOException $e) {

                /*
                |--------------------------------------------------------------------------
                | DATABASE ERROR
                |--------------------------------------------------------------------------
                */

                error_log(
                    'Password Reset Database Error: '
                    . $e->getMessage()
                );


                $error =
                    'Something went wrong. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$page_title =
    'Forgot Password';


require_once __DIR__ . '/../includes/header.php';

?>


<div class="container">

    <div class="forgot-password-wrap">

        <div class="forgot-password-card">


            <!-- TITLE -->

            <h2>
                Forgot Password
            </h2>


            <!-- SUBTITLE -->

            <p class="forgot-password-sub">

                Enter your registered email address
                and we will send you a verification code.

            </p>


            <!-- ERROR -->

            <?php if ($error): ?>

                <div class="forgot-alert">

                    <?= h($error) ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form method="POST">


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">

                        Email Address

                    </label>


                    <input
                        type="email"
                        id="email"
                        name="email"

                        value="<?= h($email) ?>"

                        placeholder="example@gmail.com"

                        autocomplete="email"

                        required
                        autofocus
                    >

                </div>


                <!-- EMAIL OTP INFO -->

                 <div class="otp-note">                      
                    <span>                         
                        ⏱                     
                    </span>                      
                    <p>                         
                        Your verification code will be valid for                         
                        <strong>5 minutes</strong>.                     
                    </p>                  
                </div>


                <!-- SEND BUTTON -->

                <button
                    type="submit"
                    class="btn btn-primary btn-block forgot-submit"
                >

                    Send Verification Code

                </button>

            </form>


            <!-- DIVIDER -->

            <div class="forgot-divider">

                <span>
                    or
                </span>

            </div>


            <!-- BACK LOGIN -->

            <div class="auth-foot">

                Remember your password?

                <a href="../auth/login.php">

                    Back to Login

                </a>

            </div>


        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>