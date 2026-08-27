# SEO, redirects and security

## All in One SEO

- Treat All in One SEO as the owner of editorial SEO fields and generated sitemap/schema behavior, but preserve the project host policy: `.ru` is canonical; `.com` and staging are noindex.
- Test homepage, article, archive and sitemap output after SEO setting/code changes.
- Purge affected HTML and sitemap URLs after verified changes; do not purge unrelated media or Redis by default.
- Never make the `.com` mirror indexable to “improve SEO”. It is an availability mirror, not a competing property.

Official documentation: [AIOSEO documentation](https://aioseo.com/documentation/) and [XML sitemaps](https://aioseo.com/doc-categories/xml-sitemap/).

## Redirection

- Prefer one-hop redirects from the old canonical URL to the final canonical URL.
- Check for rules at Redirection, WordPress, nginx, Cloudflare and proxy layers before adding another rule.
- Prevent loops, chains and broad regular expressions. Test query strings and case/slash behavior relevant to the request.
- Export or record the exact prior rule before changing runtime settings.

Official documentation: [Redirection support](https://redirection.me/support/) and [creating redirects](https://redirection.me/support/create-redirects/).

## Wordfence

- Keep the WAF enabled during normal diagnostics. Reproduce a suspected false positive and inspect the corresponding event before a narrow allowlist.
- Never allowlist a whole endpoint or role when a parameter/action-specific rule is sufficient.
- Firewall optimization touches bootstrap configuration and is a separately scoped infrastructure/security change with backup and rollback.
- Run scans deliberately; a scan is not a substitute for reviewing a code/config change, and resource impact must be considered.

Official documentation: [Wordfence firewall](https://www.wordfence.com/help/firewall/), [firewall optimization](https://www.wordfence.com/help/firewall/optimizing-the-firewall/) and [scanning](https://www.wordfence.com/help/scan/).

## Security boundary

Never paste tokens, salts, license keys, cookies or raw configuration into Git, commands displayed to users, CI logs or issue text. Inspect names/status and redact values. A cache bypass must not become an authentication or authorization bypass.
