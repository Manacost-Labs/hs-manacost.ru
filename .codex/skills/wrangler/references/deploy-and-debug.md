# Wrangler deployment and debugging

Use this guide for dry runs, deployment, versions, logs and rollback.

## Pre-deployment

- Confirm the branch and exact commit.
- Install locked dependencies and run project tests, lint and type checks.
- Run the project-pinned `wrangler deploy --dry-run` command for the target environment.
- Review bundle size, startup time, compatibility-date changes, routes and binding differences.
- Confirm every secret name exists without printing its value.

## Deployment

- Use the repository's CI workflow when one exists.
- Name the target environment explicitly.
- Do not use keep-vars or configuration overrides merely to hide drift; reconcile the owning configuration.
- Record the resulting Worker version and deployment URL/route.
- Verify health and one representative user flow from the intended region.

## Observability

- Tail logs for a bounded interval with status or search filters.
- Redact authorization, cookies, query secrets, request bodies and personal data.
- Correlate a test request with logs, metrics and the actual response.
- Treat an empty log stream as inconclusive until routes and sampling are confirmed.

## Rollback

- Identify the last known-good Wrangler version before deployment.
- Prefer a version rollback or the project's release workflow.
- Re-verify routes, bindings and dependent resources after rollback.
- Resource migrations and secret changes may need a separate recovery step; a Worker version rollback does not undo them.

## Sources

- [Deployments and versions](https://developers.cloudflare.com/workers/configuration/versions-and-deployments/)
- [Workers logs](https://developers.cloudflare.com/workers/observability/logs/)
