<?php
/**
 * Starting content for the Country Hub (states) and State Hub templates.
 *
 * Unlike the older templates, these do not ship a worked example: a page that
 * goes live before its import would otherwise show another place's copy. Only
 * settings, headings and the list of states start filled in. Everything a
 * reader would take as a fact about the place is left for the editor.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array Flat map of field key => default value.
 */
function hw_hub_country_states_defaults() {
	return array(

		/* Settings */
		'hub_term'              => 'usa',
		'parent_hub'            => '',
		'scope_rows'            => '1',

		/* Hero */
		'hero_eyebrow'          => 'Country guide',
		'hero_intro'            => '',
		'hero_image'            => '',
		'hero_overlay'          => 'light',
		'hero_cta'              => array(
			array( 'label' => 'Pick a state', 'href' => '#states' ),
			array( 'label' => 'What it costs', 'href' => '#money' ),
		),
		'stats'                 => array(),

		/* Overview */
		'intro_title'           => '',
		'intro_body'            => '',
		'facts'                 => array(),

		/* States — the four US Census Bureau regions, in the Bureau's order. */
		'states_title'          => 'Pick a state',
		'states_intro'          => '',
		'state_regions'         => array(
			array( 'name' => 'Northeast', 'intro' => '' ),
			array( 'name' => 'Midwest', 'intro' => '' ),
			array( 'name' => 'South', 'intro' => '' ),
			array( 'name' => 'West', 'intro' => '' ),
		),
		'states'                => array(
			array( 'name' => 'Connecticut', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Maine', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Massachusetts', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'New Hampshire', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'New Jersey', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'New York', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Pennsylvania', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Rhode Island', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Vermont', 'region' => 'Northeast', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Illinois', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Indiana', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Iowa', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Kansas', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Michigan', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Minnesota', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Missouri', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Nebraska', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'North Dakota', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Ohio', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'South Dakota', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Wisconsin', 'region' => 'Midwest', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Alabama', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Arkansas', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Delaware', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Florida', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Georgia', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Kentucky', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Louisiana', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Maryland', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Mississippi', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'North Carolina', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Oklahoma', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'South Carolina', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Tennessee', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Texas', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Virginia', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'West Virginia', 'region' => 'South', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Alaska', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Arizona', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'California', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Colorado', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Hawaii', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Idaho', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Montana', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Nevada', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'New Mexico', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Oregon', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Utah', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Washington', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Wyoming', 'region' => 'West', 'blurb' => '', 'guide' => '', 'image' => '', 'review_city' => '', 'quote' => '', 'itineraries' => '' ),
		),

		/* Types of trip */
		'trips_title'           => 'Types of trip',
		'trips_intro'           => '',
		'trip_types'            => array(),

		/* Sections: tags only, so the carousels work as soon as there is copy. */
		'do_tag'                => 'things-to-do',
		'do_more'               => 'All things to do in the USA',
		'food_tag'              => 'food',
		'food_more'             => 'All food guides for the USA',
		'nightlife_tag'         => 'nightlife',
		'nightlife_more'        => 'All nightlife guides for the USA',
		'transport_tag'         => 'transport',
		'transport_more'        => 'All transport guides for the USA',
		'money_tag'             => 'budget',
		'money_more'            => 'All budget guides for the USA',
		'safety_tag'            => 'safety',
		'safety_more'           => 'All safety guides for the USA',
		'entry_tag'             => '',
		'entry_more'            => '',

		/* When to go */
		'season_title'          => 'When to go to the USA',
		'season_intro'          => '',
		'season_rows'           => array(),
		'season_note'           => '',

		/* Budget */
		'budget_title'          => 'What a trip to the USA costs',
		'budget_intro'          => '',
		'budget_rows'           => array(),
		'budget_currency'       => '$',
		'budget_note'           => '',

		/* What's on */
		'events_title'          => "What's on in the USA",
		'events_intro'          => '',
		'events_source'         => 'api',
		'events_country'        => 'usa',
		'events_count'          => '8',
		'events_cache'          => '24',
		'events_cta_label'      => 'All events in the USA',
		'events_cta_url'        => 'https://www.hostelworld.com/events/north-america/usa/',
		'events'                => array(),

		/* Hostels */
		'hostels_title'         => 'The best-rated hostels in the USA',
		'hostels_intro'         => '',
		'hostels_cta_label'     => 'All hostels in the USA',
		'hostels_cta_url'       => 'https://www.hostelworld.com/hostels/North-America/USA',
		'hostels_source'        => 'api',
		'hostels_location_type' => 'country',
		'hostels_location_id'   => '222',
		'hostels_currency'      => 'USD',
		'hostels_count'         => '12',
		'hostels_cache'         => '12',
		'hostels'               => array(),

		/* FAQs */
		'faq_title'             => 'USA travel questions',
		'faq_note'              => 'The ones we get asked most.',
		'faqs'                  => array(),

		/* Closing CTA */
		'cta_title'             => 'Say yes to hostels, meet the world',
		'cta_body'              => 'Compare hostels across the USA — live prices, real reviews and free cancellation on most bookings. Then meet the people already there in the app.',
		'cta_buttons'           => array(
			array( 'label' => 'Find hostels in the USA', 'href' => 'https://www.hostelworld.com/hostels/North-America/USA' ),
			array( 'label' => 'Get the app', 'href' => 'https://hostelworld.onelink.me/IdiK/f5a50nhd' ),
		),
		'newsletter_title'      => 'Get the good stuff monthly',
		'newsletter_body'       => 'New city guides, deals and route ideas. No filler.',
		'newsletter_shortcode'  => '',
	);
}

/**
 * State hub defaults. Headings that would name the state are left empty: the
 * template fills them from the Page title, so they are right on every state.
 *
 * @return array Flat map of field key => default value.
 */
function hw_hub_state_defaults() {
	return array(

		/* Settings */
		'hub_term'             => '',
		'country_slug'         => 'usa',
		'parent_hub'           => '',
		'currency'             => 'USD',
		'scope_rows'           => '1',

		/* Hero */
		'hero_eyebrow'         => 'State guide',
		'hero_intro'           => '',
		'hero_image'           => '',
		'hero_overlay'         => 'light',
		'hero_cta'             => array(
			array( 'label' => 'See the main cities', 'href' => '#cities' ),
			array( 'label' => 'What it costs', 'href' => '#money' ),
		),
		'live_stats'           => '1',
		'stats'                => array(),

		/* At a glance */
		'intro_title'          => '',
		'intro_body'           => '',
		'facts'                => array(),

		/* Regions */
		'areas_title'          => '',
		'areas_intro'          => '',
		'areas_zoom'           => '6',
		'hostels_pins'         => '1',
		'areas'                => array(),

		/* Itineraries */
		'itin_title'           => '',
		'itin_intro'           => '',
		'itin'                 => array(),

		/* Sections: tags only. */
		'do_tag'               => 'things-to-do',
		'free_tag'             => 'free',
		'food_tag'             => 'food',
		'nightlife_tag'        => 'nightlife',
		'transport_tag'        => 'transport',
		'money_tag'            => 'budget',
		'safety_tag'           => 'safety',

		/* Main cities */
		'layout'               => 'cities',
		'cities_title'         => '',
		'cities_intro'         => '',
		'cities_live'          => '1',
		'cities'               => array(),
		'cities_tag'           => '',
		'cities_more'          => '',

		/* When to go */
		'season_title'         => '',
		'season_intro'         => '',
		'season_rows'          => array(),
		'season_note'          => '',

		/* What's on */
		'events_title'         => '',
		'events_intro'         => '',
		'events_source'        => 'api',
		'events_count'         => '8',
		'events_cache'         => '24',
		'events_cta_label'     => '',
		'events_cta_url'       => '',
		'events'               => array(),

		/* Budget */
		'budget_title'         => '',
		'budget_intro'         => '',
		'budget_rows'          => array(),
		'budget_currency'      => '$',
		'budget_note'          => '',

		/* Where to stay */
		'hostels_title'        => '',
		'hostels_intro'        => '',
		'hostels_cta_label'    => '',
		'hostels_cta_url'      => '',
		'hostels_source'       => 'api',
		'hostels_count'        => '12',
		'hostels_cache'        => '12',
		'picks'                => array(),
		'hostels'              => array(),

		/* FAQs */
		'faq_title'            => '',
		'faq_note'             => 'The ones we get asked most.',
		'faqs'                 => array(),

		/* Closing CTA */
		'cta_title'            => 'Say yes to hostels, meet the world',
		'cta_body'             => '',
		'cta_buttons'          => array(
			array( 'label' => 'Get the app', 'href' => 'https://hostelworld.onelink.me/IdiK/f5a50nhd' ),
		),
		'newsletter_title'     => 'Get the good stuff monthly',
		'newsletter_body'      => 'New city guides, deals and route ideas. No filler.',
		'newsletter_shortcode' => '',
	);
}
