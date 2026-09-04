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


$error = null;
$success = null;


/*
|--------------------------------------------------------------------------
| GET EMAIL FROM SESSION
|--------------------------------------------------------------------------
*/

$email = $_SESSION['reset_email'] ?? '';


if ($email === '') {

    redirect('../auth/forgot-password.php');
}


/*
|--------------------------------------------------------------------------
| SHOW RESEND SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['reset_otp_success'])) {

    $success =
        $_SESSION['reset_otp_success'];

    unset(
        $_SESSION['reset_otp_success']
    );
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
    | LAST SENT TIME
    |--------------------------------------------------------------------------
    */

    $lastSent =
        (int) (
            $_SESSION['reset_otp_sent_at']
            ?? 0
        );


    $secondsPassed =
        time() - $lastSent;


    /*
    |--------------------------------------------------------------------------
    | 60 SECOND COOLDOWN
    |--------------------------------------------------------------------------
    */

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

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare("
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
                'Account not found. Please start again.';

        } else {

            try {

                /*
                |--------------------------------------------------------------------------
                | GENERATE NEW 6-DIGIT OTP
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
                | OTP EXPIRES IN 5 MINUTES
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
                | SAVE NEW OTP
                |--------------------------------------------------------------------------
                */

                $update =
                    $pdo->prepare("
                        UPDATE users
                        SET
                            reset_otp = ?,
                            reset_otp_expires_at = ?
                        WHERE id = ?
                    ");


                $update->execute([
                    $newOtp,
                    $newExpiresAt,
                    $user['id']
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
                        max-width:520px;
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

                            Use the verification code below
                            to reset your password.

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
                                VERIFICATION CODE
                            </div>


                            <span style="
                                font-size:34px;
                                font-weight:700;
                                letter-spacing:10px;
                                color:#14532d;
                            ">
                                '
                                . $newOtp .
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
                | UPDATE SESSION
                |--------------------------------------------------------------------------
                */

                $_SESSION['reset_otp_sent_at'] =
                    time();


                $_SESSION['reset_otp_expires_at'] =
                    $newExpiresTimestamp;


                $_SESSION['reset_verified'] =
                    false;


                /*
                |--------------------------------------------------------------------------
                | SUCCESS MESSAGE
                |--------------------------------------------------------------------------
                */

                $_SESSION['reset_otp_success'] =
                    'A new OTP has been sent to your email.';


                /*
                |--------------------------------------------------------------------------
                | RELOAD OTP PAGE
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
                    'Reset OTP resend email error: '
                    . $e->getMessage()
                );


                $error =
                    'Unable to send the OTP. Please try again.';


            } catch (PDOException $e) {

                /*
                |--------------------------------------------------------------------------
                | DATABASE ERROR
                |--------------------------------------------------------------------------
                */

                error_log(
                    'Reset OTP database error: '
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
        | GET USER + OTP
        |--------------------------------------------------------------------------
        */

        $stmt =
            $pdo->prepare("
                SELECT
                    id,
                    email,
                    reset_otp,
                    reset_otp_expires_at
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
                'Account not found. Please try again.';

        }

        /*
        |--------------------------------------------------------------------------
        | NO ACTIVE OTP
        |--------------------------------------------------------------------------
        */

        elseif (
            empty($user['reset_otp'])
            ||
            empty($user['reset_otp_expires_at'])
        ) {

            $error =
                'No active OTP found. Please request a new one.';

        }

        /*
        |--------------------------------------------------------------------------
        | OTP EXPIRED
        |--------------------------------------------------------------------------
        */

        elseif (
            strtotime(
                $user['reset_otp_expires_at']
            ) < time()
        ) {

            $error =
                'Your OTP has expired. Please request a new one.';

        }

        /*
        |--------------------------------------------------------------------------
        | WRONG OTP
        |--------------------------------------------------------------------------
        */

        elseif (
            !hash_equals(
                (string) $user['reset_otp'],
                (string) $otp
            )
        ) {

            $error =
                'Invalid OTP. Please try again.';

        }

        /*
        |--------------------------------------------------------------------------
        | OTP CORRECT
        |--------------------------------------------------------------------------
        */

        else {

            /*
            |--------------------------------------------------------------------------
            | VERIFIED
            |--------------------------------------------------------------------------
            */

            $_SESSION['reset_verified'] =
                true;


            /*
            |--------------------------------------------------------------------------
            | CLEAR USED OTP
            |--------------------------------------------------------------------------
            */

            $clear =
                $pdo->prepare("
                    UPDATE users
                    SET
                        reset_otp = NULL,
                        reset_otp_expires_at = NULL
                    WHERE id = ?
                ");


            $clear->execute([
                $user['id']
            ]);


            /*
            |--------------------------------------------------------------------------
            | CLEAR TIMER SESSION
            |--------------------------------------------------------------------------
            */

            unset(
                $_SESSION['reset_otp_sent_at'],
                $_SESSION['reset_otp_expires_at']
            );


            /*
            |--------------------------------------------------------------------------
            | GO TO RESET PASSWORD
            |--------------------------------------------------------------------------
            */

            redirect(
                '../auth/reset-password.php'
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| TIMER VALUES
|--------------------------------------------------------------------------
*/

$sentAt =
    (int) (
        $_SESSION['reset_otp_sent_at']
        ?? time()
    );


$expiresAt =
    (int) (
        $_SESSION['reset_otp_expires_at']
        ?? ($sentAt + 300)
    );


$resendAvailableAt =
    $sentAt + 60;


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$page_title =
    'Verify Reset OTP';


require_once __DIR__ . '/../includes/header.php';

?>


<style>

/* =========================================================
   OTP MINI INFO ONLY
   Other existing auth CSS sizes stay unchanged
========================================================= */

.otp-mini-info {

    display: flex;

    align-items: center;

    justify-content: center;

    flex-wrap: wrap;

    gap: 180px;

    margin: 8px 0 14px;

    padding: 5px 8px;

    background: #f7faf8;

    border: 1px solid #e3ebe6;

    border-radius: 8px;

    color: #7b8780;

    font-size: 10px;

    line-height: 1.3;
}


.otp-mini-info span {

    display: inline-flex;

    align-items: center;

    gap: 3px;

    font-size: 10px;
}


.otp-mini-info strong {

    color: #2f6d50;

    font-size: 10px;

    font-weight: 700;
}


#otpCountdown {

    min-width: 40px;

    color: #2f6d50;

    font-size: 10px;

    font-weight: 700;

    font-variant-numeric: tabular-nums;
}


#otpCountdown.expired {

    color: #b42318;
}


/* =========================================================
   SMALL RESEND
========================================================= */

.otp-resend-area {

    margin-top: 14px;

    text-align: center;

    font-size: 12px;
}


.otp-resend-area p {

    margin: 0 0 3px;

    color: #78847d;

    font-size: 12px;
}


.otp-resend-area form {

    margin: 0;
}


.otp-resend-btn {

    padding: 3px 5px;

    border: 0;

    background: transparent;

    color: #9ca59f;

    font-family: inherit;

    font-size: 12px;

    font-weight: 600;

    cursor: not-allowed;
}


.otp-resend-btn.ready {

    color: #2f6d50;

    cursor: pointer;
}


.otp-resend-btn.ready:hover {

    text-decoration: underline;
}


#resendCountdown {

    font-size: 11px;

    font-variant-numeric: tabular-nums;
}


/* =========================================================
   EXPIRED
========================================================= */

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
                Verify OTP
            </h2>


            <p class="auth-sub">

                Enter the 6-digit verification code.

            </p>


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
                        Enter OTP
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


                <!-- SMALL EMAIL + TIMER -->

                <div class="otp-mini-info">

                    <span>

                        Code sent via

                        <strong>
                            Email
                        </strong>

                    </span>


                    <span>

                        Expires in

                        <strong id="otpCountdown">
                            05:00
                        </strong>

                    </span>

                </div>


                <!-- VERIFY BUTTON -->

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                    id="verifyButton"
                >

                    Verify OTP

                </button>

            </form>


            <!-- RESEND -->

            <div class="otp-resend-area">

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
                        class="otp-resend-btn"
                        id="resendButton"
                        disabled
                    >

                        Resend Code

                        <span id="resendCountdown">
                            in 01:00
                        </span>

                    </button>

                </form>

            </div>


            <!-- BACK -->

            <div class="auth-foot">

                <a href="../auth/login.php">

                    Back to Login

                </a>

            </div>


        </div>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | ELEMENTS
        |--------------------------------------------------------------------------
        */

        const otpInput =
            document.getElementById(
                'otp'
            );


        const verifyButton =
            document.getElementById(
                'verifyButton'
            );


        const otpCountdown =
            document.getElementById(
                'otpCountdown'
            );


        const resendButton =
            document.getElementById(
                'resendButton'
            );


        const resendCountdown =
            document.getElementById(
                'resendCountdown'
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
        | ONLY NUMBERS
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


            /*
            |--------------------------------------------------------------------------
            | EXPIRED
            |--------------------------------------------------------------------------
            */

            if (remaining <= 0) {

                otpCountdown.textContent =
                    'Expired';


                otpCountdown.classList.add(
                    'expired'
                );


                otpInput.disabled =
                    true;


                verifyButton.disabled =
                    true;


                return;
            }


            /*
            |--------------------------------------------------------------------------
            | FORMAT
            |--------------------------------------------------------------------------
            */

            const minutes =
                Math.floor(
                    remaining / 60
                );


            const seconds =
                remaining % 60;


            otpCountdown.textContent =
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


            /*
            |--------------------------------------------------------------------------
            | RESEND READY
            |--------------------------------------------------------------------------
            */

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


            /*
            |--------------------------------------------------------------------------
            | STILL WAITING
            |--------------------------------------------------------------------------
            */

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
        | START TIMERS
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