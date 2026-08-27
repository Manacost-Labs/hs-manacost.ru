# Child theme strategy

Official documentation: [Child Theme Support](https://forum.tagdiv.com/the-child-theme-support-tutorial/) and [theme package contents](https://forum.tagdiv.com/whats-included/).

tagDiv recommends Cloud Templates for many layout changes. For current Newspaper versions, much legacy functionality lives under `td-standard-pack/Newspaper`, and only documented files can be overridden through a child theme.

## Rules

- Do not create or activate a child theme as part of an unrelated fix.
- Never copy the full parent `functions.php`; add only the required hook or function.
- Preserve the parent `Template` header and enqueue parent/child assets with WordPress APIs.
- Copy only the exact documented template/module/block file and preserve its relative structure.
- For single templates, include the required paired single and loop files described by tagDiv.
- Do not assume every file under the theme or plugin can be overridden.
- tagDiv explicitly discourages using the Theme API from a child theme; prefer a dedicated site plugin for API registrations.
- After each vendor update, compare every child override with the new upstream file before promotion.

## Adoption plan for this project

1. Inventory direct customizations currently inside `Newspaper_new` and the tagDiv plugins.
2. Classify each as Cloud Template, hook/MU-plugin, supported child override, or unavoidable vendor patch.
3. Move one behavior at a time with regression coverage.
4. Activate a child theme only on staging after homepage, article, category, search, Composer, mobile, and SEO checks pass.
5. Promote separately from any Newspaper/tagDiv version update.

Until that migration is completed, `Newspaper_new` remains the active tracked theme and direct changes require the strict audit and explicit justification.
