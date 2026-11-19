(() => {
  'use strict';
  const root = document.querySelector('[data-mc-article-favorite]');
  const postId = Number(root?.dataset.postId);
  if (!root || !Number.isSafeInteger(postId) || postId < 1) return;
  const toggle = root.querySelector('[data-favorite-toggle]');
  const label = root.querySelector('[data-favorite-label]');
  const login = root.querySelector('[data-favorite-login]');
  const status = root.querySelector('[data-favorite-status]');
  if (!(toggle instanceof HTMLButtonElement) || !label || !(login instanceof HTMLAnchorElement) || !status) return;

  // On a Newspaper cover the action joins the view counter; a guest's press survives login once.
  const meta = document.querySelector('.td-post-title > .td-module-meta-info');
  const inMeta = meta instanceof HTMLElement;
  if (inMeta) { root.classList.add('mc-article-favorite--meta'); meta.append(root); }
  const pendingKey = 'mc-article-favorite-pending';
  const takePending = () => {
    try { const value = sessionStorage.getItem(pendingKey); sessionStorage.removeItem(pendingKey); return value === String(postId); } catch { return false; }
  };

  const endpoint = `/reader-api/v1/favorites/${postId}`;
  let csrf = '', saved = false, known = false, guest = false, busy = false, hydrating = true, interactionPending = false, stopped = false, statusRequest = null, operation = '', fade = 0;
  const say = (text, transient = false) => {
    clearTimeout(fade);
    status.textContent = text;
    if (inMeta && transient && text) fade = setTimeout(() => { status.textContent = ''; }, 3500);
  };
  function render() {
    toggle.hidden = !login.hidden;
    toggle.disabled = busy || interactionPending || (hydrating && !inMeta);
    toggle.setAttribute('aria-pressed', String(saved));
    if (inMeta && guest) toggle.title = 'Войдите через HearthPulse, чтобы сохранять статьи';
    else toggle.removeAttribute('title');
    label.textContent = operation === 'save' ? 'Сохраняем…' : operation === 'remove' ? 'Удаляем…'
      : inMeta ? (saved ? 'Сохранено' : 'Сохранить')
        : hydrating && !known ? 'Проверяем…' : saved ? 'В избранном' : 'Сохранить статью';
  }
  async function request(url, options = {}) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 5000);
    try {
      const response = await fetch(url, { ...options, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
      const text = await response.text();
      if (text.length > 8192) throw new Error('response_too_large');
      let data = null; try { data = text ? JSON.parse(text) : null; } catch { /* Exact DTOs below decide success. */ }
      return { response, data };
    } finally { clearTimeout(timer); }
  }
  function unavailable(announce = true) {
    known = false; csrf = ''; busy = false; hydrating = false; interactionPending = false; operation = '';
    if (inMeta) guest = true; else login.hidden = false;
    if (announce || !inMeta) say('Войдите через HearthPulse, чтобы сохранять статьи.');
    render();
  }
  function toLogin() {
    try { sessionStorage.setItem(pendingKey, String(postId)); } catch { /* Login still works. */ }
    window.location.assign(login.href);
  }
  function validStatus(data) {
    return data?.favorite?.postId === postId && typeof data.favorite.saved === 'boolean' && typeof data.csrfToken === 'string' && /^[A-Za-z0-9_-]{43}$/.test(data.csrfToken);
  }
  async function load(showFailure = false) {
    if (stopped) return false;
    if (statusRequest) return statusRequest;
    statusRequest = (async () => {
      try {
        // A missing client must fail after statusRequest is assigned.
        const { response, data } = await Promise.resolve().then(() => window.hsManacostReaderBootstrap());
        if (stopped) return false;
        if (response.status === 401) { unavailable(showFailure); return false; }
        if (!response.ok || !validStatus(data)) throw new Error('favorite_status');
        saved = data.favorite.saved; csrf = data.csrfToken; known = true; guest = false; hydrating = false; login.hidden = true; say(''); render();
        if (takePending() && !saved) setTimeout(() => { void change(); }, 0);
        return true;
      } catch {
        hydrating = false;
        if (!stopped && showFailure) say('Не удалось проверить избранное. Повторите попытку.');
        if (!stopped) render();
        return false;
      } finally { statusRequest = null; }
    })();
    return statusRequest;
  }
  async function change() {
    if (busy || interactionPending || stopped) return;
    interactionPending = true; say(''); render();
    let previous = saved;
    let changed = false;
    try {
      if (!known && !await load(true)) { if (guest) toLogin(); return; }
      previous = saved; saved = !previous; changed = true; busy = true; operation = saved ? 'save' : 'remove'; render();
      const { response, data } = await request(endpoint, { method: saved ? 'PUT' : 'DELETE', headers: { 'X-Reader-CSRF': csrf } });
      if (response.status === 401) { saved = previous; unavailable(); return; }
      if (!response.ok || data?.saved !== saved || data?.postId !== postId) throw new Error('favorite_write');
      say(saved ? 'Статья сохранена в избранное.' : 'Статья удалена из избранного.', true);
    } catch {
      if (changed) saved = previous;
      say('Не удалось изменить избранное. Повторите попытку.');
    } finally {
      if (!stopped && login.hidden) { busy = false; interactionPending = false; operation = ''; render(); }
    }
  }
  toggle.addEventListener('click', () => {
    if (inMeta && guest && !busy && !interactionPending) { toLogin(); return; }
    void change();
  });
  addEventListener('pagehide', () => { stopped = true; clearTimeout(fade); });
  render();
  void load(false);
})();
