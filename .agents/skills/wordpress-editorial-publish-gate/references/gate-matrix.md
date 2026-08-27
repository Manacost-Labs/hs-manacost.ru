# Publish gate matrix

Use stable check IDs so evidence remains comparable between releases.

| Check ID | Blocking | Required evidence |
|---|---:|---|
| `editor_save_revision` | yes | Draft save, autosave, revision restore and no-op content hash |
| `rendered_content` | yes | Preview status plus article body, links, captions, embeds and shortcodes |
| `media_s3` | yes | Source SHA256/MIME/dimensions, responsive derivative and anonymous object response |
| `seo_hosts` | yes | Title, canonical, robots and schema on primary/mirror/staging as applicable |
| `mobile_accessibility` | yes | 320 px and desktop view, keyboard path, focus, headings, labels and alt findings |
| `ads_analytics` | yes | Current creative, one intended tracker, staging suppression and no active production event |
| `cache_delivery` | yes | Targeted purge evidence plus cold/warm response on affected routes |
| `rollback` | yes | Recoverable WordPress revision and exact release commit |

Add a specialist check when a changed feature is not represented above. Do not remove a baseline check to avoid a failure; mark it `not_applicable` with a concrete reason.
