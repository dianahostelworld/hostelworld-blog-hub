<?php
/**
 * Country Hub (states)
 *
 * Rendered by the Hostelworld Blog Hubs plugin when a Page uses the "Country
 * Hub (states)" template. The country hub, with one difference: instead of a
 * list of cities, the country's states are shown as cards in a grid, grouped
 * under region headings. Fifty full-width rows would be far too long to read.
 *
 * Each card keeps what a city row has — photo, a line on it, an editor-picked
 * traveller quote, itinerary links and the button to the guide.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hub      = hw_hub_country_states_config();
$taxonomy = hw_hub_taxonomy();
$can_edit = current_user_can( 'edit_post', get_the_ID() );

$hub_label = hw_hub_page_title( get_the_ID() );
if ( '' === $hub_label ) {
	$hub_label = ucwords( str_replace( '-', ' ', $hub['hub_term'] ) );
}

$has_overview = $hub['intro_title'] || $hub['intro_body'] || $hub['facts'] || '' !== trim( (string) get_post_field( 'post_content', get_the_ID() ) );

hw_hub_site_header();

if ( have_posts() ) {
	the_post();
}
?>

<main class="hw-hub hw-hub--country hw-hub--country-states" id="hw-hub">

	<?php /* ---------- Hero ---------- */ ?>
	<?php
	$hero_markup  = hw_hub_image( $hub['hero_image'], $hub_label, 'full' );
	$hero_overlay = in_array( $hub['hero_overlay'], array( 'light', 'medium', 'strong' ), true ) ? $hub['hero_overlay'] : 'light';
	?>
	<section class="hw-hub__hero hw-hub__hero--veil-<?php echo esc_attr( $hero_overlay ); ?> hw-hub__hero--crumbs<?php echo $hero_markup ? '' : ' hw-hub__hero--plain'; ?>">
		<?php if ( $hero_markup ) : ?>
			<div class="hw-hub__hero-bg"><?php echo $hero_markup; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="hw-hub__hero-veil" aria-hidden="true"></div>
			<?php hw_hub_postcard_button( $hub['hero_image'], $hub_label ); ?>
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

			<?php if ( $has_overview ) : ?>
				<a href="#overview"><?php echo esc_html( hw_hub_t( 'Overview' ) ); ?></a>
			<?php endif; ?>
			<?php if ( $hub['state_groups'] ) : ?>
				<a href="#states"><?php echo esc_html( hw_hub_t( 'States' ) ); ?></a>
			<?php endif; ?>
			<?php if ( $hub['trip_types'] ) : ?>
				<a href="#trips"><?php echo esc_html( hw_hub_t( 'Types of trip' ) ); ?></a>
			<?php endif; ?>
			<?php foreach ( $hub['sections'] as $section ) : ?>
				<?php if ( $section['title'] || $section['body'] ) : ?>
					<a href="#<?php echo esc_attr( $section['key'] ); ?>"><?php echo esc_html( $section['nav'] ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $hub['season_rows'] ) : ?>
				<a href="#season"><?php echo esc_html( hw_hub_t( 'When to go' ) ); ?></a>
			<?php endif; ?>
			<?php if ( $hub['budget_rows'] ) : ?>
				<a href="#budget"><?php echo esc_html( hw_hub_t( 'Budget' ) ); ?></a>
			<?php endif; ?>
			<a href="#events"><?php echo esc_html( hw_hub_t( "What's on" ) ); ?></a>
			<a href="#hostels"><?php echo esc_html( hw_hub_t( 'Hostels' ) ); ?></a>
			<?php if ( $hub['faqs'] ) : ?>
				<a href="#faqs"><?php echo esc_html( hw_hub_t( 'FAQs' ) ); ?></a>
			<?php endif; ?>
			</div>

			<button type="button" class="hw-hub__jump-btn" data-direction="next" aria-label="<?php echo esc_attr( hw_hub_t( 'Scroll sections right' ) ); ?>" hidden>
				<?php echo hw_hub_icon( 'arrow-right', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</button>
		</div>
	</nav>

	<?php /* ---------- Overview ---------- */ ?>
	<?php if ( $has_overview ) : ?>
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
	<?php endif; ?>

	<?php $live = ( 'manual' === $hub['hostels_source'] ) ? array() : hw_hub_api_hostels( $hub['hostels_api'] ); ?>

	<?php /* ---------- States, as cards grouped by region ---------- */ ?>
	<?php if ( $hub['state_groups'] ) : ?>
		<section id="states" class="hw-hub__states">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $hub['states_title'] ? $hub['states_title'] : hw_hub_t( 'Pick a state' ) ); ?></h2>
				<?php if ( $hub['states_intro'] ) : ?>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['states_intro'] ); ?></p>
				<?php endif; ?>

				<?php
				$named_groups = array_filter( wp_list_pluck( $hub['state_groups'], 'name' ), 'strlen' );
				?>
				<?php if ( count( $named_groups ) > 1 ) : ?>
					<nav class="hw-hub__state-regions" aria-label="<?php echo esc_attr( hw_hub_t( 'Regions' ) ); ?>">
						<?php foreach ( $hub['state_groups'] as $group ) : ?>
							<?php if ( $group['name'] ) : ?>
								<a href="#<?php echo esc_attr( $group['anchor'] ); ?>">
									<?php echo esc_html( $group['name'] ); ?>
									<span><?php echo esc_html( number_format_i18n( count( $group['states'] ) ) ); ?></span>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</nav>
				<?php endif; ?>

				<?php foreach ( $hub['state_groups'] as $group_index => $group ) : ?>
					<div class="hw-hub__state-group hw-hub__state-group--<?php echo esc_attr( ( $group_index % 5 ) + 1 ); ?>" id="<?php echo esc_attr( $group['anchor'] ); ?>">
						<?php if ( $group['name'] ) : ?>
							<div class="hw-hub__state-group-head">
								<h3><?php echo esc_html( $group['name'] ); ?></h3>
								<?php if ( $group['intro'] ) : ?>
									<p><?php echo esc_html( $group['intro'] ); ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>

						<div class="hw-hub__state-grid">
							<?php foreach ( $group['states'] as $state ) : ?>
								<?php
								$state_url   = $state['guide'] ? hw_hub_url( $state['guide'] ) : hw_hub_country_url( $state['name'] );
								$state_img   = hw_hub_image( $state['image'], $state['name'], 'medium_large' );
								$quote       = ! empty( $state['quote'] ) ? $state['quote'] : ''; // Editor-picked only. Never fall back to a live review.
								$itineraries = hw_hub_city_itineraries( isset( $state['itineraries'] ) ? $state['itineraries'] : '' );
								?>
								<article class="hw-hub__state-card" id="state-<?php echo esc_attr( sanitize_title( $state['name'] ) ); ?>">
									<div class="hw-hub__state-photo">
										<?php echo $state_img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</div>

									<div class="hw-hub__state-body">
										<h4><?php echo esc_html( $state['name'] ); ?></h4>

										<?php if ( $state['blurb'] ) : ?>
											<p class="hw-hub__state-blurb"><?php echo esc_html( $state['blurb'] ); ?></p>
										<?php endif; ?>

										<?php if ( $quote ) : ?>
											<blockquote class="hw-hub__city-quote hw-hub__state-quote">
												<p><?php echo esc_html( $quote ); ?></p>
												<cite><?php echo esc_html( hw_hub_t( 'A traveller, on Hostelworld' ) ); ?></cite>
											</blockquote>
										<?php endif; ?>

										<?php if ( $itineraries ) : ?>
											<div class="hw-hub__itineraries hw-hub__state-itineraries">
												<span class="hw-hub__itineraries-label"><?php echo esc_html( hw_hub_t( 'Got this long?' ) ); ?></span>
												<?php foreach ( $itineraries as $itinerary ) : ?>
													<a class="hw-hub__itinerary" href="<?php echo esc_url( $itinerary['url'] ); ?>" <?php echo hw_hub_track( 'state_itinerary', $state['name'] . ' — ' . $itinerary['label'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
														<?php echo hw_hub_icon( 'calendar', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
														<span><?php echo esc_html( $itinerary['label'] ); ?></span>
													</a>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>

										<?php if ( $state_url ) : ?>
											<a class="hw-hub__btn hw-hub__state-btn" href="<?php echo esc_url( $state_url ); ?>" <?php echo hw_hub_track( 'state_guide', $state['name'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>>
												<?php
												printf(
													/* translators: %s: state name */
													esc_html( hw_hub_t( 'The %s guide' ) ),
													esc_html( $state['name'] )
												);
												?>
												<?php echo hw_hub_icon( 'arrow-right', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
											</a>
										<?php endif; ?>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Types of trip ---------- */ ?>
	<?php if ( $hub['trip_types'] ) : ?>
		<section id="trips" class="hw-hub__triptypes">
			<div class="hw-hub__wrap">
				<h2><?php echo esc_html( $hub['trips_title'] ? $hub['trips_title'] : hw_hub_t( 'Types of trip' ) ); ?></h2>
				<?php if ( $hub['trips_intro'] ) : ?>
					<p class="hw-hub__lede"><?php echo esc_html( $hub['trips_intro'] ); ?></p>
				<?php endif; ?>

				<div class="hw-hub__trip-tabs" role="tablist">
					<?php foreach ( $hub['trip_types'] as $i => $trip ) : ?>
						<button
							type="button"
							class="hw-hub__trip-tab hw-hub__trip-tab--<?php echo esc_attr( $trip['colour'] ); ?><?php echo 0 === $i ? ' is-active' : ''; ?>"
							role="tab"
							id="tab-<?php echo esc_attr( $trip['id'] ); ?>"
							aria-controls="panel-<?php echo esc_attr( $trip['id'] ); ?>"
							aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
						>
							<?php echo hw_hub_icon( $trip['icon'], 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( $trip['name'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>

				<?php foreach ( $hub['trip_types'] as $i => $trip ) : ?>
					<div
						class="hw-hub__trip-panel hw-hub__trip-panel--<?php echo esc_attr( $trip['colour'] ); ?><?php echo 0 === $i ? ' is-active' : ''; ?>"
						id="panel-<?php echo esc_attr( $trip['id'] ); ?>"
						role="tabpanel"
						aria-labelledby="tab-<?php echo esc_attr( $trip['id'] ); ?>"
						<?php echo 0 === $i ? '' : 'hidden'; ?>
					>
						<div class="hw-hub__trip-head">
							<h3><?php echo esc_html( $trip['name'] ); ?> <?php echo esc_html( hw_hub_t( 'in' ) ); ?> <?php echo esc_html( $hub_label ); ?></h3>
							<?php if ( $trip['summary'] ) : ?>
								<p><?php echo esc_html( $trip['summary'] ); ?></p>
							<?php endif; ?>
						</div>

						<div class="hw-hub__trip-cols">
							<?php if ( $trip['doing'] ) : ?>
								<div>
									<h4><?php echo esc_html( hw_hub_t( 'What to do' ) ); ?></h4>
									<ul>
										<?php foreach ( $trip['doing'] as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( $trip['spots'] ) : ?>
								<div>
									<h4><?php echo esc_html( hw_hub_t( 'Spots to visit' ) ); ?></h4>
									<ul>
										<?php foreach ( $trip['spots'] as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( $trip['cities'] ) : ?>
								<div>
									<h4><?php echo esc_html( hw_hub_t( 'Best states for it' ) ); ?></h4>
									<ul class="hw-hub__trip-cities">
										<?php foreach ( $trip['cities'] as $city_name ) : ?>
											<?php $trip_city_url = hw_hub_country_url( $city_name ); // A state, on this template. ?>
											<li>
												<?php if ( $trip_city_url ) : ?>
													<a href="<?php echo esc_url( $trip_city_url ); ?>"><?php echo esc_html( $city_name ); ?></a>
												<?php else : ?>
													<?php echo esc_html( $city_name ); ?>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>

						<?php
						if ( $trip['tag'] ) {
							hw_hub_render_carousel(
								array(
									'expression' => hw_hub_row_expression( $hub, $trip['tag'] ),
									'label'      => sprintf(
										/* translators: 1: trip type, 2: country */
										hw_hub_t( 'Reading on %1$s in %2$s' ),
										strtolower( $trip['name'] ),
										$hub_label
									),
									'more'       => hw_hub_t( 'See all' ),
									'can_edit'   => $can_edit,
									'compact'    => true,
								)
							);
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- The content sections, each with its own carousel ---------- */ ?>
	<?php $index = 0; ?>
	<?php foreach ( $hub['sections'] as $section ) : ?>
		<?php if ( ! $section['title'] && ! $section['body'] ) : ?>
			<?php continue; // Nothing written yet: leave the section off rather than show an empty band. ?>
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
								/* translators: 1: section heading, 2: country */
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
							<input type="range" min="1" max="30" value="7" data-hw-nights>
							<output data-hw-nights-out>7</output>
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

	<?php /* ---------- What's on ---------- */ ?>
	<?php
	$live_events = ( 'manual' === $hub['events_source'] ) ? array() : hw_hub_api_events( $hub['events_api'] );
	$events_cta  = hw_hub_url( $hub['events_cta']['href'] );
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
							<?php echo hw_hub_link_open( $event_url, 'hw-hub__event-card' . ( $event_photo ? '' : ' hw-hub__event-card--text' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
						<a class="hw-hub__btn" href="<?php echo esc_url( $events_cta ); ?>" rel="noopener">
							<?php echo esc_html( $hub['events_cta']['label'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- Hostels ---------- */ ?>
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
						<?php echo esc_html( hw_hub_t( 'Only you can see this. The live feed returned nothing — check the Location ID under Hostels.' ) ); ?>
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
									<?php if ( ! empty( $hostel['eco'] ) ) : ?>
										<span class="hw-hub__hostel-eco" title="<?php echo esc_attr( $hostel['eco'] ); ?>">
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
									<div class="hw-hub__hostel-place">
										<?php echo hw_hub_icon( 'map-pin', 14 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<span><?php echo esc_html( $hostel['city'] ); ?></span>
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
							<?php $hostel_url = hw_hub_url( $hostel['href'] ); ?>
							<?php echo hw_hub_link_open( $hostel_url, 'hw-hub__hostel', $hostel_url ? 'rel="noopener"' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
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
						<a class="hw-hub__btn" href="<?php echo esc_url( $hostels_cta_url ); ?>" rel="noopener">
							<?php echo esc_html( $hub['hostels_cta']['label'] ); ?>
							<?php echo hw_hub_icon( 'arrow-right', 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php /* ---------- From the Hostelworld team ---------- */ ?>
	<?php hw_hub_render_team(); ?>

	<?php /* ---------- FAQs ---------- */ ?>
	<?php if ( $hub['faqs'] ) : ?>
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
	<?php endif; ?>

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
/* ---------- Structured data: FAQPage ---------- */

if ( $hub['faqs'] ) :
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
<?php endif; ?>

<?php
hw_hub_site_footer();
