import { createHash, randomUUID } from 'node:crypto';

/** Seed pre-direct-publication rows to test legacy review/retention, not submission. */
export function legacyPending(comments, subject, input) {
  const data = comments.input(input); const profile = comments.profile(subject);
  const id = randomUUID(); const now = comments.now();
  const requestDigest = createHash('sha256').update(JSON.stringify(data)).digest('hex');
  comments.db.prepare(`INSERT INTO reader_comments
    (id,issuer,subject,author_profile_id,post_id,body,parent_id,status,version,profile_version,operation_id,request_digest,public_consent,created_at,updated_at,attachment_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 1, ?, ?, ?, 1, ?, ?, NULL)`)
    .run(id, comments.issuer, subject, profile.id, data.postId, data.body, data.parentId,
      data.profileVersion, data.operationId, requestDigest, now, now);
  return comments.list(data.postId, { viewerSubject: subject, limit: 50 }).items.find(item => item.id === id);
}
