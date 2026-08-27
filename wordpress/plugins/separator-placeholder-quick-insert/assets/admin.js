( function ( $ ) {
	'use strict';

	$( function () {
		var $list = $( '#spqi-list' );
		var frame;

		function tile( a ) {
			var thumb = a.url;
			if ( a.sizes ) {
				if ( a.sizes.medium && a.sizes.medium.url ) {
					thumb = a.sizes.medium.url;
				} else if ( a.sizes.thumbnail && a.sizes.thumbnail.url ) {
					thumb = a.sizes.thumbnail.url;
				}
			}
			var $li = $( '<li>' )
				.attr( 'data-id', a.id )
				.attr( 'data-full', a.url )
				.attr( 'data-title', a.title || '' );
			$( '<img>' ).attr( { src: thumb, alt: a.alt || '' } ).appendTo( $li );
			$( '<button type="button" class="spqi-remove">' )
				.attr( 'aria-label', SPQI_ADMIN.removeAria )
				.html( '&times;' )
				.appendTo( $li );
			$( '<input type="hidden" name="ids[]">' ).val( a.id ).appendTo( $li );
			return $li;
		}

		$( '#spqi-add' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			frame = wp.media( {
				title: SPQI_ADMIN.pickTitle,
				button: { text: SPQI_ADMIN.pickButton },
				library: { type: 'image' },
				multiple: 'add',
			} );
			frame.on( 'select', function () {
				$list.find( '.spqi-empty-state' ).remove();
				frame.state().get( 'selection' ).each( function ( att ) {
					var a = att.toJSON();
					if ( $list.find( 'li[data-id="' + a.id + '"]' ).length ) {
						return;
					}
					$list.append( tile( a ) );
				} );
			} );
			frame.open();
		} );

		$list.on( 'click', '.spqi-remove', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( this ).closest( 'li' ).remove();
		} );

		// ---------- Lightbox ----------
		var $lb = null;
		var current = -1;

		function tiles() {
			return $list.find( 'li[data-full]' ).toArray();
		}

		function buildLightbox() {
			$lb = $(
				'<div class="spqi-lightbox" role="dialog" aria-modal="true">' +
					'<button type="button" class="spqi-lb-close" aria-label="' + SPQI_ADMIN.closeAria + '">&times;</button>' +
					'<button type="button" class="spqi-lb-prev" aria-label="' + SPQI_ADMIN.prevAria + '">&#10094;</button>' +
					'<button type="button" class="spqi-lb-next" aria-label="' + SPQI_ADMIN.nextAria + '">&#10095;</button>' +
					'<figure class="spqi-lb-figure">' +
						'<img class="spqi-lb-image" alt="">' +
						'<figcaption class="spqi-lb-caption"></figcaption>' +
					'</figure>' +
				'</div>'
			).appendTo( document.body );

			$lb.on( 'click', function ( e ) {
				if ( e.target === $lb[0] ) { closeLb(); }
			} );
			$lb.find( '.spqi-lb-close' ).on( 'click', closeLb );
			$lb.find( '.spqi-lb-prev' ).on( 'click', function () { step( -1 ); } );
			$lb.find( '.spqi-lb-next' ).on( 'click', function () { step( 1 ); } );
		}

		function showAt( idx ) {
			var arr = tiles();
			if ( ! arr.length ) { closeLb(); return; }
			if ( idx < 0 ) { idx = arr.length - 1; }
			if ( idx >= arr.length ) { idx = 0; }
			current = idx;
			var $li = $( arr[ idx ] );
			$lb.find( '.spqi-lb-image' )
				.attr( 'src', $li.data( 'full' ) )
				.attr( 'alt', $li.find( 'img' ).attr( 'alt' ) || '' );
			$lb.find( '.spqi-lb-caption' ).text(
				( $li.attr( 'data-title' ) || '' ) +
				' (' + ( idx + 1 ) + ' / ' + arr.length + ')'
			);
			var multi = arr.length > 1;
			$lb.find( '.spqi-lb-prev, .spqi-lb-next' ).toggle( multi );
		}

		function step( delta ) {
			showAt( current + delta );
		}

		function openLb( idx ) {
			if ( ! $lb ) { buildLightbox(); }
			$lb.addClass( 'is-open' );
			$( document ).on( 'keydown.spqilb', function ( e ) {
				if ( 27 === e.keyCode ) { closeLb(); }
				else if ( 37 === e.keyCode ) { step( -1 ); }
				else if ( 39 === e.keyCode ) { step( 1 ); }
			} );
			showAt( idx );
		}

		function closeLb() {
			if ( ! $lb ) { return; }
			$lb.removeClass( 'is-open' );
			$( document ).off( 'keydown.spqilb' );
		}

		$list.on( 'click', 'img', function ( e ) {
			e.preventDefault();
			var $li = $( this ).closest( 'li' );
			var arr = tiles();
			var idx = arr.indexOf( $li[0] );
			if ( idx < 0 ) { return; }
			openLb( idx );
		} );
	} );
} )( jQuery );
