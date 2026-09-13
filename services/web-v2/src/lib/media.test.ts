import assert from "node:assert/strict";
import { describe, it } from "node:test";
import {
  acquireMediaSlot,
  createMediaFetchInit,
  MAX_CONCURRENT_MEDIA_REQUESTS,
  MAX_QUEUED_MEDIA_REQUESTS,
  normalizeRasterMimeType,
  parseMediaSource,
  readBoundedBody,
} from "./media.ts";

describe("media proxy boundaries", () => {
  it("accepts only the canonical HTTPS uploads origin and default TLS port", () => {
    assert.equal(parseMediaSource("https://hs-manacost.ru/wp-content/uploads/2026/09/a.jpg")?.hostname, "hs-manacost.ru");
    assert.equal(parseMediaSource("https://hs-manacost.ru:443/wp-content/uploads/2026/09/a.jpg")?.port, "");
    assert.equal(parseMediaSource("https://hs-manacost.ru:444/wp-content/uploads/2026/09/a.jpg"), null);
    assert.equal(parseMediaSource("https://127.0.0.1/wp-content/uploads/a.jpg"), null);
  });

  it("rejects redirects and applies an upstream deadline", () => {
    const controller = new AbortController();
    const init = createMediaFetchInit({ timeoutMs: 25, signal: controller.signal });
    assert.equal(init.cache, "no-store");
    assert.equal(init.redirect, "error");
    assert.ok(init.signal);
    controller.abort();
    assert.equal(init.signal?.aborted, true);
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

  it("queues excess work without exceeding the active upstream limit", async () => {
    const releases = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    assert.ok(releases.every(Boolean));
    const queued = acquireMediaSlot({ waitMs: 100 });
    const early = await Promise.race([
      queued.then(() => "released"),
      new Promise<string>((resolve) => setTimeout(() => resolve("waiting"), 5)),
    ]);
    assert.equal(early, "waiting");
    releases.shift()?.();
    const queuedRelease = await queued;
    assert.ok(queuedRelease);
    queuedRelease?.();
    releases.forEach((release) => release?.());
  });

  it("times out a queued request when all upstream slots remain occupied", async () => {
    const releases = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    assert.equal(await acquireMediaSlot({ waitMs: 5 }), null);
    releases.forEach((release) => release?.());
  });

  it("hands released slots to queued requests in FIFO order", async () => {
    const releases = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    const order: string[] = [];
    const first = acquireMediaSlot({ waitMs: 100 }).then((release) => {
      order.push("first");
      return release;
    });
    const second = acquireMediaSlot({ waitMs: 100 }).then((release) => {
      order.push("second");
      return release;
    });
    releases.shift()?.();
    const firstRelease = await first;
    assert.deepEqual(order, ["first"]);
    releases.shift()?.();
    const secondRelease = await second;
    assert.deepEqual(order, ["first", "second"]);
    firstRelease?.();
    secondRelease?.();
    releases.forEach((release) => release?.());
  });

  it("rejects only requests beyond the bounded waiting queue", async () => {
    const releases = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    const controller = new AbortController();
    const queued = Array.from({ length: MAX_QUEUED_MEDIA_REQUESTS }, () =>
      acquireMediaSlot({ waitMs: 1_000, signal: controller.signal }),
    );
    assert.equal(await acquireMediaSlot({ waitMs: 1_000 }), null);
    controller.abort();
    assert.ok((await Promise.all(queued)).every((release) => release === null));
    releases.forEach((release) => release?.());
  });

  it("removes an aborted waiter and hands the next waiter the slot", async () => {
    const releases = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    const controller = new AbortController();
    const aborted = acquireMediaSlot({ waitMs: 100, signal: controller.signal });
    const next = acquireMediaSlot({ waitMs: 100 });
    controller.abort();
    assert.equal(await aborted, null);
    releases.shift()?.();
    const nextRelease = await next;
    assert.ok(nextRelease);
    nextRelease?.();
    releases.forEach((release) => release?.());
  });

  it("restores full capacity after handoff, abort, and idempotent release", async () => {
    const releases = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    const controller = new AbortController();
    const handedOff = acquireMediaSlot({ waitMs: 100, signal: controller.signal });
    releases.shift()?.();
    const handedOffRelease = await handedOff;
    controller.abort();
    assert.equal(createMediaFetchInit({ signal: controller.signal }).signal?.aborted, true);
    handedOffRelease?.();
    handedOffRelease?.();
    releases.forEach((release) => release?.());

    const restored = await Promise.all(Array.from({ length: MAX_CONCURRENT_MEDIA_REQUESTS }, () => acquireMediaSlot()));
    assert.ok(restored.every(Boolean));
    restored.forEach((release) => release?.());
  });
});
