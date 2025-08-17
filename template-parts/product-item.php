<?php global $product_info;
if (isset($args['product']) && is_object($args['product'])){
	$product = $args['product'];
}elseif (isset($args['product_data']) && is_array($args['product_data'])){
//	$product = get_product($product_info['item_code'],0,false);
	$product = new Product($args['product_data']);

}elseif(isset($product_info)){
	$product = new Product($product_info);
}else{
    return false;
}
$compare_list = $args['compare_list']??false;
$rand_num = rand(1,8);
$noimg = IMG_URL.'san-pham/product_'.$rand_num.'.jpg';
$item_class = '';
if (isset($args['class'])){
	$item_class = $args['class'];
}
?>
<div class="product-item product-data flex-column <?php echo $item_class  ?>" <?php echo $product->itemMetaData()  ?>>
	<div class="item-content flex-grow-1">
        <?php echo $product->getDiscountLabel()  ?>
        <?php echo $product->getBadgeHtml()  ?>
        <div class="image item-image">
            <a href="<?php $product->theURL();  ?>" class="product-url">
                <img src="<?php echo $product->getImageUrl()?>" data-bs-toggle="tooltip" title="<?php $product->theTitle()  ?>" loading="lazy">
            </a>
        </div>
        <?php
        if ($product->hasVariations()) {
            $maxCount = 6;
            $variations_img = $product->getVariationsImage();
            $class =$style='';
            if (count($variations_img)>$maxCount){
                $class = 'more-variations';
                $style = 'style="--more-variations:\'+'.(count($variations_img)-$maxCount).'\';"';
            }
            ?>
            <div class="image-variations <?php echo $class;?>" <?php echo $style;?>>
                    <?php
                    $gallery_items='';
                    $i=0;
                    if ($variations_img) {
                        foreach ($variations_img as $g_img){
                            $i++;
                            if ($i>$maxCount) break;
                            $gallery_items .= '<div class="variation-photo" data-img="'.$g_img.'"><a href="'.$product->getURL().'"><img src="'.$g_img.'" alt="" data-image="'.$g_img.'" class="thumbnail-item"></a></div>';
                        }
                    }
                    echo $gallery_items;
                    ?>
            </div>
            <?php
        }
        ?>
        <div class="text-badge"><?php echo $product->getTextLabel()  ?></div>

        <div class="content">
            <h5 class="title" data-bs-toggle="tooltip" title="<?php $product->theTitle()  ?>"><a href="<?php $product->theURL();  ?>" class="product-url"><?php $product->theTitle() ?></a></h5>
            <!-- <div class="product-meta-text sku"><?php //echo $product->getSku()  ?></div> -->
            <div class="price">
                <?php echo $product->getHTMLprice()  ?>
            </div>
        </div>
        <?php if($free_items = $product->getFreeItems()) { ?>
            <ul class="free_items">
                <?php foreach ($free_items as $free){
	                printf(
		                '<li class="free_item_line"><span class="qty">%dx</span> %s</li>',
		                $free['qty'],
		                sprintf(__('Free %s', LANG_ZONE), $free['title'])
	                );
                }?>
            </ul>
        <?php } ?>
	</div>
	<div class="buttons">
	<?php
	if ($product->list_item_stock()){
		$addCartClass = 'addCart';
		$btn_text = __('Buy now', LANG_ZONE);
	}else{
		$addCartClass = 'OutStock';
		$btn_text = __('Out of stock', LANG_ZONE);
	}
    if ($product->hasVariations()) { 	?>
					<a href="<?php $product->theURL(); ?>" role="button" class="btn btn-sm btn-secondary <?php echo $addCartClass!='addCart'?$addCartClass:''  ?>"><?php echo $btn_text  ?></a>
    <?php } else {            ?>
		        <button class="btn btn-sm btn-secondary <?php echo $addCartClass  ?>" ><?php echo $btn_text  ?></button>
		<?php } ?>
        <div class="text-end">
            <div class="qty-sold text-black-50"><?php echo $product->getSold()  ?></div>
			
            		<a href="#" role="button" class="text-decoration-none text-primary fw-bold compare-btn" onclick="addToCompare(this)" data-id="<?php echo $product->getSku()  ?>"><?php _e('Compare',LANG_ZONE)  ?></a>
				
        </div>

	</div>
</div>