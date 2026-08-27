# Evidence and recovery

## Minimum incident record

- UTC timeline and reporter-visible symptom.
- Exact host/path, region, browser/authentication state and frequency.
- HTTP status, redirect chain, canonical/robots headers and timing.
- Service health and only the minimal redacted log excerpt supporting the diagnosis.
- Last known-good deployment SHA and most recent relevant configuration/content change.
- Named owner layer, proposed mutation and executable rollback.

## Recovery order

1. Stop the harmful deployment or job without destroying its state.
2. Restore availability using the narrowest reversible action.
3. Validate the original failure and dependent flows.
4. Add a regression check in source.
5. Release through staging unless an explicitly authorized emergency requires a runtime hotfix.
6. Backport an emergency hotfix to Git immediately and verify configuration drift.

For database or S3 restoration, identify the exact snapshot/object version and restore to a temporary target first. Never restore a production dump directly over the active database as a test.
