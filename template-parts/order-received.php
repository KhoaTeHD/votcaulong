<?php
/**
 * Template for displaying the Order Received page (via virtual URL).
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get order details from query variables
$order_id  = get_query_var( 'order_id' );
$order_key = get_query_var( 'order_key' ); // Key from URL for validation

// Basic validation
if ( empty( $order_id ) || ! is_numeric( $order_id ) ) {
	wp_redirect( home_url( '/' ) );
	exit;
}

// Instantiate the order class
$vcl_order = new VCL_Order($order_id);
$order_data = $vcl_order->get_order_with_meta( (int) $order_id );
$stored_key = $order_data->order_key ?? null;
$erp_order_code = $order_data->erp_order_code;

// --- Security Check: Verify Order Key ---
if ( ! $order_data || empty( $order_key ) || $stored_key !== $order_key ) {
	get_header();
	?>
    <div class="container">
        <div class="post-content bg-white">
            <section class="bg-white shadow-sm py-5 px-3">
                <div class="container vcl-order-received-page vcl-page-padding">
                    <div class="woocommerce">
                        <h5 class="woocommerce-error">
							<?php esc_html_e( 'Sorry, this order is invalid or you do not have permission to access it.', LANG_ZONE ); ?>
                        </h5>
                    </div>
                </div>
            </section>
        </div>
    </div>
	<?php
	get_footer();
	exit;
}
get_header();
global $order_statuses;

if (isset($_GET['payoo_status']) && $_GET['payoo_status'] === '0' && isset($_GET['checksum_valid']) && $_GET['checksum_valid'] === '1') {
	$error_msg = isset($_GET['msg']) ? $_GET['msg'] : '';
	$title = '<h3 class="title text-warning"><i class="bi bi-exclamation-triangle-fill fs-2"></i> ' . esc_html__('Order failed!', LANG_ZONE) . '</h3>';
} else {
	$title = '<h3 class="title text-success"><i class="bi bi-bag-check-fill fs-2"></i> ' . esc_html__('Order placed successfully!', LANG_ZONE) . '</h3>';
}
?>
<div class="container">
    <div class="post-content bg-white">
        <section class="bg-white shadow-sm py-3 px-3">
            <div class="container vcl-order-received-page vcl-page-padding">
                <div class="woocommerce">
                    <div class="woocommerce-order">
                        <div class="section-header bg-none px-0">
							<?php echo $title; ?>
                        </div>
                        <p class="thankyou_msg fs-6">
							<?php
							$user_id = get_current_user_id();
							$customer = new Customer($user_id);
							$customer_info = $customer->get_customer_info();
							$thank_you_text = sprintf(
							// Translators: %s is "Mr"/"Ms", %s is customer name.
								__( 'Thank you, %s <b>%s</b>, for giving VotCauLong Shop the opportunity to serve you.', LANG_ZONE ),
								($customer_info['gender'] == 'male' ? esc_html__('Mr.', LANG_ZONE) : esc_html__('Ms.', LANG_ZONE)),
								esc_html($order_data->fullname ?? esc_html__('you', LANG_ZONE))
							);
							echo $thank_you_text;
							?>
                        </p>
                        <div class="order-completed border rounded p-3 fs-6">
                            <div class="d-flex justify-content-between">
                                <h5>
									<?php esc_html_e('Order:', LANG_ZONE); ?>
                                    <strong>#<?php echo esc_html($erp_order_code ?? $order_data->order_id); ?></strong>
                                </h5>
                                <div>
                                    <a href="<?php echo esc_url($order_data->order_link); ?>" class="btn btn-sm btn-outline-primary"><?php esc_html_e('Manage order', LANG_ZONE); ?></a>
                                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-sm btn-outline-danger"><?php esc_html_e('Cancel', LANG_ZONE); ?></a>
                                </div>
                            </div>
                            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

                                <li class="woocommerce-order-overview__date date">
                                    <strong><?php esc_html_e('Order date:', LANG_ZONE); ?></strong>
									<?php echo esc_html($order_data->date_created ? date_i18n(get_option('date_format'), strtotime($order_data->date_created)) : 'N/A'); ?>
                                </li>
								<?php if (property_exists($order_data, 'shipping_first_name') && !empty($order_data->shipping_first_name)) : ?>
                                    <li class="woocommerce-order-overview__customer customer">
                                        <strong><?php esc_html_e('Customer name:', LANG_ZONE); ?></strong>
										<?php echo esc_html($order_data->shipping_first_name . ' ' . $order_data->shipping_last_name); ?>
                                    </li>
								<?php endif; ?>
								<?php if (property_exists($order_data, 'shipping_phone') && !empty($order_data->shipping_phone)) : ?>
                                    <li class="woocommerce-order-overview__phone phone">
                                        <strong><?php esc_html_e('Phone number:', LANG_ZONE); ?></strong>
										<?php echo esc_html($order_data->shipping_phone); ?>
                                    </li>
								<?php endif; ?>
								<?php if (property_exists($order_data, 'billing_email') && !empty($order_data->billing_email)) : ?>
                                    <li class="woocommerce-order-overview__email email">
                                        <strong><?php esc_html_e('Email:', LANG_ZONE); ?></strong>
										<?php echo esc_html($order_data->billing_email); ?>
                                    </li>
								<?php endif; ?>

								<?php if ($order_data->payment_method !== 'pickup') : ?>
                                    <li class="woocommerce-order-overview__address address">
                                        <strong><?php esc_html_e('Shipping address:', LANG_ZONE); ?></strong>
										<?php
										$address1 = $order_data->shipping_address_1;
										$address2 = $order_data->shipping_address_2;
										$city = $order_data->shipping_city;
										$ward = explode('-', $order_data->shipping_state);

										$full_address = $address1 . ', ' . ($address2 ? $address2 . ', ' : '') . $ward[0] . ', ' . $city;
										echo esc_html($full_address);
										?>
                                    </li>
								<?php endif; ?>

                                <li class="woocommerce-order-overview__total total">
                                    <strong><?php esc_html_e('Total:', LANG_ZONE); ?></strong>
                                    <span class="fs-6 fw-bold text-danger"><?php echo priceFormater($order_data->total_amount ?? 0); ?></span>
                                </li>

								<?php if (property_exists($order_data, 'payment_method_title') && !empty($order_data->payment_method_title)) : ?>
                                    <li class="woocommerce-order-overview__payment-method method">
                                        <strong><?php esc_html_e('Payment method:', LANG_ZONE); ?></strong>
										<?php echo esc_html($order_data->payment_method_title); ?>
                                    </li>
								<?php endif; ?>
								<?php
								$shipping_method = $order_data->shipping_method;
								if (isset($order_data->shipping_method) && $shipping_method === 'pickup') {
									$pickup_store_id = $order_data->pickup_store_id;
									$pickup_store = get_store_by_id($pickup_store_id);
									$pickup_store_address = $pickup_store['address'] ?? '';
									$pickup_store_name = $pickup_store['name'] ?? '';
									if (!empty($pickup_store_name)) {
										echo '<li><strong>' . esc_html__('Delivery method:', LANG_ZONE) . '</strong> ' . esc_html__('Pick up at store', LANG_ZONE);
										echo '<span><br><strong>' . esc_html__('Store:', LANG_ZONE) . '</strong> ' . esc_html($pickup_store_name);
										if (!empty($pickup_store_address)) {
											echo '<br>' . esc_html__('Address:', LANG_ZONE) . ' ' . nl2br(esc_html($pickup_store_address));
										}
										echo '</span></li>';
									} else {
										echo '<li><strong>' . esc_html__('Delivery method:', LANG_ZONE) . '</strong> ' . esc_html__('Pick up at store (Store details not available)', LANG_ZONE) . '</li>';
									}
								} elseif (isset($shipping_method) && $shipping_method === 'delivery') {
									echo '<li><strong>' . esc_html__('Delivery method:', LANG_ZONE) . '</strong> ' . esc_html__('Home delivery', LANG_ZONE) . '</li>';
									echo '<li><strong>' . esc_html__('Shipping service:', LANG_ZONE) . '</strong> ' . esc_html($order_data->shipping_service) . '</li>';
								}
								?>
                                <li class="woocommerce-order-overview__status status">
                                    <strong><?php esc_html_e('Status:', LANG_ZONE); ?></strong>
									<?php echo $order_statuses[$order_data->status] ?? ''; ?>
                                </li>
                                <li class="woocommerce-order-overview__note note">
                                    <strong><?php esc_html_e('Other requests:', LANG_ZONE); ?> </strong>
									<?php echo esc_html($order_data->customer_note); ?>
                                </li>

                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
<?php
get_footer();
?>
