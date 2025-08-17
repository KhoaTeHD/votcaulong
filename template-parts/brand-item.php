<?php $data = $args['data'];  ?>
<?php if (isset($data) && $data) {
    foreach ($data as $brand) {
        $thisBrand = search_brand_by_title($brand);
//        my_debug($thisBrand);
        ?>
        <div class="brand-item">
            <a href="<?php the_permalink($thisBrand->ID);  ?>">
                <img src="<?php echo $thisBrand->thumbnail  ?>" class="img-thumbnai" title="<?php echo $brand  ?>">
            </a>
        </div>
<?php
    }

} ?>
