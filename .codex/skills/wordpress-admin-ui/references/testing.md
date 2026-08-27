# Verification and release checklist

## Test the workflow, not the screenshot

Exercise create, open, edit, validate, save, refresh, filter, sort, paginate, bulk action, retry, cancel, and delete/archive where present. Verify direct URLs and back/forward navigation. Test with the least-privileged intended role as well as an administrator.

## State matrix

Cover each applicable state:

| State | Expected behavior |
|---|---|
| Initial loading | Stable layout, progress announced, no duplicate request |
| Empty product | Explain purpose and provide the primary creation action |
| Empty filter | Explain that filters removed all results and offer reset |
| Validation error | Preserve input, show summary and inline actionable messages |
| Network/server error | Keep data, explain retry, avoid false success |
| Permission denied | Return 403 or a clear screen without leaking privileged data |
| Partial/stale data | Mark incomplete or stale content and offer refresh |
| Success | Confirm the object and next useful action |
| Background work | Show durable queued/running/completed/failed state |

## Accessibility

- Navigate every action using Tab, Shift+Tab, Enter, Space, Escape, and arrow keys where the component pattern requires them.
- Verify visible focus, logical focus order, dialog focus return, heading structure, labels, descriptions, error association, and live announcements.
- Check that status is not communicated by color alone and text meets WCAG AA contrast.
- Test at 200% browser zoom and with reduced motion.
- Prefer native semantics; add ARIA only where native HTML cannot express the behavior.

WordPress reference: [Accessibility Team Handbook](https://make.wordpress.org/accessibility/handbook/).

## Responsive and device checks

Test at 320, 375, 768, 1024, and 1440 CSS pixels. Confirm there is no whole-page horizontal scroll, clipped notice, unreachable submit button, off-screen dialog, overlapping admin bar, or hover-only action. Test touch target spacing and the on-screen keyboard for long forms.

## Performance

- Measure initial PHP response, transferred JavaScript/CSS, REST/AJAX request count, query count when relevant, and interaction latency before and after.
- Ensure assets load only on the owned screen.
- Paginate and debounce search; cancel or ignore stale requests.
- Avoid large option autoload payloads and unbounded post/meta queries.
- Verify optimistic updates roll back and background polling stops when the screen is hidden or the job completes.

## Project release gate

1. Run focused unit/integration tests and static analysis.
2. Run `make check` and `/home/debian/server/tools/ai-quality/bin/ai-security-check staged`.
3. Review the staged diff for global admin selectors, secrets, vendor edits, and unrelated refactors.
4. Push through Git and wait for deployment to `test.hs-manacost.ru`.
5. Run the real browser flow on staging at desktop and mobile sizes; inspect console and network failures.
6. Run `./ops/smoke-check.sh staging`.
7. Promote only the tested commit through the production workflow when explicitly requested.
