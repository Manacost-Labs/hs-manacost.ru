# Design contract

Create one JSON contract per approved redesign direction. Keep it free of secrets, personal data and copied production content.

Required top-level sections:

- `project`, `audience`, `goals`, `user_journeys`, and `pages`.
- `direction`: name, thesis, signature element, rationale and explicit anti-patterns.
- `tokens`: named colors, typography roles/scale, spacing scale, radii, shadows and motion.
- `components`: name, page ownership, variants and relevant states.
- `responsive`: required viewports, zoom level, container logic and overflow policy.
- `quality`: accessibility, performance, SEO, media, ads and analytics invariants.
- `implementation`: supported Newspaper ownership layer, vertical slices and dependencies.
- `rollout`: staging evidence, approval owner, rollback commit and template assignment restore.

Use concrete values. “Modern typography”, “fast”, “accessible” and “mobile friendly” are not contracts. Name the font roles, tokens, measurable budgets, keyboard journeys and exact page templates.

The validator checks completeness and safe project invariants. It cannot determine whether the visual direction is tasteful, distinctive or correctly implemented; use screenshots and human approval for those judgments.
