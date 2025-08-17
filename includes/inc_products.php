<?php
$product_attr_name = [
	'brands' => __('Brands', LANG_ZONE),
	'branches' => __('Branches', LANG_ZONE),
	'attributes' => __('Attributes', LANG_ZONE),
	'color' => __('Color', LANG_ZONE),
	'weight' => __('Weight', LANG_ZONE),
	'play_style' => __('Play style', LANG_ZONE),
	'play_mode' => __('Play mode', LANG_ZONE),
	'skill_level' => __('Skill level', LANG_ZONE),
];

function load_category_template($template) {
	if (get_query_var('product_cate_id')) {
		wp_enqueue_script('product-js', get_template_directory_uri() . '/assets/js/category.js', ['main-js'], THEME_VER, true);
		return locate_template('template-parts/product-category.php');
	}
	return $template;
}
add_filter('template_include', 'load_category_template');
//------------
function load_product_template($template) {
	if (get_query_var('product_id')) {
		wp_enqueue_script('product-js', get_template_directory_uri() . '/assets/js/single-product.js', ['main-js'], THEME_VER, true);
		wp_enqueue_style('product-css', get_template_directory_uri() . '/assets/css/single-product.css', ['theme-css'], THEME_VER);

		return locate_template('template-parts/product-detail.php');
	}
	return $template;
}
add_filter('template_include', 'load_product_template');
//================================
function handle_compare_products($template) {
	$compare_products = get_query_var( 'compare_products' );
	if ( ! empty( $compare_products ) ) {
		wp_enqueue_script('compare', get_template_directory_uri() . '/assets/js/compare.js', ['custom-libs-js'], THEME_VER, true);
		return locate_template('template-parts/compare-products.php');

	}
	return $template;
}
add_action( 'template_include', 'handle_compare_products' );
//--------------------------------------
function custom_meta_title() {
	$custom_header = '';
	if (get_query_var('product_id')) {
		$product_name = rawurldecode(get_query_var('product_name'));
//		echo '<title>' . ($product_name) . '</title>';
		$custom_header = 'Sản phẩm: ' . ($product_name);
		echo '<meta name="description" content="Thông tin về sản phẩm ' . ($product_name) . '">';
	} elseif (get_query_var('product_cate_id')) {
		$category_name = rawurldecode(get_query_var('product_cate_name'));
//		echo '<title>' . ($category_name) . '</title>';
		echo '<meta name="description" content="Danh mục ' . $category_name . ' và các sản phẩm liên quan">';
		$custom_header = 'Danh mục: ' . ($category_name);
	}else{
//		echo '<title>'.wp_title(' ', false, 'right').'</title>';
	}
	echo '<meta name="custom-header" content="' . esc_attr($custom_header) . '">';
}
add_action('wp_head', 'custom_meta_title', 1);
//-------------------------------------
function custom_dynamic_title_and_header($title) {
	global $custom_header;

	if (get_query_var('product_id')) {
		$product_name = get_query_var('product_name');
		$title = 'Sản phẩm: ' . esc_html($product_name);
		$custom_header = 'Sản phẩm: ' . esc_html($product_name);
	} elseif (get_query_var('product_cate_id')) {
		$category_name = get_query_var('product_cate_name');
		$title = 'Danh mục: ' . esc_html($category_name);
		$custom_header = 'Danh mục: ' . esc_html($category_name);
	}

	return $title;
}
//add_filter('pre_get_document_title', 'custom_dynamic_title_and_header');


