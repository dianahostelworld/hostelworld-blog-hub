<?php
/**
 * Hub trip types: set every city hub's "Good for" field from one spreadsheet.
 *
 * Pages > Hub trip types. Upload a sheet with a "City hub" column (the hub's
 * address, its page path or its page ID) and a "Good for" column (trip types
 * separated by commas). Every row is checked and shown before anything is
 * saved, and nothing else on the page is touched.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Header text accepted for each of the two columns, lowercased.
 *
 * @return array
 */
function hw_hub_gf_headers() {
	return array(
		'hub'      => array( 'city hub', 'city hub url', 'url', 'page', 'page url', 'link', 'address', 'page id', 'id' ),
		'good_for' => array( 'good for', 'good_for', 'trip types' ),
	);
}

/**
 * Where a user's parsed upload waits between preview and apply.
 *
 * @return string
 */
function hw_hub_gf_transient() {
	return 'hw_hub_gf_' . get_current_user_id();
}

/**
 * The screen's own address.
 *
 * @param array $args Extra query args.
 * @return string
 */
function hw_hub_gf_url( $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'hw-hub-good-for' ), $args ), admin_url( 'edit.php?post_type=page' ) );
}

/**
 * Sends the person back to the screen with a message.
 *
 * @param string $message What went wrong.
 */
