<?php
define('LANG_ZONE', 'vcl-shop');
define('IMG_URL', get_template_directory_uri().'/assets/images/');
define('THEME_URL', get_stylesheet_directory_uri() );
define('PAYOO_USERNAME','SB_Votcaulong');
define('PAYOO_SHOP_ID','12217');
define('PAYOO_SECRET_KEY','NGFiNTZlNmRiZTE0ZGQ4MjlkMjRjOGIwOGY2MWY0YTI=');
define('PAYOO_CHECKOUT_URL','https://newsandbox.payoo.com.vn/v2/checkout');
define('DEV_MODE', 1);
define('THEME_VER', DEV_MODE ? time() : '1.0.5');
add_filter('show_admin_bar', '__return_false');
function vcl_theme_setup() {
	load_theme_textdomain( LANG_ZONE, get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'vcl_theme_setup' );
global $order_statuses;
$order_statuses = [
	'pending'          => __('Pending', LANG_ZONE),
	'processing'       => __('Processing', LANG_ZONE),
	'on-hold'          => __('On hold', LANG_ZONE),
	'completed'        => __('Completed', LANG_ZONE),
	'cancelled'        => __('Cancelled', LANG_ZONE),
	'paid'             => __('Paid', LANG_ZONE),
	'failed'           => __('Failed', LANG_ZONE),
	'refunded'         => __('Refunded', LANG_ZONE),
	'pending-payment'  => __('Pending payment', LANG_ZONE),
];
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
	wp_enqueue_script('popper-js', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js', ['jquery'], '5.3.0', true);
	wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.0', true);
	wp_enqueue_script('shareon-js', get_template_directory_uri() . '/assets/shareon/shareon.iife.js', ['jquery'], '2.6.0', true);
	// Thêm CSS của Swiper
	wp_enqueue_style('swiper-css', get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.css',[],'5.3');
	// Thêm JS của Swiper
	wp_enqueue_script('swiper-js', get_template_directory_uri() . '/assets/swiper/swiper-bundle.min.js', [], '5.3', true);
	// Đăng ký custom CSS
	wp_enqueue_style('rateit-css', get_template_directory_uri() . '/assets/rateit/rateit.css', ['bootstrap-css'], '1.1.6');
	wp_enqueue_style('shareon-css', get_template_directory_uri() . '/assets/shareon/shareon.min.css', ['rateit-css'], '2.5.0');
	wp_enqueue_style('theme-css', get_template_directory_uri() . '/assets/css/theme.css', ['shareon-css'], THEME_VER);
	wp_enqueue_style('content-css', get_template_directory_uri() . '/assets/css/post_content.css', ['bootstrap-css'], THEME_VER);
	wp_enqueue_style('shoppingcart-css', get_template_directory_uri() . '/assets/css/shopping-cart.css', ['bootstrap-css'], THEME_VER);
	wp_enqueue_style('account-css', get_template_directory_uri() . '/assets/css/account.css', ['theme-css'], THEME_VER);
	wp_enqueue_style('responsive-css', get_template_directory_uri() . '/assets/css/responsive.css', ['theme-css'], THEME_VER);
	//---js
	wp_enqueue_script('rateit-js', get_template_directory_uri() . '/assets/rateit/jquery.rateit.min.js', ['jquery'], '1.1.6', true);
	wp_enqueue_script('custom-libs-js', get_template_directory_uri() . '/assets/js/custom-libs.js', ['jquery'], THEME_VER, true);
	wp_enqueue_script('main-js', get_template_directory_uri() . '/assets/js/main.js', ['bootstrap-js'], THEME_VER, true);
	wp_enqueue_script('account-js', get_template_directory_uri() . '/assets/js/account.js', ['main-js'], THEME_VER, true);
	wp_enqueue_script('shoppingCart-js', get_template_directory_uri() . '/assets/js/shopping-cart.js', ['account-js'], THEME_VER, true);
	wp_enqueue_script('custom-swiper-init', get_template_directory_uri() . '/assets/js/swiper-init.js', ['jquery','custom-libs-js', 'swiper-js'], THEME_VER, true);

	// Only load checkout JS on shopping cart page
	if (is_page_template('page-shopping-cart.php')) {
		// Enqueue checkout script, dependent on cart storage
		wp_enqueue_script('checkout-js', get_template_directory_uri() . '/assets/js/checkout.js', ['jquery', 'shoppingCart-js','account-js'], THEME_VER, true); // Added vcl-cart-storage dependency

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
		wp_enqueue_script('productcate-js', get_template_directory_uri() . '/assets/js/category.js', ['main-js'], THEME_VER, true);
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

function register_my_menus() {
    register_nav_menus(
        array(
            'footer-menu1' => __( 'Footer-menu-1',LANG_ZONE ),
			'footer-menu2' => __( 'Footer-menu-2',LANG_ZONE ),
			'footer-menu3' => __( 'Footer-menu-3',LANG_ZONE ),
			'footer-menu4' => __( 'Footer-menu-4',LANG_ZONE ),
			'footer-menu5' => __( 'Footer-menu-5',LANG_ZONE ),
    ));
}

add_action('after_setup_theme', 'register_my_menus');

?>