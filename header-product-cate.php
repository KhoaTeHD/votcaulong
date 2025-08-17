<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    global $erp_data_head;
//    $erp_api = new ERP_API_Handler(true);
    $erp_api = new ERP_API_Client();
    $erp_data_head = null;
    if ($category_id = get_query_var('product_cate_id')){
//	    $api_data = $erp_api->get_category($category_id);
	    if ($pro_cate = get_term($category_id, 'pro_cate')) {
		    $category_info = $erp_api->get_category($pro_cate->name);
		    $erp_data_head = $category_info;
	    }
    }elseif ($product_id = get_query_var('product_id')){
	    $erp_data_head = $erp_api->get_product($product_id);
    }
    if (isset($erp_data_head)&& is_wp_error($erp_data_head)) {
	    $title = $erp_data_head->get_error_message();
    }elseif(isset($erp_data_head)){
	    $title = $erp_data_head['name']??$erp_data_head['title'];
    }elseif($compare = get_query_var('compare_products')){
        $title = 'So sánh sản phẩm';
    }else{
        $title = '';
    }
    ?>
    <title><?php echo $title ?></title>

    <meta name="author" content="<?php bloginfo('name'); ?>">
    <link rel="icon" href="<?php echo esc_url(get_template_directory_uri() . '/favicon.ico'); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo esc_url(get_template_directory_uri() . '/favicon.ico'); ?>" type="image/x-icon">
    <meta property="og:title" content="<?php echo $title ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url(home_url()); ?>">
    <meta property="og:image" content="<?php echo esc_url(get_template_directory_uri() . '/og-image.jpg'); ?>">
	<?php

    wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php get_template_part('template-parts/header');  ?>
<?php if (!is_wp_error($erp_data_head) ) { get_template_part('template-parts/breadcrumbs-bar' ); } ?>