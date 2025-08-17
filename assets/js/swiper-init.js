jQuery(document).ready(function ($) {
    // Lấy thông tin từ data attribute
    const productSlider = $('.product-slider-container3');
    const visibleSlides = parseInt(productSlider.data('visible-slides'), 10) || 5;
    const gap = parseInt(productSlider.data('gap'), 10) || 15;
    const autoSlide = productSlider.data('auto-slide');
    const autoSlideInterval = parseInt(productSlider.data('auto-slide-interval'), 10) || 4000;
    // Khởi tạo Swiper
    const swiper = new Swiper('.product-slider-container3', {
        lazy: true, // Bật lazy load
        slidesPerView: visibleSlides,
        spaceBetween: gap,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        autoplay: autoSlide
            ? {
                delay: autoSlideInterval,
                disableOnInteraction: false,
            }
            : false,
        grabCursor: true,
        breakpoints: {
            320: {
                slidesPerView: 1,
                spaceBetween: 10,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: gap,
            },
            1024: {
                slidesPerView: visibleSlides,
                spaceBetween: gap,
            },
        },
    });
    $('.swiper-slide3').addClass('swiper-slide-visible').show();

    // Xử lý Filter
    $('#new-product-filter3 .btn-filter').on('click', function () {
        const filterValue = $(this).data('filter'); // Lấy giá trị filter từ nút

        // Đặt trạng thái active cho nút filter
        $('.btn-filter').removeClass('active');
        $(this).addClass('active');

        // Lọc các sản phẩm trong Swiper
        $('.swiper-slide').each(function () {
            const category = $(this).data('category'); // Lấy danh mục của sản phẩm
            if (filterValue === 'all' || category === filterValue) {
                $(this).show().addClass('swiper-slide-visible');
            } else {
                $(this).hide().removeClass('swiper-slide-visible');
            }
        });

        // Cập nhật lại Swiper sau khi thay đổi
        swiper.update();
        // Đảm bảo autoplay hoạt động
        swiper.autoplay.stop(); // Dừng autoplay tạm thời
        swiper.autoplay.start(); // Khởi động lại autoplay
    });
});
