"""Exercise card changes against recorded provider responses and a controlled HTTP/cache boundary."""
import hashlib
import re
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-card-changes"


class CardChangesTest(unittest.TestCase):
    def test_pairs_source_and_rendering(self):
        for mode in ("normal", "disabled"):
            with self.subTest(mode=mode):
                result = subprocess.run(
                    ["php", "tests/fixtures/card-changes.php", mode], cwd=ROOT,
                    capture_output=True, text=True, timeout=30,
                )
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertEqual(result.stdout.strip(), "PASS")

    def test_archive_search_merges_dictionary_and_archive(self):
        result = subprocess.run(
            ["php", "tests/fixtures/card-changes-search.php"], cwd=ROOT,
            capture_output=True, text=True, timeout=30,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertEqual(result.stdout.strip(), "PASS")

    def test_search_uses_the_bounded_transport_and_throttle(self):
        search = (PLUGIN / "search.php").read_text(encoding="utf-8")
        self.assertIn("HS_Card_Changes_Source::get_lists", search)
        self.assertNotIn("wp_remote_get", search)
        self.assertNotIn("wp_safe_remote_get", search)
        admin = (PLUGIN / "admin.php").read_text(encoding="utf-8")
        self.assertIn("throttled( 'search' )", admin)
        self.assertIn("HS_Card_Changes_Search::run", admin)

    def test_public_stylesheet_is_transparent_and_mobile_single_column(self):
        css = (PLUGIN / "card-changes.css").read_text(encoding="utf-8")
        self.assertRegex(css, r"\.hs-cc \{[^}]*background: transparent;[^}]*\}")
        self.assertNotRegex(css, r"\.hs-cc__item\s*\{[^}]*(?<!-)border:\s*[1-9]")
        self.assertRegex(css, r"@media \(max-width: 720px\)\s*\{\s*\.hs-cc,\s*\.hs-cc--cols-2,\s*\.hs-cc--cols-3 \{\s*grid-template-columns: minmax\(0, 1fr\);")

    def test_editor_never_sends_markup_and_uses_core_shortcode_block(self):
        script = (PLUGIN / "editor.js").read_text(encoding="utf-8")
        self.assertIn("createBlock('core/shortcode'", script)
        self.assertIsNone(re.search(r"outerHTML|insertAdjacentHTML", script))
        # Only the server-rendered, validated preview is assigned as HTML.
        self.assertEqual(len(re.findall(r"innerHTML\s*=", script)), 1)
        self.assertIn("preview.innerHTML = data.html", script)

    def test_scripts_parse(self):
        for script in ("editor.js", "dialog-focus.js"):
            with self.subTest(script=script):
                result = subprocess.run(["node", "--check", str(PLUGIN / script)], capture_output=True, text=True, timeout=30)
                self.assertEqual(result.returncode, 0, result.stderr)

    def test_asset_versions_are_content_hashes(self):
        render = (PLUGIN / "render.php").read_text(encoding="utf-8")
        versions = dict(re.findall(r"'([\w.-]+)'\s*=>\s*'([a-f0-9]{12})'", render.split("ASSET_VERSIONS", 1)[1].split(");", 1)[0]))
        self.assertEqual({"card-changes.css", "editor.css", "editor.js", "dialog-focus.js"}, set(versions))
        for asset, version in versions.items():
            expected = hashlib.sha256((PLUGIN / asset).read_bytes()).hexdigest()[:12]
            self.assertEqual(expected, version, f"{asset} content hash is stale")
        self.assertNotIn("VERSION", re.sub(r"public const VERSION.*", "", (PLUGIN / "admin.php").read_text(encoding="utf-8")).split("enqueue", 1)[1])

    def test_visitor_and_compose_paths_never_reach_the_provider(self):
        render = (PLUGIN / "render.php").read_text(encoding="utf-8")
        self.assertNotIn("Source::resolve", render)
        self.assertNotIn("Source::refresh", render)
        self.assertNotIn("Source::prefetch", render)
        admin = (PLUGIN / "admin.php").read_text(encoding="utf-8")
        compose = admin.split("private static function compose", 1)[1]
        self.assertNotIn("Source::", compose, "compose must render from posted pinned data only")
        self.assertRegex(compose, r"HS_Card_Changes_Render::html\(.*\), true \)", "preview uses pinned-only rendering")
        self.assertIn("resolve_live", admin)
        self.assertNotIn("aria-live", re.search(r'<div id="hs-cc-preview"[^>]*>', (PLUGIN / "dialog.php").read_text(encoding="utf-8")).group(0))

    def test_staging_probe_is_staging_only_and_cleans_up(self):
        probe = (ROOT / "tests/card-changes/staging.mjs").read_text(encoding="utf-8")
        self.assertIn("base !== 'https://test.hs-manacost.ru'", probe)
        # The disposable post is removed in `finally`, only when it carries this run's unique marker.
        final = probe.split("} finally {", 1)[1]
        self.assertIn("post.title.raw === marker", final)
        # A lost create response is resolved by looking the draft up by marker and author, not reported as clean.
        self.assertIn("search=${encodeURIComponent(marker)}", final)
        self.assertIn("report.cleaned = !createSent", final)
        self.assertIn("?force=true", final)
        self.assertIn("'private'", probe)
        self.assertNotIn("'publish'", probe)
        workflow = (ROOT / ".github/workflows/admin-performance-staging.yml").read_text(encoding="utf-8")
        self.assertIn("node tests/card-changes/staging.mjs", workflow)
        self.assertIn("CARD_CHANGES_NAME_SEARCH: ${{ github.ref == 'refs/heads/main' && 'required' || 'optional' }}", workflow)

    def test_ajax_boundary_requires_nonce_and_capability(self):
        admin = (PLUGIN / "admin.php").read_text(encoding="utf-8")
        self.assertIn("check_ajax_referer( 'hs_card_changes', 'nonce' )", admin)
        self.assertIn("current_user_can( 'edit_post', $post_id )", admin)
        self.assertNotIn("wp_ajax_nopriv", admin)


if __name__ == "__main__":
    unittest.main()
