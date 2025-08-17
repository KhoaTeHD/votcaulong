<?php get_header(); ?>

<div class="container">
	<div class="post-content bg-white">
		<?php get_template_part('template-parts/page','title');  ?>
        <div class="col-md-12 p-3">
            <?php the_content();  ?>
        </div>
	</div>
</div>
<?php
get_footer(); ?>

