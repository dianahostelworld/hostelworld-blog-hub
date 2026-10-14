<?php
/**
 * Trending this week.
 *
 * The country, state and city hubs whose views are rising fastest, from the
 * Hub report's own counts: views in the last 7 days against that hub's usual
 * week (its average over the 4 weeks before). A hub has to reach a minimum
 * number of views this week, so one or two visits to a quiet hub can't put it
 * at the top. If too few hubs are rising, the list is topped up with the most
 * viewed hubs of the week, so it never looks empty.
 *
 * Shown in three places, each with the same cards:
 * - the homepage (Blog Homepage plugin, its own section in Homepage content);
 * - near the bottom of every hub, without the hub you're on;
 * - anywhere else with [hw_trending].
 *
 * Readers only ever see the destinations, never any numbers. The numbers are
 * in Pages > Hub report.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The hub templates that can trend: countries, states and cities.
 *
 * @return array
 */
function hw_hub_trending_templates() {
	return array( HW_HUB_COUNTRY_TEMPLATE_KEY, HW_HUB_COUNTRY_STATES_TEMPLATE_KEY, HW_HUB_STATE_TEMPLATE_KEY, HW_HUB_CITY_TEMPLATE_KEY );
}

/**
 * Wording for readers, translatable under Languages > String translations.
 *
 * @return array
 */
function hw_hub_trending_strings() {
	return array(
		'Trending this week',
		'Also trending this week',
		'The destinations travellers are reading about right now.',
		'Trending',
	);
}

/**
 * Registers the wording with Polylang.
 */
function hw_hub_trending_register_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	foreach ( hw_hub_trending_strings() as $string ) {
		pll_register_string( $string, $string, 'Hostelworld hubs' );
	}
}
add_action( 'init', 'hw_hub_trending_register_strings' );

/**
 * Every hub with views in the last five weeks, ranked, with its numbers.
 * Kept for an hour.
 *
 * @return array List of [ id, week, usual, change, rising ].
 */
function hw_hub_trending_ranked() {
	$cached = get_transient( 'hw_hub_trending' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	if ( ! function_exists( 'hw_hub_stats_table' ) ) {
		return array();
	}

	$table = hw_hub_stats_table();

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return array();
	}

	$today      = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	$week_start = gmdate( 'Y-m-d', $today - 6 * DAY_IN_SECONDS );
	$base_start = gmdate( 'Y-m-d', $today - 34 * DAY_IN_SECONDS );

	// "Usual week" is worked out from however much of the 4 weeks before was
	// actually counted, so a newly installed report doesn't skew it.
	$since     = get_option( 'hw_hub_stats_since' );
	$base_from = ( $since && $since > $base_start ) ? $since : $base_start;
	$base_days = max( 0, (int) round( ( strtotime( $week_start ) - strtotime( $base_from ) ) / DAY_IN_SECONDS ) );

	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT post_id, SUM(CASE WHEN day >= %s THEN hits ELSE 0 END) AS week, SUM(CASE WHEN day < %s THEN hits ELSE 0 END) AS base FROM {$table} WHERE kind = 'v' AND day >= %s GROUP BY post_id", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$week_start,
			$week_start,
			$base_start
		),
		ARRAY_A
	);

	/**
	 * The fewest views in the last 7 days for a hub to count as trending.
	 *
	 * @param int $min Default 10.
	 */
	$min = (int) apply_filters( 'hw_hub_trending_min_views', 10 );

	// Evens out small numbers: 2 views going to 6 is not a trend, 200 to 600 is.
	$k = 20;

	$templates = hw_hub_trending_templates();
	$list      = array();

	foreach ( (array) $rows as $row ) {
		$id   = (int) $row['post_id'];
		$week = (int) $row['week'];

		if ( $week < $min || 'publish' !== get_post_status( $id ) || ! in_array( get_page_template_slug( $id ), $templates, true ) ) {
			continue;
		}

		$usual = $base_days > 0 ? (int) $row['base'] / $base_days * 7 : 0;

		$list[] = array(
			'id'     => $id,
			'week'   => $week,
			'usual'  => round( $usual, 1 ),
			'change' => $usual > 0 ? round( ( $week - $usual ) / $usual * 100 ) : null,
			'score'  => ( $week + $k ) / ( $usual + $k ),
		);
	}

	// Rising first, by how fast; then the rest, by views.
	usort(
		$list,
		function ( $a, $b ) {
			$a_up = $a['score'] > 1.15;
			$b_up = $b['score'] > 1.15;

			if ( $a_up !== $b_up ) {
				return $a_up ? -1 : 1;
			}

			if ( $a_up && $a['score'] !== $b['score'] ) {
				return $b['score'] <=> $a['score'];
			}

			return $b['week'] <=> $a['week'];
		}
	);

	foreach ( $list as $i => $item ) {
		$list[ $i ]['rising'] = $item['score'] > 1.15;
	}

	set_transient( 'hw_hub_trending', $list, HOUR_IN_SECONDS );

	return $list;
}

