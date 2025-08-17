<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action('wp_ajax_get_cart_products', 'handle_get_cart_products');
add_action('wp_ajax_nopriv_get_cart_products', 'handle_get_cart_products');

function handle_get_cart_products() {
	if (!isset($_POST['cart']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request',LANG_ZONE));
	}

	$cart = json_decode(stripslashes($_POST['cart']), true);
	$select_order_rule = $_POST['order_rules']?? null;
	$voucher_code = $_POST['voucher_code'] ?? null;
	$redeem_point = $_POST['redeem_point'] ?? 0;

	$erp_api = new ERP_API_Client();
	$products = [];
	$order_rules = [];

	$html_output = '';
	$subtotal = $var_price = 0;
	$item_counter = 0;
	$pricing_rule_data =[];
	$check_order_data = [];
	$test_subtotal = [];
	if (is_user_logged_in()){
		$user_id = get_current_user_id();
		$customer = new Customer($user_id);
		$customer_info = $customer->get_customer_info();
		extract($customer_info);
		$fullname = $customer_info['first_name'] . ' ' . $customer_info['last_name'];
//                    $gender = $customer_info['gender'];
		$address_to_use = $customer_info['shipping_address'];


		if (!empty($address_to_use)) {
			$prefill_city = $address_to_use['city'] ?? '';
			$prefill_district = $address_to_use['state'] ?? ''; // District stored in 'state' field
			$prefill_ward = $address_to_use['postcode'] ?? ''; // Ward stored in 'postcode' field
			$prefill_street = $address_to_use['address_1'] ?? '';
			$prefill_phone = $address_to_use['phone'] ?? '';
		}
		if ($customer_info['erp_name']) {
			$check_order_data = [
				"customer"         => $customer_info['erp_name'],
				"phone"            => $default_shipping_address['recipient_phone'] ?? $prefill_phone,
				"address_title"    => $fullname,
				"address_line1"    => $default_shipping_address['street'] ?? $prefill_street,
				"address_location" => $default_shipping_address['location_name'] ?? $prefill_city,
				"ward"             => $default_shipping_address['ward_name'],
			];
			foreach ($cart as $item) {
				$product_id = $item['sku'];
				$product = $erp_api->get_product($product_id);
				$item_code = ($product['has_variants']?$item['id']:$item['sku']);
				$pricing_rule_data[] = ['item_code'=>(string)$item_code,'qty'=>$item['quantity']];
				if ($product) {
					unset($product['description']);
					if ($product['data_variants']){
						foreach ($product['data_variants'] as $variant){
							if ($variant['item_code']==$item['selected']){
								$product['variation'] = $variant;
								$var_price = $variant['rate'];
								break;
							}
						}
					}else{
						$var_price = 0;
					}

					$product['attributes'] = $item['attributes'];
					$product['quantity'] = $item['quantity'];
					$products[$item_code] = $product;
					$item_counter += $item['quantity'];
					$subtotal += $item['quantity'] * max($var_price,$product['price']);
				}
			}
			if (count($pricing_rule_data)){
				$cart_pricing_rule = $erp_api->get_pricing_rule(['items'=>$pricing_rule_data]);
				if (!is_wp_error($cart_pricing_rule) && isset($cart_pricing_rule['item_rules'])){
					foreach($pricing_rule_data as $idx => $_item){
						$item_code = $_item['item_code'];
						if (isset($cart_pricing_rule['item_rules'][$item_code]) && is_array($cart_pricing_rule['item_rules'][$item_code])){
							$pricing_rule_data[$idx]['pricing_rule'] =$cart_pricing_rule['item_rules'][$item_code][0]['rules']['pricing_rule'];
							$products[$item_code]['select_pricing_rules'] = $cart_pricing_rule['item_rules'][$item_code][0]['rules'];
						}

					}
					if (count($cart_pricing_rule['order_rules'])){
						$order_rules = $cart_pricing_rule['order_rules'][0]['rules'];
						$default_order_rules =$order_rules[0]['pricing_rule'];
					}
				}
			}
//			my_debug($cart_pricing_rule);
			//my_debug($products);
//			exit;
			$check_order_data['pricing_rule'] = $select_order_rule??$default_order_rules;
			$check_order_data['items'] = $pricing_rule_data;
			$check_order = $erp_api->make_sales_order($check_order_data);

		}
	}else{ // just basic cart
		$subtotal = 0;
		foreach ($cart as $item) {
			$product_id = $item['sku'];
//			$item_code = ($product['has_variants']?$item['id']:$item['sku']);
			$product = $erp_api->get_product($product_id);
			if ($product) {
				if ($product['data_variants']){
					foreach ($product['data_variants'] as $variant){
						if ($variant['item_code']==$item['selected']){
							$product['variation'] = $variant;
							$var_price = $variant['rate'];
							break;
						}
					}
				}else{
					$var_price = 0;
				}
//			my_debug($product);
//			$product['variation'] = $product['variants'];
				$product['attributes'] = $item['attributes'];
				$product['quantity'] = $item['quantity'];
				$products[$product_id] = $product;
				$item_counter += $item['quantity'];
				$subtotal += $item['quantity'] * max($var_price,$product['price']);

			}
		}
	}




	foreach($products as $product_id => $product){
		ob_start();

		get_template_part('template-parts/cart', 'item', ['product' => $product]);
		$html_output .= ob_get_clean();
	}
//	my_debug($products);


	$total_saved = $order_total = 0;
	$reward_point = 0;
	if (!is_wp_error($check_order)){
		$total_saved = -$check_order['discount_amount'];
		$order_total = $check_order['grand_total'];
	}


	$subtotal_html = '<div class="subtotal-label">'.sprintf(__('Sub-total [%s product(s)]', LANG_ZONE), $item_counter).': </div>
                    <div class="subtotal-value" data-value="'.$subtotal.'">'.priceFormater($subtotal).'</div>';

	$total_html = '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Promotion', LANG_ZONE).': </div>
					<div class="cartpageTotal-value orderPromotions promotionValue" data-value="'.abs($total_saved).'">'.priceFormater($total_saved).' </div></div>';

	$total_html .= '<div class="cartpageTotal-line shipping-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Shipping', LANG_ZONE).': <span class="shipping-method-name"></span></div>
					<div class="cartpageTotal-value" data-value="">'.priceFormater(0).' </div></div>';
	$total_html .= '<div class="cartpageTotal-line total-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Total', LANG_ZONE).': </div>
					<div class="cartpageTotal-value">'.priceFormater($order_total).' </div></div>';



	if ($reward_point) {
		$total_html .= '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">' . __( 'Reward points', LANG_ZONE ) . ': </div>
					<div class="cartpageTotal-value">' . number_format_i18n( $reward_point ) . ' </div></div>';
	}
	wp_send_json_success([
		'order_check' => $check_order_data,
		'products' => $products,
		'order_rules' => $order_rules,
		'html' => $html_output,
		'subtotal_html' =>$subtotal_html,
		'total_html' => $total_html,
		'subtest' =>$check_order
	]);
}
//---------------------
add_action('wp_ajax_refresh_checkout', 'handle_refresh_checkout');
function handle_refresh_checkout(){
	if (!isset($_POST['order_data']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request',LANG_ZONE));
	}
	$orderData = $_POST['order_data'];

	$cart = $orderData['cart'];
	$select_order_rule = $orderData['order_rule']?? '';
	$voucher_code = $orderData['voucher_code'] ?? '';
	$redeem_point = $orderData['loyalty_point'] ?? 0;
	$delivery_fee = $orderData['shipping_total'] ?? 0;
	$item_rule_selected = $orderData['items_rule']?? [];

	$erp_api = new ERP_API_Client();
	$products = [];
	$order_rules = [];

	$html_output = '';
	$subtotal = $var_price = 0;
	$item_counter = 0;
	$pricing_rule_data =[];
	$check_order_data = [];
	$customer_data = $orderData['customer_info'];
	if (is_user_logged_in()){
		$user_id = get_current_user_id();
		$customer = new Customer($user_id);
		$customer_info = $customer->get_customer_info();
		extract($customer_info);
		$fullname = $customer_info['first_name'] . ' ' . $customer_info['last_name'];


		if ($customer_info['erp_name']) {
			$check_order_data = [
				"customer"         => $customer_info['erp_name'],
			];
			foreach ($cart as $item) {
				$product_id = $item['sku'];
				$product = $erp_api->get_product($product_id);
				$item_code = ($product['has_variants']?$item['id']:$item['sku']);
				$pricing_rule_data[] = ['item_code'=>(string)$item_code,'qty'=>$item['quantity']];
				$selected_rule = '';
				if ($product) {
					if ($product['data_variants']){
						foreach ($product['data_variants'] as $variant){
							if ($variant['item_code']==$item['selected']){
								$product['variation'] = $variant;
								$var_price = $variant['rate'];
								break;
							}
						}
					}else{
						$var_price = 0;
					}
					foreach ($item_rule_selected as $rule) {
						if ($rule['item_code'] === $item_code) {
							$selected_rule = $rule['pricing_rule'];
							break;
						}
					}
					$product['attributes'] = $item['attributes'];
					$product['quantity'] = $item['quantity'];
					$product['selected_rule'] = $selected_rule;
					$products[$item_code] = $product;
					$item_counter += $item['quantity'];
					$subtotal += $item['quantity'] * max($var_price,$product['price']);

				}
			}
			if (count($pricing_rule_data)){
				$cart_pricing_rule = $erp_api->get_pricing_rule(['items'=>$pricing_rule_data]);
				if (!is_wp_error($cart_pricing_rule) && isset($cart_pricing_rule['item_rules'])){
					foreach($pricing_rule_data as $idx => $_item){
						$item_code = $_item['item_code'];
						if (isset($cart_pricing_rule['item_rules'][$item_code]) && is_array($cart_pricing_rule['item_rules'][$item_code])){
							$pricing_rule_data[$idx]['pricing_rule'] =$cart_pricing_rule['item_rules'][$item_code][0]['rules']['pricing_rule']??'';
							$products[$item_code]['select_pricing_rules'] = $cart_pricing_rule['item_rules'][$item_code][0]['rules'];
						}

					}
					if (count($cart_pricing_rule['order_rules'])){
						$order_rules = $cart_pricing_rule['order_rules'][0]['rules'];
						$default_order_rules =$order_rules[0]['pricing_rule'];
					}
				}
			}
//			my_debug($cart_pricing_rule['order_rules'][0]['rules']);
			$check_order_data['pricing_rule'] = $select_order_rule??$default_order_rules;
			$check_order_data['items'] = $pricing_rule_data;
			$check_order_data['delivery_fee'] = $delivery_fee??0;
			$check_order_data['coupon_code'] = (string)$voucher_code;
			$check_order_data['loyalty_point'] = (int)$redeem_point;

			$check_order = $erp_api->make_sales_order($check_order_data);
			if(is_wp_error($check_order)){
				$error_msg = handle_api_error_message($check_order->get_error_message());
				wp_send_json_error($error_msg);

			}

		}
	}else{ // just basic cart
		foreach ($cart as $item) {
			$product_id = $item['id'];
			$product = $erp_api->get_product($product_id);
			if ($product) {
				if ($product['data_variants']){
					foreach ($product['data_variants'] as $variant){
						if ($variant['item_code']==$item['selected']){
							$product['variation'] = $variant;
							$var_price = $variant['rate'];
							break;
						}
					}
				}else{
					$var_price = 0;
				}
//			my_debug($product);
//			$product['variation'] = $product['variants'];
				$product['attributes'] = $item['attributes'];
				$product['quantity'] = $item['quantity'];
				$products[$product_id] = $product;
				$item_counter += $item['quantity'];
				$subtotal += $item['quantity'] * max($var_price,$product['price']);

			}
		}
	}

	foreach($products as $product_id => $product){
		ob_start();
		get_template_part('template-parts/cart', 'item', ['product' => $product]);
		$html_output .= ob_get_clean();
	}
//	my_debug($products);


	$total_saved = $order_total = 0;
	$reward_point = 0;
	$loyalty_amount = 0;
	if (!is_wp_error($check_order)){
		$total_saved = - $check_order['discount_amount'];
		$order_total = $check_order['grand_total'];
		$loyalty_amount = -$check_order['loyalty_amount'];
	}
	$subtotal_html = '<div class="subtotal-label">'.sprintf(__('Sub-total [%s product(s)]', LANG_ZONE), $item_counter).': </div>
                    <div class="subtotal-value" data-value="'.$subtotal.'">'.priceFormater($subtotal).'</div>';

	$total_html = '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Promotion', LANG_ZONE).': </div>
					<div class="cartpageTotal-value orderPromotions promotionValue" data-value="'.abs($total_saved).'">'.priceFormater($total_saved).' </div></div>';
	if($loyalty_amount) {
		$total_html .= '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">' . __( 'Loyalty redeem', LANG_ZONE ) . ' (' . $redeem_point . '): </div>
					<div class="cartpageTotal-value orderPromotions loyaltyValue" data-value="' . abs($loyalty_amount) . '">' . priceFormater( $loyalty_amount ) . ' </div></div>';
	}
	$total_html .= '<div class="cartpageTotal-line shipping-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Shipping', LANG_ZONE).': <span class="shipping-method-name"></span></div>
					<div class="cartpageTotal-value" data-value="'.$delivery_fee.'">'.priceFormater($delivery_fee).' </div></div>';
	$total_html .= '<div class="cartpageTotal-line total-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Total', LANG_ZONE).': </div>
					<div class="cartpageTotal-value">'.priceFormater($order_total).' </div></div>';
	if ($reward_point) {
		$total_html .= '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">' . __( 'Reward points', LANG_ZONE ) . ': </div>
					<div class="cartpageTotal-value">' . number_format_i18n( $reward_point ) . ' </div></div>';
	}
	wp_send_json_success([
		'order_check' => $check_order_data,
		'products' => $products,
		'order_rules' => $order_rules,
		'html' => $html_output,
		'subtotal_html' =>$subtotal_html,
		'total_html' => $total_html,
		'check_order' => $check_order,
	]);
}
function handle_api_error_message($api_message) {
	if (strpos($api_message, "Coupon code") !== false) {
		return __('Invalid coupon code. Please check again.', LANG_ZONE);
	}

	if (strpos($api_message, "Insufficient loyalty points") !== false) {
//		preg_match('/Requested: (\d+\.?\d*)/', $api_message, $matches);
//		$requested_points = isset($matches[1]) ? (float) $matches[1] : 0;

		// Sử dụng hàm sprintf để chèn giá trị vào chuỗi dịch
		return __('Insufficient loyalty points.', LANG_ZONE);
	}

	// Trường hợp lỗi chung
	return __('An unknown error occurred. Please try again.', LANG_ZONE);
}

