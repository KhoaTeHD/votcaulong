<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Payoo_Handler
 *
 * Handles interactions with the Payoo payment gateway.
 * Adapted from payoovn-payment-gateway-woocommerce plugin structure.
 */
class Payoo_Handler {

    private $merchant_username;
    private $shop_id;
    private $shop_title;
    private $shop_domain;
    private $secret_key; // Checksum Key
    private $checkout_url; // Payoo Checkout URL (Sandbox/Production)
    private $return_url; // URL user returns to (shop_back_url)
    private $notify_url; // URL for Payoo IPN POST requests
    private $debug_log_enabled = false;
    private $log_file, $order_prefix;

    /**
     * Logs a message to the Payoo debug log file if enabled.
     *
     * @param string $message The message to log.
     */
    private function _log( $message ) {
        if ( ! $this->debug_log_enabled ) {
            return;
        }
        if ( empty( $this->log_file ) ) {
            $upload_dir = wp_upload_dir();
            $this->log_file = $upload_dir['basedir'] . '/payoo-debug.log';
        }

        $timestamp = current_time( 'mysql' );
        $formatted_message = sprintf( "[%s] - %s\n", $timestamp, $message );

        // Use file_put_contents with LOCK_EX for safe appending
        file_put_contents( $this->log_file, $formatted_message, FILE_APPEND | LOCK_EX );
    }

    /**
     * Loads Payoo API configuration from ACF Options Page.
     * This method populates the handler's properties based on the settings
     * defined in the 'Payoo API' group field.
     */
    private function _load_acf_config() {
        if ( ! function_exists('get_field') ) {
            // ACF is not active, do nothing.
            return;
        }

        $config = get_field('payoo_api', 'option');

        if ( empty($config) ) {
            // No configuration found.
            $this->_log('ACF field "payoo_api" not found or empty.');
            return;
        }

        // Enable/disable logging
        $this->debug_log_enabled = ! empty( $config['debug_log'] );
        $this->_log('Payoo Handler initialized.');

        $is_production = !empty($config['current_use']);
        $env_settings = $is_production ? ($config['production'] ?? []) : ($config['sandbox'] ?? []);
        
        $this->_log('Environment: ' . ($is_production ? 'Production' : 'Sandbox'));
        if ( ! empty($env_settings) ) {
            $this->merchant_username = $env_settings['username'] ?? '';
            $this->shop_id           = $env_settings['merchants_id'] ?? ''; // Corresponds to Merchant's ID
            $this->secret_key        = $env_settings['checksum_key'] ?? '';
            $this->checkout_url      = $env_settings['url_checkout'] ?? '';
            $this->_log('Configuration loaded successfully.');
        } else {
            $this->_log('Configuration for the selected environment is empty.');
        }
    }

    /**
     * Constructor.
     * Loads Payoo settings from ACF.
     */
    public function __construct() {
        // --- Configuration Loading ---
        $this->_load_acf_config();

        // --- Shop Info & Callback URLs ---
        $this->shop_title        = get_bloginfo('name'); // Get shop title from WordPress settings
        $this->shop_domain       = home_url();

        // Define a single callback endpoint URL.
       // Use the REST API endpoint URL
       $callback_url = rest_url('payment/v1/payoo-callback'); // Make sure namespace/route matches registration

       $this->return_url = $callback_url; // shop_back_url: Where user is redirected (GET)
       $this->notify_url = $callback_url; // notify_url: Where Payoo sends IPN (POST)

    }

    /**
     * Validates if the handler is configured correctly.
     * @return bool True if configured, false otherwise.
     */
    public function is_configured() {
        $is_configured = !empty($this->merchant_username) &&
               !empty($this->shop_id) &&
               !empty($this->secret_key) &&
               !empty($this->checkout_url);
        if( ! $is_configured ){
            $this->_log('is_configured check FAILED.');
        }
        return $is_configured;
    }

