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

/**
 * Postcard maker.
 *
 * The "Make a postcard" button on a hub's main photo opens a small window
 * where the reader writes a message. The postcard is drawn in the browser:
 * nothing is sent to the blog, and it can be downloaded or, on phones,
 * shared straight to an app.
 */
( function () {
	'use strict';

	var W = 1500;
	var H = 1000;
	var FONTS_URL = 'https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Poppins:wght@700;800&display=swap';
	var MAX = 160;

	var modal, canvas, ctx, field, count, status, shareBtn, downloadBtn, opener, data;
	var photo = null;
	var logo = null;
	var queued = false;

	function el( tag, cls, text ) {
		var node = document.createElement( tag );
		if ( cls ) {
			node.className = cls;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	function loadFonts() {
		if ( ! document.getElementById( 'hw-pc-fonts' ) ) {
			var link = document.createElement( 'link' );
			link.id = 'hw-pc-fonts';
			link.rel = 'stylesheet';
			link.href = FONTS_URL;
			document.head.appendChild( link );
		}

		if ( ! document.fonts || ! document.fonts.load ) {
			return Promise.resolve();
		}

		var wanted = Promise.all( [
			document.fonts.load( '600 60px Caveat' ),
			document.fonts.load( '800 120px Poppins' ),
			document.fonts.load( '700 24px Poppins' )
		] ).catch( function () {} );

		return Promise.race( [ wanted, new Promise( function ( resolve ) { setTimeout( resolve, 2500 ); } ) ] );
	}

	function loadImage( src ) {
		return new Promise( function ( resolve, reject ) {
			if ( ! src ) {
				reject();
				return;
			}
			var img = new Image();
			img.onload = function () { resolve( img ); };
			img.onerror = reject;
			img.src = src;
		} );
	}

	function slug( text ) {
		return String( text ).toLowerCase().normalize( 'NFD' ).replace( /[̀-ͯ]/g, '' ).replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' ) || 'postcard';
	}

	function wrap( text, maxWidth ) {
		var lines = [];
		String( text ).split( /\n/ ).forEach( function ( para ) {
			var words = para.split( /\s+/ ).filter( Boolean );
			var line = '';
			if ( ! words.length ) {
				lines.push( '' );
				return;
			}
			words.forEach( function ( word ) {
				var test = line ? line + ' ' + word : word;
				if ( ctx.measureText( test ).width > maxWidth && line ) {
					lines.push( line );
					line = word;
				} else {
					line = test;
				}
			} );
			lines.push( line );
		} );
		return lines;
	}

	function roundRect( x, y, w, h, r ) {
		ctx.beginPath();
		ctx.moveTo( x + r, y );
		ctx.arcTo( x + w, y, x + w, y + h, r );
		ctx.arcTo( x + w, y + h, x, y + h, r );
		ctx.arcTo( x, y + h, x, y, r );
		ctx.arcTo( x, y, x + w, y, r );
		ctx.closePath();
	}

	function drawStamp() {
		var x = 1300, y = 70, w = 140, h = 170, m = 16;

		// Drawn on its own small canvas first, so the perforated edge cuts
		// the stamp only, not the photo underneath.
		var off = document.createElement( 'canvas' );
		off.width = w;
		off.height = h;
		var main = ctx;
		ctx = off.getContext( '2d' );

		ctx.fillStyle = '#FFFFFF';
		ctx.fillRect( 0, 0, w, h );
		ctx.globalCompositeOperation = 'destination-out';
		for ( var i = 0; i <= w; i += 14 ) {
			ctx.beginPath(); ctx.arc( i, 0, 5, 0, Math.PI * 2 ); ctx.fill();
			ctx.beginPath(); ctx.arc( i, h, 5, 0, Math.PI * 2 ); ctx.fill();
		}
		for ( var j = 0; j <= h; j += 14 ) {
			ctx.beginPath(); ctx.arc( 0, j, 5, 0, Math.PI * 2 ); ctx.fill();
			ctx.beginPath(); ctx.arc( w, j, 5, 0, Math.PI * 2 ); ctx.fill();
		}
		ctx.globalCompositeOperation = 'source-over';

		// Inside: the photo itself, small, as stamps often are.
		var ix = 14, iy = 14, iw = w - 28, ih = h - 52;
		ctx.save();
		ctx.beginPath();
		ctx.rect( ix, iy, iw, ih );
		ctx.clip();
		cover( photo, ix, iy, iw, ih );
		ctx.restore();

		ctx.fillStyle = '#F25621';
		ctx.textAlign = 'center';
		ctx.textBaseline = 'alphabetic';
		var name = fitText( data.place.toUpperCase(), iw, 15, '700', 'Poppins, sans-serif' );
		ctx.fillText( name, w / 2, h - 18 );

		ctx = main;
		ctx.save();
		ctx.translate( x + w / 2, y + h / 2 );
		ctx.rotate( 0.04 );
		ctx.shadowColor = 'rgba(0,0,0,0.3)';
		ctx.shadowBlur = m;
		ctx.drawImage( off, -w / 2, -h / 2 );
		ctx.restore();

		// Postmark over the corner of the stamp.
		var cx = 1222, cy = 205, r = 58;
		ctx.save();
		ctx.strokeStyle = 'rgba(255,255,255,0.85)';
		ctx.fillStyle = 'rgba(255,255,255,0.85)';
		ctx.lineWidth = 4;
		ctx.beginPath(); ctx.arc( cx, cy, r, 0, Math.PI * 2 ); ctx.stroke();
		ctx.lineWidth = 2;
		ctx.beginPath(); ctx.arc( cx, cy, r - 10, 0, Math.PI * 2 ); ctx.stroke();
		var now = new Date();
		var lang = document.documentElement.lang || 'en';
		var dayMonth;
		try {
			dayMonth = now.toLocaleDateString( lang, { day: '2-digit', month: 'short' } );
		} catch ( e ) {
			dayMonth = now.toLocaleDateString( 'en', { day: '2-digit', month: 'short' } );
		}
		ctx.textAlign = 'center';
		ctx.textBaseline = 'middle';
		ctx.font = '700 22px Poppins, sans-serif';
		ctx.fillText( dayMonth.replace( /\./g, '' ).toUpperCase(), cx, cy - 10 );
		ctx.font = '700 20px Poppins, sans-serif';
		ctx.fillText( String( now.getFullYear() ), cx, cy + 16 );
		// Wavy cancellation lines.
		ctx.lineWidth = 3;
		for ( var k = 0; k < 3; k++ ) {
			ctx.beginPath();
			for ( var t = 0; t <= 150; t += 5 ) {
				var px = cx - r - 160 + t;
				var py = cy - 24 + k * 22 + Math.sin( t / 12 ) * 6;
				if ( 0 === t ) { ctx.moveTo( px, py ); } else { ctx.lineTo( px, py ); }
			}
			ctx.stroke();
		}
		ctx.restore();
	}

	function fitText( text, maxWidth, size, weight, family ) {
		ctx.font = weight + ' ' + size + 'px ' + family;
		if ( ctx.measureText( text ).width <= maxWidth ) {
			return text;
		}
		while ( text.length > 1 && ctx.measureText( text + '…' ).width > maxWidth ) {
			text = text.slice( 0, -1 );
		}
		return text + '…';
	}

	function cover( img, x, y, w, h ) {
		var scale = Math.max( w / img.naturalWidth, h / img.naturalHeight );
		var sw = w / scale;
		var sh = h / scale;
		var sx = ( img.naturalWidth - sw ) / 2;
		var sy = ( img.naturalHeight - sh ) / 2;
		ctx.drawImage( img, sx, sy, sw, sh, x, y, w, h );
	}

	function draw() {
		queued = false;

		if ( ! photo ) {
			return;
		}

		var B = 28;

		// Card and photo.
		ctx.fillStyle = '#FBF7EF';
		ctx.fillRect( 0, 0, W, H );
		cover( photo, B, B, W - B * 2, H - B * 2 );

		// Shade behind the title, top left.
		var g = ctx.createLinearGradient( 0, B, 0, H * 0.55 );
		g.addColorStop( 0, 'rgba(18,20,23,0.55)' );
		g.addColorStop( 1, 'rgba(18,20,23,0)' );
		ctx.fillStyle = g;
		ctx.fillRect( B, B, W - B * 2, H * 0.55 - B );

		// "Greetings from" and the place.
		ctx.save();
		ctx.shadowColor = 'rgba(0,0,0,0.35)';
		ctx.shadowBlur = 18;
		ctx.fillStyle = '#FFFFFF';
		ctx.textAlign = 'left';
		ctx.textBaseline = 'alphabetic';
		ctx.font = '600 72px Caveat, cursive';
		ctx.fillText( data.labels.greetings, 84, 150 );

		var place = data.place.toUpperCase();
		var size = 150;
		ctx.font = '800 ' + size + 'px Poppins, sans-serif';
		while ( size > 56 && ctx.measureText( place ).width > 1060 ) {
			size -= 4;
			ctx.font = '800 ' + size + 'px Poppins, sans-serif';
		}
		ctx.fillText( fitText( place, 1060, size, '800', 'Poppins, sans-serif' ), 76, 150 + size * 0.92 );
		ctx.restore();

		drawStamp();

		// The message, on a note pinned to the bottom left.
		var message = field.value.trim() || data.labels.placeholder;
		var noteW = 700;
		var pad = 36;
		var fontSize = 54;
		var lines;
		ctx.font = '600 ' + fontSize + 'px Caveat, cursive';
		lines = wrap( message, noteW - pad * 2 );
		while ( lines.length > 4 && fontSize > 38 ) {
			fontSize -= 2;
			ctx.font = '600 ' + fontSize + 'px Caveat, cursive';
			lines = wrap( message, noteW - pad * 2 );
		}
		lines = lines.slice( 0, 5 );
		var lineH = fontSize * 1.12;
		var footer = 52;
		var noteH = pad + lines.length * lineH + 16 + footer;
		var nx = 70;
		var ny = H - B - 44 - noteH;

		ctx.save();
		ctx.translate( nx + noteW / 2, ny + noteH / 2 );
		ctx.rotate( -0.025 );
		ctx.translate( -noteW / 2, -noteH / 2 );
		ctx.shadowColor = 'rgba(0,0,0,0.3)';
		ctx.shadowBlur = 24;
		ctx.shadowOffsetY = 6;
		ctx.fillStyle = '#FFFDF7';
		roundRect( 0, 0, noteW, noteH, 6 );
		ctx.fill();
		ctx.shadowColor = 'transparent';

		ctx.fillStyle = '#1D3B9A';
		ctx.font = '600 ' + fontSize + 'px Caveat, cursive';
		ctx.textAlign = 'left';
		ctx.textBaseline = 'alphabetic';
		lines.forEach( function ( line, i ) {
			ctx.fillText( line, pad, pad + fontSize * 0.85 + i * lineH );
		} );

		// Footer: a thin line, then the logo (or the blog's address).
		var fy = noteH - footer;
		ctx.strokeStyle = '#EDE6D6';
		ctx.lineWidth = 2;
		ctx.beginPath(); ctx.moveTo( pad, fy ); ctx.lineTo( noteW - pad, fy ); ctx.stroke();

		if ( logo ) {
			var lh = 28;
			var lw = Math.min( 260, lh * ( logo.naturalWidth / logo.naturalHeight || 4 ) );
			ctx.drawImage( logo, pad, fy + ( footer - lh ) / 2, lw, lw / ( logo.naturalWidth / logo.naturalHeight || 4 ) );
		} else {
			ctx.fillStyle = '#F25621';
			ctx.font = '700 22px Poppins, sans-serif';
			ctx.textBaseline = 'middle';
			ctx.fillText( data.site, pad, fy + footer / 2 + 2 );
		}
		ctx.restore();

		// Photo credit, in the white border.
		if ( data.credit ) {
			var credit = /^photo/i.test( data.credit ) ? data.credit : data.labels.photo + ': ' + data.credit;
			ctx.save();
			ctx.fillStyle = 'rgba(99,108,125,0.9)';
			ctx.textAlign = 'right';
			ctx.textBaseline = 'middle';
			ctx.fillText( fitText( credit, 900, 15, '400', 'Inter, Arial, sans-serif' ), W - B, H - B / 2 );
			ctx.restore();
		}
	}

	function redraw() {
		if ( ! queued ) {
			queued = true;
			window.requestAnimationFrame( draw );
		}
	}

	function toBlob() {
		return new Promise( function ( resolve ) {
			canvas.toBlob( resolve, 'image/jpeg', 0.9 );
		} );
	}

	function fileName() {
		return 'postcard-' + slug( data.place ) + '.jpg';
	}

	function build() {
		modal = el( 'div', 'hw-pc' );
		modal.hidden = true;
		modal.setAttribute( 'role', 'dialog' );
		modal.setAttribute( 'aria-modal', 'true' );
		modal.setAttribute( 'aria-labelledby', 'hw-pc-title' );

		var box = el( 'div', 'hw-pc__box' );
		var title = el( 'h2', 'hw-pc__title' );
		title.id = 'hw-pc-title';
		var close = el( 'button', 'hw-pc__close', '×' );
		close.type = 'button';

		var preview = el( 'div', 'hw-pc__preview' );
		canvas = document.createElement( 'canvas' );
		canvas.width = W;
		canvas.height = H;
		canvas.setAttribute( 'role', 'img' );
		ctx = canvas.getContext( '2d' );
		status = el( 'div', 'hw-pc__status', '…' );
		preview.appendChild( canvas );
		preview.appendChild( status );

		var label = el( 'label', 'hw-pc__label' );
		label.setAttribute( 'for', 'hw-pc-msg' );
		field = el( 'textarea', 'hw-pc__msg' );
		field.id = 'hw-pc-msg';
		field.maxLength = MAX;
		field.rows = 3;
		count = el( 'div', 'hw-pc__count' );

		var actions = el( 'div', 'hw-pc__actions' );
		shareBtn = el( 'button', 'hw-pc__btn' );
		shareBtn.type = 'button';
		downloadBtn = el( 'button', 'hw-pc__btn' );
		downloadBtn.type = 'button';
		actions.appendChild( shareBtn );
		actions.appendChild( downloadBtn );

		var note = el( 'p', 'hw-pc__note' );

		box.appendChild( close );
		box.appendChild( title );
		box.appendChild( preview );
		box.appendChild( label );
		box.appendChild( field );
		box.appendChild( count );
		box.appendChild( actions );
		box.appendChild( note );
		modal.appendChild( box );
		document.body.appendChild( modal );

		modal.addEventListener( 'click', function ( event ) {
			if ( event.target === modal ) {
				hide();
			}
		} );
		close.addEventListener( 'click', hide );
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && modal && ! modal.hidden ) {
				hide();
			}
		} );

		field.addEventListener( 'input', function () {
			count.textContent = field.value.length + ' / ' + MAX;
			redraw();
		} );

		downloadBtn.addEventListener( 'click', function () {
			track( 'postcard_download', 'Downloaded' );
			toBlob().then( function ( blob ) {
				if ( ! blob ) {
					return;
				}
				var url = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = url;
				a.download = fileName();
				document.body.appendChild( a );
				a.click();
				a.remove();
				setTimeout( function () { URL.revokeObjectURL( url ); }, 4000 );
			} );
		} );

		shareBtn.addEventListener( 'click', function () {
			toBlob().then( function ( blob ) {
				if ( ! blob ) {
					return;
				}
				var file = new File( [ blob ], fileName(), { type: 'image/jpeg' } );
				// Counted only once the reader has actually picked an app,
				// not if they close the share menu.
				navigator.share( { files: [ file ], title: data.labels.greetings + ' ' + data.place } ).then( function () {
					track( 'postcard_share', 'Shared' );
				} ).catch( function () {} );
			} );
		} );
	}

	/**
	 * Counts a postcard action twice over: in Pages > Hub report (section
	 * "Postcard maker") and in Google Analytics, as an hw_hub_click event
	 * with link_type postcard_open, postcard_download or postcard_share.
	 */
	function track( type, report ) {
		if ( window.hwHubStatsTrack ) {
			window.hwHubStatsTrack( 'postcard', report );
		}

		var hub = document.getElementById( 'hw-hub' );
		var params = {
			link_type: type,
			link_text: data.place,
			link_url: '',
			hub_type: hub ? ( hub.className.match( /hw-hub--(\w+)/ ) || [] )[ 1 ] || '' : '',
			hub_title: document.title
		};

		if ( window.google_tag_manager || ! window.gtag ) {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( Object.assign( { event: 'hw_hub_click' }, params ) );
		} else {
			window.gtag( 'event', 'hw_hub_click', params );
		}
	}

	function canShareFiles() {
		try {
			var test = new File( [ 'x' ], 'x.jpg', { type: 'image/jpeg' } );
			return !! ( navigator.canShare && navigator.share && navigator.canShare( { files: [ test ] } ) );
		} catch ( e ) {
			return false;
		}
	}

	function show( button ) {
		try {
			data = JSON.parse( button.getAttribute( 'data-hw-postcard' ) );
		} catch ( e ) {
			return;
		}

		if ( ! modal ) {
			build();
		}

		opener = button;
		track( 'postcard_open', 'Opened' );
		photo = null;
		logo = null;

		modal.querySelector( '.hw-pc__title' ).textContent = data.labels.title;
		modal.querySelector( '.hw-pc__close' ).setAttribute( 'aria-label', data.labels.close );
		modal.querySelector( '.hw-pc__label' ).textContent = data.labels.message;
		modal.querySelector( '.hw-pc__note' ).textContent = data.labels.note;
		canvas.setAttribute( 'aria-label', data.labels.greetings + ' ' + data.place );
		field.placeholder = data.labels.placeholder;
		count.textContent = field.value.length + ' / ' + MAX;
		shareBtn.textContent = data.labels.share;
		shareBtn.hidden = ! canShareFiles();
		downloadBtn.textContent = data.labels.download;
		downloadBtn.className = 'hw-pc__btn' + ( shareBtn.hidden ? '' : ' hw-pc__btn--ghost' );
		shareBtn.disabled = true;
		downloadBtn.disabled = true;
		status.textContent = '…';
		status.hidden = false;
		ctx.clearRect( 0, 0, W, H );

		modal.hidden = false;
		document.documentElement.classList.add( 'hw-pc-open' );
		field.focus( { preventScroll: true } );

		var current = data;

		Promise.all( [
			loadFonts(),
			loadImage( data.photo ),
			loadImage( data.logo ).catch( function () { return null; } )
		] ).then( function ( results ) {
			if ( current !== data ) {
				return;
			}
			photo = results[ 1 ];
			logo = results[ 2 ];
			status.hidden = true;
			shareBtn.disabled = false;
			downloadBtn.disabled = false;
			draw();
		} ).catch( function () {
			if ( current === data ) {
				status.textContent = data.labels.error;
			}
		} );
	}

	function hide() {
		modal.hidden = true;
		document.documentElement.classList.remove( 'hw-pc-open' );
		if ( opener ) {
			opener.focus( { preventScroll: true } );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-hw-postcard]' ) : null;
		if ( button ) {
			show( button );
		}
	} );
}() );
