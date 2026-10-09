<?php
/**
 * Field schemas for the two templates built for countries that are travelled
 * state by state, starting with the USA.
 *
 * - Country Hub (states): the country hub, with the city list swapped for state
 *   cards grouped by region. Fifty full-width rows would be far too long.
 * - State Hub: the city hub's shape, applied to a whole state. Neighbourhoods
 *   become regions, and day trips become the state's main cities, each with
 *   what it is like and its points of interest. Cities do not link anywhere.
 *
 * Field types are the same as everywhere else (see schema.php).
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The country hub's fields, with the Cities tab replaced by States.
 *
 * Built from the country schema rather than copied, so a fix there reaches
 * this template too.
 *
 * @return array Section key => section definition.
 */
function hw_hub_country_states_schema() {
	$out = array();

	foreach ( hw_hub_country_schema() as $key => $section ) {
		if ( 'cities' === $key ) {
			$out['states'] = array(
				'label'  => __( 'States', 'hostelworld' ),
				'fields' => array(
					'states_title'  => array( 'label' => 'Heading', 'type' => 'text' ),
					'states_intro'  => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
					'state_regions' => array(
						'label' => 'Regions',
						'type'  => 'list',
						'cols'  => array( 'name', 'intro' ),
						'rows'  => 6,
						'help'  => 'Region | one line shown beside its heading. This sets the order the regions appear in. A region typed against a state but missing here still shows, after these. Leave the whole box empty and the states are shown as one grid with no headings.',
					),
					'states'        => array(
						'label'      => 'States',
						'type'       => 'rows',
						'cols'       => array( 'name', 'region', 'blurb', 'guide', 'image', 'review_city', 'quote', 'itineraries' ),
						'labels'     => array(
							'name'        => 'State',
							'region'      => 'Region',
							'blurb'       => 'One line on it',
							'guide'       => 'Guide link',
							'image'       => 'Photo',
							'review_city' => 'Reviews from (Hostelworld city)',
							'quote'       => 'Traveller quote (optional)',
							'itineraries' => 'Itineraries (optional)',
						),
						'pairs'      => array( 'itineraries' ),
						'quotes'     => array( 'quote' ),
						'quote_city' => 'review_city',
						'add'        => 'Add a state',
						'help'       => 'Region must match one of the regions above, spelled the same. Guide link takes a full URL, or leave it empty and the card goes to this page\'s address plus the state name, which is where the state page will live (/usa/california/). Hostelworld has no reviews for a state as a whole, so name a city in it under "Reviews from" — Los Angeles for California — update the page, and that city\'s traveller reviews appear in the dropdown. The quote shown is only ever the one you pick or write.',
					),
				),
			);
			continue;
		}

		if ( 'trips' === $key && isset( $section['fields']['trip_types']['labels']['cities'] ) ) {
			$section['fields']['trip_types']['labels']['cities'] = 'Best states for it (separate with ;)';
		}

		if ( 'hero' === $key && isset( $section['fields']['hero_cta'] ) ) {
			$section['fields']['hero_cta']['help'] = 'Label | link. Links can be #states, a region such as #states-west, a full URL, or tags:usa+food.';
		}

		if ( 'hostels' === $key && isset( $section['fields']['hostels_location_id'] ) ) {
			$section['fields']['hostels_location_id']['help'] = 'The country ID from Hostelworld\'s own API. The USA is 222.';
		}

		if ( 'settings' === $key && isset( $section['fields']['hub_term'] ) ) {
			$section['fields']['hub_term']['help'] = 'The tag articles carry for this country, e.g. usa. Every carousel on the page is this tag plus the section\'s own tag.';
		}

		$out[ $key ] = $section;
	}

	return $out;
}

/**
 * Fields for one standard content section on a state hub.
 *
 * @param string $key     Field prefix.
 * @param string $subject Example tag, for the help text.
 * @return array
 */
function hw_hub_state_section_fields( $key, $subject ) {
	return array(
		$key . '_title'  => array( 'label' => 'Heading', 'type' => 'text' ),
		$key . '_body'   => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 10, 'help' => 'Blank line between paragraphs. Leave the heading and the copy empty and the whole section is left off the page.' ),
		$key . '_points' => array( 'label' => 'Practical points', 'type' => 'list', 'cols' => array( 'label', 'detail' ), 'rows' => 6, 'help' => 'Short heading | the detail. Shown beside the copy. Leave empty to hide the panel.' ),
		$key . '_tag'    => array( 'label' => 'Article tag', 'type' => 'text', 'help' => 'Topic tag for the carousel, e.g. ' . $subject . '. The state tag is added automatically. Leave empty and this section has no carousel.' ),
		$key . '_more'   => array( 'label' => '"See all" text', 'type' => 'text' ),
	);
}

