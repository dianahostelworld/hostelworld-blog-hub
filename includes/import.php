<?php
/**
 * Spreadsheet import.
 *
 * Reads a workbook in the shape the hub content templates use — a Fields sheet
 * of key/value pairs plus one sheet per repeating field — and writes it into a
 * hub page. Works for continent, country and city hubs: the page's own
 * template decides which schema the file is checked against.
 *
 * XLSX files are zipped XML, so this reads them with ZipArchive and SimpleXML
 * rather than pulling in a spreadsheet library.
 *
 * @package Hostelworld_Blog_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turn a column reference like "BC12" into a zero-based column index.
 *
 * @param string $ref Cell reference.
 * @return int
 */
function hw_hub_xlsx_col_index( $ref ) {
	preg_match( '/^([A-Z]+)/', strtoupper( (string) $ref ), $m );

	if ( empty( $m[1] ) ) {
		return 0;
	}

	$index = 0;

	foreach ( str_split( $m[1] ) as $letter ) {
		$index = $index * 26 + ( ord( $letter ) - 64 );
	}

	return $index - 1;
}

/**
 * Read an xlsx file into sheets of rows.
 *
 * @param string $path Absolute path to the file.
 * @return array|WP_Error Sheet name => array of rows, each row an array of strings.
 */
function hw_hub_xlsx_read( $path ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'hw_hub_no_zip', 'This server does not have the ZipArchive extension, which is needed to read spreadsheets. Ask your host to enable it.' );
	}

	$zip = new ZipArchive();

	if ( true !== $zip->open( $path ) ) {
		return new WP_Error( 'hw_hub_bad_zip', 'That file could not be opened. Make sure it is an .xlsx file saved from Excel, Numbers or Google Sheets.' );
	}

	$strings  = array();
	$shared   = $zip->getFromName( 'xl/sharedStrings.xml' );

	if ( $shared ) {
		$xml = simplexml_load_string( $shared );

		if ( $xml ) {
			foreach ( $xml->si as $si ) {
				// Text can be one <t> or several inside <r> runs.
				$text = '';

				if ( isset( $si->t ) ) {
					$text = (string) $si->t;
				} elseif ( isset( $si->r ) ) {
					foreach ( $si->r as $run ) {
						$text .= (string) $run->t;
					}
				}

				$strings[] = $text;
			}
		}
	}

	// Sheet names live in workbook.xml, their files in the rels map.
	$names = array();
	$book  = $zip->getFromName( 'xl/workbook.xml' );
	$rels  = $zip->getFromName( 'xl/_rels/workbook.xml.rels' );

	if ( $book && $rels ) {
		$book_xml = simplexml_load_string( $book );
		$rels_xml = simplexml_load_string( $rels );
		$targets  = array();

		if ( $rels_xml ) {
			foreach ( $rels_xml->Relationship as $rel ) {
				$targets[ (string) $rel['Id'] ] = ltrim( (string) $rel['Target'], '/' );
			}
		}

		if ( $book_xml ) {
			foreach ( $book_xml->sheets->sheet as $sheet ) {
				$attrs  = $sheet->attributes( 'http://schemas.openxmlformats.org/officeDocument/2006/relationships' );
				$rel_id = isset( $attrs->id ) ? (string) $attrs->id : '';
				$target = isset( $targets[ $rel_id ] ) ? $targets[ $rel_id ] : '';

				if ( $target ) {
					$names[ (string) $sheet['name'] ] = 0 === strpos( $target, 'xl/' ) ? $target : 'xl/' . $target;
				}
			}
		}
	}

	$sheets = array();

	foreach ( $names as $name => $file ) {
		$data = $zip->getFromName( $file );

		if ( ! $data ) {
			continue;
		}

		$xml = simplexml_load_string( $data );

		if ( ! $xml ) {
			continue;
		}

		$rows = array();

		foreach ( $xml->sheetData->row as $row ) {
			$cells = array();

			foreach ( $row->c as $c ) {
				$index = hw_hub_xlsx_col_index( (string) $c['r'] );
				$type  = (string) $c['t'];
				$value = '';

				if ( 'inlineStr' === $type ) {
					$value = isset( $c->is->t ) ? (string) $c->is->t : '';
				} elseif ( 's' === $type ) {
					$key   = (int) $c->v;
					$value = isset( $strings[ $key ] ) ? $strings[ $key ] : '';
				} else {
					$value = isset( $c->v ) ? (string) $c->v : '';
				}

				$cells[ $index ] = $value;
			}

			if ( $cells ) {
				$max = max( array_keys( $cells ) );

				for ( $i = 0; $i <= $max; $i++ ) {
					if ( ! isset( $cells[ $i ] ) ) {
						$cells[ $i ] = '';
					}
				}

				ksort( $cells );
			}

			$rows[] = array_values( $cells );
		}

		$sheets[ $name ] = $rows;
	}

	$zip->close();

	if ( ! $sheets ) {
		return new WP_Error( 'hw_hub_empty_file', 'No sheets could be read from that file.' );
	}

	return $sheets;
}

