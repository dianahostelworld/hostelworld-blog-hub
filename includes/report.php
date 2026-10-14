<?php
/**
 * Hub report: which parts of the hubs people actually use.
 *
 * assets/hub-stats.js sends three kinds of event from every hub page: a view,
 * each section reached, and each click (with its section and label). They are
 * stored here as daily totals per hub, with nothing about the reader, and
 * shown under Pages > Hub report.
 *
 * Logged-in editors are not counted, and neither is anything that calls itself
 * a bot. Totals older than 400 days are deleted.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HW_HUB_STATS_DB', '1' );

/**
 * @return string Table name.
 */
function hw_hub_stats_table() {
	global $wpdb;

	return $wpdb->prefix . 'hw_hub_stats';
}

/**
 * Creates or updates the table when the plugin version changes.
 */
function hw_hub_stats_install() {
	if ( HW_HUB_STATS_DB === get_option( 'hw_hub_stats_db' ) ) {
		return;
	}

	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table   = hw_hub_stats_table();
	$charset = $wpdb->get_charset_collate();

	dbDelta(
		"CREATE TABLE {$table} (
			day date NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			kind char(1) NOT NULL,
			section varchar(40) NOT NULL DEFAULT '',
			label varchar(100) NOT NULL DEFAULT '',
			hits int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (day,post_id,kind,section,label),
			KEY post_day (post_id,day)
		) {$charset};"
	);

	update_option( 'hw_hub_stats_db', HW_HUB_STATS_DB );

	if ( ! get_option( 'hw_hub_stats_since' ) ) {
		update_option( 'hw_hub_stats_since', current_time( 'Y-m-d' ) );
	}
}
add_action( 'admin_init', 'hw_hub_stats_install' );
add_action( 'rest_api_init', 'hw_hub_stats_install' );

/* -------------------------------------------------------------------------
 * Collecting
 * ---------------------------------------------------------------------- */

/**
 * Adds the counter to hub pages, except for people who edit the site.
 */
