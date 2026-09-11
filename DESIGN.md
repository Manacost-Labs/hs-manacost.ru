---
version: alpha
name: Manacost Reader — Ink and Ember
description: Focused design contract for the private cabinet, public reader profile, article favorite control, and saved-article collection on hs-manacost.ru.
colors:
  primary: "#9B630E"
  primary-hover: "#7D4F0B"
  on-primary: "#FFFFFF"
  primary-soft: "#F5EBDD"
  background: "#F3F5F6"
  surface: "#FFFFFF"
  surface-subtle: "#F7F9FA"
  ink: "#18303B"
  ink-shell: "#152D3A"
  on-ink-shell: "#F5F8F9"
  muted: "#58636D"
  outline: "#667983"
  outline-subtle: "#D8E0E4"
  focus: "#0B6E99"
  success: "#0F6B48"
  success-soft: "#E7F5EF"
  error: "#B42318"
  error-soft: "#FDECEA"
  admin: "#214D7A"
  admin-soft: "#E8F0F7"
  twitch: "#9146FF"
  youtube: "#C8152A"
typography:
  display:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 36px
    fontWeight: 700
    lineHeight: 42px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 30px
    fontWeight: 700
    lineHeight: 36px
    letterSpacing: -0.015em
  headline-md:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 24px
    fontWeight: 700
    lineHeight: 30px
    letterSpacing: -0.01em
  title:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 18px
    fontWeight: 650
    lineHeight: 24px
  body:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 25px
  body-strong:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 16px
    fontWeight: 600
    lineHeight: 24px
  label:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 14px
    fontWeight: 600
    lineHeight: 20px
  metadata:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 13px
    fontWeight: 450
    lineHeight: 18px
  caption:
    fontFamily: "Golos Text, system-ui, sans-serif"
    fontSize: 12px
    fontWeight: 500
    lineHeight: 16px
rounded:
  xs: 4px
  sm: 6px
  md: 8px
  lg: 12px
  full: 9999px
spacing:
  base: 4px
  xs: 4px
  sm: 8px
  md: 12px
  lg: 16px
  xl: 24px
  2xl: 32px
  3xl: 48px
  4xl: 64px
  gutter-mobile: 16px
  gutter-tablet: 24px
  gutter-desktop: 32px
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "12px"
    height: "44px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.on-primary}"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "12px"
    height: "44px"
  button-text:
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "8px"
    height: "44px"
  favorite-default:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "12px"
    height: "44px"
  favorite-hover:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.ink}"
  favorite-saved:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "12px"
    height: "44px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: "12px"
    height: "44px"
  panel:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "24px"
  saved-item:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sm}"
    padding: "16px"
  saved-item-hover:
    backgroundColor: "{colors.surface-subtle}"
    textColor: "{colors.ink}"
  badge-subscriber:
    backgroundColor: "{colors.primary-soft}"
    textColor: "{colors.primary-hover}"
    typography: "{typography.caption}"
    rounded: "{rounded.full}"
    padding: "4px"
    height: "24px"
  badge-admin:
    backgroundColor: "{colors.admin-soft}"
    textColor: "{colors.admin}"
    typography: "{typography.caption}"
    rounded: "{rounded.full}"
    padding: "4px"
    height: "24px"
  social-twitch:
    textColor: "{colors.twitch}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "8px"
    height: "40px"
  social-youtube:
    textColor: "{colors.youtube}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "8px"
    height: "40px"
  ink-shell:
    backgroundColor: "{colors.ink-shell}"
    textColor: "{colors.on-ink-shell}"
    typography: "{typography.label}"
  helper-text:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.muted}"
    typography: "{typography.metadata}"
  divider:
    backgroundColor: "{colors.outline-subtle}"
    height: "1px"
  focus-indicator:
    backgroundColor: "{colors.focus}"
    size: "2px"
  status-success:
    backgroundColor: "{colors.success-soft}"
    textColor: "{colors.success}"
    typography: "{typography.metadata}"
    rounded: "{rounded.sm}"
    padding: "12px"
  status-error:
    backgroundColor: "{colors.error-soft}"
    textColor: "{colors.error}"
    typography: "{typography.metadata}"
    rounded: "{rounded.sm}"
    padding: "12px"
