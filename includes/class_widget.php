<?php
class Products_Viewed_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'products_viewed_widget',
            __('Recently Viewed Products', LANG_ZONE),
            array('description' => __('Display ERP products user has recently viewed', LANG_ZONE))
        );
    }

    function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        echo '<div class="viewed-products" id="viewed-products"></div>';
        echo $args['after_widget'];
    }

    function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Recently Viewed Products', LANG_ZONE);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Title:', LANG_ZONE); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }

    function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

add_action('widgets_init', function() {
    register_widget('Products_Viewed_Widget');
});
