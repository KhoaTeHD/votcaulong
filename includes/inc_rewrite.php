<?php
function custom_rewrite_rules() {
	// Danh mục sản phẩm (category)
	add_rewrite_rule(
		'^([^/]+)-cat\.([0-9]+)$',
		'index.php?product_cate_name=$matches[1]&product_cate_id=$matches[2]',
		'top'
	);
	// Sản phẩm (product)
	add_rewrite_rule(
		'^([^/]+)-i\.([^/]+)$',
		'index.php?product_name=$matches[1]&product_id=$matches[2]',
		'top'
	);
	add_rewrite_rule(
		'^so-sanh/([^;]+(?:;[^;]+)*)$',
		'index.php?compare_products=$matches[1]',
		'top'
	);
	add_rewrite_rule(
		'^([^/]+)-tab\.([^.]+)\.id([0-9]+)/?$', // Matches pagename-tab.tabname.id{digits}
		'index.php?pagename=$matches[1]&tab=$matches[2]&view_order_id=$matches[3]', // Sends to page with tab and order ID
		'top' // Place this rule at the top to ensure it matches before the general tab rule
	);
	add_rewrite_rule(
		'^([^/]+)-tab\.([^/]+)$',
		'index.php?pagename=$matches[1]&tab=$matches[2]',
		'top'
	);
	// Example: /dat-hang-thanh-cong/123/wc_order_abcxyz/
    // Matches 'dat-hang-thanh-cong', followed by digits (order_id), followed by any characters (order_key)
    add_rewrite_rule(
        '^dat-hang-thanh-cong/([0-9]+)/([^/]+)/?$', // Regex pattern
        'index.php?vcl_page=order_received&order_id=$matches[1]&order_key=$matches[2]', // Query string mapping
        'top' // Priority
    );
}
add_action('init', 'custom_rewrite_rules');

function custom_query_vars($vars) {
	$vars[] = 'product_cate_name'; // Tên danh mục
	$vars[] = 'product_cate_id';   // ID danh mục
	$vars[] = 'product_name';  // Tên sản phẩm
	$vars[] = 'product_id';    // ID sản phẩm
	$vars[] = 'compare_products';    // so sánh
	$vars[] = 'tab';    // so sánh
	$vars[] = 'view_order_id';
	$vars[] = 'vcl_page';    // To identify our custom page type
    $vars[] = 'order_id';
    $vars[] = 'order_key';
	return $vars;
}
add_filter('query_vars', 'custom_query_vars');
/*
add_action('after_switch_theme', function() {
	flush_rewrite_rules();
});*/

/**
 * Register Payoo Callback REST API Endpoint.
 */
add_action('rest_api_init', function () {
    register_rest_route('payment/v1', '/payoo-callback', array(
        'methods' => ['GET', 'POST'], // Accept both GET and POST
        'callback' => 'handle_payoo_callback_request',
        'permission_callback' => '__return_true' // Allow public access
    ));
});

/**
 * Callback function to handle Payoo GET (Return) and POST (IPN) requests.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return WP_REST_Response|void Depending on the request type.
 */
