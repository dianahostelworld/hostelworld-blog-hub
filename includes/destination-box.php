<?php
/**
 * The destination box: a strip added automatically after the intro of any
 * article tagged with a destination that has a hub.
 *
 * It links the article up to its hub (the most specific one: city or state,
 * then country, then continent), shows a few live facts from Hostelworld and
 * two or three other articles about the same place. No article is edited:
 * the box is added as the page is built, so it can be switched off in one
 * place, or hidden on a single article from its edit screen.
 *
 * Kept light:
 * - The tag-to-hub lookup is worked out once and saved, and only rebuilt
 *   when a Page is saved, so an article does one quick read to find its hub.
 * - Live facts use the same saved Hostelworld data as the hubs, so a city's
 *   hub and its articles share one copy.
 * - The related articles are saved per destination for 12 hours.
 * - A 2KB stylesheet, only on articles that show the box.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HW_DESTBOX_INDEX', 'hw_destbox_index_v2' );

/**
 * Who sees the box: "all", "editors" (a preview only logged-in editors see)
 * or "off". Starts as a preview so it can be checked before readers see it.
 *
 * @return string
 */
function hw_destbox_mode() {
	$mode = (string) get_option( 'hw_hub_destbox_mode', 'editors' );

	return in_array( $mode, array( 'all', 'editors', 'off' ), true ) ? $mode : 'editors';
}

/**
 * How specific each hub template is: the box points to the highest.
 *
 * @return array template => level
 */
function hw_destbox_levels() {
	return array(
		'city'           => 3,
		'state'          => 3,
		'country'        => 2,
		'country_states' => 2,
		'continent'      => 1,
	);
}

/**
 * A field as saved on a hub Page, without falling back to the template's
 * sample content (a hub nobody filled in must not claim "barcelona").
 *
 * @param string $key     Field key.
 * @param int    $post_id Page ID.
 * @return string
 */
function hw_destbox_saved( $key, $post_id ) {
	foreach ( array( hw_hub_meta_key( $key, $post_id ), hw_hub_legacy_meta_key( $key, $post_id ) ) as $meta_key ) {
		if ( metadata_exists( 'post', $post_id, $meta_key ) ) {
			return trim( (string) get_post_meta( $post_id, $meta_key, true ) );
		}
	}

	return '';
}

/**
 * Every published hub, by language and tag: { lang: { tag: [id, level] } }.
 *
 * @return array
 */
function hw_destbox_index() {
	$index = get_transient( HW_DESTBOX_INDEX );

	if ( is_array( $index ) ) {
		return $index;
	}

	$index  = array();
	$levels = hw_destbox_levels();
	$args   = array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'     => '_wp_page_template',
				'value'   => hw_hub_template_keys(),
				'compare' => 'IN',
			),
		),
	);

	if ( function_exists( 'pll_languages_list' ) ) {
		$args['lang'] = ''; // Every language, not just the current one.
	}

	foreach ( get_posts( $args ) as $hub_id ) {
		$template = hw_hub_template_for_post( $hub_id );

		if ( ! isset( $levels[ $template ] ) ) {
			continue;
		}

		$lang  = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $hub_id, 'slug' ) : '';
		$typed = sanitize_title( hw_destbox_saved( 'hub_term', $hub_id ) );
		$terms = array_filter( array( $typed, get_post_field( 'post_name', $hub_id ) ) );
		$tag   = $typed ? hw_hub_tag_for( $typed ) : null;

		if ( $tag ) {
			$terms[] = $tag->slug; // The tag the hub actually reads from.
		}

		$terms = array_unique( $terms );

		foreach ( $terms as $term ) {
			// Two hubs on one tag: the more specific one wins.
			if ( ! isset( $index[ $lang ][ $term ] ) || $index[ $lang ][ $term ][1] < $levels[ $template ] ) {
				$index[ $lang ][ $term ] = array( (int) $hub_id, $levels[ $template ] );
			}
		}
	}

	set_transient( HW_DESTBOX_INDEX, $index, DAY_IN_SECONDS );

	return $index;
}

/**
 * Saving, publishing or deleting a Page rebuilds the lookup.
 *
 * @param int $post_id Post.
 */
function hw_destbox_flush( $post_id ) {
	if ( 'page' === get_post_type( $post_id ) ) {
		delete_transient( HW_DESTBOX_INDEX );
	}
}
add_action( 'save_post', 'hw_destbox_flush' );
add_action( 'deleted_post', 'hw_destbox_flush' );
add_action( 'trashed_post', 'hw_destbox_flush' );

