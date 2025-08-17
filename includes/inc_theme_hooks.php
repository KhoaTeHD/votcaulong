<?php
/**
 * Hook into order creation to update customer profile address if empty.
 */
add_action( 'vcl_order_created_update_customer_profile', 'theme_update_customer_address_from_order', 10, 2 );

function theme_update_customer_address_from_order( $order_id, $user_id ) {
    // Basic validation
    if ( ! $user_id || ! $order_id ) {
        return;
    }

    // --- Ensure required classes are loaded ---
    $customer_class_path = get_template_directory() . '/includes/class_Customer.php';
    $order_class_path = get_template_directory() . '/includes/class_vcl_order.php'; // Need VCL_Order to get details

    if ( ! class_exists('Customer') ) {
        if ( file_exists( $customer_class_path ) ) {
            require_once $customer_class_path;
        } else {
             error_log('Customer class file not found at: ' . $customer_class_path);
             return; // Cannot proceed
        }
    }
     if ( ! class_exists('VCL_Order') ) {
        if ( file_exists( $order_class_path ) ) {
            require_once $order_class_path;
        } else {
             error_log('VCL_Order class file not found at: ' . $order_class_path);
             return; // Cannot proceed
        }
    }
    // --- End Class Loading ---

    try {
        $customer = new Customer( $user_id );
        $order = new VCL_Order( $order_id ); // Instantiate to use its methods
        $order_data = $order->get_order( $order_id ); // Get the main order data row

        if (!$order_data) {
            error_log("Could not retrieve order data for ID: " . $order_id . " in action hook.");
            return;
        }

        // Check if profile addresses are empty (using address_1 as indicator)
        $billing_address_empty = empty( $customer->billing_address['address_1'] );
        $shipping_address_empty = empty( $customer->shipping_address['address_1'] );

        // Only proceed if at least one address needs updating
        if (!$billing_address_empty && !$shipping_address_empty) {
            return; // Both addresses already seem to have data
        }

        // Prepare address data from the order_data object
        $billing_update_data = [
            'first_name' => $order_data->billing_first_name,
            'last_name'  => $order_data->billing_last_name,
            'email'      => $order_data->billing_email,
            'phone'      => $order_data->billing_phone,
            // Using shipping address details from order for billing profile address fields
            'address_1'  => $order_data->shipping_address_1,
            'address_2'  => '', // Assuming not available in order data
            'city'       => $order_data->shipping_city,
            'state'      => $order_data->shipping_state,    // District
            'postcode'   => $order_data->shipping_postcode, // Ward
            'country'    => 'VN', // Assuming Vietnam
            'company'    => '',   // Assuming not available
        ];

        $shipping_update_data = [
            'first_name' => $order_data->shipping_first_name,
            'last_name'  => $order_data->shipping_last_name,
            'phone'      => $order_data->shipping_phone,
            'address_1'  => $order_data->shipping_address_1,
            'address_2'  => '',
            'city'       => $order_data->shipping_city,
            'state'      => $order_data->shipping_state,
            'postcode'   => $order_data->shipping_postcode,
            'country'    => 'VN',
            'company'    => '',
        ];

        // Update profile addresses if they were empty
        if ( $billing_address_empty ) {
            $customer->update_billing_address( $billing_update_data );
             error_log("Updated billing address for user {$user_id} from order {$order_id}"); // Optional logging
        }
        if ( $shipping_address_empty ) {
            $customer->update_shipping_address( $shipping_update_data );
             error_log("Updated shipping address for user {$user_id} from order {$order_id}"); // Optional logging
        }

    } catch ( Exception $e ) {
        error_log( 'Error in action hook theme_update_customer_address_from_order: ' . $e->getMessage() );
    }
}
function vcl_allow_brand_pagination_query_var( $redirect_url, $requested_url ) {
    // Để bật logging cho debug, hãy bỏ comment (xóa //) ở đầu các dòng error_log dưới đây.
    // Sau khi debug xong, bạn nên comment lại để tránh làm đầy file log không cần thiết.
    error_log("--- redirect_canonical filter START (regex version) ---");
    error_log("Requested URL: " . esc_url_raw($requested_url));
    error_log("Redirect URL (proposed by WP): " . esc_url_raw($redirect_url));

    $parsed_requested_url = wp_parse_url( $requested_url );
    
    // Đảm bảo path được trích xuất và chuẩn hóa đúng cách (ví dụ: luôn có dấu gạch chéo ở cuối để so sánh)
    // Loại bỏ dấu / ở cuối nếu có, sau đó thêm lại để đồng nhất
    $requested_path = isset( $parsed_requested_url['path'] ) ? trailingslashit( rtrim($parsed_requested_url['path'], '/') ) : '/';
    
    $query_vars = [];
    if ( isset( $parsed_requested_url['query'] ) ) {
        parse_str( $parsed_requested_url['query'], $query_vars );
    }

    // error_log("Normalized Requested Path: " . $requested_path);
    // error_log("Parsed Query Vars: " . print_r($query_vars, true));

    // Quan trọng: Đảm bảo 'brands' ở đây khớp với slug công khai của Custom Post Type của bạn.
    // Dựa trên file posttype_brands.php của bạn, nó là 'brands'.
    $brand_cpt_slug = 'brands'; 

    // Regex này kiểm tra xem path có dạng /brands/ten-brand-nao-do/ không
    // Nó đảm bảo 'brands' là slug CPT và có một segment theo sau nó.
    // Path được mong đợi là đã được trailingslashit.
    $pattern = '#^/' . preg_quote( $brand_cpt_slug, '#' ) . '/([^/]+?)/$#'; // Mong đợi có dấu / ở cuối

    // error_log("Pattern for preg_match: " . $pattern);

    if ( preg_match( $pattern, $requested_path ) &&
         isset( $query_vars['page'] ) &&
         is_numeric( $query_vars['page'] ) &&
         (int) $query_vars['page'] > 0 ) {

        error_log(">>> Decision: PREVENTING redirect. Path matches CPT single pattern ('" . $brand_cpt_slug . "') and 'page' var is valid. Returning false.");
        error_log("--- redirect_canonical filter END ---");
        return false; // Ngăn chặn redirect canonical
    }

    error_log("Decision: ALLOWING redirect. Conditions not met (Path: " . $requested_path . ", Pattern: " . $pattern . ", Query Page: " . (isset($query_vars['page']) ? esc_html($query_vars['page']) : 'N/A') . ").");
    error_log("--- redirect_canonical filter END (allowing redirect by default) ---");
    return $redirect_url;
}
// add_filter( 'redirect_canonical', 'vcl_allow_brand_pagination_query_var', 10, 2 );
//--------------------------