function handle_payoo_callback_request( WP_REST_Request $request ) {
    

    $payoo_handler = new Payoo_Handler();

    // Determine request method
    $method = $request->get_method();
	$request_body = $request->get_body(); // Lấy nội dung body

	// Log the request method, URI, and body
	error_log('Payoo Callback Log (Request Received): Method=' . $method . ' URI=' . $request->get_route() . ' Body=' . $request_body);
    if ( 'POST' === $method ) {
        // --- Handle IPN (POST Request) ---
        $raw_post_data = $request->get_body();
        error_log('Payoo Callback Log (IPN Received): ' . $raw_post_data); // Log raw data for debugging

        $ipn_response = $payoo_handler->handle_ipn($raw_post_data);

        // Send JSON response back to Payoo
        return new WP_REST_Response($ipn_response, 200); // HTTP 200 OK

    } elseif ( 'GET' === $method ) {
        // --- Handle Return URL (GET Request) ---
        $params = $request->get_query_params();
        error_log('Payoo Callback Log (Return Received): ' . print_r($params, true)); // Log params for debugging

        $is_valid_checksum = $payoo_handler->verify_return_checksum($params);
        $order_id = isset($params['order_no']) ? absint($params['order_no']) : 0;
        $status = isset($params['status']) ? $params['status'] : null; // '1' = success, '0' = failure/cancel
		$error_msg = isset($params['errormsg'])? $params['errormsg'] : null;

        // Prepare redirect URL (e.g., to a thank you page)
        // You might need to fetch the order key from your VCL_Order class
        $redirect_url = home_url('/'); // Default fallback redirect

        if ($order_id) {
            // Example: Get order key if needed for thank you page URL
            $order = new VCL_Order($order_id);
            // $order_key = $order->get_order_key(); // Assuming you have this method
			if ($status == '1') {
				 // Get current status using get_order_status($order_id)
				 $current_status = $order->get_order_status($order_id);
				 $note = '';
				 if ($current_status !== 'completed' && $current_status !== 'processing') {
					$order->update_status($order_id, 'paid'); // Pass order_id
					$note = 'Thanh toán Payoo thành công.';
					// TODO: Trigger other actions like sending email, reducing stock etc.
					 $erp_order = $order->sync_to_erp();

			   }
			}
            // Construct the Thank You page URL (adjust path as needed)
            $thank_you_page_url = $order->get_order_received_url($order_id) ;

            if ($thank_you_page_url) {
				$order->add_order_note($note );
                 // Add query args - adjust based on your thank you page needs
                $redirect_url = add_query_arg( array(
                  //  'order_id' => $order_id,
                    // 'key' => $order_key, // Uncomment if your thank you page uses order key
                    'payoo_status' => $status, // Pass status ('1' or '0')
					'msg'	=> $error_msg,
                    'checksum_valid' => $is_valid_checksum ? '1' : '0' // Indicate if checksum was valid
                ), $thank_you_page_url );
            }
        }

        // Perform the redirect
        wp_safe_redirect($redirect_url);
        exit; // Important: Stop script execution after redirect header

    } else {
        // Method Not Allowed
        return new WP_REST_Response(['message' => 'Method Not Allowed'], 405);
    }
}
/**
 * Filter the term link for 'pro_cate' taxonomy to match the custom URL structure.
 */
function custom_pro_cate_term_link( $termlink, $term, $taxonomy ) {
    if ( 'pro_cate' === $taxonomy ) { 
        if ( is_object( $term ) && isset( $term->term_id ) && isset( $term->slug ) ) {
            return home_url( '/' . $term->slug . '-cat.' . $term->term_id );
        }
    }
    return $termlink;
}
add_filter( 'term_link', 'custom_pro_cate_term_link', 10, 3 );
//=============================
add_action('rest_api_init', function () {
	register_rest_route('erp/v1', '/order/(?P<order_id>[^/]+)', array(
		'methods'             => 'POST',
		'callback'            => 'custom_update_order_api',
		'permission_callback' => 'custom_rest_permission_check',
	));
	register_rest_route('erp/v1', '/branch', array(
		'methods'             => ['POST', 'PUT', 'DELETE'],
		'callback'            => 'erp_branch_webhook',
//		'permission_callback' => 'custom_rest_permission_check',
		'permission_callback' => '__return_true',
	));
	register_rest_route('erp/v1', '/brand', array(
		'methods'             => ['POST', 'PUT', 'DELETE'],
		'callback'            => 'erp_brand_webhook',
		'permission_callback' => '__return_true',
	));
});

function custom_rest_permission_check($request) {
	$headers = $request->get_headers();
	$erp_API_config = get_field('erp_api','options');
	if ($erp_API_config){
		$valid_api_key = $erp_API_config['api_key'].':'.$erp_API_config['api_secret'];
	}
	$auth_header = isset($headers['authorization'][0]) ? $headers['authorization'][0] : '';

	$token = '';
	if (stripos($auth_header, 'Bearer ') === 0) {
		$token = substr($auth_header, 7);
	} elseif (stripos($auth_header, 'Token ') === 0) {
		$token = substr($auth_header, 6);
	}
	if (!$token) {
		return new WP_Error('rest_unauthorized', 'Missing token', array('status' => 401));
	}
	if ($token !== $valid_api_key) {
		return new WP_Error('rest_unauthorized', 'Invalid token', array('status' => 401));
	}
	return true;
}

