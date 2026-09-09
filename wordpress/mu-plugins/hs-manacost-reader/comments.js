(() => {
  'use strict';
  const root = document.querySelector('[data-mc-comments]');
  const uuid = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
  if (!root || !/^[1-9][0-9]*$/.test(root.dataset.postId || '')) return;
  const postId = Number(root.dataset.postId);
  if (!Number.isSafeInteger(postId)) return;
  const endpoint = `/reader-api/v1/threads/${postId}/comments`;
  const $ = selector => root.querySelector(selector);
  const status = $('[data-comments-status]'), list = $('[data-comments-list]');
  const form = $('[data-comments-form]'), body = $('[data-comments-body]');
  const consent = $('[data-comments-consent]'), submit = $('[data-comments-submit]');
  const retry = $('[data-comments-retry]'), login = $('[data-comments-login]');
  const reply = $('[data-comments-reply]'), cancel = $('[data-comments-cancel]');
  const dataTools = $('[data-comments-data]'), exportButton = $('[data-comments-export]');
  const eraseButton = $('[data-comments-erase]');
  const more = document.createElement('button');
  more.type = 'button'; more.textContent = 'Показать ещё'; more.hidden = true;
  more.className = 'mc-comments__more'; list.after(more);
  const requests = new Set();
  const stale = new Error('stale');
  let generation = 0, visible = true, me = null, csrf = '', rows = [], cursor = null;
  let parentId = null, retryPayload = null, busy = false;
  const say = message => { status.textContent = message; };
  const current = ticket => visible && ticket === generation;

  function controls() {
    body.disabled = consent.disabled = busy || Boolean(retryPayload);
    submit.disabled = retry.disabled = exportButton.disabled = eraseButton.disabled = busy;
    submit.hidden = Boolean(retryPayload); retry.hidden = !retryPayload;
    root.querySelectorAll('[data-comment-action]').forEach(button => { button.disabled = busy || Boolean(retryPayload); });
    cancel.disabled = busy || Boolean(retryPayload);
  }
  function clearDraft() {
    body.value = ''; consent.checked = false; parentId = null; retryPayload = null;
    reply.textContent = ''; reply.hidden = cancel.hidden = true; controls();
  }
  function resetPrivate() {
    me = null; csrf = ''; busy = false; clearDraft();
    form.hidden = dataTools.hidden = true; login.hidden = false;
    rows = rows.filter(item => item.status !== 'pending'); render();
  }
  function invalidate() {
    generation++;
    for (const controller of requests) controller.abort();
    requests.clear();
  }
  function expired() {
    invalidate(); resetPrivate(); say('Сессия завершена. Войдите снова.');
    if (visible) void loadComments();
  }

  /** Every asynchronous completion is tied to this page generation, including body reads. */
  async function request(url, options = {}) {
    const ticket = generation, controller = new AbortController();
    requests.add(controller);
    const deadline = setTimeout(() => controller.abort(), 7000);
    try {
      const response = await fetch(url, { ...options, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
      const text = await response.text();
      if (!current(ticket)) throw stale;
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
  function validAuthor(author, pending) {
    if (!author || !uuid.test(author.id) || typeof author.name !== 'string' || author.name.length > 160) return false;
    return pending ? author.profileUrl === null && author.avatarUrl === null && author.paidSubscriber === false
      : author.profileUrl === `/account/?reader=${author.id}` && typeof author.paidSubscriber === 'boolean';
  }
  function valid(item) {
    if (!item || !uuid.test(item.id) || item.postId !== postId
      || (item.parentId !== null && !uuid.test(item.parentId))
      || !Number.isSafeInteger(item.version) || item.version < 1
      || !Number.isSafeInteger(item.createdAt) || item.createdAt < 0 || item.createdAt > 8640000000000000
      || !['published', 'deleted', 'pending'].includes(item.status)) return false;
    if (item.status === 'deleted') return item.body === null && item.author === null;
    if (typeof item.body !== 'string' || Array.from(item.body).length > 1000 || !validAuthor(item.author, item.status === 'pending')) return false;
    return item.status !== 'pending' || (me && item.author.id === me.id);
  }
  function avatar(author) {
    return typeof author.avatarVersion === 'string' && /^[A-Za-z0-9_-]{32}$/.test(author.avatarVersion)
      && author.avatarUrl === `/reader-api/v1/readers/${author.id}/avatar?v=${author.avatarVersion}` ? author.avatarUrl : null;
  }
  function element(tag, className, text) {
    const node = document.createElement(tag); if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  }
  function commentNode(item) {
    const node = element('article', `mc-comments__comment${item.parentId ? ' mc-comments__reply' : ''}`);
    node.dataset.pending = String(item.status === 'pending');
    if (item.status === 'deleted') { node.textContent = 'Комментарий удалён.'; return node; }
    if (item.status === 'pending') node.append(element('strong', 'mc-comments__pending', 'Ваш комментарий · На проверке'));
    else {
      const author = element('a', 'mc-comments__author'); author.href = item.author.profileUrl;
      const photo = avatar(item.author);
      const placeholder = element('span', 'mc-comments__avatar mc-comments__avatar--placeholder', Array.from(item.author.name)[0] || 'М');
      placeholder.setAttribute('aria-hidden', 'true');
      if (photo) {
        const image = element('img', 'mc-comments__avatar'); image.src = photo; image.alt = ''; image.width = image.height = 44;
        image.addEventListener('error', () => image.replaceWith(placeholder), { once: true }); author.append(image);
      } else author.append(placeholder);
      author.append(element('span', 'mc-comments__name', item.author.name)); node.append(author);
      if (item.author.paidSubscriber === true) node.append(element('span', 'mc-comments__paid', 'Платный подписчик'));
    }
    const time = element('time', 'mc-comments__meta', new Date(item.createdAt).toLocaleString('ru-RU', { dateStyle: 'medium', timeStyle: 'short' }));
    time.dateTime = new Date(item.createdAt).toISOString(); node.append(time);
    node.append(element('p', 'mc-comments__body', item.body));
    const actions = element('div', 'mc-comments__actions');
    function action(label, callback) {
      const button = element('button', '', label); button.type = 'button'; button.dataset.commentAction = '';
      button.addEventListener('click', callback); actions.append(button);
    }
    if (item.status === 'published' && !item.parentId) action('Ответить', () => {
      if (!me) { login.querySelector('a')?.focus(); return; }
      parentId = item.id; reply.textContent = `Ответ для ${item.author.name}`;
      reply.hidden = cancel.hidden = false; body.focus();
    });
    if (me && item.author.id === me.id) action('Удалить', () => { void erase(item); });
    if (actions.childNodes.length) node.append(actions);
    return node;
  }
  function render() {
    list.replaceChildren(...rows.filter(valid).map(commentNode));
    more.hidden = !cursor; controls();
  }
  async function loadMe() {
    const { response, data } = await request('/reader-api/v1/me');
    if (response.status === 401) { expired(); throw stale; }
    if (!response.ok || !uuid.test(data?.profile?.id) || !Number.isSafeInteger(data.profile.version)
      || data.profile.version < 1 || typeof data.csrfToken !== 'string' || !data.csrfToken) throw new Error('profile_unavailable');
    me = data.profile; csrf = data.csrfToken;
    form.hidden = dataTools.hidden = false; login.hidden = true;
  }
  async function loadComments(append = false) {
    const ticket = generation;
    more.disabled = true;
    try {
      const { response, data } = await request(endpoint + (append && cursor ? `?cursor=${cursor}` : ''));
      if (response.status === 401) { expired(); return; }
      if (!response.ok || !Array.isArray(data?.items) || data.items.length > 20
        || (data.nextCursor !== null && !uuid.test(data.nextCursor))) throw new Error('comments_unavailable');
      const page = data.items.filter(valid);
      rows = append ? [...rows, ...page.filter(item => !rows.some(old => old.id === item.id))] : page;
      cursor = data.nextCursor; render();
      say(rows.length ? '' : 'Комментариев пока нет. Начните обсуждение.');
    } catch (error) {
      if (error === stale || !current(ticket)) return;
      rows = []; cursor = null; render(); say('Не удалось загрузить комментарии. Повторите попытку позже.');
    } finally { if (current(ticket)) more.disabled = false; }
  }
  async function erase(item) {
    if (!me || busy || retryPayload || !valid(item) || item.author?.id !== me.id || !confirm('Удалить комментарий?')) return;
    const ticket = generation; busy = true; controls();
    try {
      const { response } = await request(`/reader-api/v1/comments/${item.id}`, { method: 'DELETE', headers: write(), body: JSON.stringify({ version: item.version }) });
      if (response.status === 401) { expired(); return; }
      if (!response.ok) throw new Error('remove_failed');
      await loadComments();
    } catch (error) { if (error !== stale && current(ticket)) say('Не удалось удалить комментарий. Повторите попытку.'); }
    finally { if (current(ticket)) { busy = false; controls(); } }
  }
  async function send(payload) {
    if (!me || busy) return;
    const ticket = generation; busy = true; controls();
    try {
      const { response, data } = await request(endpoint, { method: 'POST', headers: write(), body: JSON.stringify(payload) });
      if (response.status === 401) { expired(); return; }
      if (response.status === 409) {
        retryPayload = null; consent.checked = false;
        await loadMe(); say('Профиль или обсуждение изменились. Проверьте текст и подтвердите согласие ещё раз.'); return;
      }
      if (response.status >= 400 && response.status < 500) {
        retryPayload = null; consent.checked = false;
        say(response.status === 429 ? 'Слишком много комментариев. Подождите минуту.' : 'Комментарий не отправлен. Проверьте текст или обновите страницу.'); return;
      }
      if (!response.ok || !valid(data?.comment)) throw new Error('ambiguous_result');
      clearDraft(); await loadComments();
      if (current(ticket)) say(data.comment.status === 'pending' ? 'Ваш комментарий · На проверке' : 'Комментарий опубликован.');
    } catch (error) {
      if (error === stale || !current(ticket)) return;
      retryPayload = Object.freeze({ ...payload });
      say('Результат отправки неизвестен. Повторите тот же комментарий — повторная попытка не создаст дубликат.');
    } finally { if (current(ticket)) { busy = false; controls(); } }
  }
  form.addEventListener('submit', event => {
    event.preventDefault(); if (!me || busy || retryPayload) return;
    const payload = { body: body.value.trim(), parentId, operationId: crypto.randomUUID(), profileVersion: me.version, publicConsent: true };
    if (Array.from(payload.body).length < 2 || Array.from(payload.body).length > 1000 || new TextEncoder().encode(JSON.stringify(payload)).length > 4096) {
      say('Комментарий должен содержать от 2 до 1000 символов и помещаться в 4 КБ.'); return;
    }
    if (!consent.checked) { say('Подтвердите согласие на публикацию данных профиля.'); return; }
    void send(payload);
  });
  retry.addEventListener('click', () => { if (retryPayload) void send(retryPayload); });
  cancel.addEventListener('click', () => { if (!busy && !retryPayload) { parentId = null; reply.hidden = cancel.hidden = true; body.focus(); } });
  more.addEventListener('click', () => { void loadComments(true); });

  exportButton.addEventListener('click', async () => {
    if (!me || busy) return;
    const ticket = generation, items = [], seen = new Set(); let next = null, complete = false;
    busy = true; controls();
    try {
      for (let page = 0; page < 50; page++) {
        const { response, data } = await request(`/reader-api/v1/community/export${next ? `?cursor=${next}` : ''}`);
        if (response.status === 401) { expired(); return; }
        if (!response.ok || !Array.isArray(data?.items) || data.items.length > 100) throw new Error('export_failed');
        items.push(...data.items);
        if (data.nextCursor === null) { complete = true; break; }
        if (!uuid.test(data.nextCursor) || seen.has(data.nextCursor)) throw new Error('export_cursor');
        next = data.nextCursor; seen.add(next);
      }
      if (!complete || !current(ticket)) throw new Error('export_incomplete');
      const url = URL.createObjectURL(new Blob([JSON.stringify({ items }, null, 2)], { type: 'application/json' }));
      const link = element('a'); link.href = url; link.download = 'manacost-comments.json'; link.click();
      setTimeout(() => URL.revokeObjectURL(url), 1000); say('Выгрузка подготовлена.');
    } catch (error) { if (error !== stale && current(ticket)) say('Не удалось подготовить полную выгрузку. Ничего не скачано.'); }
    finally { if (current(ticket)) { busy = false; controls(); } }
  });
  eraseButton.addEventListener('click', async () => {
    if (!me || busy || !confirm('Удалить все мои комментарии и публичный профиль? Кабинет читателя останется без изменений.')) return;
    const ticket = generation, profileId = me.id; busy = true; controls();
    try {
      const { response, data } = await request('/reader-api/v1/community/profile', { method: 'DELETE', headers: write(), body: JSON.stringify({ profileId, confirm: 'erase-community' }) });
      if (response.status === 401) { expired(); return; }
      if (!response.ok || data?.erased !== true) throw new Error('erase_failed');
      clearDraft(); await loadComments(); if (current(ticket)) say('Комментарии и публичный профиль удалены. Кабинет сохранён.');
    } catch (error) { if (error !== stale && current(ticket)) say('Не удалось удалить данные. Повторите попытку позже.'); }
    finally { if (current(ticket)) { busy = false; controls(); } }
  });

  async function start() {
    visible = true; invalidate(); resetPrivate();
    const ticket = generation;
    try { await loadMe(); }
    catch (error) { if (error === stale || !current(ticket)) return; resetPrivate(); }
    if (current(ticket)) await loadComments();
  }
  addEventListener('pagehide', () => { visible = false; invalidate(); resetPrivate(); rows = []; cursor = null; render(); });
  addEventListener('pageshow', event => { if (event.persisted) void start(); });
  void start();
})();
