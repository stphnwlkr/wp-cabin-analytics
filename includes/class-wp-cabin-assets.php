<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Assets {
	public static function init() : void {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'register' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'register' ] );
	}

	public static function register() : void {
		wp_register_style(
			'wp-cabin-analytics',
			WP_CABIN_ANALYTICS_URL . 'assets/css/wp-cabin.css',
			[],
			WP_CABIN_ANALYTICS_VERSION
		);

		wp_register_script(
			'wp-cabin-analytics-popover',
			WP_CABIN_ANALYTICS_URL . 'assets/js/wp-cabin-popover.js',
			[],
			WP_CABIN_ANALYTICS_VERSION,
			true
		);
	}

	public static function enqueue_shared() : void {
		wp_enqueue_style( 'wp-cabin-analytics' );
		wp_enqueue_script( 'wp-cabin-analytics-popover' );
	}
}
