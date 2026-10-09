<?php
/**
 * Content and live data for the Country Hub (states) and State Hub templates.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assembles a country hub (states) for the template: everything the country
 * hub has, plus the states grouped by region.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function hw_hub_country_states_config( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$config = hw_hub_country_config( $post_id );

	// The country config reads these, but this template has no city section.
	unset( $config['cities'], $config['cities_title'], $config['cities_intro'], $config['cities_live'] );

	$config['states_title']  = hw_hub_value( 'states_title', $post_id );
	$config['states_intro']  = hw_hub_value( 'states_intro', $post_id );
	$config['state_groups']  = hw_hub_group_states( hw_hub_value( 'states', $post_id ), hw_hub_value( 'state_regions', $post_id ) );
	$config['states_count']  = 0;

	foreach ( $config['state_groups'] as $group ) {
		$config['states_count'] += count( $group['states'] );
	}

	return $config;
}

/**
 * Puts the states under their regions.
 *
 * Regions come out in the order of the Regions box, then any region typed
 * against a state but not listed there, in the order first met. States with no
 * region at all go last, under no heading. With no regions anywhere, the result
 * is a single group without a heading — one plain grid.
 *
 * @param array $states  Rows from the States field.
 * @param array $regions Rows from the Regions field.
 * @return array List of array( name, intro, anchor, states ).
 */
function hw_hub_group_states( $states, $regions ) {
	$groups = array();
	$order  = array();

	foreach ( (array) $regions as $region ) {
		$name = trim( (string) $region['name'] );

		if ( '' === $name ) {
			continue;
		}

		$key = strtolower( $name );

		if ( isset( $groups[ $key ] ) ) {
			continue;
		}

		$groups[ $key ] = array(
			'name'   => $name,
			'intro'  => trim( (string) $region['intro'] ),
			'states' => array(),
		);
		$order[]        = $key;
	}

	$loose = array();

	foreach ( (array) $states as $state ) {
		if ( '' === trim( (string) $state['name'] ) ) {
			continue;
		}

		$name = trim( (string) $state['region'] );

		if ( '' === $name ) {
			$loose[] = $state;
			continue;
		}

		$key = strtolower( $name );

		if ( ! isset( $groups[ $key ] ) ) {
			$groups[ $key ] = array(
				'name'   => $name,
				'intro'  => '',
				'states' => array(),
			);
			$order[]        = $key;
		}

		$groups[ $key ]['states'][] = $state;
	}

	$out = array();

	foreach ( $order as $key ) {
		// A region listed in the box but with no states is left off.
		if ( ! $groups[ $key ]['states'] ) {
			continue;
		}

		$groups[ $key ]['anchor'] = 'states-' . sanitize_title( $groups[ $key ]['name'] );
		$out[]                    = $groups[ $key ];
	}

	if ( $loose ) {
		$out[] = array(
			'name'   => '',
			'intro'  => '',
			'anchor' => 'states-other',
			'states' => $loose,
		);
	}

	return $out;
}

