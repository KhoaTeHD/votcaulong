<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Xử lý đăng nhập
add_action('wp_ajax_custom_login', 'custom_login');
add_action('wp_ajax_nopriv_custom_login', 'custom_login');
function custom_login() {
	if ( !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request',LANG_ZONE));
	}
	$email_phone = sanitize_text_field($_POST['email_phone_number']);
	$password = sanitize_text_field($_POST['password']);

	if (is_email($email_phone)) {
		// Đăng nhập bằng email
		$user = get_user_by('email', $email_phone);
	} elseif (preg_match('/^[0-9]{10,15}$/', $email_phone)) {
		// Đăng nhập bằng số điện thoại (giả sử số điện thoại được lưu trong user meta với key 'phone_number')
		$users = get_users(array(
			'meta_key' => 'phone_number',
			'meta_value' => $email_phone,
			'number' => 1,
		));
		$user = !empty($users) ? $users[0] : false;
	} else {
		// Đăng nhập bằng username
		$user = get_user_by('login', $email_phone);
	}

	// Kiểm tra mật khẩu
	if ($user && wp_check_password($password, $user->user_pass)) {
		wp_set_auth_cookie($user->ID);
		wp_send_json_success(array('redirect' => home_url()));
	} else {
		wp_send_json_error(array('message' => __('Incorrect login information.',LANG_ZONE)));
	}
}
//=================
add_action('wp_ajax_custom_register', 'custom_register');
add_action('wp_ajax_nopriv_custom_register', 'custom_register');
function custom_register() {
	if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}

	$email_or_phone = sanitize_text_field($_POST['email_or_phone']);
	$fullname = sanitize_text_field($_POST['fullname']);
	$password = sanitize_text_field($_POST['password']);

	if (empty($email_or_phone) || empty($password)) {
		wp_send_json_error(['message' => __('Email/Phone and password are required.', LANG_ZONE)]);
	}

	// Kiểm tra tồn tại (tùy bà giữ hay bỏ, vẫn nên có)
	$check_result = check_email_or_phone_exists($email_or_phone);
	if ($check_result) {
		wp_send_json_error(['message' => $check_result]);
	}

	// Gửi vào class xử lý toàn bộ
	$new_customer = Customer::create_customer([
		'full_name' => $fullname,
		'user_pass' => $password,
		'email_or_phone' => $email_or_phone,
	]);

	if (is_wp_error($new_customer)) {
		wp_send_json_error(['message' => $new_customer->get_error_message()]);
	}

	wp_set_auth_cookie($new_customer->ID);

	$account_url = get_field('user_account', 'options');
	wp_send_json_success([
		'message' => __('Registration successful! You will be redirected in 5 seconds to complete your information', LANG_ZONE),
		'redirect' => addParamToUrl($account_url, 'edit-profile')
	]);
}
//================================
add_action('wp_ajax_logout', 'logout_callback');
add_action('wp_ajax_nopriv_logout', 'logout_callback');

function logout_callback() {
	wp_logout();
	wp_send_json_success(array('redirect' => home_url()));
}
//================================
add_action('wp_ajax_filter_products', 'filter_products_callback');
add_action('wp_ajax_nopriv_filter_products', 'filter_products_callback');
function filter_products_callback() {
	if ( !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request',LANG_ZONE));
	}
	$filters = $_POST['filters']??[];
	$args=[
	       'limit_start' => 0];
	if( is_array($filters) && count($filters) > 0 ){
		foreach ($filters as $filter_key => $filter_values){
			switch ($filter_key){
				case 'product-sort':
					$args['order_by'] = strtoupper($filter_values);
					break;
				case 'filter_category':
					$args['item_groups'] = $filter_values;
					break;
				case 'SIZE':
					$args['SIZE'] = wp_json_encode($filter_values);
				default:
					$args[$filter_key] = (is_array($filter_values)?wp_json_encode($filter_values):$filter_values);
			}
		}
	}
//	if ($filters['product-sort']){
//		$args['order_by'] = strtoupper($filters['product-sort']);
//	}
//	if ($filters['filter_category']) {
//		$args['item_groups'] = $filters['filter_category'];
//	}
//	if ($filters['SIZE']) {
//		$args['item_groups'] = $filters['filter_category'];
//	}
	$erp_api = new ERP_API_Client();
	$filtered_products = $erp_api->browse_items($args)['data'];
	if (is_wp_error($filtered_products)) {
//		echo $filtered_products->get_error_message(); // Hiển thị thông báo lỗi
		wp_die();
	}

	// Hiển thị sản phẩm đã được lọc
	if (!empty($filtered_products) && is_array($filtered_products) ) {
//        my_debug($filtered_products);
		ob_start(); // Bắt đầu output buffering
		foreach ($filtered_products as $product_info) {
			$product = get_product($product_info['item_code'],0,0);
			if ($product){
				get_template_part('template-parts/product-item','',['product'=>$product]);
			}

		}
		$output = ob_get_clean(); // Lấy HTML từ output buffer
		echo $output;
	} else {
		echo '<p>Không tìm thấy sản phẩm nào.</p>';
	}




	wp_die(); // Important for AJAX
}
//=========================================
add_action('wp_ajax_update_account_info', 'update_account_info_callback');
add_action('wp_ajax_nopriv_update_account_info', 'update_account_info_callback');

