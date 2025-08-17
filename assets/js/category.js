jQuery(document).ready(function ($) {
    const spinner = $('<div class="spinner-overlay d-flex justify-content-center" id="psort-spinner"><div class="spinner-grow" aria-hidden="true"></div></div>');
    const productList = $('#product-container');
    const $activeFiltersContainer = $('#activeFilters'); // Container cho các filter đang active

    // --- Helper Functions ---
    function getSelectedFilters() {
        let filters = {};
        // Lấy giá trị từ các checkbox filter
        $('.filter-checkbox:checked').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            if (!filters[name]) {
                filters[name] = [];
            }
            filters[name].push(value);
        });

        // Lấy giá trị từ radio button sắp xếp
        $('input[name="product-sort"]:checked').each(function() {
            const name = $(this).attr('name'); // Sẽ là 'product-sort'
            const value = $(this).val();
            filters[name] = value; // Sắp xếp là giá trị đơn
        });
        return filters;
    }

    function updateURL() {
        const filters = getSelectedFilters();
        const params = new URLSearchParams(window.location.search);
        const currentPage = params.get('page');

        // Xác định các key của filter để xóa trước khi thêm mới
        let filterParamKeys = ['filter_price', 'filter_brands', 'filter_branches', 'product-sort'];
        $('.filter-checkbox').each(function() {
            const name = $(this).attr('name');
            if (name && !filterParamKeys.includes(name)) {
                filterParamKeys.push(name);
            }
        });

        filterParamKeys.forEach(key => params.delete(key));

        // Thêm các filter hiện tại vào params
        for (const key in filters) {
            if (Array.isArray(filters[key])) {
                if (filters[key].length > 0) { // Chỉ thêm nếu mảng không rỗng
                    params.set(key, filters[key].join(',')); // Nối các giá trị bằng dấu phẩy
                }
            } else {
                params.set(key, filters[key]); // product-sort sẽ vào đây
            }
        }
        
        if (currentPage) { // Đặt lại 'page' nếu nó đã tồn tại
            params.set('page', currentPage);
        } else {
            params.delete('page'); // Xóa 'page' nếu không có, để ngầm định là trang 1
        }


        const newUrl = `${window.location.pathname}?${params.toString()}`;
        history.pushState({ path: newUrl }, '', newUrl);
    }

    function displayActiveFilters() {
        $activeFiltersContainer.empty();
        const filters = getSelectedFilters();
        let hasFilters = false; 

        for (const key in filters) {
            if (key === 'product-sort') { 
                continue;
            }

            const values = Array.isArray(filters[key]) ? filters[key] : [filters[key]];
            values.forEach(value => {
                hasFilters = true; 
                let labelText = value;
                const $inputElement = $(`input[name="${key}"][value="${value}"]`);
                
                if ($inputElement.length && $inputElement.attr('id')) {
                    const $label = $(`label[for="${$inputElement.attr('id')}"]`);
                    if ($label.length) {
                        labelText = ($label.find('.title').text().trim() || $label.text().trim() || value).replace(/<span class="count">\(\d+\)<\/span>/gi, '').trim();
                    }
                
                }

                const filterTag = $(`
                    <span class="active-filter-tag badge bg-success me-1 mb-1" data-filter-key="${key}" data-filter-value="${value}">
                        ${labelText} <i class="bi bi-x-lg ms-1" role="button" style="cursor:pointer;"></i>
                    </span>
                `);
                filterTag.find('i').on('click', function() {
                    removeFilter(key, value);
                });
                $activeFiltersContainer.append(filterTag);
            });
        }

        if (hasFilters) {
            const clearAllButton = $('<button class="clear-all-filters btn btn-sm btn-outline-danger me-2 p-0 px-3">Xóa tất cả</button>');
            clearAllButton.on('click', function() {
                $('.filter-checkbox:checked').prop('checked', false);
                triggerAjaxFilter(true);
            });
            $activeFiltersContainer.prepend(clearAllButton);

        }
    }

    function removeFilter(key, valueToRemove) {
        const $inputElement = $(`input[name="${key}"][value="${valueToRemove}"]`);
        if ($inputElement.is(':checkbox')) {
            $inputElement.prop('checked', false);
        }
        updateURL();
        triggerAjaxFilter(true); 
    }

    function triggerAjaxFilter(resetPage = false) {
        let selectedFilters = getSelectedFilters();
        let category_id = $('#product-container').data('category_id');
        let brands = $('#product-container').data('brands');

        // Thêm category_id vào filter nếu chưa có và nó tồn tại
        if (category_id && !selectedFilters['filter_category']) {
            selectedFilters['filter_category'] = [category_id];
        }
        if (brands && !selectedFilters['brands']) {
            selectedFilters['brands'] = [brands];
        }
        
        if (resetPage) {
            const params = new URLSearchParams(window.location.search);
            params.delete('page');
            const newUrl = `${window.location.pathname}?${params.toString()}`;
            history.replaceState({ path: newUrl }, '', newUrl); // Cập nhật URL trước khi gọi updateURL
        }

        updateURL(); // Cập nhật URL với các filter hiện tại (bao gồm cả sort)
        displayActiveFilters(); // Hiển thị các filter đang active

        let ajaxData = {
            action: 'filter_products',
            filters: selectedFilters, // selectedFilters đã bao gồm 'product-sort' nếu được chọn
            nonce: ThemeVars.nonce
        };

        // Lấy trang hiện tại từ URL để gửi đi, trừ khi resetPage
        const currentUrlParams = new URLSearchParams(window.location.search);
        if (!resetPage && currentUrlParams.has('page')) {
            ajaxData.page = currentUrlParams.get('page');
        }


        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: ajaxData,
            beforeSend: function(){
                productList.html(''); // Xóa sản phẩm cũ
                productList.before(spinner);
            },
            success: function(response) {
                productList.html(response); // Giả sử response là HTML của danh sách sản phẩm
                $('#psort-spinner').fadeOut().remove();
                updateCompareButtonStatus(); // Gọi lại hàm này nếu bạn có
                // displayActiveFilters(); // Đã gọi trước AJAX
                // Cập nhật lại pagination nếu server trả về (cần điều chỉnh response từ server)
                if (response.pagination) { // Giả sử server trả về pagination HTML
                    $('#pagination_wrapper').html(response.pagination);
                }
            },
            error: function(error) {
                console.error('Error filtering products:', error);
                $('#psort-spinner').fadeOut().remove();
            }
        });
    }
    
    // --- Event Handlers ---
    $('input[name="product-sort"]').on('change', function () {
        triggerAjaxFilter(true); // Sắp xếp sẽ reset về trang 1
    });

    $('.filter-checkbox').change(function() {
        triggerAjaxFilter(true); // Lọc sẽ reset về trang 1
    });

    // --- Pagination ---
    $(document).on('click','.product-pagination-small button', function(e){
        e.preventDefault();
        let thisBtn = $(this);
        let product_cate = $('#product-container').data('category_id'); // Vẫn có thể dùng nếu action products_listing cần
        const pageNumbersSpan = $('.product-pagination-small span.page-numbers');
        let current_page = parseInt(pageNumbersSpan.data('current-page'));
        let total_pages = parseInt(pageNumbersSpan.data('total-pages'));
        let newpage = 0;
        // const pageContainer = $('#pagination_wrapper');
        // const pagePrevBtn = pageContainer.find('.page-prev-btn');
        // const pageNextBtn = pageContainer.find('.page-next-btn');
        // if (!pageContainer.length) {
        //     return;
        // }

        if (thisBtn.hasClass('page-prev-btn')){
            if (current_page > 1) {
                newpage = current_page - 1;
            }
        } else if (thisBtn.hasClass('page-next-btn')) { 
            if (current_page < total_pages) {
                newpage = current_page + 1;
            }
        }
        if (newpage && newpage !== current_page) {
            // Cập nhật URL với trang mới
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('page', newpage);
            history.pushState(null, '', currentUrl.toString());
            console.log(currentUrl);
            window.location.href = currentUrl.toString();
            return;
            // displayActiveFilters(); // Không cần gọi ở đây vì URL chỉ thay đổi page

            let selectedFilters = getSelectedFilters(); // Lấy các filter hiện tại (bao gồm cả sort)
             if (product_cate && !selectedFilters['filter_category']) {
                selectedFilters['filter_category'] = [product_cate];
            }

            $.ajax({
                url: ThemeVars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'products_listing', // Hoặc 'filter_products' nếu nó cũng xử lý phân trang
                    page: newpage,
                    // cate: product_cate, // Có thể không cần nếu 'filter_category' đã có trong selectedFilters
                    filters: selectedFilters, // Gửi kèm filter và sort
                    nonce: ThemeVars.nonce
                },
                beforeSend: function(){
                    productList.html(''); // Xóa sản phẩm cũ
                    productList.before(spinner);
                },
                success: function(response) {
                    // Giả sử response là object { products: "...", pagination: "..." }
                    if(response.data && response.data.products) {
                        productList.html(response.data.products);
                    } else if (typeof response === 'string') { // Fallback nếu response chỉ là HTML sản phẩm
                        productList.html(response);
                    }

                    if(response.data && response.data.pagination) {
                        $('#pagination_wrapper').html(response.data.pagination);
                        // Cập nhật lại data cho span pageNumbers nếu cần
                        const newPageNumbersSpan = $('#pagination_wrapper').find('.product-pagination-small span.page-numbers');
                        if(newPageNumbersSpan.length) {
                             pageNumbersSpan.data('current-page', newPageNumbersSpan.data('current-page'));
                             pageNumbersSpan.data('total-pages', newPageNumbersSpan.data('total-pages'));
                             pageNumbersSpan.text(newPageNumbersSpan.text());
                        }
                    }
                    $('#psort-spinner').fadeOut().remove();
                    updateCompareButtonStatus();
                },
                error: function(error) {
                    console.error('Error paginating products:', error);
                    $('#psort-spinner').fadeOut().remove();
                }
            });
        }
    });
   
    // --- Image Variation Hover (Giữ nguyên) ---
    $(document).on('mouseenter', '.product-item .image-variations .variation-photo', function(e) { 
        let thisItem = $(this);
        let productItem = thisItem.closest('.product-item');
        let mainImage = productItem.find('.item-image img'); 
        let variationPhotoUrl = thisItem.data('img'); 

        if (variationPhotoUrl && mainImage.length) {
            if (!productItem.data('original-src')) {
                 productItem.data('original-src', mainImage.attr('src'));
            }
            mainImage.addClass('image-transitioning');
            mainImage.attr('src', variationPhotoUrl);
        }
    });

    $(document).on('mouseleave', '.product-item .image-variations .variation-photo', function(e) {
        let thisItem = $(this);
        let productItem = thisItem.closest('.product-item');
        let mainImage = productItem.find('.item-image img');
        let originalSrc = productItem.data('original-src'); 

        if (originalSrc && mainImage.length) {
            mainImage.addClass('image-transitioning');
            mainImage.attr('src', originalSrc);
            setTimeout(function() {
                mainImage.removeClass('image-transitioning');
            }, 500); // Thời gian khớp với transition CSS của bạn
        }
    });
    $('.mb-sortby-btn').on('click', function(e) {
        e.preventDefault();
        $('.product-sort-by').toggleClass('show');
    });

    // --- Initialization on Page Load ---
    function initializePage() {
        const params = new URLSearchParams(window.location.search);
        let filtersAppliedFromUrl = false;

        params.forEach((paramValue, key) => {
            // Check cả checkbox và radio
            // Nếu là filter từ checkbox, giá trị có thể là một chuỗi các giá trị nối bằng dấu phẩy
            if (key !== 'page' && key !== 'product-sort') { // Xử lý các filter dạng checkbox
                const values = paramValue.split(',');
                values.forEach(value => {
                    const $inputElement = $(`input.filter-checkbox[name="${key}"][value="${value}"]`);
                    if ($inputElement.length) {
                        $inputElement.prop('checked', true);
                        filtersAppliedFromUrl = true;
                    }
                });
            } else { // Xử lý product-sort (radio) hoặc các param khác
                const $inputElement = $(`input[name="${key}"][value="${paramValue}"]`);
                if ($inputElement.length) {
                    $inputElement.prop('checked', true);
                    if (key !== 'page') { 
                        filtersAppliedFromUrl = true;
                    }
                }
            }
        });
        
        displayActiveFilters(); // Hiển thị các filter active từ URL

        if (filtersAppliedFromUrl) {
            triggerAjaxFilter(); // Nếu có filter (bao gồm sort) từ URL, tải sản phẩm
        } else {
            // Nếu không có filter nào từ URL, có thể bạn muốn tải danh sách sản phẩm mặc định
            // Hoặc nếu #product-container đã có sẵn sản phẩm từ server-side render ban đầu thì không cần làm gì
            // Ví dụ: triggerAjaxFilter(); // để tải sản phẩm mặc định nếu container rỗng
        }
    }
    initializePage();
});