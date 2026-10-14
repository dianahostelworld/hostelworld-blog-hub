/**
 * Hub report: counts what readers do on a hub, for Pages > Hub report.
 *
 * Three things, all anonymous and stored only as daily totals per hub:
 * - a view, once per page load;
 * - each section reached (its top passes the middle of the screen), once per load;
 * - each click on a link or an FAQ question, with the section it was in and
 *   the link's label.
 *
 * No cookies, no IDs, nothing about the reader. Sent with sendBeacon, so it
 * never slows a click down. The existing Google Analytics events are separate
 * and unchanged.
 */
( function () {
	'use strict';

	var hub = document.getElementById( 'hw-hub' );
	var cfg = window.hwHubStats;

	if ( ! hub || ! cfg || ! navigator.sendBeacon || ! window.JSON ) {
		return;
	}

	var queue = [ [ 'v', '', '' ] ];
	var seen  = {};

	function zone( el ) {
		var box = el.closest( 'section[id], .hw-hub__hero, .hw-hub__jump, .hw-hub__cta' );

		if ( ! box ) {
			return 'other';
		}

		if ( box.id ) {
			return box.id;
		}

		if ( box.classList.contains( 'hw-hub__hero' ) ) {
			return 'hero';
		}

		return box.classList.contains( 'hw-hub__jump' ) ? 'nav' : 'cta';
	}

	function flush() {
		while ( queue.length ) {
			navigator.sendBeacon(
				cfg.url,
				new Blob( [ JSON.stringify( { p: cfg.post, e: queue.splice( 0, 40 ) } ) ], { type: 'text/plain' } )
			);
		}
	}

	function label( el ) {
		// FAQ questions: the question only, not the "+" beside it.
		var source = 'SUMMARY' === el.tagName && el.querySelector( 'span' ) ? el.querySelector( 'span' ) : el;
		var text   = el.getAttribute( 'data-hw-track-label' ) || el.getAttribute( 'aria-label' ) || source.textContent || '';

		return text.replace( /\s+/g, ' ' ).trim().slice( 0, 100 );
	}

	// Sections reached.
	if ( 'IntersectionObserver' in window ) {
		var observer = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				var key = zone( entry.target );

				if ( ! seen[ key ] ) {
					seen[ key ] = true;
					queue.push( [ 's', key, '' ] );
				}

				observer.unobserve( entry.target );
			} );
		}, { rootMargin: '0px 0px -50% 0px' } );

		hub.querySelectorAll( 'section[id], .hw-hub__hero, .hw-hub__cta' ).forEach( function ( section ) {
			observer.observe( section );
		} );
	}

	// Clicks on links, and FAQ questions being opened.
	hub.addEventListener( 'click', function ( event ) {
		var target = event.target.closest ? event.target.closest( 'a[href], summary' ) : null;

		if ( ! target ) {
			return;
		}

		if ( 'SUMMARY' === target.tagName ) {
			var details = target.parentNode;

			// Only count opening, not closing.
			if ( details && details.open ) {
				return;
			}
		}

		queue.push( [ 'c', zone( target ), label( target ) ] );
		flush();
	} );

	// For things that aren't links, like the postcard maker: counted as a
	// click in the given section, with the given label.
	window.hwHubStatsTrack = function ( section, text ) {
		queue.push( [ 'c', String( section ), String( text ).slice( 0, 100 ) ] );
		flush();
	};

	// The view goes straight away; sections reached go when the reader leaves.
	flush();

	document.addEventListener( 'visibilitychange', function () {
		if ( 'hidden' === document.visibilityState ) {
			flush();
		}
	} );

	window.addEventListener( 'pagehide', flush );
}() );
