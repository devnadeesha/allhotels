<?php

/*
|--------------------------------------------------------------------------
| PREMIUM POPUP
|--------------------------------------------------------------------------
|
| File:
| /allhotels/includes/premium-popup.php
|
| This file:
| - Detects logged-in hotel owners
| - Finds their hotel
| - Shows Premium popup for Free hotels
| - Sends owner to existing payment gateway
|
| PAYMENT PATH IS NOT CHANGED:
| /allhotels/payment/premium-payment.php?hotel_id=...
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| START SESSION
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


/*
|--------------------------------------------------------------------------
| CURRENT USER ID
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
    | GET CURRENT USER
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
    | Your current role value is:
    | owner
    |
    */

    if (
        $currentUser &&
        strtolower(trim($currentUser['role'])) === 'owner'
    ) {


        /*
        |--------------------------------------------------------------------------
        | GET OWNER HOTEL
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

        $premiumHotel =
            $hotelStmt->fetch(PDO::FETCH_ASSOC);


        /*
        |--------------------------------------------------------------------------
        | SHOW POPUP ONLY FOR FREE HOTEL
        |--------------------------------------------------------------------------
        */

        if (
            $premiumHotel &&
            (int) $premiumHotel['is_premium'] === 0
        ) {

            $premiumPopupShow = true;

        }

    }

}

?>


<?php if ($premiumPopupShow): ?>

<!-- ============================================================
     PREMIUM MODAL
============================================================ -->

<div
    class="premium-modal"
    id="premiumModal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="premiumTitle"
>


    <!-- ========================================================
         BACKDROP
    ========================================================= -->

    <div
        class="premium-backdrop"
        id="premiumBackdrop"
    ></div>


    <!-- ========================================================
         MODAL CARD
    ========================================================= -->

    <div class="premium-card">


        <!-- ====================================================
             CLOSE BUTTON
        ===================================================== -->

        <button
            type="button"
            class="premium-close"
            id="premiumClose"
            aria-label="Close premium popup"
        >

            <span></span>
            <span></span>

        </button>


        <!-- ====================================================
             PREMIUM TOP AREA
        ===================================================== -->

        <div class="premium-top">


            <!-- PREMIUM ICON -->

            <div class="premium-icon-wrapper">

                <div class="premium-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <path
                            d="M12 2L14.9 8.1L21.5 8.9L16.7 13.5L17.9 20L12 16.9L6.1 20L7.3 13.5L2.5 8.9L9.1 8.1L12 2Z"
                            fill="currentColor"
                        />

                    </svg>

                </div>

            </div>


            <!-- SMALL LABEL -->

            <div class="premium-label">

                PREMIUM UPGRADE

            </div>


            <!-- TITLE -->

            <h2 id="premiumTitle">

                Take Your Hotel
                <span>to the Next Level</span>

            </h2>


            <!-- HOTEL NAME -->

            <p class="premium-hotel-name">

                <?= h($premiumHotel['name']) ?>

            </p>


            <!-- DESCRIPTION -->

            <p class="premium-description">

                Give your hotel more visibility, more features,
                and a better experience for your customers.

            </p>

        </div>


        <!-- ====================================================
             BENEFITS
        ===================================================== -->

        <div class="premium-benefits">


            <!-- BENEFIT 01 -->

            <div class="premium-benefit">

                <div class="benefit-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <path
                            d="M12 3L19 6V11C19 15.5 16 19.3 12 21C8 19.3 5 15.5 5 11V6L12 3Z"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linejoin="round"
                        />

                        <path
                            d="M9 12L11 14L15 10"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>

                </div>

                <div class="benefit-content">

                    <strong>
                        Premium Badge
                    </strong>

                    <span>
                        Stand out from other hotels
                    </span>

                </div>

            </div>


            <!-- BENEFIT 02 -->

            <div class="premium-benefit">

                <div class="benefit-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <rect
                            x="3"
                            y="4"
                            width="18"
                            height="16"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <circle
                            cx="8"
                            cy="9"
                            r="1.5"
                            fill="currentColor"
                        />

                        <path
                            d="M3 16L8 12L12 15L15 12L21 17"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>

                </div>

                <div class="benefit-content">

                    <strong>
                        Rich Hotel Gallery
                    </strong>

                    <span>
                        Showcase your hotel beautifully
                    </span>

                </div>

            </div>


            <!-- BENEFIT 03 -->

            <div class="premium-benefit">

                <div class="benefit-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <path
                            d="M7 3V6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                        <path
                            d="M17 3V6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                        <rect
                            x="4"
                            y="5"
                            width="16"
                            height="16"
                            rx="2"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M4 10H20"
                            stroke="currentColor"
                            stroke-width="1.8"
                        />

                        <path
                            d="M8 14H12"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                        <path
                            d="M8 17H15"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                    </svg>

                </div>

                <div class="benefit-content">

                    <strong>
                        Booking Features
                    </strong>

                    <span>
                        Make it easier for guests to book
                    </span>

                </div>

            </div>


            <!-- BENEFIT 04 -->

            <div class="premium-benefit">

                <div class="benefit-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <path
                            d="M4 19V14"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                        <path
                            d="M10 19V10"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                        <path
                            d="M16 19V6"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                        <path
                            d="M22 19V3"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />

                    </svg>

                </div>

                <div class="benefit-content">

                    <strong>
                        Better Visibility
                    </strong>

                    <span>
                        Get noticed by more customers
                    </span>

                </div>

            </div>


        </div>


        <!-- ====================================================
             ACTION AREA
        ===================================================== -->

        <div class="premium-action">


            <!-- PAYMENT BUTTON -->

            <a
                href="/allhotels/payment/premium-payment.php?hotel_id=<?= (int) $premiumHotel['id'] ?>"
                class="premium-button"
            >

                <span>
                    Activate Premium
                </span>

                <span class="premium-button-arrow">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >

                        <path
                            d="M5 12H19"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                        />

                        <path
                            d="M13 6L19 12L13 18"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                    </svg>

                </span>

            </a>


            <!-- LATER -->

            <button
                type="button"
                class="premium-later"
                id="premiumLater"
            >

                Maybe later

            </button>


        </div>


        <!-- ====================================================
             TRUST TEXT
        ===================================================== -->

        <div class="premium-trust">

            <span class="trust-dot"></span>

            Secure payment · Instant activation after payment

        </div>


    </div>

