<?php
/**
 * Live hostel cards inside articles.
 *
 * Added from the Classic Editor with the "Add hostels" button, which writes a
 * short tag into the article:
 *
 *   [hw_hostels city="lisbon" country="portugal" count="4"]
 *   [hw_hostels city="lisbon" country="portugal" ids="339199,123456"]
 *
 * Built to cost the article nothing on load:
 * - The hostels are fetched from Hostelworld on the server and kept for a few
 *   hours (the same cache the hubs use), so no reader ever waits on the API,
 *   and the page cache serves the cards like any other HTML.
 * - No JavaScript is needed to show them. A few lines record clicks, and only
 *   on articles that have cards.
 * - One small stylesheet, loaded only on articles that have cards.
 * - Photos come from Hostelworld's image server at card size, lazy-loaded, with
 *   fixed proportions so the page does not jump as they arrive.
 * - If the feed is down, the last good result is shown; with none at all the
 *   cards are left out for readers, and editors see a note saying why.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The currency prices are shown in, by language, so a Spanish article shows
 * euros and an English one can show pounds. Change it with the
 * hw_hostels_currency filter or a currency="" on one tag.
 *
 * @return string Three-letter code.
 */
function hw_hostels_default_currency() {
	$lang = '';

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = (string) pll_current_language( 'slug' );
	} elseif ( defined( 'ICL_LANGUAGE_CODE' ) ) {
		$lang = (string) ICL_LANGUAGE_CODE;
	}

	return (string) apply_filters( 'hw_hostels_currency', 'EUR', $lang );
}

/**
 * The hostels for a city, best first, with impossible prices removed.
 *
 * @param string $city     City as Hostelworld spells it in URLs, e.g. lisbon.
 * @param string $country  Country, e.g. portugal.
 * @param string $currency Currency code.
 * @param int    $limit    How many to fetch.
 * @return array { city: array, hostels: array }
 */
function hw_hostels_fetch( $city, $country, $currency, $limit = 40 ) {
	$record = hw_hub_api_city( $city, $country );

	if ( empty( $record['id'] ) ) {
		return array( 'city' => array(), 'hostels' => array() );
	}

	$hostels = hw_hub_api_city_hostels(
		array(
			'city_id'  => $record['id'],
			'currency' => $currency,
			'limit'    => $limit,
			'hours'    => 6,
		)
	);

	if ( function_exists( 'hw_hub_drop_price_outliers' ) ) {
		$hostels = hw_hub_drop_price_outliers( $hostels );
	}

	return array( 'city' => $record, 'hostels' => $hostels );
}

/**
 * Hostelworld's image at a given width: t_20 is 288px, t_30 432px, t_40 576px.
 *
 * @param string $url Image URL from the feed.
 * @param int    $t   Transformation number.
 * @return string
 */
function hw_hostels_image_size( $url, $t ) {
	return (string) preg_replace( '#/t_\d+/#', '/t_' . (int) $t . '/', (string) $url );
}

/**
 * The [hw_hostels] tag.
 *
 * @param array $atts Tag attributes.
 * @return string
 */
