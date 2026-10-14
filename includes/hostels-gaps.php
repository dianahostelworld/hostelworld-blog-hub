<?php
/**
 * Live hostel boxes: which articles don't have one yet.
 *
 * Posts > Live hostel boxes lists every published article without the live
 * hostel cards ([hw_hostels]), with the ones that still list hostels by hand
 * first: articles linking to Hostelworld property pages, or with "hostel" in
 * a heading. Those are the ones to swap over.
 *
 * Each row has the article's city worked out from its tags (via the city
 * hubs), and a ready-made tag to copy, or open the article and use the
 * "Add hostels" button. Articles that can't have a box (nothing on
 * Hostelworld matches them) can be dismissed with one click.
 *
 * The scan reads every article once, in batches with a progress bar, and the
 * results are saved. An article is checked again whenever it is saved, so one
 * that gets a box drops off the list straight away.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HW_HOSTELS_GAPS_OPTION', 'hw_hostels_gaps' );
define( 'HW_HOSTELS_SKIP_META', 'hw_hostels_skip' );

/**
 * What the scan finds in one article.
 *
 * @param string $content Article HTML.
 * @return array has (bool), links (int), heads (int)
 */
function hw_hostels_gap_scan( $content ) {
	$content = (string) $content;

	if ( false !== stripos( $content, '[hw_hostels' ) ) {
		return array(
			'has'   => true,
			'links' => 0,
			'heads' => 0,
		);
	}

	// Links to Hostelworld property pages, old and new address styles,
	// counted once per property.
	$ids = array();

	if ( preg_match_all( '#hostelworld\.com/(?:[a-z]{2}/)?(?:pwa/)?(?:hosteldetails\.php/[^"\'\s<>]*?/(\d+)|(?:st/)?hostels/p/(\d+))#i', $content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $match ) {
			$id = ! empty( $match[1] ) ? $match[1] : ( isset( $match[2] ) ? $match[2] : '' );

			if ( '' !== $id ) {
				$ids[ $id ] = true;
			}
		}
	}

	$heads = preg_match_all( '#<h[2-4][^>]*>(?:(?!</h[2-4]>).)*hostel#isu', $content );

	return array(
		'has'   => false,
		'links' => count( $ids ),
		'heads' => (int) $heads,
	);
}

/**
 * Saved results.
 *
 * @return array { posts: id => row, with: int, built: time }
 */
function hw_hostels_gaps_results() {
	$saved = get_option( HW_HOSTELS_GAPS_OPTION, array() );

	return ( is_array( $saved ) && isset( $saved['posts'] ) ) ? $saved : array(
		'posts' => array(),
		'with'  => array(),
		'built' => 0,
	);
}

/**
 * The article's language, if Polylang is on.
 *
 * @param array $ids Post IDs.
 * @return array id => slug
 */
function hw_hostels_gaps_langs( $ids ) {
	if ( ! $ids || ! taxonomy_exists( 'language' ) ) {
		return array();
	}

	$terms = wp_get_object_terms( $ids, 'language', array( 'fields' => 'all_with_object_id' ) );
	$out   = array();

	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$out[ (int) $term->object_id ] = $term->slug;
		}
	}

	return $out;
}

/**
 * Whether an article counts as English: tagged English in Polylang (en, en-gb
 * and so on), or with no language set at all, which is how English articles
 * written before Polylang arrived are stored.
 *
 * @param string $lang Language slug.
 * @return bool
 */
function hw_hostels_gaps_is_english( $lang ) {
	return '' === $lang || 0 === strpos( strtolower( $lang ), 'en' );
}

/**
 * One batch of the scan, called repeatedly by the screen.
 */
