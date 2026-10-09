<?php
/**
 * Country hub field schema.
 *
 * Same field types as the continent hub (see schema.php). The difference is
 * what a country page is for: a continent hub is a routing page, this one is
 * meant to be read. So most sections are a heading, real body copy, a short
 * list of practical facts, and a carousel of articles tagged with this country
 * plus that section's topic.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields for one standard content section: copy, then its article carousel.
 *
 * @param string $key     Field prefix.
 * @param string $label   Section label in the panel.
 * @param string $subject What the carousel pulls, for the help text.
 * @return array
 */
function hw_hub_country_section_fields( $key, $label, $subject ) {
	return array(
		$key . '_title'  => array( 'label' => 'Heading', 'type' => 'text' ),
		$key . '_body'   => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 10, 'help' => 'Blank line between paragraphs. This is the part people actually read — write it properly rather than listing keywords.' ),
		$key . '_points' => array( 'label' => 'Practical points', 'type' => 'list', 'cols' => array( 'label', 'detail' ), 'rows' => 6, 'help' => 'Short heading | the detail. Shown as a panel beside the copy. Leave empty to hide it.' ),
		$key . '_tag'    => array( 'label' => 'Article tag', 'type' => 'text', 'help' => 'Topic tag for the carousel under this section, e.g. ' . $subject . '. The country tag is added automatically, so only posts about this country show. Leave empty and the section has no carousel at all.' ),
		$key . '_more'   => array( 'label' => '"See all" text', 'type' => 'text' ),
	);
}

/**
 * @return array Section key => section definition.
 */
