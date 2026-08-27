( function () {
	'use strict';

	var cfg  = window.SPQI_CLASSIC || {};
	var i18n = cfg.i18n || {};
	var cache = null;
	var fetching = null;

	function loadItems() {
		if ( cache ) { return Promise.resolve( cache ); }
		if ( fetching ) { return fetching; }
		if ( window.wp && window.wp.apiFetch ) {
			fetching = wp.apiFetch( { path: '/spqi/v1/items' } );
		} else {
			fetching = fetch( cfg.restUrl, {
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': cfg.nonce },
			} ).then( function ( r ) { return r.json(); } );
		}
		fetching = fetching.then( function ( data ) {
			cache = data || { separators: [], placeholders: [], misc: [], preview: [], descriptions: {} };
			if ( ! cache.descriptions ) { cache.descriptions = {}; }
			return cache;
		} ).catch( function () {
			cache = { separators: [], placeholders: [], misc: [], preview: [], descriptions: {} };
			return cache;
		} );
		return fetching;
	}

	function el( tag, attrs, children ) {
		var node = document.createElement( tag );
		if ( attrs ) {
			Object.keys( attrs ).forEach( function ( k ) {
				if ( k === 'className' ) { node.className = attrs[k]; }
				else if ( k === 'onClick' ) { node.addEventListener( 'click', attrs[k] ); }
				else if ( k === 'text' ) { node.textContent = attrs[k]; }
				else { node.setAttribute( k, attrs[k] ); }
			} );
		}
		( children || [] ).forEach( function ( c ) {
			if ( c == null ) { return; }
			node.appendChild( typeof c === 'string' ? document.createTextNode( c ) : c );
		} );
		return node;
	}

	function buildGrid( items, onPick ) {
		if ( ! items || ! items.length ) {
			return el( 'div', { className: 'spqi-c-empty' }, [ i18n.empty || '' ] );
		}
		var grid = el( 'div', { className: 'spqi-c-grid' } );
		items.forEach( function ( it ) {
			var img = el( 'img', { src: it.thumb, alt: it.alt || '', loading: 'lazy' } );
			var btn = el( 'button', {
				type: 'button',
				className: 'spqi-c-tile',
				title: it.title || '',
				'aria-label': it.alt || it.title || '',
				onClick: function () { onPick( it ); },
			}, [ img ] );
			grid.appendChild( btn );
		} );
		return grid;
	}

	function openPicker( editor ) {
		var overlay = el( 'div', { className: 'spqi-c-overlay', role: 'dialog', 'aria-modal': 'true', 'aria-label': i18n.title || '' } );
		var modal   = el( 'div', { className: 'spqi-c-modal' } );

		var header = el( 'div', { className: 'spqi-c-header' }, [
			el( 'h2', { className: 'spqi-c-title', text: i18n.title || '' } ),
			el( 'button', {
				type: 'button',
				className: 'spqi-c-close',
				'aria-label': i18n.close || 'Close',
				onClick: function () { close(); },
			}, [ '×' ] ),
		] );

		var activeTab = 'separators';
		var tabs = el( 'div', { className: 'spqi-c-tabs', role: 'tablist' } );
		var body = el( 'div', { className: 'spqi-c-body' }, [
			el( 'div', { className: 'spqi-c-loading', text: i18n.loading || '' } ),
		] );

		function pick( item ) {
			var html = '<img src="' + escapeAttr( item.url ) + '" alt="' + escapeAttr( item.alt || '' ) + '" class="wp-image-' + parseInt( item.id, 10 ) + '" />';
			if ( editor && editor.insertContent ) {
				editor.insertContent( html );
			}
			close();
		}

		function setTab( name ) {
			activeTab = name;
			Array.prototype.forEach.call( tabs.querySelectorAll( '.spqi-c-tab' ), function ( b ) {
				var on = b.getAttribute( 'data-tab' ) === name;
				b.classList.toggle( 'is-active', on );
				b.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			} );
			loadItems().then( function ( data ) {
				body.innerHTML = '';
				var desc = data.descriptions && data.descriptions[ name ] ? data.descriptions[ name ] : '';
				if ( desc ) {
					body.appendChild( el( 'p', { className: 'spqi-c-desc', text: desc } ) );
				}
				body.appendChild( buildGrid( data[ name ] || [], pick ) );
			} );
		}

		[
			[ 'separators',   i18n.separators   || 'Separators' ],
			[ 'placeholders', i18n.placeholders || 'Placeholders' ],
			[ 'misc',         i18n.misc         || 'Misc' ],
			[ 'preview',      i18n.preview      || 'Preview' ],
		].forEach( function ( pair, idx ) {
			var b = el( 'button', {
				type: 'button',
				className: 'spqi-c-tab' + ( 0 === idx ? ' is-active' : '' ),
				role: 'tab',
				'data-tab': pair[0],
				'aria-selected': 0 === idx ? 'true' : 'false',
				text: pair[1],
				onClick: function () { setTab( pair[0] ); },
			} );
			tabs.appendChild( b );
		} );

		modal.appendChild( header );
		modal.appendChild( tabs );
		modal.appendChild( body );
		overlay.appendChild( modal );
		document.body.appendChild( overlay );

		function onKey( e ) { if ( 27 === e.keyCode ) { close(); } }
		function onOverlayClick( e ) { if ( e.target === overlay ) { close(); } }

		function close() {
			document.removeEventListener( 'keydown', onKey );
			overlay.removeEventListener( 'click', onOverlayClick );
			if ( overlay.parentNode ) { overlay.parentNode.removeChild( overlay ); }
		}

		document.addEventListener( 'keydown', onKey );
		overlay.addEventListener( 'click', onOverlayClick );

		setTab( activeTab );
	}

	function escapeAttr( s ) {
		return String( s )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	}

	window.SPQI = window.SPQI || {};
	window.SPQI.openPicker = openPicker;
} )();
