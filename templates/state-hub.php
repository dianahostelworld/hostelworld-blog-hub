<?php
/**
 * State Hub
 *
 * Rendered by the Hostelworld Blog Hubs plugin when a Page uses the "State Hub"
 * template. All content comes from the "State hub content" panel on the Page.
 *
 * The city hub's shape, applied to a whole state: neighbourhoods become
 * regions, and where a city page has its day trips, a state page has its main
 * cities — what each is like and its points of interest, without links.
 *
 * Hostelworld has no record for a state, so everything live — the hero
 * figures, hostels, events, the map's city labels and hostel dots — is
 * gathered from the main cities the editor lists.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hub      = hw_hub_state_config();
$can_edit = current_user_can( 'edit_post', get_the_ID() );

$hub_label = hw_hub_page_title( get_the_ID() );
if ( '' === $hub_label ) {
	$hub_label = ucwords( str_replace( '-', ' ', $hub['hub_term'] ) );
}

$state_live = hw_hub_state_live( $hub );
$live       = ( 'manual' === $hub['hostels_source'] ) ? array() : $state_live['hostels'];

/* Headings left empty in the panel are written from the state's name, so a new
   page reads properly before its copy is in. */
$heading = function ( $value, $fallback ) use ( $hub_label ) {
	return '' !== trim( (string) $value ) ? $value : sprintf( hw_hub_t( $fallback ), $hub_label );
};

/* Two layouts, picked under Main cities. "cities": the main cities straight
   after the overview and no map. "map": a wide map across the top of the page,
   and the main cities further down, where a city page has its day trips. */
