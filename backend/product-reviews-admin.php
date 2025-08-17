<?php
/**
 * Admin page for managing product reviews.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Lớp WP_List_Table sẽ rất hữu ích ở đây, nhưng để đơn giản, ta làm một bảng HTML cơ bản trước.
global $wpdb;
$reviews_table = $wpdb->prefix . 'product_reviews';

// Xử lý actions (approve, unapprove, delete, spam)
$action = isset($_GET['action']) ? sanitize_key($_GET['action']) : null;
$review_id = isset($_GET['review_id']) ? absint($_GET['review_id']) : null;

if ($action && $review_id && check_admin_referer('review_manage_action_' . $review_id)) {
    switch ($action) {
        case 'approve':
            $wpdb->update($reviews_table, ['status' => 'approved'], ['review_id' => $review_id], ['%s'], ['%d']);
            echo '<div class="updated"><p>' . esc_html__('Review approved.', LANG_ZONE) . '</p></div>';
            break;
        case 'unapprove':
            $wpdb->update($reviews_table, ['status' => 'hold'], ['review_id' => $review_id], ['%s'], ['%d']);
            echo '<div class="updated"><p>' . esc_html__('The review has been moved to pending approval.', LANG_ZONE) . '</p></div>';
            break;
        case 'delete':
            $wpdb->delete($reviews_table, ['review_id' => $review_id], ['%d']);
            echo '<div class="updated"><p>' . esc_html__('The review has been deleted.', LANG_ZONE) . '</p></div>';
            break;
        // Thêm các case khác như 'spam' nếu cần
    }
}


// Lấy danh sách đánh giá (có thể phân trang sau)
$reviews = $wpdb->get_results("SELECT * FROM {$reviews_table} ORDER BY date_created_gmt DESC LIMIT 100"); // Giới hạn 100 để tránh quá tải

?>
<div class="wrap">
    <h1><?php esc_html_e('Product Review Management', LANG_ZONE); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Product ID', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Customer', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Email', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Rating', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Content', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Date created', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Status', LANG_ZONE); ?></th>
                <th scope="col"><?php esc_html_e('Action', LANG_ZONE); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($reviews)) : ?>
                <?php foreach ($reviews as $review) : ?>
                    <tr>
                        <td><?php echo esc_html($review->product_id); ?></td>
                        <td><?php echo esc_html($review->author_name); ?></td>
                        <td><?php echo esc_html($review->author_email); ?></td>
                        <td><?php echo esc_html($review->rating); ?>/5</td>
                        <td><?php echo esc_html(wp_trim_words($review->content, 20, '...')); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($review->date_created))); ?></td>
                        <td><?php echo esc_html($review->status); ?></td>
                        <td>
                            <?php
                            $approve_link = wp_nonce_url(admin_url('admin.php?page=product-reviews&action=approve&review_id=' . $review->review_id), 'review_manage_action_' . $review->review_id);
                            $unapprove_link = wp_nonce_url(admin_url('admin.php?page=product-reviews&action=unapprove&review_id=' . $review->review_id), 'review_manage_action_' . $review->review_id);
                            $delete_link = wp_nonce_url(admin_url('admin.php?page=product-reviews&action=delete&review_id=' . $review->review_id), 'review_manage_action_' . $review->review_id);
                            ?>
                            <?php if ($review->status !== 'approved') : ?>
                                <a href="<?php echo esc_url($approve_link); ?>"><?php esc_html_e('Approve', LANG_ZONE); ?></a> |
                            <?php endif; ?>
                            <?php if ($review->status !== 'hold') : ?>
                                <a href="<?php echo esc_url($unapprove_link); ?>"><?php esc_html_e('Unapprove', LANG_ZONE); ?></a> |
                            <?php endif; ?>
                            <a href="<?php echo esc_url($delete_link); ?>" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this review?', LANG_ZONE); ?>');" style="color:red;"><?php esc_html_e('Xóa', LANG_ZONE); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="8"><?php esc_html_e('There are no reviews yet.', LANG_ZONE); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>