// Retry only a browser-level network-change failure on idempotent navigation.
// Forms, uploads, fetch mutations and HTTP/authentication failures are not retried.
export async function navigateWithNetworkRecovery(page, url, options) {
  for (let retries = 0; ; retries++) {
    try {
      return { response: await page.goto(url, options), retries };
    } catch (error) {
      if (retries >= 2 || !String(error?.message).includes('net::ERR_NETWORK_CHANGED')) throw error;
      await page.waitForTimeout((retries + 1) * 1000);
    }
  }
}

// Keep Chromium's host resolver, user agent and authenticated cookies for cleanup.
// The caller owns this separate, static nonce document and closes it afterwards.
export async function createBrowserFixtureRequest(page, baseURL) {
  const nonceURL = new URL('/wp-admin/admin-ajax.php?action=rest-nonce', baseURL).href;
  const { response: initial } = await navigateWithNetworkRecovery(page, nonceURL, { waitUntil: 'domcontentloaded', timeout: 30_000 });
  if (!initial) throw new Error('Fixture nonce response unavailable');
  return {
    async fetch(url, options) {
      if (url === nonceURL && options.method === 'GET') return initial;
      const result = await page.evaluate(async ({ url, method, headers, timeout }) => {
        const response = await fetch(url, {
          method, headers, redirect: 'manual', cache: 'no-store', signal: AbortSignal.timeout(timeout),
        });
        return { status: response.status, body: await response.text() };
      }, { url, method: options.method, headers: options.headers, timeout: options.timeout });
      return {
        status: () => result.status,
        ok: () => result.status >= 200 && result.status < 300,
        text: async () => result.body,
        json: async () => JSON.parse(result.body),
      };
    },
  };
}

// Stable labels explain failures without exporting URLs, credentials or response text.
export function classifyDiagnosticFailure(error) {
  const message = typeof error?.message === 'string' ? error.message : '';
  const patterns = [
    [/ERR_NETWORK_CHANGED/, 'network-change'],
    [/Execution context was destroyed/, 'navigation-context-lost'],
    [/interrupted by another navigation/, 'navigation-interrupted'],
    [/performance probe is unavailable/, 'probe-unavailable'],
    [/returned HTTP/, 'http-error'],
    [/round-trip|content changed|content was modified/, 'roundtrip-mismatch'],
  ];
  return patterns.find(([pattern]) => pattern.test(message))?.[1]
    ?? (error?.name === 'TimeoutError' ? 'timeout' : 'other');
}
