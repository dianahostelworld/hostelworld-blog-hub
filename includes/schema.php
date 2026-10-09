<?php
/**
 * Field schema.
 *
 * One place that describes every editable field: what it's called, what type
 * it is, and (for repeating lists) which columns each row has. The admin panel
 * renders itself from this, and saving loops over it, so adding a field means
 * adding one entry here.
 *
 * Types:
 *   text     single-line input
 *   textarea multi-line; blank line between paragraphs where the template splits them
 *   media    image picker, stores an attachment ID
 *   check    checkbox, stores '1' or ''
 *   select   a fixed set of choices
 *   list     repeating rows, one per line, columns separated by |
 *   rows     the same data as 'list', edited as proper rows with add/remove
 *            buttons and an image picker on any column named 'image'
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which field set a Page uses, from its template.
 *
 * @param int $post_id Page ID.
 * @return string continent, country, city, country_states or state.
 */
function hw_hub_template_for_post( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();

	$slug = get_page_template_slug( $post_id );

	if ( HW_HUB_COUNTRY_TEMPLATE_KEY === $slug ) {
		return 'country';
	}

	if ( HW_HUB_CITY_TEMPLATE_KEY === $slug ) {
		return 'city';
	}

	if ( HW_HUB_COUNTRY_STATES_TEMPLATE_KEY === $slug ) {
		return 'country_states';
	}

	if ( HW_HUB_STATE_TEMPLATE_KEY === $slug ) {
		return 'state';
	}

	return 'continent';
}

/**
 * @param string $template continent or country.
 * @return array Section key => section definition.
 */
