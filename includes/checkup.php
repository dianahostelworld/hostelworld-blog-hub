<?php
/**
 * Hub check-up: every hub in one list, with what still needs doing.
 *
 * Pages > Hub check-up. Each hub is checked for:
 * - missing photos: hero, featured image, and the photos on its cards;
 * - empty sections, and sections still showing the starter text (a cell left
 *   empty in an import keeps the template's sample copy, which then shows);
 * - carousels with no articles, or whose tag doesn't exist;
 * - cities, states or countries whose guide page doesn't exist yet.
 *
 * Checking runs in small batches from the screen, with a progress bar, and the
 * results are saved, so opening the screen is instant. A hub is checked again
 * on its own whenever it is saved.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HW_HUB_CHECKUP_OPTION', 'hw_hub_checkup' );

/**
 * The kinds of problem, in the order they are shown.
 *
 * @return array Key => label.
 */
function hw_hub_checkup_kinds() {
	return array(
		'photo'    => __( 'Missing photos', 'hostelworld' ),
		'empty'    => __( 'Empty sections', 'hostelworld' ),
		'starter'  => __( 'Starter text showing', 'hostelworld' ),
		'carousel' => __( 'Carousels with no articles', 'hostelworld' ),
		'guide'    => __( 'No guide page yet', 'hostelworld' ),
	);
}

/**
 * Template names as people know them.
 *
 * @return array
 */
function hw_hub_checkup_type_names() {
	return array(
		'continent'      => __( 'Continent', 'hostelworld' ),
		'country'        => __( 'Country', 'hostelworld' ),
		'country_states' => __( 'Country (states)', 'hostelworld' ),
		'city'           => __( 'City', 'hostelworld' ),
		'state'          => __( 'State', 'hostelworld' ),
	);
}

/**
 * Every hub, in any status an editor works with.
 *
 * @return int[]
 */
function hw_hub_checkup_hub_ids() {
	$args = array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'ID',
		'order'          => 'ASC',
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

	return array_map( 'intval', get_posts( $args ) );
}

/**
 * Whether a field has been saved on this page at all. A field that never was
 * shows the template's starter text.
 *
 * @param string $key     Field key.
 * @param int    $post_id Page ID.
 * @return bool
 */
function hw_hub_checkup_saved( $key, $post_id ) {
	return metadata_exists( 'post', $post_id, hw_hub_meta_key( $key, $post_id ) )
		|| metadata_exists( 'post', $post_id, hw_hub_legacy_meta_key( $key, $post_id ) );
}

/**
 * Whether a resolved field value has anything in it.
 *
 * @param mixed $value Value from hw_hub_value().
 * @return bool
 */
function hw_hub_checkup_filled( $value ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $row ) {
			if ( '' !== trim( implode( '', array_map( 'strval', (array) $row ) ) ) ) {
				return true;
			}
		}
		return false;
	}

	return '' !== trim( (string) $value );
}

/**
 * Whether an image field points at something that will show.
 *
 * @param mixed $image Attachment ID, URL or illustration reference.
 * @return bool
 */
function hw_hub_checkup_image_ok( $image ) {
	$image = trim( (string) $image );

	if ( '' === $image ) {
		return false;
	}

	if ( is_numeric( $image ) ) {
		return (bool) wp_get_attachment_url( (int) $image );
	}

	return true;
}

/**
 * Up to five names, then "and 3 more".
 *
 * @param array $names Names.
 * @return string
 */
function hw_hub_checkup_names( $names ) {
	$names = array_values( array_unique( array_filter( array_map( 'trim', $names ) ) ) );
	$shown = array_slice( $names, 0, 5 );
	$text  = implode( ', ', $shown );

	if ( count( $names ) > 5 ) {
		/* translators: %d: how many more */
		$text .= ' ' . sprintf( __( 'and %d more', 'hostelworld' ), count( $names ) - 5 );
	}

	return $text;
}

/**
 * Published posts carrying every tag in an expression, in the hub's language.
 *
 * @param string $expression Tag expression.
 * @param string $lang       Polylang language slug, or ''.
 * @return int
 */
function hw_hub_checkup_count( $expression, $lang ) {
	$terms = hw_hub_resolve_terms( $expression );

	if ( ! $terms ) {
		return 0;
	}

	$tax_query = array( 'relation' => 'AND' );

	foreach ( $terms as $term ) {
		$tax_query[] = array(
			'taxonomy' => $term->taxonomy,
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		);
	}

	$args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery
	);

	if ( $lang ) {
		$args['lang'] = $lang;
	}

	$query = new WP_Query( $args );

	return (int) $query->found_posts;
}