/**
 * @return array Section key => section definition.
 */
function hw_hub_state_schema() {

	return array(

		'hero'        => array(
			'label'  => __( 'Hero', 'hostelworld' ),
			'fields' => array(
				'hero_eyebrow' => array( 'label' => 'Eyebrow', 'type' => 'text' ),
				'hero_h1'      => array( 'label' => 'Heading (H1)', 'type' => 'text', 'help' => 'Leave empty to use the Page title.' ),
				'hero_intro'   => array( 'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 3 ),
				'hero_image'   => array( 'label' => 'Hero image', 'type' => 'media' ),
				'hero_overlay' => array(
					'label'   => 'Darkening over the hero image',
					'type'    => 'select',
					'options' => array(
						'light'  => 'Light — photo stays bright',
						'medium' => 'Medium',
						'strong' => 'Strong — for busy or pale photos',
					),
				),
				'hero_cta'     => array( 'label' => 'Hero buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3, 'help' => 'Label | link. Links can be #cities, #regions, a full URL, or tags:california+food.' ),
				'live_stats'   => array( 'label' => 'Use live figures in the hero chips', 'type' => 'check', 'help' => 'On: how many hostels the main cities have between them, and their average rating with its review count, straight from Hostelworld. Only the cities listed under Main cities are counted, so the label says so. Off: only the chips you write below.' ),
				'stats'        => array( 'label' => 'Extra stat chips', 'type' => 'list', 'cols' => array( 'value', 'label' ), 'rows' => 4, 'help' => 'Number | label. Added after the live ones.' ),
			),
		),

		'glance'      => array(
			'label'  => __( 'At a glance', 'hostelworld' ),
			'fields' => array(
				'intro_title' => array( 'label' => 'Overview heading', 'type' => 'text' ),
				'intro_body'  => array( 'label' => 'Overview copy', 'type' => 'textarea', 'rows' => 14, 'help' => 'The opening description. Blank line between paragraphs. Anything written in the main editor above is used instead.' ),
				'facts'       => array(
					'label' => 'Facts panel',
					'type'  => 'list',
					'cols'  => array( 'label', 'value' ),
					'rows'  => 12,
					'help'  => 'Label | value. State-level practicalities: main airports, sales tax, driving, time zone, how long to stay.',
				),
			),
		),

		'areas'       => array(
			'label'  => __( 'Regions', 'hostelworld' ),
			'fields' => array(
				'areas_title'  => array( 'label' => 'Heading', 'type' => 'text' ),
				'areas_intro'  => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'areas_zoom'   => array( 'label' => 'Map zoom', 'type' => 'text', 'help' => 'Only used in the "map" layout (set under Main cities). How close the map is allowed to start: 6 fits most states, 5 suits Texas or Alaska, 7 or 8 a small state like Rhode Island.' ),
				'hostels_pins' => array( 'label' => 'Show hostels on the map', 'type' => 'check', 'help' => 'Only used in the "map" layout. Plots the top-rated hostels from the main cities as dots, each linking to its property page.' ),
				'areas'        => array(
					'label'  => 'Regions',
					'type'   => 'rows',
					'cols'   => array( 'name', 'suits', 'blurb', 'lat', 'lng', 'link' ),
					'labels' => array(
						'name'  => 'Region',
						'suits' => 'Who it suits',
						'blurb' => 'What it is like',
						'lat'   => 'Latitude',
						'lng'   => 'Longitude',
						'link'  => 'Link (optional)',
					),
					'add'    => 'Add a region',
					'help'   => 'Shown as cards. Coordinates are only needed for the "map" layout, where they put the region\'s label on the map — right-click roughly the middle of the region in Google Maps and it copies them. Link is optional: a Hostelworld search or an article about that region.',
				),
			),
		),

		'itineraries' => array(
			'label'  => __( 'Itineraries', 'hostelworld' ),
			'fields' => array(
				'itin_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'itin_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'itin'       => array(
					'label'  => 'Itineraries',
					'type'   => 'rows',
					'cols'   => array( 'label', 'blurb', 'link', 'image' ),
					'labels' => array( 'label' => 'How long', 'blurb' => 'One line on it', 'link' => 'Article URL', 'image' => 'Photo' ),
					'add'    => 'Add an itinerary',
					'help'   => 'A row with no URL is hidden from readers.',
				),
			),
		),

		'do'          => array( 'label' => __( 'Things to do', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'do', 'things-to-do' ) ),
		'free'        => array( 'label' => __( 'Free things to do', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'free', 'free' ) ),
		'food'        => array( 'label' => __( 'Food and drink', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'food', 'food' ) ),
		'nightlife'   => array( 'label' => __( 'Nightlife', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'nightlife', 'nightlife' ) ),
		'transport'   => array( 'label' => __( 'Getting around', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'transport', 'transport' ) ),
		'money'       => array( 'label' => __( 'Costs', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'money', 'budget' ) ),
		'safety'      => array( 'label' => __( 'Safety and scams', 'hostelworld' ), 'fields' => hw_hub_state_section_fields( 'safety', 'safety' ) ),

		'cities'      => array(
			'label'  => __( 'Main cities', 'hostelworld' ),
			'fields' => array(
				'layout'       => array(
					'label'   => 'Page layout',
					'type'    => 'select',
					'options' => array(
						'cities' => 'No map: main cities straight after the overview',
						'map'    => 'A map after the overview, main cities directly under it',
					),
					'help'    => 'No map: the main cities come straight after the overview. Map: after the overview, a full-width map of the state, with the regions and main cities labelled on it, and the city cards come directly under the map.',
				),
				'cities_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'cities_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'cities_live'  => array( 'label' => 'Add live figures to the city cards', 'type' => 'check', 'help' => 'How many hostels each city has and its cheapest dorm among the top-rated ones, from Hostelworld.' ),
				'cities'       => array(
					'label'  => 'Main cities',
					'type'   => 'rows',
					'cols'   => array( 'name', 'hw_city', 'blurb', 'poi', 'image' ),
					'labels' => array(
						'name'    => 'City',
						'hw_city' => 'Hostelworld city name (if different)',
						'blurb'   => 'What it is like',
						'poi'     => 'Points of interest (separate with ;)',
						'image'   => 'Photo',
					),
					'add'    => 'Add a city',
					'help'   => 'They do not link anywhere. The cities here also feed the live hostels, events, hero figures and map, so list the ones travellers actually base themselves in. Leave the Hostelworld name empty when it is the same as the city — fill it only when Hostelworld spells it differently (e.g. new-york for New York City).',
				),
				'cities_tag'   => array( 'label' => 'Article tag', 'type' => 'text', 'help' => 'Tag for the carousel under this section, e.g. city-guides. The state tag is added automatically. Leave empty for no carousel.' ),
				'cities_more'  => array( 'label' => '"See all" text', 'type' => 'text' ),
			),
		),

		'season'      => array(
			'label'  => __( 'When to go', 'hostelworld' ),
			'fields' => array(
				'season_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'season_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'season_note'  => array( 'label' => 'Footnote', 'type' => 'textarea', 'rows' => 2 ),
				'season_rows'  => array(
					'label' => 'Month by month',
					'type'  => 'list',
					'cols'  => array( 'month', 'high', 'crowd', 'note' ),
					'rows'  => 13,
					'help'  => 'Month | average high | how busy, 1 to 5 | a few words. Both numbers must be numbers — the chart is drawn from them. Use the same unit throughout.',
				),
			),
		),

		'events'      => array(
			'label'  => __( "What's on", 'hostelworld' ),
			'fields' => array(
				'events_title'     => array( 'label' => 'Heading', 'type' => 'text' ),
				'events_intro'     => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'events_source'    => array(
					'label'   => 'Where the events come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, across the main cities',
						'manual' => 'Only the ones I list below',
					),
				),
				'events_count'     => array( 'label' => 'How many to show', 'type' => 'text' ),
				'events_cache'     => array( 'label' => 'Refresh every (hours)', 'type' => 'text' ),
				'events_cta_label' => array( 'label' => 'Button text', 'type' => 'text' ),
				'events_cta_url'   => array( 'label' => 'Button URL', 'type' => 'text' ),
				'events'           => array(
					'label'  => 'Fallback events',
					'type'   => 'rows',
					'cols'   => array( 'name', 'dates', 'place', 'blurb', 'image', 'href' ),
					'labels' => array( 'name' => 'Event', 'dates' => 'When', 'place' => 'Where', 'blurb' => 'One line on it', 'image' => 'Photo (optional)', 'href' => 'Link' ),
					'add'    => 'Add an event',
					'help'   => 'Used only if the live feed is off or returns nothing.',
				),
			),
		),

		'budget'      => array(
			'label'  => __( 'Budget calculator', 'hostelworld' ),
			'fields' => array(
				'budget_title'    => array( 'label' => 'Heading', 'type' => 'text' ),
				'budget_intro'    => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'budget_note'     => array( 'label' => 'Footnote', 'type' => 'textarea', 'rows' => 2 ),
				'budget_rows'     => array(
					'label' => 'Styles of trip',
					'type'  => 'list',
					'cols'  => array( 'style', 'bed', 'food', 'transport', 'doing' ),
					'rows'  => 5,
					'help'  => 'Style | bed | food | local transport | doing things. Per night, in whole units.',
				),
				'budget_currency' => array( 'label' => 'Currency symbol', 'type' => 'text' ),
			),
		),

		'hostels'     => array(
			'label'  => __( 'Where to stay', 'hostelworld' ),
			'fields' => array(
				'hostels_title'     => array( 'label' => 'Heading', 'type' => 'text' ),
				'hostels_intro'     => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'hostels_cta_label' => array( 'label' => 'Button text', 'type' => 'text' ),
				'hostels_cta_url'   => array( 'label' => 'Button URL', 'type' => 'text' ),
				'hostels_source'    => array(
					'label'   => 'Where the hostels come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, the best-rated across the main cities',
						'manual' => 'Only the ones I list below',
					),
				),
				'hostels_count'     => array( 'label' => 'How many to show', 'type' => 'text' ),
				'hostels_cache'     => array( 'label' => 'Refresh every (hours)', 'type' => 'text' ),
				'picks'             => array(
					'label'  => 'Editorial picks',
					'type'   => 'rows',
					'cols'   => array( 'label', 'name', 'why', 'link' ),
					'labels' => array( 'label' => 'Best for', 'name' => 'Hostel', 'why' => 'Why', 'link' => 'Hostelworld URL' ),
					'add'    => 'Add a pick',
					'help'   => 'Your own recommendations, shown above the live list.',
				),
				'hostels'           => array(
					'label'  => 'Fallback hostels',
					'type'   => 'rows',
					'cols'   => array( 'name', 'place', 'note', 'image', 'href' ),
					'labels' => array( 'name' => 'Hostel', 'place' => 'City', 'note' => 'One line on it', 'image' => 'Photo', 'href' => 'Hostelworld URL' ),
					'add'    => 'Add a hostel',
					'help'   => 'Used only if the live feed is off or unreachable.',
				),
			),
		),

		'faqs'        => array(
			'label'  => __( 'FAQs', 'hostelworld' ),
			'fields' => array(
				'faq_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'faq_note'  => array( 'label' => 'Note', 'type' => 'text' ),
				'faqs'      => array( 'label' => 'Questions', 'type' => 'list', 'cols' => array( 'q', 'a' ), 'rows' => 12, 'help' => 'Question | answer. These also generate the FAQ structured data. Leave empty and the section is left off.' ),
			),
		),

		'cta'         => array(
			'label'  => __( 'Closing CTA', 'hostelworld' ),
			'fields' => array(
				'cta_title'            => array( 'label' => 'Heading', 'type' => 'text' ),
				'cta_body'             => array( 'label' => 'Body', 'type' => 'textarea', 'rows' => 3 ),
				'cta_buttons'          => array( 'label' => 'Buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3 ),
				'newsletter_title'     => array( 'label' => 'Newsletter heading', 'type' => 'text' ),
				'newsletter_body'      => array( 'label' => 'Newsletter body', 'type' => 'text' ),
				'newsletter_shortcode' => array( 'label' => 'Signup form shortcode', 'type' => 'text' ),
			),
		),

		'settings'    => array(
			'label'  => __( 'Settings', 'hostelworld' ),
			'fields' => array(
				'hub_term'     => array( 'label' => 'This state\'s tag', 'type' => 'text', 'help' => 'The tag articles carry for this state, e.g. california. Every carousel is this tag plus the section\'s own tag.' ),
				'country_slug' => array( 'label' => 'Hostelworld country name', 'type' => 'text', 'help' => 'As it appears in a Hostelworld URL: usa. Used to look the main cities up.' ),
				'parent_hub'   => array( 'label' => 'Country hub', 'type' => 'text', 'help' => 'Page path of the country this sits under, e.g. north-america/usa. Leave empty to use the parent Page.' ),
				'currency'     => array( 'label' => 'Currency code', 'type' => 'text', 'help' => 'Three letters, e.g. USD. Used for the live prices.' ),
				'scope_rows'   => array( 'label' => 'Keep carousels inside this state', 'type' => 'check', 'help' => 'On: the state tag is added to every carousel, so a food carousel can only return this state\'s food articles.' ),
			),
		),
	);
}
