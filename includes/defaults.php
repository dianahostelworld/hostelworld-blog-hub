<?php
/**
 * Starting content for a new hub page.
 *
 * These values pre-fill the "Hub content" panel the first time you open it.
 * Once you hit Update, what's saved on the Page is what shows — editing this
 * file after that changes nothing on existing pages.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $template continent or country.
 * @return array Flat map of field key => default value.
 */
function hw_hub_defaults( $template = '' ) {
	$template = $template ? $template : hw_hub_template_for_post();

	if ( 'country' === $template ) {
		return hw_hub_country_defaults();
	}

	if ( 'city' === $template ) {
		return hw_hub_city_defaults();
	}

	if ( 'country_states' === $template ) {
		return hw_hub_country_states_defaults();
	}

	if ( 'state' === $template ) {
		return hw_hub_state_defaults();
	}

	return array(

		/* Settings */
		'hub_term'            => 'europe',
		'scope_rows'          => '1',
		'show_read_time'      => '1',

		/* Hero */
		'hero_eyebrow'        => 'The continent',
		'hero_h1'             => 'Things to do in Europe',
		'hero_intro'          => 'Forty countries, one rail network that reaches nearly all of them, and a bed in most cities for under €30. Start with a country, or start with a month — this is everything we know about travelling Europe.',
		'hero_image'          => '',
		'hero_overlay'        => 'light',
		'hero_cta'            => array(
			array( 'label' => 'Pick a country', 'href' => '#countries' ),
			array( 'label' => 'What it costs', 'href' => '#costs' ),
		),

		/* Intro */
		'why_title'           => 'Small distances, big differences',
		'why_body'            => "You can have breakfast in Vienna and dinner in Budapest, and the two cities will feel nothing alike. That is the whole appeal of travelling Europe: the countries are close together and stubbornly themselves, so a fortnight here covers more ground culturally than a month almost anywhere else.\n\nIt is also the easiest continent to travel badly. Move every second day and you will spend your trip in train stations and your budget on transport. Most people who come back happy did fewer places, more slowly, and travelled overland between them.\n\nBelow: every country we cover, what a day actually costs by region, when to go for weather against price, and the guides our writers have put together on the ground.",
		'why_link_label'      => 'Read the full Europe backpacking guide',
		'why_link_url'        => 'https://www.hostelworld.com/blog/backpacking-europe/',
		'why_cards'           => array(
			array( 'icon' => 'train', 'title' => 'Everything is close', 'body' => 'Capitals are often four hours apart by train, and 30 countries sit on a single rail pass.' ),
			array( 'icon' => 'coin', 'title' => 'You choose the price', 'body' => 'The same three weeks costs €900 in Albania and Poland, or €2,400 in Norway and Switzerland.' ),
			array( 'icon' => 'users', 'title' => 'You will not be alone', 'body' => 'The most established hostel scene anywhere. Turning up solo is the normal way to do it.' ),
		),

		/* Stats */
		'stats'               => array(
			array( 'value' => '40', 'label' => 'Countries covered' ),
			array( 'value' => '135', 'label' => 'Cities, Lisbon to Tallinn' ),
			array( 'value' => '€35', 'label' => 'A day, at the cheap end' ),
			array( 'value' => '30', 'label' => 'Countries on one rail pass' ),
		),

		/* Countries */
		'countries_title'     => 'Start with a country',
		'countries_intro'     => 'Every country page carries its cities, what a day costs there, when to go and the hostels we would book ourselves.',
		'featured'            => array(
			array( 'name' => 'Spain', 'cities' => 'Barcelona · Madrid · Seville · Valencia', 'page' => 'europe/spain', 'image' => '' ),
			array( 'name' => 'Italy', 'cities' => 'Rome · Florence · Naples · Milan', 'page' => 'europe/italy', 'image' => '' ),
			array( 'name' => 'Portugal', 'cities' => 'Lisbon · Porto · the Algarve', 'page' => 'europe/portugal', 'image' => '' ),
			array( 'name' => 'Germany', 'cities' => 'Berlin · Munich · Hamburg', 'page' => 'europe/germany', 'image' => '' ),
			array( 'name' => 'Czech Republic', 'cities' => 'Prague · Brno · Bohemia', 'page' => 'europe/czech-republic', 'image' => '' ),
			array( 'name' => 'Croatia', 'cities' => 'Split · Dubrovnik · Hvar', 'page' => 'europe/croatia', 'image' => '' ),
		),
		'regions'             => array(
			array( 'name' => 'Western Europe', 'note' => 'The big hitters, and the priciest', 'countries' => 'France, Germany, Netherlands, Belgium, Austria, Switzerland, Ireland, England, Scotland, Wales, Luxembourg' ),
			array( 'name' => 'Southern Europe', 'note' => 'Coast, heat and late dinners', 'countries' => 'Spain, Portugal, Italy, Greece, Malta, Cyprus, Turkey' ),
			array( 'name' => 'Central & Eastern Europe', 'note' => 'Where the budget stretches furthest', 'countries' => 'Czech Republic, Poland, Hungary, Slovakia, Slovenia, Romania, Bulgaria, Estonia, Latvia, Lithuania, Ukraine' ),
			array( 'name' => 'The Balkans & Caucasus', 'note' => 'Cheapest, least crowded, most planning', 'countries' => 'Croatia, Serbia, Bosnia & Herzegovina, Montenegro, Albania, North Macedonia, Georgia' ),
			array( 'name' => 'The Nordics & Iceland', 'note' => 'Expensive, and worth it once', 'countries' => 'Denmark, Sweden, Norway, Finland, Iceland' ),
		),

		/* Hostels */
		'hostels_title'       => 'Where to stay while you do it',
		'hostels_intro'       => 'The European hostels travellers rate most highly. Prices and availability are live on each property page.',
		'hostels_cta_label'   => 'All hostels in Europe',
		'hostels_cta_url'     => 'https://www.hostelworld.com/hostels/Europe',
		'hostels_source'        => 'api',
		'hostels_location_type' => 'continent',
		'hostels_location_id'   => '3',
		'hostels_currency'      => 'EUR',
		'hostels_count'         => '12',
		'hostels_cache'         => '12',
		'hostels'               => array(),

		/* When to go */
		'when_title'          => 'The same trip, half the price',
		'when_intro'          => 'April to May and September to October are the value months almost everywhere: moderate temperatures, quieter sights, and dorm prices well off their August peak.',
		'seasons'             => array(
			array( 'name' => 'Spring', 'months' => 'March – May', 'tag' => 'Best value', 'tone' => 'green', 'body' => 'Mild, quiet and cheap. The sweet spot for cities, and the continent at its blooming best.', 'points' => 'The south warms first; Easter lifts prices for a week; Tulip fields, Netherlands' ),
			array( 'name' => 'Summer', 'months' => 'June – August', 'tag' => 'Peak', 'tone' => 'amber', 'body' => 'Festival season and beach weather, at the highest prices of the year.', 'points' => 'Book dorms weeks ahead; Southern cities top 30°C; Head north for daylight' ),
			array( 'name' => 'Autumn', 'months' => 'September – November', 'tag' => 'Best value', 'tone' => 'green', 'body' => 'Warm sea, harvest season, and prices falling from mid-September.', 'points' => 'Oktoberfest, Munich; The best hiking months; Sea still warm in the south' ),
			array( 'name' => 'Winter', 'months' => 'December – February', 'tag' => 'Cheapest', 'tone' => 'blue', 'body' => 'Christmas markets, ski season and museums you have almost to yourself.', 'points' => 'Markets from mid-November; Alps peak at New Year; January is the cheapest month' ),
		),

		/* Costs */
		'costs_title'         => 'What a day actually costs',
		'costs_body'          => "Daily budgets below cover a dorm bed, food, local transport and one paid thing to do. They assume you are staying put — every travel day costs roughly double a stationary one, which is why route planning matters more than any single saving.\n\nHead east and the same trip stretches much further. Prague, Kraków, Budapest and Belgrade all run at half Western European prices without feeling like a compromise.",
		'cost_rows'           => array(
			array( 'region' => 'The Balkans', 'examples' => 'Albania, Serbia, North Macedonia', 'dorm' => '€10–14', 'daily' => '€28–38' ),
			array( 'region' => 'Central & Eastern', 'examples' => 'Poland, Czechia, Hungary, Romania', 'dorm' => '€12–18', 'daily' => '€32–45' ),
			array( 'region' => 'Southern Europe', 'examples' => 'Portugal, Spain, Italy, Greece', 'dorm' => '€18–28', 'daily' => '€45–65' ),
			array( 'region' => 'Western Europe', 'examples' => 'France, Germany, Netherlands, Ireland', 'dorm' => '€25–40', 'daily' => '€60–85' ),
			array( 'region' => 'The Nordics', 'examples' => 'Denmark, Sweden, Norway, Finland', 'dorm' => '€35–50', 'daily' => '€85–110' ),
			array( 'region' => 'Switzerland & Iceland', 'examples' => 'Zurich, Geneva, Reykjavík', 'dorm' => '€40–60', 'daily' => '€95–130' ),
		),
		'cost_note'           => 'Indicative shoulder-season figures for a traveller staying in dorms and eating mostly self-catered or street food. Peak summer runs 30–50% higher in Spain, Italy, Croatia and Greece.',
		'money_reads'         => array(
			array( 'title' => 'Backpacking Europe: how much does it cost?', 'href' => 'https://www.hostelworld.com/blog/backpacking-through-europe-cost/' ),
			array( 'title' => 'How to travel Europe for a month on a student budget', 'href' => 'https://www.hostelworld.com/blog/how-to-travel-europe-for-a-month-on-a-student-budget/' ),
			array( 'title' => 'How to budget for the Interrailing trip of a lifetime', 'href' => 'https://www.hostelworld.com/blog/how-much-does-interrailing-cost/' ),
			array( 'title' => 'The cheapest European cities to visit', 'href' => 'https://www.hostelworld.com/blog/cheapest-european-cities/' ),
		),

		/* Getting around */
		'rail_title'          => 'Thirty countries, one pass',
		'rail_body'           => "An Interrail or Eurail pass covers 30 countries, and sometimes buses and ferries too. Whether it beats individual tickets comes down to one thing: how often you move. More than once every third day and the pass wins; three or four cities over a month and advance point-to-point fares are cheaper.\n\nNight trains tip the maths further towards a pass — you save a night's accommodation and a day of travelling. Buses undercut everything on price, especially in the east, and flying is almost always the worst value once you add bags and airport transfers.",
		'rail_link_label'     => 'Ten tips for Interrailing Europe',
		'rail_link_url'       => 'https://www.hostelworld.com/blog/inter-rail-europe-my-top-ten-tips/',
		'rail_image'          => '',
		'routes'              => array(
			array( 'length' => 'Three weeks', 'name' => 'The classic west', 'body' => 'London, Paris, Amsterdam, Berlin, Vienna, Rome. The big sights, the big prices — give each city three days.', 'stops' => '6 cities · 5 countries' ),
			array( 'length' => 'Three weeks', 'name' => 'Europe on a shoestring', 'body' => 'Kraków, Prague, Budapest, Ljubljana, then down to the Croatian coast. Half the cost of the western run.', 'stops' => '5 stops · 5 countries' ),
			array( 'length' => 'Two weeks', 'name' => 'The Adriatic line', 'body' => 'Venice to Ljubljana, Zagreb, Split and Dubrovnik, finishing in Kotor. Trains, then buses and ferries.', 'stops' => '6 stops · 4 countries' ),
		),

		/* What's on */
		'events_title'        => 'Worth building a route around',
		'events_intro'        => 'These are also the dates that will triple your accommodation costs if you turn up without a booking. Both facts are worth planning for.',
		'events_source'       => 'api',
		'events_continent'    => 'europe',
		'events_count'        => '8',
		'events_cache'        => '24',
		'events_cta_label'    => 'See all events in Europe',
		'events_cta_url'      => 'https://www.hostelworld.com/events/europe/',
		'events'              => array(
			array( 'month' => 'February', 'name' => 'Carnival', 'place' => 'Venice · Cologne · Nice', 'body' => "Europe's biggest pre-Lent parties, in the cheapest month to be in Italy otherwise." ),
			array( 'month' => 'March', 'name' => "St Patrick's Day", 'place' => 'Dublin', 'body' => 'Five days of it, not one. Book two months out or there will not be a bed.' ),
			array( 'month' => 'April', 'name' => 'Feria de Abril', 'place' => 'Seville', 'body' => 'Flamenco, horses and casetas. Andalusia at its most itself.' ),
			array( 'month' => 'June – August', 'name' => 'Festival season', 'place' => 'Continent-wide', 'body' => 'Glastonbury, Sziget, Primavera, EXIT. The backbone of most summer routes.' ),
			array( 'month' => 'August', 'name' => 'La Tomatina', 'place' => 'Buñol, Valencia', 'body' => 'One hour, 150,000 tomatoes. Ticketed now, so plan ahead.' ),
			array( 'month' => 'August', 'name' => 'Edinburgh Fringe', 'place' => 'Scotland', 'body' => "Three weeks, and the world's largest arts festival. Accommodation triples — book by March." ),
			array( 'month' => 'September', 'name' => 'Oktoberfest', 'place' => 'Munich', 'body' => 'Starts in September despite the name. Free to walk in, expensive once you sit down.' ),
			array( 'month' => 'November – December', 'name' => 'Christmas markets', 'place' => 'Germany · Austria · Czechia', 'body' => 'Vienna, Prague, Nuremberg, Tallinn. The cheapest way to make winter worth it.' ),
		),
		'events_pills'        => array(
			array( 'label' => "25 festivals you've probably never heard of", 'href' => 'https://www.hostelworld.com/blog/best-music-festivals/' ),
			array( 'label' => 'The best EDM festivals', 'href' => 'https://www.hostelworld.com/blog/edm-festivals/' ),
			array( 'label' => 'Winter sun on a budget', 'href' => 'https://www.hostelworld.com/blog/8-of-the-best-winter-sun-destinations-that-wont-blow-the-budget/' ),
		),

		/* Guides */
		'reads_intro'         => 'Written by our travel writers and the travellers who host them.',
		'article_rows'        => array(
			array( 'title' => 'Things to do, city by city', 'note' => 'Short itineraries you can follow as written, in the cities most routes pass through.', 'tag' => 'europe+things-to-do', 'see_all' => 'All things to do in Europe', 'link' => '' ),
			array( 'title' => 'Getting around', 'note' => 'Rail passes, night trains and the routes worth taking overland.', 'tag' => 'europe+transport', 'see_all' => 'All transport guides in Europe', 'link' => '' ),
			array( 'title' => 'Eat and drink', 'note' => 'Where the money is best spent, and what each country does better than its neighbours.', 'tag' => 'europe+food', 'see_all' => 'All food guides in Europe', 'link' => '' ),
			array( 'title' => 'Nightlife and festivals', 'note' => 'Nights worth staying up for, and the dates worth planning a route around.', 'tag' => 'europe+nightlife', 'see_all' => 'All nightlife in Europe', 'link' => '' ),
		),
		'all_title'           => 'Everything we have written about Europe',
		'all_body'            => 'Filter by country, city or the kind of trip you are planning.',
		'all_cta'             => 'All Europe articles',

		/* Types of trip */
		'trips_title'         => 'Or start with how you travel',
		'trips_note'          => 'Same continent, filtered by trip',
		'trips'               => array(
			array( 'icon' => 'users', 'label' => 'Solo travel', 'href' => 'https://www.hostelworld.com/blog/c/solo-travel/' ),
			array( 'icon' => 'coin', 'label' => 'Budget', 'href' => 'https://www.hostelworld.com/blog/travel-on-a-budget/' ),
			array( 'icon' => 'train', 'label' => 'Interrailing', 'href' => 'https://www.hostelworld.com/blog/inter-rail-europe-my-top-ten-tips/' ),
			array( 'icon' => 'nightclub', 'label' => 'Nightlife', 'href' => 'https://www.hostelworld.com/blog/edm-festivals/' ),
			array( 'icon' => 'hostel', 'label' => 'Hostel life', 'href' => 'https://www.hostelworld.com/blog/c/hostel-tips/' ),
			array( 'icon' => 'music', 'label' => 'Festivals', 'href' => 'https://www.hostelworld.com/blog/best-music-festivals/' ),
		),

		/* FAQs */
		'faq_title'           => 'Europe travel questions',
		'faq_note'            => 'The ones we get asked most.',
		'faqs'                => array(
			array( 'q' => 'How much does a trip to Europe cost per day?', 'a' => 'Budget €45–70 a day in Western Europe and €30–45 in Central and Eastern Europe, covering a dorm bed, food, local transport and one paid activity. Scandinavia and Switzerland run closer to €95. The biggest variable is not the country — it is how often you move, since every travel day costs roughly double a stationary one.' ),
			array( 'q' => 'What is the cheapest time of year to visit?', 'a' => 'Mid-January to mid-March, once the Christmas markets and ski season are out of the way. Late September to early November is the better compromise: nearly as cheap, far better weather, and the sea in the south is still warm.' ),
			array( 'q' => 'Is a rail pass actually worth it?', 'a' => "If you are moving more often than every third day, yes. If you are basing yourself in three or four cities over a month, advance point-to-point tickets will come out cheaper. Night trains tip the maths towards a pass, because you save a night's accommodation as well as a day of travelling." ),
			array( 'q' => 'How many countries should I try to see?', 'a' => 'Fewer than you think. Eight countries in three weeks becomes a tour of train stations. Four in a month, or two in a fortnight, leaves enough time to actually see somewhere.' ),
			array( 'q' => 'Do I need to book hostels in advance?', 'a' => "In July and August, yes — especially Barcelona, Amsterdam, Lisbon, Split and anywhere hosting a festival. Outside peak season a few days' notice is usually enough, and turning up flexible is part of the appeal." ),
			array( 'q' => 'Which countries are best for a first trip?', 'a' => 'Spain, Portugal, Italy and the Czech Republic. Easy transport, plenty of other travellers and forgiving budgets. The Balkans are cheaper and increasingly popular, but the buses and ferries take more planning.' ),
			array( 'q' => 'Is Europe safe to travel alone?', 'a' => 'Broadly, yes, and it is the most established solo travel region in the world. Petty theft is the realistic risk rather than anything worse: watch your bag on public transport and in busy squares, and keep your phone out of your back pocket.' ),
		),

		/* Closing CTA */
		'cta_title'           => 'Say yes to hostels, meet the world',
		'cta_body'            => 'Compare hostels across 40 European countries — live prices, real reviews and free cancellation on most bookings. Then meet the people already there in the app.',
		'cta_buttons'         => array(
			array( 'label' => 'Find hostels in Europe', 'href' => 'https://www.hostelworld.com/' ),
			array( 'label' => 'Get the app', 'href' => 'https://hostelworld.onelink.me/IdiK/f5a50nhd' ),
		),
		'newsletter_title'    => 'Get the good stuff monthly',
		'newsletter_body'     => 'New city guides, deals and route ideas. No filler.',
		'newsletter_shortcode' => '',
	);
}

