/**
 * Carousel arrows. The track scrolls natively with snap points, so swipe,
 * trackpad and keyboard all work whether or not this runs; the buttons are an
 * addition, not the mechanism.
 */
( function () {
	'use strict';

	function setUp( track ) {
		// The nearest ancestor that actually holds a pair of buttons, not the
		// whole section: several carousels can share one section, and scoping
		// to the section wires every row's buttons to every row's track — so
		// the first row that doesn't overflow disables all of them.
		var scope = track.parentElement;

		while ( scope && ! scope.querySelector( '.hw-hub__carousel-btn' ) ) {
			scope = scope.parentElement;
		}

		if ( ! scope ) {
			return;
		}

		var buttons = scope.querySelectorAll( '.hw-hub__carousel-btn' );

		function step() {
			var card = track.querySelector( ':scope > *' );

			return card ? card.getBoundingClientRect().width + 20 : track.clientWidth * 0.8;
		}

		function sync() {
			var max = track.scrollWidth - track.clientWidth - 2;

			buttons.forEach( function ( button ) {
				var atEnd = 'next' === button.dataset.direction
					? track.scrollLeft >= max
					: track.scrollLeft <= 2;

				button.disabled = atEnd || max <= 0;
			} );
		}

		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var by = 'next' === button.dataset.direction ? step() : -step();

				track.scrollBy( { left: by, behavior: 'smooth' } );
			} );
		} );

		track.addEventListener( 'scroll', sync, { passive: true } );
		window.addEventListener( 'resize', sync );

		// Carousels inside a tab, an accordion or anything else that starts
		// hidden report no width until they are shown.
		if ( 'undefined' !== typeof window.ResizeObserver ) {
			new window.ResizeObserver( sync ).observe( track );
		}

		sync();
	}

	function init() {
		document.querySelectorAll( '[data-hw-carousel]' ).forEach( setUp );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

/**
 * Trip-type tabs on the country hub. Panels are rendered server-side and the
 * first is open, so the content is in the page whether or not this runs.
 */
( function () {
	'use strict';

	function init() {
		document.querySelectorAll( '.hw-hub__trip-tabs' ).forEach( function ( tabs ) {
			var section = tabs.closest( 'section' );

			if ( ! section ) {
				return;
			}

			tabs.addEventListener( 'click', function ( event ) {
				var tab = event.target.closest( '.hw-hub__trip-tab' );

				if ( ! tab ) {
					return;
				}

				var panelId = tab.getAttribute( 'aria-controls' );

				tabs.querySelectorAll( '.hw-hub__trip-tab' ).forEach( function ( other ) {
					var active = other === tab;

					other.classList.toggle( 'is-active', active );
					other.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				} );

				section.querySelectorAll( '.hw-hub__trip-panel' ).forEach( function ( panel ) {
					var active = panel.id === panelId;

					panel.classList.toggle( 'is-active', active );
					panel.hidden = ! active;
				} );

				// A hidden panel has no width, so its carousel measured itself as
				// having nothing to scroll and disabled its own arrows. Now that
				// it is visible, make every carousel measure again.
				window.dispatchEvent( new Event( 'resize' ) );
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

/**
 * Budget calculator. Everything it needs is in data attributes on the buttons,
 * so the numbers stay editable in WordPress rather than living in here.
 */
( function () {
	'use strict';

	function setUp( calc ) {
		var symbol = calc.dataset.currency || '';
		var styles = calc.querySelectorAll( '.hw-hub__calc-style' );
		var nights = calc.querySelector( '[data-hw-nights]' );
		var out = calc.querySelector( '[data-hw-nights-out]' );
		var total = calc.querySelector( '[data-hw-total]' );
		var per = calc.querySelector( '[data-hw-per]' );

		if ( ! styles.length || ! nights ) {
			return;
		}

		function money( value ) {
			return symbol + Math.round( value ).toLocaleString();
		}

		function render() {
			var active = calc.querySelector( '.hw-hub__calc-style.is-active' ) || styles[ 0 ];
			var count = parseInt( nights.value, 10 ) || 1;
			var perNight = 0;

			[ 'bed', 'food', 'transport', 'doing' ].forEach( function ( line ) {
				var nightly = parseFloat( active.dataset[ line ] ) || 0;
				var cell = calc.querySelector( '[data-hw-line="' + line + '"]' );

				perNight += nightly;

				if ( cell ) {
					cell.textContent = money( nightly * count );
				}
			} );

			out.textContent = count;
			total.textContent = money( perNight * count );
			per.textContent = money( perNight ) + ' a night × ' + count + ( 1 === count ? ' night' : ' nights' );
		}

		styles.forEach( function ( style ) {
			style.addEventListener( 'click', function () {
				styles.forEach( function ( other ) {
					other.classList.toggle( 'is-active', other === style );
				} );

				render();
			} );
		} );

		nights.addEventListener( 'input', render );
		render();
	}

	function init() {
		document.querySelectorAll( '[data-hw-calc]' ).forEach( setUp );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

/**
 * Section nav overflow. The bar scrolls natively; these buttons appear only
 * when there is more to see, so a short nav is left alone.
 */
( function () {
	'use strict';

	function setUp( nav ) {
		var track = nav.querySelector( '.hw-hub__jump-scroll' );
		var buttons = nav.querySelectorAll( '.hw-hub__jump-btn' );

		if ( ! track || ! buttons.length ) {
			return;
		}

		function sync() {
			var max = track.scrollWidth - track.clientWidth - 2;
			var overflows = max > 2;

			buttons.forEach( function ( button ) {
				button.hidden = ! overflows;
				button.disabled = 'next' === button.dataset.direction
					? track.scrollLeft >= max
					: track.scrollLeft <= 2;
			} );
		}

		buttons.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var by = track.clientWidth * 0.6;

				track.scrollBy( { left: 'next' === button.dataset.direction ? by : -by, behavior: 'smooth' } );
			} );
		} );

		track.addEventListener( 'scroll', sync, { passive: true } );
		window.addEventListener( 'resize', sync );

		// Carousels inside a tab, an accordion or anything else that starts
		// hidden report no width until they are shown.
		if ( 'undefined' !== typeof window.ResizeObserver ) {
			new window.ResizeObserver( sync ).observe( track );
		}

		sync();
	}

	function init() {
		document.querySelectorAll( '.hw-hub__jump' ).forEach( setUp );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

/**
 * The neighbourhood map: Leaflet with OpenStreetMap tiles, no key needed.
 *
 * Markers are labels rather than pins, so the map reads as a list of names.
 * Clicking one jumps to that neighbourhood in the list beside it. If Leaflet
 * fails to load, the list is still the whole story.
 */
( function () {
	'use strict';

	function setUp( node ) {
		if ( typeof window.L === 'undefined' ) {
			node.style.display = 'none';
			return;
		}

		var markers;

		try {
			markers = JSON.parse( node.dataset.markers || '[]' );
		} catch ( error ) {
			return;
		}

		if ( ! markers.length ) {
			node.style.display = 'none';
			return;
		}

		var zoom = parseInt( node.dataset.zoom, 10 ) || 13;

		// Centre on the neighbourhoods typed into the editor, which are always
		// in the right city. The API's city coordinates are not used here: a
		// city slug that matches the wrong place (newcastle finding Newcastle,
		// Australia) would otherwise drag the whole map to the other side of
		// the world.
		var centre = markers.reduce( function ( sum, marker ) {
			return [ sum[ 0 ] + marker.lat / markers.length, sum[ 1 ] + marker.lng / markers.length ];
		}, [ 0, 0 ] );

		var map = window.L.map( node, { scrollWheelZoom: false } ).setView( centre, zoom );

		window.L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		} ).addTo( map );

		var bounds = [];
		var hostels = [];

		try {
			hostels = JSON.parse( node.dataset.hostels || '[]' );
		} catch ( error ) {
			hostels = [];
		}

		markers.forEach( function ( marker ) {
			var icon = window.L.divIcon( {
				className: '',
				html: '<span class="hw-hub__marker' + ( 'city' === marker.kind ? ' hw-hub__marker--city' : '' ) + '">' + marker.name + '</span>',
				iconSize: null
			} );

			window.L.marker( [ marker.lat, marker.lng ], { icon: icon } )
				.addTo( map )
				.on( 'click', function () {
					var target = document.getElementById( marker.id );

					if ( target ) {
						target.scrollIntoView( { behavior: 'smooth', block: 'center' } );
						window.history.replaceState( null, '', '#' + marker.id );
					}
				} );

			bounds.push( [ marker.lat, marker.lng ] );
		} );

		// Hostels as small dots, so the neighbourhood labels stay readable.
		// Hostels more than 30 km from the neighbourhoods are left off: they
		// belong to another city, and would force the map to zoom out to fit.
		// A state map sets data-radius="0": its hostels come only from its own
		// cities, which can be hundreds of kilometres apart, so none are dropped.
		var radius = '' === ( node.dataset.radius || '' ) ? 30000 : parseInt( node.dataset.radius, 10 );

		if ( radius > 0 ) {
			hostels = hostels.filter( function ( hostel ) {
				return window.L.latLng( centre ).distanceTo( [ hostel.lat, hostel.lng ] ) < radius;
			} );
		}

		hostels.forEach( function ( hostel ) {
			var dot = window.L.divIcon( {
				className: '',
				html: '<span class="hw-hub__pin"></span>',
				iconSize: [ 14, 14 ],
				iconAnchor: [ 7, 7 ]
			} );

			var bits = [ '<strong>' + hostel.name + '</strong>' ];

			if ( hostel.rating ) {
				bits.push( hostel.rating );
			}

			if ( hostel.price ) {
				bits.push( 'from ' + hostel.price );
			}

			var html = bits.join( ' · ' );

			if ( hostel.url ) {
				html = '<a href="' + hostel.url + '" rel="noopener">' + html + '</a>';
			}

			window.L.marker( [ hostel.lat, hostel.lng ], { icon: dot } )
				.addTo( map )
				.bindPopup( html );

			bounds.push( [ hostel.lat, hostel.lng ] );
		} );

		if ( bounds.length > 1 ) {
			// Fit everything in, but never closer than the zoom set in the
			// editor, so that setting still decides how close the map starts.
			map.fitBounds( bounds, { padding: [ 40, 40 ], maxZoom: zoom } );
		}
	}

	function init() {
		document.querySelectorAll( '[data-hw-map]' ).forEach( setUp );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );

/**
 * Click tracking for the hostel, event and CTA links on the hubs.
 *
 * Every tracked link carries data-hw-track (what it is) and, where useful,
 * data-hw-track-label (which one). A click pushes one event to the dataLayer,
 * which Google Tag Manager reads; on a page with gtag.js and no Tag Manager,
 * the event goes straight to GA4 instead. Nothing is sent if neither is
 * present, and the link always works regardless.
 */
( function () {
	'use strict';

	var hub  = document.getElementById( 'hw-hub' );
	var page = hub ? ( hub.className.match( /hw-hub--(\w+)/ ) || [] )[ 1 ] || '' : '';

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest ? event.target.closest( '[data-hw-track]' ) : null;

		if ( ! link ) {
			return;
		}

		var params = {
			link_type: link.getAttribute( 'data-hw-track' ),
			link_text: link.getAttribute( 'data-hw-track-label' ) || '',
			link_url: link.href || '',
			hub_type: page,
			hub_title: document.title
		};

		if ( window.google_tag_manager || ! window.gtag ) {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( Object.assign( { event: 'hw_hub_click' }, params ) );
		} else {
			window.gtag( 'event', 'hw_hub_click', params );
		}
	} );
}() );
