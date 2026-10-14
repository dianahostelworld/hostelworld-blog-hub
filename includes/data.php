<?php
/**
 * Reading and parsing saved values.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where a field is stored.
 *
 * Country hubs keep their own namespace. The two templates share field names —
 * stats, faqs, hub_term — and without this, switching a Page from one template
 * to the other silently inherits the old one's content: a Spain hub with
 * Europe's tag, Europe's stats and Europe's FAQs.
 *
 * @param string $key     Field key.
 * @param int    $post_id Page ID.
 * @return string
 */
function hw_hub_meta_key( $key, $post_id ) {
	return hw_hub_meta_prefix( $post_id ) . $key;
}

/**
 * The meta prefix for a page, which depends on its template. Each template
 * keeps its own namespace so switching one does not inherit another's content.
 *
 * @param int    $post_id Page ID.
 * @param string $base    Base prefix.
 * @return string
 */
function hw_hub_meta_prefix( $post_id, $base = HW_HUB_META_PREFIX ) {
	return $base . hw_hub_template_prefix( hw_hub_template_for_post( $post_id ) );
}

/**
 * The part of the meta key that belongs to a template.
 *
 * The states variant of the country hub shares the country namespace on
 * purpose: it is the same page with a different city section, so switching
 * a Page between the two keeps everything else that was written.
 *
 * @param string $template Template name.
 * @return string
 */
function hw_hub_template_prefix( $template ) {
	$map = array(
		'continent'      => '',
		'country'        => 'country_',
		'country_states' => 'country_',
		'city'           => 'city_',
		'state'          => 'state_',
	);

	return isset( $map[ $template ] ) ? $map[ $template ] : '';
}

/**
 * Every template name, in the order the plugin added them.
 *
 * @return array
 */
function hw_hub_template_names() {
	return array( 'continent', 'country', 'city', 'country_states', 'state' );
}

/**
 * The key this field used to be stored under, before the move to public meta.
 *
 * @param string $key     Field key.
 * @param int    $post_id Page ID.
 * @return string
 */
function hw_hub_legacy_meta_key( $key, $post_id ) {
	return hw_hub_meta_prefix( $post_id, HW_HUB_LEGACY_META_PREFIX ) . $key;
}

/**
 * Turns list rows into the pipe-separated text shown in the editor.
 *
 * @param array $rows Rows of column => value.
 * @param array $cols Column order.
 * @return string
 */
function hw_hub_list_to_text( $rows, $cols ) {
	$lines = array();

	foreach ( (array) $rows as $row ) {
		$cells = array();
		foreach ( $cols as $col ) {
			$cells[] = isset( $row[ $col ] ) ? str_replace( array( '|', "\n" ), array( '/', ' ' ), $row[ $col ] ) : '';
		}
		$lines[] = implode( ' | ', $cells );
	}

	return implode( "\n", $lines );
}

/**
 * Turns editor text back into list rows. Missing cells come back empty rather
 * than shifting the columns along, and blank lines are skipped.
 *
 * @param string $text Raw field value.
 * @param array  $cols Column order.
 * @return array
 */
function hw_hub_text_to_list( $text, $cols ) {
	$rows = array();

	foreach ( preg_split( '/\R/', (string) $text ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}

		$cells = array_map( 'trim', explode( '|', $line ) );
		$row   = array();

		foreach ( $cols as $i => $col ) {
			$row[ $col ] = isset( $cells[ $i ] ) ? $cells[ $i ] : '';
		}

		$rows[] = $row;
	}

	return $rows;
}

/**
 * The raw stored value for a field, as text, falling back to the default.
 * This is what the admin panel shows.
 *
 * @param string $key     Field key.
 * @param int    $post_id Page ID.
 * @return string
 */
function hw_hub_raw_value( $key, $post_id ) {
	$template = hw_hub_template_for_post( $post_id );
	$fields   = hw_hub_schema_fields( $template );
	$defaults = hw_hub_defaults( $template );
	$field    = isset( $fields[ $key ] ) ? $fields[ $key ] : array( 'type' => 'text' );

	$meta_key = hw_hub_meta_key( $key, $post_id );

	if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
		return (string) get_post_meta( $post_id, $meta_key, true );
	}

	// Pages saved before the move keep working, and move across on next save.
	$legacy = hw_hub_legacy_meta_key( $key, $post_id );

	if ( metadata_exists( 'post', $post_id, $legacy ) ) {
		return (string) get_post_meta( $post_id, $legacy, true );
	}

	$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';

	if ( in_array( $field['type'], array( 'list', 'rows' ), true ) ) {
		return hw_hub_list_to_text( $default, $field['cols'] );
	}

	return (string) $default;
}

