( function () {
	'use strict';
	const requestTimeoutMs = 7000;

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

	function init( root ) {
		const status = root.querySelector( '[data-reader-status]' );
		const identity = root.querySelector( '[data-reader-identity]' );
		const actions = root.querySelector( '[data-reader-actions]' );
		const accountMenu = root.querySelector( '[data-reader-account-menu]' );
		const accountActions = root.querySelector( '[data-reader-account-actions]' );
		const administrator = root.querySelector( '[data-reader-administrator]' );
		const loginEndpoint = endpoint( root, 'loginEndpoint', '/reader-auth/start?returnTo=%2Faccount%2F' );
		const logoutEndpoint = endpoint( root, 'logoutEndpoint', '/reader-auth/logout' );
		const meEndpoint = endpoint( root, 'meEndpoint', '/reader-api/v1/me' );
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

		function clearAdministrator() {
			permissionsController?.abort();
			permissionsController = null;
			if ( administrator ) administrator.hidden = true;
		}

		async function refreshAdministrator() {
			clearAdministrator();
			if ( ! administrator || ! sessionActive ) return;
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
				administrator.hidden = data?.canModerateComments !== true;
			} catch ( error ) { /* An unavailable role lookup never confers administrator UI. */ }
			finally { window.clearTimeout( deadline ); }
		}

		function clearPrivate() {
			clearAdministrator();
			identity.replaceChildren();
			identity.hidden = true;
			actions.replaceChildren();
			accountActions.replaceChildren();
			accountMenu.open = false;
			accountMenu.hidden = true;
			profileEditor?.clear();
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
			if ( ! wasActive ) clearPrivate();
			profileEditor.applySession( data.profile, data.csrfToken, {
				preserveDraft: wasActive && refreshOptions.preserveDraft,
				acceptVersion: refreshOptions.acceptVersion,
			} );
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
