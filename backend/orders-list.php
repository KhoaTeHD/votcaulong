<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * VCL_Order_List_Table class.
 * Renders the list of custom orders.
 */
class VCL_Order_List_Table extends WP_List_Table {

    private $wpdb;
    private $orders_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->orders_table = $this->wpdb->prefix . 'custom_orders';

        parent::__construct( [
            'singular' => __( 'Order', LANG_ZONE ),
            'plural'   => __( 'Orders', LANG_ZONE ),
            'ajax'     => false,
        ] );
    }

    public function get_columns() {
        return [
            'cb'             => '<input type="checkbox" />',
            'order_id'       => __( 'Order', LANG_ZONE ),
            'erp_order_code' => __( 'Order Code', LANG_ZONE ),
            'customer'       => __( 'Customer', LANG_ZONE ),
            'date_created'   => __( 'Date', LANG_ZONE ),
            'status'         => __( 'Status', LANG_ZONE ),
            'total_amount'   => __( 'Total', LANG_ZONE ),
            'payment_method' => __( 'Payment Method', LANG_ZONE ),
            'delivery_method'   => __( 'Delivery method:', LANG_ZONE ),
        ];
    }

    protected function get_sortable_columns() {
        return [
            'order_id'       => [ 'order_id', true ],
            'erp_order_code' => [ 'erp_order_code', false ],
            'date_created'   => [ 'date_created', false ],
            'total_amount'   => [ 'total_amount', false ],
        ];
    }

    protected function get_bulk_actions() {
        $actions = [
            'delete' => __( 'Delete', LANG_ZONE ),
        ];
        return $actions;
    }

    /**
     * Add filter controls to the top of the list table.
     */
    protected function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            global $order_statuses;
            $current_status = $_GET['status'] ?? '';
            ?>
            <div class="alignleft actions">
                <select name="status">
                    <option value=""><?php _e( 'All Statuses', LANG_ZONE ); ?></option>
                    <?php foreach ( $order_statuses as $status_key => $status_label ) : ?>
                        <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $current_status, $status_key ); ?>>
                            <?php echo esc_html( $status_label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php /* submit_button( __( 'Filter' ), 'button', 'filter_action', false, [ 'id' => 'post-query-submit' ] ); */ ?>
                <button type="submit" name="filter_action" id="post-query-submit" class="button"><?php _e( 'Filter', LANG_ZONE ); ?></button>
            </div>
            <?php
        }
    }

    /**
     * Displays the search box.
     *
     * @param string $text The 'submit' button text.
     * @param string $input_id The HTML id for the search input.
     */
    public function search_box( $text, $input_id ) {
        if ( empty( $_REQUEST['s'] ) && !$this->has_items() )
            return;

        $input_id = $input_id . '-search-input';

        if ( ! empty( $_REQUEST['orderby'] ) )
            echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
        if ( ! empty( $_REQUEST['order'] ) )
            echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
?>
<p class="search-box">
    <label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
    <input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
    <?php submit_button( $text, 'button', '', false, array( 'id' => 'search-submit' ) ); ?>
</p>
<?php
    }

    public function prepare_items() {
        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
        $per_page     = $this->get_items_per_page( 'orders_per_page', 20 );
        $current_page = $this->get_pagenum();
        
        $meta_table = $this->wpdb->prefix . 'custom_order_meta';
        $where_clauses = [];
        $params = [];

        // Search logic
        $search_term = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';
        if ( ! empty( $search_term ) ) {
            $search_clause = "(o.order_id LIKE %s OR o.billing_first_name LIKE %s OR o.billing_last_name LIKE %s OR o.billing_email LIKE %s OR om.meta_value LIKE %s)";
            $like_term = '%' . $this->wpdb->esc_like( $search_term ) . '%';
            $where_clauses[] = $this->wpdb->prepare( $search_clause, $like_term, $like_term, $like_term, $like_term, $like_term );
        }

        // Status filter logic
        $status_filter = isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : '';
        if ( ! empty( $status_filter ) ) {
            $where_clauses[] = $this->wpdb->prepare( "o.status = %s", $status_filter );
        }

        $where_sql = '';
        if ( ! empty( $where_clauses ) ) {
            $where_sql = " WHERE " . implode( ' AND ', $where_clauses );
        }

        // Main query to count total items with filters
        $total_items  = $this->wpdb->get_var(
            "SELECT COUNT(o.order_id) 
             FROM {$this->orders_table} AS o
             LEFT JOIN {$meta_table} AS om ON (o.order_id = om.order_id AND om.meta_key = '_erp_order_code')
             {$where_sql}"
        );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ] );

        $orderby = sanitize_sql_orderby( $_REQUEST['orderby'] ?? 'order_id' );
        $order   = sanitize_key( $_REQUEST['order'] ?? 'DESC' );
        $offset = ( $current_page - 1 ) * $per_page;

        // Modified query with LEFT JOIN and WHERE clause
        $this->items = $this->wpdb->get_results(
            "SELECT o.*, om.meta_value as erp_order_code
             FROM {$this->orders_table} AS o
             LEFT JOIN {$meta_table} AS om ON (o.order_id = om.order_id AND om.meta_key = '_erp_order_code')
             {$where_sql}
             ORDER BY {$orderby} {$order}
             LIMIT {$per_page} OFFSET {$offset}",
            ARRAY_A
        );
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'date_created':
                return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item[ $column_name ] ) );
            case 'total_amount':
                return function_exists('priceFormater') ? priceFormater($item[ $column_name ]) : number_format($item[ $column_name ], 0, ',', '.') . ' ₫';
            case 'status':
                global $order_statuses;
                return $order_statuses[$item['status']] ?? ucfirst($item['status']);
            case 'payment_method':
                return esc_html( $item['payment_method_title'] );
	        case 'delivery_method':
                $vcl_order = new VCL_Order($item['order_id']);
		        $shipping_method = $vcl_order->get_order_meta($item['order_id'],'_shipping_method', true);
                if ($shipping_method === 'pickup') {
	                if ($pickup_store_id = $vcl_order->get_order_meta($item['order_id'], '_pickup_store_id', true)){
		                $pickup_store         = get_store_by_id( $pickup_store_id );
//		                $delivery_name_address = $pickup_store['address'] ?? '';
		                $delivery_name_address    = '<b>'.$pickup_store['name'].'</b>' ?? '';
	                }
                }else{
	                $delivery_name_address = $item['shipping_address_1'].' '.$item['shipping_address_2'].'<br>'.$item['shipping_state'];
	                $delivery_name_address .= '<br>'.$item['shipping_city'];
//	                $delivery_name = '';
                }
                return ( $delivery_name_address );

            default:
                return esc_html( $item[ $column_name ] );
        }
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="order[]" value="%s" />', $item['order_id'] );
    }

    function column_order_id( $item ) {
        $delete_nonce = wp_create_nonce( 'vcl_delete_order' );
        $actions = [
            'view'   => sprintf( '<a href="?page=%s&action=view&order_id=%s">' . __( 'View Details', LANG_ZONE ) . '</a>', $_REQUEST['page'], $item['order_id'] ),
            'delete' => sprintf( '<a href="?page=%s&action=delete&order_id=%s&_wpnonce=%s" onclick="return confirm(\'Are you sure?\');">' . __( 'Delete', LANG_ZONE ) . '</a>', $_REQUEST['page'], $item['order_id'], $delete_nonce ),
        ];
        return sprintf( '<strong><a href="?page=%s&action=view&order_id=%s">#%s</a></strong> %s', $_REQUEST['page'], $item['order_id'], $item['order_id'], $this->row_actions( $actions ) );
    }

    protected function column_customer( $item ) {
        $customer_name = trim( $item['billing_first_name'] . ' ' . $item['billing_last_name'] );
        if ( empty( $customer_name ) ) {
            $customer_name = __( 'Guest', LANG_ZONE );
        }
        return esc_html( $customer_name );
    }

    /**
     * Render the ERP Order Code column.
     *
     * @param array $item
     * @return string
     */
    protected function column_erp_order_code( $item ) {

	    return sprintf( '<strong><a href="?page=%s&action=view&order_id=%s">#%s</a></strong>', $_REQUEST['page'], $item['order_id'], $item['erp_order_code']);
//        return esc_html( $item['erp_order_code'] ?? '—' );
    }
}