//======================================
function priceFormater($price, $curency = '₫'){
	return number_format($price) . '<sup>'.$curency.'</sup>';
}
//---------------------------------------
function product_price_save_html($price, $original_price ){
	$html = '';
	$html  = '<p ><span>Giá bán: </span><span class="new">'.priceFormater($price).'</span></p>';
	if ($original_price > $price){
		$saved = $original_price - $price;

		$html  .= '<p class="small"><span>Giá niêm yết:</span> <span class="old">'.priceFormater($original_price).'</span></p>';
		$html  .= '<p class="small"><span>Bạn tiết kiệm:</span> <span class="money-saved">'.priceFormater($saved).'</span></p>';
	}
	return $html;
}
//-----------------------------------------
function generate_filters($products) {
	$filters = [
		'categories' => [],
		'brands' => [],
		'branches' => [],
		'attributes' => [],
		'price_ranges' => []
	];

	$prices = [];

	foreach ($products as $product) {
		// Lọc danh mục
		if (isset($product['category_name']) && !in_array($product['category_name'], $filters['categories'])) {
			$filters['categories'][] = $product['category_name'];
		}

		// Lọc thương hiệu
		if (isset($product['brand']) && !in_array($product['brand'], $filters['brands'])) {
			$filters['brands'][] = $product['brand'];
		}

		// Lọc chi nhánh
		if (isset($product['branch']) && is_array($product['branch'])) {
			foreach ($product['branch'] as $branch) {
				$branch_id = sanitize_title($branch['name']);
				if ( !in_array($branch_id, $filters['branches'])) {
					$filters['branches'][$branch_id] = ['name'=>$branch['name'],'id'=>$branch_id];
				}
			}
		}


		// Lọc thuộc tính
		if (isset($product['specifications'])) {
			foreach ($product['specifications'] as $spec) {
				$key = $spec['spec_name'];
				$value = $spec['spec_value'];
				if (!isset($filters['attributes'][$key])) {
					$filters['attributes'][$key] = [];
				}
				if (!in_array($value, $filters['attributes'][$key])) {
					$filters['attributes'][$key][] = $value;
				}
			}
		}

		// Thu thập giá
		if (isset($product['price'])) {
			$prices[] = $product['price'];
		}
		
	}
	// Sắp xếp các mảng
	sort($filters['categories']);
	sort($filters['brands']);

	// Sort branches by name
	uasort($filters['branches'], function($a, $b) {
		return strcmp($a['name'], $b['name']);
	});


	// Sắp xếp thuộc tính và giá trị của chúng
	ksort($filters['attributes']); // Sắp xếp theo key (tên thuộc tính)
	foreach ($filters['attributes'] as $key => &$values) {
		sort($values); // Sắp xếp giá trị của từng thuộc tính
	}
	unset($values); // important: break the reference
	// Tạo các khoảng giá dựa trên giá trị nhỏ nhất và lớn nhất
	if (!empty($prices)) {
		$min_price = min($prices);
		$max_price = max($prices);

		// Làm tròn giá trị nhỏ nhất và lớn nhất đến bội số của 10,000
		$min_price = floor($min_price / 10000) * 10000;
		$max_price = ceil($max_price / 10000) * 10000;

		// Phân khoảng giá (tùy chỉnh theo nhu cầu)
		$step = 500000; // Khoảng cách mỗi khoảng giá (100,000 VND)
		for ($i = $min_price; $i <= $max_price; $i += $step) {
			$filters['price_ranges'][] = [
				'min' => $i,
				'max' => $i + $step - 1
			];
		}
	}

	return $filters;
}
//===================
function sort_products($products, $sort_criteria) {
	usort($products, function($a, $b) use ($sort_criteria) {
		switch ($sort_criteria) {
			case 'best-discount': // Khuyến mãi tốt
				$discount_a = ($a['original_price'] - $a['price']) / $a['original_price'];
				$discount_b = ($b['original_price'] - $b['price']) / $b['original_price'];
				return $discount_b <=> $discount_a;

			case 'price-asc': // Giá tăng dần
				return $a['price'] <=> $b['price'];

			case 'price-desc': // Giá giảm dần
				return $b['price'] <=> $a['price'];

			case 'newest': // Sản phẩm mới nhất (giả định id lớn hơn là mới hơn)
				return $b['id'] <=> $a['id'];

			case 'best-seller': // Bán chạy nhất (giả định field 'sold')
				return $b['sold'] <=> $a['sold'];

			default:
				return 0;
		}
	});

	return $products;
}
//-------------
function extractSoldNumber(string $soldText): int {
	if (!$soldText) return 0;
	preg_match('/\d+/', $soldText, $matches);
	return (int) ($matches[0] ?? 0);
}
//-----------------------------
function footer_cart_content(){
	echo '<div class="offcanvas offcanvas-end" tabindex="-1" id="shoppingCartCanvas" aria-labelledby="Shopping-cart">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="shoppingCartLabel">'.__('Shopping cart',LANG_ZONE).'</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div id="shopping-cart">
	  <ul id="cart-items">
	
	  </ul>
	  <p class="cart-total-line">'.__('Total:',LANG_ZONE).' <span id="cart-total"></span></p>
	  <div class="d-flex items-justified-space-between px-2 ">
	  	<a href="/gio-hang" id="viewShoppingCart-button" class="btn btn-sm btn-primary" role="button">'.__('Shopping cart / Checkout',LANG_ZONE).'</a>
	
		</div>
	</div>
	<div id="empty-cart" style="display: none;">'.__('There are no items in your cart.',LANG_ZONE).'</div>
  </div>
</div>';
}
add_action('wp_footer', 'footer_cart_content', 1);
//---------------------------------
add_action('admin_menu', 'create_pro_cate_menu');
function create_pro_cate_menu() {
	add_menu_page(
		'Product category', // Tiêu đề của menu
		'Product category', // Tên của menu
		'manage_categories', // Quyền truy cập
		'product_category', // Đường dẫn đến trang quản lý taxonomy
		'pro_cate_management_page', // Function callback (nếu cần)
		'dashicons-category', // Icon của menu
		25 // Vị trí của menu
	);
}

