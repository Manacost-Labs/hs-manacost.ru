# GitHub Copilot instructions for hs-manacost.ru

Read and follow the repository `AGENTS.md` before proposing or changing code. Use `config/ai-skills.json` for mandatory routing and load `hs-manacost-project` for all project work.

Treat `/srv/projects/wordpress/hs-manacost.ru` as source. Do not suggest editing runtime `/var/www` copies, uploads, caches, database values, secrets or generated/vendor artifacts as source. Preserve `.ru` canonical, `.com` noindex mirror and staging noindex behavior.

Follow existing WordPress/MU-plugin patterns, add a regression check, keep diffs minimal, and run `make check`. Normal delivery is Git/PR → successful Quality → staging; production uses the exact staging-verified SHA through the manual workflow.
