# Code and design quality guard

The project owns `config/quality-guard.json`. It references the real make targets,
Reader design tokens and component source. `make check` validates that registered
native commands still exist; the existing Quality workflow executes those native
checks and isolated WordPress/browser suites. No API key is required by CI.

Use `make quality-plan` to inspect risk-selected checks, `make quality-verify` for
the fast tier and `make design-verify` for current token/component contracts.
`python3 ops/code-quality/quality-guard.py --risk medium --allow-network` explicitly
allows the medium network checks. Heavy integration/visual runs require the
heavy flag and an isolated environment. Missing dependencies are failures.

The client uses the installed versioned server release and its dedicated venv.
For a reviewed staged release, set `MANACOST_QUALITY_ROOT` to that release's path.
The canonical client template lives in the skills repository at
`integrations/codex/subscription-savings/quality/project_client.py`. Updating the
server release and adopting application changes are separately verified actions.

## Поиск готового кода и проверочный набор

После выбора конкретных файлов сначала используйте локальный поиск:

```sh
context-economy --project "$PWD" retrieve 'restore image from storage' \
  --source wordpress/mu-plugins/hs-manacost-s3-offload/src/PathPolicy.php \
  --source wordpress/mu-plugins/hs-manacost-s3-offload/src/Hydrator.php
make quality-retrieval-eval
```

`config/retrieval-eval.json` содержит шесть размеченных запросов к реальному
коду и один запрос, для которого в выбранных файлах нет ответа. Локальный
прогон не обращается к модели. Для сравнения с нативными embedding/rerank
OpenRouter запустите `context-economy --project "$PWD" retrieval-eval
--manifest config/retrieval-eval.json --semantic` только при явном пробеле
локальных данных и свободном общем дневном лимите. Совпадение означает
кандидата: проверьте SHA исходника, совместимость и тесты. При перемещении
кода обновляйте метки в проектном файле.

## Учёт принятых задач

Для задачи, выбранной в реальный пилот, до подготовки выполните
`context-economy --project "$PWD" meter-start --task-id ID --current-session
--from-task-start`. Флаг `--current-session` разрешает точный локальный Codex
JSONL по `CODEX_SESSION_ID` или Claude Code JSONL по `CLAUDE_CODE_SESSION_ID`
внутри Claude Code. Для другого клиента нужен точный `--session`, а для журнала
Claude также `--session-format claude`.
Если подготовка уже началась, не утверждайте полное покрытие. Привяжите
вспомогательные сессии до их работы и передавайте `--meter-task-id ID`
командам, которые должны войти в итог.

Создайте в игнорируемой `.artifacts/pilot/` краткий `task.json` с `goal`,
непустыми `criteria` и `constraints`. Локальная команда `advise --task
.artifacts/pilot/task.json --category implementation --selected-model terra`
сохраняет `advice_id` без сетевого запроса; укажите фактическую категорию и
модель. После проверок завершите интервал через `meter-finish --task-id ID
--coverage-evidence '...'` только при полном покрытии подготовки, повторов и
помощников. Запишите фактический итог через `pilot-record --file
.artifacts/pilot/outcome.json --meter-task ID`; схема и правила сравнения
находятся в установленном `ADVISORY-PILOT.md`. Не создавайте фиктивную
baseline-задачу и не оценивайте кредиты по цене API.

`make quality-pilot-report` показывает пары принятых задач,
`make quality-usage-report` — измеренный end-to-end расход. `make
quality-cache-stats` показывает попадания, промахи, истечения и вытеснения;
`context-economy --project "$PWD" cache-stats --days 30` даёт дневной ряд.
До накопления реальных задач и вытеснений лимит кэша менять нельзя.

Integration Compose names include a hash of the physical worktree path. `start`,
`test` and `stop` derive the same name; cleanup cannot target another worktree's
old globally named integration stack. Choose a free `WP_TEST_PORT` when tests run
on the same host. Containers bind loopback and have explicit CPU/memory limits.
Production and staging data, caches, media, credentials and commercial sources
are not integration test fixtures.

Visual baselines require explicit review. The browser's fixture/synthetic-identity
checks supplement the full isolated WordPress suite and do not certify production.

`make code-quality` now includes PHPUnit, Rector dry-run and Deptrac. PHPUnit
reuses the real metadata-cache fixture and adds default/upper/coerced-limit
regressions. `config/rector-baseline.json` records six existing proposals from
487d9394 with source and proposal hashes. Changed source or new proposals fail;
never regenerate this file to silence a failure. Deptrac permits the two explicit
Newspaper adapters to use vendor APIs and forbids that dependency for other
first-party code. Its unassigned WordPress/global functions are reported; this
initial direction rule is not complete architectural classification.

`make quality-audit` checks locked Composer dependencies. `make quality-mutation`
requires CLI-only Xdebug or PCOV, regenerates coverage and runs Infection against
the metadata-cache class with one worker and a 15-second per-mutant timeout.
The initial measured score is 75.47%, above both 70% thresholds. The manual
Quality workflow can run this same command with Xdebug; production PHP extensions
are not changed. Locally, `PHP_INI_SCAN_DIR` may select an isolated CLI ini directory.

`make quality-query-monitor` and `make quality-wpscan` create and clean up an
owned disposable stack. They refuse an already active stack; use
`python3 ops/integration/quality-diagnostics.py --query-monitor` or `--wpscan`
explicitly when reusing this worktree's active fixture. Query Monitor 4.0.7 is
diagnostic-only; the browser exports numeric metrics/counts without SQL text,
cookies or nonces. The pinned WPScan container scans only loopback and the
installed-plugin inventory, with CPU/memory/time limits and timeout cleanup.
Its passive fingerprint result is not vulnerability-database coverage.
Set `WPSCAN_API_TOKEN_FILE` to an existing private token file for advisory data;
missing API data returns failure (2), never a green security result.

Native visual tests normalize the displayed loopback permalink port so separate
worktrees do not cause editor reflow. They still assert the real version button.
No snapshot was changed for the quality-tool adoption. Native performance reports
compare current samples with configured budgets: their `before` values are
configuration baselines, not measured pre-change improvements.

Public advisory alternative: `make quality-advisories` uses the no-registration
[WPVulnerability API](https://docs.wpvulnerability.com/) for the disposable stack's
exact WordPress, regular-plugin and theme inventory. Only installed component identifiers and versions
leave the host; no source code, site URL or credentials are sent. It does not impersonate the authenticated WPScan database.
Requests are bounded to 100 components, 2 MB per response and a shared 90-second request budget;
PHP version_compare applies the documented ranges. Findings, closed components,
unknown versions/components and network/schema errors return failure (2).
MU plugins/drop-ins remain covered by source checks, not this public database.
Raw public responses with SHA-256 and a summary are stored privately under
`.artifacts/quality-diagnostics/public-advisories`. No production target is scanned.

The `Newspaper_new` directory is explicitly mapped to vendor slug `newspaper`,
verified from its tagDiv/Text Domain header. No generic directory-name guessing
is used. The fixture run reported CVE-2026-93485 for WordPress 6.9.7 and three
private plugins unknown to the API; it correctly failed instead of claiming a
clean security result. This does not establish the production site's state.
Query Monitor runs WP-CLI under the operator UID/GID and cleans up only its named
browser container, including timeout paths.
