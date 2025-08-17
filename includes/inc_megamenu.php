<?php
function custom_nav_menu_item_dropdown($item_id, $item, $depth, $args, $id) {
	// Giá trị hiện tại của dropdown
	$dropdown_value = get_post_meta($item_id, '_menu_item_custom_dropdown', true);

	// Lấy danh sách các cms_block
	$cms_blocks = get_posts([
		'post_type' => 'cms_block',
		'posts_per_page' => -1, // Lấy tất cả
		'post_status' => 'publish', // Chỉ lấy các bài viết được xuất bản
		'orderby' => 'title',
		'order' => 'ASC'
	]);

	// Tùy chọn dropdown
	$options = ['' => __('None', 'LANG_ZONE')]; // Thêm tùy chọn mặc định "None"

	if (!empty($cms_blocks)) {
		foreach ($cms_blocks as $block) {
			$options[$block->ID] = $block->post_title; // Key là ID, Value là Title
		}
	}

	// HTML cho dropdown
	echo '<p class="field-custom description description-wide">';
	echo '<label for="edit-menu-item-dropdown-' . $item_id . '">';
	echo __('Dropdown settings',LANG_ZONE);
	echo '<select class="widefat" id="edit-menu-item-dropdown-' . $item_id . '" name="menu-item-custom-dropdown[' . $item_id . ']">';
	foreach ($options as $key => $label) {
		$selected = ($dropdown_value == $key) ? 'selected' : '';
		echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
	}
	echo '</select>';
	echo '</label>';
	echo '</p>';
}
add_action('wp_nav_menu_item_custom_fields', 'custom_nav_menu_item_dropdown', 10, 5);
//----------------------
function save_custom_nav_menu_dropdown($menu_id, $menu_item_db_id) {
	if (isset($_POST['menu-item-custom-dropdown'][$menu_item_db_id])) {
		$dropdown_value = sanitize_text_field($_POST['menu-item-custom-dropdown'][$menu_item_db_id]);
		update_post_meta($menu_item_db_id, '_menu_item_custom_dropdown', $dropdown_value);
	} else {
		delete_post_meta($menu_item_db_id, '_menu_item_custom_dropdown');
	}
}
add_action('wp_update_nav_menu_item', 'save_custom_nav_menu_dropdown', 10, 2);

//--------
function display_cms_block_in_menu($item_output, $item, $depth, $args) {
	$block_id = get_post_meta($item->ID, '_menu_item_custom_dropdown', true);

	if (!empty($block_id)) {
		// Lấy nội dung của block
		$block_post = get_post($block_id);
		if ($block_post) {
			//$content = apply_filters('the_content', $block_post->post_content);
			$content = $block_post->post_content;
			$item_output .= '<div class="sub-menu-item"><div><div class="megamenu-html-block">' . $content . '</div></div></div>';
		}
	}

	return $item_output;
}
add_filter('walker_nav_menu_start_el', 'display_cms_block_in_menu', 10, 4);
//================================================================

