# Font loading

Prefer the system stack or licensed local WOFF2 files. Record provenance, license, subsets, supported Cyrillic code points, styles, weights, fallbacks, cache policy, and removal path. External font services require explicit privacy and performance approval.

Use `font-display: swap` or `optional` according to the design need. Preload only a font used immediately above the fold, with the correct type and `crossorigin` behavior. Avoid duplicate formats, unused weights, faux bold/italic, and preloading every subset.

Measure transferred bytes, request count, render timing, fallback duration, and CLS. Compare fallback metrics and use metric overrides only with measured values. A font is not accepted because its network request returned 200; inspect computed font rendering in the browser.
