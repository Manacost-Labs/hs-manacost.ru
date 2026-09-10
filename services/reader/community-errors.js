export class ReaderCommentError extends Error {
  constructor(status, code, message = code) { super(message); this.name = 'ReaderCommentError'; this.status = status; this.code = code; }
}

export const fail = (status, code) => { throw new ReaderCommentError(status, code); };
export const isUUID = value => typeof value === 'string' && /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(value);
export const isVersion = value => Number.isSafeInteger(value) && value >= 0;

export function transaction(db, run) {
  db.exec('BEGIN IMMEDIATE');
  try { const result = run(); db.exec('COMMIT'); return result; }
  catch (error) { try { db.exec('ROLLBACK'); } catch {} throw error; }
}