function update_account_info_callback() {
	if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}

	if (!is_user_logged_in()) {
		wp_send_json_error(['message' => 'Bạn cần đăng nhập để cập nhật thông tin.']);
	}

	$customer = get_current_customer();

	// Gán & validate
	$fullname     = sanitize_text_field($_POST['fullname']);
	$email        = sanitize_email($_POST['email']);
	$phone_number = sanitize_text_field($_POST['phone_number']);
	$gender       = sanitize_text_field($_POST['gender']);

	$birthdate_day   = sanitize_text_field($_POST['birthdate_day']);
	$birthdate_month = sanitize_text_field($_POST['birthdate_month']);
	$birthdate_year  = sanitize_text_field($_POST['birthdate_year']);

	if (!empty($email)) {
		$result = $customer->update_email($email);
		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}
	}
	if (!empty($fullname))     $customer->update_fullname($fullname);
	if (!empty($phone_number)) $customer->update_phone_number($phone_number);
	if (!empty($gender))       $customer->update_gender($gender);

	if (!empty($birthdate_day) && !empty($birthdate_month) && !empty($birthdate_year)) {
		if (checkdate($birthdate_month, $birthdate_day, $birthdate_year)) {
			$customer->update_birthdate("$birthdate_year-$birthdate_month-$birthdate_day");
		} else {
			wp_send_json_error(['message' => 'Ngày sinh không hợp lệ.']);
		}
	}

	// Avatar
	if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		$attachment_id = media_handle_upload('avatar', 0);
		if (!is_wp_error($attachment_id)) {
			$customer->update_avatar($attachment_id);
		}
	}

	// Địa chỉ
	$billing_address = [
		'company'   => sanitize_text_field($_POST['billing_company'] ?? ''),
		'address_1' => sanitize_text_field($_POST['billing_address_1'] ?? ''),
		'city'      => sanitize_text_field($_POST['billing_location'] ?? ''),
		'ward'      => sanitize_text_field($_POST['billing_ward'] ?? ''),
		'postcode'  => sanitize_text_field($_POST['billing_postcode'] ?? ''),
	];
	$customer->update_billing_address($billing_address);
	$default_addr = $customer->get_default_address();
	if (empty($default_addr)) {
		$default_shipping_address = [
			'recipient_name' => $fullname,
			'recipient_phone' => $phone_number,
			'location_name' => $billing_address['city'],
			'ward_name' => $billing_address['ward'],
			'street' => $billing_address['address_1'],
			'is_default' => 1,
		];
		$customer->add_address($default_shipping_address);
	}

	// Đồng bộ ERP
	$sync = $customer->sync_to_erp();

	wp_send_json_success(['message' => 'Cập nhật thông tin thành công!','sync'=>$sync]);
}
//========================================
add_action('wp_ajax_vcl_change_password', 'vcl_change_password_callback');
add_action('wp_ajax_nopriv_vcl_change_password', 'vcl_change_password_callback');

