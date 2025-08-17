jQuery(document).ready(function ($) {
    // ==== Loading & Notify ====
    function showLoading() {
        $('#loading-overlay').show();
        $('#btn-checkout, #btn-checkout-with-card').prop('disabled', true);
    }
    function hideLoading() {
        $('#loading-overlay').hide();
        $('#btn-checkout, #btn-checkout-with-card').prop('disabled', false);
    }
    function siteNotify(message){
        jQuery('#siteNotify .toast-body').text(message);
        var toast = new bootstrap.Toast(jQuery('#siteNotify')[0]);
        toast.show();
    }

    // ==== LOCATION/WARD ERP (Không xài JSON, chỉ dùng AJAX tới ERP) ====
    let locationsData = null;
    let wardsCache = {};

    function loadLocations(callback) {
        if (locationsData) { if (typeof callback === 'function') callback(locationsData); return; }
        $.ajax({
            url: AccountAddressVars.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_erp_locations', nonce: ThemeVars.nonce },
            beforeSend: function () { showLoading($('#shipping_location')); },
            success: function (response) {
                hideLoading($('#shipping_location'));
                if (response.success && Array.isArray(response.data)) {
                    locationsData = response.data;
                    if (typeof callback === 'function') callback(locationsData);
                } else {
                    siteNotify('Không thể tải danh sách Tỉnh/Thành phố.', 'error');
                    if (typeof callback === 'function') callback(null);
                }
            },
            error: function () {
                hideLoading($('#shipping_location'));
                siteNotify('Không thể tải danh sách Tỉnh/Thành phố.', 'error');
                if (typeof callback === 'function') callback(null);
            }
        });
    }
    function loadWards(locationCode, callback) {
        if (!locationCode) { if (typeof callback === 'function') callback([]); return; }
        if (wardsCache[locationCode]) { if (typeof callback === 'function') callback(wardsCache[locationCode]); return; }
        $.ajax({
            url: AccountAddressVars.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_erp_wards', nonce: ThemeVars.nonce, location: locationCode },
            beforeSend: function () { showLoading($('#shipping_ward_new')); },
            success: function (response) {
                hideLoading($('#shipping_ward_new'));
                if (response.success && Array.isArray(response.data)) {
                    wardsCache[locationCode] = response.data;
                    if (typeof callback === 'function') callback(response.data);
                } else {
                    siteNotify('Không thể tải danh sách Phường/Xã.', 'error');
                    if (typeof callback === 'function') callback([]);
                }
            },
            error: function () {
                hideLoading($('#shipping_ward_new'));
                siteNotify('Không thể tải danh sách Phường/Xã.', 'error');
                if (typeof callback === 'function') callback([]);
            }
        });
    }
    // fill select, tự động cắt mã số ward nếu removeSuffix = true
    function populateSelect($select, data, defaultOptionText, valueKey, textKey, removeSuffix = false) {
        $select.empty().append($('<option>', { value: '', text: defaultOptionText }));
        if (!Array.isArray(data) || data.length === 0) {
            $select.prop('disabled', true);
            return;
        }
        data.forEach(item => {
            let displayText = item[textKey] || item;
            if (removeSuffix && displayText.includes('-')) {
                displayText = displayText.substring(0, displayText.lastIndexOf('-')).trim();
            }
            $select.append($('<option>', {
                value: item[valueKey] || item,
                text: displayText
            }));
        });
        $select.prop('disabled', false);
    }
    function selectOptionByText($select, text) {
        if (!text || !$select) return false;
        const lowerCaseText = text.toLowerCase().trim();
        let found = false;
        $select.find('option').each(function() {
            const optionText = $(this).text().toLowerCase().trim();
            if (optionText === lowerCaseText || lowerCaseText=== $(this).val().toLowerCase() ) {
                $(this).prop('selected', true);
                found = true;
                return false;
            }
        });
        if (found) {
            $select.trigger('change'); // Notify Select2 to update its display
        }
        return found;
    }
    function getCartTotal(){
        const cartItems = getCart();
        let total = 0;
        cartItems.forEach(item => {total+=parseFloat(item.price)});
        return total;
    }

    function loadShippingMethodsAndCost(addressData) {
        // Hiển thị loading khi load phí ship
        let $list = $('#shipping-method-cost-list');
        $list.html('<div class="text-muted p-2">Đang tải phí vận chuyển...</div>');

        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_shipping_cost',
                nonce: ThemeVarsCheckout.vcl_checkout_nonce,
                address_line1: addressData.address_line1,
                custom_address_location: addressData.custom_address_location,
                custom_ward: addressData.custom_ward,
                cart_total: $('#cartPage-subtotal .subtotal-value').data('value'),
            },
            beforeSend:function(){
              showLoading();
            },
            success: function(res) {
                if (res.success && res.data && res.data.shipping_cost) {
                    let shipping_cost = res.data.shipping_cost;
                    let html = '';
                    let firstMethod = '', firstFee = 0;
                    let isFirst = true;
                    Object.entries(shipping_cost).forEach(([method, fee]) => {
                        html += `
                        <label class="me-3 mb-2">
                            <input type="radio" name="shipping_service" value="${method}" ${isFirst ? 'checked' : ''}>
                            <span class="fw-bold">${method}</span>
                            <span class="text-primary ms-1">${fee.toLocaleString('vi-VN')} đ</span>
                        </label>
                    `;
                        if (isFirst) { firstMethod = method; firstFee = fee; isFirst = false; }
                    });
                    $list.html(html || '<div class="text-danger p-2">Không có phương thức vận chuyển khả dụng!</div>');

                    // Update shipping fee với phương thức đầu tiên
                    if (firstMethod) {
                        updateShippingTotal(firstMethod, firstFee);
                    }

                } else {
                    $list.html('<div class="text-danger p-2">Không thể lấy phí vận chuyển!</div>');
                }
                hideLoading();
            },
            error: function() {
                $('#shipping-method-cost-list').html('<div class="text-danger p-2">Lỗi kết nối khi lấy phí vận chuyển!</div>');
                hideLoading();
            }
        });
    }
    // ==== Shipping Address Dropdown (khởi tạo từ ERP, có tự điền lại nếu có địa chỉ mặc định) ====
    const locationSelect = $('#shipping_location');
    const wardSelectNew = $('#shipping_ward_new');
    const shipping_address = $('#shipping-address');
    let prefillCityName = '';
    let prefillWardName = '';
    let currentAddresses = AccountAddressVars.addresses || [];

    if (currentAddresses.length) {
        currentAddresses.forEach(function (address) {
            if (address.is_default){
                prefillCityName = address.location_code || address.location_name || '';
                prefillWardName = address.ward_code || address.ward_name || '';
                $('#street').val(address.street);
            }
        });
    } else {
        prefillCityName = shipping_address.data('prefill-city') || '';
        prefillWardName = shipping_address.data('prefill-ward') || '';
    }

    function initLocationAndWard($locationSelect, $wardSelect, savedLocation, savedWard, onReady) {
        loadLocations(function(locations) {
            populateSelect($locationSelect, locations, '-- Chọn Tỉnh/Thành phố --', 'name', 'name');
            $locationSelect.select2(); // Initialize Select2
            let hasSetLocation = false;
            if (savedLocation && selectOptionByText($locationSelect, savedLocation)) {
                hasSetLocation = true;
                loadWards(savedLocation, function(wards) {
                    populateSelect($wardSelect, wards, '-- Chọn Phường/Xã --', 'name', 'ward', true);
                    if (savedWard) {
                        selectOptionByText($wardSelect, savedWard);
                    }
                    $wardSelect.select2(); // Initialize Select2
                    if (typeof onReady === 'function') onReady();
                });
            }
            if (!hasSetLocation && typeof onReady === 'function') onReady();
        });
        // Sự kiện change trên location chỉ load lại wards
        $locationSelect.off('change').on('change', function() {
            const selectedLocationCode = $(this).val();
            $wardSelect.empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            if ($wardSelect.hasClass('select2-hidden-accessible')) {
                $wardSelect.select2('destroy'); // Destroy Select2 before repopulating
            }
            if (selectedLocationCode) {
                loadWards(selectedLocationCode, function(wards) {
                    populateSelect($wardSelect, wards, '-- Chọn Phường/Xã --', 'name', 'ward', true);
                    $wardSelect.select2(); // Re-initialize Select2
                });
            }
            // KHÔNG load phí ship ở đây!
        });
    }

    function updateShippingTotal(shippingMethodName, shippingFee) {
        // Update tên phương thức giao hàng
        $('.shipping-line .shipping-method-name').text(shippingMethodName ? `(${shippingMethodName})` : '');
        // Update phí ship
        $('.shipping-line .cartpageTotal-value').html(`${shippingFee.toLocaleString('vi-VN')}<sup>₫</sup>`);

        // Lấy subtotal hiện tại từ dòng total-line
        let subtotalText = $('#cartPage-subtotal .subtotal-value').data('value');
        let allDiscount = 0;
        $('#cartPage-total .orderPromotions').each(function(){
            let value = $(this).data('value');
            allDiscount += parseInt(value);
        });
        // let discountText = $('#cartPage-total .cartpageTotal-value.promotionValue').text().replace(/[^\d]/g, '');
        let subtotal = parseInt(subtotalText, 10) || 0;

        let total = subtotal + shippingFee - allDiscount;

        $('.total-line .cartpageTotal-value').html(`${total.toLocaleString('vi-VN')}<sup>₫</sup>`);
        // console.log('total:',discountText,' ship:',shippingFee);
    }

    $('#shipping-method-cost-list').on('change', 'input[name="shipping_service"]', function() {
        // Lấy tên và giá
        let shippingMethodName = $(this).val();
        let shippingFee = parseInt($(this).siblings('.text-primary').text().replace(/[^\d]/g, ''), 10) || 0;
        updateShippingTotal(shippingMethodName, shippingFee);
    });
    // Gọi khởi tạo dropdown ERP location/ward ngay khi trang tải
    // initLocationAndWard(locationSelect, wardSelectNew, prefillCityName, prefillWardName);
    initLocationAndWard(locationSelect, wardSelectNew, prefillCityName, prefillWardName);
    let shippingCostTimeout = null;
    $('#shipping_ward_new, #street').on('change blur', function() {
        clearTimeout(shippingCostTimeout);
        shippingCostTimeout = setTimeout(function() {
            if ($('input[name="shipping_method"]:checked').val() === 'delivery') {
                let address_line1 = $('#street').val() || '';
                let custom_address_location = $('#shipping_location option:selected').val() || '';
                let custom_ward = $('#shipping_ward_new').val() || '';
                if (address_line1 && custom_address_location && custom_ward) {
                    loadShippingMethodsAndCost({
                        address_line1,
                        custom_address_location,
                        custom_ward
                    });
                } else {
                    $('#shipping-method-cost-list').html('<div class="text-danger p-2">Vui lòng nhập đủ địa chỉ để tính phí vận chuyển.</div>');
                }
            }
        }, 400); // chỉ gọi 1 lần sau khi user dừng thao tác 400ms
    });
    const deliveryRadio = $('#delivery');
    const pickupRadio = $('#pickup');
    const storeListContainer = $('#store-list-container');
    const shipping_address_container = $('#shipping-address');
    const shipping_method_container = $('#shipping-method');
    const addressSearchContainer = $('#address-search').closest('.mb-3');

    // Ẩn danh sách cửa hàng và khung tìm kiếm ban đầu
    storeListContainer.hide();
    addressSearchContainer.hide();

    // Tìm kiếm cửa hàng
    const addressSearch = $('#address-search');
    const storeItems = storeListContainer.find('li');
    addressSearch.on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        storeItems.each(function() {
            const itemAddress = $(this).data('address').toLowerCase();
            const listItem = $(this);
            if (searchTerm === '' || itemAddress.includes(searchTerm)) {
                listItem.show();
            } else {
                listItem.hide();
            }
        });
    });

    $('input[name="shipping_method"]').on('change', function() {
        if ($(this).val() === 'pickup') {
            storeListContainer.slideDown();
            addressSearchContainer.slideDown();
            shipping_address.slideUp();
            shipping_method_container.slideUp();
        } else {
            storeListContainer.slideUp();
            addressSearchContainer.slideUp();
            shipping_address.slideDown();
            shipping_method_container.slideDown();
            if (!locationsData) {
                loadLocations();
            }
            let addressData = {
                address_line1: $('#street').val() || '',
                custom_address_location: $('#shipping_location option:selected').val() || '',
                custom_ward: $('#shipping_ward_new').val() || ''
            };
            if (addressData.custom_address_location && addressData.custom_ward){
                loadShippingMethodsAndCost(addressData);
            }
            // Gọi hàm lấy phí ship:

        }
    });

    // Event toggle info nhận hàng hộ, xuất hoá đơn
    const otherRecipientInfo = $('#other-recipient-info');
    const companyInvoiceInfo = $('#company-invoice-info');
    const otherRecipientCheckbox = $('#other_recipient');
    const companyInvoiceCheckbox = $('#company_invoice');
    otherRecipientInfo.hide();
    companyInvoiceInfo.hide();
    otherRecipientCheckbox.on('change', function() {
        if (this.checked) {
            otherRecipientInfo.slideDown();
        } else {
            otherRecipientInfo.slideUp();
        }
    });
    companyInvoiceCheckbox.on('change', function() {
        if (this.checked) {
            companyInvoiceInfo.slideDown();
        } else {
            companyInvoiceInfo.slideUp();
        }
    });


    function get_checkout_order() {
        const $selectedService = $('#shipping-method-cost-list input[name="shipping_service"]:checked');
        const orderData = {
            customer_info: {
                gender: $('input[name="gender"]:checked').val(),
                fullname: $('#fullname').val(),
                phone_number: $('#phone_number').val(),
                email: $('#email').val(),
            },
            shipping_total: parseInt($selectedService.siblings('.text-primary').text().replace(/[^\d]/g, ''), 10) || 0,
            voucher_code : '',
            order_rule: '',
            loyalty_point: 0,
            cart: getCart(),
            items_rule: []
        };

        if ($('#input_voucher_code').val()){
            orderData.voucher_code = $('#input_voucher_code').val();
        }
        if ($('input[name="order_pricing_rule"]:checked').val()){
            orderData.order_rule =$('input[name="order_pricing_rule"]:checked').val();
        }
        if (parseInt($('#input_redeem_loyalty').val())){
            orderData.loyalty_point= parseInt($('#input_redeem_loyalty').val());
        }

        $('#cartPage-items .cartPage-item').each(function (){
            let $item = $(this);
            let $item_code = $item.data('variation-sku') || $item.data('sku');
            let $item_promotions = $item.find('.item-promotions');
            if ($item_promotions.length) {
                let $item_pricing_rule = $item_promotions.find('input[name="item_pricing_rule"]:checked').val();
                if($item_pricing_rule){
                    orderData.items_rule.push({item_code: $item_code, pricing_rule: $item_pricing_rule});
                }
            }
        });

        // ✅ RETURN the ajax call so we can wait for it
        return jQuery.ajax({
            url: ThemeVars.ajaxurl,
            method: "POST",
            data: {
                action: "refresh_checkout",
                order_data: orderData,
                nonce: ThemeVars.nonce,
            },
            beforeSend: function () {
                showLoading();
            },
            success: function (response) {
                if (response.success) {
                    let order_rules = '';
                    jQuery("#shoppingCart-page #cartPage-items").html(response.data.html);
                    jQuery("#shoppingCart-page #cartPage-subtotal").html(response.data.subtotal_html);
                    jQuery("#shoppingCart-page #cartPage-total").html(response.data.total_html);

                    if (response.data.order_rules) {
                        jQuery(response.data.order_rules).each(function(idx, rule){
                            order_rules += `<input type="radio" style="display: none;" ${orderData.order_rule===rule.pricing_rule ? 'checked' : ''} name="order_pricing_rule" id="${rule.pricing_rule}" value="${rule.pricing_rule}"><label class="btn btn-outline-danger select_order_rules" role="button" for="${rule.pricing_rule}"><i class="bi bi-ticket-detailed"></i> ${rule.description}</label>`;
                        });
                        jQuery('#order_pricing_rule').html(order_rules);
                    }

                    if (orderData.voucher_code){
                        let voucher_applied = `<div class="voucher_applied discounts_applied"><span><i class="bi bi-ticket-detailed"></i> ${orderData.voucher_code} <i class="bi bi-x-lg ms-1 remove" role="button"></i></span></div>`;
                        apply_discount_item('#apply_voucher_button', voucher_applied);
                    }
                    if (orderData.loyalty_point) {
                        let points_redeem  = `<div class="loyalty_applied discounts_applied"><span><i class="bi bi-ticket-detailed"></i> ${orderData.loyalty_point} <i class="bi bi-x-lg ms-1 remove" role="button"></i></span></div>`;
                        apply_discount_item('#redeem_loyalty_section', points_redeem);
                    }
                } else {
                    console.error("Lỗi:", response.data);
                    siteNotify(response.data);
                }
            },
            error: function (error) {
                console.error("Lỗi khi tải thông tin sản phẩm:", error);
                jQuery("#shoppingCart-page").html(`<p>${translations.cart_error}</p>`);
            },
            complete: function () {
                hideLoading();
            }
        });
    }
    get_checkout_order().then(function () {
        const address_line1 = $('#street').val() || '';
        const custom_address_location = $('#shipping_location option:selected').val() || '';
        const custom_ward = $('#shipping_ward_new').val() || '';
        if (address_line1 && custom_address_location && custom_ward) {
            loadShippingMethodsAndCost({
                address_line1,
                custom_address_location,
                custom_ward
            });
        }
    });
    function apply_discount_item(form_wrapper,item_html){
        const $wrapper = $(form_wrapper);
        const $applied_wrapper = $('#order_discounts_applied');
        const $newItem = $(item_html);

        let type;
        if ($newItem.hasClass('voucher_applied')) {
            type = 'voucher_applied';
        } else if ($newItem.hasClass('loyalty_applied')) {
            type = 'loyalty_applied';
        } else {
            console.warn('Unknown discount type, skipping apply');
            return;
        }
        if ($applied_wrapper.find('.' + type).length > 0) {
            return;
        }

        $applied_wrapper.append($newItem);
        $wrapper.find('input').prop('disabled', true);
        $wrapper.hide();
    }
    function remove_discount_item($btn) {
        const $thisDiscount = $btn.closest('.discounts_applied');
        let $wrapper;
        if ($thisDiscount.hasClass('voucher_applied')) {
            $wrapper = $('#apply_voucher_button');
        } else if ($thisDiscount.hasClass('loyalty_applied')) {
            $wrapper = $('#redeem_loyalty_section');
        } else {
            console.warn('Unknown discount type, skipping removal');
            return;
        }
        $thisDiscount.remove();
        $wrapper.find('input[type="text"]').prop('disabled', false).val('');
        $wrapper.show();
        get_checkout_order();
    }
    $('#order_discounts_applied').on('click','.discounts_applied .remove',function (){
        remove_discount_item($(this));
    });

    $('#order_pricing_rule').on('change','input[name="order_pricing_rule"]',function(){
        get_checkout_order();
    });

    $('.refresh-checkout, #button-apply-voucher, #button-redeem-loyalty').on('click',function(){
        get_checkout_order();
        return false;
    });

    // --- Place Order Button ---
    $('#btn-checkout, #btn-checkout-with-card').on('click', function() {
        if (!$('#accept_privacy_policy').is(':checked')) {
            siteNotify(translations.accept_privacy_policy_required || 'Bạn phải đồng ý với chính sách bảo mật để tiếp tục.');
            return;
        }
        const selectedPaymentMethodInput = $('input[name="payment_method"]:checked');
        if (selectedPaymentMethodInput.length === 0) {
            siteNotify(translations.payment_method_required || 'Vui lòng chọn phương thức thanh toán.');
            return;
        }
        const paymentMethodValue = selectedPaymentMethodInput.val();
        const paymentMethodTitle = selectedPaymentMethodInput.data('title') || selectedPaymentMethodInput.closest('label').text().trim() || paymentMethodValue;
        const orderData = {
            customer_info: {
                gender: $('input[name="gender"]:checked').val(),
                fullname: $('#fullname').val(),
                phone_number: $('#phone_number').val(),
                email: $('#email').val(),
            },
            shipping_method: $('input[name="shipping_method"]:checked').val(),
            shipping_address: null,
            pickup_store_id: null,
            order_note: $('#order_note').val(),
            other_recipient: null,
            how_to_use: $('#how_to_use').is(':checked'),
            company_invoice: null,
            voucher_code: $('#input_voucher_code').val(),
            loyalty_point: parseInt($('#input_redeem_loyalty').val()),
            order_pricing_rule : null,
            payment_method: paymentMethodValue,
            payment_method_title: paymentMethodTitle,
            shipping_service: null,
            shipping_total: null,
            cart: getCart(),
            items_rule: []
        };
        let items_rule = [];
        if ($('input[name="order_pricing_rule"]:checked').val()){
            orderData.order_pricing_rule =$('input[name="order_pricing_rule"]:checked').val();
        }
        //get items pricing rule
        $('#cartPage-items .cartPage-item').each(function (){
           let $item = $(this);
           let $item_code = $item.data('variation-sku')||$item.data('sku');
           let $item_item_promotions = $item.find('.item-promotions')||null;
           if ($item_item_promotions) {
               let $item_pricing_rule = $item_item_promotions.find('input[name="item_pricing_rule"]:checked').val();
               if($item_pricing_rule){
                   orderData.items_rule.push({item_code:$item_code,pricing_rule:$item_pricing_rule});
               }
           }
        });
        // console.log(items_rule);
        let isValid = true;
        if (orderData.shipping_method === 'delivery') {
            orderData.shipping_address = {
                city_code: $('#shipping_location').val(),
                city_name: $('#shipping_location option:selected').text(),
                ward_code: $('#shipping_ward_new').val(),
                // Chỉ lấy tên ward đẹp khi show ra, không lấy mã
                ward_name: $('#shipping_ward_new option:selected').text(),
                street: $('#street').val()
            };
            const $selectedService = $('#shipping-method-cost-list input[name="shipping_service"]:checked');
            orderData.shipping_service = $selectedService.val();
            orderData.shipping_total = parseInt($selectedService.siblings('.text-primary').text().replace(/[^\d]/g, ''), 10) || 0;
            if (!orderData.shipping_address.city_code ||
                !orderData.shipping_address.ward_code ||
                !orderData.shipping_address.street) {
                siteNotify(translations.shipping_address_required || 'Vui lòng nhập đầy đủ thông tin địa chỉ giao hàng.');
                isValid = false;
            }
            if (!$selectedService.length) {
                siteNotify('Vui lòng chọn phương thức vận chuyển!');
                isValid = false;
            }
        } else if (orderData.shipping_method === 'pickup') {
            orderData.pickup_store_id = $('input[name="selected_store"]:checked').val();
            if (!orderData.pickup_store_id) {
                siteNotify(translations.pickup_store_required || 'Vui lòng chọn cửa hàng để nhận hàng.');
                isValid = false;
            }
        }
        if (!orderData.customer_info.fullname || !orderData.customer_info.phone_number) {
            siteNotify(translations.customer_info_required || 'Vui lòng nhập đầy đủ Họ tên và Số điện thoại.');
            isValid = false;
        }
        if ($('#other_recipient').is(':checked')) {
            orderData.other_recipient = {
                title: $('input[name="other_recipient_title"]:checked').val(),
                name: $('#other_recipient_name').val(),
                phone: $('#other_recipient_phone').val()
            };
            if (!orderData.other_recipient.name || !orderData.other_recipient.phone) {
                siteNotify(translations.other_recipient_required || 'Vui lòng nhập đầy đủ thông tin người nhận khác.');
                isValid = false;
            }
        }
        if ($('#company_invoice').is(':checked')) {
            orderData.company_invoice = {
                name: $('#company_name').val(),
                tax_number: $('#company_tax_number').val(),
                address: $('#company_address').val()
            };
            if (!orderData.company_invoice.name || !orderData.company_invoice.tax_number || !orderData.company_invoice.address) {
                siteNotify(translations.company_invoice_required || 'Vui lòng nhập đầy đủ thông tin xuất hóa đơn công ty.');
                isValid = false;
            }
        }
        if (!isValid) { return; }

        let ajaxAction = '';
        switch (paymentMethodValue) {
            case 'cod':
                ajaxAction = 'process_cod_order';
                break;
            case 'payoo':
                ajaxAction = 'process_payoo_order';
                break;
            case 'vnpay':
                ajaxAction = 'process_vnpay_order';
                break;
            default:
                siteNotify('Phương thức thanh toán không hợp lệ.', 'error');
                return;
        }
        $.ajax({
            url: ThemeVars.ajaxurl,
            method: 'POST',
            data: {
                action: ajaxAction,
                nonce: ThemeVarsCheckout.vcl_checkout_nonce,
                order_data: JSON.stringify(orderData)
            },
            beforeSend: function() { showLoading(); },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    siteNotify(response.data.message || 'Yêu cầu đặt hàng đã được gửi!', 'success');
                    saveCart([]);
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else if (response.data.order_received_url) {
                        window.location.href = response.data.order_received_url;
                    } else {
                        window.location.href = '/';
                    }
                } else {
                    siteNotify(response.data.message || 'Đã xảy ra lỗi khi xử lý đơn hàng. Vui lòng thử lại.', 'error');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                hideLoading();
                siteNotify('Đã xảy ra lỗi kết nối khi gửi yêu cầu đặt hàng. Vui lòng kiểm tra kết nối mạng và thử lại.', 'error');
            }
        });
    });

});
