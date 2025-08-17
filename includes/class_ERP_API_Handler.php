<?php
class ERP_API_Handler {
	private $api_url;
	private $api_key;
	private $api_secret;
	private $use_fake_data;
	private $data_path;
	private $auth_token;
	private $base_url;

	public function __construct($use_fake_data = true, $api_key = '20e1f05dbc9e215', $api_secret = '3a37d035a3b7f1b') {
		$this->data_path = get_template_directory() . '/data/';
		$this->use_fake_data = $use_fake_data;
		$this->api_url = "https://erp.webliondk.com/api/";
		$this->base_url = str_replace('/api/','',$this->api_url) ;
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->getConfigAPI();
		$this->auth_token = base64_encode($this->api_key . ':' . $this->api_secret);
	}

	// ====================
	// CORE METHODS
	// ====================
	private function getConfigAPI(){
		$erp_API_config = get_field('erp_api','options');
		if ($erp_API_config){
			$this->api_url = $erp_API_config['domain'].'/api/';
			$this->base_url = $erp_API_config['domain'];
			$this->api_key = $erp_API_config['api_key'];
			$this->api_secret = $erp_API_config['api_secret'];
		}
	}

	private function get_fake_data($endpoint, $filterCallback = null) {
		$data = $this->call_fake_api($endpoint);

		if (!is_wp_error($data) && $filterCallback) {
			return array_filter($data, $filterCallback);
		}

		return $data;
	}


	private function read_json($file_name) {
		$file_path = $this->data_path . '/' . $file_name;
		if (!file_exists($file_path)) {
			return new WP_Error('file_not_found', "File $file_name không tồn tại.");
		}
		$content = file_get_contents($file_path);
		return json_decode($content, true);
	}

	private function call_real_api($endpoint, $method = 'GET', $params = []) {
		$url = $this->api_url . $endpoint;

		$args = [
			'headers' => [
				'Authorization' => 'Basic ' . $this->auth_token,
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			],
			'method' => $method,
			'timeout' => 30
		];
//		my_debug($params);
		if ($method === 'GET' && !empty($params)) {
			$query_string = '';
			foreach ($params as $key => $value) {
				if ($key === 'filters') {
					$query_string .= "filters=" . (json_encode($value)) . "&"; // Mã hóa JSON cho filters
				} elseif ($key === 'fields') {
					$query_string .= "fields=" . (json_encode($value)) . "&"; // Mã hóa JSON cho fields
				} else {
					// Xử lý các tham số khác (nếu có)
					$query_string .= ($key) . "=" . ($value) . "&";
				}
			}
			$query_string = rtrim($query_string, '&');
			$url .= '?' . $query_string;
		}
		if (in_array($method, ['POST', 'PUT']) && !empty($params)) {
			$args['body'] = json_encode($params);
		}
		$response = wp_remote_request($url, $args);

		if (is_wp_error($response)) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$body = json_decode(wp_remote_retrieve_body($response), true);

		if ($response_code >= 400) {
			return new WP_Error('api_error', $body['message'] ?? 'Lỗi từ EPR API', [
				'status' => $response_code,
				'data' => $body
			]);
		}

		return $body;
	}

	private function call_fake_api($endpoint) {
		switch ($endpoint) {
			case 'products':
				return $this->read_json('products_complete.json');
			case 'categories':
				return $this->read_json('categories.json');
			case 'branches':
				return $this->read_json('branches.json');
			case 'brands':
				return $this->read_json('brands.json');
			case 'flashsale':
				return $this->read_json('flashsale.json');
			default:
				return new WP_Error('invalid_endpoint', "Endpoint $endpoint không hợp lệ.");
		}
	}

	public function call($endpoint, $params = []) {
		if ($this->use_fake_data) {
			return $this->call_fake_api($endpoint);
		}
		return $this->call_real_api($endpoint, 'GET', $params);
	}

	// ====================
	// PRODUCT METHODS
	// ====================

