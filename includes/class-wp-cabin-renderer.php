<?php
if ( ! defined( 'ABSPATH' ) ) exit;

final class WP_Cabin_Renderer {

	public static function render( array $args = [] ) : string {
		$api_key = (string) get_option( WP_Cabin_Plugin::OPT_API_KEY, '' );

		$mode   = isset( $args['mode'] ) ? (string) $args['mode'] : (string) get_option( WP_Cabin_Plugin::OPT_MODE, 'sparkline' );
		$range  = isset( $args['range'] ) ? (string) $args['range'] : (string) get_option( WP_Cabin_Plugin::OPT_DEFAULT_RANGE, '14d' );
		$domain = isset( $args['domain'] ) ? (string) $args['domain'] : self::get_site_domain();

		$show_tabs   = ! empty( $args['show_tabs'] );
		$show_footer = ! empty( $args['show_footer'] );

		$mode  = in_array( $mode, [ 'sparkline', 'chart' ], true ) ? $mode : 'sparkline';
		$range = in_array( $range, [ '7d', '14d', '30d' ], true ) ? $range : (string) get_option( WP_Cabin_Plugin::OPT_DEFAULT_RANGE, '14d' );

		if ( empty( $domain ) ) return '<p>' . esc_html__( 'Could not determine this site’s domain.', 'wp-cabin-analytics' ) . '</p>';

		if ( empty( $api_key ) ) {
			$settings_url = admin_url( 'options-general.php?page=wp-cabin-analytics' );
			$msg = sprintf(
				__( 'Set your Cabin API key in %s.', 'wp-cabin-analytics' ),
				'<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings → Cabin Analytics', 'wp-cabin-analytics' ) . '</a>'
			);
			return '<p>' . wp_kses_post( $msg ) . '</p>';
		}

		$data = self::get_analytics( $api_key, $domain, $range, $mode );
		if ( is_wp_error( $data ) ) return '<p><strong>' . esc_html__( 'Error:', 'wp-cabin-analytics' ) . '</strong> ' . esc_html( $data->get_error_message() ) . '</p>';

		$summary    = isset( $data['summary'] ) && is_array( $data['summary'] ) ? $data['summary'] : [];
		$daily_data = isset( $data['daily_data'] ) && is_array( $data['daily_data'] ) ? $data['daily_data'] : [];

		$page_views      = isset( $summary['page_views'] ) ? (int) $summary['page_views'] : null;
		$unique_visitors = isset( $summary['unique_visitors'] ) ? (int) $summary['unique_visitors'] : null;
		$bounce_rate_pct = self::cabin_bounce_rate_percent( $summary );

		$uv_pct = null;
		if ( ! is_null( $unique_visitors ) && ! is_null( $page_views ) && $page_views > 0 ) {
			$uv_pct = ( $unique_visitors / $page_views ) * 100;
		}

		$instance_id = 'wp-cabin-' . wp_generate_uuid4();

