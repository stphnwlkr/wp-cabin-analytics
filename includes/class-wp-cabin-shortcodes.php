<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Shortcodes {
	public static function init() : void {
		add_shortcode( 'cabin_analytics', [ __CLASS__, 'render' ] );
	}

	public static function render( $atts ) : string {
		$atts = shortcode_atts(
			[
				'mode'        => '',
				'range'       => '',
				'domain'      => '',
				'show_footer' => '0',
			],
			is_array( $atts ) ? $atts : [],
			'cabin_analytics'
		);

		$mode   = sanitize_key( (string) $atts['mode'] );
		$range  = sanitize_key( (string) $atts['range'] );
		$domain = trim( (string) $atts['domain'] );

		if ( ! in_array( $mode, [ 'sparkline', 'chart' ], true ) ) $mode = (string) get_option( WP_Cabin_Plugin::OPT_MODE, 'sparkline' );
		if ( ! in_array( $range, [ '7d', '14d', '30d' ], true ) ) $range = (string) get_option( WP_Cabin_Plugin::OPT_DEFAULT_RANGE, '14d' );
		if ( '' === $domain ) $domain = WP_Cabin_Renderer::get_site_domain();

		WP_Cabin_Assets::enqueue_shared();

		return WP_Cabin_Renderer::render( [
			'mode'        => $mode,
			'range'       => $range,
			'domain'      => $domain,
			'show_tabs'   => false,
			'show_footer' => ( '1' === (string) $atts['show_footer'] ),
		] );
	}
}
