# Cloud Templates

Official documentation: [Cloud Library plugin](https://forum.tagdiv.com/tagdiv-cloud-library-plugin/), [using Cloud Library templates](https://forum.tagdiv.com/how-use-tagdiv-cloud-library-templates/), [template types](https://forum.tagdiv.com/cloud-library-templates/), [category templates](https://forum.tagdiv.com/cloud-library-category-templates/), and [single-post templates](https://forum.tagdiv.com/design-post-pages-using-cloud-library-templates/).

## Data model precautions

- Cloud Templates are WordPress content plus tagDiv metadata, not ordinary PHP template files.
- Record the template ID, type, global/individual assignment, and current revision before changing it.
- Duplicate a production template before structural experiments.
- Do not bulk search/replace serialized content or IDs.
- Keep header, footer, single, category, author, search, tag, archive, attachment, and 404 assignments explicit.

## Safe workflow

1. Reproduce the intended change on staging.
2. Identify whether the template is global or assigned to individual posts/categories.
3. Export or otherwise preserve the current template revision before major work.
4. Change one structural concern at a time in Composer.
5. Save, close Composer, reload the public page, and verify another page using the same global template.

## Required checks

- Single article: title, author, date, views, hero image, body, inline decks, ads, related posts, comments.
- Category/search/archive: pagination, empty state, card images, filters, and mobile stacking.
- Header/footer: menus, sticky behavior, logo, social links, ad slots, and responsive variants.
- SEO: one title, canonical to `.ru`, expected robots, schema, Open Graph, and no duplicate output.
