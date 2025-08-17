<?php

class Product {
    private $id;
    private $sku;
    private $category_id;
    private $category_name;
    private $brand;
    private $title;
    private $price;
    private $original_price;
    private $discount;
    private $image_url;
    private $labels;
    private $badge;
    private $sold;
    private $branch_data; // Raw branch data from ERP
    private $attributes_raw; // Raw attributes data from ERP
    private $gallery;
    private $video_url;
    private $view_360;
    public $variations; // Giữ public theo code gốc, dù nên là private/protected
    public $stock; // Giữ public theo code gốc, dù nên là private/protected
    private $combo_products;
    private $description_raw; // Raw description from data
    private $features_raw;    // Raw features from data
	private $has_variants, $data_variants, $base_url, $free_item_data,$select_pricing_rules, $out_of_stock;

    // --- Lazy loaded properties ---
    // Khởi tạo là null trong constructor
    private $lazy_reviews_aggregate = null; // Cache cho tổng số và điểm TB đánh giá
    private $lazy_branch_details = null;    // Cache cho thông tin chi tiết chi nhánh đã xử lý
    private $lazy_attributes_processed = null; // Cache cho thuộc tính đã xử lý
    private $lazy_brand_detail = null;      // Cache cho thông tin brand chi tiết
    private $lazy_related_products = null;  // Cache cho sản phẩm liên quan (có thể cần cache theo limit/page)
    private $lazy_is_liked_by_user = [];    // Cache cho trạng thái yêu thích (lưu theo user ID)
    private $lazy_total_likes = null;       // Cache cho tổng lượt thích
    private $lazy_description_processed = null; // Cache cho description đã xử lý filter
    private $lazy_features_processed = null;    // Cache cho features đã xử lý filter
    private $lazy_review_list_cache = [];   // Cache cho danh sách đánh giá (lưu theo params)
    // --- End Lazy loaded properties ---


    public function __construct(array $data) {
        // Constructor chỉ gán dữ liệu thô, không gọi hàm tốn kém
        $this->id = $data['item_code'] ?? null;
        $this->sku = $data['item_code'] ?? null;
        $this->category_id = $data['category_id'] ?? null;
        $this->category_name = $data['category_name'] ?? ($this->category_id ? (string) $this->category_id : null);
        $this->brand = $data['brand'] ?? null;
        $this->title = $data['item_name'] ?? null;
        $this->price = $data['rate'] ?? null;
        $this->original_price = $data['price_list_rate'] ?? null;
        $this->discount = $data['discount'] ?? null;
        $this->image_url = $data['image'] ??  $data['image_url'];
        $this->labels = $data['labels'] ?? [];
        $this->badge = $data['badge'] ?? null;
        $this->sold = $data['sold'] ?? null;
        $this->branch_data = $data['stock'] ?? null;
        $this->attributes_raw = $data['attributes'] ?? [];
        $this->gallery = $data['gallery'] ?? [];
        $this->view_360 = $data['image_360_urls'] ?? [];
        $this->variations = $data['variants'] ?? [];
        $this->data_variants = $data['data_variants'] ?? null;
        $this->stock = $data['stock'] ?? [];
        $this->video_url = $data['custom_video'] ?? '';
        $this->combo_products = $data['combo_products'] ?? [];
        $this->description_raw = $data['description'] ?? '';
        $this->features_raw = $data['specifications'] ?? '';
		$this->has_variants = (boolean)$data['has_variants'];
		$this->free_item_data = $data['free_item_data']??null;
		$this->select_pricing_rules = $data['select_pricing_rules']??null;
		$this->out_of_stock = $data['out_of_stock'] ?? null;
	    $erp_API_config = get_field('erp_api','options');
	    if ($erp_API_config){
		    $this->base_url = $erp_API_config['domain'];
	    }
        // Các thuộc tính lazy loading được khởi tạo là null theo mặc định
        // $this->lazy_is_liked_by_user = []; // Khởi tạo mảng rỗng cho cache theo user ID
        // $this->lazy_review_list_cache = []; // Khởi tạo mảng rỗng cho cache theo params
    }

    // --- Getters (chỉ trả về dữ liệu thô hoặc đơn giản) ---
    public function getId(): ?string { return (string) $this->id; } // Cast để nhất quán nếu ID có thể là số
    public function getSku(): ?string { return $this->sku; }
    public function getCategoryId(): ?string { return (string) $this->category_id; }
    public function getCategoryName(): ?string { return $this->category_name; }
    // Giữ theCategoryName() để ít ảnh hưởng nhất, dù nên dùng getter
    public function theCategoryName(): void { echo ($this->category_name ?? ''); }

