<?php
/**
 * Template Name: All Product
 **/
get_header();
global $product_attr_name;
$data_path = get_template_directory() . '/data';
$erp_api = new ERP_API_Handler(FAKE_DATA);
$products = $erp_api->get_all_products();
//  
    if (!is_wp_error($products)) {
        $filters = generate_filters($products);
        };
            ?>
            <div class="container" id="">
                <div class="brand1-list">
                    <h2>Sản phẩm</h2>
               </div>
                <div class="category-product-listing product-list-filter my-3 position-relative" id="">
                    <div class="product-grid shadow-item">
                        <?php
                            foreach ($products as $product_info ){
                                get_template_part('template-parts/product-item');
                            }
                            ?>
                        </div>
                        <div class="product-pagination my-3">
                            <a href="#" role="button" class="btn pagination-btn page-prev-btn disabled"><i class="bi bi-chevron-left"></i></a>
                            <span class="btn pagination-btn active">1</span>
                            <a href="#" role="button" class="btn pagination-btn ">2</a>
                            <a href="#" role="button" class="btn pagination-btn ">3</a>
                            <span class="more-pages pagination-btn ">...</span>
                            <a href="#" role="button" class="btn pagination-btn ">10</a>
                            <a href="#" role="button" class="btn pagination-btn page-next-btn"><i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
            </div>
    <?php
get_footer(); ?>