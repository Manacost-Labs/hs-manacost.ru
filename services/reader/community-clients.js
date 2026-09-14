import { createHmac } from 'node:crypto';

const EDITORIAL_ORIGINS = new Map([
  ['https://test.hs-manacost.ru', 'test.hs-manacost.ru'],
  ['https://hs-manacost.ru', 'hs-manacost.ru'],
]);
const LOCAL_EDITORIAL_ORIGINS = new Map([
  ['https://test.hs-manacost.ru', 'http://127.0.0.1:18185'],
  ['https://hs-manacost.ru', 'http://127.0.0.1:18184'],
]);
const ENTITLEMENTS_URL = 'https://hearthpulse.net/identity/reader-entitlements';
const PERMISSIONS_URL = 'https://hearthpulse.net/identity/reader-permissions';
const basic = (name, password) => `Basic ${Buffer.from(`${name}:${password}`).toString('base64')}`;
const exactKeys = (value, keys) => value && Object.getPrototypeOf(value) === Object.prototype
  && Object.keys(value).length === keys.length && keys.every(key => Object.hasOwn(value, key));

async function boundedJSON(response, signal) {
  if (response.status !== 200 || !/^application\/json(?:;|$)/i.test(response.headers.get('content-type') ?? '')
    || Number(response.headers.get('content-length') ?? 0) > 32768 || !response.body) throw new Error('Invalid community upstream response');
  const reader = response.body.getReader();
  const chunks = []; let size = 0;
  try {
    while (true) {
      signal.throwIfAborted();
      const { done, value } = await reader.read();
      if (done) break;
      size += value.byteLength;
      if (size > 32768) throw new Error('Community response too large');
      chunks.push(Buffer.from(value));
    }
    signal.throwIfAborted();
    return JSON.parse(Buffer.concat(chunks).toString('utf8'));
  } finally { await reader.cancel().catch(() => {}); }
}

function batch(values, valid) {
  if (!Array.isArray(values) || values.length < 1 || values.length > 20
    || new Set(values).size !== values.length || !values.every(valid)) throw new Error('Invalid community batch');
}

