<?php get_header(); ?>
<?php
$col_content = 12;
$col_widget = 0;
if ( is_active_sidebar( 'post-widget-area' ) ) {
    $col_content = 9;
    $col_widget = 3;
 }

?>
<div class="container">
    <div class="bg-white p-md-3 row">
        <div class="col-md-<?php echo $col_content  ?>">
	        <?php
	        $categories = get_the_category();
	        if ( ! empty( $categories ) ) {
		        $cat = $categories[0];
		        echo '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" class="post-cat">';
		        echo esc_html( $cat->name );
		        echo '</a>';
	        }
	        ?>
            <div class="section-header">
                <h3 class="title"><?php echo the_title() ?></h3>

            </div>
            <div class="post-content mt-2">
		        <?php the_content(); ?>
            </div>
            <div class="related-posts-wrapper">
                <?php $related_query = null;
                $post_tags = wp_get_post_tags(get_the_ID());

                if ($post_tags) {
	                $tag_ids = wp_list_pluck($post_tags, 'term_id');
	                $related_args = array(
		                'tag__in' => $tag_ids,
		                'post__not_in' => array(get_the_ID()),
		                'posts_per_page' => 4,
		                'ignore_sticky_posts' => 1,
	                );
	                $related_query = new WP_Query($related_args);
                }

                if (empty($related_query) || !$related_query->have_posts()) {
	                $post_categories = wp_get_post_categories(get_the_ID());
	                if ($post_categories) {
		                $related_args = array(
			                'category__in' => $post_categories,
			                'post__not_in' => array(get_the_ID()),
			                'posts_per_page' => 4,
			                'ignore_sticky_posts' => 1,
		                );
		                $related_query = new WP_Query($related_args);
	                }
                }

                if ($related_query && $related_query->have_posts()) {
	                echo '<h3 class="related-title">'.__(' Related Posts', LANG_ZONE).'</h3>';
	                echo '<ul class="related-posts">';
	                while ($related_query->have_posts()) {
		                $related_query->the_post();
		                ?>
                        <li>
                            <a href="<?php the_permalink(); ?>">
                                <span><?php the_title(); ?></span>
                            </a>
                        </li>
		                <?php
	                }
	                echo '</ul>';
	                wp_reset_postdata();
                }
                ?>
            </div>
            <div class="post-meta">
	            <?php
	            $tags = get_the_tags();
	            if ($tags) :
		            ?>
                    <div class="post-tags">
                        <i class="bi bi-tags-fill"></i>
                        <span class="tags-label">Tags:</span>
			            <?php foreach ($tags as $tag) : ?>
                            <a class="tag-btn" href="<?php echo get_tag_link($tag->term_id); ?>">
					            <?php echo esc_html($tag->name); ?>
                            </a>
			            <?php endforeach; ?>
                    </div>
	            <?php endif; ?>
                <div class="post-share">
                    <div class="shareon" data-url="<?php the_permalink(); ?>">
                        <button class="messenger" data-fb-app-id="APP ID"></button>
                        <button class="facebook" data-title="Custom Link Title" data-hashtags="VotCauLongShop"></button>
                        <button class="pinterest" ></button>
                        <button class="twitter" data-via="your twitter username"></button>
                        <button href="#"  class="copy-url"></button>
                    </div>
                </div>
            </div>
        </div>
	    <?php
	    if ( is_active_sidebar( 'post-widget-area' ) ) : ?>
            <div class="col-md-<?php echo $col_widget  ?> vcl-sideBar">
			    <?php dynamic_sidebar( 'post-widget-area' ); ?>
            </div>
	    <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
