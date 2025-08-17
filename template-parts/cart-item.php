<?php
$product = $args['product']; // Dữ liệu sản phẩm được truyền vào từ hàm get_template_part
$url = ProductUrlGenerator::createProductUrl($product['title'],$product['id']);
$attrs = '';
$variant_product = '';
$original_price = 0;
$product_obj = new Product($product);
$remove_sku = '';
$selected_rule = $product['selected_rule']??'';
if (isset($product['variation']) && count($product['variation'])){

    $variant_product = new Product($product['variation']);
    $image_url = $variant_product->getImageUrl();
    $price = $variant_product->getPrice();
    if( $varAttr = $product['attributes']){
	    $attrs .= '<ul class="item-attr">';
        foreach ($varAttr as $key => $val){
	        $attrs .= '<li><span class="key">'.$key.'</span>: <span class="val">'.$val.'</span></li>';
        }
	    $attrs .= '</ul>';
    }
    $original_price = $variant_product->getOriginalPrice();
	$remove_sku = $variant_product->getSku();
}else{
	$image_url = $product_obj->getImageUrl();
	$price = $product['price'];
    $original_price = $product['original_price'];
	$remove_sku = $product['id'];
}
$test_free = [
	[
		'title' => 'ÁO KHOÁC CẦU LÔNG YONEX 611A XANH',
		'sku' => 'SP004827',
		'qty' => 1,
	],
	[
		'title' => 'Cuốn cán vợt',
		'sku' => 'SP00482711',
		'qty' => 3,
	],
];
$free_items = $product_obj->getFreeItems()??[];
?>

<div class="cartPage-item d-flex items-justified-space-between" data-variation-sku="<?php echo $variant_product ?$variant_product->getSku(): '' ?>" data-id="<?php echo esc_attr($product['id']); ?>" data-sku="<?php echo esc_attr($product['sku']); ?>" data-price="<?php echo esc_attr($price); ?>">

    <div class="cartItem-left ">
        <div class="item-image">
            <a href="<?php echo $url ?>" class="product-url"><img src="<?php echo esc_url($image_url); ?>" class="img-thumbnail cart-item-image" loading="lazy"></a>
        </div>
        <button class="remove-from-cart btn btn-danger btn-sm" data-product-id="<?php echo $remove_sku; ?>"><i class="bi bi-trash3"></i> <span><?php _e('Remove',LANG_ZONE)  ?></span></button>

    </div>
    <div class="cartItem-right d-flex items-justified-space-between">

        <div class="cartItem-body">
            <h3 class="item-title"><a href="<?php echo $url ?>" class="product-url"><?php echo esc_html($product['title']); ?></a></h3>
            <?php echo $attrs;  ?>
            <!--<div class="item-dec"><?php //echo esc_html($product['description']); ?></div> //-->
	        <?php if (!empty($free_items)) :
                $freeItem_ID = $product_obj->getSku().'_free_items';
                ?>
                <div class="d-flex ">
                    <span class="promotion-title"><?php _e('Promotions are being applied', LANG_ZONE)?>&nbsp;</span>
                    <a class="btn-promotion-detail" data-bs-toggle="collapse" href="#<?php echo $freeItem_ID;?>"  aria-expanded="false" aria-controls="collapseExample">
                        <?php _e('View details',LANG_ZONE) ?>
                    </a>
                </div>
                <ul class="item-promotions free_items collapse show" id="<?php echo $freeItem_ID ?>">
			        <?php foreach ($free_items as $idx =>$free) : ?>
                            <?php
                            if ($selected_rule && $selected_rule == $free['sku']){
                                $is_checked = 'checked';
                            }elseif ($idx===0){
	                            $is_checked = 'checked';
                            }else{
                                $is_checked = '';
                            }
                            printf('<li class="free_item_line"><input type="radio" value="%s" id="%s" name="item_pricing_rule" '.$is_checked.' >&nbsp;<label for="%s">%s</label></li>',$free['sku'],$free['sku'],$free['sku'],$free['title']);
                            /*printf(
	                            '<li class="free_item_line"><span class="qty">%dx</span> %s</li>',
	                            $free['qty'],
	                            sprintf(__('Free %s', LANG_ZONE), $free['title'])
                            );*/
                            ?>

			        <?php endforeach; ?>
                </ul>
	        <?php endif; ?>
        </div>
        <div class="cartItem-foot align-self-stretch">
            <div class="text-end">
                <div class="item-total "><?php echo priceFormater($price * $product['quantity']); ?></div>
                <?php if ($original_price > 0 && $original_price > $price ) :?>
                    <div class="item-original-price text-decoration-line-through "><?php echo priceFormater($original_price * $product['quantity']);?></div>  
                <?php endif;?>
            </div>
            <div class="cartItem-qtyBox">
                <button class="qty-btn qty-minus" role="button"><i class="bi bi-dash-lg"></i></button>
                <input class="item-qty form-control border-0" value="<?php echo esc_attr($product['quantity']); ?>">
                <button class="qty-btn qty-plus" role="button"><i class="bi bi-plus-lg"></i></button>
            </div>
        </div>
    </div>

</div>