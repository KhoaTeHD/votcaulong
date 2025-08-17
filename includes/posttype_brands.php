<?php
// -------------Brands------------- //
function create_brands_post_type() {
    $labels = [
        'name'                  => __('Brands', LANG_ZONE),
        'singular_name'         => __('Brand', LANG_ZONE),
        'menu_name'             => __('Brands', LANG_ZONE),
        'name_admin_bar'        => __('Brand', LANG_ZONE),
        'add_new'               => __('Add New (disabled)', LANG_ZONE),
        'add_new_item'          => __('Add New Brand (disabled)', LANG_ZONE),
        'edit_item'             => __('Edit Brand (disabled)', LANG_ZONE),
        'new_item'              => __('New Brand (disabled)', LANG_ZONE),
        'view_item'             => __('View Brand', LANG_ZONE),
        'search_items'          => __('Search Brands', LANG_ZONE),
        'not_found'             => __('No Brands found', LANG_ZONE),
        'not_found_in_trash'    => __('No Brands found in Trash', LANG_ZONE),
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'has_archive'        => true,
        'menu_icon'          => 'dashicons-tag',
        'supports'           => ['title', 'thumbnail','editor'],
        'show_in_rest'       => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'capabilities'       => [
            'create_posts' => 'do_not_allow',
        ],
        'rewrite'            => ['slug' => 'brands'],
    ];

    register_post_type('brands', $args);
}
add_action('init', 'create_brands_post_type');

function list_brand_management() {
	include(get_template_directory() . '/backend/list_brand_management.php');
}
