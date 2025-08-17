<footer class="">
    <div class="footer-top">
        <div class="container ">
            <div class="footer-logo">
                <?php
                    if ($footer_logo = get_field('footer_logo','options')) {
                        $logo_img = $footer_logo;
                    }else{
	                    $logo_img = IMG_URL.'footer-logo.png';
                    }
                ?>
                <img src="<?php echo $logo_img  ?>" alt="<?php bloginfo('name');  ?>">
            </div>
        </div>
    </div>
	<div class="container">
        <div class=" col-md-12">
            <div class="footer-body bg-white p-3">
                <div class="footer-widgets">
                    <?php 
                        $footer_column = 5;
                        for ($i=1;$i<=$footer_column;$i++) {
                            if ( is_active_sidebar( 'footer-column-'.$i ) ) : ?>
                            <div class="widget-col-<?php echo $i  ?>">
                                <?php dynamic_sidebar( 'footer-column-'.$i ); ?>
                            </div>
                            <?php endif;
                        }
                        // $menu_locations = get_nav_menu_locations();
                        // get_template_part( "template-parts/footer-widget", null, array('menu_location'=>'footer-menu1','menu_array'=>$menu_locations)); 
                        // get_template_part( "template-parts/footer-widget", null, array('menu_location'=>'footer-menu2','menu_array'=>$menu_locations));
                        // get_template_part( "template-parts/footer-widget", null, array('menu_location'=>'footer-menu3','menu_array'=>$menu_locations));
                        // get_template_part( "template-parts/footer-widget", null, array('menu_location'=>'footer-menu4','menu_array'=>$menu_locations));
                        // get_template_part( "template-parts/footer-widget", null, array('menu_location'=>'footer-menu5','menu_array'=>$menu_locations)); 
                    ?>
                </div>
                <div class="row footer-widgets py-3">
                    <div class="col-md-6">
                        <?php 
                            if ( is_active_sidebar( 'footer-column-bottom1' ) ) : ?>
                                <?php dynamic_sidebar( 'footer-column-bottom1' ); ?>
                            <?php endif;
                        ?>
                    </div>
                    <div class="col-md-6 d-none d-lg-flex items-justified-space-between">
                    <?php 
                        
                                if ( is_active_sidebar( 'footer-column-bottom2' ) ) : ?>
                                <div class="widget-col">
                                    <?php dynamic_sidebar( 'footer-column-bottom2' ); ?>
                                </div>
                                <?php endif;
                                if ( is_active_sidebar( 'footer-column-bottom3' ) ) : ?>
                                    <div class="widget-col">
                                        <?php dynamic_sidebar( 'footer-column-bottom3' ); ?>
                                    </div>
                                <?php endif;
                    ?>
                    </div>
                </div>

            </div>
            <div class="p-3 bct-container border-2 border-bottom border-primary ">
                <div class="bct-logo">
                    <a href="<?php echo esc_url( get_field('bct_url','options') ?? '#' );  ?>" target="_blank" rel="nofollow"><img src="<?php echo esc_url( get_field('logo_bct','options') );  ?>" alt=""></a>
                </div>
                <div class="bct-chung-nhan">
	                <?php the_field('business_license','options');  ?>
                </div>
                <div class="bct-ko-hang-gia">
                    <a href="<?php echo esc_url( get_field('bct_url_2','options') ?? '#' );  ?>" target="_blank" rel="nofollow"><img src="<?php echo esc_url( get_field('logo_bct_2','options') );  ?>" alt=""></a>
                </div>
                <div class="logo-ncsc">
                    <a href="<?php echo esc_url( get_field('bct_url_3','options') ?? '#' );  ?>" target="_blank" rel="nofollow"><img src="<?php echo esc_url( get_field('logo_bct_3','options') );  ?>" alt=""></a>
                </div>
            </div>
            <div class="px-3 py-2 d-flex items-justified-space-between copyright ">
                <p class=""><?php the_field('copyright_text','options');  ?></p>
                <div class="protected">
                    <a href="<?php echo esc_url( get_field('protected_url','options') );  ?>" target="_blank" rel="nofollow"><img src="<?php echo esc_url( get_field('logo_protected','options') );  ?>" alt=""> </a>

                </div>
            </div>

        </div>

	</div>
	
</footer>
<button class="btn" id="scroll-to-top"><i class="bi bi-arrow-up"></i></button>
<div id="loading-overlay" >
    <div class="d-flex justify-content-center align-items-center w-100 h-100">
    <div class="loading-spinner"></div>
    </div>
</div>
<div id="compare-popup-btn" class="shadow">
    <button onclick="showCompare()" class="icon-showCompare btn" role="button">
        <span><?php _e('Compare', LANG_ZONE)  ?> <label id="count-compare-item">(1)</label></span>
    </button>
