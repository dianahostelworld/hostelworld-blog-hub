<?php
/**
 * Translating hub fields with DeepL.
 *
 * Polylang Pro's DeepL module translates the post and the fields it knows
 * about; it does not pick these up, whether they are declared in
 * wpml-config.xml or registered as public meta. Rather than keep guessing at
 * its internals, this calls DeepL directly for this plugin's fields only.
 *
 * It reads the source language page's fields, sends the text off, and writes
 * the result into the page being edited. Everything else about the translation
 * — the post, its title, its content — is still Polylang's job.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields that must never be translated: IDs, slugs, counts, currency codes and
 * anything else that is matched rather than read. A translated tag matches no
 * articles, and a translated country name breaks the hostel lookup.
 *
 * @return array
 */
function hw_hub_untranslatable_fields() {
	return array(
		'hero_image', 'rail_image', 'hero_overlay', 'hub_term', 'parent_hub', 'scope_rows',
		'taxonomy', 'show_read_time', 'cities_live', 'hostels_source', 'hostels_location_type',
		'hostels_location_id', 'hostels_currency', 'hostels_count', 'hostels_cache',
		'events_source', 'events_country', 'events_continent', 'events_count', 'events_cache',
		'newsletter_shortcode', 'city_slug', 'country_slug', 'currency', 'areas_zoom',
		'areas_map', 'areas_live', 'hostels_pins', 'live_stats', 'layout',
		'good_for',
	);
}

/**
 * The DeepL API key, from the settings page or a constant in wp-config.php.
 *
 * @return string
 */
function hw_hub_deepl_key() {
	if ( defined( 'HW_HUB_DEEPL_KEY' ) && HW_HUB_DEEPL_KEY ) {
		return (string) HW_HUB_DEEPL_KEY;
	}

	return (string) get_option( 'hw_hub_deepl_key', '' );
}

/**
 * DeepL's endpoint. Free keys end in ":fx" and use a different host.
 *
 * @return string
 */
function hw_hub_deepl_endpoint() {
	$key = hw_hub_deepl_key();

	return ( ':fx' === substr( $key, -3 ) )
		? 'https://api-free.deepl.com/v2/translate'
		: 'https://api.deepl.com/v2/translate';
}

/**
 * Turns a Polylang language slug into a DeepL target code.
 *
 * @param string $slug Language slug.
 * @return string
 */
function hw_hub_deepl_lang( $slug ) {
	$slug = strtolower( str_replace( '_', '-', (string) $slug ) );

	$map = array(
		'en'    => 'EN-GB',
		'en-gb' => 'EN-GB',
		'en-us' => 'EN-US',
		'pt'    => 'PT-PT',
		'pt-pt' => 'PT-PT',
		'pt-br' => 'PT-BR',
		'zh'    => 'ZH',
		'nb'    => 'NB',
		'nn'    => 'NB',
		'no'    => 'NB',
	);

	if ( isset( $map[ $slug ] ) ) {
		return $map[ $slug ];
	}

	return strtoupper( substr( $slug, 0, 2 ) );
}

/**
 * Sends a batch of strings to DeepL.
 *
 * @param array  $texts  Strings to translate.
 * @param string $target Target language code.
 * @param string $source Source language code, or ''.
 * @return array|WP_Error Translations in the same order.
 */
function hw_hub_deepl_translate( $texts, $target, $source = '' ) {
	$key = hw_hub_deepl_key();

	if ( ! $key ) {
		return new WP_Error( 'hw_hub_no_key', __( 'No DeepL API key is set.', 'hostelworld' ) );
	}

	$texts = array_values( $texts );
	$out   = array();

	// DeepL takes up to 50 texts per request.
	foreach ( array_chunk( $texts, 50 ) as $chunk ) {
		$body = array(
			'text'                => $chunk,
			'target_lang'         => $target,
			'preserve_formatting' => '1',
		);

		if ( $source ) {
			$body['source_lang'] = $source;
		}

		$response = wp_remote_post(
			hw_hub_deepl_endpoint(),
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'DeepL-Auth-Key ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			return new WP_Error(
				'hw_hub_deepl_http',
				sprintf(
					/* translators: 1: HTTP status, 2: response body */
					__( 'DeepL returned %1$s: %2$s', 'hostelworld' ),
					$code,
					wp_strip_all_tags( wp_remote_retrieve_body( $response ) )
				)
			);
		}

		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $decoded['translations'] ) ) {
			return new WP_Error( 'hw_hub_deepl_empty', __( 'DeepL returned nothing.', 'hostelworld' ) );
		}

		foreach ( $decoded['translations'] as $translation ) {
			$out[] = isset( $translation['text'] ) ? $translation['text'] : '';
		}
	}

	return $out;
}

