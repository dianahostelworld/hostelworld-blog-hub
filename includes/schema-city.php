<?php
/**
 * City hub field schema.
 *
 * The country hub's shape, with the city section replaced by a neighbourhood
 * guide and a map, and more room for copy throughout. Field types are the same
 * as the other templates (see schema.php).
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields for one standard content section: copy, points, then its carousel.
 *
 * @param string $key     Field prefix.
 * @param string $subject Example tag, for the help text.
 * @return array
 */
function hw_hub_city_section_fields( $key, $subject ) {
	return array(
		$key . '_title'  => array( 'label' => 'Heading', 'type' => 'text' ),
		$key . '_body'   => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 10, 'help' => 'Blank line between paragraphs. This is a city guide, so it should read like one — more detail than the country page, not less.' ),
		$key . '_points' => array( 'label' => 'Practical points', 'type' => 'list', 'cols' => array( 'label', 'detail' ), 'rows' => 6, 'help' => 'Short heading | the detail. Shown beside the copy. Leave empty to hide the panel.' ),
		$key . '_tag'    => array( 'label' => 'Article tag', 'type' => 'text', 'help' => 'Topic tag for the carousel, e.g. ' . $subject . '. The city tag is added automatically. Leave empty and this section has no carousel.' ),
		$key . '_more'   => array( 'label' => '"See all" text', 'type' => 'text' ),
	);
}

/**
 * @return array Section key => section definition.
 */