/**
 * The page a guide link leads to, and whether it is live.
 *
 * @param int    $hub_id Hub page ID.
 * @param string $name   City, state or country name.
 * @param string $link   What was typed in the link column, if anything.
 * @param bool   $hub_url_rules Use hw_hub_url() rules (city and state cards)
 *                              rather than hw_hub_country_url() rules (continent).
 * @return string 'ok', 'missing', 'draft' or 'broken'.
 */
function hw_hub_checkup_guide( $hub_id, $name, $link, $hub_url_rules ) {
	$link = trim( (string) $link );

	// No link typed: the card goes to this hub's address plus the name, which
	// is where the child page lives once it is created under this hub.
	if ( '' === $link ) {
		$slug = sanitize_title( $name );

		if ( '' === $slug ) {
			return 'ok';
		}

		$path  = get_page_uri( $hub_id );
		$child = $path ? get_page_by_path( $path . '/' . $slug ) : null;

		if ( ! $child ) {
			// A child of this hub that isn't published yet often has no slug
			// at all, so match drafts by their title too.
			$children = get_posts(
				array(
					'post_type'      => 'page',
					'post_parent'    => $hub_id,
					'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
					'posts_per_page' => -1,
					'lang'           => '',
				)
			);

			foreach ( $children as $candidate ) {
				if ( $slug === $candidate->post_name || $slug === sanitize_title( $candidate->post_title ) ) {
					$child = $candidate;
					break;
				}
			}
		}

		if ( ! $child ) {
			return 'missing';
		}

		return 'publish' === $child->post_status ? 'ok' : 'draft';
	}

	if ( $hub_url_rules ) {
		$url = hw_hub_url( $link );

		if ( '' === $url ) {
			return 'broken';
		}
	} else {
		$url = ( 0 === strpos( $link, 'http://' ) || 0 === strpos( $link, 'https://' ) ) ? $link : home_url( '/' . ltrim( $link, '/' ) . '/' );
	}

	if ( '#' === $url[0] ) {
		return 'ok';
	}

	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	$host = wp_parse_url( $url, PHP_URL_HOST );

	// Links to the rest of hostelworld.com or other sites aren't ours to check.
	if ( $host && $home && strtolower( $host ) !== strtolower( $home ) ) {
		return 'ok';
	}

	$id = url_to_postid( $url );

	if ( ! $id ) {
		return 'missing';
	}

	return 'publish' === get_post_status( $id ) ? 'ok' : 'draft';
}

/**
 * Turns guide results into issue lines.
 *
 * @param array  $found  'missing' / 'draft' / 'broken' => names.
 * @param string $noun   "city", "state" or "country", plural ready.
 * @return array
 */
function hw_hub_checkup_guide_issues( $found, $noun ) {
	$issues = array();

	if ( ! empty( $found['missing'] ) ) {
		/* translators: 1: cities/states/countries, 2: names */
		$issues[] = sprintf( __( 'No page yet for these %1$s: %2$s', 'hostelworld' ), $noun, hw_hub_checkup_names( $found['missing'] ) );
	}

	if ( ! empty( $found['draft'] ) ) {
		/* translators: 1: cities/states/countries, 2: names */
		$issues[] = sprintf( __( 'Page not published yet for these %1$s: %2$s', 'hostelworld' ), $noun, hw_hub_checkup_names( $found['draft'] ) );
	}

	if ( ! empty( $found['broken'] ) ) {
		/* translators: 1: cities/states/countries, 2: names */
		$issues[] = sprintf( __( 'Guide link goes nowhere for these %1$s: %2$s', 'hostelworld' ), $noun, hw_hub_checkup_names( $found['broken'] ) );
	}

	return $issues;
}

/**
 * Checks one hub.
 *
 * @param int $post_id Hub page ID.
 * @return array|null Null when the page is not a hub.
 */