    /**
     * Creates the payment request data string and checksum.
     *
     * @param object $order_details Array containing necessary order details:
     *                             'id', 'total_amount', 'customer_name', 'customer_phone', 'customer_email'.
     * @return array|WP_Error ['data' => string, 'checksum' => string] or WP_Error on failure.
     */
    public function create_payment_data( $order_details ) {
        $this->_log("Attempting to create payment data for Order ID: {$order_details->order_id}.");
        if (!$this->is_configured()) {
            $error_message = __('Payoo gateway is not configured correctly.',LANG_ZONE );
            $this->_log("Error: {$error_message}");
            return new WP_Error('payoo_config_error', $error_message );
        }

        // Validate input array
        if ( ! is_object($order_details) ||
             empty($order_details->order_id) ||
             ! isset($order_details->total_amount) ||
             empty($order_details->fullname) ||
             empty($order_details->billing_phone) ||
             empty($order_details->billing_email) ) {
            $error_message = __('Invalid or incomplete order details provided for payment creation.', LANG_ZONE);
            $this->_log("Error: {$error_message} Data: " . print_r($order_details, true));
            return new WP_Error('payoo_data_error', $error_message);
        }

        $order_id = $order_details->order_id;
		$erp_order_code = str_replace('-','_',$order_details->erp_order_code);
        $total_amount = $order_details->total_amount;
        $customer_name = $order_details->fullname;
        $customer_phone = $order_details->billing_phone;
        $customer_email = $order_details->billing_email;

        if ($total_amount <= 0) {
            $error_message = __('Invalid order amount (must be greater than 0).', LANG_ZONE);
            $this->_log("Error: {$error_message} Amount: {$total_amount}");
             return new WP_Error('payoo_data_error', $error_message);
        }

        // Format required by Payoo
        $order_ship_date = date('d/m/Y');
        $order_ship_days = 0;
        $validity_time = date('YmdHis', strtotime('+1 day', time())); // Payment link expiry
        $money_total = (int) round($total_amount);

        $order_description_html = sprintf(
            '<table class="order-description paycode-description"><thead><tr><th>Thông tin đơn hàng</th><th>Tiền thanh toán</th></tr></thead><tbody><tr><td class="row-unit-product">Thanh toán đơn hàng %s từ %s</td><td class="row-total">%s</td></tr></tbody></table>',
            esc_html($order_id),
            esc_html($this->shop_title),
            esc_html(number_format($money_total))
        );

        // Construct the data string
        $data_string = '<shops><shop>';
        $data_string .= '<session>' . esc_attr($order_id) . '</session>';
        $data_string .= '<username>' . esc_attr($this->merchant_username) . '</username>';
        $data_string .= '<shop_id>' . esc_attr($this->shop_id) . '</shop_id>';
        $data_string .= '<shop_title>' . esc_attr($this->shop_title) . '</shop_title>';
        $data_string .= '<shop_domain>' . esc_attr($this->shop_domain) . '</shop_domain>';
        $data_string .= '<shop_back_url>' . esc_attr(urlencode($this->return_url)) . '</shop_back_url>';
        $data_string .= '<order_no>' . esc_attr($erp_order_code) . '</order_no>';
        $data_string .= '<order_cash_amount>' . esc_attr($money_total) . '</order_cash_amount>';
        $data_string .= '<order_ship_date>' . esc_attr($order_ship_date) . '</order_ship_date>';
        $data_string .= '<order_ship_days>' . esc_attr($order_ship_days) . '</order_ship_days>';
        $data_string .= '<order_description>' . urlencode($order_description_html) . '</order_description>';
        $data_string .= '<notify_url>' . esc_attr($this->notify_url) . '</notify_url>';
        $data_string .= '<validity_time>' . esc_attr($validity_time) . '</validity_time>';
        $data_string .= '<customer>';
        $data_string .= '<name>' . esc_attr($customer_name) . '</name>';
        $data_string .= '<phone>' . esc_attr($customer_phone) . '</phone>';
        $data_string .= '<email>' . esc_attr($customer_email) . '</email>';
        $data_string .= '</customer>';
        $data_string .= '<jsonresponse>true</jsonresponse>';
        $data_string .= '<direct_return_time>10</direct_return_time>';
        $data_string .= '</shop></shops>';

        // Calculate Checksum
        $checksum = hash('sha512', $this->secret_key . $data_string);
        
        $this->_log("Data string: {$data_string}");
        $this->_log("Successfully created payment data and checksum for Order ID: {$order_id} | {$erp_order_code}.");

        return [
            'data' => $data_string,
            'checksum' => $checksum,
        ];
    }

