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
        <div class="post-content bg-white p-2 p-md-3">
            <div class="store-map">
                <p><strong>Địa chỉ:</strong> <?php echo get_post_meta( get_the_ID(), 'store_address', true ); ?></p>
                <p><strong>Số điện thoại:</strong> <?php echo get_post_meta( get_the_ID(), 'store_phone', true ); ?></p>
                <div class="store_embed_map">
	                <?php $html = get_post_meta( get_the_ID(), 'store_google_map', true );

	                $allowed = wp_kses_allowed_html( 'post' );
	                $allowed['iframe'] = array(
		                'src'             => true,
		                'width'           => true,
		                'height'          => true,
		                'style'           => true,
		                'frameborder'     => true,
		                'allow'           => true,
		                'allowfullscreen' => true,
		                'loading'         => true,
		                'referrerpolicy'  => true,
	                );

	                echo wp_kses( $html, $allowed ); ?>
                </div>

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