/**
 * A field's starting content as editor text, whatever its type.
 *
 * @param string $key Field key.
 * @return string
 */
function hw_hub_default_text( $key, $template = '' ) {
	$fields   = hw_hub_schema_fields( $template );
	$defaults = hw_hub_defaults( $template );
	$field    = isset( $fields[ $key ] ) ? $fields[ $key ] : array( 'type' => 'text' );
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';

	if ( in_array( $field['type'], array( 'list', 'rows' ), true ) ) {
		return hw_hub_list_to_text( $default, $field['cols'] );
	}

	return (string) $default;
}

/**
 * The usable value for a field: parsed rows for lists, plain value otherwise.
 *
 * @param string $key     Field key.
 * @param int    $post_id Page ID.
 * @return mixed
 */
function hw_hub_value( $key, $post_id ) {
	$fields = hw_hub_schema_fields( hw_hub_template_for_post( $post_id ) );
	$field  = isset( $fields[ $key ] ) ? $fields[ $key ] : array( 'type' => 'text' );
	$raw    = hw_hub_raw_value( $key, $post_id );

	if ( in_array( $field['type'], array( 'list', 'rows' ), true ) ) {
		return hw_hub_text_to_list( $raw, $field['cols'] );
	}

	if ( 'check' === $field['type'] ) {
		return '1' === $raw;
	}

	if ( 'checks' === $field['type'] ) {
		$parsed = hw_hub_parse_checks( $raw, $field['options'] );
		return $parsed['values'];
	}

	return $raw;
}

/**
 * The trip types a city hub can be "good for", in the order the destination
 * finder shows them. Keys are what is stored; labels are what people see.
 *
 * @return array Slug => label.
 */
function hw_hub_trip_types() {
	return array(
		'city-break'   => 'City break',
		'adventure'    => 'Adventure',
		'beach'        => 'Beach',
		'lakes-rivers' => 'Lakes & rivers',
		'nightlife'    => 'Nightlife',
		'food'         => 'Food',
		'culture'      => 'Culture',
		'road-trip'    => 'Road trip',
	);
}

/**
 * Reads a multi-choice value: stored slugs, or labels typed into a
 * spreadsheet, separated by commas or semicolons, in any case.
 *
 * @param string|array $raw     Value, e.g. "city-break,food" or "City break; Food".
 * @param array        $options Slug => label.
 * @return array {
 *     @type array $values  Recognised slugs, in the options' order, no repeats.
 *     @type array $unknown Anything that matched no option, as written.
 * }
 */
function hw_hub_parse_checks( $raw, $options ) {
	$parts   = is_array( $raw ) ? $raw : preg_split( '/[,;]/', (string) $raw );
	$found   = array();
	$unknown = array();

	foreach ( $parts as $part ) {
		$part = trim( wp_specialchars_decode( (string) $part ) );

		if ( '' === $part ) {
			continue;
		}

		$match = '';

		foreach ( $options as $slug => $label ) {
			if ( 0 === strcasecmp( $part, (string) $slug ) || 0 === strcasecmp( $part, (string) $label ) ) {
				$match = $slug;
				break;
			}
		}

		if ( '' === $match ) {
			$unknown[] = $part;
		} else {
			$found[ $match ] = true;
		}
	}

	// Keep the options' order, so the same choice is always stored the same way.
	$values = array_values( array_filter( array_keys( $options ), function ( $slug ) use ( $found ) {
		return isset( $found[ $slug ] );
	} ) );

	return array(
		'values'  => $values,
		'unknown' => $unknown,
	);
}

/**
 * Labels for stored slugs, for showing to people.
 *
 * @param array $slugs   Slugs.
 * @param array $options Slug => label.
 * @return array
 */
function hw_hub_check_labels( $slugs, $options ) {
	$labels = array();

	foreach ( (array) $slugs as $slug ) {
		if ( isset( $options[ $slug ] ) ) {
			$labels[] = $options[ $slug ];
		}
	}

	return $labels;
}

/**
 * Splits a textarea into paragraphs on blank lines.
 *
 * @param string $text Raw value.
 * @return array
 */
function hw_hub_paragraphs( $text ) {
	$parts = preg_split( '/\R\s*\R/', trim( (string) $text ) );

	return array_values( array_filter( array_map( 'trim', (array) $parts ), 'strlen' ) );
}