function hw_hub_checkup_hub( $post_id ) {
	$post = get_post( $post_id );

	if ( ! $post || 'page' !== $post->post_type || ! in_array( get_page_template_slug( $post_id ), hw_hub_template_keys(), true ) ) {
		return null;
	}

	$template = hw_hub_template_for_post( $post_id );
	$schema   = hw_hub_schema( $template );
	$lang     = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post_id, 'slug' ) : '';
	$issues   = array_fill_keys( array_keys( hw_hub_checkup_kinds() ), array() );
	$get      = function ( $key ) use ( $post_id ) {
		return hw_hub_value( $key, $post_id );
	};

	/* Photos ------------------------------------------------------------ */

	// Continent hubs fall back to their map illustration, so no hero is fine.
	if ( 'continent' !== $template && ! hw_hub_checkup_image_ok( $get( 'hero_image' ) ) ) {
		$issues['photo'][] = __( 'No hero photo', 'hostelworld' );
	}

	if ( ! has_post_thumbnail( $post_id ) ) {
		$issues['photo'][] = __( 'No featured image (used in listings, social shares and the cards that link here)', 'hostelworld' );
	}

	// Card photos, by template: field => what the cards are called.
	$card_fields = array(
		'country'        => array( 'cities' => __( 'city cards', 'hostelworld' ) ),
		'country_states' => array( 'states' => __( 'state cards', 'hostelworld' ) ),
		'city'           => array(
			'itin'  => __( 'itinerary cards', 'hostelworld' ),
			'trips' => __( 'day trip cards', 'hostelworld' ),
		),
		'state'          => array(
			'itin'   => __( 'itinerary cards', 'hostelworld' ),
			'cities' => __( 'city cards', 'hostelworld' ),
		),
	);

	if ( isset( $card_fields[ $template ] ) ) {
		foreach ( $card_fields[ $template ] as $field => $label ) {
			$missing = array();

			foreach ( (array) $get( $field ) as $row ) {
				$name = isset( $row['name'] ) ? $row['name'] : ( isset( $row['label'] ) ? $row['label'] : '' );

				if ( '' === trim( (string) $name ) ) {
					continue;
				}

				if ( ! hw_hub_checkup_image_ok( isset( $row['image'] ) ? $row['image'] : '' ) ) {
					$missing[] = $name;
				}
			}

			if ( $missing ) {
				/* translators: 1: card type, 2: names */
				$issues['photo'][] = sprintf( __( 'No photo on %1$s: %2$s', 'hostelworld' ), $label, hw_hub_checkup_names( $missing ) );
			}
		}
	}

	if ( 'continent' === $template ) {
		$missing  = array();
		$children = hw_hub_country_pages( $post_id );

		foreach ( (array) $get( 'featured' ) as $country ) {
			if ( '' === trim( (string) $country['name'] ) || hw_hub_checkup_image_ok( $country['image'] ) ) {
				continue;
			}

			$key  = strtolower( $country['name'] );
			$page = isset( $children[ $key ] ) ? $children[ $key ] : null;

			// The card borrows the country page's featured image when it has one.
			if ( ! $page || ! has_post_thumbnail( $page ) ) {
				$missing[] = $country['name'];
			}
		}

		if ( $missing ) {
			/* translators: %s: names */
			$issues['photo'][] = sprintf( __( 'No photo on featured country cards: %s', 'hostelworld' ), hw_hub_checkup_names( $missing ) );
		}
	}

	/* Sections ---------------------------------------------------------- */

	// Live sections fill themselves from Hostelworld, Settings isn't content,
	// and the hero always has the page title (its photo is checked above).
	$skip = array( 'settings', 'events', 'hostels' );

	$empty   = array();
	$starter = array();

	foreach ( $schema as $section_key => $section ) {
		if ( in_array( $section_key, $skip, true ) ) {
			continue;
		}

		$content = 0;
		$filled  = 0;
		$unsaved = false;

		foreach ( $section['fields'] as $key => $field ) {
			$is_title = (bool) preg_match( '/_title$|_h1$/', $key );

			if ( ! in_array( $field['type'], array( 'textarea', 'list', 'rows' ), true ) && ! $is_title ) {
				continue;
			}

			// Buttons, chips and tags are optional extras, not the section's content.
			if ( preg_match( '/_cta$|^stats$|_buttons$|_pills$|^facts$|_points$|^money_reads$|^state_regions$|^picks$/', $key ) ) {
				continue;
			}

			if ( ! $is_title ) {
				$content++;

				if ( hw_hub_checkup_filled( $get( $key ) ) ) {
					$filled++;
				}
			}

			if ( ! hw_hub_checkup_saved( $key, $post_id ) && '' !== trim( hw_hub_default_text( $key, $template ) ) ) {
				$unsaved = true;
			}
		}

		$label = isset( $section['label'] ) ? $section['label'] : $section_key;

		if ( $content && ! $filled && 'hero' !== $section_key ) {
			$empty[] = $label;
		}

		if ( $unsaved ) {
			$starter[] = $label;
		}
	}

	if ( $empty ) {
		$issues['empty'][] = implode( ', ', $empty );
	}

	if ( $starter ) {
		$issues['starter'][] = implode( ', ', $starter );
	}

	/* Carousels --------------------------------------------------------- */

	$hub_term = (string) $get( 'hub_term' );
	$rows     = hw_hub_status_rows( $post_id );

	if ( $rows ) {
		if ( '' === trim( $hub_term ) ) {
			if ( $get( 'scope_rows' ) ) {
				$issues['carousel'][] = __( 'No tag set for this hub in Settings', 'hostelworld' );
			}
		} elseif ( ! hw_hub_find_term( $hub_term ) ) {
			/* translators: %s: tag slug */
			$issues['carousel'][] = sprintf( __( 'The tag %s doesn\'t exist yet, so every carousel is empty', 'hostelworld' ), sanitize_title( $hub_term ) );
			$rows                 = array();
		}
	}

	$config = array(
		'hub_term'   => $hub_term,
		'scope_rows' => $get( 'scope_rows' ),
	);

	foreach ( $rows as $row ) {
		if ( '' === trim( (string) $row['tag'] ) ) {
			continue;
		}

		$expression = hw_hub_row_expression( $config, $row['tag'] );
		$missing    = hw_hub_missing_slugs( $expression );

		if ( $missing ) {
			/* translators: 1: carousel name, 2: tag slugs */
			$issues['carousel'][] = sprintf( __( '%1$s: no tag called %2$s', 'hostelworld' ), $row['title'], implode( ', ', $missing ) );
			continue;
		}

		if ( ! hw_hub_checkup_count( $expression, $lang ) ) {
			/* translators: 1: carousel name, 2: tag expression */
			$issues['carousel'][] = sprintf( __( '%1$s: no articles (%2$s)', 'hostelworld' ), $row['title'], $expression );
		}
	}

	/* Guide pages ------------------------------------------------------- */

	$guides = array(
		'country'        => array( 'cities', __( 'cities', 'hostelworld' ) ),
		'country_states' => array( 'states', __( 'states', 'hostelworld' ) ),
	);

	if ( isset( $guides[ $template ] ) ) {
		list( $field, $noun ) = $guides[ $template ];
		$found                = array();

		foreach ( (array) $get( $field ) as $row ) {
			if ( '' === trim( (string) $row['name'] ) ) {
				continue;
			}

			$state = hw_hub_checkup_guide( $post_id, $row['name'], $row['guide'], true );

			if ( 'ok' !== $state ) {
				$found[ $state ][] = $row['name'];
			}
		}

		$issues['guide'] = hw_hub_checkup_guide_issues( $found, $noun );
	}

	if ( 'continent' === $template ) {
		$found = array();
		$seen  = array();

		foreach ( (array) $get( 'featured' ) as $country ) {
			if ( '' === trim( (string) $country['name'] ) ) {
				continue;
			}

			$seen[ strtolower( $country['name'] ) ] = true;
			$state                                   = hw_hub_checkup_guide( $post_id, $country['name'], $country['page'], false );

			if ( 'ok' !== $state ) {
				$found[ $state ][] = $country['name'];
			}
		}

		// The country grid only links countries whose page exists, so a
		// missing one is simply unlinked rather than broken.
		foreach ( (array) $get( 'regions' ) as $region ) {
			foreach ( array_filter( array_map( 'trim', explode( ',', (string) $region['countries'] ) ), 'strlen' ) as $name ) {
				if ( '' === $name || isset( $seen[ strtolower( $name ) ] ) ) {
					continue;
				}

				$seen[ strtolower( $name ) ] = true;
				$state                       = hw_hub_checkup_guide( $post_id, $name, '', false );

				if ( 'ok' !== $state ) {
					$found[ $state ][] = $name;
				}
			}
		}

		$issues['guide'] = hw_hub_checkup_guide_issues( $found, __( 'countries', 'hostelworld' ) );
	}

	$issues = array_filter( $issues );

	return array(
		'title'   => get_the_title( $post_id ),
		'type'    => $template,
		'status'  => $post->post_status,
		'lang'    => $lang,
		'issues'  => $issues,
		'count'   => array_sum( array_map( 'count', $issues ) ),
		'checked' => time(),
	);
}