---

# Manacost Reader design contract

## Overview

### Product boundary

This document is the source of truth for exactly four connected surfaces on
`hs-manacost.ru` and `test.hs-manacost.ru`:

1. the authenticated reader cabinet at `/account/`;
2. the public reader profile at `/account/?reader={opaque-id}`;
3. the article action that adds or removes an article from favorites;
4. the saved-article collection shown below the profile in the cabinet.

The homepage, article typography, article cards, main navigation, footer,
advertising, editorial WordPress screens, comments, and the visual identity of
HearthPulse are outside this contract. Do not use this document as permission
for a site-wide redesign.

### Product character: “Ink and Ember”

The Reader UI should feel adult, editorial, calm, and unmistakably Manacost.
The site’s vivid Hearthstone covers are the expressive layer; account controls
are the quiet layer that frames them. White paper-like surfaces, graphite text,
the existing deep Manacost ink color, and one warm amber action color create the
identity. Ornament never competes with content.

The intended feeling is a trusted game publication with a useful membership
layer—not a fantasy-game launcher, children’s dashboard, generic SaaS panel, or
WordPress settings page.

### Experience principles

- **Identity first.** A reader should understand whose profile this is before
  seeing settings or saved content.
- **One hierarchy, few containers.** Use whitespace, type, and dividers before
  adding another card.
- **Visible actions.** Important actions carry text; icons support meaning but
  never replace it.
- **Private by default.** Account data and favorites never appear in cacheable
  HTML or a public profile by accident.
- **Fast by construction.** No UI framework, icon font, remote font request,
  decorative animation library, or client-rendered application shell.
- **Compatible, not themed by Newspaper.** The Reader sits inside the existing
  site container but owns its internal tokens and component styles.

### Information disclosure

| Data or control | Cabinet owner | Public profile | Anonymous HTML shell |
| --- | --- | --- | --- |
| Display name, avatar, bio, favorite class | Yes | Yes, after the existing publication boundary | No |
| Twitch and YouTube links | Yes | Yes, only validated and intentionally published | No |
| Subscriber and administrator status | Yes | Yes, from server-authoritative capabilities | No |
| Saved articles | Yes | Never | No |
| Edit, logout, remove favorite | Yes | Never | No |
| Email, provider subject, tokens, session IDs | Never render | Never render | Never render |

## Colors

The palette is intentionally narrow. Covers and class crests may remain vivid,
but surrounding UI must use the semantic tokens above rather than sampling
colors from artwork.

- **Ink (`ink`, `ink-shell`)** carries headlines, body text, and the existing
  site relationship. The deep shell is allowed only for compact navigation or
  identity accents—not as a full-page cabinet background.
- **Paper (`background`, `surface`, `surface-subtle`)** makes the cabinet and
  public profile feel lighter and calmer. The account background is a cool
  off-white; primary content is pure white.
- **Ember (`primary`)** is the only general interaction accent. Use it for the
  primary save action, selected favorite state, concise highlights, and focus
  complements. It is not a decorative border system.
- **Platform colors** appear only on the Twitch and YouTube glyph or its focus
  treatment. Do not fill large buttons with platform colors.
- **Status colors** are reserved for semantic feedback. Never use red simply
  to attract attention.

`primary` with `on-primary`, `ink` with `surface`, `muted` with `surface`, and
all status text/background pairs must meet WCAG AA. Decorative borders may use
`outline-subtle`; interactive boundaries use `outline` so they remain visible.
The keyboard focus ring is always `focus`, 2px wide with a 2px offset.

Never use gold gradients, glowing purple rails, neon outlines, glass blur, or a
different accent per Hearthstone class. The class crest is enough color.

## Typography

