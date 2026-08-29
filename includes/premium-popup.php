<?php

/*
|--------------------------------------------------------------------------
| PREMIUM POPUP + PREMIUM ACTIVATION
|--------------------------------------------------------------------------
|
| File:
| /allhotels/includes/premium-popup.php
|
| Requirements:
| - config/db.php must already be loaded
| - Session must already be started
| - users table must contain:
|      id
|      role
|
| - hotels table must contain:
|      id
|      user_id
|      name
|      is_premium
|
|--------------------------------------------------------------------------
*/


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
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

$premiumPopupShow = false;

$premiumHotel = null;

$premiumMessage = '';

$premiumError = '';


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);


/*
|--------------------------------------------------------------------------
| CHECK LOGGED-IN USER
|--------------------------------------------------------------------------
*/

if ($currentUserId > 0) {

    /*
    |--------------------------------------------------------------------------
    | GET USER
    |--------------------------------------------------------------------------
    */

    $userStmt = $pdo->prepare("
        SELECT
            id,
            role
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $userStmt->execute([
        $currentUserId
    ]);

    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);


    /*
    |--------------------------------------------------------------------------
    | CHECK HOTEL OWNER
    |--------------------------------------------------------------------------
    |
    | Change 'hotel_owner' below if your actual role value is different.
    |
    */

    if (
        $currentUser &&
        strtolower(trim($currentUser['role'])) === 'owner'
    ) {


        /*
        |--------------------------------------------------------------------------
        | GET OWNER'S HOTEL
        |--------------------------------------------------------------------------
        */

        $hotelStmt = $pdo->prepare("
            SELECT
                id,
                name,
                is_premium
            FROM hotels
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $hotelStmt->execute([
            $currentUserId
        ]);

        $premiumHotel = $hotelStmt->fetch(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | PREMIUM ACTIVATION
        |--------------------------------------------------------------------------
        */

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['activate_premium'])
        ) {

            /*
            |--------------------------------------------------------------------------
            | CSRF CHECK
            |--------------------------------------------------------------------------
            */

            $csrfToken = $_POST['premium_csrf'] ?? '';

            if (
                empty($_SESSION['premium_csrf']) ||
                !hash_equals(
                    $_SESSION['premium_csrf'],
                    $csrfToken
                )
            ) {

                $premiumError =
                    'Invalid request. Please try again.';

            }


            /*
            |--------------------------------------------------------------------------
            | ACTIVATE PREMIUM
            |--------------------------------------------------------------------------
            */

            elseif (!$premiumHotel) {

                $premiumError =
                    'No hotel was found for your account.';

            }

            elseif ((int) $premiumHotel['is_premium'] === 1) {

                $premiumMessage =
                    'Your hotel is already Premium.';

            }

            else {

                try {

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE HOTEL
                    |--------------------------------------------------------------------------
                    */

                    $activateStmt = $pdo->prepare("
                        UPDATE hotels
                        SET is_premium = 1
                        WHERE id = ?
                          AND user_id = ?
                          AND is_premium = 0
                        LIMIT 1
                    ");

                    $activateStmt->execute([
                        (int) $premiumHotel['id'],
                        $currentUserId
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | CHECK UPDATE
                    |--------------------------------------------------------------------------
                    */

                    if ($activateStmt->rowCount() > 0) {

                        $premiumMessage =
                            'Premium has been activated successfully!';


                        /*
                        |--------------------------------------------------------------------------
                        | Update local hotel data
                        |--------------------------------------------------------------------------
                        */

                        $premiumHotel['is_premium'] = 1;


                        /*
                        |--------------------------------------------------------------------------
                        | Do not show popup anymore
                        |--------------------------------------------------------------------------
                        */

                        $premiumPopupShow = false;

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Re-check database
                        |--------------------------------------------------------------------------
                        */

                        $checkStmt = $pdo->prepare("
                            SELECT
                                id,
                                name,
                                is_premium
                            FROM hotels
                            WHERE id = ?
                              AND user_id = ?
                            LIMIT 1
                        ");

                        $checkStmt->execute([
                            (int) $premiumHotel['id'],
                            $currentUserId
                        ]);

                        $premiumHotel =
                            $checkStmt->fetch(PDO::FETCH_ASSOC);


                        if (
                            $premiumHotel &&
                            (int) $premiumHotel['is_premium'] === 1
                        ) {

                            $premiumMessage =
                                'Premium has been activated successfully!';

                            $premiumPopupShow = false;

                        } else {

                            $premiumError =
                                'Premium activation failed. Please try again.';

                        }

                    }

                } catch (PDOException $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | Database Error
                    |--------------------------------------------------------------------------
                    */

                    $premiumError =
                        'Unable to activate Premium. Please try again later.';

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | SHOW POPUP
        |--------------------------------------------------------------------------
        |
        | Only show if:
        | - Owner is logged in
        | - Hotel exists
        | - Hotel is NOT Premium
        |
        */

        if (
            $premiumHotel &&
            (int) $premiumHotel['is_premium'] === 0 &&
            empty($premiumMessage)
        ) {

            $premiumPopupShow = true;

        }

    }

}


/*
|--------------------------------------------------------------------------
| CREATE CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    $premiumPopupShow &&
    empty($_SESSION['premium_csrf'])
) {

    $_SESSION['premium_csrf'] =
        bin2hex(random_bytes(32));

}

?>


<?php if ($premiumPopupShow): ?>

<!-- ============================================================
     PREMIUM POPUP
============================================================ -->

<div
    class="premium-modal"
    id="premiumModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="premiumModalTitle"
>


    <!-- BACKDROP -->

    <div
        class="premium-modal-overlay"
        id="premiumModalOverlay"
    ></div>


    <!-- MODAL -->

    <div class="premium-modal-box">


        <!-- CLOSE BUTTON -->

        <button
            type="button"
            class="premium-close"
            id="premiumClose"
            aria-label="Close"
        >
            &times;
        </button>


        <!-- ICON -->

        <div class="premium-icon">

            ★

        </div>


        <!-- TITLE -->

        <h2 id="premiumModalTitle">

            Activate Premium

        </h2>


        <!-- HOTEL NAME -->

        <p class="premium-hotel-name">

            <?= h($premiumHotel['name']) ?>

        </p>


        <!-- DESCRIPTION -->

        <p class="premium-description">

            Your hotel is currently listed as a
            <strong>Free Hotel</strong>.

            Activate Premium to unlock more features
            and give your hotel better visibility.

        </p>


        <!-- FEATURES -->

        <div class="premium-features">


            <div class="premium-feature">

                <span class="feature-check">
                    ✓
                </span>

                <span>
                    Premium hotel badge
                </span>

            </div>


            <div class="premium-feature">

                <span class="feature-check">
                    ✓
                </span>

                <span>
                    Rich hotel gallery
                </span>

            </div>


            <div class="premium-feature">

                <span class="feature-check">
                    ✓
                </span>

                <span>
                    Booking features
                </span>

            </div>


            <div class="premium-feature">

                <span class="feature-check">
                    ✓
                </span>

                <span>
                    Better visibility
                </span>

            </div>


        </div>


        <!-- ========================================================
             ACTIVATION FORM
        ========================================================= -->

        <form
            method="POST"
            action=""
            id="premiumActivationForm"
        >


            <!-- CSRF -->

            <input
                type="hidden"
                name="premium_csrf"
                value="<?= h($_SESSION['premium_csrf']) ?>"
            >


            <!-- ACTION -->

            <input
                type="hidden"
                name="activate_premium"
                value="1"
            >


            <!-- ACTIVATE BUTTON -->

            <a
                href="/allhotels/payment/premium-payment.php?hotel_id=<?= (int) $premiumHotel['id'] ?>"
                class="premium-activate-btn"
            >
                <span class="premium-star">★</span>
                Activate Premium
            </a>


        </form>


        <!-- LATER -->

        <button
            type="button"
            class="premium-later-btn"
            id="premiumLater"
        >

            Maybe Later

        </button>


    </div>

</div>


<!-- ============================================================
     PREMIUM POPUP CSS
============================================================ -->

<style>

.premium-modal {

    position: fixed;

    inset: 0;

    z-index: 999999;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

}


.premium-modal-overlay {

    position: absolute;

    inset: 0;

    background: rgba(0, 0, 0, 0.68);

    backdrop-filter: blur(6px);

}


.premium-modal-box {

    position: relative;

    z-index: 2;

    width: 100%;

    max-width: 480px;

    max-height: calc(100vh - 40px);

    overflow-y: auto;

    background: #ffffff;

    border-radius: 22px;

    padding: 34px 30px 28px;

    text-align: center;

    box-shadow:
        0 30px 80px rgba(0, 0, 0, 0.28);

    animation: premiumModalShow 0.35s ease;

}


@keyframes premiumModalShow {

    from {

        opacity: 0;

        transform:
            translateY(25px)
            scale(0.94);

    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            scale(1);

    }

}


/* ============================================================
   CLOSE
============================================================ */

.premium-close {

    position: absolute;

    top: 12px;

    right: 15px;

    width: 38px;

    height: 38px;

    border: none;

    border-radius: 50%;

    background: transparent;

    color: #777;

    font-size: 30px;

    line-height: 1;

    cursor: pointer;

    transition: 0.2s;

}


.premium-close:hover {

    background: #f2f2f2;

    color: #222;

}


/* ============================================================
   ICON
============================================================ */

.premium-icon {

    width: 72px;

    height: 72px;

    margin: 0 auto 18px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #fff4d6;

    color: #e7a000;

    font-size: 35px;

}


/* ============================================================
   TITLE
============================================================ */

.premium-modal-box h2 {

    margin: 0;

    color: #222;

    font-size: 28px;

    font-weight: 750;

    line-height: 1.2;

}


.premium-hotel-name {

    margin: 8px 0 16px;

    color: #555;

    font-size: 17px;

    font-weight: 600;

}


/* ============================================================
   DESCRIPTION
============================================================ */

.premium-description {

    max-width: 400px;

    margin: 0 auto 22px;

    color: #666;

    font-size: 15px;

    line-height: 1.65;

}


.premium-description strong {

    color: #333;

}


/* ============================================================
   FEATURES
============================================================ */

.premium-features {

    margin-bottom: 24px;

    padding: 15px 18px;

    border-radius: 14px;

    background: #f7f8fa;

    text-align: left;

}


.premium-feature {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 7px 0;

    color: #333;

    font-size: 15px;

}


.feature-check {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    width: 21px;

    height: 21px;

    flex: 0 0 21px;

    border-radius: 50%;

    background: #e7f7ed;

    color: #168344;

    font-size: 13px;

    font-weight: 700;

}


/* ============================================================
   ACTIVATE BUTTON
============================================================ */

.premium-activate-btn {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    width: 100%;

    min-height: 50px;

    padding: 13px 20px;

    border: none;

    border-radius: 11px;

    background: #111827;

    color: #ffffff;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    transition:
        transform 0.2s,
        background 0.2s,
        box-shadow 0.2s;

}


.premium-activate-btn:hover {

    background: #000000;

    transform: translateY(-1px);

    box-shadow:
        0 8px 20px rgba(0, 0, 0, 0.18);

}


.premium-activate-btn:active {

    transform: translateY(0);

}


.premium-star {

    font-size: 17px;

}


/* ============================================================
   LATER BUTTON
============================================================ */

.premium-later-btn {

    width: 100%;

    margin-top: 9px;

    padding: 11px;

    border: none;

    background: transparent;

    color: #777;

    font-size: 14px;

    cursor: pointer;

    transition: 0.2s;

}


.premium-later-btn:hover {

    color: #222;

}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 500px) {

    .premium-modal {

        padding: 15px;

    }


    .premium-modal-box {

        max-height: calc(100vh - 30px);

        padding:
            30px
            20px
            22px;

        border-radius: 18px;

    }


    .premium-icon {

        width: 62px;

        height: 62px;

        font-size: 30px;

    }


    .premium-modal-box h2 {

        font-size: 24px;

    }


    .premium-hotel-name {

        font-size: 16px;

    }


    .premium-description {

        font-size: 14px;

    }


    .premium-feature {

        font-size: 14px;

    }

}

</style>


<!-- ============================================================
     PREMIUM POPUP JAVASCRIPT
============================================================ -->

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const modal =
            document.getElementById(
                'premiumModal'
            );


        const closeButton =
            document.getElementById(
                'premiumClose'
            );


        const laterButton =
            document.getElementById(
                'premiumLater'
            );


        const overlay =
            document.getElementById(
                'premiumModalOverlay'
            );


        const activationForm =
            document.getElementById(
                'premiumActivationForm'
            );


        const activateButton =
            document.getElementById(
                'premiumActivateBtn'
            );


        /*
        |--------------------------------------------------------------------------
        | CLOSE MODAL
        |--------------------------------------------------------------------------
        */

        function closePremiumModal() {

            if (!modal) {
                return;
            }


            modal.style.display = 'none';


            /*
            | Allow body scrolling again
            */

            document.body.style.overflow = '';

        }


        /*
        |--------------------------------------------------------------------------
        | CLOSE BUTTON
        |--------------------------------------------------------------------------
        */

        if (closeButton) {

            closeButton.addEventListener(
                'click',
                closePremiumModal
            );

        }


        /*
        |--------------------------------------------------------------------------
        | MAYBE LATER
        |--------------------------------------------------------------------------
        */

        if (laterButton) {

            laterButton.addEventListener(
                'click',
                closePremiumModal
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CLICK OUTSIDE
        |--------------------------------------------------------------------------
        */

        if (overlay) {

            overlay.addEventListener(
                'click',
                closePremiumModal
            );

        }


        /*
        |--------------------------------------------------------------------------
        | ESC KEY
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    modal &&
                    modal.style.display !== 'none'
                ) {

                    closePremiumModal();

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | PREVENT DOUBLE CLICK
        |--------------------------------------------------------------------------
        */

        if (activationForm) {

            activationForm.addEventListener(
                'submit',
                function () {

                    if (activateButton) {

                        activateButton.disabled =
                            true;

                        activateButton.innerHTML =
                            'Activating Premium...';

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | STOP BODY SCROLLING
        |--------------------------------------------------------------------------
        */

        if (modal) {

            document.body.style.overflow =
                'hidden';

        }

    }
);

</script>


<?php endif; ?>


<?php
/*
|--------------------------------------------------------------------------
| PREMIUM SUCCESS MESSAGE
|--------------------------------------------------------------------------
*/

if (!empty($premiumMessage)):
?>

<div
    class="premium-success-message"
    id="premiumSuccessMessage"
>

    <div class="premium-success-icon">
        ✓
    </div>

    <div>

        <strong>
            Premium Activated!
        </strong>

        <p>
            <?= h($premiumMessage) ?>
        </p>

    </div>

</div>


<style>

.premium-success-message {

    position: fixed;

    right: 25px;

    bottom: 25px;

    z-index: 999999;

    display: flex;

    align-items: center;

    gap: 12px;

    max-width: 380px;

    padding: 15px 18px;

    border-radius: 13px;

    background: #ffffff;

    box-shadow:
        0 12px 35px rgba(0, 0, 0, 0.18);

    border-left: 4px solid #168344;

    animation: premiumSuccessShow 0.35s ease;

}


@keyframes premiumSuccessShow {

    from {

        opacity: 0;

        transform: translateY(15px);

    }

    to {

        opacity: 1;

        transform: translateY(0);

    }

}


.premium-success-icon {

    width: 34px;

    height: 34px;

    flex: 0 0 34px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #e7f7ed;

    color: #168344;

    font-weight: 700;

}


.premium-success-message strong {

    color: #222;

}


.premium-success-message p {

    margin: 3px 0 0;

    color: #666;

    font-size: 13px;

}


@media (max-width: 600px) {

    .premium-success-message {

        left: 15px;

        right: 15px;

        bottom: 15px;

        max-width: none;

    }

}

</style>


<script>

setTimeout(function () {

    const message =
        document.getElementById(
            'premiumSuccessMessage'
        );

    if (message) {

        message.style.opacity = '0';

        message.style.transform =
            'translateY(10px)';

        setTimeout(function () {

            message.remove();

        }, 300);

    }

}, 4000);

</script>

<?php endif; ?>