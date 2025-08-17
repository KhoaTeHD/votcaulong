<?php

class Breadcrumb {
	private static $instance = null; // Singleton instance
	private $breadcrumbs = [];
	private $separator = ' > ';
	private $api_handler; // Tham chiếu đến ERP_API_Handler

	private function __construct($separator = ' > ') {
		$this->separator = $separator;
		$this->api_handler = $this->getApiHandler();
		$this->generate();
	}

	private function __clone() {}
	public function __wakeup() {}

	public static function getInstance($separator = ' > ') {
		if (self::$instance === null) {
			self::$instance = new self($separator);
		}
		return self::$instance;
	}

	private function getApiHandler() {
		static $erp_api = null;

		if ($erp_api === null) {
			$erp_api = new ERP_API_Client();
		}

		return $erp_api;
	}


	/**
	 * Generate breadcrumbs based on the current context
	 */
	private function generate() {
		$this->addBreadcrumb('Trang chủ', home_url());

		if (get_query_var('product_id')) {
			$this->addProductBreadcrumb();
		}
		elseif (get_query_var('product_cate_id')) {
			$this->addCategoryBreadcrumb();

		}
		// Trang khác
		elseif (is_singular()) {
			$this->addSingularBreadcrumb();
		} elseif (is_archive()) {
			$this->addBreadcrumb(post_type_archive_title('', false));
		} elseif (is_search()) {
			$this->addBreadcrumb('Kết quả tìm kiếm: ' . get_search_query());
		} elseif (is_404()) {
			$this->addBreadcrumb('Không tìm thấy trang');
		}
	}

	/**
	 * Add breadcrumb for a product
	 */
	private function addProductBreadcrumb() {
		$product_id = get_query_var('product_id');
		$product = $this->api_handler->get_product($product_id);
		if (is_wp_error($product)) {
			$this->addBreadcrumb($product->get_error_message());
			return;
		}
		if (isset($product['item_group'])) {
			// Lấy thông tin danh mục của sản phẩm
			$category = $this->api_handler->get_category( $product['item_group'], false );
			if ( ! is_wp_error( $category ) ) {
				$category_url = ProductUrlGenerator::createCategoryUrl( $category['name'], $category['id'] );
				$this->addBreadcrumb( $category['name'], $category_url );
			}
		}
//		$this->addBreadcrumb('Sản phẩm', site_url('/products'));

		$product_url = ProductUrlGenerator::createProductUrl($product['title'], $product['id']);
		$this->addBreadcrumb($product['title'], $product_url);
	}


	/**
	 * Add breadcrumb for a category
	 */
	private function addCategoryBreadcrumb() {
		$category_id = get_query_var('product_cate_id');
		$category = $this->api_handler->get_category($category_id,true);
		if (is_wp_error($category)) {
			$this->addBreadcrumb($category->get_error_message());
			return;
		}
		
		// my_debug($category);
		$ancestors = get_ancestors($category_id, 'pro-cate');
		$ancestors = array_reverse($ancestors);
		foreach ($ancestors as $ancestor_id) {
			$ancestor_category = $this->api_handler->get_category($ancestor_id);
			if (!is_wp_error($ancestor_category)) {
				$ancestor_url = ProductUrlGenerator::createCategoryUrl($ancestor_category['name'], $ancestor_category['id']);
				$this->addBreadcrumb($ancestor_category['name'], $ancestor_url);
			}
		}

		$category_url = ProductUrlGenerator::createCategoryUrl($category['name'], $category['id']);
		$this->addBreadcrumb($category['name'], $category_url);
	}

	/**
	 * Add breadcrumb for singular posts
	 */
	private function addSingularBreadcrumb() {
		$post = get_queried_object();
		$categories = get_the_category($post->ID);
		
		if (!empty($categories)) {
			$category = $categories[0];
			$this->addBreadcrumb($category->name, get_category_link($category->term_id));
		}
		$this->addBreadcrumb(get_the_title($post));
	}

	/**
	 * Add a breadcrumb to the list
	 */
	private function addBreadcrumb($label, $url = '') {
		$this->breadcrumbs[] = [
			'label' => is_string($label) ? $label : '',
			'url' => is_string($url) ? $url : ''
		];
	}

	/**
	 * Render breadcrumbs
	 */
	public function render() {
		$output = [];
		foreach ($this->breadcrumbs as $breadcrumb) {
			if (is_array($breadcrumb) && isset($breadcrumb['label'])) {
				if (!empty($breadcrumb['url'])) {
					$output[] = '<a href="' . esc_url($breadcrumb['url']) . '">' . esc_html($breadcrumb['label']) . '</a>';
				} else {
					$output[] = '<span>' . esc_html($breadcrumb['label']) . '</span>';
				}
			} else {
				error_log("Breadcrumb không hợp lệ: " . print_r($breadcrumb, true));
			}
		}
		echo '<div class="breadcrumbs">';
		echo implode($this->separator, $output);
		echo '</div>';
	}

	public function getBreadcrumbs() {
		return $this->breadcrumbs;
	}
}
