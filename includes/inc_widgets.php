<?php

function register_theme_widget_areas() {
	$footer_column = 5;
	for ($i=1;$i<=$footer_column;$i++) {
		$footer_column_title = sprintf( __( 'Footer Column %d', LANG_ZONE ), $i );
		register_sidebar( array(
			'name'          => $footer_column_title, // Tên của widget area
			'id'            => 'footer-column-'.$i, // ID của widget area (duy nhất)
			'description'   => __( 'Footer widget', LANG_ZONE ), // Mô tả của widget area
			'before_widget' => '<div id="%1$s" class="widget-box widget %2$s">', // HTML trước mỗi widget
			'before_title'  => '<h4 class="title">', // HTML trước tiêu đề widget
			'after_title'   => '</h4><div class="widget-body">', // HTML sau tiêu đề widget
			'after_widget'  => '</div></div>', // HTML sau mỗi widget
			
		) );
	}
	$footer_column_bottom = 3;
	for ($i=1;$i<=$footer_column_bottom;$i++) {
		$footer_column_bottom_title = sprintf( __( 'Footer Column Bottom %d', LANG_ZONE ), $i );
		register_sidebar( array(
			'name'          => $footer_column_bottom_title, // Tên của widget area
			'id'            => 'footer-column-bottom'.$i, // ID của widget area (duy nhất)
			'description'   => __( 'Footer widget', LANG_ZONE ), // Mô tả của widget area
			'before_widget' => '<div id="%1$s" class="widget-box widget %2$s">', // HTML trước mỗi widget
			'before_title'  => '<h4 class="title">', // HTML trước tiêu đề widget
			'after_title'   => '</h4><div class="widget-body">', // HTML sau tiêu đề widget
			'after_widget'  => '</div></div>', // HTML sau mỗi widget
			
		));
	}
	// Các widget khác
	register_sidebar( array(
		'name'          => __( 'Main Widget Area', LANG_ZONE ),
		'id'            => 'main-widget-area',
		'description'   => __( 'Main widget for Category', LANG_ZONE ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
	register_sidebar( array(
		'name'          => __( 'Post Widget Area', LANG_ZONE ),
		'id'            => 'post-widget-area',
		'description'   => __( 'Widget for Post', LANG_ZONE ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h2 class="widget-title">',
		'after_title'   => '</h2>',
	) );
	register_sidebar( array(
		'name'          => __( 'Shop Widget Area', LANG_ZONE ),
		'id'            => 'shop-widget-area',
		'description'   => __( 'Widget area for Shop and Products', LANG_ZONE ),
		'before_widget' => '<div id="%1$s" class="widget widget-box %2$s my-3">',

		'before_title'  => '<div class="widget-head"><div class="widget-title">',
		'after_title'   => '</div><div class="widget-control">
			<button class="bg-white border-0"  type="button" data-bs-toggle="collapse" data-bs-target="#uniqID" aria-expanded="true" aria-controls="uniqID">
				<i class="bi bi-chevron-up"></i>
			</button>
		</div></div><div class="widget-body collapse show" id="uniqID">',

		'after_widget'  => '</div></div>',
	) );
}
add_action( 'widgets_init', 'register_theme_widget_areas' );

function customize_shop_widget_area( $params ) {
	if ( is_array($params) && isset($params[0]) && is_array($params[0]) && isset( $params[0]['id'] ) && $params[0]['id'] == 'shop-widget-area' ) {
		$unique_id = 'widget-' . uniqid();

		$params[0]['after_title'] = str_replace( 'uniqID',  $unique_id , $params[0]['after_title'] );

	}

	return $params;
}
add_filter( 'dynamic_sidebar_params', 'customize_shop_widget_area' );
add_filter( 'widget_display_callback', function( $instance, $widget, $args ) {
	if (!$instance['title']){
		$instance['title'] = '&nbsp;';
	}
	return $instance;
}, 10, 3 );