import type { NextRequest } from "next/server";
import {
  createMediaFetchInit,
  MAX_IMAGE_BYTES,
  normalizeRasterMimeType,
  parseMediaSource,
  readBoundedBody,
  tryAcquireMediaSlot,
} from "@/lib/media";

export const dynamic = "force-dynamic";

export async function GET(request: NextRequest) {
  const value = request.nextUrl.searchParams.get("url");
  if (!value) return new Response("Missing media URL", { status: 400 });

  const source = parseMediaSource(value);
  if (!source) {
    return new Response("Media source is not allowed", { status: 403 });
  }

  const release = tryAcquireMediaSlot();
  if (!release) return new Response("Media proxy busy", { status: 503, headers: { "Retry-After": "2" } });
  try {
    const upstream = await fetch(source, createMediaFetchInit());
    const type = normalizeRasterMimeType(upstream.headers.get("content-type"));
    const declaredHeader = upstream.headers.get("content-length");
    const declaredSize = declaredHeader === null ? 0 : Number(declaredHeader);
    if (!upstream.ok) return new Response("Media unavailable", { status: 502 });
    if (!type || !Number.isFinite(declaredSize) || declaredSize < 0) {
      return new Response("Media type is not allowed", { status: 415 });
    }
    if (declaredSize > MAX_IMAGE_BYTES) return new Response("Media too large", { status: 413 });

    const body = await readBoundedBody(upstream.body);
    if (!body) return new Response("Media too large", { status: 413 });
    return new Response(body, {
      headers: {
        "Content-Type": type,
        "Content-Length": String(body.byteLength),
        "Cache-Control": "public, max-age=3600, stale-while-revalidate=86400",
        "X-Content-Type-Options": "nosniff",
      },
    });
  } catch {
    return new Response("Media unavailable", { status: 502 });
  } finally {
    release();
  }
}