function hw_hub_stats_script() {
	if ( ! is_singular( 'page' ) || ! in_array( get_page_template_slug( get_queried_object_id() ), hw_hub_template_keys(), true ) ) {
		return;
	}

	if ( current_user_can( 'edit_posts' ) ) {
		return;
	}

	wp_enqueue_script( 'hw-hub-stats', HW_HUB_URL . 'assets/hub-stats.js', array(), HW_HUB_VERSION, true );
	wp_add_inline_script(
		'hw-hub-stats',
		'window.hwHubStats=' . wp_json_encode(
			array(
				'url'  => rest_url( 'hw-hub/v1/hit' ),
				'post' => get_queried_object_id(),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'hw_hub_stats_script', 20 );

/**
 * POST /wp-json/hw-hub/v1/hit
 */
function hw_hub_stats_route() {
	register_rest_route(
		'hw-hub/v1',
		'/hit',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'hw_hub_stats_record',
		)
	);
}
add_action( 'rest_api_init', 'hw_hub_stats_route' );

/**
 * Stores one batch. Anything malformed is dropped quietly: the reader never
 * sees this, and a broken batch should never become an error page.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function hw_hub_stats_record( WP_REST_Request $request ) {
	$empty = new WP_REST_Response( null, 204 );
	$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

	if ( '' === $agent || preg_match( '/bot|crawl|spider|slurp|headless|lighthouse|pingdom|preview|monitor/i', $agent ) ) {
		return $empty;
	}

	$data = json_decode( (string) $request->get_body(), true );

	if ( ! is_array( $data ) || empty( $data['p'] ) || empty( $data['e'] ) || ! is_array( $data['e'] ) ) {
		return $empty;
	}

	$post_id = absint( $data['p'] );

	if ( 'publish' !== get_post_status( $post_id ) || ! in_array( get_page_template_slug( $post_id ), hw_hub_template_keys(), true ) ) {
		return $empty;
	}

	global $wpdb;
	$table = hw_hub_stats_table();
	$day   = current_time( 'Y-m-d' );
	$done  = array();

	foreach ( array_slice( $data['e'], 0, 40 ) as $event ) {
		if ( ! is_array( $event ) || count( $event ) < 2 ) {
			continue;
		}

		$kind    = (string) $event[0];
		$section = substr( sanitize_key( (string) $event[1] ), 0, 40 );
		$label   = 'c' === $kind && isset( $event[2] ) ? mb_substr( sanitize_text_field( (string) $event[2] ), 0, 100 ) : '';

		if ( ! in_array( $kind, array( 'v', 's', 'c' ), true ) ) {
			continue;
		}

		if ( 'v' === $kind ) {
			$section = '';
		}

		// One view and one reach per section per batch, however it was sent.
		$key = $kind . '|' . $section . '|' . $label;

		if ( 'c' !== $kind && isset( $done[ $key ] ) ) {
			continue;
		}

		$done[ $key ] = true;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"INSERT INTO {$table} (day, post_id, kind, section, label, hits) VALUES (%s, %d, %s, %s, %s, 1) ON DUPLICATE KEY UPDATE hits = hits + 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$day,
				$post_id,
				$kind,
				$section,
				$label
			)
		);
	}

	return $empty;
}

/**
 * Deletes totals older than 400 days, once a day.
 */
function hw_hub_stats_prune() {
	global $wpdb;
	$table = hw_hub_stats_table();

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE day < %s", gmdate( 'Y-m-d', time() - 400 * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
add_action( 'hw_hub_stats_prune', 'hw_hub_stats_prune' );

/**
 * Keeps the daily clean-up scheduled.
 */
function hw_hub_stats_schedule() {
	if ( ! wp_next_scheduled( 'hw_hub_stats_prune' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'hw_hub_stats_prune' );
	}
}
add_action( 'init', 'hw_hub_stats_schedule' );

/* -------------------------------------------------------------------------
 * Reading
 * ---------------------------------------------------------------------- */

/**
 * Plain names for section keys. Anything not listed is shown from its key.
 *
 * @return array
 */
function hw_hub_stats_section_names() {
	return array(
		'hero'        => 'Hero (top of the page)',
		'postcard'    => 'Postcard maker',
		'trending'    => 'Also trending this week',
		'team'        => 'From the Hostelworld team',
		'nav'         => 'On this page menu',
		'overview'    => 'Overview',
		'why'         => 'Overview',
		'areas'       => 'Neighbourhoods',
		'itineraries' => 'Itineraries',
		'do'          => 'Things to do',
		'free'        => 'Free things to do',
		'food'        => 'Food and drink',
		'nightlife'   => 'Nightlife',
		'transport'   => 'Getting around',
		'money'       => 'Costs',
		'safety'      => 'Safety and scams',
		'daytrips'    => 'Day trips',
		'events'      => "What's on",
		'hostels'     => 'Where to stay',
		'season'      => 'When to go',
		'when'        => 'When to go',
		'budget'      => 'Budget calculator',
		'costs'       => 'Costs',
		'faqs'        => 'FAQs',
		'cta'         => 'Closing call to action',
		'cities'      => 'Cities',
		'countries'   => 'Countries',
		'states'      => 'States',
		'regions'     => 'Regions',
		'map'         => 'Map',
		'trips'       => 'Types of trip',
		'rail'        => 'Rail',
		'reads'       => 'Articles',
		'other'       => 'Elsewhere on the page',
	);
}

/**
 * @param string $key Section key.
 * @return string
 */
function hw_hub_stats_section_name( $key ) {
	$names = hw_hub_stats_section_names();

	return isset( $names[ $key ] ) ? $names[ $key ] : ucfirst( str_replace( array( '-', '_' ), ' ', $key ) );
}

/**
 * The report's filters, from the address.
 *
 * @return array days, type, hub, from, to.
 */
function hw_hub_stats_filters() {
	$days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30; // phpcs:ignore WordPress.Security.NonceVerification
	$days = in_array( $days, array( 7, 30, 90, 365 ), true ) ? $days : 30;
	$type = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$hub  = isset( $_GET['hub'] ) ? absint( $_GET['hub'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

	return array(
		'days' => $days,
		'type' => in_array( $type, array( 'continent', 'country', 'country_states', 'city', 'state' ), true ) ? $type : '',
		'hub'  => $hub,
		'from' => gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) ) - ( $days - 1 ) * DAY_IN_SECONDS ),
		'to'   => current_time( 'Y-m-d' ),
	);
}

/**
 * Totals per hub, section, kind and label for the period.
 *
 * @param array $f Filters.
 * @return array Rows: post_id, kind, section, label, hits.
 */
function hw_hub_stats_rows( $f ) {
	global $wpdb;
	$table = hw_hub_stats_table();
	$where = $wpdb->prepare( 'day BETWEEN %s AND %s', $f['from'], $f['to'] );

	if ( $f['hub'] ) {
		$where .= $wpdb->prepare( ' AND post_id = %d', $f['hub'] );
	}

	$rows = $wpdb->get_results( "SELECT post_id, kind, section, label, SUM(hits) AS hits FROM {$table} WHERE {$where} GROUP BY post_id, kind, section, label", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $f['type'] && ! $f['hub'] ) {
		$rows = array_values(
			array_filter(
				$rows,
				function ( $row ) use ( $f ) {
					return hw_hub_template_for_post( (int) $row['post_id'] ) === $f['type'];
				}
			)
		);
	}

	return $rows;
}

/**
 * Works the rows into what the screen shows.
 *
 * @param array $rows Rows.
 * @return array
 */
function hw_hub_stats_summarise( $rows ) {
	$views    = 0;
	$clicks   = 0;
	$sections = array();
	$hubs     = array();
	$links    = array();

	foreach ( $rows as $row ) {
		$hits = (int) $row['hits'];
		$id   = (int) $row['post_id'];
		$sec  = $row['section'];

		if ( ! isset( $hubs[ $id ] ) ) {
			$hubs[ $id ] = array( 'views' => 0, 'clicks' => 0, 'by_section' => array() );
		}

		if ( 'v' === $row['kind'] ) {
			$views              += $hits;
			$hubs[ $id ]['views'] += $hits;
			continue;
		}

		if ( ! isset( $sections[ $sec ] ) ) {
			$sections[ $sec ] = array( 'reached' => 0, 'clicks' => 0 );
		}

		if ( 's' === $row['kind'] ) {
			$sections[ $sec ]['reached'] += $hits;
			continue;
		}

		$clicks                         += $hits;
		$sections[ $sec ]['clicks']     += $hits;
		$hubs[ $id ]['clicks']          += $hits;
		$hubs[ $id ]['by_section'][ $sec ] = ( isset( $hubs[ $id ]['by_section'][ $sec ] ) ? $hubs[ $id ]['by_section'][ $sec ] : 0 ) + $hits;

		$link_key = $sec . '|' . $row['label'];

		if ( ! isset( $links[ $link_key ] ) ) {
			$links[ $link_key ] = array( 'section' => $sec, 'label' => $row['label'], 'clicks' => 0 );
		}

		$links[ $link_key ]['clicks'] += $hits;
	}

	// Sections in roughly page order: the most-reached first.
	uasort(
		$sections,
		function ( $a, $b ) {
			return $b['reached'] - $a['reached'] ?: $b['clicks'] - $a['clicks'];
		}
	);

	usort(
		$links,
		function ( $a, $b ) {
			return $b['clicks'] - $a['clicks'];
		}
	);

	return array(
		'views'    => $views,
		'clicks'   => $clicks,
		'sections' => $sections,
		'hubs'     => $hubs,
		'links'    => $links,
	);
}

/* -------------------------------------------------------------------------
 * Search Console, through Hostelworld Content Health
 * ---------------------------------------------------------------------- */

/**
 * Whether Content Health is active and connected to Search Console. The
 * report uses its connection rather than asking for a second one.
 *
 * @return bool
 */
function hw_hub_stats_gsc_available() {
	if ( ! class_exists( 'HWCH_GSC' ) || ! method_exists( 'HWCH_GSC', 'pages' ) || ! method_exists( 'HWCH_GSC', 'token' ) ) {
		return false;
	}

	return ! is_wp_error( HWCH_GSC::token() );
}

/**
 * Search Console's dates for the period. Its data is about two days behind,
 * so the period ends two days ago and keeps the same length.
 *
 * @param array $f Filters.
 * @return array start, end.
 */
function hw_hub_stats_gsc_range( $f ) {
	$end = strtotime( current_time( 'Y-m-d' ) ) - 2 * DAY_IN_SECONDS;

	return array(
		'start' => gmdate( 'Y-m-d', $end - ( $f['days'] - 1 ) * DAY_IN_SECONDS ),
		'end'   => gmdate( 'Y-m-d', $end ),
	);
}

/**
 * Google clicks, impressions and position for every blog address in the
 * period, keyed the way Content Health normalises URLs. Cached for 12 hours.
 *
 * @param array $f Filters.
 * @return array|WP_Error
 */
function hw_hub_stats_gsc_pages( $f ) {
	$range = hw_hub_stats_gsc_range( $f );
	$key   = 'hw_hub_gsc_' . md5( $range['start'] . $range['end'] );
	$data  = get_transient( $key );

	if ( is_array( $data ) ) {
		return $data;
	}

	$data = HWCH_GSC::pages( $range['start'], $range['end'] );

	if ( ! is_wp_error( $data ) ) {
		set_transient( $key, $data, 12 * HOUR_IN_SECONDS );
	}

	return $data;
}

/**
 * One hub's figures from the Search Console map.
 *
 * @param array|WP_Error $pages Map from hw_hub_stats_gsc_pages().
 * @param int            $id    Page.
 * @return array clicks, impressions, position (zeros when Google has nothing).
 */
function hw_hub_stats_gsc_for( $pages, $id ) {
	$empty = array( 'clicks' => 0, 'impressions' => 0, 'position' => 0 );

	if ( ! is_array( $pages ) ) {
		return $empty;
	}

	$url = HWCH_GSC::normalise( get_permalink( $id ) );

	return isset( $pages[ $url ] ) ? wp_parse_args( $pages[ $url ], $empty ) : $empty;
}

/**
 * One hub's top searches in the period. Cached for 12 hours.
 *
 * @param int   $id Page.
 * @param array $f  Filters.
 * @return array|WP_Error Rows: query, clicks, impressions, position.
 */
function hw_hub_stats_gsc_queries( $id, $f ) {
	$range = hw_hub_stats_gsc_range( $f );
	$url   = get_permalink( $id );
	$key   = 'hw_hub_gscq_' . md5( $url . $range['start'] . $range['end'] );
	$data  = get_transient( $key );

	if ( is_array( $data ) ) {
		return $data;
	}

	$rows = HWCH_GSC::query(
		array( 'query' ),
		$range['start'],
		$range['end'],
		array(
			'max'     => 25,
			'filters' => array(
				array(
					'dimension'  => 'page',
					'operator'   => 'equals',
					'expression' => $url,
				),
			),
		)
	);

	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	$data = array();

	foreach ( $rows as $row ) {
		$data[] = array(
			'query'       => isset( $row['keys'][0] ) ? $row['keys'][0] : '',
			'clicks'      => (int) $row['clicks'],
			'impressions' => (int) $row['impressions'],
			'position'    => round( (float) $row['position'], 1 ),
		);
	}

	usort(
		$data,
		function ( $a, $b ) {
			return $b['clicks'] <=> $a['clicks'] ?: $b['impressions'] <=> $a['impressions'];
		}
	);

	set_transient( $key, $data, 12 * HOUR_IN_SECONDS );

	return $data;
}

/**
 * Every published hub of the chosen type, so hubs Google sends people to
 * appear even before anyone has been counted on them.
 *
 * @param array $f Filters.
 * @return int[]
 */
function hw_hub_stats_all_hubs( $f ) {
	$args = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_wp_page_template',
				'value'   => hw_hub_template_keys(),
				'compare' => 'IN',
			),
		),
	);

	if ( function_exists( 'pll_languages_list' ) ) {
		$args['lang'] = '';
	}

	$ids = get_posts( $args );

	if ( $f['type'] ) {
		$ids = array_values(
			array_filter(
				$ids,
				function ( $id ) use ( $f ) {
					return hw_hub_template_for_post( $id ) === $f['type'];
				}
			)
		);
	}

	return array_map( 'intval', $ids );
}