function hw_hostels_gaps_ajax() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( 'You do not have permission to do this.', 403 );
	}

	check_ajax_referer( 'hw_hostels_gaps', 'nonce' );

	global $wpdb;

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$size   = 300;
	$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT ID, post_title, post_date, post_content FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID ASC LIMIT %d, %d",
			$offset,
			$size
		)
	);

	$results = 0 === $offset ? array(
		'posts' => array(),
		'with'  => array(),
		'built' => 0,
	) : hw_hostels_gaps_results();

	$langs = hw_hostels_gaps_langs( wp_list_pluck( $rows, 'ID' ) );

	foreach ( $rows as $row ) {
		$id   = (int) $row->ID;
		$lang = isset( $langs[ $id ] ) ? $langs[ $id ] : '';

		// English articles only.
		if ( ! hw_hostels_gaps_is_english( $lang ) ) {
			continue;
		}

		$scan = hw_hostels_gap_scan( $row->post_content );

		if ( $scan['has'] ) {
			$results['with'][ $lang ] = ( isset( $results['with'][ $lang ] ) ? $results['with'][ $lang ] : 0 ) + 1;
			continue;
		}

		$results['posts'][ $id ] = array(
			'title' => html_entity_decode( $row->post_title, ENT_QUOTES, 'UTF-8' ),
			'date'  => substr( $row->post_date, 0, 10 ),
			'lang'  => $lang,
			'links' => $scan['links'],
			'heads' => $scan['heads'],
		);
	}

	$next = $offset + count( $rows );
	$done = ! $rows || $next >= $total;

	if ( $done ) {
		$results['built'] = time();
	}

	update_option( HW_HOSTELS_GAPS_OPTION, $results, false );

	wp_send_json_success(
		array(
			'next'  => $next,
			'total' => $total,
			'done'  => $done,
		)
	);
}
add_action( 'wp_ajax_hw_hostels_gaps', 'hw_hostels_gaps_ajax' );

/**
 * Keeps the list current when an article is saved: one that now has a box,
 * or was unpublished, drops off; one that lost its box comes back.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function hw_hostels_gaps_on_save( $post_id, $post ) {
	if ( 'post' !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$results = hw_hostels_gaps_results();

	if ( ! $results['built'] ) {
		return;
	}

	$was  = isset( $results['posts'][ $post_id ] );
	$scan = hw_hostels_gap_scan( $post->post_content );
	$lang = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post_id, 'slug' ) : '';

	unset( $results['posts'][ $post_id ] );

	if ( ! hw_hostels_gaps_is_english( $lang ) ) {
		update_option( HW_HOSTELS_GAPS_OPTION, $results, false );
		return;
	}

	if ( 'publish' === $post->post_status && ! $scan['has'] ) {
		$results['posts'][ $post_id ] = array(
			'title' => html_entity_decode( $post->post_title, ENT_QUOTES, 'UTF-8' ),
			'date'  => substr( $post->post_date, 0, 10 ),
			'lang'  => $lang,
			'links' => $scan['links'],
			'heads' => $scan['heads'],
		);
	} elseif ( $was && 'publish' === $post->post_status ) {
		$results['with'][ $lang ] = ( isset( $results['with'][ $lang ] ) ? $results['with'][ $lang ] : 0 ) + 1;
	}

	update_option( HW_HOSTELS_GAPS_OPTION, $results, false );
}
add_action( 'save_post', 'hw_hostels_gaps_on_save', 99, 2 );

/**
 * Tag slug => city and country, read once from the city hubs. Used to fill in
 * the ready-made tag for each article.
 *
 * @return array
 */
