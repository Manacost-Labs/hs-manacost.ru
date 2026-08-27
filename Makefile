.PHONY: check composer-validate code-quality php-lint test shell-check contracts contract-check integration visual plugin-audit

check: composer-validate php-lint contract-check test shell-check

composer-validate:
	@composer validate --strict --no-check-publish

code-quality: composer-validate
	@ops/code-quality/run.sh

php-lint:
	@find wordpress config -type f -name '*.php' -print0 | xargs -0 -n 1 php -l >/dev/null
	@echo "PHP syntax: OK"

test:
	@python3 -m unittest discover -s tests -v

shell-check:
	@find ops -type f -name '*.sh' -print0 | xargs -0 -n 1 bash -n
	@bash -n ops/deploy.sh ops/smoke-check.sh ops/sync-ai-skills.sh ops/ci/hs-manacost-ci-deploy
	@if command -v shellcheck >/dev/null 2>&1; then find ops -type f -name '*.sh' -print0 | xargs -0 shellcheck && shellcheck ops/deploy.sh ops/smoke-check.sh ops/sync-ai-skills.sh ops/ci/hs-manacost-ci-deploy; fi
	@if command -v actionlint >/dev/null 2>&1; then actionlint .github/workflows/*.yml; fi
	@echo "Shell syntax: OK"

contracts:
	@python3 ops/contracts/scan-wordpress-contracts.py --write

contract-check:
	@python3 ops/contracts/scan-wordpress-contracts.py --check

integration:
	@ops/integration/run.sh

visual:
	@RUN_VISUAL=1 ops/integration/run.sh

plugin-audit:
	@python3 ops/plugins/audit-updates.py --output-dir .artifacts/plugin-audit
