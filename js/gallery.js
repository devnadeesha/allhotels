document.addEventListener("DOMContentLoaded", function () {

    const mainImage =
        document.getElementById("mainHotelImage");

    const lightbox =
        document.getElementById("galleryLightbox");

    const lightboxImage =
        document.getElementById("lightboxImage");

    const thumbnails =
        document.querySelectorAll(".gallery-thumbnail");

    if (!mainImage) {
        return;
    }


    let currentIndex = 0;


    /*
    |--------------------------------------------------------------------------
    | Find Main Image Index
    |--------------------------------------------------------------------------
    */

    if (thumbnails.length > 0) {

        thumbnails.forEach(function (thumbnail, index) {

            if (thumbnail.dataset.image === mainImage.src) {
                currentIndex = index;
            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | Select Image
    |--------------------------------------------------------------------------
    */

    function selectImage(index) {

        if (
            thumbnails.length === 0 ||
            index < 0 ||
            index >= thumbnails.length
        ) {
            return;
        }


        currentIndex = index;


        const thumbnail =
            thumbnails[currentIndex];


        const imagePath =
            thumbnail.dataset.image;


        if (!imagePath) {
            return;
        }


        // Change main image

        mainImage.src = imagePath;


        // Update active thumbnail

        thumbnails.forEach(function (item) {

            item.classList.remove("active");

        });


        thumbnail.classList.add("active");


        // If lightbox is open, update it too

        if (
            lightbox &&
            lightbox.classList.contains("show") &&
            lightboxImage
        ) {

            lightboxImage.src = imagePath;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Thumbnail Click
    |--------------------------------------------------------------------------
    */

    thumbnails.forEach(function (thumbnail, index) {

        thumbnail.addEventListener("click", function () {

            selectImage(index);

        });

    });


    /*
    |--------------------------------------------------------------------------
    | Previous Image
    |--------------------------------------------------------------------------
    */

    const galleryPrev =
        document.querySelector(".gallery-prev");


    if (galleryPrev) {

        galleryPrev.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                let newIndex =
                    currentIndex - 1;


                if (newIndex < 0) {

                    newIndex =
                        thumbnails.length - 1;

                }


                selectImage(newIndex);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Next Image
    |--------------------------------------------------------------------------
    */

    const galleryNext =
        document.querySelector(".gallery-next");


    if (galleryNext) {

        galleryNext.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                let newIndex =
                    currentIndex + 1;


                if (
                    newIndex >=
                    thumbnails.length
                ) {

                    newIndex = 0;

                }


                selectImage(newIndex);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Open Lightbox
    |--------------------------------------------------------------------------
    */

    mainImage.addEventListener(
        "click",
        function () {

            if (
                !lightbox ||
                !lightboxImage
            ) {
                return;
            }


            lightboxImage.src =
                mainImage.src;


            lightbox.classList.add("show");


            document.body.style.overflow =
                "hidden";

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Close Lightbox
    |--------------------------------------------------------------------------
    */

    const closeButton =
        document.querySelector(".lightbox-close");


    if (closeButton) {

        closeButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();

                closeLightbox();

            }
        );

    }


    function closeLightbox() {

        if (!lightbox) {
            return;
        }


        lightbox.classList.remove("show");


        document.body.style.overflow =
            "";

    }


    /*
    |--------------------------------------------------------------------------
    | Click Background To Close
    |--------------------------------------------------------------------------
    */

    if (lightbox) {

        lightbox.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === lightbox
                ) {

                    closeLightbox();

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Lightbox Previous
    |--------------------------------------------------------------------------
    */

    const lightboxPrev =
        document.querySelector(
            ".lightbox-prev"
        );


    if (lightboxPrev) {

        lightboxPrev.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                let newIndex =
                    currentIndex - 1;


                if (newIndex < 0) {

                    newIndex =
                        thumbnails.length - 1;

                }


                selectImage(newIndex);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Lightbox Next
    |--------------------------------------------------------------------------
    */

    const lightboxNext =
        document.querySelector(
            ".lightbox-next"
        );


    if (lightboxNext) {

        lightboxNext.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                let newIndex =
                    currentIndex + 1;


                if (
                    newIndex >=
                    thumbnails.length
                ) {

                    newIndex = 0;

                }


                selectImage(newIndex);

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Keyboard Controls
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                !lightbox ||
                !lightbox.classList.contains("show")
            ) {
                return;
            }


            // ESC

            if (event.key === "Escape") {

                closeLightbox();

            }


            // Previous

            if (
                event.key === "ArrowLeft" &&
                thumbnails.length > 1
            ) {

                let newIndex =
                    currentIndex - 1;


                if (newIndex < 0) {

                    newIndex =
                        thumbnails.length - 1;

                }


                selectImage(newIndex);

            }


            // Next

            if (
                event.key === "ArrowRight" &&
                thumbnails.length > 1
            ) {

                let newIndex =
                    currentIndex + 1;


                if (
                    newIndex >=
                    thumbnails.length
                ) {

                    newIndex = 0;

                }


                selectImage(newIndex);

            }

        }
    );

});