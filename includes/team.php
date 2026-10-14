<?php
/**
 * "From the Hostelworld team": quotes from people at Hostelworld who have been
 * to the country or city, on country and city hubs. A place they loved, a tip,
 * a short take on the destination, with their photo, name and role.
 *
 * Filled in on the hub's edit screen. With no quotes, the section is left off.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The editor section, shared by the country and city schemas.
 *
 * @return array
 */
function hw_hub_team_section() {
	return array(
		'label'  => __( 'From the team', 'hostelworld' ),
		'fields' => array(
			'team_title' => array( 'label' => 'Heading', 'type' => 'text', 'help' => 'Leave empty for "From the Hostelworld team".' ),
			'team_intro' => array( 'label' => 'Intro (optional)', 'type' => 'textarea', 'rows' => 2 ),
			'team'       => array(
				'label'  => 'Quotes',
				'type'   => 'rows',
				'cols'   => array( 'image', 'name', 'role', 'quote' ),
				'labels' => array(
					'image' => 'Photo',
					'name'  => 'Name',
					'role'  => 'Role at Hostelworld',
					'quote' => 'What they say',
				),
				'long'   => array( 'quote' ),
				'add'    => 'Add a quote',
				'help'   => 'Someone at Hostelworld who has been here: a place they loved, a tip, their take on it. Each quote is introduced with their role, e.g. "Our CRM Lead says". A row with no quote is left out, and with no quotes at all the section is not shown. Shown just before the FAQs.',
			),
		),
	);
}

/**
 * Wording for readers, translatable under Languages > String translations.
 *
 * @return array
 */
function hw_hub_team_strings() {
	return array(
		'From the Hostelworld team',
		'Our %s says',
		'Hostelworld',
	);
}

/**
 * Registers the wording with Polylang.
 */
function hw_hub_team_register_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	foreach ( hw_hub_team_strings() as $string ) {
		pll_register_string( $string, $string, 'Hostelworld hubs' );
	}
}
add_action( 'init', 'hw_hub_team_register_strings' );

/**
 * Prints the section, or nothing when there are no quotes.
 *
 * @param int $post_id Hub page ID.
 */
function hw_hub_render_team( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$rows    = hw_hub_value( 'team', $post_id );
	$quotes  = array();

	foreach ( (array) $rows as $row ) {
		$quote = isset( $row['quote'] ) ? trim( (string) $row['quote'] ) : '';

		if ( '' === $quote ) {
			continue;
		}

		// Quote marks are added on the page, so drop any typed in.
		$row['quote'] = trim( $quote, " \t\"'“”‘’«»" );
		$row['name']  = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
		$row['role']  = isset( $row['role'] ) ? trim( (string) $row['role'] ) : '';
		$row['image'] = isset( $row['image'] ) ? trim( (string) $row['image'] ) : '';
		$quotes[]     = $row;
	}

	if ( ! $quotes ) {
		return;
	}

	$title = trim( (string) hw_hub_value( 'team_title', $post_id ) );
	$intro = trim( (string) hw_hub_value( 'team_intro', $post_id ) );
	$title = '' !== $title ? $title : hw_hub_t( 'From the Hostelworld team' );
	$solo  = 1 === count( $quotes );
	?>
	<section id="team" class="hw-hub__team<?php echo $solo ? ' hw-hub__team--solo' : ''; ?>">
		<div class="hw-hub__wrap">
			<h2><?php echo esc_html( $title ); ?></h2>
			<?php if ( '' !== $intro ) : ?>
				<p class="hw-hub__team-intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>

			<div class="hw-hub__team-grid">
				<?php foreach ( $quotes as $q ) : ?>
					<?php
					$photo = '';

					if ( is_numeric( $q['image'] ) && (int) $q['image'] ) {
						$photo = wp_get_attachment_image( (int) $q['image'], $solo ? 'medium_large' : 'medium', false, array( 'alt' => $q['name'], 'loading' => 'lazy' ) );
					} elseif ( '' !== $q['image'] ) {
						$photo = '<img src="' . esc_url( $q['image'] ) . '" alt="' . esc_attr( $q['name'] ) . '" loading="lazy">';
					}
					?>
					<figure class="hw-hub__team-card<?php echo $photo ? '' : ' hw-hub__team-card--nophoto'; ?>">
						<?php if ( $photo ) : ?>
							<div class="hw-hub__team-photo"><?php echo $photo; // phpcs:ignore WordPress.Security.EscapeOutput -- built by WordPress or escaped above. ?></div>
						<?php endif; ?>

						<div class="hw-hub__team-body">
							<?php if ( '' !== $q['role'] ) : ?>
								<p class="hw-hub__team-lead"><?php echo esc_html( sprintf( hw_hub_t( 'Our %s says' ), $q['role'] ) ); ?></p>
							<?php endif; ?>

							<blockquote class="hw-hub__team-quote">
								<p>&ldquo;<?php echo esc_html( $q['quote'] ); ?>&rdquo;</p>
							</blockquote>

							<?php if ( '' !== $q['name'] ) : ?>
								<figcaption class="hw-hub__team-who">
									<strong><?php echo esc_html( $q['name'] ); ?></strong>
									<?php if ( '' === $q['role'] ) : ?>
										<span><?php echo esc_html( hw_hub_t( 'Hostelworld' ) ); ?></span>
									<?php endif; ?>
								</figcaption>
							<?php endif; ?>
						</div>
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
}
