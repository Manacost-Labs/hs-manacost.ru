# Tooltip card coverage

The main tooltip dictionary includes collectible cards and non-collectible
minions, spells, weapons, heroes and locations from the full Russian
HearthstoneJSON cards dataset. BG cards retain their separate dictionary.
Manual and scheduled rebuilds also merge the existing public Koloda catalog.
Both offline builders use the same full-data scope.

BE_222t (Безднокрыл, dbf 130406) is a regression fixture for token lookup,
editor search and hs_card rendering. The shipped dictionary contains its ID
and numeric alias. Existing entries and image choices are retained when
seeding missing cards. For automatic name replacement, collectible cards take
precedence over same-name tokens; explicit token IDs always remain available.

The HSJSON response is bounded to 32 MiB. Failed/truncated data or incomplete
Koloda pagination cannot replace a successful dictionary. Rebuilding stays
in the existing manual/cron path; frontend rendering and search stay local.

Verify with `python3 -m unittest tests.test_tooltip_dictionary`,
`node tests/tooltip/browser.mjs`, `node tests/tooltip/koloda-browser.mjs`,
`make check`, `make code-quality`, `make integration` and `make visual`.
Release tooltip 1.1.15 to Manacost staging first, then promote the same SHA.
Koloda imports that exact plugin tree and pins its source SHA and digest.
Rollback restores the previous plugin archive and its bundled dictionaries;
article content, media and runtime options are unchanged.
