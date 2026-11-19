from __future__ import annotations

import json
import shutil
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-related-tag-tabs.php"
SCRIPT = ROOT / "wordpress/mu-plugins/hs-related-tag-tabs/related-tag-tabs.js"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
NODE_BINARY = shutil.which("node")

STUBS = r"""
define('ABSPATH', '/');
$GLOBALS['hooks'] = [];
$GLOBALS['scripts'] = [];
$GLOBALS['inline'] = [];

class WP_Term {
    public $term_id;
    public $name;
    public $count;
    public function __construct($id, $name, $count) {
        $this->term_id = $id;
        $this->name = $name;
        $this->count = $count;
    }
}

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['hooks'][$hook][] = $callback;
}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    $GLOBALS['hooks'][$hook][] = $callback;
}
function is_singular($post_type = '') { return $GLOBALS['singular'] && $post_type === 'post'; }
function get_queried_object_id() { return 77; }
function get_the_terms($post_id, $taxonomy) {
    return $post_id === 77 && $taxonomy === 'post_tag' ? $GLOBALS['terms'] : false;
}
function plugin_dir_url($file) { return '/wp-content/mu-plugins/'; }
function wp_enqueue_script($handle, $src, $deps = [], $ver = false, $args = []) {
    $GLOBALS['scripts'][$handle] = [$src, $deps, $ver, $args];
}
function wp_add_inline_script($handle, $data, $position = 'after') {
    $GLOBALS['inline'][$handle][] = [$position, $data];
}
function wp_json_encode($data, $flags = 0) { return json_encode($data, $flags); }
function wp_specialchars_decode($text, $quote_style = ENT_NOQUOTES) {
    return htmlspecialchars_decode($text, $quote_style);
}
function wp_doing_ajax() { return $GLOBALS['ajax']; }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function sanitize_key($key) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key)); }
function absint($value) { return abs((int) $value); }
"""


def run_php(body: str, *, singular: bool = True, ajax: bool = False, terms: str = "false", post: dict | None = None) -> dict:
    script = (
        STUBS
        + f"$GLOBALS['singular'] = {json.dumps(singular)};\n"
        + f"$GLOBALS['ajax'] = {json.dumps(ajax)};\n"
        + f"$GLOBALS['terms'] = {terms};\n"
        + f"$_POST = json_decode({json.dumps(json.dumps(post or {}))}, true);\n"
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


TERMS = (
    "[new WP_Term(45, 'Темная Империя', 45), new WP_Term(4310, 'Truebench', 3),"
    " new WP_Term(9, 'Один пост', 1), new WP_Term(12, 'Q&amp;A', 8)]"
)

AJAX_POST = {"action": "td_ajax_block", "block_type": "td_block_related_posts", "td_filter_value": "hs_related_tag_4310"}
RELATED_ATTS = "['td_ajax_filter_type' => 'td_custom_related', 'live_filter_cur_post_id' => '77']"
CATEGORY_ARGS = "['category__in' => [3, 4], 'post__not_in' => [77], 'posts_per_page' => 6]"


def filter_args(args: str = CATEGORY_ARGS, atts: str = RELATED_ATTS) -> str:
    return (
        "$callback = $GLOBALS['hooks']['td_data_source_blocks_query_args'][0];\n"
        f"echo json_encode(call_user_func($callback, {args}, {atts}));\n"
    )


class RelatedTagTabsTest(unittest.TestCase):
    def test_tabs_use_most_specific_shared_tags_and_skip_single_post_tags(self) -> None:
        tabs = run_php("echo json_encode(HS_Related_Tag_Tabs::tabs_for_post(77));", terms=TERMS)

        self.assertEqual(
            tabs,
            [
                {"value": "hs_related_tag_4310", "label": "Truebench"},
                {"value": "hs_related_tag_12", "label": "Q&A"},
            ],
        )

    def test_script_is_enqueued_with_tab_data_on_tagged_single_post(self) -> None:
        state = run_php(
            "foreach ($GLOBALS['hooks']['wp_enqueue_scripts'] as $cb) { call_user_func($cb); }\n"
            "echo json_encode(['scripts' => $GLOBALS['scripts'], 'inline' => $GLOBALS['inline']]);",
            terms=TERMS,
        )

        script = state["scripts"]["hs-related-tag-tabs"]
        self.assertEqual(script[0], "/wp-content/mu-plugins/hs-related-tag-tabs/related-tag-tabs.js")
        self.assertEqual(script[3], {"in_footer": True})
        position, data = state["inline"]["hs-related-tag-tabs"][0]
        self.assertEqual(position, "before")
        self.assertIn('"value":"hs_related_tag_4310"', data)
        self.assertTrue(data.startswith("window.hsRelatedTagTabs="))

    def test_inline_data_cannot_close_the_script_element(self) -> None:
        state = run_php(
            "foreach ($GLOBALS['hooks']['wp_enqueue_scripts'] as $cb) { call_user_func($cb); }\n"
            "echo json_encode($GLOBALS['inline']);",
            terms="[new WP_Term(5, '</script><script>alert(1)</script>', 4)]",
        )

        data = state["hs-related-tag-tabs"][0][1]
        self.assertNotIn("</script>", data)
        self.assertNotIn("<", data)

    def test_script_is_skipped_without_shared_tags_or_outside_posts(self) -> None:
        enqueue = (
            "foreach ($GLOBALS['hooks']['wp_enqueue_scripts'] as $cb) { call_user_func($cb); }\n"
            "echo json_encode($GLOBALS['scripts']);"
        )

        self.assertEqual(run_php(enqueue, terms="[new WP_Term(9, 'Один пост', 1)]"), [])
        self.assertEqual(run_php(enqueue, terms="false"), [])
        self.assertEqual(run_php(enqueue, singular=False, terms=TERMS), [])

    def test_tag_tab_request_replaces_category_filter_with_tag(self) -> None:
        args = run_php(filter_args(), ajax=True, post=AJAX_POST)

        self.assertEqual(args, {"post__not_in": [77], "posts_per_page": 6, "tag__in": [4310]})

    def test_default_and_author_tabs_keep_tagdiv_query(self) -> None:
        for value in ("", "td_related_more_from_author", "hs_related_tag_0", "hs_related_tag_12x", "hs_related_tag_"):
            with self.subTest(value=value):
                args = run_php(filter_args(), ajax=True, post={**AJAX_POST, "td_filter_value": value})
                self.assertEqual(args, {"category__in": [3, 4], "post__not_in": [77], "posts_per_page": 6})

    def test_other_blocks_and_page_renders_are_untouched(self) -> None:
        untouched = {"category__in": [3, 4], "post__not_in": [77], "posts_per_page": 6}

        self.assertEqual(run_php(filter_args(), ajax=False, post=AJAX_POST), untouched)
        self.assertEqual(run_php(filter_args(), ajax=True, post={**AJAX_POST, "block_type": "td_block_1"}), untouched)
        self.assertEqual(run_php(filter_args(), ajax=True, post={**AJAX_POST, "action": "td_ajax_loop"}), untouched)
        self.assertEqual(
            run_php(filter_args(atts="['td_ajax_filter_type' => 'td_category_ids_filter']"), ajax=True, post=AJAX_POST),
            untouched,
        )

    @unittest.skipUnless(NODE_BINARY, "node is required")
    def test_script_is_valid_javascript(self) -> None:
        subprocess.run([NODE_BINARY, "--check", str(SCRIPT)], check=True)


if __name__ == "__main__":
    unittest.main()
