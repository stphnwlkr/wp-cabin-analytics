<?php
/**
 * Plugin Name:       Cabin Analytics
 * Description:       Cabin Analytics dashboard widget + block + shortcode (Sparkline or Cabin-style stacked Views/Visitors chart with Popover).
 * Version:           1.2.0
 * Requires at least: 6.9
 * Requires PHP:      8.3
 * Author:            Stephen Walker
 * License:           GPL-2.0-or-later
 * Text Domain:       wp-cabin-analytics
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WP_CABIN_ANALYTICS_VERSION', '1.2.0' );
define( 'WP_CABIN_ANALYTICS_FILE', __FILE__ );
define( 'WP_CABIN_ANALYTICS_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_CABIN_ANALYTICS_URL', plugin_dir_url( __FILE__ ) );

require_once WP_CABIN_ANALYTICS_DIR . 'includes/class-wp-cabin-plugin.php';

add_action( 'plugins_loaded', [ 'WP_Cabin_Plugin', 'init' ] );
