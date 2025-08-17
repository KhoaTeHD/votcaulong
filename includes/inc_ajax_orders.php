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

	$erp_api = new ERP_API_Client();
	$products = [];
	$html_output = '';
	$subtotal = $var_price = 0;
	$item_counter = 0;
	foreach ($cart as $item) {
		$product_id = $item['sku'];

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
			}
//			my_debug($product);
//			$product['variation'] = $product['variants'];
			$product['attributes'] = $item['attributes'];
			$product['quantity'] = $item['quantity'];
			$products[] = $product;
			$item_counter += $item['quantity'];
			$subtotal += $item['quantity'] * max($var_price,$product['price']);
			ob_start();
			get_template_part('template-parts/cart', 'item', ['product' => $product]);
			$html_output .= ob_get_clean();
		}
	}
	$total_saved = 0;
	$reward_point = 0;
	$subtotal_html = '<div class="subtotal-label">'.sprintf(__('Sub-total [%s product(s)]', LANG_ZONE), $item_counter).': </div>
                    <div class="subtotal-value">'.priceFormater($subtotal).'</div>';

	$total_html = '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Promotion', LANG_ZONE).': </div>
					<div class="cartpageTotal-value">'.priceFormater($total_saved).' </div></div>';
	$total_html .= '<div class="cartpageTotal-line shipping-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Shipping', LANG_ZONE).': <span class="shipping-method-name"></span></div>
					<div class="cartpageTotal-value">'.priceFormater(0).' </div></div>';
	$total_html .= '<div class="cartpageTotal-line total-line d-flex items-justified-space-between"><div class="cartpageTotal-label">'.__('Total', LANG_ZONE).': </div>
					<div class="cartpageTotal-value">'.priceFormater($subtotal).' </div></div>';
	if ($reward_point) {
		$total_html .= '<div class="cartpageTotal-line d-flex items-justified-space-between"><div class="cartpageTotal-label">' . __( 'Reward points', LANG_ZONE ) . ': </div>
					<div class="cartpageTotal-value">' . number_format_i18n( $reward_point ) . ' </div></div>';
	}
	wp_send_json_success([
		'products' => $products,
		'html' => $html_output,
		'subtotal_html' =>$subtotal_html,
		'total_html' => $total_html
	]);
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
		$order_manager = new VCL_Order();
		$initial_status = 'processing';
		$order_id = $order_manager->create_order( $order_data, $initial_status );

		if ( $order_id ) {
			// Order created successfully

			// Clear the user's cart after successful order placement
			$user_id = get_current_user_id();
			if ( $user_id ) {
				// Clear cart stored in user meta for logged-in users
				delete_user_meta( $user_id, '_user_cart' );
			}
			$erp_order = $order_manager->sync_to_erp();

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
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vcl_checkout_nonce')) {
		wp_send_json_error(['message' => __('Invalid security token.', LANG_ZONE)]);
		return;
	}

	// 2. Check for Order Data
	if (!isset($_POST['order_data'])) {
		wp_send_json_error(['message' => __('Missing order data.', LANG_ZONE)]);
		return;
	}

	// 3. Decode Order Data
	$order_data_json = stripslashes($_POST['order_data']);
	$order_data = json_decode($order_data_json, true);

	if (json_last_error() !== JSON_ERROR_NONE || !is_array($order_data)) {
		error_log('Checkout Error: Invalid order_data JSON received: ' . $order_data_json);
		wp_send_json_error(['message' => __('Invalid order data format.', LANG_ZONE)]);
		return;
	}


	try {
		// 4. Create Order in Custom Tables
		$vcl_order = new VCL_Order();
		$initial_status = 'pending-payment'; // Set initial status for online payments
		$order_id = $vcl_order->create_order($order_data, $initial_status);

		if (!$order_id || is_wp_error($order_id)) {
			$error_message = ($order_id instanceof WP_Error) ? $order_id->get_error_message() : __('Failed to create order in database.', LANG_ZONE);
			error_log('Checkout Error (Payoo): Failed to create VCL_Order. Data: ' . print_r($order_data, true) . ' Error: ' . $error_message);
			wp_send_json_error(['message' => $error_message]);
			return;
		}

		// 5. Prepare and Send Payoo Request
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
			wp_send_json_error(['message' => __('Error sending request to Payoo:', LANG_ZONE) . ' ' . $payment_url->get_error_message()]);
			return;
		}

		// 6. Success - Return Redirect URL
		// Add a note indicating the user is being redirected
		$vcl_order_instance_for_note = new VCL_Order($order_id); // Re-instantiate with ID to use methods like add_order_note
		$vcl_order_instance_for_note->add_order_note(__('Khách hàng được chuyển hướng đến Payoo để thanh toán.', LANG_ZONE));
		$user_id = get_current_user_id();
		if ( $user_id ) {
			delete_user_meta( $user_id, '_user_cart' );
		}
		wp_send_json_success([
			'message' => __('Order created successfully. Redirecting to Payoo...', LANG_ZONE),
			'redirect_url' => $payment_url // The URL Payoo returned
		]);

	} catch (Exception $e) {
		error_log('Checkout Exception (Payoo): ' . $e->getMessage() . ' Data: ' . print_r($order_data, true));
		// Try to update order status to failed if $order_id exists
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

	// Validate input
	if (!$address_line1 || !$custom_address_location || !$custom_ward) {
		wp_send_json_error(['message' => 'Thiếu thông tin địa chỉ.']);
	}

	// Gọi API ERP
	$erp = new ERP_API_Client();
	$fee_result = $erp->calculate_delivery_fee([
		'address_line1' => $address_line1,
		'custom_address_location' => $custom_address_location,
		'custom_ward' => $custom_ward
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