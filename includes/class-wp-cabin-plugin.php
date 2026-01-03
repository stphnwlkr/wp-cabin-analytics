<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Plugin {
	public const OPT_API_KEY        = 'wp_cabin_api_key';
	public const OPT_MODE           = 'wp_cabin_widget_mode';        // 'sparkline' | 'chart'
	public const OPT_DEFAULT_RANGE  = 'wp_cabin_default_range';      // '7d' | '14d' | '30d'
	public const NONCE_ACTION       = 'wp_cabin_widget';

	public static function init() : void {
		require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-assets.php';
		require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-admin.php';
		require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-renderer.php';
		require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-dashboard-widget.php';
		require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-shortcodes.php';
		require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-blocks.php';

		WP_Cabin_Assets::init();
		WP_Cabin_Admin::init();
		WP_Cabin_Dashboard_Widget::init();
		WP_Cabin_Shortcodes::init();
		WP_Cabin_Blocks::init();
	}
}
