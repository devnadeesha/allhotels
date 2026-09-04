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
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (is_logged_in()) {
    redirect('/allhotels/index.php');
}


/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$error = null;
$old = [];


/*
|--------------------------------------------------------------------------
| HANDLE REGISTRATION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Only Hotel Owner registration
    $type = 'owner';

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $businessAddr = trim($_POST['business_address'] ?? '');

    $old = $_POST;


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        $fullName === '' ||
        $email === '' ||
        strlen($password) < 6 ||
        $phone === '' ||
        $businessAddr === ''
    ) {

        $error =
            'Please fill all required fields. Password must be at least 6 characters.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error =
            'Please enter a valid email address.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CHECK USERS TABLE
        |--------------------------------------------------------------------------
        */

        $check = $pdo->prepare(
            "SELECT
                id,
                is_verified
             FROM users
             WHERE email = ?"
        );

        $check->execute([
            $email
        ]);

        $existingUser =
            $check->fetch();


        if ($existingUser) {

            if (
                (int) $existingUser['is_verified'] === 1
            ) {

                $error =
                    'An account with this email already exists. Please log in.';

            } else {

                $error =
                    'This email has an existing unverified account. Please complete email verification.';
            }

        } else {

            /*
            |--------------------------------------------------------------------------
            | CHECK PENDING REGISTRATION
            |--------------------------------------------------------------------------
            */

            $pendingCheck =
                $pdo->prepare(
                    "SELECT id
                     FROM pending_registrations
                     WHERE email = ?"
                );

            $pendingCheck->execute([
                $email
            ]);

            $pending =
                $pendingCheck->fetch();


            /*
            |--------------------------------------------------------------------------
            | PASSWORD HASH
            |--------------------------------------------------------------------------
            */

            $hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | GENERATE 6 DIGIT OTP
            |--------------------------------------------------------------------------
            */

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
            | OTP EXPIRES IN 5 MINUTES
            |--------------------------------------------------------------------------
            */

            $otpExpiresTimestamp =
                time() + 300;

            $otpExpires =
                date(
                    'Y-m-d H:i:s',
                    $otpExpiresTimestamp
                );


            try {

                /*
                |--------------------------------------------------------------------------
                | SAVE / UPDATE PENDING REGISTRATION
                |--------------------------------------------------------------------------
                */

                if ($pending) {

                    $stmt =
                        $pdo->prepare(
                            "UPDATE pending_registrations
                             SET
                                full_name = ?,
                                password_hash = ?,
                                phone = ?,
                                whatsapp = ?,
                                business_address = ?,
                                role = ?,
                                otp_code = ?,
                                otp_expires_at = ?
                             WHERE id = ?"
                        );

                    $stmt->execute([
                        $fullName,
                        $hash,
                        $phone,
                        $whatsapp,
                        $businessAddr,
                        $type,
                        $otp,
                        $otpExpires,
                        $pending['id']
                    ]);

                } else {

                    $stmt =
                        $pdo->prepare(
                            "INSERT INTO pending_registrations
                            (
                                full_name,
                                email,
                                password_hash,
                                phone,
                                whatsapp,
                                business_address,
                                role,
                                otp_code,
                                otp_expires_at
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                    $stmt->execute([
                        $fullName,
                        $email,
                        $hash,
                        $phone,
                        $whatsapp,
                        $businessAddr,
                        $type,
                        $otp,
                        $otpExpires
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | SEND EMAIL OTP
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


                $mail->setFrom(
                    MAIL_FROM_EMAIL,
                    MAIL_FROM_NAME
                );


                $mail->addAddress(
                    $email,
                    $fullName
                );


                $mail->isHTML(true);


                $mail->Subject =
                    'AllHotels.lk - Email Verification OTP';


                $safeName =
                    htmlspecialchars(
                        $fullName,
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
                        border-radius:18px;
                        padding:35px;
                        border:1px solid #e5ebe7;
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
                            Thank you for registering as a
                            Hotel Owner on AllHotels.lk.
                            Use the verification code below
                            to verify your email address.
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
                                VERIFICATION CODE
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
                            If you did not create this account,
                            you can safely ignore this email.
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
                    'Your AllHotels.lk verification OTP is: '
                    . $otp
                    . '. This OTP expires in 5 minutes.';


                /*
                |--------------------------------------------------------------------------
                | SEND
                |--------------------------------------------------------------------------
                */

                $mail->send();


                /*
                |--------------------------------------------------------------------------
                | SAVE OTP SESSION DETAILS
                |--------------------------------------------------------------------------
                */

                $_SESSION['verify_email'] =
                    $email;

                $_SESSION['verify_otp_method'] =
                    'email';

                $_SESSION['verify_otp_sent_at'] =
                    time();

                $_SESSION['verify_otp_expires_at'] =
                    $otpExpiresTimestamp;

                $_SESSION['verify_verified'] =
                    false;


                /*
                |--------------------------------------------------------------------------
                | REDIRECT TO VERIFY PAGE
                |--------------------------------------------------------------------------
                */

                redirect(
                    '../auth/verify-otp.php'
                );


            } catch (Exception $e) {

                /*
                |--------------------------------------------------------------------------
                | DELETE PENDING REGISTRATION IF EMAIL FAILED
                |--------------------------------------------------------------------------
                */

                $delete =
                    $pdo->prepare(
                        "DELETE FROM pending_registrations
                         WHERE email = ?"
                    );

                $delete->execute([
                    $email
                ]);


                error_log(
                    'Registration OTP Email Error: '
                    . $e->getMessage()
                );


                $error =
                    'We could not send the verification email. Please check your email settings and try again.';


            } catch (PDOException $e) {

                error_log(
                    'Registration Database Error: '
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
| PAGE
|--------------------------------------------------------------------------
*/

$page_title =
    'Hotel Owner Registration';

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">


            <h2>
                Register as a Hotel Owner
            </h2>


            <p class="auth-sub">
                Create your Hotel Owner account and
                list your property on AllHotels.lk.
            </p>


            <?php if ($error): ?>

                <div class="alert alert-error">

                    <?= h($error) ?>

                </div>

            <?php endif; ?>


            <form method="POST">


                <!-- FULL NAME -->

                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"

                        value="<?=
                            h(
                                $old['full_name']
                                ?? ''
                            )
                        ?>"

                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"

                        value="<?=
                            h(
                                $old['email']
                                ?? ''
                            )
                        ?>"

                        autocomplete="email"

                        required
                    >

                </div>


                <!-- PASSWORD -->

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"

                        minlength="6"

                        autocomplete="new-password"

                        required
                    >

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label for="phone">
                        Contact Number
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"

                        value="<?=
                            h(
                                $old['phone']
                                ?? ''
                            )
                        ?>"

                        required
                    >

                </div>


                <!-- WHATSAPP NUMBER
                     Contact detail only - NOT OTP method -->

                <div class="form-group">

                    <label for="whatsapp">
                        WhatsApp Number
                    </label>

                    <input
                        type="text"
                        id="whatsapp"
                        name="whatsapp"

                        value="<?=
                            h(
                                $old['whatsapp']
                                ?? ''
                            )
                        ?>"
                    >

                </div>


                <!-- BUSINESS ADDRESS -->

                <div class="form-group">

                    <label for="business_address">
                        Business Address
                    </label>

                    <input
                        type="text"
                        id="business_address"
                        name="business_address"

                        value="<?=
                            h(
                                $old['business_address']
                                ?? ''
                            )
                        ?>"

                        required
                    >

                </div>


                <!-- EMAIL VERIFICATION INFO -->

                <div class="otp-note">
                    <p>
                        A verification code will be sent to your
                        <strong>email address</strong>.
                        The code is valid for
                        <strong>5 minutes</strong>.
                    </p>

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >
                    Register as Hotel Owner
                </button>

            </form>


            <div class="auth-foot">

                Already have an account?

                <a href="../auth/login.php">
                    Log in
                </a>

            </div>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>