/** A WordPress permalink may percent-encode non-ASCII slug characters. */
function validPublicArticlePath(path) {
  return typeof path === 'string' && path.length <= 2000 && /^\/(?!\/)/.test(path)
    && !/[\\\s?#]/.test(path) && !/%(?![0-9a-f]{2})/i.test(path)
    && !/%(?:2f|5c|3f|23)/i.test(path) && !/^\/(?:wp-|reader-|account(?:\/|$))/i.test(path);
}

/** Only an authenticated, freshly checked editorial response can permit article data. */
function createArticleClient({ key, username, password, origin, editorialOrigin }, route, transport = fetch) {
  const site = EDITORIAL_ORIGINS.get(origin);
  const localOrigin = LOCAL_EDITORIAL_ORIGINS.get(origin);
  if (typeof key !== 'string' || key.length < 43 || typeof username !== 'string' || !/^[a-z0-9-]{1,64}$/.test(username)
    || typeof password !== 'string' || password.length < 43 || !site || typeof route !== 'string'
    || (editorialOrigin !== undefined && editorialOrigin !== localOrigin)) throw new Error('Editorial configuration invalid');
  const url = `${localOrigin}/wp-json${route}`;
  return {
    async get(ids, parent = AbortSignal.timeout(2000)) {
      batch(ids, id => Number.isSafeInteger(id) && id > 0);
      const signal = AbortSignal.any([parent, AbortSignal.timeout(2000)]);
      const body = JSON.stringify({ ids }); const timestamp = String(Math.floor(Date.now() / 1000));
      const signature = createHmac('sha256', key).update(`POST\n${route}\n${timestamp}\n${body}`).digest('hex');
      const response = await transport(url, { method: 'POST', redirect: 'error', signal, body,
        headers: { authorization: basic(username, password), 'content-type': 'application/json',
          'x-reader-time': timestamp, 'x-reader-signature': signature } });
      const data = await boundedJSON(response, signal);
      if (!exactKeys(data, ['site', 'threads']) || data.site !== site
        || !Array.isArray(data.threads) || data.threads.length !== ids.length) throw new Error('Invalid editorial response');
      const result = new Map();
      for (let index = 0; index < ids.length; index++) {
        const item = data.threads[index];
        if (item?.postId !== ids[index] || typeof item.allowed !== 'boolean'
          || !exactKeys(item, item.allowed ? ['postId', 'allowed', 'title', 'path'] : ['postId', 'allowed'])) throw new Error('Invalid editorial record');
        if (item.allowed && (typeof item.title !== 'string' || item.title.length > 1000
          || !validPublicArticlePath(item.path))) throw new Error('Invalid article location');
        result.set(item.postId, item);
      }
      return result;
    },
  };
}

/** The manually reviewed discussion pilot is the only eligible comment surface. */
export function createEditorialClient(config, transport = fetch) {
  return createArticleClient(config, '/manacost-reader/v1/threads', transport);
}

/** Saving an article uses its own editorial predicate and never trusts a browser title/path. */
export function createFavoriteEditorialClient(config, transport = fetch) {
  return createArticleClient(config, '/manacost-reader/v1/favorites', transport);
}

/** Current canonical roles, never a cached token/browser claim. Failure denies privileged operations. */
export function createReaderPermissionsClient({ clientId, clientSecret }, transport = fetch) {
  if (!['manacost-reader-staging', 'manacost-reader-production'].includes(clientId)
    || typeof clientSecret !== 'string' || clientSecret.length < 43) throw new Error('Permissions configuration invalid');
  return {
    async get(subjects, parent = AbortSignal.timeout(2000)) {
      batch(subjects, subject => typeof subject === 'string' && /^[A-Za-z0-9_-]{1,128}$/.test(subject));
      const signal = AbortSignal.any([parent, AbortSignal.timeout(2000)]);
      const response = await transport(PERMISSIONS_URL, { method: 'POST', redirect: 'error', signal,
        headers: { authorization: basic(clientId, clientSecret), 'content-type': 'application/json' }, body: JSON.stringify({ subjects }) });
      const data = await boundedJSON(response, signal);
      if (!exactKeys(data, ['permissions']) || !Array.isArray(data.permissions) || data.permissions.length !== subjects.length) throw new Error('Invalid permissions response');
      const result = new Map();
      for (let index = 0; index < subjects.length; index++) {
        const item = data.permissions[index];
        if (!exactKeys(item, ['subject', 'canModerateComments']) || item.subject !== subjects[index]
          || typeof item.canModerateComments !== 'boolean') throw new Error('Invalid permissions record');
        result.set(item.subject, item.canModerateComments);
      }
      return result;
    },
  };
}

/** The title is optional decoration, never access control or a cached claim of payment. */
export function createPaidTitleClient({ clientId, clientSecret }, transport = fetch) {
  if (!['manacost-reader-staging', 'manacost-reader-production'].includes(clientId)
    || typeof clientSecret !== 'string' || clientSecret.length < 43) throw new Error('Paid title configuration invalid');
  return {
    async get(subjects, parent = AbortSignal.timeout(2000)) {
      try {
        batch(subjects, subject => typeof subject === 'string' && /^[A-Za-z0-9_-]{1,128}$/.test(subject));
        const signal = AbortSignal.any([parent, AbortSignal.timeout(2000)]);
        const response = await transport(ENTITLEMENTS_URL, { method: 'POST', redirect: 'error', signal,
          headers: { authorization: basic(clientId, clientSecret), 'content-type': 'application/json' }, body: JSON.stringify({ subjects }) });
        const data = await boundedJSON(response, signal); const now = Date.now();
        if (!exactKeys(data, ['entitlements']) || !Array.isArray(data.entitlements) || data.entitlements.length !== subjects.length) return new Map();
        const result = new Map();
        for (let index = 0; index < subjects.length; index++) {
          const item = data.entitlements[index];
          if (!exactKeys(item, ['subject', 'paid', 'checkedAt', 'validUntil']) || item.subject !== subjects[index]
            || typeof item.paid !== 'boolean') return new Map();
          const valid = item.paid === true && Number.isSafeInteger(item.checkedAt) && Number.isSafeInteger(item.validUntil)
            && item.checkedAt <= now && item.checkedAt > now - 1800000 && item.validUntil > now
            && item.validUntil <= item.checkedAt + 1800000;
          result.set(item.subject, valid);
        }
        return result;
      } catch { return new Map(); }
    },
  };
}