/**
 * Pull the column key out of a header like "One line on it [blurb]".
 *
 * @param string $header Header cell.
 * @return string
 */
function hw_hub_import_col_key( $header ) {
	if ( preg_match( '/\[([a-z0-9_]+)\]\s*$/i', (string) $header, $m ) ) {
		return strtolower( $m[1] );
	}

	return '';
}

/**
 * Read a workbook into field values for one hub page.
 *
 * @param array $sheets   Sheets from hw_hub_xlsx_read().
 * @param string $template Hub template: continent, country or city.
 * @return array {
 *     @type array $values   Field key => value, ready to save.
 *     @type array $skipped  Keys in the file that the template does not have.
 *     @type array $empty    Known keys left blank in the file.
 * }
 */
function hw_hub_import_parse( $sheets, $template ) {
	$fields  = hw_hub_schema_fields( $template );
	$values  = array();
	$skipped = array();
	$empty   = array();

	// Plain fields come from the Fields sheet: Section | Field | Key | Value.
	if ( ! empty( $sheets['Fields'] ) ) {
		foreach ( $sheets['Fields'] as $row ) {
			$key = isset( $row[2] ) ? trim( (string) $row[2] ) : '';

			if ( '' === $key || 'Key' === $key ) {
				continue;
			}

			if ( ! isset( $fields[ $key ] ) ) {
				$skipped[] = $key;
				continue;
			}

			$value = isset( $row[3] ) ? trim( (string) $row[3] ) : '';

			if ( '' === $value ) {
				$empty[] = $key;
				continue;
			}

			$values[ $key ] = $value;
		}
	}

	// Repeating fields get a sheet each, named after the field key.
	foreach ( $fields as $key => $field ) {
		if ( empty( $field['cols'] ) || ! isset( $sheets[ $key ] ) ) {
			continue;
		}

		$rows    = $sheets[ $key ];
		$header  = array_shift( $rows );
		$map     = array();
		$columns = $field['cols'];

		// Prefer the [key] tags in the header; fall back to schema order.
		if ( is_array( $header ) ) {
			foreach ( $header as $index => $label ) {
				$col = hw_hub_import_col_key( $label );

				if ( $col && in_array( $col, $columns, true ) ) {
					$map[ $index ] = $col;
				}
			}
		}

		if ( ! $map ) {
			foreach ( $columns as $index => $col ) {
				$map[ $index ] = $col;
			}
		}

		$lines = array();

		foreach ( $rows as $row ) {
			$ordered = array();

			foreach ( $columns as $col ) {
				$index          = array_search( $col, $map, true );
				$ordered[ $col ] = ( false !== $index && isset( $row[ $index ] ) ) ? trim( (string) $row[ $index ] ) : '';
			}

			// Skip rows that are entirely blank.
			if ( '' === trim( implode( '', $ordered ) ) ) {
				continue;
			}

			$lines[] = implode( ' | ', $ordered );
		}

		if ( $lines ) {
			$values[ $key ] = implode( "\n", $lines );
		} else {
			$empty[] = $key;
		}
	}

	return array(
		'values'  => $values,
		'skipped' => array_values( array_unique( $skipped ) ),
		'empty'   => array_values( array_unique( $empty ) ),
	);
}

/**
 * Carry existing row pictures through an import.
 *
 * Rows are stored as pipe-delimited lines and one column can hold a Media
 * Library ID. Nobody filling in a spreadsheet knows those IDs, so the column
 * comes back empty and a straight overwrite wipes every picture on the page.
 * This puts the stored value back wherever the imported cell is blank.
 *
 * Rows are matched on their first column — the name — so a picture follows its
 * row even when rows are reordered, with the row position as a fallback for
 * anything renamed.
 *
 * @param string $incoming Imported rows, one per line.
 * @param string $existing Rows currently stored on the page.
 * @param array  $cols     Column keys for this field, in order.
 * @return string Rows with pictures preserved.
 */