/**
 * Columns that are references rather than prose, per field.
 *
 * It has to be per field, because the same column name means different things
 * in different places: "doing" is a nightly figure in the budget calculator and
 * a list of activities in the trip types; "name" is a lookup key on a city and
 * a display label on a trip type.
 *
 * A city's name is matched against Hostelworld's own data and used to build its
 * URL, so a translated "Seville" stops finding Sevilla's hostels and points at a
 * page that does not exist. Those stay in English until someone changes the
 * field and what it points at together.
 *
 * @param string $key Field key.
 * @return array
 */
function hw_hub_reference_columns( $key ) {
	$map = array(
		'featured'     => array( 'name', 'page', 'image' ),
		'regions'      => array( 'countries' ),
		// Country hub cities, and a state hub's main cities: the name is the
		// page address or the Hostelworld lookup, so it stays as written.
		'cities'       => array( 'name', 'guide', 'image', 'hw_city' ),
		'states'       => array( 'name', 'guide', 'image', 'review_city' ),
		'areas'        => array( 'lat', 'lng', 'api', 'link' ),
		'trip_types'   => array( 'colour', 'icon', 'tag' ),
		'trips'        => array( 'icon', 'href' ),
		'article_rows' => array( 'tag', 'link' ),
		'hostels'      => array( 'name', 'image', 'href' ),
		'events'       => array( 'image', 'href' ),
		'why_cards'    => array( 'icon' ),
		'seasons'      => array( 'tone' ),
		'season_rows'  => array( 'high', 'crowd' ),
		'budget_rows'  => array( 'bed', 'food', 'transport', 'doing' ),
		'cost_rows'    => array( 'dorm', 'daily' ),
		'stats'        => array( 'value' ),
		'money_reads'  => array( 'href' ),
		'events_pills' => array( 'href' ),
		'hero_cta'     => array( 'href' ),
		'cta_buttons'  => array( 'href' ),
		'breadcrumb'   => array( 'href' ),
	);

	if ( isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}

	// Anything not listed: the usual suspects only.
	return array( 'icon', 'colour', 'tone', 'href', 'link', 'url', 'page', 'guide', 'image', 'tag' );
}

/**
 * Whether a piece of text is prose worth sending to DeepL.
 *
 * @param string $text Value.
 * @return bool
 */
function hw_hub_is_translatable_text( $text ) {
	$text = trim( (string) $text );

	if ( '' === $text || is_numeric( $text ) ) {
		return false;
	}

	// URLs and the plugin's own link references.
	if ( preg_match( '#^(https?://|page:|term:|tags:|\#)#i', $text ) ) {
		return false;
	}

	// Paths: no spaces, contains a slash. europe/spain/barcelona.
	if ( false !== strpos( $text, '/' ) && false === strpos( $text, ' ' ) ) {
		return false;
	}

	// Slugs.
	if ( preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*$/', $text ) ) {
		return false;
	}

	// Shortcodes.
	if ( preg_match( '/^\[[^\]]+\]$/', $text ) ) {
		return false;
	}

	return true;
}

/**
 * Collects the prose out of a field, with a template for putting it back.
 *
 * Decisions are made per column rather than by guessing from the text, because
 * the schema already says what each column is for.
 *
 * @param string $value Raw field value.
 * @param array  $field Field definition.
 * @param string $key   Field key.
 * @return array texts and structure.
 */