	public function get_all_products() {
		if ($this->use_fake_data) {
			return $this->get_fake_data('products');
		}
		/*$response = $this->call('resource/Item', [
			'fields' => json_encode(["name", "item_name", "item_group", "standard_rate", "brand", "description"]),
			'limit_page_length' => 0
		]);*/
		$response = $this->call('method/tada_sales.api.item.get_items', [
//			'item_group' => ($category_id)
		]);
//		my_debug($response);
		if (!is_wp_error($response)) {
			$products = $this->map_erp_items($response['message']);
//			set_transient($cache_key, $products, HOUR_IN_SECONDS);
			return $products;
		}


		return is_wp_error($response) ? $response : $this->map_erp_items($response['data']);
	}

	public function get_products_by_category($category_id, $page = 1) {
		if ($this->use_fake_data) {
			return $this->get_fake_data('products', function($product) use ($category_id) {
				return $product['category_id'] == $category_id;
			});
		}
		$cache_key = 'products_by_category_' . sanitize_title($category_id);
		$cached = get_transient($cache_key);

		if ($cached !== false && !$this->use_fake_data) {
//			return $cached;
		}
		$response = $this->call('method/tada_sales.api.item.get_items', [
			'item_group' => ($category_id),
			'page'  => $page
		]);
//		my_debug($response);
		if (!is_wp_error($response) && isset($response['message']['list_items'])) {
			$products = $this->map_erp_items($response['message']['list_items']);
			set_transient($cache_key, $products, HOUR_IN_SECONDS);
			return ['total_pages'=>$response['message']['total_pages'],'products' => $products,'filters'=>$response['message']['filters']??[]];
		}
		return $response;
	}
	public function get_product($product_id, $by_sku = false) {
		if ($this->use_fake_data) {
			$products = $this->get_fake_data('products');
			foreach ($products as $product) {
				if ($by_sku){
					if ($product['sku'] == $product_id) {
						$product['image_url'] = $this->get_item_image($product_id);
						return $product;
					}
				}else{
					if ($product['id'] == $product_id) {
						$product['image_url'] = $this->get_item_image($product_id);
						return $product;
					}
				}

			}
			return new WP_Error('product_not_found', "Không tìm thấy sản phẩm");
		}
		$cache_key = 'product_' . $product_id;
		$cached = get_transient($cache_key);

		if ($cached !== false && !$this->use_fake_data) {
			return $cached;
		}

		$response = $this->call('method/tada_sales.api.item.get_items', [
			'item_code' => $product_id
		]);
//		 my_debug($response);
		if (!is_wp_error($response) && $response['message']['list_items']) {
			$product = $this->map_erp_product(($response['message']['list_items'][0]));
			// my_debug($response['message'][0]);
			set_transient($cache_key, $product, HOUR_IN_SECONDS);
			return $product;
		}

		return new WP_Error('product_not_found', __("Product not found!",LANG_ZONE));;
	}


	// ====================
	// CATEGORY METHODS
	// ====================

	public function get_all_product_categories($use_cached = true, $limit = -1, $offset = 0) {
		if ($this->use_fake_data) {
			return $this->get_fake_data('categories');
		}

		$cache_key = 'erp_all_categories_' . $limit . '_' . $offset; // Include limit and offset in cache key
		$cached = get_transient($cache_key);

		if (($cached !== false && !$this->use_fake_data) && $use_cached) {
			return $cached;
		}

		$fields = json_encode(["name", "item_group_name", "parent_item_group", "image"]);

		$args = [
			'fields' => $fields,
		];

		if ($limit > 0) {
			$args['limit_page_length'] = $limit;
		}

		if ($offset > 0) {
			$args['limit_start'] = $offset; // Or whatever your ERP uses for offset
		}


		$response = $this->call('resource/Item Group', $args);

		if (!is_wp_error($response)) {
			$categories = $this->map_erp_categories($response['data']);

			// Only cache if limit is set to avoid caching partial data.
			if ($limit > 0) {
				set_transient($cache_key, $categories, 12 * HOUR_IN_SECONDS);
			}
			return $categories;
		}

		return $response;
	}

