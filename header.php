<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php bloginfo('description'); ?>">
    <meta name="author" content="<?php bloginfo('name'); ?>">
    <link rel="icon" href="<?php echo esc_url(get_template_directory_uri() . '/favicon.ico'); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo esc_url(get_template_directory_uri() . '/favicon.ico'); ?>" type="image/x-icon">
    <meta property="og:title" content="<?php wp_title('|', true, 'right'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo esc_url(home_url()); ?>">
    <meta property="og:image" content="<?php echo esc_url(get_template_directory_uri() . '/og-image.jpg'); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php get_template_part('template-parts/header');  ?>
<?php get_template_part('template-parts/breadcrumbs-bar');  ?>
