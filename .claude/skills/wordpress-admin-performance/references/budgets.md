# Admin performance budgets

Use budgets as release gates, not aspirational prose. Measure the same authenticated role, dataset, cache state and viewport before and after.

## Required metrics

| Metric | Definition | Initial ceiling |
|---|---|---:|
| `ttfb_ms` | Navigation start to first response byte | 1,200 ms |
| `interactive_ms` | Navigation start until the primary control is usable and no blocking task remains | 2,000 ms |
| `sql_queries` | Queries executed for the measured server request | 100 |
| `peak_memory_mb` | Peak PHP memory for the measured request | 128 MB |
| `long_tasks` | Browser main-thread tasks longer than 50 ms before interaction | 3 |

These are initial project targets. A screen with an approved exception may use a looser budget only when the report explains the constraint and includes a follow-up owner. The evaluator rejects ceilings above 1,500 ms TTFB, 2,500 ms interactive, 120 queries, 160 MB or four long tasks.

## Journey targets

| Journey | Additional target |
|---|---:|
| Open posts list | usable within 1,500 ms warm |
| Open article editor | usable within 2,000 ms warm |
| Save draft/publish response | p95 within 1,500 ms |
| Filter/search response | p95 within 700 ms |
| Autosave request | p95 within 800 ms |
| Small AJAX toggle/action | p95 within 500 ms |

Use five or more samples. Prefer median for navigation and p95 for repeated actions. Record failures and timeouts rather than discarding them.

## Regression rule

Block when an after-value exceeds its budget or is more than 5% worse than baseline. Do not trade a severe regression in one metric for an average improvement elsewhere. A faster response that returns incomplete data, skips permission checks or loses saves is a failure.
