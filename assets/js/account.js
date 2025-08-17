jQuery(document).ready(function ($) {
    // ------------------ CONFIG & CACHE ------------------
    let locationsData = null;
    let wardsCache = {};

    // ------------------ UTILS ------------------
    function showLoading($el) { if ($el) $el.addClass('loading'); }
    function hideLoading($el) { if ($el) $el.removeClass('loading'); }
    // function siteNotify(msg, type='info') { alert(msg); }
    function toggleButtonState($btn, loading) {
        if (!$btn) return;
        if (loading) {
            $btn.prop('disabled', true);
            $btn.data('original-text', $btn.html());
            $btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Đang xử lý...');
        } else {
            $btn.prop('disabled', false);
            if ($btn.data('original-text')) $btn.html($btn.data('original-text'));
        }
    }
    function populateSelect($select, data, placeholder, valueKey, textKey) {
        $select.empty().append($('<option>', { value: '', text: placeholder })).prop('disabled', true);
        if (!Array.isArray(data) || data.length === 0) return;
        data.forEach(item => {
            $select.append($('<option>', {
                value: item[valueKey] || item,
                text: item[textKey] || item
            }));
        });
        $select.prop('disabled', false);
    }
    function selectOptionByText($select, text) {
        let found = false;
        if (!text) return found;
        $select.find('option').each(function() {
            if ($(this).text().trim().toLowerCase() === text.trim().toLowerCase() || $(this).val().trim().toLowerCase() === text.trim().toLowerCase() ) {
                $(this).prop('selected', true);
                found = true;
                return false;
            }
        });
        return found;
    }

    // ------------------ AJAX LOCATION/WARD ERP ------------------
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
    function loadWards(location, callback) {
        if (!location) { if (typeof callback === 'function') callback([]); return; }
        if (wardsCache[location]) { if (typeof callback === 'function') callback(wardsCache[location]); return; }
        $.ajax({
            url: AccountAddressVars.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_erp_wards', nonce: ThemeVars.nonce, location: location },
            beforeSend: function () { showLoading($('#shipping_ward_new')); },
            success: function (response) {
                hideLoading($('#shipping_ward_new'));
                if (response.success && Array.isArray(response.data)) {
                    wardsCache[location] = response.data;
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
    function initLocationAndWard($locationSelect, $wardSelect, savedLocation, savedWard) {
        loadLocations(function(locations) {
            populateSelect($locationSelect, locations, '-- Chọn Tỉnh/Thành phố - Quận/Huyện --', 'name', 'name');
            if (savedLocation && selectOptionByText($locationSelect, savedLocation)) {
                loadWards(savedLocation, function(wards) {
                    populateSelect($wardSelect, wards, '-- Chọn Phường/Xã --', 'name', 'ward');
                    if (savedWard) selectOptionByText($wardSelect, savedWard);
                });
            }
        });
        $locationSelect.on('change', function() {
            const selectedLocation = $(this).val();
            $wardSelect.empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
            if (selectedLocation) {
                loadWards(selectedLocation, function(wards) {
                    populateSelect($wardSelect, wards, '-- Chọn Phường/Xã --', 'name', 'ward');
                });
            }
        });
    }

    // ------------------ ĐĂNG NHẬP / ĐĂNG KÝ / ĐĂNG XUẤT ------------------
    $(document).on('click','.sign-out-btn', function(e) {
        e.preventDefault();
        let $thisBtn = $(this);
        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: { action: 'logout', nonce: ThemeVars.nonce },
            beforeSend: function (){ toggleButtonState($thisBtn,true); },
            success: function(response) {
                if (response.success) window.location.href = response.data.redirect;
                else siteNotify('Lỗi khi đăng xuất: '+response.data.message,'error');
                toggleButtonState($thisBtn,false);
            },
            error: function(error) { siteNotify('Lỗi AJAX khi đăng xuất','error'); }
        });
    });

    $('.togglePassword i').click(function(){
        let passwordField = $(this).parent().siblings('input');
        const type = passwordField.attr("type") === "password" ? "text" : "password";
        passwordField.attr("type", type);
        if (type === "password") {
            $(this).addClass('bi-eye-slash').removeClass('bi-eye');
        } else{
            $(this).addClass('bi-eye').removeClass('bi-eye-slash');
        }
    });

    $('#formToggle-btn').click(function(){
        let this_text = $(this).text();
        let login_text = $(this).data('login');
        let register_text = $(this).data('register');
        const $overlay = $('.register_login_form-wrapper .left-col .overlay');
        $overlay.css('opacity', 1);
        setTimeout(() => {
            if (this_text === login_text) {
                $('.register_login_form-wrapper').addClass('login-form').removeClass('register-form');
                $('.register_login_form-wrapper .left-col .register-text').fadeIn();
                $('.register_login_form-wrapper .left-col .login-text').hide();
                $(this).text(register_text);
                let $thisform = $('#register-frm-wrapper');
                $thisform.find('.form-title').addClass('title-move-down');
                setTimeout(() => { $thisform.hide(); }, 500);
                $thisform.css('opacity', 0);
                setTimeout(() => {
                    $thisform.find('.form-title').removeClass('title-move-down');
                    $('#login-frm-wrapper').show().css('opacity', 1);
                }, 200);
            } else {
                $('.register_login_form-wrapper').addClass('register-form').removeClass('login-form');
                $('.register_login_form-wrapper .left-col .register-text').hide();
                $('.register_login_form-wrapper .left-col .login-text').fadeIn();
                $(this).text(login_text);
                let $thisform = $('#login-frm-wrapper');
                $thisform.find('.form-title').addClass('title-move-down');
                setTimeout(() => { $thisform.hide(); }, 500);
                $thisform.css('opacity', 0);
                setTimeout(() => {
                    $thisform.find('.form-title').removeClass('title-move-down');
                    $('#register-frm-wrapper').show().css('opacity', 1);
                }, 200);
            }
        },500);
        setTimeout(() => { $overlay.css('opacity', 0.6); }, 500);
    });

    $('.formToggle-btn').on('click',function(e){
        e.preventDefault();
        $('#formToggle-btn').trigger('click');
    });

    $('#frm-login').on('submit', function (e) {
        e.preventDefault();
        let checked = true;
        let $form = $(this);
        $form.find('.error').removeClass('error');
        let $submit_btn = $form.find('button[type="submit"]');
        let input_login = $form.find('input[name="email_phone_number"]').val();
        let pass = $form.find('input[name="password"]').val();
        if (input_login==='') { checked = false; $form.find('input[name="email_phone_number"]').addClass('error'); }
        if (pass==='') { checked = false; $form.find('input[name="password"]').addClass('error'); }
        if (!checked){ return false; }
        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: { action: 'custom_login', nonce: ThemeVars.nonce, email_phone_number: input_login, password: pass },
            beforeSend: function (){ toggleButtonState($submit_btn,true); },
            success: function (response) {
                if (response.success) { window.location.href = response.data.redirect; }
                else { siteNotify(response.data.message,'error'); }
                toggleButtonState($submit_btn,false);
            }
        });
    });

    $('#frm-register').on('submit', function (e) {
        e.preventDefault();
        let $form = $(this);
        let $submit_btn = $form.find('button[type="submit"]');
        $form.find('.error').removeClass('error');
        let checked = true;
        let register_fullname = $form.find('input[name="register_fullname"]').val();
        let input_register = $form.find('input[name="email_phone_number"]').val();
        let pass = $form.find('input[name="password"]').val();
        let re_pass = $form.find('input[name="repeat-password"]').val();
        if (register_fullname==='') { checked = false; $form.find('input[name="register_fullname"]').addClass('error'); }
        if (input_register==='') { checked = false; $form.find('input[name="email_phone_number"]').addClass('error'); }
        if (pass==='' || re_pass==='') { checked = false; $form.find('input[name="password"]').addClass('error'); }
        if ( pass!==re_pass){ checked = false; $form.find('input[name="password"]').addClass('error'); $form.find('input[name="repeat-password"]').addClass('error'); }
        if (!checked){ return false; }
        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: { action: 'custom_register', nonce: ThemeVars.nonce, fullname: register_fullname ,email_or_phone: input_register, password: pass },
            beforeSend: function (){ toggleButtonState($submit_btn,true); },
            success: function (response) {
                if (response.success) {
                    siteNotify(response.data.message);
                    setTimeout(function() { window.location.href = response.data.redirect; }, 3000);
                } else {
                    siteNotify(response.data.message,'error');
                    toggleButtonState($submit_btn,false);
                }
            },
            error: function() {
                siteNotify('Đã có lỗi xảy ra khi gửi yêu cầu. Vui lòng thử lại.','error');
                toggleButtonState($submit_btn,false);
            }
        });
    });

    // ------------------ FORGOT PASSWORD ------------------
    $('#frm-forgot-password').on('submit', function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $('#btn-reset-password');
        let $errorAlert = $('#forgot-password-error');
        $errorAlert.addClass('d-none').text('');

        let userLogin = $form.find('input[name="user_login"]').val().trim();

        if (userLogin === '') {
            $errorAlert.text('Vui lòng nhập email hoặc số điện thoại.').removeClass('d-none');
            return false;
        }

        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'vcl_forgot_password',
                nonce: ThemeVars.nonce,
                user_login: userLogin
            },
            beforeSend: function() {
                toggleButtonState($btn, true);
            },
            success: function(response) {
                if (response.success) {
                    siteNotify(response.data.message);
                    $('#forgotPasswordModal').modal('hide');
                    $form[0].reset();
                } else {
                    $errorAlert.text(response.data.message).removeClass('d-none');
                    siteNotify(response.data.message, 'error');
                }
            },
            error: function() {
                $errorAlert.text('Lỗi kết nối. Vui lòng thử lại.').removeClass('d-none');
                siteNotify('Lỗi kết nối máy chủ.', 'error');
            },
            complete: function() {
                toggleButtonState($btn, false);
            }
        });
    });

    // ------------------ AVATAR PREVIEW ------------------
    $('#avatar-input').change(function(e) {
        let file = e.target.files[0];
        let reader = new FileReader();
        reader.onload = function(e) { $('#avatar-preview').attr('src', e.target.result); }
        reader.readAsDataURL(file);
    });

    // ------------------ UPDATE PROFILE ------------------
    const profileForm = $('#update-account-form');
    profileForm.submit(function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $(this).find('button[type="submit"]');
        let isValid = true;
        $form.find('.error').removeClass('error');
        $form.find('.error-message').remove();
        let fullName = $form.find('input[name="fullname"]').val().trim();
        let isGenderSelected = $form.find('input[name="gender"]:checked').length > 0;
        let phone = $form.find('input[name="phone_number"]').val().trim();
        if (fullName === '') { isValid = false; $form.find('input[name="fullname"]').addClass('error'); }
        if (!isGenderSelected) { isValid = false; $form.find('input[name="gender"]').closest('.col-sm-9').addClass('error'); }
        if (phone === '') { isValid = false; $form.find('input[name="phone_number"]').addClass('error'); }
        if (!isValid) {
            siteNotify('Vui lòng điền đầy đủ các trường bắt buộc.','error');
            return false;
        }
        let formData = new FormData(this);
        formData.append('nonce',ThemeVars.nonce);
        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            beforeSend: function() {
                toggleButtonState($btn,true); showLoading();
            },
            success: function(response) {
                if (response.success) { siteNotify(response.data.message); }
                else { siteNotify(response.data.message,'error'); }
            },
            error: function(error) {
                siteNotify('Đã có lỗi xảy ra. Vui lòng thử lại sau.','error');
            },
            complete: function() { toggleButtonState($btn,false); hideLoading(); }
        });
    });

    // ------------------ CHANGE PASSWORD ------------------
    const changePasswordForm = $('#change-password-form');
    changePasswordForm.submit(function(e) {
        e.preventDefault();
        let $form = $(this);
        let $btn = $(this).find('button[type="submit"]');
        let isValid = true;
        $form.find('.error').removeClass('error');
        $form.find('.error-message').remove();

        let currentPassword = $form.find('input[name="current_password"]').val();
        let newPassword = $form.find('input[name="new_password"]').val();
        let confirmNewPassword = $form.find('input[name="confirm_new_password"]').val();

        if (currentPassword === '') {
            isValid = false;
            $form.find('input[name="current_password"]').addClass('error');
            siteNotify('Vui lòng nhập mật khẩu hiện tại.', 'error');
        }
        if (newPassword === '') {
            isValid = false;
            $form.find('input[name="new_password"]').addClass('error');
            siteNotify('Vui lòng nhập mật khẩu mới.', 'error');
        }
        if (confirmNewPassword === '') {
            isValid = false;
            $form.find('input[name="confirm_new_password"]').addClass('error');
            siteNotify('Vui lòng xác nhận mật khẩu mới.', 'error');
        }
        if (newPassword !== confirmNewPassword) {
            isValid = false;
            $form.find('input[name="new_password"]').addClass('error');
            $form.find('input[name="confirm_new_password"]').addClass('error');
            siteNotify('Mật khẩu mới và xác nhận mật khẩu không khớp.', 'error');
        }
        if (newPassword.length < 6) {
            isValid = false;
            $form.find('input[name="new_password"]').addClass('error');
            siteNotify('Mật khẩu mới phải có ít nhất 6 ký tự.', 'error');
        }

        if (!isValid) {
            return false;
        }

        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'vcl_change_password',
                nonce: ThemeVars.nonce,
                current_password: currentPassword,
                new_password: newPassword
            },
            beforeSend: function() {
                toggleButtonState($btn, true);
                showLoading();
            },
            success: function(response) {
                if (response.success) {
                    siteNotify(response.data.message);
                    $form[0].reset(); // Clear form fields on success
                    if (response.data.redirect) {
                        setTimeout(function() { window.location.href = response.data.redirect; }, 1000);
                    }
                } else {
                    siteNotify(response.data.message, 'error');
                }
            },
            error: function(error) {
                siteNotify('Đã có lỗi xảy ra. Vui lòng thử lại sau.', 'error');
            },
            complete: function() {
                toggleButtonState($btn, false);
                hideLoading();
            }
        });
    });

    // ------------------ ĐỊA CHỈ ERP - KHỞI TẠO DROPDOWN ĐỘNG ------------------
    let savedShippingLocation = (typeof AccountProfileVars !== 'undefined' && AccountProfileVars.savedShippingLocation) ? AccountProfileVars.savedShippingLocation : '';
    let savedShippingWard = (typeof AccountProfileVars !== 'undefined' && AccountProfileVars.savedShippingWard) ? AccountProfileVars.savedShippingWard : '';
    let savedBillingLocation = (typeof AccountProfileVars !== 'undefined' && AccountProfileVars.savedBillingLocation) ? AccountProfileVars.savedBillingLocation : '';
    let savedBillingWard = (typeof AccountProfileVars !== 'undefined' && AccountProfileVars.savedBillingWard) ? AccountProfileVars.savedBillingWard : '';
    if ($('#shipping_location').length && $('#shipping_ward_new').length) {
        initLocationAndWard($('#shipping_location'), $('#shipping_ward_new'), savedShippingLocation, savedShippingWard);
    }
    if ($('#billing_location').length && $('#billing_ward').length) {
        initLocationAndWard($('#billing_location'), $('#billing_ward'), savedBillingLocation, savedBillingWard);
    }

    // ------------------ CRUD ĐỊA CHỈ SHIPPING (THÊM/SỬA/XÓA/SET MẶC ĐỊNH) ------------------
    const addressListContainer = $('#customer-address-list');
    const noAddressesMessage = $('#no-addresses-message');
    const addressModalElement = document.getElementById('address-modal');
    const addressModal = addressModalElement ? new bootstrap.Modal(addressModalElement) : null;
    const addressForm = $('#address-form');
    const addressModalLabel = $('#addressModalLabel');
    const addressIdInput = $('#address_id');
    const recipientNameInput = $('#recipient_name');
    const recipientPhoneInput = $('#recipient_phone');
    const streetInput = $('#street');
    const setDefaultCheckbox = $('#is_default');
    const saveAddressBtn = $('#btn-save-address');
    const locationSelect = $('#shipping_location');
    const wardSelectNew = $('#shipping_ward_new');
    const formError = $('#address-form-error');
    let currentAddresses = AccountAddressVars.addresses || [];

    function renderAddressList(addresses) {
        addressListContainer.empty();
        if (!addresses || addresses.length === 0) {
            noAddressesMessage.show();
            return;
        }
        noAddressesMessage.hide();
        addresses.forEach(address => {
            const isDefault = address.is_default == true || address.is_default == '1';
            const defaultCardClass = isDefault ? 'border-primary' : '';
            const defaultBadge = isDefault ? '<span class="badge bg-primary ms-2">Mặc định</span>' : '';
            const dataAttributes = `
                data-address-id="${address.id}"
                data-recipient-name="${address.recipient_name || ''}"
                data-recipient-phone="${address.recipient_phone || ''}"
                data-location-name="${address.location_name || ''}"
                data-ward-name="${address.ward_name || ''}"
                data-street="${address.street || ''}"
                data-is-default="${isDefault ? '1' : '0'}"
            `;
            let wardName = address.ward_name || '';
            if (wardName.includes('-')) {
                wardName = wardName.split('-').slice(0, -1).join('-').trim();
            }
            const addressHtml = `
                <div class="card address-card mb-3 ${defaultCardClass}" ${dataAttributes}>
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="fw-bold me-2">${address.recipient_name || ''}</span> |
                                <span class="ms-2 text-muted">${address.recipient_phone || ''}</span>
                                ${defaultBadge}
                            </div>
                            <div>
                                <button type="button" class="btn btn-link btn-sm text-primary p-0 me-2 btn-edit-address" data-address-id="${address.id}" data-bs-toggle="modal" data-bs-target="#address-modal" title="Sửa">Sửa</button>
                                ${!isDefault ? `<button type="button" class="btn btn-link btn-sm text-danger p-0 btn-delete-address" data-address-id="${address.id}" title="Xóa">Xóa</button>` : ''}
                            </div>
                        </div>
                        <p class="card-text text-muted mb-1">
                            ${address.street || ''},
                            ${wardName},
                            ${address.location_name || ''}
                        </p>
                        ${!isDefault ? `<button type="button" class="btn btn-outline-secondary btn-sm mt-2 btn-set-default-address" data-address-id="${address.id}">Đặt làm mặc định</button>` : ''}
                    </div>
                </div>
            `;
            addressListContainer.append(addressHtml);
        });
    }

    function openAddressModal(mode = 'add', address = null) {
        resetAddressForm();
        if (mode === 'add') {
            addressModalLabel.text('Thêm địa chỉ mới');
            addressIdInput.val('');
            setDefaultCheckbox.prop('checked', currentAddresses.length === 0);
        } else if (mode === 'edit' && address) {
            addressModalLabel.text('Chỉnh sửa địa chỉ');
            addressIdInput.val(address.id);
            recipientNameInput.val(address.recipient_name);
            recipientPhoneInput.val(address.recipient_phone);
            streetInput.val(address.street);
            setDefaultCheckbox.prop('checked', address.is_default);
            if (selectOptionByText(locationSelect, address.location_name)) {
                loadWards(address.location_name, function() {
                    locationSelect.trigger('change');
                    setTimeout(() => {
                        selectOptionByText(wardSelectNew, address.ward_name);
                    }, 100);
                });
            } else {
                wardSelectNew.prop('disabled', true);
            }
        }
        if (addressModal) addressModal.show();
    }

    function resetAddressForm() {
        addressForm[0].reset();
        locationSelect.val('');
        wardSelectNew.empty().append($('<option>', { value: '', text: '-- Chọn Phường/Xã --' })).prop('disabled', true);
        formError.hide().text('');
        addressForm.find('.error').removeClass('error');
        addressIdInput.val('');
    }

    $('#btn-add-new-address').on('click', function() {
        openAddressModal('add');
    });

    addressListContainer.on('click', '.btn-edit-address', function() {
        const addressId = $(this).closest('.address-card').data('address-id');
        const addressToEdit = currentAddresses.find(addr => addr.id == addressId);
        if (addressToEdit) openAddressModal('edit', addressToEdit);
        else siteNotify('Không tìm thấy địa chỉ để sửa.', 'error');
    });

    addressListContainer.on('click', '.btn-delete-address', function() {
        const card = $(this).closest('.address-card');
        const addressId = card.data('address-id');
        const recipientName = card.data('recipient-name');
        if (!confirm(`Bạn có chắc chắn muốn xóa địa chỉ của "${recipientName}"?`)) return;
        $.ajax({
            url: AccountAddressVars.ajaxUrl,
            type: 'POST',
            data: { action: 'vcl_delete_address', nonce: AccountAddressVars.deleteNonce, address_id: addressId },
            beforeSend: function() { showLoading(card); },
            success: function(response) {
                if (response.success) {
                    siteNotify(response.data.message || 'Xóa địa chỉ thành công!');
                    currentAddresses = response.data.addresses || [];
                    renderAddressList(currentAddresses);
                } else {
                    siteNotify(response.data.message || 'Xóa địa chỉ thất bại.', 'error');
                }
            },
            error: function() { siteNotify('Lỗi kết nối máy chủ.', 'error'); }
        });
    });

    addressListContainer.on('click', '.btn-set-default-address', function() {
        const $button = $(this);
        const card = $button.closest('.address-card');
        const addressId = card.data('address-id');
        $.ajax({
            url: AccountAddressVars.ajaxUrl,
            type: 'POST',
            data: { action: 'vcl_set_default_address', nonce: AccountAddressVars.setDefaultNonce, address_id: addressId },
            beforeSend: function() { toggleButtonState($button, true); },
            success: function(response) {
                if (response.success) {
                    siteNotify(response.data.message || 'Đặt làm mặc định thành công!');
                    currentAddresses = response.data.addresses || [];
                    renderAddressList(currentAddresses);
                } else {
                    siteNotify(response.data.message || 'Đặt làm mặc định thất bại.', 'error');
                }
            },
            error: function() { siteNotify('Lỗi kết nối máy chủ.', 'error'); }
        });
    });

    addressForm.on('submit', function(e) {
        e.preventDefault();
        formError.text('').addClass('d-none');
        let isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        if (!isValid) {
            formError.text('Vui lòng điền đầy đủ các trường bắt buộc.').removeClass('d-none');
            return;
        }
        const formData = $(this).serializeArray();
        const dataToSend = {
            action: 'vcl_save_address',
            nonce: AccountAddressVars.saveNonce
        };
        formData.forEach(item => {
            dataToSend[item.name] = item.value;
        });
        dataToSend['is_default'] = setDefaultCheckbox.is(':checked') ? '1' : '0';
        $.ajax({
            url: AccountAddressVars.ajaxUrl,
            type: 'POST',
            data: dataToSend,
            beforeSend: function() {
                toggleButtonState(saveAddressBtn, true);
                showLoading(addressForm);
            },
            success: function(response) {
                if (response.success) {
                    siteNotify(response.data.message || 'Lưu địa chỉ thành công!');
                    currentAddresses = response.data.addresses || [];
                    renderAddressList(currentAddresses);
                    if (addressModal) addressModal.hide();
                } else {
                    formError.text(response.data.message || 'Đã có lỗi xảy ra.').removeClass('d-none');
                    siteNotify(response.data.message || 'Lưu địa chỉ thất bại.', 'error');
                }
            },
            error: function() {
                formError.text('Lỗi kết nối. Vui lòng thử lại.').removeClass('d-none');
                siteNotify('Lỗi kết nối máy chủ.', 'error');
            },
            complete: function() {
                toggleButtonState(saveAddressBtn, false);
                hideLoading(addressForm);
            }
        });
    });

    locationSelect.on('change', function() {
        const selectedLocationName = $(this).val();
        wardSelectNew.empty().append('<option value="">-- Chọn Phường/Xã --</option>').prop('disabled', true);
        if (selectedLocationName && locationsData) {
            loadWards(selectedLocationName, function(wards) {
                populateSelect(wardSelectNew, wards, '-- Chọn Phường/Xã --', 'name', 'ward');
            });
        }
    });

    // --------- Khởi tạo lần đầu (render danh sách) ----------
    renderAddressList(currentAddresses);
    //----------------------------------
    $('.order-make-payment').click(function(e){
        e.preventDefault();
        const orderid = $(this).data('orderid');
        if (orderid) {
            $.ajax({
                url: ThemeVars.ajaxurl,
                type: 'POST',
                data: {action: 'order_manual_payment', nonce: ThemeVars.nonce, 'orderid': orderid},
                beforeSend: function () {
                    showLoading();
                },
                success: function (response) {
                    hideLoading();
                    if (response.success) {
                        siteNotify(response.data.message );
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        siteNotify(response.data.message);
                    }
                },
                error: function () {
                    hideLoading();
                    siteNotify('Lỗi kết nối máy chủ.');
                }
            });
        }
    })

    // Handle Cancel Order button click
    $(document).on('click', '.order-make-cancel', function(e) {
        e.preventDefault();
        const orderId = $(this).data('orderid');
        $('#confirmCancelOrderBtn').data('orderid', orderId);
        $('#cancel-reason').val('').removeClass('is-invalid');
        $('#cancel-reason-feedback').text('');
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
        cancelModal.show();
    });

    // Handle Confirm Cancellation button click inside the modal
    $(document).on('click', '#confirmCancelOrderBtn', function(e) {
        e.preventDefault();
        const orderId = $(this).data('orderid');
        const cancelReason = $('#cancel-reason').val().trim();
        const $cancelReasonInput = $('#cancel-reason');
        const $cancelReasonFeedback = $('#cancel-reason-feedback');
        const $confirmBtn = $(this);

        if (cancelReason === '') {
            $cancelReasonInput.addClass('is-invalid');
            $cancelReasonFeedback.text('Vui lòng nhập lý do hủy đơn hàng.').show();
            return;
        } else {
            $cancelReasonInput.removeClass('is-invalid');
            $cancelReasonFeedback.text('').hide();
        }

        $.ajax({
            url: ThemeVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'vcl_cancel_order',
                nonce: ThemeVars.nonce,
                order_id: orderId,
                customer_note: cancelReason
            },
            beforeSend: function() {
                toggleButtonState($confirmBtn, true);
            },
            success: function(response) {
                if (response.success) {
                    siteNotify(response.data.message || 'Đơn hàng đã được hủy thành công.');
                    // Reload the page to reflect the status change
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    siteNotify(response.data.message || 'Không thể hủy đơn hàng.', 'error');
                }
            },
            error: function() {
                siteNotify('Lỗi kết nối máy chủ khi hủy đơn hàng.', 'error');
            },
            complete: function() {
                toggleButtonState($confirmBtn, false);
                const cancelModal = bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal'));
                if (cancelModal) cancelModal.hide();
            }
        });
    });
});