function vcl_change_password_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
        wp_send_json_error(['message' => __('Invalid request', LANG_ZONE)]);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('You need to be logged in to change your password.', LANG_ZONE)]);
    }

    $user = wp_get_current_user();
    $current_password = sanitize_text_field($_POST['current_password']);
    $new_password = sanitize_text_field($_POST['new_password']);

    if (empty($current_password) || empty($new_password)) {
        wp_send_json_error(['message' => __('Current password and new password are required.', LANG_ZONE)]);
    }

    if (strlen($new_password) < 6) {
        wp_send_json_error(['message' => __('New password must be at least 6 characters long.', LANG_ZONE)]);
    }

    // Verify current password
    if (!wp_check_password($current_password, $user->user_pass, $user->ID)) {
        wp_send_json_error(['message' => __('Current password is incorrect.', LANG_ZONE)]);
    }

    // Update password
    $result = wp_set_password($new_password, $user->ID);

    if (!is_wp_error($result)) {
	    $login_url = get_field('register_and_login','options');
        wp_logout(); // Log out the current user
        wp_send_json_success([
            'message' => __('Password changed successfully! Please log in again with your new password.', LANG_ZONE),
            'redirect' => $login_url // Redirect to login page
        ]);
    } else {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
}
//========================================
add_action('wp_ajax_vcl_forgot_password', 'vcl_forgot_password_callback');
add_action('wp_ajax_nopriv_vcl_forgot_password', 'vcl_forgot_password_callback');

function vcl_forgot_password_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
        wp_send_json_error(['message' => __('Invalid request', LANG_ZONE)]);
    }

    $user_login = sanitize_text_field($_POST['user_login']);

    if (empty($user_login)) {
        wp_send_json_error(['message' => __('Please enter your email address or phone number.', LANG_ZONE)]);
    }

    // Try to get user by email
    $user = get_user_by('email', $user_login);

    // If not found by email, try by phone number (assuming phone is stored in user meta)
    if (!$user && preg_match('/^[0-9]{10,15}$/', $user_login)) {
        $users = get_users(array(
            'meta_key' => 'phone_number',
            'meta_value' => $user_login,
            'number' => 1,
        ));
        $user = !empty($users) ? $users[0] : false;
    }

    if (!$user) {
        wp_send_json_error(['message' => __('No user found with that email address or phone number.', LANG_ZONE)]);
    }

    // Generate password reset key and URL
    $key = get_password_reset_key($user);

    if (is_wp_error($key)) {
        wp_send_json_error(['message' => __('Could not generate reset key. Please try again.', LANG_ZONE)]);
    }

    $reset_link = home_url("/reset-password/?key=$key&login=" . rawurlencode($user->user_login));

    // Send email (This function needs to be defined elsewhere, e.g., in functions.php)
    $sent = vcl_send_reset_password_email($user, $reset_link);

    if ($sent) {
        wp_send_json_success(['message' => __('A password reset link has been sent to your email address.', LANG_ZONE)]);
    } else {
        wp_send_json_error(['message' => __('Failed to send password reset email. Please try again later.', LANG_ZONE)]);
    }
}


//-------------------------------------
add_action('wp_ajax_add_to_wishlist', 'handle_add_to_wishlist');
add_action('wp_ajax_nopriv_add_to_wishlist', 'handle_add_to_wishlist'); 

function handle_add_to_wishlist() {
    if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
        wp_send_json_error(['message' => __('Invalid request', LANG_ZONE)]);
        return;
    }

//    if (!is_user_logged_in()) {
//        wp_send_json_error(['message' => __('Please login!', LANG_ZONE), 'redirect_to_login' => true]);
//        return;
//    }

    if (empty($_POST['product_id'])) {
        wp_send_json_error(['message' => __('Invalid product ID', LANG_ZONE)]);
        return;
    }
	

	$user_id = get_current_user_id();
    $product_id = sanitize_text_field($_POST['product_id']);
    $customer = get_current_customer();
	$product_liked = $customer->getProductLikeTotal($product_id);
    if (like_product($product_id)) {
		$product_liked++;
		
        wp_send_json_success([
            'message' => __('Product has been added to favorites!', LANG_ZONE),
            'count_text' => sprintf(__('Favorite (%d)', LANG_ZONE),$product_liked )
        ]);
    } else {
        wp_send_json_error(['message' => __('Error! Please try again', LANG_ZONE)]);
    }
}

add_action('wp_ajax_remove_wishlist', 'handle_remove_from_wishlist');
// add_action('wp_ajax_nopriv_remove_from_wishlist', 'handle_remove_from_wishlist'); // Tương tự, thường yêu cầu đăng nhập

