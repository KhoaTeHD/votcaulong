<?php
/**
 * Template Name: Account
 **/
get_header();
$login_page = get_field('register_and_login','options');
if (!is_user_logged_in()) {
    ?>
    <script>
        window.location.href = "<?php echo $login_page  ?>";
    </script>
<?php
}
$page_url = get_the_permalink();
$user_id = get_current_user_id();
$customer  = get_current_customer();
$customer_info = $customer->get_customer_info();
$fullname = $customer_info['first_name'] . ' ' . $customer_info['last_name'];
// Get addresses using the new method
$addresses = $customer->get_addresses();

// Sort addresses to put the default one first (optional, but good for display)
usort($addresses, function($a, $b) {
    return ($b['is_default'] ?? 0) <=> ($a['is_default'] ?? 0);
});

//my_debug($customer_info);
$avatar = $customer_info['avatar_url']??'';
$active_tab = get_query_var('tab')?get_query_var('tab'):'orders';
// JSON file paths for JavaScript

function is_active_menu($menu_slug){
    global $active_tab;
    echo $menu_slug==$active_tab?'active':'';
}
?>
<div class="container">
	<div class="post-content ">
        <section class=" ">
            <?php get_template_part('template-parts/page','title');  ?>
            <div class="row ">
                <div class="col-md-3 " >

                    <div class="account-box bg-white p-3 shadow-sm">
                        <div class="account-head d-flex  items-justified-left">
                            <div class=" w-50">
                                <?php if ($avatar){?>
                                    <img src="<?php echo $avatar;  ?>" class="rounded-circle img-thumbnail ratio ratio-1x1" alt="...">
                                <?php } ?>
                                

                            </div>
                            <div class="info ms-3">
                                <div class="mb-1">
                                <?php printf(__('Hello !<br><b>%s</b>',LANG_ZONE),$fullname)  ?>
                                </div>

                                <a class="icon-link " href="<?php echo addParamToUrl($page_url,'edit-profile')  ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    <?php _e('Edit profile',LANG_ZONE);  ?>
                                </a>
                                <a class="icon-link sign-out-btn" href="#" >
                                    <i class="bi bi-box-arrow-right"></i>
		                            <?php _e('Sign out',LANG_ZONE);  ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="account-box bg-white  shadow-sm">
                        <div class="account-navigation">
                            <ul class="account-nav nav flex-column">
                                <li class="nav-item"><a class="nav-link <?php is_active_menu('orders')  ?>" href="<?php echo addParamToUrl($page_url,'orders')  ?>"><i class="bi bi-receipt"></i> <?php _e('Orders', LANG_ZONE)  ?></a></li>
                                <li class="nav-item"><a class="nav-link <?php is_active_menu('your-favorite')  ?>" href="<?php echo addParamToUrl($page_url,'your-favorite')  ?>"><i class="bi bi-heart"></i> <?php _e('Favorites',LANG_ZONE)  ?></a></li>
                                <li class="nav-item">
                                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="collapse" data-bs-target="#loyalty" aria-controls="loyalty" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-box2-heart"></i> <?php _e('Loyalty',LANG_ZONE)  ?></a>
                                    <ul class="sub-nav collapse show" id="loyalty" >
                                        <li class="nav-item <?php is_active_menu('loyalty-member')  ?>"><a class="nav-link <?php is_active_menu('loyalty-member')  ?>" href="<?php echo addParamToUrl($page_url,'loyalty-member')  ?>"><i class="bi bi-box2-heart"></i> <?php _e('My rank',LANG_ZONE)  ?></a></li>
                                        <li class="nav-item <?php is_active_menu('loyalty-gift')  ?>"><a class="nav-link dropdown-item <?php is_active_menu('loyalty-gift')  ?>" href="<?php echo addParamToUrl($page_url,'loyalty-gift')  ?>"><i class="bi bi-gift"></i> <?php _e('Loyalty gift',LANG_ZONE)  ?></a></li>
                                    </ul>
                                </li>
                                <li class="nav-item">
                                    
                                    <a class="nav-link dropdown-toggle"  data-bs-toggle="collapse" data-bs-target="#my-account" aria-controls="my-account" aria-expanded="false" aria-label="Toggle navigation">
                                        <i class="bi bi-person-vcard"></i> <?php _e('My profile', LANG_ZONE)  ?></a>
                                    <ul class="sub-nav collapse show" id="my-account" >
                                        <li class="nav-item <?php is_active_menu('edit-profile')  ?>"><a class="nav-link <?php is_active_menu('edit-profile')  ?>" href="<?php echo addParamToUrl($page_url,'edit-profile')  ?>"><?php _e('My profile',LANG_ZONE);  ?></a></li>
                                        <li class="nav-item <?php is_active_menu('change-password')  ?>"><a class="nav-link <?php is_active_menu('change-password')  ?>" href="<?php echo addParamToUrl($page_url,'change-password')  ?>"><?php _e('Change password',LANG_ZONE);  ?></a></li>
                                        <li class="nav-item <?php is_active_menu('address')  ?>"><a class="nav-link <?php is_active_menu('address')  ?>" href="<?php echo addParamToUrl($page_url,'address')  ?>"><?php _e('Address book',LANG_ZONE);  ?></a></li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
                <div class="col-md-9 " >
                    <div class="account-box bg-white p-3 shadow-sm content-body">
                        <?php
                        get_template_part('template-parts/account',$active_tab,$customer_info);
                        ?>

                    </div>
                </div>
            </div>
        </section>
	</div>
</div>


<?php
get_footer(); ?>
