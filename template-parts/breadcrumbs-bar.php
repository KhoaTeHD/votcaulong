<?php
//echo get_query_var('product_id');
if ( is_home() && !get_query_var('product_id') && !get_query_var('product_cate_id')  ) { return;}

//echo 'get:'.get_field('show_breadcrumb');
$show_breadcrumb = get_field('show_breadcrumb');
if (isset($show_breadcrumb) && !$show_breadcrumb) { return;}
?>
<div class="container">
	<div class="post-content">
		<div class="col-md-12 Breadcrumb">
			<?php
            $breadcrumb = Breadcrumb::getInstance();
			$breadcrumb->render();
			?>
		</div>
	</div>
</div>