function hw_hostels_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'city'     => '',
			'country'  => '',
			'count'    => 4,
			'ids'      => '',
			'title'    => '',
			'layout'   => 'grid',
			'currency' => '',
		),
		$atts,
		'hw_hostels'
	);

	$city     = sanitize_title( $atts['city'] );
	$country  = sanitize_title( $atts['country'] );
	$count    = max( 1, min( 8, (int) $atts['count'] ) );
	$currency = $atts['currency'] ? strtoupper( sanitize_key( $atts['currency'] ) ) : hw_hostels_default_currency();
	$ids      = array_filter( array_map( 'absint', explode( ',', (string) $atts['ids'] ) ) );
	$can_edit = current_user_can( 'edit_posts' );

	if ( '' === $city || '' === $country ) {
		return $can_edit ? hw_hostels_notice( __( 'This hostels box needs a city and a country.', 'hostelworld' ) ) : '';
	}

	$data    = hw_hostels_fetch( $city, $country, $currency );
	$hostels = $data['hostels'];

	if ( $ids ) {
		// Chosen hostels, in the order they were picked.
		$by_id = array();
		foreach ( $hostels as $hostel ) {
			$by_id[ (int) $hostel['id'] ] = $hostel;
		}

		$hostels = array();
		foreach ( $ids as $id ) {
			if ( isset( $by_id[ $id ] ) ) {
				$hostels[] = $by_id[ $id ];
			}
		}
	} else {
		// "Top-rated hostels" means hostels: Hostelworld also lists hotels and
		// guesthouses, which only fill in when a city has too few hostels.
		$only = array_values( array_filter( $hostels, function ( $h ) {
			return 'HOSTEL' === $h['type'];
		} ) );
		$all_hostels = count( $only ) >= $count;
		$hostels     = array_slice( $all_hostels ? $only : array_merge( $only, array_filter( $hostels, function ( $h ) {
			return 'HOSTEL' !== $h['type'];
		} ) ), 0, $count );
	}

	// Hide a price that is far below the rest (a feed error, like a €3 room in Venice)
	// rather than show it; the card itself stays.
	$hostels = hw_hostels_hide_odd_prices( $hostels );

	if ( ! $hostels ) {
		return $can_edit
			? hw_hostels_notice( empty( $data['city'] )
				? sprintf( /* translators: 1: city, 2: country */ __( 'Hostelworld has no city called "%1$s" in "%2$s". Check the spelling as it appears in a Hostelworld web address.', 'hostelworld' ), $city, $country )
				: __( 'No hostels came back for this city right now, so readers see nothing here. It usually clears on its own within a few hours.', 'hostelworld' ) )
			: '';
	}

	wp_enqueue_style( 'hw-hostels' );
	hw_hostels_print_tracking();

	$city_name = ! empty( $data['city']['name'] ) ? $data['city']['name'] : ucwords( str_replace( '-', ' ', $city ) );
	$title     = trim( (string) $atts['title'] );

	$mixed = (bool) array_filter( $hostels, function ( $h ) {
		return 'HOSTEL' !== $h['type'];
	} );

	if ( '' === $title ) {
		/* translators: %s: city */
		$title = sprintf( $mixed ? hw_hub_t( 'Top-rated places to stay in %s' ) : hw_hub_t( 'Top-rated hostels in %s' ), $city_name );
	}

	$layout = 'row' === $atts['layout'] ? 'row' : 'grid';
	$shown  = count( $hostels );
	$cols   = ( 0 === $shown % 3 || 5 === $shown || 7 === $shown ) ? 3 : 2;

	ob_start();
	?>
	<aside class="hw-hostels hw-hostels--<?php echo esc_attr( $layout ); ?> hw-hostels--cols-<?php echo (int) ( 1 === $shown ? 2 : $cols ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
		<?php if ( 'none' !== strtolower( $title ) ) : ?>
			<p class="hw-hostels__title"><?php echo esc_html( $title ); ?></p>
		<?php endif; ?>
		<ul class="hw-hostels__list">
			<?php foreach ( $hostels as $hostel ) : ?>
				<?php
				$price = hw_hub_price_label( $hostel['price'] );
				$img   = (string) $hostel['image'];
				?>
				<li class="hw-hostels__item">
					<a class="hw-hostels__card" href="<?php echo esc_url( $hostel['url'] ); ?>" rel="noopener" data-hw-track="article_hostel" data-hw-track-label="<?php echo esc_attr( $hostel['name'] ); ?>">
						<span class="hw-hostels__photo">
							<?php if ( $img ) : ?>
								<img
									src="<?php echo esc_url( hw_hostels_image_size( $img, 30 ) ); ?>"
									srcset="<?php echo esc_url( hw_hostels_image_size( $img, 20 ) ); ?> 288w, <?php echo esc_url( hw_hostels_image_size( $img, 30 ) ); ?> 432w, <?php echo esc_url( hw_hostels_image_size( $img, 40 ) ); ?> 576w"
									sizes="(max-width: 600px) 80vw, 280px"
									width="432" height="288"
									alt="<?php echo esc_attr( $hostel['name'] ); ?>"
									loading="lazy" decoding="async">
							<?php endif; ?>
						</span>
						<span class="hw-hostels__body">
							<span class="hw-hostels__name"><?php echo esc_html( $hostel['name'] ); ?></span>
							<?php if ( $hostel['rating'] ) : ?>
								<span class="hw-hostels__rating">
									<span class="hw-hostels__score"><?php echo esc_html( number_format_i18n( $hostel['rating'], 1 ) ); ?></span>
									<span class="hw-hostels__reviews">
										<?php
										/* translators: %s: number of reviews */
										echo esc_html( sprintf( _n( '%s review', '%s reviews', $hostel['reviews'], 'hostelworld' ), number_format_i18n( $hostel['reviews'] ) ) );
										?>
									</span>
								</span>
							<?php endif; ?>
							<?php if ( $price ) : ?>
								<span class="hw-hostels__price">
									<?php
									printf(
										/* translators: %s: price */
										esc_html( $hostel['dorm'] ? hw_hub_t( 'Dorms from %s a night' ) : hw_hub_t( 'Rooms from %s a night' ) ),
										'<strong>' . esc_html( $price ) . '</strong>'
									);
									?>
								</span>
							<?php endif; ?>
							<span class="hw-hostels__cta"><?php echo esc_html( hw_hub_t( 'See dates and book' ) ); ?> &rarr;</span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="hw-hostels__note"><?php echo esc_html( hw_hub_t( 'Ratings and prices from Hostelworld, updated every few hours.' ) ); ?></p>
	</aside>
	<?php
	return trim( (string) ob_get_clean() );
}
add_shortcode( 'hw_hostels', 'hw_hostels_shortcode' );

