<?php
/**
 * Spreadsheet export.
 *
 * Writes the current page's fields back out as the same workbook the import
 * expects: a Fields sheet of key/value pairs plus one sheet per repeating
 * field. Round-trips, so a page can be pulled down, edited in Excel and
 * pushed back up.
 *
 * XLSX is zipped XML, so this builds it with ZipArchive and string templates
 * rather than pulling in a spreadsheet library. Cells are written as inline
 * strings, which costs a little file size and saves a shared-string table.
 *
 * @package Hostelworld_Blog_Hub
 */

defined( 'ABSPATH' ) || exit;

/**
 * Escape a value for XML content.
 *
 * @param string $value Raw value.
 * @return string
 */
function hw_hub_xml( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8' );
}

/**
 * Turn a zero-based column index into a letter reference.
 *
 * @param int $index Column index.
 * @return string
 */
function hw_hub_col_letter( $index ) {
	$letters = '';

	for ( $i = $index + 1; $i > 0; $i = intdiv( $i - 1, 26 ) ) {
		$letters = chr( 65 + ( ( $i - 1 ) % 26 ) ) . $letters;
	}

	return $letters;
}

/**
 * Build one worksheet's XML from a grid of rows.
 *
 * @param array $rows      Array of arrays of cell values.
 * @param int   $bold_rows How many leading rows use the bold style.
 * @param array $widths    Optional column widths.
 * @return string
 */
function hw_hub_sheet_xml( $rows, $bold_rows = 1, $widths = array() ) {
	$cols = '';

	if ( $widths ) {
		$cols .= '<cols>';
		foreach ( $widths as $i => $w ) {
			$cols .= sprintf( '<col min="%1$d" max="%1$d" width="%2$s" customWidth="1"/>', $i + 1, esc_attr( $w ) );
		}
		$cols .= '</cols>';
	}

	$body = '';

	foreach ( $rows as $r => $row ) {
		$cells = '';

		foreach ( array_values( $row ) as $c => $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}

			$style  = ( $r < $bold_rows ) ? ' s="1"' : ' s="2"';
			$cells .= sprintf(
				'<c r="%s%d"%s t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
				hw_hub_col_letter( $c ),
				$r + 1,
				$style,
				hw_hub_xml( $value )
			);
		}

		$body .= sprintf( '<row r="%d">%s</row>', $r + 1, $cells );
	}

	return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
		. '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
		. $cols
		. '<sheetData>' . $body . '</sheetData>'
		. '</worksheet>';
}

/**
 * Assemble the whole workbook and return it as a string of bytes.
 *
 * @param array $sheets Ordered map of sheet name => array of rows.
 * @param array $widths Map of sheet name => column widths.
 * @return string|WP_Error
 */
function hw_hub_xlsx_write( $sheets, $widths = array() ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'hw_hub_no_zip', 'This server does not have the ZipArchive extension, which is needed to build spreadsheets.' );
	}

	$file = wp_tempnam( 'hw-hub-export' );
	$zip  = new ZipArchive();

	if ( true !== $zip->open( $file, ZipArchive::OVERWRITE ) ) {
		return new WP_Error( 'hw_hub_zip_failed', 'Could not create the spreadsheet file on the server.' );
	}

	$names    = array_keys( $sheets );
	$overrides = '';
	$book      = '';
	$rels      = '';
	$i         = 0;

	foreach ( $names as $name ) {
		$i++;
		$zip->addFromString(
			"xl/worksheets/sheet{$i}.xml",
			hw_hub_sheet_xml( $sheets[ $name ], 1, isset( $widths[ $name ] ) ? $widths[ $name ] : array() )
		);
		$overrides .= sprintf( '<Override PartName="/xl/worksheets/sheet%d.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>', $i );
		$book      .= sprintf( '<sheet name="%s" sheetId="%d" r:id="rId%d"/>', hw_hub_xml( $name ), $i, $i );
		$rels      .= sprintf( '<Relationship Id="rId%1$d" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet%1$d.xml"/>', $i );
	}

	$styles_id = $i + 1;

	$zip->addFromString(
		'[Content_Types].xml',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
		. '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
		. '<Default Extension="xml" ContentType="application/xml"/>'
		. '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
		. '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
		. $overrides
		. '</Types>'
	);

	$zip->addFromString(
		'_rels/.rels',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
		. '</Relationships>'
	);

	$zip->addFromString(
		'xl/workbook.xml',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
		. 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
		. '<sheets>' . $book . '</sheets></workbook>'
	);

	$zip->addFromString(
		'xl/_rels/workbook.xml.rels',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
		. $rels
		. sprintf( '<Relationship Id="rId%d" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>', $styles_id )
		. '</Relationships>'
	);

	// Three formats: default, bold header, wrapped body.
	$zip->addFromString(
		'xl/styles.xml',
		'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
		. '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
		. '<fonts count="2"><font><sz val="10"/><name val="Arial"/></font>'
		. '<font><b/><sz val="10"/><name val="Arial"/></font></fonts>'
		. '<fills count="3"><fill><patternFill patternType="none"/></fill>'
		. '<fill><patternFill patternType="gray125"/></fill>'
		. '<fill><patternFill patternType="solid"><fgColor rgb="FFE7EEF7"/><bgColor indexed="64"/></patternFill></fill></fills>'
		. '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
		. '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
		. '<cellXfs count="3">'
		. '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
		. '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="top" wrapText="1"/></xf>'
		. '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
		. '</cellXfs>'
		. '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
		. '</styleSheet>'
	);

	$zip->close();

	$bytes = file_get_contents( $file );
	wp_delete_file( $file );

	return $bytes;
}

