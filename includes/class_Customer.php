<?php
class Customer extends WP_User {
	public $phone_number; // General phone number, might differ from billing/shipping
	public $dob;
	public $gender;
	// Store addresses as arrays
	public $billing_address = [];
	public $shipping_address = [];
	private $avatar_id;
	private $avatar_url;
    private $user_id;
    public $erp_name;
	private $wpdb; // Add wpdb property

	// Define standard address keys
    private $billing_keys = ['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'ward', 'postcode', 'country', 'email', 'phone'];
    private $shipping_keys = ['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'phone']; // Shipping usually doesn't have email
    private $addresses_meta_key = '_customer_addresses'; // Key để lưu mảng địa chỉ

	public function __construct($id) {
		parent::__construct($id);
		global $wpdb; // Access the global wpdb object
        $this->wpdb = $wpdb; // Assign it to the class property

		// Load general meta
		$this->phone_number = get_user_meta($this->ID, 'phone_number', true);
		$this->gender = get_user_meta($this->ID, 'gender', true);
		$this->dob = get_user_meta($this->ID, 'dob', true);
		$this->avatar_id = get_user_meta($this->ID, 'avatar_id', true);
        $this->erp_name = get_user_meta($this->ID, 'erp_name', true);

		// Load structured billing address from user meta
		foreach ($this->billing_keys as $key) {
			$this->billing_address[$key] = get_user_meta($this->ID, 'billing_' . $key, true);
		}
		// Optional: Align core WP fields if they should match billing by default
        // $this->first_name = $this->billing_address['first_name'] ?? $this->first_name;
        // $this->last_name = $this->billing_address['last_name'] ?? $this->last_name;
        // $this->user_email = $this->billing_address['email'] ?? $this->user_email;


		// Load structured shipping address from user meta
		foreach ($this->shipping_keys as $key) {
			$this->shipping_address[$key] = get_user_meta($this->ID, 'shipping_' . $key, true);
		}


		if ($this->avatar_id ) {
			$this->avatar_url = wp_get_attachment_url($this->avatar_id );
		} else {
			$this->avatar_url = '';
		}
	}

	/**
	 * Static method to create a new customer.
	 *
	 * @param array $data Array of user data:
	 *        - user_login (optional, auto-generated from email if omitted)
	 *        - user_pass (required)
	 *        - user_email (required)
	 *        - full_name (optional)
	 *        - phone_number, gender, dob (optional)
	 *        - billing_address, shipping_address (array, optional)
	 *        - role (optional, default 'customer')
	 * @return Customer|WP_Error
	 */
	public static function create_customer($data) {
		$user_pass = $data['user_pass'] ?? '';
		$user_login = sanitize_user($data['user_login'] ?? '', true);
		$full_name = sanitize_text_field($data['full_name'] ?? '');
		$role = sanitize_key($data['role'] ?? 'customer');

		$email_or_phone = trim($data['email_or_phone'] ?? '');
		$email_is_valid = is_email($email_or_phone);
		$phone_is_valid = preg_match('/^[0-9]{9,15}$/', $email_or_phone);

		$user_email = '';
		$phone_number = '';

		if (!$email_is_valid && !$phone_is_valid) {
			return new WP_Error('invalid_input', __('You must enter a valid email or phone number.',LANG_ZONE));
		}

		if (empty($user_pass)) {
			return new WP_Error('invalid_input', __('Password is required.',LANG_ZONE));
		}

		if ($email_is_valid) {
			$user_email = sanitize_email($email_or_phone);
			if (empty($user_login)) {
				// Lấy phần trước @ và domain, ghép lại: nguyenvana_gmail
				$parts = explode('@', $user_email);
				$username_part = sanitize_user($parts[0], true);
				$domain_part = preg_replace('/[^a-z0-9]/', '', strtolower($parts[1]));
				$base_login = $username_part . '_' . $domain_part;

				$user_login = $base_login;
				$suffix = 1;
				while (username_exists($user_login)) {
					$user_login = $base_login . '-' . $suffix;
					$suffix++;
				}
			}
		}

		if ($phone_is_valid) {
			$phone_number = sanitize_text_field($email_or_phone);
			if (empty($user_email)) {
				$user_email = ''; // Nếu chỉ có phone, bỏ email
			}
			if (empty($user_login)) {
				$base_login = 'user_' . $phone_number;
				$user_login = $base_login;
				$suffix = 1;
				while (username_exists($user_login)) {
					$user_login = $base_login . '-' . $suffix;
					$suffix++;
				}
			}
		}

		// ==== TẠO USER WORDPRESS ====
		$user_id = wp_insert_user([
			'user_login'   => $user_login,
			'user_pass'    => $user_pass,
			'user_email'   => $user_email,
			'display_name' => $full_name,
			'role'         => $role,
		]);
		if (is_wp_error($user_id)) return $user_id;

		$customer = new self($user_id);
		if ($full_name) $customer->update_fullname($full_name);
		if ($phone_number) $customer->update_phone_number($phone_number);
		if (!empty($data['gender'])) $customer->update_gender($data['gender']);
		if (!empty($data['dob'])) $customer->update_birthdate($data['dob']);

		$erp_api = new ERP_API_Client();
		$erp_customer_data = [
			'customer_name' => $full_name ?: $user_login,
			'gender' => $data['gender'] ?? '',
			'mobile_no' => $phone_number,
			'email_id' => $user_email,
			'country' => 'Vietnam',
		];

		if (!empty($data['custom_address_location'])) {
			$erp_customer_data['custom_address_location'] = $data['custom_address_location'];
		}
		if (!empty($data['custom_ward'])) {
			$erp_customer_data['custom_ward'] = $data['custom_ward'];
		}
		if (!empty($data['address_line1'])) {
			$erp_customer_data['address_line1'] = $data['address_line1'];
		}

		$erp_response = $erp_api->create_customer($erp_customer_data);

		if (!is_wp_error($erp_response) && !empty($erp_response['name'])) {
			update_user_meta($user_id, 'erp_name', $erp_response['name']);
			$customer->erp_name = $erp_response['name'];
		}

		return $customer;
	}
	public function sync_to_erp() {
		if (!$this->ID || !$this->erp_name) return false;

		$erp_api = new ERP_API_Client();

		$data = [
			'customer_id'   => $this->erp_name,
			'customer_name' => $this->display_name,
			'email_id'      => $this->user_email,
			'mobile_no'     => $this->phone_number,
			'gender'        => $this->gender,
		];

		// Thêm địa chỉ nếu có
		if (!empty($this->billing_address['address_1'])) {
			$data['address_line1'] = $this->billing_address['address_1'];
		}
		if (!empty($this->billing_address['ward'])) {
			$data['custom_ward'] = $this->billing_address['ward'];
		}
		if (!empty($this->billing_address['city'])) {
			$data['custom_address_location'] = $this->billing_address['city'];
		}

		return $erp_api->update_customer($data);
	}
	public static function sync_to_erp_static($user_id) {
		$customer = new self($user_id);
		$erp_api = new ERP_API_Client();

		$data = [
			'customer_name' => $customer->display_name,
			'email_id'      => $customer->user_email,
			'mobile_no'     => $customer->phone_number,
			'gender'        => $customer->gender,
			'country'       => 'Vietnam'
		];

		// Add address fields if available
		if (!empty($customer->billing_address['address_1'])) {
			$data['address_line1'] = $customer->billing_address['address_1'];
		}
		if (!empty($customer->billing_address['ward'])) {
			$data['custom_ward'] = $customer->billing_address['ward'];
		}
		if (!empty($customer->billing_address['city'])) {
			$data['custom_address_location'] = $customer->billing_address['city'];
		}

		try {
			$erp_name = $customer->erp_name;

			if (!empty($erp_name)) {
					$exists = $erp_api->get_customer($erp_name);
					if (!is_wp_error($exists)){
						$data['customer_id'] = $erp_name;
						return $erp_api->update_customer($data);
					}
			}

			$result = $erp_api->create_customer($data);
			if (!is_wp_error($result) && !empty($result['name'])) {
				$customer->update_erp_name($result['name']);
			}

			return $result;

		} catch (Exception $e) {
			return new WP_Error('erp_sync_error', 'ERP Sync failed: ' . $e->getMessage());
		}
	}


	public function update_phone_number($phone_number) {
		update_user_meta($this->ID, 'phone_number', sanitize_text_field($phone_number)); // Add sanitize
		$this->phone_number = $phone_number;
	}

	public function update_gender($gender) {
		update_user_meta($this->ID, 'gender', sanitize_text_field($gender)); // Add sanitize
		$this->gender = $gender;
	}

	public function update_birthdate($birthdate) {
		// Can add date format validation here
		update_user_meta($this->ID, 'dob', sanitize_text_field($birthdate)); // Add sanitize
		$this->dob = $birthdate;
	}

	/**
	 * Updates the entire billing address based on an associative array.
	 *
	 * @param array|null $address Associative array of billing address components.
	 *                       Keys must match the defined billing keys (e.g., 'first_name', 'address_1', 'city', etc.).
     *                       If null is passed, the billing address will be cleared.
	 */
	public function update_billing_address($address) {
		if (is_array($address)) {
			foreach ($this->billing_keys as $key) {
				$meta_key = 'billing_' . $key;
				if (isset($address[$key])) {
                    // Sanitize based on key type (email, phone need different sanitization)
                    $value = '';
                    if ($key === 'email') {
                        $value = sanitize_email($address[$key]);
                    } elseif ($key === 'phone') {
                        // Custom sanitize for phone if needed (e.g., keep only numbers)
                        $value = sanitize_text_field($address[$key]);
                    } else {
                        $value = sanitize_text_field($address[$key]);
                    }
					update_user_meta($this->ID, $meta_key, $value);
					$this->billing_address[$key] = $value; // Update property
				} else {
                    // If key is not in the input array, delete the corresponding meta
                    delete_user_meta($this->ID, $meta_key);
                    $this->billing_address[$key] = ''; // Clear property
                }
			}
            // Optional: Update core WP user fields if they should match billing
            // wp_update_user([
            //     'ID' => $this->ID,
            //     'first_name' => $this->billing_address['first_name'] ?? $this->first_name,
            //     'last_name' => $this->billing_address['last_name'] ?? $this->last_name,
            //     'user_email' => $this->billing_address['email'] ?? $this->user_email, // Be careful updating core email
            // ]);
		} elseif ($address === null) {
            // If null is passed, delete all billing address meta
             foreach ($this->billing_keys as $key) {
                 delete_user_meta($this->ID, 'billing_' . $key);
                 $this->billing_address[$key] = '';
             }
        }
        // Do nothing if $address is not an array or null
	}

	/**
	 * Updates the entire shipping address based on an associative array.
	 *
	 * @param array|null $address Associative array of shipping address components.
	 *                       Keys must match the defined shipping keys (e.g., 'first_name', 'address_1', 'city', etc.).
     *                       If null is passed, the shipping address will be cleared.
	 */
	public function update_shipping_address($address) {
		if (is_array($address)) {
			foreach ($this->shipping_keys as $key) {
				$meta_key = 'shipping_' . $key;
				if (isset($address[$key])) {
                    // Sanitize based on key type
                    $value = '';
                     if ($key === 'phone') {
                        // Custom sanitize for phone if needed
                        $value = sanitize_text_field($address[$key]);
                    } else {
                        $value = sanitize_text_field($address[$key]);
                    }
					update_user_meta($this->ID, $meta_key, $value);
					$this->shipping_address[$key] = $value; // Update property
				} else {
                    // If key is not in the input array, delete the corresponding meta
                    delete_user_meta($this->ID, $meta_key);
                    $this->shipping_address[$key] = ''; // Clear property
                }
			}
		} elseif ($address === null) {
            // If null is passed, delete all shipping address meta
             foreach ($this->shipping_keys as $key) {
                 delete_user_meta($this->ID, 'shipping_' . $key);
                 $this->shipping_address[$key] = '';
             }
        }
         // Do nothing if $address is not an array or null
	}

	// Methods to update user information (full name, email, etc.)
	// These methods update core WP fields. They are separate from address fields unless explicitly linked.
	public function update_fullname($fullname) {
        $fullname = sanitize_text_field($fullname);
        $parts = explode(' ', $fullname);
        $first_name = $parts[0]; // Get first name
        $last_name = implode(' ', array_slice($parts, 1)); // Get last name and middle name

		$user_data = array(
			'ID' => $this->ID,
			'first_name' => $first_name,
			'last_name' => $last_name,
            'display_name' => $fullname // Also update display_name
		);
		$result = wp_update_user($user_data);
        if (!is_wp_error($result)) {
            // Update object properties after successful wp_update_user
            $this->first_name = $first_name;
            $this->last_name = $last_name;
            $this->display_name = $fullname;
            // Optional: Update billing/shipping name meta as well if synchronization is desired
            // update_user_meta($this->ID, 'billing_first_name', $first_name);
            // update_user_meta($this->ID, 'billing_last_name', $last_name);
            // update_user_meta($this->ID, 'shipping_first_name', $first_name);
            // update_user_meta($this->ID, 'shipping_last_name', $last_name);
        }
	}

	public function update_email($email) {
        $email = sanitize_email($email);
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address.', LANG_ZONE)); // Use English for error message text if LANG_ZONE might not cover it
        }
		$user_data = array(
			'ID' => $this->ID,
			'user_email' => $email,
		);
		$result = wp_update_user($user_data);
         if (!is_wp_error($result)) {
             // Update object property
             $this->user_email = $email;
             // Optional: Update billing email meta as well if synchronization is desired
             // update_user_meta($this->ID, 'billing_email', $email);
         }
         return $result; // Return result from wp_update_user (ID or WP_Error)
	}

	public function update_avatar($attachment_id) {
		$old_avatar_id = get_user_meta($this->ID, 'avatar_id', true);
        $attachment_id = absint($attachment_id); // Ensure it's a positive integer

        // Check if the attachment exists
        if (get_post_status($attachment_id) !== 'inherit' && get_post_type($attachment_id) !== 'attachment') {
             return new WP_Error('invalid_attachment', __('Invalid avatar attachment ID.', LANG_ZONE)); // Use English for error message text
        }

		update_user_meta($this->ID, 'avatar_id', $attachment_id);
		$this->avatar_id = $attachment_id;
		$this->avatar_url = wp_get_attachment_url($this->avatar_id );

		// Only delete the old avatar if it's different from the new one and exists
		if ($old_avatar_id && $old_avatar_id != $attachment_id) {
			wp_delete_attachment($old_avatar_id, true); // Delete attachment and its thumbnails
		}
        return true;
	}

    public function update_erp_name($erp_name) {
        // Sanitize the input
        $erp_name = sanitize_text_field($erp_name);
        // Update the user meta
        update_user_meta($this->ID, 'erp_name', $erp_name);
        // Update the object property
        $this->erp_name = $erp_name;
    }

    public function update_erp_id($erp_id) {
    	
    }
	public function get_customer_info() {
		// Ensure avatar_url is set, even if empty
		if (!isset($this->avatar_url)) {
			$this->avatar_url = '';
	   }
		$customer_info = array(
			'ID' => $this->ID,
            'erp_name' => $this->erp_name,
			'user_login' => $this->user_login,
			'user_nicename' => $this->user_nicename,
			'user_email' => $this->user_email, // Core email
			'display_name' => $this->display_name,
			'first_name' => $this->first_name, // Core first name
			'last_name' => $this->last_name,   // Core last name
			'phone_number' => $this->phone_number, // General phone number
			'dob' => $this->dob,
			'gender' => $this->gender,
			// Include structured addresses directly
			'billing_address' => $this->billing_address,
			'shipping_address' => $this->shipping_address,
			'avatar_id' => $this->avatar_id,
			'avatar_url' => $this->avatar_url,
            'default_shipping_address' => $this->get_default_address(), // Get the default address
            'all_shipping_addresses' => $this->get_addresses(), // Get all addresses

		);
		return $customer_info;
	}

	/**
     * Retrieves a list of orders placed by this customer.
     *
     * @param int $limit Number of orders to retrieve. Default -1 (all).
     * @param int $offset Number of orders to skip (for pagination). Default 0.
     * @param string $orderby Column to order by. Default 'date_created'.
     * @param string $order Sort order ('ASC' or 'DESC'). Default 'DESC'.
     * @return array Array of order objects, empty array if none found.
     */
    public function get_orders( $limit = -1, $offset = 0, $orderby = 'date_created', $order = 'DESC' ) {
        // ... existing code ...
        if ( ! $this->ID ) {
            return []; // Cannot get orders for a non-existent user
        }

        $orders_table = $this->wpdb->prefix . 'custom_orders'; // Assuming you have this table

        // Check if the table exists
        if($this->wpdb->get_var("SHOW TABLES LIKE '$orders_table'") != $orders_table) {
            // Either log an error or return an empty array
             error_log("Custom orders table '{$orders_table}' does not exist.");
            return [];
        }


        // Sanitize order parameters
        $allowed_orderby = ['order_id', 'date_created', 'date_modified', 'status', 'total_amount']; // Update valid columns
        $orderby = in_array( strtolower( $orderby ), $allowed_orderby ) ? strtolower( $orderby ) : 'date_created';
        $order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
        $limit_clause = '';
        $offset_clause = '';

        // Prepare query parts safely
        $sql = "SELECT * FROM {$orders_table} WHERE user_id = %d ORDER BY {$orderby} {$order}";

        if ( $limit > 0 ) {
            $limit_clause = $this->wpdb->prepare( " LIMIT %d", absint($limit) );
        }
        if ( $offset > 0 ) {
             $offset_clause = $this->wpdb->prepare( " OFFSET %d", $offset );
        }

        // Combine the query
        $query = $this->wpdb->prepare($sql, $this->ID) . $limit_clause . $offset_clause;


        $orders = $this->wpdb->get_results( $query );


        return $orders ? $orders : [];
    }

    /**
     * Retrieves the total count of orders placed by this customer.
     *
     * @return int The total number of orders.
     */
    public function get_order_count() {
        // ... existing code ...
         if ( ! $this->ID ) {
            return 0; // Cannot get orders for a non-existent user
        }

        $orders_table = $this->wpdb->prefix . 'custom_orders'; // Assuming you have this table

         // Check if the table exists
        if($this->wpdb->get_var("SHOW TABLES LIKE '$orders_table'") != $orders_table) {
             error_log("Custom orders table '{$orders_table}' does not exist.");
            return 0;
        }

        $count = $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$orders_table} WHERE user_id = %d",
            $this->ID
        ) );

        return absint( $count );
    }

    /**
	 * Clears the user's cart data stored in user meta.
	 * Assumes cart data is stored under the '_user_cart' meta key.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear_cart() {
		if (!$this->ID) {
			return false; // Cannot clear cart if user ID is not set
		}
		return delete_user_meta($this->ID, '_user_cart');
	}

    /**
     * Get the list of all customer shipping addresses.
     *
     * @return array Array of address objects, empty if none.
     */
    public function get_addresses() {
        $addresses = get_user_meta($this->ID, $this->addresses_meta_key, true);
        return is_array($addresses) ? $addresses : [];
    }

    /**
     * Get the default shipping address.
     *
     * @return array|null The default address object or null if not found.
     */
    public function get_default_address() {
        $addresses = $this->get_addresses();
        foreach ($addresses as $address) {
            if (!empty($address['is_default'])) {
                return $address;
            }
        }
        // If no address is marked as default, return the first one (if any)
        return !empty($addresses) ? reset($addresses) : null;
    }

    /**
     * Add a new shipping address.
     *
     * @param array $address_data Address data (recipient_name, phone, location_name, ward_name, street, is_default).
     * @return bool|string Returns the new address ID on success, false on failure.
     */
    public function add_address($address_data) {
        if (!$this->ID || !is_array($address_data)) {
            return false;
        }

        $addresses = $this->get_addresses();
        $new_address_id = uniqid('addr_'); // Generate a simple unique ID

        // Sanitize input data
        $new_address = [
            'id' => $new_address_id,
            'recipient_name' => sanitize_text_field($address_data['recipient_name'] ?? ''),
            'recipient_phone' => sanitize_text_field($address_data['recipient_phone'] ?? ''),
            'location_name' => sanitize_text_field($address_data['location_name'] ?? ''), // Combined Province/City - District
            'ward_name' => sanitize_text_field($address_data['ward_name'] ?? ''),       // Ward/Commune name
            'street' => sanitize_text_field($address_data['street'] ?? ''),
            'is_default' => !empty($address_data['is_default']) ? 1 : 0,
        ];

        // If the new address is default, unset the default flag on other addresses
        if ($new_address['is_default']) {
            foreach ($addresses as &$existing_address) { // Use reference to modify directly
                $existing_address['is_default'] = 0;
            }
            unset($existing_address); // Important: unset reference after loop
        } elseif (empty($addresses)) {
             // If this is the first address, automatically set it as default
             $new_address['is_default'] = 1;
        }

        $addresses[$new_address_id] = $new_address; // Add the new address using its ID as the key

        if (update_user_meta($this->ID, $this->addresses_meta_key, $addresses)) {
            return $new_address_id;
        }

        return false;
    }

     /**
     * Update an existing shipping address.
     *
     * @param string $address_id The ID of the address to update.
     * @param array $address_data The new address data.
     * @return bool True on success, false on failure.
     */
    public function update_address($address_id, $address_data) {
        if (!$this->ID || empty($address_id) || !is_array($address_data)) {
            return false;
        }

        $addresses = $this->get_addresses();

        if (!isset($addresses[$address_id])) {
            return false; // Address not found
        }

        // Sanitize updated data
        $updated_data = [
            'recipient_name' => sanitize_text_field($address_data['recipient_name'] ?? $addresses[$address_id]['recipient_name']),
            'recipient_phone' => sanitize_text_field($address_data['recipient_phone'] ?? $addresses[$address_id]['recipient_phone']),
            'location_name' => sanitize_text_field($address_data['location_name'] ?? $addresses[$address_id]['location_name']),
            'ward_name' => sanitize_text_field($address_data['ward_name'] ?? $addresses[$address_id]['ward_name']),
            'street' => sanitize_text_field($address_data['street'] ?? $addresses[$address_id]['street']),
            'is_default' => !empty($address_data['is_default']) ? 1 : 0,
        ];

        // If this address is set as default, unset the default flag on others
        if ($updated_data['is_default']) {
             foreach ($addresses as $id => &$existing_address) {
                 if ($id !== $address_id) { // Don't unset its own flag yet
                    $existing_address['is_default'] = 0;
                 }
             }
             unset($existing_address);
        } else {
            // Check if trying to unset the default status of the only default address
            $default_count = 0;
            foreach ($addresses as $id => $addr) {
                if (!empty($addr['is_default'])) $default_count++;
            }
            // If this was the default address, and it's the only default one, prevent unsetting it (unless it's the only address overall)
            if (!empty($addresses[$address_id]['is_default']) && $default_count <= 1 && count($addresses) > 1) {
                 // Prevent unsetting default if it's the only default address (and there's more than one address total)
                 // You could return an error or just keep it default
                 $updated_data['is_default'] = 1; // Keep it default
                 // Or return false; // Indicate error
            }
        }


        // Update the address data
        $addresses[$address_id] = array_merge($addresses[$address_id], $updated_data);


        return update_user_meta($this->ID, $this->addresses_meta_key, $addresses);
    }

    /**
     * Delete a shipping address.
     *
     * @param string $address_id The ID of the address to delete.
     * @return bool True on success, false on failure.
     */
    public function delete_address($address_id) {
         if (!$this->ID || empty($address_id)) {
            return false;
        }
        $addresses = $this->get_addresses();

        if (!isset($addresses[$address_id])) {
            return false; // Not found
        }

        // Prevent deleting the default address if it's the only address
        if (!empty($addresses[$address_id]['is_default']) && count($addresses) <= 1) {
             return false; // Cannot delete the last default address
        }

         // If deleting the default address and other addresses exist, set another one as default
         if (!empty($addresses[$address_id]['is_default']) && count($addresses) > 1) {
             unset($addresses[$address_id]); // Delete it first
             // Find the first remaining address and set it as default
             if (!empty($addresses)) {
                 $first_key = array_key_first($addresses);
                 $addresses[$first_key]['is_default'] = 1;
             }
         } else {
             unset($addresses[$address_id]); // Delete non-default address
         }


        return update_user_meta($this->ID, $this->addresses_meta_key, $addresses);
    }

     /**
     * Set an address as the default shipping address.
     *
     * @param string $address_id The ID of the address to set as default.
     * @return bool True on success, false on failure.
     */
    public function set_default_address($address_id) {
        if (!$this->ID || empty($address_id)) {
            return false;
        }
        $addresses = $this->get_addresses();

        if (!isset($addresses[$address_id])) {
            return false; // Not found
        }

        // Unset default flag on all addresses
        foreach ($addresses as &$addr) { // Use reference
            $addr['is_default'] = 0;
        }
        unset($addr); // Unset reference

        // Set the default flag for the selected address
        $addresses[$address_id]['is_default'] = 1;

        return update_user_meta($this->ID, $this->addresses_meta_key, $addresses);
    }

    /**
     * Thêm sản phẩm vào danh sách yêu thích.
     *
     * @param string $product_id ID của sản phẩm.
     * @return bool True nếu thành công, false nếu thất bại.
     */
    public function addToWishlist($product_id) {
        if (empty($product_id) || !$this->ID) {
            return false;
        }

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';

        // Kiểm tra xem đã thích sản phẩm này chưa
        if ($this->hasLikedProduct($product_id)) {
            return true; // Đã thích rồi, không cần thêm lại
        }

        $result = $wpdb->insert(
            $likes_table,
            array(
                'user_id' => $this->ID,
                'product_id' => $product_id,
                'date_liked' => current_time('mysql') // GMT time
            ),
            array(
                '%d', // user_id
                '%s', // product_id
                '%s'  // date_liked
            )
        );

        return (bool)$result;
    }

    /**
     * Xóa sản phẩm khỏi danh sách yêu thích.
     *
     * @param string $product_id ID/SKU của sản phẩm.
     * @return bool True nếu thành công, false nếu thất bại.
     */
    public function removeFromWishlist($product_id) {
        if (empty($product_id) || !$this->ID) {
            return false;
        }

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';

        $result = $wpdb->delete(
            $likes_table,
            array(
                'user_id' => $this->ID,
                'product_id' => $product_id
            ),
            array(
                '%d', // user_id
                '%s'  // product_id
            )
        );

        return (bool)$result;
    }

    /**
     * Lấy danh sách các sản phẩm trong wishlist.
     *
     * @param int $limit Số lượng sản phẩm tối đa cần lấy.
     * @param int $offset Vị trí bắt đầu lấy.
     * @return Product[] Mảng các đối tượng Product.
     */
    public function getWishlistItems($limit = 10, $offset = 0) {
        if (!$this->ID) {
            return [];
        }

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';
        $erp_api = new ERP_API_Handler(defined('FAKE_DATA') && FAKE_DATA); // Khởi tạo ERP_API_Handler

        $limit_clause = '';
        if ($limit > 0) {
            $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", absint($limit), absint($offset));
        }

        $liked_product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT product_id FROM {$likes_table} WHERE user_id = %d ORDER BY date_liked DESC {$limit_clause}",
            $this->ID
        ));

        if (empty($liked_product_ids)) {
            return [];
        }

        $wishlist_products = [];
        foreach ($liked_product_ids as $product_id_str) {
            $product_data = $erp_api->get_product($product_id_str); // Lấy dữ liệu sản phẩm từ ERP
            if ($product_data && !is_wp_error($product_data)) {
                $wishlist_products[] = new Product($product_data);
            }
        }

        return $wishlist_products;
    }
    
    /**
     * Lấy tổng số sản phẩm trong wishlist.
     *
     * @return int Tổng số sản phẩm.
     */
    public function getWishlistCount() {
        if (!$this->ID) {
            return 0;
        }
        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT product_id) FROM {$likes_table} WHERE user_id = %d",
            $this->ID
        ));
        return absint($count);
    }


    /**
     * Kiểm tra xem khách hàng đã yêu thích một sản phẩm cụ thể chưa.
     *
     * @param string $product_id ID của sản phẩm.
     * @return bool True nếu đã thích, false nếu chưa.
     */
    public function hasLikedProduct($product_id) {
        if (empty($product_id) || !$this->ID) {
            return false;
        }

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$likes_table} WHERE user_id = %d AND product_id = %s",
            $this->ID,
            $product_id
        ));

        return $count > 0;
    }

    public function getProductLikeTotal($product_id) {
        if (empty($product_id)) {
            return 0;
        }

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$likes_table} WHERE product_id = %s",
            $product_id
        ));

        return absint($count);
    }
	public function getLoyalty(){
		if (!$this->erp_name) return null;
		$erp = new ERP_API_Client();

		return $erp->get_customer_loyalty($this->erp_name);
	}
}// End of Customer class