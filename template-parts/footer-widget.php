<?php 
$menu_locations = $args['menu_array'];
$menu_id = isset( $menu_locations[ $args['menu_location'] ] ) ? $menu_locations[ $args['menu_location']  ] : null;
$menu_object = wp_get_nav_menu_object( $menu_id ); 
$menu_items = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();
// my_debug($menu_items);
// my_debug($menu_object);
?>
<div class="widget-box">
    <?php if ( $menu_object && !empty($menu_items) ) : ?>
        <h4 class="title"><?php echo $menu_object->name ?> </h4>
            <div class="widget-body">
                <ul>
                    <?php foreach ($menu_items as $item){?>
                        <li><a href="<?php echo $item->url ?> " <?php echo ($item->target?'target="_blank"':'') ?> ><?php echo $item->title ?></a></li>
                    <?php } ?>
                </ul>
            </div>
    <?php else : ?>
        <h4 class="title">No menu available</h4>
        <div class="widget-body">
            <p>No menu items to display.</p>
        </div>
    <?php endif; ?>
</div>