    public function getBrand(): ?string { return $this->brand; }
    public function getVideoUrl(): ?string { return $this->video_url; }
	public function getView360():?array {return $this->view_360;}
    /**
     * Get detailed brand information. Uses lazy loading and object cache.
     * Kept original name 'Brand_detail' for minimal impact.
     * @return array|false Array with brand details or false if not found.
     */
    public function Brand_detail() { // Giữ tên gốc Brand_detail
        // --- LAZY LOADING & CACHING ---
        if ($this->lazy_brand_detail !== null) {
            return $this->lazy_brand_detail;
        }
        // --- END LAZY LOADING & CACHING ---

        $brand_detail = false; // Default return value

        // --- QUERY OPTIMIZATION (Reiterated) ---
        // Searching by title using 's' is inefficient. If possible, use ID or slug.
        // Given the current structure, perform the search ONCE and cache.
	    $brand_detail = [];
        if ($this->brand) {
             $brand_posts = get_posts([
                 'post_type' => 'brands',
                 's' => $this->brand, // Inefficient search - consider better method if possible
                 'numberposts' => 1,
                 'post_status' => 'publish', // Chỉ lấy post publish
                 'fields' => 'ids' // Chỉ lấy ID
             ]);

             if (!empty($brand_posts)) {
                 $brand_post_id = $brand_posts[0];
                 $brand_detail = [
                     'name' => $this->brand,
                     'id' => $brand_post_id,
                     'image' => get_the_post_thumbnail_url($brand_post_id) ?: null, // Lấy thumbnail URL
                     'url' => get_the_permalink($brand_post_id) ?: null,           // Lấy permalink
                 ];
             }
        }
        // --- END QUERY OPTIMIZATION ---

        // --- CACHING ---
        $this->lazy_brand_detail = $brand_detail; // Cache kết quả cho object này
        // --- END CACHING ---

        return $brand_detail;
    }


    public function getTitle(): ?string { return $this->title; }
     // Giữ theTitle() để ít ảnh hưởng nhất, dù nên dùng getter
    public function theTitle(): void { echo esc_html($this->title ?? ''); }

    public function getPrice(): ?float {
	    $prices = [];
	    $data_variants = $this->data_variants??$this->variations;
	    if (!empty($data_variants) && is_array($data_variants)) {
		    foreach ($data_variants as $variantData) {
			    if (isset($variantData['rate']) && is_numeric($variantData['rate'])) {
				    $rate = (float)$variantData['rate'];
				    if ($rate>0) $prices[] = $rate;
			    }
		    }
	    }
	    if (!empty($prices)) {
		    return min($prices);
	    }
	    $current_price = $this->price; $original_price = $this->getOriginalPrice();
	    if ($original_price && ($current_price !== $original_price)) {
		    return min($current_price,$original_price);
	    }

		return is_numeric($this->price) ? (float)$this->price : null;
	}
    public function getOriginalPrice(): ?float { return is_numeric($this->original_price) ? (float)$this->original_price : null; }
    public function getDiscount(): ?float { return is_numeric($this->discount) ? (float)$this->discount : null; }

    public function getImageUrl(): ?string {
	    if (!$this->image_url) {
		    return null;
	    }

	    if (!defined('SERVER_IMAGE_URL') || SERVER_IMAGE_URL === true) {
		    return $this->image_url;
	    }
		return filter_var($this->image_url, FILTER_VALIDATE_URL)?$this->image_url:$this->erp_image($this->image_url);
    }
	public function getGallery(): ?array {
		return $this->gallery;
	}

    public function getLabels(): array { return $this->labels; } // Dữ liệu thô từ ERP, nếu cần xử lý thì dùng lazy loading
    public function getBadge(): ?string { return $this->badge; }
    public function getSold() { return $this->sold??0; } // Giữ kiểu trả về như gốc

     /**
      * Get branch details with linked WP post data. Uses lazy loading and object cache.
      * Kept original name 'getBranch' for minimal impact.
      * @return array Array of branch data with WP post info, or empty array.
      */
    public function getBranch(): array { // Giữ tên gốc getBranch
         // --- LAZY LOADING & CACHING ---
        if ($this->lazy_branch_details !== null) {
            return $this->lazy_branch_details;
        }
        // --- END LAZY LOADING & CACHING ---

         $processed_branches = [];

	    if (!empty($this->branch_data) && is_array($this->branch_data)) {
		    if ($this->hasVariations()) {
			    $branch_data_aggregated = $this->aggregateBranchStock($this->branch_data);
		    } else {
			    $branch_data_aggregated = $this->branch_data[$this->getSku()];
		    }
		    $branch_ids = array_keys($branch_data_aggregated); // ERP branch ids
		    if (!empty($branch_ids)) {
			    // Truy vấn tất cả post store_system theo meta_key _erp_branch_id
			    $store_posts = get_posts([
				    'post_type'      => 'store_system',
				    'post_status'    => 'publish',
				    'numberposts'    => -1,
				    'orderby'        => 'title',
				    'order'          => 'ASC',
				    'meta_query'     => [
					    [
						    'key'     => '_erp_branch_id',
						    'value'   => $branch_ids,
						    'compare' => 'IN',
					    ]
				    ]
			    ]);

			    // Map theo branch_id (erp)
			    $store_post_map = [];
			    if (!empty($store_posts)) {
				    foreach ($store_posts as $post) {
					    // Lấy đúng branch id từ meta
					    $erp_branch_id = get_post_meta($post->ID, '_erp_branch_id', true);
					    $store_post_map[$erp_branch_id] = $post;
				    }
			    }

			    // Tạo kết quả cuối cùng
			    $processed_branches = [];
			    foreach ($branch_data_aggregated as $branch_id => $total_stock) {
				    $branch_with_post = [
					    'erp_branch_id' => $branch_id,
					    'name' => null,
					    'total_stock'   => $total_stock,
					    'post_id'       => null,
					    'meta_data'     => null,
				    ];
				    if (isset($store_post_map[$branch_id])) {
					    $post = $store_post_map[$branch_id];
					    $branch_with_post['post_id'] = $post->ID;
					    $branch_with_post['url'] = get_permalink($post->ID);
					    $branch_with_post['name'] = $post->post_title;


					    // Lấy meta cụ thể nếu muốn
					    // $branch_with_post['meta_data'] = get_post_meta($post->ID, 'store_address', true);
				    }
				    $processed_branches[] = $branch_with_post;
			    }
		    }
	    }


	    // --- CACHING ---
         $this->lazy_branch_details = $processed_branches; // Cache kết quả cho object này
         // --- END CACHING ---

         return $processed_branches;
    }

