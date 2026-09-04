<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = null;

$email = $_SESSION['reset_email'] ?? '';
$verified = $_SESSION['reset_verified'] ?? false;


/*
|--------------------------------------------------------------------------
| CHECK RESET SESSION
|--------------------------------------------------------------------------
*/

if ($email === '' || !$verified) {

    redirect('../auth/forgot-password.php');
}


/*
|--------------------------------------------------------------------------
| HANDLE PASSWORD RESET
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password =
        $_POST['password'] ?? '';

    $confirmPassword =
        $_POST['confirm_password'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if (strlen($password) < 6) {

        $error =
            'Password must be at least 6 characters long.';

    } elseif ($password !== $confirmPassword) {

        $error =
            'Passwords do not match.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CREATE PASSWORD HASH
            |--------------------------------------------------------------------------
            */

            $passwordHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | UPDATE USER PASSWORD
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare("
                    UPDATE users
                    SET
                        password_hash = ?,
                        reset_otp = NULL,
                        reset_otp_expires_at = NULL
                    WHERE email = ?
                ");


            $stmt->execute([
                $passwordHash,
                $email
            ]);


            /*
            |--------------------------------------------------------------------------
            | CHECK USER EXISTS
            |--------------------------------------------------------------------------
            */

            if ($stmt->rowCount() === 0) {

                $error =
                    'Unable to update password. Please try again.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | CLEAR RESET SESSION
                |--------------------------------------------------------------------------
                */

                unset(
                    $_SESSION['reset_email'],
                    $_SESSION['reset_verified'],
                    $_SESSION['reset_otp_sent_at'],
                    $_SESSION['reset_otp_expires_at'],
                    $_SESSION['reset_otp_success']
                );


                /*
                |--------------------------------------------------------------------------
                | LOGIN SUCCESS MESSAGE
                |--------------------------------------------------------------------------
                */

                $_SESSION['flash_login'] =
                    'Your password has been reset successfully. Please log in.';


                /*
                |--------------------------------------------------------------------------
                | REDIRECT TO LOGIN
                |--------------------------------------------------------------------------
                */

                redirect(
                    '../auth/login.php'
                );
            }


        } catch (PDOException $e) {

            error_log(
                'Password Reset Error: '
                . $e->getMessage()
            );


            $error =
                'Unable to reset your password. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| PAGE TITLE
|--------------------------------------------------------------------------
*/

$page_title =
    'Reset Password';


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>


<div class="container">

    <div class="auth-wrap">

        <div class="auth-card">


            <h2>
                Reset Password
            </h2>


            <p class="auth-sub">

                Create a new password for your
                AllHotels.lk account.

            </p>


            <?php if ($error): ?>

                <div class="alert alert-error">

                    <?= h($error) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                autocomplete="off"
            >


                <!-- NEW PASSWORD -->

                <div class="form-group">

                    <label for="password">

                        New Password

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


                <!-- CONFIRM PASSWORD -->

                <div class="form-group">

                    <label for="confirm_password">

                        Confirm New Password

                    </label>


                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"

                        minlength="6"

                        autocomplete="new-password"

                        required
                    >

                </div>


                <!-- SUBMIT -->

                <button
                    type="submit"
                    class="btn btn-primary btn-block"
                >

                    Reset Password

                </button>

            </form>


        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>