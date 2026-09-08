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
		const loginEndpoint = endpoint( root, 'loginEndpoint', '/reader-auth/start?returnTo=%2Faccount%2F' );
		const logoutEndpoint = endpoint( root, 'logoutEndpoint', '/reader-auth/logout' );
		const meEndpoint = endpoint( root, 'meEndpoint', '/reader-api/v1/me' );
		let controller = null;
		let generation = 0;
		let logoutInFlight = false;

		function clearPrivate() {
			identity.replaceChildren();
			identity.hidden = true;
			actions.replaceChildren();
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
			const login = link( 'Войти через HearthPulse', 'mc-reader__button mc-reader__button--primary' );
			login.href = loginEndpoint;
			actions.append( login );
		}

		function showRetry( message, retryAction = refresh ) {
			clearPrivate();
			status.textContent = message;
			const retry = actionButton( 'Повторить', 'mc-reader__button mc-reader__button--secondary' );
			retry.addEventListener( 'click', retryAction );
			actions.append( retry );
		}

		function authenticated( data ) {
			clearPrivate();
			if ( ! data || ! data.user || typeof data.user.displayName !== 'string' || typeof data.csrfToken !== 'string' || ! data.csrfToken ) throw new Error( 'invalid_profile' );
			const user = data.user;
			status.textContent = 'Вы вошли в кабинет.';
			const nameNode = document.createElement( 'strong' );
			nameNode.textContent = text( user.displayName ) || 'Читатель';
			identity.append( nameNode );
			identity.hidden = false;
			const profileHref = allowedProfileUrl( data.profileUrl );
			if ( profileHref ) {
				const profile = link( 'Профиль HearthPulse', 'mc-reader__button mc-reader__button--secondary' );
				profile.href = profileHref;
				profile.target = '_blank';
				profile.rel = 'noopener';
				actions.append( profile );
			}
			const logout = actionButton( 'Выйти', 'mc-reader__button mc-reader__button--quiet' );
			logout.addEventListener( 'click', () => logoutRequest( data.csrfToken ) );
			actions.append( logout );
		}

		function current( requestController, requestGeneration ) {
			return controller === requestController && generation === requestGeneration && ! requestController.signal.aborted;
		}

		async function logoutRequest( csrfToken ) {
			logoutInFlight = true;
			generation += 1;
			if ( controller ) controller.abort();
			controller = null;
			clearPrivate();
			status.textContent = 'Выходим…';
			const logoutController = new AbortController();
			const deadline = window.setTimeout( () => logoutController.abort(), requestTimeoutMs );
			try {
				const response = await fetch( logoutEndpoint, { method: 'POST', credentials: 'same-origin', cache: 'no-store', signal: logoutController.signal, headers: { 'Content-Type': 'application/json', 'X-Reader-CSRF': text( csrfToken ) }, body: '{}' } );
				if ( response.status !== 204 ) throw new Error( 'logout_failed' );
				guest( 'Вы вышли из кабинета.' );
			} catch ( error ) {
			showRetry( 'Не удалось выйти. Повторите попытку.', () => logoutRequest( csrfToken ) );
			} finally {
				window.clearTimeout( deadline );
				logoutInFlight = false;
			}
		}

		async function refresh() {
			if ( logoutInFlight ) return;
			if ( controller ) controller.abort();
			const requestController = new AbortController();
			const requestGeneration = ++generation;
			controller = requestController;
			status.textContent = 'Проверяем вход…';
			clearPrivate();
			const deadline = window.setTimeout( () => {
				if ( current( requestController, requestGeneration ) ) {
					requestController.abort();
					showRetry( 'Проверка входа заняла слишком много времени. Повторите попытку.' );
				}
			}, requestTimeoutMs );
			try {
				const response = await fetch( meEndpoint, { credentials: 'same-origin', cache: 'no-store', signal: requestController.signal, headers: { Accept: 'application/json' } } );
				const data = await response.json().catch( () => ( {} ) );
				if ( ! current( requestController, requestGeneration ) ) return;
				if ( response.status === 200 ) authenticated( data );
				else if ( response.status === 401 ) guest( 'Войдите через HearthPulse, чтобы открыть свой профиль.' );
				else if ( response.status === 503 ) showRetry( 'Сервис входа временно недоступен.' );
				else throw new Error( 'identity_failed' );
			} catch ( error ) {
				if ( error.name !== 'AbortError' && current( requestController, requestGeneration ) ) showRetry( 'Не удалось проверить вход. Повторите попытку.' );
			} finally {
				window.clearTimeout( deadline );
			}
		}

		const refreshIfActive = () => { if ( ! logoutInFlight ) refresh(); };
		window.addEventListener( 'pageshow', refreshIfActive );
		window.addEventListener( 'focus', refreshIfActive );
		window.addEventListener( 'pagehide', () => {
			generation += 1;
			if ( controller ) controller.abort();
			clearPrivate();
			status.textContent = 'Проверяем вход…';
		} );
		refresh();
	}

	document.querySelectorAll( '[data-mc-reader-root]' ).forEach( init );
}() );
