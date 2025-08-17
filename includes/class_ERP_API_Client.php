<?php
/**
 * ERP_API_Client
 */
class ERP_API_Client {
	private $base_url;
	private $jwt;
	private $products_per_page;

	public function __construct(array $args=[]) {
		$this->base_url = rtrim($args['base_url'] ?? '', '/');
		$this->jwt = $args['jwt'] ?? '';
		$this->products_per_page = get_field('products_per_page','options')??8;
		$this->getConfigAPI();
	}
	private function getConfigAPI(){
		$erp_API_config = get_field('erp_api','options');
		if ($erp_API_config){
			$this->base_url = $erp_API_config['domain'];
			$this->jwt = $erp_API_config['api_key'].':'.$erp_API_config['api_secret'];
		}
	}
	/*-------------------- Danh mục, sản phẩm --------------------*/
	public function list_all_item_groups() {
		return $this->get_json('/api/method/inno_erp.controller.wp.list_all_item_groups');
	}

	public function new_products(array $item_groups = []): array {
		$cache_key = 'new_products_list';
		$cached = get_transient($cache_key);

		if ($cached !== false) {
//			return $cached;
		}

		$results = [];

		if (empty($item_groups)) {
			$data = $this->browse_items(['limit_page_length' => 20]);
			if (is_wp_error($data)) return [];
			$results = $data['data'] ?? [];
		} else {
			foreach ($item_groups as $group) {
				$group_items = $this->browse_items([
					'item_groups' => [$group->name],
					'limit_page_length' => 5
				]);

				if (!empty($group_items['data'])) {
					$results = array_merge($results, $group_items['data']);
				}
			}
		}

		if (!empty($results)) {
//			my_debug($results);
			$products = $this->map_erp_items($results,false);
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
		return $this->get_json('/api/method/inno_erp.controller.wp.list_variant', $query);
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
		$result = $this->get_json('/api/method/inno_erp.controller.wp.browse_items', $p);
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
		return $this->get_json('/api/method/inno_erp.controller.wp.search_items', $query);
	}

	public function get_item_detail($item_code) {
		return $this->get_json("/api/resource/Item/{$item_code}");
	}
	public function get_product($product_id, $get_stock = true) {

		$cache_key = 'product_' . $product_id;
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
			//return $cached;
		}

		$response = $this->get_json('/api/method/inno_erp.controller.wp.detail_item', [
			'item_code' => $product_id
		]);
//		 my_debug($response);
		if (!is_wp_error($response) && $response) {
			$product = $this->map_custom_erp_product($response, $get_stock);
			// my_debug($response['message'][0]);
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

		$response = $this->get_json('resource/Item Group/' . ($category_id));

		if (!is_wp_error($response)) {
			$category = $this->map_erp_category($response);
			set_transient($cache_key, $category, 12 * HOUR_IN_SECONDS);
			return $category;
		}

		return $response;
	}
	public function get_category($cate_name,$byId = false){

		if($byId){
			// $terms = get_terms( $cate_name,'pro_cate' );
			$terms = get_terms( array(
				'include' => array($cate_name),
				'taxonomy' => 'pro_cate',
				'hide_empty' => false,
			) );
		}else{
			$terms = get_terms( array(
				'name' => $cate_name,
				'taxonomy' => 'pro_cate',
				'hide_empty' => false,
			) );

		}

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$term_array = array();
			$term = $terms[0];
			$term_array = array(
				'term_id' => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'id' => $term->term_id,
			);
			return $term_array;
		} else {
			return array();
		}

	}
	public function getStock(array $item_codes=[]){
		if (!is_array($item_codes)){
			return;
		}
		$end_point = '/api/method/inno_erp.controller.wp.get_stock_availability';
		$p= ['item_codes' => wp_json_encode($item_codes)];
		return $this->get_json($end_point, $p);
	}
	public function get_all_products($page=1,$limit_items=0){

		$limit = ($page>1?$page*$this->products_per_page:0);
		$limit_items = $limit_items?$limit_items:$this->products_per_page;
		$products = $this->browse_items( [
			'limit_start'  => $limit,
			'limit_page_length' => $limit_items
		]);
		$total_page = (int) ceil($products['total_count']/$limit_items);
		if (!is_wp_error($products) && isset($products['data']) ) {
			$products['data'] = array_map(function ($item) {
				$item['image'] = $this->get_item_image($item);
				return $item;
			}, $products['data']);
			$return_data = ['total_pages'=>$total_page,'products' => $products['data'],'filters'=>''??[]];
			return $return_data;
		}
		return [];
	}
	public function get_products_by_category($item_group,$page=1,$limit_items=0){
		$cache_key = 'products_by_category_' . sanitize_title($item_group);
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
//			return $cached;
		}
		$limit = ($page>1?$page*$this->products_per_page:0);
		$limit_items = $limit_items?$limit_items:$this->products_per_page;
		$products = $this->browse_items( [
			'item_groups' => [$item_group],
			'limit_start'  => $limit,
			'limit_page_length' => $limit_items
		]);
		if (!is_wp_error($products) && isset($products['data']) ) {
			$total_page = (int) ceil($products['total_count']/$limit_items);
			$products['data'] = array_map(function ($item) {
				$item['image'] = $this->get_item_image($item);
				return $item;
			}, $products['data']);
			$return_data = ['total_pages'=>$total_page,'products' => $products['data'],'filters'=>''??[]];
			set_transient($cache_key, $return_data, HOUR_IN_SECONDS);
			return $return_data;
		}
		return [];
	}
	public function get_products_by_brand($brand_name,$page=1,$limit_items=0){
		$cache_key = 'products_by_brand_' . sanitize_title($brand_name);
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
//			return $cached;
		}
		$limit = ($page>1?$page*$this->products_per_page:0);
		$limit_items = $limit_items?$limit_items:$this->products_per_page;
		$products = $this->browse_items( [
			'brands' => wp_json_encode([$brand_name]),
			'limit_start'  => $limit,
			'limit_page_length' => $limit_items
		]);
		$total_page = (int) ceil($products['total_count']/$limit_items);
		if (!is_wp_error($products) && isset($products['data']) ) {
			$products['data'] = array_map(function ($item) {
				$item['image'] = $this->get_item_image($item);
				return $item;
			}, $products['data']);
			$return_data = ['total_pages'=>$total_page,'products' => $products['data'],'filters'=>''??[]];
			set_transient($cache_key, $return_data, HOUR_IN_SECONDS);
			return $return_data;
		}
		return [];
	}
	public function get_filters(array $item_groups =[]){
		if (!is_array($item_groups)) return;
		$end_point = '/api/method/inno_erp.controller.wp.list_variant';
		$p=[];
		if ($item_groups){
			$p['item_groups'] = wp_json_encode($item_groups);
			return $this->get_json($end_point, $p);
		}

		return $this->get_json($end_point);
	}
	/**
	 * Lấy danh sách sản phẩm liên quan dựa trên cùng nhóm với sản phẩm hiện tại
	 *
	 * @param int $product_id ID của sản phẩm hiện tại
	 * @param int $limit Số lượng sản phẩm muốn lấy (mặc định: 8)
	 * @param int $page Trang hiện tại (mặc định: 1)
	 * @return array|WP_Error Mảng chứa thông tin sản phẩm liên quan hoặc WP_Error nếu có lỗi
	 */
	public function get_related_products($product_id, $limit = 8, $page = 1) {

		$cache_key = 'related_products_' . sanitize_title($product_id);
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
//			return $cached;
		}
		// Lấy thông tin sản phẩm hiện tại để biết nhóm của nó
		$product_info = $this->get_product($product_id);
		if (is_wp_error($product_info)) {
			return $product_info; // Trả về lỗi nếu không lấy được thông tin sản phẩm
		}

		// Lấy category_id của sản phẩm hiện tại
		$category_id = isset($product_info['category_name']) ? $product_info['category_name'] : null;

		if (!$category_id) {
			return new WP_Error('no_category', __('Không tìm thấy danh mục của sản phẩm', LANG_ZONE));
		}

		// Sử dụng phương thức get_products_by_category để lấy sản phẩm cùng danh mục
		$category_products = $this->get_products_by_category($category_id, $page, $limit+1);

		if (is_wp_error($category_products)) {
			return $category_products; // Trả về lỗi nếu không lấy được sản phẩm
		}

		// Lọc bỏ sản phẩm hiện tại khỏi danh sách kết quả
		$related_products = [];
		if (isset($category_products['products']) && is_array($category_products['products'])) {
			foreach ($category_products['products'] as $product) {
				if ($product['item_code'] != $product_id) {
					$related_products[] = $product;
				}
			}
		}

		$related_products = array_slice($related_products, 0, $limit);
