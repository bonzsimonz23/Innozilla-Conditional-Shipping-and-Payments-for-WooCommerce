<?php

// Innozilla API url
$api_url = 'https://innozilla.com/api/woo-conditional-shipping-and-payments-api-pro/index.php';

// Plugin Slug
$plugin_slug = 'innozilla-conditional-shipping-and-payments-woocommerce-pro';

// Plugin Name
$plugin_name = 'Innozilla Conditional Shipping and Payments for WooCommerce';

// Take over the update check
add_filter('pre_set_site_transient_update_plugins', 'ICSAPW__file_upload_update_checker');

function ICSAPW__file_upload_update_checker($checked_data) {
	global $api_url, $plugin_slug, $wp_version;

	// Comment out these two lines during testing.
	if ( empty( $checked_data->checked ) ) {
		return $checked_data;
	}

	$args = array(
		'slug' => $plugin_slug,
		'version' => $checked_data->checked[ $plugin_slug .'/innozilla-conditional-shipping-and-payments-woocommerce-pro.php'],
	);

	$request_string = array(
		'body' => array(
			'action' => 'basic_check',
			'request' => serialize( $args ),
			'api-key' => md5(get_bloginfo('url'))
		),
		'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
	);

	// Start checking for an update
	$raw_response = wp_remote_post( $api_url, $request_string );

	if ( ! is_wp_error( $raw_response ) && ( $raw_response['response']['code'] == 200 ) ){
		$response = unserialize( $raw_response['body'] );
	}

	if ( is_object( $response ) && ! empty( $response ) ) {
		$checked_data->response[ $plugin_slug .'/innozilla-conditional-shipping-and-payments-woocommerce-pro.php'] = $response;
	}

	return $checked_data;
}

// Take over the Plugin info screen
add_filter('plugins_api', 'ICSAPW__file_upload_api_update_call', 10, 3);

function ICSAPW__file_upload_api_update_call( $res, $action, $args ) {
	global $plugin_slug, $api_url, $wp_version, $plugin_name;

	// Do nothing if this is not about getting plugin information
	if( $action !== 'plugin_information' ) {
		return false;
	}

	// Dont proceed if it's not our plugin
	if ( isset( $args->slug ) && ( $args->slug != $plugin_slug ) ) {
		return $res;
	}

	// Get the current version
	$plugin_info = get_site_transient('update_plugins');
	$current_version = $plugin_info->checked[ $plugin_slug .'/innozilla-conditional-shipping-and-payments-woocommerce-pro.php'];

	// Versioning and Plugin name
	$args->version = $current_version;
	$args->name = $plugin_name;

	// Setup query string Args
	$request_string = array(
		'body' => array(
			'action' => $action,
			'request' => serialize($args),
			'api-key' => md5( get_bloginfo('url') )
		),
		'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
	);

	// Request file to API
	$request = wp_remote_post( $api_url, $request_string );

	if ( is_wp_error( $request ) ) {
		$res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>'), $request->get_error_message());
	} else {
		$res = unserialize( $request['body'] );
		if( $res === false ) {
			$res = new WP_Error( 'plugins_api_failed', __('An unknown error occurred'), $request['body'] );
		}
	}

	return $res;
}