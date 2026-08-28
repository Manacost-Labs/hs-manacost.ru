# Testing and release evidence

## Scenario matrix

For the changed journey verify:

- editor/administrator roles and a denied role;
- desktop and 390 px mobile layout;
- normal, empty, error and slow-response states;
- cold and warm cache as separate reports;
- realistic data volume and pagination;
- keyboard completion and visible progress;
- create/edit/save/retry/delete or the exact actions affected.

Use integration fixtures or disposable staging records. Never run destructive performance scenarios against production content.

## Evidence manifest

Create JSON with `schema_version`, `environment`, `screen`, `authenticated_role`, `dataset_size`, `sample_count`, `cache_state`, `metrics` and `functional_checks`. Each metric contains `name`, `unit`, `before`, `after` and `budget`.

Required functional checks are `behavior`, `permissions`, `desktop`, `mobile` and `error_path`. All must be true.

Run:

```bash
.agents/skills/wordpress-admin-performance/scripts/evaluate_admin_performance.py report.json
```

Exit codes are `0` for `PASS`, `1` for `BLOCKED`, and `2` for invalid evidence. The evaluator rejects secrets-related fields, fewer than five samples, missing core metrics, excessive budgets, regressions over 5%, and failed functional checks.

## Release gate

1. Add or update the behavioral and budget regression test.
2. Run `make check` and the staged security scan.
3. Merge through a focused PR and wait for Quality.
4. Verify the exact SHA on `test.hs-manacost.ru`.
5. Run the same operator journey and attach non-secret before/after evidence.
6. Promote only that staging-verified SHA with an explicit rollback.

Production smoke checks confirm availability and correctness; they are not permission to run load tests or expose profiling data.
