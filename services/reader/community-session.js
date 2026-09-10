import { fail } from './community-errors.js';

/** Call synchronously immediately before returning private data or mutating the shared SQLite database. */
export function requireSameSession(store, id, session, signal) {
  signal.throwIfAborted();
  const current = store.getSession(id);
  if (!current || current.userId !== session.userId || current.upstreamToken !== session.upstreamToken) fail(401, 'not_authenticated');
  return current;
}
