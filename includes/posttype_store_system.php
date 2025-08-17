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

function add_store_system_columns($columns) {
    $new_columns = [];
    $new_columns['cb'] = $columns['cb']; // Checkbox column
    $new_columns['thumbnail'] = __('Thumbnail', LANG_ZONE);
    $new_columns['title'] = $columns['title']; // Default Title column
    $new_columns['store_address'] = __('Address', LANG_ZONE);
    $new_columns['store_phone'] = __('Phone', LANG_ZONE);
    $new_columns['store_lat_long'] = __('Lat & Long', LANG_ZONE);
    $new_columns['date'] = $columns['date']; // Default Date column

    // Add any other default columns that might exist and you want to keep
    // foreach ($columns as $key => $value) {
    //     if (!isset($new_columns[$key])) {
    //         $new_columns[$key] = $value;
    //     }
    // }

    return $new_columns;
}
add_filter('manage_store_system_posts_columns', 'add_store_system_columns');

function custom_store_system_column_content($column, $post_id) {
    switch ($column) {
        case 'store_address':
            echo esc_html(get_post_meta($post_id, 'store_address', true));
            break;
        case 'store_phone':
            echo esc_html(get_post_meta($post_id, 'store_phone', true));
            break;
        case 'store_lat_long':
            echo esc_html(get_post_meta($post_id, 'store_lat_long', true));
            break;
        case 'thumbnail' :
		        echo get_the_post_thumbnail($post_id, [40, 40]);
	        break;
    }
}
add_action('manage_store_system_posts_custom_column', 'custom_store_system_column_content', 10, 2);

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
    $store_lat_long = get_post_meta( $post->ID, 'store_lat_long', true );

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
        <label for="store_google_map"><?php _e('Google Map (Embed code)', LANG_ZONE)  ?>:</label><br>
        <textarea cols="8" rows="5" style="width: 100%;" id="store_google_map" name="store_google_map" ><?php echo ( $store_google_map ); ?></textarea>
    </p>
    <p>
        <label for="store_google_map_link"><?php _e('Direct Google Maps link', LANG_ZONE)  ?>:</label><br>
        <input type="text" id="store_google_map_link" name="store_google_map_link" value="<?php echo esc_attr( $store_google_map_link ); ?>" style="width:100%;" />
    </p>
    <p>
        <label for="store_lat_long"><?php _e('Map lat & long', LANG_ZONE)  ?>:</label><br>
        <input type="text" id="store_lat_long" name="store_lat_long" placeholder="10.8098845383636, 106.69528918650688" value="<?php echo esc_attr( $store_lat_long ); ?>" style="width:100%;" />
        <img src="<?php echo IMG_URL; ?>copy-lat-long.jpg" width="200px;">
    </p>
    <?php
}


function quick_edit_store_system_custom_box($column_name, $post_type) {
    if ( $post_type !== 'store_system') return;

    // Add fields for 'store_address' and 'store_phone' in a new fieldset
    if ($column_name == 'store_phone') { // We hook into one of the existing columns to add our fields
        ?>
        <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Address', LANG_ZONE); ?></span>
                    <span class="input-text-wrap"><input type="text" name="store_address" value=""></span>
                </label>
                <label>
                    <span class="title"><?php _e('Phone', LANG_ZONE); ?></span>
                    <span class="input-text-wrap"><input type="text" name="store_phone" value=""></span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    if ($column_name == 'store_lat_long') {
        ?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php _e('Lat & Long', LANG_ZONE); ?></span>
                    <span class="input-text-wrap"><input type="text" name="store_lat_long" value=""></span>
                </label>
            </div>
        </fieldset>
        <?php
    }
}
add_action('quick_edit_custom_box', 'quick_edit_store_system_custom_box', 10, 2);

// Make columns sortable (optional)
function make_store_system_columns_sortable($columns) {

    $columns['store_lat_long'] = 'store_lat_long';
    return $columns;
}
add_filter('manage_edit-store_system_sortable_columns', 'make_store_system_columns_sortable');

// Add JavaScript for Quick Edit to populate fields
function store_system_quick_edit_javascript() {
    global $pagenow, $post_type;

    if ($pagenow == 'edit.php' && $post_type == 'store_system') {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                // Extend inlineEditL10n.vars.fields to include our custom fields
                if (typeof inlineEditL10n !== 'undefined' && inlineEditL10n.vars && inlineEditL10n.vars.fields) {
                    inlineEditL10n.vars.fields.store_address = 1;
                    inlineEditL10n.vars.fields.store_phone = 1;
                    inlineEditL10n.vars.fields.store_lat_long = 1;
                }

                $(document).on('click', '.editinline', function() {
                    var post_id = $(this).parents('tr').attr('id').replace('post-', '');
                    var $post_row = $('#post-' + post_id);
                    var $quick_edit_row = $('#edit-' + post_id);

                    // Get the custom field values from the list table
                    var lat_long = $post_row.find('.column-store_lat_long').text();
                    var address = $post_row.find('.column-store_address').text();
                    var phone = $post_row.find('.column-store_phone').text();

                    // Populate the Quick Edit fields
                    $quick_edit_row.find('input[name="store_lat_long"]').val(lat_long);
                    $quick_edit_row.find('input[name="store_address"]').val(address);
                    $quick_edit_row.find('input[name="store_phone"]').val(phone);
                });
            });
        </script>
        <?php
    }
}
add_action('admin_footer', 'store_system_quick_edit_javascript');

// Lưu dữ liệu Meta Box
function save_store_system_meta( $post_id ) {
    // Check if it's an autosave or not a store_system post type
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! isset( $_POST['post_type'] ) || 'store_system' != $_POST['post_type'] ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Check if it's a Quick Edit save
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['_inline_edit'] ) && wp_verify_nonce( $_REQUEST['_inline_edit'], 'inline-save' ) ) {
        if ( isset( $_POST['store_address'] ) ) {
            update_post_meta( $post_id, 'store_address', sanitize_text_field( $_POST['store_address'] ) );
        }
        if ( isset( $_POST['store_phone'] ) ) {
            update_post_meta( $post_id, 'store_phone', sanitize_text_field( $_POST['store_phone'] ) );
        }
        if ( isset( $_POST['store_lat_long'] ) ) {
            update_post_meta( $post_id, 'store_lat_long', sanitize_text_field( $_POST['store_lat_long'] ) );
        }
        // For Quick Edit, we don't need to process other fields like map embed/link
        return;
    }

    // This part handles saving from the full edit screen
    if ( isset( $_POST['store_address'] ) ) {
        update_post_meta( $post_id, 'store_address', sanitize_text_field( $_POST['store_address'] ) );
    }
    if ( isset( $_POST['store_phone'] ) ) {
        update_post_meta( $post_id, 'store_phone', sanitize_text_field( $_POST['store_phone'] ) );
    }
    if ( isset( $_POST['store_google_map'] ) ) {
        update_post_meta( $post_id, 'store_google_map', ( $_POST['store_google_map'] ) );
    }
    if ( isset( $_POST['store_google_map_link'] ) ) { // Lưu link Google Maps
        update_post_meta( $post_id, 'store_google_map_link', esc_url_raw( $_POST['store_google_map_link'] ) );
    }
    if ( isset( $_POST['store_lat_long'] ) ) { // Lưu link Google Maps
        update_post_meta( $post_id, 'store_lat_long', sanitize_text_field( $_POST['store_lat_long'] ) );
    }
}
add_action( 'save_post', 'save_store_system_meta' );