/**
 * Forgets the cached Search Console figures.
 */
function hw_hub_stats_gsc_refresh() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hub_gsc_refresh' );

	global $wpdb;
	foreach ( array( '_transient_hw_hub_gsc', '_transient_timeout_hw_hub_gsc' ) as $prefix ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=page&page=hw-hub-report' ) );
	exit;
}
add_action( 'admin_post_hw_hub_gsc_refresh', 'hw_hub_stats_gsc_refresh' );

/* -------------------------------------------------------------------------
 * The screen
 * ---------------------------------------------------------------------- */

/**
 * Pages > Hub report.
 */
function hw_hub_stats_menu() {
	add_submenu_page( 'edit.php?post_type=page', __( 'Hub report', 'hostelworld' ), __( 'Hub report', 'hostelworld' ), 'edit_pages', 'hw-hub-report', 'hw_hub_stats_render' );
}
add_action( 'admin_menu', 'hw_hub_stats_menu' );

/**
 * @param array $args Query args to change.
 * @return string
 */
function hw_hub_stats_url( $args = array() ) {
	$f    = hw_hub_stats_filters();
	$base = array(
		'post_type' => 'page',
		'page'      => 'hw-hub-report',
		'days'      => $f['days'],
		'type'      => $f['type'],
		'hub'       => $f['hub'],
	);

	return add_query_arg( array_filter( array_merge( $base, $args ) ), admin_url( 'edit.php' ) );
}

