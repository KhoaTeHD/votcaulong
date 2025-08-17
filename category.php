<?php
get_header();
$current_category = get_queried_object(); 
?>
<div class="container my-3">
	<div class="row">
        <div class="col-md-12">
            <section class="my-3 shadow-sm border border-light bg-white">
                <div class="section-header ">
                    <h3 class="title"><?php single_cat_title();  ?></h3>
                </div>
                <div class="row p-3">
                    <?php
                    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                    $posts_per_page = 10; 

                    $args = array(
                        'cat'            => $current_category->term_id, 
                        'posts_per_page' => $posts_per_page,
                        'paged'          => $paged,
                    );

                    $category_query = new WP_Query($args);

                    if ($category_query->have_posts()) : ?>
                        <div class="col-md-9"> 
                            <div class="row"> 
                                <?php while ($category_query->have_posts()) : $category_query->the_post(); ?>
                                    <div class="col-md-6 mb-4"> 
                                        <article class="post-item h-100 d-flex "> 
                                            <?php if (has_post_thumbnail()) : ?>
                                            <div class="post-item-image">
                                                <a href="<?php the_permalink(); ?>">
                                                    <img class="img-fluid" src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'medium_large'); // Sử dụng kích thước ảnh phù hợp ?>" alt="<?php the_title_attribute(); ?>">
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                            <div class="post-item-content mt-2">
                                                <a href="<?php the_permalink(); ?>">
                                                    <h5 class="post-item-title"><?php the_title(); ?></h5>
                                                </a>
                                                <p class="post-item-meta">
                                                    <span class="author"><?php the_author(); ?></span> | 
                                                    <span class="date"><?php echo get_the_date(); ?></span>
                                                </p>
                                                <div class="post-item-excerpt">
                                                    <?php echo wp_trim_words(get_the_excerpt(), 25, '...'); // Tăng số từ cho excerpt ?>
                                                </div>
                                                <span class="post-item-time mt-auto"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' trước'; ?></span>
                                            </div>
                                        </article>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div id="pagination_wrapper">
		                        <?php echo render_pagination($paged, $category_query->max_num_pages, 2,'?paged='); ?>
                            </div>
                        </div>

                        <!-- Right Sidebar -->
                        <div class="col-md-3 vcl-sideBar">
                        <?php if ( is_active_sidebar( 'main-widget-area' ) ) : ?>
			                    <?php dynamic_sidebar( 'main-widget-area' ); ?>
	                    <?php endif; ?>
                        </div>
                        <!-- Pagination -->
                        <div class="col-md-12 mt-4">


                        </div>
                    <?php
                        wp_reset_postdata();
                    else : ?>
                        <div class="col-md-12">
                            <p><?php _e('Không có bài viết nào trong chuyên mục này.', LANG_ZONE);  ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?php
get_footer();