/**
 * Handles actions like deleting or updating an order.
 */
function vcl_handle_order_actions() {
    // Handle Delete Action
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['order_id'] ) ) {
        $nonce = $_GET['_wpnonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'vcl_delete_order' ) ) {
            wp_die( __( 'VCL Security Check Failed: Nonce Mismatch for Single Delete!', LANG_ZONE ) );
        }

        $order_id = absint( $_GET['order_id'] );
        $order = new VCL_Order( $order_id );
        if ( $order->delete_order( $order_id ) ) {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Order deleted successfully.', LANG_ZONE ) . '</p></div>';
            });
            // Redirect to clean URL after successful deletion
            wp_redirect( admin_url( 'admin.php?page=vcl-orders' ) );
            exit;
        } else {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to delete order.', LANG_ZONE ) . '</p></div>';
            });
        }
    }

    // Handle Bulk Delete Action
    if ( ( isset( $_POST['action'] ) && $_POST['action'] === 'delete' ) || ( isset( $_POST['action2'] ) && $_POST['action2'] === 'delete' ) ) {
        $order_ids = $_POST['order'] ?? [];

        if ( ! empty( $order_ids ) ) {
            $deleted_count = 0;
            $delete_nonce = $_REQUEST['bulk_orders'] ?? ''; // Nonce from the form submission

            check_admin_referer( 'bulk-orders', 'bulk_orders'); // 'bulk-orders' is the default nonce action for WP_List_Table bulk actions

            foreach ( $order_ids as $order_id ) {
                $order_id = absint( $order_id );
                $order = new VCL_Order( $order_id );
                if ( $order->delete_order( $order_id ) ) {
                    $deleted_count++;
                }
            }

            if ( $deleted_count > 0 ) {
                add_action( 'admin_notices', function() use ( $deleted_count ) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( _n( '%d order deleted successfully.', '%d orders deleted successfully.', $deleted_count, LANG_ZONE ), $deleted_count ) . '</p></div>';
                });
            } else {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to delete selected orders.', LANG_ZONE ) . '</p></div>';
                });
            }
        }
    }

    // Handle Status Update Action
    if ( isset( $_POST['vcl_update_order_status'] ) && isset( $_POST['order_id'] ) ) {
        $nonce = $_POST['_wpnonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'vcl_update_order_status_nonce' ) ) {
            wp_die( __( 'Security check failed', LANG_ZONE ) );
        }

        $order_id = absint( $_POST['order_id'] );
        $new_status = sanitize_key( $_POST['order_status'] );
        
        $order = new VCL_Order( $order_id );
        if ( $order->update_status( $order_id, $new_status ) ) {
             add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Order status updated successfully.', LANG_ZONE ) . '</p></div>';
            });
        } else {
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to update order status.', LANG_ZONE ) . '</p></div>';
            });
        }
    }
}
add_action( 'admin_init', 'vcl_handle_order_actions' );


