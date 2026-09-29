import { z } from 'zod';
import { validateOrigin, metadataSchema } from './content.js';

export class WordPressSource {
  constructor(origin, { transport = fetch, authorization, pauseMs = 200 } = {}) {
    this.origin = validateOrigin(origin); this.transport = transport; this.pauseMs = pauseMs;
    if (authorization && origin !== 'https://test.hs-manacost.ru') throw new Error('source_auth_staging_only');
    this.authorization = authorization;
  }
  async request(params, path = 'posts', schema = metadataSchema.passthrough()) {
    const url = new URL(`/wp-json/wp/v2/${path}`, this.origin);
    for (const [key, value] of Object.entries({ context: 'view', ...(path === 'posts' ? { status: 'publish' } : {}), ...params })) url.searchParams.set(key, String(value));
    url.searchParams.set('_mcp_read', String(Date.now()));
    const response = await this.transport(url, { redirect: 'error', signal: AbortSignal.timeout(8000),
      headers: { Accept: 'application/json', 'Cache-Control': 'no-cache', ...(this.authorization ? { Authorization: this.authorization } : {}) } });
    if (!response.ok) { await response.body?.cancel(); throw new Error(`source_http_${response.status}`); }
    if (!response.headers.get('content-type')?.includes('application/json')) { await response.body?.cancel(); throw new Error('source_not_json'); }
    let size = 0; const chunks = [];
    for await (const chunk of response.body) { size += chunk.length; if (size > 8_000_000) throw new Error('source_response_too_large'); chunks.push(chunk); }
    const data = z.array(schema).max(100).parse(JSON.parse(Buffer.concat(chunks).toString('utf8')));
    return { data, total: response.headers.has('x-wp-total') ? Number(response.headers.get('x-wp-total')) : NaN };
  }
  async catalog() {
    const rows = []; let total;
    for (let page = 1; page <= 1000; page++) {
      const result = await this.request({ page, per_page: 100, orderby: 'id', order: 'asc', _fields: 'id,modified_gmt,content.protected' });
      if (!Number.isSafeInteger(result.total) || result.total < 0 || result.total > 100000) throw new Error('source_invalid_total');
      total ??= result.total;
      if (total !== result.total) throw new Error('source_changed_during_scan');
      rows.push(...result.data);
      if (rows.length >= total) break;
      if (!result.data.length) throw new Error('source_incomplete_scan');
      await new Promise(resolve => setTimeout(resolve, this.pauseMs));
    }
    if (rows.length !== total || new Set(rows.map(row => row.id)).size !== total) throw new Error('source_incomplete_scan');
    return rows;
  }
  async visible(ids) {
    if (!ids.length) return [];
    const { data } = await this.request({ include: ids.join(','), per_page: 100, _fields: 'id,modified_gmt,content.protected' });
    return data.filter(row => ids.includes(row.id) && !row.content.protected);
  }
  async categories(page) {
    const { data, total } = await this.request({ page, per_page: 50, hide_empty: true, _fields: 'id,name,slug' }, 'categories',
      z.object({ id: z.number().int().positive(), name: z.string().max(1000), slug: z.string().max(1000) }));
    return { results: data, next_page: Number.isFinite(total) && page * 50 < total ? page + 1 : null };
  }
  async documents(ids) {
    const { data } = await this.request({ include: ids.join(','), per_page: 25, _fields: 'id,link,modified_gmt,title.rendered,content.rendered,content.protected,categories' });
    return data.filter(row => ids.includes(row.id));
  }
}
