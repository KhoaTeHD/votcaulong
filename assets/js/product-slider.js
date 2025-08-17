jQuery(document).ready(function ($) {

    // lưu tạm
    function initSlider() {
        $('.product-slider-container').createProductSlider();
    }

// Hiệu ứng chuyển đổi sản phẩm
    function applyFilterEffect(sliderTrack, callback) {
        // Ẩn toàn bộ sản phẩm với hiệu ứng
        sliderTrack.find('.slide').each(function () {
            if (!$(this).hasClass('hidden')) {
                $(this).css({
                    opacity: '0',
                    transform: 'scale(0.9)',
                });
            }
        });

        // Chờ hiệu ứng hoàn tất rồi thực hiện callback
        setTimeout(() => {
            callback(); // Thực hiện callback (lọc và khởi tạo lại slider)
        }, 300); // Thời gian trùng với transition trong CSS
    }

// $('#new-product-filter').createFilter();
    $('#new-product-filter').on('click', '.btn-filter', function () {
        const filterValue = $(this).data('filter'); // Lấy giá trị của nút filter

        // Thay đổi trạng thái active của nút
        $('#new-product-filter').find('.btn-filter').removeClass('active');
        $(this).addClass('active');

        // Lọc sản phẩm
        const sliderTrack = $('#new-product-filter').find('.slider-track');
        // Áp dụng hiệu ứng trước khi lọc
        applyFilterEffect(sliderTrack, () => {
            // Lọc sản phẩm
            sliderTrack.find('.slide').each(function () {
                const categories = $(this).data('category');

                if (filterValue === 'all' || categories.includes(filterValue)) {
                    $(this)
                        .removeClass('hidden')
                        .css({
                            opacity: '1',
                            transform: 'scale(1)',
                            display: 'block'
                        }); // Đảm bảo sản phẩm hiển thị
                } else {
                    $(this)
                        .addClass('hidden')
                        .css('display', 'none'); // Ẩn hoàn toàn khỏi bố cục
                }
            });

            // Reset slider transform và tái khởi tạo slider
            sliderTrack.css('transform', 'translateX(0)');
            $('.product-slider-container').createProductSlider();
        });
    });
    initSlider();
})