/**
 * The hub an article belongs to: the most specific hub among its tags.
 *
 * @param int $post_id Article.
 * @return array { hub: int, term: string, template: string } or empty.
 */
function hw_destbox_hub_for( $post_id ) {
	static $memo = array();

	if ( isset( $memo[ $post_id ] ) ) {
		return $memo[ $post_id ];
	}

	$memo[ $post_id ] = array();
	$tags             = get_the_terms( $post_id, 'post_tag' );

	if ( ! $tags || is_wp_error( $tags ) ) {
		return $memo[ $post_id ];
	}

	$lang  = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $post_id, 'slug' ) : '';
	$index = hw_destbox_index();
	$map   = isset( $index[ $lang ] ) ? $index[ $lang ] : array();
	$best  = null;

	// A tag matches a hub by its slug or, failing that, by its name: the
	// Venice tag is venice-italy, and its hub may have been set up as venice.
	foreach ( $tags as $tag ) {
		foreach ( array_unique( array( $tag->slug, sanitize_title( $tag->name ) ) ) as $key ) {
			if ( isset( $map[ $key ] ) && ( ! $best || $map[ $key ][1] > $best[1] ) ) {
				$best = array( $map[ $key ][0], $map[ $key ][1], $tag );
			}
		}
	}

	if ( $best ) {
		$memo[ $post_id ] = array(
			'hub'      => $best[0],
			'term'     => $best[2],
			'template' => hw_hub_template_for_post( $best[0] ),
		);
	}

	return $memo[ $post_id ];
}

/**
 * Whether this request shows a box on this article.
 *
 * @param int $post_id Article.
 * @return bool
 */
function hw_destbox_applies( $post_id ) {
	$mode = hw_destbox_mode();

	if ( 'off' === $mode || ( 'editors' === $mode && ! current_user_can( 'edit_posts' ) ) ) {
		return false;
	}

	if ( 'post' !== get_post_type( $post_id ) || get_post_meta( $post_id, '_hw_destbox_hide', true ) ) {
		return false;
	}

	return (bool) apply_filters( 'hw_destbox_applies', (bool) hw_destbox_hub_for( $post_id ), $post_id );
}

/* ---------- What goes in the box ----------------------------------------- */

/**
 * Up to three live facts for a hub.
 *
 * @param int    $hub_id   Hub Page.
 * @param string $template Template.
 * @return array List of array( icon, text ).
 */
function hw_destbox_facts( $hub_id, $template ) {
	$facts = array();
	$event = array();

	if ( 'city' === $template ) {
		$slug    = hw_destbox_saved( 'city_slug', $hub_id );
		$country = hw_destbox_saved( 'country_slug', $hub_id );
		$city    = ( $slug && $country ) ? hw_hub_api_city( $slug, $country ) : array();

		if ( ! empty( $city['properties'] ) ) {
			$facts[] = array(
				'hostel',
				/* translators: %s: number */
				sprintf( hw_hub_t( '%s places to stay on Hostelworld' ), number_format_i18n( $city['properties'] ) ),
			);
		}

		if ( ! empty( $city['id'] ) ) {
			$currency = hw_destbox_saved( 'currency', $hub_id );
			$hostels  = hw_hub_api_city_hostels(
				array(
					'city_id'  => $city['id'],
					'currency' => $currency ? strtoupper( $currency ) : 'EUR',
					'limit'    => 40,
					'hours'    => 6,
				)
			);
			$hostels  = function_exists( 'hw_hub_drop_price_outliers' ) ? hw_hub_drop_price_outliers( $hostels ) : $hostels;
			$cheapest = null;

			foreach ( $hostels as $hostel ) {
				if ( $hostel['dorm'] && ! empty( $hostel['price']['value'] ) && ( ! $cheapest || $hostel['price']['value'] < $cheapest['value'] ) ) {
					$cheapest = $hostel['price'];
				}
			}

			if ( $cheapest ) {
				/* translators: %s: price */
				$facts[] = array( 'dorm-beds', sprintf( hw_hub_t( 'Dorms from %s a night' ), hw_hub_price_label( $cheapest ) ) );
			}

			$event = hw_destbox_next_event(
				hw_hub_api_events(
					array(
						'scope'   => 'city',
						'country' => $city['id'],
						'limit'   => 8,
						'hours'   => 24,
					)
				)
			);
		}
	} elseif ( in_array( $template, array( 'country', 'country_states', 'continent' ), true ) ) {
		$guides = array_filter(
			(array) get_pages( array( 'parent' => $hub_id, 'post_status' => 'publish' ) ),
			function ( $page ) {
				return isset( hw_destbox_levels()[ hw_hub_template_for_post( $page->ID ) ] );
			}
		);

		if ( $guides ) {
			$facts[] = array(
				'map-pin',
				sprintf(
					/* translators: %s: number */
					hw_hub_t( ( 'continent' === $template ? '%s country guide' : ( 'country_states' === $template ? '%s state guide' : '%s city guide' ) ) . ( 1 === count( $guides ) ? '' : 's' ) ),
					number_format_i18n( count( $guides ) )
				),
			);
		}

		$events = 'continent' === $template ? 'events_continent' : 'events_country';
		$where  = hw_destbox_saved( $events, $hub_id );

		if ( $where ) {
			$event = hw_destbox_next_event(
				hw_hub_api_events(
					array(
						'scope'   => 'continent' === $template ? 'continent' : 'country',
						'country' => $where,
						'limit'   => 8,
						'hours'   => 24,
					)
				)
			);
		}
	}

	if ( $event ) {
		$facts[] = array(
			'ticket',
			! empty( $event['ongoing'] ) && $event['end']
				/* translators: 1: event, 2: end date */
				? sprintf( hw_hub_t( 'On now: %1$s, until %2$s' ), $event['name'], date_i18n( 'j F', strtotime( $event['end'] ) ) )
				/* translators: 1: event, 2: dates */
				: sprintf( hw_hub_t( 'Coming up: %1$s, %2$s' ), $event['name'], hw_hub_event_dates( $event['start'], $event['end'] ) ),
		);
	}

	return array_slice( $facts, 0, 3 );
}

