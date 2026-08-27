# Editorial SEO checklist

## Before publication

- Use one clear human title and a distinct SEO title only when it materially improves search clarity.
- Write a factual description that matches visible content; avoid keyword stuffing and unsupported claims.
- Set one primary category and intentional tags. Do not create near-duplicate taxonomy terms.
- Confirm author, publication/update dates, slug, excerpt, featured image, image dimensions, MIME type, alt text, and caption/credit when required.
- Add useful internal links to stable canonical `.ru` URLs. Check status and avoid circular or forced links.
- Ensure external links use the intended target and safe `rel` attributes where applicable.
- Validate Article and Breadcrumb schema against visible content. Do not add review/rating/FAQ properties without matching page content.
- Confirm the article appears in the intended sitemap only after publication.

## After publication

- Compare stored `post_content` and rendered content; check shortcodes, lazy images, S3 variants, links, schema, OpenGraph, canonical, and robots.
- Verify the same path on `.com` remains usable while canonicalizing to `.ru` and remaining noindex.
- Verify preview, drafts, attachment pages, search pages, pagination, feeds, and error responses do not accidentally become competing indexable URLs.
- Use Search Console or crawler evidence only as delayed confirmation; do not claim indexing from HTML alone.