</div>


<!-- ============================================================
     PREMIUM POPUP CSS
============================================================ -->

<style>

/* ============================================================
   MODAL
============================================================ */

.premium-modal {

    position: fixed;

    inset: 0;

    z-index: 999999;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 24px;

}


/* ============================================================
   BACKDROP
============================================================ */

.premium-backdrop {

    position: absolute;

    inset: 0;

    background:
        rgba(15, 23, 42, 0.72);

    backdrop-filter:
        blur(8px);

    -webkit-backdrop-filter:
        blur(8px);

    animation:
        premiumBackdropIn 0.3s ease;

}


@keyframes premiumBackdropIn {

    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }

}


/* ============================================================
   CARD
============================================================ */

.premium-card {

    position: relative;

    z-index: 2;

    width: 100%;

    max-width: 510px;

    max-height: calc(100vh - 48px);

    overflow-y: auto;

    background: #ffffff;

    border-radius: 26px;

    padding: 34px 34px 26px;

    text-align: center;

    box-shadow:
        0 35px 90px
        rgba(0, 0, 0, 0.30);

    animation:
        premiumCardIn 0.4s
        cubic-bezier(.22, 1, .36, 1);

}


@keyframes premiumCardIn {

    from {

        opacity: 0;

        transform:
            translateY(35px)
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
   CLOSE BUTTON
============================================================ */

.premium-close {

    position: absolute;

    top: 17px;

    right: 17px;

    width: 38px;

    height: 38px;

    padding: 0;

    border: 0;

    border-radius: 50%;

    background: #f5f6f8;

    cursor: pointer;

    transition:
        background 0.2s,
        transform 0.2s;

}


.premium-close:hover {

    background: #e9ebef;

    transform: rotate(90deg);

}


.premium-close span {

    position: absolute;

    left: 11px;

    top: 18px;

    width: 16px;

    height: 2px;

    border-radius: 2px;

    background: #6b7280;

}


.premium-close span:first-child {

    transform: rotate(45deg);

}


.premium-close span:last-child {

    transform: rotate(-45deg);

}


/* ============================================================
   TOP SECTION
============================================================ */

.premium-top {

    padding: 4px 15px 22px;

}


/* ============================================================
   ICON
============================================================ */

.premium-icon-wrapper {

    display: flex;

    justify-content: center;

    margin-bottom: 16px;

}


.premium-icon {

    width: 70px;

    height: 70px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 20px;

    background:
        linear-gradient(
            145deg,
            #fff7d6,
            #ffe8a3
        );

    color: #d99a00;

    box-shadow:
        0 10px 25px
        rgba(217, 154, 0, 0.16);

}


.premium-icon svg {

    width: 34px;

    height: 34px;

}


/* ============================================================
   LABEL
============================================================ */

.premium-label {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 6px 11px;

    border-radius: 50px;

    background: #fff7df;

    color: #b77900;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.2px;

}


/* ============================================================
   TITLE
============================================================ */

.premium-card h2 {

    margin: 13px 0 7px;

    color: #111827;

    font-size: 29px;

    line-height: 1.18;

    font-weight: 800;

    letter-spacing: -0.7px;

}


.premium-card h2 span {

    display: block;

    color: #c78b00;

}


/* ============================================================
   HOTEL NAME
============================================================ */

.premium-hotel-name {

    margin: 0 0 12px;

    color: #374151;

    font-size: 16px;

    font-weight: 700;

}


/* ============================================================
   DESCRIPTION
============================================================ */

.premium-description {

    max-width: 390px;

    margin: 0 auto;

    color: #6b7280;

    font-size: 14px;

    line-height: 1.65;

}


/* ============================================================
   BENEFITS
============================================================ */

.premium-benefits {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 10px;

    padding: 18px;

    border-radius: 18px;

    background: #f8fafc;

    border: 1px solid #eef0f3;

    text-align: left;

}


/* ============================================================
   BENEFIT
============================================================ */

.premium-benefit {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    padding: 9px 5px;

}


/* ============================================================
   BENEFIT ICON
============================================================ */

.benefit-icon {

    width: 35px;

    height: 35px;

    flex: 0 0 35px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #ffffff;

    color: #c58b00;

    border: 1px solid #eee5ca;

}


.benefit-icon svg {

    width: 19px;

    height: 19px;

}


/* ============================================================
   BENEFIT TEXT
============================================================ */

.benefit-content {

    min-width: 0;

}


.benefit-content strong {

    display: block;

    margin-bottom: 2px;

    color: #1f2937;

    font-size: 12px;

    font-weight: 750;

}


.benefit-content span {

    display: block;

    color: #7b8492;

    font-size: 10px;

    line-height: 1.35;

}


/* ============================================================
   ACTION
============================================================ */

.premium-action {

    padding-top: 22px;

}


/* ============================================================
   PREMIUM BUTTON
============================================================ */

.premium-button {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 12px;

    width: 100%;

    min-height: 52px;

    padding: 14px 20px;

    border-radius: 13px;

    background:
        linear-gradient(
            135deg,
            #171717,
            #303030
        );

    color: #ffffff;

    text-decoration: none;

    font-size: 15px;

    font-weight: 750;

    box-shadow:
        0 10px 24px
        rgba(0, 0, 0, 0.17);

    transition:
        transform 0.2s,
        box-shadow 0.2s,
        background 0.2s;

}


.premium-button:hover {

    color: #ffffff;

    transform:
        translateY(-2px);

    box-shadow:
        0 14px 30px
        rgba(0, 0, 0, 0.22);

    background:
        linear-gradient(
            135deg,
            #0b0b0b,
            #252525
        );

}


/* ============================================================
   ARROW
============================================================ */

.premium-button-arrow {

    width: 27px;

    height: 27px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background:
        rgba(255,255,255,0.12);

}


.premium-button-arrow svg {

    width: 15px;

    height: 15px;

}


/* ============================================================
   MAYBE LATER
============================================================ */

.premium-later {

    margin-top: 9px;

    padding: 8px 14px;

    border: 0;

    background: transparent;

    color: #8a919c;

    font-size: 13px;

    cursor: pointer;

    transition:
        color 0.2s;

}


.premium-later:hover {

    color: #374151;

}


/* ============================================================
   TRUST
============================================================ */

.premium-trust {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    margin-top: 9px;

    color: #9ca3af;

    font-size: 10px;

}


.trust-dot {

    width: 6px;

    height: 6px;

    border-radius: 50%;

    background: #34a853;

}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 600px) {

    .premium-modal {

        padding: 14px;

    }


    .premium-card {

        max-height:
            calc(100vh - 28px);

        padding:
            27px
            18px
            20px;

        border-radius: 21px;

    }


    .premium-close {

        top: 12px;

        right: 12px;

        width: 34px;

        height: 34px;

    }


    .premium-close span {

        left: 9px;

        top: 16px;

        width: 15px;

    }


    .premium-top {

        padding:
            3px
            8px
            17px;

    }


    .premium-icon {

        width: 60px;

        height: 60px;

        border-radius: 17px;

    }


    .premium-icon svg {

        width: 29px;

        height: 29px;

    }


    .premium-label {

        font-size: 9px;

        padding: 5px 9px;

    }


    .premium-card h2 {

        font-size: 24px;

        letter-spacing: -0.4px;

    }


    .premium-hotel-name {

        font-size: 15px;

    }


    .premium-description {

        font-size: 13px;

    }


    .premium-benefits {

        grid-template-columns: 1fr;

        gap: 2px;

        padding: 12px;

    }


    .premium-benefit {

        padding: 8px 4px;

    }


    .benefit-content strong {

        font-size: 12px;

    }


    .benefit-content span {

        font-size: 10px;

    }


    .premium-action {

        padding-top: 17px;

    }


    .premium-button {

        min-height: 49px;

        font-size: 14px;

    }


    .premium-trust {

        font-size: 9px;

    }

}


/* ============================================================
   VERY SMALL DEVICES
============================================================ */

@media (max-width: 370px) {

    .premium-card h2 {

        font-size: 22px;

    }


    .premium-description {

        font-size: 12px;

    }


    .premium-benefits {

        padding: 9px;

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


        const backdrop =
            document.getElementById(
                'premiumBackdrop'
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


            modal.classList.add(
                'premium-closing'
            );


            setTimeout(
                function () {

                    modal.style.display =
                        'none';

                    document.body.style.overflow =
                        '';

                },
                180
            );

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
        | CLICK BACKDROP
        |--------------------------------------------------------------------------
        */

        if (backdrop) {

            backdrop.addEventListener(
                'click',
                closePremiumModal
            );

        }


        /*
        |--------------------------------------------------------------------------
        | ESCAPE KEY
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
        | DISABLE BODY SCROLL
        |--------------------------------------------------------------------------
        */

        if (modal) {

            document.body.style.overflow =
                'hidden';

        }


    }
);

</script>


<style>

/*
|--------------------------------------------------------------------------
| CLOSE ANIMATION
|--------------------------------------------------------------------------
*/

.premium-modal.premium-closing
.premium-backdrop {

    opacity: 0;

    transition:
        opacity 0.18s ease;

}


.premium-modal.premium-closing
.premium-card {

    opacity: 0;

    transform:
        translateY(15px)
        scale(0.97);

    transition:
        opacity 0.18s ease,
        transform 0.18s ease;

}

</style>

<?php endif; ?>