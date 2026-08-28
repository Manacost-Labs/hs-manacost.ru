# Release usage

## Pull requests

Run the analyzer against the PR base and save Markdown in the job summary. Treat exit code `1` as an unclassified first-party path requiring an ownership-map update. Exit code `2` means invalid input or tool failure.

## Staging

Complete every generated check that is applicable to the final diff. Browser, performance and visual artifacts must come from the exact commit deployed to staging. Do not accept a new performance or screenshot baseline without review.

## Production

Use the report to select smoke routes and rollback, then follow `wordpress-release-manager`. The report never performs deployment and never replaces explicit production authorization.