function hw_hub_split_field( $value, $field, $key = '' ) {
	$type = isset( $field['type'] ) ? $field['type'] : 'text';

	if ( ! in_array( $type, array( 'list', 'rows' ), true ) ) {
		return array(
			'texts'     => hw_hub_is_translatable_text( $value ) ? array( $value ) : array(),
			'structure' => 'plain',
		);
	}

	$cols      = isset( $field['cols'] ) ? array_values( $field['cols'] ) : array();
	$reference = hw_hub_reference_columns( $key );
	$texts     = array();
	$rows      = array();

	foreach ( preg_split( '/\R/', (string) $value ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}

		$cells = array_map( 'trim', explode( '|', $line ) );
		$row   = array();

		foreach ( $cells as $index => $cell ) {
			$col = isset( $cols[ $index ] ) ? $cols[ $index ] : '';

			// Reference columns go through untouched.
			if ( in_array( $col, $reference, true ) ) {
				$row[] = array( 'keep', $cell );
				continue;
			}

			// "3 days=https://…; 24 hours=https://…" — translate the labels only.
			if ( 'itineraries' === $col ) {
				$pairs = array();

				foreach ( explode( ';', $cell ) as $pair ) {
					$parts = explode( '=', $pair, 2 );

					if ( count( $parts ) < 2 ) {
						$pairs[] = array( 'keep', trim( $pair ) );
						continue;
					}

					$label = trim( $parts[0] );
					$pairs[] = array( 'pair', count( $texts ), trim( $parts[1] ) );
					$texts[] = $label;
				}

				$row[] = array( 'pairs', $pairs );
				continue;
			}

			// Semicolon lists are several sentences in one cell.
			if ( false !== strpos( $cell, ';' ) ) {
				$slots = array();

				foreach ( array_map( 'trim', explode( ';', $cell ) ) as $part ) {
					if ( hw_hub_is_translatable_text( $part ) ) {
						$slots[] = count( $texts );
						$texts[] = $part;
					} else {
						$slots[] = $part;
					}
				}

				$row[] = array( 'semicolons', $slots );
				continue;
			}

			if ( hw_hub_is_translatable_text( $cell ) ) {
				$row[]   = array( 'text', count( $texts ) );
				$texts[] = $cell;
			} else {
				$row[] = array( 'keep', $cell );
			}
		}

		$rows[] = $row;
	}

	return array(
		'texts'     => $texts,
		'structure' => $rows,
	);
}

/**
 * Rebuilds a field's value from the translated strings.
 *
 * @param array  $split        Result of hw_hub_split_field().
 * @param array  $translations Translated strings, in order.
 * @param array  $field        Field definition.
 * @param string $original     Original value, used when nothing was sent.
 * @return string
 */
function hw_hub_rebuild_field( $split, $translations, $field, $original ) {
	if ( 'plain' === $split['structure'] ) {
		return isset( $translations[0] ) ? $translations[0] : $original;
	}

	$lines = array();

	foreach ( $split['structure'] as $row ) {
		$cells = array();

		foreach ( $row as $cell ) {
			$kind    = $cell[0];
			$payload = $cell[1];

			if ( 'keep' === $kind ) {
				$cells[] = $payload;
				continue;
			}

			if ( 'text' === $kind ) {
				$cells[] = isset( $translations[ $payload ] ) ? $translations[ $payload ] : '';
				continue;
			}

			if ( 'pairs' === $kind ) {
				$parts = array();

				foreach ( $payload as $pair ) {
					if ( 'keep' === $pair[0] ) {
						if ( '' !== $pair[1] ) {
							$parts[] = $pair[1];
						}
						continue;
					}

					$label   = isset( $translations[ $pair[1] ] ) ? $translations[ $pair[1] ] : '';
					$parts[] = $label . '=' . $pair[2];
				}

				$cells[] = implode( '; ', $parts );
				continue;
			}

			$parts = array();

			foreach ( $payload as $slot ) {
				$parts[] = is_int( $slot )
					? ( isset( $translations[ $slot ] ) ? $translations[ $slot ] : '' )
					: $slot;
			}

			$cells[] = implode( '; ', $parts );
		}

		$lines[] = implode( ' | ', $cells );
	}

	return implode( "\n", $lines );
}

/**
 * Translates one page's hub fields from its source translation.
 *
 * @param int $post_id Page being translated into.
 * @return array|WP_Error Number of fields written.
 */
