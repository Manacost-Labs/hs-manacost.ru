import sanitizeHtml from "sanitize-html";

const textEntities: Record<string, string> = {
  amp: "&",
  apos: "'",
  gt: ">",
  hellip: "…",
  laquo: "«",
  lt: "<",
  nbsp: " ",
  quot: '"',
  raquo: "»",
};

export function decodeText(value: string): string {
  return value
    .replace(/<[^>]*>/g, " ")
    .replace(/&#(\d+);/g, (_, code: string) => String.fromCodePoint(Number(code)))
    .replace(/&#x([0-9a-f]+);/gi, (_, code: string) => String.fromCodePoint(Number.parseInt(code, 16)))
    .replace(/&([a-z]+);/gi, (entity, name: string) => textEntities[name.toLowerCase()] ?? entity)
    .replace(/\s+/g, " ")
    .trim();
}

export function localMediaUrl(value: string): string {
  try {
    const source = new URL(value);
    if (source.protocol !== "https:" || source.hostname !== "hs-manacost.ru" || !source.pathname.startsWith("/wp-content/uploads/")) {
      return "";
    }
    return `/api/media?url=${encodeURIComponent(`https://hs-manacost.ru${source.pathname}`)}`;
  } catch {
    return "";
  }
}

export function sanitizeArticleHtml(value: string): string {
  return sanitizeHtml(value, {
    allowedTags: [
      "p", "br", "strong", "em", "b", "i", "u", "s", "h2", "h3", "h4", "ul", "ol", "li",
      "blockquote", "figure", "figcaption", "img", "a", "table", "thead", "tbody", "tr", "th", "td",
      "pre", "code", "details", "summary", "div", "span", "iframe"
    ],
    allowedAttributes: {
      a: ["href", "title", "target", "rel"],
      img: ["src", "srcset", "sizes", "alt", "title", "width", "height", "loading", "decoding"],
      iframe: ["src", "title", "width", "height", "allow", "allowfullscreen", "loading"],
      "*": ["class"]
    },
    allowedSchemes: ["http", "https", "mailto"],
    allowedIframeHostnames: ["www.youtube.com", "youtube.com", "player.vimeo.com", "rutube.ru"],
    transformTags: {
      a: (_tagName, attribs) => ({
        tagName: "a",
        attribs: { ...attribs, rel: "noopener noreferrer" }
      }),
      img: (_tagName, attribs) => ({
        tagName: "img",
        attribs: { ...attribs, src: localMediaUrl(attribs.src ?? ""), srcset: "", loading: "lazy", decoding: "async" }
      })
    },
    exclusiveFilter(frame) {
      const classes = frame.attribs.class ?? "";
      return /(?:vip|paywall|td-a-rec|adsbygoogle|ya-ad)/i.test(classes);
    }
  });
}

export function readingMinutes(html: string): number {
  const words = decodeText(html).split(/\s+/).filter(Boolean).length;
  return Math.max(1, Math.ceil(words / 180));
}