function handle_remove_from_wishlist() {
    if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
        wp_send_json_error(['message' => __('Invalid request', LANG_ZONE)]);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('Please login!', LANG_ZONE), 'redirect_to_login' => true]);
        return;
    }

    if (empty($_POST['product_id'])) {
        wp_send_json_error(['message' => __('Invalid product ID', LANG_ZONE)]);
        return;
    }

    $product_id = sanitize_text_field($_POST['product_id']);
    $user_id = get_current_user_id();
    $customer = get_current_customer();

    if ($customer->removeFromWishlist($product_id)) {
        wp_send_json_success([
            'message' => __('Product removed from favorites!', LANG_ZONE),
            'wishlist_count' => $customer->getWishlistCount()
        ]);
    } else {
        wp_send_json_error(['message' => __('Error! Please try again', LANG_ZONE)]);
    }
}
//---------------------
function get_product_by_sku_callback(){
	if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}
	$product_sku = $_POST['sku'];
	if (!$product_sku) {
		wp_send_json_error(__('Invalid SKU', LANG_ZONE));
	}
//	$erp_api = new ERP_API_Client();
	$product = get_product($product_sku,1);
//	my_debug($product);
	$return = [];
	if ($product) {
//		$pro = new Product($product);
		$return['html_price'] = $product->getHTMLprice();
		$return['title'] = $product->getTitle();
		$return['sku'] = $product->getSku();
		$return['url'] = $product->getURL();
		$return['sold'] = '';
		$return['discount'] = $product->getDiscount();
		wp_send_json_success($return);
	}
	wp_send_json_error(__('Product not found!',LANG_ZONE));
}
add_action( 'wp_ajax_get_product_by_sku', 'get_product_by_sku_callback' );
add_action( 'wp_ajax_nopriv_get_product_by_sku', 'get_product_by_sku_callback' );
//-----------------------
function compareList_search_callback(){
	if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}
	$search = $_POST['search'];
	$erp_api = new ERP_API_Client();
	if(!$search){
		$products = $erp_api->new_products();
	}else{
		$search_results = $erp_api->search_item( 9,$search);
//		$products = $erp_api->new_products();
		if ($search_results['data']){
			$products = $search_results['data'];
		}
	}
	if($products){
		foreach ($products as $idx => $product){
			$product_ = get_product($product['item_code']);
			$products[$idx]['image_url'] = $product_->getImageUrl();
			$products[$idx]['htmlPrice'] = $product_->getHTMLprice();
			$products[$idx]['text_badge'] = $product_->getTextLabel();
			$products[$idx]['meta_info'] = $product_->itemMetaData();
			$products[$idx]['badge'] = $product_->getBadgeHtml();
			$products[$idx]['discount'] = $product_->getDiscountLabel() ;
			$products[$idx]['url'] = $product_->getURL() ;
			$products[$idx]['title'] = $product['item_name'] ;
			$products[$idx]['sku'] = $product['item_code'] ;
		}
//		my_debug($products);
		wp_send_json_success($products);
	}else{
		wp_send_json_error(__('Product not found!', LANG_ZONE));
	}

}
add_action( 'wp_ajax_compare_list_search', 'compareList_search_callback' );
add_action( 'wp_ajax_nopriv_compare_list_search', 'compareList_search_callback' );
//=============================
function check_user_login() {
	if (is_user_logged_in()) {
		wp_send_json_success();
	} else {
		wp_send_json_error();
	}
}
add_action('wp_ajax_check_user_login', 'check_user_login');
add_action('wp_ajax_nopriv_check_user_login', 'check_user_login');
//-----
// Lấy giỏ hàng từ user meta
function get_user_cart() {
	check_ajax_referer('themetoken-security', 'nonce'); // Kiểm tra bảo mật

	$user_id = get_current_user_id();
	if (!$user_id) {
		wp_send_json_error(['message' => 'User not logged in']);
	}

	$cart = get_user_meta($user_id, '_user_cart', true) ?: [];
	wp_send_json_success(['cart' => $cart]);
}
add_action('wp_ajax_get_user_cart', 'get_user_cart');
add_action('wp_ajax_nopriv_get_user_cart', 'get_user_cart');