Use one Reader UI family: **Golos Text**, with a system sans-serif fallback.
It provides a contemporary but not futuristic Cyrillic voice and avoids the
condensed, over-bold dashboard appearance of the current cabinet.

Implementation may load at most two self-hosted Cyrillic WOFF2 files, with a
combined transfer budget of 100 KB. If that budget or browser support cannot be
met, use the system fallback without changing metrics. Do not request Google
Fonts and do not inherit unrelated Newspaper font stacks inside `.mc-reader-ui`.

- `display`: the cabinet page title, 36/42 on desktop and 30/36 below 560px.
- `headline-lg`: reader name on the cabinet overview.
- `headline-md`: section headings and the public-profile name on small screens.
- `title`: saved-article titles and concise subheadings.
- `body`: bio, helper copy, and empty states.
- `label`: buttons, field labels, and navigation actions.
- `metadata` and `caption`: dates, counters, status details, and badges.

Use sentence case in Russian UI. Uppercase is limited to a short optional
eyebrow of at most three words; never uppercase field labels or section titles.
Do not center paragraphs, saved-article titles, or forms. Allow names and titles
to wrap; do not shrink type to fit.

## Layout

### Shared page frame

The Reader must fit the active Newspaper content width instead of creating an
independent viewport-wide canvas.

- Outer width: `min(100% - 2 × gutter, 960px)`.
- Form measure: maximum 640px.
- Desktop gutter: 32px; tablet: 24px; mobile: 16px.
- Section spacing: 48px desktop, 32px mobile.
- Do not use negative margins, fixed page heights, or horizontal clipping.
- Verify the WordPress admin bar, logged-out cached page, and logged-in page.

### Cabinet composition

The cabinet uses a single vertical flow:

1. compact page header: “Личный кабинет” and the account menu;
2. profile identity section;
3. owner actions: “Изменить профиль” and “Открыть публичный профиль”;
4. one compact favorite-class row inside identity metadata;
5. “Сохранённые статьи” directly below the profile, never in a tab;
6. a quiet return link to site materials.

The profile overview is one surface, not a card inside a card. At 768px and
above, the avatar occupies a 96px leading column and content fills the rest. At
smaller widths it becomes a 72px avatar beside the name; metadata and actions
continue below in document order. The favorite-class crest is 36–40px and sits
in a simple inline row. It must not be isolated in a large bordered tile.

The edit form replaces the overview in the same content region; it is not a
modal. Use a one-column field flow. The avatar editor may sit beside the first
fields only above 900px, and must return to document order on smaller screens.
Primary and secondary form actions stay together below the fields. Destructive
photo removal is visually separate from “Сохранить изменения”.

### Public profile composition

The public profile uses the same identity component at a calmer density:

1. “К материалам” back link;
2. 96px avatar or deterministic initials fallback;
3. name, then verified role marks;
4. bio;
5. favorite-class row;
6. Twitch and YouTube links when present.

Do not show the cabinet masthead, account menu, edit controls, favorites, email,
or private loading state. A missing profile uses a plain inline error and the
same back link; it does not render an empty hero card.

### Saved articles

Saved articles form a vertical list below the profile. A row contains a stable
3:2 thumbnail, category or content type, title, saved date when available, and
the owner-only “Удалить из избранного” action. The title is the primary link.

- Thumbnail: 120 × 80px desktop, 96 × 64px mobile; reserve its dimensions.
- Use one divider between rows, not a border around every item.
- Load six items initially and use “Показать ещё” for subsequent pages.
- The remove action remains reachable by keyboard and is not hidden on hover.
- The empty state explains the feature and links to current materials.
- A failed page preserves already loaded items and offers an inline retry.

### Responsive acceptance widths

The four surfaces must be inspected at 320, 390, 560, 768, 1024, and 1440px,
at 200% zoom, and with long Cyrillic names and article titles. There must be no
horizontal page scroll. Controls may wrap; they may not overlap, float outside
the content measure, or collapse below a 44px target.

## Elevation & Depth

