# Manacost: Decks

WordPress plugin for managing and publishing Hearthstone deck feeds on Manacost.

## Features

- Custom `hs_deck` post type with class, mode, archetype, streamer, dust, games, winrate, source, and deck code fields.
- Main deck feed shortcode: `[hs_decks]`.
- Single deck shortcode: `[hs_deck id="123"]`.
- Deck feed filtering by class, mode, period, dust, games, winrate, tags, and streamer.
- Automatic class/mode detection from Hearthstone deck codes.
- Archetype detection from similar deck titles.
- Admin statistics dashboard with period filters, top decks, archetypes, banner clicks, and copy trends.
- CSV/JSON period statistics import.
- Activity log for deck changes.
- Soft archive for removed decks.
- Members plugin capability integration for granular access control.
- REST endpoints for Telegram/external imports.

## Installation

1. Copy the repository folder into `wp-content/plugins/wp-manacost-decks`.
2. Activate **Manacost: Decks** in WordPress admin.
3. Open **База колод** in the admin menu and configure permissions/settings.
4. Place `[hs_decks]` on the page where the deck feed should appear.

## REST Import

Create a deck from Telegram or another importer:

```http
POST /wp-json/manacost/v1/decks
```

Useful JSON fields:

```json
{
  "title": "Example Hunter",
  "deck_code": "AAECA...",
  "streamer": "Author",
  "dust_cost": 4200,
  "games": 25,
  "winrate": 60,
  "publish_to_feed": true
}
```

`publish_to_feed: true` publishes the deck in `[hs_decks]`; omitted or false keeps the deck hidden by default.

## Validation

Current local validation:

```bash
php -l unified-hs-plugins.php
php -l hs-deck-manager/hs-decks-manager.php
node --check assets/js/hs-decks-front.js
```
