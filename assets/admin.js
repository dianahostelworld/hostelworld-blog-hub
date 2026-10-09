/* global jQuery, wp */
( function ( $ ) {
	'use strict';

	$( function () {
		var $admin = $( '.hw-hub-admin' );

		if ( ! $admin.length ) {
			return;
		}

		// Section tabs.
		$admin.on( 'click', '.hw-hub-admin__tab', function () {
			var section = $( this ).data( 'section' );

			$admin.find( '.hw-hub-admin__tab' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );

			$admin.find( '.hw-hub-admin__panel' ).removeClass( 'is-active' );
			$admin.find( '.hw-hub-admin__panel[data-section="' + section + '"]' ).addClass( 'is-active' );
		} );

		// Add a row. The template carries __index__ placeholders; a counter well
		// past the existing rows keeps new names unique even after deletions.
		$admin.on( 'click', '.hw-hub-admin__row-add', function ( event ) {
			event.preventDefault();

			var $rows = $( this ).closest( '.hw-hub-admin__rows' );
			var next = $rows.data( 'next' );

			if ( typeof next === 'undefined' ) {
				next = $rows.find( '.hw-hub-admin__row' ).length;
			}

			var markup = $rows.find( '.hw-hub-admin__row-template' ).html().split( '__index__' ).join( next );

			$rows.data( 'next', next + 1 );
			$rows.find( '.hw-hub-admin__rows-list' ).append( markup );
		} );

		// Delete a row.
		$admin.on( 'click', '.hw-hub-admin__row-delete', function ( event ) {
			event.preventDefault();

			var $row = $( this ).closest( '.hw-hub-admin__row' );
			var $list = $row.closest( '.hw-hub-admin__rows-list' );

			$row.remove();

			// Never leave the editor with nowhere to type.
			if ( ! $list.find( '.hw-hub-admin__row' ).length ) {
				$list.closest( '.hw-hub-admin__rows' ).find( '.hw-hub-admin__row-add' ).trigger( 'click' );
			}
		} );

		// Put a repeating field back to its starting content.
		$admin.on( 'click', '.hw-hub-admin__rows-restore', function ( event ) {
			event.preventDefault();

			if ( ! window.confirm( 'Replace these rows with the starting content? Anything you have typed here will be lost.' ) ) {
				return;
			}

			var $rows = $( this ).closest( '.hw-hub-admin__rows' );

			$rows.find( '.hw-hub-admin__rows-list' ).html( $rows.find( '.hw-hub-admin__rows-default' ).html() );
		} );

		$admin.on( 'click', '.hw-hub-admin__restore', function ( event ) {
			event.preventDefault();

			var $field = $( this ).closest( '.hw-hub-admin__field' );
			var $area = $field.find( 'textarea' );

			if ( ! window.confirm( 'Replace this with the starting content? Anything you have typed here will be lost.' ) ) {
				return;
			}

			$area.val( $area.data( 'default' ) );
		} );

		// Add and remove itinerary rows inside a city.
		$admin.on( 'click', '.hw-hub-admin__pair-add', function ( event ) {
			event.preventDefault();

			var $pairs = $( this ).closest( '.hw-hub-admin__pairs' );
			var $list = $pairs.find( '.hw-hub-admin__pairs-list' );
			var name = $pairs.data( 'pair-name' );
			var next = $list.find( '.hw-hub-admin__pair' ).length;

			$list.append(
				'<div class="hw-hub-admin__pair">' +
					'<input type="text" class="hw-hub-admin__pair-label" name="' + name + '[' + next + '][label]" value="" placeholder="3 days">' +
					'<input type="text" class="hw-hub-admin__pair-url" name="' + name + '[' + next + '][url]" value="" placeholder="https://www.hostelworld.com/blog/…">' +
					'<button type="button" class="button-link hw-hub-admin__pair-delete">&times;</button>' +
				'</div>'
			);
		} );

		$admin.on( 'click', '.hw-hub-admin__pair-delete', function ( event ) {
			event.preventDefault();

			var $pair = $( this ).closest( '.hw-hub-admin__pair' );
			var $list = $pair.closest( '.hw-hub-admin__pairs-list' );

			$pair.remove();

			if ( ! $list.find( '.hw-hub-admin__pair' ).length ) {
				$list.closest( '.hw-hub-admin__pairs' ).find( '.hw-hub-admin__pair-add' ).trigger( 'click' );
			}
		} );

		// Choosing a review drops its text into the field beneath it.
		$admin.on( 'change', '.hw-hub-admin__quote-pick', function () {
			var value = $( this ).val();

			if ( ! value ) {
				return;
			}

			$( '#' + $( this ).data( 'target' ) ).val( value );
		} );

		// Image picker.
		$admin.on( 'click', '.hw-hub-admin__select', function ( event ) {
			event.preventDefault();

			var $wrap = $( this ).closest( '.hw-hub-admin__media' );
			var frame = wp.media( {
				title: 'Upload or choose an image',
				button: { text: 'Use this image' },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var inRow = $wrap.hasClass( 'hw-hub-admin__media--row' );
				var preferred = inRow ? 'thumbnail' : 'medium';
				var size = attachment.sizes && attachment.sizes[ preferred ] ? attachment.sizes[ preferred ].url : attachment.url;

				$wrap.find( 'input[type="hidden"]' ).val( attachment.id );
				$wrap.find( '.hw-hub-admin__preview' ).html( $( '<img>' ).attr( 'src', size ) );
				$wrap.find( '.hw-hub-admin__remove' ).prop( 'hidden', false );
			} );

			frame.open();
		} );

		$admin.on( 'click', '.hw-hub-admin__remove', function ( event ) {
			event.preventDefault();

			var $wrap = $( this ).closest( '.hw-hub-admin__media' );

			$wrap.find( 'input[type="hidden"]' ).val( '' );
			$wrap.find( '.hw-hub-admin__preview' ).empty();
			$( this ).prop( 'hidden', true );
		} );
	} );
}( jQuery ) );

