<?php
/**
 * Template Name: Shoppping Cart / Checkout
 **/
get_header();
?>
<div class="container">
	<div class="post-content bg-white">
        <section class=" bg-white shadow-sm">
	        <?php get_template_part('template-parts/page','title');  ?>
            <div class="shopping-cart-page p-md-4 p-3" id="shoppingCart-page">
                <div class="cartPage-items" id="cartPage-items">

                </div>
                <div class="cartPage-subtotal border-top border-bottom d-flex items-justified-space-between py-2" id="cartPage-subtotal">
                </div>
                <?php 
                if (is_user_logged_in()) {
                ?>
                <div class="cartPage-order-details py-2">
                    <?php $user_id = get_current_user_id();
                    $customer = new Customer($user_id);
                    $customer_info = $customer->get_customer_info();
                    extract($customer_info);
                    $fullname = $customer_info['first_name'] . ' ' . $customer_info['last_name'];
//                    $gender = $customer_info['gender'];
                    $address_to_use = [];
                    if (!empty($shipping_address['address_1'])) {
                        $address_to_use = $shipping_address;
                        $prefill_shipping_phone = $shipping_address['phone'] ?? $phone_number; // Ưu tiên SĐT giao hàng
                    } elseif (!empty($billing_address['address_1'])) {
                        $address_to_use = $billing_address;
                        $prefill_shipping_phone = $billing_address['phone'] ?? $phone_number; // Dùng SĐT thanh toán nếu SĐT giao hàng trống
                    } else {
                        $prefill_shipping_phone = $phone_number; // Dùng SĐT chung nếu cả hai địa chỉ trống
                    }


                    if (!empty($address_to_use)) {
                        $prefill_city = $address_to_use['city'] ?? '';
                        $prefill_district = $address_to_use['state'] ?? ''; // District stored in 'state' field
                        $prefill_ward = $address_to_use['postcode'] ?? ''; // Ward stored in 'postcode' field
                        $prefill_street = $address_to_use['address_1'] ?? '';
                    }
                    ?>
                    <h5><?php _e('Customer Information',LANG_ZONE)  ?></h5>
                    <div class="mb-2 row">
                        <div class="col-sm-6">
                            <label for="inputPassword" class="col-form-label">Giới tính</label>
                            <div class="col-sm-9 col-form-label">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="male" value="male" <?php checked($gender, 'male'); ?>>
                                    <label class="form-check-label" for="male"><?php _e('Male', LANG_ZONE)  ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="female" value="female" <?php checked($gender, 'female'); ?>>
                                    <label class="form-check-label" for="female"><?php _e('Female', LANG_ZONE)  ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4 row">
                        <div class=" col-md-4 col-sm-12">
                            <label for="fullname" class="col-form-label">Họ và Tên</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo esc_attr($fullname); ?>" <?php echo (trim($fullname)!==''?'readonly':'') ?>>
                        </div>
                        <div class=" col-md-4 col-sm-6">
                            <label for="phone_number" class="col-form-label"><?php _e('Phone number', LANG_ZONE)  ?></label>
                            <input type="text" class="form-control" id="phone_number" name="phone_number"  value="<?php echo esc_attr($phone_number); ?>" <?php echo (trim($phone_number)!==''?'readonly':'') ?>>
                        </div>
                        <div class=" col-md-4 col-sm-6">
                            <label for="email" class="col-form-label"><?php _e('Email', LANG_ZONE)  ?></label>
                            <input type="text" class="form-control" name="email" id="email" value="<?php echo esc_attr($user_email); ?>" readonly>
                        </div>
                    </div>
                    <div class="shipping-method-section border-top py-3">
                        <h5 class="mb-2"><?php _e('Shipping method',LANG_ZONE)  ?></h5>
                        <div class=" row shipping-method">
                            <div class="col-sm-6">
                                <div class="col-sm-9 col-form-label">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="delivery" value="delivery" data-title="<?php _e('Delivery to your door', LANG_ZONE)  ?>" checked>
                                        <label class="form-check-label" for="delivery"><?php _e('Delivery to your door', LANG_ZONE)  ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="shipping_method" id="pickup" value="pickup" data-title="<?php _e('Pick up at store', LANG_ZONE)  ?>">
                                        <label class="form-check-label" for="pickup"><?php _e('Pick up at store', LANG_ZONE)  ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-2 row shipping-address" id="shipping-address" data-prefill-city="<?php echo esc_attr($prefill_city); ?>"
                                     data-prefill-district="<?php echo esc_attr($prefill_district); ?>"
                                     data-prefill-ward="<?php echo esc_attr($prefill_ward); ?>">
                                    <div class="form-group col-md-6 mb-2">
                                        <label for="shipping_location">Tỉnh/Thành phố</label>
                                        <select class="form-control" id="shipping_location" name="shipping_location">
                                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                        </select>
                                    </div>


                                    <div class="form-group col-md-6 mb-2">
                                        <label for="shipping_ward_new">Phường/Xã</label>
                                        <select class="form-control" id="shipping_ward_new" name="shipping_ward_new">
                                            <option value="">-- Chọn Phường/Xã --</option>
                                        </select>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label for="street">Địa chỉ cụ thể</label>
                                        <input type="text" class="form-control" id="street" name="shipping_address_street" placeholder="Số nhà, tên đường..." value="<?php echo esc_attr($prefill_street); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="address-search" placeholder="<?php _e('Quick search ...',LANG_ZONE)  ?>">
                                </div>

                                <div id="store-list-container">
                                    <?php
                                    $stores = get_store_list();
                                    if (!empty($stores)) :
                                        echo '<ul class="list-unstyled">';
                                        foreach ($stores as $store) :
                                            echo '<li class="mb-2" data-address="' . esc_attr(strtolower($store['name'].' - '.$store['address'])) . '">';
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="radio" name="selected_store" id="store-' . esc_attr($store['id']) . '" value="' . esc_attr($store['id']) . '">';
                                            echo '<label class="form-check-label" for="store-' . esc_attr($store['id']) . '">';
                                            echo '<strong>'.esc_html($store['name']) . '</strong>: ' . esc_html($store['address']);
                                            echo '</label>';
                                            echo '</div>';
                                            echo '</li>';
                                        endforeach;
                                        echo '</ul>';
                                    else :
                                        echo '<p>Không có cửa hàng nào.</p>';
                                    endif;
                                    ?>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group mb-2">
                                    <label for="order_note">Yêu cầu khác (không bắt buộc)</label>
                                    <input type="text" class="form-control" id="order_note" name="order_note" >
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group mb-2">
                                    <input class="form-check-input" type="checkbox" name="other_recipient" id="other_recipient" >
                                    <label for="other_recipient">Người nhận khác (nếu có)</label>
                                    <div id="other-recipient-info" class="row py-1 px-4" style="display: none;">
                                        <div class=" col-md-12 col-sm-12">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="other_recipient_title" id="other_recipient_title_male" value="Anh">
                                                <label class="form-check-label" for="other_recipient_title_male">Anh</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="other_recipient_title" id="other_recipient_title_female" value="Chị" >
                                                <label class="form-check-label" for="other_recipient_title_female">Chị</label>
                                            </div>
                                        </div>
                                        <div class=" col-md-6 col-sm-12">
                                            <label for="other_recipient_name" class="col-form-label">Họ và Tên</label>
                                            <input type="text" class="form-control" id="other_recipient_name" name="other_recipient_name" >
                                        </div>
                                        <div class=" col-md-6 col-sm-12">
                                            <label for="other_recipient_phone" class="col-form-label">Số điện thoại người nhận</label>
                                            <input type="text" class="form-control" id="other_recipient_phone" name="other_recipient_phone" >
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-2">
                                    <input class="form-check-input" type="checkbox" name="how_to_use" id="how_to_use" >
                                    <label for="how_to_use">Hướng dẫn sử dụng, giải đáp thắc mắc sản phẩm</label>
                                </div>
                                <div class="form-group mb-2">
                                    <input class="form-check-input" type="checkbox" name="company_invoice" id="company_invoice" >
                                    <label for="company_invoice">Xuất hoá đơn công ty</label>
                                    <div id="company-invoice-info" class="row py-1 px-4" style="display: none;">
                                        <div class=" col-md-7 col-sm-12">
                                            <label for="company_name" class="col-form-label">Tên công ty</label>
                                            <input type="text" class="form-control" id="company_name" name="company_name" >
                                        </div>
                                        <div class=" col-md-5 col-sm-12">
                                            <label for="company_tax_number" class="col-form-label">Mã số thuế</label>
                                            <input type="text" class="form-control" id="company_tax_number" name="company_tax_number" >
                                        </div>
                                        <div class=" col-md-12 col-sm-12">
                                            <label for="company_address" class="col-form-label">Địa chỉ</label>
                                            <input type="text" class="form-control" id="company_address" name="company_address" >
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="order_voucher">
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#enterVoucherCode" aria-expanded="false" aria-controls="enterVoucherCode"><i class="bi bi-ticket-detailed"></i> Sử dụng mã giảm giá</button>
                        </div>
                        <div class="col-md-12 collapse" id="enterVoucherCode">
                            <div class="input-group p-3 bg-body-tertiary my-1 rounded">
                                <input type="text" id="input_voucher_code" name="input_voucher_code" class="form-control" placeholder="Nhập mã giảm giá/ Phiếu mua hàng" aria-label="Nhập mã giảm giá/ Phiếu mua hàng" aria-describedby="button-apply-voucher">
                                <button class="btn btn-outline-secondary" type="button" id="button-apply-voucher">Áp dụng</button>
                            </div>

                        </div>
                    </div>
                    <div class="shipping-method-section border-top mt-3 py-3" id="shipping-method">
                        <h5 class="mb-2"><?php _e('Shipping method',LANG_ZONE) ?></h5>
                        <div class="d-flex" id="shipping-method-cost-list">

                        </div>
                    </div>
                    <div class="payment-method-section border-top mt-3 py-3" id="payment-method">
                        <h5 class="mb-2"><?php _e('Payment method',LANG_ZONE) ?></h5>
                        <div class="row payment-method">
                            <div class="col-sm-6">
                                <div class="col-sm-9 col-form-label">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_method_cod" value="cod" data-title="<?php _e('COD', LANG_ZONE) ?>" checked>
                                        <label class="form-check-label" for="payment_method_cod"><?php _e('COD', LANG_ZONE) ?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_method_online" data-title="<?php _e('Payoo', LANG_ZONE)?>" value="payoo" >
                                        <label class="form-check-label" for="payment_method_online"><img src="https://www.payoo.vn/website/static/css/image/payoo-logo.png" style="max-width: 65px;" /></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cartPage-total border-top border-bottom py-3" id="cartPage-total"></div>

                    <div class="col-md-12 my-3">
                        <div class="form-check form-check-inline checkout_privacy_policy">
                            <input class="form-check-input" type="checkbox" name="accept_privacy_policy" id="accept_privacy_policy"  >
                            <label class="form-check-label" for="accept_privacy_policy"><?php $checkout_privacy_policy = get_field('checkout_privacy_policy', 'options');
                                echo do_shortcode($checkout_privacy_policy);
                            ?></label>
                        </div>
                    </div>    
                    <div class="cartPage-checkout row my-5">
                        <div class="col-md-6 text-center">
                            <button type="button" class="btn btn-primary btn-lg w-50" id="btn-checkout">Đặt hàng</button>
                        </div>   
                        <div class="col-md-6 text-center">
                            <button type="button" class="btn btn-outline-primary btn-lg w-50 " id="btn-checkout-with-card">Thanh toán qua thẻ</button>
                        </div> 
                    </div>
                </div>
                <?php } else { ?>
                    <div class="col-md-12 ">
                        <div class="account-box bg-white p-3 content-body">
                            <p >Quý khách vui lòng Đăng nhập để tiếp tục Đặt hàng. </p>
                            <a class="btn btn-primary text-light" role="button"  href="<?php echo get_field('register_and_login','options')?>" >Đăng nhập/Đăng ký</a> 
                        </div>  
                <?php } ?>
            </div>
        </section>
	</div>
</div>

<?php
get_footer(); ?>
