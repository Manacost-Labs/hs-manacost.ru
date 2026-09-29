import { articleFromWordPress } from './content.js';

export async function syncArticles(store, source, { full = false } = {}) {
  const catalog = (await source.catalog()).filter(row => !row.content.protected);
  const versions = store.versions(); const recent = Date.parse(store.lastSync()) - 120000;
  const changes = catalog.filter(row => full || versions.get(row.id) !== row.modified_gmt || Date.parse(`${row.modified_gmt}Z`) >= recent);
  store.beginSync(); let updated = 0; const denied = new Set();
  for (let start = 0; start < changes.length; start += 25) {
    const ids = changes.slice(start, start + 25).map(row => row.id);
    const rows = await source.documents(ids);
    for (const id of ids) if (!rows.some(row => row.id === id && !row.content.protected)) denied.add(id);
    const documents = rows.map(row => articleFromWordPress(row, source.origin)).filter(Boolean);
    store.stage(documents); updated += documents.length;
    if (source.pauseMs) await new Promise(resolve => setTimeout(resolve, source.pauseMs));
  }
  store.replace(catalog.map(row => row.id).filter(id => !denied.has(id)));
  return { articles: store.count(), updated };
}