//=================================================

/**
 * Handles the AJAX request for placing a Cash on Delivery (COD) order.
 */
function handle_process_cod_order() {
	// 1. Verify Nonce
	// Use 'vcl_checkout_nonce' which should be sent from your checkout JavaScript
	check_ajax_referer( 'vcl_checkout_nonce', 'nonce' );

	// 2. Get and Decode Data
	$order_data_json = isset( $_POST['order_data'] ) ? wp_unslash( $_POST['order_data'] ) : '';
	if ( empty( $order_data_json ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid order data.', 'votcaulong-shop' ) ) );
		return;
	}

	$order_data = json_decode( $order_data_json, true ); // Decode JSON into PHP array

	// Check for JSON decoding errors
	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $order_data ) ) {
		error_log("Checkout AJAX Error: Invalid JSON received. Data: " . $order_data_json);
		wp_send_json_error( array( 'message' => __( 'Error processing order data.', 'votcaulong-shop' ) ) );
		return;
	}

	// 3. Server-side Validation
	// Basic customer info
	if ( empty( $order_data['customer_info']['fullname'] ) || empty( $order_data['customer_info']['phone_number'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please provide full name and phone number.', 'votcaulong-shop' ) ) );
		return;
	}
	// --- Split fullname into first and last name ---
	$fullname = trim($order_data['customer_info']['fullname']);
	$space_pos = strpos($fullname, ' ');
	if ($space_pos === false) {
		// Only one name provided, use it as first name
		$first_name = $fullname;
		$last_name = ''; // Or you could repeat the first name if required
	} else {
		$first_name = trim(substr($fullname, 0, $space_pos));
		$last_name = trim(substr($fullname, $space_pos + 1));
	}
	// Add the split names to the customer_info array
	$order_data['customer_info']['first_name'] = $first_name;
	$order_data['customer_info']['last_name'] = $last_name;
	// --- End splitting name ---
	$recipient_phone = $order_data['customer_info']['phone_number'];
	$recipient_name = $order_data['customer_info']['fullname'];
	$recipient_gender = $order_data['customer_info']['gender'];

	// Shipping address validation (only if delivery method is chosen)
	if ( isset($order_data['shipping_method']) && $order_data['shipping_method'] === 'delivery' ) {
		if ( empty( $order_data['shipping_address']['city_code'] ) || empty( $order_data['shipping_address']['ward_code'] ) || empty( $order_data['shipping_address']['street'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide the complete delivery address.', 'votcaulong-shop' ) ) );
			return;
		}
	}
	// Pickup store validation (only if pickup method is chosen)
	elseif ( isset($order_data['shipping_method']) && $order_data['shipping_method'] === 'pickup' ) {
		if ( empty( $order_data['pickup_store_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a pickup store.', 'votcaulong-shop' ) ) );
			return;
		}
		$pickup_store = get_the_title( $order_data['pickup_store_id'] );
	}
	// Handle case where shipping method might be missing (though frontend should prevent this)
	elseif ( !isset($order_data['shipping_method']) ) {
		wp_send_json_error( array( 'message' => __( 'Please select a shipping method.', 'votcaulong-shop' ) ) );
		return;
	}


	// Validation for 'Other Recipient' if the data is present
	if ( isset( $order_data['other_recipient'] ) && is_array( $order_data['other_recipient'] ) ) {
		// Check if the intention was to fill it (e.g., based on a checkbox value sent from JS)
		// Assuming 'enabled' key is sent if checkbox is checked
		if( !empty($order_data['other_recipient']['enabled']) ) {
			if ( empty( $order_data['other_recipient']['name'] ) || empty( $order_data['other_recipient']['phone'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter the full information for the other recipient.', LANG_ZONE ) ) );
				return;
			}
			$recipient_phone = $order_data['other_recipient']['phone_number'];
			$recipient_name = $order_data['other_recipient']['name'];
			$recipient_gender = $order_data['other_recipient']['title'];
		}
	}

	// Validation for 'Company Invoice' if the data is present
	if ( isset( $order_data['company_invoice'] ) && is_array( $order_data['company_invoice'] ) ) {
		// Assuming 'enabled' key is sent if checkbox is checked
		if( !empty($order_data['company_invoice']['enabled']) ) {
			if ( empty( $order_data['company_invoice']['name'] ) || empty( $order_data['company_invoice']['tax_number'] ) || empty( $order_data['company_invoice']['address'] ) ) {
				wp_send_json_error( array( 'message' => __( 'Please enter the full company invoice details.', LANG_ZONE ) ) );
				return;
			}
		}
	}

	// --- Cart Items Validation (Crucial!) ---
	if ( empty( $order_data['cart'] ) || !is_array($order_data['cart']) ) {
		wp_send_json_error( array( 'message' => __( 'Your cart is empty or invalid.', LANG_ZONE ) ) );
		return;
	}
	try {
		$erp_api = new ERP_API_Client();
		$user_id = get_current_user_id();
		$customer = new Customer($user_id);
		$customer_info = $customer->get_customer_info();
		$erp_order_data = [
			"customer"      =>    $customer_info['erp_name'],
			"phone"         =>    $recipient_phone,
			"address_title" => $recipient_gender.' '.$recipient_name,
			"address_line1"=> $order_data['shipping_address']['street'],
			"address_location"=> $order_data['shipping_address']['city_code'],
			"ward"=> $order_data['shipping_address']['ward_code'],
			"pricing_rule" => $order_data['order_pricing_rule'],
			"delivery_vendor" => $order_data['shipping_service'],
			"delivery_fee" => $order_data['shipping_total'],
			"loyalty_point" =>$order_data['loyalty_point'],
			"coupon_code" => $order_data['voucher_code'],
			"payment_mode" => "CASH",
			"pickup_at" => $pickup_store ?? '',
			"docstatus" => 1
		];
		$pricing_rule_data =[];
		$cart = $order_data['cart'];
		foreach ($cart as $item) {
			$product_id    = $item['id'];
			$pricing_rule  = '';

			foreach ($order_data['items_rule'] as $rule) {
				if ($rule['item_code'] === $product_id) {
					$pricing_rule = $rule['pricing_rule'];
					break;
				}
			}
			$pricing_rule_data[] = [
				'item_code'     => (string)$product_id,
				'qty'           => $item['quantity'],
				'pricing_rule'  => $pricing_rule,
			];
		}
		$erp_order_data['items'] = $pricing_rule_data;
		$erp_order = $erp_api->make_sales_order($erp_order_data);
		if (!is_wp_error($erp_order)) {
			unset($order_data['cart']);
			$order_manager = new VCL_Order();
			$initial_status = 'processing';
			$erp_order['more_info'] = $order_data;
			$order_id = $order_manager->create_order_from_erp( $erp_order, $initial_status );
			if ( $order_id ) {
				if ( $user_id ) {
					delete_user_meta( $user_id, '_user_cart' );
				}
				$order_key = $order_manager->get_order_key($order_id);
				if (!$order_key) {
					error_log("Checkout AJAX Warning: Could not retrieve order key for Order ID: " . $order_id);
					$order_key = ''; // Prevent errors in add_query_arg
				}
				$redirect_url = $order_manager->get_order_received_url($order_id);

				wp_send_json_success( array(
					'message'      => __( 'Order placed successfully! We will contact you soon.', LANG_ZONE ),
					'redirect_url' => $redirect_url,
					'order_id'     => $order_id
				) );

			} else {
				// Order creation failed within VCL_Order::create_order
				// VCL_Order::create_order should ideally log specific errors
				error_log("Checkout AJAX Error: VCL_Order::create_order returned false for COD attempt.");
				wp_send_json_error( array( 'message' => __( 'Unable to create the order. Please try again.', LANG_ZONE ) ) );
			}

		}else{
			error_log( "Checkout ERROR: " . $erp_order->get_error_message()  );
//			my_debug($erp_order_data);
			wp_send_json_error( array( 'message' => $erp_order->get_error_message()  ) );
		}


	} catch ( Exception $e ) {
		// Catch any unexpected errors during order creation process
		error_log( "Checkout AJAX Exception: " . $e->getMessage() . "\nOrder Data: " . $order_data_json );
		wp_send_json_error( array( 'message' => __( 'A system error occurred while processing the order.', LANG_ZONE ) ) );
	}
}
// Hook the function to the AJAX action defined in JavaScript
add_action( 'wp_ajax_process_cod_order', 'handle_process_cod_order' );        // For logged-in users
add_action( 'wp_ajax_nopriv_process_cod_order', 'handle_process_cod_order' ); // For non-logged-in users
//=========================================
// Xử lý đơn hàng Payoo
//=========================================
add_action('wp_ajax_process_payoo_order', 'handle_process_payoo_order');
add_action('wp_ajax_nopriv_process_payoo_order', 'handle_process_payoo_order');

function handle_process_payoo_order() {
	// 1. Verify Nonce
	check_ajax_referer( 'vcl_checkout_nonce', 'nonce' );

	// 2. Get and Decode Data
	$order_data_json = isset( $_POST['order_data'] ) ? wp_unslash( $_POST['order_data'] ) : '';
	if ( empty( $order_data_json ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid order data.', LANG_ZONE ) ) );
		return;
	}
	$order_data = json_decode( $order_data_json, true );
	if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $order_data ) ) {
		error_log("Payoo Checkout AJAX Error: Invalid JSON received. Data: " . $order_data_json);
		wp_send_json_error( array( 'message' => __( 'Error processing order data.', LANG_ZONE ) ) );
		return;
	}

	// 3. Server-side Validation (Copied from COD handler)
	if ( empty( $order_data['customer_info']['fullname'] ) || empty( $order_data['customer_info']['phone_number'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Please provide full name and phone number.', LANG_ZONE ) ) );
		return;
	}
	// --- Split fullname into first and last name ---
	$fullname = trim($order_data['customer_info']['fullname']);
	$space_pos = strpos($fullname, ' ');
	if ($space_pos === false) {
		$first_name = $fullname;
		$last_name = '';
	} else {
		$first_name = trim(substr($fullname, 0, $space_pos));
		$last_name = trim(substr($fullname, $space_pos + 1));
	}
	$order_data['customer_info']['first_name'] = $first_name;
	$order_data['customer_info']['last_name'] = $last_name;
	// --- End splitting name ---
	$recipient_phone = $order_data['customer_info']['phone_number'];
	$recipient_name = $order_data['customer_info']['fullname'];
	$recipient_gender = $order_data['customer_info']['gender'];
	if ( isset($order_data['shipping_method']) && $order_data['shipping_method'] === 'delivery' ) {
		if ( empty( $order_data['shipping_address']['city_code'] ) || empty( $order_data['shipping_address']['ward_code'] ) || empty( $order_data['shipping_address']['street'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide the complete delivery address.', LANG_ZONE ) ) );
			return;
		}
	}
	elseif ( isset($order_data['shipping_method']) && $order_data['shipping_method'] === 'pickup' ) {
		if ( empty( $order_data['pickup_store_id'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a pickup store.', LANG_ZONE ) ) );
			return;
		}
		$pickup_store = get_the_title( $order_data['pickup_store_id'] );
	}
	elseif ( !isset($order_data['shipping_method']) ) {
		wp_send_json_error( array( 'message' => __( 'Please select a shipping method.', LANG_ZONE ) ) );
		return;
	}

	if ( isset( $order_data['other_recipient'] ) && is_array( $order_data['other_recipient'] ) && !empty($order_data['other_recipient']['enabled']) ) {
		if ( empty( $order_data['other_recipient']['name'] ) || empty( $order_data['other_recipient']['phone'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter the full information for the other recipient.', LANG_ZONE ) ) );
			return;
		}
		$recipient_phone = $order_data['other_recipient']['phone_number'];
		$recipient_name = $order_data['other_recipient']['name'];
		$recipient_gender = $order_data['other_recipient']['title'];
	}

	if ( isset( $order_data['company_invoice'] ) && is_array( $order_data['company_invoice'] ) && !empty($order_data['company_invoice']['enabled']) ) {
		if ( empty( $order_data['company_invoice']['name'] ) || empty( $order_data['company_invoice']['tax_number'] ) || empty( $order_data['company_invoice']['address'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter the full company invoice details.', LANG_ZONE ) ) );
			return;
		}
	}

	if ( empty( $order_data['cart'] ) || !is_array($order_data['cart']) ) {
		wp_send_json_error( array( 'message' => __( 'Your cart is empty or invalid.', LANG_ZONE ) ) );
		return;
	}

	try {
		// 4. Prepare ERP Data and Create DRAFT Sales Order in ERP
		$erp_api = new ERP_API_Client();
		$user_id = get_current_user_id();
		$customer = new Customer($user_id);
		$customer_info = $customer->get_customer_info();

		$erp_order_data = [
			"customer"      => $customer_info['erp_name'],
			"phone"         =>    $recipient_phone,
			"address_title" => $recipient_gender.' '.$recipient_name,
			"address_line1" => $order_data['shipping_address']['street'],
			"address_location" => $order_data['shipping_address']['city_code'],
			"ward"          => $order_data['shipping_address']['ward_code'],
			"pricing_rule"  => $order_data['order_pricing_rule'],
			"delivery_vendor" => $order_data['shipping_service'],
			"delivery_fee"  => $order_data['shipping_total'],
			"loyalty_point" => $order_data['loyalty_point'],
			"coupon_code"   => $order_data['voucher_code'],
			"payment_mode" => "CARD",
			"pickup_at"    => $pickup_store ??'',
			"docstatus"     => 1
		];

		$pricing_rule_data = [];
		foreach ($order_data['cart'] as $item) {
			$item_code = $item['id'];
			$pricing_rule = '';
			foreach ($order_data['items_rule'] as $rule) {
				if ($rule['item_code'] === $item_code) {
					$pricing_rule = $rule['pricing_rule'];
					break;
				}
			}
			$pricing_rule_data[] = [
				'item_code'    => (string)$item_code,
				'qty'          => $item['quantity'],
				'pricing_rule' => $pricing_rule,
			];
		}
		$erp_order_data['items'] = $pricing_rule_data;

		// Call ERP to create the draft order
		$erp_order = $erp_api->make_sales_order($erp_order_data);

		if (is_wp_error($erp_order)) {
			error_log("Payoo Checkout ERP Error: " . $erp_order->get_error_message() . " Data: " . print_r($erp_order_data, true));
			error_log("Payoo Checkout ERP Error: " . $erp_order->get_error_message() . " Cart: " . print_r($order_data, true));
			wp_send_json_error(array('message' => $erp_order->get_error_message()));
			return;
		}

		// 5. Create Local Order from ERP Response
		$order_manager = new VCL_Order();
		$initial_status = 'pending-payment'; // Correct status for online payments
		$erp_order['more_info'] = $order_data; // Add original frontend data for reference
		$order_id = $order_manager->create_order_from_erp($erp_order, $initial_status);

		if (!$order_id) {
			error_log("Payoo Checkout Local DB Error: VCL_Order::create_order_from_erp returned false. ERP Response: " . print_r($erp_order, true));
			wp_send_json_error(array('message' => __('Unable to save the order locally. Please try again.', LANG_ZONE)));
			return;
		}

		// 6. Prepare and Send Payoo Request
		$payoo_handler = new Payoo_Handler();
		if (!$payoo_handler->is_configured()) {
			wp_send_json_error(['message' => __('Payoo payment gateway is not configured correctly.', LANG_ZONE)]);
			return;
		}

		$local_order_details = $order_manager->get_order_with_meta($order_id);
		$payment_data = $payoo_handler->create_payment_data($local_order_details);

		if (is_wp_error($payment_data)) {
			wp_send_json_error(['message' => __('Error preparing Payoo payment data:', LANG_ZONE) . ' ' . $payment_data->get_error_message()]);
			return;
		}

		$payment_url = $payoo_handler->send_payment_request($payment_data);

		if (is_wp_error($payment_url)) {
			wp_send_json_error(['message' => __('Error sending request to Payoo:', LANG_ZONE) . ' ' . $payment_url->get_error_message()]);
			return;
		}

		// 7. Success - Return Redirect URL
		$order_manager->add_order_note(__('Khách hàng được chuyển hướng đến Payoo để thanh toán.', LANG_ZONE));
		if ($user_id) {
			delete_user_meta($user_id, '_user_cart');
		}

		wp_send_json_success([
			'message'      => __('Order created successfully. Redirecting to Payoo...', LANG_ZONE),
			'redirect_url' => $payment_url
		]);

	} catch (Exception $e) {
		error_log('Payoo Checkout Exception: ' . $e->getMessage() . ' Data: ' . $order_data_json);
		// If an order was created locally, try to mark it as failed.
		if (isset($order_id) && $order_id) {
			$failed_order = new VCL_Order($order_id);
			$failed_order->update_status($order_id, 'failed');
			$failed_order->add_order_note('Lỗi hệ thống khi xử lý thanh toán Payoo: ' . $e->getMessage());
		}
		wp_send_json_error(['message' => __('An unexpected error occurred during checkout. Please try again later.', LANG_ZONE)]);
	}
}

//--------------------------
add_action('wp_ajax_get_shipping_cost','handle_get_shipping_cost');
add_action('wp_ajax_nopriv_get_shipping_cost','handle_get_shipping_cost');
function handle_get_shipping_cost(){
	check_ajax_referer('vcl_checkout_nonce', 'nonce');

	$address_line1 = sanitize_text_field($_POST['address_line1'] ?? '');
	$custom_address_location = sanitize_text_field($_POST['custom_address_location'] ?? '');
	$custom_ward = sanitize_text_field($_POST['custom_ward'] ?? '');
	$cart_total = (float)($_POST['cart_total'] ?? 0);

	// Validate input
	if (!$address_line1 || !$custom_address_location || !$custom_ward) {
		wp_send_json_error(['message' => 'Thiếu thông tin địa chỉ.']);
	}

	// Gọi API ERP
	$erp = new ERP_API_Client();
	$fee_result = $erp->calculate_delivery_fee([
		'address_line1' => $address_line1,
		'custom_address_location' => $custom_address_location,
		'custom_ward' => $custom_ward,
		'package_value' => $cart_total
	]);

	if (is_wp_error($fee_result)) {
		wp_send_json_error(['message' => $fee_result->get_error_message()]);
	}

	// Có thể trả về cả mảng hoặc chỉ số, tùy theo design JS
	wp_send_json_success(['shipping_cost' => $fee_result]);
}
//-----------------------------
add_action('wp_ajax_order_manual_payment','handle_order_manual_payment');
add_action('wp_ajax_nopriv_order_manual_payment','handle_order_manual_payment');
function handle_order_manual_payment(){
	if (!check_ajax_referer('themetoken-security', 'nonce', false)) {
		wp_send_json_error('Nonce invalid or expired.', LANG_ZONE);
	}
	if (!isset($_POST['orderid'])) {
		wp_send_json_error(['message' => __('Missing order ID.', LANG_ZONE)]);
		return;
	}

	$order_id = (int)$_POST['orderid'];
	$vcl_order = new VCL_Order();
//	$order = $vcl_order->get_order($order_id);
	if ($order_id){
		$payoo_handler = new Payoo_Handler();
		if (!$payoo_handler->is_configured()) {
			wp_send_json_error(['message' => __('Payoo payment gateway is not configured correctly.', LANG_ZONE)]);
			return;
		}
		$order_data = $vcl_order->get_order_with_meta( $order_id );
		// Prepare payment data using the created order ID and original data
		$payment_data = $payoo_handler->create_payment_data( $order_data);
		if (is_wp_error($payment_data)) {
			wp_send_json_error(['message' => __('Error preparing Payoo payment data:', LANG_ZONE) . ' ' . $payment_data->get_error_message(),'data' => $order_data]);
			return;
		}

		// Send request to Payoo
		$payment_url = $payoo_handler->send_payment_request($payment_data);

		if (is_wp_error($payment_url)) {
			error_log('[Manual payment ERROR] '.print_r($payment_data,true));
			wp_send_json_error(['message' => __('Error sending request to Payoo:', LANG_ZONE) . ' ' . $payment_url->get_error_message()]);
			return;
		}

		// 6. Success - Return Redirect URL
		// Add a note indicating the user is being redirected
		$vcl_order_instance_for_note = new VCL_Order($order_id);
		$vcl_order_instance_for_note->add_order_note(__('Khách hàng được chuyển hướng đến Payoo để thanh toán.', LANG_ZONE));
		wp_send_json_success([
			'message' => __('Redirecting to Payoo...', LANG_ZONE),
			'redirect_url' => $payment_url // The URL Payoo returned
		]);
	}
}

/**
 * Handles the AJAX request for cancelling an order.
 */
add_action( 'wp_ajax_vcl_cancel_order', 'handle_vcl_cancel_order' );
add_action( 'wp_ajax_nopriv_vcl_cancel_order', 'handle_vcl_cancel_order' );

function handle_vcl_cancel_order() {
    // Verify Nonce
    if ( ! check_ajax_referer( 'themetoken-security', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid request or expired nonce.', LANG_ZONE ) ] );
    }

    // Get order ID and customer note
    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $customer_note = isset( $_POST['customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_note'] ) ) : '';

    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => __( 'Missing order ID.', LANG_ZONE ) ] );
    }

    $vcl_order = new VCL_Order( $order_id );

    // Check if the current user is the owner of the order (basic security)
    $order_data = $vcl_order->get_order( $order_id );
    if ( ! $order_data || $order_data->user_id != get_current_user_id() ) {
        wp_send_json_error( [ 'message' => __( 'You do not have permission to cancel this order.', LANG_ZONE ) ] );
    }

    // Attempt to cancel the order
    $cancelled = $vcl_order->cancel_order( $order_id, $customer_note, 'customer' );
//	error_log("Cancel order: {$cancelled}".$order_id, (boolean)$cancelled);
    if ( $cancelled ) {
		$erp_client = new ERP_API_Client();
	    $_erp_order_code = normalizeOrderName($vcl_order->get_order_meta($order_id,'_erp_order_code'));
		if ($_erp_order_code){
			$erp_client->cancel_sales_order(['order_name' => $_erp_order_code,'reason' => $customer_note]);
//			error_log('cancel order: '.print_r($erp_client,true));
		}
	    wp_send_json_success( [ 'message' => __( 'Order cancelled successfully.', LANG_ZONE ) ] );
    } else {
        // If cancellation failed, it might be due to status or other internal reasons
        wp_send_json_error( [ 'message' => __( 'Could not cancel the order. Please check its status or contact support.', LANG_ZONE ) ] );
    }
}