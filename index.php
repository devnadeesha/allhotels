<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';


/*
|--------------------------------------------------------------------------
| SEARCH / FILTER VALUES
|--------------------------------------------------------------------------
*/

$q = trim($_GET['q'] ?? '');

$district = trim($_GET['district'] ?? '');

$function_type = (int) ($_GET['function_type'] ?? 0);

$guests = (int) ($_GET['guests'] ?? 0);

$max_price = (int) ($_GET['max_price'] ?? 0);


/*
|--------------------------------------------------------------------------
| PAGINATION
|--------------------------------------------------------------------------
*/

$perPage = 6;

$currentPage = max(
    1,
    (int) ($_GET['page'] ?? 1)
);


/*
|--------------------------------------------------------------------------
| DISTRICTS
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
| FUNCTION TYPES
|--------------------------------------------------------------------------
*/

$functionTypes = $pdo->query("
    SELECT *
    FROM function_types
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| GET APPROVED HOTELS
|--------------------------------------------------------------------------
*/

$sql = "
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
";

$params = [];


/*
|--------------------------------------------------------------------------
| SEARCH HOTEL
|--------------------------------------------------------------------------
*/

if ($q !== '') {

    $sql .= "
        AND (
            h.name LIKE ?
            OR h.address LIKE ?
            OR h.district LIKE ?
        )
    ";

    $searchTerm = '%' . $q . '%';

    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}


/*
|--------------------------------------------------------------------------
| DISTRICT FILTER
|--------------------------------------------------------------------------
*/

if ($district !== '') {

    $sql .= "
        AND h.district = ?
    ";

    $params[] = $district;
}


/*
|--------------------------------------------------------------------------
| FUNCTION TYPE FILTER
|--------------------------------------------------------------------------
*/

if ($function_type > 0) {

    $sql .= "
        AND EXISTS (
            SELECT 1
            FROM hotel_function_types hft_filter
            WHERE hft_filter.hotel_id = h.id
              AND hft_filter.function_type_id = ?
        )
    ";

    $params[] = $function_type;
}


/*
|--------------------------------------------------------------------------
| GUEST CAPACITY FILTER
|--------------------------------------------------------------------------
|
| This assumes your hotels table uses:
| guest_capacity
|
| If your actual column name is different,
| change h.guest_capacity below.
|
|--------------------------------------------------------------------------
*/

if ($guests > 0) {

    $sql .= "
        AND h.guest_capacity >= ?
    ";

    $params[] = $guests;
}


/*
|--------------------------------------------------------------------------
| MAX PRICE FILTER
|--------------------------------------------------------------------------
*/

if ($max_price > 0) {

    $sql .= "
        AND h.starting_price <= ?
    ";

    $params[] = $max_price;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
|
| Premium hotels first.
| Newest hotels after that.
|
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY
        h.is_premium DESC,
        h.created_at DESC
";


$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| PAGINATION CALCULATION
|--------------------------------------------------------------------------
*/

$totalHotels = count($hotels);

$totalPages = max(
    1,
    (int) ceil($totalHotels / $perPage)
);


/*
|--------------------------------------------------------------------------
| VALID CURRENT PAGE
|--------------------------------------------------------------------------
*/

if ($currentPage > $totalPages) {

    $currentPage = $totalPages;
}


/*
|--------------------------------------------------------------------------
| GET ONLY 6 HOTELS FOR CURRENT PAGE
|--------------------------------------------------------------------------
*/

$offset = ($currentPage - 1) * $perPage;

$displayHotels = array_slice(
    $hotels,
    $offset,
    $perPage
);


/*
|--------------------------------------------------------------------------
| PAGINATION URL
|--------------------------------------------------------------------------
|
| Keeps search/filter values when changing pages.
|
|--------------------------------------------------------------------------
*/

function pageUrl($page)
{
    $params = $_GET;

    $params['page'] = $page;

    return '?' . http_build_query($params);
}


/*
|--------------------------------------------------------------------------
| PAGINATION PAGE NUMBERS
|--------------------------------------------------------------------------
|
| Example:
|
| 1 2 3 4 5 ... 100
|
| When current page is in the middle:
|
| 1 ... 48 49 50 51 52 ... 100
|
|--------------------------------------------------------------------------
*/

$pageNumbers = [];


/*
|--------------------------------------------------------------------------
| FIRST PAGE
|--------------------------------------------------------------------------
*/

$pageNumbers[] = 1;


/*
|--------------------------------------------------------------------------
| PAGES AROUND CURRENT PAGE
|--------------------------------------------------------------------------
*/

if ($totalPages > 1) {

    $startPage = max(
        2,
        $currentPage - 2
    );

    $endPage = min(
        $totalPages - 1,
        $currentPage + 2
    );


    /*
    | Add dots after page 1
    */

    if ($startPage > 2) {

        $pageNumbers[] = '...';
    }


    /*
    | Add middle pages
    */

    for (
        $i = $startPage;
        $i <= $endPage;
        $i++
    ) {

        $pageNumbers[] = $i;
    }


    /*
    | Add dots before last page
    */

    if ($endPage < $totalPages - 1) {

        $pageNumbers[] = '...';
    }


    /*
    | Last page
    */

    $pageNumbers[] = $totalPages;
}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/slider.php';

?>


<!-- ============================================================
     HERO SEARCH
============================================================ -->

<section class="hero">

    <div class="container">

        <form
            class="search-panel"
            id="searchForm"
            method="GET"
            action="/allhotels/index.php"
        >

            <!-- SEARCH HOTEL -->

            <div class="search-field">

                <label for="q">
                    Search Hotel
                </label>

                <input
                    type="text"
                    id="q"
                    name="q"
                    value="<?= h($q) ?>"
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
                            <?= $district === $d['district'] ? 'selected' : '' ?>
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
                            <?= $function_type === (int) $ft['id'] ? 'selected' : '' ?>
                        >
                            <?= h($ft['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- GUEST CAPACITY -->

            <div class="search-field">

                <label for="guests">
                    Guest Capacity
                </label>

                <input
                    type="number"
                    id="guests"
                    name="guests"
                    value="<?= $guests > 0 ? $guests : '' ?>"
                    min="1"
                    placeholder="e.g. 150"
                >

            </div>


            <!-- MAX PRICE -->

            <div class="search-field">

                <label for="max_price">
                    Max Price (Rs.)
                </label>

                <input
                    type="number"
                    id="max_price"
                    name="max_price"
                    value="<?= $max_price > 0 ? $max_price : '' ?>"
                    min="0"
                    step="500"
                    placeholder="e.g. 50000"
                >

            </div>


            <!-- SEARCH BUTTON -->

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
                <?= $totalHotels ?> hotel(s) found
            </span>

        </div>


        <!-- ========================================================
             HOTEL GRID
        ========================================================= -->

        <div
            class="hotel-grid"
            id="hotelGrid"
        >

            <?php if (empty($displayHotels)): ?>


                <div class="empty-state">

                    No hotels found matching your search.

                    <br>

                    Try changing your search or filters.

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

                                $imagePath = trim(
                                    $hotel['main_image']
                                );


                                /*
                                | Database already contains /allhotels/
                                */

                                if (
                                    strpos(
                                        $imagePath,
                                        '/allhotels/'
                                    ) === 0
                                ) {

                                    $imageUrl = $imagePath;

                                }


                                /*
                                | Normal database value:
                                | api/images/hotel_xxx.jpg
                                */

                                elseif (
                                    strpos(
                                        $imagePath,
                                        'api/images/'
                                    ) === 0
                                ) {

                                    $imageUrl =
                                        '/allhotels/' .
                                        ltrim(
                                            $imagePath,
                                            '/'
                                        );

                                }


                                /*
                                | Other path
                                */

                                else {

                                    $imageUrl =
                                        '/allhotels/' .
                                        ltrim(
                                            $imagePath,
                                            '/'
                                        );
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
                                    array_slice(
                                        $tags,
                                        0,
                                        3
                                    ) as $tag
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


        <!-- ========================================================
             PAGINATION
        ========================================================= -->

        <?php if ($totalPages > 1): ?>

            <nav
                class="pagination"
                aria-label="Hotel pagination"
            >


                <!-- PREVIOUS -->

                <?php if ($currentPage > 1): ?>

                    <a
                        href="<?= h(pageUrl($currentPage - 1)) ?>"
                        class="page-btn prev-next"
                    >
                        ‹ Prev
                    </a>

                <?php else: ?>

                    <span class="page-btn disabled">
                        ‹ Prev
                    </span>

                <?php endif; ?>


                <!-- PAGE NUMBERS -->

                <?php foreach ($pageNumbers as $page): ?>


                    <?php if ($page === '...'): ?>

                        <span class="page-dots">
                            …
                        </span>


                    <?php else: ?>

                        <a
                            href="<?= h(pageUrl($page)) ?>"
                            class="page-btn <?= (int) $page === $currentPage ? 'active' : '' ?>"
                        >
                            <?= $page ?>
                        </a>

                    <?php endif; ?>


                <?php endforeach; ?>


                <!-- NEXT -->

                <?php if ($currentPage < $totalPages): ?>

                    <a
                        href="<?= h(pageUrl($currentPage + 1)) ?>"
                        class="page-btn prev-next"
                    >
                        Next ›
                    </a>

                <?php else: ?>

                    <span class="page-btn disabled">
                        Next ›
                    </span>

                <?php endif; ?>


            </nav>

        <?php endif; ?>


    </div>

</section>


<?php

/*
|--------------------------------------------------------------------------
| Do NOT load old client-side search.js
|--------------------------------------------------------------------------
|
| Search and filtering are now handled by PHP + MySQL.
|
|--------------------------------------------------------------------------
*/

$extra_scripts = [];

require_once __DIR__ . '/includes/footer.php';

?>
```