    /**
     * Get processed attributes as a key-value map. Uses lazy loading and object cache.
     * Kept original name 'getAttributes' for minimal impact.
     * @return array
     */
    public function getAttributes(): array { // Giữ tên gốc getAttributes
         // --- LAZY LOADING & CACHING ---
        if ($this->lazy_attributes_processed !== null) {
            return $this->lazy_attributes_processed;
        }
        // --- END LAZY LOADING & CACHING ---

        // Thực hiện xử lý chỉ khi cần
        $attributesMap = [];
        if (!empty($this->attributes_raw) && is_array($this->attributes_raw)) {
            foreach ($this->attributes_raw as $attribute) {
                $attrKey = $attribute['attribute_key'] ?? '';
                $attrVal = $attribute['attribute_value'] ?? '';
                if ($attrKey && $attrVal) {
                     $attributesMap[sanitize_text_field($attrKey)] = sanitize_text_field($attrVal);
                }
            }
            if (isset($attributesMap['Size'])) {
                $sizeValueMap = $attributesMap['Size'];
                unset($attributesMap['Size']);
                $attributesMap['Size'] = $sizeValueMap;
            }
        }

        // --- CACHING ---
        $this->lazy_attributes_processed = $attributesMap; // Cache kết quả
        // --- END CACHING ---

        return $attributesMap;
    }

    // Helper to format price - assumes priceFormater exists globally
    public function priceFormater($price, $curency = '₫'): string {
        if (!function_exists('priceFormater')) {
             return number_format((float)$price, 0, ',', '.') . $curency;
        }
        return priceFormater((float)$price, $curency);
    }

    // Các phương thức hiển thị giá giữ nguyên như code gốc hoặc phiên bản tối ưu trước
    public function getFormattedPrice(): string { return $this->priceFormater($this->getPrice()); }
    public function getFormattedOriginalPrice(): string { return $this->priceFormater($this->getOriginalPrice()); }
    public function getHTMLprice(): string {
         $prices = [];
	    $data_variants = $this->data_variants??$this->variations;
         if (!empty($data_variants) && is_array($data_variants)) {
             foreach ($data_variants as $variantData) {
                 if (isset($variantData['rate']) && is_numeric($variantData['rate'])) {
					 $rate = (float)$variantData['rate'];
                     if ($rate>0) $prices[] = $rate;
                 }
             }
         }
         if (!empty($prices)) {
             $min = min($prices); $max = max($prices);
             $priceStr = ($min == $max) ? $this->priceFormater($min) : $this->priceFormater($min) . ' - ' . $this->priceFormater($max);
             return sprintf('<p><span class="new">%s</span></p>', ($priceStr));
         }
         $current_price = $this->getPrice(); $original_price = $this->getOriginalPrice();
         if ($original_price && ($current_price !== $original_price)) {
             return sprintf('<p class="old">%s</p><p class="new">%s</p>', $this->getFormattedOriginalPrice(), $this->getFormattedPrice());
         }
         return sprintf('<p class="new">%s</p>', $this->getFormattedPrice());
    }
	public function getHTML_price_detail(): string {
         $prices = [];
	     $data_variants = $this->data_variants??$this->variations;
         if (!empty($data_variants) && is_array($data_variants)) {
             foreach ($data_variants as $variantData) {
                 if (isset($variantData['rate']) && is_numeric($variantData['rate'])) {
	                 $rate = (float)$variantData['rate'];
	                 if ($rate>0) $prices[] = $rate;
                 }
             }
         }
         if (!empty($prices)) {
             $min = min($prices); $max = max($prices);

			 if ($min == $max){
				 return product_price_save_html($min,0);
			 }
	         $priceStr = $this->priceFormater($min) . ' - ' . $this->priceFormater($max);
			 return sprintf('<p><span>%s: </span><span class="new">%s</span></p>', __('Only from', LANG_ZONE), ($priceStr)); // Use translatable string
         }
          if (function_exists('product_price_save_html')) {
              return product_price_save_html($this->getPrice(), $this->getOriginalPrice());
          } else {
               $current_price = $this->getPrice(); $original_price = $this->getOriginalPrice();
               if ($original_price && ($current_price !== $original_price)) {
                   return sprintf('<p class="old">%s</p><p class="new">%s</p>', $this->getFormattedOriginalPrice(), $this->getFormattedPrice());
               }
               return sprintf('<p class="new">%s</p>', $this->getFormattedPrice());
          }
    }

