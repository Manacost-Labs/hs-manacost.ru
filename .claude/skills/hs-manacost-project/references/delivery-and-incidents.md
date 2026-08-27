# Delivery and incident workflow

## Normal delivery

1. Work on a short branch in the source repository.
2. Run project tests/security checks and review the intended staged diff.
3. Commit, push and open a pull request.
4. Merge only after Quality passes.
5. Successful Quality on a `main` push automatically deploys that SHA to isolated staging and runs staging smoke checks.
6. Exercise task-specific behavior on `test.hs-manacost.ru` in addition to automated smoke checks.
7. Production requires the manual `Promote production` workflow with the full SHA that has a successful staging deployment.
8. After production promotion, verify `.ru`, `.com`, origin and both regional proxy routes as required by the workflow.

Do not use a newer branch tip when only an earlier commit was tested. Deployment evidence must identify a full commit SHA.

## Incident triage

1. Define user impact, start time, domains/regions and whether authenticated or anonymous traffic is affected.
2. Preserve evidence: status/redirect, selected headers, content marker/hash and exact failing interaction. Do not collect secrets unnecessarily.
3. Compare DNS/TLS, edge/proxy, origin, WordPress/cache, database and S3 in that order appropriate to the symptom.
4. Isolate the first divergent layer. Prefer a reversible mitigation limited to that layer.
5. Add a regression check and implement the source fix. If an authorized runtime hotfix is unavoidable, reproduce its exact diff in Git immediately.
6. Verify recovery on affected routes and watch for a reasonable period proportional to impact.

## Rollback

Rollback restores the prior source/config/data state; cache invalidation only exposes that restored state. Record:

- exact known-good commit or version;
- changed runtime/data objects;
- restore action and required authority;
- health checks deciding rollback success;
- cache/proxy URLs to invalidate after restoration.

Never use `git reset --hard`, broad filesystem deletion, database import or plugin deactivate-all as an improvised rollback.

## Manual boundaries

DNS, Cloudflare account rules, nginx activation, Wordfence optimized firewall, Redis drop-ins, database writes, S3 deletion and production deployment are external/runtime state changes. Execute only when they are inside the explicit request and a precise rollback exists.
