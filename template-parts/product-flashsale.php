<?php
$p_info = $args['product'];
$product = new Product($p_info);
$item_class = '';
if (isset($args['class'])){
	$item_class = $args['class'];
}
$rand_num = rand(1,8);
?>
<div class="product-item <?php echo $item_class ?>" <?php echo $product->itemMetaData()  ?>>
	<div class="image">
        <a href="<?php $product->theURL();  ?>"><img src="<?php echo IMG_URL?>san-pham/product_<?php echo $rand_num  ?>.jpg" alt="<?php $product->theTitle();  ?>" loading="lazy"></a>
	</div>
	<div class="content">
        <h5 class="title"><a href="<?php $product->theURL();  ?>"><?php $product->theTitle();  ?></a></h5>
		<div class="price">
			<?php echo $product->getHTMLprice()  ?>
		</div>
		<div class="stock" style="--max-stock:<?php echo $p_info['quantity_for_program']; ?>;--stock:<?php echo $p_info['quantity_for_program'] - $p_info['quantity_sold']; ?>;">
			<span class="stock-icon"><i class="bi bi-lightning-charge-fill"></i></span>
			<span>Còn <?php echo $p_info['quantity_for_program'] - $p_info['quantity_sold']; ?> sản phẩm</span>
		</div>

	</div>
	<div class="buttons">
		<button class="btn btn-danger addCart" >Chọn mua</button>
	</div>
</div>