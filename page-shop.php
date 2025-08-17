<?php
/**
 * Template Name: Shop
 **/
get_header();
$erp_api = new ERP_API_Client();
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$all_products = $erp_api->get_all_products();
if (!is_wp_error($all_products) &&  $all_products['products']) {

    $products = $all_products['products'];
	$total_pages = $all_products['total_pages'];
	$filters = $erp_api->get_filters();
}
?>
    <div class="container">
        <div class="section-header">
            <h3 class="title"><?php the_title();  ?></h3>
        </div>
        <div class="post-content bg-white p-3">
		        <?php
                if ($all_products['products']) {
	                get_template_part( 'template-parts/product', 'listing', [ 'products'     => $products,
	                                                                          'total_pages'  => $all_products['total_pages'],
	                                                                          'filters'      => $erp_api->get_filters(),
	                                                                          'current_page' => $current_page
	                ] );
                }
                ?>
        </div>
    </div>
    <script id="category-js" src="<?php echo get_template_directory_uri() . '/assets/js/category.js?ver='.time()  ?>"></script>
<?php

get_footer(); ?>