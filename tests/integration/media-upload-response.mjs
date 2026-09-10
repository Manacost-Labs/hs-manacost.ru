// WordPress legacy async-upload.php and current AJAX response contracts.
export function parseAsyncUpload(responseText) {
  const plainId = responseText.match(/^\s*([1-9]\d*)\s*$/)?.[1];
  if (plainId) return { ok: true, id: Number(plainId), url: '' };
  try {
    const parsed = JSON.parse(responseText);
    if (parsed?.success && Number(parsed.data?.id) > 0) {
      return { ok: true, id: Number(parsed.data.id), url: String(parsed.data?.url ?? '') };
    }
    return { ok: false, message: String(parsed?.data?.message ?? parsed?.data ?? '') };
  } catch {
    const id = responseText.match(/post=(\d+)/)?.[1];
    const url = responseText.match(/data-clipboard-text="([^"]+)"/)?.[1];
    if (Number(id) > 0 && url) return { ok: true, id: Number(id), url };
    return { ok: false, message: responseText.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 300) };
  }
}
