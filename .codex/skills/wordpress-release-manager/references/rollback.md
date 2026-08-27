# Rollback

## Code rollback

Prefer the project deployment workflow using the last known-good, previously tested commit. Do not manually copy a mixture of files from multiple revisions.

## Data rollback

Use the migration-specific reverse operation or restore an exact verified snapshot to a temporary target first. Code rollback alone may not reverse schema/options/content changes.

## Media rollback

Restore the precise object version/source and its attachment mapping, regenerate optimized sidecars, verify S3, then invalidate only affected URLs.

## Proxy/config rollback

Install the named pre-change configuration on one node, validate syntax, reload, verify, then continue node by node. Keep a cache namespace change explicit.

After rollback, repeat the same acceptance matrix used for release. A successful command without user-flow verification is not a completed rollback.