    /**
     * Sends the payment request to Payoo and gets the redirect URL.
     *
     * @param array $payment_data Data from create_payment_data().
     * @return string|WP_Error The payment URL to redirect the user to, or WP_Error on failure.
     */
    public function send_payment_request( $payment_data ) {
        $this->_log("Attempting to send payment request to Payoo.");
        if (is_wp_error($payment_data)) {
            $this->_log("Payment data contains WP_Error: " . $payment_data->get_error_message());
            return $payment_data;
        }
        if (!$this->is_configured()) {
            $error_message = 'Payoo gateway is not configured correctly.';
            $this->_log("Error: {$error_message}");
             return new WP_Error('payoo_config_error', $error_message);
        }
        if (!isset($payment_data['data']) || !isset($payment_data['checksum'])) {
            $error_message = __('Invalid payment data structure for sending request.', LANG_ZONE);
            $this->_log("Error: {$error_message}");
             return new WP_Error('payoo_internal_error', $error_message);
        }

        $post_fields = [
            'data' => $payment_data['data'],
            'checksum' => $payment_data['checksum'],
            'refer' => $this->shop_domain,
        ];

        $args = [
            'body'        => $post_fields,
            'timeout'     => 30,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded'], // Explicitly set header
            'cookies'     => [],
            'sslverify'   => true // Recommended: Set to true for production. Ensure server has up-to-date CA certificates. Set to false only if necessary for testing/debugging.
        ];
        
        $this->_log("Sending POST request to: {$this->checkout_url}");
        $response = wp_remote_post($this->checkout_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->_log("Payoo API Request Error: " . $error_message);
            error_log("Payoo API Request Error: " . $error_message);
            return new WP_Error('payoo_connection_error', 'Lỗi kết nối đến Payoo: ' . $error_message);
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        $this->_log("Received response. HTTP Code: {$http_code}. Body: " . $body);

        if ($http_code >= 300) {
             $this->_log("Payoo API HTTP Error: Code " . $http_code . " - Body: " . $body);
             error_log("Payoo API HTTP Error: Code " . $http_code . " - Body: " . $body);
             return new WP_Error('payoo_http_error', 'Lỗi HTTP từ Payoo: ' . $http_code);
        }

        $response_data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($response_data)) {
             $this->_log("Payoo API Response Error: Invalid JSON. Body: " . $body);
             error_log("Payoo API Response Error: Invalid JSON. Body: " . $body);
             return new WP_Error('payoo_response_error', 'Phản hồi không hợp lệ từ Payoo.');
        }

        if (isset($response_data['order']['payment_url'])) {
            $this->_log("Successfully received payment URL: " . $response_data['order']['payment_url']);
            return $response_data['order']['payment_url']; // Success
        } else {
            $error_desc = $response_data['error_message'] ?? ($response_data['description'] ?? 'Unknown Payoo error');
            $this->_log("Payoo Payment Creation Failed: " . $error_desc);
            error_log("Payoo Payment Creation Failed: " . print_r($response_data, true));
            return new WP_Error('payoo_payment_error', 'Lỗi tạo thanh toán Payoo: ' . $error_desc);
        }
    }

