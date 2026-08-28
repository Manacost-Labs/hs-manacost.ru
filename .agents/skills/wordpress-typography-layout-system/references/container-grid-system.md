# Container and grid system

The baseline Newspaper grid is 12 columns with a 1068 px desktop container, approximately 980 px at 1019–1140, 740 px at 768–1018, and 20 px mobile gutters at 767 and below. Treat these as owned constraints, not incidental numbers.

Keep article measure between roughly 55 and 75 characters per line for normal body copy. Separate the reading column from full-bleed media, ads, tables, deck widgets, and related-content grids. Full-bleed elements still need bounded behavior at 320 px and 200% zoom.

Use named containers and semantic grid areas. Align repeated edges across header, content, sidebar, footer, and admin patterns. Avoid arbitrary negative margins, percentage widths plus fixed padding that exceed 100%, and page-level overflow suppression.