/**
 * "12.3" per 100, or a dash when there is nothing to divide by.
 *
 * @param int $part  Part.
 * @param int $whole Whole.
 * @return string
 */
function hw_hub_stats_rate( $part, $whole ) {
	return $whole ? number_format_i18n( 100 * $part / $whole, 1 ) : '–';
}

/**
 * A row's bar, as a share of the biggest value in its column.
 *
 * @param int $value Value.
 * @param int $max   Largest value in the column.
 * @return string
 */
function hw_hub_stats_bar( $value, $max ) {
	$width = $max ? max( 1, round( 100 * $value / $max ) ) : 0;

	return '<span class="hw-report__bar"><span style="width:' . (int) $width . '%"></span></span>';
}

/**
 * The hub's name, with its language where there are several.
 *
 * @param int $id Page.
 * @return string
 */
function hw_hub_stats_hub_name( $id ) {
	$name = get_the_title( $id );

	if ( function_exists( 'pll_get_post_language' ) ) {
		$lang = pll_get_post_language( $id, 'slug' );
		$name .= $lang ? ' (' . $lang . ')' : '';
	}

	return $name ? $name : '#' . $id;
}

/**
 * The template's plain name.
 *
 * @param int $id Page.
 * @return string
 */
function hw_hub_stats_type_name( $id ) {
	$names = array(
		'continent'      => 'Continent',
		'country'        => 'Country',
		'country_states' => 'Country (states)',
		'city'           => 'City',
		'state'          => 'State',
	);
	$type  = hw_hub_template_for_post( $id );

	return isset( $names[ $type ] ) ? $names[ $type ] : $type;
}


/**
 * Everything the screen and the CSV show, worked out once.
 *
 * @param array $f Filters.
 * @return array
 */
function hw_hub_stats_build( $f ) {
	$data       = hw_hub_stats_summarise( hw_hub_stats_rows( $f ) );
	$gsc_on     = hw_hub_stats_gsc_available();
	$gsc        = $gsc_on ? hw_hub_stats_gsc_pages( $f ) : null;
	$gsc_error  = is_wp_error( $gsc ) ? $gsc->get_error_message() : '';
	$ids        = $f['hub'] ? array( $f['hub'] ) : hw_hub_stats_all_hubs( $f );
	$g_clicks   = 0;
	$g_impr     = 0;
	$g_pos_sum  = 0;

	foreach ( $ids as $id ) {
		$g = $gsc_on && ! $gsc_error ? hw_hub_stats_gsc_for( $gsc, $id ) : array( 'clicks' => 0, 'impressions' => 0, 'position' => 0 );

		if ( ! isset( $data['hubs'][ $id ] ) ) {
			// A hub nobody has been counted on yet only belongs in the list if Google shows it.
			if ( ! $g['impressions'] && ! $f['hub'] ) {
				continue;
			}

			$data['hubs'][ $id ] = array( 'views' => 0, 'clicks' => 0, 'by_section' => array() );
		}

		$data['hubs'][ $id ]['g'] = $g;
		$g_clicks                += $g['clicks'];
		$g_impr                  += $g['impressions'];
		$g_pos_sum               += $g['position'] * $g['impressions'];
	}

	// Hubs counted on the site but outside the type filter can't appear here,
	// because the rows are already filtered; anything left without 'g' gets zeros.
	foreach ( $data['hubs'] as $id => $h ) {
		if ( ! isset( $h['g'] ) ) {
			$data['hubs'][ $id ]['g'] = array( 'clicks' => 0, 'impressions' => 0, 'position' => 0 );
		}
	}

	$data['gsc_on']    = $gsc_on;
	$data['gsc_error'] = $gsc_error;
	$data['g_clicks']  = $g_clicks;
	$data['g_impr']    = $g_impr;
	$data['g_pos']     = $g_impr ? round( $g_pos_sum / $g_impr, 1 ) : 0;
	$data['queries']   = ( $f['hub'] && $gsc_on && ! $gsc_error ) ? hw_hub_stats_gsc_queries( $f['hub'], $f ) : array();

	return $data;
}

