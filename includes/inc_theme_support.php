<?php
function theme_setup() {
	// Enable automatic feed links
	// Custom menu areas
	register_nav_menus( array(
		'main-nav' => 'Main Menu',
		'mobile-nav' => 'Mobile Menu',
		'mobile-category-nav' => 'Mobile Category Menu',
		'footer-nav' => 'Footer Menu',
	) );
	// Enable automatic feed links
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	// Enable featured image
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'excerpt' );
}
add_action( 'after_setup_theme', 'theme_setup' );
//---------------
function add_additional_class_on_li($classes, $item, $args) {
	global $product_menu_array;
	$product_menu_array = [21];
	if(isset($args->add_li_class)) {
		$classes[] = $args->add_li_class;
	}
	$block_id = get_post_meta($item->ID, '_menu_item_custom_dropdown', true);
	if (!empty($block_id)) {
		$classes[] = 'menu-item-has-children';
	}
	$erp_api = new ERP_API_Handler(FAKE_DATA);
	if ($category_id = get_query_var('product_cate_id')){
		$api_data = $erp_api->get_category($category_id);
	}elseif ($product_id = get_query_var('product_id')){
		$api_data = get_product($product_id);
	}
	if (isset($api_data) && !is_wp_error($api_data) && in_array($item->ID,$product_menu_array)){
		$classes[] = 'current-menu-item';
	}

	return $classes;
}
add_filter('nav_menu_css_class', 'add_additional_class_on_li', 1, 3);
//----
function add_additional_class_on_a($classes, $item, $args)
{
	if (isset($args->add_a_class)) {
		$classes['class'] = $args->add_a_class;
	}
	return $classes;
}

add_filter('nav_menu_link_attributes', 'add_additional_class_on_a', 1, 3);
//---------------


class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
	// Bắt đầu thẻ <ul> cho sub-menu
	function start_lvl(&$output, $depth = 0, $args = null) {
		$indent = str_repeat("\t", $depth); // Định dạng khoảng cách
		$classes = ['sub-menu'];
		$class_names = join(' ', apply_filters('nav_menu_submenu_css_class', $classes, $args, $depth));
		$class_names = ' class="' . esc_attr($class_names) . '"';

		// Thêm cấu trúc custom sub-menu
		$output .= "\n{$indent}<div class=\"custom-submenu\">\n";
		$output .= "{$indent}\t<div>\n";
		$output .= "{$indent}\t\t<ul{$class_names}>\n";
	}

	function end_lvl(&$output, $depth = 0, $args = null) {
		$indent = str_repeat("\t", $depth);
		$output .= "{$indent}\t\t</ul>\n";
		$output .= "{$indent}\t</div>\n";
		$output .= "{$indent}</div>\n";
	}

	function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
		$indent = ($depth) ? str_repeat("\t", $depth) : '';
		$classes = empty($item->classes) ? [] : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
		$id = $id ? ' id="' . esc_attr($id) . '"' : '';

		$output .= $indent . '<li' . $id . $class_names . '>';

		$atts = [];
		$atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = !empty($item->target) ? $item->target : '';
		$atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
		$atts['href'] = !empty($item->url) ? $item->url : '';

		$atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

		$attributes = '';
		foreach ($atts as $attr => $value) {
			if (!empty($value)) {
				$attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
			}
		}

		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}

	// Kết thúc thẻ </li>
	function end_el(&$output, $item, $depth = 0, $args = null) {
		$output .= "</li>\n";
	}
}