// Cập nhật giỏ hàng vào user meta
function update_user_cart() {
	check_ajax_referer('themetoken-security', 'nonce');

	$user_id = get_current_user_id();
	if (!$user_id) {
		wp_send_json_error(['message' => 'User not logged in']);
	}

	$cart = isset($_POST['cart']) ? json_decode(stripslashes($_POST['cart']), true) : [];
	update_user_meta($user_id, '_user_cart', $cart);

	wp_send_json_success(['message' => 'Cart updated']);
}
add_action('wp_ajax_update_user_cart', 'update_user_cart');

//--------------------------------------------
function products_listing_callback(){
	check_ajax_referer('themetoken-security', 'nonce');
	$page = $_POST['page']??1;
	$category = $_POST['cate']??'';
	$erp_api = new ERP_API_Client();
	if ($category) {
		$data = $erp_api->get_products_by_category($category,$page);
		if ($data['products']){
			ob_start(); // Bắt đầu output buffering
			foreach ($data['products'] as $product_info) {
				$product = new Product($product_info);
//			my_debug($product);
				// Sử dụng template hoặc code HTML để hiển thị sản phẩm
				get_template_part('template-parts/product-item','',['product'=>$product]);
			}
			$output = ob_get_clean();
			$pagination = render_pagination($page,$data['total_pages'],2);
			wp_send_json_success(['products'=>$output, 'pagination' =>$pagination]);
		}
	}

}
add_action('wp_ajax_products_listing', 'products_listing_callback');
add_action('wp_ajax_nopriv_products_listing', 'products_listing_callback');

// --- Address Management AJAX Handlers ---

/**
 * AJAX handler for adding or updating a customer address.
 */
function vcl_ajax_save_address() {
    // Verify nonce
    check_ajax_referer('vcl_save_address_nonce', 'nonce');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => __('User not logged in.',LANG_ZONE)]); 
    }

    // Get current customer
    $customer = get_current_customer();
    if (!$customer->ID) {
         wp_send_json_error(['message' => __('Invalid customer.',LANG_ZONE)]);
    }

    // Get data from POST request
    $address_id = isset($_POST['address_id']) ? sanitize_text_field($_POST['address_id']) : '';
    $address_data = [
        'recipient_name' => isset($_POST['recipient_name']) ? sanitize_text_field($_POST['recipient_name']) : '',
        'recipient_phone' => isset($_POST['recipient_phone']) ? sanitize_text_field($_POST['recipient_phone']) : '',
        'location_name' => isset($_POST['location_name']) ? sanitize_text_field($_POST['location_name']) : '', // Expecting combined name
        'ward_name' => isset($_POST['ward_name']) ? sanitize_text_field($_POST['ward_name']) : '',
        'street' => isset($_POST['street']) ? sanitize_text_field($_POST['street']) : '',
        'is_default' => isset($_POST['is_default']) && $_POST['is_default'] == '1' ? 1 : 0, // Check value '1'
    ];

    // Basic validation (add more specific validation as needed)
    if (empty($address_data['recipient_name']) || empty($address_data['recipient_phone']) || empty($address_data['location_name']) || empty($address_data['ward_name']) || empty($address_data['street'])) {
        wp_send_json_error(['message' => 'Vui lòng điền đầy đủ thông tin địa chỉ.']); // Please fill in all address fields.
    }

    $result = false;
    $new_address_id = null;

    if (empty($address_id)) {
        // Add new address
        $result = $customer->add_address($address_data);
        if ($result) {
            $new_address_id = $result; // add_address returns the new ID on success
        }
    } else {
        // Update existing address
        $result = $customer->update_address($address_id, $address_data);
    }

    if ($result !== false) {
        // Get updated list of addresses to send back
        $updated_addresses = $customer->get_addresses();
        // Ensure it's a plain array for JSON
        $updated_addresses_array = !empty($updated_addresses) ? array_values($updated_addresses) : [];

        wp_send_json_success([
            'message' => empty($address_id) ? 'Thêm địa chỉ thành công!' : 'Cập nhật địa chỉ thành công!', // Address added/updated successfully!
            'addresses' => $updated_addresses_array, // Send back the full updated list
            'new_address_id' => $new_address_id // Send the new ID if adding
        ]);
    } else {
        wp_send_json_error(['message' => 'Đã có lỗi xảy ra khi lưu địa chỉ. Vui lòng thử lại.']); // An error occurred while saving the address. Please try again.
    }
}
add_action('wp_ajax_vcl_save_address', 'vcl_ajax_save_address'); // Action name matches JS

