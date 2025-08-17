<?php
function my_theme_create_keywords_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'keywords';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$table_name} (
    id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
    keyword VARCHAR(255) NOT NULL,
    count INT(11) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY  (id),
      UNIQUE KEY keyword (keyword)
  ) {$charset_collate};";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

function my_theme_setup_order_database() {
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql_orders_table = "CREATE TABLE {$table_prefix}custom_orders (
        order_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
        order_key VARCHAR(64) NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        currency CHAR(3) NOT NULL DEFAULT 'VND',
        total_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        subtotal_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        shipping_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        tax_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(100) NULL DEFAULT NULL,
        payment_method_title VARCHAR(255) NULL DEFAULT NULL,
        transaction_id VARCHAR(255) NULL DEFAULT NULL,
        customer_ip_address VARCHAR(100) NULL DEFAULT NULL,
        customer_user_agent TEXT NULL DEFAULT NULL,
        customer_note TEXT NULL DEFAULT NULL,
        admin_note TEXT NULL DEFAULT NULL,
        billing_first_name VARCHAR(100) NULL DEFAULT NULL,
        billing_last_name VARCHAR(100) NULL DEFAULT NULL,
        billing_company VARCHAR(255) NULL DEFAULT NULL,
        billing_address_1 VARCHAR(255) NULL DEFAULT NULL,
        billing_address_2 VARCHAR(255) NULL DEFAULT NULL,
        billing_city VARCHAR(100) NULL DEFAULT NULL,
        billing_state VARCHAR(100) NULL DEFAULT NULL,
        billing_postcode VARCHAR(20) NULL DEFAULT NULL,
        billing_country CHAR(2) NULL DEFAULT NULL,
        billing_email VARCHAR(255) NULL DEFAULT NULL,
        billing_phone VARCHAR(50) NULL DEFAULT NULL,
        shipping_first_name VARCHAR(100) NULL DEFAULT NULL,
        shipping_last_name VARCHAR(100) NULL DEFAULT NULL,
        shipping_company VARCHAR(255) NULL DEFAULT NULL,
        shipping_address_1 VARCHAR(255) NULL DEFAULT NULL,
        shipping_address_2 VARCHAR(255) NULL DEFAULT NULL,
        shipping_city VARCHAR(100) NULL DEFAULT NULL,
        shipping_state VARCHAR(100) NULL DEFAULT NULL,
        shipping_postcode VARCHAR(20) NULL DEFAULT NULL,
        shipping_country CHAR(2) NULL DEFAULT NULL,
        shipping_phone VARCHAR(50) NULL DEFAULT NULL,
        date_created DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        date_created_gmt DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        date_modified DATETIME NULL DEFAULT NULL,
        date_modified_gmt DATETIME NULL DEFAULT NULL,
        date_paid_gmt DATETIME NULL DEFAULT NULL,
        date_completed_gmt DATETIME NULL DEFAULT NULL,
          PRIMARY KEY  (order_id),
          KEY user_id (user_id),
          KEY status (status(20)),
          KEY date_created (date_created),
          KEY order_key (order_key(10))
    ) $charset_collate;";

	// --- Định nghĩa SQL cho bảng wp_custom_order_items (Giống hệt như code cho plugin) ---
	$sql_order_items_table = "CREATE TABLE {$table_prefix}custom_order_items (
        order_item_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        order_item_name TEXT NOT NULL,
        order_item_type VARCHAR(50) NOT NULL DEFAULT 'line_item',
        product_id VARCHAR(30) NOT NULL,
        variation_id VARCHAR(30) NULL DEFAULT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        unit_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        line_subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        line_tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
          PRIMARY KEY  (order_item_id),
          KEY order_id (order_id),
          KEY order_item_type (order_item_type(20))
    ) $charset_collate;";

	$sql_order_meta_table = "CREATE TABLE {$table_prefix}custom_order_meta (
        meta_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        meta_key VARCHAR(255) NULL DEFAULT NULL,
        meta_value LONGTEXT NULL DEFAULT NULL,
          PRIMARY KEY  (meta_id),
          KEY order_id (order_id),
          KEY meta_key (meta_key(191))
    ) $charset_collate;";

	dbDelta( $sql_orders_table );
	dbDelta( $sql_order_items_table );
	dbDelta( $sql_order_meta_table );
}
function my_theme_setup_review_like_database() {
	global $wpdb;
	$table_prefix = $wpdb->prefix;
	$charset_collate = $wpdb->get_charset_collate();
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	$sql_reviews_table = "CREATE TABLE {$table_prefix}product_reviews (
      review_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      product_id varchar(50) NOT NULL,
      user_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
      author_name VARCHAR(255) NULL DEFAULT NULL,
      author_email VARCHAR(100) NULL DEFAULT NULL,
      rating TINYINT UNSIGNED NOT NULL DEFAULT 0,
      content TEXT NULL DEFAULT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'hold',
      parent_id BIGINT(20) UNSIGNED NULL DEFAULT NULL,
      date_created DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
      date_created_gmt DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
        PRIMARY KEY  (review_id),
        KEY product_id (product_id),
        KEY user_id (user_id),
        KEY status_date (status, date_created_gmt),
        KEY parent_id (parent_id)
    ) {$charset_collate};";

	$sql_likes_table = "CREATE TABLE {$table_prefix}product_likes (
      like_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      product_id varchar(50) NOT NULL,
      user_id BIGINT(20) UNSIGNED NOT NULL,
      date_liked DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (like_id),
        UNIQUE KEY user_product_like (user_id, product_id),
        KEY product_id (product_id)
    ) {$charset_collate};";

	dbDelta( $sql_reviews_table );
	dbDelta( $sql_likes_table );
}
function my_theme_run_all_db_setups() {
	my_theme_create_keywords_table();
	my_theme_setup_order_database();
  my_theme_setup_review_like_database();
}
add_action( 'after_switch_theme', 'my_theme_run_all_db_setups' );