/**
 * Assembles everything the template needs for the current Page.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function hw_hub_page_config( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$get = function ( $key ) use ( $post_id ) {
		return hw_hub_value( $key, $post_id );
	};

	$h1 = $get( 'hero_h1' );

	$config = array(
		'hub_term'       => $get( 'hub_term' ),
		'scope_rows'     => $get( 'scope_rows' ),
		'show_read_time' => $get( 'show_read_time' ),

		'eyebrow'        => $get( 'hero_eyebrow' ),
		'h1'             => $h1 ? $h1 : get_the_title( $post_id ),
		'intro'          => $get( 'hero_intro' ),
		'hero_image'     => $get( 'hero_image' ),
		'hero_overlay'   => $get( 'hero_overlay' ),
		'hero_cta'       => $get( 'hero_cta' ),

		'why_title'      => $get( 'why_title' ),
		'why_body'       => hw_hub_paragraphs( $get( 'why_body' ) ),
		'why_link'       => array(
			'label' => $get( 'why_link_label' ),
			'href'  => $get( 'why_link_url' ),
		),
		'why_cards'      => $get( 'why_cards' ),
		'stats'          => $get( 'stats' ),

		'countries_title' => $get( 'countries_title' ),
		'countries_intro' => $get( 'countries_intro' ),
		'featured'        => $get( 'featured' ),
		'regions'         => array(),

		'events_title'   => $get( 'events_title' ),
		'events_intro'   => $get( 'events_intro' ),
		'events_source'  => $get( 'events_source' ),
		'events'         => $get( 'events' ),
		'events_cta'     => array(
			'label' => $get( 'events_cta_label' ),
			'href'  => $get( 'events_cta_url' ),
		),
		'events_api'     => array(
			'country' => $get( 'events_country' ),
			'limit'   => (int) $get( 'events_count' ),
			'hours'   => (int) $get( 'events_cache' ),
		),

		'hostels_title'  => $get( 'hostels_title' ),
		'hostels_intro'  => $get( 'hostels_intro' ),
		'hostels_cta'    => array(
			'label' => $get( 'hostels_cta_label' ),
			'href'  => $get( 'hostels_cta_url' ),
		),
		'hostels'        => $get( 'hostels' ),
		'hostels_source' => $get( 'hostels_source' ),
		'hostels_api'    => array(
			'location_type' => $get( 'hostels_location_type' ),
			'location_id'   => $get( 'hostels_location_id' ),
			'currency'      => $get( 'hostels_currency' ),
			'limit'         => (int) $get( 'hostels_count' ),
			'hours'         => (int) $get( 'hostels_cache' ),
		),

		'when_title'     => $get( 'when_title' ),
		'when_intro'     => $get( 'when_intro' ),
		'seasons'        => array(),

		'costs_title'    => $get( 'costs_title' ),
		'costs_body'     => hw_hub_paragraphs( $get( 'costs_body' ) ),
		'cost_rows'      => $get( 'cost_rows' ),
		'cost_note'      => $get( 'cost_note' ),
		'money_reads'    => $get( 'money_reads' ),

		'rail_title'     => $get( 'rail_title' ),
		'rail_body'      => hw_hub_paragraphs( $get( 'rail_body' ) ),
		'rail_link'      => array(
			'label' => $get( 'rail_link_label' ),
			'href'  => $get( 'rail_link_url' ),
		),
		'rail_image'     => $get( 'rail_image' ),
		'routes'         => $get( 'routes' ),

		'events_title'   => $get( 'events_title' ),
		'events_intro'   => $get( 'events_intro' ),
		'events_cta'     => array(
			'label' => $get( 'events_cta_label' ),
			'href'  => $get( 'events_cta_url' ),
		),
		'events'         => $get( 'events' ),
		'events_pills'   => $get( 'events_pills' ),
		'events_source'  => $get( 'events_source' ),
		'events_api'     => array(
			'scope'   => 'continent',
			'country' => $get( 'events_continent' ),
			'limit'   => (int) $get( 'events_count' ),
			'hours'   => (int) $get( 'events_cache' ),
		),

		'reads_intro'    => $get( 'reads_intro' ),
		'article_rows'   => $get( 'article_rows' ),
		'all_title'      => $get( 'all_title' ),
		'all_body'       => $get( 'all_body' ),
		'all_cta'        => $get( 'all_cta' ),

		'trips_title'    => $get( 'trips_title' ),
		'trips_note'     => $get( 'trips_note' ),
		'trips'          => $get( 'trips' ),

		'faq_title'      => $get( 'faq_title' ),
		'faq_note'       => $get( 'faq_note' ),
		'faqs'           => $get( 'faqs' ),

		'cta_title'      => $get( 'cta_title' ),
		'cta_body'       => $get( 'cta_body' ),
		'cta_buttons'    => $get( 'cta_buttons' ),
		'newsletter'     => array(
			'title'     => $get( 'newsletter_title' ),
			'body'      => $get( 'newsletter_body' ),
			'shortcode' => $get( 'newsletter_shortcode' ),
		),
	);

	// Regions: the comma-separated country column becomes an array.
	foreach ( $get( 'regions' ) as $region ) {
		$names = array_values( array_filter( array_map( 'trim', explode( ',', $region['countries'] ) ), 'strlen' ) );

		$config['regions'][] = array(
			'name'      => $region['name'],
			'note'      => $region['note'],
			'countries' => $names,
		);
	}

	// Seasons: the semicolon-separated bullet column becomes an array.
	foreach ( $get( 'seasons' ) as $season ) {
		$points = array_values( array_filter( array_map( 'trim', explode( ';', $season['points'] ) ), 'strlen' ) );
		$tone   = in_array( $season['tone'], array( 'green', 'amber', 'blue' ), true ) ? $season['tone'] : 'green';

		$config['seasons'][] = array(
			'name'   => $season['name'],
			'months' => $season['months'],
			'tag'    => $season['tag'],
			'tone'   => $tone,
			'body'   => $season['body'],
			'points' => $points,
		);
	}

	return $config;
}

/**
 * Curated articles for a row, used while the tags are still being set up and
 * only on the hub those articles belong to.
 *
 * Keyed off the hub term in Settings rather than the row's tags, so rows saved
 * before tags were joined with + keep working.
 *
 * @param string $hub_term   Hub term slug from Settings.
 * @param string $expression The row's tag expression.
 * @return array
 */