    public function getDiscountPercentage(): ?string {
        $price = $this->getPrice();
        $original_price = $this->getOriginalPrice();
        if (($price !== null && $original_price !== null) && ($price < $original_price) && $original_price > 0) {
            return round((($original_price - $price) / $original_price) * 100) . '%';
        }
        return null;
    }
    public function getDiscountLabel(): string {
        if ($percent = $this->getDiscountPercentage()){
            return sprintf('<div class="badge sale-percent rounded-pill bg-danger">%s</div>', esc_html($percent));
        }
        return '';
    }

    public function hasBadge(): bool { return !empty($this->badge); }
    public function isSoldOut(): bool { return $this->sold !== null && $this->sold <= 0; } // Dựa trên logic gốc và giả định 'sold' là stock

    public function getTextLabel(){ // Giữ tên gốc getTextLabel
        if (empty($this->labels) || !is_array($this->labels)){
            return '';
        }
        $html = '';
        foreach ($this->labels as $label){
            $sanitized_label = sanitize_text_field($label);
            if (empty($sanitized_label)) continue;
            $slug = sanitize_title($sanitized_label);
            $html .= sprintf('<div class="badge text-label rounded-pill %s">%s</div>', esc_attr($slug), ($sanitized_label));
        }
        return $html;
    }

    public function getURL(): ?string {
         if (class_exists('ProductUrlGenerator')) {
              if ($this->getTitle() && $this->getId()) {
                   return ProductUrlGenerator::createProductUrl($this->getTitle(), $this->getId());
              }
         }
         return null;
    }
     // Giữ theURL() để ít ảnh hưởng nhất
    public function theURL(): void { echo esc_url($this->getURL()); }

    public function getBadgeHtml(): string {
        if (empty($this->badge) || !is_string($this->badge)) { return ''; }
        $badge = $this->badge;
        if (strpos($badge, '|') !== false) {
            $parts = explode('|', $badge);
            if (count($parts) === 2) {
                 list($type, $date_str) = $parts;
                 $type = sanitize_text_field($type); $date_str = sanitize_text_field($date_str);
                 $date_parts = explode('/', $date_str);
                 if (count($date_parts) === 2) {
                     $day = $date_parts[0]; $month = $date_parts[1];
                     return sprintf(
                         '<div class="badge top text-label sieu-sale rounded-pill">
                         <span class="date">%s<i class="bi bi-lightning-charge-fill"></i>%s</span> %s
                         </div>',
                         esc_html($day), esc_html($month), esc_html($type)
                     );
                 }
            }
        } else {
             $sanitized_badge = sanitize_text_field($badge);
             if (empty($sanitized_badge)) return '';
             $slug = sanitize_title($sanitized_badge);
             return sprintf('<div class="badge top text-label %s rounded-pill">%s</div>', esc_attr($slug), ($sanitized_badge));
        }
        return '';
    }
	public function getBadgeHtml_detail(): string {
        if (empty($this->badge) || !is_string($this->badge)) { return ''; }
        $badge = $this->badge;
        if (strpos($badge, '|') !== false) {
            $parts = explode('|', $badge);
            if (count($parts) === 2) {
                 list($type, $date_str) = $parts;
                 $type = sanitize_text_field($type); $date_str = sanitize_text_field($date_str);
                 $date_parts = explode('/', $date_str);
                 if (count($date_parts) === 2) {
                     $day = $date_parts[0]; $month = $date_parts[1];
                     return sprintf(
                         '<div class="badge text-label sieu-sale rounded-pill">
                         <span class="date">%s<i class="bi bi-lightning-charge-fill"></i>%s</span> %s
                         </div>',
                         esc_html($day), esc_html($month), esc_html($type)
                     );
                 }
            }
        } else {
             $sanitized_badge = sanitize_text_field($badge);
             if (empty($sanitized_badge)) return '';
             $slug = sanitize_title($sanitized_badge);
             return sprintf('<div class="badge text-label %s rounded-pill">%s</div>', esc_attr($slug), ($sanitized_badge));
        }
        return '';
    }

    public function itemMetaData(): string {
         return sprintf(
             ' data-sku="%s" data-price="%s" data-saleoff="%d" data-original-price="%s" data-sold="%s" data-id="%s" data-category="%s" data-name="%s" data-price-html="%s" data-url="%s" data-image="%s" data-status="%s" data-selected="%s"',
             esc_attr($this->getSku() ?? ''),
             esc_attr($this->getPrice() ?? ''),
             (int)str_replace('%', '', $this->getDiscountPercentage() ?? 0),
             esc_attr($this->getOriginalPrice() ?? ''),
             esc_attr($this->getSold() ), // Check if helper exists
             esc_attr($this->getId() ?? ''),
             esc_attr(sanitize_title($this->getCategoryName() ?? '')),
             esc_attr($this->getTitle() ?? ''),
             esc_attr($this->getFormattedPrice()),
             esc_url($this->getURL() ?? ''),
             esc_url($this->getImageUrl() ?? ''),
	         esc_attr($this->stock_status() ? 'in-stock':'out-stock'),
	         (!$this->hasVariations() ?$this->getSku():''),
         );
    }