	/**
	 * Xử lý thông báo thanh toán tức thì (IPN) từ Payoo.
	 *
	 * @param string $raw_post_data Dữ liệu POST thô từ Payoo.
	 * @param string $remote_ip Địa chỉ IP của request đến.
	 * @return array Phản hồi để gửi lại cho Payoo.
	 */
	public function handle_ipn( $raw_post_data, $remote_ip ) {
		$this->_log("Handling IPN callback. Raw data: " . $raw_post_data . " | From IP: " . $remote_ip);

		// 1. Giải mã cấu trúc JSON bên ngoài
		$ipn_data = json_decode(stripslashes($raw_post_data), true);
		if (!is_array($ipn_data)) {
			$this->_log("IPN Error: Dữ liệu thô nhận được không phải là JSON hợp lệ. Data: " . $ipn_data);
			return ['ReturnCode' => -1, 'Description' => 'INVALID_JSON_FORMAT'];
		}

		if (!isset($ipn_data['ResponseData']) || !isset($ipn_data['SecureHash'])) {
			$this->_log("IPN Error: Cấu trúc JSON không hợp lệ hoặc thiếu khóa 'ResponseData'/'SecureHash'. Data: " . $raw_post_data);
			return ['ReturnCode' => -1, 'Description' => 'INVALID_JSON_STRUCTURE'];
		}

		$response_data_str = $ipn_data['ResponseData'];
		$received_signature = $ipn_data['SecureHash'];

		// 2. Xác thực chữ ký (BƯỚC QUAN TRỌNG)
		// Công thức theo tài liệu: SHA512(ChecksumKey + ResponseData + Payoo’s IP)
		$string_to_hash = $this->secret_key . $response_data_str . $remote_ip;
		$calculated_signature = hash('sha512', $string_to_hash);

		if (!hash_equals($calculated_signature, $received_signature)) {
			$this->_log("IPN SECURITY ERROR: Chữ ký không khớp!");
			$this->_log("Dữ liệu để hash: " . $string_to_hash);
			$this->_log("Chữ ký nhận được: " . $received_signature);
			$this->_log("Chữ ký tính toán: " . $calculated_signature);
			return ['ReturnCode' => -1, 'Description' => 'INVALID_SIGNATURE'];
		}
		$this->_log("IPN Signature verified successfully.");

		// 3. Giải mã chuỗi JSON ResponseData bên trong
		$data = json_decode($response_data_str, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->_log("IPN Error: Dữ liệu JSON trong ResponseData không hợp lệ. String: " . $response_data_str);
			return ['ReturnCode' => -1, 'Description' => 'INVALID_RESPONSE_DATA_JSON'];
		}

		// 4. Xử lý dữ liệu đã được xác thực
		$order_no = $data['OrderNo'] ?? null;
		if (empty($order_no)) {
			$this->_log("IPN Error: Không tìm thấy OrderNo trong ResponseData.");
			return ['ReturnCode' => -1, 'Description' => 'ORDER_NOT_FOUND_IN_DATA'];
		}

		$order_id = VCL_Order::get_erp_order_id($order_no);
		if (empty($order_id)) {
			$this->_log("IPN Info: Không tìm thấy đơn hàng cục bộ khớp với ERP OrderNo: {$order_no}. Ghi nhận đã nhận thông báo.");
			return ['ReturnCode' => 0, 'Description' => 'NOTIFY_RECEIVED_ORDER_NOT_FOUND'];
		}

		$this->_log("IPN cho ERP OrderNo: {$order_no} | Tìm thấy Order ID cục bộ: {$order_id}");

		$payment_status = $data['PaymentStatus']; // 1 = Success [cite: 1286]
		$payoo_transaction_id = $data['PYTransId'] ?? 'N/A';

		try {
			$order = new VCL_Order($order_id);

			if (!$order || !$order->get_id()) {
				$this->_log("IPN Info: Không thể tạo đối tượng Order cho Order ID: {$order_id}. Ghi nhận đã nhận thông báo.");
				return ['ReturnCode' => 0, 'Description' => 'NOTIFY_RECEIVED_ORDER_NOT_FOUND'];
			}

			$order->save_single_meta('_payoo_transaction_details', json_encode($data));
			$current_status = $order->get_order_status($order_id);
			$note = '';

			$this->_log("Order ID {$order_id} - Trạng thái hiện tại: {$current_status}, IPN PaymentStatus: {$payment_status}");

			switch ($payment_status) {
				case 1: // Thành công [cite: 1286]
					if ($current_status !== 'completed' && $current_status !== 'processing') {
						$order->update_status($order_id, 'processing');
						$note = 'Thanh toán Payoo thành công.';
						$this->_log("Order ID {$order_id} - Trạng thái đã cập nhật thành 'processing'.");

						/*$erp_api = new ERP_API_Client();
						$erp_result = $erp_api->update_sales_order($order_no, ['docstatus' => 1]);

						if (is_wp_error($erp_result)) {
							$erp_error_msg = $erp_result->get_error_message();
							$order->add_order_note("LỖI: Không thể submit đơn hàng trên ERP sau khi thanh toán thành công. Lỗi: " . $erp_error_msg);
							$this->_log("IPN ERP Error: Thất bại khi submit SO {$order_no}. Lỗi: " . $erp_error_msg);
						} else {
							$order->add_order_note("Đơn hàng đã được tự động submit trên ERP.");
							$this->_log("IPN ERP Success: Đã submit SO {$order_no}.");
						}*/
					} else {
						$note = 'Đã nhận thông báo thành công Payoo (trạng thái không đổi).';
						$this->_log("Order ID {$order_id} - Trạng thái đã là '{$current_status}', không thay đổi.");
					}
					break;
				default: // Thất bại hoặc các trạng thái khác
					if ($current_status !== 'failed' && $current_status !== 'cancelled') {
						$order->update_status($order_id, 'failed');
						$note = sprintf('Thanh toán Payoo không thành công hoặc trạng thái không xác định (%s).', esc_html($payment_status));
						$this->_log("Order ID {$order_id} - Trạng thái đã cập nhật thành 'failed'.");

						/*$erp_api = new ERP_API_Client();
						$erp_result = $erp_api->cancel_sales_order($order_no);

						if (is_wp_error($erp_result)) {
							$erp_error_msg = $erp_result->get_error_message();
							$order->add_order_note("LỖI: Không thể hủy đơn hàng nháp trên ERP sau khi thanh toán thất bại. Lỗi: " . $erp_error_msg);
							$this->_log("IPN ERP Error: Thất bại khi hủy SO {$order_no}. Lỗi: " . $erp_error_msg);
						} else {
							$order->add_order_note("Đơn hàng nháp đã được tự động hủy trên ERP.");
							$this->_log("IPN ERP Success: Đã hủy SO {$order_no}.");
						}*/
					} else {
						$note = sprintf('Đã nhận thông báo không thành công Payoo (%s) (trạng thái không đổi).', esc_html($payment_status));
						$this->_log("Order ID {$order_id} - Trạng thái đã là '{$current_status}', không thay đổi.");
					}
					break;
			}

			$full_note = $note . ' Mã giao dịch Payoo: ' . esc_html($payoo_transaction_id);
			$order->add_order_note($full_note);
			$this->_log("Order ID {$order_id} - Đã thêm ghi chú: " . $full_note);

		} catch (Exception $e) {
			$this->_log("IPN Exception: Lỗi trong quá trình cập nhật đơn hàng cho Order ID {$order_id}: " . $e->getMessage());
			//error_log('Payoo IPN Error: Exception during order update for Order ID ' . $order_id . ': ' . $e->getMessage());
			return ['ReturnCode' => -1, 'Description' => 'INTERNAL_PROCESSING_ERROR'];
		}

		$this->_log("IPN cho Order ID {$order_id} đã được xử lý thành công. Trả về NOTIFY_RECEIVED.");
		return ['ReturnCode' => 0, 'Description' => 'NOTIFY_RECEIVED']; // Trả về 0 để xác nhận đã nhận IPN [cite: 1316]
	}