function hw_hub_import_keep_row_images( $incoming, $existing, $cols ) {
	$image_at = array_search( 'image', $cols, true );

	if ( false === $image_at || '' === trim( $existing ) ) {
		return $incoming;
	}

	$old_rows = hw_hub_import_split_rows( $existing );
	$new_rows = hw_hub_import_split_rows( $incoming );

	// Pictures by row name, so they survive reordering.
	$by_name = array();

	foreach ( $old_rows as $row ) {
		$name  = isset( $row[0] ) ? strtolower( $row[0] ) : '';
		$image = isset( $row[ $image_at ] ) ? $row[ $image_at ] : '';

		if ( '' !== $name && '' !== $image ) {
			$by_name[ $name ] = $image;
		}
	}

	// Names still present after the import. A stored row whose name survived has
	// already handed its picture over by name, so its slot must not be reused as
	// a positional fallback — otherwise a brand new row inherits someone else's
	// picture.
	$new_names = array();

	foreach ( $new_rows as $row ) {
		if ( isset( $row[0] ) && '' !== trim( $row[0] ) ) {
			$new_names[ strtolower( trim( $row[0] ) ) ] = true;
		}
	}

	$out = array();

	foreach ( $new_rows as $i => $row ) {
		$row = array_pad( $row, count( $cols ), '' );

		if ( '' === trim( $row[ $image_at ] ) ) {
			$name = isset( $row[0] ) ? strtolower( $row[0] ) : '';

			if ( '' !== $name && isset( $by_name[ $name ] ) ) {
				// Same row, wherever it now sits in the list.
				$row[ $image_at ] = $by_name[ $name ];
			} elseif ( isset( $old_rows[ $i ] ) ) {
				// Possibly the same row renamed in place: only inherit when the
				// old name has gone from the list entirely.
				$old_name = isset( $old_rows[ $i ][0] ) ? strtolower( trim( $old_rows[ $i ][0] ) ) : '';

				if ( '' !== $old_name && ! isset( $new_names[ $old_name ] ) && isset( $old_rows[ $i ][ $image_at ] ) ) {
					$row[ $image_at ] = $old_rows[ $i ][ $image_at ];
				}
			}
		}

		$out[] = implode( ' | ', $row );
	}

	return implode( "\n", $out );
}

/**
 * Split stored or imported rows into arrays of cells.
 *
 * @param string $text Rows, one per line.
 * @return array
 */
function hw_hub_import_split_rows( $text ) {
	$rows = array();

	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}

		$rows[] = array_map( 'trim', explode( '|', $line ) );
	}

	return $rows;
}

/**
 * Save parsed values onto a page, using the same sanitising as the panel.
 *
 * @param int   $post_id Page ID.
 * @param array $values  Field key => value.
 * @return int Number of fields written.
 */
function hw_hub_import_apply( $post_id, $values ) {
	$template = hw_hub_template_for_post( $post_id );
	$fields   = hw_hub_schema_fields( $template );
	$written  = 0;

	foreach ( $values as $key => $value ) {
		if ( ! isset( $fields[ $key ] ) ) {
			continue;
		}

		$field = $fields[ $key ];
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';

		// Each field type is stored the way the panel stores it, so an imported
		// value behaves exactly as if it had been typed in. Checkboxes in
		// particular keep '1' — anything else leaves them unticked.
		if ( 'check' === $type ) {
			$truthy = array( '1', 'yes', 'y', 'true', 'on', 'x', 'tick', 'ticked', 'checked' );
			$clean  = in_array( strtolower( trim( $value ) ), $truthy, true ) ? '1' : '';
		} elseif ( 'select' === $type ) {
			$options = isset( $field['options'] ) ? $field['options'] : array();
			$raw     = trim( $value );
			$clean   = '';

			if ( isset( $options[ $raw ] ) ) {
				$clean = $raw;
			} else {
				// Allow the label as well as the stored key, case-insensitively.
				foreach ( $options as $option_key => $label ) {
					if ( 0 === strcasecmp( $raw, (string) $option_key ) || 0 === strcasecmp( $raw, (string) $label ) ) {
						$clean = $option_key;
						break;
					}
				}
			}

			// An unrecognised option is left alone rather than reset.
			if ( '' === $clean ) {
				continue;
			}
		} elseif ( 'media' === $type ) {
			// A blank image cell means "leave the picture alone", not "remove it".
			// Spreadsheets carry the ID as a number at best, and the person filling
			// one in has no way to look one up, so an empty cell is never a request
			// to clear an image that was chosen in the Media Library.
			if ( '' === trim( (string) $value ) ) {
				continue;
			}

			$clean = (string) absint( $value );
		} elseif ( ! empty( $field['cols'] ) ) {
			$clean = hw_hub_import_keep_row_images(
				sanitize_textarea_field( $value ),
				(string) get_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), true ),
				$field['cols']
			);
		} elseif ( 'textarea' === $type || 'list' === $type ) {
			$clean = sanitize_textarea_field( $value );
		} else {
			$clean = sanitize_text_field( $value );
		}

		update_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), $clean );
		$written++;
	}

	return $written;
}

