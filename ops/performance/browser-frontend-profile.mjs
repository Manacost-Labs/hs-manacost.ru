// Serialized into the browser by Playwright; never return element text or URL queries.
export function collectFrontendProfile() {
  const hosts = new Set(['test.hs-manacost.ru', 'hs-manacost.ru', 'hs-manacost.com',
    'fonts.googleapis.com', 'fonts.gstatic.com', 'api.kolodahs.ru',
    'cards.hearthstonejson.com', 'blz-contentstack-assets.akamaized.net']);
  const safeResource = value => {
    try {
      const url = new URL(value);
      if (url.protocol !== 'https:' || url.username || url.password || !hosts.has(url.hostname)
        || url.pathname.length > 512
        || !/\.(?:avif|webp|png|jpe?g|gif|svg|woff2?|ttf|css|js)$/i.test(url.pathname)) return null;
      return { origin: url.origin, path: url.pathname };
    } catch { return null; }
  };
  const timing = entry => ({
    start_ms: Math.round(entry.startTime), response_end_ms: Math.round(entry.responseEnd),
    duration_ms: Math.round(entry.duration), transfer_bytes: entry.transferSize,
    encoded_body_bytes: entry.encodedBodySize,
  });
  // Match the collector's existing vertical image gate without changing it.
  const visibleImages = Array.from(globalThis.document?.querySelectorAll?.('img') ?? [])
    .filter(image => {
      const rect = image.getBoundingClientRect();
      return rect.top < globalThis.innerHeight && rect.bottom > 0 && rect.width > 0 && rect.height > 0;
    });
  const imageResources = predicate => visibleImages.filter(predicate)
    .map(image => safeResource(image.currentSrc || image.src)).filter(Boolean).slice(0, 12);
  const lcp = globalThis.__hsFrontend?.lcpEntry;
  const resources = performance.getEntriesByType('resource');
  const match = lcp?.url ? resources.find(entry => entry.name === lcp.url) : null;
  const tag = lcp?.tag;
  return {
    lcp_tag: typeof tag === 'string' && /^[A-Z][A-Z0-9]{0,15}$/.test(tag) ? tag : null,
    lcp_kind: ['text', 'img', 'background-image', 'detached', 'video', 'svg-image'].includes(lcp?.kind) ? lcp.kind : null,
    lcp_loading: ['lazy', 'eager', 'auto'].includes(lcp?.loading) ? lcp.loading : null,
    lcp_fetchpriority: ['high', 'low', 'auto'].includes(lcp?.fetchpriority) ? lcp.fetchpriority : null,
    lcp_resource: safeResource(lcp?.url),
    failed_visible_resources: imageResources(image => image.complete && !image.naturalWidth),
    pending_visible_resources: imageResources(image => !image.complete),
    // Cross-origin timing/byte fields may be zero when Timing-Allow-Origin is absent.
    lcp_timing: match && safeResource(match.name) ? timing(match) : null,
    slowest_resources: resources.filter(entry => safeResource(entry.name))
      .sort((left, right) => right.duration - left.duration).slice(0, 12)
      .map(entry => ({ resource: safeResource(entry.name), ...timing(entry) })),
  };
}
