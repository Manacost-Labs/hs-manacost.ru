import TurndownService from 'turndown';
import { gfm } from 'turndown-plugin-gfm';
import { z } from 'zod';

export function validateOrigin(origin) {
  if (!['https://hs-manacost.ru', 'https://test.hs-manacost.ru'].includes(origin)) throw new Error('invalid_source_origin');
  return origin;
}
export const metadataSchema = z.object({ id: z.number().int().positive(), modified_gmt: z.string().regex(/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d$/),
  content: z.object({ protected: z.boolean() }).passthrough() });
const documentSchema = metadataSchema.extend({ link: z.string().max(2048), title: z.object({ rendered: z.string().max(10000) }),
  content: z.object({ protected: z.boolean(), rendered: z.string().max(2_000_000) }), categories: z.array(z.number().int().positive()).max(100) });

function safeUrl(value, origin) {
  try { const url = new URL(value, origin); return ['https:', 'http:'].includes(url.protocol) && !url.username && !url.password ? url.href : ''; }
  catch { return ''; }
}
export function articleFromWordPress(value, origin) {
  validateOrigin(origin); const post = documentSchema.parse(value);
  if (post.content.protected) return null;
  const url = new URL(post.link);
  if (url.origin !== origin || url.username || url.password) throw new Error('invalid_article_url');
  const converter = new TurndownService({ headingStyle: 'atx', codeBlockStyle: 'fenced' });
  converter.use(gfm);
  converter.remove(['script', 'style', 'form', 'button', 'input', 'noscript', 'svg', 'nav', 'footer']);
  converter.addRule('public-links', { filter: 'a', replacement: (text, node) => {
    const href = safeUrl(node.getAttribute('href'), origin); return href ? `[${text}](${href})` : text;
  } });
  converter.addRule('public-images', { filter: 'img', replacement: (_, node) => {
    const src = safeUrl(node.getAttribute('data-src') || node.getAttribute('src'), origin);
    return src ? `![${(node.getAttribute('alt') || '').replace(/[\[\]\n]/g, '')}](${src})` : '';
  } });
  converter.addRule('embeds', { filter: 'iframe', replacement: (_, node) => {
    const src = safeUrl(node.getAttribute('src'), origin); return src ? `\n[Embedded content](${src})\n` : '';
  } });
  return { id: String(post.id), title: converter.turndown(post.title.rendered), url: url.href,
    modified: post.modified_gmt, categories: post.categories, text: converter.turndown(post.content.rendered) };
}
