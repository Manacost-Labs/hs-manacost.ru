.PHONY: check php-lint test shell-check

check: php-lint test shell-check

php-lint:
	@find wordpress config -type f -name '*.php' -print0 | xargs -0 -n 1 php -l >/dev/null
	@echo "PHP syntax: OK"

test:
	@python3 -m unittest discover -s tests -v

shell-check:
	@bash -n ops/deploy.sh ops/smoke-check.sh ops/sync-ai-skills.sh ops/ci/hs-manacost-ci-deploy
	@if command -v shellcheck >/dev/null 2>&1; then shellcheck ops/deploy.sh ops/smoke-check.sh ops/sync-ai-skills.sh ops/ci/hs-manacost-ci-deploy; fi
	@if command -v actionlint >/dev/null 2>&1; then actionlint .github/workflows/*.yml; fi
	@echo "Shell syntax: OK"
