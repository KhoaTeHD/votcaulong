<?php
/**
 * Template Name: Review
 **/
get_header();
?>
<div class="container my-3">
	<div class="row">
        <div class="col-md-12">
            <section class="my-3 shadow-sm border border-light bg-white">
                <div class="section-header bg-none">
                    <h3 class="title">review tổng hợp</h3>
                </div>
                <div class="row p-3">
                    <div class="col-md">
                        <ul class="news-nav nav items-justified-space-between ">
                            <li class="nav-item"><a href="" class="nav-link active">Cầu lông</a></li>
                            <li class="nav-item"><a href="" class="nav-link">Tennis</a></li>
                            <li class="nav-item"><a href="" class="nav-link">Pickleball</a></li>
                            <li class="nav-item"><a href="" class="nav-link">Khuyến mãi</a></li>
                            <li class="nav-item"><a href="" class="nav-link">Giải đấu</a></li>
                            <li class="nav-item"><a href="" class="nav-link">Sức khỏe</a></li>
                            <li class="nav-item"><a href="" class="nav-link">Giải trí</a></li>
                        </ul>
                        <div class="row my-3">
                            <?php
                            $args = array(
                                'category_name'  => 'review', // Thay bằng slug của category bạn muốn lấy
                                'posts_per_page' => 1, // Chỉ lấy 1 bài viết mới nhất
                            );

                            $query1 = new WP_Query($args);

                            if ($query1->have_posts()) :
                                while ($query1->have_posts()) : $query1->the_post(); ?>
                                    <div class="col-md-6">
                                        <article class="blog-post">
                                            <div class="post-thumbnail">
                                                <a href="<?php the_permalink(); ?>">
                                                    <img class="img-fluid" src="<?php echo get_the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
                                                </a>
                                            </div>
                                            <a href="<?php the_permalink(); ?>">
                                                <h5 class="post-title"><?php the_title(); ?></h5>
                                            </a>
                                            <p class="post-desc"><?php the_excerpt(); ?></p>
                                            <p class="post-item-time"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) ; ?></p>
                                        </article>
                                    </div>
                                <?php endwhile;
                                wp_reset_postdata();
                            else :
                                echo '<p>Không có bài viết nào.</p>';
                            endif;
                            ?>

                            <div class="col-md-6">
                                <div class="post-listing">
                                    <?php
                                    $args = array(
                                        'category_name'  => 'review',
                                        'posts_per_page' => 4,
                                        'offset'         => 1, 
                                    );
                                    $query2 = new WP_Query($args);
                                    if ($query2->have_posts()) :
                                        while ($query2->have_posts()) : $query2->the_post(); ?>
                                            <article class="post-item">
                                                <div class="post-item-image">
                                                    <a href="<?php the_permalink(); ?>">
                                                        <img src="<?php echo get_the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
                                                    </a>
                                                </div>
                                                <div class="post-item-content">
                                                    <a href="<?php the_permalink(); ?>">
                                                        <h5 class="post-item-title"><?php the_title(); ?></h5>
                                                    </a>
                                                    <p class="post-item-meta"><?php the_author(); ?></p>
                                                    <p class="post-item-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
                                                    <span class="post-item-time"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')); ?></span>
                                                </div>
                                            </article>
                                        <?php endwhile;
                                        wp_reset_postdata();
                                    else :
                                        echo '<p>Không có bài viết nào.</p>';
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

                    $posts_per_page = 5;
                    $args = array(
                        'category_name' => 'review', // Slug của category cần đếm
                        'posts_per_page' => -1, // Lấy tất cả bài viết
                    );
                    
                    $query = new WP_Query($args);
                    
                    $total_posts = $query->found_posts;

                    // echo $total_posts;
                    $offset = ($paged - 1) * $posts_per_page + 5;

                    $args_pagination = array(
                        'category_name'  => 'review',
                        'posts_per_page' => $posts_per_page,  // Số bài trên mỗi trang
                        'paged'          => $paged,  // Phân trang
                        'offset'         => $offset,  // Bỏ qua 5 bài đầu tiên và tính toán offset
                    );

                    $query_pagination = new WP_Query($args_pagination);

                    if ($query_pagination->have_posts()) : ?>
                        <section class="my-3 shadow-sm border border-light bg-white">
                            <div class="section-header">
                                <h3 class="title">Các Đánh Giá Khác</h3>
                            </div>
                            <div class="row p-3">
                                <?php while ($query_pagination->have_posts()) : $query_pagination->the_post(); ?>
                                    <div class="col-md-6">
                                        <article class="post-item">
                                            <div class="post-item-image">
                                                <a href="<?php the_permalink(); ?>">
                                                    <img src="<?php echo get_the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
                                                </a>
                                            </div>
                                            <div class="post-item-content">
                                                <a href="<?php the_permalink(); ?>">
                                                    <h5 class="post-item-title"><?php the_title(); ?></h5>
                                                </a>
                                                <p class="post-item-meta"><?php the_author(); ?></p>
                                                <p class="post-item-excerpt"><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
                                                <span class="post-item-time"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')); ?></span>
                                            </div>
                                        </article>
                                    </div>
                                <?php endwhile; ?>
                            </div>

                            <!-- Pagination -->
                            <div class="pagination">
                                <?php
                                $total_pages = ceil(($total_posts - 5) / 5);
                                echo paginate_links(array(
                                    'total'        => $total_pages,
                                    'current'      => max(1, get_query_var('paged')),
                                    'format'       => '?paged=%#%',
                                    'prev_text'    => '<i class="bi bi-chevron-left"></i>',
                                    'next_text'    => '<i class="bi bi-chevron-right"></i>',
                                ));
                                ?>
                            </div>
                        </section>
                    <?php
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?php

get_footer();

