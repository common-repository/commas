<?php
/**
 * Plugin Name:       Commas
 * Plugin URI:        https://github.com/HardeepAsrani/commas
 * Description:       Grammar-check for the Block Editor.
 * Version:           0.0.1
 * Author:            Hardeep Asrani
 * Author URI:        http://www.hardeepasrani.com
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       commas
 */

define( 'COMMAS_VERSION', '1.0.0' );
define( 'COMMAS_REST_NAMESPACE', 'commas' );
define( 'COMMAS_REST_VERSION', 'v1' );

function commas_enqueue_scripts() {
	wp_enqueue_script( 'commas', plugins_url( '/', __FILE__ ) . 'build/build.js', array( 'wp-api', 'wp-i18n', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-element' ), COMMAS_VERSION, true );
}

add_action( 'enqueue_block_editor_assets', 'commas_enqueue_scripts' );

function commas_register_rest() {
	register_rest_route(
		COMMAS_REST_NAMESPACE . '/' . COMMAS_REST_VERSION,
		'/check',
		array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => 'commas_check',
				'args'                => array(
					'content' => array(
						'type'        => 'string',
						'required'    => true,
						'description' => __( 'Content of Block.', 'commas' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			),
		)
	);
}

add_action( 'rest_api_init', 'commas_register_rest' );

function commas_check( $request ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return false;
	}

	$content = $request->get_param( 'content' );
	$api     = $request->get_param( 'api' );
	$args    = array(
		'headers' => array(
			'x-rapidapi-host' => 'grammarbot.p.rapidapi.com',
			'x-rapidapi-key'  => $api,
			'content-type'    => 'application/x-www-form-urlencoded'
		),
		'body'    => array(
			'language' => 'en-US',
			'text'     => $content
		),
	);

	$response = wp_remote_post( 'https://grammarbot.p.rapidapi.com/check', $args );
	$body     = wp_remote_retrieve_body( $response );
	return rest_ensure_response( $body );
}

function commas_register_settings() {
	register_setting(
		'commas',
		'commas_api',
		array(
			'type'              => 'string',
			'description'       => __( 'GrammarBot API', 'commas' ),
			'sanitize_callback' => 'sanitize_text_field',
			'show_in_rest'      => true,
			'default'           => '',
		)
	);
}

add_action( 'init', 'commas_register_settings', 99 );