</div>
<div id="compare-list" class="sticky-bottom container">
    <a href="#" role="button" onclick="hideCompare()" class="clearall hide-compare-list-btn"><?php _e('Collapse', LANG_ZONE)  ?> <i class="bi bi-chevron-down"></i></a>
    <ul class="listcompare" >
        <li class="formsg" >
            <a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                <i class="bi bi-plus-lg"></i><p><?php _e('Add product', LANG_ZONE)  ?></p></a>
        </li>
        <li class="formsg">
            <a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                <i class="bi bi-plus-lg"></i><p><?php _e('Add product', LANG_ZONE)  ?></p></a>
        </li>
        <li class="formsg">
            <a href="#" class="cp-plus cp-plus_new"  data-bs-toggle="modal" data-bs-target="#searchProduct_modal">
                <i class="bi bi-plus-lg"></i><p><?php _e('Add product', LANG_ZONE)  ?></p></a>
        </li>
    </ul>
    <div class="closecompare">
        <!--<a href="#" id="gotoCompare" class="prevent btn btn-primary btn-sm my-0" role="button"> <?php _e('Compare now', LANG_ZONE)  ?></a> //-->
        <a href="#" id="quickCompare" data-bs-toggle="modal" data-bs-target="#quickCompare_modal" class="prevent btn btn-secondary btn-sm my-0" role="button"> <?php _e('Quick compare', LANG_ZONE)  ?></a>
        <a href="#" onclick="RemoveAllIdCompare()" class="txtremoveall"><?php _e('Clear all', LANG_ZONE)  ?></a>
    </div>
</div>
<!-- Compare search Modal -->
<div class="modal fade" id="searchProduct_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="searchProduct_modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="w-75">
                    <h3 class="modal-title fs-6" id="staticBackdropLabel"><?php _e('Enter name/sku to search', LANG_ZONE)  ?></h3>
                    <input type="text" class="form-control" id="searchProduct_compare_input" placeholder="<?php _e('Minimum 3 characters', LANG_ZONE)  ?>">
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center w-100">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden"><?php _e('Loading...', LANG_ZONE)  ?></span>
                    </div>
                </div>

                <div id="searchProduct_grid" class="product-grid shadow-item">

                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Close', LANG_ZONE)  ?></button>
            </div>
        </div>
    </div>
</div>
<!----- Quick compare --->
<div class="modal fade" id="quickCompare_modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="quickCompare_modal" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="section-header w-100">
                    <h3 class="modal-title fs-6" id="staticBackdropLabel"><?php _e('Quick compare', LANG_ZONE)  ?></h3>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

            </div>
            <div class="modal-body">

                <div id="product-comparison-container">
                    <div id="comparison-loading" style="display: none;">
                        <p><?php _e('Loading product information...',LANG_ZONE)  ?></p>
                    </div>

                    <div id="comparison-table-container">
                        <div class="comparison-error">

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed top-50 start-50 translate-middle p-3 custom-toast">
    <div id="siteNotify" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto" id="siteNotifyTitle"><?php _e('Notification', LANG_ZONE)  ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="siteNotifyBody">

        </div>
    </div>
</div>

<!-- Share Cart Modal -->
<div class="modal fade" id="shareCartModal" tabindex="-1" aria-labelledby="shareCartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareCartModalLabel"><?php _e('Share Your Cart', LANG_ZONE) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?php _e('Copy the link below to share your cart:', LANG_ZONE) ?></p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="share-cart-link" value="" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="copy-share-link-btn"><?php _e('Copy', LANG_ZONE) ?></button>
                </div>
                <div class="alert alert-info d-none" id="share-cart-message" role="alert"></div>
                <div class="share-product d-flex items-justified-space-between align-items-center">
                    <div class="shareon cart-shareon" data-url=''>
                        <button class="messenger" data-fb-app-id="APP ID"></button>
                        <button class="facebook" data-title="<?php echo get_bloginfo('name')  ?>" data-hashtags="VotCauLongShop"></button>
                        <button class="pinterest" ></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Close', LANG_ZONE) ?></button>
            </div>
        </div>
    </div>
</div>

