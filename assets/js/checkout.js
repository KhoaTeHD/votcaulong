jQuery(document).ready(function ($) {
    // ==== Loading & Notify ====
    function showLoading() {
        $('body').addClass('loading');
        $('#btn-checkout, #btn-checkout-with-card').prop('disabled', true);
    }
    function hideLoading() {
        $('body').removeClass('loading');
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
        $select.empty().append($('<option>', { value: '', text: defaultOptionText })).prop('disabled', true);
        if (!Array.isArray(data) || data.length === 0) return;
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
        return found;
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
                custom_ward: addressData.custom_ward
            },
            success: function(res) {
                if (res.success && res.data && res.data.shipping_cost) {
                    let shipping_cost = res.data.shipping_cost;
                    let html = '';
                    let first = false;
                    let firstMethod = '', firstFee = 0;
                    Object.entries(shipping_cost).forEach(([method, fee]) => {
                        html += `
                        <label class="me-3 mb-2">
                            <input type="radio" name="shipping_service" value="${method}" ${first ? 'checked' : ''}>
                            <span class="fw-bold">${method}</span>
                            <span class="text-primary ms-1">${fee.toLocaleString('vi-VN')} đ</span>
                        </label>
                    `;
                        if (first) { firstMethod = method; firstFee = fee; }
                        first = false;
                    });
                    $list.html(html || '<div class="text-danger p-2">Không có phương thức vận chuyển khả dụng!</div>');

                    // Update shipping fee với phương thức đầu tiên
                    updateShippingTotal(firstMethod, firstFee);

                } else {
                    $list.html('<div class="text-danger p-2">Không thể lấy phí vận chuyển!</div>');
                }
            },
            error: function() {
                $('#shipping-method-cost-list').html('<div class="text-danger p-2">Lỗi kết nối khi lấy phí vận chuyển!</div>');
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
            let hasSetLocation = false;
            if (savedLocation && selectOptionByText($locationSelect, savedLocation)) {
                hasSetLocation = true;
                loadWards(savedLocation, function(wards) {
                    populateSelect($wardSelect, wards, '-- Chọn Phường/Xã --', 'name', 'ward', true);
                    if (savedWard) selectOptionByText($wardSelect, savedWard);
                    if (typeof onReady === 'function') onReady();
                });
            }
            if (!hasSetLocation && typeof onReady === 'function') onReady();
        });
        // Sự kiện change trên location chỉ load lại wards
        $locationSelect.off('change').on('change', function() {
            const selectedLocationCode = $(this).val();
            $wardSelect.empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            if (selectedLocationCode) {
                loadWards(selectedLocationCode, function(wards) {
                    populateSelect($wardSelect, wards, '-- Chọn Phường/Xã --', 'name', 'ward', true);
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
        let subtotalText = $('#cartPage-subtotal .subtotal-value').text().replace(/[^\d]/g, '');
        let subtotal = parseInt(subtotalText, 10) || 0;

        let total = subtotal + shippingFee;

        $('.total-line .cartpageTotal-value').html(`${total.toLocaleString('vi-VN')}<sup>₫</sup>`);
    }

    $('#shipping-method-cost-list').on('change', 'input[name="shipping_service"]', function() {
        // Lấy tên và giá
        let shippingMethodName = $(this).val();
        let shippingFee = parseInt($(this).siblings('.text-primary').text().replace(/[^\d]/g, ''), 10) || 0;
        updateShippingTotal(shippingMethodName, shippingFee);
    });
    // Gọi khởi tạo dropdown ERP location/ward ngay khi trang tải
    // initLocationAndWard(locationSelect, wardSelectNew, prefillCityName, prefillWardName);
    initLocationAndWard(locationSelect, wardSelectNew, prefillCityName, prefillWardName, function(){
        // callback khi đã load xong location/ward (cả khi có prefill hay không)
        if ($('input[name="shipping_method"]:checked').val() === 'delivery') {
            let address_line1 = $('#street').val() || '';
            let custom_address_location = $('#shipping_location option:selected').text() || '';
            let custom_ward = $('#shipping_ward_new').val() || '';
            if (address_line1 && custom_address_location && custom_ward) {
                loadShippingMethodsAndCost({
                    address_line1,
                    custom_address_location,
                    custom_ward
                });
            }
        }
    });
    let shippingCostTimeout = null;
    $('#shipping_ward_new, #street').on('change blur', function() {
        clearTimeout(shippingCostTimeout);
        shippingCostTimeout = setTimeout(function() {
            if ($('input[name="shipping_method"]:checked').val() === 'delivery') {
                let address_line1 = $('#street').val() || '';
                let custom_address_location = $('#shipping_location option:selected').text() || '';
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
                custom_address_location: $('#shipping_location option:selected').text() || '',
                custom_ward: $('#shipping_ward_new').val() || ''
            };
            // Gọi hàm lấy phí ship:
            loadShippingMethodsAndCost(addressData);
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
            payment_method: paymentMethodValue,
            payment_method_title: paymentMethodTitle,
            shipping_service: null,
            shipping_total: null,
            cart: getCart()
        };

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
    $(document).on('change','#shoppingCart-page #cartPage-total',function(){
        console.log('Total change');
    });
});