	public function get_category_erp($category_id) {
		if ($this->use_fake_data) {
			$categories = $this->get_fake_data('categories');
			foreach ($categories as $category) {
				if ($category['id'] == $category_id) {
					return $category;
				}
			}
			return new WP_Error('category_not_found', "Không tìm thấy danh mục");
		}
		$cache_key = 'category_' . $category_id;
		$cached = get_transient($cache_key);

		if ($cached !== false && !$this->use_fake_data) {
			return $cached;
		}

		$response = $this->call('resource/Item Group/' . ($category_id));

		if (!is_wp_error($response)) {
			$category = $this->map_erp_category($response['data']);
			set_transient($cache_key, $category, 12 * HOUR_IN_SECONDS);
			return $category;
		}

		return $response;
	}
	public function get_category($cate_name,$byId = false){
		if ($this->use_fake_data) {
			$categories = $this->get_fake_data('categories');
			foreach ($categories as $category) {
				if ($category['id'] == $cate_name) {
					return $category;
				}
			}
			return new WP_Error('category_not_found', "Không tìm thấy danh mục");
		}
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
			return array(); // Trả về mảng rỗng nếu không tìm thấy
		}

	}
	// ====================
	// FILTER & SEARCH
	// ====================



	private function apply_fake_filters($products, $filters) {
		return array_filter($products, function ($product) use ($filters) {
			foreach ($filters as $key => $values) {
				if (empty($values)) continue;

				switch ($key) {
					case 'filter_category':
						if (!isset($product['category_id']) || !in_array($product['category_id'], $values)) {
							return false;
						}
						break;

					case 'filter_price':
						$in_range = false;
						foreach ($values as $range) {
							list($min, $max) = explode('_', $range);
							$product_price = $product['price'] ?? 0;
							if ($product_price >= $min && $product_price <= $max) {
								$in_range = true;
								break;
							}
						}
						if (!$in_range) return false;
						break;

					case 'filter_branches':

						if (!is_array($product['branch']) || empty($product['branch'])) {
							return false;
						}

						// Chuyển đổi tên chi nhánh sang dạng sanitize_title
						$branch_slugs = array_map(function($branch) {
							return sanitize_title($branch['name']);
						}, $product['branch']);

						if (!array_intersect($branch_slugs, $values)) {
							return false;
						}
						break;

					case 'filter_brands':
						$brand_slug = sanitize_title($product['brand'] ?? '');
						if (!in_array($brand_slug, $values)) {
							return false;
						}
						break;

					case 'attr_color':
						$color = sanitize_title($product['attributes']['color'] ?? '');
						if (!in_array($color, $values)) {
							return false;
						}
						break;

					case 'attr_weight':
						$weight = $product['attributes']['weight'] ?? null;
						if (!$weight || !in_array($weight, $values)) {
							return false;
						}
						break;

					case 'attr_skill_level':
						$skill = sanitize_title($product['attributes']['skill_level'] ?? '');
						if (!in_array($skill, $values)) {
							return false;
						}
						break;

					case 'attr_play_style':
						$style = sanitize_title($product['attributes']['play_style'] ?? '');
						if (!in_array($style, $values)) {
							return false;
						}
						break;

					case 'attr_play_mode':
						$mode = sanitize_title($product['attributes']['play_mode'] ?? '');
						if (!in_array($mode, $values)) {
							return false;
						}
						break;

					default:
						if (strpos($key, 'attr_') === 0) {
							$attr_name = substr($key, 5);
							$attr_value = sanitize_title($product['attributes'][$attr_name] ?? '');
							if (!in_array($attr_value, $values)) {
								return false;
							}
						}
						break;
				}
			}
			return true;
		});
	}

	public function filter_products($filters) {
		if ($this->use_fake_data) {
			$products = $this->get_fake_data('products');
			return $this->apply_fake_filters($products, $filters);
		}

		// Xử lý cho API thật
		$erp_filters = [];
		foreach ($filters as $key => $values) {
			if (empty($values)) continue;

			switch ($key) {
				case 'filter_category':
					$erp_filters[] = ['item_group', 'in', $values];
					break;

				case 'filter_price':
					$or_filters = [];
					foreach ($values as $range) {
						list($min, $max) = explode('_', $range);
						$or_filters[] = ['standard_rate', '>=', $min];
						$or_filters[] = ['standard_rate', '<=', $max];
					}
					$erp_filters[] = ['or' => $or_filters];
					break;

				case 'filter_branches':
					$erp_filters[] = ['branch', 'in', $values];
					break;

				case 'filter_brands':
					$erp_filters[] = ['brand', 'in', $values];
					break;

				case 'attr_color':
					$erp_filters[] = ['color', 'in', $values];
					break;

				case 'attr_weight':
					$erp_filters[] = ['weight', 'in', $values];
					break;

				case 'attr_skill_level':
					$erp_filters[] = ['skill_level', 'in', $values];
					break;

				case 'attr_play_style':
					$erp_filters[] = ['play_style', 'in', $values];
					break;

				case 'attr_play_mode':
					$erp_filters[] = ['play_mode', 'in', $values];
					break;

				default:
					if (strpos($key, 'attr_') === 0) {
						$field = substr($key, 5);
						$erp_filters[] = [$field, 'in', $values];
					}
					break;
			}
		}
		$erp_filters[] = ['variant_of','=',''];
		$erp_filters[] = ['disabled','=','0'];
		$response = $this->call('resource/Item', [
			'filters' => ($erp_filters),
			'limit_page_length' => 0,
			'fields' => ["*"]
		]);

		return is_wp_error($response) ? $response : $this->map_erp_items($response['data']);
	}


	// ====================
	// MAPPING METHODS
	// ====================

	private function map_erp_items($items) {
		return array_map([$this, 'map_custom_erp_product'], $items);
	}
	private function map_custom_erp_product($item) {
		$item += ['sku' => null, 'image_url' => [], 'original_price' => 0, 'price' => 0];
		$item['id'] = $item['sku'];
		$item['image'] = $item['image_url'][0] ?? null;
		$item['gallery'] = $item['image_url'];
		$item['image_url'] = $this->get_item_image($item);
		$item['original_price'] = ($item['original_price'] != $item['price']) ? $item['original_price'] : 0;
		$item['variants'] = $this->getGroupedVariantsByAttributes($item);
		return $item;
	}
	public function getGroupedVariantsByAttributes($item) {
		if (!isset($item['variant'])) return [];

		$attributeOptions = [];
		$variantCombinations = [];

		foreach ($item['variant'] as $variant) {
			if (!isset($variant['attributes']) || !is_array($variant['attributes'])) continue;

			$baseInfo = [
				'sku' => $variant['sku'] ?? null,
				'price' => $variant['price'] ?? null,
				'price_formatted' => priceFormater($variant['price']) ?? null,
				'original_price' => $variant['original_price'] ?? 0,
				'original_price_formatted' => priceFormater($variant['original_price']) ?? 0,
				'discount' => $variant['discount'] ?? 0,
				'stock' => $variant['stock'] ?? null,
				'image_url' => isset($variant['image_url'][0]) ? $this->erp_item_image($variant['image_url'][0]) : null,
			];
			$baseInfo['price_html']=product_price_save_html($variant['price'],$variant['original_price']);

			$attributesMap = [];

			foreach ($variant['attributes'] as $attribute) {
				$attrKey = $attribute['attribute_key'] ?? '';
				$attrVal = $attribute['attribute_value'] ?? '';

				if ($attrKey && $attrVal) {
					$attributeOptions[$attrKey][$attrVal] = true;
					$attributesMap[$attrKey] = $attrVal;
				}
			}
			if (isset($attributeOptions['Size'])) {
				$sizeValue = $attributeOptions['Size'];
				unset($attributeOptions['Size']);
				$attributeOptions['Size'] = $sizeValue;
			}
			if (isset($attributesMap['Size'])) {
				$sizeValueMap = $attributesMap['Size'];
				unset($attributesMap['Size']);
				$attributesMap['Size'] = $sizeValueMap;
			}

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



	private function map_erp_product($item) {
		$extend = [
			'image_url' => $this->get_item_image($item),
			"gallery" => $item['image_url'] ?? [],
			"view_360" => $item['image_360_url'] ?? [],
			'id'    => $item['id'] ?? $item['sku'],
			'variants' => $this->getGroupedVariantsByAttributes($item),
			// 'discount' => round($item['discount'],0)?? 0,

		];
		if ( $cate = $this->get_category($item['category_name']) ) {
			$item['category_id'] = $cate['term_id'];
		}
		$item['discount'] = round($item['discount'],0)?? 0;
		// $item['gallery']

		// foreach ($item as $key => $value) {
		// 	if (preg_match('/^custom_attach_image(_\d+)?$/', $key)) {
		// 		$extend["gallery"][] = filter_var($value, FILTER_VALIDATE_URL)?$value:$this->base_url.$value;
		// 	} elseif (preg_match('/^custom_attach_360_image_\d+$/', $key)) {
		// 		$extend["view_360"][] = filter_var($value, FILTER_VALIDATE_URL)?$value:$this->base_url.$value;
		// 	}
		// }
		foreach ($extend['gallery'] as $key => $value) {
			if (filter_var($value, FILTER_VALIDATE_URL)) {
				$extend['gallery'][$key] = $value;
			} else {
				$extend['gallery'][$key] = $this->base_url . $value;
			}
		}
		foreach ($extend['view_360'] as $key => $value) {
			if (filter_var($value, FILTER_VALIDATE_URL)) {
				$extend['view_360'][$key] = $value;
			} else {
				$extend['view_360'][$key] = $this->base_url . $value;
			}
		}
		return array_merge($item, $extend);
// 		return [
// 			'id' => $item['item_code'],
// 			'sku' => $item['item_code'],
// 			'title' => $item['item_name'],
// 			'description' => $item['description'] ?? '',
// 			'price' => $item['standard_rate'],
// 			'category_id' => $item['item_group'],
// 			'brand' => $item['brand'] ?? '',
// 			'image_url' => $this->get_item_image($item),
// //			'image_url' =>$item['image'],
// 			'gallery' =>$extend['gallery'],
// 			'view_360' =>$extend['view_360'],
// 			'video' =>$extend['video'],
// 			'attributes' => [
// 				'color' => $item['color'] ?? '',
// 				'weight' => $item['weight'] ?? '',
// 				'skill_level' => $item['skill_level'] ?? '',
// 				'play_style' => $item['play_style'] ?? ''
// 			]
// 		];
	}

	private function map_erp_categories($categories) {
		return array_map([$this, 'map_erp_category'], $categories);
	}

	private function map_erp_category($category) {
		return [
			'id' => $category['name'],
			'name' => $category['name'],
			'parent_id' => $category['name'],
			'image_url' => $this->get_category_image($category)
		];
	}

	// ====================
	// UTILITY METHODS
	// ====================

	public function get_item_image($item) {
		if ($this->use_fake_data) {
			$rand_num = rand(1,8);
			return IMG_URL.'san-pham/product_'.$rand_num.'.jpg';
		}
		if (isset($item['image_url'][0])) {
			return filter_var($item['image_url'][0], FILTER_VALIDATE_URL)?$item['image_url'][0]:$this->base_url.$item['image_url'][0];
		}
		return $item['image'] ?? IMG_URL.'No_Image_Available.jpg';
	}
	public function erp_item_image($image){
		return filter_var($image, FILTER_VALIDATE_URL)?$image:$this->base_url.$image;
	}

	private function get_category_image($category) {
		if ($this->use_fake_data) {
			return $this->read_json('categories.json')[0]['image_url'] ?? IMG_URL.'default-category.jpg';
		}
		if (isset($category['image'])) {
			return filter_var($category['image'], FILTER_VALIDATE_URL)?$category['image']:$this->base_url.$category['image'];
		}
		return IMG_URL.'No_Image_Available.jpg';
	}

	// ====================
	// LEGACY METHODS
	// ====================

	public function get_products_by_brand($brand_name, $page=1) {
		if ($this->use_fake_data) {
			return $this->get_fake_data('products', function($product) use ($brand_name) {
				return $product['brand'] == $brand_name;
			});
		}
		$cache_key = 'products_by_brand_' . sanitize_title($brand_name);
		$cached = get_transient($cache_key);

		if ($cached !== false && !$this->use_fake_data) {
			//return $cached;
		}
		$response = $this->call('method/tada_sales.api.item.get_items', [
			'brand' => $brand_name,
			'items_per_page' => 50,
			'page'  => $page
		]);
//		my_debug($response);
		if (!is_wp_error($response) && isset($response['message']['list_items'])) {
			$products = $this->map_erp_items($response['message']['list_items']);
			set_transient($cache_key, $products, HOUR_IN_SECONDS);
			return ['total_pages'=>$response['message']['total_pages'],'products' => $products];
		}
		return $response;
		
	}

	public function new_products() {
		if ($this->use_fake_data) {
			return $this->get_fake_data('products');
		}

		/*$response =  $this->call('resource/Item', [
			'order_by' => 'creation DESC',
			'limit_page_length' => 10,
			'fields' => json_encode(["*"])
		]);*/
		$response = $this->call('method/tada_sales.api.item.get_items', [
			'new_products' => true,
		]);
//		my_debug($response);
		if (!is_wp_error($response) && isset($response['message']['list_items'])) {
			$products = $this->map_erp_items($response['message']['list_items']);
//			set_transient($cache_key, $products, HOUR_IN_SECONDS);
//			my_debug($products);
			return $products;
		}

		return $response;
	}

	public function flashsale() {
		if ($this->use_fake_data) {
			return $this->get_fake_data('flashsale');
		}
//		return $this->call('flashsale');
	}
	public function search_products($keyword, $limit=6) {
		$keyword = mb_strtolower($keyword, 'UTF-8');

		if ($this->use_fake_data) {
			$all_products = $this->get_all_products();

			$filtered_products = array_filter($all_products, function ($product) use ($keyword) {
				return stripos(mb_strtolower($product['title'], 'UTF-8'), $keyword) !== false ||
				       stripos(mb_strtolower($product['sku'], 'UTF-8'), $keyword) !== false;
			});

			return array_values($filtered_products);
		}
		// return $keyword;
		// Tìm kiếm trong API
		$response = $this->call('method/vcls_draft.api.search_item.search_item', [
			'search_text' => $keyword,
			'limit' => $limit,
		]);
		
		// my_debug($response);
		return is_wp_error($response) ? $response : ($response['message']);
	}

	/**
	 * Creates a new customer in the ERP system.
	 *
	 * @param string $name Customer's full name.
	 * @param string $email Customer's email.
	 * @param string $phone Customer's phone number.
	 * @param string $gender Customer's gender ('male', 'female', 'other').
	 * @return array Result array with 'name' on success or 'error' on failure.
	 */
	public function create_customer($name, $email, $phone, $gender) {
		if ($this->use_fake_data) {
			return ['name' => 'CUST-FAKE-' . rand(1000, 9999)];
		}

		$endpoint = 'resource/Customer';
		$data = [
			'customer_name'   => $name,
			'gender'          => ucfirst($gender), 
			'customer_type'   => 'Individual',
			'customer_group'  => 'Individual', 
			'territory'       => 'All Territories',
			'email_id'        => $email,
			'mobile_no'       => $phone,
		];

		$response = $this->call_real_api($endpoint, 'POST', $data);
		
		if (is_wp_error($response)) {
			return ['error' => $response->get_error_message()];
		}
		if (isset($response['data']['name'])) {
			return $response['data']['name'];
		} else {
			error_log('ERP Create Customer Response Error: ' . print_r($response, true));
			return ['error' => 'Không thể tạo khách hàng hoặc định dạng phản hồi không đúng.'];
		}
	}
	/**
	 * Updates an existing customer in the ERP system.
	 *
	 * @param string $customer_id The unique name/ID of the customer in ERP.
	 * @param array $data An associative array of fields to update (e.g., ['customer_name' => 'New Name', 'mobile_no' => '09xxxxxxxx']).
	 * @return array Result array with 'name' on success or 'error' on failure.
	 */
	public function update_customer($customer_id, $data) {
		if ($this->use_fake_data) {
			return ['name' => $customer_id]; // Giả lập thành công
		}

		if (empty($customer_id) || empty($data) || !is_array($data)) {
			return ['error' => 'Thiếu ID khách hàng hoặc dữ liệu cập nhật không hợp lệ.'];
		}

		// Endpoint để cập nhật khách hàng cụ thể
		$endpoint = 'resource/Customer/' . urlencode($customer_id);

		// Dữ liệu cần gửi đi (chỉ gửi các trường cần cập nhật)
		$update_data = $data;

		// Gọi API bằng phương thức PUT
		$response = $this->call_real_api($endpoint, 'PUT', $update_data);

		if (is_wp_error($response)) {
			return ['error' => $response->get_error_message()];
		}
		// Kiểm tra cấu trúc response trả về từ API của bạn sau khi cập nhật
		if (isset($response['data']['name'])) {
			return ['name' => $response['data']['name']]; // Trả về name của khách hàng đã cập nhật
		} else {
			// Ghi log lỗi hoặc trả về lỗi cụ thể hơn nếu cần
			error_log('ERP Update Customer Response Error: ' . print_r($response, true));
			return ['error' => 'Không thể cập nhật khách hàng hoặc định dạng phản hồi không đúng.'];
		}
	}
	/**
	 * Checks if a customer exists in the ERP system by phone AND name.
	 *
	 * @param string $phone The phone number to check.
	 * @param string $name The customer name to check.
	 * @return array|false|WP_Error Returns customer data array if found, false if not found, WP_Error on API error.
	 */
	public function check_customer($phone, $name) { // Thay đổi tham số
		if ($this->use_fake_data) {
			return false; // Giả lập không tìm thấy
		}

		if (empty($phone) || empty($name)) { // Kiểm tra cả phone và name
			return new WP_Error('missing_identifier', 'Cần cung cấp cả số điện thoại và tên khách hàng để kiểm tra.');
		}

		$endpoint = 'resource/Customer';

		$params = [
			'filters' => [
				['mobile_no', '=', $phone],     // Điều kiện số điện thoại
				['customer_name', '=', $name]   // Điều kiện tên khách hàng
			],
			'fields' => ['name', 'customer_name', 'email_id', 'mobile_no'], // Chỉ lấy các trường cần thiết
			'limit_page_length' => 1 // Chỉ cần 1 kết quả để xác nhận tồn tại
		];

		$response = $this->call_real_api($endpoint, 'GET', $params);
		if (is_wp_error($response)) {

			return $response;
		}

		// Kiểm tra xem API có trả về dữ liệu khách hàng không
		
		if (!empty($response['data'])) {
			// Khách hàng tồn tại, trả về thông tin khách hàng đầu tiên tìm thấy
			return $response['data'][0];
		} else {
			// Không tìm thấy khách hàng
			return false;
		}
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
		if ($this->use_fake_data) {
			// Trả về dữ liệu giả lập nếu đang ở chế độ fake data
			return [];
		}
		$cache_key = 'related_products_' . sanitize_title($product_id);
		$cached = get_transient($cache_key);

		if ($cached !== false ) {
			return $cached;
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
		$category_products = $this->get_products_by_category($category_id, $page, $limit);
		
		if (is_wp_error($category_products)) {
			return $category_products; // Trả về lỗi nếu không lấy được sản phẩm
		}
		
		// Lọc bỏ sản phẩm hiện tại khỏi danh sách kết quả
		$related_products = [];
		if (isset($category_products['products']) && is_array($category_products['products'])) {
			foreach ($category_products['products'] as $product) {
				if ($product['id'] != $product_id) {
					$related_products[] = $product;
				}
			}
		}
	
		$related_products = array_slice($related_products, 0, $limit);
		$related_data = [
			'products' => $related_products,
			'total_pages' => $category_products['total_pages'] ?? 1,
			'current_page' => $page,
			'total_products' => count($related_products),
			'filters' => $category_products['filters'] ?? []
		];
		set_transient($cache_key, $related_data, HOUR_IN_SECONDS);
		return $related_data;
	}
	//-------------------------------- NEW API method
	public function list_all_item_groups($use_cached = true){
		$endpoint = 'method/inno_erp.controller.wp.list_all_item_groups';
		$response = $this->call_real_api($endpoint);
		if (!is_wp_error($response)) {
			$categories = $response['message'];
			$cache_key = 'erp_all_item_groups'; // Include limit and offset in cache key
			$cached = get_transient($cache_key);
			if ($cached !== false &&$use_cached ) {
				return $cached;
			}
			set_transient($cache_key, $categories, 12 * HOUR_IN_SECONDS);
			return $categories;
		}

	}
	
}