( function () {
	'use strict';
	const requestTimeoutMs = 7000;
	const favoriteId = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

	const endpoint = ( root, name, fallback ) => {
		const value = root.dataset[ name ] || fallback;
		try { return value.startsWith( '/' ) && new URL( value, window.location.origin ).origin === window.location.origin ? value : fallback; }
		catch ( error ) { return fallback; }
	};
	const text = ( value ) => typeof value === 'string' ? value : '';

	function allowedProfileUrl( value ) {
		try {
			const url = new URL( value );
			return url.origin === 'https://hearthpulse.net' && ! url.username && ! url.password ? url.href : null;
		} catch ( error ) {
			return null;
		}
	}

	function allowedArticlePath( value ) {
		if ( typeof value !== 'string' || ! /^\/(?!\/)/.test( value ) || /[\\\s?#%\u0000-\u001f\u007f]/.test( value ) ) return null;
		try {
			const url = new URL( value, window.location.origin );
			return url.origin === window.location.origin && url.pathname === value ? value : null;
		} catch ( error ) {
			return null;
		}
	}

	function favorite( value ) {
		const path = allowedArticlePath( value?.path );
		if ( ! value || ! favoriteId.test( value.id || '' ) || ! Number.isSafeInteger( value.postId ) || value.postId < 1
			|| typeof value.title !== 'string' || ! value.title.trim() || value.title.length > 1000
			|| ! path || ! Number.isSafeInteger( value.createdAt ) || value.createdAt < 0 ) return null;
		return { id: value.id, postId: value.postId, title: value.title.trim(), path, createdAt: value.createdAt };
	}

	function init( root ) {
		const status = root.querySelector( '[data-reader-status]' );
		const identity = root.querySelector( '[data-reader-identity]' );
		const actions = root.querySelector( '[data-reader-actions]' );
		const accountMenu = root.querySelector( '[data-reader-account-menu]' );
		const accountActions = root.querySelector( '[data-reader-account-actions]' );
		const administrator = root.querySelector( '[data-reader-administrator]' );
		const paid = root.querySelector( '[data-reader-paid]' );
		const profileOverview = root.querySelector( '[data-reader-profile-overview]' );
		const favoritesPanel = root.querySelector( '[data-reader-favorites]' );
		const favoritesSentinel = root.querySelector( '[data-reader-favorites-sentinel]' );
		const favoritesStatus = root.querySelector( '[data-reader-favorites-status]' );
		const favoritesList = root.querySelector( '[data-reader-favorites-list]' );
		const favoritesMore = root.querySelector( '[data-reader-favorites-more]' );
		const communityData = root.querySelector( '[data-reader-community-data]' );
		const communityDataStatus = root.querySelector( '[data-reader-community-data-status]' );
		const commentsExport = root.querySelector( '[data-reader-comments-export]' );
		const commentsErase = root.querySelector( '[data-reader-comments-erase]' );
		const loginEndpoint = endpoint( root, 'loginEndpoint', '/reader-auth/start?returnTo=%2Faccount%2F' );
		const logoutEndpoint = endpoint( root, 'logoutEndpoint', '/reader-auth/logout' );
		const meEndpoint = endpoint( root, 'meEndpoint', '/reader-api/v1/bootstrap' );
		const profileEndpoint = endpoint( root, 'profileEndpoint', '/reader-api/v1/profile' );
		const avatarEndpoint = endpoint( root, 'avatarEndpoint', '/reader-api/v1/profile/avatar' );
		let controller = null;
		let generation = 0;
		let logoutInFlight = false;
		let logoutController = null;
		let logoutGeneration = 0;
		let sessionActive = false;
		let currentCsrfToken = '';
		let profileEditor = null;
		let permissionsController = null;
		let favoritesController = null;
		let favoritesRows = [];
		let favoritesCursor = null;
		let favoritesLoaded = false;
		let favoritesOwnerId = '';
		let favoritesObserver = null;
		let favoritesScheduleCancel = null;
		let communityDataController = null;
		let communityDataBusy = false;
		let currentProfileId = '';

		function clearAdministrator() {
			permissionsController?.abort();
			permissionsController = null;
			if ( administrator ) administrator.hidden = true;
			if ( paid ) paid.hidden = true;
		}

		function cancelDeferredFavoritesLoad() {
			favoritesObserver?.disconnect();
			favoritesObserver = null;
			favoritesScheduleCancel?.();
			favoritesScheduleCancel = null;
		}

		function scheduleFavoritesLoad() {
			if ( ! sessionActive || favoritesLoaded || favoritesController || favoritesScheduleCancel ) return;
			const load = () => {
				favoritesScheduleCancel = null;
				if ( sessionActive && ! favoritesLoaded && ! favoritesController ) void loadFavorites();
			};
			if ( 'requestIdleCallback' in window ) {
				const handle = window.requestIdleCallback( load, { timeout: 750 } );
				favoritesScheduleCancel = () => window.cancelIdleCallback?.( handle );
			} else {
				const handle = window.setTimeout( load, 0 );
				favoritesScheduleCancel = () => window.clearTimeout( handle );
			}
		}

		function revealFavorites() {
			if ( ! favoritesPanel || ! sessionActive ) return;
			favoritesPanel.hidden = false;
			cancelDeferredFavoritesLoad();
			if ( favoritesLoaded || favoritesController ) return;
			if ( ! favoritesSentinel || ! ( 'IntersectionObserver' in window ) ) {
				scheduleFavoritesLoad();
				return;
			}
			favoritesObserver = new IntersectionObserver( entries => {
				if ( ! entries.some( entry => entry.isIntersecting ) ) return;
				favoritesObserver?.disconnect();
				favoritesObserver = null;
				scheduleFavoritesLoad();
			}, { rootMargin: '320px 0px' } );
			favoritesObserver.observe( favoritesSentinel );
		}

		function clearFavorites() {
			cancelDeferredFavoritesLoad();
			favoritesController?.abort();
			favoritesController = null;
			favoritesRows = [];
			favoritesCursor = null;
			favoritesLoaded = false;
			favoritesOwnerId = '';
			favoritesList?.replaceChildren();
			if ( favoritesStatus ) favoritesStatus.textContent = '';
			if ( favoritesMore ) {
				favoritesMore.hidden = true;
				favoritesMore.disabled = false;
				favoritesMore.textContent = 'Показать ещё';
			}
			if ( favoritesPanel ) favoritesPanel.hidden = true;
		}

		function setCommunityDataBusy( busy ) {
			communityDataBusy = busy;
			if ( commentsExport ) commentsExport.disabled = busy;
			if ( commentsErase ) commentsErase.disabled = busy;
			communityData?.setAttribute( 'aria-busy', String( busy ) );
		}

		async function communityDataRequest( url, options = {} ) {
			communityDataController?.abort();
			const requestController = new AbortController();
			communityDataController = requestController;
			const deadline = window.setTimeout( () => requestController.abort(), requestTimeoutMs );
			try {
				const response = await fetch( url, { ...options, credentials: 'same-origin', cache: 'no-store', signal: requestController.signal } );
				const body = await response.text();
				if ( body.length > 524288 ) throw new Error( 'response_too_large' );
				return { response, data: body ? JSON.parse( body ) : null };
			} finally { window.clearTimeout( deadline ); if ( communityDataController === requestController ) communityDataController = null; }
		}

		async function exportComments() {
			if ( ! sessionActive || communityDataBusy ) return;
			const ticket = generation, items = [], seen = new Set();
			let next = null, complete = false, reactions = [];
			setCommunityDataBusy( true );
			communityDataStatus.textContent = 'Готовим выгрузку…';
			try {
				for ( let page = 0; page < 50; page++ ) {
					const { response, data } = await communityDataRequest( `/reader-api/v1/community/export${ next ? `?cursor=${ encodeURIComponent( next ) }` : '' }`, { headers: { Accept: 'application/json' } } );
					if ( response.status === 401 ) { guest( 'Сессия завершена. Войдите через HearthPulse снова.' ); return; }
					if ( ! response.ok || ! Array.isArray( data?.items ) || data.items.length > 100 ) throw new Error( 'export_failed' );
					if ( 0 === page && data.reactions !== undefined ) {
						if ( ! Array.isArray( data.reactions ) || data.reactions.length > 1000 ) throw new Error( 'export_failed' );
						reactions = data.reactions;
					}
					items.push( ...data.items );
					if ( data.nextCursor === null ) { complete = true; break; }
					if ( ! favoriteId.test( data.nextCursor || '' ) || seen.has( data.nextCursor ) ) throw new Error( 'export_cursor' );
					next = data.nextCursor;
					seen.add( next );
				}
				if ( ! complete || ticket !== generation || ! sessionActive ) throw new Error( 'export_incomplete' );
				const url = URL.createObjectURL( new Blob( [ JSON.stringify( { items, reactions }, null, 2 ) ], { type: 'application/json' } ) );
				const download = document.createElement( 'a' );
				download.href = url; download.download = 'manacost-comments.json'; download.click();
				window.setTimeout( () => URL.revokeObjectURL( url ), 1000 );
				communityDataStatus.textContent = 'Выгрузка подготовлена.';
			} catch ( error ) { if ( 'AbortError' !== error.name && ticket === generation && sessionActive ) communityDataStatus.textContent = 'Не удалось подготовить полную выгрузку. Ничего не скачано.'; }
			finally { if ( ticket === generation && sessionActive ) setCommunityDataBusy( false ); }
		}

		async function eraseComments() {
			if ( ! sessionActive || communityDataBusy || ! currentProfileId || ! window.confirm( 'Удалить все мои комментарии и публичный профиль? Кабинет читателя останется без изменений.' ) ) return;
			const ticket = generation, profileId = currentProfileId;
			setCommunityDataBusy( true );
			communityDataStatus.textContent = 'Удаляем данные обсуждений…';
			try {
				const { response, data } = await communityDataRequest( '/reader-api/v1/community/profile', {
					method: 'DELETE', body: JSON.stringify( { profileId, confirm: 'erase-community' } ),
					headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Reader-CSRF': currentCsrfToken },
				} );
				if ( response.status === 401 ) { guest( 'Сессия завершена. Войдите через HearthPulse снова.' ); return; }
				if ( ! response.ok || data?.erased !== true ) throw new Error( 'erase_failed' );
				if ( ticket === generation && sessionActive ) communityDataStatus.textContent = 'Комментарии и публичный профиль удалены. Кабинет сохранён.';
			} catch ( error ) { if ( 'AbortError' !== error.name && ticket === generation && sessionActive ) communityDataStatus.textContent = 'Не удалось удалить данные. Повторите попытку позже.'; }
			finally { if ( ticket === generation && sessionActive ) setCommunityDataBusy( false ); }
		}

		function favoriteRow( item ) {
			const row = document.createElement( 'li' );
			row.className = 'mc-reader__favorite';
			row.dataset.favoriteId = item.id;
			const article = document.createElement( 'a' );
			article.className = 'mc-reader__favorite-link';
			article.href = item.path;
			article.textContent = item.title;
			const controls = document.createElement( 'div' );
			controls.className = 'mc-reader__favorite-actions';
			const saved = document.createElement( 'span' );
			saved.className = 'mc-reader__favorite-label';
			saved.textContent = 'Сохранено';
			const remove = actionButton( 'Убрать', 'mc-reader__favorite-remove mc-ui-button mc-ui-button--text' );
			remove.setAttribute( 'aria-label', `Убрать «${ item.title }» из избранного` );
			remove.addEventListener( 'click', () => { void removeFavorite( item, remove ); } );
			controls.append( saved, remove );
			row.append( article, controls );
			return row;
		}

		function renderFavorites() {
			if ( ! favoritesList ) return;
			favoritesList.replaceChildren( ...favoritesRows.map( favoriteRow ) );
			if ( favoritesLoaded && favoritesStatus ) favoritesStatus.textContent = favoritesRows.length ? '' : 'Здесь появятся статьи, которые вы сохраните на сайте.';
			if ( favoritesMore && favoritesLoaded ) {
				favoritesMore.hidden = ! favoritesCursor;
				favoritesMore.disabled = false;
				favoritesMore.textContent = 'Показать ещё';
			}
		}

		function favoriteRequestCurrent( requestController, ticket ) {
			return favoritesController === requestController && sessionActive && ticket === generation && ! requestController.signal.aborted;
		}

		async function loadFavorites( append = false ) {
			if ( ! sessionActive || favoritesController || ! favoritesStatus ) return;
			const ticket = generation;
			const requestController = new AbortController();
			favoritesController = requestController;
			if ( favoritesMore ) {
				favoritesMore.disabled = true;
				favoritesMore.textContent = append ? 'Загружаем…' : 'Загружаем…';
			}
			if ( ! append ) favoritesStatus.textContent = 'Загружаем сохранённые статьи…';
			const deadline = window.setTimeout( () => requestController.abort(), requestTimeoutMs );
			try {
				const query = append && favoritesCursor ? `?cursor=${ encodeURIComponent( favoritesCursor ) }` : '';
				const response = await fetch( `/reader-api/v1/favorites${ query }`, { credentials: 'same-origin', cache: 'no-store', signal: requestController.signal, headers: { Accept: 'application/json' } } );
				const body = await response.text();
				if ( ! favoriteRequestCurrent( requestController, ticket ) ) return;
				if ( response.status === 401 ) { guest( 'Сессия завершена. Войдите через HearthPulse снова.' ); return; }
				if ( ! response.ok || body.length > 65536 ) throw new Error( 'favorites_unavailable' );
				const data = JSON.parse( body );
				if ( ! Array.isArray( data?.items ) || data.items.length > 20 || ( data.nextCursor !== null && ! favoriteId.test( data.nextCursor || '' ) ) ) throw new Error( 'invalid_favorites' );
				const items = data.items.map( favorite ).filter( Boolean );
				if ( items.length !== data.items.length || new Set( items.map( item => item.id ) ).size !== items.length ) throw new Error( 'invalid_favorites' );
				const known = new Set( append ? favoritesRows.map( item => item.id ) : [] );
				favoritesRows = append ? [ ...favoritesRows, ...items.filter( item => ! known.has( item.id ) ) ] : items;
				favoritesCursor = data.nextCursor;
				favoritesLoaded = true;
				renderFavorites();
			} catch ( error ) {
				if ( error.name !== 'AbortError' && favoriteRequestCurrent( requestController, ticket ) ) {
					favoritesStatus.textContent = 'Не удалось загрузить избранное. Повторите попытку.';
					if ( favoritesMore ) {
						favoritesMore.hidden = false;
						favoritesMore.disabled = false;
						favoritesMore.textContent = 'Повторить';
					}
				}
			} finally {
				window.clearTimeout( deadline );
				const retryAfterSessionRefresh = favoritesController === requestController && ! append && sessionActive
					&& ! favoritesLoaded && ticket !== generation && ! requestController.signal.aborted;
				if ( favoritesController === requestController ) favoritesController = null;
				if ( retryAfterSessionRefresh ) queueMicrotask( () => {
					if ( sessionActive && profileOverview && ! profileOverview.hidden && ! favoritesLoaded && ! favoritesController ) revealFavorites();
				} );
			}
		}

		async function removeFavorite( item, button ) {
			if ( ! sessionActive || ! currentCsrfToken || ! favoritesRows.some( value => value.id === item.id ) ) return;
			const ticket = generation;
			const before = favoritesRows;
			favoritesRows = favoritesRows.filter( value => value.id !== item.id );
			button.disabled = true;
			renderFavorites();
			const requestController = new AbortController();
			const deadline = window.setTimeout( () => requestController.abort(), requestTimeoutMs );
			try {
				const response = await fetch( `/reader-api/v1/favorites/${ item.postId }`, {
					method: 'DELETE', credentials: 'same-origin', cache: 'no-store', signal: requestController.signal,
					headers: { Accept: 'application/json', 'X-Reader-CSRF': currentCsrfToken },
				} );
				if ( ticket !== generation || ! sessionActive ) return;
				if ( response.status === 401 ) { guest( 'Сессия завершена. Войдите через HearthPulse снова.' ); return; }
				if ( ! response.ok ) throw new Error( 'favorite_remove_failed' );
			} catch ( error ) {
				if ( error.name !== 'AbortError' && ticket === generation && sessionActive ) {
					favoritesRows = before;
					renderFavorites();
					favoritesStatus.textContent = 'Не удалось убрать статью. Повторите попытку.';
				}
			} finally {
				window.clearTimeout( deadline );
			}
		}

		async function refreshAdministrator() {
			clearAdministrator();
			if ( ! administrator || ! paid || ! sessionActive ) return;
			const ticket = generation, token = currentCsrfToken;
			const permissionRequest = new AbortController();
			permissionsController = permissionRequest;
			const deadline = window.setTimeout( () => permissionRequest.abort(), requestTimeoutMs );
			try {
				const response = await fetch( '/reader-api/v1/community/me', { credentials: 'same-origin', cache: 'no-store', signal: permissionRequest.signal } );
				const body = await response.text();
				if ( permissionRequest.signal.aborted || permissionsController !== permissionRequest || ticket !== generation || token !== currentCsrfToken || ! sessionActive ) return;
				if ( response.status === 401 ) { guest( 'Сессия завершена. Войдите через HearthPulse снова.' ); return; }
				if ( ! response.ok || body.length > 1024 ) return;
				const data = JSON.parse( body );
				if ( ( data?.canModerateComments !== true && data?.canModerateComments !== false )
					|| ( data?.paidSubscriber !== true && data?.paidSubscriber !== false ) ) return;
				administrator.hidden = data.canModerateComments !== true;
				paid.hidden = data.paidSubscriber !== true;
			} catch ( error ) { /* An unavailable role lookup never confers administrator UI. */ }
			finally { window.clearTimeout( deadline ); }
		}

		function clearPrivate() {
			clearAdministrator();
			clearFavorites();
			identity.replaceChildren();
			identity.hidden = true;
			actions.replaceChildren();
			accountActions.replaceChildren();
			accountMenu.open = false;
			accountMenu.hidden = true;
			profileEditor?.clear();
			communityDataController?.abort();
			communityDataController = null;
			setCommunityDataBusy( false );
			if ( communityData ) communityData.hidden = true;
			if ( communityDataStatus ) communityDataStatus.textContent = '';
			currentProfileId = '';
			currentCsrfToken = '';
			sessionActive = false;
		}

		function link( label, className ) {
			const node = document.createElement( 'a' );
			node.className = className;
			node.textContent = label;
			return node;
		}

		function actionButton( label, className ) {
			const node = document.createElement( 'button' );
			node.type = 'button';
			node.className = className;
			node.textContent = label;
			return node;
		}

		function guest( message ) {
			clearPrivate();
			status.textContent = message;
			const login = link( 'Войти через HearthPulse', 'mc-reader__button mc-reader__button--primary mc-ui-button' );
			login.href = loginEndpoint;
			actions.append( login );
		}

		function showRetry( message, retryAction = refresh ) {
			clearPrivate();
			status.textContent = message;
			const retry = actionButton( 'Повторить', 'mc-reader__button mc-reader__button--secondary mc-ui-button mc-ui-button--secondary' );
			retry.addEventListener( 'click', retryAction );
			actions.append( retry );
		}

		function showPreservedRetry( message ) {
			status.textContent = message;
			if ( actions.querySelector( '[data-reader-refresh-session]' ) ) return;
			const retry = actionButton( 'Обновить вход', 'mc-reader__button mc-reader__button--secondary mc-ui-button mc-ui-button--secondary' );
			retry.dataset.readerRefreshSession = '';
			retry.addEventListener( 'click', () => refresh( { preserveDraft: true } ) );
			actions.append( retry );
		}

		function authenticated( data, refreshOptions = {} ) {
			if ( ! data || ! data.user || typeof data.user.displayName !== 'string' || typeof data.csrfToken !== 'string' || ! data.csrfToken || ! data.profile ) throw new Error( 'invalid_profile' );
			const wasActive = sessionActive;
			const nextFavoritesOwnerId = typeof data.profile.id === 'string' ? data.profile.id : '';
			if ( ! wasActive ) clearPrivate();
			else if ( favoritesOwnerId !== nextFavoritesOwnerId ) clearFavorites();
			profileEditor.applySession( data.profile, data.csrfToken, {
				preserveDraft: wasActive && refreshOptions.preserveDraft,
				acceptVersion: refreshOptions.acceptVersion,
			} );
			favoritesOwnerId = nextFavoritesOwnerId;
			currentProfileId = nextFavoritesOwnerId;
			currentCsrfToken = data.csrfToken;
			identity.hidden = false;
			actions.replaceChildren();
			accountActions.replaceChildren();
			status.textContent = '';
			accountMenu.hidden = false;
			const profileHref = allowedProfileUrl( data.profileUrl );
			if ( profileHref ) {
				const profile = link( 'Профиль HearthPulse', 'mc-reader__button mc-reader__button--secondary mc-ui-button mc-ui-button--secondary' );
				profile.href = profileHref;
				profile.target = '_blank';
				profile.rel = 'noopener';
				accountActions.append( profile );
			}
			const logout = actionButton( 'Выйти', 'mc-reader__button mc-reader__button--quiet mc-ui-button mc-ui-button--text' );
			logout.addEventListener( 'click', () => logoutRequest( currentCsrfToken ) );
			accountActions.append( logout );
			sessionActive = true;
			if ( communityData ) communityData.hidden = false;
			if ( profileOverview && ! profileOverview.hidden ) revealFavorites();
			void refreshAdministrator();
		}

		function current( requestController, requestGeneration ) {
			return controller === requestController && generation === requestGeneration && ! requestController.signal.aborted;
		}

		async function logoutRequest( csrfToken ) {
			logoutInFlight = true;
			const requestLogoutGeneration = ++logoutGeneration;
			generation += 1;
			if ( controller ) controller.abort();
			controller = null;
			clearPrivate();
			status.textContent = 'Выходим…';
			logoutController = new AbortController();
			const deadline = window.setTimeout( () => logoutController.abort(), requestTimeoutMs );
			try {
				const response = await fetch( logoutEndpoint, { method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: logoutController.signal, headers: { 'Content-Type': 'application/json', 'X-Reader-CSRF': text( csrfToken ) }, body: '{}' } );
				if ( response.status !== 204 ) throw new Error( 'logout_failed' );
				if ( logoutGeneration !== requestLogoutGeneration ) return;
				guest( 'Вы вышли из кабинета.' );
			} catch ( error ) {
				if ( logoutGeneration === requestLogoutGeneration ) showRetry( 'Не удалось выйти. Повторите попытку.', () => logoutRequest( csrfToken ) );
			} finally {
				window.clearTimeout( deadline );
				if ( logoutGeneration === requestLogoutGeneration ) {
					logoutController = null;
					logoutInFlight = false;
				}
			}
		}

		async function refresh( refreshOptions = {} ) {
			if ( logoutInFlight ) return;
			clearAdministrator();
			if ( controller ) controller.abort();
			const requestController = new AbortController();
			const requestGeneration = ++generation;
			controller = requestController;
			const preservePrivate = sessionActive || profileEditor.isDirty();
			if ( ! preservePrivate ) {
				status.textContent = 'Проверяем вход…';
				clearPrivate();
			}
			const deadline = window.setTimeout( () => {
				if ( current( requestController, requestGeneration ) ) {
					requestController.abort();
					if ( preservePrivate ) showPreservedRetry( 'Не удалось обновить вход вовремя. Изменения в форме сохранены.' );
					else showRetry( 'Проверка входа заняла слишком много времени. Повторите попытку.' );
				}
			}, requestTimeoutMs );
			try {
				const response = await fetch( meEndpoint, { credentials: 'same-origin', cache: 'no-store', signal: requestController.signal, headers: { Accept: 'application/json' } } );
				const data = await response.json().catch( () => ( {} ) );
				if ( ! current( requestController, requestGeneration ) ) return;
				if ( response.status === 200 ) authenticated( data, { ...refreshOptions, preserveDraft: preservePrivate } );
				else if ( response.status === 401 ) guest( 'Войдите через HearthPulse, чтобы открыть свой профиль.' );
				else if ( response.status === 503 || response.status === 429 ) {
					if ( preservePrivate ) showPreservedRetry( 'Не удалось обновить вход. Изменения в форме сохранены.' );
					else showRetry( 'Сервис входа временно недоступен.' );
				}
				else throw new Error( 'identity_failed' );
			} catch ( error ) {
				if ( error.name !== 'AbortError' && current( requestController, requestGeneration ) ) {
					if ( error.message === 'invalid_profile' ) showRetry( 'Сервис вернул некорректные данные профиля. Повторите попытку.' );
					else if ( preservePrivate ) showPreservedRetry( 'Не удалось обновить вход. Изменения в форме сохранены.' );
					else showRetry( 'Не удалось проверить вход. Повторите попытку.' );
				}
			} finally {
				window.clearTimeout( deadline );
			}
		}

		profileEditor = window.hsManacostReaderProfileEditor.create( root, {
			profileEndpoint,
			avatarEndpoint,
			onRefresh: ( options ) => refresh( { ...options, silent: true } ),
			onMutationStart: () => {
				permissionsController?.abort();
				generation += 1;
				if ( controller ) controller.abort();
				controller = null;
			},
			onUnauthorized: () => {
				generation += 1;
				if ( controller ) controller.abort();
				controller = null;
				guest( 'Сессия завершена. Войдите через HearthPulse снова.' );
			},
		} );
		root.querySelector( '[data-reader-open-editor]' )?.addEventListener( 'click', () => {
			cancelDeferredFavoritesLoad();
			if ( favoritesPanel ) favoritesPanel.hidden = true;
		} );
		root.querySelector( '[data-reader-cancel-editor]' )?.addEventListener( 'click', () => {
			if ( sessionActive ) revealFavorites();
		} );
		favoritesMore?.addEventListener( 'click', () => { void loadFavorites( favoritesLoaded ); } );
		commentsExport?.addEventListener( 'click', () => { void exportComments(); } );
		commentsErase?.addEventListener( 'click', () => { void eraseComments(); } );
		accountMenu.addEventListener( 'keydown', ( event ) => {
			if ( 'Escape' !== event.key || ! accountMenu.open ) return;
			event.preventDefault();
			accountMenu.open = false;
			accountMenu.querySelector( 'summary' ).focus();
		} );
		const refreshIfActive = () => { if ( ! logoutInFlight && ! profileEditor.isBusy() ) refresh( { preserveDraft: true, silent: true } ); };
		// Initial navigation already calls refresh below; only BFCache needs a second entry path.
		window.addEventListener( 'pageshow', event => { if ( event.persisted ) refreshIfActive(); } );
		window.addEventListener( 'focus', refreshIfActive );
		window.addEventListener( 'pagehide', () => {
			generation += 1;
			if ( controller ) controller.abort();
			logoutGeneration += 1;
			if ( logoutController ) logoutController.abort();
			logoutController = null;
			logoutInFlight = false;
			clearPrivate();
			status.textContent = 'Проверяем вход…';
		} );
		refresh();
	}

	document.querySelectorAll( '[data-mc-reader-root]' ).forEach( init );
}() );
