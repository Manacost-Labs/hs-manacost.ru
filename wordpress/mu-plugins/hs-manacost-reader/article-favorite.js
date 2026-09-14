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

  const endpoint = `/reader-api/v1/favorites/${postId}`;
  let csrf = '', saved = false, known = false, busy = false, hydrating = true, interactionPending = false, stopped = false, statusRequest = null, operation = '';
  const say = text => { status.textContent = text; };
  function render() {
    toggle.hidden = !login.hidden;
    toggle.disabled = busy || hydrating || interactionPending;
    toggle.setAttribute('aria-pressed', String(saved));
    label.textContent = hydrating && !known ? 'Проверяем…' : operation === 'save' ? 'Сохраняем…' : operation === 'remove' ? 'Удаляем…' : saved ? 'В избранном' : 'Сохранить статью';
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
  function unavailable() {
    login.hidden = false; known = false; csrf = ''; busy = false; hydrating = false; interactionPending = false; operation = '';
    say('Войдите через HearthPulse, чтобы сохранять статьи.'); render();
  }
  function validStatus(data) {
    return data?.favorite?.postId === postId && typeof data.favorite.saved === 'boolean' && typeof data.csrfToken === 'string' && /^[A-Za-z0-9_-]{43}$/.test(data.csrfToken);
  }
  async function load(showFailure = false) {
    if (stopped) return false;
    if (statusRequest) return statusRequest;
    statusRequest = (async () => {
      try {
        const { response, data } = await window.hsManacostReaderBootstrap();
        if (stopped) return false;
        if (response.status === 401) { unavailable(); return false; }
        if (!response.ok || !validStatus(data)) throw new Error('favorite_status');
        saved = data.favorite.saved; csrf = data.csrfToken; known = true; hydrating = false; login.hidden = true; say(''); render(); return true;
      } catch {
        hydrating = false;
        if (!stopped && showFailure) say('Не удалось проверить избранное. Повторите попытку.');
        if (!stopped) render();
        return false;
      } finally { statusRequest = null; }
    })();
    return statusRequest;
  }
  toggle.addEventListener('click', async () => {
    if (busy || interactionPending || stopped) return;
    interactionPending = true; render();
    let previous = saved;
    let changed = false;
    try {
      if (!known && !await load(true)) return;
      previous = saved; saved = !previous; changed = true; busy = true; operation = saved ? 'save' : 'remove'; render();
      const { response, data } = await request(endpoint, { method: saved ? 'PUT' : 'DELETE', headers: { 'X-Reader-CSRF': csrf } });
      if (response.status === 401) { saved = previous; unavailable(); return; }
      if (!response.ok || data?.saved !== saved || data?.postId !== postId) throw new Error('favorite_write');
      say(saved ? 'Статья сохранена в избранное.' : 'Статья удалена из избранного.');
    } catch {
      if (changed) saved = previous;
      say('Не удалось изменить избранное. Повторите попытку.');
    } finally {
      if (!stopped && login.hidden) { busy = false; interactionPending = false; operation = ''; render(); }
    }
  });
  addEventListener('pagehide', () => { stopped = true; });
  render();
  void load(false);
})();
