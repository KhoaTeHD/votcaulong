<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
add_action('wp_ajax_get_erp_locations', 'ajax_get_erp_locations');
add_action('wp_ajax_nopriv_get_erp_locations', 'ajax_get_erp_locations');

function ajax_get_erp_locations() {
	if (!check_ajax_referer('themetoken-security', 'nonce', false)) {
		wp_send_json_error('Nonce invalid or expired.', LANG_ZONE);
	}

	$erp = new ERP_API_Client();
	$response = $erp->list_address_locations(0);

	if (is_wp_error($response)) {
		wp_send_json_error($response->get_error_message());
	}

	wp_send_json_success($response);
}

add_action('wp_ajax_get_erp_wards', 'ajax_get_erp_wards');
add_action('wp_ajax_nopriv_get_erp_wards', 'ajax_get_erp_wards');

function ajax_get_erp_wards() {
	if (!check_ajax_referer('themetoken-security', 'nonce', false)) {
		wp_send_json_error('Nonce invalid or expired.', LANG_ZONE);
	}
	$location = sanitize_text_field($_POST['location'] ?? '');


	$erp = new ERP_API_Client();
	$response = $erp->list_wards_by_location($location);

	if (is_wp_error($response)) {
		wp_send_json_error($response->get_error_message());
	}

	wp_send_json_success($response);
}
