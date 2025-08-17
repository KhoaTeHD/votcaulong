<?php
/**
 * Lấy danh sách các cửa hàng từ custom post type 'store_system'.
 *
 * @return array Một mảng các đối tượng cửa hàng, mỗi đối tượng chứa:
 * - 'id' (int): ID của post cửa hàng.
 * - 'name' (string): Tiêu đề của cửa hàng.
 * - 'url' (string): Đường dẫn tĩnh (permalink) đến trang của cửa hàng.
 * - 'address' (string): Địa chỉ của cửa hàng (từ meta field 'store_address').
 * - 'google_map_url' (string): URL Google Maps (từ meta field 'store_google_map_link').
 * - 'thumbnail' (string): URL của ảnh đại diện của cửa hàng,
 * hoặc URL mặc định 'No_Image_Available.jpg' nếu không có ảnh đại diện.
 */
function get_store_list(): array {
	$stores = [];
	$args = array(
		'post_type'      => 'store_system',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC'
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) :
		while ( $query->have_posts() ) : $query->the_post();
			$post_id = get_the_ID();
			$thumbnail_url = get_the_post_thumbnail_url($post_id, 'full') ?: (defined('IMG_URL') ? IMG_URL . 'No_Image_Available.jpg' : '');
			$stores[] = [
				'id'    =>  $post_id,
				'name'  => get_the_title($post_id),
				'url'   => get_the_permalink($post_id),
				'address' => get_post_meta( $post_id, 'store_address', true ),
				'google_map_url' => get_post_meta( $post_id, 'store_google_map_link', true ),
				'thumbnail' => $thumbnail_url
			];
		endwhile;
	endif;
	wp_reset_postdata();
	return $stores;
}
/**
 * Shortcode để hiển thị danh sách cửa hàng.
 *
 * @param array $atts Mảng các thuộc tính shortcode (hiện tại không được sử dụng).
 * @return string HTML hiển thị danh sách cửa hàng.
 */
function display_store_list_shortcode($atts) {
	$stores = get_store_list();
	ob_start();
	if (!empty($stores)) :
		?>
        <ul class="store-list">
			<?php
			foreach ($stores as $store) :
				?>
                <li><a href="<?php echo esc_url($store['url']); ?>">Địa chỉ <?php echo strtolower(esc_html($store['name'])); ?>: <?php echo esc_html($store['address']); ?></a></li>
			<?php
			endforeach;
			?>
        </ul>
	<?php
	else :
		echo '<p>'.__('No stores found.',LANG_ZONE).'</p>';
	endif;

	return ob_get_clean();
}
add_shortcode('display_store_list', 'display_store_list_shortcode');

/**
 * Hiển thị danh sách hình ảnh cửa hàng.
 * Sử dụng dữ liệu từ hàm get_store_list() để hiển thị hình ảnh và tiêu đề.
 */
function display_store_image_list() {
	$stores = get_store_list();

	if (!empty($stores)) :
		foreach ($stores as $store) :
			$title = esc_html($store['name']);
			$image_url = !empty($store['thumbnail']) ? esc_url($store['thumbnail']) : 'default-image.jpg'; // Sử dụng thumbnail từ get_store_list, có fallback

			?>
            <div class="photo-box-item">
                <a href="<?php echo esc_url($store['url']); ?>"> <img src="<?php echo $image_url; ?>" alt="<?php echo esc_attr($title); ?>">
                    <span class="title"><?php echo $title; ?></span>
                </a>
            </div>
		<?php
		endforeach;
	else :
		echo '<p>'.__('No stores found.',LANG_ZONE).'</p>';
	endif;
}

/**
 * Get detailed information for a single store by ID
 *
 * @param int $store_id The ID of the store to retrieve
 * @return array|null Store information array or null if not found, containing:
 * - 'id' (int): Store post ID
 * - 'name' (string): Store title
 * - 'url' (string): Store permalink
 * - 'address' (string): Store address from meta
 * - 'google_map_url' (string): Google Maps URL from meta
 * - 'thumbnail' (string): Store thumbnail URL or default image
 * - 'content' (string): Store post content
 */
function get_store_by_id(int $store_id): ?array {
    if (!$store_id) return null;
    $post = get_post($store_id);
    
    if (!$post || $post->post_type !== 'store_system') {
        return null;
    }

    $thumbnail_url = get_the_post_thumbnail_url($store_id, 'full') ?: 
        (defined('IMG_URL') ? IMG_URL . 'No_Image_Available.jpg' : '');

    return [
        'id' => $store_id,
        'name' => get_the_title($store_id),
        'url' => get_permalink($store_id),
        'address' => get_post_meta($store_id, 'store_address', true),
        'google_map_url' => get_post_meta($store_id, 'store_google_map_link', true),
        'thumbnail' => $thumbnail_url,
        'content' => apply_filters('the_content', $post->post_content)
    ];
}