function hw_hub_city_schema() {

	return array(

		'hero'       => array(
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
				'hero_cta'     => array( 'label' => 'Hero buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3 ),
				'live_stats'   => array( 'label' => 'Use live figures in the hero chips', 'type' => 'check', 'help' => 'On: hostel count, average rating with its review count, and the cheapest dorm, straight from Hostelworld. They update on their own. Off: only the chips you write below.' ),
				'stats'        => array( 'label' => 'Extra stat chips', 'type' => 'list', 'cols' => array( 'value', 'label' ), 'rows' => 4, 'help' => 'Number | label. Added after the live ones.' ),
			),
		),

		'glance'     => array(
			'label'  => __( 'At a glance', 'hostelworld' ),
			'fields' => array(
				'intro_title' => array( 'label' => 'Overview heading', 'type' => 'text' ),
				'intro_body'  => array( 'label' => 'Overview copy', 'type' => 'textarea', 'rows' => 14, 'help' => 'The opening description. Blank line between paragraphs. Anything written in the main editor above is used instead.' ),
				'facts'       => array(
					'label' => 'Facts panel',
					'type'  => 'list',
					'cols'  => array( 'label', 'value' ),
					'rows'  => 12,
					'help'  => 'Label | value. City-level practicalities: airport transfer, transport card, tipping, tap water, emergency number, how long to stay.',
				),
			),
		),

		'areas'      => array(
			'label'  => __( 'Neighbourhoods', 'hostelworld' ),
			'fields' => array(
				'areas_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'areas_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'areas_map'   => array( 'label' => 'Show the map', 'type' => 'check', 'help' => 'An OpenStreetMap map with a marker per neighbourhood. No account or key needed. Neighbourhoods without coordinates simply do not get a marker.' ),
				'areas_zoom'  => array( 'label' => 'Map zoom', 'type' => 'text', 'help' => '13 shows most of a city, 14 is tighter, 12 is wider.' ),
				'hostels_pins' => array( 'label' => 'Show hostels on the map', 'type' => 'check', 'help' => 'Plots the top-rated hostels as dots, each linking to its property page. Their coordinates come from Hostelworld, so there is nothing to enter.' ),
				'areas_live'  => array( 'label' => 'Add live hostel counts and prices', 'type' => 'check', 'help' => 'Matches each neighbourhood to Hostelworld\'s own district data by the name in the API column.' ),
				'areas'       => array(
					'label'  => 'Neighbourhoods',
					'type'   => 'rows',
					'cols'   => array( 'name', 'suits', 'blurb', 'lat', 'lng', 'api', 'link' ),
					'labels' => array(
						'name'  => 'Neighbourhood',
						'suits' => 'Who it suits',
						'blurb' => 'What it is like',
						'lat'   => 'Latitude',
						'lng'   => 'Longitude',
						'api'   => 'Hostelworld district name',
						'link'  => 'Hostels link',
					),
					'add'    => 'Add a neighbourhood',
					'help'   => 'Coordinates put the marker on the map — right-click a spot in Google Maps and it copies them. The Hostelworld district name is what the live counts match on, and it has to be spelled as their data does (Barri Gotic, not Barri Gòtic). Leave it empty for a neighbourhood they do not list.',
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
					'help'   => 'A row with no URL is ignored. These are the highest-intent links on a city page, so they get cards rather than chips.',
				),
			),
		),

		'do'         => array( 'label' => __( 'Things to do', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'do', 'things-to-do' ) ),
		'free'       => array( 'label' => __( 'Free things to do', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'free', 'free' ) ),
		'food'       => array( 'label' => __( 'Food and drink', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'food', 'food' ) ),
		'nightlife'  => array( 'label' => __( 'Nightlife', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'nightlife', 'nightlife' ) ),
		'transport'  => array( 'label' => __( 'Getting around', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'transport', 'transport' ) ),
		'money'      => array( 'label' => __( 'Costs', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'money', 'budget' ) ),
		'safety'     => array( 'label' => __( 'Safety and scams', 'hostelworld' ), 'fields' => hw_hub_city_section_fields( 'safety', 'safety' ) ),

		'daytrips'   => array(
			'label'  => __( 'Day trips', 'hostelworld' ),
			'fields' => array(
				'trips_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'trips_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'trips'       => array(
					'label'  => 'Day trips',
					'type'   => 'rows',
					'cols'   => array( 'name', 'time', 'how', 'blurb', 'link', 'image' ),
					'labels' => array( 'name' => 'Where', 'time' => 'How long away', 'how' => 'How to get there', 'blurb' => 'Why go', 'link' => 'Link', 'image' => 'Photo' ),
					'add'    => 'Add a day trip',
				),
				'trips_tag'   => array( 'label' => 'Article tag', 'type' => 'text', 'help' => 'Tag for the carousel under this section, e.g. day-trips.' ),
				'trips_more'  => array( 'label' => '"See all" text', 'type' => 'text' ),
			),
		),

		'season'     => array(
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
					'help'  => 'Month | average high in °C | how busy, 1 to 5 | a few words. Both numbers must be numbers — the chart is drawn from them.',
				),
			),
		),

		'events'     => array(
			'label'  => __( "What's on", 'hostelworld' ),
			'fields' => array(
				'events_title'     => array( 'label' => 'Heading', 'type' => 'text' ),
				'events_intro'     => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'events_source'    => array(
					'label'   => 'Where the events come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, with this year\'s dates',
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
					'help'   => 'Used only if the live feed is off or unreachable. The photo is optional: leave it empty and the card shows a calendar icon instead.',
				),
			),
		),

		'budget'     => array(
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

		'hostels'    => array(
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
						'api'    => 'Live from Hostelworld, sorted by rating',
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
					'help'   => 'Your own recommendations, shown above the live list: best for solo travellers, best for a party, quietest.',
				),
				'hostels'           => array(
					'label'  => 'Fallback hostels',
					'type'   => 'rows',
					'cols'   => array( 'name', 'place', 'note', 'image', 'href' ),
					'labels' => array( 'name' => 'Hostel', 'place' => 'Area', 'note' => 'One line on it', 'image' => 'Photo', 'href' => 'Hostelworld URL' ),
					'add'    => 'Add a hostel',
					'help'   => 'Used only if the live feed is off or unreachable.',
				),
			),
		),

		'faqs'       => array(
			'label'  => __( 'FAQs', 'hostelworld' ),
			'fields' => array(
				'faq_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'faq_note'  => array( 'label' => 'Note', 'type' => 'text' ),
				'faqs'      => array( 'label' => 'Questions', 'type' => 'list', 'cols' => array( 'q', 'a' ), 'rows' => 12, 'help' => 'Question | answer. These also generate the FAQ structured data.' ),
			),
		),

		'cta'        => array(
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

		'settings'   => array(
			'label'  => __( 'Settings', 'hostelworld' ),
			'fields' => array(
				'hub_term'     => array( 'label' => 'This city\'s tag', 'type' => 'text', 'help' => 'The tag articles carry for this city, e.g. barcelona. Every carousel is this tag plus the section\'s own tag.' ),
				'city_slug'    => array( 'label' => 'Hostelworld city name', 'type' => 'text', 'help' => 'As it appears in a Hostelworld URL, e.g. barcelona. Used for the live hostel, district, event and review data.' ),
				'country_slug' => array( 'label' => 'Hostelworld country name', 'type' => 'text', 'help' => 'e.g. spain. Needed to look the city up.' ),
				'parent_hub'   => array( 'label' => 'Country hub', 'type' => 'text', 'help' => 'Page path of the country this sits under, e.g. europe/spain.' ),
				'currency'     => array( 'label' => 'Currency code', 'type' => 'text', 'help' => 'Three letters, e.g. EUR. Used for the live prices.' ),
				'scope_rows'   => array( 'label' => 'Keep carousels inside this city', 'type' => 'check', 'help' => 'On: the city tag is added to every carousel, so a food carousel can only return this city\'s food articles.' ),
			),
		),
	);
}
