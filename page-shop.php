<?php
/**
 * Template Name: Shop
 **/
get_header();
$erp_api = new ERP_API_Client();
$current_page = get_query_var('paged') ? get_query_var('paged') : 1;

// Initialize variables
$products = [];
$total_pages = 0;
$filters = [];
$all_products = $erp_api->get_all_products($current_page);

if (!is_wp_error($all_products) && !empty($all_products['products'])) {
    $products = $all_products['products'];
    $total_pages = $all_products['total_pages'];

    // Fetch filters only if products were successfully retrieved
    $retrieved_filters = $erp_api->get_filters();
    if (!is_wp_error($retrieved_filters)) {
        $filters = $retrieved_filters;
    }
}
?>
<div class="container">
    <div class="section-header">
        <h3 class="title"><?php the_title(); ?></h3>
    </div>
    <div class="post-content bg-white p-3">
        <?php
        if (!empty($products)) {
            get_template_part('template-parts/product', 'listing', [
                'products'     => $products,
                'total_pages'  => $total_pages,
                'filters'      => $filters,
                'current_page' => $current_page
            ]);
        } else {
            // Optionally, display an error message if the API call failed
            if (is_wp_error($all_products)) {
                echo '<div class="alert alert-danger">Đã có lỗi xảy ra khi tải sản phẩm. Vui lòng thử lại sau.</div>';
                // For debugging, you can log the error: error_log($all_products->get_error_message());
            } else {
	            get_template_part( 'template-parts/not-found-page' );
            }
        }
        ?>
    </div>
</div>
<script id="category-js" src="<?php echo get_template_directory_uri() . '/assets/js/category.js?ver=' . time() ?>"></script>
<?php

get_footer(); ?>
