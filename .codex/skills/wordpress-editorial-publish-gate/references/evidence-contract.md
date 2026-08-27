# Evidence contract

The evaluator accepts a JSON object with `subject`, `target`, `checks`, and `rollback`. Each check has `status` (`pass`, `fail`, or `not_applicable`), non-empty `evidence`, and optional `blocking` (defaults to true). A `not_applicable` check also requires `reason`.

Evidence must state the observed value and its source, not merely “checked”. Remove candidate, reader, cookie, IP, token, and authentication data. Use a staging post ID instead of copying private content into the manifest.

Example shape:

```json
{
  "subject": "staging post 123",
  "target": "test.hs-manacost.ru",
  "checks": {
    "editor_save_revision": {"status": "pass", "evidence": "revision 456 restored; content hash unchanged"}
  },
  "rollback": {"revision": "456", "commit": "0123456789abcdef0123456789abcdef01234567"}
}
```

The script validates completeness, not the truth of external observations. Pair it with the referenced specialist checks and retain raw logs/screenshots outside Git when they contain sensitive data.
