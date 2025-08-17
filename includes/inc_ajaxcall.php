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
	$filters = $_POST['filters'];
	$args=[
	       'limit_start' => 0];
	if( is_array($filters)){
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
//	my_debug($filtered_products);
	if (is_wp_error($filtered_products)) {
//		echo $filtered_products->get_error_message(); // Hiển thị thông báo lỗi
		wp_die();
	}

	// Hiển thị sản phẩm đã được lọc
	if (!empty($filtered_products)) {
//        my_debug($filtered_products);
		ob_start(); // Bắt đầu output buffering
		foreach ($filtered_products as $product_info) {
			$product = get_product($product_info['item_code'],0,0);
			// Sử dụng template hoặc code HTML để hiển thị sản phẩm
				get_template_part('template-parts/product-item','',['product'=>$product]);
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

	// Đồng bộ ERP
	$sync = $customer->sync_to_erp();

	wp_send_json_success(['message' => 'Cập nhật thông tin thành công!','sync'=>$sync]);
}
//=============================
add_action('wp_ajax_update_pro_cate', 'update_pro_cate_callback');
//add_action('wp_ajax_nopriv_update_pro_cate', 'update_pro_cate_callback');

function update_pro_cate_callback() {
	if ( !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request',LANG_ZONE));
	}
	if (!isset($_POST['cate_id'])) {
		wp_send_json_error('Thiếu ID danh mục.');
	}

	$cate_id = intval($_POST['cate_id']);
	$description = sanitize_textarea_field($_POST['description']);

	$term = get_term($cate_id, 'pro_cate');
	if (is_wp_error($term)) {
		wp_send_json_error('Không tìm thấy term: ' . $term->get_error_message());
	}
	// Xử lý thumbnail
	$thumbnail_id = null;
	$thumbnail_url = null;
	if (isset($_FILES['thumbnail'])) {
		$attachment_id = media_handle_upload('thumbnail', 0); // 0 là user hiện tại
		if (is_wp_error($attachment_id)) {
			wp_send_json_error('Lỗi tải lên thumbnail: ' . $attachment_id->get_error_message());
		}
		$thumbnail_id = $attachment_id;
		$thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
	}



	// Cập nhật mô tả
	$update_result = wp_update_term($cate_id, 'pro_cate', array('description' => $description));

	if (is_wp_error($update_result)) {
		wp_send_json_error('Lỗi cập nhật mô tả: ' . $update_result->get_error_message());
	}

	// Cập nhật term meta cho thumbnail
	if ($thumbnail_id) {
		delete_term_meta($cate_id,'pro_cate_thumbnail_id');
		delete_term_meta($cate_id,'pro_cate_thumbnail_url');
		add_term_meta($cate_id, 'pro_cate_thumbnail_id', $thumbnail_id);
//		update_term_meta($cate_id, 'pro_cate_thumbnail_url', '', true);
	}

	// Lấy dữ liệu danh mục đã cập nhật
	$updated_term = get_term($cate_id, 'pro_cate');
//	$updated_thumbnail_url = get_term_meta($cate_id, 'pro_cate_thumbnail_url', true);

	$response_data = array(
		'id' => $cate_id,
		'name' => $updated_term->name,
		'description' => $updated_term->description,
		'thumbnail_url' => $thumbnail_url,
		'thumbnail_id' => $thumbnail_id,
		'url' => ProductUrlGenerator::createCategoryUrl($updated_term->name, $cate_id)
	);

	wp_send_json_success($response_data);
}
//===============
add_action('wp_ajax_get_pro_cate_table', 'get_pro_cate_table_callback');


/**
 * Helper function to get the depth and full path of a term.
 *
 * @param int   $term_id       The ID of the term.
 * @param array $all_terms_map A map of all terms (term_id => WP_Term object).
 * @return array An array containing 'depth' (int) and 'full_path_names' (array of ancestor names).
 */
function get_pro_cate_hierarchy_info($term_id, $all_terms_map) {
	$depth = 0;
	$full_path_names = []; // Stores names from root to parent
	$current_term_id = $term_id;

	// Build the path from current term upwards to the root, then reverse
	$temp_path = [];
	while ($current_term_id !== 0 && isset($all_terms_map[$current_term_id])) {
		$term_obj = $all_terms_map[$current_term_id];
		if ($term_obj->parent !== 0) { // If it has a parent
			$temp_path[] = $all_terms_map[$term_obj->parent]->name; // Add parent's name to temp path
			$current_term_id = $term_obj->parent; // Move up to parent
			$depth++; // Increment depth
		} else {
			// Reached a top-level term
			$current_term_id = 0;
		}
	}
	$full_path_names = array_reverse($temp_path); // Reverse to get root -> parent -> ...

	return ['depth' => $depth, 'full_path_names' => $full_path_names];
}


/**
 * Handles AJAX requests to retrieve and synchronize product categories.
 *
 * This function performs the following steps:
 * 1. Security nonce verification.
 * 2. **Synchronization with ERP:**
 *    - Fetches hierarchical category data from the ERP system.
 *    - Iterates through ERP categories, creating new WordPress terms if they don't exist
 *      or updating existing ones (description, parent).
 *    - Handles term thumbnail synchronization: attempts to download and attach images
 *      from ERP URLs, and updates term meta.
 *    - Identifies and deletes WordPress terms that no longer exist in the ERP data.
 *    NOTE: Running full ERP synchronization on every AJAX request can be inefficient
 *    for large datasets. Consider moving the sync logic to a less frequent process
 *    (e.g., cron job, dedicated admin action).
 * 3. **Data Retrieval for DataTables:**
 *    - Fetches the synchronized 'pro_cate' taxonomy terms based on DataTables
 *      pagination and search parameters.
 *    - Formats the term data for DataTables, including hierarchical display info.
 * 4. Sends a JSON success response with the DataTables formatted data.
 */
function get_pro_cate_table_callback() {
	// 1. Security Check
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}

	$erp_api = new ERP_API_Client(); // Instantiate your ERP API client

	$erp_categories = $erp_api->list_all_item_groups();

	if (is_wp_error($erp_categories)) {
		// If ERP API fails, log the error and proceed with existing WP data.
		error_log('ERP API Error during category sync: ' . $erp_categories->get_error_message());
	}elseif (empty($erp_categories) || !is_array($erp_categories)) {
		error_log('ERP API returned empty or invalid category list. SKIP syncing.');
	}  else {
		$wp_terms_by_parent_and_name = [];
		$all_wp_terms_before_sync = get_terms([
			'taxonomy' => 'pro_cate',
			'hide_empty' => false,
			'get' => 'all' // Get all terms to build the map
		]);

		if (is_wp_error($all_wp_terms_before_sync)) {
			error_log('Error retrieving existing WP terms before sync: ' . $all_wp_terms_before_sync->get_error_message());
		} else {
			foreach ($all_wp_terms_before_sync as $term) {
				// For top-level terms, parent is 0
				$parent_key = (int)$term->parent;
				if (!isset($wp_terms_by_parent_and_name[$parent_key])) {
					$wp_terms_by_parent_and_name[$parent_key] = [];
				}
				$wp_terms_by_parent_and_name[$parent_key][$term->name] = $term->term_id;
			}
		}

		$synced_term_ids = [];

		function sync_erp_categories_recursive($erp_categories, $parent_id, &$wp_terms_by_parent_and_name, &$synced_term_ids) {
			// Include necessary WordPress media functions for media_sideload_image
			if (!function_exists('media_handle_upload')) {
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				require_once(ABSPATH . 'wp-admin/includes/file.php');
				require_once(ABSPATH . 'wp-admin/includes/media.php');
			}

			foreach ($erp_categories as $erp_cat) {
				$term_name = $erp_cat['name'];
				$term_description = $erp_cat['description'] ?? '';
				$erp_image_url = $erp_cat['image_url'] ?? '';

				$current_term_id = 0;

				// ==== TÌM TERM TRÙNG TÊN (BẤT KỂ PARENT) ====
				$found_term_id = null;
				$old_parent_key = null;
				foreach ($wp_terms_by_parent_and_name as $parent_key => $terms_by_name) {
					if (isset($terms_by_name[$term_name])) {
						$found_term_id = $terms_by_name[$term_name];
						$old_parent_key = $parent_key;
						break; // lấy term đầu tiên
					}
				}

				if ($found_term_id) {
					$current_term_id = $found_term_id;
					// Update lại parent/description
					$update_result = wp_update_term($current_term_id, 'pro_cate', [
						'description' => $term_description,
						'parent' => $parent_id
					]);

					if (is_wp_error($update_result)) {
						error_log("Error updating term '{$term_name}' (ID: {$current_term_id}): " . $update_result->get_error_message());
					} else {
						$current_term_id = $update_result['term_id'];
						$synced_term_ids[] = $current_term_id;
					}
					// Nếu parent thay đổi, cập nhật lại map
					if ($old_parent_key !== null && $old_parent_key != $parent_id) {
						unset($wp_terms_by_parent_and_name[$old_parent_key][$term_name]);
						if (!isset($wp_terms_by_parent_and_name[$parent_id])) {
							$wp_terms_by_parent_and_name[$parent_id] = [];
						}
						$wp_terms_by_parent_and_name[$parent_id][$term_name] = $current_term_id;
					}
				} else {
					// Không tìm thấy, tạo mới
					$insert_result = wp_insert_term($term_name, 'pro_cate', [
						'description' => $term_description,
						'parent' => $parent_id
					]);

					if (is_wp_error($insert_result)) {
						error_log("Error inserting term '{$term_name}' (Parent: {$parent_id}): " . $insert_result->get_error_message());
						continue;
					} else {
						$current_term_id = $insert_result['term_id'];
						if (!isset($wp_terms_by_parent_and_name[$parent_id])) {
							$wp_terms_by_parent_and_name[$parent_id] = [];
						}
						$wp_terms_by_parent_and_name[$parent_id][$term_name] = $current_term_id;
						$synced_term_ids[] = $current_term_id;
					}
				}

				// Handle thumbnail synchronization
				if ($current_term_id && !empty($erp_image_url)) {
					$current_thumbnail_id = get_term_meta($current_term_id, 'pro_cate_thumbnail_id', true);
					$current_thumbnail_url_meta = get_term_meta($current_term_id, 'pro_cate_thumbnail_url_erp', true); // Storing original ERP URL for comparison

					// Only update if the ERP image URL has changed or no thumbnail is currently set
					if ($current_thumbnail_url_meta !== $erp_image_url || !$current_thumbnail_id) {
						// Attempt to download and attach image as a WordPress attachment
						// The last parameter 'id' makes it return the attachment ID
						$new_attachment_id = media_sideload_image($erp_image_url, 0, $term_name, 'id');

						if (!is_wp_error($new_attachment_id)) {
							// If a new attachment was created, delete the old one if different
							if ($current_thumbnail_id && $current_thumbnail_id !== $new_attachment_id) {
								wp_delete_attachment($current_thumbnail_id, true); // true for force delete
							}
							// Update term meta with the new attachment ID
							update_term_meta($current_term_id, 'pro_cate_thumbnail_id', $new_attachment_id);
							// Store the ERP URL for future comparisons
							update_term_meta($current_term_id, 'pro_cate_thumbnail_url_erp', $erp_image_url);
						} else {
							error_log("Error sideloading image for term '{$term_name}' (ID: {$current_term_id}): " . $new_attachment_id->get_error_message());
						}
					}
				}

				// Recursively process children categories
				if ($current_term_id && !empty($erp_cat['children'])) {
					sync_erp_categories_recursive($erp_cat['children'], $current_term_id, $wp_terms_by_parent_and_name, $synced_term_ids);
				}
			}
		}

		// Start the recursive sync process for top-level categories (parent ID 0)
		sync_erp_categories_recursive($erp_categories, 0, $wp_terms_by_parent_and_name, $synced_term_ids);

		// 3. Delete WordPress terms that are no longer present in the ERP data
		// Get ALL existing terms again (after potential inserts/updates from sync)
		$all_wp_terms_after_sync = get_terms([
			'taxonomy' => 'pro_cate',
			'hide_empty' => false,
			'get' => 'all'
		]);

		if (is_wp_error($all_wp_terms_after_sync)) {
			error_log('Error retrieving WP terms for deletion: ' . $all_wp_terms_after_sync->get_error_message());
		} else {
			foreach ($all_wp_terms_after_sync as $term) {
				// If a term's ID is not in our list of synced terms, it means it's no longer in ERP
				if (!in_array($term->term_id, $synced_term_ids)) {
					// Delete associated thumbnail media if it exists
					$thumbnail_id = get_term_meta($term->term_id, 'pro_cate_thumbnail_id', true);
					if ($thumbnail_id) {
						wp_delete_attachment($thumbnail_id, true); // true for force delete
					}
					// Delete the term itself
					$delete_result = wp_delete_term($term->term_id, 'pro_cate');
					if (is_wp_error($delete_result)) {
						error_log("Error deleting term '{$term->name}' (ID: {$term->term_id}): " . $delete_result->get_error_message());
					}
				}
			}
		}
	}
	// --- End Synchronization Logic ---


	// --- Start Data Retrieval for DataTables ---
	// These parameters are typically sent by DataTables for pagination and search.
	$per_page = isset($_POST['length']) ? intval($_POST['length']) : 10;
	$offset = isset($_POST['start']) ? intval($_POST['start']) : 0;
	$search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';

	// Fetch ALL terms into a map once for efficient hierarchy lookups
	// This map is used for `get_pro_cate_hierarchy_info` and to get `parent_name`
	$all_wp_terms_for_hierarchy = get_terms([
		'taxonomy' => 'pro_cate',
		'hide_empty' => false,
		'get' => 'all'
	]);
	$all_wp_terms_map = [];
	if (!is_wp_error($all_wp_terms_for_hierarchy)) {
		foreach ($all_wp_terms_for_hierarchy as $t) {
			$all_wp_terms_map[$t->term_id] = $t;
		}
	} else {
		error_log('Error retrieving all WP terms for hierarchy: ' . $all_wp_terms_for_hierarchy->get_error_message());
		// Handle gracefully, maybe proceed with limited hierarchy info
	}

	$args = [
		'taxonomy' => 'pro_cate',
		'hide_empty' => false, // Include terms even if no posts are assigned
		'number' => $per_page,  // Limit results for pagination
		'offset' => $offset,    // Offset for pagination
		'orderby' => 'name',    // Order by name
		'order' => 'ASC',
		'search' => $search_value, // Apply search filter
		'hierarchical' => false // DataTables expects a flat list for pagination
	];

	$terms = get_terms($args);

	if (is_wp_error($terms)) {
		wp_send_json_error(['error' => $terms->get_error_message()]);
	}

	// Count total terms matching the search for 'recordsFiltered'
	$count_args_filtered = [
		'taxonomy' => 'pro_cate',
		'hide_empty' => false,
		'search' => $search_value
	];
	$recordsFiltered = wp_count_terms($count_args_filtered);

	if (is_wp_error($recordsFiltered)) {
		wp_send_json_error(['error' => 'Error getting filtered items count']);
	}

	// Count total terms without any search filter for 'recordsTotal'
	$count_args_total = [
		'taxonomy' => 'pro_cate',
		'hide_empty' => false,
	];
	$recordsTotal = wp_count_terms($count_args_total);

	if (is_wp_error($recordsTotal)) {
		wp_send_json_error(['error' => 'Error getting total items count']);
	}

	// Prepare data for DataTables, adding hierarchical information
	$data = [];
	foreach ($terms as $term) {
		$hierarchy_info = get_pro_cate_hierarchy_info($term->term_id, $all_wp_terms_map);
		$depth = $hierarchy_info['depth'];
		$ancestor_names = $hierarchy_info['full_path_names'];

		// Define the indentation characters. You can use HTML entities like   or —
		$indent_chars = '— '; // Example: "— "
		$indent = str_repeat($indent_chars, $depth);

		$cate = [
			'id' => $term->term_id,
			'name' => $indent . $term->name, // Indented name for visual hierarchy
			'original_name' => $term->name, // Keep original name if needed for sorting/filtering on client-side
			'description' => $term->description,
			'parent_id' => $term->parent, // The numeric ID of the parent
			'parent_name' => ($term->parent !== 0 && isset($all_wp_terms_map[$term->parent])) ? $all_wp_terms_map[$term->parent]->name : '',
			'full_path' => !empty($ancestor_names) ? implode(' > ', $ancestor_names) . ' > ' . $term->name : $term->name, // Full path: Root > Parent > Current
			'depth' => $depth, // Numeric depth, useful for client-side sorting or custom rendering
			'url' => ProductUrlGenerator::createCategoryUrl($term->name, $term->term_id),
		];

		// Retrieve and format thumbnail information
		$thumbnail_id = get_term_meta($term->term_id, 'pro_cate_thumbnail_id', true);
		// Get the URL for the attachment. 'thumbnail' size is usually good for tables.
		$cate['thumbnail_url'] = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'thumbnail') : '';
		$cate['pro_cate_thumbnail_id'] = $thumbnail_id; // Keep ID for potential JS use
		$data[] = $cate;
	}

	// Send the JSON response required by DataTables
	$response_data = [
		'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1, // DataTables draw counter
		'data' => $data, // Array of category data for the current page
		'recordsTotal' => $recordsTotal, // Total records without filtering
		'recordsFiltered' => $recordsFiltered, // Total records after filtering
	];

	wp_send_json_success($response_data);
}

