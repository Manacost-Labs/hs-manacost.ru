( function () {
	'use strict';

	const maxAvatarBytes = 4 * 1024 * 1024;
	const avatarTypes = new Set( [ 'image/jpeg', 'image/png', 'image/webp' ] );
	const classNames = new Map( [
		[ 'death-knight', 'Рыцарь смерти' ],
		[ 'demon-hunter', 'Охотник на демонов' ],
		[ 'druid', 'Друид' ],
		[ 'hunter', 'Охотник' ],
		[ 'mage', 'Маг' ],
		[ 'paladin', 'Паладин' ],
		[ 'priest', 'Жрец' ],
		[ 'rogue', 'Разбойник' ],
		[ 'shaman', 'Шаман' ],
		[ 'warlock', 'Чернокнижник' ],
		[ 'warrior', 'Воин' ],
	] );
	const codepointLength = ( value ) => Array.from( value ).length;
	const hasOwn = ( value, key ) => Object.prototype.hasOwnProperty.call( value, key );
	const sameDraft = ( left, right ) => left.displayName === right.displayName && left.bio === right.bio && left.favoriteClass === right.favoriteClass
		&& left.twitchUrl === right.twitchUrl && left.youtubeUrl === right.youtubeUrl;

	function safeAvatarPath( value, endpoint ) {
		if ( value === null ) return null;
		if ( typeof value !== 'string' ) throw new Error( 'invalid_profile' );
		const url = new URL( value, window.location.origin );
		const allowed = new URL( endpoint, window.location.origin );
		if ( url.origin !== window.location.origin || url.pathname !== allowed.pathname || url.searchParams.size !== 1
			|| ! /^[A-Za-z0-9_-]+$/.test( url.searchParams.get( 'v' ) || '' ) ) throw new Error( 'invalid_profile' );
		return url.pathname + url.search;
	}

	function safeSocialUrl( value, service ) {
		if ( value === null || value === '' ) return null;
		if ( typeof value !== 'string' || value.length > 200 || /[\u0000-\u001f\u007f]/.test( value ) ) throw new Error( 'invalid_profile' );
		let url;
		try { url = new URL( value.trim() ); } catch ( error ) { throw new Error( 'invalid_profile' ); }
		if ( url.protocol !== 'https:' || url.username || url.password || url.port || url.search || url.hash ) throw new Error( 'invalid_profile' );
		const host = url.hostname.toLowerCase();
		if ( 'twitch' === service ) {
			if ( ! /^(?:www\.)?twitch\.tv$/i.test( host ) ) throw new Error( 'invalid_profile' );
			const match = url.pathname.match( /^\/([a-z0-9_]{4,25})\/?$/i );
			if ( ! match ) throw new Error( 'invalid_profile' );
			return `https://www.twitch.tv/${ match[ 1 ].toLowerCase() }`;
		}
		if ( ! /^(?:www\.|m\.)?youtube\.com$/i.test( host ) ) throw new Error( 'invalid_profile' );
		const handle = url.pathname.match( /^\/@([a-z0-9_.-]{3,30})$/i );
		if ( handle ) return `https://www.youtube.com/@${ handle[ 1 ] }`;
		const channel = url.pathname.match( /^\/channel\/(UC[a-z0-9_-]{22})$/i );
		if ( channel ) return `https://www.youtube.com/channel/${ channel[ 1 ] }`;
		throw new Error( 'invalid_profile' );
	}

	function validProfile( value, avatarEndpoint ) {
		if ( ! value || typeof value !== 'object' || ! /^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test( value.id )
			|| typeof value.displayName !== 'string' || codepointLength( value.displayName ) < 2 || codepointLength( value.displayName ) > 40
			|| typeof value.bio !== 'string' || codepointLength( value.bio ) > 280
			|| ( value.favoriteClass !== null && ! classNames.has( value.favoriteClass ) )
			|| ! Number.isSafeInteger( value.version ) || value.version < 1 ) throw new Error( 'invalid_profile' );
		const hasTwitchUrl = hasOwn( value, 'twitchUrl' );
		const hasYoutubeUrl = hasOwn( value, 'youtubeUrl' );
		if ( hasTwitchUrl !== hasYoutubeUrl ) throw new Error( 'invalid_profile' );
		const socialFieldsAvailable = hasTwitchUrl && hasYoutubeUrl;
		return {
			id: value.id,
			displayName: value.displayName,
			bio: value.bio,
			favoriteClass: value.favoriteClass,
			twitchUrl: socialFieldsAvailable ? safeSocialUrl( value.twitchUrl, 'twitch' ) : null,
			youtubeUrl: socialFieldsAvailable ? safeSocialUrl( value.youtubeUrl, 'youtube' ) : null,
			socialFieldsAvailable,
			version: value.version,
			avatarUrl: safeAvatarPath( value.avatarUrl, avatarEndpoint ),
		};
	}

	function create( root, options ) {
		const form = root.querySelector( '[data-reader-profile-editor]' );
		const overview = root.querySelector( '[data-reader-profile-overview]' );
		const openEditor = root.querySelector( '[data-reader-open-editor]' );
		const cancelEditor = root.querySelector( '[data-reader-cancel-editor]' );
		const name = root.querySelector( '[data-reader-display-name]' );
		const bio = root.querySelector( '[data-reader-bio]' );
		const twitch = root.querySelector( '[data-reader-twitch]' );
		const youtube = root.querySelector( '[data-reader-youtube]' );
		const socialHelp = root.querySelector( '[data-reader-social-help]' );
		const favoriteClass = root.querySelector( '[data-reader-favorite-class]' );
		const identity = root.querySelector( '[data-reader-identity]' );
		const authorMarks = [
			{ node: root.querySelector( '[data-reader-twitch-mark]' ), service: 'twitch' },
			{ node: root.querySelector( '[data-reader-youtube-mark]' ), service: 'youtube' },
		];
		const previewClass = root.querySelector( '[data-reader-preview-class]' );
		const classCrest = root.querySelector( '[data-reader-class-crest]' );
		const classIconBase = root.dataset.classIconBase || '';
		const previewBio = root.querySelector( '[data-reader-preview-bio]' );
		const previewLabel = root.querySelector( '[data-reader-preview-label]' );
		const avatarImage = root.querySelector( '[data-reader-avatar-image]' );
		const avatarPlaceholder = root.querySelector( '[data-reader-avatar-placeholder]' );
		const avatarViews = [
			{ image: avatarImage, placeholder: avatarPlaceholder },
			{ image: root.querySelector( '[data-reader-editor-avatar-image]' ), placeholder: root.querySelector( '[data-reader-editor-avatar-placeholder]' ) },
		];
		const avatarInput = root.querySelector( '[data-reader-avatar-input]' );
		const removeAvatar = root.querySelector( '[data-reader-remove-avatar]' );
		const save = root.querySelector( '[data-reader-save-profile]' );
		const retry = root.querySelector( '[data-reader-retry-profile]' );
		const reloadVersion = root.querySelector( '[data-reader-reload-version]' );
		const editorStatus = root.querySelector( '[data-reader-editor-status]' );
		const nameCount = root.querySelector( '[data-reader-name-count]' );
		const bioCount = root.querySelector( '[data-reader-bio-count]' );
		const publicConsent = root.querySelector( '[data-reader-public-consent]' );
		const publication = root.querySelector( '.mc-reader__publication' );
		const publishProfile = root.querySelector( '[data-reader-publish-profile]' );
		const publicationHelp = root.querySelector( '[data-reader-publication-help]' );
		let serverProfile = null;
		let csrfToken = '';
		let knownVersion = 0;
		let dirty = false;
		let localAvatarUrl = null;
		let mutationController = null;
		let mutationGeneration = 0;
		let retryAction = null;
		let socialFieldsAvailable = false;

		const draft = () => ( {
			displayName: name.value,
			bio: bio.value,
			favoriteClass: favoriteClass.value || null,
			twitchUrl: twitch.value || null,
			youtubeUrl: youtube.value || null,
		} );

		function revokeLocalAvatar() {
			if ( localAvatarUrl ) URL.revokeObjectURL( localAvatarUrl );
			localAvatarUrl = null;
		}

		function showEditorStatus( message, mode = '' ) {
			if ( editorStatus.textContent === message && editorStatus.dataset.state === mode ) return;
			editorStatus.textContent = message;
			editorStatus.dataset.state = mode;
		}

		function setRetry( action ) {
			retryAction = action;
			retry.hidden = ! action;
		}

		function syncSocialFields() {
			const unavailable = ! socialFieldsAvailable;
			twitch.disabled = Boolean( mutationController ) || unavailable;
			youtube.disabled = Boolean( mutationController ) || unavailable;
			if ( socialHelp ) {
				socialHelp.textContent = unavailable
					? 'Ссылки Twitch и YouTube станут доступны сразу после обновления сервиса профиля. Остальные данные можно сохранять уже сейчас.'
					: publication && ! publication.hidden
						? 'Необязательно. Для показа ссылок другим читателям подтвердите обновление профиля в комментариях ниже.'
						: 'Необязательно. Сохранение здесь не публикует ссылки для других читателей.';
			}
		}

		function setBusy( busy, label = '' ) {
			save.disabled = busy;
			if ( publicConsent ) publicConsent.disabled = busy;
			syncPublication();
			avatarInput.disabled = busy;
			removeAvatar.disabled = busy;
			syncSocialFields();
			form.setAttribute( 'aria-busy', String( busy ) );
			if ( busy && label ) showEditorStatus( label );
		}

		function syncPublication() {
			// A cached pre-publication shell can briefly receive the newer script.
			if ( ! publication || ! publicConsent || ! publishProfile || ! publicationHelp ) return;
			publishProfile.disabled = publication.hidden || Boolean( mutationController ) || ! serverProfile || dirty || ! publicConsent.checked;
			publicationHelp.textContent = dirty ? 'Сначала сохраните изменения профиля.' : 'Будет опубликована сохранённая версия профиля. Это действие не создаёт комментарий.';
		}

		function updateCounters() {
			nameCount.textContent = `${ codepointLength( name.value ) } / 40`;
			bioCount.textContent = `${ codepointLength( bio.value ) } / 280`;
		}

		function initials( value ) {
			const words = value.trim().split( /\s+/u ).filter( Boolean );
			return ( words.length > 1 ? [ words[ 0 ][ 0 ], words[ 1 ][ 0 ] ] : Array.from( words[ 0 ] || 'М' ).slice( 0, 2 ) ).join( '' ).toLocaleUpperCase( 'ru-RU' );
		}

		function renderPreview() {
			const current = draft();
			identity.textContent = current.displayName || 'Читатель';
			for ( const mark of authorMarks ) {
				let url = null;
				try { url = current[ `${ mark.service }Url` ] ? safeSocialUrl( current[ `${ mark.service }Url` ], mark.service ) : null; } catch ( error ) { /* A malformed draft remains unmarked until fixed. */ }
				if ( ! mark.node ) continue;
				mark.node.hidden = ! url;
				if ( url ) mark.node.href = url;
				else mark.node.removeAttribute( 'href' );
			}
			previewClass.textContent = current.favoriteClass ? classNames.get( current.favoriteClass ) : 'Не выбран';
			const icon = current.favoriteClass && classIconBase ? `${ classIconBase }${ current.favoriteClass.replace( /-/g, '' ) }.png` : '';
			if ( icon ) {
				classCrest.src = icon;
				classCrest.alt = `Эмблема класса ${ classNames.get( current.favoriteClass ) }`;
				classCrest.hidden = false;
			} else {
				classCrest.removeAttribute( 'src' );
				classCrest.alt = '';
				classCrest.hidden = true;
			}
			previewBio.textContent = current.bio || 'Описание пока не добавлено.';
			const source = localAvatarUrl || serverProfile?.avatarUrl || '';
			for ( const view of avatarViews ) {
				view.placeholder.textContent = initials( current.displayName );
				if ( source ) {
					if ( view.image.getAttribute( 'src' ) !== source ) view.image.src = source;
					view.image.hidden = false;
					view.placeholder.hidden = true;
				} else {
					view.image.removeAttribute( 'src' );
					view.image.hidden = true;
					view.placeholder.hidden = false;
				}
			}
			previewLabel.textContent = dirty || localAvatarUrl ? 'Есть несохранённые изменения.' : '';
			previewLabel.hidden = ! dirty && ! localAvatarUrl;
			removeAvatar.hidden = ! serverProfile?.avatarUrl && ! localAvatarUrl;
			updateCounters();
		}

		function updateDirtyState() {
			dirty = Boolean( serverProfile ) && ! sameDraft( draft(), serverProfile );
			if ( publicConsent ) publicConsent.checked = false;
			syncPublication();
			renderPreview();
			if ( dirty ) {
				showEditorStatus( 'Есть несохранённые изменения.' );
			} else if ( editorStatus.dataset.state !== 'success' ) {
				showEditorStatus( '' );
			}
		}

		function fill( profile ) {
			name.value = profile.displayName;
			bio.value = profile.bio;
			favoriteClass.value = profile.favoriteClass || '';
			twitch.value = profile.twitchUrl || '';
			youtube.value = profile.youtubeUrl || '';
		}

		function applySession( rawProfile, nextCsrfToken, sessionOptions = {} ) {
			const profile = validProfile( rawProfile, options.avatarEndpoint );
			if ( typeof nextCsrfToken !== 'string' || ! nextCsrfToken ) throw new Error( 'invalid_profile' );
			csrfToken = nextCsrfToken;
			if ( publicConsent ) publicConsent.checked = false;
			syncPublication();
			if ( ! serverProfile ) form.hidden = true;
			overview.hidden = ! form.hidden;
			if ( serverProfile?.id === profile.id && profile.version < knownVersion ) {
				renderPreview();
				return;
			}
			const mayPreserve = sessionOptions.preserveDraft && ( dirty || mutationController ) && serverProfile?.id === profile.id;
			if ( mayPreserve ) {
				socialFieldsAvailable = profile.socialFieldsAvailable;
				if ( sessionOptions.acceptVersion ) {
					serverProfile = profile;
					knownVersion = profile.version;
					revokeLocalAvatar();
					dirty = ! sameDraft( draft(), serverProfile );
					setRetry( null );
					reloadVersion.hidden = true;
					showEditorStatus( dirty ? 'Версия обновлена. Введённый текст сохранён — проверьте его и сохраните снова.' : 'Профиль уже актуален.', 'success' );
				} else if ( ! mutationController ) {
					setRetry( null );
					showEditorStatus( 'Вход обновлён. Несохранённые изменения остались в форме.', 'success' );
				}
				syncSocialFields();
				renderPreview();
				return;
			}
			revokeLocalAvatar();
			serverProfile = profile;
			knownVersion = profile.version;
			socialFieldsAvailable = profile.socialFieldsAvailable;
			fill( profile );
			dirty = false;
			setRetry( null );
			reloadVersion.hidden = true;
			showEditorStatus( '' );
			syncSocialFields();
			renderPreview();
		}

		function clear() {
			mutationGeneration += 1;
			if ( mutationController ) mutationController.abort();
			mutationController = null;
			revokeLocalAvatar();
			serverProfile = null;
			socialFieldsAvailable = false;
			csrfToken = '';
			knownVersion = 0;
			dirty = false;
			if ( publicConsent ) publicConsent.checked = false;
			fill( { displayName: '', bio: '', favoriteClass: null, twitchUrl: null, youtubeUrl: null } );
			identity.replaceChildren();
			for ( const mark of authorMarks ) mark.node.hidden = true;
			previewClass.replaceChildren();
			previewBio.replaceChildren();
			for ( const view of avatarViews ) {
				view.placeholder.replaceChildren();
				view.image.removeAttribute( 'src' );
				view.image.hidden = true;
				view.placeholder.hidden = false;
			}
			avatarInput.value = '';
			setRetry( null );
			reloadVersion.hidden = true;
			showEditorStatus( '' );
			form.hidden = true;
			overview.hidden = true;
			setBusy( false );
		}

		function handleFailure( response, operation, repeat ) {
			setRetry( null );
			reloadVersion.hidden = true;
			if ( response.status === 401 ) {
				options.onUnauthorized();
				return;
			}
			if ( response.status === 403 ) {
				showEditorStatus( 'Сессия формы устарела. Обновите данные входа и повторите действие.', 'error' );
				setRetry( () => options.onRefresh( { preserveDraft: true } ) );
				return;
			}
			if ( response.status === 409 ) {
				showEditorStatus( 'Профиль изменён в другом окне. Обновите версию — введённый текст останется в форме.', 'error' );
				reloadVersion.hidden = false;
				return;
			}
			if ( operation === 'publication' && response.status === 404 ) {
				showEditorStatus( 'Публичный профиль появится после первого комментария в доступной статье. Пока ничего не опубликовано.', 'error' );
				return;
			}
			if ( response.status === 413 ) showEditorStatus( 'Файл больше 4 МБ. Выберите изображение меньшего размера.', 'error' );
			else if ( response.status === 400 ) showEditorStatus( operation === 'profile' ? 'Проверьте имя, описание, выбранный класс и ссылки.' : 'Не удалось обработать изображение. Выберите другой файл.', 'error' );
			else if ( response.status === 429 || response.status === 503 ) showEditorStatus( 'Сервис занят. Повторите попытку чуть позже.', 'error' );
			else if ( response.status >= 500 ) {
				showEditorStatus( 'Ответ сервиса неизвестен. Сначала обновите версию профиля, затем при необходимости сохраните снова.', 'error' );
				setRetry( () => options.onRefresh( { preserveDraft: true, acceptVersion: true } ) );
				return;
			} else showEditorStatus( 'Не удалось сохранить изменения. Повторите попытку.', 'error' );
			setRetry( repeat );
		}

		async function request( { method, endpoint, body, headers, operation, repeat, sentDraft = null } ) {
			if ( mutationController || ! serverProfile ) return;
			options.onMutationStart();
			const requestController = new AbortController();
			const requestGeneration = ++mutationGeneration;
			mutationController = requestController;
			setRetry( null );
			reloadVersion.hidden = true;
			setBusy( true, operation === 'publication' ? 'Обновляем профиль в комментариях…' : operation === 'profile' ? 'Сохраняем профиль…' : 'Сохраняем фотографию…' );
			const deadline = window.setTimeout( () => requestController.abort(), 7000 );
			try {
				const response = await fetch( endpoint, { method, credentials: 'same-origin', cache: 'no-store', signal: requestController.signal, headers, body } );
				const data = await response.json().catch( ( error ) => {
					if ( requestController.signal.aborted ) throw error;
					return {};
				} );
				if ( mutationGeneration !== requestGeneration ) return;
				if ( requestController.signal.aborted ) throw new DOMException( 'Request aborted', 'AbortError' );
				if ( response.status !== 200 ) {
					handleFailure( response, operation, repeat );
					return;
				}
				const profile = validProfile( data.profile, options.avatarEndpoint );
				if ( profile.id !== serverProfile.id ) throw new Error( 'invalid_profile' );
				serverProfile = profile;
				knownVersion = profile.version;
				socialFieldsAvailable = profile.socialFieldsAvailable;
				syncSocialFields();
				if ( operation === 'profile' && sentDraft && sameDraft( draft(), sentDraft ) ) fill( profile );
				dirty = ! sameDraft( draft(), serverProfile );
				if ( publicConsent ) publicConsent.checked = false;
				revokeLocalAvatar();
				setRetry( null );
				reloadVersion.hidden = true;
				showEditorStatus( operation === 'publication' ? 'Профиль в комментариях обновлён: фото и ссылки теперь видны другим читателям.' : operation === 'profile' ? ( dirty ? 'Сохранено. Новые правки остались в форме.' : 'Изменения сохранены.' ) : 'Фотография профиля обновлена.', 'success' );
				renderPreview();
			} catch ( error ) {
				if ( mutationGeneration === requestGeneration && error.name !== 'AbortError' ) {
					showEditorStatus( 'Ответ сервиса неизвестен. Сначала обновите версию профиля, затем при необходимости сохраните снова.', 'error' );
					setRetry( () => options.onRefresh( { preserveDraft: true, acceptVersion: true } ) );
				} else if ( mutationGeneration === requestGeneration ) {
					showEditorStatus( 'Ответ сервиса не получен вовремя. Сначала обновите версию профиля, затем при необходимости сохраните снова.', 'error' );
					setRetry( () => options.onRefresh( { preserveDraft: true, acceptVersion: true } ) );
				}
			} finally {
				window.clearTimeout( deadline );
				if ( mutationGeneration === requestGeneration ) {
					mutationController = null;
					setBusy( false );
					if ( operation !== 'profile' && editorStatus.dataset.state !== 'success' ) {
						revokeLocalAvatar();
						renderPreview();
					}
				}
			}
		}

		function validateDraft( value ) {
			name.setCustomValidity( '' );
			bio.setCustomValidity( '' );
			twitch.setCustomValidity( '' );
			youtube.setCustomValidity( '' );
			const displayLength = codepointLength( value.displayName.trim() );
			if ( displayLength < 2 || displayLength > 40 ) name.setCustomValidity( 'Введите имя длиной от 2 до 40 символов.' );
			if ( codepointLength( value.bio.trim() ) > 280 ) bio.setCustomValidity( 'Описание должно быть не длиннее 280 символов.' );
			if ( socialFieldsAvailable ) {
				try { value.twitchUrl = safeSocialUrl( value.twitchUrl, 'twitch' ); } catch ( error ) { twitch.setCustomValidity( 'Укажите ссылку вида https://twitch.tv/your_channel.' ); }
				try { value.youtubeUrl = safeSocialUrl( value.youtubeUrl, 'youtube' ); } catch ( error ) { youtube.setCustomValidity( 'Укажите канал YouTube вида https://youtube.com/@your_channel.' ); }
			} else {
				value.twitchUrl = null;
				value.youtubeUrl = null;
			}
			if ( ! form.reportValidity() ) {
				showEditorStatus( 'Исправьте отмеченные поля.', 'error' );
				return false;
			}
			if ( socialFieldsAvailable ) {
				twitch.value = value.twitchUrl || '';
				youtube.value = value.youtubeUrl || '';
			}
			return true;
		}

		function saveProfile() {
			const value = draft();
			value.displayName = value.displayName.trim();
			value.bio = value.bio.trim();
			if ( ! validateDraft( value ) ) return;
			const payload = { version: knownVersion, displayName: value.displayName, bio: value.bio, favoriteClass: value.favoriteClass };
			if ( socialFieldsAvailable ) Object.assign( payload, { twitchUrl: value.twitchUrl, youtubeUrl: value.youtubeUrl } );
			const body = JSON.stringify( payload );
			request( {
				method: 'PATCH', endpoint: options.profileEndpoint, body, operation: 'profile', sentDraft: value,
				headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Reader-CSRF': csrfToken },
				repeat: saveProfile,
			} );
		}

		function uploadAvatar( file ) {
			if ( publicConsent ) publicConsent.checked = false;
			syncPublication();
			if ( ! file || ! avatarTypes.has( file.type ) || file.size < 1 || file.size > maxAvatarBytes ) {
				showEditorStatus( file?.size > maxAvatarBytes ? 'Файл больше 4 МБ. Выберите изображение меньшего размера.' : 'Выберите JPEG, PNG или WebP.', 'error' );
				avatarInput.value = '';
				return;
			}
			revokeLocalAvatar();
			localAvatarUrl = URL.createObjectURL( file );
			renderPreview();
			request( {
				method: 'PUT', endpoint: options.avatarEndpoint, body: file, operation: 'avatar',
				headers: { Accept: 'application/json', 'Content-Type': file.type, 'X-Reader-CSRF': csrfToken, 'X-Reader-Profile-Version': String( knownVersion ) },
				repeat: () => uploadAvatar( file ),
			} );
			avatarInput.value = '';
		}

		function deleteAvatar() {
			if ( publicConsent ) publicConsent.checked = false;
			syncPublication();
			request( {
				method: 'DELETE', endpoint: options.avatarEndpoint, body: undefined, operation: 'avatar',
				headers: { Accept: 'application/json', 'X-Reader-CSRF': csrfToken, 'X-Reader-Profile-Version': String( knownVersion ) },
				repeat: deleteAvatar,
			} );
		}

		function publishSavedProfile() {
			if ( ! publication || publication.hidden || ! publicConsent?.checked || dirty || mutationController ) return;
			request( {
				method: 'PUT', endpoint: '/reader-api/v1/community/profile', operation: 'publication',
				body: JSON.stringify( { profileVersion: knownVersion, publicConsent: true } ),
				headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Reader-CSRF': csrfToken },
				repeat: publishSavedProfile,
			} );
		}

		form.addEventListener( 'submit', ( event ) => { event.preventDefault(); saveProfile(); } );
		openEditor.addEventListener( 'click', () => {
			form.hidden = false;
			overview.hidden = true;
			form.querySelector( '#mc-reader-display-name' )?.focus();
			form.scrollIntoView( { behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth', block: 'start' } );
		} );
		cancelEditor.addEventListener( 'click', () => {
			if ( ! mutationController ) {
				form.hidden = true;
				overview.hidden = false;
				openEditor.focus();
			}
		} );
		form.addEventListener( 'input', ( event ) => {
			if ( event.target === publicConsent ) { syncPublication(); return; }
			if ( event.target === name ) name.setCustomValidity( '' );
			if ( event.target === bio ) bio.setCustomValidity( '' );
			if ( event.target === twitch ) twitch.setCustomValidity( '' );
			if ( event.target === youtube ) youtube.setCustomValidity( '' );
			if ( event.target !== avatarInput ) updateDirtyState();
		} );
		avatarInput.addEventListener( 'change', () => uploadAvatar( avatarInput.files?.[ 0 ] ) );
		removeAvatar.addEventListener( 'click', deleteAvatar );
		publishProfile?.addEventListener( 'click', publishSavedProfile );
		retry.addEventListener( 'click', () => retryAction?.() );
		reloadVersion.addEventListener( 'click', () => options.onRefresh( { preserveDraft: true, acceptVersion: true } ) );
		for ( const view of avatarViews ) {
			view.image.addEventListener( 'error', () => {
				view.image.hidden = true;
				view.placeholder.hidden = false;
			} );
		}

		return {
			applySession,
			clear,
			isBusy: () => Boolean( mutationController ),
			isDirty: () => dirty,
		};
	}

	window.hsManacostReaderProfileEditor = { create };
}() );