//---------------------
add_action('wp_head', function() {
	global $custom_header;
	if (!empty($custom_header)) {
		echo '<meta name="custom-header" content="' . esc_attr($custom_header) . '">';
	}
});
//-------------------------
add_action('wp_footer', function() {
	if (class_exists('Breadcrumb')) {
		$breadcrumb = Breadcrumb::getInstance();
		$breadcrumbs = $breadcrumb->getBreadcrumbs();
		$breadcrumb_schema = [
			"@context" => "https://schema.org",
			"@type" => "BreadcrumbList",
			"itemListElement" => []
		];

		foreach ($breadcrumbs as $index => $breadcrumb_item) {
			$breadcrumb_schema["itemListElement"][] = [
				"@type" => "ListItem",
				"position" => $index + 1,
				"name" => $breadcrumb_item["label"],
				"item" => $breadcrumb_item["url"]
			];
		}

		echo '<script type="application/ld+json">' . json_encode($breadcrumb_schema) . '</script>';
		echo '<script id="translation-js-extra">';
		?>
		let translations = {
			cart_no_item : "<?php _e('There are no items in your cart.',LANG_ZONE)  ?>",
			cart_error : "<?php _e('There was an error loading the cart. Please try again later.',LANG_ZONE)  ?>",
			cart_out_stock : "<?php _e('Product is temporarily out of stock!',LANG_ZONE)  ?>",
			cart_select_variation : "<?php _e('Please select full product variation.',LANG_ZONE)  ?>",
			cart_invalid_product : "<?php _e('Invalid product or not enough variants selected.',LANG_ZONE)  ?>",
			branch_not_found : "<?php _e('Branch information not found.',LANG_ZONE)  ?>",
			max_compare : "<?php _e('Please remove products to continue comparing!',LANG_ZONE)  ?>",
			added_compare : "<?php _e('Product has been added to comparison list.',LANG_ZONE)  ?>",
            add_product : "<?php _e('Add product',LANG_ZONE)  ?>",
            added : "<?php _e('Added',LANG_ZONE)  ?>",
            compare : "<?php _e('Compare',LANG_ZONE)  ?>",
            only_difference : "<?php _e('Show only the difference',LANG_ZONE)  ?>",
            products_left : "<?php _e('products left',LANG_ZONE)  ?>",
            not_available : "<?php _e('Not available',LANG_ZONE)  ?>",
		};
        let isUserLoggedIn = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
        const attributeTranslations = {
            "color": "<?php _e('Color', LANG_ZONE)  ?>",
            "weight": "<?php _e('Weight', LANG_ZONE)  ?>",
            "play_style": "<?php _e('Play style', LANG_ZONE)  ?>",
            "play_mode": "<?php _e('Play mode', LANG_ZONE)  ?>",
            "skill_level": "<?php _e('Skill level', LANG_ZONE)  ?>",
            };
		<?php
		echo '</script>';
        ?>

        <?php
	} else {
		error_log("Class Breadcrumb không tồn tại!");
	}
});
//------------------------
function check_email_or_phone_exists($input) {
	if (is_email($input)) {
		if (email_exists($input)) {
			return __('Email is already registered.',LANG_ZONE);
		}
	} elseif (preg_match('/^[0-9]{10,15}$/', $input)) {
		$users = get_users(array(
			'meta_key' => 'phone_number',
			'meta_value' => $input,
			'number' => 1,
		));
		if (!empty($users)) {
			return __('Phone number is already registered.',LANG_ZONE);
		}
	} else {
		return __('Invalid email or phone number.',LANG_ZONE);
	}
	return false;
}
//----
add_filter('widget_text', 'do_shortcode', 11);
//------------------------
add_role(
	'customer', // Slug của role
	__('Customer', LANG_ZONE),
	array(
		'read' => true,
		'edit_posts' => false,
		'edit_pages' => false,
		'publish_posts' => false,
		'create_posts' => false,

	)
);
//---------------------------------
function NoImage($url){
    $no_img = IMG_URL.'/No_Image_Available.jpg';
    return ($url!=='')?$url:$no_img;
}
function randomImage(){
	$rand_num = rand(1,8);
    return IMG_URL.'san-pham/product_'.$rand_num.'.jpg';
}
//-------------------------
function add_so_sanh_page_class( $classes ) {
	if ( is_compare_page() ) {
		$classes[] = 'compare-products-page';
	}
	return $classes;
}
add_filter( 'body_class', 'add_so_sanh_page_class' );

function is_compare_page() {
	global $wp_query;
	if ( isset( $wp_query->query_vars['compare_products'] ) ) {
		return true;
	}
	return false;
}
function search_brand_by_title($search_keyword) {
	global $wpdb;
	$search_keyword = esc_sql($search_keyword);

	$query = "SELECT ID, post_title FROM $wpdb->posts 
              WHERE post_type = 'brands' 
              AND post_status = 'publish' 
              AND post_title LIKE '%$search_keyword%'
              LIMIT 1";

	$result = $wpdb->get_row($query);

	if ($result) {
		$result->thumbnail = get_the_post_thumbnail_url($result->ID, 'full'); // Lấy ảnh đại diện
	}

	return $result;
}
//------------
function addParamToUrl($url, $param){
    return $url.'-tab.'.$param;
}
function get_local_file_path_from_url($url) {
	$upload_dir = wp_upload_dir();
	$baseurl = $upload_dir['baseurl'];
	$basedir = $upload_dir['basedir'];

	if (strpos($url, $baseurl) === 0) {
		return str_replace($baseurl, $basedir, $url);
	}

	return false;
}
//---------
function vcl_get_shop_page() {
	return get_field('shop_page','options');
}
//----------
/**
 * Load custom template for the order received page.
 */
