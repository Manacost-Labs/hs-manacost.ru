# Kolodahs API Sync for Manacost Decks

Small add-on plugin for `wp-manacost-decks`.

When an `hs_deck` post receives or changes `_deck_code`, the add-on schedules a
background sync job. The job sends the deck code to:

```text
https://api.kolodahs.ru/v1/deck
```

Then it stores the returned dust value in `_dust_cost`, downloads the generated
deck image into the WordPress media library, and sets it as the featured image.

The add-on also injects a **Сделать картинку и пыль** button next to the deck
code field on the `hs_deck` edit screen. The button uses the current textarea
value, so editors can generate the image and dust before pressing the main
WordPress update button.

When the button runs, it asks the public Deckview API for an archetype:

- `POST https://api.blizzcore.ru/archetype`
- `GET https://api.blizzcore.ru/archetypes?search=<raw>&limit=50`

The translated archetype fills the `hs_deck` title and is sent to Kolodahs, so
the generated image uses the same archetype title.

The plugin also adds a TinyMCE **HS Deck** button. It opens a modal where an
editor can paste a deck code and press **Вставить**. The add-on creates a hidden
`hs_deck` entry, generates the image and dust, then inserts `[hs_deck id="..."]`
into the editor.

## Settings

Open `Decks HS -> Kolodahs API` in wp-admin and set:

- API endpoint: `https://api.kolodahs.ru/v1/deck`
- API key: the key created on `kolodahs.ru`
- Wait seconds: up to `25`

The API key is stored in `wp_options`; it is not committed to Git.

## Kolodahs API

Create or request a deck image:

```http
POST https://api.kolodahs.ru/v1/deck
Content-Type: application/json
X-Kolodahs-Api-Key: <key>

{
  "title": "Example Hunter",
  "deck_code": "AAECA...",
  "wait_seconds": 20
}
```

Check a job later:

```http
GET https://api.kolodahs.ru/v1/deck/<hash>
X-Kolodahs-Api-Key: <key>
```

The response includes `dust`, `class`, `deck_type`, `state`, `ready`,
`image_url`, `download_url`, and `status_url`.