function hw_hostels_gaps_place_map() {
	$cached = get_transient( 'hw_hostels_place_map' );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$map  = array();
	$args = array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'private', 'draft' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => HW_HUB_CITY_TEMPLATE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
	);

	if ( function_exists( 'pll_languages_list' ) ) {
		$args['lang'] = '';
	}

	foreach ( get_posts( $args ) as $hub_id ) {
		$city    = (string) get_post_meta( $hub_id, hw_hub_meta_key( 'city_slug', $hub_id ), true );
		$country = (string) get_post_meta( $hub_id, hw_hub_meta_key( 'country_slug', $hub_id ), true );

		if ( '' === $city || '' === $country ) {
			continue;
		}

		$place = array(
			'city'    => $city,
			'country' => $country,
		);
		$term  = sanitize_title( (string) get_post_meta( $hub_id, hw_hub_meta_key( 'hub_term', $hub_id ), true ) );

		foreach ( array_filter( array( $term, get_post_field( 'post_name', $hub_id ), $city ) ) as $slug ) {
			if ( ! isset( $map[ $slug ] ) ) {
				$map[ $slug ] = $place;
			}
		}
	}

	set_transient( 'hw_hostels_place_map', $map, 12 * HOUR_IN_SECONDS );

	return $map;
}

/**
 * The cities an article is tagged with, as hostel tags.
 *
 * @param int   $post_id Post ID.
 * @param array $map     From hw_hostels_gaps_place_map().
 * @return array Places.
 */
function hw_hostels_gaps_places( $post_id, $map ) {
	$places = array();

	foreach ( wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'slugs' ) ) as $slug ) {
		if ( isset( $map[ $slug ] ) ) {
			$key            = $map[ $slug ]['city'] . '|' . $map[ $slug ]['country'];
			$places[ $key ] = $map[ $slug ];
		}
	}

	return array_values( $places );
}

/**
 * Dismiss or restore an article (the button's fallback when scripts are off).
 */
function hw_hostels_gaps_toggle() {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

	check_admin_referer( 'hw_hostels_skip_' . $id );

	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	if ( get_post_meta( $id, HW_HOSTELS_SKIP_META, true ) ) {
		delete_post_meta( $id, HW_HOSTELS_SKIP_META );
	} else {
		update_post_meta( $id, HW_HOSTELS_SKIP_META, '1' );
	}

	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?page=hw-hostels-gaps' ) );
	exit;
}
add_action( 'admin_post_hw_hostels_skip', 'hw_hostels_gaps_toggle' );

/**
 * The Dismiss / Restore button, without reloading the page.
 */
function hw_hostels_gaps_toggle_ajax() {
	$id = isset( $_POST['post'] ) ? absint( $_POST['post'] ) : 0;

	check_ajax_referer( 'hw_hostels_skip_' . $id, 'nonce' );

	if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error( 'You do not have permission to do this.', 403 );
	}

	if ( get_post_meta( $id, HW_HOSTELS_SKIP_META, true ) ) {
		delete_post_meta( $id, HW_HOSTELS_SKIP_META );
		wp_send_json_success( array( 'dismissed' => false ) );
	}

	update_post_meta( $id, HW_HOSTELS_SKIP_META, '1' );
	wp_send_json_success( array( 'dismissed' => true ) );
}
add_action( 'wp_ajax_hw_hostels_skip', 'hw_hostels_gaps_toggle_ajax' );

/**
 * Posts > Live hostel boxes.
 */
function hw_hostels_gaps_menu() {
	add_posts_page( __( 'Live hostel boxes', 'hostelworld' ), __( 'Live hostel boxes', 'hostelworld' ), 'edit_others_posts', 'hw-hostels-gaps', 'hw_hostels_gaps_render' );
}
add_action( 'admin_menu', 'hw_hostels_gaps_menu' );

/**
 * Filters from the address bar.
 *
 * @return array
 */
function hw_hostels_gaps_filters() {
	// phpcs:disable WordPress.Security.NonceVerification
	$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'manual';
	$f    = array(
		'view' => in_array( $view, array( 'manual', 'all', 'hidden' ), true ) ? $view : 'manual',
		'lang' => '',
		'tag'  => isset( $_GET['tag_filter'] ) ? sanitize_title( wp_unslash( $_GET['tag_filter'] ) ) : '',
		'q'    => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
	);
	// phpcs:enable

	return $f;
}

