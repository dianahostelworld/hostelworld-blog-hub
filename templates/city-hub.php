<?php
/**
 * City Hub
 *
 * Rendered by the Hostelworld Blog Hubs plugin when a Page uses the "City Hub"
 * template. All content comes from the "City hub content" panel on the Page.
 *
 * The country hub's shape, with the cities grid replaced by a neighbourhood
 * guide and a map, and more room for copy. Live data — hostel counts, ratings,
 * district prices, events — comes from Hostelworld's Static Pages API.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hub      = hw_hub_city_config();
$can_edit = current_user_can( 'edit_post', get_the_ID() );

$hub_label = hw_hub_page_title( get_the_ID() );
if ( '' === $hub_label ) {
	$hub_label = ucwords( str_replace( '-', ' ', $hub['hub_term'] ) );
}

// One lookup feeds the hero chips, the map centre and the district data.
$city      = hw_hub_api_city( $hub['city_slug'], $hub['country_slug'] );
$districts = ( $hub['areas_live'] && ! empty( $city['id'] ) ) ? hw_hub_api_districts( $city['id'], $hub['currency'] ) : array();

// Fetched once, used by both the map and the Where to stay carousel.
$live = ( 'manual' === $hub['hostels_source'] || empty( $city['id'] ) )
	? array()
	: hw_hub_api_city_hostels(
		array(
			'city_id'  => $city['id'],
			'currency' => $hub['currency'],
			'limit'    => $hub['hostels_count'] ? $hub['hostels_count'] : 12,
			'hours'    => $hub['hostels_cache'] ? $hub['hostels_cache'] : 12,
		)
	);

hw_hub_site_header();

if ( have_posts() ) {
	the_post();
}
?>

<main class="hw-hub hw-hub--city" id="hw-hub">

	<?php /* ---------- Hero ---------- */ ?>
	<?php
	$hero_markup  = hw_hub_image( $hub['hero_image'], $hub_label, 'full' );
	$hero_overlay = in_array( $hub['hero_overlay'], array( 'light', 'medium', 'strong' ), true ) ? $hub['hero_overlay'] : 'light';

	$chips = array();

	if ( $hub['live_stats'] && $city ) {
		if ( ! empty( $city['properties'] ) ) {
			$chips[] = array(
				'value' => number_format_i18n( $city['properties'] ),
				'label' => hw_hub_t( 'Hostels in the city' ),
			);
		}

		if ( ! empty( $city['rating'] ) ) {
			$chips[] = array(
				'value' => number_format_i18n( $city['rating'], 1 ),
				'label' => sprintf(
					/* translators: %s: number of reviews */
					hw_hub_t( 'Average, from %s reviews' ),
					number_format_i18n( $city['reviews'] )
				),
			);
		}
	}

	foreach ( $hub['stats'] as $stat ) {
		$chips[] = array( 'value' => $stat['value'], 'label' => $stat['label'] );
	}
	?>
	<section class="hw-hub__hero hw-hub__hero--veil-<?php echo esc_attr( $hero_overlay ); ?> hw-hub__hero--crumbs<?php echo $hero_markup ? '' : ' hw-hub__hero--plain'; ?>">
		<?php if ( $hero_markup ) : ?>
			<div class="hw-hub__hero-bg"><?php echo $hero_markup; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="hw-hub__hero-veil" aria-hidden="true"></div>
		<?php endif; ?>

		<?php hw_hub_render_breadcrumbs( get_the_ID(), $hub['parent_hub'] ); ?>

		<div class="hw-hub__wrap hw-hub__hero-inner">
			<?php if ( $hub['eyebrow'] ) : ?>
				<div class="hw-hub__hero-eyebrow"><?php echo esc_html( $hub['eyebrow'] ); ?></div>
			<?php endif; ?>

			<h1><?php echo esc_html( $hub['h1'] ); ?></h1>
			<p class="hw-hub__hero-intro"><?php echo esc_html( $hub['intro'] ); ?></p>

			<div class="hw-hub__hero-actions">
				<?php foreach ( $hub['hero_cta'] as $i => $cta ) : ?>
					<?php $cta_url = hw_hub_url( $cta['href'] ); ?>
					<?php if ( $cta_url ) : ?>
						<a class="hw-hub__btn<?php echo 0 === $i ? '' : ' hw-hub__btn--ghost'; ?>" href="<?php echo esc_url( $cta_url ); ?>" <?php echo hw_hub_track( 'hero_cta', $cta['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $cta['label'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php if ( $chips ) : ?>
				<div class="hw-hub__hero-stats">
					<?php foreach ( $chips as $chip ) : ?>
						<div class="hw-hub__hero-stat">
							<div class="hw-hub__hero-stat-value"><?php echo esc_html( $chip['value'] ); ?></div>
							<div class="hw-hub__hero-stat-label"><?php echo esc_html( $chip['label'] ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php /* ---------- Jump nav ---------- */ ?>
	<nav class="hw-hub__jump" aria-label="<?php echo esc_attr( hw_hub_t( 'On this page' ) ); ?>">
		<div class="hw-hub__wrap hw-hub__jump-inner">
			<button type="button" class="hw-hub__jump-btn" data-direction="prev" aria-label="<?php echo esc_attr( hw_hub_t( 'Scroll sections left' ) ); ?>" hidden>
				<?php echo hw_hub_icon( 'arrow-left', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>

			<div class="hw-hub__jump-scroll">
				<a href="#overview"><?php echo esc_html( hw_hub_t( 'Overview' ) ); ?></a>
				<a href="#areas"><?php echo esc_html( hw_hub_t( 'Neighbourhoods' ) ); ?></a>
				<?php if ( $hub['itin'] ) : ?>
					<a href="#itineraries"><?php echo esc_html( hw_hub_t( 'Itineraries' ) ); ?></a>
				<?php endif; ?>
				<?php foreach ( $hub['sections'] as $section ) : ?>
					<a href="#<?php echo esc_attr( $section['key'] ); ?>"><?php echo esc_html( $section['nav'] ); ?></a>
				<?php endforeach; ?>
				<?php if ( $hub['trips'] ) : ?>
					<a href="#daytrips"><?php echo esc_html( hw_hub_t( 'Day trips' ) ); ?></a>
				<?php endif; ?>
				<a href="#hostels"><?php echo esc_html( hw_hub_t( 'Where to stay' ) ); ?></a>
				<a href="#season"><?php echo esc_html( hw_hub_t( 'When to go' ) ); ?></a>
				<a href="#faqs"><?php echo esc_html( hw_hub_t( 'FAQs' ) ); ?></a>
			</div>

			<button type="button" class="hw-hub__jump-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'Scroll sections right' ) ); ?>" hidden>
				<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
		</div>
	</nav>

	<?php /* ---------- Overview and facts ---------- */ ?>
	<section id="overview" class="hw-hub__overview">
		<div class="hw-hub__wrap hw-hub__overview-grid">
			<div>
				<h2><?php echo esc_html( $hub['intro_title'] ); ?></h2>
				<?php
				$page_content = trim( get_post_field( 'post_content', get_the_ID() ) );

				if ( $page_content ) {
					the_content();
				} else {
					foreach ( $hub['intro_body'] as $para ) {
						echo '<p>' . esc_html( $para ) . '</p>';
					}
				}
				?>
			</div>

			<?php if ( $hub['facts'] ) : ?>
				<aside class="hw-hub__facts">
					<h3><?php echo esc_html( $hub_label ); ?> <?php echo esc_html( hw_hub_t( 'in short' ) ); ?></h3>
					<dl>
						<?php foreach ( $hub['facts'] as $fact ) : ?>
							<div class="hw-hub__fact">
								<dt><?php echo esc_html( $fact['label'] ); ?></dt>
								<dd><?php echo esc_html( $fact['value'] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</aside>
			<?php endif; ?>
		</div>
	</section>

	<?php /* ---------- Neighbourhoods and map ---------- */ ?>
	<?php if ( $hub['areas'] ) : ?>
		<?php
		$markers = array();

		foreach ( $hub['areas'] as $index => $area ) {
			if ( ! is_numeric( $area['lat'] ) || ! is_numeric( $area['lng'] ) ) {
				continue;
			}

			$markers[] = array(
				'id'   => 'area-' . $index,
				'name' => $area['name'],
				'lat'  => (float) $area['lat'],
				'lng'  => (float) $area['lng'],
				'note' => $area['suits'],
			);
		}
		?>
		<?php
		$pins = array();

		if ( $hub['areas_map'] && $hub['hostels_pins'] ) {
			foreach ( $live as $hostel ) {
				if ( empty( $hostel['lat'] ) || empty( $hostel['lng'] ) ) {
					continue;
				}

				$pins[] = array(
					'name'   => $hostel['name'],
					'lat'    => $hostel['lat'],
					'lng'    => $hostel['lng'],
					'url'    => $hostel['url'],
					'rating' => $hostel['rating'] ? number_format_i18n( $hostel['rating'], 1 ) : '',
					'price'  => hw_hub_price_label( $hostel['price'] ),
				);
			}
		}
		?>
		<section id="areas" class="hw-hub__areas">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $hub['areas_title'] ); ?></h2>
				<p class="hw-hub__lede"><?php echo esc_html( $hub['areas_intro'] ); ?></p>

				<div class="hw-hub__areas-grid">
					<div class="hw-hub__areas-list">
						<?php foreach ( $hub['areas'] as $index => $area ) : ?>
							<?php
							$key       = strtolower( trim( (string) $area['api'] ) );
							$area_live = ( $key && isset( $districts[ $key ] ) ) ? $districts[ $key ] : array();
							$link      = hw_hub_url( $area['link'] );
							$bits      = array();

							if ( ! empty( $area_live['properties'] ) ) {
								$bits[] = sprintf(
									/* translators: %s: number of hostels */
									_n( '%s hostel', '%s hostels', $area_live['properties'], 'hostelworld' ),
									number_format_i18n( $area_live['properties'] )
								);
							}

							if ( ! empty( $area_live['from'] ) ) {
								$bits[] = sprintf(
									/* translators: %s: price */
									hw_hub_t( 'from %s' ),
									hw_hub_price_label( array( 'value' => $area_live['from'], 'currency' => $area_live['currency'] ) )
								);
							}
							?>
							<article class="hw-hub__area" id="area-<?php echo esc_attr( $index ); ?>">
								<div class="hw-hub__area-head">
									<h3><?php echo esc_html( $area['name'] ); ?></h3>
									<?php if ( $area['suits'] ) : ?>
										<span class="hw-hub__area-suits"><?php echo esc_html( $area['suits'] ); ?></span>
									<?php endif; ?>
								</div>

								<?php if ( $area['blurb'] ) : ?>
									<p><?php echo esc_html( $area['blurb'] ); ?></p>
								<?php endif; ?>

								<div class="hw-hub__area-foot">
									<?php if ( $bits ) : ?>
										<span class="hw-hub__area-stats"><?php echo esc_html( implode( ' · ', $bits ) ); ?></span>
									<?php endif; ?>

									<?php if ( $link ) : ?>
										<a class="hw-hub__area-link" href="<?php echo esc_url( $link ); ?>" <?php echo hw_hub_track( 'area_hostels', $area['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
											<?php echo esc_html( hw_hub_t( 'Hostels here' ) ); ?>
											<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										</a>
									<?php endif; ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<?php if ( $hub['areas_map'] && $markers ) : ?>
						<div class="hw-hub__map-wrap">
							<div
								class="hw-hub__map"
								data-hw-map
								data-lat="<?php echo esc_attr( $city['lat'] ? $city['lat'] : $markers[0]['lat'] ); ?>"
								data-lng="<?php echo esc_attr( $city['lng'] ? $city['lng'] : $markers[0]['lng'] ); ?>"
								data-zoom="<?php echo esc_attr( $hub['areas_zoom'] ); ?>"
								data-markers="<?php echo esc_attr( wp_json_encode( $markers ) ); ?>"
								data-hostels="<?php echo esc_attr( wp_json_encode( $pins ) ); ?>"
							></div>
							<p class="hw-hub__map-note">
								<?php if ( $pins ) : ?>
									<?php
									printf(
										/* translators: %s: number of hostels */
										esc_html( hw_hub_t( 'Orange labels are neighbourhoods, dots are the %s top-rated hostels. Map data from OpenStreetMap contributors.' ) ),
										esc_html( number_format_i18n( count( $pins ) ) )
									);
									?>
								<?php else : ?>
									<?php echo esc_html( hw_hub_t( 'Map data from OpenStreetMap contributors.' ) ); ?>
								<?php endif; ?>
							</p>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Itineraries ---------- */ ?>
	<?php if ( $hub['itin'] ) : ?>
		<section id="itineraries" class="hw-hub__itin">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $hub['itin_title'] ); ?></h2>
				<p class="hw-hub__lede"><?php echo esc_html( $hub['itin_intro'] ); ?></p>

				<div class="hw-hub__itin-grid">
					<?php foreach ( $hub['itin'] as $itinerary ) : ?>
						<?php $itin_url = hw_hub_url( $itinerary['link'] ); ?>
						<?php if ( ! $itin_url && ! $can_edit ) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<?php echo hw_hub_link_open( $itin_url, 'hw-hub__itin-card' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<div class="hw-hub__thumb"><?php echo hw_hub_image( $itinerary['image'], $itinerary['label'], 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<div class="hw-hub__itin-body">
								<div class="hw-hub__itin-label">
									<?php echo hw_hub_icon( 'calendar', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( $itinerary['label'] ); ?></span>
								</div>
								<?php if ( $itinerary['blurb'] ) : ?>
									<p><?php echo esc_html( $itinerary['blurb'] ); ?></p>
								<?php endif; ?>
								<?php if ( ! $itin_url && $can_edit ) : ?>
									<span class="hw-hub__itin-missing"><?php echo esc_html( hw_hub_t( 'Only you can see this: no article linked yet.' ) ); ?></span>
								<?php endif; ?>
							</div>
						<?php echo hw_hub_link_close( $itin_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Content sections ---------- */ ?>
	<?php foreach ( $hub['sections'] as $index => $section ) : ?>
		<?php if ( ! $section['title'] && ! $section['body'] ) : ?>
			<?php continue; ?>
		<?php endif; ?>
		<section id="<?php echo esc_attr( $section['key'] ); ?>" class="hw-hub__block hw-hub__block--<?php echo 0 === $index % 2 ? 'light' : 'tint'; ?> hw-hub__block--<?php echo esc_attr( $section['colour'] ); ?>">
			<div class="hw-hub__wrap">
				<div class="hw-hub__block-grid">
					<div class="hw-hub__block-copy">
						<div class="hw-hub__block-badge">
							<?php echo hw_hub_icon( $section['icon'], 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $section['nav'] ); ?></span>
						</div>
						<h2><?php echo esc_html( $section['title'] ); ?></h2>
						<?php foreach ( $section['body'] as $para ) : ?>
							<p><?php echo esc_html( $para ); ?></p>
						<?php endforeach; ?>
					</div>

					<?php if ( $section['points'] || $section['art'] ) : ?>
						<aside class="hw-hub__points">
							<?php if ( $section['art'] ) : ?>
								<div class="hw-hub__points-art">
									<?php echo hw_hub_illustration( $section['art'], '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</div>
							<?php endif; ?>
							<?php foreach ( $section['points'] as $point ) : ?>
								<div class="hw-hub__point">
									<div class="hw-hub__point-label"><?php echo esc_html( $point['label'] ); ?></div>
									<div class="hw-hub__point-detail"><?php echo esc_html( $point['detail'] ); ?></div>
								</div>
							<?php endforeach; ?>
						</aside>
					<?php endif; ?>
				</div>

				<?php
				if ( $section['tag'] ) {
					hw_hub_render_carousel(
						array(
							'expression' => hw_hub_row_expression( $hub, $section['tag'] ),
							'label'      => sprintf(
								/* translators: 1: section heading, 2: city */
								hw_hub_t( 'More on %1$s in %2$s' ),
								strtolower( $section['nav'] ),
								$hub_label
							),
							'more'       => $section['more'],
							'can_edit'   => $can_edit,
						)
					);
				}
				?>
			</div>
		</section>
	<?php endforeach; ?>

	<?php /* ---------- Day trips ---------- */ ?>
	<?php if ( $hub['trips'] ) : ?>
		<section id="daytrips" class="hw-hub__daytrips">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $hub['trips_title'] ); ?></h2>
				<p class="hw-hub__lede"><?php echo esc_html( $hub['trips_intro'] ); ?></p>

				<div class="hw-hub__trip-grid">
					<?php foreach ( $hub['trips'] as $trip ) : ?>
						<?php $trip_url = hw_hub_url( $trip['link'] ); ?>
						<?php echo hw_hub_link_open( $trip_url, 'hw-hub__daytrip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<div class="hw-hub__thumb"><?php echo hw_hub_image( $trip['image'], $trip['name'], 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<div class="hw-hub__daytrip-body">
								<h3><?php echo esc_html( $trip['name'] ); ?></h3>
								<div class="hw-hub__daytrip-meta">
									<?php echo hw_hub_icon( 'train', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span><?php echo esc_html( trim( $trip['time'] . ( $trip['how'] ? ' · ' . $trip['how'] : '' ), ' ·' ) ); ?></span>
								</div>
								<?php if ( $trip['blurb'] ) : ?>
									<p><?php echo esc_html( $trip['blurb'] ); ?></p>
								<?php endif; ?>
							</div>
						<?php echo hw_hub_link_close( $trip_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>

				<?php
				if ( $hub['trips_tag'] ) {
					hw_hub_render_carousel(
						array(
							'expression' => hw_hub_row_expression( $hub, $hub['trips_tag'] ),
							'label'      => sprintf(
								/* translators: %s: city */
								hw_hub_t( 'Day trips from %s' ),
								$hub_label
							),
							'more'       => $hub['trips_more'],
							'can_edit'   => $can_edit,
						)
					);
				}
				?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- What's on ---------- */ ?>
	<?php
	$live_events = ( 'manual' === $hub['events_source'] || empty( $city['id'] ) )
		? array()
		: hw_hub_api_events(
			array(
				'scope'   => 'city',
				'country' => $city['id'],
				'limit'   => $hub['events_count'] ? $hub['events_count'] : 8,
				'hours'   => $hub['events_cache'] ? $hub['events_cache'] : 24,
			)
		);
	$events_cta = hw_hub_url( $hub['events_cta']['href'] );
	?>
	<?php if ( $live_events || $hub['events'] ) : ?>
		<section id="events" class="hw-hub__events-country">
			<div class="hw-hub__wrap">
				<div class="hw-hub__events-head">
					<div>
						<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( "What's on" ) ); ?></div>
						<h2><?php echo esc_html( $hub['events_title'] ); ?></h2>
						<p><?php echo esc_html( $hub['events_intro'] ); ?></p>
					</div>

					<div class="hw-hub__carousel-nav">
						<button type="button" class="hw-hub__carousel-btn" data-direction="prev" aria-label="<?php echo esc_attr( hw_hub_t( 'Previous events' ) ); ?>">
							<?php echo hw_hub_icon( 'arrow-left', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
						<button type="button" class="hw-hub__carousel-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'More events' ) ); ?>">
							<?php echo hw_hub_icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
					</div>
				</div>

				<div class="hw-hub__carousel" data-hw-carousel>
					<?php if ( $live_events ) : ?>
						<?php foreach ( $live_events as $event ) : ?>
							<div class="hw-hub__event-card">
								<div class="hw-hub__event-photo">
									<?php if ( $event['image'] ) : ?>
										<img src="<?php echo esc_url( $event['image'] ); ?>" alt="<?php echo esc_attr( $event['name'] ); ?>" loading="lazy">
									<?php endif; ?>
									<?php if ( $event['pick'] ) : ?>
										<span class="hw-hub__event-pick"><?php echo esc_html( hw_hub_t( 'Staff pick' ) ); ?></span>
									<?php endif; ?>
								</div>
								<div class="hw-hub__event-body">
									<div class="hw-hub__event-when"><?php echo esc_html( hw_hub_event_dates( $event['start'], $event['end'] ) ); ?></div>
									<h3><?php echo esc_html( $event['name'] ); ?></h3>
									<?php if ( $event['blurb'] ) : ?>
										<p><?php echo esc_html( $event['blurb'] ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( $hub['events'] as $event ) : ?>
							<?php $event_url = hw_hub_url( $event['href'] ); ?>
							<?php $event_photo = hw_hub_image( $event['image'], $event['name'], 'medium_large' ); ?>
							<?php echo hw_hub_link_open( $event_url, 'hw-hub__event-card' . ( $event_photo ? '' : ' hw-hub__event-card--text' ), $event_url ? hw_hub_track( 'event_card', $event['name'] ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<?php if ( $event_photo ) : ?>
								<div class="hw-hub__event-photo"><?php echo $event_photo; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<?php else : ?>
								<div class="hw-hub__event-badge" aria-hidden="true"><?php echo hw_hub_icon( 'calendar', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<?php endif; ?>
								<div class="hw-hub__event-body">
									<div class="hw-hub__event-when"><?php echo esc_html( $event['dates'] ); ?></div>
									<h3><?php echo esc_html( $event['name'] ); ?></h3>
									<?php if ( $event['place'] ) : ?>
										<div class="hw-hub__event-place">
											<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<span><?php echo esc_html( $event['place'] ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( $event['blurb'] ) : ?>
										<p><?php echo esc_html( $event['blurb'] ); ?></p>
									<?php endif; ?>
								</div>
							<?php echo hw_hub_link_close( $event_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<?php if ( $events_cta ) : ?>
					<div class="hw-hub__hostels-cta">
						<a class="hw-hub__btn" href="<?php echo esc_url( $events_cta ); ?>" rel="noopener" <?php echo hw_hub_track( 'events_cta', $hub['events_cta']['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<?php echo esc_html( $hub['events_cta']['label'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Where to stay ---------- */ ?>
	<?php
	$picks = array_filter(
		$hub['picks'],
		function ( $pick ) {
			return ! empty( $pick['name'] );
		}
	);
	?>
	<?php if ( $live || $hub['hostels'] || $picks || $can_edit ) : ?>
		<section id="hostels" class="hw-hub__hostels">
			<div class="hw-hub__wrap">
				<div class="hw-hub__hostels-head">
					<div>
						<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( 'Hostels' ) ); ?></div>
						<h2><?php echo esc_html( $hub['hostels_title'] ); ?></h2>
						<p><?php echo esc_html( $hub['hostels_intro'] ); ?></p>
					</div>

					<div class="hw-hub__carousel-nav">
						<button type="button" class="hw-hub__carousel-btn" data-direction="prev" aria-label="<?php echo esc_attr( hw_hub_t( 'Previous hostels' ) ); ?>">
							<?php echo hw_hub_icon( 'arrow-left', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
						<button type="button" class="hw-hub__carousel-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'More hostels' ) ); ?>">
							<?php echo hw_hub_icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
					</div>
				</div>

				<?php if ( $picks ) : ?>
					<div class="hw-hub__picks">
						<?php foreach ( $picks as $pick ) : ?>
							<?php $pick_url = hw_hub_url( $pick['link'] ); ?>
							<?php echo hw_hub_link_open( $pick_url, 'hw-hub__pick', $pick_url ? hw_hub_track( 'hostel_pick', $pick['name'] ?? '' ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<div class="hw-hub__pick-label"><?php echo esc_html( $pick['label'] ); ?></div>
								<h3><?php echo esc_html( $pick['name'] ); ?></h3>
								<?php if ( $pick['why'] ) : ?>
									<p><?php echo esc_html( $pick['why'] ); ?></p>
								<?php endif; ?>
							<?php echo hw_hub_link_close( $pick_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( ! $live && ! $hub['hostels'] ) : ?>
					<div class="hw-hub__empty">
						<?php echo hw_hub_illustration( 'search-map', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo esc_html( hw_hub_t( 'Only you can see this. The live feed returned nothing — check the Hostelworld city name under Settings.' ) ); ?>
					</div>
				<?php endif; ?>

				<div class="hw-hub__carousel" data-hw-carousel>
					<?php if ( $live ) : ?>
						<?php foreach ( $live as $hostel ) : ?>
							<?php $price = hw_hub_price_label( $hostel['price'] ); ?>
							<?php echo hw_hub_link_open( $hostel['url'], 'hw-hub__hostel', 'rel="noopener" ' . hw_hub_track( 'hostel_card', $hostel['name'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<div class="hw-hub__hostel-photo">
									<?php if ( $hostel['image'] ) : ?>
										<img src="<?php echo esc_url( $hostel['image'] ); ?>" alt="<?php echo esc_attr( $hostel['name'] ); ?>" loading="lazy">
									<?php endif; ?>
									<?php if ( ! empty( $hostel['eco'] ) ) : ?>
										<span class="hw-hub__hostel-eco">
											<?php echo hw_hub_icon( 'checkmark', 12 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php echo esc_html( hw_hub_t( 'Sustainable' ) ); ?>
										</span>
									<?php endif; ?>
									<?php if ( $hostel['rating'] ) : ?>
										<span class="hw-hub__hostel-rating">
											<?php echo hw_hub_icon( 'star', 13 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<strong><?php echo esc_html( number_format_i18n( $hostel['rating'], 1 ) ); ?></strong>
											<span><?php echo esc_html( number_format_i18n( $hostel['reviews'] ) ); ?></span>
										</span>
									<?php endif; ?>
								</div>
								<div class="hw-hub__hostel-body">
									<h3><?php echo esc_html( $hostel['name'] ); ?></h3>
									<?php if ( $price ) : ?>
										<div class="hw-hub__hostel-price">
											<?php
											printf(
												/* translators: %s: price with currency symbol */
												esc_html( $hostel['dorm'] ? hw_hub_t( 'Dorms from %s a night' ) : hw_hub_t( 'Rooms from %s a night' ) ),
												'<strong>' . esc_html( $price ) . '</strong>'
											);
											?>
										</div>
									<?php endif; ?>
									<span class="hw-hub__hostel-link">
										<?php echo esc_html( hw_hub_t( 'See dates and book' ) ); ?>
										<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</span>
								</div>
							<?php echo hw_hub_link_close( $hostel['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( $hub['hostels'] as $hostel ) : ?>
							<?php $hostel_url = hw_hub_url( $hostel['href'] ); ?>
							<?php echo hw_hub_link_open( $hostel_url, 'hw-hub__hostel', $hostel_url ? 'rel="noopener" ' . hw_hub_track( 'hostel_card', $hostel['name'] ) : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<div class="hw-hub__hostel-photo"><?php echo hw_hub_image( $hostel['image'], $hostel['name'], 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								<div class="hw-hub__hostel-body">
									<h3><?php echo esc_html( $hostel['name'] ); ?></h3>
									<?php if ( $hostel['place'] ) : ?>
										<div class="hw-hub__hostel-place">
											<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<span><?php echo esc_html( $hostel['place'] ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( $hostel['note'] ) : ?>
										<p><?php echo esc_html( $hostel['note'] ); ?></p>
									<?php endif; ?>
								</div>
							<?php echo hw_hub_link_close( $hostel_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<?php $hostels_cta_url = hw_hub_url( $hub['hostels_cta']['href'] ); ?>
				<?php if ( $hostels_cta_url ) : ?>
					<div class="hw-hub__hostels-cta">
						<a class="hw-hub__btn" href="<?php echo esc_url( $hostels_cta_url ); ?>" rel="noopener" <?php echo hw_hub_track( 'hostels_cta', $hub['hostels_cta']['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
							<?php echo esc_html( $hub['hostels_cta']['label'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- When to go ---------- */ ?>
	<?php if ( $hub['season_rows'] ) : ?>
		<section id="season" class="hw-hub__season-strip">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $hub['season_title'] ); ?></h2>
				<p class="hw-hub__lede"><?php echo esc_html( $hub['season_intro'] ); ?></p>

				<div class="hw-hub__months" role="list">
					<?php
					$temps = array_map( 'intval', wp_list_pluck( $hub['season_rows'], 'high' ) );
					$max   = $temps ? max( $temps ) : 1;
					$min   = $temps ? min( $temps ) : 0;
					$span  = max( 1, $max - $min );
					?>
					<?php foreach ( $hub['season_rows'] as $month ) : ?>
						<?php
						$high   = (int) $month['high'];
						$crowd  = max( 1, min( 5, (int) $month['crowd'] ) );
						$height = 30 + round( ( ( $high - $min ) / $span ) * 70 );
						?>
						<div class="hw-hub__month" role="listitem">
							<div class="hw-hub__month-bar-wrap">
								<span class="hw-hub__month-temp"><?php echo esc_html( $high ); ?>°</span>
								<div class="hw-hub__month-bar hw-hub__month-bar--<?php echo esc_attr( $crowd ); ?>" style="height: <?php echo esc_attr( $height ); ?>%"></div>
							</div>
							<div class="hw-hub__month-name"><?php echo esc_html( substr( $month['month'], 0, 3 ) ); ?></div>
							<div class="hw-hub__month-note"><?php echo esc_html( $month['note'] ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="hw-hub__months-key">
					<span><?php echo esc_html( hw_hub_t( 'Bar height is temperature. Colour is how busy it gets:' ) ); ?></span>
					<span class="hw-hub__key-swatch hw-hub__key-swatch--1"></span><span><?php echo esc_html( hw_hub_t( 'quiet' ) ); ?></span>
					<span class="hw-hub__key-swatch hw-hub__key-swatch--3"></span><span><?php echo esc_html( hw_hub_t( 'busy' ) ); ?></span>
					<span class="hw-hub__key-swatch hw-hub__key-swatch--5"></span><span><?php echo esc_html( hw_hub_t( 'peak' ) ); ?></span>
				</div>

				<?php if ( $hub['season_note'] ) : ?>
					<p class="hw-hub__note"><?php echo esc_html( $hub['season_note'] ); ?></p>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Budget calculator ---------- */ ?>
	<?php if ( $hub['budget_rows'] ) : ?>
		<section id="budget" class="hw-hub__budget">
			<div class="hw-hub__wrap">
				<div class="hw-hub__budget-grid">
					<div>
						<h2><?php echo esc_html( $hub['budget_title'] ); ?></h2>
						<p><?php echo esc_html( $hub['budget_intro'] ); ?></p>
						<?php if ( $hub['budget_note'] ) : ?>
							<p class="hw-hub__note"><?php echo esc_html( $hub['budget_note'] ); ?></p>
						<?php endif; ?>
					</div>

					<div class="hw-hub__calc" data-hw-calc data-currency="<?php echo esc_attr( $hub['budget_currency'] ); ?>">
						<div class="hw-hub__calc-styles">
							<?php foreach ( $hub['budget_rows'] as $i => $style ) : ?>
								<button
									type="button"
									class="hw-hub__calc-style<?php echo 0 === $i ? ' is-active' : ''; ?>"
									data-bed="<?php echo esc_attr( (float) $style['bed'] ); ?>"
									data-food="<?php echo esc_attr( (float) $style['food'] ); ?>"
									data-transport="<?php echo esc_attr( (float) $style['transport'] ); ?>"
									data-doing="<?php echo esc_attr( (float) $style['doing'] ); ?>"
								><?php echo esc_html( $style['style'] ); ?></button>
							<?php endforeach; ?>
						</div>

						<label class="hw-hub__calc-nights">
							<span><?php echo esc_html( hw_hub_t( 'Nights' ) ); ?></span>
							<input type="range" min="1" max="30" value="3" data-hw-nights>
							<output data-hw-nights-out>3</output>
						</label>

						<div class="hw-hub__calc-total">
							<div class="hw-hub__calc-figure" data-hw-total>—</div>
							<div class="hw-hub__calc-per" data-hw-per></div>
						</div>

						<ul class="hw-hub__calc-breakdown">
							<li><span><?php echo esc_html( hw_hub_t( 'Beds' ) ); ?></span><span data-hw-line="bed">—</span></li>
							<li><span><?php echo esc_html( hw_hub_t( 'Food and drink' ) ); ?></span><span data-hw-line="food">—</span></li>
							<li><span><?php echo esc_html( hw_hub_t( 'Getting around' ) ); ?></span><span data-hw-line="transport">—</span></li>
							<li><span><?php echo esc_html( hw_hub_t( 'Doing things' ) ); ?></span><span data-hw-line="doing">—</span></li>
						</ul>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- FAQs ---------- */ ?>
	<section id="faqs" class="hw-hub__faqs">
		<div class="hw-hub__wrap">
			<div class="hw-hub__faq-head">
				<div>
					<h2><?php echo esc_html( $hub['faq_title'] ); ?></h2>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['faq_note'] ); ?></p>
				</div>
				<div class="hw-hub__faq-art">
					<?php echo hw_hub_illustration( 'question', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>

			<div class="hw-hub__faq-list">
				<?php foreach ( $hub['faqs'] as $faq ) : ?>
					<details class="hw-hub__faq">
						<summary>
							<span><?php echo esc_html( $faq['q'] ); ?></span>
							<span class="hw-hub__faq-plus" aria-hidden="true">+</span>
						</summary>
						<div class="hw-hub__faq-answer"><?php echo esc_html( $faq['a'] ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>

		</div>
	</section>

	<?php /* ---------- Closing CTA ---------- */ ?>
	<div class="hw-hub__cta">
		<div class="hw-hub__wrap">
			<div class="hw-hub__cta-inner">
				<div>
					<h2><?php echo esc_html( $hub['cta_title'] ); ?></h2>
					<p><?php echo esc_html( $hub['cta_body'] ); ?></p>
					<div class="hw-hub__cta-actions">
						<?php foreach ( $hub['cta_buttons'] as $i => $button ) : ?>
							<?php $button_url = hw_hub_url( $button['href'] ); ?>
							<?php if ( $button_url ) : ?>
								<a class="hw-hub__btn <?php echo 0 === $i ? 'hw-hub__btn--light' : 'hw-hub__btn--ghost'; ?>" href="<?php echo esc_url( $button_url ); ?>" <?php echo hw_hub_track( 'closing_cta', $button['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>><?php echo esc_html( $button['label'] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( empty( $hub['newsletter']['shortcode'] ) ) : ?>
					<div class="hw-hub__cta-art">
						<?php echo hw_hub_illustration( 'backpacker', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php else : ?>
					<div class="hw-hub__newsletter">
						<div class="hw-hub__newsletter-title"><?php echo esc_html( $hub['newsletter']['title'] ); ?></div>
						<p><?php echo esc_html( $hub['newsletter']['body'] ); ?></p>
						<?php echo do_shortcode( $hub['newsletter']['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

</main>

<?php
/* ---------- Structured data ----------
 * One graph with three parts, so search engines and AI answer engines can read
 * the page without parsing the layout:
 * - TouristDestination: what the page is about, where it sits, and where it is.
 * - FAQPage: the questions and answers, exactly as shown on the page.
 * - BreadcrumbList: the trail shown at the top of the page. Skipped when Yoast
 *   or Rank Math is active, since both already output one for every page.
 */

$page_url = get_permalink();
$graph    = array();

$destination = array(
	'@type'       => 'TouristDestination',
	'@id'         => $page_url . '#destination',
	'name'        => $hub_label,
	'description' => $hub['intro'],
	'url'         => $page_url,
);

$parent_page = $hub['parent_hub'] ? get_page_by_path( $hub['parent_hub'] ) : null;
$parent_id   = $parent_page ? $parent_page->ID : wp_get_post_parent_id( get_the_ID() );

if ( $parent_id ) {
	$destination['containedInPlace'] = array(
		'@type' => 'Country',
		'name'  => hw_hub_page_title( $parent_id ),
		'url'   => get_permalink( $parent_id ),
	);
}

if ( ! empty( $city['lat'] ) && ! empty( $city['lng'] ) ) {
	$destination['geo'] = array(
		'@type'     => 'GeoCoordinates',
		'latitude'  => $city['lat'],
		'longitude' => $city['lng'],
	);
}

$graph[] = $destination;

if ( $hub['faqs'] ) {
	$faq_schema = array(
		'@type'      => 'FAQPage',
		'@id'        => $page_url . '#faq',
		'about'      => array( '@id' => $page_url . '#destination' ),
		'mainEntity' => array(),
	);

	foreach ( $hub['faqs'] as $faq ) {
		$faq_schema['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => $faq['q'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $faq['a'],
			),
		);
	}

	$graph[] = $faq_schema;
}

if ( ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) {
	$crumbs = array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $page_url . '#breadcrumb',
		'itemListElement' => array(),
	);

	foreach ( hw_hub_breadcrumbs( get_the_ID() ) as $i => $crumb ) {
		$crumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb['name'],
			'item'     => $crumb['url'],
		);
	}

	$graph[] = $crumbs;
}
?>
<script type="application/ld+json"><?php echo wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?></script>

<?php
hw_hub_site_footer();