    // Giữ tên compareButton() và addToCartButton() và hành vi echo/return
    public function compareButton($echo = false){
         $html = sprintf('<a href="#" role="button" class="btn text-primary fw-bold compare-btn" onclick="addToCompare(this)" data-id="%s"><i class="bi bi-plus-circle"></i> %s</a>',
             esc_attr($this->getSku() ?? $this->getId() ?? ''),
             esc_html__('Compare', LANG_ZONE)
         );
         if ($echo) { echo $html; } else { return $html; }
    }
    public function addToCartButton($echo = false, $class=''){
        $html = sprintf(
            '<button class="btn btn-danger addCart %s" data-product-id="%s" data-product-sku="%s" data-price="%s">%s</button>',
            esc_attr($class),
            esc_attr($this->getId() ?? ''),
            esc_attr($this->getSku() ?? ''),
            esc_attr($this->getPrice() ?? ''),
            esc_html__('Add to Cart', LANG_ZONE)
        );
        if ($echo) { echo $html; } else { return $html; }
    }

    public function hasVariations(): bool { return $this->has_variants; }


    public function variationsRender($show_price= false): string {
        if (empty($this->variations['attributes']) || !is_array($this->variations['attributes']) || !$this->hasVariations()) { return ''; }
        $html = '<div class="product-variations">';
        $availableOptionsData = [];
        foreach ($this->variations['combinations'] as $variantData) {
             if (!is_array($variantData) || !isset($variantData['stock']) || !isset($variantData['attributes'])) { continue; }
            $has_stock = $this->hasStock($variantData['stock']);
            if ($has_stock) {
                foreach ($variantData['attributes'] as $attrName => $attrValue) {
                    $sanitized_attr_name = sanitize_text_field($attrName);
                    $sanitized_attr_value = sanitize_text_field($attrValue);
                    if (empty($sanitized_attr_name) || empty($sanitized_attr_value)) continue;
                    $availableOptionsData[$sanitized_attr_name][$sanitized_attr_value] = [
                        'image' => $variantData['image_url'] ?? '',
                        'price_formatted' => $variantData['price_formatted'] ?? '',
                         'variant_id' => $variantData['id'] ?? null, 'variant_sku' => $variantData['sku'] ?? null,
                         'variant_price' => $variantData['price'] ?? null, 'variant_original_price' => $variantData['original_price'] ?? null,
                         'variant_stock_qty' => is_numeric($variantData['stock']) ? (float)$variantData['stock'] : null,
                         'attributes' => $variantData['attributes'],
                    ];
                }
            }
        }
         $html .= sprintf(
             '<div class="variations-combinations-data" data-combinations="%s"></div>',
             esc_attr(wp_json_encode($this->variations['combinations'] ?? []))
         );

        foreach ($this->variations['attributes'] as $attributeName => $options) {
             $sanitized_attribute_name = sanitize_text_field($attributeName);
             if (empty($sanitized_attribute_name) || !is_array($options)) continue;
            $html .= '<div class="variation-group">';
            $html .= sprintf('<p class="variation-label">%s:</p>', esc_html($sanitized_attribute_name));
            $html .= sprintf('<div class="variation-options" data-attribute="%s">', esc_attr($sanitized_attribute_name));
            foreach ($options as $optionName => $_) {
                 $sanitized_option_name = sanitize_text_field($optionName);
                 if (empty($sanitized_option_name)) continue;
                $isAvailable = isset($availableOptionsData[$sanitized_attribute_name][$sanitized_option_name]);
                $class_stock = $isAvailable ? '' : 'disabled';
                 $image_url = $isAvailable ? ($availableOptionsData[$sanitized_attribute_name][$sanitized_option_name]['image'] ?? '') : '';
                 $price_str = $isAvailable ? ($availableOptionsData[$sanitized_attribute_name][$sanitized_option_name]['price_formatted'] ?? '') : '';

                 $option_data_attrs = '';
                 if ($isAvailable) {
                      $option_data = $availableOptionsData[$sanitized_attribute_name][$sanitized_option_name];
                      $option_data_attrs = sprintf(
                          ' data-variant-id="%s" data-variant-sku="%s" data-variant-price="%s" data-variant-original-price="%s" data-variant-stock="%s" data-variant-image="%s" data-attributes="%s"',
                          esc_attr($option_data['variant_id'] ?? ''), esc_attr($option_data['variant_sku'] ?? ''),
                          esc_attr($option_data['variant_price'] ?? ''), esc_attr($option_data['variant_original_price'] ?? ''),
                          esc_attr($option_data['variant_stock_qty'] ?? ''), esc_url($option_data['image'] ?? ''),
                          esc_attr(wp_json_encode($option_data['attributes'] ?? []))
                      );
                 }

                $html .= sprintf(
                    '<button class="variation-option %s" type="button" data-name="%s" %s>',
                    esc_attr($class_stock), esc_attr($sanitized_option_name), $option_data_attrs
                );
                if ($image_url && strtolower($sanitized_attribute_name) !== 'size') {
                    $html .= sprintf('<img src="%s" alt="%s">', esc_url($image_url), esc_attr($sanitized_option_name));
                }
                $html .= '<span class="variation-option-info">';
                $html .= sprintf('<span class="variation-title">%s</span>', esc_html($sanitized_option_name));
                if ($show_price) {
	                if ( strtolower( $sanitized_attribute_name ) !== 'size' && $price_str ) {
		                $html .= sprintf( '<span class="variation-price">%s</span>', ( $price_str ) );
	                }
                }
                $html .= '</span></button>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }
	public function stock_status(){
		return $this->list_item_stock();
	}
	public function list_item_stock() : bool{
		if (!$this->hasVariations()){
			return !(boolean)$this->out_of_stock;
		}else{
//			my_debug($this);
			$variations = $this->data_variants ?? $this->variations;
			if ($variations){
				foreach ($variations as $variation){
					if (!$variation['out_of_stock']){
						return true;
					}
				}
			}
		}
		return false;
	}
	private function hasStock($stockData): bool {
		if (is_numeric($stockData)) {
			return (float)$stockData > 0;
		}
		if (is_array($stockData)) {
			foreach ($stockData as $skuOrStore => $inner) {
				if (is_array($inner)) {
					foreach ($inner as $store => $arr) {
						if (is_array($arr) && isset($arr[0]) && is_numeric($arr[0]) && (float)$arr[0] > 0) {
							return true;
						}
					}
				}
			}
		}
		return false;
	}
    // renderAttributes() không còn là public, đổi tên thành private để dùng nội bộ bởi getAttributes()
    private function _processAttributes(array $attributes_raw): array {
         $attributesMap = [];
         if (!empty($attributes_raw)) {
             foreach ($attributes_raw as $attribute) {
                 $attrKey = $attribute['attribute_key'] ?? '';
                 $attrVal = $attribute['attribute_value'] ?? '';
                 if ($attrKey && $attrVal) {
                     $attributesMap[sanitize_text_field($attrKey)] = sanitize_text_field($attrVal);
                 }
             }
             if (isset($attributesMap['Size'])) {
                 $sizeValueMap = $attributesMap['Size'];
                 unset($attributesMap['Size']);
                 $attributesMap['Size'] = $sizeValueMap;
             }
         }
         return $attributesMap;
    }

    public function getVariationsImage(): array {
        $images = [];
	    if (isset($this->variations) && is_array($this->variations)) {
		    foreach ($this->variations as $variant) {
			    if (!empty($variant['image'])) {
//				    $color = sanitize_text_field($variant['attributes']['Màu sắc']);

				    $image_url = ($this->erp_image($variant['image']));
				    if (!in_array($image_url, $images)) {
					    $images[] = $image_url;
				    }
//				    if (empty($color) || empty($image_url)) continue;
//				    if (!isset($images[$color])) { $images[$color] = $image_url; }
			    }
		    }
		}

        /*if (isset($this->variations['combinations']) && is_array($this->variations['combinations'])) {
            foreach ($this->variations['combinations'] as $variant) {
                 if (is_array($variant) && isset($variant['attributes']['Màu sắc']) && !empty($variant['image_url'])) {
                    $color = sanitize_text_field($variant['attributes']['Màu sắc']);
                     $image_url = esc_url($variant['image_url']);
                     if (empty($color) || empty($image_url)) continue;
                    if (!isset($images[$color])) { $images[$color] = $image_url; }
                }
            }
        }*/
        return ($images);
    }
	public function erp_image($img_url) {
		if (!$img_url) {
			return null;
		}
		if (!defined('SERVER_IMAGE_URL') || SERVER_IMAGE_URL === true) {
			return $img_url;
		}
		return $this->base_url . $img_url;
	}

    public function hasComboProducts(): bool { return !empty($this->combo_products); }
    public function hasShippingEstimate(): bool { return false; } // Giữ nguyên theo gốc

    /**
     * Fetches and caches product reviews aggregate data (total count, average rating).
     * Used internally by getTotalReviews and getAverageRating. Uses object cache.
     * Changed name to be clearly internal.
     * @return array Array with total_reviews and average_rating.
     */
    private function _getReviewsAggregate(): array { // Đổi tên
        // --- LAZY LOADING & CACHING ---
        if ($this->lazy_reviews_aggregate !== null) {
            return $this->lazy_reviews_aggregate;
        }
        // --- END LAZY LOADING & CACHING ---

        $reviews_data = ['total_reviews' => 0, 'average_rating' => 0];

        if ($this->id) {
            global $wpdb;
            $reviews_table = $wpdb->prefix . 'product_reviews';
             if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $reviews_table)) == $reviews_table) {
                 $stats = $wpdb->get_row($wpdb->prepare(
                     "SELECT COUNT(*) as total_reviews, AVG(rating) as average_rating
                      FROM {$reviews_table}
                      WHERE product_id = %s AND status = 'approved'",
                     (string) $this->id // Cast to string for safety with %s
                 ));
                 if ($stats) {
                     $reviews_data = [
                         'total_reviews' => (int)$stats->total_reviews,
                         'average_rating' => round((float)$stats->average_rating, 1)
                     ];
                 }
             } else { error_log("Product reviews table '{$reviews_table}' does not exist."); }
        }

        // --- CACHING ---
        $this->lazy_reviews_aggregate = $reviews_data; // Cache kết quả
        // --- END CACHING ---

        return $reviews_data;
    }

