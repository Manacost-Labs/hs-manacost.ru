(() => {
  'use strict';
  const mirrorHosts = new Set(['hs-manacost.com', 'www.hs-manacost.com']);
  if (mirrorHosts.has(window.location.hostname)) {
    const { pathname, search, hash } = window.location;
    window.location.replace(`https://hs-manacost.ru${pathname}${search}${hash}`);
    return;
  }
  let current = null;
  window.hsManacostReaderBootstrap = (refresh = false) => {
    if (refresh) current = null;
    if (current) return current;
    const favorite = document.querySelector('[data-mc-article-favorite]');
    const postId = Number(favorite?.dataset.postId);
    const query = Number.isSafeInteger(postId) && postId > 0 ? `?postId=${postId}` : '';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 5000);
    current = fetch(`/reader-api/v1/bootstrap${query}`, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
      headers: { Accept: 'application/json' } })
      .then(async response => {
        const text = await response.text();
        if (text.length > 8192) throw new Error('response_too_large');
        let data = null; try { data = text ? JSON.parse(text) : null; } catch { /* DTO checks belong to each surface. */ }
        return { response, data };
      })
      .finally(() => clearTimeout(timer));
    return current;
  };
})();
