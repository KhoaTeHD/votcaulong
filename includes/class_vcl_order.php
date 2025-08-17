<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class VCL_Order {

    private $order_id;
    private $orders_table;
    private $order_items_table;
    private $order_meta_table;
    private $wpdb;

    public function __construct( $order_id = 0 ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->orders_table = $this->wpdb->prefix . 'custom_orders';
        $this->order_items_table = $this->wpdb->prefix . 'custom_order_items';
        $this->order_meta_table = $this->wpdb->prefix . 'custom_order_meta';

        if ( $order_id ) {
            $this->order_id = absint( $order_id );
            // Optionally load order data here if needed immediately
        }
    }

    public function get_id() {
        return $this->order_id;
    }
    /**
     * Creates a new order in the custom tables.
     *
     * @param array $order_data Data from the checkout form.
     * @param string $initial_status Initial status (e.g., 'pending', 'processing').
     * @return int|false Order ID on success, false on failure.
     */
    public function create_order( $order_data, $initial_status = 'pending' ) {
        $current_time = current_time( 'mysql' );
        $current_time_gmt = current_time( 'mysql', 1 ); // GMT time

        // 1. Prepare data for wp_custom_orders table
        $order_insert_data = [
            'user_id'              => get_current_user_id() ? get_current_user_id() : null,
            'order_key'            => 'vcl-' . wp_generate_password( 12, false ) . time(), // Generate a unique key
            'status'               => sanitize_key( $initial_status ),
            'currency'             => '₫', // Or get from settings
            'payment_method'       => $order_data['payment_method'] ?? null, // e.g., 'cod', 'vnpay'
            'payment_method_title' => $order_data['payment_method_title'] ?? null, // e.g., 'Cash on Delivery', 'VNPay Gateway'
            'customer_ip_address'  => $this->get_ip_address(),
            'customer_user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'customer_note'        => sanitize_textarea_field( $order_data['order_note'] ?? '' ),
            'billing_first_name'   => sanitize_text_field( $order_data['customer_info']['first_name'] ?? '' ), // Adjust keys as needed
            'billing_last_name'    => sanitize_text_field( $order_data['customer_info']['last_name'] ?? '' ), // Adjust keys as needed
            'billing_email'        => sanitize_email( $order_data['customer_info']['email'] ?? '' ),
            'billing_phone'        => sanitize_text_field( $order_data['customer_info']['phone_number'] ?? '' ),
            // Add billing address fields if shipping is not different
            'shipping_first_name'  => sanitize_text_field( $order_data['shipping_address']['first_name'] ?? $order_data['customer_info']['first_name'] ?? '' ), // Adjust keys
            'shipping_last_name'   => sanitize_text_field( $order_data['shipping_address']['last_name'] ?? $order_data['customer_info']['last_name'] ?? '' ), // Adjust keys
            'shipping_address_1'   => sanitize_text_field( $order_data['shipping_address']['street'] ?? '' ),
            'shipping_city'        => sanitize_text_field( $order_data['shipping_address']['city_name'] ?? '' ),
            'shipping_state'       => sanitize_text_field( $order_data['shipping_address']['ward_code'] ?? '' ), // Using state for district
//            'shipping_postcode'    => sanitize_text_field( $order_data['shipping_address']['ward_code'] ?? '' ), // Using postcode for ward
            'shipping_phone'       => sanitize_text_field( $order_data['shipping_address']['phone'] ?? $order_data['customer_info']['phone_number'] ?? '' ),
//	        'shipping_title'       => sanitize_text_field( $order_data['shipping_title'] ?? '' ),
            'date_created'         => $current_time,
            'date_created_gmt'     => $current_time_gmt,
            'date_modified'        => $current_time,
            'date_modified_gmt'    => $current_time_gmt,
            // Initialize totals - these will be calculated later
            'total_amount'         => 0.00,
            'subtotal_amount'      => 0.00,
            'shipping_total'       => (int)$order_data['shipping_total'],
            'tax_total'            => 0.00, // Calculate tax if applicable
        ];
        if (isset($order_data['other_recipient'])){
            $fullname = $order_data['other_recipient']['name'];
            $phone = $order_data['other_recipient']['phone'];
            $order_insert_data['shipping_first_name'] = $fullname;
            $order_insert_data['shipping_last_name'] = '';
            $order_insert_data['shipping_phone'] = $phone;    
        }

        // 2. Insert into wp_custom_orders table
        $inserted = $this->wpdb->insert( $this->orders_table, $order_insert_data );

        if ( ! $inserted ) {
            error_log( 'Failed to insert order into ' . $this->orders_table . ': ' . $this->wpdb->last_error );
			my_debug($order_insert_data);
            return false;
        }

        $this->order_id = $this->wpdb->insert_id;

        // 3. Save order items to wp_custom_order_items
        $cart_items = $order_data['cart'] ?? [];
        $subtotal = $this->save_cart_items_to_order( $this->order_id, $cart_items );

        // 4. Calculate final total (subtotal + shipping + tax - discounts)
        // TODO: Implement calculation logic including shipping, tax, vouchers
        $shipping_cost = $order_data['shipping_total']; // Replace with actual calculation
        $tax_amount = 0.00;    // Replace with actual calculation
        $discount_amount = 0.00; // Replace with actual calculation based on voucher
        $total_amount = ($subtotal + $shipping_cost + $tax_amount) - $discount_amount;
        // $total_amount = 10000;

        // 5. Update totals in wp_custom_orders table
        $this->wpdb->update(
            $this->orders_table,
            [
                'subtotal_amount' => $subtotal,
                'shipping_total'  => $shipping_cost,
                'tax_total'       => $tax_amount,
                'total_amount'    => $total_amount,
            ],
            [ 'order_id' => $this->order_id ] // WHERE clause
        );

        // 6. Save additional meta data to wp_custom_order_meta
        $this->update_order_meta( $this->order_id, $order_data );

        // 7. Trigger action hook after order creation is complete
        $user_id = get_current_user_id(); // Ensure user_id is available here
        if ( $this->order_id && $user_id ) {
             do_action( 'vcl_order_created_update_customer_profile', $this->order_id, $user_id );
        }

        // TODO: Trigger actions/emails if needed


        return $this->order_id;
    }

    /**
     * Saves cart items to the custom order items table.
     *
     * @param int $order_id The ID of the order.
     * @param array $cart_items Array of items from the cart.
     * @return float The calculated subtotal of all items.
     */
    private function save_cart_items_to_order( $order_id, $cart_items ) {
        $subtotal = 0.00;

        // Use the logging added previously
        if ( ! $order_id || empty( $cart_items ) || ! is_array( $cart_items ) ) {
            error_log("VCL_Order::save_cart_items_to_order - No cart items to save for Order ID: " . $order_id);
            return $subtotal;
        }

        foreach ( $cart_items as $item_key => $item ) {
            // Treat product_id as a string (SKU) and sanitize it
            $product_id = isset($item['id']) ? sanitize_text_field( $item['id'] ) : '';
            // Keep variation_id as integer for now, assuming it's numeric.
            // If variation_id is also an SKU/string, remove absint() and use sanitize_text_field() like product_id.
            $variation_id = isset($item['variation']['id']) ? sanitize_text_field($item['variation']['id']) : '';
            $quantity = absint( $item['quantity'] ?? 0 );
            $unit_price = isset($item['variation']['price']) ? floatval($item['variation']['price']) : floatval($item['price'] ?? 0);
            $line_total = $unit_price * $quantity;
            // Use 'title' as discussed before, adjust if needed
            $item_name = sanitize_text_field($item['name'] ?? 'Unknown Product');

            // Append variation attributes (code remains the same as previous update)
            if (!empty($variation_id ) && isset($item['attributes']) && is_array(['attributes'])) {
                 $attrs_str = [];
                 foreach ($item['attributes'] as $attr_key => $attr_val) {
                     $attrs_str[] = sanitize_text_field($attr_key) . ': ' . sanitize_text_field($attr_val);
                 }
                 if (!empty($attrs_str)) {
                    $item_name .= ' (' . implode(', ', $attrs_str) . ')';
                 }
            }

            // Validate using !empty() for string SKU and quantity > 0
            if ( ! empty( $product_id ) && $quantity > 0 ) {
                $item_data = [
                    'order_id'        => $order_id,
                    'order_item_name' => $item_name,
                    'order_item_type' => 'line_item',
                    'product_id'      => $product_id, // Store the SKU string
                    'variation_id'    => $variation_id,
                    'quantity'        => $quantity,
                    'unit_price'      => $unit_price,
                    'line_subtotal'   => $line_total,
                    'line_total'      => $line_total,
                    'line_tax'        => 0.00,
                ];

                $inserted = $this->wpdb->insert( $this->order_items_table, $item_data );

                if ($inserted) {
                    $subtotal += $line_total;
                } else {
                    error_log("VCL_Order::save_cart_items_to_order - Failed to insert item for Order ID: " . $order_id . ". Item data: " . print_r($item_data, true) . ". DB Error: " . $this->wpdb->last_error);
                }
            } else {
                 error_log("VCL_Order::save_cart_items_to_order - Skipped invalid item for Order ID: " . $order_id . ". Invalid SKU ('{$product_id}') or quantity ({$quantity}). Item data: " . print_r($item, true));
            }
        }
        return $subtotal;
    }

    /**
     * Updates meta data in the custom order meta table.
     *
     * @param int $order_id The ID of the order.
     * @param array $order_data The full order data array.
     */
    public function update_order_meta( $order_id, $order_data ) {
        if ( ! $order_id || empty( $order_data ) || ! is_array( $order_data ) ) {
            return;
        }
        $order_id = absint( $order_id );

        // Example: Save shipping method choice
        if ( isset( $order_data['shipping_method'] ) ) {
            $this->add_or_update_meta( $order_id, '_shipping_method', $order_data['shipping_method'] );
        }
	    if ( isset( $order_data['shipping_service'] ) ) {
		    $this->add_or_update_meta( $order_id, '_shipping_service', $order_data['shipping_service'] );
	    }
        // Example: Save pickup store ID if applicable
        if ( isset( $order_data['pickup_store_id'] ) && $order_data['shipping_method'] === 'pickup' ) {
             $this->add_or_update_meta( $order_id, '_pickup_store_id', $order_data['pickup_store_id'] );
        }

        // Example: Save other recipient info
        if ( isset( $order_data['other_recipient'] ) && is_array($order_data['other_recipient']) ) {
            $this->add_or_update_meta( $order_id, '_other_recipient_details', maybe_serialize($order_data['other_recipient']) );
        }
         // Example: Save company invoice info
        if ( isset( $order_data['company_invoice'] ) && is_array($order_data['company_invoice']) ) {
            $this->add_or_update_meta( $order_id, '_company_invoice_details', maybe_serialize($order_data['company_invoice']) );
        }
         // Example: Save voucher code used
        if ( !empty( $order_data['voucher_code'] ) ) {
            $this->add_or_update_meta( $order_id, '_voucher_code', $order_data['voucher_code'] );
            // You might also store voucher discount amount here after calculation
        }
        // Add more meta fields as needed...
         $this->add_or_update_meta( $order_id, '_how_to_use_requested', !empty($order_data['how_to_use']) );


    }

    /**
     * Saves or updates a single meta key-value pair for the order.
     *
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     * @return bool True on success, false on failure.
     */
    public function save_single_meta( $meta_key, $meta_value ) {
        if ( ! $this->order_id ) {
            return false;
        }
        // Use the existing private helper method
        return $this->add_or_update_meta( $this->order_id, $meta_key, $meta_value );
    }

     /**
     * Helper function to add or update order meta.
     *
     * @param int    $order_id   Order ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     * @return bool True on success, false on failure.
     */
    private function add_or_update_meta( $order_id, $meta_key, $meta_value ) {
        $order_id = absint( $order_id );
        $meta_key = sanitize_key( $meta_key );
        $meta_value = maybe_serialize( $meta_value ); // Serialize arrays/objects

        // Check if meta key already exists for this order
        $existing_meta_id = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT meta_id FROM {$this->order_meta_table} WHERE order_id = %d AND meta_key = %s",
            $order_id,
            $meta_key
        ) );

        if ( $existing_meta_id ) {
            // Update existing meta
            $result = $this->wpdb->update(
                $this->order_meta_table,
                [ 'meta_value' => $meta_value ], // Data
                [ 'meta_id' => $existing_meta_id ] // Where
            );
        } else {
            // Insert new meta
            $result = $this->wpdb->insert(
                $this->order_meta_table,
                [
                    'order_id'   => $order_id,
                    'meta_key'   => $meta_key,
                    'meta_value' => $meta_value,
                ]
            );
        }

        return (bool) $result;
    }

    /**
     * Adds a note to the order history (stored in order meta).
     *
     * @param string $note The note content.
     * @param bool   $is_customer_note Whether this note is visible to the customer (optional).
     * @return bool True on success, false on failure.
     */
    public function add_order_note( $note, $is_customer_note = false ) {
        if ( ! $this->order_id || empty( $note ) ) {
            return false;
        }

        $note_data = [
            'content' => wp_kses_post( $note ), // Sanitize note content
            'date_created_gmt' => current_time( 'mysql', 1 ),
            'added_by' => is_admin() ? 'admin' : 'system', // Track who added the note
            'is_customer_note' => (bool) $is_customer_note,
        ];

        // Retrieve existing notes (if any)
        $notes = $this->get_order_meta( $this->order_id, '_order_notes', false ); // Get all notes as an array
        if ( ! is_array( $notes ) ) {
            $notes = [];
        }

        // Add the new note
        $notes[] = $note_data;

        // Save the updated notes array back to meta
        return $this->add_or_update_meta( $this->order_id, '_order_notes', $notes );
    }

    /**
     * Updates the status of an order.
     *
     * @param int $order_id The ID of the order.
     * @param string $new_status The new status key.
     * @return bool True on success, false on failure.
     */
    public function update_status( $order_id, $new_status ) {
        $order_id = absint( $order_id );
        if ( ! $order_id ) return false;

        $updated = $this->wpdb->update(
            $this->orders_table,
            [
                'status'            => sanitize_key( $new_status ),
                'date_modified'     => current_time( 'mysql' ),
                'date_modified_gmt' => current_time( 'mysql', 1 ),
                // Potentially update date_paid_gmt or date_completed_gmt based on status
            ],
            [ 'order_id' => $order_id ] // WHERE clause
        );

        // TODO: Trigger actions/emails based on status change

        return (bool) $updated;
    }

    /**
     * Gets the client IP address.
     *
     * @return string IP address.
     */
    private function get_ip_address() {
        if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // Check for proxies.
            return (string) rest_is_ip_address( trim( current( explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }
        return '';
    }

    /**
     * Retrieves the unique order key for a given order ID.
     *
     * @param int $order_id The ID of the order.
     * @return string|false The order key string on success, false if not found or on error.
     */
    public function get_order_key( $order_id ) {
        $order_id = absint( $order_id );
        if ( ! $order_id ) {
            return false;
        }

        // Query the database to get the order_key directly from the orders table
        $order_key = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT order_key FROM {$this->orders_table} WHERE order_id = %d",
            $order_id
        ) );

        // get_var returns NULL if no row is found, or the value if found.
        // Return false if NULL or empty, otherwise return the key.
        return ! empty( $order_key ) ? $order_key : false;
    }


    public function get_order_status( $order_id ) {
        $order_id = absint( $order_id );
        if (! $order_id ) {
            return false;
        }
        // Query the database to get the order_status directly from the orders table
        $order_status = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT status FROM {$this->orders_table} WHERE order_id = %d",
            $order_id           
        )); 

        // get_var returns NULL if no row is found, or the value if found.
        // Return false if NULL or empty, otherwise return the status.
        return! empty( $order_status )? $order_status : false;
    }
	public function get_order_note($order_id){
		$order_id = absint( $order_id );
		if (! $order_id ) {
			return false;
		}
		// Query the database to get the order_status directly from the orders table
		$order_note = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT customer_note FROM {$this->orders_table} WHERE order_id = %d",
			$order_id
		));

		// get_var returns NULL if no row is found, or the value if found.
		// Return false if NULL or empty, otherwise return the status.
		return! empty( $order_note )? $order_note : false;
	}
    /**
     * Retrieves the main order data for a given order ID.
     *
     * @param int $order_id The ID of the order.
     * @return object|null Order data object on success, null if not found or on error.
     */
    public function get_order( $order_id ) {
        $order_id = absint( $order_id );
        if ( ! $order_id ) {
            return null;
        }

        // Query the database to get the order data
        $order_data = $this->wpdb->get_row( $this->wpdb->prepare(
            "SELECT * FROM {$this->orders_table} WHERE order_id = %d",
            $order_id
        ) );

        return $order_data; // Returns null if not found
    }

    /**
     * Retrieves all order items for a given order ID.
     *
     * @param int $order_id The ID of the order.
     * @return array Array of order item objects, empty array if none found.
     */
    public function get_order_items( $order_id ) {
        $order_id = absint( $order_id );
        if ( ! $order_id ) {
            return [];
        }

        // Query the database to get all items for the order
        $items = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT * FROM {$this->order_items_table} WHERE order_id = %d ORDER BY order_item_id ASC",
            $order_id
        ) );

        return $items ? $items : [];
    }

    /**
     * Retrieves meta data for a given order ID.
     * Mimics the behavior of get_post_meta.
     *
     * @param int    $order_id The ID of the order.
     * @param string $key      Optional. The meta key to retrieve. If empty, retrieves all meta.
     * @param bool   $single   Optional. Whether to return a single value. Default true.
     *                         If false, returns an array of values for the given key.
     *                         If $key is empty, $single is ignored, and all meta is returned.
     * @return mixed Will be an array if $key is empty or $single is false. Will be value if $single is true.
     *               Returns empty string or empty array if no meta is found.
     */
    public function get_order_meta( $order_id, $key = '', $single = true ) {
        $order_id = absint( $order_id );
        if ( ! $order_id ) {
            return $single ? '' : [];
        }

        $key = sanitize_key( $key );

        if ( ! empty( $key ) ) {
            // Retrieve a specific meta key
            $metas = $this->wpdb->get_col( $this->wpdb->prepare(
                "SELECT meta_value FROM {$this->order_meta_table} WHERE order_id = %d AND meta_key = %s",
                $order_id,
                $key
            ) );

            if ( empty( $metas ) ) {
                return $single ? '' : [];
            }

            // Unserialize values
            $metas = array_map( 'maybe_unserialize', $metas );

            return $single ? $metas[0] : $metas;

        } else {
            // Retrieve all meta keys for the order
            $all_meta = [];
            $results = $this->wpdb->get_results( $this->wpdb->prepare(
                "SELECT meta_key, meta_value FROM {$this->order_meta_table} WHERE order_id = %d",
                $order_id
            ) );

            if ( $results ) {
                foreach ( $results as $meta ) {
                    $all_meta[ $meta->meta_key ][] = maybe_unserialize( $meta->meta_value );
                }
            }
            return $all_meta;
        }
    }
    /**
     * Retrieves the main order data along with all its meta data.
     * Meta data values are added as properties to the main order object.
     * Assumes meta keys are unique and uses the first value if multiple exist for a key.
     * Meta keys starting with '_' will have the underscore removed in the property name.
     *
     * @param int $order_id The ID of the order.
     * @return object|null Order data object with meta properties included, null if order not found.
     */
    public function get_order_with_meta( $order_id ) {
        // First, get the main order data
        $order_data = $this->get_order( $order_id );

        if ( ! $order_data ) {
            return null; // Order not found
        }

        $order_data->fullname = $order_data->billing_first_name . ' ' . $order_data->billing_last_name;
        $order_data->order_link =home_url('/tai-khoan-tab.orders.id'.$order_id) ;
        // Get all meta data for the order
        // Use $single = false to get arrays of values for each key
        $all_meta = $this->get_order_meta( $order_id, '', false );

        if ( ! empty( $all_meta ) && is_array( $all_meta ) ) {
            foreach ( $all_meta as $meta_key => $meta_values ) {
                // Add the meta key as a property to the order object.
                // Use the first value from the array returned by get_order_meta.
                // Remove leading underscore often used for internal meta keys for cleaner access.
                $property_key = ltrim( $meta_key, '_' );
                if ( ! empty( $meta_values ) && isset( $meta_values[0] ) ) {
                    // Only add if the property doesn't already exist from the main table query
                    // to avoid overwriting core fields like 'status' if a meta key happens to be named 'status'.
                    // Also check if the cleaned property key is not empty (in case meta_key was just '_')
                    if ( ! empty($property_key) && ! property_exists( $order_data, $property_key ) ) {
                         $order_data->$property_key = $meta_values[0];
                    } elseif ( property_exists( $order_data, $property_key ) ) {
                        // Optionally log a warning if a meta key conflicts with a table column name
                        // error_log("VCL_Order::get_order_with_meta - Meta key '{$meta_key}' conflicts with existing property on Order ID: {$order_id}");
                    }
                }
            }
        }

        return $order_data;
    }
    /**
     * Deletes an order and its associated items and meta data.
     * Use with caution!
     *
     * @param int $order_id The ID of the order to delete.
     * @return bool True on success (all deletions successful), false otherwise.
     */
    public function delete_order( $order_id ) {
        $order_id = absint( $order_id );
        if ( ! $order_id ) {
            return false;
        }

        // Delete order items first
        $items_deleted = $this->wpdb->delete(
            $this->order_items_table,
            [ 'order_id' => $order_id ],
            [ '%d' ] // Format for the WHERE clause value
        );

        // Delete order meta
        $meta_deleted = $this->wpdb->delete(
            $this->order_meta_table,
            [ 'order_id' => $order_id ],
            [ '%d' ]
        );

        // Delete the main order record
        $order_deleted = $this->wpdb->delete(
            $this->orders_table,
            [ 'order_id' => $order_id ],
            [ '%d' ]
        );

        // Return true only if the main order deletion was successful.
        // Deletion of items/meta might return 0 if there were none, which is okay.
        // $items_deleted !== false && $meta_deleted !== false && $order_deleted !== false;
        // A more robust check might be needed depending on exact requirements.
        // For now, we primarily care if the main order record was deleted.
        return ( $order_deleted !== false );
    }

    public function get_order_received_url( $order_id ): string {
        $order_id = absint( $order_id );
        if (! $order_id ) {
            return '';
        }
        $order_key = $this->get_order_key( $order_id );
        if (! $order_key ) {
            return '';
        }
        $order_link = home_url('/dat-hang-thanh-cong/' . $order_id . '/' . $order_key . '/');
        return $order_link;
    }

	public function sync_to_erp() {
		if (! $this->order_id) return new WP_Error('invalid_order_id', __('Order ID is not set.', LANG_ZONE));

		$order = $this->get_order_with_meta($this->order_id);
		if (! $order) return new WP_Error('order_not_found', __('Order not found.',LANG_ZONE));

//		$items = $this->wpdb->get_results($this->wpdb->prepare(
//			"SELECT product_id, quantity FROM {$this->order_items_table} WHERE order_id = %d",
//			$this->order_id
//		));
		$customer_erp_code = get_user_meta($order->user_id,'erp_name',true);
		$items = $this->get_order_items($this->order_id);
		$order_data = $this->get_order( $this->order_id );
		$items_payload = [];
		foreach ($items as $item) {
			$items_payload[] = [
				'item_code' => $item->product_id,
				'qty' => (int)$item->quantity,
			];
		}
//		$notes = $this->get_order_note($this->order_id);
//		$payment = $this->get_
		$payload = [
			'customer' => $customer_erp_code,
			'transaction_date' => current_time('Y-m-d'),
			'items' => $items_payload,
			'phone' => $order->shipping_phone,
			'address_title' => trim($order->shipping_first_name . ' ' . $order->shipping_last_name),
			'address_line1' => $order->shipping_address_1,
			'custom_address_location' => $order->shipping_city,
			'custom_ward' => $order->shipping_state,
			'custom_customers_request'=> $order_data->customer_note??"",
//			'custom_pickup_at' => "BR-2025-00001",
			'payment_mode'=> $order_data->payment_method!=='cod'?'CARD':'COD',
			'delivery_vendor'=> $order_data->shipping_service??'',
			'delivery_fee'=> $order->shipping_total??0
		];

		$erp = new ERP_API_Client();
		$result = $erp->create_sales_order($payload);
//		error_log('[Order Sync ERP:'.print_r($payload,1));
		if (is_wp_error($result)) {
			$this->add_order_note("❌ Sync ERP failed: " . $result->get_error_message(), false);
			error_log('[ERP Sync Order] => Error'.$result->get_error_message());
		} else {
			$this->add_order_note("✅ Đã sync ERP thành công: " . $result['name'], false);
			$this->save_single_meta('_erp_order_code', $result['name']);
		}

		return $result;
	}

	/**
	 * Get order_id by erp_name (meta _erp_order_code).
	 *
	 * @param string $erp_name
	 * @return int|false
	 */
	public function get_order_by_erp_name($erp_name) {
		if (empty($erp_name)) return false;
		$erp_name = sanitize_text_field($erp_name);

		$order_id = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT order_id FROM {$this->order_meta_table}
         WHERE meta_key = %s AND meta_value = %s
         LIMIT 1",
			'_erp_order_code', $erp_name
		));

		return $order_id ? intval($order_id) : false;
	}

	/**
	 * Update status by erp_name.
	 *
	 * @param string $erp_name
	 * @param string $new_status
	 * @return bool True/false .
	 */
	public function update_status_by_erp_name($erp_name, $new_status) {
		$order_id = $this->get_order_by_erp_name($erp_name);
		if (!$order_id) return false;
		return $this->update_status($order_id, $new_status);
	}
} // End of VCL_Order class
