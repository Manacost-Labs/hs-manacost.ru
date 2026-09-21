(() => {
  'use strict';
  // Reuse the early account client.
  if (typeof window.hsManacostReaderBootstrap === 'function') return;
  const mirrorHosts = new Set(['hs-manacost.com', 'www.hs-manacost.com']);
  if (mirrorHosts.has(window.location.hostname)) {
    const { pathname, search, hash } = window.location;
    window.location.replace(`https://hs-manacost.ru${pathname}${search}${hash}`);
    return;
  }
  let current = null;
  const invalidate = (reason = 'superseded') => {
    const previous = current;
    current = null;
    previous?.controller.abort(new DOMException(reason, 'AbortError'));
  };
  const bootstrap = (refresh = false) => {
    if (refresh) invalidate('superseded');
    if (current) return current.promise;
    const favorite = document.querySelector('[data-mc-article-favorite]');
    const postId = Number(favorite?.dataset.postId);
    const query = Number.isSafeInteger(postId) && postId > 0 ? `?postId=${postId}` : '';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(new DOMException('bootstrap_timeout', 'TimeoutError')), 5000);
    const entry = { controller, promise: null };
    current = entry;
    entry.promise = fetch(`/reader-api/v1/bootstrap${query}`, { credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
      headers: { Accept: 'application/json' } })
      .then(async response => {
        const text = await response.text();
        // A transport/mock may finish despite cancellation; never return old identity.
        if (controller.signal.aborted) throw controller.signal.reason;
        if (text.length > 8192) throw new Error('response_too_large');
        const data = text ? JSON.parse(text) : null;
        // Anonymous is a valid initial state; don't duplicate a fast guest probe.
        if (!response.ok && response.status !== 401 && current === entry) current = null;
        return { response, data };
      })
      .catch(error => {
        if (current === entry) current = null;
        throw error;
      })
      .finally(() => clearTimeout(timer));
    return entry.promise;
  };
  bootstrap.invalidate = invalidate;
  window.hsManacostReaderBootstrap = bootstrap;
  window.addEventListener('pagehide', () => invalidate('pagehide'));
  // Account consumers share the early request, including any pending error.
  if (document.querySelector('[data-mc-reader-root]')) void bootstrap().catch(() => {});
})();
