<?php
/**
 * Xử lý đánh giá sản phẩm
 */

class Product_Review_Handler {
    private $wpdb;
    private $reviews_table;

    // Optional: Add a cache for review lists if needed outside the Product object context
    // private $review_list_cache = [];

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->reviews_table = $wpdb->prefix . 'product_reviews';

        if ( $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->reviews_table ) ) != $this->reviews_table ) {
             error_log( "Product reviews table '{$this->reviews_table}' does not exist. Review functionality may be limited." );
        }

        add_action('wp_ajax_submit_product_review', array($this, 'ajax_submit_review'));
        add_action('wp_ajax_nopriv_submit_product_review', array($this, 'ajax_submit_review'));

        add_action('vcl_after_product_description', array($this, 'display_product_reviews'));

        add_shortcode('product_review_form', array($this, 'review_form_shortcode'));

        // Add a hook for admin review management page if you have one
        // add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     *
     * @param string $product_id ID của sản phẩm.
     * @param int $limit Số lượng đánh giá chính.
     * @param int $offset Vị trí bắt đầu.
     * @param string $status Trạng thái đánh giá ('approved', 'hold', etc.).
     * @return array Mảng các đối tượng đánh giá, mỗi đối tượng có thuộc tính 'replies'.
     */
    public function get_product_reviews(string $product_id, int $limit = 10, int $offset = 0, string $status = 'approved'): array {
         if ( empty( $product_id ) || $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->reviews_table ) ) != $this->reviews_table ) {
              return [];
         }

        $allowed_statuses = ['approved', 'hold', 'spam', 'trash'];
        $status = in_array( strtolower( $status ), $allowed_statuses ) ? strtolower( $status ) : 'approved';

        $main_reviews_query = $this->wpdb->prepare(
            "SELECT * FROM {$this->reviews_table}
             WHERE product_id = %s AND status = %s AND parent_id IS NULL
             ORDER BY date_created_gmt DESC
             LIMIT %d OFFSET %d",
            $product_id, $status, $limit, $offset
        );

        $main_reviews = $this->wpdb->get_results($main_reviews_query);

        $main_review_ids = [];
        if (!empty($main_reviews)) {
            foreach ($main_reviews as $review) {
                if (isset($review->review_id)) {
                    $main_review_ids[] = $review->review_id;
                    $review->replies = [];
                }
            }
        }

        if (!empty($main_review_ids)) {
            $placeholders = implode(',', array_fill(0, count($main_review_ids), '%d')); // Prepare placeholders for IN clause
            $replies_query = $this->wpdb->prepare(
                "SELECT * FROM {$this->reviews_table}
                 WHERE parent_id IN ({$placeholders}) AND status = 'approved'
                 ORDER BY date_created_gmt ASC",
                $main_review_ids // Pass the array of IDs to prepare
            );

            $all_replies = $this->wpdb->get_results($replies_query);

            if (!empty($all_replies)) {
                $replies_map = [];
                foreach ($all_replies as $reply) {
                    if (isset($reply->parent_id)) {
                        $replies_map[$reply->parent_id][] = $reply;
                    }
                }

                foreach ($main_reviews as $key => $review) {
                    if (isset($review->review_id) && isset($replies_map[$review->review_id])) {
                        $main_reviews[$key]->replies = $replies_map[$review->review_id];
                    }
                }
            }
        }
        return $main_reviews;
    }

    /**
     *
     * @param int $review_id ID của đánh giá cha.
     * @return array Mảng các đối tượng phản hồi.
     */
    public function get_review_replies(int $review_id): array {
         if ( empty( $review_id ) || $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->reviews_table ) ) != $this->reviews_table ) {
              return [];
         }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->reviews_table}
             WHERE parent_id = %d AND status = 'approved'
             ORDER BY date_created_gmt ASC",
            $review_id
        );

        $replies = $this->wpdb->get_results($query);
        return is_array($replies) ? $replies : [];
    }

    /**
     *
     * @param string $product_id ID của sản phẩm.
     * @param string $status Trạng thái đánh giá.
     * @return int Tổng số đánh giá.
     */
    public function count_product_reviews(string $product_id, string $status = 'approved'): int {
         if ( empty( $product_id ) || $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->reviews_table ) ) != $this->reviews_table ) {
              return 0;
         }

        // Sanitize status
        $allowed_statuses = ['approved', 'hold', 'spam', 'trash'];
        $status = in_array( strtolower( $status ), $allowed_statuses ) ? strtolower( $status ) : 'approved';

        $query = $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->reviews_table}
             WHERE product_id = %s AND status = %s AND parent_id IS NULL",
            $product_id, $status
        );

        $count = $this->wpdb->get_var($query);
        return absint($count);
    }

    /**
     *
     * @param string $product_id ID của sản phẩm.
     * @return float Điểm đánh giá trung bình.
     */
    public function get_average_rating(string $product_id): float {
         if ( empty( $product_id ) || $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->reviews_table ) ) != $this->reviews_table ) {
              return 0.0;
         }

        $query = $this->wpdb->prepare(
            "SELECT AVG(rating) FROM {$this->reviews_table}
             WHERE product_id = %s AND status = 'approved' AND parent_id IS NULL",
            $product_id
        );

        $avg = $this->wpdb->get_var($query);
        return $avg ? round((float)$avg, 1) : 0.0; // Ensure float return
    }

    /**
     *
     * @param array $data Dữ liệu đánh giá.
     * @return int|WP_Error Review ID on success, WP_Error on failure.
     */
    public function add_review(array $data) {
         if ( $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES LIKE %s", $this->reviews_table ) ) != $this->reviews_table ) {
              return new WP_Error('db_table_missing', __('Product reviews table not found.',LANG_ZONE)); // Use translatable error
         }

        $current_time = current_time('mysql');
        $current_time_gmt = current_time('mysql', true);

        $defaults = array(
            'product_id' => '',
            'user_id' => get_current_user_id(),
            'author_name' => '',
            'author_email' => '',
            'rating' => 0,
            'content' => '',
            'status' => 'hold',
            'parent_id' => null,
            'date_created' => $current_time,
            'date_created_gmt' => $current_time_gmt
        );

        // Use wp_parse_args to merge defaults and input, then sanitize
        $data = wp_parse_args($data, $defaults);

        // Sanitize input data immediately after parsing
        $data['product_id'] = sanitize_text_field($data['product_id']); // Assuming product_id can be non-numeric (SKU etc.)
        $data['user_id'] = absint($data['user_id']);
        $data['rating'] = absint($data['rating']);
        $data['content'] = sanitize_textarea_field($data['content']);
        $data['parent_id'] = !empty($data['parent_id']) ? absint($data['parent_id']) : null; // Ensure parent_id is int or null
        $data['author_name'] = sanitize_text_field($data['author_name']);
        $data['author_email'] = sanitize_email($data['author_email']);
         $data['status'] = sanitize_text_field($data['status']); // Sanitize status input


        // --- Validation ---
        if (empty($data['product_id'])) {
             return new WP_Error('invalid_product', __('Product ID is invalid.',LANG_ZONE)); // Use translatable error
        }

        if (empty($data['content'])) {
             return new WP_Error('empty_content', __('Review content cannot be empty.',LANG_ZONE)); // Use translatable error
        }

        // If not a reply (parent_id is null), rating is required and must be between 1 and 5
        if (is_null($data['parent_id'])) {
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                 return new WP_Error('invalid_rating', __('Please select a valid rating (1-5 stars).',LANG_ZONE)); // Use translatable error
            }
            // For main reviews, ensure rating is not 0 if it came from default
             $data['rating'] = max(1, min(5, $data['rating'])); // Clamp rating to 1-5
        } else {
             // For replies, rating is not applicable, set to 0 or null in DB if possible
             // Based on table structure, setting to 0
             $data['rating'] = 0;
        }


        // If not logged in, name and email are required
        if (!$data['user_id']) {
            if (empty($data['author_name'])) {
                 return new WP_Error('empty_name', __('Please enter your name.',LANG_ZONE)); // Use translatable error
            }

            if (empty($data['author_email']) || !is_email($data['author_email'])) {
                 return new WP_Error('invalid_email', __('Invalid email address.',LANG_ZONE)); // Use translatable error
            }
        } else {
            // If logged in, get info from user, overwrite input if necessary
            $user = get_userdata($data['user_id']);
            if ($user) {
                $data['author_name'] = $user->display_name;
                $data['author_email'] = $user->user_email;
            } else {
                // User ID invalid or not found, maybe treat as guest? Or return error?
                // For now, proceed with potentially empty author_name/email if user not found
                 $data['user_id'] = 0; // Set to 0 if user not found for safety
            }
        }

        // Determine status - default 'hold', but can be 'approved' if user has capability or settings allow
        // For minimal impact, keeping default 'hold' from the original code.
        // You might add logic here: if (user_can( $data['user_id'], 'moderate_comments' )) $data['status'] = 'approved';

        // Prepare data for insertion (ensure all columns are present even if null/0)
        $insert_data = array(
             'product_id' => $data['product_id'],
             'user_id' => $data['user_id'],
             'author_name' => $data['author_name'],
             'author_email' => $data['author_email'],
             'rating' => $data['rating'],
             'content' => $data['content'],
             'status' => $data['status'],
             'parent_id' => $data['parent_id'],
             'date_created' => $data['date_created'],
             'date_created_gmt' => $data['date_created_gmt']
        );

        // Prepare format string for wpdb::insert
        $insert_format = array('%s', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s');


        // Thêm vào database
        // wpdb::insert returns false on error, 0 if no rows inserted, or number of rows inserted (usually 1)
        $result = $this->wpdb->insert(
            $this->reviews_table,
            $insert_data,
            $insert_format
        );

        if ($result === false) {
             // $this->wpdb->last_error might have details
             error_log("WPDB Insert Error: " . $this->wpdb->last_error);
             return new WP_Error('db_insert_error', __('Could not save review due to a database error.',LANG_ZONE)); // Use translatable error
        }
         if ($result === 0) {
              // Insert was technically not an error, but no row was added.
              // This might indicate an issue with data or database constraints.
             return new WP_Error('db_insert_failed', __('Review could not be saved.',LANG_ZONE)); // Use translatable error
         }


        $review_id = $this->wpdb->insert_id;

        // Gửi email thông báo cho admin
        // Pass more relevant data or fetch product inside
        $this->send_admin_notification($review_id, $data['product_id'], $data);

        return $review_id;
    }

    /**
     * Gửi email thông báo cho admin khi có đánh giá mới
     * Added product_id as parameter.
     *
     * @param int $review_id ID của đánh giá vừa thêm.
     * @param string $product_id ID sản phẩm liên quan.
     * @param array $review_data Dữ liệu đánh giá.
     */
    private function send_admin_notification(int $review_id, string $product_id, array $review_data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        // --- OPTIMIZATION 4: Get Product Title Reliably ---
        // Use the ProductManager/helper to get the product object for title
        $product = get_product($product_id); // Uses our cached get_product
        $product_title = $product ? $product->getTitle() : __('Product',LANG_ZONE).' #' . $product_id;
        $product_url = $product ? $product->getURL() : '#'; // Get product URL if product object available
        // --- END OPTIMIZATION 4 ---


        $subject = sprintf(__('[%s] New product review for approval',LANG_ZONE), $site_name); // Use translatable string

        $message = sprintf(__('There are new reviews for the product: %s',LANG_ZONE), $product_title) . "\n\n"; // Use translatable string
        $message .= sprintf(__('Sender: %s',LANG_ZONE), $review_data['author_name']) . "\n"; // Use translatable string
        $message .= sprintf(__('Email: %s',LANG_ZONE), $review_data['author_email']) . "\n"; // Use translatable string
        $message .= sprintf(__('Rating: %d sao',LANG_ZONE), $review_data['rating']) . "\n"; // Use translatable string
        $message .= sprintf(__('Content: %s',LANG_ZONE), $review_data['content']) . "\n\n"; // Use translatable string

        // Link to the product page for admin review if possible
        if ($product_url && $product_url !== '#') {
            $message .= sprintf(__('View product: %s',LANG_ZONE), $product_url) . "\n"; // Use translatable string
        }

        // Link directly to admin review page if one exists (replace 'product-reviews' with actual page slug)
        // This assumes you have an admin page to manage these reviews.
        $admin_review_url = admin_url('admin.php?page=product-reviews&action=edit&review_id=' . $review_id); // Adjust URL
        $message .= sprintf(__('Visit the admin page to browse this review: %s',LANG_ZONE), $admin_review_url); // Use translatable string

        // Send the email
        // wp_mail($admin_email, $subject, $message);
        // For debugging, uncomment the line below instead of wp_mail
         // error_log("Admin notification email sent: \nSubject: {$subject}\nBody: {$message}");
    }

    /**
     * Xử lý AJAX submit đánh giá
     * Thêm kiểm tra cho user_id khi logged in.
     *
     * @return void Sends JSON response and exits.
     */
    public function ajax_submit_review(): void {
        // --- Security: Nonce verification ---
        if (!isset($_POST['product_review_nonce_field']) || !wp_verify_nonce($_POST['product_review_nonce_field'], 'product_review_nonce')) {
             wp_send_json_error(array('message' => __('Security check failed. Please refresh the page.',LANG_ZONE))); // Use translatable error
             wp_die(); // Always die after AJAX response
        }

        // --- Sanitize Input ---
        $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? absint($_POST['parent_id']) : null; // Handle empty string for null parent
        $author_name = isset($_POST['author_name']) ? sanitize_text_field($_POST['author_name']) : '';
        $author_email = isset($_POST['author_email']) ? sanitize_email($_POST['author_email']) : '';
         // Status should likely be forced to 'hold' from AJAX to prevent spam approval
         $status = 'hold';


        // Get user ID for logged-in users
        $user_id = get_current_user_id();

        $review_data = array(
            'product_id' => $product_id,
            'user_id' => $user_id, // Use retrieved user ID
            'rating' => $rating,
            'content' => $content,
            'parent_id' => $parent_id,
            'author_name' => $author_name,
            'author_email' => $author_email,
            'status' => $status, // Forced status
        );

        $result = $this->add_review($review_data); // add_review now handles validation and gets user info if logged in

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        } else {
             // Success message depends on status - if 'hold', it needs approval
             $success_message = ($status === 'approved') ?
                                 __('Thank you for your review!',LANG_ZONE) :
                                 __('Thank you for your review. It will be visible after approval.',LANG_ZONE); // Use translatable string

            wp_send_json_success(array(
                'message' => $success_message,
                'review_id' => $result
            ));
        }
         wp_die(); // Always die after AJAX response
    }

    /**
     * Hiển thị đánh giá trong trang sản phẩm.
     * Uses the Product object's methods for reviews, count, and average rating.
     *
     * @param Product|false $product The Product object.
     */
    public function display_product_reviews($product): void {
        // Check if a valid Product object was passed
        if ( !$product || !($product instanceof Product) || !$product->getId() ) {
             echo '<p>' . esc_html__('Cannot load reviews: Invalid product object.',LANG_ZONE) . '</p>'; // Use translatable string
             return;
        }

        $product_id = $product->getId(); // Get ID from the Product object

        $reviews = $product->getReviews(); // This fetches the reviews list (and replies) via lazy loading/caching
        $total_reviews = $product->getTotalReviews(); // Uses lazy-loaded aggregate data
        $average_rating = $product->getAverageRating(); // Uses lazy-loaded aggregate data


        $template_path = get_template_directory() . '/template-parts/product-reviews.php'; // Assumes template is in theme root /template-parts

        // Use locate_template for child theme compatibility
        $located_template = locate_template('template-parts/product-reviews.php');

        if ($located_template) {
            include($located_template);
        } else {
            echo '<p>' . sprintf(
                esc_html__('Error: Template file %s not found.',LANG_ZONE), // Use translatable string
                '<code>template-parts/product-reviews.php</code>'
            ) . '</p>';
        }
    }

    /**
     * Shortcode hiển thị form đánh giá
     * Simplified product_id retrieval logic.
     *
     * @param array $atts Shortcode attributes.
     * @return string Output HTML.
     */
    public function review_form_shortcode($atts): string {
        $atts = shortcode_atts(array(
            'product_id' => 0,
        ), $atts, 'product_review_form');

        $product_id = $atts['product_id'];
         if (empty($product_id) && isset($GLOBALS['product']) && $GLOBALS['product'] instanceof Product && $GLOBALS['product']->getId() ) {
             $product_id = absint($GLOBALS['product']->getId());
         }
        if (!$product_id) {
             return '<p>' . esc_html__('Product ID not specified for the review form.',LANG_ZONE) . '</p>'; // Use translatable string
        }

        $current_product_id_for_review_form = $product_id; // Variable name to pass

        ob_start();
        $located_template = locate_template('template-parts/product-review-form.php');

        if ($located_template) {
             include($located_template); // $current_product_id_for_review_form is available here
        } else {
             echo '<p>' . sprintf(
                esc_html__('Error: Template file %s not found.',LANG_ZONE), // Use translatable string
                '<code>template-parts/product-review-form.php</code>'
            ) . '</p>';
        }

        return ob_get_clean();
    }

    
}

