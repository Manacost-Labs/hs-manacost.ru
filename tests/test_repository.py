import json
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


class RepositoryPolicyTests(unittest.TestCase):
    def test_configuration_is_valid_json(self) -> None:
        for relative_path in (
            "config/site.json",
            "config/plugins.json",
            "config/network.json",
            "config/ai-skills.json",
            "config/wordpress-plugins.json",
        ):
            with (ROOT / relative_path).open(encoding="utf-8") as config_file:
                self.assertIsInstance(json.load(config_file), dict)

    def test_required_source_trees_exist(self) -> None:
        required = (
            "wordpress/mu-plugins",
            "wordpress/plugins",
            "wordpress/themes/Newspaper_new",
            "ops/nginx/origin.conf",
            "ops/nginx/mirror.conf",
            "ops/nginx/staging.conf",
            "ops/smoke-check.sh",
            "AGENTS.md",
            ".agents/skills/wordpress-plugin-dev/SKILL.md",
            ".claude/skills/wordpress-plugin-dev/SKILL.md",
        )
        for relative_path in required:
            self.assertTrue((ROOT / relative_path).exists(), relative_path)

    def test_forbidden_runtime_files_are_not_tracked(self) -> None:
        forbidden_names = {"wp-config.php", ".env", ".htpasswd"}
        forbidden_suffixes = {".sql", ".dump", ".pem", ".key", ".p12", ".pfx"}
        public_verification_material = {
            "wordpress/plugins/wordfence/lib/noc1.key",
            "wordpress/plugins/wordfence/vendor/wordfence/wf-waf/src/cacert.pem",
            "wordpress/plugins/wordfence/vendor/wordfence/wf-waf/src/falsepositive.key",
            "wordpress/plugins/wordfence/vendor/wordfence/wf-waf/src/rules.key",
        }
        failures: list[str] = []

        tracked = subprocess.run(
            ["git", "ls-files", "--cached", "--others", "--exclude-standard"],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
        ).stdout.splitlines()
        for relative in tracked:
            relative_path = Path(relative)
            path = ROOT / relative_path
            if not path.is_file():
                continue
            if path.name in forbidden_names or path.suffix.lower() in forbidden_suffixes:
                if relative not in public_verification_material:
                    failures.append(str(relative_path))
            if "uploads" in relative_path.parts:
                failures.append(str(relative_path))

        self.assertEqual([], sorted(set(failures)))

    def test_no_nested_git_repositories(self) -> None:
        nested = [
            str(path.relative_to(ROOT))
            for path in ROOT.rglob(".git")
            if path != ROOT / ".git"
        ]
        self.assertEqual([], nested)

    def test_all_domains_are_owned_by_this_repository(self) -> None:
        site = json.loads((ROOT / "config/site.json").read_text(encoding="utf-8"))
        self.assertEqual("https://hs-manacost.ru", site["site"]["primary_url"])
        self.assertEqual("https://hs-manacost.com", site["site"]["mirror_url"])
        self.assertEqual("https://test.hs-manacost.ru", site["site"]["staging_url"])

    def test_pipeline_keeps_production_manual(self) -> None:
        staging = (ROOT / ".github/workflows/deploy-staging.yml").read_text(encoding="utf-8")
        production = (ROOT / ".github/workflows/promote-production.yml").read_text(encoding="utf-8")
        self.assertIn("workflow_run:", staging)
        self.assertIn("workflow_run.event == 'push'", staging)
        self.assertIn("head_repository.full_name == github.repository", staging)
        self.assertIn("workflow_dispatch:", staging)
        self.assertIn("Full 40-character SHA currently at main", staging)
        self.assertIn("REQUESTED_SHA", staging)
        self.assertIn("git rev-parse origin/main", staging)
        self.assertIn("smoke-check.sh staging", staging)
        self.assertIn("skip_novosibirsk", staging)
        self.assertIn("MANACOST_SKIP_NOVOSIBIRSK", staging)
        self.assertIn("workflow_dispatch:", production)
        self.assertIn(
            "verify-staging:\n    runs-on: [self-hosted, linux, x64, hs-manacost-production]",
            production,
        )
        self.assertNotIn("verify-staging:\n    runs-on: ubuntu-latest", production)
        self.assertIn("successful staging deployment", production)
        self.assertIn("smoke-check.sh production", production)
        self.assertIn("skip_novosibirsk", production)
        self.assertIn("MANACOST_SKIP_NOVOSIBIRSK", production)

    def test_smoke_check_allows_only_an_explicit_novosibirsk_maintenance_exception(self) -> None:
        smoke = (ROOT / "ops/smoke-check.sh").read_text(encoding="utf-8")

        self.assertIn('MANACOST_SKIP_NOVOSIBIRSK:-false', smoke)
        self.assertIn("true|false", smoke)
        self.assertIn("ru-novosibirsk", smoke)
        self.assertIn("SKIP: ru-novosibirsk edge is in declared maintenance", smoke)

    def test_staging_release_clears_page_cache_and_warms_reader_assets_before_exposure(self) -> None:
        staging = (ROOT / ".github/workflows/deploy-staging.yml").read_text(encoding="utf-8")
        deploy_script = ROOT / "ops/ci/hs-manacost-ci-deploy"
        installer = ROOT / "ops/ci/install-deploy-helper.sh"
        warm_script = ROOT / "ops/ci/warm-staging-minified-assets.php"

        self.assertFalse((ROOT / "ops/purge-reader-page-cache.sh").exists())
        self.assertTrue(installer.is_file())
        self.assertTrue(warm_script.is_file())
        script = deploy_script.read_text(encoding="utf-8")
        installer_text = installer.read_text(encoding="utf-8")
        warm_script_text = warm_script.read_text(encoding="utf-8")
        self.assertIn("test-hs-manacost-wordpress", script)
        self.assertIn("test.hs-manacost.ru", script)
        self.assertIn('if [[ "$environment" == staging && ! -f "$workspace_real/ops/ci/warm-staging-minified-assets.php" ]]', script)
        self.assertIn('SERVER_NAME="$wordpress_host"', script)
        staging_branch = script.split('if [[ "$environment" == staging ]]', 1)[1].split(
            'if [[ "$environment" == production ]]', 1
        )[0]
        production_branch = script.split('if [[ "$environment" == production ]]', 1)[1]
        self.assertIn("rocket_clean_files", staging_branch)
        self.assertIn("rocket_clean_domain", staging_branch)
        self.assertIn("rocket_clean_minify", staging_branch)
        self.assertIn("https://test.hs-manacost.ru/account/", staging_branch)
        self.assertIn("wp_parse_url", staging_branch)
        self.assertIn('rocket_clean_files( array( $account_url ), null, false )', staging_branch)
        self.assertIn('run_wp eval-file "$workspace_real/ops/ci/warm-staging-minified-assets.php"', staging_branch)
        self.assertLess(
            staging_branch.index("rocket_clean_domain"),
            staging_branch.index("rocket_clean_minify"),
        )
        self.assertLess(
            staging_branch.index("rocket_clean_minify"),
            staging_branch.index('run_wp eval-file "$workspace_real/ops/ci/warm-staging-minified-assets.php"'),
        )
        self.assertLess(
            staging_branch.index('run_wp eval-file "$workspace_real/ops/ci/warm-staging-minified-assets.php"'),
            staging_branch.index('rocket_clean_files( array( $account_url ), null, false )'),
        )
        self.assertIn("get_page_by_path( 'account' )", warm_script_text)
        self.assertIn("get_header()", warm_script_text)
        self.assertIn("the_content()", warm_script_text)
        self.assertIn("get_footer()", warm_script_text)
        self.assertIn("apply_filters( 'rocket_buffer'", warm_script_text)
        self.assertIn("WP_ROCKET_MINIFY_CACHE_PATH", warm_script_text)
        self.assertIn('data-minify="1"', warm_script_text)
        self.assertIn("get_rocket_option( 'minify_css' )", warm_script_text)
        self.assertIn("is_rocket_post_excluded_option( 'minify_css' )", warm_script_text)
        self.assertIn("expected minified assets", warm_script_text)
        self.assertIn("$minified_size = filesize( $minified_file )", warm_script_text)
        self.assertIn("false === $minified_size || 0 === $minified_size", warm_script_text)
        self.assertIn("retain_staging_refresh_on_failure", script)
        self.assertIn("release_staging_refresh", script)
        self.assertNotIn("rocket_clean_", production_branch)
        self.assertNotIn("warm-staging-minified-assets.php", production_branch)
        self.assertIn("install -o root -g root -m 0755", installer_text)
        self.assertIn("/usr/local/sbin/hs-manacost-ci-deploy", installer_text)
        self.assertIn("Verify authorized deployment helper", staging)
        self.assertIn("cmp --silent", staging)
        self.assertNotIn("Purge reader account page cache", staging)
        self.assertNotIn("./ops/purge-reader-page-cache.sh staging", staging)
        self.assertLess(
            staging.index("Verify authorized deployment helper"),
            staging.index("Deploy isolated staging"),
        )

    def test_required_ai_skills_are_pinned(self) -> None:
        registry = json.loads((ROOT / "config/ai-skills.json").read_text(encoding="utf-8"))
        baseline = set(registry["baseline_for_code_changes"])
        self.assertTrue(
            {
                "agent-test-driven-development",
                "agent-code-review-and-quality",
                "agent-code-simplification",
                "agent-security-and-hardening",
                "agent-git-workflow-and-versioning",
            }.issubset(baseline)
        )
        routes = registry["task_routes"]
        self.assertIn("seo-technical", routes["seo"])
        self.assertIn("web-quality-core-web-vitals", routes["performance"])
        self.assertIn("frontend-design", routes["frontend_design"])

        local_skills = {item["name"]: item for item in registry["project_local"]}
        for name in (
            "hs-manacost-project",
            "newspaper-tagdiv",
            "wordpress-admin-ui",
            "wordpress-article-editor",
            "wordpress-runtime-stack",
            "wordpress-router",
            "wp-performance",
            "wp-phpstan",
            "wp-plugin-development",
            "wp-project-triage",
            "wp-rest-api",
            "wp-wpcli-and-ops",
        ):
            self.assertIn(name, local_skills)
            skill_path = ROOT / local_skills[name]["path"]
            self.assertTrue(skill_path.is_file(), str(skill_path))

        self.assertIn("newspaper-tagdiv", routes["newspaper_tagdiv"])
        self.assertIn("wordpress-admin-ui", routes["wordpress_admin_ui"])
        self.assertIn("wordpress-article-editor", routes["wordpress_article_editor"])
        self.assertIn("wordpress-runtime-stack", routes["wordpress_runtime_stack"])
        self.assertIn("hs-manacost-project", registry["baseline_for_project_tasks"])
        self.assertIn("hs-manacost-project", routes["wordpress"])


if __name__ == "__main__":
    unittest.main()
