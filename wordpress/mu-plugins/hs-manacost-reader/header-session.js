(() => {
  'use strict';
  // Site-wide sign-in indicator on the «Кабинет» menu entry. Guests never call
  // the Reader API: only a browser that saw a session sets this non-secret hint.
  const hint = 'mc_reader_signed_in';
  const setHint = on => {
    document.cookie = `${hint}=${on ? '1; Max-Age=2592000' : '; Max-Age=0'}; Path=/; SameSite=Lax; Secure`;
  };
  window.hsManacostReaderHint = setHint;
  const hinted = document.cookie.split('; ').includes(`${hint}=1`);

  const decorate = data => {
    const name = typeof data?.user?.displayName === 'string' ? data.user.displayName.trim().slice(0, 40) : '';
    if (!name) return;
    const avatar = data.profile?.avatarUrl;
    const safeAvatar = typeof avatar === 'string' && /^\/reader-api\/v1\/profile\/avatar\?v=[\w-]+$/.test(avatar) ? avatar : '';
    for (const link of document.querySelectorAll('a[data-mc-reader-entry]')) {
      const mark = document.createElement(safeAvatar ? 'img' : 'span');
      mark.className = 'mc-reader-entry__avatar';
      mark.setAttribute('aria-hidden', 'true');
      if (safeAvatar) { mark.src = safeAvatar; mark.alt = ''; mark.width = 24; mark.height = 24; mark.decoding = 'async'; }
      else mark.textContent = Array.from(name)[0].toUpperCase();
      const label = document.createElement('span');
      label.className = 'mc-reader-entry__name';
      label.textContent = name;
      const plain = document.createElement('span');
      plain.className = 'mc-reader-entry__label';
      plain.textContent = 'Кабинет';
      link.replaceChildren(mark, label, plain);
      link.title = `Личный кабинет: ${name}`;
      link.setAttribute('aria-label', `Личный кабинет: ${name}`);
      link.closest('li')?.classList.add('mc-reader-entry--signed-in');
    }
  };

  const run = () => {
    // Cabinet/article pages reuse the shared bootstrap; elsewhere only hinted browsers ask /me.
    const shared = typeof window.hsManacostReaderBootstrap === 'function';
    if (!shared && !hinted) return;
    const probe = shared
      ? window.hsManacostReaderBootstrap()
      : fetch('/reader-api/v1/me', { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' },
        signal: AbortSignal.timeout(5000) })
        .then(async response => ({ response, data: response.ok ? await response.json() : null }));
    probe.then(({ response, data }) => {
      if (response.status === 401) setHint(false);
      else if (response.ok) { setHint(true); decorate(data); }
    }).catch(() => {});
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, { once: true });
  else run();
})();