function vcl_template_redirect_order_received() {
    // Check if our custom query var 'vcl_page' is set to 'order_received'
    if ( get_query_var( 'vcl_page' ) == 'order_received' ) {
        // Construct the path to your custom template file
        $template_path = get_template_directory() . '/template-parts/order-received.php'; // Adjust path if needed

        if ( file_exists( $template_path ) ) {
            include( $template_path );
            exit; // Important: Stop WordPress from loading the default template
        } else {
            // Optional: Handle case where template file is missing
            wp_die( 'Template file not found for order received page.' );
        }
    }
}
add_action( 'template_redirect', 'vcl_template_redirect_order_received' );
//-----------------
function my_custom_nav_menu_widget_options( $widget, $return, $instance ) {
    // Chỉ áp dụng cho widget 'nav_menu'
    if ( 'nav_menu' !== $widget->id_base ) {
        return;
    }
    $hide_on_mobile = isset( $instance['hide_on_mobile'] ) ? (bool) $instance['hide_on_mobile'] : false;
    ?>
    <p>
        <input class="checkbox" type="checkbox" <?php checked( $hide_on_mobile ); ?> id="<?php echo esc_attr( $widget->get_field_id( 'hide_on_mobile' ) ); ?>" name="<?php echo esc_attr( $widget->get_field_name( 'hide_on_mobile' ) ); ?>" />
        <label for="<?php echo esc_attr( $widget->get_field_id( 'hide_on_mobile' ) ); ?>"><?php esc_html_e( 'Ẩn trên Điện thoại', LANG_ZONE );  ?></label>
    </p>
    <?php
}
add_action( 'in_widget_form', 'my_custom_nav_menu_widget_options', 10, 3 );
//---------------------
function my_custom_nav_menu_widget_update( $instance, $new_instance, $old_instance, $widget ) {
     if ( 'nav_menu' !== $widget->id_base ) {
        return $instance;
    }
    $instance['hide_on_mobile'] = isset( $new_instance['hide_on_mobile'] ) ? (bool) $new_instance['hide_on_mobile'] : false;
    return $instance; 
}
add_filter( 'widget_update_callback', 'my_custom_nav_menu_widget_update', 10, 4 );
//---------------
/**
 * Thêm class CSS 'hide-on-mobile' vào widget wrapper nếu tùy chọn được check.
 * Áp dụng cho tất cả các widget trong sidebar được gọi bằng dynamic_sidebar().
 *
 * @param array $params Các tham số của sidebar động.
 * @return array        Các tham số đã được lọc.
 */
function my_custom_widget_wrapper_class( $params ) {
    global $wp_registered_widgets; // Biến toàn cục chứa thông tin các widget đã đăng ký

    $widget_id = $params[0]['widget_id'];
    $widget_obj = $wp_registered_widgets[$widget_id];
    $widget_opt = get_option( $widget_obj['callback'][0]->option_name );
    $widget_num = $widget_obj['params'][0]['number'];

    // Chỉ kiểm tra các widget Navigation Menu
    if ( isset($widget_obj['callback'][0]->id_base) && $widget_obj['callback'][0]->id_base === 'nav_menu' ) {
        // Lấy cài đặt cụ thể của instance widget này
        if ( isset( $widget_opt[$widget_num] ) ) {
            $instance = $widget_opt[$widget_num];
            $option_key = 'hide_on_mobile';

            // Kiểm tra xem tùy chọn "hide_on_mobile" có được set và là true không
            if ( isset( $instance[$option_key] ) && $instance[$option_key] ) {
                // Thêm class 'hide-on-mobile' vào before_widget
                // Giả sử before_widget có dạng <tag id="%1$s" class="widget %2$s">
                $params[0]['before_widget'] = preg_replace( '/class="/', 'class="hide-on-mobile ', $params[0]['before_widget'], 1 );
            }
        }
    }

    return $params; // Trả về tham số đã được chỉnh sửa (hoặc không)
}
// Hook vào filter dynamic_sidebar_params
add_filter( 'dynamic_sidebar_params', 'my_custom_widget_wrapper_class' );
//---------------------------------------

    /**
     * Helper function to get a Customer object by user ID.
     *
     * @param int|WP_User|null $user User ID, WP_User object, or null (for current user).
     * @return Customer|false Customer object or false.
     */
    function get_customer( $user = null ) {
        return CustomerManager::get_customer( $user );
    }

    /**
     * Helper function to get the current logged-in Customer object.
     *
     * @return Customer|false Customer object or false.
     */
    function get_current_customer() {
        return CustomerManager::get_current_customer();
    }

    /**
     * Helper function to get a Product object by ID using the ProductManager.
     *
     * @param string|int $product_id The ID of the product.
     * @return Product|false Product object or false.
     */
    function get_product( $product_id , $clear_cache = false, $get_stock = true)  {
        return ProductManager::get_product( $product_id , $clear_cache , $get_stock);
    }