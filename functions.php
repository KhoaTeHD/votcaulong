<?php
define('LANG_ZONE', 'vcl-shop');
define('IMG_URL', get_template_directory_uri().'/assets/images/');
define('THEME_URL', get_stylesheet_directory_uri() );
define('PAYOO_USERNAME','SB_Votcaulong');
define('PAYOO_SHOP_ID','12217');
define('PAYOO_SECRET_KEY','NGFiNTZlNmRiZTE0ZGQ4MjlkMjRjOGIwOGY2MWY0YTI=');
define('PAYOO_CHECKOUT_URL','https://newsandbox.payoo.com.vn/v2/checkout');
defined('DEV_MODE') || define('DEV_MODE', false);
define('THEME_VER', DEV_MODE ? time() : '1.0.8');
define('VCL_DB_VER', 1.0);
add_filter('show_admin_bar', '__return_false');
function vcl_theme_setup() {
	load_theme_textdomain( LANG_ZONE, get_template_directory() . '/languages' );

    register_nav_menus(
        array(
            'footer-menu1' => __( 'Footer-menu-1',LANG_ZONE ),
			'footer-menu2' => __( 'Footer-menu-2',LANG_ZONE ),
			'footer-menu3' => __( 'Footer-menu-3',LANG_ZONE ),
			'footer-menu4' => __( 'Footer-menu-4',LANG_ZONE ),
			'footer-menu5' => __( 'Footer-menu-5',LANG_ZONE ),
    ));
}
add_action( 'after_setup_theme', 'vcl_theme_setup' );

function vcl_init_globals() {
    global $order_statuses;
    $order_statuses = [
        'pending'          => __('Pending', LANG_ZONE),
        'processing'       => __('Processing', LANG_ZONE),
        'paid'             => __('Paid', LANG_ZONE),
        'shipped'           => __('Shipped', LANG_ZONE),
        'completed'        => __('Completed', LANG_ZONE),
        'pending-payment'  => __('Pending payment', LANG_ZONE),
        'on-hold'          => __('On hold', LANG_ZONE),
        'failed'           => __('Failed', LANG_ZONE),
        'refunded'         => __('Refunded', LANG_ZONE),
        'cancelled'        => __('Cancelled', LANG_ZONE),

    ];
}
add_action('init', 'vcl_init_globals');

if ( SITECOOKIEPATH != COOKIEPATH ) {
	setcookie(TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN);
}
$includes_path = get_stylesheet_directory() . '/includes/';

// Danh sách các file cần nạp theo thứ tự
$priority_files = [
	'class_ProductUrlGenerator.php',
	'class_Breadcrumb.php',
	'class_Customer.php'
];

foreach ($priority_files as $file) {
	$file_path = $includes_path . $file;
	if (is_readable($file_path)) {
		require_once $file_path;
	} else {
		error_log("Không thể nạp file: $file_path");
	}
}

// Nạp các file còn lại (không trùng với priority_files)
foreach (glob($includes_path . '*.php') as $file) {
	if (!in_array(basename($file), $priority_files)) {
		if (is_readable($file)) {
			require_once $file;
		} else {
			error_log("Không thể nạp file: $file");
		}
	}
}