/**
 * Handle the upload over ajax so the panel never nests a form inside the
 * post form — which would make WordPress validate this file field when
 * someone clicks Save draft.
 */
function hw_hub_ajax_import() {
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => 'You do not have permission to import content into this page.' ) );
	}

	check_ajax_referer( 'hw_hub_import_' . $post_id, 'nonce' );

	if ( empty( $_FILES['file']['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $_FILES['file']['error'] ) {
		wp_send_json_error( array( 'message' => 'No file was uploaded, or it was too large for this server.' ) );
	}

	$name = isset( $_FILES['file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ) : '';

	if ( 'xlsx' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
		wp_send_json_error( array( 'message' => 'That is not an .xlsx file. Export from Google Sheets or Numbers as Excel first.' ) );
	}

	$sheets = hw_hub_xlsx_read( $_FILES['file']['tmp_name'] );

	if ( is_wp_error( $sheets ) ) {
		wp_send_json_error( array( 'message' => $sheets->get_error_message() ) );
	}

	$template = hw_hub_template_for_post( $post_id );
	$parsed   = hw_hub_import_parse( $sheets, $template );

	if ( empty( $parsed['values'] ) ) {
		wp_send_json_error(
			array(
				'message' => 'Nothing in that file matched this page. Check it is the right hub type — this page uses the ' . $template . ' template.',
			)
		);
	}

	$written = hw_hub_import_apply( $post_id, $parsed['values'] );

	$message = sprintf( 'Imported %d field%s from %s.', $written, 1 === $written ? '' : 's', $name );

	if ( $parsed['skipped'] ) {
		$message .= ' Ignored keys this template does not have: ' . implode( ', ', array_slice( $parsed['skipped'], 0, 8 ) ) . ( count( $parsed['skipped'] ) > 8 ? '…' : '' ) . '.';
	}

	wp_send_json_success( array( 'message' => $message ) );
}
add_action( 'wp_ajax_hw_hub_import', 'hw_hub_ajax_import' );

/**
 * The upload form, shown at the top of the hub panel.
 *
 * @param WP_Post $post     Page being edited.
 * @param string  $template Hub template.
 */
function hw_hub_import_form( $post, $template ) {
	$labels = array(
		'continent' => 'continent hub',
		'country'   => 'country hub',
		'city'      => 'city hub',
		'country_states' => 'country hub (states)',
		'state'     => 'state hub',
	);
	$label  = isset( $labels[ $template ] ) ? $labels[ $template ] : 'hub';
	?>
	<div class="hw-hub-admin__import"
		data-post="<?php echo esc_attr( $post->ID ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'hw_hub_import_' . $post->ID ) ); ?>">
		<div>
			<strong><?php esc_html_e( 'Import this page\'s fields from a spreadsheet', 'hostelworld' ); ?></strong>
			<p>
				<?php
				printf(
					/* translators: %s: hub type, e.g. country hub */
					esc_html__( 'Optional. Export this page to a spreadsheet to edit offline, or import a filled-in %s content workbook to fill the fields below. Importing saves straight to the page, so the panel reloads afterwards.', 'hostelworld' ),
					esc_html( $label )
				);
				?>
			</p>
			<p class="hw-hub-admin__import-status" role="status"></p>
		</div>
		<div class="hw-hub-admin__import-controls">
			<input type="file" accept=".xlsx" class="hw-hub-admin__import-file" hidden />
			<a class="button" href="<?php echo esc_url( hw_hub_export_url( $post ) ); ?>"><?php esc_html_e( 'Export spreadsheet', 'hostelworld' ); ?></a>
			<button type="button" class="button hw-hub-admin__import-go"><?php esc_html_e( 'Import spreadsheet', 'hostelworld' ); ?></button>
		</div>
	</div>
	<?php
}
