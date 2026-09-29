// The caller must establish ownership before passing an attachment ID here.
export async function deleteUploadFixture(rest, id) {
  if (!Number.isSafeInteger(id) || id < 1) throw new Error('Invalid fixture ID');
  let deletionError;
  try {
    const deleted = await rest(`media/${id}?force=true`, 'DELETE');
    if (deleted.status !== 200 || deleted.data?.deleted !== true) {
      throw new Error('Attachment deletion was not confirmed');
    }
  } catch (error) {
    deletionError = error;
  }
  // A lost DELETE response can still mean the server removed the fixture.
  // Check the outcome instead of repeating a mutation.
  const absent = await rest(`media/${id}?context=edit`);
  if (absent.status === 404) return;
  throw deletionError ?? new Error('Attachment still exists after deletion');
}
