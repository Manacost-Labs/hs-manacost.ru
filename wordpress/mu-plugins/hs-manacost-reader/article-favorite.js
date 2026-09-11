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
  let csrf = '', saved = false, known = false, busy = false, interactionPending = false, stopped = false, statusRequest = null;
  const say = text => { status.textContent = text; };
  function render() {
    toggle.hidden = !login.hidden;
    toggle.disabled = busy || interactionPending;
    toggle.setAttribute('aria-pressed', String(saved));
    label.textContent = saved ? 'В избранном' : 'Сохранить статью';
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
    login.hidden = false; known = false; csrf = ''; busy = false; interactionPending = false;
    say('Войдите через HearthPulse, чтобы сохранять статьи.'); render();
  }
  function validStatus(data) {
    return data && typeof data.saved === 'boolean' && typeof data.csrfToken === 'string' && /^[A-Za-z0-9_-]{43}$/.test(data.csrfToken);
  }
  async function load(showFailure = false) {
    if (stopped) return false;
    if (statusRequest) return statusRequest;
    statusRequest = (async () => {
      try {
        const { response, data } = await request(endpoint);
        if (stopped) return false;
        if (response.status === 401) { unavailable(); return false; }
        if (!response.ok || !validStatus(data)) throw new Error('favorite_status');
        saved = data.saved; csrf = data.csrfToken; known = true; login.hidden = true; say(''); render(); return true;
      } catch {
        if (!stopped && showFailure) say('Не удалось проверить избранное. Повторите попытку.');
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
      previous = saved; saved = !previous; changed = true; busy = true; render();
      const { response, data } = await request(endpoint, { method: saved ? 'PUT' : 'DELETE', headers: { 'X-Reader-CSRF': csrf } });
      if (response.status === 401) { saved = previous; unavailable(); return; }
      if (!response.ok || data?.saved !== saved || data?.postId !== postId) throw new Error('favorite_write');
      say(saved ? 'Статья сохранена в избранное.' : 'Статья удалена из избранного.');
    } catch {
      if (changed) saved = previous;
      say('Не удалось изменить избранное. Повторите попытку.');
    } finally {
      if (!stopped && login.hidden) { busy = false; interactionPending = false; render(); }
    }
  });
  const defer = () => { void load(false); };
  if ('requestIdleCallback' in window) window.requestIdleCallback(defer, { timeout: 1200 });
  else setTimeout(defer, 800);
  addEventListener('pagehide', () => { stopped = true; });
  render();
})();
