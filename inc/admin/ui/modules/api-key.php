<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

add_settings_section( 'rocket_display_apikey_options', __( 'License validation', 'rocket' ), '__return_false', 'rocket_apikey' );

/**
 * Panel caption
 */
add_settings_field(
	'rocket_apikey_options_panel',
	false,
	'rocket_field',
	'rocket_apikey',
	'rocket_display_apikey_options',
	array(
		array(
			'type'         => 'helper_panel_description',
			'name'         => 'apikey_options_panel_caption',
			'description'  => sprintf(
				'<span class="dashicons dashicons-lock" aria-hidden="true"></span><strong>%1$s</strong>',
				/* translators: line break recommended, but not mandatory  */
				__( 'WP Rocket was not able to automatically validate your license.<br>Follow <a href="http://docs.wp-rocket.me/article/100-resolving-problems-with-license-validation" target="_blank">this tutorial</a>, or contact <a href="https://wp-rocket.me/support/" target="_blank">support</a> to get this engine started.', 'rocket' )
			),
		),
	)
);

/**
 * API key
 */
add_settings_field(
	'rocket_api_key',
	__( 'API key', 'rocket' ),
	'rocket_field',
	'rocket_apikey',
	'rocket_display_apikey_options',
	array(
		array(
			'type'         => 'text',
			'label_for'    => 'consumer_key',
			'label_screen' => __( 'API key', 'rocket' ),
			'name'         => 'consumer_key',
			'placeholder'  => '123ab45c',
		),
	)
);