Hierarchy comes from tonal surfaces, whitespace, and 1px dividers. Default
profile, form, and saved-list surfaces have no shadow. The only permitted page
shadow is a subtle `0 8px 24px rgba(24, 48, 59, 0.08)` on a temporary menu or
popover that visually leaves the document plane.

Do not stack nested shadows or use a glow to imitate premium status. Subscriber
and administrator roles are communicated by a compact badge, readable label,
and server truth—not elevation.

Interaction transitions last 120–160ms and affect only color, opacity, and at
most a 1px translation. Under `prefers-reduced-motion: reduce`, remove optional
transitions. Loading must not pulse indefinitely; use a static reserved shape or
short opacity cycle that stops when content arrives.

## Shapes

The shape language is restrained and geometric.

- Main surfaces: 8px radius.
- Buttons and fields: 6px radius.
- Small media containers: 4–6px radius.
- Avatars and role-icon containers: circular.
- Badges: pill only because their content is short status metadata.
- Thumbnails retain their rectangular editorial proportion.

Avoid mixed radii in one row, oversized capsules, diagonal cuts, fantasy frames,
ornamental side bars, and gold rules extending through a component. Icons use a
consistent 20px viewport, 1.75–2px stroke, round joins, and no icon font.

## Components

### Cabinet header

Use the exact title **“Личный кабинет”**. The small “Профиль Манакоста” kicker
may remain only if it does not create a second competing headline. The account
menu aligns to the title baseline on wide screens and drops below it on mobile.
Its disclosure summary must expose expanded/collapsed state natively.

Do not reproduce the main site footer links, social strip, or a second dark
navigation bar inside the account surface.

### Identity block

Avatar, name, role marks, bio, social links, favorite class, and owner actions
form one component in that order. The avatar always reserves its final size.
If the image cannot load, show up to two initials derived from the display name;
never display a broken-image glyph.

Marks after the name follow these rules:

- Twitch and YouTube are links only when their server-normalized profile URL is
  present. Use the official silhouette in the platform color inside a neutral
  24px target. Include an accessible name and visible tooltip on hover/focus.
- A paid Boosty or Patreon entitlement displays a small amber crown plus the
  visible label “Платный подписчик” in roomy views. In a compact name line the
  crown may stand alone visually, but screen-reader text and tooltip remain.
- Administrator displays a shield or staff mark and “Администратор”. Never infer
  this role from WordPress login, CSS class, or browser data.
- Multiple marks stay on the same wrap context as the name and preserve 8px
  spacing. They never overlap the name or crest.

The favorite class is secondary metadata: crest, label “Любимый класс”, and
localized class name in a horizontal row. It uses no large border, hero copy,
or separate column. If not selected, omit the row from the overview and show
“Не выбран” only in the editor.

### Profile editor

The editor contains these fields in this order:

1. photo: JPEG, PNG, or WebP up to 4 MB, saved independently;
2. “Имя в Манакосте”, 2–40 Unicode code points;
3. “О себе”, optional, up to 280 code points;
4. “Любимый класс”, optional;
5. “Где меня найти”: validated Twitch and YouTube channel URLs;
6. “Сохранить изменения” and “Отмена”.

Labels stay above fields. Counters align to the trailing edge under their field.
Helper text appears before an error; the error is adjacent to the failing field
and is referenced through `aria-describedby`. Do not erase a valid draft after
upload, conflict, session expiry, or network failure.

Photo selection shows an immediate local preview and explicit progress. Success
must mean the server returned the new version; failure restores the last saved
avatar and keeps a retry action. The native file input remains operable even if
the visible trigger is styled.

The primary action is “Сохранить изменения”. “Отмена” is secondary text or
outline. “Повторить” and “Обновить версию” appear only for their relevant error
state. Do not show two equal primary buttons.

### Public-profile states

| State | Required result |
| --- | --- |
| Loading | Reserved avatar and text geometry; status announced politely |
| Ready | One identity block with only approved public fields |
| No avatar | Initials fallback with the same dimensions |
| Missing optional field | Omit its row; no placeholder copy |
| Not found or withdrawn | “Профиль недоступен” and “К материалам” |
| Temporary error | Keep page context, explain retry, provide one retry action |

