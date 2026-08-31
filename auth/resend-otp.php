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


// Get email from session
$email = $_SESSION['verify_email'] ?? '';


// If email is not available
if ($email === '') {
    redirect('../register/register.php');
}


// Find user
$stmt = $pdo->prepare(
    "SELECT id, full_name, email, is_verified
     FROM users
     WHERE email = ?"
);

$stmt->execute([$email]);

$user = $stmt->fetch();


if (!$user) {

    $_SESSION['flash_error'] =
        'Account not found. Please register again.';

    redirect('../register/register.php');
}


// Already verified
if ((int)$user['is_verified'] === 1) {

    unset($_SESSION['verify_email']);

    $_SESSION['flash_login'] =
        'Your email is already verified. Please log in.';

    redirect('../auth/login.php');
}


try {

    /*
     * Generate new 6-digit OTP
     */

    $otp = str_pad(
        (string) random_int(0, 999999),
        6,
        '0',
        STR_PAD_LEFT
    );


    /*
     * OTP expires after 5 minutes
     */

    $otpExpires = date(
        'Y-m-d H:i:s',
        time() + (5 * 60)
    );


    /*
     * Save new OTP
     */

    $update = $pdo->prepare(
        "UPDATE users
         SET
            otp_code = ?,
            otp_expires_at = ?
         WHERE id = ?"
    );

    $update->execute([
        $otp,
        $otpExpires,
        $user['id']
    ]);


    /*
     * Create PHPMailer
     */

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure =
        PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;

    $mail->setFrom(
        MAIL_FROM_EMAIL,
        MAIL_FROM_NAME
    );
    
    $mail->addAddress(
        $user['email'],
        $user['full_name']
    );

    $mail->isHTML(true);

    $mail->Subject =
        'AllHotels.lk - New Verification OTP';


    /*
     * Email body
     */

    $mail->Body = '

    <div style="
        font-family: Arial, sans-serif;
        max-width: 600px;
        margin: auto;
        padding: 30px;
        background: #f8fafc;
    ">

        <div style="
            background: white;
            padding: 30px;
            border-radius: 10px;
        ">

            <h2 style="
                color: #2563eb;
                margin-bottom: 20px;
            ">
                AllHotels.lk
            </h2>


            <p>
                Hello
                <strong>
                    ' . htmlspecialchars($user['full_name']) . '
                </strong>,
            </p>


            <p>
                You requested a new verification code.
            </p>


            <p>
                Your new OTP is:
            </p>


            <div style="
                background: #f1f5f9;
                padding: 20px;
                text-align: center;
                margin: 25px 0;
                border-radius: 8px;
            ">

                <span style="
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 8px;
                    color: #111827;
                ">
                    ' . $otp . '
                </span>

            </div>


            <p>
                This OTP will expire in
                <strong>5 minutes</strong>.
            </p>


            <p style="color:#64748b;">
                If you did not request this code,
                please ignore this email.
            </p>


            <br>


            <p>
                Regards,<br>
                <strong>AllHotels.lk Team</strong>
            </p>

        </div>

    </div>

    ';


    /*
     * Plain text version
     */

    $mail->AltBody =
        "Your new AllHotels.lk verification OTP is: "
        . $otp
        . ". This OTP expires in 5 minutes.";


    /*
     * Send email
     */

    $mail->send();


    /*
     * Success message
     */

    $_SESSION['flash_otp'] =
        'A new OTP has been sent to your email address.';


} catch (Exception $e) {

    $_SESSION['flash_otp_error'] =
        'Unable to send OTP. Please try again later.';
}


/*
 * Go back to OTP page
 */

redirect('../auth/verify-otp.php');