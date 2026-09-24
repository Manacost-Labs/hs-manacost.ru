---
name: wordpress-admin-plugin-portability
description: Build WordPress admin settings and first-party plugins in hs-manacost.ru with a reviewable path to reuse their code and allowed settings on the separate kolodahearthstone.com WordPress site.
---

# Portable admin plugins

Use this skill when a request combines a new admin feature or plugin with future use on `kolodahearthstone.com`. The sites have separate repositories, production WordPress installations, databases and staging environments. They do not share options or deployed plugin files.

## Before building

1. Read the `AGENTS.md` and project snapshot in each repository that will be changed. In hs-manacost, use `wordpress-admin-ui`, `wp-plugin-development`, `wordpress-plugin-dev`, `wordpress-change-impact` and the code-change baseline. Use the target project's own rules before editing Koloda.
2. Write a small contract: plugin slug and owner, operator task, roles/capabilities, admin screen, option names with types/defaults/sanitizers, assets, data dependencies, theme dependencies, and which settings are portable. Name the target site explicitly.
3. Extend an existing WordPress screen when it covers the task. For a new screen, use the native Settings API and the project's [admin pattern library](../../../docs/admin-ui-pattern-library.md). Scope hooks and assets to the screen. Keep expensive work and remote calls out of page rendering.
4. Keep common behavior independent of Newspaper and Blocksy. Put theme-specific behavior behind detected adapters; test both themes. Use site URLs from WordPress APIs, not a source-domain constant. Do not install an unused framework or copy an entire admin dashboard.

## Settings contract

- Register each option with a type, default and sanitizer. Use one explicit capability for access and writes; Settings API nonces protect intent. Validate allowed values and escape output. Keep large or seldom-used options out of autoload.
- Separate portable configuration from runtime state, counters, queues, cache keys and site-specific URLs. Never include credentials, licenses, cookies, tokens, personal data or serialized opaque plugin state in a transfer bundle.
- Record an allowlist of exact option names and safe transformations for the plugin. Review it against Koloda's `config/plugin-settings-policy.json`; an allowed prefix alone does not make every value safe to copy. Where possible, defaults and configuration in code replace a data transfer.
- Treat a setting change as a WordPress contract: update contract inventory and behavior tests in each affected repository.

## Move a plugin to Koloda

1. Verify the source plugin on hs-manacost staging, then record the reviewed source commit and a deterministic tree digest. Existing `hs-tooltip` has a pinned copy in Koloda's `config/shared-plugin-lock.json`. Add each new shared plugin to the lock and confirm that Koloda's verifier checks **every** entry; upgrade any legacy verifier that checks only `hs-tooltip` before relying on it.
2. Copy only reviewed plugin source into Koloda's source repository, without runtime files or secrets. Review the diff for Newspaper-only hooks, source-domain assumptions, admin capabilities, asset scope and collisions with existing Koloda plugins. Check compatibility with Blocksy and WordPress 6.9.7.
3. Activate and test on `test.kolodahearthstone.com` through Koloda's release path. Verify the admin screen with the intended role, save/validation/errors, keyboard/mobile, frontend impact and performance. Keep the source and target releases independently reversible.
4. For each allowed setting, export a redacted manifest of **names, types and intended transformations** first. Use a protected backup and a dry run before importing values on Koloda staging. Compare expected and stored values there without printing secrets. Transfer only the exact reviewed keys; never import the source database or theme options wholesale.
5. Promote the target's staging-verified SHA through its own production workflow. Verify `kolodahearthstone.com`, its legacy redirect, origin and regional proxies. Record the source SHA, target SHA, option allowlist and rollback in the handoff.

## Acceptance evidence

Provide a source/target compatibility table, option allowlist, source tree digest, target lock verification, staging functional and performance results, production state and rollback. If no concrete plugin or option values were requested, prepare the contract and tooling without claiming that a transfer occurred.