		ob_start();
		?>
		<div class="wp-cabin" data-wp-cabin-instance>
			<?php if ( $show_tabs ) : ?>
				<?php echo self::render_tabs_header( $range, $domain, $mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>

			<?php if ( 'chart' === $mode ) : ?>
				<?php $chart = self::views_visitors_stacked_render( $daily_data, $instance_id ); ?>
				<div class="wp-cabin-chart-only">
					<div class="wp-cabin-chart-wrap">
						<div class="wp-cabin-chart-head" aria-hidden="true">
							<div class="wp-cabin-legend">
								<span class="wp-cabin-legend__item"><span class="wp-cabin-legend__swatch is-views" aria-hidden="true"></span><?php echo esc_html__( 'Views', 'wp-cabin-analytics' ); ?></span>
								<span class="wp-cabin-legend__item"><span class="wp-cabin-legend__swatch is-visitors" aria-hidden="true"></span><?php echo esc_html__( 'Visitors', 'wp-cabin-analytics' ); ?></span>
								<span class="wp-cabin-help" title="<?php echo esc_attr__( 'Click a bar to see values.', 'wp-cabin-analytics' ); ?>">
									<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
									<span class="screen-reader-text"><?php echo esc_html__( 'Click a bar to see values.', 'wp-cabin-analytics' ); ?></span>
								</span>
							</div>
						</div>
						<div class="wp-cabin-chart" aria-hidden="true">
							<div class="wp-cabin-chart-shell">
								<?php echo $chart['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo $chart['overlay']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo $chart['popover']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
						</div>
					</div>
					<div class="wp-cabin-metrics-row">
						<div class="wp-cabin-metric-card"><div class="wp-cabin-metric-card__label"><?php echo esc_html__( 'Page hits', 'wp-cabin-analytics' ); ?></div><div class="wp-cabin-metric-card__value"><?php echo esc_html( is_null( $page_views ) ? '—' : number_format_i18n( $page_views ) ); ?></div></div>
						<div class="wp-cabin-metric-card"><div class="wp-cabin-metric-card__label"><?php echo esc_html__( 'Unique visitors', 'wp-cabin-analytics' ); ?><?php if ( ! is_null( $uv_pct ) ) : ?><span class="wp-cabin-metric-card__hint">(<?php echo esc_html( number_format_i18n( $uv_pct, 0 ) ); ?>%)</span><?php endif; ?></div><div class="wp-cabin-metric-card__value"><?php echo esc_html( is_null( $unique_visitors ) ? '—' : number_format_i18n( $unique_visitors ) ); ?></div></div>
						<div class="wp-cabin-metric-card"><div class="wp-cabin-metric-card__label"><?php echo esc_html__( 'Bounce rate', 'wp-cabin-analytics' ); ?></div><div class="wp-cabin-metric-card__value"><?php echo esc_html( is_null( $bounce_rate_pct ) ? '—' : number_format_i18n( $bounce_rate_pct ) . '%' ); ?></div></div>
					</div>
				</div>
			<?php else : ?>
				<?php
					$spark_points = self::daily_points_from_timestamp_series( $daily_data, 'page_views' );
					$spark_svg    = self::sparkline_svg( $spark_points, 520, 120 );
				?>
				<div class="wp-cabin-grid">
					<div class="wp-cabin-metric"><div class="wp-cabin-metric__label"><?php echo esc_html__( 'Page hits', 'wp-cabin-analytics' ); ?></div><div class="wp-cabin-metric__value"><?php echo esc_html( is_null( $page_views ) ? '—' : number_format_i18n( $page_views ) ); ?></div></div>
					<div class="wp-cabin-metric"><div class="wp-cabin-metric__label"><?php echo esc_html__( 'Unique visitors', 'wp-cabin-analytics' ); ?><?php if ( ! is_null( $uv_pct ) ) : ?><span class="wp-cabin-metric__hint">(<?php echo esc_html( number_format_i18n( $uv_pct, 0 ) ); ?>%)</span><?php endif; ?></div><div class="wp-cabin-metric__value"><?php echo esc_html( is_null( $unique_visitors ) ? '—' : number_format_i18n( $unique_visitors ) ); ?></div></div>
					<div class="wp-cabin-metric"><div class="wp-cabin-metric__label"><?php echo esc_html__( 'Bounce rate', 'wp-cabin-analytics' ); ?></div><div class="wp-cabin-metric__value"><?php echo esc_html( is_null( $bounce_rate_pct ) ? '—' : number_format_i18n( $bounce_rate_pct ) . '%' ); ?></div></div>
					<div class="wp-cabin-spark"><div class="wp-cabin-spark__label"><?php echo esc_html__( 'Trend', 'wp-cabin-analytics' ); ?></div><div class="wp-cabin-spark__chart" aria-hidden="true"><?php echo $spark_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><p class="description wp-cabin-spark__desc"><?php echo esc_html__( 'Daily page hits over the selected range.', 'wp-cabin-analytics' ); ?></p></div>
				</div>
			<?php endif; ?>

			<?php if ( $show_footer ) : ?>
				<?php echo self::render_footer( $domain ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_tabs_header( string $range, string $domain, string $mode ) : string {
		$tabs = [ '7d' => '7d', '14d' => '14d', '30d' => '30d' ];
		$base = remove_query_arg( [ 'cabin_refresh', '_wpnonce' ] );

		$out  = '<div class="wp-cabin-header">';
		$out .= '<div class="wp-cabin-header__meta"><span class="dashicons dashicons-chart-area" aria-hidden="true"></span> <span class="wp-cabin-domain">' . esc_html( $domain ) . '</span></div>';
		$out .= '<nav class="wp-cabin-tabs" aria-label="Cabin date range">';

		foreach ( $tabs as $key => $label ) {
			$url = add_query_arg( 'cabin_range', $key, $base );
			$cls = 'wp-cabin-tab' . ( $range === $key ? ' is-active' : '' );
			$out .= '<a class="' . esc_attr( $cls ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
		}
		$out .= '</nav>';

		$refresh_url = wp_nonce_url(
			add_query_arg( [ 'cabin_range' => $range, 'cabin_refresh' => '1' ], $base ),
			WP_Cabin_Plugin::NONCE_ACTION
		);

		$out .= '<div class="wp-cabin-actions">';
		$out .= '<span class="wp-cabin-mode">' . esc_html( 'chart' === $mode ? 'Chart' : 'Sparkline' ) . '</span>';
		$out .= ' <a class="wp-cabin-refresh" href="' . esc_url( $refresh_url ) . '">' . esc_html__( 'Refresh', 'wp-cabin-analytics' ) . '</a>';
		$out .= '</div></div>';

		return $out;
	}

	private static function render_footer( string $domain ) : string {
		$cabin_link = 'https://withcabin.com/dashboard/' . rawurlencode( $domain );
		$left = '';
		if ( current_user_can( 'manage_options' ) ) {
			$settings_link = admin_url( 'options-general.php?page=wp-cabin-analytics' );
			$left = '<a href="' . esc_url( $settings_link ) . '">' . esc_html__( 'Settings', 'wp-cabin-analytics' ) . '</a>';
		}
		return '<div class="wp-cabin-footer">' . $left .
			'<a href="' . esc_url( $cabin_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Dashboard', 'wp-cabin-analytics' ) . '</a></div>';
	}

	/* API + caching */
	private static function get_analytics( string $api_key, string $domain, string $range, string $mode ) {
		$cache_key = self::cache_key( $domain, $range, $mode );
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) return $cached;

		[ $date_from, $date_to ] = self::range_dates( $range );

		$url = add_query_arg(
			[
				'domain'      => $domain,
				'date_from'   => $date_from,
				'date_to'     => $date_to,
				'scope'       => 'core',
				'limit_lists' => 10,
			],
			'https://api.withcabin.com/v1/analytics'
		);

		$res = wp_remote_get( $url, [
			'timeout' => 12,
			'headers' => [ 'x-api-key' => $api_key, 'accept' => 'application/json' ],
		] );

		if ( is_wp_error( $res ) ) return $res;

		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = (string) wp_remote_retrieve_body( $res );
		if ( $code < 200 || $code >= 300 ) return new WP_Error( 'cabin_http', 'Cabin API request failed (HTTP ' . $code . ').' );

		$json = json_decode( $body, true );
		if ( ! is_array( $json ) ) return new WP_Error( 'cabin_json', 'Could not parse Cabin response as JSON.' );

		set_transient( $cache_key, $json, 10 * MINUTE_IN_SECONDS );
		return $json;
	}

	public static function delete_cache( string $domain, string $range, string $mode ) : void {
		delete_transient( self::cache_key( $domain, $range, $mode ) );
	}

	private static function cache_key( string $domain, string $range, string $mode ) : string {
		return 'wp_cabin_' . md5( $domain . '|' . $range . '|' . $mode );
	}

	private static function range_dates( string $range ) : array {
		$tz = wp_timezone();
		$now = new DateTimeImmutable( 'now', $tz );
		$today = $now->format( 'Y-m-d' );
		$days = ( '30d' === $range ) ? 30 : ( ( '7d' === $range ) ? 7 : 14 );
		$from = $now->sub( new DateInterval( 'P' . ( $days - 1 ) . 'D' ) )->format( 'Y-m-d' );
		return [ $from, $today ];
	}

	public static function get_site_domain() : string {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! is_string( $host ) || '' === trim( $host ) ) return '';
		$host = preg_replace( '/:\\d+$/', '', $host );
		return strtolower( $host );
	}

	/* Sparkline */
	private static function daily_points_from_timestamp_series( array $daily_data, string $key ) : array {
		$points = [];
		foreach ( $daily_data as $row ) {
			if ( ! is_array( $row ) ) continue;
			if ( isset( $row[ $key ] ) && is_numeric( $row[ $key ] ) ) $points[] = (float) $row[ $key ];
		}
		return $points;
	}

	private static function sparkline_svg( array $values, int $w, int $h ) : string {
		$pad = 6;
		if ( count( $values ) < 2 ) {
			return '<svg class="wp-cabin-sparkline" viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '"><path d="M ' . (int) $pad . ' ' . (int) ( $h - $pad ) . ' L ' . (int) ( $w - $pad ) . ' ' . (int) ( $h - $pad ) . '" fill="none" stroke="currentColor" stroke-width="3" opacity="0.35" /></svg>';
		}

		$min = min( $values ); $max = max( $values );
		$range = ( $max - $min ); if ( 0.0 === $range ) $range = 1.0;
		$count = count( $values );
		$step = ( $w - ( 2 * $pad ) ) / ( $count - 1 );

		$pts = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$x = $pad + ( $i * $step );
			$norm = ( $values[ $i ] - $min ) / $range;
			$y = ( $h - $pad ) - ( $norm * ( $h - ( 2 * $pad ) ) );
			$pts[] = sprintf( '%.2f,%.2f', $x, $y );
		}
		$poly = implode( ' ', $pts );
		$area = $poly . ' ' . sprintf( '%.2f,%.2f', $pad + ( ( $count - 1 ) * $step ), ( $h - $pad ) ) . ' ' . sprintf( '%.2f,%.2f', $pad, ( $h - $pad ) );

		return '<svg class="wp-cabin-sparkline" viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '">' .
			'<polygon points="' . esc_attr( $area ) . '" fill="currentColor" opacity="0.10"></polygon>' .
			'<polyline points="' . esc_attr( $poly ) . '" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"></polyline>' .
		'</svg>';
	}

	/* Chart */
	private static function nice_max( float $max ) : float {
		if ( $max <= 0 ) return 1;
		$exp = floor( log10( $max ) );
		$base = pow( 10, $exp );
		$f = $max / $base;
		if ( $f <= 1 ) return 1 * $base;
		if ( $f <= 2 ) return 2 * $base;
		if ( $f <= 5 ) return 5 * $base;
		return 10 * $base;
	}
	private static function format_compact_number( float $n ) : string {
		if ( $n >= 1000000 ) return number_format_i18n( $n / 1000000, 1 ) . 'M';
		if ( $n >= 1000 ) return number_format_i18n( $n / 1000, 1 ) . 'K';
		return number_format_i18n( $n, 0 );
	}
	private static function cabin_bounce_rate_percent( array $summary ) : ?int {
		$uv = isset( $summary['unique_visitors'] ) && is_numeric( $summary['unique_visitors'] ) ? (float) $summary['unique_visitors'] : null;
		$b  = isset( $summary['bounces'] ) && is_numeric( $summary['bounces'] ) ? (float) $summary['bounces'] : null;
		if ( is_null( $uv ) || $uv <= 0 || is_null( $b ) || $b < 0 ) return null;
		$rate = min( 1.0, max( 0.0, $b / $uv ) );
		return (int) round( $rate * 100 );
	}

	private static function views_visitors_stacked_render( array $daily_data, string $instance_id ) : array {
		$points = [];
		foreach ( $daily_data as $row ) {
			if ( ! is_array( $row ) ) continue;
			$ts = isset( $row['timestamp'] ) && is_numeric( $row['timestamp'] ) ? (int) $row['timestamp'] : 0;
			$views = isset( $row['page_views'] ) && is_numeric( $row['page_views'] ) ? (float) $row['page_views'] : null;
			$uniq  = isset( $row['unique_visitors'] ) && is_numeric( $row['unique_visitors'] ) ? (float) $row['unique_visitors'] : null;
			if ( $ts <= 0 || is_null( $views ) || is_null( $uniq ) ) continue;
			$views = max( 0.0, $views );
			$uniq = max( 0.0, min( $uniq, $views ) );
			$cap = max( 0.0, $views - $uniq );
			$points[] = [ 'ts' => $ts, 'views' => $views, 'uniq' => $uniq, 'cap' => $cap ];
		}
		usort( $points, static fn( $a, $b ) => $a['ts'] <=> $b['ts'] );
		if ( count( $points ) < 2 ) return [ 'svg' => '', 'overlay' => '', 'popover' => '' ];

		$raw_max = max( array_map( static fn($p) => $p['views'], $points ) );
		$max = self::nice_max( (float) $raw_max ); if ( $max <= 0 ) $max = 1.0;

		$w = 860; $h = 380;
		$padL = 56; $padB = 52; $padT = 18; $padR = 16;
		$innerW = $w - $padL - $padR;
		$innerH = $h - $padT - $padB;

		$n = count( $points );
		$gap = 10;
		$barW = max( 10, ( $innerW - ( ( $n - 1 ) * $gap ) ) / $n );
		$gridLines = 4;
		$labelEvery = ( $n > 14 ) ? 3 : ( ( $n > 10 ) ? 2 : 1 );

		$tz = wp_timezone();

		$svg = '<svg class="wp-cabin-vv-chart" viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '" role="img" aria-label="Views and visitors per day">';
		$svg .= '<g class="grid">';
		for ( $i = 0; $i <= $gridLines; $i++ ) {
			$y = $padT + ( $innerH * ( $i / $gridLines ) );
			$svg .= '<line x1="' . (int) $padL . '" y1="' . (int) $y . '" x2="' . (int) ( $w - $padR ) . '" y2="' . (int) $y . '" />';
			$val = $max * ( 1 - ( $i / $gridLines ) );
			$svg .= '<text class="ylab" x="' . (int) ( $padL - 10 ) . '" y="' . (int) ( $y + 4 ) . '" text-anchor="end">' . esc_html( self::format_compact_number( $val ) ) . '</text>';
		}
		$svg .= '</g><g class="bars">';

		$overlay = '<div class="wp-cabin-overlay" aria-hidden="true">';
		$popover_id = 'wp-cabin-popover-' . preg_replace( '/[^a-z0-9\-]/i', '', $instance_id );

		for ( $i = 0; $i < $n; $i++ ) {
			$p = $points[ $i ];
			$x = $padL + ( $i * ( $barW + $gap ) );

			$uniqH = ( $p['uniq'] / $max ) * $innerH;
			$capH  = ( $p['cap']  / $max ) * $innerH;
			$yUniq = $padT + ( $innerH - $uniqH );
			$yCap  = $yUniq - $capH;
			$stackTop = $yCap;
			$stackH = $uniqH + $capH;

			$ts_sec = (int) floor( $p['ts'] / 1000 );
			$label = wp_date( 'D, j M Y', $ts_sec, $tz );

			$svg .= '<rect class="bar bar--visitors" x="' . esc_attr( $x ) . '" y="' . esc_attr( $yUniq ) . '" width="' . esc_attr( $barW ) . '" height="' . esc_attr( $uniqH ) . '" rx="2" />';
			if ( $capH > 0 ) $svg .= '<rect class="bar bar--views-cap" x="' . esc_attr( $x ) . '" y="' . esc_attr( $yCap ) . '" width="' . esc_attr( $barW ) . '" height="' . esc_attr( $capH ) . '" rx="2" />';
			if ( 0 === ( $i % $labelEvery ) ) {
				$short = wp_date( 'M j', $ts_sec, $tz );
				$svg .= '<text class="xlab" x="' . esc_attr( $x + ( $barW / 2 ) ) . '" y="' . esc_attr( $padT + $innerH + 32 ) . '" text-anchor="middle">' . esc_html( $short ) . '</text>';
			}

			$leftPct = ( $x / $w ) * 100;
			$topPct  = ( $stackTop / $h ) * 100;
			$wPct    = ( $barW / $w ) * 100;
			$hPct    = ( $stackH / $h ) * 100;
			if ( $hPct < 2.0 ) { $topPct = max( 0.0, $topPct - ( (2.0 - $hPct) / 2.0 ) ); $hPct = 2.0; }

			$anchor = '--wp-cabin-a-' . preg_replace( '/[^a-z0-9\-]/i', '', $instance_id ) . '-' . $i;
			$title = sprintf( '%s — Views: %s, Visitors: %s', $label, number_format_i18n( (int) $p['views'] ), number_format_i18n( (int) $p['uniq'] ) );

			$overlay .= sprintf(
				'<button type="button" class="wp-cabin-hit" style="left:%.4f%%; top:%.4f%%; width:%.4f%%; height:%.4f%%; anchor-name:%s;" data-wp-cabin-popover-id="%s" data-wp-cabin-anchor="%s" data-label="%s" data-uniq="%s" data-views="%s" aria-label="%s"></button>',
				$leftPct, $topPct, $wPct, $hPct,
				esc_attr( $anchor ),
				esc_attr( $popover_id ),
				esc_attr( $anchor ),
				esc_attr( $label ),
				esc_attr( number_format_i18n( (int) $p['uniq'] ) ),
				esc_attr( number_format_i18n( (int) $p['views'] ) ),
				esc_attr( $title )
			);
		}

		$svg .= '</g></svg>';
		$overlay .= '</div>';

		$popover =
			'<div class="wp-cabin-pop" popover id="' . esc_attr( $popover_id ) . '" data-wp-cabin-popover aria-hidden="true">' .
				'<div class="wp-cabin-balloon">' .
					'<div class="wp-cabin-balloon__title" data-wp-cabin-pop-title></div>' .
					'<div class="wp-cabin-balloon__row"><span class="sw sw--dark" aria-hidden="true"></span><span data-wp-cabin-pop-uniq></span></div>' .
					'<div class="wp-cabin-balloon__row"><span class="sw sw--light" aria-hidden="true"></span><span data-wp-cabin-pop-views></span></div>' .
				'</div>' .
			'</div>';

		return [ 'svg' => $svg, 'overlay' => $overlay, 'popover' => $popover ];
	}
}
