<?php
/**
 * Postcard maker.
 *
 * A "Make a postcard" button on the main photo of every hub. Readers write a
 * message, and the postcard (the hub photo, "Greetings from …", their message
 * and the blog's logo) is drawn in their browser, ready to download or to
 * share from their phone. Nothing they write is sent to or kept by the blog.
 *
 * The photo is passed through the blog's own address so the browser is
 * allowed to turn it into an image file, wherever the uploads are stored.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The wording the postcard uses, translatable under Languages > String translations.
 *
 * @return array
 */
function hw_hub_postcard_strings() {
	return array(
		'Make a postcard',
		'Make a postcard from %s',
		'Greetings from',
		'Your message',
		'Wish you were here!',
		'Download',
		'Share',
		'Close',
		'Nothing you write is saved. The postcard is made on your device.',
		'Photo',
		'The photo could not be loaded. Please try again.',
	);
}

/**
 * Registers the postcard wording with Polylang.
 */
function hw_hub_postcard_register_strings() {
	if ( ! function_exists( 'pll_register_string' ) ) {
		return;
	}

	foreach ( hw_hub_postcard_strings() as $string ) {
		pll_register_string( $string, $string, 'Hostelworld hubs' );
	}
}
add_action( 'init', 'hw_hub_postcard_register_strings' );

/**
 * The Media Library ID behind a hub's main photo, or 0 when it isn't a photo
 * from the Media Library (an illustration, or a photo hosted elsewhere).
 *
 * @param mixed $image The hero_image field: an ID, a URL or illustration:name.
 * @return int
 */
function hw_hub_postcard_photo_id( $image ) {
	$image = is_string( $image ) ? trim( $image ) : $image;

	if ( empty( $image ) ) {
		return 0;
	}

	if ( is_numeric( $image ) ) {
		$id = (int) $image;
	} elseif ( is_string( $image ) && 0 === strpos( $image, 'illustration:' ) ) {
		return 0;
	} else {
		$id = attachment_url_to_postid( (string) $image );
	}

	return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
}

/**
 * The "Make a postcard" button, placed on the hub's main photo.
 *
 * @param mixed  $image The hub's hero_image field.
 * @param string $place The destination name, e.g. "Lisbon".
 */
function hw_hub_postcard_button( $image, $place ) {
	$id    = hw_hub_postcard_photo_id( $image );
	$place = trim( wp_strip_all_tags( (string) $place ) );

	if ( ! $id || '' === $place ) {
		return;
	}

	$credit = trim( wp_strip_all_tags( wp_get_attachment_caption( $id ) ) );
	$logo   = '';
	$logo_id = (int) get_theme_mod( 'custom_logo' );

	if ( $logo_id && ! wp_attachment_is_image( $logo_id ) ) {
		$logo = (string) wp_get_attachment_url( $logo_id ); // An SVG logo, which the browser can draw as it is.
	} elseif ( $logo_id ) {
		$logo = add_query_arg(
			array(
				'action' => 'hw_postcard_photo',
				'id'     => $logo_id,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	$data = array(
		'photo'  => add_query_arg(
			array(
				'action' => 'hw_postcard_photo',
				'id'     => $id,
			),
			admin_url( 'admin-ajax.php' )
		),
		'place'  => $place,
		'credit' => $credit,
		'logo'   => $logo,
		'site'   => preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( home_url() ) ),
		'labels' => array(
			'title'       => sprintf( hw_hub_t( 'Make a postcard from %s' ), $place ),
			'greetings'   => hw_hub_t( 'Greetings from' ),
			'message'     => hw_hub_t( 'Your message' ),
			'placeholder' => hw_hub_t( 'Wish you were here!' ),
			'download'    => hw_hub_t( 'Download' ),
			'share'       => hw_hub_t( 'Share' ),
			'close'       => hw_hub_t( 'Close' ),
			'note'        => hw_hub_t( 'Nothing you write is saved. The postcard is made on your device.' ),
			'photo'       => hw_hub_t( 'Photo' ),
			'error'       => hw_hub_t( 'The photo could not be loaded. Please try again.' ),
		),
	);

	printf(
		'<button type="button" class="hw-hub__postcard-btn" data-hw-postcard="%1$s">%2$s<span>%3$s</span></button>',
		esc_attr( wp_json_encode( $data ) ),
		'<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M12 9v6"/><path d="M15 9h4"/><path d="M15 12h4"/><rect x="5" y="9" width="4" height="4"/></svg>',
		esc_html( hw_hub_t( 'Make a postcard' ) )
	);
}

/**
 * Hands the browser a Media Library image from the blog's own address, at a
 * size fit for a postcard. Only images, and only ones that are public anyway.
 */
function hw_hub_postcard_photo() {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- public, read-only.

	if ( ! $id || ! wp_attachment_is_image( $id ) || 'attachment' !== get_post_type( $id ) ) {
		status_header( 404 );
		exit;
	}

	$parent = wp_get_post_parent_id( $id );

	if ( $parent && 'publish' !== get_post_status( $parent ) && ! current_user_can( 'edit_post', $parent ) ) {
		status_header( 404 );
		exit;
	}

	$mime = get_post_mime_type( $id );
	$file = '';
	$url  = '';

	// The smallest saved size that is at least 1600 pixels wide, else the original.
	$original = get_attached_file( $id );
	$meta     = wp_get_attachment_metadata( $id );

	if ( $original && ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		$best = null;

		foreach ( $meta['sizes'] as $size => $info ) {
			if ( empty( $info['width'] ) || $info['width'] < 1600 ) {
				continue;
			}

			if ( null === $best || $info['width'] < $best['width'] ) {
				$best = $info + array( 'name' => $size );
			}
		}

		if ( $best ) {
			$file = path_join( dirname( $original ), $best['file'] );
			$mime = ! empty( $best['mime-type'] ) ? $best['mime-type'] : $mime;
			$url  = wp_get_attachment_image_url( $id, $best['name'] );
		}
	}

	if ( ! $file ) {
		$file = $original;
		$url  = wp_get_attachment_url( $id );
	}

	$body = '';

	if ( $file && is_readable( $file ) ) {
		$body = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	} elseif ( $url ) {
		// Uploads kept somewhere else (a CDN or cloud storage).
		$response = wp_safe_remote_get( $url, array( 'timeout' => 15 ) );

		if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
		}
	}

	if ( '' === $body ) {
		status_header( 404 );
		exit;
	}

	status_header( 200 );
	header( 'Content-Type: ' . $mime );
	header( 'Content-Length: ' . strlen( $body ) );
	header( 'Cache-Control: public, max-age=86400' );
	header( 'X-Content-Type-Options: nosniff' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- image bytes.
	exit;
}
add_action( 'wp_ajax_hw_postcard_photo', 'hw_hub_postcard_photo' );
add_action( 'wp_ajax_nopriv_hw_postcard_photo', 'hw_hub_postcard_photo' );
