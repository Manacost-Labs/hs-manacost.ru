# Component and content system

Inventory components by user purpose, not by their current CSS selector. Start with:

- global header, primary navigation, mobile menu and search;
- breaking/featured story, article card variants and editorial labels;
- homepage sections, category feed, pagination and search results;
- article header, author/date/meta, body typography, figures/captions, embeds, deck widgets, tables, quotes and related content;
- ad slots, newsletter/community actions, comments and footer;
- editor preview or wp-admin controls only when the redesign changes authoring behavior.

For each component record content limits, variants, default/loading/empty/error/disabled states, keyboard behavior, image aspect ratio/fallback, analytics event, cache sensitivity and owning implementation layer.

Derive tokens before component CSS:

- semantic color roles instead of page-specific hex values;
- display/body/utility typography roles with Cyrillic coverage and fallback stacks;
- a bounded spacing and type scale;
- a small hierarchy of radii, borders and shadows;
- motion durations/easing plus reduced-motion behavior;
- container widths and responsive rules based on content, not device brand names.

Test the longest real title, missing image, narrow phone, slow connection, logged-in toolbar and enlarged text. Do not duplicate near-identical card markup to obtain visual variants.