/**
 * The rows for the current filters, hostel links first.
 *
 * @param array $results Saved results.
 * @param array $f       Filters.
 * @return array { rows, counts }
 */
function hw_hostels_gaps_filtered( $results, $f ) {
	global $wpdb;

	$hidden = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = '1'", HW_HOSTELS_SKIP_META ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$hidden = array_flip( $hidden );

	$in_tag = null;

	if ( $f['tag'] ) {
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tag'            => $f['tag'],
			'no_found_rows'  => true,
		);

		if ( function_exists( 'pll_languages_list' ) ) {
			$args['lang'] = '';
		}

		$in_tag = array_flip( array_map( 'intval', get_posts( $args ) ) );
	}

	$rows   = array();
	$counts = array(
		'manual' => 0,
		'all'    => 0,
		'hidden' => 0,
	);

	foreach ( $results['posts'] as $id => $row ) {
		// English only, including lists saved before the scan skipped the rest.
		if ( ! hw_hostels_gaps_is_english( $row['lang'] ) ) {
			continue;
		}

		if ( null !== $in_tag && ! isset( $in_tag[ $id ] ) ) {
			continue;
		}

		if ( '' !== $f['q'] && false === stripos( $row['title'], $f['q'] ) ) {
			continue;
		}

		$is_hidden = isset( $hidden[ $id ] );
		$manual    = $row['links'] || $row['heads'];

		if ( $is_hidden ) {
			$counts['hidden']++;
		} else {
			$counts['all']++;
			if ( $manual ) {
				$counts['manual']++;
			}
		}

		if ( ( 'hidden' === $f['view'] ) !== $is_hidden ) {
			continue;
		}

		if ( 'manual' === $f['view'] && ! $manual ) {
			continue;
		}

		$rows[ $id ] = $row;
	}

	uasort(
		$rows,
		function ( $a, $b ) {
			if ( $a['links'] !== $b['links'] ) {
				return $b['links'] <=> $a['links'];
			}
			if ( $a['heads'] !== $b['heads'] ) {
				return $b['heads'] <=> $a['heads'];
			}
			return strcmp( $b['date'], $a['date'] );
		}
	);

	return array(
		'rows'   => $rows,
		'counts' => $counts,
	);
}

/**
 * The screen.
 */
