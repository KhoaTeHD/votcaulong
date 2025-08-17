<?php 
$erp_api = new ERP_API_Client();
$brand_results = $erp_api->list_brands();

if (!is_wp_error($brand_results)) {
    $brands = $brand_results['data']; // Dữ liệu danh sách brand
?>
    <div class="container mb-fluid">
        <div class="section-header">
            <h3 class="title">Danh sách thương hiệu</h3>
        </div>
        <div class="post-content bg-white p-3">
            <div class="row">
                <?php foreach($brands as $brand): ?>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="brand-card p-2 border text-center">
                            <h5><?php echo esc_html($brand['brand_name'] ?? $brand['name']); ?></h5>
                            <!-- Thêm gì đó như logo, mô tả nếu có -->
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php 
} else {
    echo '<p class="text-danger">Không thể tải danh sách thương hiệu.</p>';
}
get_footer(); 
?>