    /**
     * Lấy số lượng đánh giá. Uses lazy-loaded data.
     */
    public function getTotalReviews(): int {
        $reviews = $this->_getReviewsAggregate(); // Calls the internal lazy method
        return $reviews['total_reviews'];
    }

    /**
     * Lấy điểm đánh giá trung bình. Uses lazy-loaded data.
     */
    public function getAverageRating(): float {
         $reviews = $this->_getReviewsAggregate(); // Calls the internal lazy method
        return $reviews['average_rating'];
    }

    /**
     * Hiển thị HTML đánh giá sao với text. Uses lazy-loaded data.
     */
    public function displayRatingStars($echo = true){
        if (!function_exists('vcl_display_star_rating_with_text')) {
             error_log('Helper function vcl_display_star_rating_with_text not found.');
            if ($echo) return; return '';
        }
        $rating = $this->getAverageRating();
        $total = $this->getTotalReviews();
        if ($echo) { vcl_display_star_rating_with_text($rating, $total); } else { return vcl_display_star_rating_with_text($rating, $total, false); }
    }

    /**
     * Kiểm tra xem sản phẩm có đánh giá không. Uses lazy-loaded data.
     */
    public function hasReviews(): bool {
        return $this->getTotalReviews() > 0;
    }

    /**
     * Get the processed description. Applies filters and sanitization. Uses lazy loading and object cache.
     */
    public function getDescription(): string {
         // --- LAZY LOADING & CACHING ---
        if ($this->lazy_description_processed !== null) {
           // return $this->lazy_description_processed;
        }
//		return $this->description_raw.'-----LIONDK';
        // --- END LAZY LOADING & CACHING ---
	    $processed = wpautop(do_shortcode(wp_kses_post($this->description_raw ?? '')));
        $this->lazy_description_processed = $processed; // Cache
        return $processed;
    }