function custom_update_order_api($request) {
	$order_id = $request->get_param('order_id');
	$params   = $request->get_json_params();
	$status   = isset($params['status']) ? $params['status'] : '';
//	$note     = isset($params['note']) ? $params['note'] : '';
	if ($status){
		$order = new VCL_Order();
		$success = $order->update_status_by_erp_name($order_id, $status);
	}
	if ($success) {
		return new WP_REST_Response([
			'success'  => true,
			'message'  => 'Order updated!',
			'order_name' => $order_id,
			'status' => $status
		], 200);
	} else {
		return new WP_Error('update_error', 'Invalid Order', array('status' => 401));
	}

}
//---------- ERP branches ----------
function erp_branch_webhook($request){
	$method = $request->get_method();
	$body = $request->get_json_params();
	// Lấy các field chính
	$type   = $body['type']   ?? null;
	$event  = $body['event']  ?? null;
	$data   = $body['data']   ?? [];
	$valid = false;
	if ($method === 'POST'   && $event === 'INSERT') $valid = true;
	if ($method === 'PUT'    && $event === 'UPDATE') $valid = true;
	if ($method === 'DELETE' && $event === 'DELETE') $valid = true;

	if (!$valid) {
		return new WP_Error(
			'invalid_event_method',
			"HTTP method ($method) không khớp với event ($event)",
			array('status' => 400)
		);
	}
	$erp_api = new ERP_API_Client();
	switch ($method) {
		case 'POST':
			// Xử lý tạo mới
			$warehouse_id = $data['warehouse'] ?? '';
			if (empty($warehouse_id)) {
				return new WP_Error('missing_warehouse', 'Thiếu warehouse trong data', array('status' => 400));
			}
			$post_id = wp_insert_post([
				'post_title'  => $data['name'] ?? $data['branch'],
				'post_type'   => 'store_system',
				'post_status' => 'publish',
			]);
			if ($post_id) {
				$meta_map = [
					'_erp_branch_code'      => $data['branch'] ?? '',
					'_erp_branch_id'        => $warehouse_id,
					'store_phone'           => $data['custom_mobile'] ?? '',
					'store_address'         => $data['custom_address'] ?? '',
					'store_google_map'      => $data['custom_map'] ?? '',
					'store_google_map_link' => $data['custom_map'] ?? '',
					'_erp_branch_creation'  => $data['creation'] ?? '',
					'_erp_branch_modified'  => $data['modified'] ?? '',
				];
				foreach ($meta_map as $meta_key => $meta_val) {
					if (!empty($meta_val)) update_post_meta($post_id, $meta_key, $meta_val);
				}
				// Ảnh đại diện
				$custom_image_path = $data['custom_image'] ?? '';
				$image_url = $custom_image_path ? $erp_api->erp_item_image($custom_image_path) : '';
				if ($image_url) set_post_thumbnail_from_url($post_id, $image_url);
				return new WP_REST_Response(['message' => 'Created new', 'data' => $data], 201);
			} else {
				return new WP_REST_Response(['message' => 'Error', 'data' => $data], 400);
			}

		case 'PUT':
			// Xử lý update
			$warehouse_id = $data['warehouse'] ?? '';
			if (empty($warehouse_id)) {
				return new WP_Error('missing_warehouse', 'Thiếu warehouse trong data', array('status' => 400));
			}
			// Tìm post theo metakey _erp_branch_id
			$args = array(
				'post_type'      => 'store_system',
				'post_status'    => 'any',
				'meta_key'       => '_erp_branch_id',
				'meta_value'     => $warehouse_id,
				'posts_per_page' => 1,
				'fields'         => 'ids'
			);
			$query = new WP_Query($args);

			if (!empty($query->posts)) {
				$post_id = $query->posts[0];
				wp_update_post(array(
					'ID'         => $post_id,
					'post_title' => $data['name'] ?? ''
				));
				$mess = 'Updated';
				$http_code = 200;
			} else { // tạo mới
				$post_id = wp_insert_post([
					'post_title' => $data['name'] ?? $data['branch'] ,
					'post_type' => 'store_system',
					'post_status' => 'publish',
				]);
				$mess= 'Created new';
				$http_code = 201;
			}
			if ($post_id) {
				// Cập nhật các custom field khác nếu muốn
				$meta_map = [
					'_erp_branch_code'      => $data['branch'] ?? '',
					'_erp_branch_id'        => $warehouse_id,
					'store_phone'           => $data['custom_mobile'] ?? '',
					'store_address'         => $data['custom_address'] ?? '',
					'store_google_map'      => $data['custom_map'] ?? '',
					'store_google_map_link' => $data['custom_map'] ?? '',
					'_erp_branch_creation'  => $data['creation'] ?? '',
					'_erp_branch_modified'  => $data['modified'] ?? '',
				];
				foreach ($meta_map as $meta_key => $meta_val) {
					if (!empty($meta_val)) update_post_meta($post_id, $meta_key, $meta_val);
				}

				$custom_image_path = $data['custom_image'] ?? '';
				$image_url = $custom_image_path?$erp_api->erp_item_image($custom_image_path):'';
				if ($image_url) set_post_thumbnail_from_url($post_id, $image_url);

				return new WP_REST_Response(['message' => $mess, 'data' => $data], $http_code);
			}else{
				return new WP_REST_Response(['message' => 'Error', 'data' => $data], 404);
			}


		case 'DELETE':
			$branch_name = $data['name'] ?? '';
			if (empty($branch_name)) {
				return new WP_Error('missing_branch_name', 'Thiếu name (tên chi nhánh) trong data', array('status' => 400));
			}

			$query = new WP_Query(array(
				'post_type'      => 'store_system',
				'post_status'    => 'any',
				's'              => $branch_name,
				'posts_per_page' => 5,
				'fields'         => 'ids'
			));

			// Lọc kết quả đúng post_title tuyệt đối
			$post_id = 0;
			foreach ($query->posts as $pid) {
				if (get_the_title($pid) === $branch_name) {
					$post_id = $pid;
					break;
				}
			}

			if ($post_id) {
				$thumbnail_id = get_post_thumbnail_id($post_id);
				if ($thumbnail_id) {
					wp_delete_attachment($thumbnail_id, true);
				}
				wp_delete_post($post_id, true);
				return new WP_REST_Response([
					'message'      => 'Deleted',
					'post_id'      => $post_id,
					'branch_name'  => $branch_name
				], 200);
			} else {
				return new WP_REST_Response([
					'message'      => 'Không tìm thấy chi nhánh cần xoá',
					'branch_name'  => $branch_name
				], 404);
			}

		default:
			return new WP_Error('invalid_method', 'Invalid request method', array('status' => 405));
	}
}
//------------- ERP Brands -------------
function erp_brand_webhook($request){
	$method = $request->get_method();
	$body = $request->get_json_params();

	$type  = $body['type']   ?? null;
	$event = $body['event']  ?? null;
	$data  = $body['data']   ?? [];

	$valid = false;
	if ($method === 'POST'   && $event === 'INSERT') $valid = true;
	if ($method === 'PUT'    && $event === 'UPDATE') $valid = true;
	if ($method === 'DELETE' && $event === 'DELETE') $valid = true;

	if (!$valid) {
		return new WP_Error(
			'invalid_event_method',
			"HTTP method ($method) không khớp với event ($event)",
			array('status' => 400)
		);
	}

	$erp_api = new ERP_API_Client();
	$desc = $data['description'] ?? '';
	$desc = trim($desc);
	$desc = wp_kses_post($desc);
	switch ($method) {
		// ----------- TẠO MỚI BRAND -----------
		case 'POST':
			$brand_id = $data['name'] ?? '';
			if (empty($brand_id)) {
				return new WP_Error('missing_brand_id', 'Thiếu name (mã brand) trong data', array('status' => 400));
			}
			$post_id = wp_insert_post([
				'post_title'  => $brand_id ,
				'post_type'   => 'brands',
				'post_status' => 'publish',
				'post_content' => $desc
			]);
			if ($post_id) {
				update_post_meta($post_id, '_erp_brand_id', $brand_id);

				// Nếu có logo/ảnh brand thì set luôn thumbnail
				$custom_image_path = $data['image'] ?? '';
				$image_url = $custom_image_path ? $erp_api->erp_item_image($custom_image_path) : '';
				if ($image_url) set_post_thumbnail_from_url($post_id, $image_url);

				return new WP_REST_Response(['message' => 'Created new', 'data' => $data, 'post_id' => $post_id], 201);
			} else {
				return new WP_REST_Response(['message' => 'Error', 'data' => $data], 400);
			}

		// ----------- CẬP NHẬT BRAND -----------
		case 'PUT':
			$brand_id = $data['name'] ?? $data['brand'];
			if (empty($brand_id)) {
				return new WP_Error('missing_brand_id', 'Thiếu name (mã brand) trong data', array('status' => 400));
			}
			// Tìm post theo _erp_brand_id
			$args = array(
				'post_type'      => 'brands',
				'post_status'    => 'any',
				'meta_key'       => '_erp_brand_id',
				'meta_value'     => $brand_id,
				'posts_per_page' => 1,
				'fields'         => 'ids'
			);
			$query = new WP_Query($args);

			if (!empty($query->posts)) {
				$post_id = $query->posts[0];
				wp_update_post([
					'ID'         => $post_id,
					'post_title' => $brand_id,
					'post_content' => $desc
				]);
				$mess = 'Updated';
				$http_code = 200;
			} else {
				// Không tìm thấy thì tạo mới
				$post_id = wp_insert_post([
					'post_title'  =>  $brand_id,
					'post_type'   => 'brands',
					'post_status' => 'publish',
					'post_content' => $desc
				]);
				$mess = 'Created new';
				$http_code = 201;
			}
			if ($post_id) {
				update_post_meta($post_id, '_erp_brand_id', $brand_id);

				$custom_image_path = $data['image'] ?? '';
				$image_url = $custom_image_path ? $erp_api->erp_item_image($custom_image_path) : '';
				if ($image_url) set_post_thumbnail_from_url($post_id, $image_url);

				return new WP_REST_Response(['message' => $mess, 'data' => $data, 'post_id' => $post_id], $http_code);
			} else {
				return new WP_REST_Response(['message' => 'Error', 'data' => $data], 404);
			}

		// ----------- XOÁ BRAND -----------
		case 'DELETE':
			$brand_id = $data['name'] ?? '';
			if (empty($brand_id)) {
				return new WP_Error('missing_brand_id', 'Thiếu name (mã brand) trong data', array('status' => 400));
			}
			// Tìm post theo meta _erp_brand_id
			$args = array(
				'post_type'      => 'brands',
				'post_status'    => 'any',
				'meta_key'       => '_erp_brand_id',
				'meta_value'     => $brand_id,
				'posts_per_page' => 1,
				'fields'         => 'ids'
			);
			$query = new WP_Query($args);

			if (!empty($query->posts)) {
				$post_id = $query->posts[0];
				// Xoá luôn ảnh đại diện nếu có
				$thumbnail_id = get_post_thumbnail_id($post_id);
				if ($thumbnail_id) {
					wp_delete_attachment($thumbnail_id, true);
				}
				wp_delete_post($post_id, true);
				return new WP_REST_Response([
					'message'     => 'Deleted',
					'post_id'     => $post_id,
					'brand_id'    => $brand_id
				], 200);
			} else {
				return new WP_REST_Response([
					'message'     => 'Không tìm thấy brand cần xoá',
					'brand_id'    => $brand_id
				], 404);
			}

		default:
			return new WP_Error('invalid_method', 'Invalid request method', array('status' => 405));
	}
}
