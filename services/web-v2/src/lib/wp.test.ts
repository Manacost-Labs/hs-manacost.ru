import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { getCategoryStories, wpFetch } from "./wp.ts";

function post(id: number) {
  return {
    id,
    date: "2026-09-13T00:00:00",
    modified: "2026-09-13T00:00:00",
    slug: `story-${id}`,
    link: `https://hs-manacost.ru/story-${id}`,
    title: { rendered: `Story ${id}` },
    excerpt: { rendered: "Excerpt" },
    categories: [],
    featured_media: 0,
  };
}

function jsonFetcher(items: ReturnType<typeof post>[], inspect?: (url: URL) => void) {
  return async (url: URL) => {
    inspect?.(url);
    return new Response(JSON.stringify(items), {
      status: 200,
      headers: { "Content-Type": "application/json" },
    });
  };
}

describe("WordPress adapter boundaries", () => {
  it("uses a 13-item offset window and trims it into a stable 12-item page", async () => {
    let requested: URL | undefined;
    const result = await getCategoryStories(7, 3, {
      fetcher: jsonFetcher(Array.from({ length: 13 }, (_, index) => post(index + 1)), (url) => { requested = url; }),
    });
    assert.equal(requested?.searchParams.get("offset"), "24");
    assert.equal(requested?.searchParams.get("per_page"), "13");
    assert.equal(result.stories.length, 12);
    assert.equal(result.hasNext, true);
  });

  it("does not advertise a next page for an exact final page", async () => {
    const result = await getCategoryStories(7, 2, {
      fetcher: jsonFetcher(Array.from({ length: 12 }, (_, index) => post(index + 1))),
    });
    assert.equal(result.stories.length, 12);
    assert.equal(result.hasNext, false);
  });

  it("aborts a WordPress request at its server-side deadline", async () => {
    const stalled = (_url: URL, init: RequestInit) => new Promise<Response>((_resolve, reject) => {
      init.signal?.addEventListener("abort", () => reject(init.signal?.reason), { once: true });
    });
    const keepEventLoopAlive = setTimeout(() => undefined, 50);
    try {
      await assert.rejects(
        wpFetch("posts", {}, { fetcher: stalled, timeoutMs: 5 }),
        /timed out/,
      );
    } finally {
      clearTimeout(keepEventLoopAlive);
    }
  });
});