The public page title is the person’s display name; “Читатель Манакоста” is
metadata, not a giant eyebrow. Social links open safely in a new tab and include
platform name in visible text on the public profile.

### Favorite article control

The favorite control is a compact article action, not a promotional banner. It
must sit with article actions or metadata and must not be appended as a large
card after the full article body.

| State | Label | Visual treatment | Behavior |
| --- | --- | --- | --- |
| Guest | “Сохранить статью” | Secondary button with bookmark outline | Starts HearthPulse login and preserves the exact article return path |
| Checking | “Проверяем…” | Same width; quiet progress indicator | Disabled; no layout shift |
| Ready, not saved | “Сохранить статью” | White surface, ink text, amber hover | `aria-pressed=false` |
| Saving | “Сохраняем…” | Preserve current visual state | Disable duplicate writes |
| Saved | “В избранном” | Amber fill, white text, filled bookmark | `aria-pressed=true` |
| Removing | “Удаляем…” | Preserve saved state | Disable duplicate writes |
| Error | Previous stable label | Roll back optimistic state | Inline retry message; do not lose article context |

The entire control has a 44px minimum target. Keep a visible label at every
supported width; an icon-only favorite is not allowed. The live status uses
`aria-live=polite`, but routine successful hydration is silent. A successful
toggle updates both the article control and the cabinet collection on the next
read without exposing favorite data to page cache.

### Saved-article row

The row is editorial rather than dashboard-like. Its thumbnail and title link
to the canonical article. The remove control uses bookmark-minus or trash only
with the accessible label “Удалить из избранного”. Removing a row should keep
focus at the next logical row or the section heading, announce the result, and
restore the row if the write fails.

The collection has these states:

- **Loading:** reserve up to three row geometries without shifting the profile.
- **Empty:** “Здесь появятся статьи, которые вы сохраните на сайте.” followed by
  “Перейти к материалам”.
- **Partial failure:** preserve loaded rows and show “Не удалось загрузить ещё”
  with “Повторить”.
- **Session expired:** hide private titles immediately and offer HearthPulse
  login with return to `/account/`.

### Buttons, links, and fields

Primary buttons use amber fill only for the dominant write. Secondary buttons
use white or transparent backgrounds, ink text, and a visible 1px outline.
Text buttons are allowed for cancellation and quiet navigation. Disabled state
must use more than opacity alone and retain readable text.

Links are underlined on hover and focus; links embedded in prose are underlined
by default. Every control supports `:focus-visible`. Hover cannot be the only way
to discover an action. Touch and pointer interactions share the same labels and
stable geometry.

### Feedback and copy

Use direct, human Russian. Avoid infrastructure terms such as OIDC, BFF, token,
issuer, cache, or endpoint in the interface.

| Purpose | Preferred copy |
| --- | --- |
| Page title | “Личный кабинет” |
| Edit action | “Изменить профиль” |
| Public link | “Открыть публичный профиль” |
| Saved heading | “Сохранённые статьи” |
| Empty favorites | “Здесь появятся статьи, которые вы сохраните на сайте.” |
| Login action | “Войти через HearthPulse” |
| Generic retry | “Не получилось. Попробуйте ещё раз.” |
| Session expiry | “Сессия завершилась. Войдите снова, чтобы продолжить.” |

Do not expose a permanent “Проверяем вход…” message, internal response codes, or
raw validation identifiers. A status must be actionable or disappear.

### Implementation ownership

All selectors must remain under `.mc-reader-ui` or the existing Reader component
roots. Never style global `h1`, `button`, `input`, `img`, `.td-container`, or
Newspaper utility classes to achieve these screens.

- `ui.css` owns colors, type, spacing, buttons, fields, focus, badges, and shared
  identity primitives.
- `reader.css` owns cabinet, editor, favorite collection, and responsive layout.
- `comments.css` may own the public-profile layout only until a dedicated shared
  profile file exists; do not duplicate identity tokens there.
