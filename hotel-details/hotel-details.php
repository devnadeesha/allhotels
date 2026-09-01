<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

/*
|--------------------------------------------------------------------------
| Validate Hotel ID
|--------------------------------------------------------------------------
*/

if ($id <= 0) {
    http_response_code(404);

    $page_title = 'Hotel Not Found';

    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div class="container section">
        <div class="empty-state">
            <h2>Hotel not found</h2>

            <p>Invalid hotel ID.</p>

            <a class="btn btn-primary" href="/allhotels/index.php">
                Back to Home
            </a>
        </div>
    </div>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}


/*
|--------------------------------------------------------------------------
| Get Hotel
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM hotels
    WHERE id = ?
      AND status = 'approved'
    LIMIT 1
");

$stmt->execute([$id]);

$hotel = $stmt->fetch(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Hotel Not Found
|--------------------------------------------------------------------------
*/

if (!$hotel) {
    http_response_code(404);

    $page_title = 'Hotel Not Found';

    require_once __DIR__ . '/../includes/header.php';
    ?>

    <div class="container section">
        <div class="empty-state">

            <h2>Hotel not found</h2>

            <p>
                The listing you are looking for is unavailable.
            </p>

            <a class="btn btn-primary" href="/allhotels/index.php">
                Back to Home
            </a>

        </div>
    </div>

    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}


$page_title = $hotel['name'];


/*
|--------------------------------------------------------------------------
| Get Hotel Images
|--------------------------------------------------------------------------
*/

