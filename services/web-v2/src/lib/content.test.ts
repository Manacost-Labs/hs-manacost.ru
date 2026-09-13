import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { decodeText, localMediaUrl, readingMinutes, sanitizeArticleHtml } from "./content.ts";

describe("content boundaries", () => {
  it("decodes safe editorial text", () => {
    assert.equal(decodeText("<b>Гайд</b> &laquo;Монах&raquo;&nbsp;2026"), "Гайд «Монах» 2026");
  });

  it("removes scripts, event handlers, ads and unsafe urls", () => {
    const html = sanitizeArticleHtml('<p onclick="bad()">Текст</p><script>bad()</script><div class="ya-ad">ad</div><a href="javascript:bad()">x</a>');
    assert.match(html, /<p>Текст<\/p>/);
    assert.doesNotMatch(html, /script|onclick|javascript|ya-ad|>ad</);
  });

  it("returns a stable minimum reading time", () => {
    assert.equal(readingMinutes("<p>Короткий текст</p>"), 1);
  });

  it("proxies only the public WordPress uploads path", () => {
    assert.match(localMediaUrl("https://hs-manacost.ru/wp-content/uploads/2026/09/cover.jpg"), /^\/api\/media\?url=/);
    assert.equal(localMediaUrl("https://hs-manacost.ru/wp-json/wp/v2/users"), "");
    assert.equal(localMediaUrl("https://example.com/wp-content/uploads/cover.jpg"), "");
  });
});