function hw_hub_translate_page( $post_id ) {
	if ( ! function_exists( 'pll_get_post_language' ) || ! function_exists( 'pll_get_post' ) ) {
		return new WP_Error( 'hw_hub_no_polylang', __( 'Polylang is not active, so there is no source language to translate from.', 'hostelworld' ) );
	}

	$target_lang = pll_get_post_language( $post_id, 'slug' );
	$source_id   = 0;

	foreach ( pll_languages_list() as $lang ) {
		if ( $lang === $target_lang ) {
			continue;
		}

		$candidate = pll_get_post( $post_id, $lang );

		if ( $candidate && $candidate !== $post_id && get_post_meta( $candidate, hw_hub_meta_key( 'hero_h1', $candidate ), true ) ) {
			$source_id   = $candidate;
			$source_lang = $lang;
			break;
		}
	}

	if ( ! $source_id ) {
		return new WP_Error( 'hw_hub_no_source', __( 'No other translation of this page has any hub content to translate from.', 'hostelworld' ) );
	}

	$fields   = hw_hub_schema_fields( hw_hub_template_for_post( $post_id ) );
	$skip     = hw_hub_untranslatable_fields();
	$splits   = array();
	$all      = array();

	foreach ( $fields as $key => $field ) {
		if ( in_array( $key, $skip, true ) ) {
			// Carried across as-is: IDs, slugs and settings.
			update_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), hw_hub_raw_value( $key, $source_id ) );
			continue;
		}

		$value = hw_hub_raw_value( $key, $source_id );

		if ( '' === trim( (string) $value ) ) {
			continue;
		}

		$split = hw_hub_split_field( $value, $field, $key );

		if ( ! $split['texts'] ) {
			update_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), $value );
			continue;
		}

		$splits[ $key ] = array(
			'split'  => $split,
			'offset' => count( $all ),
			'value'  => $value,
			'field'  => $field,
		);

		$all = array_merge( $all, $split['texts'] );
	}

	if ( ! $all ) {
		return new WP_Error( 'hw_hub_nothing', __( 'There was nothing to translate.', 'hostelworld' ) );
	}

	$translated = hw_hub_deepl_translate(
		$all,
		hw_hub_deepl_lang( $target_lang ),
		hw_hub_deepl_lang( isset( $source_lang ) ? $source_lang : '' )
	);

	if ( is_wp_error( $translated ) ) {
		return $translated;
	}

	$written = 0;

	foreach ( $splits as $key => $data ) {
		$slice = array_slice( $translated, $data['offset'], count( $data['split']['texts'] ) );
		$value = hw_hub_rebuild_field( $data['split'], $slice, $data['field'], $data['value'] );

		update_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), $value );
		delete_post_meta( $post_id, hw_hub_legacy_meta_key( $key, $post_id ) );
		++$written;
	}

	return array(
		'fields'  => $written,
		'strings' => count( $all ),
		'source'  => $source_id,
	);
}

/**
 * Handles the button on the edit screen.
 */
function hw_hub_handle_translate() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You cannot edit this page.', 'hostelworld' ) );
	}

	check_admin_referer( 'hw_hub_translate_' . $post_id );

	$result = hw_hub_translate_page( $post_id );

	$args = is_wp_error( $result )
		? array( 'hw_hub_error' => rawurlencode( $result->get_error_message() ) )
		: array( 'hw_hub_done' => (int) $result['fields'] );

	wp_safe_redirect( add_query_arg( $args, get_edit_post_link( $post_id, 'url' ) ) );
	exit;
}
add_action( 'admin_post_hw_hub_translate', 'hw_hub_handle_translate' );

/**
 * The settings page holding the DeepL key.
 */
function hw_hub_settings_page() {
	add_options_page(
		__( 'Blog hubs', 'hostelworld' ),
		__( 'Blog hubs', 'hostelworld' ),
		'manage_options',
		'hw-hub-settings',
		'hw_hub_render_settings'
	);
}
add_action( 'admin_menu', 'hw_hub_settings_page' );

/**
 * Registers the key setting.
 */
function hw_hub_register_settings() {
	register_setting(
		'hw_hub_settings',
		'hw_hub_deepl_key',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);
}
add_action( 'admin_init', 'hw_hub_register_settings' );

/**
 * Renders the settings page.
 */
function hw_hub_render_settings() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Blog hubs', 'hostelworld' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'hw_hub_settings' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="hw_hub_deepl_key"><?php esc_html_e( 'DeepL API key', 'hostelworld' ); ?></label></th>
					<td>
						<?php if ( defined( 'HW_HUB_DEEPL_KEY' ) && HW_HUB_DEEPL_KEY ) : ?>
							<p><?php esc_html_e( 'Set in wp-config.php, which takes precedence over this field.', 'hostelworld' ); ?></p>
						<?php else : ?>
							<input type="password" class="regular-text" id="hw_hub_deepl_key" name="hw_hub_deepl_key" value="<?php echo esc_attr( get_option( 'hw_hub_deepl_key', '' ) ); ?>" autocomplete="off">
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'Used only to translate hub fields, from the button on a hub page. Free keys end in :fx and are sent to DeepL\'s free endpoint automatically. For a shared site, HW_HUB_DEEPL_KEY in wp-config.php keeps the key out of the database.', 'hostelworld' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php do_settings_sections( 'hw-hub-settings' ); ?>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
