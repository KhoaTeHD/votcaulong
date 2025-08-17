<?php
// ------------Store_system----------------
function create_store_system_post_type() {
    $args = [
        'labels' => [
            'name' => __('Store', LANG_ZONE),
            'singular_name' => __('Store', LANG_ZONE),
            'menu_name' => __('Store', LANG_ZONE),
            'name_admin_bar' => __('Store', LANG_ZONE),
            'add_new' => __('Add New (disabled)', LANG_ZONE),
            'add_new_item' => __('Add New Store (disabled)', LANG_ZONE),
            'edit_item' => __('Edit Store (disabled)', LANG_ZONE),
            'new_item' => __('New Store (disabled)', LANG_ZONE),
            'view_item' => __('View Store', LANG_ZONE),
            'search_items' => __('Search Store', LANG_ZONE),
            'not_found' => __('No Store found', LANG_ZONE),
            'not_found_in_trash' => __('No Store found in Trash', LANG_ZONE),
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-store',
        'show_in_menu' => true,
        'show_in_rest' => true, // Cho block editor hoặc REST API nếu cần
        'supports' => ['title', 'thumbnail'], // Loại bỏ editor nếu không dùng
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'capabilities' => [
            'create_posts' => 'do_not_allow', // ❌ Không cho tạo thủ công
        ],
    ];
    register_post_type('store_system', $args);
}
add_action('init', 'create_store_system_post_type');

function add_store_system_meta_boxes() {
    add_meta_box(
        'store_system_info',
        __('Store information', LANG_ZONE),
        'render_store_system_meta_box',
        'store_system',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'add_store_system_meta_boxes' );

// Render Meta Box
function render_store_system_meta_box( $post ) {
    // Lấy giá trị đã lưu (nếu có)
    $store_address = get_post_meta( $post->ID, 'store_address', true );
    $store_phone = get_post_meta( $post->ID, 'store_phone', true );
    $store_google_map = get_post_meta( $post->ID, 'store_google_map', true );
    $store_google_map_link = get_post_meta( $post->ID, 'store_google_map_link', true ); // Thêm link

    ?>

    <p>
        <label for="store_address"><?php _e('Store address', LANG_ZONE)  ?>:</label><br>
        <input type="text" id="store_address" name="store_address" value="<?php echo esc_attr( $store_address ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="store_phone"><?php _e('Phone number', LANG_ZONE)  ?>:</label><br>
        <input type="text" id="store_phone" name="store_phone" value="<?php echo esc_attr( $store_phone ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="store_google_map"><?php _e('Google Map (Embed URL)', LANG_ZONE)  ?>:</label><br>
        <input type="text" id="store_google_map" name="store_google_map" value="<?php echo esc_attr( $store_google_map ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="store_google_map_link"><?php _e('Direct Google Maps link', LANG_ZONE)  ?>:</label><br>
        <input type="text" id="store_google_map_link" name="store_google_map_link" value="<?php echo esc_attr( $store_google_map_link ); ?>" style="width:100%;" />
    </p>
    <?php
}


// Lưu dữ liệu Meta Box
function save_store_system_meta( $post_id ) {
    if ( isset( $_POST['store_address'] ) ) {
        update_post_meta( $post_id, 'store_address', sanitize_text_field( $_POST['store_address'] ) );
    }
    if ( isset( $_POST['store_phone'] ) ) {
        update_post_meta( $post_id, 'store_phone', sanitize_text_field( $_POST['store_phone'] ) );
    }
    if ( isset( $_POST['store_google_map'] ) ) {
        update_post_meta( $post_id, 'store_google_map', esc_url_raw( $_POST['store_google_map'] ) );
    }
    if ( isset( $_POST['store_google_map_link'] ) ) { // Lưu link Google Maps
        update_post_meta( $post_id, 'store_google_map_link', esc_url_raw( $_POST['store_google_map_link'] ) );
    }
}
add_action( 'save_post', 'save_store_system_meta' );