/**
 * Saved results.
 *
 * @return array
 */
function hw_hub_checkup_results() {
	$saved = get_option( HW_HUB_CHECKUP_OPTION, array() );

	return is_array( $saved ) && isset( $saved['hubs'] ) ? $saved : array(
		'hubs' => array(),
		'run'  => 0,
	);
}

/**
 * Saves results.
 *
 * @param array $results Results.
 */
function hw_hub_checkup_save( $results ) {
	update_option( HW_HUB_CHECKUP_OPTION, $results, false );
}

/**
 * Checks a hub again when it is saved, so the list stays current between full
 * checks. Runs after the hub's own fields are written.
 *
 * @param int $post_id Page ID.
 */
function hw_hub_checkup_on_save( $post_id, $post ) {
	if ( 'page' !== $post->post_type || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$results = hw_hub_checkup_results();

	if ( ! $results['run'] ) {
		return;
	}

	$row = hw_hub_checkup_hub( $post_id );

	if ( $row ) {
		$results['hubs'][ $post_id ] = $row;
	} else {
		unset( $results['hubs'][ $post_id ] );
	}

	hw_hub_checkup_save( $results );
}
add_action( 'save_post', 'hw_hub_checkup_on_save', 99, 2 );

/**
 * Drops a deleted hub from the list.
 *
 * @param int $post_id Post ID.
 */
function hw_hub_checkup_on_delete( $post_id ) {
	$results = hw_hub_checkup_results();

	if ( isset( $results['hubs'][ $post_id ] ) ) {
		unset( $results['hubs'][ $post_id ] );
		hw_hub_checkup_save( $results );
	}
}
add_action( 'deleted_post', 'hw_hub_checkup_on_delete' );
add_action( 'trashed_post', 'hw_hub_checkup_on_delete' );

/**
 * One batch of the full check, called repeatedly by the screen.
 */
function hw_hub_checkup_ajax() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_send_json_error( 'You do not have permission to do this.', 403 );
	}

	check_ajax_referer( 'hw_hub_checkup', 'nonce' );

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
	$ids    = hw_hub_checkup_hub_ids();
	$batch  = array_slice( $ids, $offset, 20 );

	// A full check starts from a clean list, so hubs that are gone disappear.
	$results = 0 === $offset ? array(
		'hubs' => array(),
		'run'  => 0,
	) : hw_hub_checkup_results();

	foreach ( $batch as $id ) {
		$row = hw_hub_checkup_hub( $id );

		if ( $row ) {
			$results['hubs'][ $id ] = $row;
		}
	}

	$done = $offset + count( $batch ) >= count( $ids );

	if ( $done ) {
		$results['run'] = time();
	}

	hw_hub_checkup_save( $results );

	wp_send_json_success(
		array(
			'next'  => $offset + count( $batch ),
			'total' => count( $ids ),
			'done'  => $done,
		)
	);
}
add_action( 'wp_ajax_hw_hub_checkup', 'hw_hub_checkup_ajax' );

