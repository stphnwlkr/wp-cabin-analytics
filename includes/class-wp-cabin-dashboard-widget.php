<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Dashboard_Widget {
	public static function init() : void {
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'register_widget' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_dashboard_assets' ] );
	}

	public static function register_widget() : void {
		wp_add_dashboard_widget(
			'wp_cabin_dashboard_widget',
			__( 'Cabin Analytics', 'wp-cabin-analytics' ),
			[ __CLASS__, 'render' ]
		);
	}

	public static function enqueue_dashboard_assets( string $hook ) : void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'dashboard' !== $screen->id ) return;
		WP_Cabin_Assets::enqueue_shared();
	}

	public static function render() : void {
		$range = isset( $_GET['cabin_range'] ) ? sanitize_key( (string) $_GET['cabin_range'] ) : '';
		if ( ! in_array( $range, [ '7d', '14d', '30d' ], true ) ) {
			$range = (string) get_option( WP_Cabin_Plugin::OPT_DEFAULT_RANGE, '14d' );
		}

		$mode = (string) get_option( WP_Cabin_Plugin::OPT_MODE, 'sparkline' );
		$mode = in_array( $mode, [ 'sparkline', 'chart' ], true ) ? $mode : 'sparkline';

		$domain = WP_Cabin_Renderer::get_site_domain();

		$force_refresh = isset( $_GET['cabin_refresh'] ) && '1' === (string) $_GET['cabin_refresh'];
		if ( $force_refresh && check_admin_referer( WP_Cabin_Plugin::NONCE_ACTION ) ) {
			WP_Cabin_Renderer::delete_cache( $domain, $range, $mode );
		}

		WP_Cabin_Assets::enqueue_shared();

		echo WP_Cabin_Renderer::render( [
			'mode'        => $mode,
			'range'       => $range,
			'domain'      => $domain,
			'show_tabs'   => true,
			'show_footer' => true,
		] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