/**
 * AJAX handler for deleting a customer address.
 */
function vcl_ajax_delete_address() {
    // Verify nonce
    check_ajax_referer('vcl_delete_address_nonce', 'nonce');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

    // Get customer
    $customer = get_current_customer();
     if (!$customer->ID) {
         wp_send_json_error(['message' => 'Invalid customer.']);
    }

    // Get address ID from POST
    $address_id = isset($_POST['address_id']) ? sanitize_text_field($_POST['address_id']) : '';

    if (empty($address_id)) {
        wp_send_json_error(['message' => 'Thiếu ID địa chỉ.']); // Missing address ID.
    }

    // Attempt to delete
    $result = $customer->delete_address($address_id);

    if ($result) {
         // Get updated list of addresses to send back
        $updated_addresses = $customer->get_addresses();
        $updated_addresses_array = !empty($updated_addresses) ? array_values($updated_addresses) : [];

        wp_send_json_success([
            'message' => 'Xóa địa chỉ thành công!', // Address deleted successfully!
            'addresses' => $updated_addresses_array // Send back the updated list
        ]);
    } else {
        // Check if it failed because it was the last default address
        $addresses = $customer->get_addresses();
        if (isset($addresses[$address_id]) && !empty($addresses[$address_id]['is_default']) && count($addresses) <= 1) {
             wp_send_json_error(['message' => 'Không thể xóa địa chỉ mặc định cuối cùng.']); // Cannot delete the last default address.
        } else {
             wp_send_json_error(['message' => 'Đã có lỗi xảy ra khi xóa địa chỉ. Vui lòng thử lại.']); // An error occurred while deleting the address. Please try again.
        }
    }
}
add_action('wp_ajax_vcl_delete_address', 'vcl_ajax_delete_address'); // Action name matches JS

/**
 * AJAX handler for setting a default customer address.
 */
function vcl_ajax_set_default_address() {
    // Verify nonce
    check_ajax_referer('vcl_set_default_address_nonce', 'nonce');

    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.']);
    }

     // Get customer
	 $customer = get_current_customer();
     if (!$customer->ID) {
         wp_send_json_error(['message' => 'Invalid customer.']);
    }

    // Get address ID from POST
    $address_id = isset($_POST['address_id']) ? sanitize_text_field($_POST['address_id']) : '';

    if (empty($address_id)) {
        wp_send_json_error(['message' => 'Thiếu ID địa chỉ.']); // Missing address ID.
    }

    // Attempt to set default
    $result = $customer->set_default_address($address_id);

    if ($result) {
        // Get updated list of addresses to send back
        $updated_addresses = $customer->get_addresses();
        $updated_addresses_array = !empty($updated_addresses) ? array_values($updated_addresses) : [];

        wp_send_json_success([
            'message' => 'Đặt địa chỉ mặc định thành công!', // Default address set successfully!
            'addresses' => $updated_addresses_array // Send back the updated list
        ]);
    } else {
        wp_send_json_error(['message' => 'Đã có lỗi xảy ra khi đặt địa chỉ mặc định. Vui lòng thử lại.']); // An error occurred while setting the default address. Please try again.
    }
}
add_action('wp_ajax_vcl_set_default_address', 'vcl_ajax_set_default_address'); // Action name matches JS