/**
 * Pages > Hub check-up.
 */
function hw_hub_checkup_menu() {
	add_submenu_page( 'edit.php?post_type=page', __( 'Hub check-up', 'hostelworld' ), __( 'Hub check-up', 'hostelworld' ), 'edit_pages', 'hw-hub-checkup', 'hw_hub_checkup_render' );
}
add_action( 'admin_menu', 'hw_hub_checkup_menu' );

/**
 * The filters from the address bar.
 *
 * @return array
 */
function hw_hub_checkup_filters() {
	// phpcs:disable WordPress.Security.NonceVerification
	$kinds = hw_hub_checkup_kinds();
	$types = hw_hub_checkup_type_names();
	$kind  = isset( $_GET['kind'] ) ? sanitize_key( $_GET['kind'] ) : '';
	$type  = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
	$state = isset( $_GET['state'] ) ? sanitize_key( $_GET['state'] ) : '';
	$q     = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	// phpcs:enable

	return array(
		'kind'  => isset( $kinds[ $kind ] ) ? $kind : '',
		'type'  => isset( $types[ $type ] ) ? $type : '',
		'state' => in_array( $state, array( 'publish', 'unpublished' ), true ) ? $state : '',
		'q'     => $q,
	);
}

/**
 * The hubs that pass the filters, worst first.
 *
 * @param array $results Saved results.
 * @param array $f       Filters.
 * @return array
 */
