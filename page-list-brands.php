<?php
/**
 * Template Name: List_brands
 **/
get_header();

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$args = array(
    'post_type'      => 'brands',
    'posts_per_page' => 24,
    'paged'          => $paged
);
$query = new WP_Query($args);
?>
<div class="container">
    <div class="section-header">
        <h3 class="title"><?php echo the_title() ?></h3>
    </div>
    <div class="bg-white p-3">
        <div class="post-content "><?php the_content();  ?></div>
        <div class="brands-listing-wrapper">
            <div class="brands-listing">
                <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="brand-item">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail('medium'); ?>
                            <h3><?php the_title(); ?></h3>
                        </a>
                    </div>
                <?php endwhile; endif; wp_reset_postdata(); ?>
            </div>
            <div id="pagination_wrapper">
		        <?php echo render_pagination($paged, $query->max_num_pages, 2,'?paged='); ?>
            </div>

        </div>
    </div>

</div>
<?php
get_footer();