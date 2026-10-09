(() => {
  'use strict';
  const root = document.querySelector('[data-mc-comments]');
  const defaultAvatar = /^\/wp-content\/mu-plugins\/hs-manacost-reader\/default-avatar\.webp\?ver=[a-f0-9]{12}$/i.test(root?.dataset.defaultAvatarUrl || '') ? root.dataset.defaultAvatarUrl : null;
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  if (!root || !/^[1-9][0-9]*$/.test(root.dataset.postId || '')) return;
  const postId = Number(root.dataset.postId);
  if (!Number.isSafeInteger(postId)) return;
  const endpoint = `/reader-api/v1/threads/${postId}/comments`;
  const $ = selector => root.querySelector(selector);
  const status = $('[data-comments-status]'), list = $('[data-comments-list]');
  const form = $('[data-comments-form]'), body = $('[data-comments-body]');
  const submit = $('[data-comments-submit]');
  const retry = $('[data-comments-retry]'), login = $('[data-comments-login]');
  const reply = $('[data-comments-reply]'), cancel = $('[data-comments-cancel]');
  const composerIdentity = $('[data-comments-me]'), count = $('[data-comments-count]');
  const profileNotice = $('[data-comments-profile-notice]'), refreshProfile = $('[data-comments-refresh-profile]');
  const [attachmentInput, attachmentPicker, attachmentPreview, attachmentImage, attachmentStatus, attachmentRemove] = ['input', 'picker', 'preview', 'image', 'status', 'remove'].map(name => $(`[data-comments-attachment-${name}]`));
  const attachmentTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
  const maxAttachmentBytes = 4 * 1024 * 1024, tooLargeText = 'Изображение больше 4 МБ. Выберите файл меньшего размера.';
  const byTime = (left, right) => left.createdAt - right.createdAt || left.id.localeCompare(right.id);
  const more = document.createElement('button');
  more.type = 'button'; more.textContent = 'Показать ещё'; more.hidden = true;
  more.className = 'mc-comments__more mc-ui-button mc-ui-button--secondary'; list.after(more);
  const requests = new Set();
  const stale = new Error('stale');
  let generation = 0, visible = true, me = null, csrf = '', rows = [], cursor = null, threadLoaded = false, refreshBootstrap = false;
  let activationObserver = null, activated = false;
  let parentId = null, retryPayload = null, busy = false, commentingBlocked = false;
  let stagedAttachment = null, attachmentPreviewUrl = null, attachmentUploading = false;
  let reconcileTimer=0;const reconcileIds=new Set;
  const rendered = new Map(), blockedText = 'Вам запрещено комментировать. Черновик сохранён.';
  let lastFocus = null; // Chrome blurs disabled row buttons to <body>.
  const forgetFocus = e => { if (!list.contains(e.target)) lastFocus = null; };
  for (const event of ['focusin', 'pointerdown']) document.addEventListener(event, forgetFocus);
  const say = message => { status.removeAttribute('data-loading'); status.textContent = message; };
  const showLoading = () => { status.dataset.loading = 'true'; status.textContent = 'Загружаем комментарии…'; };
  const current = ticket => visible && ticket === generation;
  const release = (ticket, upload = false) => {
    if (!current(ticket)) return;
    if (upload) attachmentUploading = false; else busy = false;
    controls();
  };
  const community = window.hsManacostReaderCommunity?.create({
    root, request, write: () => write(), getMe: () => me, getRows: () => rows.filter(visibleRow),
    sessionKey: () => `${generation}:${csrf}`, expired, say, reload: () => loadComments(),
    updateReactions(id, reactions) {
      const item = rows.find(row => row.id === id);
      if (item) { item.reactions = reactions; community?.patchReactions(id); }
    },
    replaceComment(id, replacement) {
      const index = rows.findIndex(row => row.id === id);
      if (index >= 0) { rows[index] = { ...rows[index], ...replacement }; render(); }
    },
    setCommentingBlocked(blocked) {
      commentingBlocked = blocked === true; controls();
      if (commentingBlocked) say(blockedText);
    },
    offerLogin: () => offerLogin('поставить реакцию'),
  });
  function offerLogin(goal) { login.hidden = false; say(`Войдите через HearthPulse, чтобы ${goal}.`); login.querySelector('a')?.focus(); }

  function controls() {
    const locked = busy || Boolean(retryPayload);
    body.disabled = locked;
    const attaching = locked || attachmentUploading;
    if (attachmentInput) attachmentInput.disabled = attaching;
    if (attachmentPicker) attachmentPicker.disabled = attaching;
    if (attachmentRemove) attachmentRemove.disabled = attaching || !stagedAttachment;
    submit.disabled = attaching || commentingBlocked;
    retry.disabled = busy || commentingBlocked || attachmentUploading;
    submit.hidden = Boolean(retryPayload); retry.hidden = !retryPayload;
    if (!locked) lastFocus = null; else if (list.contains(document.activeElement)) lastFocus = document.activeElement;
    root.querySelectorAll('[data-comment-action]').forEach(button => { button.disabled = locked; });
    cancel.disabled = locked;
    form.setAttribute('aria-busy', String(busy));
    if (refreshProfile && profileNotice) {
      refreshProfile.hidden = profileNotice.hidden = !hasOlderIdentity();
      refreshProfile.disabled = locked;
    }
    if (count) count.textContent = `${Array.from(body.value).length} / 1000`;
  }
  function revokeAttachmentPreview() {
    if (attachmentPreviewUrl) URL.revokeObjectURL(attachmentPreviewUrl);
    attachmentPreviewUrl = null;
  }
  function clearAttachment() {
    revokeAttachmentPreview(); stagedAttachment = null;
    if (attachmentInput) attachmentInput.value = '';
    if (attachmentImage) attachmentImage.removeAttribute('src');
    if (attachmentStatus) attachmentStatus.textContent = '';
    if (attachmentPreview) attachmentPreview.hidden = true;
  }
  function clearDraft() {
    body.value = ''; clearAttachment(); parentId = null; retryPayload = null;
    reply.textContent = ''; reply.hidden = cancel.hidden = true; controls();
  }
  function reconcileLater(id, ticket) {
    reconcileIds.add(id); clearTimeout(reconcileTimer);
    reconcileTimer = setTimeout(() => {
      reconcileTimer = 0;
      if (current(ticket) && threadLoaded) void loadComments(false, new Set(reconcileIds));
    }, 1000);
  }
  function clearReconcile() {
    clearTimeout(reconcileTimer); reconcileTimer = 0; reconcileIds.clear();
  }
  function resetPrivate() {
    me = null; csrf = ''; busy = false; attachmentUploading = false; commentingBlocked = false; community?.reset(); clearDraft();
    composerIdentity?.replaceChildren();
    form.hidden = true; login.hidden = false;
    rows = rows.filter(item => item.status !== 'pending'); render();
  }
  function invalidate() {
    generation++;
    clearReconcile();
    for (const controller of requests) controller.abort();
    requests.clear();
  }
  function expired() {
    invalidate(); resetPrivate(); say('Сессия завершена. Войдите снова.');
    if (visible) void loadComments();
  }

  // Generation-bound responses; 401 ends the session.
  async function request(url, options = {}) {
    const ticket = generation, controller = new AbortController();
    requests.add(controller);
    const deadline = setTimeout(() => controller.abort(), 7000);
    try {
      const response = await fetch(url, { ...options, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
      const text = await response.text();
      if (!current(ticket)) throw stale;
      if (response.status === 401) { expired(); throw stale; }
      if (text.length > 524288) throw new Error('response_too_large');
      let data = null;
      try { data = text ? JSON.parse(text) : null; } catch { /* Callers validate their exact success DTO. */ }
      return { response, data };
    } catch (error) {
      if (!current(ticket)) throw stale;
      throw error;
    } finally {
      clearTimeout(deadline); requests.delete(controller);
    }
  }
  const write = () => ({ 'Content-Type': 'application/json', 'X-Reader-CSRF': csrf });
  const sendJson = (url, method, value) => request(url, { method, headers: write(), body: JSON.stringify(value) });
  function validAuthor(author, pending) {
    if (!author || !uuid.test(author.id) || typeof author.name !== 'string' || author.name.length > 160
      || (author.administrator !== undefined && typeof author.administrator !== 'boolean')) return false;
    const platformFlags = (author.hasTwitch === undefined && author.hasYoutube === undefined)
      || (typeof author.hasTwitch === 'boolean' && typeof author.hasYoutube === 'boolean');
    return pending ? author.profileUrl === null && author.avatarUrl === null && author.paidSubscriber === false && platformFlags
      : author.profileUrl === `/account/?reader=${author.id}` && typeof author.paidSubscriber === 'boolean' && platformFlags;
  }
  const side = value => Number.isSafeInteger(value) && value >= 1 && value <= 1600;
  const validImage = value => value && uuid.test(value.id) && side(value.width) && side(value.height);
  function validAttachment(attachment, commentId) {
    return (attachment === undefined || attachment === null) || (validImage(attachment)
      && attachment.url === `/reader-api/v1/comments/${commentId}/attachment`);
  }
  function valid(item) {
    if (!item || !uuid.test(item.id) || item.postId !== postId
      || (item.parentId !== null && !uuid.test(item.parentId))
      || !Number.isSafeInteger(item.version) || item.version < 1
      || !Number.isSafeInteger(item.createdAt) || item.createdAt < 0 || item.createdAt > 8640000000000000
      || !['published', 'deleted', 'pending'].includes(item.status)) return false;
    if (item.status === 'deleted') return item.body === null && item.author === null && (item.attachment === undefined || item.attachment === null);
    if (typeof item.body !== 'string' || Array.from(item.body).length > 1000 || !validAttachment(item.attachment, item.id)
      || !validAuthor(item.author, item.status === 'pending')) return false;
    return true;
  }
  function visibleRow(item) {
    return valid(item) && item.status !== 'deleted' && (item.status !== 'pending' || (me && item.author.id === me.id));
  }
  function showThreadStatus() {
    say(commentingBlocked ? blockedText : rows.some(visibleRow) ? '' : 'Комментариев пока нет. Начните обсуждение.');
  }
  function avatar(author) {
    if (typeof author.avatarVersion === 'string' && /^[A-Za-z0-9_-]{32}$/.test(author.avatarVersion)
      && author.avatarUrl === `/reader-api/v1/readers/${author.id}/avatar?v=${author.avatarVersion}`) return author.avatarUrl;
    return defaultAvatar;
  }
  function element(tag, className, text) {
    const node = document.createElement(tag); if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }
  function privateAvatar() {
    return typeof me?.avatarUrl === 'string' && /^\/reader-api\/v1\/profile\/avatar\?v=[A-Za-z0-9_-]{32}$/.test(me.avatarUrl) ? me.avatarUrl : null;
  }
  function hasOlderIdentity() {
    if (!me) return false;
    const own = rows.find(item => item.status === 'published' && item.author?.id === me.id)?.author;
    return Boolean(own && (own.name !== me.displayName || (own.avatarVersion || null) !== (privateAvatar()?.split('=')[1] || null)
      || own.hasTwitch !== (typeof me.twitchUrl === 'string') || own.hasYoutube !== (typeof me.youtubeUrl === 'string')));
  }
  function avatarNode(name, photo) {
    const placeholder = element('span', 'mc-comments__avatar mc-comments__avatar--placeholder', Array.from(name)[0] || 'М');
    placeholder.setAttribute('aria-hidden', 'true');
    if (!photo) return placeholder;
    const image = element('img', 'mc-comments__avatar'); image.src = photo; image.alt = ''; image.width = image.height = 40;
    image.decoding = 'async';
    image.addEventListener('error', () => image.replaceWith(placeholder), { once: true });
    return image;
  }
  function renderComposer() {
    if (!composerIdentity || !me) return;
    const name = typeof me.displayName === 'string' ? me.displayName : 'Читатель';
    const text = element('div', 'mc-comments__identity-text');
    text.append(element('span', 'mc-comments__name', name));
    if (typeof me.twitchUrl === 'string') text.append(authorBadge('twitch', 'Ваш Twitch'));
    if (typeof me.youtubeUrl === 'string') text.append(authorBadge('youtube', 'Ваш YouTube'));
    composerIdentity.replaceChildren(avatarNode(name, privateAvatar() || defaultAvatar), text);
  }
  function authorBadge(service, label) {
    const badge = element('span', `mc-comments__author-badge mc-comments__author-badge--${service}`);
    if (service === 'administrator') { badge.textContent = label; return badge; }
    badge.setAttribute('role', 'img'); badge.setAttribute('aria-label', label); badge.title = label;
    if (service === 'twitch' || service === 'youtube') {
      const template = $('[data-comments-' + service + '-icon]');
      if (template?.content.firstElementChild) badge.append(template.content.firstElementChild.cloneNode(true));
      return badge;
    }
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('aria-hidden', 'true'); svg.setAttribute('focusable', 'false');
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', 'm4 8 4.25 3.25L12 5l3.75 6.25L20 8l-1.7 10H5.7L4 8Z M6.25 20h11.5');
    svg.append(path); badge.append(svg); return badge;
  }
  function commentNode(item) {
    const node = element('article', `mc-comments__comment${item.parentId ? ' mc-comments__reply' : ''}`);
    node.dataset.pending = String(item.status === 'pending'); node.dataset.commentId = item.id;
    const time = element('time', 'mc-comments__meta', new Date(item.createdAt).toLocaleString('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }));
    time.dateTime = new Date(item.createdAt).toISOString();
    if (item.status === 'pending') node.append(element('strong', 'mc-comments__pending', 'Ваш комментарий · На проверке'), time);
    else {
      const header = element('header', 'mc-comments__identity');
      const author = element('a', 'mc-comments__author'); author.href = item.author.profileUrl;
      author.append(avatarNode(item.author.name, avatar(item.author)));
      const identityText = element('span', 'mc-comments__identity-text');
      identityText.append(element('span', 'mc-comments__name', item.author.name));
      if (item.author.hasTwitch) identityText.append(authorBadge('twitch', 'Автор ведёт Twitch'));
      if (item.author.hasYoutube) identityText.append(authorBadge('youtube', 'Автор ведёт YouTube'));
      if (item.author.administrator === true) identityText.append(authorBadge('administrator', 'Администратор'));
      if (item.author.paidSubscriber === true) identityText.append(authorBadge('paid', 'Платный подписчик'));
      author.append(identityText); header.append(author, time); node.append(header);
    }
    node.append(element('p', 'mc-comments__body', item.body));
    if (item.attachment) {
      const attachmentLink = element('a', 'mc-comments__image-link');
      attachmentLink.href = item.attachment.url;
      attachmentLink.target = '_blank'; attachmentLink.rel = 'noopener';
      attachmentLink.setAttribute('aria-label', 'Открыть изображение к комментарию');
      const image = element('img', 'mc-comments__image');
      image.src = item.attachment.url; image.alt = 'Изображение к комментарию';
      image.width = item.attachment.width; image.height = item.attachment.height;
      image.decoding = 'async'; image.loading = 'lazy';
      image.addEventListener('error', () => attachmentLink.remove(), { once: true });
      attachmentLink.append(image); node.append(attachmentLink);
    }
    const actions = element('div', 'mc-comments__actions');
    function action(kind, label, callback) {
      const button = element('button', 'mc-ui-button mc-ui-button--text', label); button.type = 'button'; button.dataset.commentAction = kind;
      button.addEventListener('click', callback); actions.append(button);
    }
    if (item.status === 'published' && !item.parentId) action('reply', 'Ответить', () => {
      if (!me) { offerLogin('ответить'); return; }
      if (commentingBlocked) { say(blockedText); return; }
      parentId = item.id; reply.textContent = `Ответ для ${item.author.name}`;
      reply.hidden = cancel.hidden = false; body.focus();
    });
    if (me && item.author.id === me.id) action('delete', 'Удалить', () => { void erase(item); });
    if (actions.childNodes.length) node.append(actions);
    return node;
  }
  // Row signature; community-ui owns controls.
  function rowFields(item) {
    const a = item.author || {}, f = item.attachment || {};
    return [item.status, item.version, item.parentId, item.createdAt, item.body, a.id, a.name, a.profileUrl, a.avatarUrl, a.avatarVersion,
      a.hasTwitch, a.hasYoutube, a.administrator, a.paidSubscriber, f.id, f.width, f.height, f.url, Boolean(me && a.id === me.id)];
  }
  // Restore the same control, then the first action.
  const controlKey = node => node.dataset.reaction ? `[data-reaction="${node.dataset.reaction}"]`
    : node.dataset.commentAction ? `[data-comment-action="${node.dataset.commentAction}"]`
    : ['.mc-comments__author', '.mc-comments__image-link', '[data-community-admin]>summary'].find(key => node.matches(key));
  const focusTarget = (row, key) => row && [key, '[data-comment-action]', '[data-reaction]', 'a[href]']
    .map(selector => selector && row.querySelector(selector + ':not(:disabled)')).find(Boolean);
  // Preserve unchanged rows; evict hidden rows.
  function render() {
    const next = rows.filter(visibleRow), ids = new Set(next.map(item => item.id));
    const blurred = !document.activeElement || document.activeElement === document.body;
    const active = list.contains(document.activeElement) ? document.activeElement : blurred && list.contains(lastFocus) ? lastFocus : null;
    const activeRow = active?.closest('[data-comment-id]'), siblings = activeRow ? [...list.children] : [], at = siblings.indexOf(activeRow);
    const fallbackId = [...siblings.slice(at), ...siblings.slice(0, at).reverse()].map(node => node.dataset.commentId).find(id => ids.has(id));
    const key = activeRow && fallbackId === activeRow.dataset.commentId ? controlKey(active) : null;
    if (!rendered.size) list.replaceChildren();
    for (const [id, entry] of rendered) if (!ids.has(id)) { entry.node.remove(); rendered.delete(id); }
    let anchor = list.firstElementChild;
    for (const item of next) {
      const fields = rowFields(item), entry = rendered.get(item.id);
      let node = entry?.node;
      if (!entry || entry.fields.some((value, index) => value !== fields[index])) {
        const fresh = commentNode(item);
        if (node) { if (node === anchor) anchor = fresh; node.replaceWith(fresh); }
        node = fresh; rendered.set(item.id, { node, fields });
      }
      if (node === anchor) anchor = node.nextElementSibling; else list.insertBefore(node, anchor);
    }
    while (anchor) { const extra = anchor; anchor = anchor.nextElementSibling; extra.remove(); }
    more.hidden = !cursor; controls();
    community?.render();
    if (!activeRow || active.isConnected) return;
    (focusTarget(rendered.get(fallbackId)?.node, key) || (!form.hidden && !body.disabled ? body : login.querySelector('a')))?.focus({ preventScroll: true });
  }
  async function loadMe(initial = false, refresh = false) {
    const { response, data } = await window.hsManacostReaderBootstrap(refresh);
    // Keep anonymous prefetch on initial 401.
    if (response.status === 401 && initial) { resetPrivate(); return; }
    if (response.status === 401) { expired(); throw stale; }
    if (!response.ok || !uuid.test(data?.profile?.id) || !Number.isSafeInteger(data.profile.version)
      || data.profile.version < 1 || typeof data.csrfToken !== 'string' || !data.csrfToken) throw new Error('profile_unavailable');
    me = data.profile; csrf = data.csrfToken;
    renderComposer();
    form.hidden = false; login.hidden = true;
    render();
    if (threadLoaded) showThreadStatus();
  }
  async function loadComments(append = false, preserveIds = null) {
    const ticket = generation;
    more.disabled = true;
    try {
      const { response, data } = await request(endpoint + (append && cursor ? `?cursor=${cursor}` : ''));
      if (!response.ok || !Array.isArray(data?.items) || data.items.length > 20
        || (data.nextCursor !== null && !uuid.test(data.nextCursor))) throw new Error('comments_unavailable');
      const page = data.items.filter(valid);
      const keep = preserveIds instanceof Set ? preserveIds : new Set();
      for (const id of reconcileIds) keep.add(id);
      const received = new Set(page.map(item => item.id));
      if (append) rows = [...rows, ...page.filter(item => !rows.some(old => old.id === item.id))];
      else if (keep.size) {
        const preserved = rows.filter(item => keep.has(item.id) && !received.has(item.id));
        rows = [...page, ...preserved].sort(byTime);
      } else rows = page;
      for (const id of received) reconcileIds.delete(id);
      cursor = data.nextCursor; threadLoaded = true; render();
      if (!(preserveIds instanceof Set)) showThreadStatus();
      return true;
    } catch (error) {
      if (error === stale || !current(ticket)) return;
      if (!(preserveIds instanceof Set)) { rows = []; cursor = null; threadLoaded = false; render(); }
      say('Не удалось загрузить комментарии. Повторите попытку позже.');
      return false;
    } finally { if (current(ticket)) more.disabled = false; }
  }
  async function publishIdentity() {
    if (!me || busy || retryPayload || !hasOlderIdentity()) return;
    const ticket = generation, profileVersion = me.version, profileId = me.id;
    busy = true; controls(); say('Обновляем профиль в комментариях…');
    try {
      const { response, data } = await sendJson('/reader-api/v1/community/profile', 'PUT', { profileVersion });
      if (response.status === 409) {
        await loadMe(false, true); say('Профиль изменился. Проверьте его и обновите комментарии ещё раз.'); return;
      }
      if (!response.ok || data?.profile?.id !== profileId || data.profile.version !== profileVersion) throw new Error('profile_refresh_failed');
      if (await loadComments()) say('Фото и значки в комментариях обновлены.');
    } catch (error) {
      if (error !== stale && current(ticket)) say('Не удалось обновить профиль в комментариях. Повторите попытку.');
    } finally { release(ticket); }
  }
  const discardAttachment = id => request(`/reader-api/v1/comment-attachments/${id}`, {
    method: 'DELETE', headers: { Accept: 'application/json', 'X-Reader-CSRF': csrf },
  });
  function attachmentContentType(file) {
    const supplied = typeof file?.type === 'string' ? file.type.toLowerCase() : '';
    if (attachmentTypes.has(supplied)) return supplied;
    const extension = typeof file?.name === 'string' ? file.name.toLowerCase().match(/\.(jpe?g|png|webp)$/)?.[1] : null;
    return extension ? `image/${extension === 'jpg' ? 'jpeg' : extension}` : null;
  }
  function previewAttachment(file, message) {
    revokeAttachmentPreview();
    attachmentPreviewUrl = URL.createObjectURL(file);
    if (attachmentImage) attachmentImage.src = attachmentPreviewUrl;
    if (attachmentStatus) attachmentStatus.textContent = message;
    if (attachmentPreview) attachmentPreview.hidden = false;
  }
  async function stageAttachment(file) {
    if (!me || busy || retryPayload || attachmentUploading) return;
    const contentType = attachmentContentType(file);
    if (!file || !contentType || file.size < 1 || file.size > maxAttachmentBytes) {
      say(file?.size > maxAttachmentBytes ? tooLargeText : 'Прикрепите JPEG, PNG или WebP до 4 МБ.');
      if (attachmentInput) attachmentInput.value = '';
      return;
    }
    const previous = stagedAttachment;
    const ticket = generation;
    attachmentUploading = true;
    clearAttachment();
    previewAttachment(file, 'Подготавливаем изображение…');
    controls();
    try {
      if (previous) {
        if (!(await discardAttachment(previous.id)).response.ok) throw new Error('attachment_discard_failed');
      }
      const { response, data } = await request('/reader-api/v1/comment-attachments', {
        method: 'PUT', body: file,
        headers: { Accept: 'application/json', 'Content-Type': contentType, 'X-Reader-CSRF': csrf },
      });
      if (!response.ok || !validImage(data?.attachment)) {
        const code = response.status;
        say(code === 413 ? tooLargeText
          : code === 429 ? 'Можно подготовить не более трёх изображений одновременно. Уберите ненужное и повторите попытку.'
          : data?.error === 'attachment_busy' ? 'Обработка занята. Повторите через несколько секунд.'
          : code === 503 ? 'Изображения временно недоступны. Повторите.'
          : code === 403 ? 'Сессия изменилась. Обновите страницу и повторите.'
          : code === 400 || data?.error === 'invalid_attachment' ? 'Файл не распознан. Выберите JPEG, PNG или WebP.'
          : 'Не удалось загрузить изображение. Повторите.');
        clearAttachment();
        return;
      }
      stagedAttachment = Object.freeze({ id: data.attachment.id, width: data.attachment.width, height: data.attachment.height });
      if (attachmentStatus) attachmentStatus.textContent = 'Изображение готово и будет опубликовано вместе с комментарием.';
    } catch (error) {
      if (error !== stale && current(ticket)) {
        clearAttachment();
        say('Не удалось подготовить изображение. Повторите попытку.');
      }
    } finally { release(ticket, true); }
  }
  async function removeAttachment() {
    if (!stagedAttachment || busy || retryPayload || attachmentUploading) return;
    const attachment = stagedAttachment;
    const ticket = generation;
    attachmentUploading = true;
    clearAttachment();
    controls();
    try {
      const { response } = await discardAttachment(attachment.id);
      if (!response.ok) throw new Error('attachment_discard_failed');
      say('Изображение убрано.');
    } catch (error) {
      if (error !== stale && current(ticket)) say('Не удалось убрать изображение. Оно останется приватным и будет удалено автоматически.');
    } finally { release(ticket, true); }
  }
  async function erase(item) {
    if (!me || busy || retryPayload || !valid(item) || item.author?.id !== me.id || !confirm('Удалить комментарий?')) return;
    const ticket = generation; busy = true; controls();
    try {
      const { response } = await sendJson(`/reader-api/v1/comments/${item.id}`, 'DELETE', { version: item.version });
      if (!response.ok) throw new Error('remove_failed');
      reconcileIds.delete(item.id); rows = rows.filter(row => row.id !== item.id); render();
      await loadComments();
    } catch (error) { if (error !== stale && current(ticket)) say('Не удалось удалить комментарий. Повторите попытку.'); }
    finally { release(ticket); }
  }
  async function send(payload) {
    if(!me || busy || commentingBlocked)return;
    const ticket=generation; busy=true; controls();
    try {
      const { response, data } = await sendJson(endpoint, 'POST', payload);
      if (response.status === 403 && data?.error === 'commenting_blocked') {
        commentingBlocked = true; retryPayload = null; say(blockedText); return;
      }
      if (response.status === 409) {
        retryPayload = null;
        await loadMe(false, true); say('Профиль или обсуждение изменились. Проверьте текст и отправьте комментарий ещё раз.'); return;
      }
      if (response.status >= 400 && response.status < 500) {
        retryPayload = null;
        say(response.status === 429 ? 'Слишком много комментариев. Подождите минуту.' : 'Комментарий не отправлен. Проверьте текст или обновите страницу.'); return;
      }
      if (!response.ok || !valid(data?.comment)) throw new Error('ambiguous_result');
      clearDraft();
      const index = rows.findIndex(item => item.id === data.comment.id);
      if (index >= 0) rows[index] = data.comment;
      else rows = [...rows, data.comment].sort(byTime);
      render();
      if (current(ticket)) {
        say(data.comment.status === 'pending' ? 'Ваш комментарий · На проверке' : 'Комментарий опубликован.');
        reconcileLater(data.comment.id, ticket);
      }
    } catch (error) {
      if (error === stale || !current(ticket)) return;
      retryPayload = Object.freeze({ ...payload });
      say('Результат отправки неизвестен. Повторите тот же комментарий — повторная попытка не создаст дубликат.');
    } finally { release(ticket); }
  }
  form.addEventListener('submit', event => {
    event.preventDefault(); if (!me || busy || retryPayload) return;
    if (commentingBlocked) { say(blockedText); return; }
    const payload = { body: body.value.trim(), parentId, operationId: crypto.randomUUID(), profileVersion: me.version, attachmentId: stagedAttachment?.id ?? null };
    if (Array.from(payload.body).length < 2 || Array.from(payload.body).length > 1000 || new TextEncoder().encode(JSON.stringify(payload)).length > 4096) {
      say('Комментарий должен содержать от 2 до 1000 символов и помещаться в 4 КБ.'); return;
    }
    void send(payload);
  });
  retry.addEventListener('click', () => { if (retryPayload) void send(retryPayload); });
  refreshProfile?.addEventListener('click', () => { void publishIdentity(); });
  body.addEventListener('input', controls);
  body.addEventListener('paste', event => {
    const item = [...(event.clipboardData?.items || [])].find(candidate => attachmentTypes.has(candidate.type));
    const file = item?.getAsFile();
    if (!file || busy || retryPayload || attachmentUploading) return;
    event.preventDefault();
    void stageAttachment(file);
  });
  attachmentInput?.addEventListener('change', () => { void stageAttachment(attachmentInput.files?.[0]); });
  attachmentPicker?.addEventListener('click', () => { attachmentInput?.click(); });
  attachmentRemove?.addEventListener('click', () => { void removeAttachment(); });
  cancel.addEventListener('click', () => { if (!busy && !retryPayload) { parentId = null; reply.hidden = cancel.hidden = true; body.focus(); } });
  more.addEventListener('click', () => { void loadComments(true); });

  async function start() {
    visible = true; invalidate(); resetPrivate();
    threadLoaded = false;
    showLoading();
    const ticket = generation;
    // Render public rows immediately; pending rows require verified viewer identity.
    const commentsReady = loadComments();
    const refreshIdentity = refreshBootstrap; refreshBootstrap = false;
    try { await loadMe(true, refreshIdentity); }
    catch (error) {
      if (error !== stale && current(ticket)) resetPrivate();
    }
    await commentsReady;
  }
  function activate() {
    if (activated) return;
    activated = true; activationObserver?.disconnect(); activationObserver = null;
    void start();
  }
  function scheduleStart() {
    if (activated) { void start(); return; }
    if (!('IntersectionObserver' in window)) { activate(); return; }
    activationObserver?.disconnect();
    activationObserver = new IntersectionObserver(entries => {
      if (entries.some(entry => entry.isIntersecting)) activate();
    }, { rootMargin: '640px 0px' });
    activationObserver.observe(root);
  }
  addEventListener('pagehide', () => { visible = false; activationObserver?.disconnect(); activationObserver = null; invalidate(); resetPrivate(); rows = []; cursor = null; refreshBootstrap = true; render(); });
  addEventListener('pageshow', event => { if (event.persisted) scheduleStart(); });
  scheduleStart();
})();
