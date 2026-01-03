<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Blocks {
	public static function init() : void {
		add_action( 'init', [ __CLASS__, 'register_block' ] );
	}

	public static function register_block() : void {
		$editor = 'wp-cabin-analytics-block-editor';

		wp_register_script(
			$editor,
			WP_CABIN_ANALYTICS_URL . 'assets/js/block-editor.js',
			[ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ],
			WP_CABIN_ANALYTICS_VERSION,
			true
		);

		register_block_type( 'wp-cabin/analytics', [
			'api_version'     => 2,
			'editor_script'   => $editor,
			'style'           => 'wp-cabin-analytics',
			'script'          => 'wp-cabin-analytics-popover',
			'render_callback' => [ __CLASS__, 'render_callback' ],
			'attributes'      => [
				'mode'       => [ 'type' => 'string',  'default' => '' ],
				'range'      => [ 'type' => 'string',  'default' => '' ],
				'domain'     => [ 'type' => 'string',  'default' => '' ],
				'showFooter' => [ 'type' => 'boolean', 'default' => false ],
			],
			'supports'        => [ 'html' => false ],
		] );
	}

	public static function render_callback( array $attributes ) : string {
		$mode   = isset( $attributes['mode'] ) ? sanitize_key( (string) $attributes['mode'] ) : '';
		$range  = isset( $attributes['range'] ) ? sanitize_key( (string) $attributes['range'] ) : '';
		$domain = isset( $attributes['domain'] ) ? trim( (string) $attributes['domain'] ) : '';

		if ( ! in_array( $mode, [ 'sparkline', 'chart' ], true ) ) $mode = (string) get_option( WP_Cabin_Plugin::OPT_MODE, 'sparkline' );
		if ( ! in_array( $range, [ '7d', '14d', '30d' ], true ) ) $range = (string) get_option( WP_Cabin_Plugin::OPT_DEFAULT_RANGE, '14d' );
		if ( '' === $domain ) $domain = WP_Cabin_Renderer::get_site_domain();

		WP_Cabin_Assets::enqueue_shared();

		return WP_Cabin_Renderer::render( [
			'mode'        => $mode,
			'range'       => $range,
			'domain'      => $domain,
			'show_tabs'   => false,
			'show_footer' => ! empty( $attributes['showFooter'] ),
		] );
	}
}
