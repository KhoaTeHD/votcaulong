<?php
/**
 * Template Name: Share Cart Loader
 * Description: Handles loading shared cart data from a unique URL and redirects to the shopping cart page.
 */

// Ensure WordPress environment is loaded
if ( ! defined( 'ABSPATH' ) ) {
    require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );
}

$cart_id = get_query_var('cart_id');

// Default values for Open Graph tags
$og_title = __('Shared Shopping Cart from Votcaulong-shop', LANG_ZONE);
$og_description = __('Check out this shopping cart with awesome products!', LANG_ZONE);
$og_image = get_field('site-logo','options'); // Default shop logo
$og_url = home_url('/share-cart/' . $cart_id); // Current share URL
$logo = get_field('site-logo','options')??'';
$cart_data = null;
if (!empty($cart_id)) {
    $cart_data = get_transient('vcl_shared_cart_' . $cart_id);
}

if ($cart_data) {
    // Optionally, generate a more specific description based on cart contents
    $item_count = count($cart_data);
    if ($item_count > 0) {
        $first_item_name = $cart_data[0]['name'] ?? __('some products', LANG_ZONE);
        $og_description = sprintf(__('My cart contains %d items, including %s.', LANG_ZONE), $item_count, $first_item_name);
        // Optionally, use the first product's image if available
        if (!empty($cart_data[0]['image'])) {
            $og_image = $cart_data[0]['image'];
        }
    }

    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html($og_title); ?></title>

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo esc_url($og_url); ?>">
        <meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
        <meta property="og:description" content="<?php echo esc_attr($og_description); ?>">
        <meta property="og:image" content="<?php echo esc_url($og_image); ?>">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="<?php echo esc_url($og_url); ?>">
        <meta property="twitter:title" content="<?php echo esc_attr($og_title); ?>">
        <meta property="twitter:description" content="<?php echo esc_attr($og_description); ?>">
        <meta property="twitter:image" content="<?php echo esc_url($og_image); ?>">

        <script type="text/javascript">
            // Set cart data in localStorage
            localStorage.setItem('cart', JSON.stringify(<?php echo json_encode($cart_data); ?>));
            // Redirect to the main shopping cart page after a short delay
            setTimeout(function() {
                window.location.replace('<?php echo vcl_get_cart_page(); ?>');
            }, 1500);
        </script>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 0;margin: 0; }
            .logo-section{
                background-color: #555d58;
            }
            .logo-section img {max-width: 200px;}
            .gotoCart-url {color: #21935c; font-weight: 600;}
            .spinner { border: 4px solid rgba(0,0,0,.1); border-left-color: #21935c; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
    </head>
    <body>
        <?php if($logo) {  ?>
        <div class="logo-section"><img src="<?php echo $logo  ?>" alt="<?php bloginfo('name');  ?>"> </div>
        <?php }  ?>
        <div class="spinner"></div>
        <h1><?php _e('Loading Your Shared Cart...', LANG_ZONE); ?></h1>
        <p><?php _e('Please wait while we load your shopping cart.', LANG_ZONE); ?></p>
        <p><?php _e('If you are not redirected automatically, click here:', LANG_ZONE); ?> <a href="<?php echo vcl_get_cart_page(); ?>" class="gotoCart-url"><?php _e('Go to Cart', LANG_ZONE); ?></a></p>
    </body>
    </html>
    <?php
    exit;
} else {
    // Cart data not found or expired, redirect to shopping cart with an error
    wp_redirect(vcl_get_cart_page().'?share_error=expired_or_invalid');
    exit;
}