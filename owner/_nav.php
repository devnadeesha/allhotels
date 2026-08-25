<?php

$current = basename($_SERVER['SCRIPT_NAME']);

?>

<div class="dash-nav">

    <!-- OWNER DASHBOARD -->
    <div class="who">
        Owner Dashboard
    </div>


    <!-- DASHBOARD -->
    <a
        href="/allhotels/owner/dashboard.php"
        class="<?= $current === 'dashboard.php' ? 'active' : '' ?>"
    >
        My Profile
    </a>


    <!-- MY HOTELS -->
    <a
        href="/allhotels/owner/my-hotels.php"
        class="<?= $current === 'my-hotels.php' ? 'active' : '' ?>"
    >
        My Hotels
    </a>


    <!-- ADD HOTEL -->
    <a
        href="/allhotels/owner/add-hotel.php"
        class="<?= $current === 'add-hotel.php' ? 'active' : '' ?>"
    >
        Add Hotel
    </a>


    <!-- HOTEL GALLERY -->
    <a
        href="/allhotels/owner/gallery.php"
        class="<?= $current === 'gallery.php' ? 'active' : '' ?>"
    >
        Hotel Gallery
        <small>(Premium)</small>
    </a>


    <!-- BOOKINGS -->
    <a
        href="/allhotels/owner/bookings.php"
        class="<?= $current === 'bookings.php' ? 'active' : '' ?>"
    >
        Bookings
        <small>(Premium)</small>
    </a>


    <!-- CUSTOMER REVIEWS -->
    <a
        href="/allhotels/owner/reviews.php"
        class="<?= $current === 'reviews.php' ? 'active' : '' ?>"
    >
        Customer Reviews
    </a>


    <!-- NOTIFICATIONS -->
    <a
        href="/allhotels/owner/notifications.php"
        class="<?= $current === 'notifications.php' ? 'active' : '' ?>"
    >
        Notifications Log
    </a>
    


    <!-- ACCOUNT SETTINGS -->
    <a
        href="/allhotels/owner/account-settings.php"
        class="<?= $current === 'account-settings.php' ? 'active' : '' ?>"
    >
        Account Settings
    </a>


    <!-- LOGOUT -->
    <a
        href="/allhotels/auth/logout.php"
        class="logout"
    >
        Logout
    </a>

</div>