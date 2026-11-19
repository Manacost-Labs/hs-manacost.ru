"""Exercise card changes against recorded provider responses and a controlled HTTP/cache boundary."""
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

    def test_ajax_boundary_requires_nonce_and_capability(self):
        admin = (PLUGIN / "admin.php").read_text(encoding="utf-8")
        self.assertIn("check_ajax_referer( 'hs_card_changes', 'nonce' )", admin)
        self.assertIn("current_user_can( 'edit_post', $post_id )", admin)
        self.assertNotIn("wp_ajax_nopriv", admin)


if __name__ == "__main__":
    unittest.main()
