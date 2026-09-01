</main>

<!--  ============== FOOTER ============= -->

<footer class="site-footer">

    <div class="footer-inner">

        <!-- BRAND -->
        <div class="footer-brand">

            <span class="logo">
                <img src="/allhotels/api/images/logo-white.png" alt="AllHotels.lk Logo">
            </span>

            <p>
                Connecting Sri Lanka's finest venues with the people
                celebrating life's biggest moments.
            </p>
            

        </div>


        <!-- EXPLORE -->
        <div class="footer-links">

            <h4>Explore</h4>

            <a href="/allhotels/index.php">
                Home
            </a>

            <a href="/allhotels/about.php">
                About Us
            </a>

            <a href="/allhotels/contact.php">
                Contact Us
            </a>

        </div>


        <!-- PARTNERS 
        <div class="footer-links">

            <h4>Partners</h4>

            <a href="/allhotels/auth/register.php?type=owner">
                List Your Hotel
            </a>

            <a href="/allhotels/auth/login.php">
                Owner Login
            </a>

        </div>-->

        <!-- Contact Us -->
        <div class="footer-links">

            <h4>Contact Us</h4>
            <a href="tel:+94740740890"> 0740 740 890</a>
            <a href="mailto:sales@allhotels.lk"> sales@allhotels.lk</a>
        </div>

    </div>


    <!-- FOOTER BOTTOM -->

    <div class="footer-bottom">

        &copy; <?= date('Y') ?> AllHotels.lk — All rights reserved - Created by <span><a href="https://www.linkedin.com/in/nadeesha-kalhara-4187863a5?utm_source=share_via&utm_content=profile&utm_medium=member_ios" target="_blank">ViaDesign.</span>

    </div>

</footer>


<!-- MAIN JAVASCRIPT-->

<script src="/allhotels/js/main.js"></script>


<?php if (!empty($extra_scripts)): ?>

    <?php foreach ($extra_scripts as $s): ?>

        <script src="<?= h($s) ?>"></script>

    <?php endforeach; ?>

<?php endif; ?>


<!--  HOTEL PHOTO SLIDER-->

<script>

document.addEventListener('DOMContentLoaded', function () {

    const slides = document.querySelectorAll('.slider-image');
    const dots = document.querySelectorAll('.slider-dot');

    /*  Stop if there are no slider images */

    if (slides.length === 0) {
        return;
    }


    let currentSlide = 0;
    let sliderTimer;


    /* Show selected slide */

    function showSlide(index) {

        /* Remove active from all images */

        slides.forEach(function (slide) {

            slide.classList.remove('active');

        });


        /* Remove active from all dots */

        dots.forEach(function (dot) {

            dot.classList.remove('active');

        });


        /* Make selected image active */

        slides[index].classList.add('active');


        /* Make selected dot active */

        if (dots[index]) {

            dots[index].classList.add('active');

        }


        currentSlide = index;

    }


    /* Next slide */

    function nextSlide() {

        let next = currentSlide + 1;

        if (next >= slides.length) {

            next = 0;

        }

        showSlide(next);

    }


    /* Start automatic slider*/

    function startSlider() {

        clearInterval(sliderTimer);

        sliderTimer = setInterval(function () {

            nextSlide();

        }, 6500);

    }


    /* Dot click */

    dots.forEach(function (dot, index) {

        dot.addEventListener('click', function () {

            showSlide(index);

            startSlider();

        });

    });


    /* Initialize */

    showSlide(0);

    startSlider();

});


</script>

<!-- HOTEL GALLERY -->

<script src="/allhotels/js/gallery.js"></script>


</body>
</html>
