<?php

class CustomerManager {

    /**
     * Static cache for Customer objects.
     * Stores instances by user ID.
     * @var array<int, Customer>
     */
    private static $instances = [];

    /**
     * Get or create a Customer object for a given user ID.
     *
     * @param int|WP_User $user User ID or WP_User object. Defaults to current user ID if null.
     * @return Customer|false Customer object on success, false if user does not exist or ID is invalid.
     */
    public static function get_customer($user = null) {
        if (null === $user) {
            $user = get_current_user_id();
        } elseif ($user instanceof WP_User) {
             $user = $user->ID;
        }

        $user_id = absint($user); // Ensure user ID is a positive integer

        if (empty($user_id)) {
            // No user ID provided or invalid, and no current user logged in.
            // You might return null, false, or a default "guest" object here.
            return false;
        }

        // Check if the instance is already in the cache
        if (isset(self::$instances[$user_id])) {
            return self::$instances[$user_id];
        }

        // Instance not in cache, create a new one
        $customer = new Customer($user_id);

        // Check if the user actually exists before caching
        if (!$customer->exists()) { // Use WP_User's exists() method
            // User not found, return false and do not cache
            return false;
        }

        // Store the new instance in the cache
        self::$instances[$user_id] = $customer;

        return $customer;
    }

    /**
     * Get the current logged-in Customer object.
     *
     * @return Customer|false Customer object on success, false if no user is logged in.
     */
    public static function get_current_customer() {
        return self::get_customer(get_current_user_id());
    }

    // You might add other utility methods here if needed
}

// Make sure your Customer class is defined before this or included.
// Example: include_once 'class-customer.php';