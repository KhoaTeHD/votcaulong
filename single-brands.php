<?php
get_header();
global $product_attr_name;
$erp_api = new ERP_API_Client();
$total_pages = 0;
if ( have_posts() ) :
    while ( have_posts() ) : the_post(); ?>
            <?php
            $brand_name = get_the_title();
            $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $_results = $erp_api->get_products_by_brand($brand_name,$current_page);
            $products = $_results['products'];
	        $total_pages = $_results['total_pages'];
            if (!is_wp_error($_results)  ) {
                $filters = $erp_api->get_filters();
            }
            ?>
        <div class="container">
            <div class="section-header">
                <h3 class="title"><?php echo $brand_name ?></h3>
                <div class="brand_search">
                    <input id="quickSearch_brand_input" class="form-control" type="text" placeholder="<?php _e('Quick search...', LANG_ZONE)  ?>">
                    <div id="quickSearch_brand_results"></div>
                </div>
            </div>
            <div class="post-content bg-white p-3">
                <div class="brand1-list mb-3">
		            <?php the_post_thumbnail('medium'); ?>
                    <div class="my-2">
                        <?php the_content();?>
                    </div>
                </div>
                <?php 
                require_once('template-parts/product-listing.php');
                ?>
            </div>
        </div>
    <?php
    endwhile;
endif;
get_footer();
