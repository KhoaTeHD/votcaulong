<?php
// ----------- cms_block -----------------------
function register_cms_block_cpt() {
	$args = [
		'labels' => [
			'name' => __('HTML Blocks',LANG_ZONE),
			'singular_name' => __('HTML Block', LANG_ZONE),
			'add_new' => __('Add New Item', LANG_ZONE),
			'add_new_item' => __('Add New Item', LANG_ZONE),
			'edit_item' => __('Edit HTML Block', LANG_ZONE),
			'new_item' => __('New HTML Block', LANG_ZONE),
			'view_item' => __('View HTML Block', LANG_ZONE),
			'search_items' => __('Search HTML Blocks', LANG_ZONE),
			'not_found' => __('No HTML Blocks found', LANG_ZONE),
			'not_found_in_trash' => __('No HTML Blocks found in Trash', LANG_ZONE),
		],
		'public' => true,
		'has_archive' => false,
		'menu_icon' => 'dashicons-editor-code',
		'supports' => ['title', 'editor'],
		'rewrite' => ['slug' => 'cms_block'],
	];
	register_post_type('cms_block', $args);
}
add_action('init', 'register_cms_block_cpt');
//----------
// Thêm cột Shortcode vào bảng quản lý CMS Block
function add_shortcode_column_to_cms_block($columns) {
	// Thêm cột Shortcode vào sau cột Title
	$new_columns = [];
	foreach ($columns as $key => $title) {
		$new_columns[$key] = $title;
		if ($key === 'title') {
			$new_columns['shortcode'] = 'Shortcode';
		}
	}
	return $new_columns;
}
add_filter('manage_cms_block_posts_columns', 'add_shortcode_column_to_cms_block');

// Hiển thị nội dung trong cột Shortcode
function render_shortcode_column_in_cms_block($column, $post_id) {
	if ($column === 'shortcode') {
		echo '<code>[html_block id="' . $post_id . '"]</code>';
	}
}
add_action('manage_cms_block_posts_custom_column', 'render_shortcode_column_in_cms_block', 10, 2);