/**
 * The trending hubs to show readers.
 *
 * @param int $count   How many.
 * @param int $exclude A hub to leave out (the one being read).
 * @return array Post IDs.
 */
function hw_hub_trending_ids( $count = 6, $exclude = 0 ) {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language() : '';
	$ids  = array();

	foreach ( hw_hub_trending_ranked() as $item ) {
		if ( (int) $exclude === $item['id'] ) {
			continue;
		}

		// Only hubs in the language of the page being read.
		if ( $lang && function_exists( 'pll_get_post_language' ) && pll_get_post_language( $item['id'] ) !== $lang ) {
			continue;
		}

		$ids[] = $item['id'];

		if ( count( $ids ) >= $count ) {
			break;
		}
	}

	return $ids;
}

/**
 * A hub's name for the card: its page title.
 *
 * @param int $id Page ID.
 * @return string
 */
function hw_hub_trending_name( $id ) {
	return function_exists( 'hw_hub_page_title' ) ? hw_hub_page_title( $id ) : get_the_title( $id );
}

/**
 * A hub's main photo, as an image tag. Falls back to the featured image.
 *
 * @param int    $id   Page ID.
 * @param string $name Alt text.
 * @return string
 */
function hw_hub_trending_image( $id, $name ) {
	$image = hw_hub_value( 'hero_image', $id );
	$image = is_string( $image ) ? trim( $image ) : $image;

	if ( is_numeric( $image ) && (int) $image ) {
		$html = wp_get_attachment_image( (int) $image, 'medium_large', false, array( 'alt' => $name, 'loading' => 'lazy' ) );

		if ( $html ) {
			return $html;
		}
	}

	if ( is_string( $image ) && '' !== $image && 0 !== strpos( $image, 'illustration:' ) ) {
		return '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $name ) . '" loading="lazy">';
	}

	$thumb = get_post_thumbnail_id( $id );

	return $thumb ? wp_get_attachment_image( $thumb, 'medium_large', false, array( 'alt' => $name, 'loading' => 'lazy' ) ) : '';
}

/**
 * Registers the stylesheet, and loads it on hubs and on pages using the shortcode.
 */
function hw_hub_trending_assets() {
	wp_register_style( 'hw-trending', HW_HUB_URL . 'assets/trending.css', array(), HW_HUB_VERSION );

	if ( ! is_singular() ) {
		return;
	}

	$post = get_queried_object();

	if ( in_array( get_page_template_slug( $post ), hw_hub_template_keys(), true ) || ( $post && has_shortcode( (string) $post->post_content, 'hw_trending' ) ) ) {
		wp_enqueue_style( 'hw-trending' );
	}
}
add_action( 'wp_enqueue_scripts', 'hw_hub_trending_assets' );

/**
 * The "Trending this week" section.
 *
 * @param array $args {
 *     @type string $title   Heading. Default "Trending this week".
 *     @type string $intro   Line under the heading. Empty for none.
 *     @type int    $count   How many destinations. Default 6.
 *     @type int    $exclude A hub to leave out.
 *     @type string $class   Extra class for the section.
 * }
 * @return string Markup, or nothing when there is nothing to show.
 */
