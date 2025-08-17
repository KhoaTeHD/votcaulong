<?php
/**
 * ERP_API_Client
 *
 * A robust API client for handling communication with the ERP system.
 * Features centralized endpoint management, robust error handling,
 * effective caching, and abstraction of API complexities.
 */
class ERP_API_Client {
	private $base_url;
	private $jwt;
	private $products_per_page;
	private $endpoint_update_order_status;

	// --- API Endpoint Constants ---
	private const ENDPOINT_LIST_ALL_ITEM_GROUPS = '/api/method/inno_vclshop.controller.wp.list_all_item_groups';
	private const ENDPOINT_LIST_VARIANTS = '/api/method/inno_vclshop.controller.wp.list_variant';
	private const ENDPOINT_BROWSE_ITEMS = '/api/method/inno_vclshop.controller.wp.browse_items';
	private const ENDPOINT_SEARCH_ITEMS = '/api/method/inno_vclshop.controller.wp.search_items';
	private const ENDPOINT_ITEM_DETAIL = '/api/method/inno_vclshop.controller.wp.detail_item';
	private const ENDPOINT_ITEM_RESOURCE = '/api/resource/Item/';
	private const ENDPOINT_ITEM_GROUP_RESOURCE = '/api/resource/Item Group/';
	private const ENDPOINT_GET_STOCK = '/api/method/inno_vclshop.controller.wp.get_stock_availability';
	private const ENDPOINT_ADDRESS_LOCATION_RESOURCE = '/api/resource/Address Location';
	private const ENDPOINT_ADDRESS_WARD_RESOURCE = '/api/resource/Address Ward';
	private const ENDPOINT_ADDRESS_RESOURCE = '/api/resource/Address';
	private const ENDPOINT_CUSTOMER_RESOURCE = '/api/resource/Customer';
	private const ENDPOINT_UPDATE_CUSTOMER = '/api/method/inno_vclshop.controller.wp.update_customer';
	private const ENDPOINT_GET_CUSTOMER_LOYALTY = '/api/method/inno_erp.inno_account.overrides.loyalty_program.loyalty_program.get_customer_loyalty_tier_info';
	private const ENDPOINT_GET_ALL_LOYALTY_RANK = '/api/method/inno_erp.inno_account.overrides.loyalty_program.loyalty_program.get_all_loyalty_program';
	private const ENDPOINT_GET_PRICING_RULE = '/api/method/inno_vclshop.controller.wp.get_pricing_rule';
	private const ENDPOINT_MAKE_SALES_ORDER = '/api/method/inno_vclshop.controller.wp.make_sales_order';
	private const ENDPOINT_SAVE_ORDER = '/api/method/inno_vclshop.controller.wp.save_order';
	private const ENDPOINT_SALES_ORDER_RESOURCE = '/api/resource/Sales Order';
	private const ENDPOINT_CANCEL_ORDER = '/api/method/inno_vclshop.controller.wp.cancel_order';
	private const ENDPOINT_CALCULATE_DELIVERY_FEE = '/api/method/inno_vclshop.controller.wp.calculate_delivery_fee';
	private const ENDPOINT_BRAND_RESOURCE = '/api/resource/Brand';
	private const ENDPOINT_BRANCH_RESOURCE = '/api/resource/Branch';


	public function __construct(array $args=[]) {
		$this->base_url = rtrim($args['base_url'] ?? '', '/');
		$this->jwt = $args['jwt'] ?? '';
		$this->products_per_page = 8; // Default value
		$this->load_config();
	}

	private function load_config(){
		// Make the class more robust by checking if ACF is active.
		if (function_exists('get_field')) {
			$this->products_per_page = get_field('products_per_page', 'options') ?? 8;
			$erp_api_config = get_field('erp_api', 'options');
			if ($erp_api_config){
				$this->base_url = $erp_api_config['domain'];
				$this->jwt = $erp_api_config['api_key'] . ':' . $erp_api_config['api_secret'];
				$this->endpoint_update_order_status = $erp_api_config['update_order_status_endpoint'];
			}
		}
	}

	/*-------------------- Danh mục, sản phẩm --------------------*/
	public function list_all_item_groups() {
		return $this->get_json(self::ENDPOINT_LIST_ALL_ITEM_GROUPS);
	}