     /**
      * Get the processed features. Applies filters and sanitization. Uses lazy loading and object cache.
      */
    public function getFeatures(): string {
//		return $this->features_raw;
	    if (!is_array($this->features_raw)){
		    $processed = wpautop(do_shortcode(wp_kses_post($this->features_raw ?? '')));
	    }else{
			$processed = '<ul class="color-list">';
			foreach ($this->features_raw as $feature){
				$processed .='<li class="col-list-item">'.$feature['specification'].': '.esc_html($feature['specification_value']).'</li>';
			}
		    $processed .= '</ul>';
	    }

        return $processed;
    }

     /**
      * Get related products. Fetches from ERP API and uses lazy loading and object cache.
      * Kept original name getRelatedProducts. Caches the *first* result set fetched.
      * Consider using ProductManager::get_products for creating the related product objects if using a Manager.
      *
      * @param int $limit Number of products. Default 8.
      * @param int $page Page number. Default 1.
      * @return array Array of Product objects.
      */
    public function getRelatedProducts(int $limit = 8, int $page = 1): array {
         // --- LAZY LOADING & CACHING ---
         // Simple cache for the first call to this method on this object instance.
         // Note: This cache doesn't differentiate based on $limit or $page.
         // A more complex cache key is needed if different pagination results should be cached separately.
         if ($this->lazy_related_products !== null) {
//             return $this->lazy_related_products;
         }
         // --- END LAZY LOADING & CACHING ---

        if (empty($this->id)) { $this->lazy_related_products = []; return []; }

        // Assuming ERP_API_Handler can be used like this.
        // Ideally, reuse instance via Singleton/DI.
        $erp_api = new ERP_API_Client();

        $related_products_data = $erp_api->get_related_products((string) $this->id, $limit, $page);
//	    my_debug($related_products_data);
        if (is_wp_error($related_products_data) || !is_array($related_products_data)) {
             $this->lazy_related_products = []; return [];
        }

        $related_products = [];
        if (!empty($related_products_data)) {
             foreach ($related_products_data as $product_data) {
                 if (is_array($product_data) && isset($product_data['item_code'])) {
                      // Creating new Product objects directly:
                     $related_products[] =new Product($product_data);
                 }
             }
        }
        $this->lazy_related_products = $related_products; // Cache
//		my_debug($related_products_data);
        return $related_products;
    }