- `article-favorite.css` owns only article placement and favorite states.
- PHP renders semantic, cache-safe shells. JavaScript hydrates private state and
  updates existing nodes; it does not replace the page with a framework app.
- Behavioral hooks remain `data-*` attributes. Presentation uses classes.

Introduce CSS custom properties with the `--mc-r-*` prefix and map them to the
tokens in this file. Do not add an override file after existing Reader styles;
change the owning primitive or add one documented variant.

### Cache, privacy, and performance contract

- Anonymous HTML contains no reader name, avatar URL, roles, social links,
  favorites, CSRF value, or login-derived class.
- Owner data is hydrated from same-origin Reader endpoints with `no-store`.
- Public-profile data comes only from the existing publishable snapshot; owner
  profile storage is not a public API.
- Favorites remain per-reader private data and article metadata remains
  server-authoritative.
- Session and entitlement truth remain in HearthPulse/Reader, never WordPress
  users or browser-supplied fields.
- WP Rocket and the local optimizer must see identical Reader CSS for guest and
  administrator views. Every required dynamic class must be safelisted or
  present in static source before release.

Performance budgets for these surfaces:

| Budget | Maximum |
| --- | --- |
| Combined compressed Reader CSS needed by one surface | 25 KB |
| Initial compressed Reader JavaScript needed by one surface | 60 KB |
| Reader font transfer | 100 KB, two WOFF2 files, one family |
| Inline SVG icon markup | 1 KB per unique icon; reuse the canonical path set |
| Cabinet layout shift caused by avatar or thumbnails | CLS 0.02 |
| Favorite interaction acknowledgement | 100 ms visual response |
| Duplicate network writes from one activation | 0 |

Do not add React/Vue, an icon package, a remote avatar proxy, background video,
blur filters, or a second analytics client for this scope.

### Release acceptance

A build is not ready until the following matrix passes on the actual staging
container with Newspaper and optimizer assets enabled:

- guest, reader, paid subscriber, administrator, and expired-session fixtures;
- default avatar, uploaded avatar, failed avatar, long Cyrillic name and bio;
- zero, one, six, and more-than-six favorites;
- favorite add, remove, double-click protection, failure rollback, and return
  from HearthPulse login to the same article;
- public profile ready, omitted optional fields, withdrawn, and temporary error;
- keyboard-only operation, visible focus, landmarks, names, status announcements,
  and screen-reader order;
- 320/390/560/768/1024/1440px, 200% zoom, reduced motion, forced colors, and no
  horizontal scroll;
- anonymous cached response and administrator response use the same typography,
  geometry, icons, and asset versions;
- no private data in page source, cache, analytics payload, or WordPress user
  tables.

Synthetic tests prove contract behavior but do not by themselves prove a real
HearthPulse login, real avatar upload, edge cache publication, or live favorite
write. Those flows require separate staging evidence before release.

## Do's and Don'ts

### Do

- Let article covers and class crests provide the visual energy.
- Use white space and typography to organize the account.
- Keep the favorite class as compact metadata beside the profile information.
- Put saved articles immediately below the profile, without tabs.
- Keep labels beside Twitch, YouTube, subscriber, and administrator marks where
  space permits.
- Preserve drafts and stable geometry through loading and errors.
- Reuse semantic identity, button, field, badge, and status primitives.
- Validate both guest and administrator CSS after every optimizer change.

### Don't

- Do not return to a full dark-teal dashboard canvas.
- Do not add decorative amber rails, glowing crowns, fantasy frames, or nested
  cards.
- Do not make the favorite control a large end-of-article advertisement.
- Do not put saved articles behind a tab or disclose them on a public profile.
- Do not use an icon without text or an accessible name for a primary action.
- Do not trust WordPress login, browser data, or CSS to determine Reader roles.
- Do not hide failures, silently retry uncertain writes, or leave an optimistic
  avatar/favorite state after the server rejected it.
- Do not change Newspaper templates or global site styles to implement this
  contract.
