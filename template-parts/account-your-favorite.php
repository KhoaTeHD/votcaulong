<h5 class="title"><?php _e('Favorites',LANG_ZONE)  ?></h5>
<p ><?php _e('List of your favorite products',LANG_ZONE)  ?></p>
<div class="account-wishlist grid">
<?php
$customer = get_current_customer();
//$product_id = $_POST['product_id'];
$wishlist= $customer->getWishlistItems();

if ($wishlist) {
   
foreach ($wishlist as $pro){
	// $pro = new Product($wl_product);
?>
    <div class="wishlist-item g-col-3 d-flex product-item " <?php echo $pro->itemMetaData()  ?>>
        <div class="product-image me-2 position-relative">
            <a href="<?php echo $pro->getURL()  ?>" class="product-url"><img src="<?php echo $pro->getImageUrl()  ?>" class="img-thumbnail" alt="<?php echo $pro->getTitle()  ?>" width="80px"></a>
            <a class="btn text-black-50 remove-favorite-btn m-0 p-0 text-decoration-none border-0 position-absolute top-0 start-0" role="button" href="#" data-product-id="<?php echo $pro->getSku()  ?>"><i class="bi bi-x-circle-fill"></i></a>
        </div>
        <div class="product-content">
            <p class="title m-0" style="min-height: auto;"><a href="<?php echo $pro->getURL()  ?>" class="product-url" style="min-height: auto;"><?php echo $pro->getTitle()  ?></a></p>
            <p class="product-meta-text sku m-0"><?php echo $pro->getSku()  ?></p>
            <div class="price m-0"><?php echo $pro->getHTMLprice()  ?></div>
            <a href="<?php $pro->theURL(); ?>" role="button" class="btn btn-sm btn-secondary"><?php _e('Buy now',LANG_ZONE)  ?></a>
        </div>

    </div>
    <?php
}
}
?>
</div>