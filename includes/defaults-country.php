<?php
/**
 * Starting content for a country hub, written for Spain.
 *
 * Copy is a worked example to edit, not gospel. Anything that changes with
 * policy or season — entry rules, prices, emergency numbers — needs checking
 * before a hub goes live.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array Flat map of field key => default value.
 */
function hw_hub_country_defaults() {

	return array(

		/* Settings */
		'hub_term'              => 'spain',
		'parent_hub'            => 'europe',
		'scope_rows'            => '1',

		/* Hero */
		'hero_eyebrow'          => 'Country guide',
		'hero_h1'               => 'Spain',
		'hero_intro'            => 'Seventeen regions that argue about whether they are one country, 8,000km of coast, and dinner at ten. Spain is the easiest place in Europe to travel badly and the hardest to leave.',
		'hero_image'            => '',
		'hero_overlay'          => 'light',
		'hero_cta'              => array(
			array( 'label' => 'Pick a city', 'href' => '#cities' ),
			array( 'label' => 'What it costs', 'href' => '#money' ),
		),
		'stats'                 => array(
			array( 'value' => '17', 'label' => 'Regions, each its own thing' ),
			array( 'value' => '€18', 'label' => 'Dorm bed, from' ),
			array( 'value' => '4', 'label' => 'Official languages' ),
			array( 'value' => '300', 'label' => 'Days of sun in the south' ),
		),

		/* Overview */
		'intro_title'           => 'One country, several countries',
		'intro_body'            => "Spain does not travel as one place. The Basque Country has its own language and a food culture built on small plates eaten standing up. Catalonia looks to the sea and to France. Andalusia is Moorish, sun-bleached and slower. Galicia, in the north-west, is green, Celtic and rains like Ireland. Travellers who plan Spain as a single trip usually end up doing the coast and the three big cities, which is a fine holiday and about a quarter of the country.\n\nThe practical things are easy. Trains are fast and cheap if you book ahead, hostels are among the best in Europe, and almost every city is walkable. The adjustment is the clock: lunch runs from two, dinner rarely starts before nine, and nightlife genuinely begins at midnight. Fighting that schedule is the most common way to have a worse time than you should.\n\nIt is also a country where the season changes the trip completely. July and August in Seville reach 40°C and the cities empty as Spaniards go to the coast. Spring and autumn are the value months, and winter in the south is mild enough for a city break in shirtsleeves.",
		'facts'                 => array(
			array( 'label' => 'Currency', 'value' => 'Euro (€)' ),
			array( 'label' => 'Languages', 'value' => 'Spanish, plus Catalan, Basque and Galician where they are official' ),
			array( 'label' => 'Emergency number', 'value' => '112' ),
			array( 'label' => 'Plugs', 'value' => 'Type F, 230V' ),
			array( 'label' => 'Tipping', 'value' => 'Not expected. Round up if the service was good' ),
			array( 'label' => 'Tap water', 'value' => 'Safe to drink almost everywhere' ),
			array( 'label' => 'Best months', 'value' => 'April to June, September to October' ),
		),

		/* Cities */
		'cities_title'          => 'Where to go',
		'cities_intro'          => 'Most first trips take two or three of these. Trains link them all, and none of them needs more than four days unless you want it to.',
		'cities_live'           => '1',
		'cities'                => array(
			array( 'name' => 'Barcelona', 'blurb' => 'Gaudí, the Gothic Quarter and a beach in the city. Busiest and priciest.', 'guide' => '', 'image' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Madrid', 'blurb' => 'The best art in the country and a nightlife that outlasts everywhere else.', 'guide' => '', 'image' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Seville', 'blurb' => 'Flamenco, orange trees and Andalusia at its most itself. Avoid August.', 'guide' => '', 'image' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Valencia', 'blurb' => 'Paella where it was invented, a riverbed park, and fewer crowds than the coast.', 'guide' => '', 'image' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'Granada', 'blurb' => 'The Alhambra, free tapas with every drink, and the Sierra Nevada an hour away.', 'guide' => '', 'image' => '', 'quote' => '', 'itineraries' => '' ),
			array( 'name' => 'San Sebastián', 'blurb' => 'Pintxos bars, a perfect city beach and Basque cooking at its best.', 'guide' => '', 'image' => '', 'quote' => '', 'itineraries' => '' ),
		),

		/* Types of trip */
		'trips_title'           => 'What kind of trip is this?',
		'trips_intro'           => 'Pick one and the page reshuffles around it: what to do, where to go, and the cities that suit it best.',
		'trip_types'            => array(
			array(
				'name'    => 'City break',
				'colour'  => 'blue',
				'icon'    => 'city',
				'summary' => 'Three or four days, one city, no train journeys longer than an hour.',
				'doing'   => 'A morning at the Prado or the Picasso Museum; A free walking tour on day one to get your bearings; An evening tapas crawl instead of a sit-down dinner; A day trip out to Toledo, Girona or Ronda',
				'spots'   => 'Barcelona\'s Gothic Quarter; Madrid\'s Retiro and the museum triangle; Seville\'s Alcázar; Valencia\'s City of Arts and Sciences',
				'cities'  => 'Madrid; Barcelona; Seville; Valencia',
				'tag'     => 'city-break',
			),
			array(
				'name'    => 'Adventure',
				'colour'  => 'green',
				'icon'    => 'backpack',
				'summary' => 'Mountains, long walks and coastline, with cities as rest days.',
				'doing'   => 'Walk a stretch of the Camino de Santiago; Hike the Caminito del Rey above El Chorro; Canyoning in the Sierra de Guara; Surf the Atlantic coast around San Sebastián and Zarautz',
				'spots'   => 'Picos de Europa; Sierra Nevada; Ordesa y Monte Perdido; The Galician coast',
				'cities'  => 'Santiago de Compostela; Granada; San Sebastián; Oviedo',
				'tag'     => 'adventure',
			),
			array(
				'name'    => 'Beach',
				'colour'  => 'teal',
				'icon'    => 'sunglasses',
				'summary' => 'Coast and islands, where the season matters more than anywhere else.',
				'doing'   => 'Island-hop the Balearics by ferry; Swim the coves of the Costa Brava; Take the boat to Tarifa for the Atlantic side; Watch sunset at a chiringuito rather than a beach club',
				'spots'   => 'Costa Brava; Menorca\'s coves; Cabo de Gata; Tarifa',
				'cities'  => 'Palma; Valencia; Málaga; Cádiz',
				'tag'     => 'beach',
			),
			array(
				'name'    => 'Nightlife',
				'colour'  => 'purple',
				'icon'    => 'nightclub',
				'summary' => 'Late even by Spanish standards. Nothing starts before midnight.',
				'doing'   => 'Start with vermouth before dinner, not after; Madrid\'s Malasaña and Lavapiés for bars; Ibiza in shoulder season for a third of the price; A flamenco tablao that locals go to, not a dinner show',
				'spots'   => 'Malasaña, Madrid; El Born, Barcelona; Alameda, Seville; Ibiza and Formentera',
				'cities'  => 'Madrid; Barcelona; Ibiza; Seville',
				'tag'     => 'nightlife',
			),
			array(
				'name'    => 'Food',
				'colour'  => 'sunset',
				'icon'    => 'local-markets',
				'summary' => 'Four or five regional cuisines that share a country and not much else.',
				'doing'   => 'Pintxos bar-hopping in San Sebastián; Paella in Valencia, at lunch, never at night; Free tapas with every drink in Granada; A morning at Mercado de la Boqueria or La Ribera',
				'spots'   => 'San Sebastián\'s old town; Valencia\'s Ruzafa; Granada\'s Albaicín bars; Rioja wine country',
				'cities'  => 'San Sebastián; Valencia; Granada; Bilbao',
				'tag'     => 'food',
			),
			array(
				'name'    => 'Culture',
				'colour'  => 'pink',
				'icon'    => 'culture',
				'summary' => 'Eight centuries of Moorish Spain, then everything built on top of it.',
				'doing'   => 'The Alhambra, booked weeks ahead; The Mezquita in Córdoba; Gaudí beyond the Sagrada Família; A flamenco show in Jerez, where it comes from',
				'spots'   => 'Alhambra, Granada; Mezquita, Córdoba; Alcázar, Seville; Guggenheim, Bilbao',
				'cities'  => 'Granada; Córdoba; Seville; Bilbao',
				'tag'     => 'culture',
			),
		),

		/* Things to do */
		'do_title'              => 'Things to do in Spain',
		'do_body'               => "The honest advice is to do less. Spain rewards sitting in one place long enough to eat properly, and punishes the three-cities-in-five-days itinerary with a lot of time in train stations.\n\nIf you only do a handful of things: see the Alhambra, eat pintxos in San Sebastián, spend an evening in a Madrid neighbourhood bar with no plan, and get out to one small town. Ronda, Girona, Cudillero or Toledo will all tell you more about the country than another museum.",
		'do_points'             => array(
			array( 'label' => 'Book ahead', 'detail' => 'The Alhambra and Sagrada Família sell out weeks out in summer' ),
			array( 'label' => 'Free walking tours', 'detail' => 'In every city, tip-based, and the fastest way to orient yourself' ),
			array( 'label' => 'Museum hours', 'detail' => 'Many are free in the last two hours of the day' ),
		),
		'do_tag'                => 'things-to-do',
		'do_more'               => 'All things to do in Spain',

		/* Food */
		'food_title'            => 'Eating and drinking',
		'food_body'             => "There is no single Spanish cuisine. The north-west does seafood and cider, the Basque Country does pintxos, Valencia does rice, Andalusia does fried fish and gazpacho. Paella is Valencian and eaten at lunch; ordering it for dinner on a tourist strip is how you get a bad one.\n\nTapas culture varies too. In Granada and parts of León a small plate arrives free with every drink. In Barcelona and Madrid you pay for each one. Either way, the move is to have one and a drink, then move on to the next bar rather than settling in.\n\nMeal times are the real adjustment. Lunch is the big meal, from around two. Dinner starts at nine and kitchens often stay open past midnight. Most bars serve something all afternoon if you get the timing wrong.",
		'food_points'           => array(
			array( 'label' => 'Menú del día', 'detail' => 'Three courses at lunch, usually €12–15, the best value meal in the country' ),
			array( 'label' => 'Order at the bar', 'detail' => 'Often cheaper than the same thing at a table' ),
			array( 'label' => 'Vermut', 'detail' => 'The pre-lunch drink, and a whole social ritual on Sundays' ),
		),
		'food_tag'              => 'food',
		'food_more'             => 'All food guides for Spain',

		/* Nightlife */
		'nightlife_title'       => 'Going out',
		'nightlife_body'        => "Spanish nights run on a schedule that catches people out. Bars fill around eleven, clubs do not get going until two, and it is normal to leave at sunrise and get churros on the way home. Turning up to a club at midnight means having it to yourself.\n\nMadrid has the deepest scene, spread across neighbourhoods rather than a single strip: Malasaña for bars, Lavapiés for a younger crowd, Chueca for the best of the city's LGBTQ+ nightlife. Barcelona is beach clubs and El Born. Ibiza is its own economy, and far more affordable in May or October than in August.",
		'nightlife_points'      => array(
			array( 'label' => 'Getting home', 'detail' => 'Metros run all night on Saturdays in Madrid and Barcelona' ),
			array( 'label' => 'Guest lists', 'detail' => 'Most clubs cut entry cost if you sign up online beforehand' ),
			array( 'label' => 'Hostel bars', 'detail' => 'The easiest way into a night out if you are travelling alone' ),
		),
		'nightlife_tag'         => 'nightlife',
		'nightlife_more'        => 'All nightlife guides for Spain',

		/* Getting around */
		'transport_title'       => 'Getting around',
		'transport_body'        => "Spain has one of the best rail networks in Europe. AVE high-speed trains connect Madrid to Barcelona, Seville, Valencia and Málaga in two to three hours, and advance fares are a fraction of the walk-up price. Booking a month out is the single biggest saving on a Spanish trip.\n\nBuses cover everything the trains miss, especially in Andalusia and the north, and cost less again. Cities are walkable with good metros. A car is only worth it for the Picos, rural Andalusia or the Basque coast, where the driving is the point.",
		'transport_points'      => array(
			array( 'label' => 'Book early', 'detail' => 'AVE fares rise steeply as the date approaches' ),
			array( 'label' => 'Bus over train', 'detail' => 'Usually cheaper and often just as quick in the south' ),
			array( 'label' => 'Ferries', 'detail' => 'Overnight to the Balearics saves a night\'s accommodation' ),
		),
		'transport_tag'         => 'transport',
		'transport_more'        => 'All transport guides for Spain',

		/* Costs */
		'money_title'           => 'What it costs',
		'money_body'            => "Spain sits in the middle of Western Europe on price: cheaper than France or the Netherlands, more than Portugal or the Balkans. A dorm bed runs €18–28, more in Barcelona and on the coast in August, less inland and out of season.\n\nA realistic day with a dorm, a menú del día, tapas in the evening, local transport and one paid sight comes to €45–65. You can do it for less by eating at the bar and using the free museum hours, and much more if you are eating dinner in restaurants every night.",
		'money_points'          => array(
			array( 'label' => 'Dorm bed', 'detail' => '€18–28, peaking in August' ),
			array( 'label' => 'A day, all in', 'detail' => '€45–65 outside peak season' ),
			array( 'label' => 'Menú del día', 'detail' => '€12–15 for three courses at lunch' ),
			array( 'label' => 'Cards', 'detail' => 'Accepted almost everywhere, but small bars still like cash' ),
		),
		'money_tag'             => 'budget',
		'money_more'            => 'All budget guides for Spain',

		/* Safety */
		'safety_title'          => 'Staying safe',
		'safety_body'           => "Spain is a safe country to travel, including alone. Violent crime against travellers is rare. Pickpocketing is not, and Barcelona in particular has a reputation for it: Las Ramblas, the metro, the beach and crowded bars are where it happens.\n\nThe practical version is dull and works. Keep your phone out of your back pocket, use the hostel locker, do not leave a bag on the back of a chair, and be aware that most scams involve someone creating a distraction. Emergency services are 112, English-speaking operators are usually available, and pharmacies handle minor problems without an appointment.",
		'safety_points'         => array(
			array( 'label' => 'Emergency', 'detail' => '112, free from any phone' ),
			array( 'label' => 'Pickpockets', 'detail' => 'Crowds, metros and beaches, not dark streets' ),
			array( 'label' => 'Solo travel', 'detail' => 'Widely done, with an established hostel scene in every city' ),
			array( 'label' => 'Beaches', 'detail' => 'Never leave bags unattended while you swim' ),
		),
		'safety_tag'            => 'safety',
		'safety_more'           => 'All safety guides for Spain',

		/* Entry and documents */
		'entry_title'           => 'Entry and documents',
		'entry_body'            => "Spain is in the Schengen Area. Most visitors from outside the EU can stay 90 days in any 180-day period without a visa, and your passport generally needs to be valid for three months beyond the date you intend to leave. EU, EEA and Swiss citizens can travel on a national ID card.\n\nRules change, and they differ by nationality, so check your own government's travel advice and the Spanish consulate before you book rather than trusting a blog post, this one included. Hostels are legally required to register guests, so bring the passport or ID you booked with.",
		'entry_points'          => array(
			array( 'label' => 'Schengen', 'detail' => '90 days in any 180 for most non-EU visitors' ),
			array( 'label' => 'At check-in', 'detail' => 'Hostels must register your passport or national ID' ),
			array( 'label' => 'Health cover', 'detail' => 'EHIC or GHIC for EU and UK visitors; insurance otherwise' ),
			array( 'label' => 'Check first', 'detail' => 'Entry rules depend on your nationality and do change' ),
		),
		'entry_tag'             => '',
		'entry_more'            => '',

		/* When to go */
		'season_title'          => 'When to go',
		'season_intro'          => 'Spain has three seasons that matter: the shoulder months when everything is comfortable and cheap, high summer when the south is too hot to walk around in, and winter, which is mild in the south and genuinely cold in the middle.',
		'season_rows'           => array(
			array( 'month' => 'January', 'high' => '11', 'crowd' => '1', 'note' => 'Cheapest month. Cold in Madrid, mild on the coast' ),
			array( 'month' => 'February', 'high' => '13', 'crowd' => '1', 'note' => 'Carnival, and almost no queues' ),
			array( 'month' => 'March', 'high' => '16', 'crowd' => '2', 'note' => 'Las Fallas in Valencia' ),
			array( 'month' => 'April', 'high' => '18', 'crowd' => '3', 'note' => 'Semana Santa and Feria push prices up' ),
			array( 'month' => 'May', 'high' => '22', 'crowd' => '3', 'note' => 'The best month almost everywhere' ),
			array( 'month' => 'June', 'high' => '28', 'crowd' => '4', 'note' => 'Hot in the south, perfect in the north' ),
			array( 'month' => 'July', 'high' => '33', 'crowd' => '5', 'note' => 'Peak prices. Coast over cities' ),
			array( 'month' => 'August', 'high' => '33', 'crowd' => '5', 'note' => 'Cities empty out, prices do not' ),
			array( 'month' => 'September', 'high' => '28', 'crowd' => '4', 'note' => 'Sea still warm, crowds thinning' ),
			array( 'month' => 'October', 'high' => '23', 'crowd' => '3', 'note' => 'The other best month' ),
			array( 'month' => 'November', 'high' => '17', 'crowd' => '2', 'note' => 'Quiet, cheap, occasional rain' ),
			array( 'month' => 'December', 'high' => '13', 'crowd' => '2', 'note' => 'Markets, then a spike over Christmas' ),
		),
		'season_note'           => 'Temperatures are rough national averages and vary a lot between Seville and Bilbao. Check the city you are going to before you pack.',

		/* Budget calculator */
		'budget_title'          => 'What your trip will cost',
		'budget_intro'          => 'Pick how you travel and how long you are going for. These are per-night figures for one person, based on what Spain actually costs rather than what looks good in a table.',
		'budget_rows'           => array(
			array( 'style' => 'Shoestring', 'bed' => '18', 'food' => '15', 'transport' => '4', 'doing' => '5' ),
			array( 'style' => 'Middle of the road', 'bed' => '25', 'food' => '28', 'transport' => '7', 'doing' => '12' ),
			array( 'style' => 'Comfortable', 'bed' => '40', 'food' => '45', 'transport' => '12', 'doing' => '20' ),
		),
		'budget_currency'       => '€',
		'budget_note'           => 'Excludes flights and long-distance trains between cities. Add roughly €30–60 for each intercity hop booked in advance.',

		/* What's on */
		'events_title'          => 'What is on while you are there',
		'events_intro'          => 'Spain builds its year around these. They are also the dates that will double the price of a bed if you turn up without a booking, so they are worth planning around either way.',
		'events_source'         => 'api',
		'events_country'        => 'spain',
		'events_count'          => '8',
		'events_cache'          => '24',
		'events_cta_label'      => 'All events in Spain',
		'events_cta_url'        => 'https://www.hostelworld.com/events/europe/spain/',
		'events'                => array(
			array( 'name' => 'Las Fallas', 'dates' => 'Mid-March', 'place' => 'Valencia', 'blurb' => 'Giant satirical sculptures, built for a year and burned in one night.', 'image' => '', 'href' => '' ),
			array( 'name' => 'Feria de Abril', 'dates' => 'April', 'place' => 'Seville', 'blurb' => 'Flamenco dresses, horses and casetas, two weeks after Easter.', 'image' => '', 'href' => '' ),
			array( 'name' => 'San Fermín', 'dates' => 'Early July', 'place' => 'Pamplona', 'blurb' => 'The bull run, and a week of the city not going to bed.', 'image' => '', 'href' => '' ),
			array( 'name' => 'La Tomatina', 'dates' => 'Last Wednesday in August', 'place' => 'Buñol', 'blurb' => 'One hour, 150,000 tomatoes, ticketed now so plan ahead.', 'image' => '', 'href' => '' ),
			array( 'name' => 'La Mercè', 'dates' => 'Late September', 'place' => 'Barcelona', 'blurb' => 'Human towers, fire runs and free concerts across the city.', 'image' => '', 'href' => '' ),
		),

		/* Hostels */
		'hostels_title'         => 'Where to stay',
		'hostels_intro'         => 'The highest-rated hostels across Spain, straight from Hostelworld. Prices and availability are live on each property page.',
		'hostels_cta_label'     => 'All hostels in Spain',
		'hostels_cta_url'       => 'https://www.hostelworld.com/hostels/Europe/Spain',
		'hostels_source'        => 'api',
		'hostels_location_type' => 'country',
		'hostels_location_id'   => '196',
		'hostels_currency'      => 'EUR',
		'hostels_count'         => '12',
		'hostels_cache'         => '12',
		'hostels'               => array(),

		/* FAQs */
		'faq_title'             => 'Spain travel questions',
		'faq_note'              => 'The ones we get asked most.',
		'faqs'                  => array(
			array( 'q' => 'How many days do you need in Spain?', 'a' => 'Ten days covers two cities properly with a day trip from each. A fortnight lets you add the coast or the north. Under a week, pick one city and stay there rather than moving every second day.' ),
			array( 'q' => 'What is the cheapest time to visit Spain?', 'a' => 'January to March, outside the Christmas and Easter weeks. Late September to October is the better trade: prices well off their August peak, the sea still warm, and the cities comfortable to walk in.' ),
			array( 'q' => 'Is Spain safe for solo travellers?', 'a' => 'Yes, and it is one of the most popular solo destinations in Europe. Petty theft is the realistic risk rather than anything worse. The hostel scene is well established in every city, which makes meeting people easy if you want to.' ),
			array( 'q' => 'Do I need to speak Spanish?', 'a' => 'No, but a little goes a long way outside the big cities and tourist areas. English is widely spoken in Barcelona, Madrid and along the coast, less so in small towns and in the north.' ),
			array( 'q' => 'What is the best way to travel between cities?', 'a' => 'Trains for the main routes, booked as far ahead as you can, since AVE fares climb sharply near the date. Buses are cheaper and reach the places trains do not, particularly in Andalusia.' ),
			array( 'q' => 'When do Spanish people eat?', 'a' => 'Lunch from around two, and it is the big meal of the day. Dinner from nine, later at weekends. Most bars serve something in between, so you will not go hungry while you adjust.' ),
			array( 'q' => 'Is tipping expected in Spain?', 'a' => 'No. Rounding up or leaving small change for good service is normal, and anything more is unusual outside high-end restaurants.' ),
		),

		/* Closing CTA */
		'cta_title'             => 'Say yes to hostels, meet the world',
		'cta_body'              => 'Compare hostels across Spain — live prices, real reviews and free cancellation on most bookings. Then meet the people already there in the app.',
		'cta_buttons'           => array(
			array( 'label' => 'Find hostels in Spain', 'href' => 'https://www.hostelworld.com/hostels/Europe/Spain' ),
			array( 'label' => 'Get the app', 'href' => 'https://hostelworld.onelink.me/IdiK/f5a50nhd' ),
		),
		'newsletter_title'      => 'Get the good stuff monthly',
		'newsletter_body'       => 'New city guides, deals and route ideas. No filler.',
		'newsletter_shortcode'  => '',
	);
}