$layout = 'map' === $hub['layout'] ? 'map' : 'cities';

	$markers = array();

	foreach ( $hub['areas'] as $index => $area ) {
		if ( ! is_numeric( $area['lat'] ) || ! is_numeric( $area['lng'] ) ) {
			continue;
		}

		$markers[] = array(
			'id'   => 'region-' . $index,
			'name' => $area['name'],
			'lat'  => (float) $area['lat'],
			'lng'  => (float) $area['lng'],
		);
	}

	// The main cities go on the map too, placed from Hostelworld's own
	// coordinates. Clicking one jumps down to its card.
	foreach ( $hub['cities'] as $index => $main_city ) {
		$record = isset( $state_live['cities'][ strtolower( $main_city['name'] ) ] ) ? $state_live['cities'][ strtolower( $main_city['name'] ) ] : array();

		if ( empty( $record['lat'] ) || empty( $record['lng'] ) ) {
			continue;
		}

		$markers[] = array(
			'id'   => 'city-' . $index,
			'name' => $main_city['name'],
			'lat'  => (float) $record['lat'],
			'lng'  => (float) $record['lng'],
			'kind' => 'city',
		);
	}

	$pins = array();

	if ( 'map' === $layout && $hub['hostels_pins'] ) {
		foreach ( $state_live['hostels'] as $hostel ) {
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

$show_map = 'map' === $layout && $markers;

$has_overview = $hub['intro_title'] || $hub['intro_body'] || $hub['facts'] || '' !== trim( (string) get_post_field( 'post_content', get_the_ID() ) );

hw_hub_site_header();

if ( have_posts() ) {
	the_post();
}
?>

<main class="hw-hub hw-hub--city hw-hub--state" id="hw-hub">

	<?php /* ---------- Hero ---------- */ ?>
	<?php
	$hero_markup  = hw_hub_image( $hub['hero_image'], $hub_label, 'full' );
	$hero_overlay = in_array( $hub['hero_overlay'], array( 'light', 'medium', 'strong' ), true ) ? $hub['hero_overlay'] : 'light';

	$chips = array();

	if ( $hub['live_stats'] && $state_live['totals']['cities'] ) {
		if ( $state_live['totals']['properties'] ) {
			$chips[] = array(
				'value' => number_format_i18n( $state_live['totals']['properties'] ),
				'label' => 1 === $state_live['totals']['cities']
					? hw_hub_t( 'Hostels in its main city' )
					: sprintf(
						/* translators: %s: number of cities */
						hw_hub_t( 'Hostels across its %s main cities' ),
						number_format_i18n( $state_live['totals']['cities'] )
					),
			);
		}

		if ( $state_live['totals']['rating'] ) {
			$chips[] = array(
				'value' => number_format_i18n( $state_live['totals']['rating'], 1 ),
				'label' => sprintf(
					/* translators: %s: number of reviews */
					hw_hub_t( 'Average, from %s reviews' ),
					number_format_i18n( $state_live['totals']['reviews'] )
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
			<?php if ( $hub['intro'] ) : ?>
				<p class="hw-hub__hero-intro"><?php echo esc_html( $hub['intro'] ); ?></p>
			<?php endif; ?>

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
				<?php if ( $has_overview ) : ?>
					<a href="#overview"><?php echo esc_html( hw_hub_t( 'Overview' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $show_map ) : ?>
					<a href="#map"><?php echo esc_html( hw_hub_t( 'Map' ) ); ?></a>
				<?php endif; ?>
				<?php if ( 'map' === $layout && $hub['cities'] ) : ?>
					<a href="#cities"><?php echo esc_html( hw_hub_t( 'Main cities' ) ); ?></a>
				<?php endif; ?>
				<?php if ( 'map' === $layout && $hub['areas'] ) : ?>
					<a href="#regions"><?php echo esc_html( hw_hub_t( 'Regions' ) ); ?></a>
				<?php endif; ?>
				<?php if ( 'map' !== $layout && $hub['cities'] ) : ?>
					<a href="#cities"><?php echo esc_html( hw_hub_t( 'Main cities' ) ); ?></a>
				<?php endif; ?>
				<?php if ( 'map' !== $layout && $hub['areas'] ) : ?>
					<a href="#regions"><?php echo esc_html( hw_hub_t( 'Regions' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $hub['itin'] ) : ?>
					<a href="#itineraries"><?php echo esc_html( hw_hub_t( 'Itineraries' ) ); ?></a>
				<?php endif; ?>
				<?php foreach ( $hub['sections'] as $section ) : ?>
					<?php if ( $section['title'] || $section['body'] ) : ?>
						<a href="#<?php echo esc_attr( $section['key'] ); ?>"><?php echo esc_html( $section['nav'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
				<a href="#hostels"><?php echo esc_html( hw_hub_t( 'Where to stay' ) ); ?></a>
				<?php if ( 'manual' !== $hub['events_source'] && $state_live['events'] || $hub['events'] ) : ?>
					<a href="#events"><?php echo esc_html( hw_hub_t( "What's on" ) ); ?></a>
				<?php endif; ?>
				<?php if ( $hub['season_rows'] ) : ?>
					<a href="#season"><?php echo esc_html( hw_hub_t( 'When to go' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $hub['faqs'] ) : ?>
					<a href="#faqs"><?php echo esc_html( hw_hub_t( 'FAQs' ) ); ?></a>
				<?php endif; ?>
			</div>

			<button type="button" class="hw-hub__jump-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'Scroll sections right' ) ); ?>" hidden>
				<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
		</div>
	</nav>

	<?php /* ---------- Main cities. Printed after the overview, or after the map in the map layout. ---------- */ ?>
	<?php ob_start(); ?>
	<?php if ( $hub['cities'] ) : ?>
		<section id="cities" class="hw-hub__maincities hw-hub__maincities--<?php echo esc_attr( $layout ); ?>">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $heading( $hub['cities_title'], 'The main cities in %s' ) ); ?></h2>
				<?php if ( $hub['cities_intro'] ) : ?>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['cities_intro'] ); ?></p>
				<?php endif; ?>

				<div class="hw-hub__maincity-grid">
					<?php foreach ( $hub['cities'] as $index => $main_city ) : ?>
						<?php
						$record = isset( $state_live['cities'][ strtolower( $main_city['name'] ) ] ) ? $state_live['cities'][ strtolower( $main_city['name'] ) ] : array();
						$bits   = array();

						if ( $hub['cities_live'] && ! empty( $record['properties'] ) ) {
							$bits[] = sprintf(
								/* translators: %s: number of hostels */
								_n( '%s hostel', '%s hostels', $record['properties'], 'hostelworld' ),
								number_format_i18n( $record['properties'] )
							);
						}

						if ( $hub['cities_live'] && ! empty( $record['from']['value'] ) ) {
							$bits[] = sprintf(
								/* translators: %s: price with currency symbol */
								hw_hub_t( 'dorms from %s' ),
								hw_hub_price_label( $record['from'] )
							);
						}
						?>
						<article class="hw-hub__maincity" id="city-<?php echo esc_attr( $index ); ?>">
							<div class="hw-hub__thumb"><?php echo hw_hub_image( $main_city['image'], $main_city['name'], 'medium_large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<div class="hw-hub__maincity-body">
								<h3><?php echo esc_html( $main_city['name'] ); ?></h3>

								<?php if ( $bits ) : ?>
									<div class="hw-hub__city-stats"><?php echo esc_html( implode( ' · ', $bits ) ); ?></div>
								<?php endif; ?>

								<?php if ( $main_city['blurb'] ) : ?>
									<p><?php echo esc_html( $main_city['blurb'] ); ?></p>
								<?php endif; ?>

								<?php if ( $main_city['poi'] ) : ?>
									<div class="hw-hub__poi">
										<h4><?php echo esc_html( hw_hub_t( 'Points of interest' ) ); ?></h4>
										<ul>
											<?php foreach ( $main_city['poi'] as $poi ) : ?>
												<li>
													<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
													<span><?php echo esc_html( $poi ); ?></span>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<?php
				if ( $hub['cities_tag'] ) {
					hw_hub_render_carousel(
						array(
							'expression' => hw_hub_row_expression( $hub, $hub['cities_tag'] ),
							'label'      => sprintf(
								/* translators: %s: state */
								hw_hub_t( 'More on the cities of %s' ),
								$hub_label
							),
							'more'       => $hub['cities_more'],
							'can_edit'   => $can_edit,
						)
					);
				}
				?>
			</div>
		</section>
	<?php endif; ?>
	<?php $cities_html = ob_get_clean(); ?>

	<?php /* ---------- Regions: cards only. The map, when there is one, sits above the main cities. ---------- */ ?>
	<?php ob_start(); ?>
	<?php if ( $hub['areas'] ) : ?>
		<section id="regions" class="hw-hub__regions">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $heading( $hub['areas_title'], 'The regions of %s' ) ); ?></h2>
				<?php if ( $hub['areas_intro'] ) : ?>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['areas_intro'] ); ?></p>
				<?php endif; ?>

				<div class="hw-hub__region-grid">
					<?php foreach ( $hub['areas'] as $index => $area ) : ?>
						<?php $link = hw_hub_url( $area['link'] ); ?>
						<article class="hw-hub__area" id="region-<?php echo esc_attr( $index ); ?>">
							<div class="hw-hub__area-head">
								<h3><?php echo esc_html( $area['name'] ); ?></h3>
								<?php if ( $area['suits'] ) : ?>
									<span class="hw-hub__area-suits"><?php echo esc_html( $area['suits'] ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( $area['blurb'] ) : ?>
								<p><?php echo esc_html( $area['blurb'] ); ?></p>
							<?php endif; ?>

							<?php if ( $link ) : ?>
								<div class="hw-hub__area-foot">
									<a class="hw-hub__area-link" href="<?php echo esc_url( $link ); ?>" <?php echo hw_hub_track( 'region_link', $area['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
										<?php echo esc_html( hw_hub_t( 'Read more' ) ); ?>
										<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</a>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php $regions_html = ob_get_clean(); ?>

	<?php /* ---------- Overview and facts ---------- */ ?>
	<?php if ( $has_overview ) : ?>
	<section id="overview" class="hw-hub__overview">
		<div class="hw-hub__wrap hw-hub__overview-grid">
			<div>
				<h2><?php echo esc_html( $heading( $hub['intro_title'], '%s at a glance' ) ); ?></h2>
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
	<?php endif; ?>

	<?php /* ---------- The map, after the overview, in the map layout ---------- */ ?>
	<?php if ( $show_map ) : ?>
		<section id="map" class="hw-hub__statemap">
			<div
				class="hw-hub__map hw-hub__map--wide"
				data-hw-map
				data-zoom="<?php echo esc_attr( $hub['areas_zoom'] ); ?>"
				data-radius="0"
				data-markers="<?php echo esc_attr( wp_json_encode( $markers ) ); ?>"
				data-hostels="<?php echo esc_attr( wp_json_encode( $pins ) ); ?>"
				role="region"
				aria-label="<?php echo esc_attr( sprintf( hw_hub_t( 'Map of %s' ), $hub_label ) ); ?>"
			></div>
			<div class="hw-hub__wrap">
				<p class="hw-hub__map-note">
					<?php if ( $pins ) : ?>
						<?php
						printf(
							/* translators: %s: number of hostels */
							esc_html( hw_hub_t( 'Orange labels are regions, dark labels the main cities, and dots the %s top-rated hostels. Map data from OpenStreetMap contributors.' ) ),
							esc_html( number_format_i18n( count( $pins ) ) )
						);
						?>
					<?php else : ?>
						<?php echo esc_html( hw_hub_t( 'Orange labels are regions, dark labels the main cities. Map data from OpenStreetMap contributors.' ) ); ?>
					<?php endif; ?>
				</p>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Main cities directly under the map, in the map layout ---------- */ ?>
	<?php
	if ( 'map' === $layout ) {
		echo $cities_html; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped above.
		echo $regions_html; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	?>

	<?php /* ---------- Main cities near the top, in the cities layout ---------- */ ?>
	<?php
	if ( 'map' !== $layout ) {
		echo $cities_html; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped above.
		echo $regions_html; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	?>

	<?php /* ---------- Itineraries ---------- */ ?>
	<?php if ( $hub['itin'] ) : ?>
		<section id="itineraries" class="hw-hub__itin">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $heading( $hub['itin_title'], 'Itineraries for %s' ) ); ?></h2>
				<?php if ( $hub['itin_intro'] ) : ?>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['itin_intro'] ); ?></p>
				<?php endif; ?>

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
	<?php $index = 0; ?>
	<?php foreach ( $hub['sections'] as $section ) : ?>
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
								/* translators: 1: section heading, 2: state */
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
		<?php $index++; ?>
	<?php endforeach; ?>

	<?php /* ---------- What's on ---------- */ ?>
	<?php
	$live_events = ( 'manual' === $hub['events_source'] ) ? array() : $state_live['events'];
	$events_cta  = hw_hub_url( $hub['events_cta']['href'] );
	?>
	<?php if ( $live_events || $hub['events'] ) : ?>
		<section id="events" class="hw-hub__events-country">
			<div class="hw-hub__wrap">
				<div class="hw-hub__events-head">
					<div>
						<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( "What's on" ) ); ?></div>
						<h2><?php echo esc_html( $heading( $hub['events_title'], "What's on in %s" ) ); ?></h2>
						<?php if ( $hub['events_intro'] ) : ?>
							<p><?php echo esc_html( $hub['events_intro'] ); ?></p>
						<?php endif; ?>
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
									<?php if ( $event['city'] ) : ?>
										<div class="hw-hub__event-place">
											<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<span><?php echo esc_html( $event['city'] ); ?></span>
										</div>
									<?php endif; ?>
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
						<h2><?php echo esc_html( $heading( $hub['hostels_title'], 'Where to stay in %s' ) ); ?></h2>
						<?php if ( $hub['hostels_intro'] ) : ?>
							<p><?php echo esc_html( $hub['hostels_intro'] ); ?></p>
						<?php endif; ?>
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
						<?php echo esc_html( hw_hub_t( 'Only you can see this. The live feed returned nothing — check the cities under Main cities, and their Hostelworld names.' ) ); ?>
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
									<?php if ( $hostel['city'] ) : ?>
										<div class="hw-hub__hostel-place">
											<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											<span><?php echo esc_html( $hostel['city'] ); ?></span>
										</div>
									<?php endif; ?>
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
				<h2><?php echo esc_html( $heading( $hub['season_title'], 'When to go to %s' ) ); ?></h2>
				<?php if ( $hub['season_intro'] ) : ?>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['season_intro'] ); ?></p>
				<?php endif; ?>

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
						<h2><?php echo esc_html( $heading( $hub['budget_title'], 'What a trip to %s costs' ) ); ?></h2>
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
	<?php if ( $hub['faqs'] ) : ?>
	<section id="faqs" class="hw-hub__faqs">
		<div class="hw-hub__wrap">
			<div class="hw-hub__faq-head">
				<div>
					<h2><?php echo esc_html( $heading( $hub['faq_title'], '%s travel questions' ) ); ?></h2>
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
	<?php endif; ?>

	<?php /* ---------- Closing CTA ---------- */ ?>
	<div class="hw-hub__cta">
		<div class="hw-hub__wrap">
			<div class="hw-hub__cta-inner">
				<div>
					<h2><?php echo esc_html( $hub['cta_title'] ); ?></h2>
					<?php if ( $hub['cta_body'] ) : ?>
						<p><?php echo esc_html( $hub['cta_body'] ); ?></p>
					<?php endif; ?>
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
	'@type'       => array( 'TouristDestination', 'State' ),
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
