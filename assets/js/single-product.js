jQuery(document).ready(function ($) {
    const productItem = $('.single-product');
    if (productItem.length) {
        const product = {
            id: productItem.data('id'), // ID sản phẩm
            sku: productItem.data('sku'), // SKU sản phẩm
            name: productItem.data('name').trim(), // Tên sản phẩm
            price: productItem.data('price-html'), // Giá sản phẩm
            originalPrice: productItem.data('original-price'), // Giá gốc
            saleOff: productItem.data('saleoff'), // Giảm giá (%)
            image: productItem.data('image'), // URL hình ảnh sản phẩm
            category: productItem.data('category'), // Danh mục sản phẩm
            url: productItem.data('url'),
        };
        saveRecentlyViewedProduct(product);
    }


    function saveRecentlyViewedProduct(product) {
        let viewedProducts = JSON.parse(localStorage.getItem('viewedProducts')) || [];
        viewedProducts = viewedProducts.filter(p => p.id !== product.id);
        viewedProducts.unshift(product);
        if (viewedProducts.length > 10) {
            viewedProducts = viewedProducts.slice(0, 10);
        }
        localStorage.setItem('viewedProducts', JSON.stringify(viewedProducts));
    }

    $(".addToCart-box .qty-plus-old").on("click", function () {
        const qtyInput = $(this).siblings(".item-qty");
        let qty = parseInt(qtyInput.val());
        qtyInput.val(qty + 1); // Tăng số lượng
    });


    $(".addToCart-box #addToCart, .addToCart-box #quickBuy, .addToCart-box #muaTraGop")
        .on("click", function (e) {
            e.preventDefault();

            const $btn = $(this);
            const btnID = $btn.attr('id');
            const isQuickBuy = btnID === 'quickBuy';
            const isTraGop  = btnID === 'muaTraGop';
            const checkoutPage = $btn.data('checkout');


            if (productItem.data('status') !== 'in-stock') {
                siteNotify(translations.cart_out_stock);
                return;
            }

            let quantity = parseInt($('.addToCart-box #quantity').val(), 10);
            if (!Number.isFinite(quantity) || quantity < 1) quantity = 1;

            const product = {
                id: productItem.data('id'),
                sku: productItem.data('sku'),
                name: (productItem.data('name') || '').trim(),
                price: productItem.data('price'),
                originalPrice: productItem.data('original-price') || 0,
                saleOff: productItem.data('saleoff') || 0,
                image: productItem.data('image') || '',
                category: productItem.data('category') || '',
                url: productItem.data('url') || '',
                attributes: productItem.data('attributes') || null,
                variation: productItem.data('variation') || null,
                quantity,
                selected: productItem.data('selected') || ''
            };

            if (!product.id || !product.price || !product.selected) {
                siteNotify(translations.cart_invalid_product);
                return;
            }

            // Thêm vào giỏ (nếu addToCart async, hãy redirect trong callback .then)
            addToCart(product);

            if ( (isQuickBuy || isTraGop)
                && typeof checkoutPage === 'string'
                && checkoutPage.trim() !== '' ) {
                window.location.assign(checkoutPage);
            } else {
                const el = document.getElementById('shoppingCartCanvas');
                if (el) {
                    const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(el);
                    offcanvas.show();
                }
            }
        });

    var isLightboxInitialized = false;
    var lightboxSwiper;
    let lightboxItems = $('.lightbox-swiper .swiper-slide').length;
    var swiper = new Swiper('.thumbnail-navigation.swiper-container', {
        slidesPerView: 3,
        spaceBetween: 10,
        navigation: {
            nextEl: '.thumbnail-navigation .swiper-button-next',
            prevEl: '.thumbnail-navigation .swiper-button-prev',
        },
    });

    // Click event for thumbnails
    $('.thumbnail-item').on('click', function() {
        var imageSrc = $(this).attr('data-image');
        $('#main-product-image').attr('src', imageSrc);
        $('.zoomed-image').attr('src', imageSrc);
        $('.thumbnail-item').removeClass('active');
        $(this).addClass('active');
    });

    function initializeLightboxSwiper() {
        var lightboxThumbs = new Swiper(".lightbox-thumbs", {
            spaceBetween: 10,
            slidesPerView: 4,
            freeMode: true,
            watchSlidesProgress: true,
            navigation: {
                nextEl: '.lightbox-thumbs .swiper-button-next',
                prevEl: '.lightbox-thumbs .swiper-button-prev',
            },
        });


        if (!isLightboxInitialized) {
            lightboxSwiper = new Swiper('.lightbox-swiper', {
                loop: false,
                navigation: {
                    nextEl: '.lightbox-swiper .swiper-button-next',
                    prevEl: '.lightbox-swiper .swiper-button-prev',
                },
                thumbs: {
                    swiper: lightboxThumbs,
                },
            });
            isLightboxInitialized = true;
            lightboxSwiper.on("slideChange", function () {
                lightboxThumbs.slideTo(lightboxSwiper.activeIndex);
            });
        }
    }
    $('.lightbox-swiper .swiper-button-next, .lightbox-swiper .swiper-button-prev').on('click', function(e) {
        e.stopPropagation();
    });
    initializeLightboxSwiper();
    let $container = $(".main-image"),
        $lens = $(".zoom-lens"),
        $result = $(".zoom-result"),
        $zoomedImage = $(".zoomed-image"),
        imageWidth = $("#main-product-image").width(),
        imageHeight = $("#main-product-image").height(),
        zoomWidth = $result.width(),
        zoomHeight = $result.height(),
        scale = $zoomedImage.width() / imageWidth;

    $container.on("mousemove", function (e) {
        let rect = this.getBoundingClientRect(),
            x = e.clientX - rect.left,
            y = e.clientY - rect.top;

        let lensX = x - $lens.width() / 2;
        let lensY = y - $lens.height() / 2;

        lensX = Math.max(0, Math.min(imageWidth - $lens.width(), lensX));
        lensY = Math.max(0, Math.min(imageHeight - $lens.height(), lensY));

        $lens.css({ left: lensX, top: lensY, display: "block" });
        $result.css({ display: "block" });

        $zoomedImage.css({
            transform: `translate(-${lensX * scale}px, -${lensY * scale}px)`
        });
    });

    $container.on("mouseleave", function () {
        $lens.hide();
        $result.hide();
    });
    // Click event for main image and thumbnails to show lightbox
    $('.main-image').on('click', function() {
        var imageSrc = $(this).find('#main-product-image').attr('src') || $(this).attr('data-image');
        var imageIndex;
        if ($(this).hasClass('thumbnail-item')) {
            imageIndex = $(this).closest('.swiper-slide').index();
        } else {
            var currentThumbnail = $('.thumbnail-item.active');
            imageIndex = currentThumbnail.closest('.swiper-slide').index();
        }
        lightboxSwiper.slideTo(imageIndex);
        $('#lightbox').modal('show');
    });



    function getYoutubeEmbedUrl(url) {
        // Nhận diện các kiểu link YouTube: /watch?v=..., /shorts/..., youtu.be/...
        let id = null;

        // Dạng watch
        let match = url.match(/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        if (match && match[1]) {
            id = match[1];
        }
        // Dạng shorts
        match = url.match(/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/);
        if (match && match[1]) {
            id = match[1];
        }
        // Dạng có thêm tham số sau id
        if (!id) {
            match = url.match(/youtube\.com\/.*[?&]v=([a-zA-Z0-9_-]{11})/);
            if (match && match[1]) {
                id = match[1];
            }
        }
        if (id) {
            return `https://www.youtube.com/embed/${id}`;
        }
        return url; // Không phải YouTube, trả về như cũ
    }
    function isYoutubeShorts(url) {
        return /youtube\.com\/shorts\//.test(url);
    }
    // Click event for video button
    $('#view-video-btn').on('click', function(e) {
        e.preventDefault();
        var videoSrc = $(this).attr('data-video');
        if (!videoSrc) return;
        let is_tiktok = false;
        if (videoSrc.includes('tiktok.com')){
            is_tiktok = true;
        }
        console.log(videoSrc);

        if (is_tiktok){
            let videoID = getTikTokVideoId(videoSrc);
            var tiktok_embed = `<blockquote class="tiktok-embed" cite="${videoSrc}" data-video-id="${videoID}" style="max-width: 605px;min-width: 325px;" > <section>  </section> </blockquote> <script async src="https://www.tiktok.com/embed.js"></script>`;
            $('#video360-lightbox .modal-body').html(tiktok_embed);
        }else{
            var embedSrc = getYoutubeEmbedUrl(videoSrc);
            var ratio_class = 'ratio-16x9';
            if (isYoutubeShorts(videoSrc)) {
                $('#video360-lightbox').addClass('video-modal');
                ratio_class = 'ratio-9x16';
            } else {
                $('#video360-lightbox').removeClass('video-modal');

            }
            $('#video360-lightbox .modal-body').html(`
                <div class="${ratio_class}">
                    <iframe src="${embedSrc}" frameborder="0" allowfullscreen class="lightbox-video" 
                    style=""></iframe>
                </div>
            `);
        }
        $('#video360-lightbox').modal('show');
    });


    // Click event for 360 button
    $('#360-button').on('click', function(e) {
        e.preventDefault();
        if ($('#360-button').data('image360').length) {
            let html = `<div id="product-360" >
                        <img id="product-image-360" src="" class="img-fluid lightbox-360"alt="Product 360 View">
                    </div>`;
            // Cập nhật ảnh 360
            // $('.lightbox-360').attr('src', image360Src);
            $('#video360-lightbox .modal-body').html(html);
            updateImage();
            $('#video360-lightbox').modal('show');
        }
    });

    // Close lightbox when clicking outside the image
    $('#lightbox').on('click', function(e) {
        if (e.target !== $('#lightbox-image')[0]) {
            // $('#lightbox').modal('hide');
        }
    });
    // Reset video when lightbox is closed
    $('#lightbox').on('hidden.bs.modal', function() {
        $('.lightbox-video').attr('src', ''); // Dừng video khi đóng lightbox
    });
    // 360 view
    var $containerID = '#product-360';
    var $imageID = '#product-image-360';
    var $image360Src = $('#360-button').data('image360');
    var currentImageIndex = 0;
    var startX = 0;
    if ($image360Src.length>0) {
        // Hàm cập nhật ảnh
        function updateImage() {
            $(document).find($imageID).attr('src', $image360Src[currentImageIndex]);
        }

        // Tải trước ảnh (tùy chọn)
        for (var i = 0; i < $image360Src.length; i++) {
            (new Image()).src = $image360Src[i];
        }

        $(document).on('mousemove', $containerID, function (e) {
            var deltaX = e.pageX - startX;
            var sensitivity = 35; // Điều chỉnh độ nhạy
            if (Math.abs(deltaX) > sensitivity) {
                currentImageIndex -= Math.round(deltaX / sensitivity);
                if (currentImageIndex < 0) {
                    currentImageIndex = $image360Src.length - 1;
                } else if (currentImageIndex >= $image360Src.length) {
                    currentImageIndex = 0;
                }
                updateImage();
                startX = e.pageX;
            }
        });
    }

    function getTikTokVideoId(url) {
        const regex = /\/video\/(\d+)/;
        const match = url.match(regex);
        if (match && match[1]) {
            return match[1];
        }
        return null;
    }



    const $variationsContainer = $('.product-variations');
    // const productItem = $('.single-product-item');

// Tạo key chuẩn hóa từ object thuộc tính
    function generateNormalizedKey(attributes) {
        const sortedKeys = Object.keys(attributes).sort();
        return sortedKeys.map(key => `${key}:${attributes[key]}`).join(' | ');
    }

// Tìm biến thể khớp với thuộc tính đã chọn
    function findMatchingVariant(selectedAttributes) {
        for (const [key, variant] of Object.entries(window.productVariants)) {
            const variantAttributes = variant.attributes || {};

            const selectedKeys = Object.keys(selectedAttributes).sort();
            const variantKeys = Object.keys(variantAttributes).sort();

            if (selectedKeys.length !== variantKeys.length) continue;

            let match = true;
            for (let i = 0; i < selectedKeys.length; i++) {
                const attr = selectedKeys[i];
                const selectedVal = String(selectedAttributes[attr] || '').toLowerCase().trim();
                const variantVal = String(variantAttributes[attr] || '').toLowerCase().trim();

                if (attr !== variantKeys[i] || selectedVal !== variantVal) {
                    match = false;
                    break;
                }
            }

            if (match) return variant;
        }

        return null;
    }


// Cập nhật thông tin sản phẩm khi chọn đúng biến thể
    function updateProductWithVariant(variant) {
        const { price_formatted, price, original_price_formatted, original_price, image_url, sku, stock, price_html,attributes } = variant;

        // const totalStock = Array.isArray(stock)
        //     ? stock.reduce((sum, item) => sum + (parseInt(item.stock, 10) || 0), 0)
        //     : 0;

        $('.price').html(price_html);

        if (image_url) {
            $('#main-product-image').attr('src', image_url);
            $('.zoomed-image').attr('src', image_url);
        }

        // $('.product-meta-text.sku span').text(sku);
        // $('.product-stock').text(totalStock > 0 ? `Tồn kho: ${totalStock}` : 'Không có sẵn');
        // console.log(stock > 0 ? `Tồn kho: ${stock}` : 'Không có sẵn');
        if(stock){
            productItem.data({id:sku, price, attributes:attributes, image:image_url, variation:{sku:sku,id:sku} ,selected:sku});
        }

    }

// Cập nhật trạng thái "disabled" của các lựa chọn chưa chọn
    function updateOptionsAvailability(selectedAttributes) {
        // Đếm tổng số thuộc tính có trong variant (lấy từ 1 variant mẫu)
        const sampleVariant = Object.values(window.productVariants)[0];
        const totalAttributes = Object.keys(sampleVariant.attributes).length;

        $variationsContainer.find('.variation-group').each(function () {
            const $group = $(this);
            const attr = $group.find('.variation-options').data('attribute');

            // Nếu thuộc tính này đã chọn rồi thì bỏ qua
            if (selectedAttributes[attr]) return;

            $group.find('.variation-option').each(function () {
                const $option = $(this);
                const val = $option.data('name');
                $option.removeClass('disabled');

                // Ghép thuộc tính đã chọn + option này thành 1 object tempSelected
                const tempSelected = { ...selectedAttributes, [attr]: val };

                // Kiểm tra: Có variant nào trong window.productVariants mà match tempSelected và stock > 0 không?
                let matchAndStock = false;
                for (const variant of Object.values(window.productVariants)) {
                    // Kiểm tra tất cả key trong tempSelected phải đúng value trong variant.attributes
                    let match = true;
                    for (const key in tempSelected) {
                        if (
                            !variant.attributes.hasOwnProperty(key) ||
                            String(variant.attributes[key]).toLowerCase().trim() !== String(tempSelected[key]).toLowerCase().trim()
                        ) {
                            match = false;
                            break;
                        }
                    }
                    // Nếu match và stock > 0, thì vẫn enable
                    if (match && parseInt(variant.stock, 10) > 0) {
                        matchAndStock = true;
                        break;
                    }
                }

                if (!matchAndStock) {
                    $option.addClass('disabled').removeClass('active');
                }
            });
        });
    }




    function collectSelectedAttributes() {
        const selectedAttributes = {};

        $('.variation-group', $variationsContainer).each(function () {
            const attr = $(this).find('.variation-options').data('attribute');
            const $selected = $(this).find('.variation-option.active');

            if ($selected.length > 0) {
                selectedAttributes[attr] = $selected.data('name');
            }
        });

        return selectedAttributes;
    }


// Gán sự kiện click
    $variationsContainer.on('click', '.variation-option', function () {
        const $this = $(this);
        if ($this.hasClass('active')) {
            $this.removeClass('active');
            const selectedAttributes = collectSelectedAttributes();
            // console.log(selectedAttributes);
            updateOptionsAvailability(selectedAttributes);
            productItem.data('price',0);
            return;
        }
        const $group = $this.closest('.variation-group');
        const variant_sku = $this.data('variant-sku') ?? null;
        if (variant_sku) {
            const branchList = window.BranchStock[variant_sku];
            if (branchList) {
                // Tạo array các chi nhánh còn hàng
                const branchesWithStock = Object.entries(branchList)
                    .filter(([branchName, info]) => info.stock > 0)
                    .map(([branchName, info]) => ({
                        name: branchName,
                        stock: info.stock,
                        url: info.url
                    }));
                let html = "";
                if (branchesWithStock.length) {
                    branchesWithStock.forEach(branch => {
                        let url = '#';
                        let branch_info = window.branches_data[branch.name]??null;
                        if (branch_info && branch_info.url){
                            url = branch_info.url;
                            name = branch_info.name;
                        }
                        html += `<li>
                            <a href="${url}" target="_blank" role="button">${name}</a> 
                            <span class="store-stock">${branch.stock} ${translations.products_left}</span>
                        </li>`;
                    });
                }
                // Gắn vào một element, ví dụ:
                $('.product-store').html(html);
            } else {
                $('.product-store').html("<li>"+translations.branch_not_found+"</li>");
            }
        }

        if ($this.hasClass('disabled')) return;

        $group.find('.variation-option').removeClass('active');

        $this.addClass('active');
        const image_url = $this.find('img').attr('src');
        if (image_url) {
            $('#main-product-image').attr('src', image_url);
            $('.zoomed-image').attr('src', image_url);
        }

        const selectedAttributes = collectSelectedAttributes();
        const allSelected = Object.keys(selectedAttributes).length === $('.variation-group', $variationsContainer).length;

        // Cập nhật trạng thái enable/disable cho các lựa chọn chưa được chọn
        updateOptionsAvailability(selectedAttributes);

        if (!allSelected || Object.keys(selectedAttributes).length < $variationsContainer.find('.variation-group').length) {
            productItem.data('price',0);
            // console.log('price',productItem.data('price'));
            // console.log('Selected',allSelected,' length:',$variationsContainer.find('.variation-group').length);
            return
        };

        const variant = findMatchingVariant(selectedAttributes);
        // console.log('Attrs:',selectedAttributes);
        if (!variant) {
            $('.product-stock').text(translation.not_available);
            return;
        }

        updateProductWithVariant(variant);
    });
})