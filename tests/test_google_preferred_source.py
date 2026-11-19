from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-google-preferred-source.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
PREFERRED_SOURCE_URL = "https://www.google.com/preferences/source?q=https://hs-manacost.ru"

STUBS = r"""
define('ABSPATH', '/');
$GLOBALS['filters'] = [];
$GLOBALS['actions'] = [];
$GLOBALS['styles'] = [];
$GLOBALS['inline_styles'] = [];

class WP_Post {
    public $ID;
    public $post_status;
    public function __construct($id, $status) { $this->ID = $id; $this->post_status = $status; }
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['filters'][$hook][] = [$callback, $priority];
}
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['actions'][$hook][] = [$callback, $priority];
}
function is_admin() { return $GLOBALS['ctx']['admin']; }
function is_feed() { return $GLOBALS['ctx']['feed']; }
function is_preview() { return $GLOBALS['ctx']['preview']; }
function wp_doing_ajax() { return $GLOBALS['ctx']['ajax']; }
function wp_is_json_request() { return $GLOBALS['ctx']['json']; }
function is_singular($post_type = '') { return $GLOBALS['ctx']['singular'] && $post_type === 'post'; }
function in_the_loop() { return $GLOBALS['ctx']['loop']; }
function is_main_query() { return $GLOBALS['ctx']['main']; }
function get_the_ID() { return $GLOBALS['ctx']['the_id']; }
function get_queried_object_id() { return 77; }
function get_queried_object() { return new WP_Post(77, $GLOBALS['ctx']['status']); }
function esc_url($url) { return htmlspecialchars($url, ENT_QUOTES); }
function esc_html__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES); }
function esc_attr__($text, $domain = 'default') { return htmlspecialchars($text, ENT_QUOTES); }
function wp_register_style($handle, $src, $deps = [], $ver = false) {
    $GLOBALS['styles'][$handle] = ['src' => $src, 'enqueued' => false];
}
function wp_enqueue_style($handle) { $GLOBALS['styles'][$handle]['enqueued'] = true; }
function wp_add_inline_style($handle, $css) { $GLOBALS['inline_styles'][$handle] = $css; }
"""

PUBLIC_ARTICLE = {
    "admin": False,
    "feed": False,
    "preview": False,
    "ajax": False,
    "json": False,
    "singular": True,
    "loop": True,
    "main": True,
    "the_id": 77,
    "status": "publish",
}


def run_php(body: str, **overrides: object) -> dict:
    ctx = {**PUBLIC_ARTICLE, **overrides}
    pdf = ctx.pop("pdf", False)
    script = (
        STUBS
        + f"$GLOBALS['ctx'] = json_decode({json.dumps(json.dumps(ctx))}, true);\n"
        + ("define('MANACOST_GUIDE_PDF_RENDERING', true);\n" if pdf else "")
        + f"require {json.dumps(str(PLUGIN))};\n"
        + body
    )
    result = subprocess.run(
        [PHP_BINARY, "-d", "display_errors=stderr", "-r", script],
        capture_output=True,
        text=True,
        check=False,
    )
    if result.returncode != 0 or result.stderr.strip():
        raise AssertionError(result.stderr or result.stdout)
    return json.loads(result.stdout)


FILTER_CONTENT = (
    "$entry = $GLOBALS['filters']['the_content'][0];\n"
    "echo json_encode(['priority' => $entry[1], 'html' => call_user_func($entry[0], $GLOBALS['content'] ?? '<p>Текст статьи.</p>')]);"
)


class GooglePreferredSourceTest(unittest.TestCase):
    def test_block_is_appended_after_article_text_and_late_ad_banner(self) -> None:
        result = run_php(FILTER_CONTENT)

        self.assertGreater(result["priority"], 30, "must run after the RSYA end-of-article banner (priority 30)")
        html = result["html"]
        self.assertTrue(html.startswith("<p>Текст статьи.</p>"))
        self.assertIn('class="hs-google-source"', html)
        self.assertIn(f'href="{PREFERRED_SOURCE_URL}"', html)
        self.assertIn('target="_blank"', html)
        self.assertIn('rel="noopener nofollow"', html)
        self.assertIn("предпочитаемые источники Google", html)
        self.assertIn('aria-hidden="true"', html)

    def test_block_is_rendered_once(self) -> None:
        body = (
            "$cb = $GLOBALS['filters']['the_content'][0][0];\n"
            "$once = call_user_func($cb, '<p>Текст</p>');\n"
            "echo json_encode(call_user_func($cb, $once));"
        )

        html = run_php(body)
        self.assertEqual(html.count('class="hs-google-source"'), 1)

    def test_block_is_skipped_outside_the_public_article_body(self) -> None:
        cases = {
            "admin": {"admin": True},
            "feed": {"feed": True},
            "preview": {"preview": True},
            "ajax": {"ajax": True},
            "rest": {"json": True},
            "page": {"singular": False},
            "outside loop": {"loop": False},
            "secondary query": {"main": False},
            "other post": {"the_id": 12},
            "draft": {"status": "draft"},
            "guide pdf": {"pdf": True},
        }
        for name, overrides in cases.items():
            with self.subTest(name):
                self.assertEqual(run_php(FILTER_CONTENT, **overrides)["html"], "<p>Текст статьи.</p>")

    def test_inline_style_is_enqueued_only_for_public_articles(self) -> None:
        body = (
            "foreach ($GLOBALS['actions']['wp_enqueue_scripts'] as $entry) { call_user_func($entry[0]); }\n"
            "echo json_encode(['styles' => $GLOBALS['styles'], 'inline' => $GLOBALS['inline_styles']]);"
        )

        state = run_php(body)
        self.assertEqual(state["styles"]["hs-google-preferred-source"], {"src": False, "enqueued": True})
        self.assertIn(".hs-google-source__link", state["inline"]["hs-google-preferred-source"])

        for overrides in ({"singular": False}, {"feed": True}, {"status": "draft"}):
            with self.subTest(overrides=overrides):
                self.assertEqual(run_php(body, **overrides), {"styles": [], "inline": []})


if __name__ == "__main__":
    unittest.main()
