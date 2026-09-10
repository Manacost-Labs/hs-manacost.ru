import { ReaderComments } from './comments-store.js';
import { createEditorialClient, createPaidTitleClient, createReaderPermissionsClient } from './community-clients.js';

/** Validate the complete pilot boundary before an additive schema migration. */
export function createCommunity({ options, db, env = process.env, transport = fetch }) {
  if (env.READER_COMMENTS_ENABLED !== '1') return null;
  if (options.deployment !== 'staging' || options.origin !== 'https://test.hs-manacost.ru'
    || options.issuer !== 'https://hearthpulse.net/identity' || options.clientId !== 'manacost-reader-staging') {
    throw new Error('Comments require the explicit staging identity bridge');
  }
  const editorial = createEditorialClient({ key: env.READER_EDITORIAL_KEY,
    username: env.READER_EDITORIAL_USERNAME, password: env.READER_EDITORIAL_PASSWORD }, transport);
  const entitlements = createPaidTitleClient(options, transport);
  const permissions = createReaderPermissionsClient(options, transport);
  const comments = new ReaderComments({ db, issuer: options.issuer, origin: options.origin });
  return { comments, editorial, entitlements, permissions };
}
