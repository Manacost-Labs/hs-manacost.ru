export class ArticleLibrary {
  constructor(store, source) { this.store = store; this.source = source; }
  async current(articles) {
    const states = new Map((await this.source.visible(articles.map(row => Number(row.id)))).map(row => [String(row.id), row]));
    return articles.filter(row => { const state = states.get(row.id); return state && !state.content.protected && state.modified_gmt === row.modified; });
  }
  summary(article) { return { id: article.id, title: article.title, url: article.url }; }
  categories(page) { return this.source.categories(page); }
  async search(query) { return { results: (await this.current(this.store.search(query))).map(article => this.summary(article)) }; }
  async fetch(id) {
    const article = this.store.get(id); const allowed = article ? await this.current([article]) : [];
    if (!allowed.length) throw new Error('article_not_found_or_changed');
    return { id: article.id, title: article.title, text: article.text, url: article.url,
      metadata: { modified_gmt: article.modified, categories: article.categories } };
  }
  async list({ cursor = '0', limit = 20 } = {}) {
    const rows = this.store.list(Number(cursor), limit);
    return { results: (await this.current(rows)).map(article => this.summary(article)),
      next_cursor: rows.length === limit ? rows.at(-1).id : null };
  }
}
