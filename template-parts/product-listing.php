<?php
if (isset($args['products'])){
	$products = $args['products'];
	$filters = $args['filters'];
	$total_pages = $args['total_pages'];
	$current_page = $args['current_page'];
}
 if (!$products){
	            _e('Updating...', LANG_ZONE);
            }else {  ?>
            <div class="row">
                <!--Sidebar-->
                <div class="col-md-3 offcanvas offcanvas-start"  data-bs-backdrop="static"  tabindex="-1" id="cateSidebarOffcanvas" aria-labelledby="cateSidebarOffcanvasLabel">
                    <div class="offcanvas-header d-md-none p-0 py-3">
                        <h6 class="offcanvas-title" id="cateSidebarOffcanvasLabel"><?php _e('Filter Products', LANG_ZONE); ?></h6>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body filter-group border rounded p-2 ">
                        <?php
                        if (isset($filters)) {
                            if ($filters['price_ranges']){
	                            get_template_part( 'template-parts/widget', 'filter', [
		                            'name' => __('Price ranges',LANG_ZONE),
		                            'data' => $filters['price_ranges'],
		                            'field_name' => 'filter_price'
	                            ] );
                            }

	                        if ($filters['brands'] && !is_singular( 'brands' )){
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
                            foreach ($filters as $attr_name => $attr_data) {
                                if ( in_array( $attr_name,['branches','brands','price_ranges'])){
                                    continue;
                                }
                                
                                get_template_part( 'template-parts/widget', 'filter', [
                                    'name' => $attr_name,
                                    'data' => $attr_data,
                                    'field_name' => $attr_name,
                                ] );
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
                <div class="col-12 col-md-9">
                    <!-- Brands-->
                     <?php if ($filters['brands'] && !is_singular( 'brands' )){?>
                    <div class="brands d-flex flex-wrap mb-3">
                        <?php
	                        get_template_part( 'template-parts/brand', 'item', ['data' => $filters['brands']] );
                        ?>

                    </div>
                        <?php }
                            ?>
                    <!-- //Brands-->
                    
                    <div class="active-filters mb-3" id="activeFilters" ></div>
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
                    <div class="category-product-listing product-list-filter mb-3 position-relative" id="">
                        <div class="category-control px-3 py-2 border rounded-1  mb-3 d-flex items-justified-space-between align-items-center">
                            <button class="btn btn-primary btn-sm d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#cateSidebarOffcanvas" aria-controls="cateSidebarOffcanvas">
                                <i class="bi bi-filter"></i> <?php _e('Filter Products', LANG_ZONE); ?>
                            </button>
                            
                            <div class="fw-semibold d-none d-md-flex"><?php _e('Sort by',LANG_ZONE)  ?></div>
                            <div class="product-sort-by d-flex items-justified-space-between align-items-center" >
                                <button type="button" class="btn-close text-reset d-md-none mb-sortby-btn" aria-label="Close"></button>
                                <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_khuyenmai" value="BEST_DISCOUNT"><label class="btn-sort-by" for="sort_khuyenmai">Khuyến mãi tốt</label></div>
                                <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_giatang" value="PRICE_ASC">  <label class="btn-sort-by" for="sort_giatang">Giá tăng dần</label></div>
                                <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_giagiam" value="PRICE_DESC">  <label class="btn-sort-by" for="sort_giagiam">Giá giảm dần</label></div>
                                <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_moinhat" value="CREATION_DESC">  <label class="btn-sort-by" for="sort_moinhat">Sản phẩm mới nhất</label></div>
                                <div class="sort-by-item"><input type="radio" name="product-sort" id="sort_banchay" value="BEST_SELLING">  <label class="btn-sort-by" for="sort_banchay">Bán chạy nhất</label></div>
                            </div>
                            <div class="product-pagination-small">
                                <button class="btn page-prev-btn"><i class="bi bi-chevron-left"></i></button>
                                <span class="page-numbers" data-current-page="<?php echo $current_page  ?>" data-total-pages="<?php echo $total_pages??'1';  ?>"><?php echo $current_page  ?>/<?php echo $total_pages??'1';  ?></span>
                                <button class="btn page-next-btn"><i class="bi bi-chevron-right"></i></button>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm d-md-none mb-sortby-btn" type="button" id="mb-sortby-btn">
                                <i class="bi bi-filter-left"></i>
                            </button>
                        </div>
                        <div id="product-container" class="product-grid shadow-item2" data-category_id="<?php echo $category_id??''  ?>" data-brands="<?php echo (is_singular('brands')?get_the_title():'')  ?>">

                            <?php

                            foreach ($products as $product_info ){
                                get_template_part('template-parts/product','item',['product_data'=>$product_info]);
                            }
                            ?>

                        </div>
                        <div id="pagination_wrapper">
                            <?php echo render_pagination($current_page, $total_pages, 2); ?>
                        </div>
                    </div>
                </div>
                <!--//Content-->
            </div>
            <?php }  ?>