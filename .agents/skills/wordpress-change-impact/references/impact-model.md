# Impact model

## Risk levels

- `low`: documentation or tests with no runtime behavior.
- `medium`: isolated first-party behavior with bounded rollback.
- `high`: editor/admin data flow, Newspaper, plugins, cache, SEO, analytics, deployment, proxy or security behavior.
- `critical`: production data mutation, destructive media work, credentials, DNS authority or an explicitly declared critical rule. The automated map does not grant authority for these operations.

The final risk is the highest matching rule. Path rules accumulate skills, surfaces, domains and checks rather than allowing a later rule to erase an earlier requirement.

## Classification

First-party PHP under `wordpress/mu-plugins` must match an owning rule in `config/change-impact-map.json`. The generic first-party rule provides baseline checks but deliberately remains unclassified. Add a narrow rule when introducing a new subsystem.

Contracts are selected from `config/wordpress-contracts.json` when their owning file changes. Dynamic contracts remain a manual-review item and require an integration test.

## Domain model

- `test.hs-manacost.ru`: first deployment and browser verification.
- `hs-manacost.ru`: production canonical.
- `hs-manacost.com`: production noindex mirror sharing runtime behavior.
- `origin`, `ru-moscow`, `ru-novosibirsk`: delivery routes required for production/network changes.

Admin-only changes normally affect staging and the shared production runtime, even though public frontend rendering may not change.