function hw_hub_fallback_articles( $hub_term, $expression ) {
	$fallbacks = hw_hub_article_fallbacks();

	if ( $hub_term !== $fallbacks['hub_term'] ) {
		return array();
	}

	foreach ( hw_hub_parse_slugs( $expression ) as $slug ) {
		if ( isset( $fallbacks['rows'][ $slug ] ) ) {
			return $fallbacks['rows'][ $slug ];
		}
	}

	return array();
}

/**
 * Assembles a country hub's content for the template.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function hw_hub_country_config( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$get = function ( $key ) use ( $post_id ) {
		return hw_hub_value( $key, $post_id );
	};

	$h1 = $get( 'hero_h1' );

	$config = array(
		'hub_term'       => $get( 'hub_term' ),
		'parent_hub'     => $get( 'parent_hub' ),
		'scope_rows'     => $get( 'scope_rows' ),

		'eyebrow'        => $get( 'hero_eyebrow' ),
		'h1'             => $h1 ? $h1 : get_the_title( $post_id ),
		'intro'          => $get( 'hero_intro' ),
		'hero_image'     => $get( 'hero_image' ),
		'hero_overlay'   => $get( 'hero_overlay' ),
		'hero_cta'       => $get( 'hero_cta' ),
		'stats'          => $get( 'stats' ),

		'intro_title'    => $get( 'intro_title' ),
		'intro_body'     => hw_hub_paragraphs( $get( 'intro_body' ) ),
		'facts'          => $get( 'facts' ),

		'cities_title'   => $get( 'cities_title' ),
		'cities_intro'   => $get( 'cities_intro' ),
		'cities'         => $get( 'cities' ),
		'cities_live'    => $get( 'cities_live' ),

		'season_title'   => $get( 'season_title' ),
		'season_intro'   => $get( 'season_intro' ),
		'season_note'    => $get( 'season_note' ),
		'season_rows'    => $get( 'season_rows' ),

		'budget_title'   => $get( 'budget_title' ),
		'budget_intro'   => $get( 'budget_intro' ),
		'budget_note'    => $get( 'budget_note' ),
		'budget_rows'    => $get( 'budget_rows' ),
		'budget_currency' => $get( 'budget_currency' ),

		'trips_title'    => $get( 'trips_title' ),
		'trips_intro'    => $get( 'trips_intro' ),
		'trip_types'     => array(),

		'sections'       => array(),

		'events_title'   => $get( 'events_title' ),
		'events_intro'   => $get( 'events_intro' ),
		'events_source'  => $get( 'events_source' ),
		'events'         => $get( 'events' ),
		'events_cta'     => array(
			'label' => $get( 'events_cta_label' ),
			'href'  => $get( 'events_cta_url' ),
		),
		'events_api'     => array(
			'country' => $get( 'events_country' ),
			'limit'   => (int) $get( 'events_count' ),
			'hours'   => (int) $get( 'events_cache' ),
		),

		'hostels_title'  => $get( 'hostels_title' ),
		'hostels_intro'  => $get( 'hostels_intro' ),
		'hostels_cta'    => array(
			'label' => $get( 'hostels_cta_label' ),
			'href'  => $get( 'hostels_cta_url' ),
		),
		'hostels'        => $get( 'hostels' ),
		'hostels_source' => $get( 'hostels_source' ),
		'hostels_api'    => array(
			'location_type' => $get( 'hostels_location_type' ),
			'location_id'   => $get( 'hostels_location_id' ),
			'currency'      => $get( 'hostels_currency' ),
			'limit'         => (int) $get( 'hostels_count' ),
			'hours'         => (int) $get( 'hostels_cache' ),
		),

		'faq_title'      => $get( 'faq_title' ),
		'faq_note'       => $get( 'faq_note' ),
		'faqs'           => $get( 'faqs' ),

		'cta_title'      => $get( 'cta_title' ),
		'cta_body'       => $get( 'cta_body' ),
		'cta_buttons'    => $get( 'cta_buttons' ),
		'newsletter'     => array(
			'title'     => $get( 'newsletter_title' ),
			'body'      => $get( 'newsletter_body' ),
			'shortcode' => $get( 'newsletter_shortcode' ),
		),
	);

	// Trip types: the semicolon-separated columns become lists.
	foreach ( $get( 'trip_types' ) as $index => $trip ) {
		$colour = in_array( $trip['colour'], array( 'blue', 'green', 'purple', 'pink', 'teal', 'sunset', 'orange' ), true ) ? $trip['colour'] : 'blue';

		$config['trip_types'][] = array(
			'id'      => 'trip-' . ( $index + 1 ) . '-' . sanitize_title( $trip['name'] ),
			'name'    => $trip['name'],
			'colour'  => $colour,
			'icon'    => $trip['icon'],
			'summary' => $trip['summary'],
			'doing'   => hw_hub_split_list( $trip['doing'] ),
			'spots'   => hw_hub_split_list( $trip['spots'] ),
			'cities'  => hw_hub_split_list( $trip['cities'] ),
			'tag'     => $trip['tag'],
		);
	}

	// The content sections, in page order, each with its own icon, accent and
	// illustration so seven blocks of prose don't read as one wall of text.
	$sections = array(
		'do'        => array( 'nav' => __( 'Things to do', 'hostelworld' ), 'icon' => 'panoramic-views', 'colour' => 'blue', 'art' => 'in-destination-activities' ),
		'food'      => array( 'nav' => __( 'Food and drink', 'hostelworld' ), 'icon' => 'local-markets', 'colour' => 'sunset', 'art' => 'food-experiences' ),
		'nightlife' => array( 'nav' => __( 'Nightlife', 'hostelworld' ), 'icon' => 'nightclub', 'colour' => 'purple', 'art' => 'party' ),
		'transport' => array( 'nav' => __( 'Getting around', 'hostelworld' ), 'icon' => 'train', 'colour' => 'green', 'art' => 'multi-day-tours' ),
		'money'     => array( 'nav' => __( 'Costs', 'hostelworld' ), 'icon' => 'wallet', 'colour' => 'teal', 'art' => 'piggy-bank' ),
		'safety'    => array( 'nav' => __( 'Safety', 'hostelworld' ), 'icon' => 'security', 'colour' => 'pink', 'art' => 'security' ),
		'entry'     => array( 'nav' => __( 'Entry and documents', 'hostelworld' ), 'icon' => 'passport', 'colour' => 'orange', 'art' => 'travel-plan' ),
	);

	foreach ( $sections as $key => $meta ) {
		$config['sections'][] = array(
			'key'    => $key,
			'nav'    => $meta['nav'],
			'icon'   => $meta['icon'],
			'colour' => $meta['colour'],
			'art'    => $meta['art'],
			'title'  => $get( $key . '_title' ),
			'body'   => hw_hub_paragraphs( $get( $key . '_body' ) ),
			'points' => $get( $key . '_points' ),
			'tag'    => $get( $key . '_tag' ),
			'more'   => $get( $key . '_more' ),
		);
	}

	return $config;
}

/**
 * Splits a semicolon-separated column into a list.
 *
 * @param string $value Raw column value.
 * @return array
 */
