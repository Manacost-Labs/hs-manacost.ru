# RSYA article placement verification

## Scope and placement contract

`wordpress/mu-plugins/manacost-rsya-inline.php` owns the loader and placements:

- Intro: `R-A-16113237-6`, after the third text paragraph (first paragraph in a short article).
- End of content / after Telegram: `R-A-16113237-5`, independent of the intro.
- Floor Ad: `R-A-16113237-7`, **desktop** configuration supplied by the owner. The SDK owns platform selection and close controls. No mobile Floor Ad ID was supplied.
- Published posts dated at or after `2026-08-31 09:00:39` UTC: the original ten-post cohort plus subsequent normally dated publications. Older, draft/private posts, previews, admin, feeds, AJAX and PDF output remain excluded.
- One loader, one call per owned slot per document; no polling, auction retries, forced refresh or client analytics added. Logged-in users use the same eligibility rule as guests.
- A manually inserted single container does not suppress the other placement. Existing manual markup is preserved, not repaired or replaced.
- A container inside a supported collapsed spoiler/details element is moved outside it after DOM parsing, before requesting the ad; the article itself is unchanged.

## Reproduced problems

2026-09-08 read-only production audit:

- Floor Ad was in PR #34, absent from production.
- The priest article had one loader and both distinct inline containers on the primary, mirror, origin and both regional proxies.
- Three of the latest ten articles had **no** RSYA markup at their normal URLs. Fresh query responses for those same posts had both inline placements. This establishes stale cached HTML, not an ad-auction failure. Posts: 316311, 316263, 316074.
- Unit reproductions: one existing placement suppressed the second; successful rendering did not restore a container previously hidden by the error handler.
- Browser reproduction with the real WordPress spoiler shortcode: an intro container inserted inside a closed spoiler was invisible until expanded.

## Verification and interpretation

`tests/test_rsya_inline_banner.py` covers cutoff/future dates, guest/authenticated eligibility, individual placement repair, repeated filtering, loader/error/no-fill/success and recovery callbacks.

`tests/visual/rsya.spec.ts` uses a disposable WordPress article and a real browser, replacing **only the paid SDK response** at the HTTP boundary. It checks both slots together, slow loader, duplicate tag execution, reloads, independent no-fill, an authenticated WordPress login and 320/390/768/1024/1440px geometry. Both browser projects run in isolated contexts. Other third-party traffic is blocked. These checks do not prove paid fill or the real SDK's mobile suppression of the desktop Floor Ad.

The integration fixture previously returned Apache 404 for pretty article/category URLs. Its startup now generates WordPress rewrite rules explicitly (CLI cannot detect Apache modules). Public screenshot tests assert HTTP 200. Four formerly 404 reference images were replaced only after inspecting the actual article/category pages in the pinned Playwright container. This affects local/CI fixtures only, not production web-server configuration.

Slot DOM attributes support passive diagnosis without sending telemetry:

- `data-manacost-rsya-state`: queued / requested / rendered / no-fill / error / loader-error; Floor Ad additionally records closed.
- `data-manacost-rsya-code`: bounded SDK error/warning code when provided.
- `data-manacost-rsya-rendered`: a successful onRender callback occurred.

A blocked loader can leave queued diagnostic state while hiding inline containers. Inspect the loader request and `window.manacostRsyaLoaderFailed` too. Empty-space collapse must not be interpreted as confirmed no-fill without the callback evidence. Actual paid delivery, region/account restrictions and blocking extensions require observation of a genuine visitor session or the RSYA dashboard; never fabricate impressions to test them.

Official callback/placement reference: https://yandex.ru/support/partner/ru/web/units/tag-features . Container dimensions remain the owner's existing 970x90 maximum desktop and 320x100 maximum mobile treatment; not every creative fits these dimensions.

## Release / rollback

Run `make check`, `make code-quality`, `make visual`, the staged security check and independent review. Merge PR #34 only after Quality succeeds. Stage its exact merged SHA; use `Promote production` only after that SHA's successful staging deployment.

After promotion, compare normal (not only cache-busting) URLs for all latest ten posts across primary, mirror, origin, Moscow and Novosibirsk, then repeat the priest URL on desktop/mobile and with reloads. Require one loader and all three placement tags; preserve mirror noindex and primary canonical. Browser verification must block measurement requests and paid SDK impressions. If stale HTML remains, use targeted page-cache invalidation for the affected canonical URLs and recheck regional responses.

Rollback target before this release: `15f47857b401c02f69210bb6d74f344c3b70204e`, through `Promote production` with that previously staging-tested SHA. The normal deploy retains the prior code in `/var/backups/hs-manacost-deploy/production/`. No production article content, account, database schema, theme/vendor package or nginx configuration changes are required by this release.