     /**
     * Verifies the checksum for the simple GET redirect from Payoo (shop_back_url).
     *
     * @param array $get_params $_GET parameters received. Expected keys: 'session', 'order_no', 'status', 'checksum'.
     * @return bool True if checksum is valid, false otherwise.
     */
    public function verify_return_checksum( $get_params ) {
        $this->_log("Verifying return checksum. Data: " . print_r($get_params, true));
         if (!$this->is_configured()) {
            $this->_log('Return Error: Gateway not configured.');
            error_log('Payoo Return Error: Gateway not configured.');
            return false;
         }
        if ( !isset($get_params['session']) || !isset($get_params['order_no']) || !isset($get_params['status']) || !isset($get_params['checksum']) ) {
            $this->_log('Return Error: Missing parameters in GET request.');
            error_log('Payoo Return Error: Missing parameters in GET request.');
            return false;
        }

        $session = $get_params['session'];
        $order_no = $get_params['order_no'];
        $status = $get_params['status']; // '1' for success, '0' for failure/cancel
        $received_checksum = $get_params['checksum'];

        // Calculate checksum based on plugin logic: key + session + '.' + order_no + '.' + status
        $string_to_hash = $this->secret_key . $session . '.' . $order_no . '.' . $status;
        $calculated_checksum = hash('sha512', $string_to_hash);

        $is_valid = hash_equals($calculated_checksum, $received_checksum);

        if (!$is_valid) {
            $this->_log("Return Error: Checksum mismatch. Order: {$order_no}, Status: {$status}. Expected: {$calculated_checksum}, Received: {$received_checksum}");
            error_log('Payoo Return Error: Checksum mismatch. Order: ' . $order_no . ' Status: ' . $status);
        } else {
            $this->_log("Return checksum verified successfully for Order: {$order_no}.");
        }

        return $is_valid;
    }
}
