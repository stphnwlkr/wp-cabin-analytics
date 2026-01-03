<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Admin {
	public static function init() : void {
		add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
		add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
	}

	public static function add_settings_page() : void {
		add_options_page(
			__( 'Cabin Analytics', 'wp-cabin-analytics' ),
			__( 'Cabin Analytics', 'wp-cabin-analytics' ),
			'manage_options',
			'wp-cabin-analytics',
			[ __CLASS__, 'render_settings_page' ]
		);
	}

	public static function register_settings() : void {
		register_setting( 'wp_cabin_settings', WP_Cabin_Plugin::OPT_API_KEY, [
			'type' => 'string',
			'sanitize_callback' => [ __CLASS__, 'sanitize_api_key' ],
			'default' => '',
		] );

		register_setting( 'wp_cabin_settings', WP_Cabin_Plugin::OPT_MODE, [
			'type' => 'string',
			'sanitize_callback' => [ __CLASS__, 'sanitize_mode' ],
			'default' => 'sparkline',
		] );

		register_setting( 'wp_cabin_settings', WP_Cabin_Plugin::OPT_DEFAULT_RANGE, [
			'type' => 'string',
			'sanitize_callback' => [ __CLASS__, 'sanitize_range' ],
			'default' => '14d',
		] );

		add_settings_section(
			'wp_cabin_main',
			__( 'Settings', 'wp-cabin-analytics' ),
			function () {
				echo '<p>' . esc_html__( 'Enter your Cabin API key. Output uses the current site domain by default.', 'wp-cabin-analytics' ) . '</p>';
			},
			'wp-cabin-analytics'
		);

		add_settings_field(
			WP_Cabin_Plugin::OPT_API_KEY,
			__( 'Cabin API Key', 'wp-cabin-analytics' ),
			[ __CLASS__, 'render_api_key_field' ],
			'wp-cabin-analytics',
			'wp_cabin_main'
		);

		add_settings_field(
			WP_Cabin_Plugin::OPT_MODE,
			__( 'Default display mode', 'wp-cabin-analytics' ),
			[ __CLASS__, 'render_mode_field' ],
			'wp-cabin-analytics',
			'wp_cabin_main'
		);

		add_settings_field(
			WP_Cabin_Plugin::OPT_DEFAULT_RANGE,
			__( 'Default date range', 'wp-cabin-analytics' ),
			[ __CLASS__, 'render_default_range_field' ],
			'wp-cabin-analytics',
			'wp_cabin_main'
		);
	}

	public static function sanitize_api_key( $value ) : string {
		$value = is_string( $value ) ? trim( $value ) : '';
		return preg_replace( '/\s+/', '', $value );
	}

	public static function sanitize_mode( $value ) : string {
		$value = is_string( $value ) ? sanitize_key( $value ) : 'sparkline';
		return in_array( $value, [ 'sparkline', 'chart' ], true ) ? $value : 'sparkline';
	}

	public static function sanitize_range( $value ) : string {
		$value = is_string( $value ) ? sanitize_key( $value ) : '14d';
		return in_array( $value, [ '7d', '14d', '30d' ], true ) ? $value : '14d';
	}

	public static function render_api_key_field() : void {
		$val = get_option( WP_Cabin_Plugin::OPT_API_KEY, '' );
		echo '<input type="password" class="regular-text" name="' . esc_attr( WP_Cabin_Plugin::OPT_API_KEY ) . '" value="' . esc_attr( $val ) . '" autocomplete="off" />';
		echo '<p class="description">' . esc_html__( 'Required for live data.', 'wp-cabin-analytics' ) . '</p>';
	}

	public static function render_mode_field() : void {
		$mode = get_option( WP_Cabin_Plugin::OPT_MODE, 'sparkline' );
		?>
		<fieldset>
			<label><input type="radio" name="<?php echo esc_attr( WP_Cabin_Plugin::OPT_MODE ); ?>" value="sparkline" <?php checked( $mode, 'sparkline' ); ?> /> <?php echo esc_html__( 'Sparkline', 'wp-cabin-analytics' ); ?></label><br />
			<label><input type="radio" name="<?php echo esc_attr( WP_Cabin_Plugin::OPT_MODE ); ?>" value="chart" <?php checked( $mode, 'chart' ); ?> /> <?php echo esc_html__( 'Chart', 'wp-cabin-analytics' ); ?></label>
		</fieldset>
		<?php
	}

	public static function render_default_range_field() : void {
		$val = (string) get_option( WP_Cabin_Plugin::OPT_DEFAULT_RANGE, '14d' );
		?>
		<fieldset>
			<label><input type="radio" name="<?php echo esc_attr( WP_Cabin_Plugin::OPT_DEFAULT_RANGE ); ?>" value="7d"  <?php checked( $val, '7d' ); ?> /> 7d</label><br />
			<label><input type="radio" name="<?php echo esc_attr( WP_Cabin_Plugin::OPT_DEFAULT_RANGE ); ?>" value="14d" <?php checked( $val, '14d' ); ?> /> 14d</label><br />
			<label><input type="radio" name="<?php echo esc_attr( WP_Cabin_Plugin::OPT_DEFAULT_RANGE ); ?>" value="30d" <?php checked( $val, '30d' ); ?> /> 30d</label>
		</fieldset>
		<?php
	}

	public static function render_settings_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-cabin-analytics' ) );
		$domain = WP_Cabin_Renderer::get_site_domain();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Cabin Analytics', 'wp-cabin-analytics' ); ?></h1>
			<div class="notice notice-info inline"><p><strong><?php echo esc_html__( 'Domain:', 'wp-cabin-analytics' ); ?></strong> <?php echo esc_html( $domain ?: '—' ); ?></p></div>
			<form method="post" action="options.php">
				<?php settings_fields( 'wp_cabin_settings' ); do_settings_sections( 'wp-cabin-analytics' ); submit_button( __( 'Save Settings', 'wp-cabin-analytics' ) ); ?>
			</form>
		</div>
		<?php
	}
}
