<?php
//--------
function render_html_block_shortcode($atts) {
	$atts = shortcode_atts([
		'id' => '', // ID của block
	], $atts);
	$post_id = intval($atts['id']);
	if (!$post_id) {
		return __('Invalid Block ID.', LANG_ZONE);
	}
	$cache_key = 'html_block_' . $post_id;
	$cached_content = get_transient($cache_key);

	if ($cached_content !== false) {
		return $cached_content;
	}
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'cms_block' || $post->post_status !== 'publish') {
		return __('Block not found or not published.', LANG_ZONE);
	}
	$content = wp_kses_post(do_shortcode(apply_filters('the_content', $post->post_content)));
	set_transient($cache_key, $content, HOUR_IN_SECONDS); // Cache trong 1 giờ
	return $content;
}
add_shortcode('html_block', 'render_html_block_shortcode');
//---------------
function privacy_policy_shortcode() {
    $privacy_page_id = get_option('wp_page_for_privacy_policy');
    if (!$privacy_page_id) {
        return '';
    }
    
    $privacy_page = get_post($privacy_page_id);
    if (!$privacy_page) {
        return '';
    }
    
    return sprintf(
        '<a href="%s">%s</a>',
        esc_url(get_permalink($privacy_page)),
        esc_html($privacy_page->post_title)
    );
}
add_shortcode('privacy_policy', 'privacy_policy_shortcode');
