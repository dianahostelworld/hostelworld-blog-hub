<?php
/**
 * The "Continent hub content" panel on the Page edit screen.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows the panel only on Pages using the hub template, so it doesn't clutter
 * every other Page. Pick the template, hit Update, and it appears.
 */
function hw_hub_add_meta_box() {
	global $post;

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$slug = get_page_template_slug( $post->ID );

	if ( ! in_array( $slug, hw_hub_template_keys(), true ) ) {
		return;
	}

	add_meta_box(
		'hw-hub-content',
		hw_hub_panel_title( $slug ),
		'hw_hub_render_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_page', 'hw_hub_add_meta_box' );

/**
 * Renders the panel from the schema.
 *
 * @param WP_Post $post Current page.
 */
function hw_hub_render_meta_box( $post ) {
	wp_nonce_field( 'hw_hub_save', 'hw_hub_nonce' );
	$template = hw_hub_template_for_post( $post->ID );
	$sections = hw_hub_schema( $template );
	$first    = true;
	?>
	<div class="hw-hub-admin">
		<p class="hw-hub-admin__lead">
			<?php if ( in_array( $template, array( 'country_states', 'state' ), true ) ) : ?>
				<?php esc_html_e( 'Everything on the hub page is edited here. Only the settings and headings start filled in — the copy is yours to write or import, so nothing invented reaches the page.', 'hostelworld' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Everything on the hub page is edited here. Fields start out filled with the Europe copy as a worked example — overwrite what you need and delete what you don\'t.', 'hostelworld' ); ?>
			<?php endif; ?>
		</p>

		<?php hw_hub_import_form( $post, $template ); ?>

		<?php hw_hub_render_translate_bar( $post->ID ); ?>

		<div class="hw-hub-admin__tabs">
			<?php foreach ( $sections as $key => $section ) : ?>
				<button type="button" class="hw-hub-admin__tab<?php echo $first ? ' is-active' : ''; ?>" data-section="<?php echo esc_attr( $key ); ?>">
					<?php echo esc_html( $section['label'] ); ?>
				</button>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</div>

		<?php
		$first = true;
		foreach ( $sections as $key => $section ) :
			?>
			<div class="hw-hub-admin__panel<?php echo $first ? ' is-active' : ''; ?>" data-section="<?php echo esc_attr( $key ); ?>">
				<?php
				foreach ( $section['fields'] as $field_key => $field ) {
					hw_hub_render_field( $field_key, $field, $post->ID );
				}

				if ( 'settings' === $key ) {
					hw_hub_render_status( $post->ID );
				}
				?>
			</div>
			<?php
			$first = false;
		endforeach;
		?>
	</div>
	<?php
}

/**
 * Renders one field.
 *
 * @param string $key     Field key.
 * @param array  $field   Field definition.
 * @param int    $post_id Page ID.
 */
function hw_hub_render_field( $key, $field, $post_id ) {
	$name  = 'hw_hub[' . $key . ']';
	$id    = 'hw-hub-' . $key;
	$value = hw_hub_raw_value( $key, $post_id );
	$rows  = isset( $field['rows'] ) ? (int) $field['rows'] : 4;
	?>
	<div class="hw-hub-admin__field hw-hub-admin__field--<?php echo esc_attr( $field['type'] ); ?>">
		<?php /* Marks this field as having been on screen. Without it, any save
			that doesn't carry the panel would overwrite the field with nothing. */ ?>
		<input type="hidden" name="hw_hub_present[<?php echo esc_attr( $key ); ?>]" value="1">

		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $field['label'] ); ?></label>

		<?php if ( 'text' === $field['type'] ) : ?>
			<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="widefat">

		<?php elseif ( 'textarea' === $field['type'] ) : ?>
			<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( $rows ); ?>" class="widefat"><?php echo esc_textarea( $value ); ?></textarea>

		<?php elseif ( 'select' === $field['type'] ) : ?>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>">
				<?php foreach ( $field['options'] as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $option_value, $value ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>

		<?php elseif ( 'check' === $field['type'] ) : ?>
			<label class="hw-hub-admin__check">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( '1', $value ); ?>>
				<span><?php esc_html_e( 'Yes', 'hostelworld' ); ?></span>
			</label>

		<?php elseif ( 'media' === $field['type'] ) : ?>
			<div class="hw-hub-admin__media" data-target="<?php echo esc_attr( $id ); ?>">
				<div class="hw-hub-admin__preview">
					<?php
					if ( $value && is_numeric( $value ) ) {
						echo wp_get_attachment_image( (int) $value, 'medium' );
					}
					?>
				</div>
				<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<button type="button" class="button hw-hub-admin__select"><?php esc_html_e( 'Choose image', 'hostelworld' ); ?></button>
				<button type="button" class="button-link hw-hub-admin__remove"<?php echo $value ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'hostelworld' ); ?></button>
			</div>

		<?php elseif ( 'rows' === $field['type'] ) : ?>
			<?php hw_hub_render_rows( $key, $field, hw_hub_text_to_list( $value, $field['cols'] ) ); ?>

		<?php elseif ( 'list' === $field['type'] ) : ?>
			<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="<?php echo esc_attr( $rows ); ?>" class="widefat hw-hub-admin__list" spellcheck="false" data-default="<?php echo esc_attr( hw_hub_default_text( $key, hw_hub_template_for_post( $post_id ) ) ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
			<p class="hw-hub-admin__actions">
				<button type="button" class="button-link hw-hub-admin__restore"><?php esc_html_e( 'Restore starting content', 'hostelworld' ); ?></button>
			</p>
			<p class="hw-hub-admin__cols">
				<?php
				printf(
					/* translators: %s: column names */
					esc_html__( 'One per line, in this order: %s', 'hostelworld' ),
					'<code>' . esc_html( implode( ' | ', $field['cols'] ) ) . '</code>'
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( ! empty( $field['help'] ) ) : ?>
			<p class="hw-hub-admin__help"><?php echo esc_html( $field['help'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The translate button, and whatever the last run had to say.
 *
 * @param int $post_id Page ID.
 */
function hw_hub_render_translate_bar( $post_id ) {
	if ( ! function_exists( 'pll_get_post_language' ) ) {
		return;
	}

	$lang = pll_get_post_language( $post_id, 'name' );
	$url  = wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'hw_hub_translate',
				'post'   => $post_id,
			),
			admin_url( 'admin-post.php' )
		),
		'hw_hub_translate_' . $post_id
	);
	?>
	<?php if ( isset( $_GET['hw_hub_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-success inline">
			<p>
				<?php
				printf(
					/* translators: %s: number of fields */
					esc_html__( 'Translated %s fields. Read them before publishing — machine translation is a first draft, not a final one.', 'hostelworld' ),
					absint( $_GET['hw_hub_done'] ) // phpcs:ignore WordPress.Security.NonceVerification
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['hw_hub_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
		<div class="notice notice-error inline">
			<p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['hw_hub_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification ?></p>
		</div>
	<?php endif; ?>

	<div class="hw-hub-admin__translate">
		<div>
			<strong><?php esc_html_e( 'Translate this page\'s fields', 'hostelworld' ); ?></strong>
			<p>
				<?php
				printf(
					/* translators: %s: language name */
					esc_html__( 'Fills every field on this page by translating the same fields from another language into %s, using DeepL. Tags, IDs, image references and settings are copied untouched. Anything already typed here is overwritten.', 'hostelworld' ),
					esc_html( $lang ? $lang : __( 'this language', 'hostelworld' ) )
				);
				?>
			</p>
		</div>

		<?php if ( hw_hub_deepl_key() ) : ?>
			<a class="button button-secondary" href="<?php echo esc_url( $url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Translate and overwrite every field on this page?', 'hostelworld' ) ); ?>');">
				<?php esc_html_e( 'Translate with DeepL', 'hostelworld' ); ?>
			</a>
		<?php else : ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=hw-hub-settings' ) ); ?>">
				<?php esc_html_e( 'Add a DeepL key first', 'hostelworld' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders a repeating row editor.
 *
 * Each cell posts as hw_hub_rows[field][index][column], so the rows survive a
 * save with JavaScript switched off — the Add and Remove buttons are the only
 * part that needs it. On save the rows are collapsed back into the same
 * pipe-separated string the textarea fields use, so nothing downstream changes.
 *
 * @param string $key   Field key.
 * @param array  $field Field definition.
 * @param array  $rows  Existing rows.
 */
function hw_hub_render_rows( $key, $field, $rows ) {
	// Used below to find this page's template; it was never passed in.
	$post_id = get_the_ID();
	$labels = isset( $field['labels'] ) ? $field['labels'] : array();

	// One empty row so there's always somewhere to start typing.
	if ( ! $rows ) {
		$rows = array( array_fill_keys( $field['cols'], '' ) );
	}
	?>
	<div class="hw-hub-admin__rows" data-field="<?php echo esc_attr( $key ); ?>">
		<div class="hw-hub-admin__rows-list">
			<?php foreach ( array_values( $rows ) as $index => $row ) : ?>
				<?php hw_hub_render_row( $key, $field, $labels, $row, $index ); ?>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button hw-hub-admin__row-add">
			<?php echo esc_html( isset( $field['add'] ) ? $field['add'] : __( 'Add a row', 'hostelworld' ) ); ?>
		</button>

		<button type="button" class="button-link hw-hub-admin__rows-restore">
			<?php esc_html_e( 'Restore starting content', 'hostelworld' ); ?>
		</button>

		<script type="text/html" class="hw-hub-admin__rows-default">
			<?php
			$starting = hw_hub_text_to_list( hw_hub_default_text( $key, hw_hub_template_for_post( $post_id ) ), $field['cols'] );

			foreach ( array_values( $starting ) as $index => $row ) {
				hw_hub_render_row( $key, $field, $labels, $row, $index );
			}
			?>
		</script>

		<script type="text/html" class="hw-hub-admin__row-template">
			<?php hw_hub_render_row( $key, $field, $labels, array_fill_keys( $field['cols'], '' ), '__index__' ); ?>
		</script>
	</div>
	<?php
}

/**
 * One row of a repeating field.
 *
 * @param string     $key    Field key.
 * @param array      $field  Field definition.
 * @param array      $labels Column labels.
 * @param array      $row    Row values.
 * @param int|string $index  Row index, or the template placeholder.
 */
function hw_hub_render_row( $key, $field, $labels, $row, $index ) {
	?>
	<div class="hw-hub-admin__row">
		<div class="hw-hub-admin__row-cells">
			<?php foreach ( $field['cols'] as $col ) : ?>
				<?php
				$name  = 'hw_hub_rows[' . $key . '][' . $index . '][' . $col . ']';
				$value = isset( $row[ $col ] ) ? $row[ $col ] : '';
				$label = isset( $labels[ $col ] ) ? $labels[ $col ] : ucfirst( $col );
				?>
				<div class="hw-hub-admin__cell hw-hub-admin__cell--<?php echo esc_attr( $col ); ?>">
					<span class="hw-hub-admin__cell-label"><?php echo esc_html( $label ); ?></span>

					<?php if ( ! empty( $field['pairs'] ) && in_array( $col, (array) $field['pairs'], true ) ) : ?>
						<?php hw_hub_render_pairs( $key, $col, $index, $value ); ?>

					<?php elseif ( ! empty( $field['quotes'] ) && in_array( $col, (array) $field['quotes'], true ) ) : ?>
						<?php
						// A state is not a Hostelworld city, so a states row names the
						// city whose reviews to offer in a column of its own.
						$quote_col  = ! empty( $field['quote_city'] ) ? $field['quote_city'] : 'name';
						$quote_city = isset( $row[ $quote_col ] ) ? $row[ $quote_col ] : '';
						hw_hub_render_quote_picker( $name, $value, $quote_city, 'name' !== $quote_col );
						?>

					<?php elseif ( 'image' === $col ) : ?>
						<div class="hw-hub-admin__media hw-hub-admin__media--row">
							<div class="hw-hub-admin__preview">
								<?php
								if ( $value && is_numeric( $value ) ) {
									echo wp_get_attachment_image( (int) $value, 'thumbnail' );
								} elseif ( $value ) {
									echo '<img src="' . esc_url( $value ) . '" alt="">';
								}
								?>
							</div>
							<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
							<button type="button" class="button button-small hw-hub-admin__select"><?php esc_html_e( 'Upload / choose', 'hostelworld' ); ?></button>
							<button type="button" class="button-link hw-hub-admin__remove"<?php echo $value ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'hostelworld' ); ?></button>
						</div>
					<?php else : ?>
						<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="widefat">
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button-link hw-hub-admin__row-delete" aria-label="<?php esc_attr_e( 'Delete this row', 'hostelworld' ); ?>">&times;</button>
	</div>
	<?php
}

/**
 * The panel's title, which depends on the template.
 *
 * @param string $slug Template slug.
 * @return string
 */
function hw_hub_panel_title( $slug ) {
	if ( HW_HUB_COUNTRY_TEMPLATE_KEY === $slug ) {
		return __( 'Country hub content', 'hostelworld' );
	}

	if ( HW_HUB_CITY_TEMPLATE_KEY === $slug ) {
		return __( 'City hub content', 'hostelworld' );
	}

	if ( HW_HUB_COUNTRY_STATES_TEMPLATE_KEY === $slug ) {
		return __( 'Country hub (states) content', 'hostelworld' );
	}

	if ( HW_HUB_STATE_TEMPLATE_KEY === $slug ) {
		return __( 'State hub content', 'hostelworld' );
	}

	return __( 'Continent hub content', 'hostelworld' );
}

/**
 * Every carousel on the page, whichever template it is, so the status panel can
 * report what each one currently matches.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function hw_hub_status_rows( $post_id ) {
	$rows = array();

	$template = hw_hub_template_for_post( $post_id );

	if ( 'city' === $template || 'state' === $template ) {
		$sections = array(
			'do'        => 'Things to do',
			'free'      => 'Free things to do',
			'food'      => 'Food and drink',
			'nightlife' => 'Nightlife',
			'transport' => 'Getting around',
			'money'     => 'Costs',
			'safety'    => 'Safety',
		);

		if ( 'state' === $template ) {
			$sections['cities'] = 'Main cities';
		} else {
			$sections['trips'] = 'Day trips';
		}

		foreach ( $sections as $key => $label ) {
			$tag = hw_hub_value( $key . '_tag', $post_id );

			if ( '' === trim( (string) $tag ) ) {
				continue;
			}

			$rows[] = array( 'tag' => $tag, 'title' => $label );
		}

		return $rows;
	}

	if ( 'country' === $template || 'country_states' === $template ) {
		$sections = array(
			'do'        => 'Things to do',
			'food'      => 'Food and drink',
			'nightlife' => 'Nightlife',
			'transport' => 'Getting around',
			'money'     => 'Costs',
			'safety'    => 'Safety',
			'entry'     => 'Entry and documents',
		);

		foreach ( $sections as $key => $label ) {
			$tag = hw_hub_value( $key . '_tag', $post_id );

			if ( '' === trim( (string) $tag ) ) {
				continue;
			}

			$rows[] = array(
				'tag'   => $tag,
				'title' => $label,
			);
		}

		foreach ( hw_hub_value( 'trip_types', $post_id ) as $trip ) {
			if ( ! empty( $trip['tag'] ) ) {
				$rows[] = array(
					'tag'   => $trip['tag'],
					'title' => $trip['name'],
				);
			}
		}

		return $rows;
	}

	foreach ( hw_hub_value( 'article_rows', $post_id ) as $row ) {
		$rows[] = array(
			'tag'   => $row['tag'],
			'title' => $row['title'],
		);
	}

	return $rows;
}

/**
 * Shows whether this hub's tag exists and what each article row currently
 * matches. Every destination lives twice — as this Page, and as a tag that
 * articles carry — and until the tag exists the rows have nothing to pull.
 *
 * @param int $post_id Page ID.
 */
function hw_hub_render_status( $post_id ) {
	$hub_term = hw_hub_value( 'hub_term', $post_id );
	$term     = $hub_term ? hw_hub_find_term( $hub_term ) : null;
	$config   = array(
		'hub_term'   => $hub_term,
		'scope_rows' => hw_hub_value( 'scope_rows', $post_id ),
	);
	?>
	<div class="hw-hub-admin__status">
		<h4><?php esc_html_e( 'Status', 'hostelworld' ); ?></h4>

		<p class="hw-hub-admin__status-line">
			<strong><?php esc_html_e( 'This page:', 'hostelworld' ); ?></strong>
			<?php echo esc_html( get_the_title( $post_id ) ); ?>
			<?php if ( 'publish' !== get_post_status( $post_id ) ) : ?>
				<span class="hw-hub-admin__flag"><?php esc_html_e( 'not published yet', 'hostelworld' ); ?></span>
			<?php endif; ?>
		</p>

		<p class="hw-hub-admin__status-line">
			<strong><?php esc_html_e( 'Matching tag:', 'hostelworld' ); ?></strong>
			<?php if ( $term ) : ?>
				<code><?php echo esc_html( $term->slug ); ?></code>
				<?php
				printf(
					/* translators: %s: post count */
					esc_html( _n( 'exists, on %s post', 'exists, on %s posts', $term->count, 'hostelworld' ) ),
					esc_html( number_format_i18n( $term->count ) )
				);
				?>
				<a href="<?php echo esc_url( get_edit_term_link( $term->term_id, $term->taxonomy ) ); ?>"><?php esc_html_e( 'Edit', 'hostelworld' ); ?></a>
			<?php else : ?>
				<span class="hw-hub-admin__flag">
					<?php
					printf(
						/* translators: %s: term slug */
						esc_html__( 'no tag called %s yet — article rows will stay empty until it exists and posts carry it', 'hostelworld' ),
						'<code>' . esc_html( $hub_term ) . '</code>'
					);
					?>
				</span>
				<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=post_tag' ) ); ?>"><?php esc_html_e( 'Create it', 'hostelworld' ); ?></a>
			<?php endif; ?>
		</p>

		<p class="hw-hub-admin__status-line"><strong><?php esc_html_e( 'Article rows:', 'hostelworld' ); ?></strong></p>
		<ul class="hw-hub-admin__status-rows">
			<?php foreach ( hw_hub_status_rows( $post_id ) as $row ) : ?>
				<?php
				$expression = hw_hub_row_expression( $config, $row['tag'] );
				$count      = hw_hub_count_posts( $expression );
				$missing    = hw_hub_missing_slugs( $expression );
				?>
				<li>
					<code><?php echo esc_html( $expression ); ?></code>
					<?php if ( $missing ) : ?>
						<span class="hw-hub-admin__flag">
							<?php
							printf(
								/* translators: %s: list of tag slugs */
								esc_html__( 'no tag: %s', 'hostelworld' ),
								esc_html( implode( ', ', $missing ) )
							);
							?>
						</span>
					<?php else : ?>
						<?php
						printf(
							/* translators: %s: post count */
							esc_html( _n( '%s post', '%s posts', $count, 'hostelworld' ) ),
							esc_html( number_format_i18n( $count ) )
						);
						?>
					<?php endif; ?>
					<span class="hw-hub-admin__status-label"><?php echo esc_html( $row['title'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="hw-hub-admin__status">
		<h4><?php esc_html_e( 'Icons you can use', 'hostelworld' ); ?></h4>
		<p class="hw-hub-admin__status-line"><?php esc_html_e( 'Official WDS icons. Type the name into any icon column.', 'hostelworld' ); ?></p>
		<div class="hw-hub-admin__icons">
			<?php foreach ( hw_hub_icon_names() as $icon_name ) : ?>
				<span class="hw-hub-admin__icon">
					<?php echo hw_hub_icon( $icon_name, 18 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<code><?php echo esc_html( $icon_name ); ?></code>
				</span>
			<?php endforeach; ?>
		</div>

		<h4><?php esc_html_e( 'Illustrations you can use', 'hostelworld' ); ?></h4>
		<p class="hw-hub-admin__status-line">
			<?php esc_html_e( 'Official WDS illustrations. In any image field, type illustration: followed by the name, e.g. illustration:continent-map-europe.', 'hostelworld' ); ?>
		</p>
		<div class="hw-hub-admin__illustrations">
			<?php foreach ( hw_hub_illustration_names() as $art ) : ?>
				<span class="hw-hub-admin__art">
					<img src="<?php echo esc_url( HW_HUB_URL . 'assets/illustrations/' . $art . '.svg' ); ?>" alt="">
					<code><?php echo esc_html( $art ); ?></code>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * A dropdown of this city's real traveller reviews, above the text field.
 *
 * Reviews are fetched live for whichever city is typed in the row, so picking
 * one is a matter of reading them rather than hunting for them on the site.
 * Choosing fills the text box, which is what actually gets saved — so an
 * edited or hand-written quote works exactly the same way.
 *
 * @param string $name  Input name.
 * @param string $value Current quote.
 * @param string $city  City name from this row.
 * @param bool   $city_from_column Whether the city comes from its own column
 *                                 (states rows) rather than the row's name.
 */
function hw_hub_render_quote_picker( $name, $value, $city, $city_from_column = false ) {
	global $post;

	$country = $post instanceof WP_Post ? hw_hub_value( 'events_country', $post->ID ) : '';
	$reviews = ( $city && $country ) ? hw_hub_api_city_reviews( $city, $country ) : array();
	$id      = 'hw-hub-quote-' . md5( $name );
	?>
	<?php if ( $reviews ) : ?>
		<select class="widefat hw-hub-admin__quote-pick" data-target="<?php echo esc_attr( $id ); ?>">
			<option value=""><?php esc_html_e( 'Pick a traveller review…', 'hostelworld' ); ?></option>
			<?php foreach ( $reviews as $review ) : ?>
				<option value="<?php echo esc_attr( $review['text'] ); ?>">
					<?php
					echo esc_html(
						sprintf(
							'%s%s — %s',
							number_format_i18n( $review['rating'], 1 ),
							$review['good'] ? ' ★' : '',
							mb_strimwidth( $review['text'], 0, 90, '…' )
						)
					);
					?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php elseif ( $city ) : ?>
		<p class="hw-hub-admin__help"><?php esc_html_e( 'No reviews came back for this city. Check the spelling against Hostelworld, or write your own quote.', 'hostelworld' ); ?></p>
	<?php elseif ( $city_from_column ) : ?>
		<p class="hw-hub-admin__help"><?php esc_html_e( 'Fill in "Reviews from (Hostelworld city)" and update the page to pick from that city\'s traveller reviews. Or write your own quote.', 'hostelworld' ); ?></p>
	<?php endif; ?>

	<input type="text" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Or write your own', 'hostelworld' ); ?>">
	<?php
}

/**
 * A small list of label-and-URL rows inside one cell of a repeating row: a
 * city's itineraries, for instance, where one city has five and another has
 * none.
 *
 * Each half posts as its own input, so rows survive a save without JavaScript;
 * the Add and × buttons are the only part that needs it. On save the pairs are
 * folded back into one "label=url; label=url" string, which is what the cell
 * has always stored.
 *
 * @param string     $key   Field key.
 * @param string     $col   Column name.
 * @param int|string $index Row index.
 * @param string     $value Current cell value.
 */
function hw_hub_render_pairs( $key, $col, $index, $value ) {
	$pairs = array();

	foreach ( explode( ';', (string) $value ) as $entry ) {
		$parts = explode( '=', $entry, 2 );

		if ( count( $parts ) < 2 ) {
			continue;
		}

		$pairs[] = array(
			'label' => trim( $parts[0] ),
			'url'   => trim( $parts[1] ),
		);
	}

	// Always one empty row to type into.
	$pairs[] = array( 'label' => '', 'url' => '' );

	$name = 'hw_hub_pairs[' . $key . '][' . $index . '][' . $col . ']';
	?>
	<div class="hw-hub-admin__pairs" data-pair-name="<?php echo esc_attr( $name ); ?>">
		<div class="hw-hub-admin__pairs-list">
			<?php foreach ( array_values( $pairs ) as $i => $pair ) : ?>
				<div class="hw-hub-admin__pair">
					<input type="text" class="hw-hub-admin__pair-label" name="<?php echo esc_attr( $name . '[' . $i . '][label]' ); ?>" value="<?php echo esc_attr( $pair['label'] ); ?>" placeholder="<?php esc_attr_e( '3 days', 'hostelworld' ); ?>">
					<input type="text" class="hw-hub-admin__pair-url" name="<?php echo esc_attr( $name . '[' . $i . '][url]' ); ?>" value="<?php echo esc_attr( $pair['url'] ); ?>" placeholder="https://www.hostelworld.com/blog/…">
					<button type="button" class="button-link hw-hub-admin__pair-delete" aria-label="<?php esc_attr_e( 'Remove this itinerary', 'hostelworld' ); ?>">&times;</button>
				</div>
			<?php endforeach; ?>
		</div>

		<button type="button" class="button button-small hw-hub-admin__pair-add"><?php esc_html_e( 'Add another', 'hostelworld' ); ?></button>
	</div>
	<?php
}

/**
 * Saves the panel.
 *
 * @param int $post_id Page ID.
 */
function hw_hub_save_meta_box( $post_id ) {
	if ( ! isset( $_POST['hw_hub_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['hw_hub_nonce'] ) ), 'hw_hub_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	$submitted = isset( $_POST['hw_hub'] ) ? wp_unslash( $_POST['hw_hub'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	if ( ! is_array( $submitted ) ) {
		return;
	}

	$submitted_rows = isset( $_POST['hw_hub_rows'] ) ? wp_unslash( $_POST['hw_hub_rows'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$present        = isset( $_POST['hw_hub_present'] ) ? (array) wp_unslash( $_POST['hw_hub_present'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$submitted_pairs = isset( $_POST['hw_hub_pairs'] ) ? (array) wp_unslash( $_POST['hw_hub_pairs'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	foreach ( hw_hub_schema_fields( hw_hub_template_for_post( $post_id ) ) as $key => $field ) {
		// A field that wasn't rendered can't have been edited. Writing to it
		// anyway is how a whole section gets emptied by an unrelated save.
		if ( ! isset( $present[ $key ] ) ) {
			continue;
		}

		$raw = isset( $submitted[ $key ] ) ? $submitted[ $key ] : '';

		if ( 'rows' === $field['type'] ) {
			$rows  = ( is_array( $submitted_rows ) && isset( $submitted_rows[ $key ] ) && is_array( $submitted_rows[ $key ] ) ) ? $submitted_rows[ $key ] : array();
			$lines = array();

			foreach ( $rows as $row_index => $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$cells = array();

				foreach ( $field['cols'] as $col ) {
					// Pair sub-rows post separately; fold them into this cell.
					if ( ! empty( $field['pairs'] ) && in_array( $col, (array) $field['pairs'], true ) ) {
						$pairs = isset( $submitted_pairs[ $key ][ $row_index ][ $col ] ) ? (array) $submitted_pairs[ $key ][ $row_index ][ $col ] : array();
						$built = array();

						foreach ( $pairs as $pair ) {
							if ( ! is_array( $pair ) || empty( $pair['url'] ) ) {
								continue;
							}

							$pair_label = sanitize_text_field( isset( $pair['label'] ) ? $pair['label'] : '' );
							$pair_url   = esc_url_raw( trim( $pair['url'] ) );

							if ( '' === $pair_label || '' === $pair_url ) {
								continue;
							}

							$built[] = str_replace( array( ';', '=', '|' ), '', $pair_label ) . '=' . $pair_url;
						}

						$cells[] = implode( '; ', $built );
						continue;
					}

					$cell = isset( $row[ $col ] ) ? sanitize_text_field( $row[ $col ] ) : '';
					// Pipes are the row separator, so they can't survive inside a cell.
					$cells[] = str_replace( '|', '/', $cell );
				}

				if ( '' === trim( implode( '', $cells ) ) ) {
					continue;
				}

				$lines[] = implode( ' | ', $cells );
			}

			update_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), implode( "\n", $lines ) );
			delete_post_meta( $post_id, hw_hub_legacy_meta_key( $key, $post_id ) );
			continue;
		}

		if ( 'select' === $field['type'] ) {
			$value = isset( $field['options'][ $raw ] ) ? $raw : (string) array_key_first( $field['options'] );
		} elseif ( 'check' === $field['type'] ) {
			$value = $raw ? '1' : '';
		} elseif ( 'media' === $field['type'] ) {
			$value = $raw ? (string) absint( $raw ) : '';
		} elseif ( 'text' === $field['type'] ) {
			$value = sanitize_text_field( $raw );
		} else {
			// Textareas and lists keep their line breaks; tags are stripped.
			$value = sanitize_textarea_field( $raw );
		}

		update_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), $value );
		delete_post_meta( $post_id, hw_hub_legacy_meta_key( $key, $post_id ) );
	}
}
add_action( 'save_post_page', 'hw_hub_save_meta_box' );

/**
 * Loads the panel's styles, the media picker and the tab switching.
 *
 * @param string $hook Current admin screen.
 */
function hw_hub_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_style( 'hw-hub-admin', HW_HUB_URL . 'assets/admin.css', array(), HW_HUB_VERSION );
	wp_enqueue_script( 'hw-hub-admin', HW_HUB_URL . 'assets/admin.js', array( 'jquery' ), HW_HUB_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'hw_hub_admin_assets' );