function hw_hub_split_list( $value ) {
	$parts = explode( ';', (string) $value );

	return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
}

/**
 * Every meta key this plugin stores, for both templates.
 *
 * @return array
 */
function hw_hub_all_meta_keys() {
	$keys = array();

	foreach ( hw_hub_template_names() as $template ) {
		foreach ( array( HW_HUB_META_PREFIX, HW_HUB_LEGACY_META_PREFIX ) as $base ) {
			$prefix = $base . hw_hub_template_prefix( $template );

			foreach ( array_keys( hw_hub_schema_fields( $template ) ) as $field ) {
				$keys[] = $prefix . $field;
			}
		}
	}

	return array_values( array_unique( $keys ) );
}

/**
 * Carries the hub content across when Polylang creates a translation.
 *
 * Without this, a new Spanish Spain page starts completely blank: the content
 * lives in post meta, and Polylang copies no meta it has not been told about.
 * Copied, not synchronised — the whole point is that the translator then edits
 * it, and synchronising would push those edits back over the English.
 *
 * @param array $keys Meta keys Polylang already copies.
 * @return array
 */
function hw_hub_polylang_copy_metas( $keys ) {
	return array_merge( (array) $keys, hw_hub_all_meta_keys() );
}
add_filter( 'pll_copy_post_metas', 'hw_hub_polylang_copy_metas' );

