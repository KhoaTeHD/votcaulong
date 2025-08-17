<?php
/**
 * Template Name: Discount
 **/
get_header();
global $product_attr_name;
$data_path = get_template_directory() . '/data';
$erp_api = new ERP_API_Handler(FAKE_DATA);
$products = $erp_api->get_all_products();
// my_debug($products);
usort($products, function ($a, $b) {
    $discountA = ($a['original_price'] > 0) ? round((($a['original_price'] - $a['price']) / $a['original_price']) * 100) : 0;
    $discountB = ($b['original_price'] > 0) ? round((($b['original_price'] - $b['price']) / $b['original_price']) * 100) : 0;
    return $discountB - $discountA; // Sắp xếp giảm dần theo % giảm giá
});

// my_debug($products);
if (!is_wp_error($products)) {
    $filters = generate_filters($products);
}

?>
    <div class="container">
        <div class="section-header">
            <h3 class="title"><?php the_title();  ?></h3>
        </div>
        <div class="post-content bg-white p-3">
			<?php if (!$products){
				_e('Updating...', LANG_ZONE);
			}else {  ?>
                <div class="row">
                    <!--Sidebar-->
                    <div class="col-md-2">
                        <div class="filter-group border rounded p-2">

							<?php
							if (isset($filters)) {
								if ($filters['price_ranges']){
									get_template_part( 'template-parts/widget', 'filter', [
										'name' => __('Price ranges',LANG_ZONE),
										'data' => $filters['price_ranges'],
										'field_name' => 'filter_price'
									] );
								}

								if ($filters['brands']){
									get_template_part( 'template-parts/widget', 'filter', [
										'name' => __('Brands',LANG_ZONE),
										'data' => $filters['brands'],
										'field_name' => 'filter_brands'
									] );
								}

								if ($filters['branches']){
									get_template_part( 'template-parts/widget', 'filter', [
										'name' => __('Branches',LANG_ZONE),
										'data' => $filters['branches'],
										'field_name' => 'filter_branches',
										'has_search' => true
									] );
								}
								if (count($filters['attributes'])){
									foreach ($filters['attributes'] as $attr_name => $attr_data) {
										get_template_part( 'template-parts/widget', 'filter', [
											'name' => $product_attr_name[$attr_name],
											'data' => $attr_data,
											'field_name' => 'attr_'.$attr_name,
										] );
									}
								}
							}
							?>
							<?php if ( is_active_sidebar( 'shop-widget-area' ) ) : ?>
								<?php dynamic_sidebar( 'shop-widget-area' ); ?>
							<?php endif; ?>
                        </div>

                    </div>
                    <!--// Sidebar-->
                    <!--Content-->
                    <div class="col-md-10">
                        <!-- Brands-->
                        <div class="brands d-flex flex-wrap">
							<?php
							if ($filters['brands']){
								get_template_part( 'template-parts/brand', 'item', ['data' => $filters['brands']] );
							}
							?>

                        </div>
                        <!-- //Brands-->

                        <!-- Filter with image-->
                        <div class="filter-with-image-box">
							<?php
							if (isset($filters['attributes']) && ( isset($filters['attributes']['play_style']) || isset($filters['attributes']['play_mode']))) {
								?>
                                <div class="filter-title"><?php _e('Choose racket as required',LANG_ZONE)  ?></div>
                                <div class="filter-with-image-body">
                                    <ul>
										<?php foreach ($filters['attributes']['play_style'] as $play_style) {
											if(FAKE_DATA){
												$value = sanitize_title($play_style);
											}else{
												$value = ($play_style);
											}
											$item_id = 'attr_play_style';
											?>
                                            <li class="filter-with-image-item">
                                                <input type="checkbox" class="filter-checkbox" name="<?php echo $item_id  ?>" id="filter_<?php echo $value  ?>" value="<?php echo $value  ?>">
                                                <label for="filter_<?php echo $value  ?>" >
                                                    <img src="<?php echo IMG_URL  ?>No_Image_Available.jpg" alt="">
                                                    <div class="title"><?php echo $play_style  ?></div>
                                                </label>
                                            </li>
										<?php }  ?>
										<?php foreach ($filters['attributes']['play_mode'] as $play_mode) {
											if(FAKE_DATA){
												$value = sanitize_title($play_mode);
											}else{
												$value = ($play_mode);
											}
											$item_id = 'attr_play_mode';
											?>
                                            <li class="filter-with-image-item">
                                                <input type="checkbox" class="filter-checkbox" name="<?php echo $item_id  ?>" id="filter_<?php echo $value  ?>" value="<?php echo $value  ?>">
                                                <label for="filter_<?php echo $value  ?>" >
                                                    <img src="<?php echo IMG_URL  ?>No_Image_Available.jpg" alt="">
                                                    <div class="title"><?php echo $play_mode  ?></div>
                                                </label>
                                            </li>
										<?php }  ?>
                                    </ul>
                                </div>
							<?php }  ?>
                        </div>
                        <!-- //Filter with image-->
                        <div class="category-product-listing product-list-filter my-3 position-relative" id="">
                            <div class="category-control px-3 py-2 border rounded-1  my-3 d-flex items-justified-space-between align-items-center">
                                <div class="fw-semibold"><?php _e('Sort by',LANG_ZONE)  ?></div>
                                <div class="product-sort-by d-flex items-justified-space-between align-items-center" >
                                    <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_khuyenmai" value="best-discount"><label class="btn-sort-by" for="sort_khuyenmai">Khuyến mãi tốt</label></div>
                                    <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_giatang" value="price-asc">  <label class="btn-sort-by" for="sort_giatang">Giá tăng dần</label></div>
                                    <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_giagiam" value="price-desc">  <label class="btn-sort-by" for="sort_giagiam">Giá giảm dần</label></div>
                                    <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_moinhat" value="newest">  <label class="btn-sort-by" for="sort_moinhat">Sản phẩm mới nhất</label></div>
                                    <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_banchay" value="best-seller">  <label class="btn-sort-by" for="sort_banchay">Bán chạy nhất</label></div>
                                </div>
                                <div class="product-pagination-small">
                                    <button class="btn page-prev-btn"><i class="bi bi-chevron-left"></i></button>
                                    <span class="page-numbers">1/10</span>
                                    <button class="btn page-next-btn"><i class="bi bi-chevron-right"></i></button>
                                </div>
                            </div>
                            <div id="product-container" class="product-grid shadow-item2" data-category_id="<?php echo $category_id  ?>">

								<?php
								foreach ($products as $product_info ){
									get_template_part('template-parts/product-item');
								}
								?>

                            </div>
                            <div class="product-pagination my-3">
                                <a href="#" role="button" class="btn pagination-btn page-prev-btn disabled"><i class="bi bi-chevron-left"></i></a>
                                <span class="btn pagination-btn active">1</span>
                                <a href="#" role="button" class="btn pagination-btn ">2</a>
                                <a href="#" role="button" class="btn pagination-btn ">3</a>
                                <span class="more-pages pagination-btn ">...</span>
                                <a href="#" role="button" class="btn pagination-btn ">10</a>
                                <a href="#" role="button" class="btn pagination-btn page-next-btn"><i class="bi bi-chevron-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <!--//Content-->
                </div>
			<?php }  ?>
        </div>
    </div>
    <script id="category-js" src="<?php echo get_template_directory_uri() . '/assets/js/category.js?ver='.time()  ?>"></script>
    <?php
get_footer(); ?>