function hw_hub_schema( $template = '' ) {
	$template = $template ? $template : hw_hub_template_for_post();

	if ( 'country' === $template ) {
		return hw_hub_country_schema();
	}

	if ( 'city' === $template ) {
		return hw_hub_city_schema();
	}

	if ( 'country_states' === $template ) {
		return hw_hub_country_states_schema();
	}

	if ( 'state' === $template ) {
		return hw_hub_state_schema();
	}

	return array(

		'hero'      => array(
			'label'  => __( 'Hero', 'hostelworld' ),
			'fields' => array(
				'hero_eyebrow' => array( 'label' => 'Eyebrow', 'type' => 'text' ),
				'hero_h1'      => array( 'label' => 'Heading (H1)', 'type' => 'text', 'help' => 'Leave empty to use the Page title.' ),
				'hero_intro'   => array( 'label' => 'Intro paragraph', 'type' => 'textarea', 'rows' => 3 ),
				'hero_image'   => array( 'label' => 'Hero image', 'type' => 'media', 'help' => 'Fills the whole hero behind the text. Left empty, the hub falls back to its WDS continent map illustration.' ),
				'hero_overlay' => array(
					'label'   => 'Darkening over the hero image',
					'type'    => 'select',
					'options' => array(
						'light'  => 'Light — photo stays bright',
						'medium' => 'Medium',
						'strong' => 'Strong — for busy or pale photos',
					),
					'help'    => 'Only as much as the text needs. Go up a level if a bright photo is making the heading hard to read.',
				),
				'hero_cta'     => array( 'label' => 'Hero buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3, 'help' => 'Label | link. First button is solid, second outlined. Links can be #countries, a full URL, page:blog/destinations, or tags:europe+solo-travel for posts carrying both tags.' ),
			),
		),

		'intro'     => array(
			'label'  => __( 'Intro section', 'hostelworld' ),
			'fields' => array(
				'why_title'      => array( 'label' => 'Heading', 'type' => 'text' ),
				'why_body'       => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 8, 'help' => 'Leave a blank line between paragraphs. Anything you write in the main editor above wins over this.' ),
				'why_link_label' => array( 'label' => 'Link text', 'type' => 'text' ),
				'why_link_url'   => array( 'label' => 'Link URL', 'type' => 'text' ),
				'why_cards'      => array( 'label' => 'Three cards', 'type' => 'list', 'cols' => array( 'icon', 'title', 'body' ), 'rows' => 4, 'help' => 'Icon | heading | body. See the icon list at the bottom of the Settings tab.' ),
				'stats'          => array( 'label' => 'Stat chips on the hero', 'type' => 'list', 'cols' => array( 'value', 'label' ), 'rows' => 5, 'help' => 'Number | label. The label is all the reader gets, so make it stand alone: "A day in the east", not "A day, from". Four fit across the hero.' ),
			),
		),

		'countries' => array(
			'label'  => __( 'Countries', 'hostelworld' ),
			'fields' => array(
				'countries_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'countries_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'featured'        => array( 'label' => 'Featured countries', 'type' => 'rows', 'cols' => array( 'name', 'cities', 'page', 'image' ), 'labels' => array( 'name' => 'Country', 'cities' => 'Cities', 'page' => 'Custom link', 'image' => 'Card image' ), 'add' => 'Add a country', 'help' => 'Countries link to this hub\'s address plus the country name — on a hub at /blog/europe/, Spain goes to /blog/europe/spain/. Leave Custom link empty unless one country needs to go somewhere else. Leave the image empty and the card uses the country page\'s featured image once that page exists.' ),
				'regions'         => array( 'label' => 'Country grid', 'type' => 'list', 'cols' => array( 'name', 'note', 'countries' ), 'rows' => 7, 'help' => 'Region | note | countries, comma separated. Each country links to its child Page once that exists.' ),
			),
		),

		'hostels'   => array(
			'label'  => __( 'Hostels', 'hostelworld' ),
			'fields' => array(
				'hostels_title'     => array( 'label' => 'Heading', 'type' => 'text' ),
				'hostels_intro'     => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'hostels_cta_label' => array( 'label' => 'Button text', 'type' => 'text' ),
				'hostels_cta_url'   => array( 'label' => 'Button URL', 'type' => 'text', 'help' => 'Point this at a Hostelworld search for the continent, so the full, live list is one click away.' ),
				'hostels_source'    => array(
					'label'   => 'Where the hostels come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, sorted by rating',
						'manual' => 'Only the ones I list below',
					),
					'help'    => 'Live pulls the top-rated properties in this continent\'s most popular cities, with their real ratings and prices, and refreshes on its own. If the API is unreachable it falls back to your list below, so it is worth keeping a few there.',
				),
				'hostels_location_type' => array(
					'label'   => 'Location type',
					'type'    => 'select',
					'options' => array(
						'continent' => 'Continent',
						'country'   => 'Country',
						'region'    => 'Region',
						'area'      => 'Area',
					),
				),
				'hostels_location_id' => array(
					'label' => 'Location ID',
					'type'  => 'text',
					'help'  => 'Continents: 1 North America, 2 South America, 3 Europe, 4 Asia, 5 Oceania, 6 Africa. Country and region IDs come from the same API.',
				),
				'hostels_currency'  => array( 'label' => 'Currency', 'type' => 'text', 'help' => 'Three-letter code for the prices, e.g. EUR.' ),
				'hostels_count'     => array( 'label' => 'How many to show', 'type' => 'text' ),
				'hostels_cache'     => array( 'label' => 'Refresh every (hours)', 'type' => 'text', 'help' => 'Prices move daily, so 12 to 24 is sensible. Lower means more calls and no real gain.' ),
				'hostels'           => array(
					'label'  => 'Fallback hostels',
					'type'   => 'rows',
					'cols'   => array( 'name', 'place', 'note', 'image', 'href' ),
					'labels' => array( 'name' => 'Hostel', 'place' => 'City', 'note' => 'One line on it', 'image' => 'Photo', 'href' => 'Hostelworld URL' ),
					'add'    => 'Add a hostel',
					'help'   => 'Used when the live feed is off or unreachable. No ratings or prices here on purpose: typed in, they would be stale within days. The live feed carries real ones.',
				),
			),
		),

		'when'      => array(
			'label'  => __( 'When to go', 'hostelworld' ),
			'fields' => array(
				'when_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'when_intro' => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'seasons'    => array( 'label' => 'Seasons', 'type' => 'list', 'cols' => array( 'name', 'months', 'tag', 'tone', 'body', 'points' ), 'rows' => 5, 'help' => 'Name | months | badge | badge colour | body | bullets separated by semicolons. Badge colours: green, amber, blue.' ),
			),
		),

		'costs'     => array(
			'label'  => __( 'Costs', 'hostelworld' ),
			'fields' => array(
				'costs_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'costs_body'  => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 6, 'help' => 'Blank line between paragraphs.' ),
				'cost_rows'   => array( 'label' => 'Cost table', 'type' => 'list', 'cols' => array( 'region', 'examples', 'dorm', 'daily' ), 'rows' => 7, 'help' => 'Region | examples | dorm price | daily budget.' ),
				'cost_note'   => array( 'label' => 'Table footnote', 'type' => 'textarea', 'rows' => 2 ),
				'money_reads' => array( 'label' => 'Budget guides', 'type' => 'list', 'cols' => array( 'title', 'href' ), 'rows' => 5, 'help' => 'Title | URL.' ),
			),
		),

		'rail'      => array(
			'label'  => __( 'Getting around', 'hostelworld' ),
			'fields' => array(
				'rail_title'      => array( 'label' => 'Heading', 'type' => 'text' ),
				'rail_body'       => array( 'label' => 'Body copy', 'type' => 'textarea', 'rows' => 6, 'help' => 'Blank line between paragraphs.' ),
				'rail_link_label' => array( 'label' => 'Link text', 'type' => 'text' ),
				'rail_link_url'   => array( 'label' => 'Link URL', 'type' => 'text' ),
				'rail_image'      => array( 'label' => 'Section image', 'type' => 'media' ),
				'routes'          => array( 'label' => 'Route cards', 'type' => 'list', 'cols' => array( 'length', 'name', 'body', 'stops' ), 'rows' => 4, 'help' => 'Duration | name | body | stops summary.' ),
			),
		),

		'events'    => array(
			'label'  => __( "What's on", 'hostelworld' ),
			'fields' => array(
				'events_title'     => array( 'label' => 'Heading', 'type' => 'text' ),
				'events_intro'     => array( 'label' => 'Intro', 'type' => 'textarea', 'rows' => 2 ),
				'events_source'    => array(
					'label'   => 'Where the events come from',
					'type'    => 'select',
					'options' => array(
						'api'    => 'Live from Hostelworld, with real dates',
						'manual' => 'Only the ones I list below',
					),
					'help'    => 'Live pulls this continent\'s staff picks and top events by type, drops anything that has finished, and refreshes daily.',
				),
				'events_continent' => array( 'label' => 'Continent name for the feed', 'type' => 'text', 'help' => 'As it appears in a Hostelworld URL, e.g. europe.' ),
				'events_count'     => array( 'label' => 'How many to show', 'type' => 'text' ),
				'events_cache'     => array( 'label' => 'Refresh every (hours)', 'type' => 'text' ),
				'events_cta_label' => array( 'label' => 'Button text', 'type' => 'text' ),
				'events_cta_url'   => array( 'label' => 'Button URL', 'type' => 'text' ),
				'events'           => array( 'label' => 'Fallback events', 'type' => 'list', 'cols' => array( 'month', 'name', 'place', 'body' ), 'rows' => 9, 'help' => 'Month | name | place | body. Used when the live feed is off or unreachable.' ),
				'events_pills'     => array( 'label' => 'Link pills', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 4, 'help' => 'Label | URL.' ),
			),
		),

		'guides'    => array(
			'label'  => __( 'Guides', 'hostelworld' ),
			'fields' => array(
				'reads_intro'    => array( 'label' => 'Section intro', 'type' => 'textarea', 'rows' => 2 ),
				'article_rows'   => array( 'label' => 'Article rows', 'type' => 'list', 'cols' => array( 'title', 'note', 'tag', 'see_all', 'link' ), 'rows' => 5, 'help' => 'Heading | note | tags | "see all" link text | link (optional). Join tags with + to require all of them: europe+things-to-do shows only posts carrying both. Leave the link column empty and the button goes to the same combination, so it stays inside the continent. If a tag does not exist, the row is skipped rather than widened.' ),
				'show_read_time' => array( 'label' => 'Show read time on cards', 'type' => 'check' ),
				'all_title'      => array( 'label' => 'All-articles heading', 'type' => 'text' ),
				'all_body'       => array( 'label' => 'All-articles body', 'type' => 'textarea', 'rows' => 2 ),
				'all_cta'        => array( 'label' => 'All-articles button', 'type' => 'text' ),
			),
		),

		'trips'     => array(
			'label'  => __( 'Types of trip', 'hostelworld' ),
			'fields' => array(
				'trips_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'trips_note'  => array( 'label' => 'Note', 'type' => 'text' ),
				'trips'       => array( 'label' => 'Tiles', 'type' => 'list', 'cols' => array( 'icon', 'label', 'href' ), 'rows' => 7, 'help' => 'Icon | label | URL. Use tags:europe+solo-travel to point a tile at posts carrying both tags. See the icon list at the bottom of the Settings tab.' ),
			),
		),

		'faqs'      => array(
			'label'  => __( 'FAQs', 'hostelworld' ),
			'fields' => array(
				'faq_title' => array( 'label' => 'Heading', 'type' => 'text' ),
				'faq_note'  => array( 'label' => 'Note', 'type' => 'text' ),
				'faqs'      => array( 'label' => 'Questions', 'type' => 'list', 'cols' => array( 'q', 'a' ), 'rows' => 9, 'help' => 'Question | answer, one per line. These also generate the FAQ schema, so keep answers accurate.' ),
			),
		),

		'cta'       => array(
			'label'  => __( 'Closing CTA', 'hostelworld' ),
			'fields' => array(
				'cta_title'            => array( 'label' => 'Heading', 'type' => 'text' ),
				'cta_body'             => array( 'label' => 'Body', 'type' => 'textarea', 'rows' => 3 ),
				'cta_buttons'          => array( 'label' => 'Buttons', 'type' => 'list', 'cols' => array( 'label', 'href' ), 'rows' => 3, 'help' => 'Label | URL.' ),
				'newsletter_title'     => array( 'label' => 'Newsletter heading', 'type' => 'text' ),
				'newsletter_body'      => array( 'label' => 'Newsletter body', 'type' => 'text' ),
				'newsletter_shortcode' => array( 'label' => 'Signup form shortcode', 'type' => 'text', 'help' => 'Paste your signup form shortcode. Leave empty and the newsletter box is hidden.' ),
			),
		),

		'settings'  => array(
			'label'  => __( 'Settings', 'hostelworld' ),
			'fields' => array(
				'hub_term'   => array( 'label' => 'This hub\'s tag', 'type' => 'text', 'help' => 'The tag articles carry for this destination, e.g. europe. Articles are pulled by tag — this is not the Page slug and not a destination-taxonomy term, though the same name usually exists as all three.' ),
				'scope_rows' => array( 'label' => 'Keep article rows inside this hub', 'type' => 'check', 'help' => 'On: the hub term above is added to every article row, so a row tagged things-to-do can only return Europe posts. Off: rows return exactly the tags you list, nothing added.' ),
			),
		),
	);
}

/**
 * Flat map of field key => field definition.
 *
 * @param string $template continent or country.
 * @return array
 */
function hw_hub_schema_fields( $template = '' ) {
	$template = $template ? $template : hw_hub_template_for_post();

	static $flat = array();

	if ( isset( $flat[ $template ] ) ) {
		return $flat[ $template ];
	}

	$flat[ $template ] = array();

	foreach ( hw_hub_schema( $template ) as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$flat[ $template ][ $key ] = $field;
		}
	}

	return $flat[ $template ];
}
