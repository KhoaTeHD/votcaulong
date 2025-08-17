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

    /**
     * Constructor.
     * Loads Payoo settings.
     */
    public function __construct() {
        // --- Configuration Loading ---
        // Option 1: Using defined constants (Recommended for security)
        // Example: define('PAYOO_USERNAME', 'your_username'); in wp-config.php
        $this->merchant_username = defined('PAYOO_USERNAME') ? PAYOO_USERNAME : '';
        $this->shop_id           = defined('PAYOO_SHOP_ID') ? PAYOO_SHOP_ID : '';
        $this->secret_key        = defined('PAYOO_SECRET_KEY') ? PAYOO_SECRET_KEY : '';
        $this->checkout_url      = defined('PAYOO_CHECKOUT_URL') ? PAYOO_CHECKOUT_URL : ''; // e.g., 'https://sandbox.payoo.vn/v2/paynow'

        // Option 2: Using Theme Options (Requires a theme options framework)
        // Example: $options = get_option('my_theme_options');
        // $this->merchant_username = $options['payoo_username'] ?? '';
        // $this->shop_id           = $options['payoo_shop_id'] ?? '';
        // $this->secret_key        = $options['payoo_secret_key'] ?? '';
        // $this->checkout_url      = $options['payoo_checkout_url'] ?? '';

        // --- Fallback/Default values (Use only if options/constants are not set) ---
        if (empty($this->merchant_username)) { /* Log error or set default test value */ }
        if (empty($this->shop_id)) { /* Log error or set default test value */ }
        if (empty($this->secret_key)) { /* Log error or set default test value */ }
        if (empty($this->checkout_url)) { /* Log error or set default test value */ }


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
        return !empty($this->merchant_username) &&
               !empty($this->shop_id) &&
               !empty($this->secret_key) &&
               !empty($this->checkout_url);
    }

    /**
     * Creates the payment request data string and checksum.
     *
     * @param object $order_details Array containing necessary order details:
     *                             'id', 'total_amount', 'customer_name', 'customer_phone', 'customer_email'.
     * @return array|WP_Error ['data' => string, 'checksum' => string] or WP_Error on failure.
     */
    public function create_payment_data( $order_details ) {
        if (!$this->is_configured()) {
            return new WP_Error('payoo_config_error', __('Payoo gateway is not configured correctly.',LANG_ZONE ) );
        }

        // Validate input array
        if ( ! is_object($order_details) ||
             empty($order_details->order_id) ||
             ! isset($order_details->total_amount) ||
             empty($order_details->fullname) ||
             empty($order_details->billing_phone) ||
             empty($order_details->billing_email) ) {
            return new WP_Error('payoo_data_error', __('Invalid or incomplete order details provided for payment creation.', LANG_ZONE));
        }

        $order_id = $order_details->order_id;
        $total_amount = $order_details->total_amount;
        $customer_name = $order_details->fullname;
        $customer_phone = $order_details->billing_phone;
        $customer_email = $order_details->billing_email;

        if ($total_amount <= 0) {
             return new WP_Error('payoo_data_error', __('Invalid order amount (must be greater than 0).', LANG_ZONE));
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
        $data_string .= '<order_no>' . esc_attr($order_id) . '</order_no>';
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
        if (is_wp_error($payment_data)) {
            return $payment_data;
        }
        if (!$this->is_configured()) {
             return new WP_Error('payoo_config_error', 'Payoo gateway is not configured correctly.');
        }
        if (!isset($payment_data['data']) || !isset($payment_data['checksum'])) {
             return new WP_Error('payoo_internal_error', __('Invalid payment data structure for sending request.', LANG_ZONE));
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

        $response = wp_remote_post($this->checkout_url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("Payoo API Request Error: " . $error_message);
            return new WP_Error('payoo_connection_error', 'Lỗi kết nối đến Payoo: ' . $error_message);
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($http_code >= 300) {
             error_log("Payoo API HTTP Error: Code " . $http_code . " - Body: " . $body);
             return new WP_Error('payoo_http_error', 'Lỗi HTTP từ Payoo: ' . $http_code);
        }

        $response_data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($response_data)) {
             error_log("Payoo API Response Error: Invalid JSON. Body: " . $body);
             return new WP_Error('payoo_response_error', 'Phản hồi không hợp lệ từ Payoo.');
        }

        if (isset($response_data['order']['payment_url'])) {
            return $response_data['order']['payment_url']; // Success
        } else {
            $error_desc = $response_data['error_message'] ?? ($response_data['description'] ?? 'Unknown Payoo error');
            error_log("Payoo Payment Creation Failed: " . print_r($response_data, true));
            return new WP_Error('payoo_payment_error', 'Lỗi tạo thanh toán Payoo: ' . $error_desc);
        }
    }

    /**
     * Handles the Instant Payment Notification (IPN) callback from Payoo.
     * Verifies the notification and updates the order status using VCL_Order methods.
     *
     * @param string $raw_post_data Raw data from php://input.
     * @return array JSON response for Payoo ['ReturnCode' => int, 'Description' => string].
     */
    public function handle_ipn( $raw_post_data ) {
        // ... existing initial checks and checksum verification ...

        // --- Process the Notification Data ---
        $data = json_decode($raw_post_data, true);
        // ... existing data validation ...

        $order_id = $data['OrderNo'];
        $payment_status = $data['PaymentStatus']; // 1 = Success
        $payoo_transaction_id = $data['TransactionId'] ?? 'N/A';

        try {
            // --- Load and Update VCL_Order ---
            // Load order using VCL_Order constructor
            $order = new VCL_Order($order_id);

            // Check if order loaded successfully using get_id()
            if ( ! $order || ! $order->get_id() ) {
                 error_log('Payoo IPN Info: Order not found for OrderNo: ' . $order_id . '. Acknowledging receipt.');
                 return ['ReturnCode' => 0, 'Description' => 'NOTIFY_RECEIVED_ORDER_NOT_FOUND'];
            }

            // Save Payoo transaction ID using the new save_single_meta method
            $order->save_single_meta('_payoo_transaction_id', $payoo_transaction_id);

            // Get current status using get_order_status($order_id)
            $current_status = $order->get_order_status($order_id);
            $note = '';

            switch ($payment_status) {
                case 1: // Success
                    // Update status using update_status($order_id, $new_status)
                    // Add note using add_order_note($note)
                    if ($current_status !== 'completed' && $current_status !== 'processing') {
                         $order->update_status($order_id, 'processing'); // Pass order_id
                         $note = 'Thanh toán Payoo thành công.';
                         // TODO: Trigger other actions like sending email, reducing stock etc.
                    } else {
                         $note = 'Đã nhận thông báo thành công Payoo (trạng thái không đổi).';
                    }
                    break;
                // case 0: // Pending - Optional handling
                //     if ($current_status !== 'on-hold') {
                //         $order->update_status($order_id, 'on-hold'); // Pass order_id
                //         $note = 'Thanh toán Payoo đang chờ xử lý.';
                //     }
                //     break;
                default: // Failure or other statuses
                     // Update status using update_status($order_id, $new_status)
                     // Add note using add_order_note($note)
                     if ($current_status !== 'failed' && $current_status !== 'cancelled') {
                         $order->update_status($order_id, 'failed'); // Pass order_id
                         $note = sprintf('Thanh toán Payoo không thành công hoặc trạng thái không xác định (%s).', esc_html($payment_status));
                     } else {
                         $note = sprintf('Đã nhận thông báo không thành công Payoo (%s) (trạng thái không đổi).', esc_html($payment_status));
                     }
                    break;
            }

            // Add a note with transaction details using add_order_note($note)
            $order->add_order_note($note . ' Mã giao dịch Payoo: ' . esc_html($payoo_transaction_id));

        } catch (Exception $e) {
             error_log('Payoo IPN Error: Exception during order update for OrderNo ' . $order_id . ': ' . $e->getMessage());
             return ['ReturnCode' => -1, 'Description' => 'INTERNAL_PROCESSING_ERROR'];
        }

        // Acknowledge successful processing to Payoo
        return ['ReturnCode' => 0, 'Description' => 'NOTIFY_RECEIVED'];
    }

     /**
     * Verifies the checksum for the simple GET redirect from Payoo (shop_back_url).
     *
     * @param array $get_params $_GET parameters received. Expected keys: 'session', 'order_no', 'status', 'checksum'.
     * @return bool True if checksum is valid, false otherwise.
     */
    public function verify_return_checksum( $get_params ) {
         if (!$this->is_configured()) {
            error_log('Payoo Return Error: Gateway not configured.');
            return false;
         }
        if ( !isset($get_params['session']) || !isset($get_params['order_no']) || !isset($get_params['status']) || !isset($get_params['checksum']) ) {
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
            error_log('Payoo Return Error: Checksum mismatch. Order: ' . $order_no . ' Status: ' . $status);
        }

        return $is_valid;
    }
}