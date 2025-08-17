<?php
function my_admin_title ( $admin_title, $title ) {
	// $all_brands = get_all_brands();
	return $title . ' ‹ ' . get_bloginfo( 'name' ) . ' — ' . 'Admin';
}
add_filter( 'admin_title', 'my_admin_title', 10, 2 );
//----------------------------
function my_custom_login() {
	echo '<link rel="stylesheet" type="text/css" href="' . get_stylesheet_directory_uri() . '/backend/css/login-style.css?ver=1.61226" />';
}
add_action('login_footer', 'my_custom_login');
//-----------------------
function load_custom_wp_admin_style() {
	//$time = wp_get_theme()->get('Version');
//	$time = time();
//	wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');
	wp_enqueue_style( 'datatable_admin', get_stylesheet_directory_uri(). '/backend/datatables/datatables.min.css', false, '2.2.2.1' );
	wp_enqueue_style( 'custom_wp_admin', get_stylesheet_directory_uri(). '/backend/css/admin-style.css', ['datatable_admin'], THEME_VER );
//	wp_enqueue_style( 'custom_wp_admin_css' );
	wp_enqueue_script( 'datatables_admin',get_stylesheet_directory_uri() . '/backend/datatables/datatables.min.js',array( 'jquery' ),'2.2.2.1' );
	wp_enqueue_script( 'admin-scripts',get_stylesheet_directory_uri() . '/backend/admin_script.js',array( 'jquery' ),THEME_VER );
	wp_localize_script( 'admin-scripts', 'MyVars', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),'nonce'    => wp_create_nonce('themetoken-security'),));
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

add_action('admin_enqueue_scripts', function($hook) {
	if (
		$hook === 'edit.php' &&
		isset($_GET['post_type']) && $_GET['post_type'] === 'store_system'
	) {
		wp_enqueue_script('my-erp-sync', get_template_directory_uri().'/backend/erp_store_system.js', ['jquery'], THEME_VER, true);
	}
});
add_filter('manage_store_system_posts_columns', function($columns){
	// Thêm cột thumbnail sau checkbox
	$columns = array_slice($columns, 0, 1, true) +
	           ['thumbnail' => 'Thumbnail'] +
	           array_slice($columns, 1, null, true);

	// Thêm cột meta
	$columns['store_phone'] = 'Số điện thoại';
	$columns['store_address'] = 'Địa chỉ';
	return $columns;
});

add_action('manage_store_system_posts_custom_column', function($column, $post_id){
	if ($column === 'thumbnail') {
		echo get_the_post_thumbnail($post_id, [60, 60]);
	}
	if ($column === 'store_phone') {
		echo esc_html(get_post_meta($post_id, 'store_phone', true));
	}
	if ($column === 'store_address') {
		echo esc_html(get_post_meta($post_id, 'store_address', true));
	}
}, 10, 2);
//--------------
add_action('admin_enqueue_scripts', function($hook) {
	if (
		$hook === 'edit.php' &&
		isset($_GET['post_type']) && $_GET['post_type'] === 'brands'
	) {
		wp_enqueue_script('my-erp-sync', get_template_directory_uri().'/backend/erp_brands.js', ['jquery'], THEME_VER, true);
	}
});
add_filter('manage_brands_posts_columns', function($columns){
	// Thêm cột thumbnail sau checkbox
	$columns = array_slice($columns, 0, 1, true) +
	           ['thumbnail' => 'Thumbnail'] +
	           array_slice($columns, 1, null, true);

	return $columns;
});

add_action('manage_brands_posts_custom_column', function($column, $post_id){
	if ($column === 'thumbnail') {
		echo get_the_post_thumbnail($post_id, [60, 60]);
	}

}, 10, 2);
//------------------------ USER List
add_filter('manage_users_columns', 'add_custom_user_column');
function add_custom_user_column($columns) {
	$columns['erp_name'] = __('ERP ID', LANG_ZONE);
	$columns['custom_actions'] = __('Actions', LANG_ZONE);
	return $columns;
}
add_action('manage_users_custom_column', 'show_custom_user_column_content', 10, 3);
function show_custom_user_column_content($value, $column_name, $user_id) {
	if ($column_name === 'erp_name') {
		$erp_name = get_user_meta($user_id, 'erp_name', true);
		return esc_html($erp_name ?: '—');
	}
	if ($column_name === 'custom_actions') {
		$sync_url = add_query_arg([
			'action' => 'sync_to_erp',
			'user_id' => $user_id,
			'_wpnonce' => wp_create_nonce('sync_to_erp_' . $user_id),
		], admin_url('users.php'));

		return '<a href="' . esc_url($sync_url) . '">' . __('Sync to ERP', LANG_ZONE) . '</a>';
	}
	return $value;
}
add_filter('manage_users_sortable_columns', 'make_custom_user_column_sortable');
function make_custom_user_column_sortable($columns) {
	$columns['erp_name'] = 'erp_name';
	return $columns;
}
//---- sync
add_action('admin_init', function() {
	if (
		isset($_GET['action'], $_GET['user_id'], $_GET['_wpnonce']) &&
		$_GET['action'] === 'sync_to_erp' &&
		current_user_can('manage_options') // Có thể đổi sang quyền phù hợp
	) {
		$user_id = (int) $_GET['user_id'];

		if (!wp_verify_nonce($_GET['_wpnonce'], 'sync_to_erp_' . $user_id)) {
			wp_die(__('Nonce verification failed', LANG_ZONE));
		}

		$result = Customer::sync_to_erp_static($user_id);

		// Nếu WP_Error, ghi log hoặc gán mã lỗi tùy ý
		if (is_wp_error($result)) {
			wp_redirect(add_query_arg([
				'erp_synced' => '0',
				'erp_error' => urlencode($result->get_error_message())
			], admin_url('users.php')));
		} else {
			wp_redirect(add_query_arg('erp_synced', '1', admin_url('users.php')));
		}
		exit;
	}
});
add_action('admin_notices', function() {
	if (isset($_GET['erp_synced'])) {
		if ($_GET['erp_synced'] === '1') {
			echo '<div class="notice notice-success is-dismissible"><p>Đã đồng bộ ERP thành công.</p></div>';
		} else {
			$msg = isset($_GET['erp_error']) ? urldecode($_GET['erp_error']) : 'Đồng bộ ERP thất bại.';
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($msg) . '</p></div>';
		}
	}
});