	public function new_products(array $item_groups = []): array {
		$cache_key = 'new_products_list';
		$cached = get_transient($cache_key);

		if ($cached !== false) {
			// return $cached;
		}

		$results = [];

		if (empty($item_groups)) {
			$data = $this->browse_items(['limit_page_length' => 20]);
			if (is_wp_error($data)) {
				return []; // Return empty array on error
			}
			$results = $data['data'] ?? [];
		} else {
			foreach ($item_groups as $group) {
				$group_items = $this->browse_items([
					'item_groups' => [$group->name],
					'limit_page_length' => 5
				]);

				// Check for error on each call inside the loop
				if (!is_wp_error($group_items) && !empty($group_items['data'])) {
					array_push($results, ...$group_items['data']);
				}
			}
		}

		if (!empty($results)) {
			$products = $this->map_erp_items($results, false);
			$products = array_map(function ($item) {
				$item['image'] = $this->get_item_image($item);
				return $item;
			}, $products);
			set_transient($cache_key, $products, HOUR_IN_SECONDS);
			return $products;
		}

		return [];
	}

	public function list_variants(array $item_groups) {
		$query = ['item_groups' => wp_json_encode($item_groups)];
		return $this->get_json(self::ENDPOINT_LIST_VARIANTS, $query);
	}

	public function browse_items(array $args = []) {
		$defaults = [
			'limit_page_length' => $this->products_per_page,
			'limit_start' => 0,
			'item_groups' => [],
		];
		$p = wp_parse_args($args, $defaults);
		if ($p['item_groups']) {
			$p['item_groups'] = wp_json_encode($p['item_groups']);
		}
		$result = $this->get_json(self::ENDPOINT_BROWSE_ITEMS, $p);
		if (is_wp_error($result)) {
			return $result;
		}
		if (!isset($result['data']) || !is_array($result['data'])) {
			return ['success' => false, 'message' => 'API trả về dữ liệu không hợp lệ'];
		}
		if (empty($result['data'])) {
			return ['success' => false, 'message' => 'Không có sản phẩm nào được trả về','data'=>[]];
		}

		return $result;
	}

	public function search_item(int $limit = 5, string $search_text = '') {
		$query = [
			'limit' => $limit,
			'query' => $search_text,
		];
		return $this->get_json(self::ENDPOINT_SEARCH_ITEMS, $query);
	}

	public function get_item_detail($item_code) {
		return $this->get_json(self::ENDPOINT_ITEM_RESOURCE . $item_code);
	}

	public function get_product($product_id, $get_stock = true) {
		$cache_key = 'product_' . $product_id;
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
			//return $cached;
		}

		$response = $this->get_json(self::ENDPOINT_ITEM_DETAIL, [
			'item_code' => $product_id
		]);

		if (!is_wp_error($response) && $response) {
			$product = $this->map_custom_erp_product($response, $get_stock);
			set_transient($cache_key, $product, HOUR_IN_SECONDS);
			return $product;
		}