$imagesStmt = $pdo->prepare("
    SELECT *
    FROM hotel_images
    WHERE hotel_id = ?
    ORDER BY is_main DESC, id ASC
");

$imagesStmt->execute([$id]);

$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Create Correct Image URLs
|--------------------------------------------------------------------------
*/

foreach ($images as &$img) {

    $img['image_url'] =
        '/allhotels/' . ltrim($img['image_path'], '/');

}

unset($img);


/*
|--------------------------------------------------------------------------
| Get Main Image
|--------------------------------------------------------------------------
*/

$mainImage = null;

foreach ($images as $img) {

    if ((int) $img['is_main'] === 1) {

        $mainImage = $img;

        break;

    }

}


/*
|--------------------------------------------------------------------------
| Fallback Main Image
|--------------------------------------------------------------------------
*/

if (!$mainImage && !empty($images)) {

    $mainImage = $images[0];

}

/*
|--------------------------------------------------------------------------
| Get Function Types
|--------------------------------------------------------------------------
*/

$funcStmt = $pdo->prepare("
    SELECT ft.*
    FROM hotel_function_types hft
    INNER JOIN function_types ft
        ON ft.id = hft.function_type_id
    WHERE hft.hotel_id = ?
    ORDER BY ft.name ASC
");

$funcStmt->execute([$id]);

$functions = $funcStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Reviews
|--------------------------------------------------------------------------
|
| reviewer_name is stored directly in reviews table.
| user_id is optional.
|
*/

$reviewsStmt = $pdo->prepare("
    SELECT
        r.id,
        r.hotel_id,
        r.user_id,
        r.reviewer_name,
        r.rating,
        r.comment,
        r.created_at
    FROM reviews r
    WHERE r.hotel_id = ?
    ORDER BY r.created_at DESC, r.id DESC
");

$reviewsStmt->execute([$id]);

$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Average Rating
|--------------------------------------------------------------------------
*/

$avg = average_rating($pdo, $id);


/*
|--------------------------------------------------------------------------
| Flash Message
|--------------------------------------------------------------------------
*/

$flash = $_SESSION['flash'] ?? null;

unset($_SESSION['flash']);


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container section">

    <!-- FLASH MESSAGE -->

    <?php if ($flash): ?>

        <div
            class="alert alert-<?= h($flash['type']) ?>"
            data-autohide
        >
            <?= h($flash['message']) ?>
        </div>

    <?php endif; ?>


    <!-- =========================================================
         HOTEL IMAGE GALLERY
         ========================================================= -->

    <?php if (!empty($images) && $mainImage): ?>

        <div class="hotel-gallery">

            <!-- MAIN IMAGE -->

            <div class="gallery-main">

                <img
                    id="mainHotelImage"
                    src="<?= h($mainImage['image_url']) ?>"
                    alt="<?= h($hotel['name']) ?>"
                >


                <?php if (count($images) > 1): ?>

                    <!-- PREVIOUS -->

                    <button
                        type="button"
                        class="gallery-arrow gallery-prev"
                        aria-label="Previous image"
                    >
                        ❮
                    </button>


                    <!-- NEXT -->

                    <button
                        type="button"
                        class="gallery-arrow gallery-next"
                        aria-label="Next image"
                    >
                        ❯
                    </button>

                <?php endif; ?>


                <!-- PREMIUM BADGE -->

                <?php if ((int) ($hotel['is_premium'] ?? 0) === 1): ?>

                    <div class="premium-badge">
                        ★ Premium Listed
                    </div>

                <?php endif; ?>

            </div>


            <!-- =================================================
                 THUMBNAILS
                 ================================================= -->

            <?php if (count($images) > 1): ?>

                <div class="gallery-thumbnails">

                    <?php foreach ($images as $index => $img): ?>

                        <img
                            src="<?= h($img['image_url']) ?>"
                            alt="<?= h($hotel['name']) ?> Gallery <?= $index + 1 ?>"
                            class="gallery-thumbnail <?= $img['image_path'] === $mainImage['image_path'] ? 'active' : '' ?>"
                            data-image="<?= h($img['image_url']) ?>"
                        >

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>


        <!-- =====================================================
             LARGE IMAGE LIGHTBOX
             ===================================================== -->

        <div
            id="galleryLightbox"
            class="gallery-lightbox"
        >

            <!-- CLOSE -->

            <button
                type="button"
                class="lightbox-close"
                aria-label="Close gallery"
            >
                ×
            </button>


            <?php if (count($images) > 1): ?>

                <!-- PREVIOUS -->

                <button
                    type="button"
                    class="lightbox-arrow lightbox-prev"
                    aria-label="Previous image"
                >
                    ❮
                </button>

            <?php endif; ?>


            <!-- LARGE IMAGE -->

            <img
                id="lightboxImage"
                src=""
                alt="<?= h($hotel['name']) ?>"
            >


            <?php if (count($images) > 1): ?>

                <!-- NEXT -->

                <button
                    type="button"
                    class="lightbox-arrow lightbox-next"
                    aria-label="Next image"
                >
                    ❯
                </button>

            <?php endif; ?>

        </div>

    <?php else: ?>

        <!-- NO IMAGE -->

        <div class="details-hero">

            <div class="hotel-no-image">
                <?= h($hotel['name']) ?>
            </div>

            <?php if ((int) ($hotel['is_premium'] ?? 0) === 1): ?>

                <div
                    class="premium-badge"
                    style="top:20px;left:20px;"
                >
                    ★ Premium Listed
                </div>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         DETAILS LAYOUT
         ========================================================= -->

    <div class="details-layout">

        <!-- =====================================================
             LEFT
             ===================================================== -->

        <div>

            <!-- HOTEL INFORMATION -->

            <div class="info-card">

                <h1>
                    <?= h($hotel['name']) ?>
                </h1>


                <!-- FUNCTION TYPES -->

                <?php if (!empty($functions)): ?>

                    <div class="tags">

                        <?php foreach ($functions as $f): ?>

                            <span class="tag">
                                <?= h($f['name']) ?>
                            </span>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- ADDRESS -->

                <div class="info-row">

                    <div class="label">
                        Address
                    </div>

                    <div>

                        <?= h($hotel['address'] ?? '') ?>

                        <?php if (!empty($hotel['district'])): ?>

                            , <?= h($hotel['district']) ?>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- CONTACT -->

                <div class="info-row">

                    <div class="label">
                        Contact
                    </div>

                    <div>

                        <?= h(
                            $hotel['contact_number']
                            ?? 'Not provided'
                        ) ?>

                    </div>

                </div>


                <!-- PRICE -->

                <div class="info-row">

                    <div class="label">
                        Cost
                    </div>

                    <div>

                        Starting from Rs.

                        <?= number_format(
                            (float) ($hotel['starting_price'] ?? 0)
                        ) ?>

                    </div>

                </div>


                <!-- GUEST CAPACITY -->

                <?php if (!empty($hotel['max_guests'])): ?>

                    <div class="info-row">

                        <div class="label">
                            Guest Capacity
                        </div>

                        <div>

                            <?= (int) (
                                $hotel['min_guests'] ?? 1
                            ) ?>

                            –

                            <?= (int) $hotel['max_guests'] ?>

                            guests

                        </div>

                    </div>

                <?php endif; ?>


                <!-- DESCRIPTION -->

                <div class="info-row">

                    <div class="label">
                        Description
                    </div>

                    <div>

                        <?= nl2br(
                            h($hotel['description'] ?? '')
                        ) ?>

                    </div>

                </div>


                <!-- RATING -->

                <div class="avg-rating-big">

                    <span class="num">

                        <?= !empty($avg['avg_rating'])
                            ? number_format(
                                (float) $avg['avg_rating'],
                                1
                            )
                            : '—'
                        ?>

                    </span>

                    <div>

                        <div class="stars">

                            <?= star_html(
                                $avg['avg_rating'] ?? 0
                            ) ?>

                        </div>

                        <div class="footer-note">

                            <?= (int) (
                                $avg['total'] ?? 0
                            ) ?>

                            review(s)

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CUSTOMER REVIEWS
                 ================================================= -->

            <div class="info-card">

                <h3>
                    Customer Reviews
                </h3>


                <?php if (empty($reviews)): ?>

                    <p class="footer-note">
                        No reviews yet.
                        Be the first to share your experience!
                    </p>

                <?php else: ?>


                    <?php foreach ($reviews as $r): ?>

                        <div class="review">

                            <!-- RATING -->

                            <div class="stars">

                                <?= star_html(
                                    (int) $r['rating']
                                ) ?>

                            </div>


                            <!-- COMMENT -->

                            <p>

                                <?= nl2br(
                                    h($r['comment'] ?? '')
                                ) ?>

                            </p>


                            <!-- REVIEWER -->

                            <div class="reviewer">

                                <?= h(
                                    !empty($r['reviewer_name'])
                                        ? $r['reviewer_name']
                                        : 'Guest'
                                ) ?>


                                <?php if (!empty($r['created_at'])): ?>

                                    <span class="date">

                                        —

                                        <?= date(
                                            'd M Y',
                                            strtotime(
                                                $r['created_at']
                                            )
                                        ) ?>

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>


                <hr
                    style="
                        border:none;
                        border-top:1px solid var(--sand-100);
                        margin:20px 0;
                    "
                >


                <!-- WRITE REVIEW -->

                <h3>
                    Write a Review
                </h3>

                <p class="footer-note">
                    You don't need an account to leave a review.
                </p>


                <form
                    class="review-form"
                    method="POST"
                    action="/allhotels/api/submit-review.php"
                >

                    <!-- HOTEL ID -->

                    <input
                        type="hidden"
                        name="hotel_id"
                        value="<?= (int) $hotel['id'] ?>"
                    >


                    <!-- REVIEWER NAME -->

                    <label for="reviewer_name">
                        Your Name
                    </label>

                    <input
                        type="text"
                        id="reviewer_name"
                        name="reviewer_name"
                        placeholder="Enter your name"
                        maxlength="100"
                        required
                    >


                    <!-- RATING -->

                    <label>
                        Rating
                    </label>

                    <div
                        class="star-select"
                        data-input="#ratingInput"
                    >

                        <span data-value="1">★</span>
                        <span data-value="2">★</span>
                        <span data-value="3">★</span>
                        <span data-value="4">★</span>
                        <span data-value="5">★</span>

                    </div>

                    <input
                        type="hidden"
                        id="ratingInput"
                        name="rating"
                        value="5"
                    >


                    <!-- COMMENT -->

                    <label for="comment">
                        Your Review
                    </label>

                    <textarea
                        id="comment"
                        name="comment"
                        rows="4"
                        maxlength="1000"
                        placeholder="Write your experience..."
                        required
                    ></textarea>


                    <!-- SUBMIT -->

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Submit Review
                    </button>

                </form>

            </div>

        </div>


        <!-- =====================================================
             RIGHT
             ===================================================== -->

        <div>

            <!-- BOOKING CARD -->

            <div class="sidebar-card">

                <h3>
                    Booking
                </h3>


                <div class="price-line">

                    Rs.

                    <?= number_format(
                        (float) ($hotel['starting_price'] ?? 0)
                    ) ?>

                </div>


                <p class="footer-note">

                    Starting price — final cost depends on
                    function type and guest count.

                </p>


                <?php if ((int) ($hotel['is_premium'] ?? 0) === 1): ?>

                    <a
                        href="/allhotels/api/book-hotel.php?hotel_id=<?= (int) $hotel['id'] ?>"
                        class="btn btn-terracotta btn-block"
                    >
                        Book Now
                    </a>


                    <p
                        class="footer-note"
                        style="margin-top:10px;"
                    >
                        Online booking is available for this
                        Premium hotel.
                    </p>

                <?php else: ?>

                    <div class="locked-feature">

                        🔒 Online booking is available
                        on Premium listings.

                        <br><br>

                        Contact the hotel directly using
                        the number above.

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>