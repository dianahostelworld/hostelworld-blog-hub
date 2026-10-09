<?php
/**
 * Starting content for a city hub, written for Barcelona.
 *
 * A worked example to edit, not gospel. Transport fares, opening rules and
 * anything seasonal need checking before a page goes live. The neighbourhood
 * coordinates are approximate centres, good enough for a marker and easy to
 * nudge.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array Flat map of field key => default value.
 */
function hw_hub_city_defaults() {

	return array(

		/* Settings */
		'hub_term'             => 'barcelona',
		'city_slug'            => 'barcelona',
		'country_slug'         => 'spain',
		'parent_hub'           => 'europe/spain',
		'currency'             => 'EUR',
		'scope_rows'           => '1',

		/* Hero */
		'hero_eyebrow'         => 'City guide',
		'hero_h1'              => 'Barcelona',
		'hero_intro'           => 'A city that runs from the mountain to the sea in forty minutes, with Gaudí in between. Busy, expensive by Spanish standards, and worth every bit of the queue.',
		'hero_image'           => '',
		'hero_overlay'         => 'light',
		'hero_cta'             => array(
			array( 'label' => 'Pick a neighbourhood', 'href' => '#areas' ),
			array( 'label' => 'What it costs', 'href' => '#money' ),
		),
		'live_stats'           => '1',
		'stats'                => array(
			array( 'value' => '3', 'label' => 'Days to do it properly' ),
		),

		/* Overview and facts */
		'intro_title'          => 'Mountain, city, sea, in that order',
		'intro_body'           => "Barcelona is laid out on a slope. The old city sits by the water in a knot of medieval lanes, the Eixample spreads uphill in a grid of identical octagonal blocks, and Gràcia sits above that still feeling like the separate village it used to be. Get that geography straight on day one and the city stops being confusing: almost everything worth seeing is downhill from wherever you are staying.\n\nThe city has a reputation for being overrun, and in July and August it is. The rest of the year it is a normal European city with an unusual amount of modernista architecture and a beach at the end of the metro line. Spring and autumn are genuinely pleasant, and January to March is quiet and cheap enough to be worth the cooler weather.\n\nThree days is the honest minimum: one for Gaudí, one for the old city and the beach, one for a neighbourhood nobody put on your list. A week lets you add Montserrat, Girona or the Costa Brava without rushing. Whatever you do, book the Sagrada Família and Park Güell before you arrive — turning up and queueing has not been an option for years.",
		'facts'                => array(
			array( 'label' => 'Languages', 'value' => 'Catalan and Spanish, both official. English is widely spoken' ),
			array( 'label' => 'Currency', 'value' => 'Euro (€)' ),
			array( 'label' => 'From the airport', 'value' => 'Aerobús 35 minutes, around €7. Metro L9 Sud is cheaper and slower' ),
			array( 'label' => 'Transport card', 'value' => 'T-casual, 10 journeys across metro, bus and tram' ),
			array( 'label' => 'Emergency', 'value' => '112' ),
			array( 'label' => 'Tap water', 'value' => 'Safe, though it tastes of chlorine. Most people buy bottled' ),
			array( 'label' => 'Tipping', 'value' => 'Not expected. Round up if the service was good' ),
			array( 'label' => 'How long to stay', 'value' => 'Three days minimum, a week if you want day trips' ),
			array( 'label' => 'Best months', 'value' => 'May, June, September and October' ),
		),

		/* Neighbourhoods */
		'areas_title'          => 'Where to base yourself',
		'areas_intro'          => 'Barcelona is walkable enough that no area is a bad choice, but they are not interchangeable. The old city puts you in the middle of everything and the noise; the Eixample is quieter and better value; Gràcia is where people live.',
		'areas_map'            => '1',
		'areas_zoom'           => '13',
		'areas_live'           => '1',
		'hostels_pins'         => '1',
		'areas'                => array(
			array( 'name' => 'Barri Gòtic', 'suits' => 'First trips, short stays', 'blurb' => 'The medieval core: narrow lanes, the cathedral, and bars that have been there for a century. Central, atmospheric, and loud at night.', 'lat' => '41.3833', 'lng' => '2.1767', 'api' => 'Barri Gotic', 'link' => '' ),
			array( 'name' => 'El Raval', 'suits' => 'Budget, food, going out', 'blurb' => 'Scruffier and more mixed, with the best cheap eating in the old city and the MACBA skaters outside. Keep your wits about you late at night.', 'lat' => '41.3797', 'lng' => '2.1682', 'api' => 'Las Ramblas', 'link' => '' ),
			array( 'name' => 'El Born', 'suits' => 'Food, bars, wandering', 'blurb' => 'The prettiest part of the old city. Santa Maria del Mar, the Picasso Museum, and a bar on every corner of a lane you did not mean to walk down.', 'lat' => '41.3848', 'lng' => '2.1817', 'api' => '', 'link' => '' ),
			array( 'name' => 'Eixample', 'suits' => 'Quiet nights, better value', 'blurb' => 'The grid. Gaudí\'s apartment buildings, wide pavements, and dorm beds a few euros cheaper than the old city for a ten-minute walk.', 'lat' => '41.3919', 'lng' => '2.1650', 'api' => 'Passeig de Gracia', 'link' => '' ),
			array( 'name' => 'Gràcia', 'suits' => 'Longer stays, locals', 'blurb' => 'A separate town until 1897 and it still feels like one: small squares, independent shops, and almost no tour groups. Twenty minutes from the centre.', 'lat' => '41.4036', 'lng' => '2.1560', 'api' => '', 'link' => '' ),
			array( 'name' => 'Barceloneta', 'suits' => 'Beach trips, summer', 'blurb' => 'The old fishing quarter next to the city beach. Great in June, packed in August, and a long walk back from anywhere else at night.', 'lat' => '41.3797', 'lng' => '2.1897', 'api' => 'Barceloneta', 'link' => '' ),
			array( 'name' => 'Poble-sec', 'suits' => 'Tapas, Montjuïc', 'blurb' => 'Under Montjuïc, with Carrer Blai and its pintxos bars. Cheap, low-key, and five minutes from the old city on foot.', 'lat' => '41.3722', 'lng' => '2.1610', 'api' => '', 'link' => '' ),
			array( 'name' => 'Sants', 'suits' => 'Early trains, lower prices', 'blurb' => 'Around the main station. Nothing to see, but the cheapest beds in the city and a direct line to the airport and the rest of Spain.', 'lat' => '41.3755', 'lng' => '2.1330', 'api' => 'Sants', 'link' => '' ),
		),

		/* Itineraries */
		'itin_title'           => 'How long have you got?',
		'itin_intro'           => 'Follow these as written or take the bits you want.',
		'itin'                 => array(
			array( 'label' => '24 hours', 'blurb' => 'Sagrada Família, the Gothic Quarter, tapas, a beach sunset.', 'link' => '', 'image' => '' ),
			array( 'label' => '2 days', 'blurb' => 'Adds Park Güell, El Born and a proper night out.', 'link' => '', 'image' => '' ),
			array( 'label' => '3 days', 'blurb' => 'Adds Montjuïc, the markets and a neighbourhood off the list.', 'link' => '', 'image' => '' ),
		),

		/* Things to do */
		'do_title'             => 'Things to do in Barcelona',
		'do_body'              => "The famous things are famous for a reason and you should do them, but book them. The Sagrada Família sells out days ahead most of the year, Park Güell's monumental zone has timed entry, and Casa Batlló queues run down the block by mid-morning.\n\nAround those, the city rewards walking. The Gothic Quarter is best without a plan, Montjuïc has the Joan Miró Foundation and a cable car with the view everyone photographs from the wrong side, and the Bunkers del Carmel give you the whole city for free if you climb for twenty minutes.\n\nIf you only have one afternoon spare, spend it in a neighbourhood rather than a museum. Gràcia's squares on a weekday evening will tell you more about Barcelona than another queue.",
		'do_points'            => array(
			array( 'label' => 'Book ahead', 'detail' => 'Sagrada Família, Park Güell and Casa Batlló, days in advance' ),
			array( 'label' => 'Free entry', 'detail' => 'Most municipal museums, first Sunday of the month' ),
			array( 'label' => 'Best view', 'detail' => 'Bunkers del Carmel at sunset, and it costs nothing' ),
		),
		'do_tag'               => 'things-to-do',
		'do_more'              => 'All things to do in Barcelona',

		/* Free things */
		'free_title'           => 'And what it costs nothing to do',
		'free_body'            => "Barcelona is expensive for a Spanish city, which makes the free things worth knowing. The Magic Fountain show at Montjuïc runs several evenings a week. The Gothic Quarter, El Born and Gràcia cost nothing to walk around, which is the best thing to do in all three.\n\nMunicipal museums are free on the first Sunday of every month and on Sunday afternoons after three. The beaches are free and go on for four kilometres. And a free walking tour on your first morning is the fastest way to work out where everything is, tip included.",
		'free_points'          => array(
			array( 'label' => 'Magic Fountain', 'detail' => 'Free show at Montjuïc, several evenings a week in season' ),
			array( 'label' => 'Museums', 'detail' => 'Free on the first Sunday of the month, and Sunday after 15:00' ),
			array( 'label' => 'Beaches', 'detail' => 'Four kilometres of them, all free' ),
		),
		'free_tag'             => 'free',
		'free_more'            => 'All free things to do in Barcelona',

		/* Food */
		'food_title'           => 'Eating and drinking',
		'food_body'            => "Catalan food is not the Spanish food people expect. Pa amb tomàquet rather than tapas as a default, fideuà instead of paella, calçots in late winter, and seafood that arrives from the market a few streets away.\n\nAvoid eating on Las Ramblas and the tourist strips around the cathedral. Walk five minutes into El Born, Poble-sec or Gràcia and both the price and the food change completely. Carrer Blai in Poble-sec does pintxos at a euro or two each, which is the cheapest good dinner in the city.\n\nMarkets are the other move. La Boqueria is the famous one and worth seeing early, before the crowds and the fruit-cup stalls take over. Santa Caterina and Mercat de Sant Antoni are where people actually shop.",
		'food_points'          => array(
			array( 'label' => 'Menú del día', 'detail' => 'Three courses at lunch, €12–16, the best value in the city' ),
			array( 'label' => 'Carrer Blai', 'detail' => 'Pintxos at €1–2 each in Poble-sec' ),
			array( 'label' => 'Markets', 'detail' => 'La Boqueria early, Santa Caterina and Sant Antoni any time' ),
			array( 'label' => 'Skip', 'detail' => 'Anywhere on Las Ramblas with a photo menu' ),
		),
		'food_tag'             => 'food',
		'food_more'            => 'All food guides for Barcelona',

		/* Nightlife */
		'nightlife_title'      => 'Going out',
		'nightlife_body'       => "Nights start late and end later. Bars fill after eleven, clubs do not get going until two, and the beach clubs run until dawn. Turning up to a club at midnight means having it to yourself.\n\nEl Born and the Gothic Quarter are the easy option: small bars, walkable, mixed crowd. El Raval is grittier and cheaper. Poble-sec and Sant Antoni are where you go for a quieter drink. The big clubs — Razzmatazz, Apolo, Pacha — are spread out, so plan how you are getting home.\n\nHostel bars are a genuinely good starting point here, and most run a crawl. If you are on your own on your first night, that is the shortcut.",
		'nightlife_points'     => array(
			array( 'label' => 'Timing', 'detail' => 'Bars from 23:00, clubs from 02:00' ),
			array( 'label' => 'Getting home', 'detail' => 'Metro runs all night on Saturdays; taxis otherwise' ),
			array( 'label' => 'Guest lists', 'detail' => 'Sign up online and most clubs cut the entry price' ),
		),
		'nightlife_tag'        => 'nightlife',
		'nightlife_more'       => 'All nightlife guides for Barcelona',

		/* Getting around */
		'transport_title'      => 'Getting around',
		'transport_body'       => "Walk. Barcelona is compact and almost everything in the centre is twenty minutes from everything else, and you will see more of it on foot than from underground.\n\nWhen you do need it, the metro is fast, clean and runs until midnight on weekdays, 2am on Fridays and all night on Saturdays. A T-casual card gives you ten journeys across metro, bus and tram and is far cheaper than single tickets. From the airport, the Aerobús takes 35 minutes to Plaça de Catalunya; the L9 Sud metro is cheaper, slower and needs a change.\n\nBicing is for residents, but the city is flat near the water and there are plenty of rental bikes. Taxis are metered and reasonable, and the black-and-yellow ones are the official ones.",
		'transport_points'     => array(
			array( 'label' => 'T-casual', 'detail' => 'Ten journeys, shared across metro, bus and tram' ),
			array( 'label' => 'Airport', 'detail' => 'Aerobús 35 minutes; metro L9 Sud cheaper and slower' ),
			array( 'label' => 'Late metro', 'detail' => 'All night on Saturdays, 2am Fridays, midnight otherwise' ),
		),
		'transport_tag'        => 'transport',
		'transport_more'       => 'All transport guides for Barcelona',

		/* Costs */
		'money_title'          => 'What Barcelona costs',
		'money_body'           => "Barcelona is the most expensive city in Spain for a bed, and the gap widens in summer. Dorms start around €18 in the quieter months and can double in August. Eixample and Sants are consistently cheaper than the old city for the sake of a short walk.\n\nEverything else is normal Spanish pricing if you stay off the tourist strips. A menú del día is €12–16, a coffee €1.50–2, a beer €3–4, a metro journey about €1.20 on a T-casual. The big variable is sightseeing: the Sagrada Família and Park Güell together are the better part of €50.",
		'money_points'         => array(
			array( 'label' => 'Dorm bed', 'detail' => 'From €18, doubling in August' ),
			array( 'label' => 'A day, all in', 'detail' => '€55–75 outside peak season' ),
			array( 'label' => 'Sagrada Família', 'detail' => 'Around €26, more with a tower' ),
			array( 'label' => 'Cheaper beds', 'detail' => 'Eixample and Sants, a short walk out' ),
		),
		'money_tag'            => 'budget',
		'money_more'           => 'All budget guides for Barcelona',

		/* Safety */
		'safety_title'         => 'Safety and scams',
		'safety_body'          => "Barcelona is safe to walk around, including alone and at night in the central neighbourhoods. What it does have is Europe's most determined pickpockets, and being relaxed about it is how people lose phones.\n\nThe pattern is always distraction: someone asking directions, a spilled drink, a crowd forming around you on the metro. Las Ramblas, the L3 metro line, the beach and busy bars are where it happens. Keep your phone out of your back pocket, your bag in front of you and in sight, and nothing on the sand while you swim.\n\nBeyond that it is an ordinary city. Emergency services are 112 with English-speaking operators, and pharmacies handle small problems without an appointment.",
		'safety_points'        => array(
			array( 'label' => 'Emergency', 'detail' => '112, free from any phone' ),
			array( 'label' => 'Hotspots', 'detail' => 'Las Ramblas, metro L3, the beach, crowded bars' ),
			array( 'label' => 'The tell', 'detail' => 'Any sudden distraction is the setup' ),
			array( 'label' => 'At the beach', 'detail' => 'Never leave a bag while you swim' ),
		),
		'safety_tag'           => 'safety',
		'safety_more'          => 'All safety guides for Barcelona',

		/* Day trips */
		'trips_title'          => 'Worth leaving the city for',
		'trips_intro'          => 'All of these are doable in a day on public transport.',
		'trips'                => array(
			array( 'name' => 'Montserrat', 'time' => '1 hour', 'how' => 'Train from Plaça Espanya, then rack railway or cable car', 'blurb' => 'A monastery on a serrated mountain, with walking trails above it.', 'link' => '', 'image' => '' ),
			array( 'name' => 'Girona', 'time' => '40 minutes', 'how' => 'High-speed train from Sants', 'blurb' => 'Medieval walls, a Jewish quarter and the cathedral steps from the TV series.', 'link' => '', 'image' => '' ),
			array( 'name' => 'Sitges', 'time' => '40 minutes', 'how' => 'Rodalies train from Sants or Passeig de Gràcia', 'blurb' => 'Beach town with seventeen beaches and the best nightlife outside the city.', 'link' => '', 'image' => '' ),
			array( 'name' => 'Costa Brava', 'time' => '1.5 hours', 'how' => 'Bus from Estació del Nord', 'blurb' => 'Coves, cliff paths and water clear enough to see the bottom.', 'link' => '', 'image' => '' ),
		),
		'trips_tag'            => 'day-trips',
		'trips_more'           => 'All day trips from Barcelona',

		/* When to go */
		'season_title'         => 'When to go',
		'season_intro'         => 'Barcelona has no bad month, but it has two very different ones: August, when the city belongs to visitors, and January, when it belongs to itself.',
		'season_rows'          => array(
			array( 'month' => 'January', 'high' => '14', 'crowd' => '1', 'note' => 'Cheapest month, and the city feels local again' ),
			array( 'month' => 'February', 'high' => '15', 'crowd' => '1', 'note' => 'Quiet. Calçots are in season' ),
			array( 'month' => 'March', 'high' => '17', 'crowd' => '2', 'note' => 'Warming up, still calm' ),
			array( 'month' => 'April', 'high' => '19', 'crowd' => '3', 'note' => 'Sant Jordi on the 23rd fills the streets with books' ),
			array( 'month' => 'May', 'high' => '22', 'crowd' => '3', 'note' => 'The best month. Primavera Sound at the end' ),
			array( 'month' => 'June', 'high' => '26', 'crowd' => '4', 'note' => 'Beach weather, Sónar, and prices climbing' ),
			array( 'month' => 'July', 'high' => '29', 'crowd' => '5', 'note' => 'Hot and full' ),
			array( 'month' => 'August', 'high' => '29', 'crowd' => '5', 'note' => 'Peak everything. Locals leave, prices do not' ),
			array( 'month' => 'September', 'high' => '26', 'crowd' => '4', 'note' => 'La Mercè, warm sea, thinning crowds' ),
			array( 'month' => 'October', 'high' => '22', 'crowd' => '3', 'note' => 'The other best month' ),
			array( 'month' => 'November', 'high' => '18', 'crowd' => '2', 'note' => 'Quiet and mild, some rain' ),
			array( 'month' => 'December', 'high' => '15', 'crowd' => '2', 'note' => 'Santa Llúcia market, then a Christmas spike' ),
		),
		'season_note'          => 'Average highs, rounded. The sea is warm enough to swim from June to October.',

		/* Events */
		'events_title'         => 'What is on while you are there',
		'events_intro'         => 'Barcelona builds its year around these, and they are also the weeks a bed doubles in price. Both facts are worth planning around.',
		'events_source'        => 'api',
		'events_count'         => '8',
		'events_cache'         => '24',
		'events_cta_label'     => 'All events in Barcelona',
		'events_cta_url'       => 'https://www.hostelworld.com/events/europe/spain/barcelona/',
		'events'               => array(
			array( 'name' => 'La Mercè', 'dates' => 'Late September', 'place' => 'Across the city', 'blurb' => 'Human towers, fire runs and free concerts. The city\'s own festival.', 'image' => '', 'href' => '' ),
			array( 'name' => 'Sant Jordi', 'dates' => '23 April', 'place' => 'Las Ramblas and everywhere', 'blurb' => 'Books and roses, and the whole city outside.', 'image' => '', 'href' => '' ),
			array( 'name' => 'Primavera Sound', 'dates' => 'Late May or early June', 'place' => 'Parc del Fòrum', 'blurb' => 'One of Europe\'s biggest music festivals. Book beds months out.', 'image' => '', 'href' => '' ),
		),

		/* Budget calculator */
		'budget_title'         => 'What your trip will cost',
		'budget_intro'         => 'Per night, for one person, based on Barcelona prices rather than Spanish averages.',
		'budget_rows'          => array(
			array( 'style' => 'Shoestring', 'bed' => '20', 'food' => '18', 'transport' => '4', 'doing' => '6' ),
			array( 'style' => 'Middle of the road', 'bed' => '30', 'food' => '32', 'transport' => '6', 'doing' => '15' ),
			array( 'style' => 'Comfortable', 'bed' => '48', 'food' => '50', 'transport' => '10', 'doing' => '25' ),
		),
		'budget_currency'      => '€',
		'budget_note'          => 'Excludes flights and the big ticketed sights. Add around €50 if you are doing both the Sagrada Família and Park Güell.',

		/* Where to stay */
		'hostels_title'        => 'Where to stay',
		'hostels_intro'        => 'The highest-rated hostels in Barcelona, straight from Hostelworld. Prices and availability are live on each property page.',
		'hostels_cta_label'    => 'All hostels in Barcelona',
		'hostels_cta_url'      => 'https://www.hostelworld.com/hostels/Europe/Spain/Barcelona',
		'hostels_source'       => 'api',
		'hostels_count'        => '12',
		'hostels_cache'        => '12',
		'picks'                => array(
			array( 'label' => 'Best for solo travellers', 'name' => '', 'why' => '', 'link' => '' ),
			array( 'label' => 'Best for a party', 'name' => '', 'why' => '', 'link' => '' ),
			array( 'label' => 'Quietest', 'name' => '', 'why' => '', 'link' => '' ),
		),
		'hostels'              => array(),

		/* FAQs */
		'faq_title'            => 'Barcelona questions',
		'faq_note'             => 'The ones we get asked most.',
		'faqs'                 => array(
			array( 'q' => 'How many days do you need in Barcelona?', 'a' => 'Three as a minimum: one for Gaudí, one for the old city and the beach, one for a neighbourhood off the usual list. A week lets you add Montserrat, Girona or the Costa Brava without rushing anything.' ),
			array( 'q' => 'Where should I stay in Barcelona?', 'a' => 'The Gothic Quarter or El Born if it is your first trip and you want everything on your doorstep. Eixample for a few euros less and quieter nights. Gràcia if you are staying longer and want to feel like you live there.' ),
			array( 'q' => 'Is Barcelona expensive?', 'a' => 'For Spain, yes — it has the country\'s priciest dorm beds, from around €18 outside peak season and roughly double in August. Food and transport are normal Spanish prices as long as you avoid the tourist strips.' ),
			array( 'q' => 'Do I need to book the Sagrada Família in advance?', 'a' => 'Yes. It sells out days ahead for most of the year, and Park Güell\'s monumental zone has timed entry too. Turning up and queueing has not worked for years.' ),
			array( 'q' => 'Is Barcelona safe?', 'a' => 'Yes to walk around, including at night in the central neighbourhoods. Pickpocketing is the real risk and the city is known for it: Las Ramblas, the L3 metro, the beach and busy bars. Every attempt starts with a distraction.' ),
			array( 'q' => 'What language do they speak in Barcelona?', 'a' => 'Catalan and Spanish, both official. Signs are usually in Catalan first. English is widely spoken in the centre, and nobody expects you to speak Catalan.' ),
			array( 'q' => 'When is the cheapest time to visit Barcelona?', 'a' => 'January to March, outside the Christmas and Easter weeks. Late October and November are the better compromise: still mild, far cheaper than summer, and the city is calm.' ),
		),

		/* Closing CTA */
		'cta_title'            => 'Say yes to hostels, meet the world',
		'cta_body'             => 'Compare hostels across Barcelona — live prices, real reviews and free cancellation on most bookings. Then meet the people already there in the app.',
		'cta_buttons'          => array(
			array( 'label' => 'Find hostels in Barcelona', 'href' => 'https://www.hostelworld.com/hostels/Europe/Spain/Barcelona' ),
			array( 'label' => 'Get the app', 'href' => 'https://hostelworld.onelink.me/IdiK/f5a50nhd' ),
		),
		'newsletter_title'     => 'Get the good stuff monthly',
		'newsletter_body'      => 'New city guides, deals and route ideas. No filler.',
		'newsletter_shortcode' => '',
	);
}