/**
 * The biggest upcoming event: the feed is already ordered staff picks first,
 * then by popularity, and only lists what has not finished.
 *
 * @param array $events Events.
 * @return array
 */
function hw_destbox_next_event( $events ) {
	$today   = current_time( 'Y-m-d' );
	$ongoing = array();

	foreach ( (array) $events as $event ) {
		if ( empty( $event['name'] ) || empty( $event['start'] ) ) {
			continue;
		}

		if ( substr( $event['start'], 0, 10 ) > $today ) {
			return $event;
		}

		if ( ! $ongoing ) {
			$ongoing            = $event;
			$ongoing['ongoing'] = true;
		}
	}

	return $ongoing;
}

/**
 * Other articles on the same destination, freshest first.
 *
 * @param WP_Term $term    Destination tag.
 * @param int     $exclude This article.
 * @param int     $count   How many.
 * @return WP_Post[]
 */
function hw_destbox_related( $term, $exclude, $count = 3 ) {
	$lang = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( $exclude, 'slug' ) : '';
	$key  = 'hw_destbox_rel_' . $term->term_id . '_' . $lang;
	$ids  = get_transient( $key );

	if ( ! is_array( $ids ) ) {
		$args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $count + 1,
			'tag_id'              => $term->term_id,
			'orderby'             => 'modified',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
		);

		if ( $lang ) {
			$args['lang'] = $lang;
		}

		$ids = get_posts( $args );
		set_transient( $key, $ids, 12 * HOUR_IN_SECONDS );
	}

	$ids = array_slice( array_values( array_diff( array_map( 'intval', $ids ), array( (int) $exclude ) ) ), 0, $count );

	return $ids ? array_map( 'get_post', $ids ) : array();
}

/**
 * The trail above the hub (Europe › Spain), built from the Page hierarchy.
 *
 * @param int $hub_id Hub.
 * @return array List of array( name, url ).
 */
function hw_destbox_trail( $hub_id ) {
	$trail = array();

	foreach ( array_reverse( get_post_ancestors( $hub_id ) ) as $ancestor ) {
		if ( isset( hw_destbox_levels()[ hw_hub_template_for_post( $ancestor ) ] ) && 'publish' === get_post_status( $ancestor ) ) {
			$trail[] = array( 'name' => hw_hub_page_title( $ancestor ), 'url' => get_permalink( $ancestor ) );
		}
	}

	return $trail;
}

/**
 * The box's HTML for an article.
 *
 * @param int $post_id Article.
 * @return string
 */
