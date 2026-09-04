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
| DEFAULT
|--------------------------------------------------------------------------
*/

$error = null;
$success = null;


/*
|--------------------------------------------------------------------------
| GET EMAIL FROM SESSION
|--------------------------------------------------------------------------
*/

$email = $_SESSION['verify_email'] ?? '';


if ($email === '') {

    redirect('../register/register.php');
}


/*
|--------------------------------------------------------------------------
| SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['verify_otp_success'])) {

    $success =
        $_SESSION['verify_otp_success'];

    unset(
        $_SESSION['verify_otp_success']
    );
}


/*
|--------------------------------------------------------------------------
| GET PENDING REGISTRATION
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        email,
        password_hash,
        phone,
        whatsapp,
        business_address,
        role,
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
    $stmt->fetch(PDO::FETCH_ASSOC);


if (!$pending) {

    $_SESSION['flash_error'] =
        'Registration session not found. Please register again.';


    unset(
        $_SESSION['verify_email'],
        $_SESSION['verify_otp_sent_at'],
        $_SESSION['verify_otp_expires_at']
    );


    redirect('../register/register.php');
}


/*
|--------------------------------------------------------------------------
| RESEND OTP
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['resend_otp'])
) {

    /*
    |--------------------------------------------------------------------------
    | 60 SECOND COOLDOWN
    |--------------------------------------------------------------------------
    */

    $lastSent =
        (int) (
            $_SESSION['verify_otp_sent_at']
            ?? 0
        );


    $secondsPassed =
        time() - $lastSent;


    if (
        $lastSent > 0
        &&
        $secondsPassed < 60
    ) {

        $wait =
            60 - $secondsPassed;


        $error =
            'Please wait '
            . $wait
            . ' seconds before requesting another OTP.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | GENERATE NEW OTP
            |--------------------------------------------------------------------------
            */

            $newOtp =
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
            | NEW EXPIRY - 5 MINUTES
            |--------------------------------------------------------------------------
            */

            $newExpiresTimestamp =
                time() + 300;


            $newExpiresAt =
                date(
                    'Y-m-d H:i:s',
                    $newExpiresTimestamp
                );


            /*
            |--------------------------------------------------------------------------
            | UPDATE PENDING REGISTRATION
            |--------------------------------------------------------------------------
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
                $newOtp,
                $newExpiresAt,
                $pending['id']
            ]);


            /*
            |--------------------------------------------------------------------------
            | SEND EMAIL
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

                        You requested a new verification code.
                        Use the code below to verify your
                        AllHotels.lk account.

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
                            . $newOtp .
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
                . $newOtp
                . '. This OTP expires in 5 minutes.';


            /*
            |--------------------------------------------------------------------------
            | SEND
            |--------------------------------------------------------------------------
            */

            $mail->send();


            /*
            |--------------------------------------------------------------------------
            | UPDATE SESSION TIMES
            |--------------------------------------------------------------------------
            */

            $_SESSION['verify_otp_sent_at'] =
                time();


            $_SESSION['verify_otp_expires_at'] =
                $newExpiresTimestamp;


            $_SESSION['verify_otp_success'] =
                'A new OTP has been sent to your email.';


            /*
            |--------------------------------------------------------------------------
            | REFRESH PAGE
            |--------------------------------------------------------------------------
            */

            redirect(
                '../auth/verify-otp.php'
            );


        } catch (Exception $e) {

            error_log(
                'Registration OTP Resend Error: '
                . $e->getMessage()
            );


            $error =
                'Unable to send OTP. Please try again later.';


        } catch (PDOException $e) {

            error_log(
                'Registration OTP Database Error: '
                . $e->getMessage()
            );


            $error =
                'Something went wrong. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| VERIFY OTP
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['verify_otp'])
) {

    /*
    |--------------------------------------------------------------------------
    | GET OTP
    |--------------------------------------------------------------------------
    */

    $otp =
        trim(
            $_POST['otp']
            ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE FORMAT
    |--------------------------------------------------------------------------
    */

    if (
        !preg_match(
            '/^[0-9]{6}$/',
            $otp
        )
    ) {

        $error =
            'Please enter a valid 6-digit OTP.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | GET LATEST PENDING DATA
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare(
                "SELECT
                    id,
                    full_name,
                    email,
                    password_hash,
                    phone,
                    whatsapp,
                    business_address,
                    role,
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
            $stmt->fetch(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | CHECK REGISTRATION
        |--------------------------------------------------------------------------
        */

        if (!$pending) {

            $error =
                'Registration session not found. Please register again.';

        } elseif (
            empty($pending['otp_code'])
            ||
            empty($pending['otp_expires_at'])
        ) {

            $error =
                'No active OTP found. Please request a new OTP.';

        } elseif (
            strtotime(
                $pending['otp_expires_at']
            ) < time()
        ) {

            $error =
                'Your OTP has expired. Please request a new OTP.';

        } elseif (
            !hash_equals(
                (string) $pending['otp_code'],
                (string) $otp
            )
        ) {

            $error =
                'Invalid OTP. Please try again.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | OTP CORRECT
            |--------------------------------------------------------------------------
            */

            try {

                $pdo->beginTransaction();


                /*
                |--------------------------------------------------------------------------
                | CHECK USERS TABLE
                |--------------------------------------------------------------------------
                */

                $check =
                    $pdo->prepare(
                        "SELECT id
                         FROM users
                         WHERE email = ?
                         LIMIT 1"
                    );


                $check->execute([
                    $pending['email']
                ]);


                $existingUser =
                    $check->fetch();


                if ($existingUser) {

                    $pdo->rollBack();


                    $error =
                        'An account with this email already exists. Please log in.';

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | CREATE REAL USER
                    |--------------------------------------------------------------------------
                    */

                    $insert =
                        $pdo->prepare(
                            "INSERT INTO users
                            (
                                full_name,
                                email,
                                password_hash,
                                phone,
                                whatsapp,
                                business_address,
                                role,
                                verify_token,
                                is_verified,
                                otp_code,
                                otp_expires_at
                            )
                            VALUES (
                                ?, ?, ?, ?, ?, ?, ?,
                                NULL,
                                1,
                                NULL,
                                NULL
                            )"
                        );


                    $insert->execute([
                        $pending['full_name'],
                        $pending['email'],
                        $pending['password_hash'],
                        $pending['phone'],
                        $pending['whatsapp'],
                        $pending['business_address'],
                        $pending['role']
                    ]);


                    $userId =
                        $pdo->lastInsertId();


                    /*
                    |--------------------------------------------------------------------------
                    | DELETE PENDING
                    |--------------------------------------------------------------------------
                    */

                    $delete =
                        $pdo->prepare(
                            "DELETE FROM pending_registrations
                             WHERE id = ?"
                        );


                    $delete->execute([
                        $pending['id']
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | COMMIT
                    |--------------------------------------------------------------------------
                    */

                    $pdo->commit();


                    /*
                    |--------------------------------------------------------------------------
                    | WELCOME NOTIFICATION
                    |--------------------------------------------------------------------------
                    */

                    notify(
                        $pdo,
                        $userId,
                        'welcome',
                        'Welcome to AllHotels.lk! Your Hotel Owner account has been verified successfully.',
                        'both'
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | CLEAR SESSION
                    |--------------------------------------------------------------------------
                    */

                    unset(
                        $_SESSION['verify_email'],
                        $_SESSION['verify_otp_sent_at'],
                        $_SESSION['verify_otp_expires_at'],
                        $_SESSION['verify_otp_success']
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN MESSAGE
                    |--------------------------------------------------------------------------
                    */

                    $_SESSION['flash_login'] =
                        'Email verified successfully. Your Hotel Owner account is now active. Please log in.';


                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN
                    |--------------------------------------------------------------------------
                    */

                    redirect(
                        '../auth/login.php'
                    );
                }


            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {

                    $pdo->rollBack();
                }


                error_log(
                    'Registration Verification Error: '
                    . $e->getMessage()
                );


                $error =
                    'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| TIMER VALUES
|--------------------------------------------------------------------------
*/

/*
 * Prefer database expiry because refreshing the page
 * must NOT restart the 5-minute OTP timer.
 */

$databaseExpiry =
    !empty($pending['otp_expires_at'])
        ? strtotime($pending['otp_expires_at'])
        : time();


$expiresAt =
    $databaseExpiry;


$sentAt =
    (int) (
        $_SESSION['verify_otp_sent_at']
        ?? ($expiresAt - 300)
    );


$resendAvailableAt =
    $sentAt + 60;


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$page_title =
    'Verify Email';

require_once __DIR__ . '/../includes/header.php';

?>


<style>

/* =========================================================
   REGISTRATION OTP SMALL INFO
========================================================= */

.registration-otp-email {

    margin: -5px 0 18px;

    text-align: center;

    color: #68756d;

    font-size: 12px;
}


.registration-otp-email strong {

    color: #2f6d50;

    font-size: 12px;
}


/* EMAIL + TIMER */

.registration-otp-info {

    display: flex;

    align-items: center;

    justify-content: center;

    flex-wrap: wrap;

    gap: 5px;

    margin: 7px 0 14px;

    padding: 5px 8px;

    background: #f7faf8;

    border: 1px solid #e3ebe6;

    border-radius: 8px;

    color: #7b8780;

    font-size: 10px;
}


.registration-otp-info span {

    display: inline-flex;

    align-items: center;

    gap: 3px;

    font-size: 10px;
}


.registration-otp-info strong {

    color: #2f6d50;

    font-size: 10px;

    font-weight: 700;
}


.registration-otp-divider {

    color: #b5beb8;

    font-size: 8px;
}


#registrationOtpCountdown {

    min-width: 40px;

    color: #2f6d50;

    font-size: 10px;

    font-weight: 700;

    font-variant-numeric: tabular-nums;
}


#registrationOtpCountdown.expired {

    color: #b42318;
}


/* =========================================================
   RESEND
========================================================= */

.registration-resend {

    margin-top: 14px;

    text-align: center;
}


.registration-resend p {

    margin: 0 0 3px;

    color: #78847d;

    font-size: 12px;
}


.registration-resend form {

    margin: 0;
}


.registration-resend-btn {

    padding: 3px 5px;

    border: 0;

    background: transparent;

    color: #9ca59f;

    font-family: inherit;

    font-size: 12px;

    font-weight: 600;

    cursor: not-allowed;
}


.registration-resend-btn.ready {

    color: #2f6d50;

    cursor: pointer;
}


.registration-resend-btn.ready:hover {

    text-decoration: underline;
}


#registrationResendCountdown {

    font-size: 11px;

    font-variant-numeric: tabular-nums;
}


/* EXPIRED */

#otp:disabled {

    cursor: not-allowed;

    opacity: 0.6;
}


#verifyButton:disabled {

    cursor: not-allowed;

    opacity: 0.55;
}

</style>


<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">


            <h2>
                Verify Your Email
            </h2>


            <p class="auth-sub">
                Enter the 6-digit verification code
                to activate your Hotel Owner account.
            </p>


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


            <!-- EMAIL -->

            <div class="registration-otp-email">

                Code sent to

                <strong>
                    <?= h($email) ?>
                </strong>

            </div>


            <!-- VERIFY FORM -->

            <form
                method="POST"
                id="otpForm"
                autocomplete="off"
            >

                <input
                    type="hidden"
                    name="verify_otp"
                    value="1"
                >


                <div class="form-group">

                    <label for="otp">
                        Enter Verification Code
                    </label>


                    <input
                        type="text"
                        id="otp"
                        name="otp"

                        maxlength="6"
                        minlength="6"

                        inputmode="numeric"

                        pattern="[0-9]{6}"

                        placeholder="Enter 6-digit OTP"

                        autocomplete="one-time-code"

                        required
                        autofocus
                    >

                </div>


                <!-- EMAIL + TIMER -->

                <div class="registration-otp-info">

                    <span>

                        Sent via

                        <strong>
                            Email
                        </strong>

                    </span>


                    <span class="registration-otp-divider">
                        •
                    </span>


                    <span>

                        Expires in

                        <strong id="registrationOtpCountdown">
                            05:00
                        </strong>

                    </span>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                    id="verifyButton"
                >
                    Verify Email
                </button>

            </form>


            <!-- RESEND -->

            <div class="registration-resend">

                <p>
                    Didn't receive the code?
                </p>


                <form method="POST">

                    <input
                        type="hidden"
                        name="resend_otp"
                        value="1"
                    >


                    <button
                        type="submit"
                        class="registration-resend-btn"
                        id="registrationResendButton"
                        disabled
                    >

                        Resend OTP

                        <span id="registrationResendCountdown">
                            in 01:00
                        </span>

                    </button>

                </form>

            </div>


            <div class="auth-foot">

                <a href="../register/register.php">
                    Back to Registration
                </a>

            </div>

        </div>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const otpInput =
            document.getElementById(
                'otp'
            );


        const verifyButton =
            document.getElementById(
                'verifyButton'
            );


        const countdown =
            document.getElementById(
                'registrationOtpCountdown'
            );


        const resendButton =
            document.getElementById(
                'registrationResendButton'
            );


        const resendCountdown =
            document.getElementById(
                'registrationResendCountdown'
            );


        /*
        |--------------------------------------------------------------------------
        | SERVER TIMES
        |--------------------------------------------------------------------------
        */

        const expiresAt =
            <?= (int) $expiresAt * 1000 ?>;


        const resendAvailableAt =
            <?= (int) $resendAvailableAt * 1000 ?>;


        /*
        |--------------------------------------------------------------------------
        | NUMBERS ONLY
        |--------------------------------------------------------------------------
        */

        otpInput.addEventListener(
            'input',
            function () {

                this.value =
                    this.value
                        .replace(/\D/g, '')
                        .slice(0, 6);

            }
        );


        /*
        |--------------------------------------------------------------------------
        | OTP TIMER
        |--------------------------------------------------------------------------
        */

        function updateOtpTimer() {

            let remaining =
                Math.ceil(
                    (
                        expiresAt
                        -
                        Date.now()
                    ) / 1000
                );


            if (remaining <= 0) {

                countdown.textContent =
                    'Expired';


                countdown.classList.add(
                    'expired'
                );


                otpInput.disabled =
                    true;


                verifyButton.disabled =
                    true;


                return;
            }


            const minutes =
                Math.floor(
                    remaining / 60
                );


            const seconds =
                remaining % 60;


            countdown.textContent =
                String(minutes)
                    .padStart(2, '0')
                +
                ':'
                +
                String(seconds)
                    .padStart(2, '0');

        }


        /*
        |--------------------------------------------------------------------------
        | RESEND TIMER
        |--------------------------------------------------------------------------
        */

        function updateResendTimer() {

            let remaining =
                Math.ceil(
                    (
                        resendAvailableAt
                        -
                        Date.now()
                    ) / 1000
                );


            if (remaining <= 0) {

                resendButton.disabled =
                    false;


                resendButton.classList.add(
                    'ready'
                );


                resendCountdown.textContent =
                    '';


                return;
            }


            resendButton.disabled =
                true;


            resendButton.classList.remove(
                'ready'
            );


            const minutes =
                Math.floor(
                    remaining / 60
                );


            const seconds =
                remaining % 60;


            resendCountdown.textContent =
                'in '
                +
                String(minutes)
                    .padStart(2, '0')
                +
                ':'
                +
                String(seconds)
                    .padStart(2, '0');

        }


        /*
        |--------------------------------------------------------------------------
        | START
        |--------------------------------------------------------------------------
        */

        updateOtpTimer();

        updateResendTimer();


        setInterval(
            function () {

                updateOtpTimer();

                updateResendTimer();

            },
            1000
        );

    }
);

</script>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>