function hw_hostels_gaps_render() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	$results = hw_hostels_gaps_results();
	$f       = hw_hostels_gaps_filters();
	$data    = hw_hostels_gaps_filtered( $results, $f );
	$rows    = $data['rows'];
	$counts  = $data['counts'];
	$with    = 0;

	foreach ( $results['with'] as $lang => $n ) {
		if ( hw_hostels_gaps_is_english( (string) $lang ) ) {
			$with += (int) $n;
		}
	}

	$csv      = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'hw_hostels_gaps_csv' ), array_filter( array( 'view' => $f['view'], 'tag_filter' => $f['tag'], 'q' => $f['q'] ) ) ), admin_url( 'admin-post.php' ) ), 'hw_hostels_gaps_csv' );
	$per_page = 50;
	$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
	$pages    = max( 1, (int) ceil( count( $rows ) / $per_page ) );
	$paged    = min( $paged, $pages );
	$shown    = array_slice( $rows, ( $paged - 1 ) * $per_page, $per_page, true );
	$map      = $shown ? hw_hostels_gaps_place_map() : array();
	$tab      = function ( $view, $label, $count ) use ( $f ) {
		return '<a class="nav-tab ' . ( $f['view'] === $view ? 'nav-tab-active' : '' ) . '" href="' . esc_url( add_query_arg( array( 'view' => $view, 'paged' => false ) ) ) . '">' . esc_html( $label ) . ' <span class="count">(' . esc_html( number_format_i18n( $count ) ) . ')</span></a>';
	};
	?>
	<div class="wrap hw-gaps">
		<style>
			.hw-gaps__bar, .hw-gaps__filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 14px 0; }
			.hw-gaps__progress { display: none; align-items: center; gap: 10px; }
			.hw-gaps__progress.is-on { display: inline-flex; }
			.hw-gaps__meter { width: 220px; height: 8px; background: #f0f0f1; border-radius: 4px; overflow: hidden; }
			.hw-gaps__meter span { display: block; height: 100%; width: 0; background: #F25621; transition: width .2s; }
			.hw-gaps__tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin: 12px 0 16px; }
			.hw-gaps__tile { background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 14px 16px; }
			.hw-gaps__tile strong { display: block; font-size: 26px; line-height: 1.1; margin-bottom: 4px; }
			.hw-gaps__tile span { color: #646970; }
			.hw-gaps td { vertical-align: top; }
			.hw-gaps td.num, .hw-gaps th.num { text-align: center; white-space: nowrap; }
			.hw-gaps__code { display: inline-block; font-family: monospace; font-size: 12px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 2px 6px; margin: 0 6px 4px 0; }
			.hw-gaps .row-actions { position: static; color: #646970; }
			.hw-gaps tr.is-gone { opacity: .35; }
			.hw-gaps td.hw-gaps__act { text-align: right; }
			.hw-gaps .description { max-width: 900px; }
		</style>

		<h1><?php esc_html_e( 'Live hostel boxes', 'hostelworld' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Published English articles that don\'t have the live hostel cards yet. The first tab lists the ones that still mention hostels by hand (links to Hostelworld property pages, or "hostel" in a heading), with the most hostel links first. Open one, use "Add hostels" in the editor, or paste the ready-made tag, then save: the article leaves this list. If an article can\'t have live hostels, click Dismiss: it moves to the Dismissed tab, where you can restore it.', 'hostelworld' ); ?></p>

		<div class="hw-gaps__bar">
			<button type="button" class="button button-primary" id="hw-gaps-run" data-nonce="<?php echo esc_attr( wp_create_nonce( 'hw_hostels_gaps' ) ); ?>"><?php echo esc_html( $results['built'] ? __( 'Scan all articles again', 'hostelworld' ) : __( 'Scan all articles', 'hostelworld' ) ); ?></button>
			<span class="hw-gaps__progress" id="hw-gaps-progress"><span class="hw-gaps__meter"><span></span></span><span class="hw-gaps__count"></span></span>
			<?php if ( $results['built'] ) : ?>
				<span class="description">
					<?php
					printf(
						/* translators: %s: date */
						esc_html__( 'Last full scan: %s. Saved articles update on their own.', 'hostelworld' ),
						esc_html( wp_date( 'j M Y, H:i', $results['built'] ) )
					);
					?>
				</span>
				<a class="button" href="<?php echo esc_url( $csv ); ?>"><?php esc_html_e( 'Download as CSV', 'hostelworld' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( ! $results['built'] ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Click "Scan all articles" to build the list. It reads every published article once and takes a moment.', 'hostelworld' ); ?></p></div>
		<?php else : ?>

			<div class="hw-gaps__tiles">
				<div class="hw-gaps__tile"><strong><?php echo esc_html( number_format_i18n( $with ) ); ?></strong><span><?php esc_html_e( 'Articles with live hostels', 'hostelworld' ); ?></span></div>
				<div class="hw-gaps__tile"><strong><?php echo esc_html( number_format_i18n( $counts['all'] ) ); ?></strong><span><?php esc_html_e( 'Without them', 'hostelworld' ); ?></span></div>
				<div class="hw-gaps__tile"><strong><?php echo esc_html( number_format_i18n( $counts['manual'] ) ); ?></strong><span><?php esc_html_e( 'Still listing hostels by hand', 'hostelworld' ); ?></span></div>
			</div>

			<form class="hw-gaps__filters" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
				<input type="hidden" name="page" value="hw-hostels-gaps">
				<input type="hidden" name="view" value="<?php echo esc_attr( $f['view'] ); ?>">
				<input type="text" name="tag_filter" value="<?php echo esc_attr( $f['tag'] ); ?>" placeholder="<?php esc_attr_e( 'Tag, e.g. lisbon', 'hostelworld' ); ?>" style="width:150px">
				<input type="search" name="q" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="<?php esc_attr_e( 'Title contains…', 'hostelworld' ); ?>">
				<?php submit_button( __( 'Filter', 'hostelworld' ), 'secondary', '', false ); ?>
			</form>

			<h2 class="nav-tab-wrapper">
				<?php
				echo $tab( 'manual', __( 'Listing hostels by hand', 'hostelworld' ), $counts['manual'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo $tab( 'all', __( 'All without live hostels', 'hostelworld' ), $counts['all'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo $tab( 'hidden', __( 'Dismissed', 'hostelworld' ), $counts['hidden'] ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</h2>

			<table class="widefat striped" style="margin-top:12px">
				<thead>
					<tr>
						<th style="width:38%"><?php esc_html_e( 'Article', 'hostelworld' ); ?></th>
						<th class="num"><?php esc_html_e( 'Hostel links', 'hostelworld' ); ?></th>
						<th class="num"><?php esc_html_e( 'Hostel headings', 'hostelworld' ); ?></th>
						<th><?php esc_html_e( 'Ready-made tag', 'hostelworld' ); ?></th>
						<th style="width:110px"></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $shown ) : ?>
						<tr><td colspan="5"><?php esc_html_e( 'Nothing here.', 'hostelworld' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $shown as $id => $row ) : ?>
						<?php
						$places = hw_hostels_gaps_places( $id, $map );
						$toggle = wp_nonce_url( admin_url( 'admin-post.php?action=hw_hostels_skip&post=' . $id ), 'hw_hostels_skip_' . $id );
						?>
						<tr>
							<td>
								<strong><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a></strong>
								<div class="row-actions">
									<?php echo esc_html( wp_date( 'j M Y', strtotime( $row['date'] ) ) ); ?>
									· <a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php esc_html_e( 'Edit', 'hostelworld' ); ?></a>
									· <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'hostelworld' ); ?></a>
								</div>
							</td>
							<td class="num"><?php echo $row['links'] ? (int) $row['links'] : '–'; ?></td>
							<td class="num"><?php echo $row['heads'] ? (int) $row['heads'] : '–'; ?></td>
							<td>
								<?php if ( $places ) : ?>
									<?php foreach ( $places as $place ) : ?>
										<?php $code = sprintf( '[hw_hostels city="%s" country="%s" count="4"]', $place['city'], $place['country'] ); ?>
										<span class="hw-gaps__code"><?php echo esc_html( $code ); ?></span><button type="button" class="button button-small hw-gaps__copy" data-code="<?php echo esc_attr( $code ); ?>"><?php esc_html_e( 'Copy', 'hostelworld' ); ?></button><br>
									<?php endforeach; ?>
								<?php else : ?>
									<span class="description"><?php esc_html_e( 'No city hub tag on this article: pick the city in "Add hostels".', 'hostelworld' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="hw-gaps__act">
								<a class="button button-small hw-gaps__dismiss" href="<?php echo esc_url( $toggle ); ?>" data-post="<?php echo (int) $id; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'hw_hostels_skip_' . $id ) ); ?>"><?php echo esc_html( 'hidden' === $f['view'] ? __( 'Restore', 'hostelworld' ) : __( 'Dismiss', 'hostelworld' ) ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $paged,
								'total'   => $pages,
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<script>
	( function () {
		var button   = document.getElementById( 'hw-gaps-run' );
		var progress = document.getElementById( 'hw-gaps-progress' );

		document.addEventListener( 'click', function ( event ) {
			var dismiss = event.target.closest( '.hw-gaps__dismiss' );

			if ( dismiss ) {
				event.preventDefault();

				var row  = dismiss.closest( 'tr' );
				var body = new FormData();
				body.append( 'action', 'hw_hostels_skip' );
				body.append( 'post', dismiss.getAttribute( 'data-post' ) );
				body.append( 'nonce', dismiss.getAttribute( 'data-nonce' ) );
				dismiss.classList.add( 'disabled' );

				fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( r ) {
						if ( ! r || ! r.success ) {
							throw new Error();
						}
						row.classList.add( 'is-gone' );
						dismiss.textContent = r.data.dismissed ? <?php echo wp_json_encode( __( 'Dismissed', 'hostelworld' ) ); ?> : <?php echo wp_json_encode( __( 'Restored', 'hostelworld' ) ); ?>;
						setTimeout( function () { row.remove(); }, 600 );
					} )
					.catch( function () {
						// Fall back to the plain link, which reloads the page.
						window.location.href = dismiss.href;
					} );

				return;
			}

			var copy = event.target.closest( '.hw-gaps__copy' );

			if ( ! copy ) {
				return;
			}

			var code = copy.getAttribute( 'data-code' );
			var done = function () {
				copy.textContent = <?php echo wp_json_encode( __( 'Copied', 'hostelworld' ) ); ?>;
				setTimeout( function () { copy.textContent = <?php echo wp_json_encode( __( 'Copy', 'hostelworld' ) ); ?>; }, 1500 );
			};

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( code ).then( done, done );
			} else {
				window.prompt( '', code );
			}
		} );

		if ( ! button ) {
			return;
		}

		function step( offset ) {
			var body = new FormData();
			body.append( 'action', 'hw_hostels_gaps' );
			body.append( 'nonce', button.getAttribute( 'data-nonce' ) );
			body.append( 'offset', offset );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( r ) {
					if ( ! r || ! r.success ) {
						throw new Error( r && r.data ? r.data : 'The scan stopped.' );
					}

					var pct = r.data.total ? Math.round( r.data.next / r.data.total * 100 ) : 100;
					progress.querySelector( '.hw-gaps__meter span' ).style.width = pct + '%';
					progress.querySelector( '.hw-gaps__count' ).textContent = r.data.next + ' / ' + r.data.total;

					if ( r.data.done ) {
						window.location.reload();
					} else {
						step( r.data.next );
					}
				} )
				.catch( function ( e ) {
					button.disabled = false;
					progress.querySelector( '.hw-gaps__count' ).textContent = e.message + ' Click again to start over.';
				} );
		}

		button.addEventListener( 'click', function () {
			button.disabled = true;
			progress.classList.add( 'is-on' );
			step( 0 );
		} );
	}() );
	</script>
	<?php
}

/**
 * The current tab as a CSV.
 */
function hw_hostels_gaps_csv() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hostels_gaps_csv' );

	$f    = hw_hostels_gaps_filters();
	$data = hw_hostels_gaps_filtered( hw_hostels_gaps_results(), $f );
	$map  = hw_hostels_gaps_place_map();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=articles-without-live-hostels-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, array( 'Article', 'Title', 'Published', 'Language', 'Hostel links', 'Hostel headings', 'Ready-made tag', 'Edit link' ) );

	foreach ( $data['rows'] as $id => $row ) {
		$codes = array();

		foreach ( hw_hostels_gaps_places( $id, $map ) as $place ) {
			$codes[] = sprintf( '[hw_hostels city="%s" country="%s" count="4"]', $place['city'], $place['country'] );
		}

		fputcsv( $out, array( get_permalink( $id ), $row['title'], $row['date'], $row['lang'], $row['links'], $row['heads'], implode( ' ', $codes ), admin_url( 'post.php?post=' . $id . '&action=edit' ) ) );
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_hw_hostels_gaps_csv', 'hw_hostels_gaps_csv' );
