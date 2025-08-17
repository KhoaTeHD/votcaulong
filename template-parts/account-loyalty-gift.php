<h5 class="title"><?php _e('Loyalty gift',LANG_ZONE)  ?></h5>
<div class="loyalty-member ">
<?php
$customer = get_current_customer();
//$product_id = $_POST['product_id'];
$loyalty_rank_slug = 'gold';
$loyalty_rank = 'Vàng';
$loyalty_rank_slug=sanitize_title($loyalty_rank);
$order_total = "5/20";
$rank_expired = '31.12.2025';
$order_percent = $spend_percent = $loyalty_point = 0;
$loyalty = $customer->getLoyalty();
if ($loyalty) {
	$current_tier = $loyalty['current_tier'];
    $current_stats = $loyalty['current_stats'];

}
?>

</div>
