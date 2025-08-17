<?php
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

	// Ưu tiên thumbnail_id từ media library
	if (isset($_POST['thumbnail_id']) && !empty($_POST['thumbnail_id'])) {
		$thumbnail_id = intval($_POST['thumbnail_id']);
		if (get_post($thumbnail_id)) { // Check if attachment exists
			$thumbnail_url = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
		} else {
			$thumbnail_id = null; // Invalid ID
		}
	} elseif (isset($_FILES['thumbnail'])) {
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
	// Xóa meta cũ trước khi thêm mới hoặc cập nhật
	delete_term_meta($cate_id, 'pro_cate_thumbnail_id');
	delete_term_meta($cate_id, 'pro_cate_thumbnail_url'); // Xóa URL ERP nếu có

	if ($thumbnail_id) {
		add_term_meta($cate_id, 'pro_cate_thumbnail_id', $thumbnail_id);
	} else {
		// If no thumbnail is set (either uploaded or selected), ensure meta is empty
		// This handles cases where a user clears the thumbnail
	}

	// Lấy dữ liệu danh mục đã cập nhật
	$updated_term = get_term($cate_id, 'pro_cate');
//	$updated_thumbnail_url = get_term_meta($cate_id, 'pro_cate_thumbnail_url', true);

	$response_data = array(
		'id' => $cate_id,
		'name' => $updated_term->name,
		'description' => $updated_term->description,
		'thumbnail_url' => $thumbnail_url, // Use the URL determined above
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

add_action('wp_ajax_sync_pro_cate_from_erp', 'sync_pro_cate_from_erp_callback');

function sync_pro_cate_from_erp_callback() {
	// 1. Security Check
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Không đủ quyền');
	}

	$erp_api = new ERP_API_Client(); // Instantiate your ERP API client

	$erp_categories = $erp_api->list_all_item_groups();

	if (is_wp_error($erp_categories)) {
		wp_send_json_error('ERP API Error during category sync: ' . $erp_categories->get_error_message());
	} elseif (empty($erp_categories) || !is_array($erp_categories)) {
		wp_send_json_error('ERP API returned empty or invalid category list. No syncing performed.');
	} else {
		$wp_terms_by_parent_and_name = [];
		$all_wp_terms_before_sync = get_terms([
			'taxonomy' => 'pro_cate',
			'hide_empty' => false,
			'get' => 'all' // Get all terms to build the map
		]);

		if (is_wp_error($all_wp_terms_before_sync)) {
			wp_send_json_error('Error retrieving existing WP terms before sync: ' . $all_wp_terms_before_sync->get_error_message());
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
			wp_send_json_error('Error retrieving WP terms for deletion: ' . $all_wp_terms_after_sync->get_error_message());
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
		wp_send_json_success(['message' => 'Product categories synced successfully!']);
	}
}

add_action('wp_ajax_sync_stores_from_erp', 'sync_stores_from_erp_callback');

function sync_stores_from_erp_callback() {
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'themetoken-security')) {
		wp_send_json_error(__('Invalid request', LANG_ZONE));
	}
	if (!current_user_can('manage_options')) {
		wp_send_json_error('Không đủ quyền');
	}
	$delay = 300; // 10 phút
	if (!vcl_can_sync_store_branch($delay)) {
		$next = get_option('vcl_store_sync_last_time', 0) + $delay;
		$remain = max(0, $next - time());
		wp_send_json_error("Bạn chỉ được đồng bộ mỗi 5 phút! Đợi thêm ".ceil($remain/60)." phút nữa.");
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
