<?php
/**
 * Continent Hub
 *
 * Rendered by the Hostelworld Blog Hubs plugin when a Page uses the
 * "Continent Hub" template. All content comes from the "Continent hub content"
 * panel on the Page itself, so there is nothing to edit in here.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------------------------------------------------------
 * Render
 * ------------------------------------------------------------------------- */

$hub      = hw_hub_page_config();
$taxonomy = hw_hub_taxonomy();
$hub_url  = hw_hub_url( 'term:' . $taxonomy . ':' . $hub['hub_term'] );

// Falls back to the term name, then the slug, so an untitled Page still reads
// as "Europe" rather than leaving blanks in the card tags and headings.
$hub_label = trim( wp_strip_all_tags( get_the_title() ) );
if ( '' === $hub_label ) {
	$hub_term_obj = get_term_by( 'slug', $hub['hub_term'], $taxonomy );
	$hub_label    = ( $hub_term_obj && ! is_wp_error( $hub_term_obj ) )
		? $hub_term_obj->name
		: ucwords( str_replace( '-', ' ', $hub['hub_term'] ) );
}

hw_hub_site_header();

// Set up the Page so the_title(), the_content() and get_the_ID() work below.
if ( have_posts() ) {
	the_post();
}
?>


<main class="hw-hub" id="hw-hub">

	<?php /* ---------- Hero ---------- */ ?>
	<?php
	$hero_art = $hub['hero_image'];

	// No image set: fall back to this continent's WDS map illustration.
	if ( '' === trim( (string) $hero_art ) ) {
		$hero_art = 'illustration:continent-map-' . sanitize_title( $hub['hub_term'] );
	}

	$hero_markup = hw_hub_image( $hero_art, '', 'full' );
	?>
	<?php $hero_overlay = in_array( $hub['hero_overlay'], array( 'light', 'medium', 'strong' ), true ) ? $hub['hero_overlay'] : 'light'; ?>
	<section class="hw-hub__hero hw-hub__hero--veil-<?php echo esc_attr( $hero_overlay ); ?><?php echo $hero_markup ? '' : ' hw-hub__hero--plain'; ?>">
		<?php if ( $hero_markup ) : ?>
			<div class="hw-hub__hero-bg"><?php echo $hero_markup; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="hw-hub__hero-veil" aria-hidden="true"></div>
			<?php hw_hub_postcard_button( $hub['hero_image'], $hub_label ); ?>
		<?php endif; ?>

		<div class="hw-hub__wrap hw-hub__hero-inner">
			<h1><?php echo esc_html( $hub['h1'] ); ?></h1>
			<p class="hw-hub__hero-intro"><?php echo esc_html( $hub['intro'] ); ?></p>

			<div class="hw-hub__hero-actions">
				<?php foreach ( $hub['hero_cta'] as $i => $cta ) : ?>
					<?php $cta_url = hw_hub_url( $cta['href'] ); ?>
					<?php if ( $cta_url ) : ?>
						<a class="hw-hub__btn<?php echo 0 === $i ? '' : ' hw-hub__btn--ghost'; ?>" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta['label'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php if ( $hub['stats'] ) : ?>
				<div class="hw-hub__hero-stats">
					<?php foreach ( $hub['stats'] as $stat ) : ?>
						<div class="hw-hub__hero-stat">
							<div class="hw-hub__hero-stat-value"><?php echo esc_html( $stat['value'] ); ?></div>
							<div class="hw-hub__hero-stat-label"><?php echo esc_html( $stat['label'] ); ?></div>
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

			<a href="#why"><?php echo esc_html( 'Why ' . $hub_label ); ?></a>
			<a href="#countries"><?php echo esc_html( hw_hub_t( 'Countries' ) ); ?></a>
			<a href="#trips"><?php echo esc_html( hw_hub_t( 'Types of trip' ) ); ?></a>
			<?php /* Hostels always has content once the feed is on. */ ?>
			<a href="#hostels"><?php echo esc_html( hw_hub_t( 'Hostels' ) ); ?></a>
			<a href="#when"><?php echo esc_html( hw_hub_t( 'When to go' ) ); ?></a>
			<a href="#costs"><?php echo esc_html( hw_hub_t( 'Costs' ) ); ?></a>
			<a href="#rail"><?php echo esc_html( hw_hub_t( 'Getting around' ) ); ?></a>
			<a href="#events"><?php echo esc_html( hw_hub_t( "What's on" ) ); ?></a>
			<a href="#reads"><?php echo esc_html( hw_hub_t( 'Guides' ) ); ?></a>
			<a href="#faqs"><?php echo esc_html( hw_hub_t( 'FAQs' ) ); ?></a>
			</div>

			<button type="button" class="hw-hub__jump-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'Scroll sections right' ) ); ?>" hidden>
				<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
		</div>
	</nav>

	<?php /* ---------- Intro ---------- */ ?>
	<section id="why" class="hw-hub__intro">
		<div class="hw-hub__wrap hw-hub__intro-grid">
			<div>
				<div class="hw-hub__eyebrow"><?php echo esc_html( $hub['eyebrow'] ); ?></div>
				<h2><?php echo esc_html( $hub['why_title'] ); ?></h2>

				<?php
				// Page content wins if you've written any; otherwise the config copy runs.
				$page_content = trim( get_post_field( 'post_content', get_the_ID() ) );
				if ( $page_content ) {
					the_content();
				} else {
					foreach ( $hub['why_body'] as $para ) {
						echo '<p>' . esc_html( $para ) . '</p>';
					}
				}

				$why_url = hw_hub_url( $hub['why_link']['href'] );
				if ( $why_url ) :
					?>
					<a class="hw-hub__textlink" href="<?php echo esc_url( $why_url ); ?>">
						<?php echo esc_html( $hub['why_link']['label'] ); ?>
						<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="hw-hub__cards">
				<?php foreach ( $hub['why_cards'] as $card ) : ?>
					<div class="hw-hub__card">
						<div class="hw-hub__card-icon"><?php echo hw_hub_icon( $card['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
						<div>
							<h3><?php echo esc_html( $card['title'] ); ?></h3>
							<p><?php echo esc_html( $card['body'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* ---------- Countries ---------- */ ?>
	<section id="countries" class="hw-hub__countries">
		<div class="hw-hub__countries-top">
		<div class="hw-hub__wrap">
			<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( 'Country guides' ) ); ?></div>
			<h2><?php echo esc_html( $hub['countries_title'] ); ?></h2>
			<p class="hw-hub__lede"><?php echo esc_html( $hub['countries_intro'] ); ?></p>

			<div class="hw-hub__featured">
				<?php
				$children = hw_hub_country_pages( get_the_ID() );

				foreach ( $hub['featured'] as $country ) {
					// Still looked up, but only to borrow the page's featured image.
					$key          = strtolower( $country['name'] );
					$country_page = isset( $children[ $key ] ) ? $children[ $key ] : null;
					$url = hw_hub_country_url( $country['name'], $country['page'] );

					$image = isset( $country['image'] ) ? trim( $country['image'] ) : '';

					if ( '' === $image && $country_page && has_post_thumbnail( $country_page ) ) {
						$image = get_post_thumbnail_id( $country_page );
					}

					$count = hw_hub_country_count( $hub, $country['name'] );

					echo hw_hub_link_open( $url, 'hw-hub__featured-card' ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo '<div class="hw-hub__thumb">' . hw_hub_image( $image, $country['name'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
					echo '<div class="hw-hub__featured-body">';
					echo '<h3>' . esc_html( $country['name'] ) . '</h3>';
					echo '<p>' . esc_html( $country['cities'] ) . '</p>';
					if ( $count ) {
						echo '<span class="hw-hub__count">' . esc_html( $count ) . '</span>';
					}
					echo '</div>';
					echo hw_hub_link_close( $url ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</div>
		</div>

		<div class="hw-hub__wrap">
			<?php foreach ( $hub['regions'] as $region ) : ?>
				<div class="hw-hub__region">
					<div class="hw-hub__region-head">
						<h3><?php echo esc_html( $region['name'] ); ?></h3>
						<span><?php echo esc_html( $region['note'] ); ?></span>
					</div>
					<div class="hw-hub__region-grid">
						<?php
						foreach ( $region['countries'] as $name ) {
							$url   = hw_hub_country_url( $name );
							$count = hw_hub_country_count( $hub, $name );

							echo hw_hub_link_open( $url, 'hw-hub__country' ); // phpcs:ignore WordPress.Security.EscapeOutput
							echo '<span>';
							echo '<span class="hw-hub__country-name">' . esc_html( $name ) . '</span>';
							if ( $count ) {
								echo '<span class="hw-hub__country-count">' . esc_html( $count ) . '</span>';
							}
							echo '</span>';
							if ( $url ) {
								echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput
							}
							echo hw_hub_link_close( $url ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<?php /* ---------- Types of trip ---------- */ ?>
	<section id="trips" class="hw-hub__trips">
		<div class="hw-hub__wrap">
			<div class="hw-hub__trips-head">
				<h3><?php echo esc_html( $hub['trips_title'] ); ?></h3>
				<span><?php echo esc_html( $hub['trips_note'] ); ?></span>
			</div>
			<div class="hw-hub__trips-grid">
				<?php foreach ( $hub['trips'] as $trip ) : ?>
					<?php $trip_url = hw_hub_url( $trip['href'] ); ?>
					<?php if ( $trip_url ) : ?>
						<a class="hw-hub__trip" href="<?php echo esc_url( $trip_url ); ?>">
							<div class="hw-hub__trip-icon"><?php echo hw_hub_icon( $trip['icon'], 24 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							<div class="hw-hub__trip-label"><?php echo esc_html( $trip['label'] ); ?></div>
						</a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* ---------- Hostels ---------- */ ?>
	<?php
	$can_edit = current_user_can( 'edit_post', get_the_ID() );
	$live     = ( 'manual' === $hub['hostels_source'] ) ? array() : hw_hub_api_hostels( $hub['hostels_api'] );
	?>
	<?php if ( $live || $hub['hostels'] || $can_edit ) : ?>
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

				<?php if ( ! $live && ! $hub['hostels'] ) : ?>
					<div class="hw-hub__empty">
						<?php echo hw_hub_illustration( 'search-map', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo esc_html( hw_hub_t( 'Only you can see this. The live feed returned nothing and no fallback hostels are listed — check the Location ID under Hostels.' ) ); ?>
					</div>
				<?php endif; ?>

				<div class="hw-hub__carousel" data-hw-carousel>
					<?php if ( $live ) : ?>
						<?php foreach ( $live as $hostel ) : ?>
							<?php $price = hw_hub_price_label( $hostel['price'] ); ?>
							<?php echo hw_hub_link_open( $hostel['url'], 'hw-hub__hostel', 'rel="noopener"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<div class="hw-hub__hostel-photo">
									<?php if ( $hostel['image'] ) : ?>
										<img src="<?php echo esc_url( $hostel['image'] ); ?>" alt="<?php echo esc_attr( $hostel['name'] ); ?>" loading="lazy">
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
									<div class="hw-hub__hostel-place">
										<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span><?php echo esc_html( trim( $hostel['city'] . ( $hostel['country'] ? ', ' . $hostel['country'] : '' ), ', ' ) ); ?></span>
									</div>
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
							<?php
							$hostel_url = hw_hub_url( $hostel['href'] );
							$hostel_img = hw_hub_image( $hostel['image'], $hostel['name'], 'medium_large' );
							?>
							<?php echo hw_hub_link_open( $hostel_url, 'hw-hub__hostel', $hostel_url ? 'rel="noopener"' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<div class="hw-hub__hostel-photo"><?php echo $hostel_img; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
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
									<?php if ( $hostel_url ) : ?>
										<span class="hw-hub__hostel-link">
											<?php echo esc_html( hw_hub_t( 'See prices and reviews' ) ); ?>
											<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										</span>
									<?php endif; ?>
								</div>
							<?php echo hw_hub_link_close( $hostel_url ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<?php $hostels_cta_url = hw_hub_url( $hub['hostels_cta']['href'] ); ?>
				<?php if ( $hostels_cta_url ) : ?>
					<div class="hw-hub__hostels-cta">
						<a class="hw-hub__btn" href="<?php echo esc_url( $hostels_cta_url ); ?>" rel="noopener">
							<?php echo esc_html( $hub['hostels_cta']['label'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- When to go ---------- */ ?>
	<section id="when" class="hw-hub__when">
		<div class="hw-hub__wrap">
			<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( 'When to go' ) ); ?></div>
			<h2><?php echo esc_html( $hub['when_title'] ); ?></h2>
			<p class="hw-hub__lede"><?php echo esc_html( $hub['when_intro'] ); ?></p>

			<div class="hw-hub__seasons">
				<?php foreach ( $hub['seasons'] as $season ) : ?>
					<div class="hw-hub__season">
						<div class="hw-hub__season-head">
							<div class="hw-hub__season-name"><?php echo esc_html( $season['name'] ); ?></div>
							<div class="hw-hub__season-months"><?php echo esc_html( $season['months'] ); ?></div>
							<div class="hw-hub__tag hw-hub__tag--<?php echo esc_attr( $season['tone'] ); ?>"><?php echo esc_html( $season['tag'] ); ?></div>
						</div>
						<div class="hw-hub__season-body">
							<p><?php echo esc_html( $season['body'] ); ?></p>
							<ul>
								<?php foreach ( $season['points'] as $point ) : ?>
									<li><span><?php echo esc_html( $point ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* ---------- Costs ---------- */ ?>
	<section id="costs" class="hw-hub__costs">
		<div class="hw-hub__wrap">
			<div>
				<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( 'Costs' ) ); ?></div>
				<h2><?php echo esc_html( $hub['costs_title'] ); ?></h2>
				<?php foreach ( $hub['costs_body'] as $para ) : ?>
					<p><?php echo esc_html( $para ); ?></p>
				<?php endforeach; ?>

				<div class="hw-hub__money">
					<?php foreach ( $hub['money_reads'] as $read ) : ?>
						<?php $read_url = hw_hub_url( $read['href'] ); ?>
						<?php if ( $read_url ) : ?>
							<a href="<?php echo esc_url( $read_url ); ?>">
								<span><?php echo esc_html( $read['title'] ); ?></span>
								<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</a>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="hw-hub__table">
				<div class="hw-hub__row hw-hub__row--head">
					<div><?php echo esc_html( hw_hub_t( 'Region' ) ); ?></div>
					<div><?php echo esc_html( hw_hub_t( 'Dorm bed' ) ); ?></div>
					<div><?php echo esc_html( hw_hub_t( 'Per day' ) ); ?></div>
				</div>
				<?php foreach ( $hub['cost_rows'] as $row ) : ?>
					<div class="hw-hub__row">
						<div>
							<div class="hw-hub__row-region"><?php echo esc_html( $row['region'] ); ?></div>
							<div class="hw-hub__row-examples"><?php echo esc_html( $row['examples'] ); ?></div>
						</div>
						<div class="hw-hub__row-dorm"><?php echo esc_html( $row['dorm'] ); ?></div>
						<div class="hw-hub__row-daily"><?php echo esc_html( $row['daily'] ); ?></div>
					</div>
				<?php endforeach; ?>
				<p class="hw-hub__note"><?php echo esc_html( $hub['cost_note'] ); ?></p>
			</div>
		</div>
	</section>

	<?php /* ---------- Getting around ---------- */ ?>
	<section id="rail" class="hw-hub__rail">
		<div class="hw-hub__wrap">
			<div class="hw-hub__rail-grid">
				<div>
					<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( 'Getting around' ) ); ?></div>
					<h2><?php echo esc_html( $hub['rail_title'] ); ?></h2>
					<?php foreach ( $hub['rail_body'] as $para ) : ?>
						<p><?php echo esc_html( $para ); ?></p>
					<?php endforeach; ?>
					<?php $rail_url = hw_hub_url( $hub['rail_link']['href'] ); ?>
					<?php if ( $rail_url ) : ?>
						<a class="hw-hub__textlink" href="<?php echo esc_url( $rail_url ); ?>">
							<?php echo esc_html( $hub['rail_link']['label'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="hw-hub__rail-media">
					<?php echo hw_hub_image( $hub['rail_image'], $hub['rail_title'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>

			<div class="hw-hub__routes">
				<?php foreach ( $hub['routes'] as $route ) : ?>
					<div class="hw-hub__route">
						<div class="hw-hub__route-meta">
							<?php echo hw_hub_icon( 'train', 22 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $route['length'] ); ?></span>
						</div>
						<h3><?php echo esc_html( $route['name'] ); ?></h3>
						<p><?php echo esc_html( $route['body'] ); ?></p>
						<div class="hw-hub__route-stops"><?php echo esc_html( $route['stops'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* ---------- What's on ---------- */ ?>
	<section id="events" class="hw-hub__events">
		<div class="hw-hub__wrap">
			<div class="hw-hub__events-head">
				<div>
					<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( "What's on" ) ); ?></div>
					<h2><?php echo esc_html( $hub['events_title'] ); ?></h2>
					<p><?php echo esc_html( $hub['events_intro'] ); ?></p>
				</div>
				<div class="hw-hub__events-actions">
					<div class="hw-hub__carousel-nav">
						<button type="button" class="hw-hub__carousel-btn" data-direction="prev" aria-label="<?php echo esc_attr( hw_hub_t( 'Previous events' ) ); ?>">
							<?php echo hw_hub_icon( 'arrow-left', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
						<button type="button" class="hw-hub__carousel-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'More events' ) ); ?>">
							<?php echo hw_hub_icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</button>
					</div>

				<?php $events_url = hw_hub_url( $hub['events_cta']['href'] ); ?>
				<?php if ( $events_url ) : ?>
					<a class="hw-hub__btn" href="<?php echo esc_url( $events_url ); ?>">
						<?php echo esc_html( $hub['events_cta']['label'] ); ?>
						<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				<?php endif; ?>
				</div>
			</div>

			<?php $live_events = ( 'manual' === $hub['events_source'] ) ? array() : hw_hub_api_events( $hub['events_api'] ); ?>

			<?php if ( ! $live_events && ! $hub['events'] && current_user_can( 'edit_post', get_the_ID() ) ) : ?>
				<p class="hw-hub__notice">
					<?php
					printf(
						/* translators: %s: continent name used for the feed */
						esc_html( hw_hub_t( 'Only you can see this. Nothing came back for %s and there are no fallback events listed, so this section is empty for visitors. The feed drops out occasionally — if it stays empty, check the continent name under What\'s on.' ) ),
						'<code>' . esc_html( $hub['events_api']['country'] ) . '</code>'
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( $live_events ) : ?>
				<div class="hw-hub__carousel" data-hw-carousel>
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
								<?php if ( $event['city'] || $event['country'] ) : ?>
									<div class="hw-hub__event-place">
										<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span><?php echo esc_html( trim( $event['city'] . ( $event['country'] ? ', ' . $event['country'] : '' ), ', ' ) ); ?></span>
									</div>
								<?php endif; ?>
								<?php if ( $event['blurb'] ) : ?>
									<p><?php echo esc_html( $event['blurb'] ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="hw-hub__events-grid">
					<?php foreach ( $hub['events'] as $event ) : ?>
						<div class="hw-hub__event">
							<div class="hw-hub__event-month"><?php echo esc_html( $event['month'] ); ?></div>
							<h3><?php echo esc_html( $event['name'] ); ?></h3>
							<div class="hw-hub__event-place"><?php echo esc_html( $event['place'] ); ?></div>
							<p><?php echo esc_html( $event['body'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<div class="hw-hub__pills">
				<?php foreach ( $hub['events_pills'] as $pill ) : ?>
					<?php $pill_url = hw_hub_url( $pill['href'] ); ?>
					<?php if ( $pill_url ) : ?>
						<a href="<?php echo esc_url( $pill_url ); ?>"><?php echo esc_html( $pill['label'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php /* ---------- Article rows ---------- */ ?>
	<section id="reads" class="hw-hub__reads">
		<div class="hw-hub__wrap">
			<div class="hw-hub__eyebrow"><?php echo esc_html( hw_hub_t( 'From the blog' ) ); ?></div>
			<h2><?php echo esc_html( $hub_label . ' guides' ); ?></h2>
			<p class="hw-hub__lede" style="margin-bottom:0"><?php echo esc_html( $hub['reads_intro'] ); ?></p>
		</div>
	</section>

	<?php foreach ( $hub['article_rows'] as $row ) : ?>
		<?php
		$expression = hw_hub_row_expression( $hub, $row['tag'] );
		$row_posts  = hw_hub_get_posts( $expression, 4 );
		$fallback   = $row_posts ? array() : hw_hub_fallback_articles( $hub['hub_term'], $row['tag'] );
		$see_url    = $row['link'] ? hw_hub_url( $row['link'] ) : hw_hub_term_filter_url( $expression );
		$hub_name   = $hub_label;

		// The row always renders, with or without articles. Editors also get
		// told which tag is missing.
		$missing = ( $row_posts || $fallback ) ? array() : hw_hub_missing_slugs( $expression );
		?>
		<div class="hw-hub__row-block">
			<div class="hw-hub__wrap">
				<div class="hw-hub__row-head">
					<div>
						<h3><?php echo esc_html( $row['title'] ); ?></h3>
						<p><?php echo esc_html( $row['note'] ); ?></p>
					</div>
					<?php if ( $see_url ) : ?>
						<a class="hw-hub__seeall" href="<?php echo esc_url( $see_url ); ?>">
							<?php echo esc_html( $row['see_all'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					<?php endif; ?>
				</div>

				<?php if ( ! $row_posts && ! $fallback ) : ?>
					<div class="hw-hub__empty">
						<?php echo hw_hub_illustration( 'search-map', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo esc_html( hw_hub_t( 'Guides for this are on the way.' ) ); ?>
					</div>

					<?php if ( current_user_can( 'edit_post', get_the_ID() ) ) : ?>
						<p class="hw-hub__notice">
							<?php
							if ( $missing ) {
								printf(
									/* translators: 1: list of tag slugs, 2: the tags this row queries */
									esc_html( hw_hub_t( 'Only you can see this. Nothing is tagged %1$s, so the row %2$s comes back empty. Create the tag, or edit the row under Continent hub content > Guides.' ) ),
									'<strong>' . esc_html( implode( ', ', $missing ) ) . '</strong>',
									'<code>' . esc_html( $expression ) . '</code>'
								);
							} else {
								printf(
									/* translators: %s: the tags this row queries */
									esc_html( hw_hub_t( 'Only you can see this. The tags exist, but no post carries all of %s yet.' ) ),
									'<code>' . esc_html( $expression ) . '</code>'
								);
							}
							?>
						</p>
					<?php endif; ?>
				<?php endif; ?>

				<div class="hw-hub__articles">
					<?php if ( $row_posts ) : ?>
						<?php foreach ( $row_posts as $post_item ) : ?>
							<?php
							$labels = hw_hub_card_labels( $post_item->ID, $hub );
							$label  = $labels['primary'];
							$meta   = get_the_author_meta( 'display_name', $post_item->post_author );
							if ( $hub['show_read_time'] ) {
								$meta .= ' · ' . hw_hub_read_time( $post_item->ID ) . ' min read';
							}
							?>
							<a class="hw-hub__article" href="<?php echo esc_url( get_permalink( $post_item ) ); ?>">
								<div class="hw-hub__thumb">
									<?php echo get_the_post_thumbnail( $post_item, 'medium_large', array( 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								</div>
								<div class="hw-hub__article-body">
									<div class="hw-hub__article-tags">
										<?php if ( $label ) : ?>
											<span class="hw-hub__article-tag"><?php echo esc_html( $label ); ?></span>
										<?php endif; ?>
										<?php if ( $labels['in_hub'] ) : ?>
											<span class="hw-hub__article-tag hw-hub__article-tag--muted">· <?php echo esc_html( $hub_name ); ?></span>
										<?php endif; ?>
									</div>
									<h4><?php echo esc_html( get_the_title( $post_item ) ); ?></h4>
									<div class="hw-hub__article-meta"><?php echo esc_html( $meta ); ?></div>
								</div>
							</a>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ( $fallback as $item ) : ?>
							<?php $item_url = hw_hub_url( $item['href'] ); ?>
							<?php if ( $item_url ) : ?>
								<a class="hw-hub__article" href="<?php echo esc_url( $item_url ); ?>">
									<div class="hw-hub__thumb"></div>
									<div class="hw-hub__article-body">
										<div class="hw-hub__article-tags">
											<span class="hw-hub__article-tag"><?php echo esc_html( $item['tag'] ); ?></span>
											<span class="hw-hub__article-tag hw-hub__article-tag--muted">· <?php echo esc_html( $hub_name ); ?></span>
										</div>
										<h4><?php echo esc_html( $item['title'] ); ?></h4>
									</div>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>

	<?php /* ---------- All articles ---------- */ ?>
	<?php if ( $hub_url ) : ?>
		<div class="hw-hub__all">
			<div class="hw-hub__wrap">
				<div class="hw-hub__all-inner">
					<div class="hw-hub__all-art">
						<?php echo hw_hub_illustration( 'search-map', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<div>
						<h3><?php echo esc_html( $hub['all_title'] ); ?></h3>
						<p><?php echo esc_html( $hub['all_body'] ); ?></p>
					</div>
					<a class="hw-hub__btn" href="<?php echo esc_url( $hub_url ); ?>">
						<?php echo esc_html( $hub['all_cta'] ); ?>
						<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</a>
				</div>
			</div>
		</div>
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

	<?php /* ---------- Also trending this week ---------- */ ?>
	<?php hw_hub_trending_strip(); ?>

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
								<a class="hw-hub__btn <?php echo 0 === $i ? 'hw-hub__btn--light' : 'hw-hub__btn--ghost'; ?>" href="<?php echo esc_url( $button_url ); ?>"><?php echo esc_html( $button['label'] ); ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( empty( $hub['newsletter']['shortcode'] ) ) : ?>
					<div class="hw-hub__cta-art">
						<?php echo hw_hub_illustration( 'backpacker', '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $hub['newsletter']['shortcode'] ) ) : ?>
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
/* ---------- Structured data: FAQPage ---------- */

$faq_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
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

?>
<script type="application/ld+json"><?php echo wp_json_encode( $faq_schema ); ?></script>

<?php
hw_hub_site_footer();