function hw_hub_gf_fail( $message ) {
	set_transient( 'hw_hub_gf_err_' . get_current_user_id(), $message, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( hw_hub_gf_url( array( 'hw_gf_error' => 1 ) ) );
	exit;
}

/**
 * Adds the screen under Pages.
 */
function hw_hub_gf_menu() {
	add_submenu_page(
		'edit.php?post_type=page',
		__( 'Hub trip types', 'hostelworld' ),
		__( 'Hub trip types', 'hostelworld' ),
		'edit_pages',
		'hw-hub-good-for',
		'hw_hub_gf_render'
	);
}
add_action( 'admin_menu', 'hw_hub_gf_menu' );

/**
 * Every city hub, in the default language when Polylang is active.
 *
 * @return WP_Post[]
 */
function hw_hub_gf_city_hubs() {
	$args = array(
		'post_type'      => 'page',
		'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
		'posts_per_page' => -1,
		'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => HW_HUB_CITY_TEMPLATE_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	if ( function_exists( 'pll_default_language' ) ) {
		$args['lang'] = pll_default_language();
	}

	return get_posts( $args );
}

/**
 * Finds the page a "City hub" cell points at.
 *
 * Takes a full address (from any domain, so a sheet made from the live blog
 * also works on staging), a page path like asia/japan/osaka, or a page ID.
 *
 * @param string $ref Cell value.
 * @return int Page ID, or 0.
 */
function hw_hub_gf_find_page( $ref ) {
	$ref = trim( (string) $ref );

	if ( '' === $ref ) {
		return 0;
	}

	if ( ctype_digit( $ref ) ) {
		$post = get_post( (int) $ref );
		return ( $post && 'page' === $post->post_type ) ? $post->ID : 0;
	}

	$path = $ref;

	if ( preg_match( '#^https?://#i', $ref ) ) {
		$path      = (string) wp_parse_url( $ref, PHP_URL_PATH );
		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = trim( $home_path, '/' );

		// Strip this site's own base (e.g. /blog) from the front of the path.
		if ( '' !== $home_path && 0 === strpos( trim( $path, '/' ) . '/', $home_path . '/' ) ) {
			$path = substr( trim( $path, '/' ), strlen( $home_path ) );
		}
	}

	$path = trim( $path, '/' );

	if ( '' === $path ) {
		return 0;
	}

	// A site installed under a different base (staging at the root, say) won't
	// recognise the live /blog/ prefix, so drop leading segments until a page
	// matches.
	$segments = explode( '/', $path );

	while ( $segments ) {
		$page = get_page_by_path( implode( '/', $segments ), OBJECT, 'page' );

		if ( $page ) {
			return $page->ID;
		}

		array_shift( $segments );

		// Never fall back to a single segment: "osaka" alone could be any page.
		if ( count( $segments ) < 2 ) {
			break;
		}
	}

	if ( preg_match( '#^https?://#i', $ref ) ) {
		$id = url_to_postid( $ref );

		if ( $id && 'page' === get_post_type( $id ) ) {
			return $id;
		}
	}

	return 0;
}

/**
 * Reads an uploaded .xlsx or .csv into rows.
 *
 * @param string $path Temp file.
 * @param string $name Original file name.
 * @return array|WP_Error Sheet name => rows.
 */
function hw_hub_gf_read_file( $path, $name ) {
	$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

	if ( 'xlsx' === $ext ) {
		return hw_hub_xlsx_read( $path );
	}

	if ( 'csv' === $ext ) {
		$rows   = array();
		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! $handle ) {
			return new WP_Error( 'hw_hub_gf_csv', 'That file could not be opened.' );
		}

		while ( ( $row = fgetcsv( $handle, 0, ',', '"', '\\' ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
			$rows[] = array_map( 'strval', $row );
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		// Excel's CSV export starts with a byte-order mark.
		if ( isset( $rows[0][0] ) ) {
			$rows[0][0] = preg_replace( '/^\xEF\xBB\xBF/', '', $rows[0][0] );
		}

		return array( 'CSV' => $rows );
	}

	return new WP_Error( 'hw_hub_gf_type', 'Upload an .xlsx or .csv file.' );
}

/**
 * Finds the sheet and header row holding the two columns.
 *
 * @param array $sheets Sheet name => rows.
 * @return array|WP_Error { sheet, header_row, hub_col, gf_col }
 */
function hw_hub_gf_locate( $sheets ) {
	$accepted = hw_hub_gf_headers();

	foreach ( $sheets as $sheet => $rows ) {
		foreach ( array_slice( $rows, 0, 10, true ) as $r => $row ) {
			$hub = null;
			$gf  = null;

			foreach ( $row as $c => $cell ) {
				$cell = strtolower( trim( (string) $cell ) );

				if ( null === $hub && in_array( $cell, $accepted['hub'], true ) ) {
					$hub = $c;
				} elseif ( null === $gf && in_array( $cell, $accepted['good_for'], true ) ) {
					$gf = $c;
				}
			}

			if ( null !== $hub && null !== $gf ) {
				return array(
					'sheet'      => $sheet,
					'header_row' => $r,
					'hub_col'    => $hub,
					'gf_col'     => $gf,
				);
			}
		}
	}

	return new WP_Error( 'hw_hub_gf_headers', 'No sheet in that file has both a "City hub" column and a "Good for" column in its header row.' );
}

/**
 * Checks every row and works out what would change.
 *
 * @param array $sheets Sheet name => rows.
 * @return array|WP_Error Preview data.
 */
function hw_hub_gf_build_preview( $sheets ) {
	$where = hw_hub_gf_locate( $sheets );

	if ( is_wp_error( $where ) ) {
		return $where;
	}

	$options = hw_hub_trip_types();
	$rows    = array_slice( $sheets[ $where['sheet'] ], $where['header_row'] + 1, null, true );
	$out     = array();
	$seen    = array();

	foreach ( $rows as $index => $row ) {
		$ref  = isset( $row[ $where['hub_col'] ] ) ? trim( (string) $row[ $where['hub_col'] ] ) : '';
		$cell = isset( $row[ $where['gf_col'] ] ) ? trim( (string) $row[ $where['gf_col'] ] ) : '';

		if ( '' === $ref && '' === $cell ) {
			continue;
		}

		$item = array(
			'line'    => $index + 1,
			'ref'     => $ref,
			'cell'    => $cell,
			'id'      => 0,
			'title'   => '',
			'current' => array(),
			'new'     => array(),
			'status'  => 'error',
			'note'    => '',
		);

		$id = hw_hub_gf_find_page( $ref );

		if ( ! $id ) {
			$item['note'] = 'No page found at this address.';
			$out[]        = $item;
			continue;
		}

		$item['id']    = $id;
		$item['title'] = get_the_title( $id );

		if ( HW_HUB_CITY_TEMPLATE_KEY !== get_page_template_slug( $id ) ) {
			$item['note'] = 'This page is not a City Hub.';
			$out[]        = $item;
			continue;
		}

		if ( ! current_user_can( 'edit_post', $id ) ) {
			$item['note'] = 'You cannot edit this page.';
			$out[]        = $item;
			continue;
		}

		if ( isset( $seen[ $id ] ) ) {
			$item['note'] = 'This hub is already on row ' . $seen[ $id ] . '. Only the first row is used.';
			$out[]        = $item;
			continue;
		}

		$seen[ $id ] = $item['line'];

		$stored          = hw_hub_parse_checks( (string) get_post_meta( $id, hw_hub_meta_key( 'good_for', $id ), true ), $options );
		$item['current'] = $stored['values'];

		if ( '' === $cell ) {
			$item['status'] = 'skip';
			$item['note']   = 'Good for is empty, so this hub is left as it is.';
			$item['new']    = $item['current'];
			$out[]          = $item;
			continue;
		}

		if ( 'none' === strtolower( $cell ) ) {
			$item['new'] = array();
		} else {
			$parsed = hw_hub_parse_checks( $cell, $options );

			if ( $parsed['unknown'] ) {
				$item['note'] = 'Not a trip type: ' . implode( ', ', $parsed['unknown'] ) . '. Use: ' . implode( ', ', $options ) . '.';
				$out[]        = $item;
				continue;
			}

			$item['new'] = $parsed['values'];
		}

		$item['status'] = ( $item['new'] === $item['current'] ) ? 'same' : 'change';
		$out[]          = $item;
	}

	if ( ! $out ) {
		return new WP_Error( 'hw_hub_gf_empty', 'The sheet has the right headers but no rows under them.' );
	}

	return array(
		'sheet' => $where['sheet'],
		'rows'  => $out,
	);
}

/**
 * Handles the upload: reads, checks, and sends the person to the preview.
 */
function hw_hub_gf_handle_upload() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hub_gf_upload' );

	$file = isset( $_FILES['hw_hub_gf_file'] ) ? $_FILES['hw_hub_gf_file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

	if ( ! $file || ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		hw_hub_gf_fail( 'Choose a file to upload.' );
	}

	$sheets = hw_hub_gf_read_file( $file['tmp_name'], sanitize_file_name( $file['name'] ) );
	$data   = is_wp_error( $sheets ) ? $sheets : hw_hub_gf_build_preview( $sheets );

	if ( is_wp_error( $data ) ) {
		hw_hub_gf_fail( $data->get_error_message() );
	}

	$data['file'] = sanitize_file_name( $file['name'] );
	set_transient( hw_hub_gf_transient(), $data, HOUR_IN_SECONDS );

	wp_safe_redirect( hw_hub_gf_url( array( 'step' => 'preview' ) ) );
	exit;
}
add_action( 'admin_post_hw_hub_gf_upload', 'hw_hub_gf_handle_upload' );

/**
 * Saves the checked rows. Translations of each hub get the same value, since
 * what a city is good for does not depend on the language it is read in.
 */
function hw_hub_gf_handle_apply() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hub_gf_apply' );

	$data = get_transient( hw_hub_gf_transient() );

	if ( ! $data || empty( $data['rows'] ) ) {
		hw_hub_gf_fail( 'The preview has expired. Upload the file again.' );
	}

	$hubs         = 0;
	$translations = 0;

	foreach ( $data['rows'] as $row ) {
		if ( 'change' !== $row['status'] || ! current_user_can( 'edit_post', $row['id'] ) ) {
			continue;
		}

		$value = implode( ',', $row['new'] );
		update_post_meta( $row['id'], hw_hub_meta_key( 'good_for', $row['id'] ), $value );
		$hubs++;

		if ( function_exists( 'pll_get_post_translations' ) ) {
			foreach ( pll_get_post_translations( $row['id'] ) as $translation_id ) {
				if ( (int) $translation_id === (int) $row['id'] || HW_HUB_CITY_TEMPLATE_KEY !== get_page_template_slug( $translation_id ) ) {
					continue;
				}

				update_post_meta( $translation_id, hw_hub_meta_key( 'good_for', $translation_id ), $value );
				$translations++;
			}
		}
	}

	delete_transient( hw_hub_gf_transient() );

	wp_safe_redirect(
		hw_hub_gf_url(
			array(
				'hw_gf_done' => $hubs,
				'hw_gf_tr'   => $translations,
			)
		)
	);
	exit;
}
add_action( 'admin_post_hw_hub_gf_apply', 'hw_hub_gf_handle_apply' );

/**
 * Downloads every city hub with its current Good for value, in the same shape
 * the upload takes, so the sheet can be edited and uploaded straight back.
 */
function hw_hub_gf_handle_export() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( 'You do not have permission to do this.' );
	}

	check_admin_referer( 'hw_hub_gf_export' );

	$options = hw_hub_trip_types();
	$rows    = array( array( 'City hub', 'Good for', 'Page title', 'Status' ) );

	foreach ( hw_hub_gf_city_hubs() as $post ) {
		$parsed = hw_hub_parse_checks( (string) get_post_meta( $post->ID, hw_hub_meta_key( 'good_for', $post->ID ), true ), $options );
		$rows[] = array(
			get_permalink( $post ),
			implode( ', ', hw_hub_check_labels( $parsed['values'], $options ) ),
			get_the_title( $post ),
			$post->post_status,
		);
	}

	$bytes = hw_hub_xlsx_write( array( 'Good for' => $rows ), array( 'Good for' => array( 60, 50, 28, 10 ) ) );

	if ( is_wp_error( $bytes ) ) {
		wp_die( esc_html( $bytes->get_error_message() ) );
	}

	nocache_headers();
	header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	header( 'Content-Disposition: attachment; filename="city-hub-trip-types-' . gmdate( 'Y-m-d' ) . '.xlsx"' );
	header( 'Content-Length: ' . strlen( $bytes ) );

	echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary file.
	exit;
}
add_action( 'admin_post_hw_hub_gf_export', 'hw_hub_gf_handle_export' );