function hw_hub_trending_html( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'   => hw_hub_t( 'Trending this week' ),
			'intro'   => hw_hub_t( 'The destinations travellers are reading about right now.' ),
			'count'   => 6,
			'exclude' => 0,
			'class'   => '',
		)
	);

	$count = max( 1, min( 12, (int) $args['count'] ) );
	$ids   = hw_hub_trending_ids( $count, (int) $args['exclude'] );

	if ( ! $ids ) {
		return '';
	}

	wp_enqueue_style( 'hw-trending' );

	ob_start();
	?>
	<section id="trending" class="hw-trend <?php echo esc_attr( $args['class'] ); ?>">
		<div class="hw-trend__wrap">
			<?php if ( '' !== trim( (string) $args['title'] ) ) : ?>
				<h2 class="hw-trend__title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== trim( (string) $args['intro'] ) ) : ?>
				<p class="hw-trend__intro"><?php echo esc_html( $args['intro'] ); ?></p>
			<?php endif; ?>

			<ol class="hw-trend__list">
				<?php foreach ( $ids as $i => $id ) : ?>
					<?php
					$name   = hw_hub_trending_name( $id );
					$parent = wp_get_post_parent_id( $id );
					$where  = $parent ? hw_hub_trending_name( $parent ) : '';
					$image  = hw_hub_trending_image( $id, $name );
					?>
					<li class="hw-trend__item">
						<a class="hw-trend__card" href="<?php echo esc_url( get_permalink( $id ) ); ?>" data-hw-track="trending" data-hw-track-label="<?php echo esc_attr( $name ); ?>">
							<span class="hw-trend__img<?php echo $image ? '' : ' hw-trend__img--none'; ?>">
								<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput -- built by WordPress or escaped above. ?>
								<span class="hw-trend__rank"><?php echo esc_html( number_format_i18n( $i + 1 ) ); ?></span>
							</span>
							<span class="hw-trend__body">
								<span class="hw-trend__name"><?php echo esc_html( $name ); ?></span>
								<?php if ( $where ) : ?>
									<span class="hw-trend__where"><?php echo esc_html( $where ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Prints the section. Used by the hub templates and the homepage.
 *
 * @param array $args See hw_hub_trending_html().
 */
function hw_hub_trending_render( $args = array() ) {
	echo hw_hub_trending_html( $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside.
}

/**
 * [hw_trending count="6" title="…" intro="…"]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function hw_hub_trending_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'count' => 6,
			'title' => null,
			'intro' => null,
		),
		$atts,
		'hw_trending'
	);

	$args = array(
		'count'   => $atts['count'],
		'exclude' => is_singular() ? get_queried_object_id() : 0,
		'class'   => 'hw-trend--inline',
	);

	if ( null !== $atts['title'] ) {
		$args['title'] = $atts['title'];
	}

	if ( null !== $atts['intro'] ) {
		$args['intro'] = $atts['intro'];
	}

	return hw_hub_trending_html( $args );
}
add_shortcode( 'hw_trending', 'hw_hub_trending_shortcode' );

/**
 * The strip near the bottom of every hub, before the closing call to action.
 */
function hw_hub_trending_strip() {
	hw_hub_trending_render(
		array(
			'title'   => hw_hub_t( 'Also trending this week' ),
			'intro'   => '',
			'count'   => 4,
			'exclude' => get_the_ID(),
			'class'   => 'hw-trend--hub',
		)
	);
}

/**
 * The trending table in Pages > Hub report, with the numbers behind it.
 */
function hw_hub_trending_admin_table() {
	$list = array_slice( hw_hub_trending_ranked(), 0, 15 );
	$pll  = function_exists( 'pll_get_post_language' );
	?>
	<h2><?php esc_html_e( 'Trending this week', 'hostelworld' ); ?></h2>
	<p class="description"><?php esc_html_e( 'What readers see in "Trending this week" (homepage, bottom of each hub and [hw_trending]), in order, for every language. Rising hubs come first, by how much their views in the last 7 days beat their usual week (the average of the 4 weeks before). A hub needs at least 10 views this week to count. The list is worked out again every hour.', 'hostelworld' ); ?></p>
	<?php if ( ! $list ) : ?>
		<p><?php esc_html_e( 'Nothing yet: no country, state or city hub has had enough views in the last 7 days.', 'hostelworld' ); ?></p>
		<?php
		return;
	endif;
	?>
	<table class="widefat striped" style="max-width:900px">
		<thead><tr>
			<th>#</th>
			<th><?php esc_html_e( 'Hub', 'hostelworld' ); ?></th>
			<?php if ( $pll ) : ?>
				<th><?php esc_html_e( 'Language', 'hostelworld' ); ?></th>
			<?php endif; ?>
			<th class="num"><?php esc_html_e( 'Views, last 7 days', 'hostelworld' ); ?></th>
			<th class="num"><?php esc_html_e( 'Usual week', 'hostelworld' ); ?></th>
			<th class="num"><?php esc_html_e( 'Change', 'hostelworld' ); ?></th>
			<th><?php esc_html_e( 'Why it\'s here', 'hostelworld' ); ?></th>
		</tr></thead>
		<tbody>
			<?php foreach ( $list as $i => $item ) : ?>
				<tr>
					<td><?php echo esc_html( $i + 1 ); ?></td>
					<td><a href="<?php echo esc_url( get_permalink( $item['id'] ) ); ?>"><?php echo esc_html( hw_hub_trending_name( $item['id'] ) ); ?></a></td>
					<?php if ( $pll ) : ?>
						<td><?php echo esc_html( strtoupper( (string) pll_get_post_language( $item['id'] ) ) ); ?></td>
					<?php endif; ?>
					<td class="num"><?php echo esc_html( number_format_i18n( $item['week'] ) ); ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $item['usual'] ) ); ?></td>
					<td class="num"><?php echo null === $item['change'] ? esc_html__( 'new', 'hostelworld' ) : esc_html( ( $item['change'] > 0 ? '+' : '' ) . number_format_i18n( $item['change'] ) . '%' ); ?></td>
					<td><?php echo $item['rising'] ? esc_html__( 'Rising', 'hostelworld' ) : esc_html__( 'Most viewed (filling in)', 'hostelworld' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}