//		my_debug($related_products);
//		$related_data = [
//			'products' => $related_products,
//			'total_pages' => $category_products['total_pages'] ?? 1,
//			'current_page' => $page,
//			'total_products' => count($related_products),
//			'filters' => $category_products['filters'] ?? []
//		];
		set_transient($cache_key, $related_products, HOUR_IN_SECONDS);
		return $related_products;
	}
	/*-------------------- Địa chỉ --------------------*/
	public function list_address_locations($force_refresh = false) {
		$end_point ='/api/resource/Address Location';
		$cache_key = 'erp_cached_locations';
		if (!$force_refresh) {
			$cached = get_transient($cache_key);
			if ($cached !== false) return $cached;
		}

		$response = $this->get_json($end_point,['limit_page_length' => 999999]);
		if (!is_wp_error($response)) {
			set_transient($cache_key, $response, DAY_IN_SECONDS);
		}

		return $response;
	}

	public function list_wards_by_location($location, $force_refresh = false) {
		$cache_key = 'erp_cached_wards_' . ($location ? md5($location) : 'all');
		if (!$force_refresh) {
			$cached = get_transient($cache_key);
			if ($cached !== false) return $cached;
		}

		$endpoint = '/api/resource/Address Ward';
		$params = [
			'limit_page_length' => 999999,
			'fields' => json_encode(['name', 'ward', 'location']),
		];
		if (!empty($location)) {
			$params['filters'] = json_encode([
				['location', '=', $location]
			]);
		}

		$response = $this->get_json($endpoint, $params);

		if (!is_wp_error($response)) {
			set_transient($cache_key, $response, DAY_IN_SECONDS);
		}

		return $response;
	}

	public function create_address(array $data) {
		return $this->post_json_resource('/api/resource/Address', $data);
	}

	public function get_address($address_id) {
		return $this->get_json("/api/resource/Address/{$address_id}");
	}

	public function update_address($address_id, array $data) {
		return $this->put_json("/api/resource/Address/{$address_id}", $data);
	}

	public function delete_address($address_id) {
		return $this->delete_json("/api/resource/Address/{$address_id}");
	}

	/*-------------------- Khách hàng --------------------*/
	public function create_customer(array $data) {
		return $this->post_json_resource('/api/resource/Customer', $data);
	}

	public function get_customer($customer_id) {
		return $this->get_json("/api/resource/Customer/{$customer_id}");
	}

	public function update_customer(array $data) {
		return $this->put_json('/api/method/inno_erp.controller.wp.update_customer', $data);
	}

	public function delete_customer($customer_id) {
		return $this->delete_json("/api/resource/Customer/{$customer_id}");
	}

	public function list_customers(array $filters = [], array $fields = ['name','customer_name','customer_group','customer_type']) {
		$query = [
			'fields' => wp_json_encode($fields),
			'filters' => !empty($filters) ? wp_json_encode($filters) : null,
		];
		return $this->get_json('/api/resource/Customer', $query);
	}
	public function get_customer_loyalty($customer_id){
		$query = [
			'customer' => $customer_id
		];
		$result= $this->get_json("/api/method/inno_erp.inno_account.overrides.loyalty_program.loyalty_program.get_customer_loyalty_tier_info",$query);
		if(is_wp_error($result)) return [];
		return $result;
	}
	public function get_all_loyalty_rank(){
		//'https://a403071bc5ee.ngrok-free.app/api/method/inno_erp.inno_account.overrides.loyalty_program.loyalty_program.get_loyalty_program_rules';
		$result= $this->get_json("/api/method/inno_erp.inno_account.overrides.loyalty_program.loyalty_program.get_all_loyalty_program");
//		if(is_wp_error($result)) return [];
		return $result;
	}

	/*-------------------- Đơn hàng --------------------*/
	public function create_sales_order(array $data) {
		return $this->post_json_resource('/api/method/inno_erp.controller.wp.save_order', $data);
	}

	public function get_sales_order($order_id) {
		return $this->get_json("/api/resource/Sales Order/{$order_id}");
	}

	public function update_sales_order($order_id, array $data) {
		return $this->put_json("/api/resource/Sales Order/{$order_id}", $data);
	}

	public function delete_sales_order($order_id) {
		return $this->delete_json("/api/resource/Sales Order/{$order_id}");
	}

	public function list_sales_orders(array $filters = [], array $fields = ['name','customer','transaction_date','status']) {
		$query = [
			'fields' => wp_json_encode($fields),
			'filters' => !empty($filters) ? wp_json_encode($filters) : null,
		];
		return $this->get_json('/api/resource/Sales Order', $query);
	}

	public function cancel_sales_order(string $order_name) {
		// Gọi method custom của ERP (PUT)
		return $this->put_json('/api/method/inno_erp.controller.wp.cancel_order', ['order_name' => $order_name]);
	}
	/**
	 * Tính phí vận chuyển qua ERP.
	 * @param array $address Mảng địa chỉ: [
	 *     'address_line1' => 'Số 12, đường Nguyễn Văn A',
	 *     'custom_address_location' => 'Hồ Chí Minh - Thành phố Thủ Đức',
	 *     'custom_ward' => 'Phường Linh Tây-26818'
	 * ]
	 * @return array|WP_Error|null Kết quả phí vận chuyển, ví dụ: ['GHTK' => 20000], hoặc WP_Error nếu lỗi.
	 */
	public function calculate_delivery_fee(array $address = []) {
		$end_point = '/api/method/inno_erp.controller.wp.calculate_delivery_fee';
		return $this->post_json_resource($end_point, $address);
	}
	//-------------- List brand
	public function list_brands(){
		$end_point="/api/resource/Brand";
		return $this->get_json($end_point,['fields'=>'["*"]','limit_page_length'=>999999]);
	}
	//----------- List branch
	public function list_branchs(){
		$end_point ="/api/resource/Branch";
		return $this->get_json($end_point,['fields'=>'["*"]','limit_page_length'=>999999]);
	}
	/*-------------------- Helper chung --------------------*/
	private function get_response_data(array $response, string $path) {
		if (is_wp_error($response)) return $response;

		$payload = $response['payload'] ?? [];

		// Xác định key dựa trên path
		if (strpos($path, '/api/method/') === 0) {
			return $payload['message'] ?? $payload; // Ưu tiên 'message' cho method endpoints
		} elseif (strpos($path, '/api/resource/') === 0) {
			return $payload['data'] ?? $payload; // Ưu tiên 'data' cho resource endpoints
		}

		return $payload; // Fallback
	}

	private function get_json(string $path, array $query = []) {
		$url = add_query_arg($query, "{$this->base_url}{$path}");
		$res = wp_remote_get($url, [
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Accept' => 'application/json',
			],
		]);

		if (is_wp_error($res)) return $res;

		$code = wp_remote_retrieve_response_code($res);
		$body = wp_remote_retrieve_body($res);
		$payload = json_decode($body, true);

		// Nếu có JSON với trường exception (vẫn giữ lại)
		if (is_array($payload) && isset($payload['exception'])) {
			return new WP_Error(
				'erp_api_error',
				$payload['exception'],
				[
					'code' => $code,
					'exc_type' => $payload['exc_type'] ?? '',
					'details' => $payload,
				]
			);
		}

		// Nếu code >= 400 (hoặc chỉ >=500, tùy nhu cầu)
		if ($code >= 400) {
			if(!empty($payload['_server_messages'])){
				$mess_arr = json_decode(json_decode($payload['_server_messages'])[0]);
				$mess = is_object($mess_arr)?$mess_arr->message:$mess_arr;
			}else{
				$mess = "API trả về lỗi HTTP $code"
				        . (trim($body) ? (": ".substr(trim($body), 0, 100)) : '');
			}
			$msg = $mess;

			return new WP_Error(
				'http_error',
				$msg,
				[
					'code' => $code,
					'body' => json_decode($body),
				]
			);
		}

		$response = [
			'code' => $code,
			'payload' => $payload,
		];

		return $this->get_response_data($response, $path);
	}



	private function post_json_resource(string $path, array $data) {
		$url = "{$this->base_url}{$path}";
		$res = wp_remote_post($url, [
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode($data),
		]);

		if (is_wp_error($res)) return $res;
		$code = wp_remote_retrieve_response_code($res);
		$body = wp_remote_retrieve_body($res);
		$payload = json_decode($body, true);

		if (is_array($payload) && isset($payload['exception'])) {
			return new WP_Error(
				'erp_api_error',
				$payload['exception'],
				[
					'code' => $code,
					'exc_type' => $payload['exc_type'] ?? '',
					'details' => $payload,
				]
			);
		}

		$response = [
			'code' => $code,
			'payload' => $payload,
		];


		return $this->get_response_data($response, $path);
	}

	private function put_json(string $path, array $data) {
		$url = (strpos($path, 'http') === 0) ? $path : "{$this->base_url}{$path}";
		$res = wp_remote_request($url, [
			'method' => 'PUT',
			'headers' => [
				'Authorization' => "token {$this->jwt}",
				'Content-Type' => 'application/json',
			],
			'body' => wp_json_encode($data),
		]);

		if (is_wp_error($res)) return $res;
		$code = wp_remote_retrieve_response_code($res);
		$body = wp_remote_retrieve_body($res);
		$payload = json_decode($body, true);

		if (is_array($payload) && isset($payload['exception'])) {
			return new WP_Error(
				'erp_api_error',
				$payload['exception'],
				[
					'code' => $code,
					'exc_type' => $payload['exc_type'] ?? '',
					'details' => $payload,
				]
			);
		}

		$response = [
			'code' => $code,
			'payload' => $payload,
		];


		return $this->get_response_data($response, $path);
	}

	private function delete_json(string $path) {
		$url = (strpos($path, 'http') === 0) ? $path : "{$this->base_url}{$path}";
		$res = wp_remote_request($url, [
			'method' => 'DELETE',
			'headers' => [
				'Authorization' => "token {$this->jwt}",
			],
		]);

		if (is_wp_error($res)) return $res;

		$response = [
			'code' => wp_remote_retrieve_response_code($res),
			'payload' => json_decode(wp_remote_retrieve_body($res), true),
		];

		return $this->get_response_data($response, $path);
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
//		$item['image'] = $item['image_url'][0] ?? null;
		$item['gallery'] = $item['image_urls']??[];
		$item['image'] = $this->get_item_image($item);
		$item['original_price'] = ($item['price_list_rate'] != $item['rate']) ? $item['price_list_rate'] : 0;
		$item['price'] = $item['rate'];
		$item['title'] = $item['item_name'];
		$item['category_name'] = $item['item_group'];
		$item['discount'] = $item['discount_percentage'] ?? 0;
//		$item['variants'] = [];
		$item['data_variants'] = $item['variants']??null;
		$item['stock']=[];
		if ($item['has_variants']){
			foreach ($item['variants'] as $variant){
				$item_codes[] = $variant['item_code'];
				$variant_img = $this->get_item_image($variant);
				if (!in_array($variant_img,$item['gallery'])){
					$item['gallery'][] = $variant_img;
				}

			}
			if($get_stock){
				$item['stock'] = $this->getStock($item_codes);
				$item['variants'] = $this->getGroupedVariantsByAttributes($item);
			}
		}else{
			$item['variants']= [];
			if($get_stock) {
				$item['stock'] = $this->getStock( [ $item['item_code'] ] );
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
			$total_stock =  0;
			if ($item['stock'][$variant['item_code']]) {
				foreach ($item['stock'][$variant['item_code']] as $branch_stock) {
					$total_stock+=$branch_stock[0];
				}
			}
			$baseInfo = [
				'sku' => $variant['item_code'] ?? null,
				'title' => $variant['item_name'],
				'price' => $variant['rate'] ?? null,
				'price_formatted' => priceFormater($variant['rate']) ?? null,
				'original_price' => $variant['price_list_rate'] ?? 0,
				'original_price_formatted' => priceFormater($variant['price_list_rate']) ?? 0,
				'discount' => $variant['discount_percentage'] ?? 0,
				'stock' => $total_stock,
				'image_url' => isset($variant['image']) ? $this->erp_item_image($variant['image']) : null,
			];
			$baseInfo['price_html']=product_price_save_html($variant['rate'],$variant['price_list_rate']);

			$attributesMap = [];

			foreach ($variant['attributes'] as $attribute) {

				$attrKey = $attribute['attribute'] ?? '';
				$attrVal = $attribute['attribute_value'] ?? '';

				if ($attrKey && $attrVal) {
					$attributeOptions[$attrKey][$attrVal] = true;
					$attributesMap[$attrKey] = $attrVal;
				}
			}

//			if (isset($attributeOptions['Size'])) {
//				$sizeValue = $attributeOptions['Size'];
//				unset($attributeOptions['Size']);
//				$attributeOptions['Size'] = $sizeValue;
//			}
//			if (isset($attributesMap['Size'])) {
//				$sizeValueMap = $attributesMap['Size'];
//				unset($attributesMap['Size']);
//				$attributesMap['Size'] = $sizeValueMap;
//			}

			// Tạo key tổ hợp đã chuẩn hóa (dù người dùng chọn theo thứ tự nào thì key vẫn giống nhau)
			$normalizedKey = $this->generateNormalizedKey($attributesMap);

			$variantCombinations[$normalizedKey] = array_merge($baseInfo, [
				'attributes' => $attributesMap
			]);
		}



		return [
			'attributes' => $attributeOptions,
			'combinations' => $variantCombinations,
		];
	}

	private function generateNormalizedKey($attributes) {
		ksort($attributes); // Sắp xếp theo key (attribute_key)
		$pairs = [];

		foreach ($attributes as $key => $value) {
			$pairs[] = "{$key}:{$value}";
		}

		return implode(' | ', $pairs);
	}
	public function erp_item_image($image){
		return filter_var($image, FILTER_VALIDATE_URL)?$image:$this->base_url.$image;
	}
	public function get_item_image($item) {

		if (isset($item['image'])) {
			return filter_var($item['image'], FILTER_VALIDATE_URL)?$item['image']:$this->base_url.$item['image'];
		}elseif (isset($item['image_url'])) {
			return filter_var($item['image_url'], FILTER_VALIDATE_URL)?$item['image_url']:$this->base_url.$item['image_url'];
		}
		return $item['image'] ?? IMG_URL.'No_Image_Available.jpg';
	}
}