function vcl_live_search_callback() {
    // Verify nonce
    check_ajax_referer('themetoken-security', 'nonce');

    $results = [];
    $query = isset($_POST['search_query']) ? sanitize_text_field(trim($_POST['search_query'])) : '';

    if (empty($query) || strlen($query) < 3) {
        wp_send_json_error(__('Search keyword is too short.',LANG_ZONE));
        wp_die();
    }
	$erp = new ERP_API_Client();
	$products_result = $erp->search_item(5,$query);
	$products = $post_searchs = [];
	if ($products_result['data']){
		
		foreach ($products_result['data'] as $product){
			$this_product = get_product($product['item_code']);
			//my_debug($this_product );
			$products[] = [
				'id' => $this_product->getId(),
				'title' => $this_product->getTitle(),
				'url' => $this_product->getURL(),
				'price' => $this_product->getHTMLprice(),
				'thumbnail' => $this_product->getImageUrl(),
				'type' => 'product',
			];
		}
	}
    // --- Example Query: Search Posts and Products ---
    $args = array(
        'post_type'      => array('post'), // Search in posts and products
        'posts_per_page' => 10,                      // Limit number of results
        's'              => $query,                  // The search keyword
        'post_status'    => 'publish',
    );
    $search_query = new WP_Query($args);

    if ($search_query->have_posts()) {
        while ($search_query->have_posts()) {
            $search_query->the_post();
            $post_id = get_the_ID();
            $post_type_obj = get_post_type_object(get_post_type());

            $post_searchs[] = array(
                'id'        => $post_id,
                'title'     => get_the_title(),
                'url'       => get_permalink(),
                'thumbnail' => has_post_thumbnail() ? get_the_post_thumbnail_url($post_id, 'thumbnail') : '', // Get thumbnail URL
                'type'      => $post_type_obj ? $post_type_obj->labels->singular_name : '', // Get post type name (e.g., "Bài viết", "Sản phẩm")
            );
        }
        wp_reset_postdata(); // Reset post data
    }
	$results = array_merge($products,$post_searchs); 
	if ($results){
		wp_send_json_success($results);
	}	else {
        wp_send_json_error(__('No results found.', LANG_ZONE));
    }

    wp_die(); // Required to terminate immediately and return a proper response
}
add_action('wp_ajax_live_search', 'vcl_live_search_callback');
add_action('wp_ajax_nopriv_live_search', 'vcl_live_search_callback');

//--------------------- Save search keyword -------------
add_action('wp_ajax_add_search_keyword', 'add_search_keyword');
add_action('wp_ajax_nopriv_add_search_keyword', 'add_search_keyword');
function add_search_keyword() {
	check_ajax_referer('themetoken-security', 'nonce');
	if (!empty($_POST['keyword'])) {
		$keyword = trim(($_POST['keyword']));
		if (strlen($keyword) > 2 && preg_match('/[a-zA-Z0-9áàảãạăâđêôơưóòỏõọúùủũụíìỉĩịéèẻẽẹýỳỷỹỵ\s]+/u', $keyword)) {
			$kw = new Keyword_Manager();
			$kw->increment($keyword);
		}
	}
	wp_die();
}
//----------------- Compare products ---------------------//
add_action('wp_ajax_load_product_comparison', 'load_CompareProducts');
add_action('wp_ajax_nopriv_load_product_comparison', 'load_CompareProducts');

function load_CompareProducts(){
	check_ajax_referer('themetoken-security', 'nonce');
	if (empty($_POST['product_skus'])) {wp_send_json_error(['message' => __('Invalid products!',LANG_ZONE)]);}
	$product_skus = ($_POST['product_skus']);
	$skus_array = $_POST['product_skus'];

	$comparison_data = array();

	foreach ($skus_array as $sku) {
		$sku = trim($sku);
		if (!empty($sku)) {
			$product_data = get_product_data_by_sku($sku);
			if ($product_data) {
				$comparison_data[] = prepare_product_for_comparison($product_data);
			}
		}
	}

	wp_send_json_success($comparison_data);

}
//----
function get_product_data_by_sku($sku) {
	// This function should be customized based on your data source
	$erp = new ERP_API_Client();
	$product = $erp->get_product($sku);
	return $product;
}
// Prepare product data for comparison
function prepare_product_for_comparison($product_data) {
	$this_product = new Product($product_data);
	$comparison_product = array(
		'basic_info' => array(
			'name' => $product_data['name'],
			'item_code' => $product_data['item_code'],
			'item_name' => $product_data['item_name'],
			'brand' => $product_data['brand'],
			'item_group' => $product_data['item_group'],
			'description' => $product_data['description'],
			'image_url' => $this_product->getImageUrl(),
			'price' => $this_product->getHTMLprice()
		),
		'attributes' => array(),
		'specifications' => $product_data['specification']
	);

	// Combine all attributes from variants
	if (isset($product_data['data_variants']) && is_array($product_data['data_variants'])) {
		$combined_attributes = array();

		foreach ($product_data['data_variants'] as $variant) {
			if (isset($variant['attributes']) && is_array($variant['attributes'])) {
				foreach ($variant['attributes'] as $attr) {
					$attr_name = $attr['attribute'];
					$attr_value = $attr['attribute_value'];

					if (!isset($combined_attributes[$attr_name])) {
						$combined_attributes[$attr_name] = array();
					}

					if (!in_array($attr_value, $combined_attributes[$attr_name])) {
						$combined_attributes[$attr_name][] = $attr_value;
					}
				}
			}
		}

		$comparison_product['attributes'] = $combined_attributes;
	}

	return $comparison_product;
}
// Get all unique comparison fields from products
function get_comparison_fields($products) {
	$all_attributes = array();
	$all_specifications = array();

	foreach ($products as $product) {
		// Collect attributes
		if (isset($product['attributes'])) {
			foreach ($product['attributes'] as $attr_name => $attr_values) {
				if (!in_array($attr_name, $all_attributes)) {
					$all_attributes[] = $attr_name;
				}
			}
		}

		// Collect specifications
		if (isset($product['specifications'])) {
			foreach ($product['specifications'] as $spec_name => $spec_value) {
				if (!in_array($spec_name, $all_specifications)) {
					$all_specifications[] = $spec_name;
				}
			}
		}
	}

	return array(
		'attributes' => $all_attributes,
		'specifications' => $all_specifications
	);
}

