# Accessibility test matrix

| Area | Required checks |
|---|---|
| Keyboard | logical order, all actions reachable, Escape behavior, no trap, skip link |
| Focus | visible indicator, restored after dialog, not hidden by sticky header |
| Structure | one meaningful page heading, ordered headings, landmarks, page language |
| Names | controls, icon buttons, images, inputs and status messages have useful names |
| Forms | persistent labels, described help/errors, summary, invalid state, retained input |
| Visual | contrast, 200% zoom, text spacing, reduced motion, no horizontal page scroll |
| Touch | targets are separated and usable without precision; drag has an alternative |
| Dynamic UI | loading/error/success announced; expanded/selected/sort states exposed |

Test the complete task, including validation errors and save completion. A page that can be tabbed through but cannot communicate the result is not complete.
