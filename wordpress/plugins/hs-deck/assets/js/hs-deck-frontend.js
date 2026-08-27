/* global GLightbox, hsDeckAjax */
( function () {
	const SEARCH_DEBOUNCE_MS = 120;
	const CARD_SELECTOR = '.hs-card';

	function onDomReady( callback ) {
		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', callback );
			return;
		}

		callback();
	}

	function debounce( callback, waitMs ) {
		let timerId = null;

		return function () {
			const context = this;
			const args = arguments;

			if ( timerId ) {
				clearTimeout( timerId );
			}

			timerId = setTimeout( function () {
				callback.apply( context, args );
			}, waitMs );
		};
	}

	function getWrapperControls( wrapper ) {
		if ( ! wrapper ) {
			return null;
		}

		const grid = wrapper.querySelector( '[id$="-grid"]' );
		if ( ! grid ) {
			return null;
		}

		return {
			grid,
			searchInput: wrapper.querySelector( '.hs-search' ),
			classSelect: wrapper.querySelector( '.hs-filter-class' ),
			modeSelect: wrapper.querySelector( '.hs-filter-mode' ),
		};
	}

	function getSearchModel( card ) {
		if ( card.dataset.hsFilterReady !== '1' ) {
			const titleNode = card.querySelector( '.hs-card-title' );
			card.dataset.hsFilterTitle = titleNode
				? ( titleNode.textContent || '' ).toLowerCase()
				: '';
			card.dataset.hsFilterAuthor = (
				card.getAttribute( 'data-author' ) || ''
			).toLowerCase();
			card.dataset.hsFilterClass =
				card.getAttribute( 'data-class' ) || '';
			card.dataset.hsFilterMode = card.getAttribute( 'data-mode' ) || '';
			card.dataset.hsFilterReady = '1';
		}

		return {
			title: card.dataset.hsFilterTitle || '',
			author: card.dataset.hsFilterAuthor || '',
			deckClass: card.dataset.hsFilterClass || '',
			deckMode: card.dataset.hsFilterMode || '',
		};
	}

	function matchesFilters( model, filters ) {
		const matchesQuery =
			model.title.indexOf( filters.query ) !== -1 ||
			model.author.indexOf( filters.query ) !== -1;
		const matchesClass =
			! filters.deckClass ||
			model.deckClass.indexOf( filters.deckClass ) !== -1;
		const matchesMode =
			! filters.deckMode ||
			model.deckMode.indexOf( filters.deckMode ) !== -1;

		return matchesQuery && matchesClass && matchesMode;
	}

	function applyWrapperFilter( wrapper ) {
		const controls = getWrapperControls( wrapper );
		if ( ! controls ) {
			return;
		}

		const filters = {
			query:
				controls.searchInput && controls.searchInput.value
					? controls.searchInput.value.toLowerCase()
					: '',
			deckClass:
				controls.classSelect && controls.classSelect.value
					? controls.classSelect.value
					: '',
			deckMode:
				controls.modeSelect && controls.modeSelect.value
					? controls.modeSelect.value
					: '',
		};

		controls.grid
			.querySelectorAll( CARD_SELECTOR )
			.forEach( function ( card ) {
				const shouldHide = ! matchesFilters(
					getSearchModel( card ),
					filters
				);
				if ( card.classList.contains( 'hs-hidden' ) !== shouldHide ) {
					card.classList.toggle( 'hs-hidden', shouldHide );
				}
			} );
	}

	function bindFilterControl( control, eventName, callback ) {
		if ( ! control || control.dataset.hsInit === '1' ) {
			return;
		}

		control.dataset.hsInit = '1';
		control.addEventListener( eventName, callback );
	}

	function initFilters() {
		document
			.querySelectorAll( '.hs-wrapper' )
			.forEach( function ( wrapper ) {
				const controls = getWrapperControls( wrapper );
				if ( ! controls ) {
					return;
				}

				bindFilterControl(
					controls.searchInput,
					'input',
					debounce( function () {
						applyWrapperFilter( wrapper );
					}, SEARCH_DEBOUNCE_MS )
				);
				bindFilterControl( controls.classSelect, 'change', function () {
					applyWrapperFilter( wrapper );
				} );
				bindFilterControl( controls.modeSelect, 'change', function () {
					applyWrapperFilter( wrapper );
				} );
			} );
	}

	function initSliders() {
		document
			.querySelectorAll( '.hs-slider-wrap' )
			.forEach( function ( wrapper ) {
				if ( wrapper.dataset.hsSliderInit === '1' ) {
					return;
				}
				wrapper.dataset.hsSliderInit = '1';

				const track = wrapper.querySelector( '.hs-slider-track' );
				const dots = Array.prototype.slice.call(
					wrapper.querySelectorAll( '.hs-slider-dot' )
				);
				const slidesCount = dots.length;

				if ( ! track || ! slidesCount ) {
					return;
				}

				const prevButton = wrapper.querySelector( '.hs-slider-prev' );
				const nextButton = wrapper.querySelector( '.hs-slider-next' );
				let currentSlide = 0;

				function goToSlide( index ) {
					currentSlide = Math.max(
						0,
						Math.min( slidesCount - 1, index )
					);
					track.style.transform =
						'translateX(-' + currentSlide * 100 + '%)';
					dots.forEach( function ( dot, dotIndex ) {
						dot.classList.toggle(
							'active',
							dotIndex === currentSlide
						);
					} );
				}

				if ( prevButton ) {
					prevButton.addEventListener( 'click', function () {
						goToSlide( currentSlide - 1 );
					} );
				}

				if ( nextButton ) {
					nextButton.addEventListener( 'click', function () {
						goToSlide( currentSlide + 1 );
					} );
				}

				dots.forEach( function ( dot ) {
					dot.addEventListener( 'click', function () {
						goToSlide(
							parseInt( dot.getAttribute( 'data-slide' ), 10 ) ||
								0
						);
					} );
				} );
			} );
	}

	function initLightbox() {
		if ( typeof GLightbox === 'undefined' ) {
			return;
		}

		if ( ! document.querySelector( '.hs-glightbox' ) ) {
			return;
		}

		if ( ! window.hsLightbox ) {
			window.hsLightbox = GLightbox( {
				selector: '.hs-glightbox',
				touchNavigation: true,
				loop: true,
				zoomable: true,
			} );
			return;
		}

		if ( typeof window.hsLightbox.reload === 'function' ) {
			window.hsLightbox.reload();
		}
	}

	function postForm( url, params, timeoutMs ) {
		const controller =
			typeof AbortController !== 'undefined'
				? new AbortController()
				: null;
		let timeoutId = null;

		const requestOptions = {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: params,
		};

		if ( controller ) {
			requestOptions.signal = controller.signal;
			timeoutId = setTimeout( function () {
				controller.abort();
			}, timeoutMs || 15000 );
		}

		return fetch( url, requestOptions ).finally( function () {
			if ( timeoutId ) {
				clearTimeout( timeoutId );
			}
		} );
	}

	function sendTrackEvent( eventType, postId ) {
		if ( ! postId || typeof hsDeckAjax === 'undefined' ) {
			return;
		}

		const params = new URLSearchParams();
		params.append( 'action', 'hs_deck_manager_track' );
		params.append( 'event_type', eventType );
		params.append( 'post_id', postId );
		params.append( 'nonce', hsDeckAjax.nonce );

		postForm( hsDeckAjax.url, params, 8000 ).catch( function () {
			return null;
		} );
	}

	function setCopyDoneState( button, originalHtml, doneText ) {
		button.classList.add( 'hs-copy-done' );
		button.textContent = doneText;
		setTimeout( function () {
			button.classList.remove( 'hs-copy-done' );
			button.innerHTML = originalHtml;
		}, 1500 );
	}

	function copyToClipboard( text ) {
		if (
			window.navigator.clipboard &&
			window.navigator.clipboard.writeText
		) {
			return window.navigator.clipboard.writeText( text );
		}

		return new Promise( function ( resolve, reject ) {
			try {
				const textarea = document.createElement( 'textarea' );
				textarea.value = text;
				document.body.appendChild( textarea );
				textarea.select();
				document.execCommand( 'copy' );
				document.body.removeChild( textarea );
				resolve();
			} catch ( error ) {
				reject( error );
			}
		} );
	}

	function handleCopyClick( button ) {
		const code = button.getAttribute( 'data-code' ) || '';
		const postId = button.getAttribute( 'data-post-id' ) || '';
		const originalHtml = button.innerHTML;
		const doneText = button.getAttribute( 'data-done' ) || 'Done';

		copyToClipboard( code )
			.then( function () {
				setCopyDoneState( button, originalHtml, doneText );
				sendTrackEvent( 'copy', postId );
			} )
			.catch( function () {
				return null;
			} );
	}

	function hideLoadMoreButton( button ) {
		if ( button.parentElement ) {
			button.parentElement.style.display = 'none';
		}
	}

	function appendCardsHtml( grid, html ) {
		const container = document.createElement( 'div' );
		container.innerHTML = html;

		const fragment = document.createDocumentFragment();
		while ( container.firstChild ) {
			fragment.appendChild( container.firstChild );
		}

		grid.appendChild( fragment );
	}

	function createLoadMoreParams( button, lastCard ) {
		const params = new URLSearchParams();
		params.append( 'action', 'hs_deck_manager_load_more' );
		params.append(
			'per_page',
			button.getAttribute( 'data-per-page' ) || '10'
		);
		params.append( 'order', button.getAttribute( 'data-order' ) || 'date' );
		params.append(
			'last_id',
			lastCard ? lastCard.getAttribute( 'data-post-id' ) || '0' : '0'
		);
		params.append(
			'last_date',
			lastCard ? lastCard.getAttribute( 'data-date' ) || '' : ''
		);
		params.append(
			'last_winrate',
			lastCard ? lastCard.getAttribute( 'data-winrate' ) || '0' : '0'
		);
		params.append( 'nonce', hsDeckAjax.nonce );
		return params;
	}

	function handleLoadMoreClick( button ) {
		if ( button.disabled || typeof hsDeckAjax === 'undefined' ) {
			return;
		}

		const feedId = button.getAttribute( 'data-feed-id' );
		const grid = feedId
			? document.getElementById( feedId + '-grid' )
			: null;
		if ( ! grid ) {
			return;
		}

		const totalPages =
			parseInt( button.getAttribute( 'data-total-pages' ), 10 ) || 1;
		const currentPage =
			parseInt( button.getAttribute( 'data-page-reached' ), 10 ) || 1;

		if ( currentPage >= totalPages ) {
			hideLoadMoreButton( button );
			return;
		}

		const lastCard = grid.querySelector( CARD_SELECTOR + ':last-of-type' );
		const originalText = button.textContent;
		button.classList.remove( 'hs-load-more-error' );
		button.disabled = true;
		button.textContent = 'Loading...';

		postForm(
			hsDeckAjax.url,
			createLoadMoreParams( button, lastCard ),
			15000
		)
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Load more HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success ) {
					if ( payload && payload.data === 'No more decks' ) {
						hideLoadMoreButton( button );
						button.setAttribute(
							'data-page-reached',
							String( totalPages )
						);
						return;
					}
					throw new Error( 'Invalid load more payload' );
				}

				if ( ! payload.data ) {
					return;
				}

				appendCardsHtml( grid, payload.data );

				const nextPage = currentPage + 1;
				button.setAttribute( 'data-page-reached', String( nextPage ) );
				if ( nextPage >= totalPages ) {
					hideLoadMoreButton( button );
				}

				const wrapper = grid.closest( '.hs-wrapper' );
				if ( wrapper ) {
					applyWrapperFilter( wrapper );
				}

				initLightbox();
			} )
			.catch( function () {
				button.classList.add( 'hs-load-more-error' );
			} )
			.finally( function () {
				button.disabled = false;
				button.textContent = originalText;
			} );
	}

	function initGlobalEvents() {
		if ( document.body.dataset.hsDeckGlobalInit === '1' ) {
			return;
		}
		document.body.dataset.hsDeckGlobalInit = '1';

		document.addEventListener( 'click', function ( event ) {
			const copyButton = event.target.closest( '.hs-copy-code-btn' );
			if ( copyButton ) {
				handleCopyClick( copyButton );
				return;
			}

			const loadMoreButton = event.target.closest( '.hs-load-more-btn' );
			if ( loadMoreButton ) {
				handleLoadMoreClick( loadMoreButton );
			}
		} );
	}

	function init() {
		initFilters();
		initSliders();
		initLightbox();
		initGlobalEvents();
	}

	onDomReady( init );
} )();
