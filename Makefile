.PHONY: check composer-validate code-quality php-lint test reader-test reader-browser-test shell-check skill-audit contracts contract-check change-impact integration visual admin-performance plugin-audit

check: composer-validate php-lint contract-check skill-audit test reader-test shell-check

composer-validate:
	@composer validate --strict --no-check-publish

code-quality: composer-validate
	@ops/code-quality/run.sh

php-lint:
	@find wordpress config -type f -name '*.php' -print0 | xargs -0 -n 1 php -l >/dev/null
	@echo "PHP syntax: OK"

test:
	@python3 -m unittest discover -s tests -v

reader-test:
	@for source in services/reader/*.js; do node --check "$$source" || exit; done
	@node --test services/reader/test/*.test.js
	@for source in wordpress/mu-plugins/hs-manacost-reader/*.js; do node --check "$$source" || exit; done
	@python3 -m unittest discover -s tests/reader-ui -v

reader-browser-test:
	@node tests/reader-ui/browser.mjs
	@node tests/reader-ui/comments-browser.mjs
	@node tests/reader-ui/comments-flows.mjs
	@node tests/reader-ui/community-browser.mjs

shell-check:
	@find ops -type f -name '*.sh' -print0 | xargs -0 -n 1 bash -n
	@bash -n ops/deploy.sh ops/smoke-check.sh ops/sync-ai-skills.sh ops/ci/hs-manacost-ci-deploy
	@if command -v shellcheck >/dev/null 2>&1; then find ops -type f -name '*.sh' -print0 | xargs -0 shellcheck && shellcheck ops/deploy.sh ops/smoke-check.sh ops/sync-ai-skills.sh ops/ci/hs-manacost-ci-deploy; fi
	@if command -v actionlint >/dev/null 2>&1; then actionlint .github/workflows/*.yml; fi
	@echo "Shell syntax: OK"

skill-audit:
	@python3 ops/ai-skills/audit.py

contracts:
	@python3 ops/contracts/scan-wordpress-contracts.py --write

contract-check:
	@python3 ops/contracts/scan-wordpress-contracts.py --check

change-impact:
	@.agents/skills/wordpress-change-impact/scripts/analyze_change_impact.py --base "$${CHANGE_IMPACT_BASE:-origin/main}" --format markdown

integration:
	@ops/integration/run.sh

visual:
	@RUN_VISUAL=1 ops/integration/run.sh

admin-performance:
	@RUN_PERFORMANCE=1 ops/integration/run.sh

plugin-audit:
	@python3 ops/plugins/audit-updates.py --output-dir .artifacts/plugin-audit
