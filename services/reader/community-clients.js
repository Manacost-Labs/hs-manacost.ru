import { createHmac } from 'node:crypto';

const EDITORIAL_URL = 'https://test.hs-manacost.ru/wp-json/manacost-reader/v1/threads';
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

/** Only an authenticated, freshly checked editorial response can make a thread readable. */
export function createEditorialClient({ key, username, password }, transport = fetch) {
  if (typeof key !== 'string' || key.length < 43 || typeof username !== 'string' || !/^[a-z0-9-]{1,64}$/.test(username)
    || typeof password !== 'string' || password.length < 43) throw new Error('Editorial configuration invalid');
  return {
    async get(ids, parent = AbortSignal.timeout(2000)) {
      batch(ids, id => Number.isSafeInteger(id) && id > 0);
      const signal = AbortSignal.any([parent, AbortSignal.timeout(2000)]);
      const body = JSON.stringify({ ids }); const timestamp = String(Math.floor(Date.now() / 1000));
      const signature = createHmac('sha256', key).update(`POST\n/manacost-reader/v1/threads\n${timestamp}\n${body}`).digest('hex');
      const response = await transport(EDITORIAL_URL, { method: 'POST', redirect: 'error', signal, body,
        headers: { authorization: basic(username, password), 'content-type': 'application/json',
          'x-reader-time': timestamp, 'x-reader-signature': signature } });
      const data = await boundedJSON(response, signal);
      if (!exactKeys(data, ['site', 'threads']) || data.site !== 'test.hs-manacost.ru'
        || !Array.isArray(data.threads) || data.threads.length !== ids.length) throw new Error('Invalid editorial response');
      const result = new Map();
      for (let index = 0; index < ids.length; index++) {
        const item = data.threads[index];
        if (item?.postId !== ids[index] || typeof item.allowed !== 'boolean'
          || !exactKeys(item, item.allowed ? ['postId', 'allowed', 'title', 'path'] : ['postId', 'allowed'])) throw new Error('Invalid editorial record');
        if (item.allowed && (typeof item.title !== 'string' || item.title.length > 1000
          || typeof item.path !== 'string' || item.path.length > 2000 || !/^\/(?!\/)/.test(item.path)
          || /[\\\s?#%]/.test(item.path) || /^\/(?:wp-|reader-|account(?:\/|$))/i.test(item.path))) throw new Error('Invalid article location');
        result.set(item.postId, item);
      }
      return result;
    },
  };
}

/** Current canonical roles, never a cached token/browser claim. Failure denies privileged operations. */
export function createReaderPermissionsClient({ clientId, clientSecret }, transport = fetch) {
  if (clientId !== 'manacost-reader-staging' || typeof clientSecret !== 'string' || clientSecret.length < 43) throw new Error('Permissions configuration invalid');
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
  if (clientId !== 'manacost-reader-staging' || typeof clientSecret !== 'string' || clientSecret.length < 43) throw new Error('Paid title configuration invalid');
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
