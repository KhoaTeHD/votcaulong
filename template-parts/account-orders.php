<?php
/**
 * Account Orders Template
 *
 * Displays customer orders list OR details using custom VCL_Order and Customer classes.
 * Assumes user is already logged in (checked by parent template like page-account.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $order_statuses;

// Get the query variables and current user ID
$view_order_id = get_query_var('view_order_id');
$current_user_id = get_current_user_id();

// --- Display Order Details ---
if ( ! empty( $view_order_id ) && is_numeric( $view_order_id ) ) :

	$order_id_to_view = absint( $view_order_id );

	// Instantiate necessary classes
	if ( class_exists('VCL_Order') ) {
		$vcl_order = new VCL_Order();
		$order_data = $vcl_order->get_order( $order_id_to_view );
		$order_items = $vcl_order->get_order_items( $order_id_to_view );
		$erp_order_code = $vcl_order->get_order_meta($order_id_to_view,'_erp_order_code');

		// Security Check
		if ( ! $order_data || $order_data->user_id != $current_user_id ) {
			echo '<p>' . esc_html__( 'Invalid order or you do not have permission to view this order.', LANG_ZONE ) . '</p>';
		} else {
			$make_payment_btn = '';
			if ($order_data->status == 'pending-payment') {
				$make_payment_btn = '<button data-orderid="'.$order_id_to_view.'" class="btn btn-danger btn-sm text-white order-make-payment" >' . esc_html__('Make payment', LANG_ZONE) . '</button>';
			}
			?>
            <h5 class="title" data-name="<?php echo esc_attr($erp_order_code); ?>">
				<?php printf( esc_html__( 'Order Details #%s', LANG_ZONE ), $erp_order_code ? $erp_order_code : $order_data->order_id ); ?>
            </h5>
            <p><?php printf( esc_html__( 'Order date: %s', LANG_ZONE ), date_i18n( get_option( 'date_format' ), strtotime( $order_data->date_created ) ) ); ?></p>
            <p><?php printf( '<b>' . esc_html__('Status:', LANG_ZONE) . '</b> %s', $order_statuses[$order_data->status] ); ?> <?php echo $make_payment_btn;?></p>
            <p><?php printf( '<b>' . esc_html__('Payment method:', LANG_ZONE) . '</b> %s', esc_html($order_data->payment_method_title) ); ?></p>
			<?php
			// Shipping method
			$shipping_method = $vcl_order->get_order_meta($order_id_to_view, '_shipping_method', true);
			if ( isset($shipping_method) && $shipping_method === 'pickup' ) {
				$pickup_store_id = $vcl_order->get_order_meta($order_id_to_view, '_pickup_store_id', true);
				$pickup_store = get_store_by_id($pickup_store_id);
				$pickup_store_address = $pickup_store['address'] ?? '';
				$pickup_store_name = $pickup_store['name'] ?? '';
				if ( !empty($pickup_store_name) ) {
					echo '<p><strong>' . esc_html__( 'Delivery method:', LANG_ZONE ) . '</strong> ' . esc_html__( 'Pick up at store', LANG_ZONE ) . '</p>';
					echo '<p><strong>' . esc_html__( 'Store:', LANG_ZONE ) . '</strong> ' . esc_html($pickup_store_name);
					if (!empty($pickup_store_address)) {
						echo '<br>' . esc_html__('Address:', LANG_ZONE) . ' ' . nl2br(esc_html($pickup_store_address));
					}
					echo '</p>';
				} else {
					echo '<p><strong>' . esc_html__( 'Delivery method:', LANG_ZONE ) . '</strong> ' . esc_html__( 'Pick up at store (Store details not available)', LANG_ZONE ) . '</p>';
				}
			} elseif (isset($shipping_method) && $shipping_method === 'delivery') {
				echo '<p><strong>' . esc_html__( 'Delivery method:', LANG_ZONE ) . '</strong> ' . esc_html__( 'Home delivery', LANG_ZONE ) . '</p>';
				echo '<p><strong>' . esc_html__( 'Shipping service:', LANG_ZONE ) . '</strong> ' . esc_html($vcl_order->get_order_meta($order_id_to_view, '_shipping_service')) . '</p>';
			}
			if ($order_data->customer_note) {
				echo '<p><strong>' . esc_html__( 'Note:', LANG_ZONE ) . '</strong> ' . nl2br(esc_html($order_data->customer_note)) . '</p>';
			}

			$company_invoice = $vcl_order->get_order_meta($order_id_to_view, '_company_invoice_details', true);
			if ($company_invoice) {
				echo '<p><strong>' . esc_html__( 'Company invoice:', LANG_ZONE ) . '</strong></p>';
				$company_invoice = unserialize($company_invoice);
				echo '<div class="company_info my-2 p-2 border rounded"><p>' . esc_html__('Company name:', LANG_ZONE) . ' ' . esc_html($company_invoice['name']) . '</p>';
				echo '<p>' . esc_html__('Tax code:', LANG_ZONE) . ' ' . esc_html($company_invoice['tax_number']) . '</p>';
				echo '<p>' . esc_html__('Address:', LANG_ZONE) . ' ' . nl2br(esc_html($company_invoice['address'])) . '</p></div>';
			}
			?>

            <div class="order-details-wrapper">

                <section class="order-items">
                    <div class="table-responsive">
                        <table class="table table-bordered shop_table order_details">
                            <thead>
                            <tr>
                                <th class="product-name"><?php esc_html_e( 'Product', LANG_ZONE ); ?></th>
                                <th class="product-total text-end"><?php esc_html_e( 'Total', LANG_ZONE ); ?></th>
                            </tr>
                            </thead>
                            <tbody>
							<?php if ( ! empty( $order_items ) ) : ?>
								<?php foreach ( $order_items as $item ) :
									$item_name = $item->order_item_name;
									$product_sku = $item->product_id;
									$product_link = '#';
									?>
                                    <tr>
                                        <td class="product-name">
                                            <a href="<?php echo esc_url($product_link); ?>"><?php echo esc_html($item_name); ?></a>
                                            <strong class="product-quantity">&times;&nbsp;<?php echo esc_html($item->quantity); ?></strong>
                                        </td>
                                        <td class="product-total text-end">
											<?php
											if ( function_exists('priceFormater') ) {
												echo priceFormater( $item->line_total );
											} else {
												echo esc_html( number_format( $item->line_total, 0, ',', '.' ) . ' ' . $order_data->currency );
											}
											?>
                                        </td>
                                    </tr>
								<?php endforeach; ?>
							<?php else: ?>
                                <tr><td colspan="2"><?php esc_html_e( 'No products in this order.', LANG_ZONE ); ?></td></tr>
							<?php endif; ?>
                            </tbody>
                            <tfoot>
							<?php if ( isset($order_data->subtotal_amount) ) { ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Subtotal:', LANG_ZONE ); ?></th>
                                    <td class="text-end"><?php echo function_exists('priceFormater') ? priceFormater( $order_data->subtotal_amount ) : number_format( $order_data->subtotal_amount ); ?></td>
                                </tr>
							<?php }
							if ( isset($order_data->shipping_total) && $order_data->shipping_total > 0 ) { ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Shipping fee:', LANG_ZONE ); ?></th>
                                    <td class="text-end"><?php echo function_exists('priceFormater') ? priceFormater( $order_data->shipping_total, $order_data->currency ) : number_format( $order_data->shipping_total ); ?></td>
                                </tr>
							<?php }
							$voucher_code = $vcl_order->get_order_meta($order_id_to_view, '_voucher_code', true);
							if ( !empty($voucher_code) ) { ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Discount (Voucher):', LANG_ZONE ); ?></th>
                                    <td class="text-end"><?php echo esc_html($voucher_code); ?></td>
                                </tr>
							<?php }
							if ( isset($order_data->tax_total) && $order_data->tax_total > 0 ) { ?>
                                <tr>
                                    <th scope="row"><?php esc_html_e( 'Tax:', LANG_ZONE ); ?></th>
                                    <td class="text-end"><?php echo function_exists('priceFormater') ? priceFormater( $order_data->tax_total ) : number_format( $order_data->tax_total ); ?></td>
                                </tr>
							<?php } ?>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Grand Total:', LANG_ZONE ); ?></th>
                                <td class="text-end text-danger fw-bold"><?php echo function_exists('priceFormater') ? priceFormater( $order_data->total_amount ) : number_format( $order_data->total_amount ); ?></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="order-addresses row">
                    <div class="col-md-6">
                        <h6><?php esc_html_e( 'Billing address', LANG_ZONE ); ?></h6>
                        <address>
							<?php
							echo esc_html($order_data->billing_first_name . ' ' . $order_data->billing_last_name) . '<br>';
							if (!empty($order_data->billing_company)) echo esc_html($order_data->billing_company) . '<br>';
							echo esc_html($order_data->billing_address_1) . '<br>';
							if (!empty($order_data->billing_address_2)) echo esc_html($order_data->billing_address_2) . '<br>';
							echo esc_html($order_data->billing_city) . '<br>';
							if (!empty($order_data->billing_state)) echo esc_html($order_data->billing_state) . '<br>';
							if (!empty($order_data->billing_postcode)) echo esc_html($order_data->billing_postcode) . '<br>';
							if (!empty($order_data->billing_country)) echo esc_html($order_data->billing_country) . '<br>';
							echo esc_html($order_data->billing_phone) . '<br>';
							echo esc_html($order_data->billing_email);
							?>
                        </address>
                    </div>
					<?php
					$has_shipping_address = !empty($order_data->shipping_address_1);
					if ( $has_shipping_address ):
						?>
                        <div class="col-md-6">
                            <h6><?php esc_html_e( 'Shipping address', LANG_ZONE ); ?></h6>
                            <address>
								<?php
								echo esc_html($order_data->shipping_first_name . ' ' . $order_data->shipping_last_name) . '<br>';
								if (!empty($order_data->shipping_company)) echo esc_html($order_data->shipping_company) . '<br>';
								echo esc_html($order_data->shipping_address_1) . '<br>';
								if (!empty($order_data->shipping_address_2)) echo esc_html($order_data->shipping_address_2) . '<br>';
								echo esc_html($order_data->shipping_city) . '<br>';
								echo esc_html($order_data->shipping_state) . '<br>';
								echo esc_html($order_data->shipping_postcode) . '<br>';
								if (!empty($order_data->shipping_country)) echo esc_html($order_data->shipping_country) . '<br>';
								if (!empty($order_data->shipping_phone)) echo esc_html($order_data->shipping_phone) . '<br>';
								?>
                            </address>
                        </div>
					<?php endif; ?>
                </section>
            </div> <!-- .order-details-wrapper -->
			<?php
		}
	} else {
		echo '<p>' . esc_html__( 'Error: Could not load order details. VCL_Order class does not exist.', LANG_ZONE ) . '</p>';
	}

// --- Display Order List ---
else :

	$page_url = get_the_permalink();
	$base_order_tab_url = function_exists('addParamToUrl') ? addParamToUrl($page_url, 'orders') : home_url('/account-tab.orders');
	?>
    <h5 class="title"><?php esc_html_e('Your orders', LANG_ZONE); ?></h5>
    <p><?php esc_html_e('Manage your orders', LANG_ZONE); ?></p>
    <div class="account-orders grid">
		<?php
		if ( class_exists('Customer') ) {
			$customer = get_current_customer();
			$customer_orders = $customer->get_orders();

			if ( $customer_orders ) :
				?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover vcl-orders-table my_account_orders account-orders-table">
                        <thead>
                        <tr>
                            <th class="vcl-orders-table__header vcl-orders-table__header-order-number"><span class="nobr"><?php esc_html_e('Order', LANG_ZONE); ?></span></th>
                            <th class="vcl-orders-table__header vcl-orders-table__header-order-date"><span class="nobr"><?php esc_html_e('Date', LANG_ZONE); ?></span></th>
                            <th class="vcl-orders-table__header vcl-orders-table__header-order-status"><span class="nobr"><?php esc_html_e('Status', LANG_ZONE); ?></span></th>
                            <th class="vcl-orders-table__header vcl-orders-table__header-order-total text-end"><span class="nobr"><?php esc_html_e('Total', LANG_ZONE); ?></span></th>
                            <th class="vcl-orders-table__header vcl-orders-table__header-order-actions text-center"><span class="nobr"><?php esc_html_e('Action', LANG_ZONE); ?></span></th>
                        </tr>
                        </thead>
                        <tbody>
						<?php
						foreach ( $customer_orders as $order_data ) {
							$order_id = $order_data->order_id;
							$view_order_url = $base_order_tab_url . '.id' . $order_id . '/';
							$erp_order_code = ( new VCL_Order )->get_order_meta( $order_id, '_erp_order_code',1 ) ?? '';
							?>
                            <tr class="vcl-orders-table__row vcl-orders-table__row--status-<?php echo esc_attr( $order_data->status ); ?> order">
                                <td class="vcl-orders-table__cell vcl-orders-table__cell-order-number" data-title="<?php esc_attr_e('Order', LANG_ZONE); ?>">
                                    <a href="<?php echo esc_url( $view_order_url ); ?>" data-erp="<?php echo esc_attr($erp_order_code); ?>">
										<?php echo $erp_order_code ? $erp_order_code : '#'.$order_id; ?>
                                    </a>
                                </td>
                                <td class="vcl-orders-table__cell vcl-orders-table__cell-order-date" data-title="<?php esc_attr_e('Date', LANG_ZONE); ?>">
                                    <time datetime="<?php echo esc_attr( date( 'c', strtotime($order_data->date_created) ) ); ?>">
										<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($order_data->date_created) ) ); ?>
                                    </time>
                                </td>
                                <td class="vcl-orders-table__cell vcl-orders-table__cell-order-status" data-title="<?php esc_attr_e('Status', LANG_ZONE); ?>">
									<?php echo $order_statuses[$order_data->status]; ?>
                                </td>
                                <td class="vcl-orders-table__cell vcl-orders-table__cell-order-total text-end" data-title="<?php esc_attr_e('Total', LANG_ZONE); ?>">
									<?php
									if ( function_exists('priceFormater') ) {
										$currency_symbol = ($order_data->currency === 'VND') ? '₫' : $order_data->currency;
										echo priceFormater( $order_data->total_amount, $currency_symbol );
									} else {
										echo esc_html( number_format( $order_data->total_amount, 0, ',', '.' ) . ' ' . $order_data->currency );
									}
									?>
                                </td>
                                <td class="vcl-orders-table__cell vcl-orders-table__cell-order-actions text-center" data-title="<?php esc_attr_e('Action', LANG_ZONE); ?>">
                                    <a href="<?php echo esc_url( $view_order_url ); ?>" class="button view"><?php esc_html_e('View', LANG_ZONE); ?></a>
                                </td>
                            </tr>
							<?php
						}
						?>
                        </tbody>
                    </table>
                </div>
			<?php else : ?>
                <div class="vcl-message vcl-message--info vcl-info">
					<?php
					$shop_page_url = function_exists('vcl_get_shop_page') ? vcl_get_shop_page() : home_url( '/shop/' );
					?>
                    <a class="vcl-Button button" href="<?php echo esc_url( $shop_page_url ); ?>"><?php esc_html_e('Go to shop', LANG_ZONE); ?></a>
					<?php esc_html_e('No orders have been made yet.', LANG_ZONE); ?>
                </div>
			<?php
			endif;
		} else {
			echo '<p>' . esc_html__( 'Error: Could not load customer information. Customer class does not exist.', LANG_ZONE ) . '</p>';
			error_log('Error in account-orders.php: Customer class not found for user ID ' . $current_user_id);
		}
		?>
    </div><!-- .account-orders -->
<?php endif; // End check for view_order_id ?>
