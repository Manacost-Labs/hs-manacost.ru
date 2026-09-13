.PHONY: check composer-validate code-quality php-lint test lightbox-test lightbox-browser-test reader-test reader-browser-test reader-css-check web-v2-check web-v2-browser-test shell-check skill-audit contracts contract-check change-impact integration visual admin-performance plugin-audit

.PHONY: nginx-media-test

check: composer-validate php-lint contract-check skill-audit test lightbox-test reader-css-check reader-test web-v2-check shell-check nginx-media-test

nginx-media-test:
	@python3 ops/nginx/tests/check_media_negotiation.py
	@python3 ops/nginx/media-negotiation/test_deploy.py
	@python3 ops/nginx/media-negotiation/test_tls.py

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

lightbox-test:
	@node --check wordpress/mu-plugins/hs-manacost-lightbox/lightbox.js
	@python3 -m unittest tests.test_content_lightbox -v

lightbox-browser-test:
	@node tests/lightbox/browser.mjs

reader-css-check:
	@tmp_file=$$(mktemp); trap 'rm -f "$$tmp_file"' EXIT; \
		./node_modules/.bin/tailwindcss -c tailwind.config.js \
		-i wordpress/mu-plugins/hs-manacost-reader/tailwind.input.css \
		-o "$$tmp_file" --minify >/dev/null; \
		cmp -s "$$tmp_file" wordpress/mu-plugins/hs-manacost-reader/tailwind.css || \
		{ echo "Reader Tailwind CSS is stale; run npm run build:reader-css" >&2; exit 1; }

reader-browser-test: lightbox-browser-test
	@node tests/reader-ui/browser.mjs
	@node tests/reader-ui/comments-browser.mjs
	@node tests/reader-ui/comments-flows.mjs
	@node tests/reader-ui/community-browser.mjs

web-v2-check:
	@npm ci --prefix services/web-v2 --ignore-scripts
	@npm audit --prefix services/web-v2 --audit-level=high
	@npm run lint --prefix services/web-v2
	@npm run typecheck --prefix services/web-v2
	@npm run test --prefix services/web-v2
	@npm run build --prefix services/web-v2

web-v2-browser-test: web-v2-check
	@npm run test:browser --prefix services/web-v2

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