/**
 * Removes prices under a quarter of the median for their kind (dorm or room).
 *
 * @param array $hostels Hostels.
 * @return array
 */
function hw_hostels_hide_odd_prices( $hostels ) {
	foreach ( array( true, false ) as $dorm ) {
		$prices = array();
		foreach ( $hostels as $h ) {
			if ( $h['dorm'] === $dorm && ! empty( $h['price']['value'] ) ) {
				$prices[] = (float) $h['price']['value'];
			}
		}
		if ( count( $prices ) < 3 ) {
			continue;
		}
		sort( $prices );
		$median = $prices[ (int) floor( count( $prices ) / 2 ) ];
		foreach ( $hostels as $i => $h ) {
			if ( $h['dorm'] === $dorm && ! empty( $h['price']['value'] ) && (float) $h['price']['value'] < $median / 4 ) {
				$hostels[ $i ]['price'] = null;
			}
		}
	}
	return $hostels;
}

/**
 * A note only editors see, in place of the cards.
 *
 * @param string $message Message.
 * @return string
 */
function hw_hostels_notice( $message ) {
	return '<p class="hw-hostels-notice" style="border:1px dashed #F25621;padding:10px 14px;border-radius:8px;font-size:14px;color:#31363F;">' . esc_html__( 'Only you can see this.', 'hostelworld' ) . ' ' . esc_html( $message ) . '</p>';
}

/**
 * Registers the stylesheet, and loads it in the head on any article whose
 * content has the tag, so the cards never appear unstyled for a moment.
 */