// AJAX handler for brand quick search
function vcl_brand_quick_search() {
	// Verify nonce
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vcl_brand_search_nonce')) {
		wp_send_json_error('Nonce verification failed', 403);
		return;
	}

	// Sanitize search term
	$search_term = sanitize_text_field($_POST['s']);

	if (empty($search_term)) {
		wp_die();
	}

	$args = array(
		'post_type'      => 'brands',
		'posts_per_page' => 10, // Limit results for a dropdown
		's'              => $search_term,
		'post_status'    => 'publish',
	);

	$query = new WP_Query($args);

	if ($query->have_posts()) {
		$output = '<ul>';
		while ($query->have_posts()) {
			$query->the_post();

			// Get thumbnail or a placeholder
			if (has_post_thumbnail()) {
				$thumbnail = get_the_post_thumbnail(get_the_ID(), array(40, 40));
			} else {
				$placeholder_img_src = IMG_URL . 'No_Image_Available.jpg';
				$thumbnail = '<img src="' . esc_url($placeholder_img_src) . '" alt="' . get_the_title() . '" style="width:40px; height:40px; object-fit:cover;">';
			}

			$output .= '<li>';
			$output .= '<a href="' . get_permalink() . '">';
			$output .= '<div class="result-item">';
			$output .= '<div class="result-thumbnail">' . $thumbnail . '</div>';
			$output .= '<div class="result-title">' . get_the_title() . '</div>';
			$output .= '</div>';
			$output .= '</a>';
			$output .= '</li>';
		}
		$output .= '</ul>';
		echo $output;
	} else {
		echo '<ul><li>' . __('No brands found', 'votcaulong-shop') . '</li></ul>';
	}

	wp_die(); // this is required to terminate immediately and return a proper response
}
add_action('wp_ajax_vcl_brand_quick_search', 'vcl_brand_quick_search');
add_action('wp_ajax_nopriv_vcl_brand_quick_search', 'vcl_brand_quick_search');

//========================================
add_action('wp_ajax_vcl_share_cart', 'vcl_share_cart_callback');
add_action('wp_ajax_nopriv_vcl_share_cart', 'vcl_share_cart_callback');

function vcl_share_cart_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
        wp_send_json_error(['message' => __('Invalid request', LANG_ZONE)]);
    }

    $cart_data_json = isset($_POST['cart_data']) ? stripslashes($_POST['cart_data']) : '';
    $cart_data = json_decode($cart_data_json, true);

    if (empty($cart_data) || !is_array($cart_data)) {
        wp_send_json_error(['message' => __('Invalid cart data.', LANG_ZONE)]);
    }

    // Generate a unique ID for the cart
    $cart_id = uniqid('cart_');

    // Save the cart data as a transient (expires in 1 day)
    set_transient('vcl_shared_cart_' . $cart_id, $cart_data, DAY_IN_SECONDS);

    // Construct the shareable URL
    $share_url = home_url('/share-cart/' . $cart_id);

    wp_send_json_success([
        'message' => __('Your cart has been shared!', LANG_ZONE),
        'share_url' => $share_url
    ]);
}