    /**
     * Kiểm tra xem sản phẩm có được người dùng yêu thích không. Uses lazy loading and object cache per user ID.
     * Kept original name isLikedByUser.
     *
     * @param int $user_id ID của người dùng.
     * @return bool True nếu được yêu thích, false nếu không.
     */
    public function isLikedByUser(int $user_id): bool {
        if (empty($user_id) || empty($this->id)) { return false; }

        // --- LAZY LOADING & CACHING (per user ID) ---
        if (isset($this->lazy_is_liked_by_user[$user_id])) {
             return $this->lazy_is_liked_by_user[$user_id];
        }
        // --- END LAZY LOADING & CACHING ---

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';

        $is_liked = false;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $likes_table)) == $likes_table) {
             $count = $wpdb->get_var($wpdb->prepare(
                 "SELECT COUNT(*) FROM {$likes_table} WHERE user_id = %d AND product_id = %s LIMIT 1",
                 absint($user_id), (string) $this->id
             ));
             $is_liked = $count > 0;
        } else { error_log("Product likes table '{$likes_table}' does not exist."); }

        // --- CACHING ---
        $this->lazy_is_liked_by_user[$user_id] = $is_liked; // Cache kết quả theo user ID
        // --- END CACHING ---
        return $is_liked;
    }

    /**
     * Lấy tổng số lượt yêu thích của sản phẩm. Uses lazy loading and object cache.
     * Kept original name getTotalLikes.
     *
     * @return int Tổng số lượt yêu thích.
     */
    public function getTotalLikes(): int {
        if (empty($this->id)) { return 0; }

        // --- LAZY LOADING & CACHING ---
        if ($this->lazy_total_likes !== null) {
            return $this->lazy_total_likes;
        }
        // --- END LAZY LOADING & CACHING ---

        global $wpdb;
        $likes_table = $wpdb->prefix . 'product_likes';

        $total_likes = 0;
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $likes_table)) == $likes_table) {
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$likes_table} WHERE product_id = %s",
                (string) $this->id
            ));
            $total_likes = absint($count);
        } else { error_log("Product likes table '{$likes_table}' does not exist."); }

        // --- CACHING ---
        $this->lazy_total_likes = $total_likes; // Cache kết quả
        // --- END CACHING ---
        return $total_likes;
    }

    // --- NEW METHOD: Get List of Reviews ---
    /**
     * Get a list of approved reviews for the product.
     * Uses lazy loading and object cache based on pagination and order parameters.
     *
     * @param int $limit Number of reviews to retrieve. Default 10. Use -1 for all.
     * @param int $offset Number of reviews to skip (for pagination). Default 0.
     * @param string $orderby Column to order by. Default 'date_created'. Allowed: 'date_created', 'rating'.
     * @param string $order Sort order ('ASC' or 'DESC'). Default 'DESC'.
     * @return array Array of review objects/arrays, empty array if none found or error.
     */
    public function getReviews(int $limit = 10, int $offset = 0, string $orderby = 'date_created', string $order = 'DESC'): array {
        if (empty($this->id)) { return []; }

        // --- LAZY LOADING & CACHING ---
        // Create a unique cache key based on parameters
        $cache_key = md5(serialize([$limit, $offset, $orderby, $order]));

        if (isset($this->lazy_review_list_cache[$cache_key])) {
            return $this->lazy_review_list_cache[$cache_key];
        }
        // --- END LAZY LOADING & CACHING ---

        global $wpdb;
        $reviews_table = $wpdb->prefix . 'product_reviews';

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $reviews_table)) != $reviews_table) {
            error_log("Product reviews table '{$reviews_table}' does not exist.");
            $this->lazy_review_list_cache[$cache_key] = []; // Cache empty result
            return [];
        }

        $allowed_orderby = ['date_created', 'rating'];
        $orderby = in_array(strtolower($orderby), $allowed_orderby) ? esc_sql(strtolower($orderby)) : 'date_created';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $limit_clause = '';
        $query_args = ['approved', (string) $this->id];

        $sql = "SELECT * FROM {$reviews_table} WHERE status = %s AND product_id = %s ORDER BY {$orderby} {$order}";

        if ($limit > -1) {
            $sql .= " LIMIT %d";
            $query_args[] = absint($limit);
            if ($offset > 0) {
                $sql .= " OFFSET %d";
                $query_args[] = absint($offset);
            }
        } elseif ($offset > 0) {
             $sql .= " OFFSET %d";
             $query_args[] = absint($offset);
        }

        $query = $wpdb->prepare($sql, $query_args);
        $reviews = $wpdb->get_results($query);

        $reviews = is_array($reviews) ? $reviews : [];

        // --- CACHING ---
        $this->lazy_review_list_cache[$cache_key] = $reviews; // Cache result
        // --- END CACHING ---

        return $reviews;
    }
    // --- End NEW METHOD ---

	public function aggregateBranchStock(array $data): array
	{
		$aggregatedBranches = [];

		foreach ($data as $productKey => $branches) {
			foreach ($branches as $branchName => $stockAndOtherData) {
				$normalizedBranchName = trim($branchName);
				$stock = $stockAndOtherData[0];
				if (!isset($aggregatedBranches[$normalizedBranchName])) {
					$aggregatedBranches[$normalizedBranchName] = 0;
				}
				$aggregatedBranches[$normalizedBranchName] += $stock;
			}
		}

		return $aggregatedBranches;
	}
	public function getVariate_BranchStock(){
		if (!$this->branch_data) return;
		$return = [];
		foreach ($this->branch_data as $sku => $branch_stock) {
			foreach ($branch_stock as $branch_name => $stock){
				if ($stock[0]>0){
					$return[$sku][$branch_name] = [
						'stock' => $stock[0],
//						'url' => '#'
					];
				}
			}
		}
		return $return;
	}

	public function getFreeItems(){
		if (!$this->select_pricing_rules) return;
		$return = [];
		foreach ($this->select_pricing_rules as $free_item) {
			$return[] = [
				'sku' => $free_item['pricing_rule'],
				'title' => $free_item['description'],
//				'qty' => $free_item['qty'],
//				'desc' => $free_item['description'],
			];
		}
		return $return;
	}

} //end class Product