function vcl_theme_enqueue_scripts() {


	// Đăng ký Bootstrap CSS và JS
	wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', [], '5.3.0');

	// Đăng ký Select2 CSS
	wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0-rc.0');
	wp_enqueue_script('popper-js', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js', ['jquery'], '5.3.0', true);
	wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
	// Đăng ký Select2 JS
	wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
	wp_enqueue_script('shareon-js', get_template_directory_uri() . '/assets/shareon/shareon.iife.js', ['jquery'], '2.7.0', true);
	// Thêm CSS của Swiper
	wp_enqueue_style('swiper-css', get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.css',[],'5.3');
	// Thêm JS của Swiper
	wp_enqueue_script('swiper-js', get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.js', [], '5.3', true);
	// Đăng ký custom CSS
	wp_enqueue_style('rateit-css', get_template_directory_uri() . '/assets/rateit/rateit.css', ['bootstrap-css'], '1.1.6');
	wp_enqueue_style('shareon-css', get_template_directory_uri() . '/assets/shareon/shareon.min.css', ['rateit-css'], '2.7.0');
	wp_enqueue_style('theme-css', get_template_directory_uri() . '/assets/css/theme.min.css', ['shareon-css'], THEME_VER);
	wp_enqueue_style('content-css', get_template_directory_uri() . '/assets/css/post_content.min.css', ['bootstrap-css'], THEME_VER);
	wp_enqueue_style('shoppingcart-css', get_template_directory_uri() . '/assets/css/shopping-cart.min.css', ['bootstrap-css'], THEME_VER);
	wp_enqueue_style('account-css', get_template_directory_uri() . '/assets/css/account.min.css', ['theme-css'], THEME_VER);
	wp_enqueue_style('responsive-css', get_template_directory_uri() . '/assets/css/responsive.min.css', ['theme-css'], THEME_VER);
	//---js
	wp_enqueue_script('rateit-js', get_template_directory_uri() . '/assets/rateit/jquery.rateit.min.js', ['jquery'], '1.1.6', true);
	wp_enqueue_script('custom-libs-js', get_template_directory_uri() . '/assets/js/custom-libs.min.js', ['jquery'], THEME_VER, true);
	wp_enqueue_script('main-js', get_template_directory_uri() . '/assets/js/main.min.js', ['bootstrap-js'], THEME_VER, true);
	wp_enqueue_script('account-js', get_template_directory_uri() . '/assets/js/account.min.js', ['main-js'], THEME_VER, true);
	wp_enqueue_script('shoppingCart-js', get_template_directory_uri() . '/assets/js/shopping-cart.min.js', ['account-js'], THEME_VER, true);
	wp_enqueue_script('custom-swiper-init', get_template_directory_uri() . '/assets/js/swiper-init.min.js', ['jquery','custom-libs-js', 'swiper-js'], THEME_VER, true);

	// Only load checkout JS on shopping cart page
	if (is_page_template('page-shopping-cart.php')) {
		// Enqueue checkout script, dependent on cart storage
		wp_enqueue_script('checkout-js', get_template_directory_uri() . '/assets/js/checkout.min.js', ['jquery', 'shoppingCart-js','account-js'], THEME_VER, true); // Added vcl-cart-storage dependency

		// Localize script specifically for checkout-js
		$checkout_vars = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'vcl_checkout_nonce' => wp_create_nonce( 'vcl_checkout_nonce' ), // Create the specific nonce here
			'theme_data' => get_template_directory_uri().'/assets/data', // Path to city/district data if needed by checkout.js
			'translations' => array( // Add translations needed by checkout.js
				'accept_privacy_policy_required' => __('You must agree to the privacy policy to continue.', LANG_ZONE),
				'shipping_address_required' => __('Please enter complete shipping address information (Province/City, District, Ward/Commune, Detailed address).', LANG_ZONE),
				'customer_info_required' => __('Please enter your full name and phone number.', LANG_ZONE),
				'pickup_store_required' => __('Please select a store for pickup.', LANG_ZONE),
				'other_recipient_required' => __('Please enter complete information for the other recipient.', LANG_ZONE),
				'company_invoice_required' => __('Please enter complete company invoice information.', LANG_ZONE),

				// Add other necessary translations
			)
			// Add any other variables needed specifically by checkout.js
		);
		wp_localize_script( 'checkout-js', 'ThemeVarsCheckout', $checkout_vars ); // Target 'checkout-js' handle
	}
	//------------
	if (is_singular('brands')){
		wp_enqueue_script('productcate-js', get_template_directory_uri() . '/assets/js/category.min.js', ['main-js'], THEME_VER, true);
	}
    // Enqueue brand search script only on the list brands page
    if (is_page_template('page-list-brands.php') || is_singular('brands')) {
        wp_enqueue_script('vcl-brand-search', get_template_directory_uri() . '/assets/js/brand-search.min.js', ['jquery'], THEME_VER, true);
        wp_localize_script('vcl-brand-search', 'vcl_brand_search', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('vcl_brand_search_nonce'),
            'searching_text' => __('Searching...', LANG_ZONE),
            'no_results_text' => __('No brands found.', LANG_ZONE),
            'error_text' => __('An error occurred.', LANG_ZONE),
        ));
    }
	//------------
	wp_localize_script( 'main-js', 'ThemeVars', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ),
	                                                    'theme_images' => get_template_directory_uri().'/assets/images',
	                                                    'theme_data' => get_template_directory_uri().'/data',
	                                                    'nonce'    => wp_create_nonce('themetoken-security'),
	                                                   'is_logged_in' => is_user_logged_in() ? 1 : 0,
	                                                    'ws' => ''
	));
}
add_action('wp_enqueue_scripts', 'vcl_theme_enqueue_scripts');

function vcl_admin_enqueue_scripts($hook) {
    // Only enqueue media scripts on the specific admin page where it's needed
    // You might want to refine this condition based on the actual admin page slug
    // For example, if your product category management page has a specific hook name
    // if ('edit-tags.php' == $hook && isset($_GET['taxonomy']) && $_GET['taxonomy'] == 'pro_cate') {
    //     wp_enqueue_media();
    // }
    // For now, let's enqueue it more broadly in the admin for demonstration/testing
    wp_enqueue_media();

    // Enqueue admin-specific CSS/JS if any
    wp_enqueue_style('vcl-admin-style', get_template_directory_uri() . '/backend/css/admin-style.css', [], THEME_VER);
    wp_enqueue_script('vcl-admin-script', get_template_directory_uri() . '/backend/admin_script.js', ['jquery'], THEME_VER, true);

    // Localize script for admin AJAX calls
    wp_localize_script( 'vcl-admin-script', 'MyVars', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce('themetoken-security'),
    ));
}
add_action('admin_enqueue_scripts', 'vcl_admin_enqueue_scripts');

