<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';

/*
|--------------------------------------------------------------------------
| Districts
|--------------------------------------------------------------------------
*/

$districts = $pdo->query("
    SELECT DISTINCT district
    FROM hotels
    WHERE status = 'approved'
      AND district IS NOT NULL
      AND district != ''
    ORDER BY district
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Function Types
|--------------------------------------------------------------------------
*/

$functionTypes = $pdo->query("
    SELECT *
    FROM function_types
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Get Approved Hotels
|--------------------------------------------------------------------------
*/

$hotels = $pdo->query("
    SELECT 
        h.*,

        (
            SELECT hi.image_path
            FROM hotel_images hi
            WHERE hi.hotel_id = h.id
              AND hi.is_main = 1
            ORDER BY hi.id DESC
            LIMIT 1
        ) AS main_image,

        (
            SELECT AVG(r.rating)
            FROM reviews r
            WHERE r.hotel_id = h.id
        ) AS avg_rating,

        (
            SELECT GROUP_CONCAT(
                ft.name
                SEPARATOR ','
            )
            FROM hotel_function_types hft
            INNER JOIN function_types ft
                ON ft.id = hft.function_type_id
            WHERE hft.hotel_id = h.id
        ) AS functions

    FROM hotels h

    WHERE h.status = 'approved'

    ORDER BY
        h.is_premium DESC,
        h.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Limit Initial Hotels to 8
|--------------------------------------------------------------------------
*/

$displayHotels = array_slice($hotels, 0, 8);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/slider.php';
?>


<!-- ============================================================
     HERO SEARCH
============================================================ -->

<section class="hero">

    <div class="container">

        <form class="search-panel" id="searchForm">

            <!-- SEARCH HOTEL -->

            <div class="search-field">

                <label for="q">
                    Search Hotel
                </label>

                <input
                    type="text"
                    id="q"
                    name="q"
                    placeholder="Hotel name or keyword"
                >

            </div>


            <!-- LOCATION -->

            <div class="search-field">

                <label for="district">
                    Location
                </label>

                <select
                    id="district"
                    name="district"
                >

                    <option value="">
                        All Districts
                    </option>

                    <?php foreach ($districts as $d): ?>

                        <option
                            value="<?= h($d['district']) ?>"
                        >
                            <?= h($d['district']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- FUNCTION TYPE -->

            <div class="search-field">

                <label for="function_type">
                    Function Type
                </label>

                <select
                    id="function_type"
                    name="function_type"
                >

                    <option value="">
                        Any
                    </option>

                    <?php foreach ($functionTypes as $ft): ?>

                        <option
                            value="<?= (int) $ft['id'] ?>"
                        >
                            <?= h($ft['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- GUESTS -->

            <div class="search-field">

                <label for="guests">
                    Guest Capacity
                </label>

                <input
                    type="number"
                    id="guests"
                    name="guests"
                    min="1"
                    placeholder="e.g. 150"
                >

            </div>


            <!-- PRICE -->

            <div class="search-field">

                <label for="max_price">
                    Max Price (Rs.)
                </label>

                <input
                    type="number"
                    id="max_price"
                    name="max_price"
                    min="0"
                    step="1000"
                    placeholder="e.g. 50000"
                >

            </div>


            <button
                type="submit"
                class="search-btn"
            >
                Search Hotels
            </button>

        </form>

    </div>

</section>



<!-- ============================================================
     HOTELS
============================================================ -->

<section class="section">

    <div class="container">


        <!-- SECTION HEADER -->

        <div class="section-head">

            <div>

                <h2>
                    Featured &amp; Available Hotels
                </h2>

                <p>
                    Premium partners are highlighted first with richer
                    galleries and instant booking.
                </p>

            </div>


            <span
                class="results-count"
                id="resultsCount"
            >
                <?= count($displayHotels) ?> hotel(s) found
            </span>

        </div>



        <!-- HOTEL GRID -->

        <div
            class="hotel-grid"
            id="hotelGrid"
        >


            <?php if (empty($displayHotels)): ?>


                <div class="empty-state">

                    No hotels listed yet.
                    Check back soon!

                </div>


            <?php else: ?>


                <?php foreach ($displayHotels as $hotel): ?>


                    <article class="hotel-card">


                        <!-- =================================================
                             IMAGE
                        ================================================== -->

                        <div class="thumb">


                            <?php if ((int) $hotel['is_premium'] === 1): ?>

                                <div class="premium-badge">
                                    ★ Premium
                                </div>

                            <?php endif; ?>


                            <?php

                            /*
                            |--------------------------------------------------------------------------
                            | Image Path
                            |--------------------------------------------------------------------------
                            */

                            $imageUrl = '';

                            if (!empty($hotel['main_image'])) {

                                $imagePath = trim($hotel['main_image']);

                                /*
                                | If database already contains /allhotels/
                                */

                                if (strpos($imagePath, '/allhotels/') === 0) {

                                    $imageUrl = $imagePath;

                                }

                                /*
                                | Normal database value:
                                | api/images/hotel_xxx.jpg
                                */

                                elseif (strpos($imagePath, 'api/images/') === 0) {

                                    $imageUrl =
                                        '/allhotels/' . ltrim($imagePath, '/');

                                }

                                /*
                                | Other path
                                */

                                else {

                                    $imageUrl =
                                        '/allhotels/' . ltrim($imagePath, '/');

                                }

                            }

                            ?>


                            <?php if ($imageUrl !== ''): ?>


                                <img
                                    src="<?= h($imageUrl) ?>"
                                    alt="<?= h($hotel['name']) ?>"
                                    loading="lazy"
                                    class="hotel-main-image"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >


                                <!-- Image error fallback -->

                                <div
                                    class="image-fallback"
                                    style="display:none;"
                                >
                                    <?= h($hotel['name']) ?>
                                </div>


                            <?php else: ?>


                                <!-- No Image -->

                                <div class="image-fallback">

                                    <?= h($hotel['name']) ?>

                                </div>


                            <?php endif; ?>


                        </div>



                        <!-- =================================================
                             HOTEL BODY
                        ================================================== -->

                        <div class="body">


                            <h3>
                                <?= h($hotel['name']) ?>
                            </h3>


                            <div class="addr">

                                📍
                                <?= h($hotel['address']) ?>,
                                <?= h($hotel['district']) ?>

                            </div>



                            <!-- FUNCTION TAGS -->

                            <div class="tags">

                                <?php

                                $tags = array_filter(
                                    explode(
                                        ',',
                                        $hotel['functions'] ?? ''
                                    )
                                );

                                ?>


                                <?php foreach (
                                    array_slice($tags, 0, 3)
                                    as $tag
                                ): ?>

                                    <span class="tag">
                                        <?= h(trim($tag)) ?>
                                    </span>

                                <?php endforeach; ?>


                            </div>



                            <!-- PRICE + RATING -->

                            <div class="card-meta">


                                <span class="price">

                                    From Rs.
                                    <?= number_format(
                                        (float) $hotel['starting_price']
                                    ) ?>

                                </span>


                                <span class="rating">

                                    <?= star_html(
                                        $hotel['avg_rating'] ?? 0
                                    ) ?>

                                    <?php if (
                                        !empty($hotel['avg_rating'])
                                    ): ?>

                                        <?= number_format(
                                            (float) $hotel['avg_rating'],
                                            1
                                        ) ?>

                                    <?php else: ?>

                                        New

                                    <?php endif; ?>

                                </span>


                            </div>



                            <!-- VIEW DETAILS -->

                            <a
                                class="btn btn-primary btn-block"
                                href="/allhotels/hotel-details/hotel-details.php?id=<?= (int) $hotel['id'] ?>"
                            >
                                View Details
                            </a>


                        </div>


                    </article>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </div>

</section>



<?php

$extra_scripts = ['/js/search.js'];

require_once __DIR__ . '/includes/footer.php';
