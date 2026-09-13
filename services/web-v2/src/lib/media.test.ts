import assert from "node:assert/strict";
import { describe, it } from "node:test";
import {
  createMediaFetchInit,
  MAX_CONCURRENT_MEDIA_REQUESTS,
  normalizeRasterMimeType,
  parseMediaSource,
  readBoundedBody,
  tryAcquireMediaSlot,
} from "./media.ts";

describe("media proxy boundaries", () => {
  it("accepts only the canonical HTTPS uploads origin and default TLS port", () => {
    assert.equal(parseMediaSource("https://hs-manacost.ru/wp-content/uploads/2026/09/a.jpg")?.hostname, "hs-manacost.ru");
    assert.equal(parseMediaSource("https://hs-manacost.ru:443/wp-content/uploads/2026/09/a.jpg")?.port, "");
    assert.equal(parseMediaSource("https://hs-manacost.ru:444/wp-content/uploads/2026/09/a.jpg"), null);
    assert.equal(parseMediaSource("https://127.0.0.1/wp-content/uploads/a.jpg"), null);
  });

  it("rejects redirects and applies an upstream deadline", () => {
    const init = createMediaFetchInit(25);
    assert.equal(init.cache, "no-store");
    assert.equal(init.redirect, "error");
    assert.ok(init.signal);
  });

  it("allows raster MIME types but rejects active SVG content", () => {
    assert.equal(normalizeRasterMimeType("image/jpeg; charset=binary"), "image/jpeg");
    assert.equal(normalizeRasterMimeType("image/svg+xml"), null);
    assert.equal(normalizeRasterMimeType("text/html"), null);
  });

  it("stops reading a response once its byte limit is crossed", async () => {
    const stream = new ReadableStream<Uint8Array>({
      start(controller) {
        controller.enqueue(new Uint8Array([1, 2, 3]));
        controller.enqueue(new Uint8Array([4, 5, 6]));
        controller.close();
      },
    });
    assert.equal(await readBoundedBody(stream, 5), null);
  });

  it("rejects excess concurrent upstream media work", () => {
    const releases = Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => tryAcquireMediaSlot());
    assert.ok(releases.every(Boolean));
    assert.equal(tryAcquireMediaSlot(), null);
    releases.forEach((release) => release?.());
    const release = tryAcquireMediaSlot();
    assert.ok(release);
    release?.();
  });
});
