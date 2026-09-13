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
export const MAX_QUEUED_MEDIA_REQUESTS = 24;
export const MEDIA_SLOT_WAIT_MS = 3_000;

let activeMediaRequests = 0;
type ReleaseMediaSlot = () => void;
type MediaWaiter = {
  resolve: (release: ReleaseMediaSlot | null) => void;
  signal?: AbortSignal;
  onAbort?: () => void;
  timer?: ReturnType<typeof setTimeout>;
  settled: boolean;
};
const mediaQueue: MediaWaiter[] = [];

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

export function createMediaFetchInit({
  timeoutMs = MEDIA_TIMEOUT_MS,
  signal,
}: { timeoutMs?: number; signal?: AbortSignal } = {}): RequestInit {
  const timeoutSignal = AbortSignal.timeout(timeoutMs);
  return {
    headers: { Accept: "image/avif,image/webp,image/png,image/jpeg,image/gif" },
    cache: "no-store",
    redirect: "error",
    signal: signal ? AbortSignal.any([signal, timeoutSignal]) : timeoutSignal,
  };
}

function removeWaiter(waiter: MediaWaiter): void {
  const index = mediaQueue.indexOf(waiter);
  if (index >= 0) mediaQueue.splice(index, 1);
}

function settleWaiter(waiter: MediaWaiter, release: ReleaseMediaSlot | null): boolean {
  if (waiter.settled) return false;
  waiter.settled = true;
  if (waiter.timer) clearTimeout(waiter.timer);
  if (waiter.signal && waiter.onAbort) waiter.signal.removeEventListener("abort", waiter.onAbort);
  waiter.resolve(release);
  return true;
}

function createRelease(): ReleaseMediaSlot {
  let released = false;
  return () => {
    if (released) return;
    released = true;
    while (mediaQueue.length > 0) {
      const waiter = mediaQueue.shift();
      if (waiter && settleWaiter(waiter, createRelease())) return;
    }
    activeMediaRequests -= 1;
  };
}

export async function acquireMediaSlot({
  waitMs = MEDIA_SLOT_WAIT_MS,
  signal,
}: { waitMs?: number; signal?: AbortSignal } = {}): Promise<ReleaseMediaSlot | null> {
  if (signal?.aborted) return null;
  if (activeMediaRequests < MAX_CONCURRENT_MEDIA_REQUESTS) {
    activeMediaRequests += 1;
    return createRelease();
  }
  if (mediaQueue.length >= MAX_QUEUED_MEDIA_REQUESTS) return null;

  return new Promise((resolve) => {
    const waiter: MediaWaiter = {
      resolve,
      signal,
      settled: false,
    };
    waiter.onAbort = () => {
      removeWaiter(waiter);
      settleWaiter(waiter, null);
    };
    waiter.timer = setTimeout(() => {
      removeWaiter(waiter);
      settleWaiter(waiter, null);
    }, waitMs);
    mediaQueue.push(waiter);
    signal?.addEventListener("abort", waiter.onAbort, { once: true });
  });
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
