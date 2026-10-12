# Asset preservation evidence, 2026-10-12

Native offscreen thumbnails retain their exact source URLs and Composer crop
positions. Twenty-six paired browser replays cover home and page 2, mobile and
wide desktop, widths 320/390/530/768/900/1024/1440/1920 and DPR 1/2/3. No converted
thumbnail starts in the viewport; scrolling loads the images. Crop, geometry,
text metrics and fonts match; no duplicate background or failed native image.

Five mobile pairs on each page reduced median initially requested asset bodies
from 9,086,576 to 3,343,790 bytes (home) and 9,095,468 to 3,352,682 (page 2), about
63%. This controlled replay uses cached public asset responses and is not a
measurement of production wire bytes, live loading time or field Core Web Vitals.
Existing 900 px layout shifts remained roughly 0.10 in both versions.

Four additional paired CDP checks at 390 and 1440 px inspect actual platform
fonts for article headings, block headings, body and menu/search icons. Font
families, PostScript names, custom-font status, rendered glyph counts and text
geometry match: Ubuntu, Roboto, Oswald, Fira Sans ExtraBold and icomoon. No font
asset, family, weight, subset, CSS source or font-display rule was changed.
These checks do not establish every future editorial or authenticated state;
retain the full currently required font inventory and verify staging states.

## Six original images and lossless candidates

Originals were downloaded from the primary S3 bucket. Candidates use cwebp
`-lossless -exact -metadata all -q 100 -m 6` in a private local pilot. All six
match dimensions, decoded RGBA, ICC and EXIF; original SHA256 remains unchanged.
PNG chunks contain no gAMA/cHRM/sRGB color overrides; pHYs on some images records
print density, which WebP does not retain. Pixel dimensions and browser layout
are unchanged. These candidates have not been published or bulk-applied.

| Source | Original bytes | Candidate bytes | Current public WebP-capable response |
| --- | ---: | ---: | --- |
| 3131313-768x390.png | 614067 | 459146 | PNG 614067 |
| kvest-zhrecz-768x390.png | 608714 | 440922 | PNG 608714 |
| meta-polej-srazhenij-1068x542.png | 1067246 | 741260 | lossless WebP 741260 |
| oblozhka-dlya-meta-otcheta-1068x542.png | 960894 | 670538 | lossless WebP 670538 |
| oblozhka.png | 1335086 | 933076 | PNG 1335086 |
| temnaya-versiya-v2.png | 1726676 | 1111292 | existing WebP 179258 |

Both legacy and modern public requests returned 200 with correct dimensions.
Legacy responses are the byte-identical S3 PNGs. The two existing lossless WebPs
already match decoded originals and candidate size, so rewriting them offers no
gain. The background's new lossless candidate is much larger than current
WebP; retain current delivery. The three remaining PNGs offer roughly 25–30%
lossless savings if the missing/stale sidecar delivery can be resolved safely.
Do not claim those savings until publication and origin/region Accept checks.

Sidecar publication depends on complete independent media backup, restore and
capacity gates. Do not modify the copied optimizer engine without its canonical
source workflow. Existing image selection stays intact while native loading
removes the measured premature transfers. Any responsive-size change must first
prove attachment metadata, crop/aspect ratio, DPR coverage and cache invalidation;
filename substitution alone is insufficient.
