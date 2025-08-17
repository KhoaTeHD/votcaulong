<?php
/**
 * Template Name: Hệ thống cửa hàng
 **/
get_header(); ?>
<div class="container">
	<div class="post-content bg-white">
        <section class=" bg-white shadow-sm">
            <div class="section-header">
                <h3 class="title"><?php the_title()  ?></h3>
            </div>
            <div class="photo-box p-3 style-2">

                <div class="photo-box-item">
                    <a href="<?php echo get_permalink();  ?>">
                        <img src="<?php echo IMG_URL  ?>store-1.jpg" alt="Vợt cầu lông">
                        <span class="title">CN. TÂN PHÚ 1</span>
                    </a>
                </div>

                <div class="photo-box-item">
                    <a href="#">
                        <img src="<?php echo IMG_URL  ?>store-1.jpg" alt="Vợt cầu lông">
                        <span class="title">CN. Phú thọ</span>
                    </a>
                </div>

                <div class="photo-box-item">
                    <a href="#">
                        <img src="<?php echo IMG_URL  ?>store-1.jpg" alt="Vợt cầu lông">
                        <span class="title">CN. TÂN Bình</span>
                    </a>
                </div>
                <div class="photo-box-item">
                    <a href="#">
                        <img src="<?php echo IMG_URL  ?>store-1.jpg" alt="Vợt cầu lông">
                        <span class="title">Giày cầu lông</span>
                    </a>
                </div>



            </div>
            <div class="section-footer p-3">
                <p>" VOTCAULONGSHOP với hệ thống cửa hàng toàn quốc giúp<br>quý khách có thể trực tiếp qua thăm quan và chọn sản phẩm, trải nghiệm sản phẩm thực tế. ”</p>
            </div>
        </section>
	</div>
</div>
<?php
get_footer(); ?>
