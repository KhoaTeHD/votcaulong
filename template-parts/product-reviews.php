<?php
/**
 * Template part for displaying product reviews.
 * Biến $reviews, $total_reviews, $average_rating được truyền từ Product_Review_Handler::display_product_reviews()
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $reviews ) || ! isset( $product_id ) ) { // $product_id cũng nên được truyền vào
    echo '<p>' . esc_html__( 'Unable to load review data.', LANG_ZONE ) . '</p>';
	return;
}
?>
<div id="product-reviews-summary" class="mb-3">
    <h6><?php esc_html_e( 'Overview of the review', LANG_ZONE ); ?></h6>
    <?php if ( $total_reviews > 0 ) : ?>
        <?php
        // Tạo HTML cho các ngôi sao dựa trên điểm trung bình
        $stars_html = '<span class="average-rating-stars" aria-label="' . sprintf(esc_attr__('Average rating: %s out of 5 stars', LANG_ZONE), ($average_rating)) . '">';
        for ( $i = 1; $i <= 5; $i++ ) {
            if ( $average_rating >= $i ) {
                $stars_html .= '<i class="bi bi-star-fill text-warning"></i>';
            } elseif ( $average_rating >= ( $i - 0.5 ) ) {
                $stars_html .= '<i class="bi bi-star-half text-warning"></i>';
            } else {
                $stars_html .= '<i class="bi bi-star text-warning"></i>';
            }
        }
        $stars_html .= '</span>';
        ?>
        <p>
            <?php 
            printf(
                /* translators: %1$s: HTML string of star icons, %2$s: strong-tagged average rating number, %3$s: strong-tagged total reviews count */
                esc_html__( '%1$s (%2$s/5) based on %3$s ratings.', LANG_ZONE ),
                $stars_html,
                '<strong>' . esc_html( number_format( floatval( $average_rating ), 1 ) ) . '</strong>',
                '<strong>' . esc_html( $total_reviews ) . '</strong>'
            ); 
            ?>
        </p>

    <?php else : ?>
        <p><?php esc_html_e( 'There are no reviews yet.', LANG_ZONE ); ?></p>
    <?php endif; ?>
</div>

<div id="product-reviews-list" class="my-4">
	<h6 class="mb-3"><?php esc_html_e( 'Customer Reviews', LANG_ZONE ); ?></h6>

	<?php if ( ! empty( $reviews ) ) : ?>
		<ul class="list-unstyled reviews-list">
			<?php foreach ( $reviews as $review ) : ?>
				<li id="review-<?php echo esc_attr($review->review_id); ?>" class="review-item mb-3 p-3 border rounded bg-light">
					<div class="review-author fw-bold"><?php echo esc_html( $review->author_name ); ?></div>
					<div class="review-rating mb-1">
                        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                            <i class="bi <?php echo ( $i <= $review->rating ) ? 'bi-star-fill text-warning' : 'bi-star text-warning'; ?>"></i>
                        <?php endfor; ?>
                        <small class="text-muted ms-1">(<?php echo esc_html( $review->rating ); ?>/5)</small>
                    </div>
					<div class="review-date text-muted small mb-2">
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->date_created ) ) ); ?>
                    </div>
					<div class="review-content">
                        <?php echo wpautop( esc_html( $review->content ) ); ?>
                    </div>

                    <?php /* Phần hiển thị trả lời (nếu có) */ ?>
                    <?php if ( ! empty( $review->replies ) ) : ?>
                        <div class="review-replies mt-3 ps-4">
                            <?php foreach ( $review->replies as $reply ) : ?>
                                <div id="review-<?php echo esc_attr($reply->review_id); ?>" class="review-item review-reply mb-2 p-2 border-start border-primary">
                                    <div class="review-author fw-bold"><?php echo esc_html( $reply->author_name ); ?> <small class="text-muted">(<?php esc_html_e('Reply', LANG_ZONE); ?>)</small></div>
                                    <div class="review-date text-muted small mb-1">
                                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $reply->date_created ) ) ); ?>
                                    </div>
                                    <div class="review-content">
                                        <?php echo wpautop( esc_html( $reply->content ) ); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php /* Nút trả lời (có thể thêm sau) */ ?>
				</li>
			<?php endforeach; ?>
		</ul>
        <?php // TODO: Thêm phân trang nếu cần ?>
	<?php elseif ($total_reviews == 0) :  ?>
        <p><?php esc_html_e( 'There are no approved reviews for this product yet.', LANG_ZONE ); ?></p>
    <?php endif; ?>
</div>