/**
 * Curated article lists, used only while the taxonomy is still being built and
 * only on the hub these articles actually belong to.
 *
 * @return array
 */
function hw_hub_article_fallbacks() {
	return array(
		'hub_term' => 'europe',
		'rows'     => array(
			'things-to-do'      => array(
				array( 'tag' => 'Italy', 'title' => 'Top things to do in Rome in 2 days', 'href' => 'https://www.hostelworld.com/blog/top-things-to-do-in-rome-in-2-days/' ),
				array( 'tag' => 'France', 'title' => 'Top things to do in Paris in 3 days', 'href' => 'https://www.hostelworld.com/blog/top-things-to-do-in-paris-3-days-guide/' ),
				array( 'tag' => 'Germany', 'title' => 'Top 12 things to do in Berlin in 3 days', 'href' => 'https://www.hostelworld.com/blog/top-12-things-to-do-in-berlin-in-3-days-itinerary/' ),
				array( 'tag' => 'Netherlands', 'title' => '12 things to do in Amsterdam in 2 days', 'href' => 'https://www.hostelworld.com/blog/12-things-to-do-in-amsterdam-in-2-days/' ),
			),
			'transport'         => array(
				array( 'tag' => 'Interrailing', 'title' => 'Top ten tips for Interrailing Europe', 'href' => 'https://www.hostelworld.com/blog/inter-rail-europe-my-top-ten-tips/' ),
				array( 'tag' => 'Rail', 'title' => 'The most breathtaking train journeys around Europe', 'href' => 'https://www.hostelworld.com/blog/train-journeys-europe/' ),
				array( 'tag' => 'Routes', 'title' => 'Best Interrailing destinations and routes', 'href' => 'https://www.hostelworld.com/blog/top-destinations-for-inter-railing/' ),
				array( 'tag' => 'Solo travel', 'title' => 'The best places to travel solo in Europe by train', 'href' => 'https://www.hostelworld.com/blog/the-best-places-to-travel-solo-in-europe-by-train/' ),
			),
			'food'               => array(
				array( 'tag' => 'Food', 'title' => 'The one thing you must eat in these 14 European countries', 'href' => 'https://www.hostelworld.com/blog/what-one-thing-should-you-eat-in-these-14-european-countries/' ),
				array( 'tag' => 'Food', 'title' => 'The most drool-worthy European desserts', 'href' => 'https://www.hostelworld.com/blog/european-desserts/' ),
			),
			'nightlife'          => array(
				array( 'tag' => 'Nightlife', 'title' => "25 best music festivals you've probably never heard of", 'href' => 'https://www.hostelworld.com/blog/best-music-festivals/' ),
				array( 'tag' => 'Nightlife', 'title' => 'The best EDM festivals', 'href' => 'https://www.hostelworld.com/blog/edm-festivals/' ),
			),
			// Kept so rows still saved as food-and-nightlife keep showing something.
			'food-and-nightlife' => array(
				array( 'tag' => 'Food', 'title' => 'The one thing you must eat in these 14 European countries', 'href' => 'https://www.hostelworld.com/blog/what-one-thing-should-you-eat-in-these-14-european-countries/' ),
				array( 'tag' => 'Food', 'title' => 'The most drool-worthy European desserts', 'href' => 'https://www.hostelworld.com/blog/european-desserts/' ),
				array( 'tag' => 'Free', 'title' => '50 free things to do in Europe', 'href' => 'https://www.hostelworld.com/blog/50-things-to-do-for-free-in-europe/' ),
				array( 'tag' => 'Nightlife', 'title' => "25 best music festivals you've probably never heard of", 'href' => 'https://www.hostelworld.com/blog/best-music-festivals/' ),
			),
		),
	);
}
