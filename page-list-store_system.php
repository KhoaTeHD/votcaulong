<?php
/**
 * Template Name: Store_system
 **/
get_header();
?>
<div class="container">
    <div class="section-header">
            <h3 class="title"><?php the_title();  ?></h3>
        </div>  
<div class="store-list-container post-content bg-white p-3">
    <div class="row">
        <div class="store-all-list col-6">
            <ul>
                <?php
                $args = array(
                    'post_type'      => 'store_system',
                    'posts_per_page' => -1,  // Lấy tất cả cửa hàng
                    'orderby'        => 'date',
                    'order'          => 'ASC'
                );
                $query = new WP_Query($args);

                if ($query->have_posts()) :
                    while ($query->have_posts()) : $query->the_post();
                        $store_address = get_post_meta(get_the_ID(), 'store_address', true);
                        $store_phone   = get_post_meta(get_the_ID(), 'store_phone', true);
                        $store_map     = get_post_meta(get_the_ID(), 'store_google_map', true);
                        ?>
                        <li 
                            onmouseover="changeMap('<?php echo esc_url($store_map); ?>')" 
                            onclick="window.location.href='<?php echo get_permalink(); ?>'">
                            <strong><?php the_title(); ?></strong><br>
                            📍 <?php echo esc_html($store_address); ?><br>
                            📞 <?php echo esc_html($store_phone); ?>
                        </li>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    echo "<p>Không có cửa hàng nào.</p>";
                endif;
                ?>
            </ul>
        </div>

        <div class="store-map col-6">
            <h2>Bản đồ</h2>
            <iframe id="googleMapFrame" 
                src="https://www.google.com/maps/embed?pb=" 
                width="100%" height="400" 
                style="border:0;" allowfullscreen="" loading="lazy">
            </iframe>
        </div>
    </div>
</div>

<script>
    function changeMap(mapUrl) {
        document.getElementById("googleMapFrame").src = mapUrl;
    }
</script>
</div>
<?php
get_footer();