/**
 * Runs each hub field through a filter as Polylang copies it to a translation.
 *
 * Polylang Pro does not machine-translate on its own — it hands the fields to a
 * translator, or to whatever translation plugin is installed. This makes that
 * possible for these fields specifically: anything hooked to
 * hw_hub_translate_field gets the value, the field key and the target language,
 * and whatever it returns is what the translation starts with. Untouched, the
 * value is copied verbatim, which is the current behaviour.
 *
 * @param mixed  $value Meta value.
 * @param string $key   Meta key.
 * @param string $lang  Target language slug.
 * @return mixed
 */
function hw_hub_polylang_translate_meta( $value, $key, $lang ) {
	if ( 0 !== strpos( (string) $key, HW_HUB_META_PREFIX ) && 0 !== strpos( (string) $key, HW_HUB_LEGACY_META_PREFIX ) ) {
		return $value;
	}

	return apply_filters( 'hw_hub_translate_field', $value, $key, $lang );
}
add_filter( 'pll_translate_post_meta', 'hw_hub_polylang_translate_meta', 10, 3 );

/**
 * The template's own wording, translated through Polylang when it is active.
 *
 * The editable content is per-language because each translation has its own
 * fields. The labels baked into the template — "What to do", "min read", "See
 * dates and book" — are not, so they are registered as strings and looked up
 * here. Without Polylang, or before a string is translated, the English is
 * returned unchanged.
 *
 * @param string $text English wording.
 * @return string
 */
function hw_hub_t( $text ) {
	return function_exists( 'pll__' ) ? pll__( $text ) : $text;
}

/**
 * The wording baked into the templates.
 *
 * @return array
 */
function hw_hub_ui_strings() {
	return array(
		'Top-rated hostels in %s',
		'%s places to stay on Hostelworld',
		'%s country guide',
		'%s country guides',
		'%s state guide',
		'%s city guide',
		'On now: %1$s, until %2$s',
		'%s state guides',
		'%s city guides',
		'Coming up: %1$s, %2$s',
		'Planning a trip to %s?',
		'Read our %s guide',
		'More on %s',
		'Top-rated places to stay in %s',
		'Ratings and prices from Hostelworld, updated every few hours.',
		'%s min read',
		'A traveller, on Hostelworld',
		'Back to %s',
		'Bar height is temperature. Colour is how busy it gets:',
		'Beds',
		'Blog',
		'Breadcrumb',
		'Best cities for it',
		'Budget',
		'Cities',
		'Costs',
		'Countries',
		'Country guides',
		'Doing things',
		'Dorm bed',
		'Dorms from %s a night',
		'FAQs',
		'Food and drink',
		'From the blog',
		'Getting around',
		'Got this long?',
		'Guides',
		'Guides for this are on the way.',
		'Hostels',
		'More articles',
		'More events',
		'More hostels',
		'More on %1$s in %2$s',
		'Nights',
		'On this page',
		'Only you can see this. No articles here yet: nothing is tagged %1$s, so %2$s returns nothing.',
		'Only you can see this. Nothing is tagged %1$s, so the row %2$s comes back empty. Create the tag, or edit the row under Continent hub content > Guides.',
		'Only you can see this. The live feed returned nothing and no fallback hostels are listed — check the Location ID under Hostels.',
		'Only you can see this. The live feed returned nothing — check the Location ID under Hostels.',
		'Only you can see this. The tags exist, but no post carries all of %s yet.',
		'Overview',
		'Per day',
		'Previous articles',
		'Previous events',
		'Previous hostels',
		'Reading on %1$s in %2$s',
		'Region',
		'Rooms from %s a night',
		'Scroll sections left',
		'Scroll sections right',
		'See all',
		'See dates and book',
		'See prices and reviews',
		'Spots to visit',
		'Staff pick',
		'Sustainable',
		'The %s guide',
		'Types of trip',
		'What to do',
		"What's on",
		'When to go',
		'busy',
		'dorms from %s',
		'in',
		'in short',
		'peak',
		'quiet',
		// Country hub (states) and State hub.
		'%s at a glance',
		'%s travel questions',
		'Average, from %s reviews',
		'Best states for it',
		'Hostels across its %s main cities',
		'Hostels in its main city',
		'Itineraries',
		'Itineraries for %s',
		'Main cities',
		'Map',
		'Map of %s',
		'More on the cities of %s',
		'Only you can see this. The live feed returned nothing — check the cities under Main cities, and their Hostelworld names.',
		'Orange labels are regions, dark labels the main cities, and dots the %s top-rated hostels. Map data from OpenStreetMap contributors.',
		'Orange labels are regions, dark labels the main cities. Map data from OpenStreetMap contributors.',
		'Pick a state',
		'Points of interest',
		'Read more',
		'Regions',
		'States',
		'The main cities in %s',
		'The regions of %s',
		'What a trip to %s costs',
		"What's on in %s",
		'When to go to %s',
		'Where to stay',
		'Where to stay in %s',
		'See all hostels in %s',
		'See all places to stay in %s',
	);
}

