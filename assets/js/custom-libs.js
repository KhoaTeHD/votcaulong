(function ($) {
    $.fn.createFilter = function (options) {
        // Cấu hình mặc định
        const settings = $.extend({
            buttonSelector: '.btn-filter', // Nút lọc
            itemSelector: '.product-item', // Sản phẩm cần lọc
            activeClass: 'active', // Class cho nút đang được chọn
            hiddenClass: 'hidden', // Class ẩn sản phẩm
            allFilterValue: 'all', // Giá trị để hiển thị tất cả sản phẩm
        }, options);

        // Xử lý từng container được gọi hàm
        return this.each(function () {
            const container = $(this); // Container hiện tại
            const buttons = container.find(settings.buttonSelector); // Nút lọc trong container
            const items = container.find(settings.itemSelector); // Sản phẩm trong container

            // Gán sự kiện click vào các nút
            buttons.on('click', function (e) {
                e.preventDefault();
                const filterValue = $(this).data('filter'); // Lấy giá trị data-filter từ nút

                // Xóa class active khỏi tất cả nút và thêm vào nút hiện tại
                buttons.removeClass(settings.activeClass);
                $(this).addClass(settings.activeClass);
                // Lọc sản phẩm
                items.each(function () {
                    const productCategories = $(this).data('category'); // Lấy giá trị data-category
                    if (filterValue === settings.allFilterValue || productCategories.includes(filterValue)) {
                        $(this).removeClass(settings.hiddenClass); // Hiển thị sản phẩm phù hợp
                    } else {
                        $(this).addClass(settings.hiddenClass); // Ẩn sản phẩm không phù hợp
                    }
                });
            });
        });
    };
    $.fn.createProductSlider = function (options) {
        return this.each(function () {
            const sliderContainer = $(this);
            const sliderTrack = sliderContainer.find('.slider-track');
            let slides = sliderTrack.find('.slide:not(.hidden)'); // Chỉ lấy các sản phẩm hiển thị
            let totalSlides = slides.length;

            // Cấu hình mặc định
            const settings = $.extend({
                visibleSlides: parseInt(sliderContainer.data('visible-slides'), 10) || 5,
                gap: parseInt(sliderContainer.data('gap'), 10) || 15,
                autoSlide: true,
                autoSlideInterval: 3000,
            }, options);

            const { visibleSlides, gap, autoSlide, autoSlideInterval } = settings;

            // Tính toán kích thước sản phẩm
            const containerWidth = sliderContainer.width();
            const slideWidth = (containerWidth - (visibleSlides - 1) * gap) / visibleSlides;

            // Reset `currentSlide` về vị trí mặc định
            let currentSlide = visibleSlides;

            // Nếu tổng số slide <= số sản phẩm hiển thị thì không cần slider
            if (totalSlides <= visibleSlides) {
                // Ẩn nút điều khiển
                sliderContainer.find('.next, .prev').hide();

                // Tính toán lại chiều rộng và khoảng cách
                sliderTrack.css('gap', `${gap}px`);
                slides.css({
                    'width': `${slideWidth}px`,
                    'flex': `0 0 ${slideWidth}px`,
                });

                // Dừng auto slide nếu đã có
                if (sliderContainer.data('autoSlideTimer')) {
                    clearInterval(sliderContainer.data('autoSlideTimer'));
                    sliderContainer.removeData('autoSlideTimer');
                }

                // Đặt trạng thái auto slide là không hoạt động
                sliderContainer.data('autoSlideEnabled', false);

                return; // Không tạo slider
            }
            sliderContainer.find('.next, .prev').show();

            // Xóa các sản phẩm clone cũ trước khi tạo mới
            sliderTrack.find('.clone').remove();

            // Clone các sản phẩm đầu và cuối
            for (let i = 0; i < visibleSlides; i++) {
                sliderTrack.prepend(slides.eq(totalSlides - 1 - i).clone().addClass('clone'));
                sliderTrack.append(slides.eq(i).clone().addClass('clone'));
            }

            // Cập nhật lại danh sách slides (bao gồm cả clone)
            slides = sliderTrack.find('.slide');
            totalSlides = slides.length; // Cập nhật tổng số slides

            // Áp dụng kích thước cho tất cả sản phẩm (bao gồm cả clone)
            slides.css({
                'width': `${slideWidth}px`,
                'flex': `0 0 ${slideWidth}px`,
            });

            // Cập nhật kích thước và khoảng cách của slider
            sliderTrack.css({
                'gap': `${gap}px`,
                'transform': `translateX(-${currentSlide * (slideWidth + gap)}px)`,
            });

            // Hàm xử lý trượt
            function slideTo(position) {
                sliderContainer.find('.next, .prev').prop('disabled', true);
                sliderTrack.css('transition', 'transform 0.5s ease-in-out');
                sliderTrack.css('transform', `translateX(-${position * (slideWidth + gap)}px)`);
                currentSlide = position;

                sliderTrack.one('transitionend', function () {
                    sliderContainer.find('.next, .prev').prop('disabled', false);
                    if (currentSlide >= totalSlides - visibleSlides) {
                        sliderTrack.css('transition', 'none');
                        currentSlide = visibleSlides;
                        sliderTrack.css('transform', `translateX(-${currentSlide * (slideWidth + gap)}px)`);
                    } else if (currentSlide < visibleSlides) {
                        sliderTrack.css('transition', 'none');
                        currentSlide = totalSlides - (2 * visibleSlides);
                        sliderTrack.css('transform', `translateX(-${currentSlide * (slideWidth + gap)}px)`);
                    }
                });
            }

            // Nút "Next"
            sliderContainer.find('.next').off('click').on('click', function () {
                slideTo(currentSlide + 1);
            });

            // Nút "Prev"
            sliderContainer.find('.prev').off('click').on('click', function () {
                slideTo(currentSlide - 1);
            });

            // Xóa `autoSlideTimer` trước đó nếu có
            if (sliderContainer.data('autoSlideTimer')) {
                clearInterval(sliderContainer.data('autoSlideTimer'));
                sliderContainer.removeData('autoSlideTimer');
            }

            // Tự động trượt (auto slide)
            if (autoSlide && totalSlides > visibleSlides) {
                const autoSlideTimer = setInterval(() => {
                    slideTo(currentSlide + 1);
                }, autoSlideInterval);

                sliderContainer.data('autoSlideTimer', autoSlideTimer); // Lưu timer
                sliderContainer.data('autoSlideEnabled', true); // Đặt trạng thái auto slide hoạt động

                // Dừng và tiếp tục auto slide khi hover
                sliderContainer.hover(
                    function () {
                        // Hover in: Dừng auto slide
                        if (sliderContainer.data('autoSlideTimer')) {
                            clearInterval(sliderContainer.data('autoSlideTimer'));
                            sliderContainer.removeData('autoSlideTimer');
                        }
                    },
                    function () {
                        // Hover out: Khởi động lại auto slide (nếu đủ sản phẩm)
                        if (
                            sliderContainer.data('autoSlideEnabled') &&
                            totalSlides > visibleSlides &&
                            !sliderContainer.data('autoSlideTimer')
                        ) {
                            const resumedTimer = setInterval(() => {
                                slideTo(currentSlide + 1);
                            }, autoSlideInterval);
                            sliderContainer.data('autoSlideTimer', resumedTimer);
                        }
                    }
                );
            } else {
                sliderContainer.data('autoSlideEnabled', false); // Đặt trạng thái auto slide không hoạt động
            }
        });
    };

    $.fn.initSwiperWithFilter = function (options = {}) {
        return this.each(function () {
            const $wrapper = $(this); // `.slider-with-filter-container`
            const $sliderContainer = $wrapper.find('.swiper'); // Tự tìm slider container
            const $filterContainer = $wrapper.find('.swiper-filter'); // Tự tìm filter container
            const $pagingContainer = $wrapper.find('.swiper-pagination');
            const pagination = $sliderContainer.data('pagination');
            const paginationSpace = parseInt($sliderContainer.data('pagination-space'),10) || 20

            if (!$sliderContainer.length) {
                console.error('Không tìm thấy slider container!');
                return;
            }
            if ($pagingContainer.length && pagination ){
                $sliderContainer.css({'padding-bottom':paginationSpace+'px'});
            }

            // Tùy chọn mặc định và merge với options
            const settings = $.extend(
                {
                    visibleSlides: parseInt($sliderContainer.data('visible-slides'), 10) || 5,
                    gap: parseInt($sliderContainer.data('gap'), 10) || 15,
                    autoSlide: $sliderContainer.data('auto-slide') ,
                    pagination: $sliderContainer.data('pagination'),
                    autoSlideInterval: parseInt($sliderContainer.data('auto-slide-interval'), 10) || 4000,
                },
                options
            );

            // Khởi tạo Swiper
            const swiper = new Swiper($sliderContainer[0], {
                slidesPerView: settings.visibleSlides,
                spaceBetween: settings.gap,
                navigation: {
                    nextEl: $sliderContainer.find('.swiper-button-next')[0],
                    prevEl: $sliderContainer.find('.swiper-button-prev')[0],
                },
                pagination: settings.pagination
                    ? {
                        el: $sliderContainer.find('.swiper-pagination')[0],
                        clickable: true,
                    }
                    : false,
                autoplay: settings.autoSlide
                    ? {
                        delay: settings.autoSlideInterval,
                        disableOnInteraction: false,
                    }
                    : false,
                grabCursor: true,
                breakpoints: {
                    320: {
                        slidesPerView: 2,
                        spaceBetween: 5,
                    },
                    768: {
                        slidesPerView: 3,
                        spaceBetween: settings.gap,
                    },
                    1024: {
                        slidesPerView: settings.visibleSlides,
                        spaceBetween: settings.gap,
                    },
                },
            });

            // Hiển thị tất cả sản phẩm khi khởi tạo
            $sliderContainer.find('.swiper-slide').addClass('swiper-slide-visible').show();

            // Nếu có filter container thì khởi tạo filter
            if ($filterContainer.length) {
                $filterContainer.on('click', '.btn-filter', function (e) {
                    e.preventDefault();
                    const filterValue = $(this).data('filter'); // Lấy giá trị filter từ nút
                    
                    // Đặt trạng thái active cho nút filter
                    $filterContainer.find('.btn-filter').removeClass('active');
                    $(this).addClass('active');

                    // Lọc các sản phẩm trong Swiper
                    $sliderContainer.find('.swiper-slide').each(function () {
                        const $slide = $(this);
                        const category = $slide.data('category');
                        if (filterValue === 'all' || category === filterValue) {
                            $slide.show().addClass('swiper-slide-visible');
                        } else {
                            $slide.hide().removeClass('swiper-slide-visible');
                        }
                    });

                    // Cập nhật lại Swiper
                    swiper.update();
                    // console.log('update swiper',swiper);
                    
                    // Đảm bảo autoplay hoạt động
                    swiper.autoplay.stop();
                    swiper.autoplay.start();
                });
            }
        });
    };

    $.fn.filterableList = function () {
        return this.each(function () {
            const $listContainer = $(this);
            const $filterInput = $listContainer.find('.filter-input');
            const $listItems = $listContainer.find('.filter-item');
            const $emptyMessage = $('<div class="empty-message text-muted"></div>').hide(); // Thông báo khi không có mục nào phù hợp

            // Lấy các thuộc tính từ HTML
            const debounceTime = parseInt($filterInput.data('debounce'), 10) || 300;
            const caseSensitive = $filterInput.data('case-sensitive') === true || $filterInput.data('case-sensitive') === "true";
            const emptyMessageText = $filterInput.data('empty-message') || 'No matching items found.';

            // Thêm thông báo rỗng vào DOM
            $listContainer.append($emptyMessage.text(emptyMessageText));

            let debounceTimer;
            $filterInput.on('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const filterText = caseSensitive ? $(this).val() : $(this).val().toLowerCase();

                    let hasVisibleItems = false;
                    $listItems.each(function () {
                        const itemText = caseSensitive ? $(this).text() : $(this).text().toLowerCase();
                        const isMatch = itemText.includes(filterText);
                        $(this).toggle(isMatch);

                        if (isMatch) {
                            hasVisibleItems = true;
                        }
                    });

                    // Hiển thị hoặc ẩn thông báo rỗng
                    if (hasVisibleItems) {
                        $emptyMessage.hide();
                    } else {
                        $emptyMessage.show();
                    }
                }, debounceTime);
            });
        });
    };
})(jQuery);