function hw_hub_checkup_filtered( $results, $f ) {
	$rows = array();

	foreach ( $results['hubs'] as $id => $hub ) {
		if ( $f['type'] && $hub['type'] !== $f['type'] ) {
			continue;
		}

		if ( 'publish' === $f['state'] && 'publish' !== $hub['status'] ) {
			continue;
		}

		if ( 'unpublished' === $f['state'] && 'publish' === $hub['status'] ) {
			continue;
		}

		if ( $f['kind'] && empty( $hub['issues'][ $f['kind'] ] ) ) {
			continue;
		}

		if ( '' !== $f['q'] && false === stripos( $hub['title'], $f['q'] ) ) {
			continue;
		}

		$rows[ $id ] = $hub;
	}

	uasort(
		$rows,
		function ( $a, $b ) {
			if ( $a['count'] !== $b['count'] ) {
				return $b['count'] <=> $a['count'];
			}
			return strcasecmp( $a['title'], $b['title'] );
		}
	);

	return $rows;
}

/**
 * Renders the screen.
 */
function hw_hub_checkup_render() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	$results = hw_hub_checkup_results();
	$f       = hw_hub_checkup_filters();
	$kinds   = hw_hub_checkup_kinds();
	$types   = hw_hub_checkup_type_names();
	$rows    = hw_hub_checkup_filtered( $results, $f );
	$base    = admin_url( 'edit.php?post_type=page&page=hw-hub-checkup' );
	$csv     = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'hw_hub_checkup_csv' ), array_filter( $f ) ), admin_url( 'admin-post.php' ) ), 'hw_hub_checkup_csv' );

	$totals = array_fill_keys( array_keys( $kinds ), 0 );
	$with   = 0;

	foreach ( $results['hubs'] as $hub ) {
		if ( $hub['count'] ) {
			$with++;
		}

		foreach ( $hub['issues'] as $kind => $lines ) {
			if ( isset( $totals[ $kind ] ) && $lines ) {
				$totals[ $kind ]++;
			}
		}
	}

	$per_page = 50;
	$paged    = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
	$pages    = max( 1, (int) ceil( count( $rows ) / $per_page ) );
	$paged    = min( $paged, $pages );
	$shown    = array_slice( $rows, ( $paged - 1 ) * $per_page, $per_page, true );
	?>
	<div class="wrap hw-checkup">
		<style>
			.hw-checkup__bar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin: 16px 0; }
			.hw-checkup__progress { display: none; align-items: center; gap: 10px; }
			.hw-checkup__progress.is-on { display: inline-flex; }
			.hw-checkup__meter { width: 220px; height: 8px; background: #f0f0f1; border-radius: 4px; overflow: hidden; }
			.hw-checkup__meter span { display: block; height: 100%; width: 0; background: #F25621; transition: width .2s; }
			.hw-checkup__tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; margin: 12px 0 16px; }
			.hw-checkup__tile { display: block; background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 14px 16px; color: inherit; text-decoration: none; }
			.hw-checkup__tile:hover, .hw-checkup__tile.is-on { border-color: #F25621; box-shadow: 0 0 0 1px #F25621; }
			.hw-checkup__tile strong { display: block; font-size: 26px; line-height: 1.1; margin-bottom: 4px; }
			.hw-checkup__tile span { color: #646970; }
			.hw-checkup__filters { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 8px 0 12px; }
			.hw-checkup table td { vertical-align: top; }
			.hw-checkup__hub strong { display: block; }
			.hw-checkup__hub .row-actions { position: static; color: #646970; }
			.hw-checkup__issue { display: flex; gap: 10px; margin: 0 0 8px; }
			.hw-checkup__issue:last-child { margin-bottom: 0; }
			.hw-checkup__line { display: block; margin-bottom: 2px; }
			.hw-checkup__kind { flex: 0 0 auto; display: inline-block; width: 168px; box-sizing: border-box; padding: 1px 8px; border-radius: 10px; font-size: 12px; line-height: 20px; height: 20px; text-align: center; }
			.hw-checkup__kind--photo { background: #fde7e0; color: #8a2a0c; }
			.hw-checkup__kind--empty { background: #fcf0d4; color: #6b4b00; }
			.hw-checkup__kind--starter { background: #f3e8fd; color: #5a2a85; }
			.hw-checkup__kind--carousel { background: #e3eefc; color: #18407a; }
			.hw-checkup__kind--guide { background: #e2f4ea; color: #145a32; }
			.hw-checkup__ok { color: #1a7f37; font-weight: 600; }
			.hw-checkup__status { display: inline-block; font-size: 12px; color: #646970; }
			.hw-checkup .description { max-width: 900px; }
		</style>

		<h1><?php esc_html_e( 'Hub check-up', 'hostelworld' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Every hub in one list, with what still needs doing: missing photos, empty sections, sections still showing the starter text, carousels with no articles, and cities, states or countries whose guide page doesn\'t exist yet. A hub is checked again whenever it is saved. "Check all hubs" runs the whole list again.', 'hostelworld' ); ?></p>

		<div class="hw-checkup__bar">
			<button type="button" class="button button-primary" id="hw-checkup-run" data-nonce="<?php echo esc_attr( wp_create_nonce( 'hw_hub_checkup' ) ); ?>"><?php esc_html_e( 'Check all hubs', 'hostelworld' ); ?></button>
			<span class="hw-checkup__progress" id="hw-checkup-progress"><span class="hw-checkup__meter"><span></span></span><span class="hw-checkup__count"></span></span>
			<?php if ( $results['run'] ) : ?>
				<span class="description">
					<?php
					printf(
						/* translators: %s: date and time */
						esc_html__( 'Last full check: %s', 'hostelworld' ),
						esc_html( wp_date( 'j M Y, H:i', $results['run'] ) )
					);
					?>
				</span>
				<a class="button" href="<?php echo esc_url( $csv ); ?>"><?php esc_html_e( 'Download as CSV', 'hostelworld' ); ?></a>
			<?php endif; ?>
		</div>

		<?php if ( ! $results['run'] && ! $results['hubs'] ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'No check has run yet. Click "Check all hubs" to start. It takes a moment for a few hundred hubs.', 'hostelworld' ); ?></p></div>
		<?php else : ?>

			<div class="hw-checkup__tiles">
				<a class="hw-checkup__tile <?php echo '' === $f['kind'] ? 'is-on' : ''; ?>" href="<?php echo esc_url( remove_query_arg( array( 'kind', 'paged' ) ) ); ?>">
					<strong><?php echo esc_html( number_format_i18n( $with ) ); ?> / <?php echo esc_html( number_format_i18n( count( $results['hubs'] ) ) ); ?></strong>
					<span><?php esc_html_e( 'Hubs with something to fix', 'hostelworld' ); ?></span>
				</a>
				<?php foreach ( $kinds as $kind => $label ) : ?>
					<a class="hw-checkup__tile <?php echo $f['kind'] === $kind ? 'is-on' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'kind' => $kind, 'paged' => false ) ) ); ?>">
						<strong><?php echo esc_html( number_format_i18n( $totals[ $kind ] ) ); ?></strong>
						<span><?php echo esc_html( $label ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>

			<form class="hw-checkup__filters" method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>">
				<input type="hidden" name="post_type" value="page">
				<input type="hidden" name="page" value="hw-hub-checkup">
				<?php if ( $f['kind'] ) : ?>
					<input type="hidden" name="kind" value="<?php echo esc_attr( $f['kind'] ); ?>">
				<?php endif; ?>
				<select name="type">
					<option value=""><?php esc_html_e( 'All hub types', 'hostelworld' ); ?></option>
					<?php foreach ( $types as $value => $text ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $f['type'], $value ); ?>><?php echo esc_html( $text ); ?></option>
					<?php endforeach; ?>
				</select>
				<select name="state">
					<option value=""><?php esc_html_e( 'Published and drafts', 'hostelworld' ); ?></option>
					<option value="publish" <?php selected( $f['state'], 'publish' ); ?>><?php esc_html_e( 'Published only', 'hostelworld' ); ?></option>
					<option value="unpublished" <?php selected( $f['state'], 'unpublished' ); ?>><?php esc_html_e( 'Not published yet', 'hostelworld' ); ?></option>
				</select>
				<input type="search" name="q" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="<?php esc_attr_e( 'Hub name', 'hostelworld' ); ?>">
				<?php submit_button( __( 'Filter', 'hostelworld' ), 'secondary', '', false ); ?>
				<?php if ( array_filter( $f ) ) : ?>
					<a href="<?php echo esc_url( $base ); ?>"><?php esc_html_e( 'Clear', 'hostelworld' ); ?></a>
				<?php endif; ?>
			</form>

			<p class="description">
				<?php
				printf(
					/* translators: %s: number of hubs */
					esc_html( _n( '%s hub, the most to fix first.', '%s hubs, the most to fix first.', count( $rows ), 'hostelworld' ) ),
					esc_html( number_format_i18n( count( $rows ) ) )
				);
				?>
			</p>

			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:26%"><?php esc_html_e( 'Hub', 'hostelworld' ); ?></th>
						<th style="width:12%"><?php esc_html_e( 'Type', 'hostelworld' ); ?></th>
						<th><?php esc_html_e( 'What needs doing', 'hostelworld' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $shown ) : ?>
						<tr><td colspan="3"><?php esc_html_e( 'No hubs match.', 'hostelworld' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $shown as $id => $hub ) : ?>
						<tr>
							<td class="hw-checkup__hub">
								<strong><a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php echo esc_html( $hub['title'] ? $hub['title'] : '#' . $id ); ?></a></strong>
								<span class="hw-checkup__status">
									<?php
									$bits = array();
									if ( 'publish' !== $hub['status'] ) {
										$status_object = get_post_status_object( $hub['status'] );
										$bits[]        = $status_object ? $status_object->label : $hub['status'];
									}
									if ( $hub['lang'] ) {
										$bits[] = strtoupper( $hub['lang'] );
									}
									echo esc_html( implode( ' · ', $bits ) );
									?>
								</span>
								<div class="row-actions">
									<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>"><?php esc_html_e( 'Edit', 'hostelworld' ); ?></a> |
									<a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'hostelworld' ); ?></a>
								</div>
							</td>
							<td><?php echo esc_html( isset( $types[ $hub['type'] ] ) ? $types[ $hub['type'] ] : $hub['type'] ); ?></td>
							<td>
								<?php if ( ! $hub['count'] ) : ?>
									<span class="hw-checkup__ok"><?php esc_html_e( 'All good', 'hostelworld' ); ?></span>
								<?php endif; ?>
								<?php foreach ( $kinds as $kind => $label ) : ?>
									<?php if ( empty( $hub['issues'][ $kind ] ) ) { continue; } ?>
									<div class="hw-checkup__issue">
										<span class="hw-checkup__kind hw-checkup__kind--<?php echo esc_attr( $kind ); ?>"><?php echo esc_html( $label ); ?></span>
										<span>
											<?php foreach ( $hub['issues'][ $kind ] as $line ) : ?>
												<span class="hw-checkup__line"><?php echo esc_html( $line ); ?></span>
											<?php endforeach; ?>
										</span>
									</div>
								<?php endforeach; ?>
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
		var button   = document.getElementById( 'hw-checkup-run' );
		var progress = document.getElementById( 'hw-checkup-progress' );

		if ( ! button ) {
			return;
		}

		function step( offset ) {
			var body = new FormData();
			body.append( 'action', 'hw_hub_checkup' );
			body.append( 'nonce', button.getAttribute( 'data-nonce' ) );
			body.append( 'offset', offset );

			fetch( ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( r ) {
					if ( ! r || ! r.success ) {
						throw new Error( r && r.data ? r.data : 'The check stopped.' );
					}

					var pct = r.data.total ? Math.round( r.data.next / r.data.total * 100 ) : 100;
					progress.querySelector( '.hw-checkup__meter span' ).style.width = pct + '%';
					progress.querySelector( '.hw-checkup__count' ).textContent = r.data.next + ' / ' + r.data.total;

					if ( r.data.done ) {
						window.location.reload();
					} else {
						step( r.data.next );
					}
				} )
				.catch( function ( e ) {
					button.disabled = false;
					progress.querySelector( '.hw-checkup__count' ).textContent = e.message + ' Click again to carry on.';
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
 * The list as a CSV, with the screen's filters.
 */
function hw_hub_checkup_csv() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hub_checkup_csv' );

	$kinds = hw_hub_checkup_kinds();
	$types = hw_hub_checkup_type_names();
	$rows  = hw_hub_checkup_filtered( hw_hub_checkup_results(), hw_hub_checkup_filters() );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=hub-check-up-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	fputcsv( $out, array_merge( array( 'Hub', 'Type', 'Status', 'Language', 'URL', 'Edit link' ), array_values( $kinds ) ) );

	foreach ( $rows as $id => $hub ) {
		$line = array(
			$hub['title'],
			isset( $types[ $hub['type'] ] ) ? $types[ $hub['type'] ] : $hub['type'],
			$hub['status'],
			$hub['lang'],
			get_permalink( $id ),
			admin_url( 'post.php?post=' . $id . '&action=edit' ),
		);

		foreach ( array_keys( $kinds ) as $kind ) {
			$line[] = empty( $hub['issues'][ $kind ] ) ? '' : implode( ' / ', $hub['issues'][ $kind ] );
		}

		fputcsv( $out, $line );
	}

	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_hw_hub_checkup_csv', 'hw_hub_checkup_csv' );
