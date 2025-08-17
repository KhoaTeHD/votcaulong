<h5 class="title"><?php _e('Loyalty',LANG_ZONE)  ?></h5>
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
	$spend = $current_stats['total_spent']?priceFormater($current_stats['total_spent']):0;
	$rank_limit = $current_tier['min_spent']?priceFormater($current_tier['min_spent']):0;
    $loyalty_point = $current_stats['loyalty_points']??0;
	$next_tier = $loyalty['next_tier_requirements'];
	$loyalty_rank_slug = sanitize_title($current_tier['tier_name']);
    if($current_stats['total_orders'] &&$current_tier['min_orders'] ){
	    $order_percent = round(($current_stats['total_orders']*100)/$current_tier['min_orders'],0);
    }
    if ($spend &&$rank_limit ){
	    $spend_percent = round(($spend*100)/$rank_limit,0);
    }

    ?>
    <div class="loyalty-card <?php echo $loyalty_rank_slug;  ?>">
        <div class="loyalty-card-header">
            <div>
                <div class="loyalty-card-title"><?php echo $current_tier['tier_name'] ?></div>
                <div class="loyalty-card-user"><?php echo $customer->display_name  ?></div>
            </div>
            <div class="loyalty-card-benefit"><a href="#">Ưu đãi mỗi thứ hạng ›</a></div>
        </div>
        <div class="loyalty-card-main">
            <div class="mb-1">Điểm thưởng: <span class="loyalty-card-progress-label"><?php echo $loyalty_point ?></span></div>
            <div class="loyalty-card-progress-label">Duy trì thứ hạng thẻ</div>
            <div class="loyalty-card-progress">
                <div class="loyalty-card-progress-item">
                    <div>Đơn hàng</div>
                    <div><span><?php echo $current_stats['total_orders']  ?></span>/<?php echo $current_tier['min_orders']  ?></div>
                    <div class="loyalty-card-progress-bar-bg" style="--progress-var:<?php echo $order_percent  ?>%;">
                        <div class="loyalty-card-progress-bar"></div>
                    </div>
                </div>
                <div class="loyalty-card-progress-item">
                    <div>Chi tiêu</div>
                    <div><span><?php echo $spend  ?></span>/<?php echo $rank_limit  ?></div>
                    <div class="loyalty-card-progress-bar-bg" style="--progress-var:<?php echo  $spend_percent  ?>%;">
                        <div class="loyalty-card-progress-bar" ></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="loyalty-card-footer">
            <div>Thứ hạng sẽ được cập nhật lại sau 31.12.2025.</div>
            <a href="#">Chi Tiết ></a>
        </div>
    </div>
    <?php
}
?>

</div>
<style>
    .loyalty-member {display: grid; grid-template-columns: 1fr 1fr; gap:15px;}
    .loyalty-card {
        max-width: 500px;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.13);
        background: #d7eecb;
        color: var(--theme-gray);
        /*margin: 40px auto;*/
        position: relative;
    }
    .loyalty-card-header {
        padding: 18px 24px 0 24px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .loyalty-card-title {
        font-size: 22px;
        font-weight: bold;
        letter-spacing: 1px;
        text-shadow: 0 1px 2px #b88b2c33;
        text-transform: uppercase;
    }
    .loyalty-card-benefit {
        font-size: 14px;
        opacity: 0.95;
        font-weight: 600;
    }
    .loyalty-card-user {
        padding: 0 24px 15px 0;
        font-size: 15px;
        opacity: 0.93;
        font-weight: 500;
    }
    .loyalty-card-main {
        background: #fff;
        color: var(--theme-gray2);
        border-radius: 14px;
        margin: 0 16px;
        box-shadow: 0 2px 8px #f4a33813;
        padding: 18px 16px 8px 16px;
        margin-bottom: 14px;
        position: relative;
        font-weight: 500;
    }
    .loyalty-card-progress-label {
        color: var(--bs-danger);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 9px;
    }
    .loyalty-card-progress {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 16px;
    }
    .loyalty-card-progress-item {
        width: 46%;
    }
    .loyalty-card-progress-item span {
        font-size: 15px;
        font-weight: 700;
        color: #CF432C;
    }
    .loyalty-card-progress-bar-bg {
        width: 100%;
        height: 7px;
        background-color: var(--bs-gray-200);
        border-radius: 6px;
        margin-top: 4px;
        overflow: hidden;
    }
    .loyalty-card-progress-bar {
        width: var(--progress-var);
        height: 100%;
        background: linear-gradient(90deg, #c1f5a6 40%, #92e94e 100%);
        border-radius: 6px;
    }
    .loyalty-card-footer {
        font-size: 13px;
        color: var(--theme-gray2);
        padding: 2px 22px 18px 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .loyalty-card-footer a {
        color: var(--theme-gray2);
        font-weight: 500;
        font-size: 13px;
    }


</style>