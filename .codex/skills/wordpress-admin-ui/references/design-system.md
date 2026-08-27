# Admin design system

## Start from the existing product

Inventory current WordPress controls, icon language, brand colors, spacing, typography, border treatment, notices, and content density. Reuse WordPress admin patterns unless the project already owns an intentional component system. Do not introduce a disconnected visual style for one page.

## Define semantic tokens

Scope custom properties under the page root, for example `.manacost-admin`:

```css
.manacost-admin {
  --mc-color-text: #1d2327;
  --mc-color-muted: #646970;
  --mc-color-surface: #fff;
  --mc-color-border: #c3c4c7;
  --mc-color-accent: #2271b1;
  --mc-color-danger: #b32d2e;
  --mc-space-1: 4px;
  --mc-space-2: 8px;
  --mc-space-3: 12px;
  --mc-space-4: 16px;
  --mc-space-6: 24px;
  --mc-radius: 4px;
}
```

Prefer semantic names over component-specific or raw-color names. Reuse the spacing scale. Introduce a token only when at least two elements need it.

## Establish hierarchy

- Use one page heading and short contextual help.
- Put the most frequent action in the page header or immediately beside the relevant object.
- Render secondary actions as secondary or text buttons.
- Separate destructive actions spatially and visually; do not make them the default focus.
- Use section headings for scanability, not giant promotional typography.
- Keep body text readable and compact; do not shrink labels or helper text to hide density problems.

## Avoid synthetic visual noise

Avoid purple/indigo defaults, decorative gradients, oversized radii, shadow stacks, identical card grids, excessive whitespace, and icons without labels. Use cards only to express a real grouping or boundary. Prefer alignment and spacing before borders and shadows.

## Motion and feedback

Use motion to explain state change, not to decorate. Keep transitions short and limited to specific properties. Respect `prefers-reduced-motion`. Saving, completion, failure, stale data, and background processing must have persistent textual feedback; a toast alone is insufficient for a failed destructive or long-running action.

## Content design

- Name buttons with verbs: “Сохранить вакансию”, not “ОК”.
- State what went wrong and how to recover.
- Preserve submitted values after validation errors.
- Use concise, real Russian examples that expose long wrapping and empty values.
- Use consistent nouns for the same entity across menu, heading, filters, form, and notices.