/**
 * The report.
 */
function hw_hub_stats_render() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	hw_hub_stats_install();

	$f       = hw_hub_stats_filters();
	$data    = hw_hub_stats_build( $f );
	$since   = get_option( 'hw_hub_stats_since' );
	$range   = hw_hub_stats_gsc_range( $f );
	$csv     = wp_nonce_url( add_query_arg( array( 'action' => 'hw_hub_report_csv', 'days' => $f['days'], 'type' => $f['type'], 'hub' => $f['hub'] ), admin_url( 'admin-post.php' ) ), 'hw_hub_report_csv' );
	$refresh = wp_nonce_url( admin_url( 'admin-post.php?action=hw_hub_gsc_refresh' ), 'hw_hub_gsc_refresh' );
	$tile    = function ( $value, $label ) {
		return '<div class="hw-report__tile"><strong>' . esc_html( $value ) . '</strong><span>' . esc_html( $label ) . '</span></div>';
	};
	?>
	<div class="wrap hw-report">
		<style>
			.hw-report__filters { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin: 16px 0; }
			.hw-report__tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; margin: 12px 0 8px; }
			.hw-report__tile { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 16px; }
			.hw-report__tile strong { display: block; font-size: 26px; line-height: 1.1; margin-bottom: 4px; }
			.hw-report__tile span { color: #646970; }
			.hw-report__group { margin: 20px 0 4px; font-weight: 600; color: #1d2327; }
			.hw-report table td, .hw-report table th { vertical-align: middle; }
			.hw-report td.num, .hw-report th.num { text-align: right; white-space: nowrap; }
			.hw-report th.gsc, .hw-report td.gsc { background: #f6f7ff; }
			.hw-report__bar { display: inline-block; width: 140px; height: 8px; background: #f0f0f1; border-radius: 4px; overflow: hidden; vertical-align: middle; }
			.hw-report__bar span { display: block; height: 100%; background: #F25621; }
			.hw-report h2 { margin-top: 32px; }
			.hw-report .description { max-width: 860px; }
		</style>

		<h1><?php echo esc_html( $f['hub'] ? 'Hub report: ' . hw_hub_stats_hub_name( $f['hub'] ) : 'Hub report' ); ?></h1>

		<?php if ( $f['hub'] ) : ?>
			<p><a href="<?php echo esc_url( hw_hub_stats_url( array( 'hub' => 0 ) ) ); ?>">← <?php esc_html_e( 'All hubs', 'hostelworld' ); ?></a> · <a href="<?php echo esc_url( get_permalink( $f['hub'] ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View the hub', 'hostelworld' ); ?></a></p>
		<?php endif; ?>

		<form class="hw-report__filters" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
			<input type="hidden" name="post_type" value="page">
			<input type="hidden" name="page" value="hw-hub-report">
			<?php if ( $f['hub'] ) : ?>
				<input type="hidden" name="hub" value="<?php echo (int) $f['hub']; ?>">
			<?php endif; ?>
			<label><?php esc_html_e( 'Period', 'hostelworld' ); ?>
				<select name="days">
					<?php foreach ( array( 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months' ) as $value => $text ) : ?>
						<option value="<?php echo (int) $value; ?>" <?php selected( $f['days'], $value ); ?>><?php echo esc_html( $text ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php if ( ! $f['hub'] ) : ?>
				<label><?php esc_html_e( 'Hubs', 'hostelworld' ); ?>
					<select name="type">
						<?php foreach ( array( '' => 'All hubs', 'continent' => 'Continent hubs', 'country' => 'Country hubs', 'country_states' => 'Country hubs (states)', 'city' => 'City hubs', 'state' => 'State hubs' ) as $value => $text ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $f['type'], $value ); ?>><?php echo esc_html( $text ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<?php submit_button( __( 'Show', 'hostelworld' ), 'secondary', '', false ); ?>
			<a class="button" href="<?php echo esc_url( $csv ); ?>"><?php esc_html_e( 'Download as CSV', 'hostelworld' ); ?></a>
		</form>

		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					'On the page: counted on the hubs since %s, anonymously and as daily totals, without logged-in editors or bots. A section counts as reached when its top passes the middle of the screen.',
					$since ? date_i18n( 'j F Y', strtotime( $since ) ) : '–'
				)
			);
			?>
			<?php if ( $data['gsc_on'] && ! $data['gsc_error'] ) : ?>
				<?php echo esc_html( sprintf( ' From Google: Search Console, through the Content Health connection, %1$s to %2$s (its data is about two days behind). Updated every 12 hours.', date_i18n( 'j M', strtotime( $range['start'] ) ), date_i18n( 'j M Y', strtotime( $range['end'] ) ) ) ); ?>
				<a href="<?php echo esc_url( $refresh ); ?>"><?php esc_html_e( 'Refresh now', 'hostelworld' ); ?></a>
			<?php endif; ?>
		</p>

		<?php if ( ! $data['gsc_on'] ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Google figures (search clicks, impressions, position and searches) appear here when Hostelworld Content Health is active and connected to Search Console.', 'hostelworld' ); ?></p></div>
		<?php elseif ( $data['gsc_error'] ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( 'Search Console figures could not be loaded: ' . $data['gsc_error'] ); ?></p></div>
		<?php endif; ?>

		<?php if ( $data['gsc_on'] && ! $data['gsc_error'] ) : ?>
			<p class="hw-report__group"><?php esc_html_e( 'From Google', 'hostelworld' ); ?></p>
			<div class="hw-report__tiles">
				<?php
				echo $tile( number_format_i18n( $data['g_impr'] ), 'impressions in search' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo $tile( number_format_i18n( $data['g_clicks'] ), 'clicks from search' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo $tile( $data['g_impr'] ? number_format_i18n( 100 * $data['g_clicks'] / $data['g_impr'], 1 ) . '%' : '–', 'click-through rate' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo $tile( $data['g_pos'] ? number_format_i18n( $data['g_pos'], 1 ) : '–', 'average position' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		<?php endif; ?>

		<p class="hw-report__group"><?php esc_html_e( 'On the page', 'hostelworld' ); ?></p>
		<div class="hw-report__tiles">
			<?php
			echo $tile( number_format_i18n( $data['views'] ), 'page views' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo $tile( number_format_i18n( $data['clicks'] ), 'clicks on the page' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo $tile( hw_hub_stats_rate( $data['clicks'], $data['views'] ), 'clicks per 100 views' ); // phpcs:ignore WordPress.Security.EscapeOutput
			if ( ! $f['hub'] ) {
				echo $tile( number_format_i18n( count( array_filter( $data['hubs'], function ( $h ) { return $h['views'] > 0; } ) ) ), 'hubs viewed' ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
		</div>

		<?php
		if ( ! $f['hub'] && function_exists( 'hw_hub_trending_admin_table' ) ) {
			hw_hub_trending_admin_table();
		}
		?>

		<?php if ( $data['views'] || $data['clicks'] ) : ?>
			<h2><?php esc_html_e( 'Sections', 'hostelworld' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Reached: how many views scrolled as far as the section. Clicks per 100 reached: how often people who got there used it. A section many people reach but few click is one to look at.', 'hostelworld' ); ?></p>
			<?php
			$max_reach = 0;
			$max_click = 0;
			foreach ( $data['sections'] as $sec ) {
				$max_reach = max( $max_reach, $sec['reached'] );
				$max_click = max( $max_click, $sec['clicks'] );
			}
			?>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Section', 'hostelworld' ); ?></th>
					<th class="num"><?php esc_html_e( 'Reached', 'hostelworld' ); ?></th>
					<th class="num"><?php esc_html_e( '% of views', 'hostelworld' ); ?></th>
					<th></th>
					<th class="num"><?php esc_html_e( 'Clicks', 'hostelworld' ); ?></th>
					<th class="num"><?php esc_html_e( 'Clicks per 100 reached', 'hostelworld' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
					<?php foreach ( $data['sections'] as $key => $sec ) : ?>
						<tr>
							<td><?php echo esc_html( hw_hub_stats_section_name( $key ) ); ?></td>
							<td class="num"><?php echo esc_html( $sec['reached'] ? number_format_i18n( $sec['reached'] ) : '–' ); ?></td>
							<td class="num"><?php echo esc_html( $sec['reached'] ? hw_hub_stats_rate( $sec['reached'], $data['views'] ) . '%' : '–' ); ?></td>
							<td><?php echo $sec['reached'] ? hw_hub_stats_bar( $sec['reached'], $max_reach ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $sec['clicks'] ) ); ?></td>
							<td class="num"><?php echo esc_html( $sec['reached'] ? hw_hub_stats_rate( $sec['clicks'], $sec['reached'] ) : '–' ); ?></td>
							<td><?php echo hw_hub_stats_bar( $sec['clicks'], $max_click ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing counted on the page in this period yet. On-page totals appear as soon as people visit the hubs.', 'hostelworld' ); ?></p>
		<?php endif; ?>

		<?php if ( $f['hub'] ) : ?>

			<?php if ( $data['links'] ) : ?>
				<h2><?php esc_html_e( 'Most-clicked links', 'hostelworld' ); ?></h2>
				<table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Link', 'hostelworld' ); ?></th>
						<th><?php esc_html_e( 'Section', 'hostelworld' ); ?></th>
						<th class="num"><?php esc_html_e( 'Clicks', 'hostelworld' ); ?></th>
					</tr></thead>
					<tbody>
						<?php foreach ( array_slice( $data['links'], 0, 50 ) as $link ) : ?>
							<tr>
								<td><?php echo esc_html( '' !== $link['label'] ? $link['label'] : '(no label)' ); ?></td>
								<td><?php echo esc_html( hw_hub_stats_section_name( $link['section'] ) ); ?></td>
								<td class="num"><?php echo esc_html( number_format_i18n( $link['clicks'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php if ( $data['gsc_on'] && ! $data['gsc_error'] ) : ?>
				<h2><?php esc_html_e( 'Top searches', 'hostelworld' ); ?></h2>
				<?php if ( is_wp_error( $data['queries'] ) ) : ?>
					<p><?php echo esc_html( $data['queries']->get_error_message() ); ?></p>
				<?php elseif ( ! $data['queries'] ) : ?>
					<p><?php esc_html_e( 'Google has no searches for this hub in the period yet.', 'hostelworld' ); ?></p>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'What people searched for when this hub appeared in Google. Compare it with the sections they use once they arrive.', 'hostelworld' ); ?></p>
					<table class="widefat striped">
						<thead><tr>
							<th><?php esc_html_e( 'Search', 'hostelworld' ); ?></th>
							<th class="num"><?php esc_html_e( 'Clicks', 'hostelworld' ); ?></th>
							<th class="num"><?php esc_html_e( 'Impressions', 'hostelworld' ); ?></th>
							<th class="num"><?php esc_html_e( 'CTR', 'hostelworld' ); ?></th>
							<th class="num"><?php esc_html_e( 'Position', 'hostelworld' ); ?></th>
						</tr></thead>
						<tbody>
							<?php foreach ( $data['queries'] as $q ) : ?>
								<tr>
									<td><?php echo esc_html( $q['query'] ); ?></td>
									<td class="num"><?php echo esc_html( number_format_i18n( $q['clicks'] ) ); ?></td>
									<td class="num"><?php echo esc_html( number_format_i18n( $q['impressions'] ) ); ?></td>
									<td class="num"><?php echo esc_html( $q['impressions'] ? number_format_i18n( 100 * $q['clicks'] / $q['impressions'], 1 ) . '%' : '–' ); ?></td>
									<td class="num"><?php echo esc_html( number_format_i18n( $q['position'], 1 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php endif; ?>

		<?php elseif ( $data['hubs'] ) : ?>

			<?php
			$orders = array( 'views', 'clicks', 'rate', 'google', 'impressions', 'position' );
			$order  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : ( $data['gsc_on'] && ! $data['gsc_error'] ? 'impressions' : 'views' ); // phpcs:ignore WordPress.Security.NonceVerification
			$order  = in_array( $order, $orders, true ) ? $order : 'views';
			$hubs   = $data['hubs'];
			$gsc_ok = $data['gsc_on'] && ! $data['gsc_error'];

			uasort(
				$hubs,
				function ( $a, $b ) use ( $order ) {
					switch ( $order ) {
						case 'rate':
							return ( $b['views'] ? $b['clicks'] / $b['views'] : 0 ) <=> ( $a['views'] ? $a['clicks'] / $a['views'] : 0 );
						case 'google':
							return $b['g']['clicks'] <=> $a['g']['clicks'];
						case 'impressions':
							return $b['g']['impressions'] <=> $a['g']['impressions'];
						case 'position':
							// Best position first; hubs with no position last.
							return ( $a['g']['position'] ? $a['g']['position'] : 999 ) <=> ( $b['g']['position'] ? $b['g']['position'] : 999 );
						default:
							return $b[ $order ] <=> $a[ $order ];
					}
				}
			);
			$sort = function ( $key, $text, $class = '' ) {
				return '<th class="num ' . esc_attr( $class ) . '"><a href="' . esc_url( hw_hub_stats_url( array( 'orderby' => $key ) ) ) . '">' . esc_html( $text ) . '</a></th>';
			};
			?>
			<h2><?php esc_html_e( 'Hubs', 'hostelworld' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Click a hub to see its sections, its most-clicked links and its top searches. Click a column heading to sort.', 'hostelworld' ); ?>
				<?php if ( $gsc_ok ) : ?>
					<?php esc_html_e( 'Two patterns worth a look: plenty of impressions but few clicks from search (the title and description need work), and plenty of visits but few clicks on the page (the page itself needs work).', 'hostelworld' ); ?>
				<?php endif; ?>
			</p>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Hub', 'hostelworld' ); ?></th>
					<th><?php esc_html_e( 'Type', 'hostelworld' ); ?></th>
					<?php
					if ( $gsc_ok ) {
						echo $sort( 'impressions', 'Impressions', 'gsc' ) . $sort( 'google', 'Clicks from search', 'gsc' ) . '<th class="num gsc">CTR</th>' . $sort( 'position', 'Position', 'gsc' ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo $sort( 'views', 'Views' ) . $sort( 'clicks', 'Clicks on the page' ) . $sort( 'rate', 'Clicks per 100 views' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
					<th><?php esc_html_e( 'Most-clicked section', 'hostelworld' ); ?></th>
				</tr></thead>
				<tbody>
					<?php foreach ( array_slice( $hubs, 0, 200, true ) as $id => $h ) : ?>
						<?php
						$top = '';
						if ( $h['by_section'] ) {
							arsort( $h['by_section'] );
							$top = hw_hub_stats_section_name( (string) array_key_first( $h['by_section'] ) );
						}
						?>
						<tr>
							<td><a href="<?php echo esc_url( hw_hub_stats_url( array( 'hub' => $id ) ) ); ?>"><?php echo esc_html( hw_hub_stats_hub_name( $id ) ); ?></a></td>
							<td><?php echo esc_html( hw_hub_stats_type_name( $id ) ); ?></td>
							<?php if ( $gsc_ok ) : ?>
								<td class="num gsc"><?php echo esc_html( number_format_i18n( $h['g']['impressions'] ) ); ?></td>
								<td class="num gsc"><?php echo esc_html( number_format_i18n( $h['g']['clicks'] ) ); ?></td>
								<td class="num gsc"><?php echo esc_html( $h['g']['impressions'] ? number_format_i18n( 100 * $h['g']['clicks'] / $h['g']['impressions'], 1 ) . '%' : '–' ); ?></td>
								<td class="num gsc"><?php echo esc_html( $h['g']['position'] ? number_format_i18n( $h['g']['position'], 1 ) : '–' ); ?></td>
							<?php endif; ?>
							<td class="num"><?php echo esc_html( number_format_i18n( $h['views'] ) ); ?></td>
							<td class="num"><?php echo esc_html( number_format_i18n( $h['clicks'] ) ); ?></td>
							<td class="num"><?php echo esc_html( hw_hub_stats_rate( $h['clicks'], $h['views'] ) ); ?></td>
							<td><?php echo esc_html( $top ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( count( $hubs ) > 200 ) : ?>
				<p class="description"><?php echo esc_html( sprintf( 'Showing the top 200 of %d hubs. The CSV has all of them.', count( $hubs ) ) ); ?></p>
			<?php endif; ?>

		<?php endif; ?>
	</div>
	<?php
}

/**
 * The CSV: hubs and sections for the whole site, or sections and links for
 * one hub, in the period chosen.
 */
function hw_hub_stats_csv() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hub_report_csv' );

	$f    = hw_hub_stats_filters();
	$data = hw_hub_stats_build( $f );
	$gsc  = $data['gsc_on'] && ! $data['gsc_error'];
	$name = 'hub-report-' . ( $f['hub'] ? sanitize_title( get_the_title( $f['hub'] ) ) . '-' : '' ) . $f['from'] . '-to-' . $f['to'] . '.csv';

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $name . '"' );

	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- so Excel reads the accents.

	fputcsv( $out, array( 'Sections', $f['from'] . ' to ' . $f['to'] ), ',', '"', '\\' );
	fputcsv( $out, array( 'Section', 'Reached', '% of views', 'Clicks', 'Clicks per 100 reached' ), ',', '"', '\\' );

	foreach ( $data['sections'] as $key => $s ) {
		fputcsv( $out, array( hw_hub_stats_section_name( $key ), $s['reached'], $s['reached'] ? round( 100 * $s['reached'] / max( 1, $data['views'] ), 1 ) : '', $s['clicks'], $s['reached'] ? round( 100 * $s['clicks'] / $s['reached'], 1 ) : '' ), ',', '"', '\\' );
	}

	fputcsv( $out, array(), ',', '"', '\\' );

	if ( $f['hub'] ) {
		fputcsv( $out, array( 'Link', 'Section', 'Clicks' ), ',', '"', '\\' );

		foreach ( $data['links'] as $link ) {
			fputcsv( $out, array( $link['label'], hw_hub_stats_section_name( $link['section'] ), $link['clicks'] ), ',', '"', '\\' );
		}

		if ( $gsc && is_array( $data['queries'] ) && $data['queries'] ) {
			fputcsv( $out, array(), ',', '"', '\\' );
			fputcsv( $out, array( 'Search', 'Clicks', 'Impressions', 'Position' ), ',', '"', '\\' );

			foreach ( $data['queries'] as $q ) {
				fputcsv( $out, array( $q['query'], $q['clicks'], $q['impressions'], $q['position'] ), ',', '"', '\\' );
			}
		}
	} else {
		$head = array( 'Hub', 'Type', 'URL' );

		if ( $gsc ) {
			$head = array_merge( $head, array( 'Impressions', 'Clicks from search', 'CTR %', 'Position' ) );
		}

		fputcsv( $out, array_merge( $head, array( 'Views', 'Clicks on the page', 'Clicks per 100 views' ) ), ',', '"', '\\' );

		foreach ( $data['hubs'] as $id => $h ) {
			$line = array( hw_hub_stats_hub_name( $id ), hw_hub_stats_type_name( $id ), get_permalink( $id ) );

			if ( $gsc ) {
				$line = array_merge( $line, array( $h['g']['impressions'], $h['g']['clicks'], $h['g']['impressions'] ? round( 100 * $h['g']['clicks'] / $h['g']['impressions'], 1 ) : '', $h['g']['position'] ? $h['g']['position'] : '' ) );
			}

			fputcsv( $out, array_merge( $line, array( $h['views'], $h['clicks'], $h['views'] ? round( 100 * $h['clicks'] / $h['views'], 1 ) : '' ) ), ',', '"', '\\' );
		}
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_hw_hub_report_csv', 'hw_hub_stats_csv' );
