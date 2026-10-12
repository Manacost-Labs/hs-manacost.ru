// The caller must establish ownership before passing an attachment ID here.
export async function deleteUploadFixture(rest, id) {
  if (!Number.isSafeInteger(id) || id < 1) throw new Error('Invalid fixture ID');
  let deleteStatus = null;
  let verifyStatus = null;
  try {
    const deleted = await rest(`media/${id}?force=true`, 'DELETE');
    deleteStatus = deleted.status;
  } catch { /* Verify the outcome of an uncertain mutation below. */ }
  // A lost DELETE response can still mean the server removed the fixture.
  // Check the outcome instead of repeating a mutation.
  try {
    const absent = await rest(`media/${id}?context=edit`);
    verifyStatus = absent.status;
    if (verifyStatus === 404) return;
  } catch { /* Keep private transport errors out of the diagnostic report. */ }
  const error = new Error('Attachment absence was not confirmed');
  error.name = 'UploadFixtureCleanupError';
  error.deleteStatus = Number.isInteger(deleteStatus) ? deleteStatus : null;
  error.verifyStatus = Number.isInteger(verifyStatus) ? verifyStatus : null;
  throw error;
}