/**
 * Assembles a state hub's content for the template.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function hw_hub_state_config( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$get = function ( $key ) use ( $post_id ) {
		return hw_hub_value( $key, $post_id );
	};

	$h1 = $get( 'hero_h1' );

	$config = array(
		'hub_term'     => $get( 'hub_term' ),
		'country_slug' => $get( 'country_slug' ) ? $get( 'country_slug' ) : 'usa',
		'parent_hub'   => $get( 'parent_hub' ),
		'currency'     => $get( 'currency' ) ? strtoupper( $get( 'currency' ) ) : 'USD',
		'scope_rows'   => $get( 'scope_rows' ),

		'eyebrow'      => $get( 'hero_eyebrow' ),
		'h1'           => $h1 ? $h1 : hw_hub_page_title( $post_id ),
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
		'areas_zoom'   => (int) $get( 'areas_zoom' ) ? (int) $get( 'areas_zoom' ) : 6,
		'hostels_pins' => $get( 'hostels_pins' ),
		'areas'        => $get( 'areas' ),

		'itin_title'   => $get( 'itin_title' ),
		'itin_intro'   => $get( 'itin_intro' ),
		'itin'         => $get( 'itin' ),

		'sections'     => array(),

		'layout'       => $get( 'layout' ),
		'cities_title' => $get( 'cities_title' ),
		'cities_intro' => $get( 'cities_intro' ),
		'cities_live'  => $get( 'cities_live' ),
		'cities'       => array(),
		'cities_tag'   => $get( 'cities_tag' ),
		'cities_more'  => $get( 'cities_more' ),

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

	foreach ( $get( 'cities' ) as $city ) {
		if ( '' === trim( (string) $city['name'] ) ) {
			continue;
		}

		$config['cities'][] = array(
			'name'    => trim( $city['name'] ),
			'hw_city' => trim( (string) $city['hw_city'] ) ? trim( $city['hw_city'] ) : trim( $city['name'] ),
			'blurb'   => $city['blurb'],
			'poi'     => hw_hub_split_list( $city['poi'] ),
			'image'   => $city['image'],
		);
	}

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

/**
 * Everything live a state hub shows, gathered from its main cities.
 *
 * Hostelworld has no state-level record, so the state is the sum of the cities
 * the editor listed: each is looked up by name, then its hostels and events
 * are fetched and merged. Every call underneath is cached, so a visitor never
 * waits on the API; the first view after a cache expires does the work.
 *
 * @param array $hub State hub config.
 * @return array {
 *     @type array $cities  City name (lowercase) => record: id, lat, lng,
 *                          properties, rating, reviews, from (cheapest dorm).
 *     @type array $hostels Top-rated hostels across the cities.
 *     @type array $events  Upcoming events across the cities.
 *     @type array $totals  properties, reviews, rating (weighted by reviews).
 * }
 */
