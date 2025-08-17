<?php
get_header('product-cate');
?>

	<div class="container">
        <div class="section-header mt-3">
            <h3 class="title"><?php _e('Compare products', LANG_ZONE)  ?></h3>
        </div>

        <div class=" bg-white p-1">
            <div id="product-comparison-container">
                <div id="comparison-loading" style="display: none;">
                    <p><?php _e('Loading product information...',LANG_ZONE)  ?></p>
                </div>

                <div id="comparison-table-container">
                    <div class="comparison-error">

                    </div>
                </div>
            </div>

	</div>
	</div>
<script>
    is_comparePage = true;
</script>
		<?php
get_footer(); ?>