<?php
$enable_popup = get_field('enable_popup','option');
if ($enable_popup){

$popup = get_field('popup_banner', 'option');
if( !empty($popup) && (!empty($popup['content_text']) || !empty($popup['content_block'])) ):

	$content_type   = (boolean)$popup['content_type'] ? 'text' : 'html_block';
	$content_text   = $popup['content_text'] ?? '';
	$content_block  = $popup['content_block'] ?? '';
	$bg_color       = 'transparent';
	$text_color     = $popup['content_color'] ?: '#fff';
	$bg_photo       = $popup['background_photo'];
	$width          = $popup['popup_width'] ?: 400;

	$hide_on_mobile = !empty($popup['hide_on_mobile']);
	$show_only_one_time = !empty($popup['show_only_one_time']);
	$popup_version = isset($popup['popup_version']) ? intval($popup['popup_version']) : 1;

	$bg_image_css = $box_shadow = '';
	if( $bg_photo && is_array($bg_photo) && !empty($bg_photo['url']) && $content_type != 'html_block' ){
		$bg_image_css = 'background-image:url(' . esc_url($bg_photo['url']) . ');background-size:cover;background-position:center;';
		$bg_color     = $popup['popup_background_color'] ?: '#fff';
		$box_shadow   = 'box-shadow:0 8px 32px rgba(0,0,0,0.18);';
		$width        = intval($width).'px';
		$padding      = '32px 24px;';
	} else {
		$width        = 'auto';
		$padding      = '0';
	}
	?>
    <div id="custom-popup-overlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9998;background:rgba(0,0,0,0.8);"></div>
    <div id="custom-popup"
         style="
                 display:none;
                 position:fixed;
                 top:50%;
                 left:50%;
                 color: <?php echo esc_attr($text_color); ?>;
                 transform:translate(-50%,-50%);
                 width:<?php echo ($width); ?>;
                 max-width:90vw;
                 background:<?php echo esc_attr($bg_color); ?>;
	     <?php echo $bg_image_css; ?>
                 z-index:9999;
	     <?php echo $box_shadow; ?>
                 border-radius:12px;
                 padding:<?php echo $padding?>;
                 ">
        <button id="close-custom-popup" style="position:absolute;top:0px;right:5px;border:none;background:none;font-size:2rem;cursor:pointer;line-height:0;border-radius:50%;background-color:#ffffff6e;width:25px;height:25px;padding:0;margin:0;z-index:9999;">&times;</button>
        <div>
			<?php
			if($content_type === 'html_block' && !empty($content_block)){
				if (class_exists('\Elementor\Plugin')) {
					echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($content_block);
				} else {
					$block_post = get_post($content_block);
					echo apply_filters('the_content', $block_post->post_content);
				}
			} else {
				echo apply_filters('the_content', $content_text);
			}
			?>
        </div>
    </div>
    <script>
        (function(){
            // Config
            var hideOnMobile = <?php echo json_encode($hide_on_mobile); ?>;
            var showOnlyOneTime = <?php echo json_encode($show_only_one_time); ?>;
            var popupVersion = <?php echo intval($popup_version); ?>;
            var storageKey = 'popup_dismissed_v' + popupVersion;

            // Detect mobile
            function isMobile() {
                return /iPhone|iPad|iPod|Android|webOS|BlackBerry|Windows Phone|Opera Mini/i.test(navigator.userAgent);
            }

            document.addEventListener('DOMContentLoaded', function(){
                // Ẩn popup trên mobile nếu chọn
                if(hideOnMobile && isMobile()) {
                    return;
                }
                if(showOnlyOneTime && window.localStorage.getItem(storageKey) === '1') {
                    return;
                }
                setTimeout(function(){
                    document.getElementById('custom-popup').style.display = 'block';
                    document.getElementById('custom-popup-overlay').style.display = 'block';
                }, 500);

                document.getElementById('close-custom-popup').onclick = function(){
                    document.getElementById('custom-popup').style.display = 'none';
                    document.getElementById('custom-popup-overlay').style.display = 'none';
                    // Lưu vào localStorage nếu cần
                    if(showOnlyOneTime) {
                        window.localStorage.setItem(storageKey, '1');
                    }
                };
                document.getElementById('custom-popup-overlay').onclick = function(){
                    document.getElementById('custom-popup').style.display = 'none';
                    this.style.display = 'none';
                    if(showOnlyOneTime) {
                        window.localStorage.setItem(storageKey, '1');
                    }
                };
            });
        })();
    </script>
<?php endif;
}
?>


</body>
<?php 
$locations_json_url = get_theme_file_uri('/data/locations.json');
$wards_json_url = get_theme_file_uri('/data/wards.json');
if (is_user_logged_in()) {
    $customer = get_current_customer();
    $addresses = $customer->get_addresses();
}else{
    $addresses = array();
}
    ?>
    <script type="text/javascript">
        const AccountAddressVars = {
            ajaxUrl: typeof ThemeVars !== 'undefined' ? ThemeVars.ajaxurl : '/wp-admin/admin-ajax.php', 
            saveNonce: '<?php echo wp_create_nonce('vcl_save_address_nonce'); ?>', 
            deleteNonce: '<?php echo wp_create_nonce('vcl_delete_address_nonce'); ?>',
            setDefaultNonce: '<?php echo wp_create_nonce('vcl_set_default_address_nonce'); ?>',
            locationsJsonUrl: '<?php echo esc_url($locations_json_url); ?>',
            wardsJsonUrl: '<?php echo esc_url($wards_json_url); ?>',
            addresses: <?php echo wp_json_encode(array_values($addresses));  ?>
        };
    </script>
    <?php wp_footer(); ?>
</html>