/**
 * Registers that wording so it appears under Languages > String translations.
 */
function hw_hub_register_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	foreach ( hw_hub_ui_strings() as $string ) {
		pll_register_string( $string, $string, 'Hostelworld hubs' );
	}
}
add_action( 'init', 'hw_hub_register_strings' );

/**
 * Registers every field as public post meta.
 *
 * Two reasons. Public meta is what translation tooling looks at — anything
 * prefixed with an underscore is protected and skipped. And registering it
 * exposes the fields over the REST API, which is how Polylang Pro's DeepL
 * module reads and writes a post's content.
 */
function hw_hub_register_meta() {
	$done = array();

	foreach ( hw_hub_template_names() as $template ) {
		$prefix = HW_HUB_META_PREFIX . hw_hub_template_prefix( $template );

		foreach ( array_keys( hw_hub_schema_fields( $template ) ) as $field ) {
			// The states variant shares the country namespace, so most of its
			// keys are already registered by the time it comes round.
			if ( isset( $done[ $prefix . $field ] ) ) {
				continue;
			}

			$done[ $prefix . $field ] = true;

			register_post_meta(
				'page',
				$prefix . $field,
				array(
					'type'          => 'string',
					'single'        => true,
					'default'       => '',
					'show_in_rest'  => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_pages' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'hw_hub_register_meta', 20 );

/**
 * Assembles a city hub's content for the template.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function hw_hub_city_config( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$get = function ( $key ) use ( $post_id ) {
		return hw_hub_value( $key, $post_id );
	};

	$h1 = $get( 'hero_h1' );

	$config = array(
		'hub_term'     => $get( 'hub_term' ),
		'city_slug'    => $get( 'city_slug' ),
		'country_slug' => $get( 'country_slug' ),
		'parent_hub'   => $get( 'parent_hub' ),
		'currency'     => $get( 'currency' ) ? $get( 'currency' ) : 'EUR',
		'scope_rows'   => $get( 'scope_rows' ),

		'eyebrow'      => $get( 'hero_eyebrow' ),
		'h1'           => $h1 ? $h1 : get_the_title( $post_id ),
		'intro'        => $get( 'hero_intro' ),
		'hero_image'   => $get( 'hero_image' ),
		'hero_overlay' => $get( 'hero_overlay' ),
		'hero_cta'     => $get( 'hero_cta' ),
		'live_stats'   => $get( 'live_stats' ),
		'stats'        => $get( 'stats' ),

		'intro_title'  => $get( 'intro_title' ),
		'intro_body'   => hw_hub_paragraphs( $get( 'intro_body' ) ),
		'facts'        => $get( 'facts' ),

		'areas_title'  => $get( 'areas_title' ),
		'areas_intro'  => $get( 'areas_intro' ),
		'areas_map'    => $get( 'areas_map' ),
		'areas_zoom'   => (int) $get( 'areas_zoom' ) ? (int) $get( 'areas_zoom' ) : 13,
		'areas_live'   => $get( 'areas_live' ),
		'hostels_pins' => $get( 'hostels_pins' ),
		'areas'        => $get( 'areas' ),

		'itin_title'   => $get( 'itin_title' ),
		'itin_intro'   => $get( 'itin_intro' ),
		'itin'         => $get( 'itin' ),

		'sections'     => array(),

		'trips_title'  => $get( 'trips_title' ),
		'trips_intro'  => $get( 'trips_intro' ),
		'trips'        => $get( 'trips' ),
		'trips_tag'    => $get( 'trips_tag' ),
		'trips_more'   => $get( 'trips_more' ),

		'season_title' => $get( 'season_title' ),
		'season_intro' => $get( 'season_intro' ),
		'season_note'  => $get( 'season_note' ),
		'season_rows'  => $get( 'season_rows' ),

		'events_title'  => $get( 'events_title' ),
		'events_intro'  => $get( 'events_intro' ),
		'events_source' => $get( 'events_source' ),
		'events'        => $get( 'events' ),
		'events_cta'    => array(
			'label' => $get( 'events_cta_label' ),
			'href'  => $get( 'events_cta_url' ),
		),
		'events_count'  => (int) $get( 'events_count' ),
		'events_cache'  => (int) $get( 'events_cache' ),

		'budget_title'    => $get( 'budget_title' ),
		'budget_intro'    => $get( 'budget_intro' ),
		'budget_note'     => $get( 'budget_note' ),
		'budget_rows'     => $get( 'budget_rows' ),
		'budget_currency' => $get( 'budget_currency' ),

		'hostels_title'  => $get( 'hostels_title' ),
		'hostels_intro'  => $get( 'hostels_intro' ),
		'hostels_cta'    => array(
			'label' => $get( 'hostels_cta_label' ),
			'href'  => $get( 'hostels_cta_url' ),
		),
		'hostels_source' => $get( 'hostels_source' ),
		'hostels_count'  => (int) $get( 'hostels_count' ),
		'hostels_cache'  => (int) $get( 'hostels_cache' ),
		'picks'          => $get( 'picks' ),
		'hostels'        => $get( 'hostels' ),

		'faq_title'   => $get( 'faq_title' ),
		'faq_note'    => $get( 'faq_note' ),
		'faqs'        => $get( 'faqs' ),

		'cta_title'   => $get( 'cta_title' ),
		'cta_body'    => $get( 'cta_body' ),
		'cta_buttons' => $get( 'cta_buttons' ),
		'newsletter'  => array(
			'title'     => $get( 'newsletter_title' ),
			'body'      => $get( 'newsletter_body' ),
			'shortcode' => $get( 'newsletter_shortcode' ),
		),
	);

	$sections = array(
		'do'        => array( 'nav' => __( 'Things to do', 'hostelworld' ), 'icon' => 'panoramic-views', 'colour' => 'blue', 'art' => 'in-destination-activities' ),
		'free'      => array( 'nav' => __( 'Free things', 'hostelworld' ), 'icon' => 'price-low', 'colour' => 'green', 'art' => 'backpack' ),
		'food'      => array( 'nav' => __( 'Food and drink', 'hostelworld' ), 'icon' => 'local-markets', 'colour' => 'sunset', 'art' => 'food-experiences' ),
		'nightlife' => array( 'nav' => __( 'Nightlife', 'hostelworld' ), 'icon' => 'nightclub', 'colour' => 'purple', 'art' => 'party' ),
		'transport' => array( 'nav' => __( 'Getting around', 'hostelworld' ), 'icon' => 'train', 'colour' => 'teal', 'art' => 'multi-day-tours' ),
		'money'     => array( 'nav' => __( 'Costs', 'hostelworld' ), 'icon' => 'wallet', 'colour' => 'orange', 'art' => 'piggy-bank' ),
		'safety'    => array( 'nav' => __( 'Safety', 'hostelworld' ), 'icon' => 'security', 'colour' => 'pink', 'art' => 'security' ),
	);

	foreach ( $sections as $key => $meta ) {
		$config['sections'][] = array(
			'key'    => $key,
			'nav'    => $meta['nav'],
			'icon'   => $meta['icon'],
			'colour' => $meta['colour'],
			'art'    => $meta['art'],
			'title'  => $get( $key . '_title' ),
			'body'   => hw_hub_paragraphs( $get( $key . '_body' ) ),
			'points' => $get( $key . '_points' ),
			'tag'    => $get( $key . '_tag' ),
			'more'   => $get( $key . '_more' ),
		);
	}

	return $config;
}