/**
 * Renders the main VCL Orders admin page or the order detail view.
 */
function vcl_render_orders_admin_page() {
    if ( isset( $_GET['action'] ) && $_GET['action'] === 'view' && ! empty( $_GET['order_id'] ) ) {
        vcl_render_order_details_page( absint( $_GET['order_id'] ) );
    } else {
        vcl_render_orders_list_page();
    }
}

/**
 * Renders the list of orders.
 */
function vcl_render_orders_list_page() {
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e( 'Orders Management', LANG_ZONE ); ?></h1>
        <form id="orders-filter" method="post">
            <input type="hidden" name="page" value="vcl-orders" />
            <?php wp_nonce_field( 'bulk-orders','bulk_orders' ); ?>
            <?php
            $order_list_table = new VCL_Order_List_Table();
            $order_list_table->prepare_items();
            $order_list_table->search_box( __( 'Search Orders', LANG_ZONE ), 'order' );
            $order_list_table->display();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Renders the detailed view of a single order.
 *
 * @param int $order_id The ID of the order to display.
 */
function vcl_render_order_details_page( $order_id ) {
    $order_manager = new VCL_Order( $order_id );
    $order = $order_manager->get_order_with_meta( $order_id );

    if ( ! $order ) {
        echo '<div class="wrap"><h1>' . __( 'Error', LANG_ZONE ) . '</h1><p>' . __( 'Order not found.', LANG_ZONE ) . '</p></div>';
        return;
    }

    $order_items = $order_manager->get_order_items( $order_id );
    $order_discounts = $order_manager->get_order_discounts( $order_id );
    global $order_statuses;
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <?php 
            $order_code_display = !empty($order->erp_order_code) ? ' | ' . esc_html($order->erp_order_code) : '';
            printf( __( 'Order #%d%s', LANG_ZONE ), $order_id, $order_code_display );
            ?>
        </h1>
        <a href="?page=vcl-orders" class="page-title-action"><?php _e( 'Back to Orders List', LANG_ZONE ); ?></a>
        <hr class="wp-header-end">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    

                    

                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Customer Details', LANG_ZONE ); ?></span></h2>
                        <div class="inside" style="display: flex; justify-content: space-between;">
                            <div style="">
                                <p><strong><?php _e( 'Full Name:', LANG_ZONE ); ?></strong><br><?php echo esc_html( trim( $order->billing_first_name . ' ' . $order->billing_last_name ) ); ?></p>
                                <p><strong><?php _e( 'Email:', LANG_ZONE ); ?></strong><br><a href="mailto:<?php echo esc_attr( $order->billing_email ); ?>"><?php echo esc_html( $order->billing_email ); ?></a></p>
                                <p><strong><?php _e( 'Phone:', LANG_ZONE ); ?></strong><br><?php echo esc_html( $order->billing_phone ); ?></p>
                            </div>
                            <div style="">
                                <?php if ($order->shipping_method === 'pickup'){  ?>
                                    <h4><?php _e( 'Pick up at store', LANG_ZONE ) ?></h4>
                                    <?php
		                               if ( $pickup_store         = get_store_by_id( $order->pickup_store_id )) {
			                               $pickup_store_address = $pickup_store['address'] ?? '';
			                               $pickup_store_name    = $pickup_store['name'] ?? '';
			                               echo '<b>' . $pickup_store_name . '</b><br>';
			                               echo esc_html( $pickup_store_address );
		                               }
		                                ?>
                                <?php } else {  ?>
                                <h4><?php _e( 'Shipping Address', LANG_ZONE ); ?></h4>
                                <address>
                                    <?php
                                    echo esc_html( trim( $order->shipping_first_name . ' ' . $order->shipping_last_name ) ) . '<br>';
                                    echo esc_html( $order->shipping_address_1 ) . '<br>';
                                    echo esc_html( $order->shipping_state ) . ', ' . esc_html( $order->shipping_city );
                                    ?>
                                </address>
                                <p><strong><?php _e( 'Shipping Phone:', LANG_ZONE ); ?></strong><br><?php echo esc_html( $order->shipping_phone ); ?></p>
                                <?php }  ?>
                            </div>
                            <?php if($order->customer_note) {  ?>
                                <div>
                                    <?php echo '<p><strong>' . esc_html__( 'Note:', LANG_ZONE ) . '</strong> ' . nl2br(esc_html($order->customer_note)) . '</p>';  ?>
                                </div>
                            <?php }  ?>
                            <div style="clear: both;"></div>
                        </div>
                    </div>

                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Order Items', LANG_ZONE ); ?></span></h2>
                        <div class="inside">
                            <table class="wp-list-table widefat fixed striped order-details-table">
                                <thead>
                                    <tr>
                                        <th class="product-name"><?php _e( 'Product', LANG_ZONE ); ?></th>
                                        <th class="product-sku"><?php _e( 'SKU', LANG_ZONE ); ?></th>
                                        <th class="product-quantity"><?php _e( 'Quantity', LANG_ZONE ); ?></th>
                                        <th class="product-total text-end"><?php _e( 'Total', LANG_ZONE ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $order_items as $item ) : ?>
                                        <tr class="order-items">
                                            <td class="product-name"><?php echo ( $item->order_item_name ); ?></td>
                                            <td><?php echo esc_html( $item->variation_id ?: $item->product_id ); ?></td>
                                            <td><?php echo esc_html( $item->quantity ); ?></td>
                                            <td class="text-end"><?php echo priceFormater( $item->line_total ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end"><?php _e( 'Subtotal:', LANG_ZONE ); ?></th>
                                        <td class="text-end"><?php echo priceFormater( $order->subtotal_amount ); ?></td>
                                    </tr>
                                    <?php foreach ( $order_discounts as $discount ) : ?>
                                    <tr>
                                        <th colspan="3" class="text-end"><?php echo esc_html( $discount->order_item_name ); ?>:</th>
                                        <td class="text-end"><?php echo priceFormater( $discount->line_total ); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <th colspan="3" class="text-end"><?php _e( 'Shipping:', LANG_ZONE ); ?></th>
                                        <td class="text-end"><?php echo priceFormater( $order->shipping_total ); ?></td>
                                    </tr>
                                    <tr>
                                        <th colspan="3" class="text-end"><strong><?php _e( 'Total:', LANG_ZONE ); ?></strong></th>
                                        <td class="text-end"><strong><?php echo priceFormater( $order->total_amount ); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Order Actions', LANG_ZONE ); ?></span></h2>
                        <div class="inside">
                            <form method="post">
                                <p>
                                    <strong><?php _e( 'Order Date:', LANG_ZONE ); ?></strong><br>
                                    <?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order->date_created ) ); ?>
                                </p>
                                <p>
                                    <strong><?php _e( 'Order Code:', LANG_ZONE ); ?></strong><br>
                                    <?php echo esc_html( $order->erp_order_code ?? __( 'N/A', LANG_ZONE ) ); ?>
                                </p>
                                <p>
                                    <label for="order_status"><strong><?php _e( 'Status:', LANG_ZONE ); ?></strong></label><br>
                                    <select id="order_status" name="order_status" style="width: 100%;">
                                        <?php foreach ( $order_statuses as $status_key => $status_label ) : ?>
                                            <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $order->status, $status_key ); ?>>
                                                <?php echo esc_html( $status_label ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>
                                <p>
                                    <strong><?php _e( 'Payment Method:', LANG_ZONE ); ?></strong><br>
                                    <?php echo esc_html( $order->payment_method_title ); ?>
                                </p>
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                        <input type="hidden" name="vcl_update_order_status" value="1">
                                        <?php wp_nonce_field( 'vcl_update_order_status_nonce' ); ?>
                                        <button type="submit" class="button button-primary button-large"><?php _e( 'Update', LANG_ZONE ); ?></button>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                     <div class="postbox">
                        <h2 class="hndle"><span><?php _e( 'Notes', LANG_ZONE ); ?></span></h2>
                        <div class="inside">
                           <?php
                           $order_notes = $order_manager->get_order_meta( $order_id, '_order_notes', 1 );

                           if ( ! empty( $order->customer_note ) ) {
                               echo '<p><strong>' . __( 'Customer Note:', LANG_ZONE ) . '</strong><br>' . nl2br( esc_html( $order->customer_note ) ) . '</p>';
                           }
                           if ( ! empty( $order_notes ) && is_array( $order_notes ) ) {
                               echo '<h4>' . __( 'Order History', LANG_ZONE ) . '</h4>';
                               echo '<ul class="order-notes-list">';
                               foreach ( $order_notes as $note ) {
                                   $note_content = wp_kses_post( $note['content'] ?? '' );
                                   $note_date = isset( $note['date_created_gmt'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $note['date_created_gmt'] ) ) : '';
                                   $note_by = isset( $note['added_by'] ) ? ucfirst( $note['added_by'] ) : __( 'System', LANG_ZONE );
                                   $note_class = ( $note['is_customer_note'] ?? false ) ? 'customer-note' : 'admin-note';
                                   if ($note_content) {
	                                   echo '<li class="' . esc_attr( $note_class ) . '">';
                                       echo '<p><small>' . sprintf( __( 'Added on %1$s', LANG_ZONE ), $note_date ) . '</small><br>';
                                       echo $note_content . '</p>';
	                                   echo '</li>';
                                   }
                               }
                               echo '</ul>';
                           } else if ( empty( $order->customer_note ) ) {
                               echo '<p>' . __( 'No notes available.', LANG_ZONE ) . '</p>';
                           }
                           ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