/**
 * Turn one hub page into the sheet structure the import expects.
 *
 * @param int $post_id Page ID.
 * @return array {
 *     @type array $sheets Sheet name => rows.
 *     @type array $widths Sheet name => column widths.
 * }
 */
function hw_hub_export_sheets( $post_id ) {
	$template = hw_hub_template_for_post( $post_id );
	$schema   = hw_hub_schema( $template );

	$labels = array(
		'continent' => 'Continent hub',
		'country'   => 'Country hub',
		'city'      => 'City hub',
		'country_states' => 'Country hub (states)',
		'state'     => 'State hub',
	);
	$label = isset( $labels[ $template ] ) ? $labels[ $template ] : 'Hub';

	$sheets = array();
	$widths = array();

	$fields_rows = array( array( 'Section', 'Field', 'Key', 'Value', 'How to fill it' ) );
	$row_sheets  = array();

	foreach ( $schema as $section ) {
		foreach ( $section['fields'] as $key => $field ) {
			$value = get_post_meta( $post_id, hw_hub_meta_key( $key, $post_id ), true );

			if ( ! empty( $field['cols'] ) ) {
				$row_sheets[ $key ] = array( $field, $value );
				continue;
			}

			$help = isset( $field['help'] ) ? wp_strip_all_tags( $field['help'] ) : '';

			if ( isset( $field['type'] ) && 'select' === $field['type'] && ! empty( $field['options'] ) ) {
				$help = 'One of: ' . implode( ', ', array_keys( $field['options'] ) ) . '. ' . $help;
			}

			if ( isset( $field['type'] ) && 'check' === $field['type'] ) {
				$value = $value ? 'yes' : 'no';
				$help  = 'yes or no. ' . $help;
			}

			if ( isset( $field['type'] ) && 'checks' === $field['type'] && ! empty( $field['options'] ) ) {
				$parsed = hw_hub_parse_checks( $value, $field['options'] );
				$value  = implode( ', ', hw_hub_check_labels( $parsed['values'], $field['options'] ) );
				$help   = 'Any of, separated by commas: ' . implode( ', ', $field['options'] ) . '. ' . $help;
			}

			$fields_rows[] = array(
				$section['label'],
				isset( $field['label'] ) ? $field['label'] : $key,
				$key,
				(string) $value,
				trim( $help ),
			);
		}
	}

	// Read me first, so the workbook opens on it.
	$intro = array(
		array( $label . ' — exported content' ),
		array( '' ),
		array( 'Exported from ' . get_the_title( $post_id ) . ' on ' . date_i18n( 'j F Y, H:i' ) . '.' ),
		array( 'Edit this file and import it back into the same page to apply the changes.' ),
		array( 'Fields sheet: only the Value column is read on import. Leave Key alone — it is what the importer matches on.' ),
		array( 'Other sheets: one per list. One item per row, under the headings.' ),
		array( 'A blank Value empties that field. A list sheet left completely blank is not touched.' ),
		array( '' ),
		array( 'List sheets in this file:' ),
	);

	foreach ( $row_sheets as $key => $pair ) {
		$intro[] = array( $key . ' — ' . ( isset( $pair[0]['label'] ) ? $pair[0]['label'] : $key ) );
	}

	$sheets['Read me'] = $intro;
	$widths['Read me'] = array( 110 );

	$sheets['Fields'] = $fields_rows;
	$widths['Fields'] = array( 18, 30, 22, 70, 58 );

	foreach ( $row_sheets as $key => $pair ) {
		list( $field, $value ) = $pair;

		$cols   = $field['cols'];
		$labels_map = isset( $field['labels'] ) ? $field['labels'] : array();
		$header = array();

		foreach ( $cols as $col ) {
			$col_label = isset( $labels_map[ $col ] ) ? $labels_map[ $col ] : ucfirst( str_replace( '_', ' ', $col ) );
			$header[]  = $col_label . ' [' . $col . ']';
		}

		$rows = array( $header );

		foreach ( preg_split( '/\r\n|\r|\n/', (string) $value ) as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line ) );
			$row   = array();

			foreach ( $cols as $i => $col ) {
				$row[] = isset( $parts[ $i ] ) ? $parts[ $i ] : '';
			}

			$rows[] = $row;
		}

		$sheets[ substr( $key, 0, 31 ) ] = $rows;
		$widths[ substr( $key, 0, 31 ) ] = array_fill( 0, count( $cols ), 32 );
	}

	return array( $sheets, $widths );
}

/**
 * Send the workbook to the browser as a download.
 */
function hw_hub_handle_export() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( 'You do not have permission to export this page.' );
	}

	check_admin_referer( 'hw_hub_export_' . $post_id );

	list( $sheets, $widths ) = hw_hub_export_sheets( $post_id );

	$bytes = hw_hub_xlsx_write( $sheets, $widths );

	if ( is_wp_error( $bytes ) ) {
		wp_die( esc_html( $bytes->get_error_message() ) );
	}

	$slug = sanitize_title( get_the_title( $post_id ) );
	$slug = $slug ? $slug : 'hub-' . $post_id;
	$name = 'hub-content-' . $slug . '-' . gmdate( 'Y-m-d' ) . '.xlsx';

	nocache_headers();
	header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	header( 'Content-Disposition: attachment; filename="' . $name . '"' );
	header( 'Content-Length: ' . strlen( $bytes ) );

	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary file.
	exit;
}
add_action( 'admin_post_hw_hub_export', 'hw_hub_handle_export' );

/**
 * The export link, shown beside the import button.
 *
 * @param WP_Post $post Page being edited.
 * @return string
 */
function hw_hub_export_url( $post ) {
	return wp_nonce_url(
		admin_url( 'admin-post.php?action=hw_hub_export&post=' . $post->ID ),
		'hw_hub_export_' . $post->ID
	);
}