function hw_hub_country_schema() {

	return array(

		'hero'      => array(
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
				'hero_cta'     => array( 'label' => 'Hero buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3, 'help' => 'Label | link. Links can be #cities, a full URL, or tags:spain+food.' ),
				'stats'        => array( 'label' => 'Stat chips on the hero', 'type' => 'list', 'cols' => array( 'value', 'label' ), 'rows' => 5, 'help' => 'Number | label. The label has to stand alone. Four fit across.' ),
			),
		),

		'overview'  => array(
			'label'  => __( 'Overview', 'hostelworld' ),
			'fields' => array(
				'intro_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'intro_body'  => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 12, 'help' => 'The opening description of the country. Blank line between paragraphs. Anything written in the main editor above is used instead of this.' ),
				'facts'       => array(
					'label'  => 'Country facts',
					'type'   => 'list',
					'cols'   => array( 'label', 'value' ),
					'rows'   => 10,
					'help'   => 'Label | value, e.g. Currency | Euro (€). Shown as a facts panel. Check anything that changes — visa rules especially.',
				),
			),
		),

		'cities'    => array(
			'label'  => __( 'Cities', 'hostelworld' ),
			'fields' => array(
				'cities_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'cities_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'cities_live'  => array(
					'label'   => 'Add live data to the city cards',
					'type'    => 'check',
					'help'    => 'Shows how many hostels each city has, the cheapest dorm among its top-rated ones, and a recent traveller quote. All from Hostelworld, refreshed daily.',
				),
				'cities'       => array(
					'label'  => 'Cities',
					'type'   => 'rows',
					'cols'   => array( 'name', 'blurb', 'guide', 'image', 'quote', 'itineraries' ),
					'labels' => array( 'name' => 'City', 'blurb' => 'One line on it', 'guide' => 'Guide link', 'image' => 'Photo', 'quote' => 'Traveller quote (optional)', 'itineraries' => 'Itineraries (optional)' ),
					'pairs'  => array( 'itineraries' ),
					'quotes' => array( 'quote' ),
					'add'    => 'Add a city',
					'help'   => 'Guide link takes a full URL, or leave it empty and the card goes to this page\'s address plus the city name, which is where the city page will live. Pick a traveller review from the dropdown, or write your own. Leave it empty and the best-scoring review is used automatically, or nothing if none are good enough. Add as many itinerary links as that city has — one row each, a label like "24 hours" or "3 days" and the article URL. A row with no URL is ignored, and a city with none shows no itinerary links at all.',
				),
			),
		),

		'trips'     => array(
			'label'  => __( 'Types of trip', 'hostelworld' ),
			'fields' => array(
				'trips_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'trips_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'trip_types'  => array(
					'label'  => 'Trip types',
					'type'   => 'rows',
					'cols'   => array( 'name', 'colour', 'icon', 'summary', 'doing', 'spots', 'cities', 'tag' ),
					'labels' => array(
						'name'    => 'Trip type',
						'colour'  => 'Panel colour',
						'icon'    => 'Icon',
						'summary' => 'Opening line',
						'doing'   => 'Things to do (separate with ;)',
						'spots'   => 'Spots to visit (separate with ;)',
						'cities'  => 'Best cities for it (separate with ;)',
						'tag'     => 'Article tag',
					),
					'add'    => 'Add a trip type',
					'help'   => 'Each one opens its own panel when clicked. Colours: blue, green, purple, pink, teal, sunset, orange. Icon names are listed at the bottom of the Settings tab.',
				),
			),
		),

		'do'        => array(
			'label'  => __( 'Things to do', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'do', 'Things to do', 'things-to-do' ),
		),

		'food'      => array(
			'label'  => __( 'Food and drink', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'food', 'Food and drink', 'food' ),
		),

		'nightlife' => array(
			'label'  => __( 'Nightlife', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'nightlife', 'Nightlife', 'nightlife' ),
		),

		'transport' => array(
			'label'  => __( 'Getting around', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'transport', 'Getting around', 'transport' ),
		),

		'money'     => array(
			'label'  => __( 'Costs', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'money', 'Costs', 'budget' ),
		),

		'safety'    => array(
			'label'  => __( 'Safety', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'safety', 'Safety', 'safety' ),
		),

		'entry'     => array(
			'label'  => __( 'Entry and documents', 'hostelworld' ),
			'fields' => hw_hub_country_section_fields( 'entry', 'Entry and documents', 'travel-tips' ),
		),

		'season'    => array(
			'label'  => __( 'When to go', 'hostelworld' ),
			'fields' => array(
				'season_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'season_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'season_note'  => array( 'label' => 'Footnote', 'type' => 'textarea', 'rows' => 2, 'help' => 'Where the numbers come from, and how rough they are.' ),
				'season_rows'  => array(
					'label' => 'Month by month',
					'type'  => 'list',
					'cols'  => array( 'month', 'high', 'crowd', 'note' ),
					'rows'  => 13,
					'help'  => 'Month | average high in °C | how busy, 1 to 5 | a few words. The chart is drawn from the temperature and the crowd number, so both need to be numbers.',
				),
			),
		),

		'budget'    => array(
			'label'  => __( 'Budget calculator', 'hostelworld' ),
			'fields' => array(
				'budget_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'budget_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'budget_note'  => array( 'label' => 'Footnote', 'type' => 'textarea', 'rows' => 2 ),
				'budget_rows'  => array(
					'label' => 'Styles of trip',
					'type'  => 'list',
					'cols'  => array( 'style', 'bed', 'food', 'transport', 'doing' ),
					'rows'  => 5,
					'help'  => 'Style | bed | food | local transport | doing things. Four numbers per night, in whole currency units. The reader picks a style and a number of nights, and the total is worked out in the browser.',
				),
				'budget_currency' => array( 'label' => 'Currency symbol', 'type' => 'text' ),
			),
		),

		'events'    => array(
			'label'  => __( "What's on", 'hostelworld' ),
			'fields' => array(
				'events_title'     => array( 'label' => 'Heading', 'type' => 'text' ),
				'events_intro'     => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 3 ),
				'events_source'    => array(
					'label'   => 'Where the events come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, with this year\'s dates',
						'manual' => 'Only the ones I list below',
					),
					'help'    => 'Live gives real dates for the current cycle rather than "every August", and drops anything that has already finished. Staff picks come first.',
				),
				'events_country'   => array( 'label' => 'Country name for the feed', 'type' => 'text', 'help' => 'As it appears in a Hostelworld URL, e.g. spain.' ),
				'events_count'     => array( 'label' => 'How many to show', 'type' => 'text' ),
				'events_cache'     => array( 'label' => 'Refresh every (hours)', 'type' => 'text' ),
				'events_cta_label' => array( 'label' => 'Button text', 'type' => 'text' ),
				'events_cta_url'   => array( 'label' => 'Button URL', 'type' => 'text', 'help' => 'The events page for this country, e.g. https://www.hostelworld.com/events/europe/spain/' ),
				'events'           => array(
					'label'  => 'Fallback events',
					'type'   => 'rows',
					'cols'   => array( 'name', 'dates', 'place', 'blurb', 'image', 'href' ),
					'labels' => array( 'name' => 'Event', 'dates' => 'When', 'place' => 'City', 'blurb' => 'One line on it', 'image' => 'Photo (optional)', 'href' => 'Link' ),
					'add'    => 'Add an event',
					'help'   => 'Used only if the live feed is off or unreachable. Write dates loosely here (\'Late September\'), since these are not year-specific.',
				),
			),
		),

		'hostels'   => array(
			'label'  => __( 'Hostels', 'hostelworld' ),
			'fields' => array(
				'hostels_title'         => array( 'label' => 'Heading', 'type' => 'text' ),
				'hostels_intro'         => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'hostels_cta_label'     => array( 'label' => 'Button text', 'type' => 'text' ),
				'hostels_cta_url'       => array( 'label' => 'Button URL', 'type' => 'text' ),
				'hostels_source'        => array(
					'label'   => 'Where the hostels come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, sorted by rating',
						'manual' => 'Only the ones I list below',
					),
				),
				'hostels_location_type' => array(
					'label'   => 'Location type',
					'type'    => 'select',
					'options' => array(
						'country'   => 'Country',
						'region'    => 'Region',
						'area'      => 'Area',
						'continent' => 'Continent',
					),
				),
				'hostels_location_id'   => array( 'label' => 'Location ID', 'type' => 'text', 'help' => 'The country ID from Hostelworld\'s own API. Spain is 196, Portugal 170, Italy 105, France 73, Germany 80, Greece 83, Croatia 53, Czech Republic 56. Ask me for any other.' ),
				'hostels_currency'      => array( 'label' => 'Currency', 'type' => 'text' ),
				'hostels_count'         => array( 'label' => 'How many to show', 'type' => 'text' ),
				'hostels_cache'         => array( 'label' => 'Refresh every (hours)', 'type' => 'text' ),
				'hostels'               => array(
					'label'  => 'Fallback hostels',
					'type'   => 'rows',
					'cols'   => array( 'name', 'place', 'note', 'image', 'href' ),
					'labels' => array( 'name' => 'Hostel', 'place' => 'City', 'note' => 'One line on it', 'image' => 'Photo', 'href' => 'Hostelworld URL' ),
					'add'    => 'Add a hostel',
					'help'   => 'Used only if the live feed is off or unreachable.',
				),
			),
		),

		'faqs'      => array(
			'label'  => __( 'FAQs', 'hostelworld' ),
			'fields' => array(
				'faq_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'faq_note'  => array( 'label' => 'Note', 'type' => 'text' ),
				'faqs'      => array( 'label' => 'Questions', 'type' => 'list', 'cols' => array( 'q', 'a' ), 'rows' => 10, 'help' => 'Question | answer, one per line. These also generate the FAQ structured data, so keep the answers accurate.' ),
			),
		),

		'cta'       => array(
			'label'  => __( 'Closing CTA', 'hostelworld' ),
			'fields' => array(
				'cta_title'            => array( 'label' => 'Heading', 'type' => 'text' ),
				'cta_body'             => array( 'label' => 'Body', 'type' => 'textarea', 'rows' => 3 ),
				'cta_buttons'          => array( 'label' => 'Buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3 ),
				'newsletter_title'     => array( 'label' => 'Newsletter heading', 'type' => 'text' ),
				'newsletter_body'      => array( 'label' => 'Newsletter body', 'type' => 'text' ),
				'newsletter_shortcode' => array( 'label' => 'Signup form shortcode', 'type' => 'text', 'help' => 'Leave empty and the newsletter box is hidden.' ),
			),
		),

		'settings'  => array(
			'label'  => __( 'Settings', 'hostelworld' ),
			'fields' => array(
				'hub_term'   => array( 'label' => 'This country\'s tag', 'type' => 'text', 'help' => 'The tag articles carry for this country, e.g. spain. Every carousel on the page is this tag plus the section\'s own tag.' ),
				'parent_hub' => array( 'label' => 'Continent hub', 'type' => 'text', 'help' => 'Page path of the continent this sits under, e.g. europe. Used for the "back to" link.' ),
				'scope_rows' => array( 'label' => 'Keep carousels inside this country', 'type' => 'check', 'help' => 'On: the country tag is added to every carousel, so a food carousel can only return this country\'s food articles.' ),
			),
		),
	);
}