//-------------------------------------
add_action('wp_ajax_add_to_wishlist', 'handle_add_to_wishlist');
add_action('wp_ajax_nopriv_add_to_wishlist', 'handle_add_to_wishlist'); 

function handle_add_to_wishlist() {
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
	
	if (!is_user_logged_in( )){
		wp_send_json_error(['message' => __('Please login!', LANG_ZONE),'redirect_to_login' => true]);
		return;
	}
	$user_id = get_current_user_id();
    $product_id = sanitize_text_field($_POST['product_id']);
    $customer = get_current_customer();
	$product_liked = $customer->getProductLikeTotal($product_id);
    if ($customer->addToWishlist($product_id)) {
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
	$erp_api = new ERP_API_Handler(FAKE_DATA);
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

add_action('wp_ajax_sync_stores_from_erp', 'sync_stores_from_erp_callback');

function sync_stores_from_erp_callback() {
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Không đủ quyền');
	}
	$delay = 600; // 10 phút
	if (!vcl_can_sync_store_branch($delay)) {
		$next = get_option('vcl_store_sync_last_time', 0) + $delay;
		$remain = max(0, $next - time());
		wp_send_json_error("Bạn chỉ được đồng bộ mỗi 10 phút! Đợi thêm ".ceil($remain/60)." phút nữa.");
	}


	$erp_api = new ERP_API_Client();
	$branches = $erp_api->list_branchs();

	if (is_wp_error($branches)) {
		wp_send_json_error($branches->get_error_message());
	}
	if(!($branches)) {
		wp_send_json_error('Không có dữ liệu từ ERP');
	}
	// Build current map from WP
	$existing_posts = get_posts([
		'post_type' => 'store_system',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	]);
	$wp_map = [];
	foreach ($existing_posts as $post) {
		$wp_map[get_post_meta($post->ID, '_erp_branch_id', true)] = $post->ID;
	}

	$erp_ids = [];
	$synced = [];

	foreach ($branches as $branch) {
	    $erp_id = $branch['custom_selling_warehouse'];
	    $erp_ids[] = $erp_id;
	    $title = $branch['branch'];
	    $address = $branch['custom_address'] ?? '';
	    $phone = $branch['custom_mobile'] ?? '';
	    $map_url = $branch['custom_map'] ?? '';
	    $custom_image_path = $branch['custom_image'] ?? '';
		$image_url = $custom_image_path?$erp_api->erp_item_image($custom_image_path):'';
	    if (isset($wp_map[$erp_id])) {
	        $post_id = $wp_map[$erp_id];
	        $synced[] = ['id' => $post_id, 'status' => 'updated'];
	    } else {
	        $post_id = wp_insert_post([
	            'post_title' => $title,
	            'post_type' => 'store_system',
	            'post_status' => 'publish',
	        ]);
	        if (is_wp_error($post_id)) continue;
	        $synced[] = ['id' => $post_id, 'status' => 'inserted'];
	    }
		if ($post_id){
			if ($image_url) set_post_thumbnail_from_url($post_id, $image_url);
			update_store_meta_fields($title, $post_id, $address, $phone, $map_url, $erp_id);
		}
	}

	// Draft old ones
	foreach ($wp_map as $erp_id => $post_id) {
		if (!in_array($erp_id, $erp_ids)) {
			wp_update_post([
				'ID' => $post_id,
				'post_status' => 'draft',
			]);
			$synced[] = ['id' => $post_id, 'status' => 'set_draft'];
		}
	}
	update_option('vcl_store_sync_last_time', time());
	wp_send_json_success(['synced' => $synced]);
}
function set_post_thumbnail_from_url($post_id, $image_url) {
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	if (empty($image_url) || !$post_id) return false;

	$query = new WP_Query([
		'post_type'      => 'attachment',
		'posts_per_page' => 1,
		'post_status'    => 'inherit',
		'meta_query'     => [
			[
				'key'   => '_origin_image_url',
				'value' => $image_url,
				'compare' => '='
			]
		]
	]);
	if ($query->have_posts()) {
		$attachment_id = $query->posts[0]->ID;
		set_post_thumbnail($post_id, $attachment_id);
		return $attachment_id;
	}

	$attachment_id = media_sideload_image($image_url, $post_id, null, 'id');
	if (!is_wp_error($attachment_id)) {
		set_post_thumbnail($post_id, $attachment_id);
		update_post_meta($attachment_id, '_origin_image_url', $image_url);
		return $attachment_id;
	}
	return false;
}

function update_store_meta_fields($title, $post_id, $address, $phone, $map_url, $erp_id) {
	$current_title = get_the_title($post_id);
	if ($current_title !== $title) {
		wp_update_post([
			'ID' => $post_id,
			'post_title' => $title
		]);
	}
    update_post_meta($post_id, 'store_address', $address);
    update_post_meta($post_id, 'store_phone', $phone);
    update_post_meta($post_id, 'store_google_map', $map_url);
    update_post_meta($post_id, 'store_google_map_link', $map_url);
    update_post_meta($post_id, '_erp_branch_id', $erp_id);
}
function vcl_can_sync_store_branch($delay = 600) { // 600s = 10 phút
	$last = get_option('vcl_store_sync_last_time', 0);
	return (time() - $last > $delay);
}


add_action('wp_ajax_sync_brands_from_erp', 'sync_brands_from_erp_callback');

function sync_brands_from_erp_callback() {
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Không đủ quyền');
	}
	$delay = 600; // 10 phút
	if (!vcl_can_sync_brand($delay)) {
		$next = get_option('vcl_brand_sync_last_time', 0) + $delay;
		$remain = max(0, $next - time());
		wp_send_json_error("Bạn chỉ được đồng bộ mỗi 10 phút! Đợi thêm ".ceil($remain/60)." phút nữa.");
	}

	$erp_api = new ERP_API_Client();
	$brands = $erp_api->list_brands(); // <-- Hàm lấy brands bên ERP, đổi đúng tên

	if (is_wp_error($brands)) {
		wp_send_json_error($brands->get_error_message());
	}
	if(!($brands)) {
		wp_send_json_error('Không có dữ liệu từ ERP');
	}
	// Build current map from WP
	$existing_posts = get_posts([
		'post_type' => 'brands',
		'posts_per_page' => -1,
		'post_status' => 'publish',
	]);
	$wp_map = [];
	foreach ($existing_posts as $post) {
		$wp_map[get_post_meta($post->ID, '_erp_brand_id', true)] = $post->ID;
	}

	$erp_ids = [];
	$synced = [];

	foreach ($brands as $brand) {
		$erp_id = $brand['name'];
		$erp_ids[] = $erp_id;
		$title = $brand['brand'];
		$custom_image_path = $brand['custom_image'] ?? '';
		$image_url = $custom_image_path ? $erp_api->erp_item_image($custom_image_path) : '';
		if (isset($wp_map[$erp_id])) {
			$post_id = $wp_map[$erp_id];
			$synced[] = ['id' => $post_id, 'status' => 'updated'];
		} else {
			$post_id = wp_insert_post([
				'post_title' => $title,
				'post_type' => 'brand_system',
				'post_status' => 'publish',
			]);
			if (is_wp_error($post_id)) continue;
			$synced[] = ['id' => $post_id, 'status' => 'inserted'];
		}
		if ($post_id){
			if ($image_url) set_post_thumbnail_from_url($post_id, $image_url);
			update_brand_meta_fields($post_id, $erp_id);
		}
	}

	// Draft old ones
	foreach ($wp_map as $erp_id => $post_id) {
		if (!in_array($erp_id, $erp_ids)) {
			wp_update_post([
				'ID' => $post_id,
				'post_status' => 'draft',
			]);
			$synced[] = ['id' => $post_id, 'status' => 'set_draft'];
		}
	}
	update_option('vcl_brand_sync_last_time', time());
	wp_send_json_success(['synced' => $synced]);
}


function update_brand_meta_fields($post_id, $erp_id) {
	update_post_meta($post_id, '_erp_brand_id', $erp_id);
}

function vcl_can_sync_brand($delay = 600) {
	$last = get_option('vcl_brand_sync_last_time', 0);
	return (time() - $last > $delay);
}

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