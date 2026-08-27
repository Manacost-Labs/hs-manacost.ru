.PHONY: check php-lint test shell-check

check: php-lint test shell-check

php-lint:
	@find wordpress config -type f -name '*.php' -print0 | xargs -0 -n 1 php -l >/dev/null
	@echo "PHP syntax: OK"

test:
	@python3 -m unittest discover -s tests -v

shell-check:
	@bash -n ops/deploy.sh
	@if command -v shellcheck >/dev/null 2>&1; then shellcheck ops/deploy.sh; fi
	@echo "Shell syntax: OK"