function hw_destbox_html( $post_id ) {
	$found = hw_destbox_hub_for( $post_id );

	if ( ! $found ) {
		return '';
	}

	$hub_id  = $found['hub'];
	$name    = hw_hub_page_title( $hub_id );
	$url     = get_permalink( $hub_id );
	$trail   = hw_destbox_trail( $hub_id );
	$facts   = hw_destbox_facts( $hub_id, $found['template'] );
	$related = hw_destbox_related( $found['term'], $post_id );

	ob_start();
	?>
	<aside class="hw-destbox" aria-label="<?php echo esc_attr( $name ); ?>">
		<div class="hw-destbox__main">
			<?php if ( $trail ) : ?>
				<p class="hw-destbox__trail">
					<?php foreach ( $trail as $crumb ) : ?>
						<a href="<?php echo esc_url( $crumb['url'] ); ?>" data-hw-track="destbox_trail" data-hw-track-label="<?php echo esc_attr( $crumb['name'] ); ?>"><?php echo esc_html( $crumb['name'] ); ?></a><span aria-hidden="true"> › </span>
					<?php endforeach; ?>
					<span><?php echo esc_html( $name ); ?></span>
				</p>
			<?php endif; ?>
			<p class="hw-destbox__title">
				<?php
				/* translators: %s: destination */
				echo esc_html( sprintf( hw_hub_t( 'Planning a trip to %s?' ), $name ) );
				?>
			</p>
			<?php if ( $facts ) : ?>
				<ul class="hw-destbox__facts">
					<?php foreach ( $facts as $fact ) : ?>
						<li><?php echo hw_hub_icon( $fact[0], 16 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $fact[1] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<a class="hw-destbox__btn" href="<?php echo esc_url( $url ); ?>" data-hw-track="destbox_hub" data-hw-track-label="<?php echo esc_attr( $name ); ?>">
				<?php
				/* translators: %s: destination */
				echo esc_html( sprintf( hw_hub_t( 'Read our %s guide' ), $name ) );
				?>
				&rarr;
			</a>
		</div>
		<?php if ( $related ) : ?>
			<div class="hw-destbox__more">
				<p class="hw-destbox__more-title">
					<?php
					/* translators: %s: destination */
					echo esc_html( sprintf( hw_hub_t( 'More on %s' ), $name ) );
					?>
				</p>
				<ul>
					<?php foreach ( $related as $item ) : ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $item ) ); ?>" data-hw-track="destbox_article" data-hw-track-label="<?php echo esc_attr( get_the_title( $item ) ); ?>">
								<?php if ( has_post_thumbnail( $item ) ) : ?>
									<?php echo get_the_post_thumbnail( $item, 'thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
								<?php endif; ?>
								<span><?php echo esc_html( get_the_title( $item ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</aside>
	<?php
	return trim( (string) ob_get_clean() );
}

/* ---------- Placing it in the article ------------------------------------ */

/**
 * Adds the box after the third paragraph of text. Paragraphs inside quotes,
 * tables, lists or boxes are not counted, so it never lands in the middle of
 * one. A short article gets it at the end.
 *
 * @param string $content Article HTML.
 * @return string
 */
function hw_destbox_insert( $content ) {
	if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id = get_the_ID();

	if ( ! hw_destbox_applies( $post_id ) || false !== strpos( $content, 'hw-destbox' ) ) {
		return $content;
	}

	$box = hw_destbox_html( $post_id );

	if ( '' === $box ) {
		return $content;
	}

	hw_hostels_print_tracking();

	$after = (int) apply_filters( 'hw_destbox_after_paragraph', 3, $post_id );
	$count = 0;
	$depth = 0;

	if ( preg_match_all( '#<(/?)(blockquote|table|ul|ol|div|figure|aside)\b[^>]*>|<p\b[^>]*>(.*?)</p>#is', $content, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m as $match ) {
			if ( ! empty( $match[2][0] ) ) {
				$depth += '/' === $match[1][0] ? -1 : 1;
				$depth  = max( 0, $depth );
				continue;
			}

			if ( $depth > 0 || '' === trim( wp_strip_all_tags( $match[3][0] ) ) ) {
				continue;
			}

			if ( ++$count === $after ) {
				$at = $match[0][1] + strlen( $match[0][0] );
				return substr( $content, 0, $at ) . "\n" . $box . "\n" . substr( $content, $at );
			}
		}
	}

	return $content . "\n" . $box;
}
add_filter( 'the_content', 'hw_destbox_insert', 20 );

/**
 * The stylesheet, in the head, only where the box will show.
 */
function hw_destbox_assets() {
	if ( is_singular( 'post' ) && hw_destbox_applies( get_queried_object_id() ) ) {
		wp_enqueue_style( 'hw-destbox', HW_HUB_URL . 'assets/destination-box.css', array(), HW_HUB_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'hw_destbox_assets' );

/* ---------- Editing ------------------------------------------------------ */

/**
 * A note on each article's edit screen: which hub its box points to, and a
 * checkbox to hide it on this article.
 */
function hw_destbox_meta_box() {
	add_meta_box( 'hw-destbox', __( 'Destination box', 'hostelworld' ), 'hw_destbox_render_meta_box', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'hw_destbox_meta_box' );

/**
 * The meta box.
 *
 * @param WP_Post $post Article.
 */
function hw_destbox_render_meta_box( $post ) {
	wp_nonce_field( 'hw_destbox_save', 'hw_destbox_nonce' );
	$found = hw_destbox_hub_for( $post->ID );
	$mode  = hw_destbox_mode();

	if ( $found ) {
		printf(
			'<p>%s <a href="%s" target="_blank" rel="noopener">%s</a> <span class="description">(%s)</span></p>',
			esc_html__( 'Points to', 'hostelworld' ),
			esc_url( get_permalink( $found['hub'] ) ),
			esc_html( hw_hub_page_title( $found['hub'] ) ),
			esc_html( sprintf( /* translators: %s: tag */ __( 'from the tag %s', 'hostelworld' ), $found['term']->slug ) )
		);
	} else {
		echo '<p>' . esc_html__( 'None of this article\'s tags has a published hub, so no box shows. Add a destination tag to get one.', 'hostelworld' ) . '</p>';
	}

	if ( 'all' !== $mode ) {
		echo '<p class="description">' . esc_html( 'off' === $mode ? __( 'Switched off for the whole blog in Settings → Blog hubs.', 'hostelworld' ) : __( 'Preview: only logged-in editors see boxes for now. Switch them on for readers in Settings → Blog hubs.', 'hostelworld' ) ) . '</p>';
	}
	?>
	<label><input type="checkbox" name="hw_destbox_hide" value="1" <?php checked( (bool) get_post_meta( $post->ID, '_hw_destbox_hide', true ) ); ?>> <?php esc_html_e( 'Hide the box on this article', 'hostelworld' ); ?></label>
	<?php
}

/**
 * Saves the checkbox.
 *
 * @param int $post_id Article.
 */
function hw_destbox_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['hw_destbox_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hw_destbox_nonce'] ), 'hw_destbox_save' ) ) {
		return;
	}

	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! empty( $_POST['hw_destbox_hide'] ) ) {
		update_post_meta( $post_id, '_hw_destbox_hide', '1' );
	} else {
		delete_post_meta( $post_id, '_hw_destbox_hide' );
	}
}
add_action( 'save_post_post', 'hw_destbox_save_meta_box' );

