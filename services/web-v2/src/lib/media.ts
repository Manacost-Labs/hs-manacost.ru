const ALLOWED_HOST = "hs-manacost.ru";
const ALLOWED_PATH = "/wp-content/uploads/";
const ALLOWED_MIME_TYPES = new Set([
  "image/avif",
  "image/gif",
  "image/jpeg",
  "image/png",
  "image/webp",
]);

export const MAX_IMAGE_BYTES = 5 * 1024 * 1024;
export const MEDIA_TIMEOUT_MS = 8_000;
export const MAX_CONCURRENT_MEDIA_REQUESTS = 6;

let activeMediaRequests = 0;

export function parseMediaSource(value: string): URL | null {
  let source: URL;
  try {
    source = new URL(value);
  } catch {
    return null;
  }
  if (
    source.protocol !== "https:" ||
    source.hostname !== ALLOWED_HOST ||
    (source.port !== "" && source.port !== "443") ||
    source.username ||
    source.password ||
    !source.pathname.startsWith(ALLOWED_PATH) ||
    !/\.(?:avif|gif|jpe?g|png|webp)$/i.test(source.pathname)
  ) {
    return null;
  }
  source.search = "";
  source.hash = "";
  return source;
}

export function normalizeRasterMimeType(value: string | null): string | null {
  const type = value?.split(";", 1)[0]?.trim().toLowerCase() ?? "";
  return ALLOWED_MIME_TYPES.has(type) ? type : null;
}

export function createMediaFetchInit(timeoutMs = MEDIA_TIMEOUT_MS): RequestInit {
  return {
    headers: { Accept: "image/avif,image/webp,image/png,image/jpeg,image/gif" },
    cache: "no-store",
    redirect: "error",
    signal: AbortSignal.timeout(timeoutMs),
  };
}

export function tryAcquireMediaSlot(): (() => void) | null {
  if (activeMediaRequests >= MAX_CONCURRENT_MEDIA_REQUESTS) return null;
  activeMediaRequests += 1;
  let released = false;
  return () => {
    if (released) return;
    released = true;
    activeMediaRequests -= 1;
  };
}

export async function readBoundedBody(
  body: ReadableStream<Uint8Array> | null,
  maximum = MAX_IMAGE_BYTES,
): Promise<Uint8Array | null> {
  if (!body) return null;
  const reader = body.getReader();
  const chunks: Uint8Array[] = [];
  let size = 0;
  try {
    while (true) {
      const { done, value } = await reader.read();
      if (done) break;
      size += value.byteLength;
      if (size > maximum) {
        await reader.cancel("media exceeds byte limit");
        return null;
      }
      chunks.push(value);
    }
  } finally {
    reader.releaseLock();
  }
  const result = new Uint8Array(size);
  let offset = 0;
  for (const chunk of chunks) {
    result.set(chunk, offset);
    offset += chunk.byteLength;
  }
  return result;
}