// Database version management
function vcl_check_db_version() {
    $current_db_version = get_option( 'vcl_db_version', '0' );
    $db_version = VCL_DB_VER; // Using the existing THEME_VER constant

    if ( version_compare( $current_db_version, $db_version, '<' ) ) {
        // Run all database setup functions
        my_theme_run_all_db_setups();
        // Update the stored database version
        update_option( 'vcl_db_version', $db_version );
    }
}
add_action( 'admin_init', 'vcl_check_db_version' );


function vcl_send_reset_password_email($user, $reset_link) {
    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);


	$message  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif; line-height:1.6; color:#333;">';
	$message .= '<div style="max-width:600px;margin:0 auto;padding:20px;border:1px solid #eaeaea;">';
	$message .= '<h2 style="text-align:center;color:#2a8fbd;">'.__('Reset your password', LANG_ZONE).'</h2>';
	$message .= sprintf(__('Site Name: %s', LANG_ZONE), $blogname) . "\r\n";
	$message .= '<p>'.sprintf(__('Hello  <strong>%s</strong>', LANG_ZONE), esc_html( $user->display_name ) ) . "</p>";
	$message .= '<p>'.sprintf(__('Username: %s', LANG_ZONE), $user->user_login) . "</p>";
	$message .= '<p>'.__('Someone has requested a password reset for the following account:', LANG_ZONE).'</p>';
	$message .= '<p>'.__('To reset your password, visit the following address:', LANG_ZONE).':</p>';
	$message .= '<p style="text-align:center;margin:30px 0;">';
	$message .= '<a href="' . esc_url( $reset_link ) . '" style="display:inline-block;padding:12px 24px;background-color:#2a8fbd;color:#fff;text-decoration:none;border-radius:4px;">';
	$message .= __('Reset password', LANG_ZONE);
	$message .= '</a>';
	$message .= '</p>';
	$message .= '<p><a href="' . esc_url( $reset_link ) . '">' . esc_url( $reset_link ) . '</a></p>';
	$message .= '<hr style="border:none;border-top:1px solid #eaeaea;margin:20px 0;">';
	$message .= '<p style="font-size:0.9em;color:#666;">'.__('If this was a mistake, just ignore this email and nothing will happen.', LANG_ZONE).'</p>';
	$message .= '<p style="font-size:0.9em;color:#999;">&copy; ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.</p>';
	$message .= '</div></body></html>';


    $title = sprintf(__('[%s] Password Reset', LANG_ZONE), $blogname);

	$headers   = [];
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$headers[] = 'From: ' . get_bloginfo('name') . ' <no-reply@votcaulongshop.vn>';

    // Send the email
    $sent = wp_mail($user->user_email, $title, $message, $headers);

    return $sent;
}

// Add rewrite rule for /reset-password
function vcl_add_rewrite_rules() {
    add_rewrite_rule(
        '^reset-password/?$',
        'index.php?pagename=reset-password',
        'top'
    );
    add_rewrite_rule(
        '^reset-password/([^/]+)/([^/]+)/?$',
        'index.php?pagename=reset-password&key=$matches[1]&login=$matches[2]',
        'top'
    );
}
add_action('init', 'vcl_add_rewrite_rules');

// Flush rewrite rules on theme activation (important!)
function vcl_flush_rewrite_rules() {
    vcl_add_rewrite_rules();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'vcl_flush_rewrite_rules');

// Load custom template for /reset-password
function vcl_reset_password_template($template) {
    if (get_query_var('pagename') == 'reset-password') {
        $new_template = locate_template(array('page-reset-password.php'));
        if ('' != $new_template) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'vcl_reset_password_template');

// Add query vars for key and login
function vcl_add_query_vars($vars) {
    $vars[] = 'key';
    $vars[] = 'login';
    return $vars;
}
add_filter('query_vars', 'vcl_add_query_vars');

// Handle the actual password reset on the custom page
function vcl_handle_password_reset() {
    if (get_query_var('pagename') == 'reset-password' && isset($_GET['key']) && isset($_GET['login'])) {
        $key = sanitize_text_field($_GET['key']);
        $login = sanitize_text_field($_GET['login']);

        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            wp_die($user->get_error_message());
        }

        // If the key is valid, display the password reset form
        // This part will be handled by the page-reset-password.php template
    }
}
add_action('template_redirect', 'vcl_handle_password_reset');




// Load custom template for /share-cart
function vcl_share_cart_template_redirect() {
    if (get_query_var('pagename') == 'share-cart-loader') {
        $new_template = locate_template(array('page-share-cart-loader.php'));
        if ('' != $new_template) {
            include $new_template; // Use include instead of return for direct execution
            exit;
        }
    }
}
add_action('template_redirect', 'vcl_share_cart_template_redirect');

?>