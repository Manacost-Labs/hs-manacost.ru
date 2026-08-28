# Cyrillic quality

Require complete Cyrillic coverage for every user-facing family and weight. Test Russian uppercase/lowercase, `Ё/ё`, numerals, punctuation, quotes, em dash, non-breaking spaces, mixed Hearthstone names, and Latin URLs/deck codes.

Use real long Cyrillic headlines at 320 and 390 px. Check glyph substitution, weight mismatch, line breaks, orphaned short prepositions, clipping of descenders/diacritics, faux styles, and fallback flashes. Confirm both anonymous pages and the editor/admin view use an intentional readable fallback.

Do not ship a font subset that silently falls back character by character or a display face that makes dense Russian editorial scanning slower.
