<?php
/**
 * Template part for displaying product review form (AJAX version).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $product_id ) || ! $product_id ) {
    if (is_singular()) {
        $current_post_id = get_the_ID();
        $product_id = $current_post_id;
    }
}

if ( ! $product_id ) {
	echo '<p>' . esc_html__( 'Product is not valid for review.', LANG_ZONE ) . '</p>';
	return;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;
?>

<div id="review_form_wrapper" class="my-4">
	<div id="review_form_container">
		<h6 id="reply-title" class="comment-reply-title mb-3"><?php esc_html_e( 'Leave your review', LANG_ZONE ); ?></h6>
        <div id="review-form-notice" class="alert" style="display:none;"></div>
		<form id="vcl-product-review-form" class="comment-form">
			
			<?php if ( ! is_user_logged_in() ) : ?>
				<p class="comment-notes"><span id="email-notes"><?php esc_html_e( 'Please Login to review this product!', LANG_ZONE ); ?></span></p>
			<?php else :?>
			

			<div class="mb-3">
				<label for="rating" class="form-label"><?php esc_html_e( 'Your review', LANG_ZONE ); ?> <span class="required">*</span></label>
				<div class="star-rating-input">
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
						<input type="radio" id="rating-<?php echo esc_attr( $i ); ?>" name="rating" value="<?php echo esc_attr( $i ); ?>" <?php if ($i==5) echo 'required'; ?> />
						<label for="rating-<?php echo esc_attr( $i ); ?>" title="<?php echo esc_attr( $i ); ?> <?php esc_attr_e('star', LANG_ZONE); ?>"><i class="bi bi-star-fill"></i></label>
					<?php endfor; ?>
				</div>
			</div>

			<div class="mb-3">
				<label for="comment" class="form-label"><?php esc_html_e( 'Review content', LANG_ZONE ); ?> <span class="required">*</span></label>
				<textarea id="comment" name="content" cols="45" rows="5" class="form-control" required></textarea>
			</div>
			
			<?php wp_nonce_field( 'product_review_nonce', 'product_review_nonce_field' ); ?>
			
			<input type="hidden" name="action" value="submit_product_review">
			<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
			<?php /* parent_id có thể được thêm nếu bạn làm chức năng trả lời đánh giá */ ?>
			<?php /* <input type="hidden" name="parent_id" value="0"> */ ?>


			<p class="form-submit mt-3">
				<button name="submit" type="submit" id="submit_review_button" class="btn btn-primary">
					<?php esc_html_e( 'Submit a review', LANG_ZONE ); ?>
				</button>
                <span class="spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true" style="display: none;"></span>
			</p>
            <?php endif; ?>
		</form>
	</div><!-- #review_form_container -->
</div><!-- #review_form_wrapper -->

<style>
.star-rating-input { display: inline-block; direction: rtl; }
.star-rating-input input[type="radio"] { display: none; }
.star-rating-input label { font-size: 1.5em; color: #ddd; cursor: pointer; padding: 0 0.1em; }
.star-rating-input input[type="radio"]:checked ~ label { color: #ffc107; }
.star-rating-input label:hover,
.star-rating-input label:hover ~ label { color: #ffc107 !important; }
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const REVIEW_TIMEOUT_MINUTES = 15; 
    const STORAGE_KEY = 'vcl_product_review_timeout_<?php echo esc_js($product_id); ?>';
    const reviewform = $('#vcl-product-review-form');
    const submitButton = $('#submit_review_button');
    const timeoutNotice = $('#review-timeout-notice');
    const countdownSpan = $('#timeout-countdown');
    
    function checkReviewTimeout() {
        const timeoutData = localStorage.getItem(STORAGE_KEY);
        
        if (timeoutData) {
            const timeoutInfo = JSON.parse(timeoutData);
            const now = new Date().getTime();
            const timeoutEnd = timeoutInfo.timestamp + (REVIEW_TIMEOUT_MINUTES * 60 * 1000);
            
            if (now < timeoutEnd) {
                // Còn trong thời gian timeout
                const minutesLeft = Math.ceil((timeoutEnd - now) / (60 * 1000));
                countdownSpan.text(minutesLeft);
                timeoutNotice.show();
                submitButton.prop('disabled', true);
                reviewform.addClass('opacity-50');
                setTimeout(checkReviewTimeout, 60000);
                return true;
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
        
        timeoutNotice.hide();
        submitButton.prop('disabled', false);
        reviewform.removeClass('opacity-50');
        return false;
    }
    
    // Kiểm tra timeout khi trang tải
    checkReviewTimeout();
    reviewform.on('submit', function(e) {
        e.preventDefault();
        
        if (checkReviewTimeout()) {
            return; // Nếu còn trong thời gian timeout, không gửi đánh giá
        }
        var formData = reviewform.serialize();
        var noticeDiv = $('#review-form-notice');
        var submitButton = $('#submit_review_button');
        var spinner = reviewform.find('.spinner-border');

        noticeDiv.hide().removeClass('alert-success alert-danger');
        submitButton.prop('disabled', true);
        spinner.show();

        $.ajax({
            type: 'POST',
            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    noticeDiv.addClass('alert-success').html(response.data.message).show();
                    reviewform.trigger('reset'); 
                    const timeoutInfo = {
                        timestamp: new Date().getTime(),
                        product_id: '<?php echo esc_js($product_id); ?>'
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(timeoutInfo));
                    checkReviewTimeout();
                } else {
                    noticeDiv.addClass('alert-danger').html(response.data.message).show();
                    submitButton.prop('disabled', false);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                noticeDiv.addClass('alert-danger').html('<?php esc_html_e('An error occurred. Please try again.', LANG_ZONE); ?> (' + textStatus + ')').show();
                console.error("Review form AJAX error:", textStatus, errorThrown, jqXHR.responseText);
                submitButton.prop('disabled', false);
            },
            complete: function() {
                submitButton.prop('disabled', false);
                spinner.hide();
            }
        });
    });
});
</script>