/**
 * The on/off setting, on Settings → Blog hubs.
 */
function hw_destbox_register_setting() {
	register_setting(
		'hw_hub_settings',
		'hw_hub_destbox_mode',
		array(
			'type'              => 'string',
			'default'           => 'editors',
			'sanitize_callback' => function ( $value ) {
				return in_array( $value, array( 'all', 'editors', 'off' ), true ) ? $value : 'editors';
			},
		)
	);

	add_settings_section( 'hw_destbox', __( 'Destination box on articles', 'hostelworld' ), '__return_false', 'hw-hub-settings' );
	add_settings_field(
		'hw_hub_destbox_mode',
		__( 'Who sees it', 'hostelworld' ),
		function () {
			$mode    = hw_destbox_mode();
			$choices = array(
				'all'     => __( 'Everyone: readers and editors', 'hostelworld' ),
				'editors' => __( 'Preview: only logged-in editors (readers see nothing)', 'hostelworld' ),
				'off'     => __( 'Nobody: switched off', 'hostelworld' ),
			);

			foreach ( $choices as $value => $label ) {
				printf(
					'<label style="display:block;margin-bottom:6px"><input type="radio" name="hw_hub_destbox_mode" value="%s" %s> %s</label>',
					esc_attr( $value ),
					checked( $mode, $value, false ),
					esc_html( $label )
				);
			}

			echo '<p class="description">' . esc_html__( 'Added after the third paragraph of any article tagged with a destination that has a published hub, pointing to the most specific one (city, then country, then continent). Hide it on one article from the box on its edit screen.', 'hostelworld' ) . '</p>';
		},
		'hw-hub-settings',
		'hw_destbox'
	);
}
add_action( 'admin_init', 'hw_destbox_register_setting' );
