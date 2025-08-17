<?php get_header('product-cate');
global $product_attr_name;

$category_id = get_query_var('product_cate_id');
$erp_api = new ERP_API_Client();
if ($pro_cate = get_term($category_id, 'pro_cate')) {
//	$category_info = $erp_api->get_category_erp($pro_cate->name);
	$category_id = $pro_cate->name;
}
//my_debug($pro_cate);
$total_pages = 0;
if (is_wp_error($pro_cate) || !$pro_cate){
   require_once('not-found-page.php');
}else{
	$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $cate_results = $erp_api->get_products_by_category($category_id,$current_page);
    if (!is_wp_error($cate_results)) {
	    $products = $cate_results['products'];
//	    my_debug($cate_results);
	    $total_pages = $cate_results['total_pages'];
	    $filters = $erp_api->get_filters([$category_id]);
//        $filters = $cate_results['filters'];
    }else{
	    error_log("Load category error: " . $cate_results->get_error_message() );
    }
?>
    <div class="container mb-fluid">
        <div class="section-header">
            <h3 class="title"><?php echo $pro_cate->name  ?></h3>
        </div>
        <div class="post-content bg-white p-3">
            <?php
            require_once('product-listing.php');
          ?>
        </div>
    </div>
    
<?php
}
get_footer(); ?>