		return $response;
	}

	public function get_category_erp($category_id) {
		$cache_key = 'category_' . $category_id;
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
			return $cached;
		}

		$response = $this->get_json(self::ENDPOINT_ITEM_GROUP_RESOURCE . $category_id);

		if (!is_wp_error($response)) {
			$category = $this->map_erp_category($response);
			set_transient($cache_key, $category, 12 * HOUR_IN_SECONDS);
			return $category;
		}

		return $response;
	}

	public function get_category($cate_name, $byId = false){
		$args = [
			'taxonomy' => 'pro_cate',
			'hide_empty' => false,
		];
		if ($byId) {
			$args['include'] = [$cate_name];
		} else {
			$args['name'] = $cate_name;
		}
		$terms = get_terms($args);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$term = $terms[0];
			return [
				'term_id' => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'id' => $term->term_id,
			];
		}
		return [];
	}

	public function getStock(array $item_codes=[]){
		if (empty($item_codes)){
			return [];
		}
		$p = ['item_codes' => wp_json_encode($item_codes)];
		return $this->get_json(self::ENDPOINT_GET_STOCK, $p);
	}

	/**
	 * Helper method to fetch and process product lists.
	 *
	 * @param array $browse_args Arguments for the browse_items method.
	 * @param int $limit_items Number of items per page.
	 * @return array|WP_Error Processed list of products or an error.
	 */
	private function _fetch_and_map_products(array $browse_args, int $limit_items) {
		$products = $this->browse_items($browse_args);

		if (is_wp_error($products) || !isset($products['data'])) {
			return is_wp_error($products) ? $products : [];
		}

		$total_pages = (int) ceil(($products['total_count'] ?? 0) / $limit_items);
		$mapped_products = array_map(function ($item) {
			$item['image'] = $this->get_item_image($item);
			return $item;
		}, $products['data']);

		return [
			'total_pages' => $total_pages,
			'products' => $mapped_products,
			'filters' => '' ?? []
		];
	}

	public function get_all_products($page = 1, $limit_items = 0) {
		$limit_items = $limit_items ?: $this->products_per_page;
		$limit_start = ($page > 1) ? ($page - 1) * $limit_items : 0;

		$args = [
			'limit_start'  => $limit_start,
			'limit_page_length' => $limit_items
		];

		return $this->_fetch_and_map_products($args, $limit_items);
	}

	public function get_products_by_category($item_group, $page = 1, $limit_items = 0) {
		$limit_items = $limit_items ?: $this->products_per_page;
		$limit_start = ($page > 1) ? ($page - 1) * $limit_items : 0;

		$args = [
			'item_groups' => [$item_group],
			'limit_start'  => $limit_start,
			'limit_page_length' => $limit_items
		];

		// Caching can be added here if needed, similar to the original implementation.
		return $this->_fetch_and_map_products($args, $limit_items);
	}

	public function get_products_by_brand($brand_name, $page = 1, $limit_items = 0) {
		$limit_items = $limit_items ?: $this->products_per_page;
		$limit_start = ($page > 1) ? ($page - 1) * $limit_items : 0;

		$args = [
			'brands' => wp_json_encode([$brand_name]),
			'limit_start'  => $limit_start,
			'limit_page_length' => $limit_items
		];

		// Caching can be added here if needed.
		return $this->_fetch_and_map_products($args, $limit_items);
	}

	public function get_filters(array $item_groups =[]){
		if (empty($item_groups)) {
			return $this->get_json(self::ENDPOINT_LIST_VARIANTS);
		}
		return $this->get_json(self::ENDPOINT_LIST_VARIANTS, ['item_groups' => wp_json_encode($item_groups)]);
	}

	public function get_related_products($product_id, $limit = 8, $page = 1) {
		$cache_key = 'related_products_' . sanitize_title($product_id);
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
			// return $cached;
		}

		$product_info = $this->get_product($product_id);
		if (is_wp_error($product_info)) {
			return $product_info;
		}

		$category_id = $product_info['category_name'] ?? null;
		if (!$category_id) {
			return new WP_Error('no_category', __('Không tìm thấy danh mục của sản phẩm', 'your-text-domain'));
		}

		$category_products = $this->get_products_by_category($category_id, $page, $limit + 1);
		if (is_wp_error($category_products)) {
			return $category_products;
		}

		$related_products = [];
		if (isset($category_products['products']) && is_array($category_products['products'])) {
			$related_products = array_filter($category_products['products'], function($product) use ($product_id) {
				return $product['item_code'] != $product_id;
			});
		}

		$related_products = array_slice($related_products, 0, $limit);
		set_transient($cache_key, $related_products, HOUR_IN_SECONDS);
		return $related_products;
	}

	/*-------------------- Địa chỉ --------------------*/
	public function list_address_locations($force_refresh = false) {
		$cache_key = 'erp_cached_locations';
		if (!$force_refresh) {
			$cached = get_transient($cache_key);
			if ($cached !== false) return $cached;
		}

		$response = $this->get_json(self::ENDPOINT_ADDRESS_LOCATION_RESOURCE, ['limit_page_length' => 999999]);
		if (!is_wp_error($response)) {
			set_transient($cache_key, $response, DAY_IN_SECONDS);
		}
		return $response;
	}

	public function list_wards_by_location($location, $force_refresh = false) {
		$cache_key = 'erp_cached_wards_' . md5($location ?: 'all');
		if (!$force_refresh) {
			$cached = get_transient($cache_key);
			if ($cached !== false) return $cached;
		}

		$params = [
			'limit_page_length' => 999999,
			'fields' => json_encode(['name', 'ward', 'location']),
		];
		if (!empty($location)) {
			$params['filters'] = json_encode([['location', '=', $location]]);
		}

		$response = $this->get_json(self::ENDPOINT_ADDRESS_WARD_RESOURCE, $params);
		if (!is_wp_error($response)) {
			set_transient($cache_key, $response, DAY_IN_SECONDS);
		}
		return $response;
	}

	public function create_address(array $data) {
		return $this->post_json_resource(self::ENDPOINT_ADDRESS_RESOURCE, $data);
	}

	public function get_address($address_id) {
		return $this->get_json(self::ENDPOINT_ADDRESS_RESOURCE . "/{$address_id}");
	}

	public function update_address($address_id, array $data) {
		return $this->put_json(self::ENDPOINT_ADDRESS_RESOURCE . "/{$address_id}", $data);
	}

	public function delete_address($address_id) {
		return $this->delete_json(self::ENDPOINT_ADDRESS_RESOURCE . "/{$address_id}");
	}

	/*-------------------- Khách hàng --------------------*/
	public function create_customer(array $data) {
		return $this->post_json_resource(self::ENDPOINT_CUSTOMER_RESOURCE, $data);
	}

	public function get_customer($customer_id) {
		return $this->get_json(self::ENDPOINT_CUSTOMER_RESOURCE . "/{$customer_id}");
	}

	public function update_customer(array $data) {
		return $this->put_json(self::ENDPOINT_UPDATE_CUSTOMER, $data);
	}

	public function delete_customer($customer_id) {
		return $this->delete_json(self::ENDPOINT_CUSTOMER_RESOURCE . "/{$customer_id}");
	}

	public function list_customers(array $filters = [], array $fields = ['name','customer_name','customer_group','customer_type']) {
		$query = [
			'fields' => wp_json_encode($fields),
			'filters' => !empty($filters) ? wp_json_encode($filters) : null,
		];
		return $this->get_json(self::ENDPOINT_CUSTOMER_RESOURCE, $query);
	}

	public function get_customer_loyalty($customer_id){
		$result = $this->get_json(self::ENDPOINT_GET_CUSTOMER_LOYALTY, ['customer' => $customer_id]);
		return is_wp_error($result) ? [] : $result;
	}

	public function get_all_loyalty_rank(){
		return $this->get_json(self::ENDPOINT_GET_ALL_LOYALTY_RANK);
	}

	/*-------------------- Đơn hàng --------------------*/
	public function get_pricing_rule(array $data) {
		$res = $this->post_json_resource(self::ENDPOINT_GET_PRICING_RULE, $data);
		if (is_wp_error($res) || !$res['success']) {
			return false;
		}
		return $res['data'];
	}

	public function make_sales_order(array $data) {
		$res = $this->post_json_resource(self::ENDPOINT_MAKE_SALES_ORDER, $data);
		if (is_wp_error($res) ) {
			return $res;
		}
			return $res['data'];
	}

	public function create_sales_order(array $data) {
		return $this->post_json_resource(self::ENDPOINT_SAVE_ORDER, $data);
	}

	public function get_sales_order($order_id) {
		return $this->get_json(self::ENDPOINT_SALES_ORDER_RESOURCE . "/{$order_id}");
	}

	public function update_sales_order($order_id, array $data) {
		return $this->put_json(self::ENDPOINT_SALES_ORDER_RESOURCE . "/{$order_id}", $data);
	}

	public function delete_sales_order($order_id) {
		return $this->delete_json(self::ENDPOINT_SALES_ORDER_RESOURCE . "/{$order_id}");
	}

	public function list_sales_orders(array $filters = [], array $fields = ['name','customer','transaction_date','status']) {
		$query = [
			'fields' => wp_json_encode($fields),
			'filters' => !empty($filters) ? wp_json_encode($filters) : null,
		];
		return $this->get_json(self::ENDPOINT_SALES_ORDER_RESOURCE, $query);
	}

	/*
	 * $cancel_data ['order_name' => $order_name , 'reason' : '']
	 */
	public function cancel_sales_order(array $cancel_data) {
		return $this->put_json(self::ENDPOINT_CANCEL_ORDER, $cancel_data);
	}

	public function calculate_delivery_fee(array $address = []) {
		return $this->post_json_resource(self::ENDPOINT_CALCULATE_DELIVERY_FEE, $address);
	}

	/*-------------------- Brands & Branches --------------------*/
	public function list_brands(){
		return $this->get_json(self::ENDPOINT_BRAND_RESOURCE, ['fields'=>'["*"]', 'limit_page_length'=>999999]);
	}

	public function list_branchs(){
		return $this->get_json(self::ENDPOINT_BRANCH_RESOURCE, ['fields'=>'["*"]', 'limit_page_length'=>999999]);
	}

	/*-------------------- Helper chung --------------------*/
	private function get_response_data(array $response, string $path) {
		if (is_wp_error($response)) return $response;

		$payload = $response['payload'] ?? [];

		if (strpos($path, '/api/method/') === 0) {
			return $payload['message'] ?? $payload;
		}
		if (strpos($path, '/api/resource/') === 0) {
			return $payload['data'] ?? $payload;
		}
		return $payload;
	}

	private function get_json(string $path, array $query = []) {
		if (empty($this->base_url)) return new WP_Error('no_config', 'ERP API URL is not configured.');
		$url = add_query_arg($query, "{$this->base_url}{$path}");
		$res = wp_remote_get($url, [
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Accept' => 'application/json',
			],
			'timeout' => 30, // Increase timeout to 30 seconds
		]);

		return $this->handle_response($res, $path);
	}

	private function post_json_resource(string $path, array $data) {
		if (empty($this->base_url)) return new WP_Error('no_config', 'ERP API URL is not configured.');
		$url = "{$this->base_url}{$path}";
		$res = wp_remote_post($url, [
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode($data),
			'timeout' => 30, // Increase timeout to 30 seconds
		]);
		return $this->handle_response($res, $path);
	}

	private function put_json(string $path, array $data) {
		if (empty($this->base_url)) return new WP_Error('no_config', 'ERP API URL is not configured.');
		$url = (strpos($path, 'http') === 0) ? $path : "{$this->base_url}{$path}";
		$res = wp_remote_request($url, [
			'method' => 'PUT',
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode($data),
			'timeout' => 30, // Increase timeout to 30 seconds
		]);
		return $this->handle_response($res, $path);
	}

	private function delete_json(string $path) {
		if (empty($this->base_url)) return new WP_Error('no_config', 'ERP API URL is not configured.');
		$url = (strpos($path, 'http') === 0) ? $path : "{$this->base_url}{$path}";
		$res = wp_remote_request($url, [
			'method' => 'DELETE',
			'headers' => [
				'Authorization' => "token {$this->jwt}",
			],
			'timeout' => 30, // Increase timeout to 30 seconds
		]);
		return $this->handle_response($res, $path);
	}

	private function handle_response($res, $path) {
		if (is_wp_error($res)) return $res;

		$code = wp_remote_retrieve_response_code($res);
		$body = wp_remote_retrieve_body($res);
		$payload = json_decode($body, true);

		if (is_array($payload) && isset($payload['exception'])) {
			return new WP_Error('erp_api_error', $payload['exception'], [
				'code' => $code,
				'exc_type' => $payload['exc_type'] ?? '',
				'details' => $payload,
			]);
		}

		if ($code >= 400) {
			$msg = "API Error: HTTP {$code}";
			if (!empty($payload['_server_messages'])) {
				$server_msg = json_decode(json_decode($payload['_server_messages'])[0]);
				$msg = is_object($server_msg) ? $server_msg->message : $server_msg;
			} elseif (trim($body)) {
				if ($payload['error']){
					$msg = $payload['error'];
				}else{
					$msg .= ": " . substr(trim($body), 0, 100);
				}

			}
			return new WP_Error('http_error', $msg, ['code' => $code, 'body' => $payload]);
		}

		return $this->get_response_data(['code' => $code, 'payload' => $payload], $path);
	}

	//--------- Mapping data
	public function map_erp_items($items, $get_stock = false) {
		return array_map(function($item) use ($get_stock) {
			return $this->map_custom_erp_product($item, $get_stock);
		}, $items);
	}

	private function map_custom_erp_product($item, $get_stock) {
		$item_codes = [];
		$item += ['sku' => $item['item_code'], 'gallery' => [], 'original_price' => 0, 'price' => 0,'variants_stock'=>[],'data_variants'=>[], 'base_url' => $this->base_url];
		$item['id'] = $item['item_code'];
		$item['gallery'] = array_map([$this, 'erp_item_image'], $item['image_urls'] ?? []);
		$item['image_360_urls'] = array_map([$this, 'erp_item_image'], $item['image_360_urls'] ?? []);
		$item['image'] = $this->get_item_image($item);
		$item['original_price'] = (($item['price_list_rate'] ?? 0) != ($item['rate'] ?? 0)) ? ($item['price_list_rate'] ?? 0) : 0;
		$item['price'] = $item['rate'] ?? 0;
		$item['title'] = $item['item_name'];
		$item['category_name'] = $item['item_group'];
		$item['discount'] = $item['discount_percentage'] ?? 0;
		$item['data_variants'] = $item['variants'] ?? null;
		$item['stock'] = [];

		if ($item['has_variants'] && !empty($item['variants'])){
			foreach ($item['variants'] as $variant){
				$item_codes[] = $variant['item_code'];
				$variant_img = $this->get_item_image($variant);
				if (!in_array($variant_img, $item['gallery'])){
					$item['gallery'][] = $variant_img;
				}
			}
			if($get_stock){
				$item['stock'] = $this->getStock($item_codes);
				$item['variants'] = $this->getGroupedVariantsByAttributes($item);
			}
		} else {
			$item['variants'] = [];
			if($get_stock) {
				$item['stock'] = $this->getStock([$item['item_code']]);
			}
		}

		return $item;
	}

	public function getGroupedVariantsByAttributes($item) {
		if (!isset($item['variants'])) return [];

		$attributeOptions = [];
		$variantCombinations = [];

		foreach ($item['variants'] as $variant) {
			if (!isset($variant['attributes']) || !is_array($variant['attributes'])) continue;

			$total_stock = 0;
			if (isset($item['stock'][$variant['item_code']])) {
				foreach ($item['stock'][$variant['item_code']] as $branch_stock) {
					$total_stock += $branch_stock[0];
				}
			}

			$baseInfo = [
				'sku' => $variant['item_code'] ?? null,
				'title' => $variant['item_name'],
				'price' => $variant['rate'] ?? null,
				'price_formatted' => function_exists('priceFormater') ? priceFormater($variant['rate']) : $variant['rate'],
				'original_price' => $variant['price_list_rate'] ?? 0,
				'original_price_formatted' => function_exists('priceFormater') ? priceFormater($variant['price_list_rate']) : $variant['price_list_rate'],
				'discount' => $variant['discount_percentage'] ?? 0,
				'stock' => $total_stock,
				'image_url' => isset($variant['image']) ? $this->erp_item_image($variant['image']) : null,
			];
			if (function_exists('product_price_save_html')) {
				$baseInfo['price_html'] = product_price_save_html($variant['rate'] ?? 0, $variant['price_list_rate'] ?? 0);
			}


			$attributesMap = [];
			foreach ($variant['attributes'] as $attribute) {
				$attrKey = $attribute['attribute'] ?? '';
				$attrVal = $attribute['attribute_value'] ?? '';
				if ($attrKey && $attrVal) {
					$attributeOptions[$attrKey][$attrVal] = true;
					$attributesMap[$attrKey] = $attrVal;
				}
			}

			$normalizedKey = $this->generateNormalizedKey($attributesMap);
			$variantCombinations[$normalizedKey] = array_merge($baseInfo, ['attributes' => $attributesMap]);
		}

		return [
			'attributes' => $attributeOptions,
			'combinations' => $variantCombinations,
		];
	}

	private function generateNormalizedKey($attributes) {
		ksort($attributes);
		$pairs = [];
		foreach ($attributes as $key => $value) {
			$pairs[] = "{$key}:{$value}";
		}
		return implode(' | ', $pairs);
	}

	public function erp_item_image($image) {
		if (!$image) {
			return null;
		}

		// If the constant is NOT defined OR is TRUE, return the URL as is from ERP.
		// This assumes ERP provides the full, absolute URL.
		if (!defined('SERVER_IMAGE_URL') || SERVER_IMAGE_URL === true) {
			return $image;
		}

		// If the constant is defined and is FALSE, it means we need to construct the URL.
		return filter_var($image, FILTER_VALIDATE_URL)
			? $image
			: $this->base_url . $image;
	}

	public function get_item_image($item) {
		$default_image = defined('IMG_URL') ? IMG_URL . 'No_Image_Available.jpg' : '';
		$image_path = $item['image'] ?? $item['image_url'] ?? null;

		if (!$image_path) {
			return $default_image;
		}

		// If the constant is NOT defined OR is TRUE, return the URL as is from ERP.
		if (!defined('SERVER_IMAGE_URL') || SERVER_IMAGE_URL === true) {
			return $image_path;
		}

		// If the constant is defined and is FALSE, construct the URL.
		if (filter_var($image_path, FILTER_VALIDATE_URL)) {
			return $image_path; // It's already a full URL, no need to prepend.
		}

		if (empty($this->base_url)) {
			return $default_image; // Cannot construct URL if base is missing.
		}

		return $this->base_url . $image_path;
	}
	public function update_order_status($payoo_var): WP_Error|false|array {
		if (!$this->endpoint_update_order_status || !isset($payoo_var)) {
			return false;
		}
		$url = add_query_arg($payoo_var, $this->endpoint_update_order_status);
		$res = wp_remote_get($url, [
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Accept' => 'application/json',
			],
			'timeout' => 30, // Increase timeout to 30 seconds
		]);
		return $res;
	}
}

