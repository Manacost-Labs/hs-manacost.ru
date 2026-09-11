import { ReaderComments } from './comments-store.js';
import { ReaderCommentAttachments } from './comment-attachments.js';
import { ReaderArticleFavorites } from './article-favorites.js';
import { createEditorialClient, createFavoriteEditorialClient, createPaidTitleClient, createReaderPermissionsClient } from './community-clients.js';

/** Validate the complete pilot boundary before an additive schema migration. */
export function createCommunity({ options, db, env = process.env, transport = fetch }) {
  if (env.READER_COMMENTS_ENABLED !== '1') return null;
  if (options.deployment !== 'staging' || options.origin !== 'https://test.hs-manacost.ru'
    || options.issuer !== 'https://hearthpulse.net/identity' || options.clientId !== 'manacost-reader-staging') {
    throw new Error('Comments require the explicit staging identity bridge');
  }
  const editorial = createEditorialClient({ key: env.READER_EDITORIAL_KEY,
    username: env.READER_EDITORIAL_USERNAME, password: env.READER_EDITORIAL_PASSWORD }, transport);
  const favoriteEditorial = createFavoriteEditorialClient({ key: env.READER_EDITORIAL_KEY,
    username: env.READER_EDITORIAL_USERNAME, password: env.READER_EDITORIAL_PASSWORD }, transport);
  const entitlements = createPaidTitleClient(options, transport);
  const permissions = createReaderPermissionsClient(options, transport);
  const attachments = new ReaderCommentAttachments({ db, issuer: options.issuer });
  const comments = new ReaderComments({ db, issuer: options.issuer, origin: options.origin, attachments });
  const favorites = new ReaderArticleFavorites({ db, issuer: options.issuer });
  return { comments, attachments, favorites, editorial, favoriteEditorial, entitlements, permissions };
}