// Khởi tạo lớp xử lý đánh giá
$product_review_handler = new Product_Review_Handler();

// Thêm trang quản lý đánh giá trong admin
function add_product_reviews_admin_page() {
    add_menu_page(
        __('Product Reviews', LANG_ZONE),
        __('Product Reviews', LANG_ZONE),
        'edit_posts',
        'product-reviews',
        'render_product_reviews_admin_page',
        'dashicons-star-filled',
        30
    );
}
add_action('admin_menu', 'add_product_reviews_admin_page');

// Render trang quản lý đánh giá
function render_product_reviews_admin_page() {
    include(get_template_directory() . '/backend/product-reviews-admin.php');
}

/**
 * Hiển thị đánh giá bằng sao và văn bản đi kèm.
 * Ví dụ: 3.5 sao (3.5/5) dựa trên 2 đánh giá.
 *
 * @param float $rating Điểm đánh giá (ví dụ: 3.5).
 * @param int   $review_count Số lượng đánh giá (ví dụ: 2).
 * @param bool  $echo True để echo, false để return HTML. Mặc định là true.
 * @return string|void HTML của đánh giá nếu $echo là false, ngược lại không trả về gì.
 */
function vcl_display_star_rating_with_text( $rating, $review_count, $echo = true ) {
    $rating = floatval( $rating );
    $review_count = intval( $review_count );
    $output = '';

    $stars_html = '<span class="average-rating-stars" aria-label="' . sprintf(esc_attr__('Rating: %s out of 5 stars', LANG_ZONE), number_format($rating, 1)) . '">';
    for ( $i = 1; $i <= 5; $i++ ) {
        if ( $rating >= $i ) {
            $stars_html .= '<i class="bi bi-star-fill text-warning"></i>';
        } elseif ( $rating >= ( $i - 0.5 ) ) {
            $stars_html .= '<i class="bi bi-star-half text-warning"></i>';
        } else {
            $stars_html .= '<i class="bi bi-star text-warning"></i>';
        }
    }
    $stars_html .= '</span>';

    // Phần văn bản
    // Sử dụng _n() để xử lý số nhiều cho "đánh giá" nếu theme của bạn hỗ trợ (cần file .mo/.po)
    // Trong trường hợp này, theo hình ảnh, "2 đánh giá" vẫn dùng từ "đánh giá"
    $review_text_singular = __( 'review', LANG_ZONE );
    $review_text_plural = __( 'reviews', LANG_ZONE );
    $review_label = _n( $review_text_singular, $review_text_plural, $review_count, LANG_ZONE );

    $text_html = sprintf(
        '<span class="rating-text ms-1">(<strong>%1$s</strong>/5) %2$s <strong>%3$s</strong> %4$s.</span>',
        esc_html( number_format( $rating, 1 ) ),
        esc_html__( 'has', LANG_ZONE ),
        esc_html( number_format_i18n( $review_count ) ), // Sử dụng number_format_i18n cho số lượng
        esc_html( $review_label )
    );

    $output = $stars_html . ' ' . ($review_count?$text_html:'');

    if ( $echo ) {
        echo $output;
    } else {
        return $output;
    }
}