function hw_hostels_assets() {
	wp_register_style( 'hw-hostels', HW_HUB_URL . 'assets/hostels-embed.css', array(), HW_HUB_VERSION );

	if ( is_singular() ) {
		$post = get_post();

		if ( $post && has_shortcode( (string) $post->post_content, 'hw_hostels' ) ) {
			wp_enqueue_style( 'hw-hostels' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hw_hostels_assets' );

/**
 * Click tracking: the same event the hubs send, with hub_type "article", so
 * GA shows which articles send people to book. Printed once per page.
 */
function hw_hostels_print_tracking() {
	static $done = false;

	if ( $done ) {
		return;
	}

	$done = true;

	add_action(
		'wp_footer',
		function () {
			?>
			<script>
			document.addEventListener('click',function(e){var l=e.target.closest&&e.target.closest('.hw-hostels [data-hw-track], .hw-destbox [data-hw-track]');if(!l)return;var p={link_type:l.getAttribute('data-hw-track'),link_text:l.getAttribute('data-hw-track-label')||'',link_url:l.href||'',hub_type:'article',hub_title:document.title};if(window.google_tag_manager||!window.gtag){window.dataLayer=window.dataLayer||[];window.dataLayer.push(Object.assign({event:'hw_hub_click'},p));}else{window.gtag('event','hw_hub_click',p);}});
			</script>
			<?php
		},
		99
	);
}

/* ---------- The Classic Editor button ------------------------------------ */

/**
 * "Add hostels" next to "Add Media", on posts (and pages that are not hubs).
 *
 * @param string $editor_id Editor.
 */
function hw_hostels_media_button( $editor_id ) {
	if ( 'content' !== $editor_id || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	printf(
		'<button type="button" class="button hw-hostels-open" data-editor="%s"><span class="dashicons dashicons-building" style="vertical-align:text-top;margin:0 2px 0 -2px;"></span> %s</button>',
		esc_attr( $editor_id ),
		esc_html__( 'Add hostels', 'hostelworld' )
	);
}
add_action( 'media_buttons', 'hw_hostels_media_button', 20 );

/**
 * The button's script and styles, on the edit screens only.
 *
 * @param string $hook Admin screen.
 */
function hw_hostels_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	wp_enqueue_style( 'hw-hostels', HW_HUB_URL . 'assets/hostels-embed.css', array(), HW_HUB_VERSION );
	wp_enqueue_script( 'hw-hostels-button', HW_HUB_URL . 'assets/hostels-button.js', array( 'jquery' ), HW_HUB_VERSION, true );
	wp_localize_script(
		'hw-hostels-button',
		'hwHostels',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'hw_hostels_find' ),
			'guess' => hw_hostels_guess_place( get_the_ID() ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'hw_hostels_admin_assets' );

/**
 * A head start for the dialog: the city and country this article is tagged
 * with, read off the destination hubs (a city hub sits under its country hub).
 *
 * @param int $post_id Article.
 * @return array city, country.
 */
function hw_hostels_guess_place( $post_id ) {
	$out = array( 'city' => '', 'country' => '' );

	if ( ! $post_id ) {
		return $out;
	}

	$tags = wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'slugs' ) );

	if ( is_wp_error( $tags ) || ! $tags ) {
		return $out;
	}

	$hubs = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => 500,
			'meta_key'       => '_wp_page_template',
			'meta_value'     => HW_HUB_CITY_TEMPLATE_KEY,
			'fields'         => 'ids',
		)
	);

	foreach ( $hubs as $hub_id ) {
		$term = (string) hw_hub_value( 'hub_term', $hub_id );

		if ( ! in_array( $term, $tags, true ) && ! in_array( get_post_field( 'post_name', $hub_id ), $tags, true ) ) {
			continue;
		}

		$slug    = (string) hw_hub_value( 'city_slug', $hub_id );
		$country = (string) hw_hub_value( 'country_slug', $hub_id );

		if ( $slug && $country ) {
			return array( 'city' => $slug, 'country' => $country );
		}
	}

	return $out;
}

/**
 * The dialog's "Find hostels": the city's hostels, best first, to pick from.
 */
function hw_hostels_ajax_find() {
	check_ajax_referer( 'hw_hostels_find', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'hostelworld' ) ), 403 );
	}

	$city    = isset( $_POST['city'] ) ? sanitize_title( wp_unslash( $_POST['city'] ) ) : '';
	$country = isset( $_POST['country'] ) ? sanitize_title( wp_unslash( $_POST['country'] ) ) : '';

	if ( '' === $city || '' === $country ) {
		wp_send_json_error( array( 'message' => __( 'Type a city and its country, as they appear in a Hostelworld web address (lisbon, portugal).', 'hostelworld' ) ) );
	}

	$data = hw_hostels_fetch( $city, $country, hw_hostels_default_currency() );

	if ( empty( $data['city'] ) ) {
		wp_send_json_error( array( 'message' => sprintf( /* translators: 1: city 2: country */ __( 'Hostelworld has no "%1$s" in "%2$s". Check the spelling in a Hostelworld web address, e.g. hostelworld.com/hostels/europe/portugal/lisbon/.', 'hostelworld' ), $city, $country ) ) );
	}

	$rows = array();

	foreach ( $data['hostels'] as $hostel ) {
		$rows[] = array(
			'id'      => (int) $hostel['id'],
			'name'    => $hostel['name'],
			'rating'  => $hostel['rating'] ? number_format_i18n( $hostel['rating'], 1 ) : '',
			'reviews' => number_format_i18n( $hostel['reviews'] ),
			'price'   => hw_hub_price_label( $hostel['price'] ),
			'type'    => 'HOSTEL' === $hostel['type'] ? '' : ucfirst( strtolower( $hostel['type'] ) ),
		);
	}

	wp_send_json_success(
		array(
			'city'    => $data['city']['name'],
			'hostels' => $rows,
		)
	);
}
add_action( 'wp_ajax_hw_hostels_find', 'hw_hostels_ajax_find' );