function hw_hub_state_live( $hub ) {
	$out = array(
		'cities'  => array(),
		'hostels' => array(),
		'events'  => array(),
		'totals'  => array( 'properties' => 0, 'reviews' => 0, 'rating' => 0, 'cities' => 0 ),
	);

	$want_hostels = 'manual' !== $hub['hostels_source'];
	$want_events  = 'manual' !== $hub['events_source'];
	$limit        = $hub['hostels_count'] ? $hub['hostels_count'] : 12;
	$weighted     = 0;
	$hostels      = array();
	$events       = array();

	foreach ( $hub['cities'] as $city ) {
		$record = hw_hub_api_city( $city['hw_city'], $hub['country_slug'] );

		if ( empty( $record['id'] ) ) {
			continue;
		}

		$record['from'] = array();

		// Needed for the dorm price on the card and for the map, even when the
		// carousel itself is set to manual.
		if ( $want_hostels || $hub['cities_live'] || $hub['hostels_pins'] ) {
			$city_hostels = hw_hub_api_city_hostels(
				array(
					'city_id'  => $record['id'],
					'currency' => $hub['currency'],
					'limit'    => $limit,
					'hours'    => $hub['hostels_cache'] ? $hub['hostels_cache'] : 12,
				)
			);

			// The feed occasionally carries a placeholder price — a $1 or $0 dorm
			// beside a row of $30 ones. Anything under a quarter of the city's
			// typical dorm price is treated as missing rather than advertised.
			$city_hostels = hw_hub_drop_price_outliers( $city_hostels );

			foreach ( $city_hostels as $hostel ) {
				if ( '' === $hostel['city'] ) {
					$hostel['city'] = $record['name'] ? $record['name'] : $city['name'];
				}

				$hostels[] = $hostel;

				if ( ! empty( $hostel['dorm'] ) && ! empty( $hostel['price']['value'] ) ) {
					$value = (float) $hostel['price']['value'];

					if ( ! $record['from'] || $value < $record['from']['value'] ) {
						$record['from'] = array(
							'value'    => $value,
							'currency' => isset( $hostel['price']['currency'] ) ? $hostel['price']['currency'] : $hub['currency'],
						);
					}
				}
			}
		}

		if ( $want_events ) {
			foreach ( hw_hub_api_events(
				array(
					'scope'   => 'city',
					'country' => $record['id'],
					'limit'   => $hub['events_count'] ? $hub['events_count'] : 8,
					'hours'   => $hub['events_cache'] ? $hub['events_cache'] : 24,
				)
			) as $event ) {
				if ( '' === $event['city'] ) {
					$event['city'] = $city['name'];
				}

				$events[] = $event;
			}
		}

		$out['cities'][ strtolower( $city['name'] ) ] = $record;

		$out['totals']['properties'] += $record['properties'];
		$out['totals']['reviews']    += $record['reviews'];
		$out['totals']['cities']++;
		$weighted                    += $record['rating'] * $record['reviews'];
	}

	if ( $out['totals']['reviews'] ) {
		$out['totals']['rating'] = $weighted / $out['totals']['reviews'];
	}

	// Hostels: one list across the state, best first, each property once.
	$seen = array();

	foreach ( $hostels as $hostel ) {
		$id = $hostel['url'] ? $hostel['url'] : $hostel['name'];

		if ( isset( $seen[ $id ] ) ) {
			continue;
		}

		$seen[ $id ]      = true;
		$out['hostels'][] = $hostel;
	}

	usort(
		$out['hostels'],
		function ( $a, $b ) {
			if ( $a['rating'] === $b['rating'] ) {
				return $b['reviews'] <=> $a['reviews'];
			}

			return $b['rating'] <=> $a['rating'];
		}
	);

	if ( $want_hostels ) {
		$out['hostels'] = array_slice( $out['hostels'], 0, $limit );
	}

	// Events: the same ordering the country hub uses — staff picks, then
	// popularity, then date — and each event once.
	$seen = array();

	foreach ( $events as $event ) {
		$id = strtolower( $event['name'] . '|' . $event['start'] );

		if ( isset( $seen[ $id ] ) ) {
			continue;
		}

		$seen[ $id ]     = true;
		$out['events'][] = $event;
	}

	usort(
		$out['events'],
		function ( $a, $b ) {
			if ( $a['pick'] !== $b['pick'] ) {
				return $a['pick'] ? -1 : 1;
			}

			if ( $a['score'] !== $b['score'] ) {
				return $b['score'] <=> $a['score'];
			}

			return strcmp( $a['start'], $b['start'] );
		}
	);

	$out['events'] = array_slice( $out['events'], 0, $hub['events_count'] ? $hub['events_count'] : 8 );

	return $out;
}

/**
 * Clears prices that cannot be real, so they are neither shown on a hostel card
 * nor used as a city's "dorms from".
 *
 * A price is dropped when it is under a quarter of the median dorm price among
 * the same city's hostels. With fewer than three dorm prices there is nothing
 * to compare against, so the list is returned untouched.
 *
 * @param array $hostels Result of hw_hub_api_city_hostels().
 * @return array
 */
function hw_hub_drop_price_outliers( $hostels ) {
	$prices = array();

	foreach ( $hostels as $hostel ) {
		if ( ! empty( $hostel['dorm'] ) && isset( $hostel['price']['value'] ) && (float) $hostel['price']['value'] > 0 ) {
			$prices[] = (float) $hostel['price']['value'];
		}
	}

	if ( count( $prices ) < 3 ) {
		return $hostels;
	}

	sort( $prices );
	$middle = (int) floor( count( $prices ) / 2 );
	$median = count( $prices ) % 2 ? $prices[ $middle ] : ( $prices[ $middle - 1 ] + $prices[ $middle ] ) / 2;
	$floor  = $median / 4;

	foreach ( $hostels as $i => $hostel ) {
		if ( isset( $hostel['price']['value'] ) && (float) $hostel['price']['value'] < $floor ) {
			$hostels[ $i ]['price'] = null;
			$hostels[ $i ]['dorm']  = false;
		}
	}

	return $hostels;
}