function pro_cate_management_page() {
	include(get_template_directory() . '/backend/pro_cate_management.php');
}
//----
/**
 * Tạo mã HTML cho phân trang.
 *
 * @param int $current_page Trang hiện tại.
 * @param int $total_pages Tổng số trang.
 * @param int $page_range Số trang hiển thị ở mỗi bên của trang hiện tại (mặc định là 1).
 * @param string $base_url URL cơ sở để tạo link (ví dụ: '?page=' hoặc '/products/page/').
 * @return string Chuỗi HTML của phân trang hoặc chuỗi rỗng nếu không cần phân trang.
 */
function render_pagination(int $current_page, int $total_pages, int $page_range = 1, string $base_url_param = '?page='): string
{

	$generate_page_link = function($page_number) use ($base_url_param) {
		return $base_url_param . $page_number;
	};

	// --- Kiểm tra đầu vào và điều kiện hiển thị phân trang ---
	if ($total_pages <= 1) {
		return ''; // Không cần hiển thị nếu chỉ có 1 trang hoặc ít hơn
	}

	// Đảm bảo trang hiện tại hợp lệ
	$current_page = max(1, min($current_page, $total_pages));

	// --- Bắt đầu xây dựng HTML ---
	$html = '<div class="product-pagination my-3 d-flex justify-content-center">';

	// --- Nút Previous ---
	$prev_link = ($current_page > 1) ? $generate_page_link($current_page - 1) : '#';
	$prev_disabled_class = ($current_page <= 1) ? 'disabled' : '';
	$html .= '<a href="' . $prev_link . '" role="button" class="btn pagination-btn page-prev-btn ' . $prev_disabled_class . '">';
	$html .= '<i class="bi bi-chevron-left"></i>';
	$html .= '</a>';

	// --- Logic hiển thị số trang và dấu "..." ---
	$show_ellipsis_start = false;
	$show_ellipsis_end = false;

	for ($i = 1; $i <= $total_pages; $i++) {
		// Điều kiện hiển thị trang số $i:
		// 1. Là trang đầu tiên (1)
		// 2. Là trang cuối cùng ($total_pages)
		// 3. Nằm trong khoảng $page_range xung quanh trang hiện tại
		if ($i == 1 || $i == $total_pages || ($i >= $current_page - $page_range && $i <= $current_page + $page_range)) {
			if ($i == $current_page) {
				// Trang hiện tại -> dùng span và class active
				$html .= '<span class="btn pagination-btn active">' . $i . '</span>';
			} else {
				// Trang khác -> dùng link
				$html .= '<a href="' . $generate_page_link($i) . '" role="button" class="btn pagination-btn">' . $i . '</a>';
			}
			// Đánh dấu lại khu vực đã hiển thị để tránh ellipsis thừa
			// Nếu đang hiển thị một trang trước vùng ellipsis dự kiến bên trái, nghĩa là không cần ellipsis trái nữa
			if ($i < $current_page - $page_range) {
				$show_ellipsis_start = true;
			}
			// Nếu đang hiển thị một trang sau vùng ellipsis dự kiến bên phải, nghĩa là không cần ellipsis phải nữa
			if ($i > $current_page + $page_range) {
				$show_ellipsis_end = true;
			}
		}
		// Hiển thị dấu "..." bên trái (sau trang 1 và trước khoảng quanh trang hiện tại)
		elseif ($i < $current_page && !$show_ellipsis_start) {
			// Chỉ hiển thị một lần khi gặp trang ngay trước vùng `- page_range`
			if ($i == $current_page - $page_range - 1) {
				$html .= '<span class="more-pages pagination-btn">...</span>';
				$show_ellipsis_start = true;
			}
		}
		// Hiển thị dấu "..." bên phải (sau khoảng quanh trang hiện tại và trước trang cuối)
		elseif ($i > $current_page && !$show_ellipsis_end) {
			// Chỉ hiển thị một lần khi gặp trang ngay sau vùng `+ page_range`
			if ($i == $current_page + $page_range + 1) {
				$html .= '<span class="more-pages pagination-btn">...</span>';
				$show_ellipsis_end = true;
			}
		}
	} // Hết vòng lặp for

	// --- Nút Next ---
	$next_link = ($current_page < $total_pages) ? $generate_page_link($current_page + 1) : '#';
	$next_disabled_class = ($current_page >= $total_pages) ? 'disabled' : '';
	$html .= '<a href="' . $next_link . '" role="button" class="btn pagination-btn page-next-btn ' . $next_disabled_class . '">';
	$html .= '<i class="bi bi-chevron-right"></i>';
	$html .= '</a>';

	// --- Kết thúc HTML ---
	$html .= '</div>';

	return $html;
}