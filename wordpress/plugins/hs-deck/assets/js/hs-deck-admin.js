( function () {
	const MODE_SECTIONS = {
		standard: 'fields_standard',
		total: 'fields_total',
		winrate: 'fields_winrate',
	};

	function onDomReady( callback ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	}

	function setStatsModeSections( mode ) {
		Object.keys( MODE_SECTIONS ).forEach( function ( key ) {
			const section = document.getElementById( MODE_SECTIONS[ key ] );
			if ( section ) {
				section.classList.toggle( 'is-active', key === mode );
			}
		} );
	}

	function initStatsMode() {
		const radios = document.querySelectorAll( 'input[name="stats_mode"]' );
		if ( ! radios.length ) {
			return;
		}

		let selectedMode = 'standard';
		radios.forEach( function ( radio ) {
			if ( radio.checked ) {
				selectedMode = radio.value;
			}
			radio.addEventListener( 'change', function () {
				setStatsModeSections( radio.value || 'standard' );
			} );
		} );

		setStatsModeSections( selectedMode );
	}

	function bindSelectOnFocusAndClick( input ) {
		if ( ! input ) {
			return;
		}

		const selectValue = function () {
			input.select();
		};

		input.addEventListener( 'focus', selectValue );
		input.addEventListener( 'click', selectValue );
	}

	function initShortcodeCopy() {
		bindSelectOnFocusAndClick(
			document.querySelector( '.hs-shortcode-copy' )
		);
	}

	function initAnnouncementMedia() {
		const button = document.getElementById( 'ann_img_btn' );
		const input = document.getElementById( 'ann_img' );

		if ( ! button || ! input || typeof wp === 'undefined' || ! wp.media ) {
			return;
		}

		button.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			const frame = wp.media( {
				title: 'Select image',
				multiple: false,
				button: {
					text: 'Use image',
				},
			} );

			frame.on( 'select', function () {
				const selected = frame.state().get( 'selection' ).first();
				if ( ! selected ) {
					return;
				}

				const file = selected.toJSON();
				if ( file && file.url ) {
					input.value = file.url;
				}
			} );

			frame.open();
		} );
	}

	function init() {
		initStatsMode();
		initShortcodeCopy();
		initAnnouncementMedia();
	}

	onDomReady( init );
} )();
