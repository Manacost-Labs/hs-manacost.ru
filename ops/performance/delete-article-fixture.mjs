// APIRequestContext shares browser cookies but survives document navigation.
export async function deleteArticleFixture(request, baseURL, id, title) {
  if (new URL(baseURL).hostname !== 'test.hs-manacost.ru'
      || !Number.isSafeInteger(id) || id < 1
      || !/^Admin performance probe [0-9]{13}$/.test(title)) return false;
  const send = (route, method = 'GET', nonce) => request.fetch(new URL(route, baseURL).href, {
    method, timeout: 30_000, maxRedirects: 0,
    ...(nonce ? { headers: { 'X-WP-Nonce': nonce } } : {}),
  });
  try {
    const nonceResponse = await send('/wp-admin/admin-ajax.php?action=rest-nonce');
    const nonce = await nonceResponse.text();
    if (!nonceResponse.ok() || !/^[a-z0-9]{10}$/i.test(nonce)) return false;
    const route = `/wp-json/wp/v2/posts/${id}?context=edit`;
    const existing = await send(route, 'GET', nonce);
    if (existing.status() === 404) return true;
    if (!existing.ok()) return false;
    const post = await existing.json();
    const ownerResponse = await send('/wp-json/wp/v2/users/me', 'GET', nonce);
    if (!ownerResponse.ok()) return false;
    const owner = await ownerResponse.json();
    const ownsTitle = post.title?.raw === title
      || [0, 1, 2, 3, 4].some(sample => post.title?.raw === `${title} ${sample}`);
    const expected = 'Synthetic staging performance content. '.repeat(1750).trim();
    const content = post.content?.raw;
    if (!Number.isSafeInteger(owner.id) || owner.id < 1 || post.author !== owner.id || !ownsTitle
        || typeof content !== 'string' || content.replace(/<[^>]*>/g, '').trim() !== expected) return false;
    try {
      await send(`/wp-json/wp/v2/posts/${id}?force=true`, 'DELETE', nonce);
    } catch { /* A lost response still requires an absence check, never a repeated DELETE. */ }
    return (await send(route, 'GET', nonce)).status() === 404;
  } catch { // Keep credentials and private response bodies out of diagnostic evidence.
    return false;
  }
}
