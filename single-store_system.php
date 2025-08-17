<?php
get_header();
?>
 <div class="container">
    <div class="section-header">
            <h3 class="title"><?php the_title();  ?></h3>
        </div>
    <div class="row">
        <div class="col-12">
<?php
if ( have_posts() ) :
    while ( have_posts() ) : the_post(); ?>
        <div class="post-content bg-white px-3 py-5">
            

            <div class="store-content">
                <?php //the_content(); ?>
            </div>

            <div class="store-map">
                <p><strong>Địa chỉ:</strong> <?php echo get_post_meta( get_the_ID(), 'store_address', true ); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo get_post_meta( get_the_ID(), 'store_phone', true ); ?></p>
                <iframe 
                    src="<?php echo get_post_meta( get_the_ID(), 'store_google_map', true ); ?>" 
                    width="100%" 
                    height="300" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    <?php
    endwhile;
endif;
?>
        </div>
    </div>
</div>
<?php
get_footer();