/**
 * The screen.
 */
function hw_hub_gf_render() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	$options = hw_hub_trip_types();
	$step    = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$data    = 'preview' === $step ? get_transient( hw_hub_gf_transient() ) : false;
	$export  = wp_nonce_url( admin_url( 'admin-post.php?action=hw_hub_gf_export' ), 'hw_hub_gf_export' );
	?>
	<div class="wrap hw-hub-gf">
		<h1><?php esc_html_e( 'Hub trip types', 'hostelworld' ); ?></h1>

		<?php
		$error = isset( $_GET['hw_gf_error'] ) ? get_transient( 'hw_hub_gf_err_' . get_current_user_id() ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $error ) :
			delete_transient( 'hw_hub_gf_err_' . get_current_user_id() );
			?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>
		<?php endif; ?>

		<?php if ( isset( $_GET['hw_gf_done'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success">
				<p>
					<?php
					$done = absint( $_GET['hw_gf_done'] ); // phpcs:ignore WordPress.Security.NonceVerification
					$tr   = isset( $_GET['hw_gf_tr'] ) ? absint( $_GET['hw_gf_tr'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
					printf( esc_html( _n( 'Updated %d city hub.', 'Updated %d city hubs.', $done, 'hostelworld' ) ), (int) $done );
					if ( $tr ) {
						echo ' ';
						printf( esc_html( _n( 'Their translations were updated too (%d page).', 'Their translations were updated too (%d pages).', $tr, 'hostelworld' ) ), (int) $tr );
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $data ) : ?>
			<?php
			$counts = array( 'change' => 0, 'same' => 0, 'skip' => 0, 'error' => 0 );
			foreach ( $data['rows'] as $row ) {
				$counts[ $row['status'] ]++;
			}
			$labels = array(
				'change' => 'Will change',
				'same'   => 'Already set',
				'skip'   => 'Left as is',
				'error'  => 'Problem',
			);
			?>
			<h2><?php echo esc_html( sprintf( 'Preview: %s (sheet "%s")', $data['file'], $data['sheet'] ) ); ?></h2>
			<p>
				<?php
				echo esc_html(
					sprintf(
						'%1$d will change, %2$d already match, %3$d left as they are, %4$d with a problem. Nothing has been saved yet.',
						$counts['change'],
						$counts['same'],
						$counts['skip'],
						$counts['error']
					)
				);
				?>
			</p>
			<?php if ( $counts['error'] ) : ?>
				<p><?php esc_html_e( 'Rows with a problem are not saved. Fix them in the sheet and upload it again, or apply the rest now.', 'hostelworld' ); ?></p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0 16px">
				<?php wp_nonce_field( 'hw_hub_gf_apply' ); ?>
				<input type="hidden" name="action" value="hw_hub_gf_apply">
				<?php submit_button( sprintf( 'Apply %d changes', $counts['change'] ), 'primary', 'submit', false, $counts['change'] ? array() : array( 'disabled' => 'disabled' ) ); ?>
				<a class="button" href="<?php echo esc_url( hw_hub_gf_url() ); ?>"><?php esc_html_e( 'Cancel', 'hostelworld' ); ?></a>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:5%"><?php esc_html_e( 'Row', 'hostelworld' ); ?></th>
						<th style="width:22%"><?php esc_html_e( 'City hub', 'hostelworld' ); ?></th>
						<th style="width:25%"><?php esc_html_e( 'Now', 'hostelworld' ); ?></th>
						<th style="width:25%"><?php esc_html_e( 'After', 'hostelworld' ); ?></th>
						<th><?php esc_html_e( 'Status', 'hostelworld' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data['rows'] as $row ) : ?>
						<tr>
							<td><?php echo (int) $row['line']; ?></td>
							<td>
								<?php if ( $row['id'] ) : ?>
									<a href="<?php echo esc_url( get_edit_post_link( $row['id'] ) ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
								<?php else : ?>
									<code><?php echo esc_html( $row['ref'] ); ?></code>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( implode( ', ', hw_hub_check_labels( $row['current'], $options ) ) ); ?></td>
							<td>
								<?php
								if ( 'error' === $row['status'] ) {
									echo esc_html( $row['cell'] );
								} else {
									echo esc_html( implode( ', ', hw_hub_check_labels( $row['new'], $options ) ) );
								}
								?>
							</td>
							<td>
								<strong><?php echo esc_html( $labels[ $row['status'] ] ); ?></strong>
								<?php if ( $row['note'] ) : ?>
									<br><?php echo esc_html( $row['note'] ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php else : ?>
			<p><?php esc_html_e( 'Sets the "Good for" trip types on many city hubs at once. Nothing else on the pages changes.', 'hostelworld' ); ?></p>

			<h2><?php esc_html_e( '1. Get a sheet', 'hostelworld' ); ?></h2>
			<p>
				<a class="button" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'Download every city hub with its current trip types', 'hostelworld' ); ?></a>
			</p>
			<p><?php esc_html_e( 'Or use your own. It needs two columns, headed "City hub" and "Good for". Other columns are ignored.', 'hostelworld' ); ?></p>
			<ul style="list-style:disc;padding-left:20px">
				<li><?php esc_html_e( 'City hub: the hub\'s address, its page path (asia/japan/osaka) or its page ID.', 'hostelworld' ); ?></li>
				<li><?php echo esc_html( 'Good for: trip types separated by commas. Use: ' . implode( ', ', $options ) . '.' ); ?></li>
				<li><?php esc_html_e( 'An empty Good for cell leaves that hub as it is. Write "none" to clear it.', 'hostelworld' ); ?></li>
			</ul>

			<h2><?php esc_html_e( '2. Upload it', 'hostelworld' ); ?></h2>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'hw_hub_gf_upload' ); ?>
				<input type="hidden" name="action" value="hw_hub_gf_upload">
				<input type="file" name="hw_hub_gf_file" accept=".xlsx,.csv" required>
				<?php submit_button( __( 'Check the file', 'hostelworld' ), 'primary', 'submit', false ); ?>
			</form>
			<p class="description"><?php esc_html_e( 'You see every row and what would change before anything is saved. Translations of each hub get the same trip types.', 'hostelworld' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}
