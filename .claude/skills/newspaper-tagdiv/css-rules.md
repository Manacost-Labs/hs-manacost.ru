# CSS rules

Official references: [Composer tutorial](https://forum.tagdiv.com/tagdiv-composer-tutorial/), [Website Manager](https://forum.tagdiv.com/tagdiv-composer-website-manager/), and [font customization](https://forum.tagdiv.com/font-customization/).

## Preferred order

1. Existing Composer element controls and responsive settings.
2. Website Manager global colors/fonts.
3. Scoped project stylesheet loaded by an MU-plugin or child theme.
4. Vendor stylesheet edit only as a documented last resort.

## Guardrails

- Scope every site-specific selector under a stable Manacost or page/component class.
- Do not use global resets, wildcard overrides, or broad `!important` rules to fight tagDiv specificity.
- Do not edit `style.css`, generated CSS, and minified CSS in parallel without identifying the actual runtime source.
- Preserve tagDiv breakpoints unless a measured layout requirement justifies an additional project breakpoint.
- Use logical properties where practical and test Cyrillic text expansion.
- Keep visible focus, sufficient contrast, zoom to 200%, and 44–48px touch targets.
- Give media explicit dimensions/aspect ratio to avoid CLS.

## Visual checks

- 390px phone, tablet, and desktop widths.
- No horizontal white strip or off-canvas overflow.
- Header/menu, homepage grids, article typography, tables, embeds, deck widgets, ads, and footer.
- Logged-out/incognito and logged-in admin bar states.
- Slow image load and missing-image fallback.
