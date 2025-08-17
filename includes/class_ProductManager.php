<?php



class ProductManager {

    /**
     * Static cache for Product objects within the current request.
     * This is the fastest layer, checked first after the very initial fetch from persistent cache.
     * @var array<string|int, Product|false> Stores Product objects or false if fetching failed.
     */
    private static $instances = [];

    // Define a cache group for WP Object Cache/Transients
    const CACHE_GROUP = 'vcl_products';
    const TRANSIENT_EXPIRATION = HOUR_IN_SECONDS; // Cache product data for 1 hour

    /**
     * Get or create a Product object for a given product ID using a multi-layer cache.
     * Checks static cache, then WP Object Cache, then Transients, then fetches from ERP.
     *
     * @param string|int $product_id The ID (or SKU) of the product.
     * @return Product|false Product object on success, false if product data cannot be fetched or ID is invalid.
     */
    public static function get_product($product_id, $clear_cache = true, $get_stock = true) {
        // 1. Sanitize and validate product ID
        $product_id = sanitize_text_field($product_id); // Hoặc absint() nếu chắc chắn là ID số

        if (empty($product_id)) {
            return false;
        }

        // 2. Check Static Cache (fastest, per request, after first persistent fetch)
        // Using array_key_exists to differentiate between null and false
        if (array_key_exists($product_id, self::$instances) && !$clear_cache) {
            return self::$instances[$product_id];
        }

        // --- Cache Hierarchy Check ---
        // 3. Check WP Object Cache (persistent if backend like Memcached/Redis is installed)
        // This is the preferred persistent cache layer if available
        $product_object = wp_cache_get($product_id, self::CACHE_GROUP);

        if (false === $product_object || $clear_cache) {
            // 4. WP Object Cache Miss - Check Transients (persistent, DB-backed fallback)
            // Transients are used if Object Cache backend is not available, or as a secondary cache.
            $transient_key = self::CACHE_GROUP . '_' . md5($product_id); // Generate a safe transient key
            $product_data = get_transient($transient_key);

            if (false === $product_data || $clear_cache) {
                // 5. Transient Miss - Fetch from ERP (most expensive operation)
                try {
                    // Instantiate or get your ERP_API_Handler instance
                    // Ideally, this should be a Singleton or managed externally
                    $erp_api = new ERP_API_Client();
                    $product_data = $erp_api->get_product($product_id, $get_stock);

                    if (is_wp_error($product_data) || empty($product_data) || !is_array($product_data) || !isset($product_data['item_code'])) {
                        // Fetch failed from ERP - Cache false/error result
                        $product_data = false; // Store false to indicate failure
                        // Cache this failure result in Transient for a short time to avoid hammering ERP
                        set_transient($transient_key, $product_data, 5 * MINUTE_IN_SECONDS); // Cache failure for 5 minutes
                        // Also cache failure in Object Cache if available
                        wp_cache_set($product_id, $product_data, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);

                        self::$instances[$product_id] = false; // Cache failure in Static Cache
                        return false; // Return false immediately on fetch failure
                    }

                    // 6. ERP Fetch Success - Cache the raw data in Transient (for next requests if Object Cache is off)
                    set_transient($transient_key, $product_data, self::TRANSIENT_EXPIRATION);

                } catch (Exception $e) {
                    // Handle exceptions during API call or handler initialization
                    error_log("Error fetching product data via ERP for ID {$product_id}: " . $e->getMessage());
                    $product_data = false; // Indicate failure
                     // Cache failure result in Transient and Object Cache
                     set_transient($transient_key, $product_data, 5 * MINUTE_IN_SECONDS);
                     wp_cache_set($product_id, $product_data, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);

                    self::$instances[$product_id] = false; // Cache failure in Static Cache
                    return false; // Return false on exception
                }
            }
             // If we reached here, product_data is either valid data from Transient or false from Transient/ERP fetch failure
             if (false === $product_data || empty($product_data) || !isset($product_data['item_code'])) {
                  self::$instances[$product_id] = false; // Cache failure in Static Cache if not already set
                  return false;
             }

            // If data was found in Transient, or just fetched from ERP successfully,
            // create the Product object and cache it in WP Object Cache.
            $product_object = new Product($product_data);

            // 7. Cache the Product Object in WP Object Cache (for future requests)
            // This is faster to retrieve than raw data from Transient + new Product()
            wp_cache_set($product_id, $product_object, self::CACHE_GROUP, self::TRANSIENT_EXPIRATION);

        }
        // --- End Cache Hierarchy Check ---

        // At this point, $product_object holds either the cached Product object (from Object Cache)
        // or the newly created Product object (from Transient data or ERP fetch).
        // $product_object could also be false if fetch failed and false was cached.

        // 8. Store the result (Product object or false) in the Static Cache (for repeated calls in *this* request)
        self::$instances[$product_id] = $product_object;

        return $product_object;
    }

    /**
     * Add a Product object to the static cache manually (optional).
     * Useful if you create a Product object differently (e.g., from a list query that returns full data)
     * and want to ensure it's cached for future get_product() calls within the request.
     * Does *not* cache in persistent storage.
     *
     * @param Product $product The Product object to cache.
     * @return bool True on success, false if product ID is missing or not a Product object.
     */
    public static function cache_product_in_request(Product $product): bool {
        if (!($product instanceof Product)) return false;
        $product_id = $product->getId();
        if (empty($product_id)) {
            return false;
        }
        self::$instances[$product_id] = $product;
         // Optionally, cache in persistent storage here as well if you have the raw data
         // wp_cache_set($product_id, $product, self::CACHE_GROUP, self::TRANSIENT_EXPIRATION);
         // $transient_key = self::CACHE_GROUP . '_' . md5($product_id);
         // set_transient($transient_key, $product->get_raw_data(), self::TRANSIENT_EXPIRATION); // Requires Product to have a get_raw_data() method

        return true;
    }

    /**
     * Invalidate the cache for a specific product.
     *
     * @param string|int $product_id The ID of the product.
     */
    public static function invalidate_product_cache($product_id): void {
        $product_id = sanitize_text_field($product_id);
        if (empty($product_id)) return;

        // Remove from static cache
        unset(self::$instances[$product_id]);

        // Remove from WP Object Cache
        wp_cache_delete($product_id, self::CACHE_GROUP);

        // Remove from Transients
        $transient_key = self::CACHE_GROUP . '_' . md5($product_id);
        delete_transient($transient_key);

        // Note: If you cache related lists etc., you might need to invalidate those too.
    }


}