/* Spreadsheet import. One button: it opens the file picker, and the upload
   starts as soon as a file is chosen. Posts in the background so the panel
   never nests a form inside the post form, which would block Save draft. */
( function () {
	function upload( box, file ) {
		var button = box.querySelector( '.hw-hub-admin__import-go' );
		var status = box.querySelector( '.hw-hub-admin__import-status' );

		var data = new FormData();
		data.append( 'action', 'hw_hub_import' );
		data.append( 'post_id', box.dataset.post );
		data.append( 'nonce', box.dataset.nonce );
		data.append( 'file', file );

		button.disabled = true;
		status.className = 'hw-hub-admin__import-status';
		status.textContent = 'Reading ' + file.name + '…';

		fetch( ( window.ajaxurl || '/wp-admin/admin-ajax.php' ), {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( result ) {
				if ( result && result.success ) {
					status.className = 'hw-hub-admin__import-status is-done';
					status.textContent = result.data.message + ' Reloading…';
					window.setTimeout( function () { window.location.reload(); }, 1200 );
					return;
				}

				button.disabled = false;
				status.className = 'hw-hub-admin__import-status is-error';
				status.textContent = ( result && result.data && result.data.message ) ? result.data.message : 'That import did not work.';
			} )
			.catch( function () {
				button.disabled = false;
				status.className = 'hw-hub-admin__import-status is-error';
				status.textContent = 'The upload failed. The file may be too large for this server.';
			} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.hw-hub-admin__import-go' );

		if ( ! button ) {
			return;
		}

		button.closest( '.hw-hub-admin__import' ).querySelector( '.hw-hub-admin__import-file' ).click();
	} );

	document.addEventListener( 'change', function ( event ) {
		var input = event.target.closest( '.hw-hub-admin__import-file' );

		if ( ! input || ! input.files || ! input.files.length ) {
			return;
		}

		upload( input.closest( '.hw-hub-admin__import' ), input.files[0] );
		input.value = '';
	} );
}() );
