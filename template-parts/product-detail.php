<?php get_header('product-cate');
global $erp_data_head;

?>
	<div class="container">
		<div class="post-content product-item ">
			<div class="col-md-12">
				<?php
				
				$product_id = get_query_var('product_id');
				$erp = new ERP_API_Client();
                if (!is_wp_error($erp_data_head)) {
	                $product_data = $erp->get_product($product_id);
                    if (!is_wp_error($product_data) ){
	                    $product = new Product($product_data);
                        if ($product->list_item_stock()){
                            $stock_label = __('In stock',LANG_ZONE);
                            $stock_slug = 'in-stock';
                        }else{
	                        $stock_label = __('Out of stock', LANG_ZONE);
	                        $stock_slug = 'out-stock';
                        }
                        $customer = get_current_customer();
                        $productBrand = $product->Brand_detail();
                        $noimg = $product->getImageUrl();
                        $view_video = IMG_URL . 'icons/view-video.png';
                        $view_360 = IMG_URL . 'icons/view-360.png';
                        $brand_logo = ($productBrand?$productBrand['image']:'');
                        $video_url = $product->getVideoUrl();
                        $product_sku = $product->getSku();
                        //make 360 images
                        $images_360 = $product->getView360();
                        $gallery = $product->getGallery();
                        /*if (empty($images_360)){
                            foreach (glob(get_stylesheet_directory() . '/assets/images/360/' . '*.jpg') as $file) {
                                $images_360[] = IMG_URL.'360/'.basename($file);
                            }
                        }*/
                        /*if (empty($data['gallery'])){
                            for ($i=1;$i<=4;$i++){
                                $data['gallery'][] = randomImage();
                            }
                        }*/
	                    $promotions_box = get_field('promotion_box','options');
                    
                    ?>
                    <div class="single-product row product-data" <?php echo $product->itemMetaData()  ?>>
                        <div class="col-md-3">
                            <div class="single-product-box product-image-container bg-white p-3 shadow-sm mb-3">
                                <div class="main-image">
                                    <figure>
                                        <img src="<?php echo $noimg ?>" alt="<?php $product->theTitle(); ?>" id="main-product-image" class="img-fluid">
                                    </figure>
                                    <div class="zoom-lens"></div>
                                    <div class="zoom-result shadow-sm">
                                        <img src="<?php echo $noimg ?>" alt="Zoomed Image" class="zoomed-image">
                                    </div>
                                </div>
                                <div class="product-media-nav d-flex">
                                    <div class="thumbnail-navigation swiper-container">
                                        <div class="swiper-wrapper">
                                            <div class="swiper-slide">
                                                <img src="<?php echo $noimg?>" alt="Thumbnail main" data-image="<?php echo $noimg ?>" class="thumbnail-item">
                                            </div>
                                            <?php
                                            $gallery_items='';
                                            if ($gallery) {
                                                foreach ($gallery as $g_img){

                                                    $gallery_items .= '<div class="swiper-slide"><img src="'.($g_img).'" alt="" data-image="'.($g_img).'" class="thumbnail-item"></div>';
                                                }
                                            }
                                            echo $gallery_items;
                                            ?>

                                        </div>
                                        <!-- Add Navigation Arrows -->
                                        <div class="swiper-button-next"></div>
                                        <div class="swiper-button-prev"></div>
                                    </div>
                                    <div class="product-video">
                                        <a href="#" id="view-video-btn" class="" data-video="<?php echo $video_url?>" >
                                            <img src="<?php echo $view_video ?>" alt="Video">
                                        </a>
                                    </div>
                                    <div class="view-360">
                                        <a href="#" id="360-button" data-image360='<?php echo json_encode($images_360)  ?>'>
                                            <img src="<?php echo $view_360 ?>" alt="360 Icon">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <!-- Lightbox -->
                            <div id="lightbox" class="modal fade product-gallery" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-fullscreen" role="document">
                                    <div class="modal-content">
                                        <div class="modal-body">
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            <!-- Swiper chính -->
                                            <div class="swiper lightbox-swiper">
                                                <div class="swiper-wrapper">
                                                    <div class="swiper-slide">
                                                        <img src="<?php echo $noimg ?>" alt="main Image" class="">
                                                    </div>
                                                    <?php echo str_replace('thumbnail-item','img-fluid1',$gallery_items); ?>
                                                </div>
                                                <div class="swiper-button-next"></div>
                                                <div class="swiper-button-prev"></div>
                                            </div>
                                            <!-- Swiper thumbnails -->
                                            <div class="swiper lightbox-thumbs">
                                                <div class="swiper-wrapper">
                                                    <div class="swiper-slide">
                                                        <img src="<?php echo $noimg ?>" alt="thumb Image" class="thumbnail-item">
                                                    </div>
                                                    <?php echo $gallery_items; ?>
                                                </div>
                                                <div class="swiper-button-next"></div>
                                                <div class="swiper-button-prev"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="video360-lightbox" class="modal fade" tabindex="-1" role="dialog">
                                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                    <div class="modal-content">
                                        <button type="button" class="btn-close video-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                        <div class="modal-body">

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="single-product-box detail-box bg-white mb-3" style="--box-color-style:var(--bs-success);">
                                <div class="box-head d-flex items-justified-space-between">
                                    <div class="d-flex items-justified-center align-items-center"><i class="vcl-icon bi bi-ui-checks"></i>  Tính năng nổi bật</div>
                                    <div class="fb-social">Like & share</div>
                                </div>
                                <div class="box-body">
                                    <?php echo $product->getFeatures() ?>
                                </div>
                                <hr class="box-divider">
                                <div class="d-flex items-justified-space-between align-items-center py-2 px-3">
                                    <div class="share-product d-flex items-justified-space-between align-items-center">Share:
                                        <div class="shareon" data-url="<?php echo $product->getURL()  ?>">
                                            <button class="messenger" data-fb-app-id="APP ID"></button>
                                            <button class="facebook" data-title="Custom Link Title" data-hashtags="VotCauLongShop"></button>
                                            <button class="pinterest" ></button>
                                            <button class="twitter" data-via="your twitter username"></button>
                                            <button href="#"  class="copy-url"></button>
                                        </div>
                                    </div>
                                    <div class="favourite-product">
                                        <?php 
                                            $class_added = ($customer && $customer->hasLikedProduct($product->getSku()))?'added':'';
                                            $product_liked = $product->getTotalLikes();
                                            $liked_text = sprintf(__('Favorite (%d)', LANG_ZONE),$product_liked );
                                        ?>
                                        <button class="favourite-btn <?php echo $class_added;?>" data-product-id="<?php echo $product->getSku()  ?>"> <?php echo $liked_text?></button>
                                    </div>
                                </div>
<!--                                <hr class="box-divider">-->
<!--                                <div class="qrcode p-3">-->
<!--                                    Quà Tặng VIP-->
<!--                                    Sản phẩm của VOTCAULONGSHOP-->
<!--                                    Quét để tải App-->
<!--                                    Tích & Sử dụng điểm cho khách hàng thân thiết.-->
<!--                                </div>-->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="single-product-box product-info bg-white p-3 shadow-sm mb-3">
                                <div class="info-head d-flex align-items-center">
                                    <?php
                                    if ($product->getBadgeHtml_detail()) {
                                        echo '<div class="text-badge">'.$product->getBadgeHtml_detail().'</div>';
                                    }
                                    ?>
                                    <?php if ($productBrand) {?>
                                        <div class="brand-logo "><a href="<?php echo $productBrand['url']  ?>"><img src="<?php echo $brand_logo  ?>" alt="<?php echo $productBrand['name']  ?>"></a></div>
                                    <?php }  ?>
                                </div>
                                <div class="product-title-section position-relative">
                                    <h1 class="product-title"><?php $product->theTitle();  ?></h1>
                                    <a href="#" role="button" class="btn text-primary fw-bold compare-btn" onclick="addToCompare(this)" data-id="<?php echo $product->getSku()  ?>"><i class="bi bi-plus-circle"></i> <?php _e('Compare',LANG_ZONE)  ?></a>
                                </div>
                                <div class="product-meta">
                                    <div class="rating-wrapper">
                                        <?php $product->displayRatingStars();?>
                                    </div>
                                    <div class="sold"><?php printf(__('Sold : %s', LANG_ZONE),(string)$product->getSold() );  ?></div>
                                    <div class="brand"><?php _e('Brand', LANG_ZONE)  ?>: <span class="brand-name"><?php echo $product->getBrand() ?></span></div>
                                </div>
                                <div class="text-badge badge-style-2">
                                    
                                    <div class="badge text-label top-ban-chay">#5 Top bán chạy</div>
                                </div>
                                <div class="product-meta-text sku"><?php _e('SKU', LANG_ZONE)  ?>: <span><?php echo $product_sku;  ?></span></div>
                                <div class="product-meta-text status <?php echo $stock_slug?>"><?php _e('Status',LANG_ZONE)  ?>: <span><?php  echo $stock_label;  ?></span></div>
                                <div class="price d-flex items-justified-space-between"><?php echo $product->getHTML_price_detail()  ?>		</div>
                                <div class="variations">
                                <?php
                                    echo $product->variationsRender();
//                                    my_debug($product->getVariate_BranchStock());
                                    if ($product->hasVariations()){
                                ?>
                                    <script>
                                        window.productVariants = <?php echo json_encode($product->variations['combinations']) ?>;
                                        window.BranchStock = <?php echo json_encode($product->getVariate_BranchStock()) ?>;
                                    </script>
                                    <?php }  ?>
                                </div>
                            </div>
                            <?php 

                            if ($promotions_box){
                                foreach ($promotions_box as $box){
                                    $can_show = false;
                                    $box_apply = $box['apply_to']??[];
                                    $show_sku = false;
                                    if (!in_array($box['box_style'],['box-style-1','box-style-2'])){
                                        continue;
                                    }
                                    if (in_array('all', $box_apply)) {
                                        $can_show = true;
                                    } else {
                                        if (!$can_show && in_array('brand', $box_apply) && !empty($box['brand_select'])) {
                                            $product_brand_name = strtolower($productBrand['name']);
                                            foreach ($box['brand_select'] as $select_brand) {
                                                if (strtolower($select_brand->post_title) == $product_brand_name) {
                                                    $can_show = true;
                                                    break; 
                                                }
                                            }
                                        }
                                        if (!$can_show && in_array('category', $box_apply) && !empty($box['category_select'])) {
                                            $product_cat_name= strtolower($product->getCategoryName());
                                            foreach ($box['category_select'] as $select_cat) {
                                                if (strtolower($select_cat->name) == $product_cat_name) {
                                                    $can_show = true;
                                                    break; 
                                                }
                                            }
                                        }
                                        if (!$can_show && in_array('product', $box_apply) && !$box['product_sku']=='') {
                                            if (strtolower($box['product_sku']) == strtolower($product_sku)) {
                                                $can_show = true;
	                                            $show_sku = true;
                                            }
                                        }
                                        
                                    }
                                    if ($can_show){
                                        $box_style = $box['box_style']=='box_style_1'?'':$box['box_style'];
                                        $box_color = $box['box_color'];
                                        ?>
                                        <div class="single-product-box detail-box <?php echo $box_style; ?> bg-white shadow-sm mb-3" style="--box-color-style:<?php echo $box_color; ?>;">
                                            <div class="box-head">
                                                <i class="vcl-icon vcl-icon-<?php echo $box['box_icon'] ?>"></i>  <?php echo $box['box_title']. ($show_sku?' '.$box['product_sku']:'')?>
                                            </div>
                                            <div class="box-body">
                                                <?php echo $box['box_content']?>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                }
                            }
                            ?>
                            
                            
                            <p class="reward-point fs-6 d-none"><span class="text-danger fw-bold">+10.900</span> điểm tích luỹ Quà Tặng VIP <a href="#"><i class="bi bi-question-circle"></i></a></p>
                            <div class="addToCart-box">
                                <form class="addToCart-form">
                                    <div class="mb-3 d-flex">
                                        <label for="quantity" class="col-form-label fs-6 me-3"><?php _e('Quantity',LANG_ZONE)  ?></label>
                                        <div class="col-md-4 input-group qty-control shadow-sm">
                                            <button class="qty-btn qty-minus input-group-text" type="button"><i class="bi bi-dash-lg"></i></button>
                                            <input type="text" class="form-control text-center item-qty" id="quantity" name="quantity" value="1">
                                            <button class="qty-btn qty-plus input-group-text"  type="button"><i class="bi bi-plus-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="col-md-12 ">
                                        <button class="btn btn-danger btn-2line w-100 mb-3" id="quickBuy" data-checkout="<?php echo get_field('shopping_cart','options');  ?>" type="button"><span>Mua ngay</span><span>Giao hàng tận nơi</span></button>
                                        <div class="btn-group mb-3 w-100 column-gap-3">
                                            <button class="btn btn-primary btn-2line w-50 " id="addToCart"><span>Thêm vào giỏ hàng</span></button>
                                            <button class="btn btn-primary btn-2line w-50 " id="muaTraGop" type="button" data-checkout="<?php echo get_field('shopping_cart','options');  ?>"><span>Mua trả góp 0%</span><span>Duyệt hồ sơ trong 5 phút</span></button>
                                        </div>

                                    </div>

                                </form>
                            </div>
                            <?php if ($product->hasShippingEstimate()) {?>
                            <div class="single-product-box shipping-estimate-box bg-white shadow-sm p-3 mb-3">
                                <p class="fw-medium">Miễn phí vận chuyển  <a href="#" class="fw-normal link-underline link-underline-opacity-0" role="button" ><i class="bi bi-geo-alt"></i> Chọn địa chỉ nhận hàng để biết thời gian giao.</a></p>
                                <p class="fst-italic">"Đặt hàng trong vòng 12 giờ 4 phút tới để được xử lý ngay hôm nay và nhận hàng dự kiến từ ngày 23 đến ngày 27 tháng 10.”</p>
                                <div class="shipping-box-icon d-flex items-justified-space-between align-items-center">
                                    <i class="bi bi-box"></i>
                                    <hr class="shipping-box-icon w-100">
                                    <i class="vcl-icon-truck-2"></i>
                                    <hr class="shipping-box-icon w-100">
                                    <i class="bi bi-geo-alt-fill"></i>
                                </div>
                                <div class="shipping-box-desc d-flex items-justified-space-between align-items-center">
                                    <div class="desc">Đơn hàng đã được xử lý<br><span class="start-date">ngày 10 tháng 10</span></div>
                                    <div class="desc text-center">Đơn hàng đã được vận chuyển<br><span class="shipping-date">ngày 15 tháng 10</span></div>
                                    <div class="desc text-end">Đã giao hàng<br><span class="start-date">23 tháng 10 - 27 tháng 10</span></div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <div class="col-md-3">
                            <?php if ($branches = $product->getBranch()){
                                    $branches_data = [];
                                ?>
                            <div class="single-product-box detail-box box-style-3 bg-white shadow-sm mb-3" style="--box-color-style:var(--theme-gray);">
                                <div class="box-head">
                                    <i class="vcl-icon vcl-icon-location"></i> đang có hàng tại
                                </div>
                                <div class="box-body p-0">
                                    <ul class="product-store">
                                        <?php foreach ($branches as $branch) {
	                                        $branches_data[$branch['erp_branch_id']] = [
                                                    'url' => $branch['post_id']?get_permalink($branch['post_id']):'#url',
                                                    'name' => $branch['name'],
                                                    'id'    => $branch['post_id']??''
                                            ]
                                            ?>
                                        <li><a href="<?php echo $branch['post_id']?get_permalink($branch['post_id']):'#';  ?>" role="button"><span class="store-name"><i class="bi bi-shop-window"></i> <?php echo $branch['name']  ?></span></a><span class="store-stock"><?php $branch['total_stock']?printf(__('%d product(s) left',LANG_ZONE),$branch['total_stock'][0]??$branch['total_stock'] ):_e('Out of stock',LANG_ZONE);  ?></span> </li>
                                        <?php }  ?>

                                    </ul>
                                </div>
                            </div>
                                <script>
                                    window.branches_data = <?php echo json_encode($branches_data) ?>;
                                </script>
                            <?php
                                }  //branches
                            if ($promotions_box){
	                            foreach ($promotions_box as $box){
		                            $can_show = false;
		                            $box_apply = $box['apply_to']??[];
		                            if (in_array($box['box_style'],['box-style-1','box-style-2'])){
			                            continue;
		                            }
		                            if (in_array('all', $box_apply)) {
			                            $can_show = true;
		                            } else {
			                            if (!$can_show && in_array('brand', $box_apply) && !empty($box['brand_select'])) {
				                            $product_brand_name = strtolower($productBrand['name']);
				                            foreach ($box['brand_select'] as $select_brand) {
					                            if (strtolower($select_brand->post_title) == $product_brand_name) {
						                            $can_show = true;
						                            break;
					                            }
				                            }
			                            }
			                            if (!$can_show && in_array('category', $box_apply) && !empty($box['category_select'])) {
				                            $product_cat_name= strtolower($product->getCategoryName());
				                            foreach ($box['category_select'] as $select_cat) {
					                            if (strtolower($select_cat->name) == $product_cat_name) {
						                            $can_show = true;
						                            break;
					                            }
				                            }
			                            }
			                            if (!$can_show && in_array('product', $box_apply) && !$box['product_sku']=='') {
				                            if (strtolower($box['product_sku']) == strtolower($product_sku)) {
					                            $can_show = true;
					                            $show_sku = true;
				                            }
			                            }

		                            }
		                            if ($can_show){
			                            $box_style = $box['box_style']=='box_style_1'?'':$box['box_style'];
			                            $box_color = $box['box_color'];
			                            ?>
                                        <div class="single-product-box detail-box <?php echo $box_style; ?> bg-white shadow-sm mb-3" style="--box-color-style:<?php echo $box_color; ?>;">
                                            <div class="box-head">
                                                <i class="vcl-icon vcl-icon-<?php echo $box['box_icon'] ?>"></i>  <?php echo $box['box_title']?>
                                            </div>
                                            <div class="box-body">
					                            <?php echo $box['box_content']?>
                                            </div>
                                        </div>
			                            <?php
		                            }
	                            }
                            }
                            ?>

                            <?php if ($product->hasComboProducts()) { ?>
                            <div class="single-product-box detail-box box-style-3 bg-white shadow-sm mb-3" style="--box-color-style:#21935C;">
                                <div class="box-head">
                                    <i class="vcl-icon vcl-icon-gift-box"></i>Giảm thêm khi mua kèm
                                </div>
                                
                                <div class="combo-products">
                                    <div class="combo-list p-3">
                                        <div class="combo-item main-product">
                                            <div class="combo-item-image">
                                                <img src="<?php echo $noimg  ?>" alt="Vợt cầu lông Yonex Nanoflare 700 Pro 2024">
                                            </div>
                                            <div class="combo-item-detail">
                                                <p class="item-name"><?php $product->theTitle()  ?></p>
                                                <p class="item-sku"><?php echo $product->getSku()  ?></p>
                                                <div class="item-price"><?php echo $product->getHTMLprice()  ?></div>
                                            </div>
                                        </div>
                                        <div class="combo-item ">
                                            <input type="checkbox">
                                            <div class="combo-item-image">
                                            <img src="<?php echo randomImage()  ?>" alt="Cước căng yonex BG65 Titanium " >
                                            </div>
                                            <div class="combo-item-detail">
                                                <p class="item-name">Cước căng yonex BG65 Titanium </p>
                                                <p class="item-sku">SKU-12345</p>
                                                <div class="item-price"><p class="old">1,900,000đ</p><p class="new">900,000đ</p></div>
                                            </div>
                                        </div>

                                        <div class="combo-item ">
                                            <input type="checkbox">
                                            <div class="combo-item-image">
                                            <img src="<?php echo randomImage()  ?>" alt="Cước căng yonex BG65 Titanium " >
                                            </div>
                                            <div class="combo-item-detail">
                                                <p class="item-name">Cước căng yonex BG65 Titanium </p>
                                                <p class="item-sku">SKU-12345</p>
                                                <div class="item-price"><p class="old">1,900,000đ</p><p class="new">900,000đ</p></div>
                                            </div>
                                        </div>

                                        <div class="combo-item ">
                                            <input type="checkbox">
                                            <div class="combo-item-image">
                                                <img src="<?php echo randomImage()  ?>" alt="Cước căng yonex BG65 Titanium " >
                                            </div>
                                            <div class="combo-item-detail">
                                                <p class="item-name">Lorem ipsum dolor sit amet, consectetur adipisicing elit. </p>
                                                <p class="item-sku">SKU-12345</p>
                                                <div class="item-price"><p class="old">1,900,000đ</p><p class="new">900,000đ</p></div>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="box-divider">
                                    <div class="combo-total p-3">
                                        <p class="combo-total-price">Tổng tiền: <span class="new">3.900.000đ</span><span class="old">4.900.000đ</span></p>
                                        <button role="button" class="btn w-100 btn-2line"><span>Mua 3 sản phẩm</span><span>Tiết kiệm: 2.199.000đ </span> </button>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                        <?php 
                            $related_products = $product->getRelatedProducts(5);

                            if ($related_products){
//                                my_debug($related_products);
                        ?>
                        <div class="col-12">
                            <div class="related-products bg-white mb-3 shadow-sm category-product-listing detail-box box-style-3" style="--box-color-style:var(--theme-gray);">
                                <div class="box-head">
                                    Sản phẩm liên quan
                                </div>
                                <div class="product-grid shadow-item2 position-relative box-body p-3" style="--item-cols:5;">
                                    <?php
                                        foreach ($related_products as $related_product) {
                                            get_template_part('template-parts/product-item','',['product'=> $related_product]);
                                        }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php  } ?>
                        <div class="col-12">
                            <div class="product-tabs shadow-sm">
                                <ul class="nav nav-tabs bg-white rounded-top" id="productTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active p-3" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Chi tiết sản phẩm</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link p-3" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button" role="tab" aria-controls="review" aria-selected="false">Đánh giá sản phẩm</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="productTabs-content">
                                    <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                                        <?php //echo $product->getDescription() ?>
                                        <div class="product-description bg-white shadow-sm mb-3 p-3 detail-box">
                                        
                                            <div class="box-body p-0">
                                                <?php echo $product->getDescription();?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="review" role="tabpanel" aria-labelledby="review-tab">
                                        <div class="bg-white shadow-sm mb-3 p-3 detail-box">
                                        
                                            <div class="box-body p-0">
                                                <div class="row">
                                                    <?php 
                                                    $current_product_id_for_review = 0;
                                                    if ( isset($product) && is_object($product) && method_exists($product, 'getID') ) {
                                                        $current_product_id_for_review = $product->getID();
                                                    } elseif (isset($product_id) && ($product_id)) {
                                                        $current_product_id_for_review = $product_id;
                                                    }
                                                    
                                                    ?>
                                                    <div class="col-6">
                                                        <?php 
                                                        //my_debug($product->getReviews());
                                                        if ($product) {
                                                            do_action('vcl_after_product_description', $product);
                                                        } else {
                                                            echo '<p>Không thể tải đánh giá, ID sản phẩm không xác định.</p>';
                                                        }
                                                        ?>
                                                        <!-- <div class="rating-wrapper-sum">
                                                            
                                                            <div class="rateit" data-rateit-value="4.5" data-rateit-ispreset="true" data-rateit-readonly="true"  ></div>
                                                            
                                                        </div> -->
                                                    </div>
                                                    <div class="col-6">
                                                        <!-- Product Review Form -->
                                                        <div class="product-review-form">
                                                            <!-- <h6>Đánh giá sản phẩm</h6> -->
                                                            <?php
                                                                if ($current_product_id_for_review) {
                                                                    echo do_shortcode('[product_review_form product_id="' . $current_product_id_for_review . '"]');
                                                                }
                                                                ?>
                                                        
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                            
                    </div>
                    <?php }
                    } else {
                        require_once('not-found-page.php');
                    }?>
            </div>
                
			</div>
		</div>
	</div>

<?php
get_footer(); ?>