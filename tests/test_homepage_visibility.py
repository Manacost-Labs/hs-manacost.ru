from __future__ import annotations

import json
import shutil
import subprocess
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-homepage-visibility.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class HomepageVisibilityTest(unittest.TestCase):
    def run_plugin(
        self,
        *,
        front_page: bool = True,
        can_edit: bool = True,
        nonce_valid: bool = True,
        stored_meta: str = "",
        post_data: dict[str, object] | None = None,
        autosave: bool = False,
        revision: bool = False,
        tagdiv_ajax: bool = False,
		block_editor: bool = False,
    ) -> dict:
        post_data = post_data or {}
        script = f"""
        define('ABSPATH', '/fixture/');
        $GLOBALS['actions'] = [];
        $GLOBALS['filters'] = [];
        $GLOBALS['meta_boxes'] = [];
		$GLOBALS['nonce_fields'] = [];
        $GLOBALS['meta'] = [77 => ['_hs_show_on_homepage' => {json.dumps(stored_meta)}]];
        $GLOBALS['updates'] = [];
        $GLOBALS['deletes'] = [];
        $GLOBALS['front_page'] = {json.dumps(front_page)};
        $GLOBALS['can_edit'] = {json.dumps(can_edit)};
        $GLOBALS['nonce_valid'] = {json.dumps(nonce_valid)};
        $GLOBALS['autosave'] = {json.dumps(autosave)};
        $GLOBALS['revision'] = {json.dumps(revision)};
		$GLOBALS['tagdiv_ajax'] = {json.dumps(tagdiv_ajax)};
		$GLOBALS['block_editor'] = {json.dumps(block_editor)};
        $_POST = json_decode({json.dumps(json.dumps(post_data))}, true);

        class WP_Post {{
            public int $ID;
            public string $post_type;
            public function __construct(int $id, string $post_type = 'post') {{
                $this->ID = $id;
                $this->post_type = $post_type;
            }}
        }}

        class Screen {{
            public function is_block_editor() {{ return $GLOBALS['block_editor']; }}
        }}

        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['filters'][$hook][] = [$callback, $priority, $accepted_args];
        }}
        function add_meta_box($id, $title, $callback, $screen, $context, $priority) {{
            $GLOBALS['meta_boxes'][$id] = [$title, $callback, $screen, $context, $priority];
        }}
		function get_current_screen() {{ return new Screen(); }}
		function wp_nonce_field($action, $name) {{
			$GLOBALS['nonce_fields'][] = [$action, $name];
            echo '<input type="hidden" name="' . $name . '" value="nonce">';
        }}
        function wp_verify_nonce($nonce, $action) {{ return $GLOBALS['nonce_valid']; }}
        function wp_unslash($value) {{ return $value; }}
        function sanitize_text_field($value) {{ return (string) $value; }}
        function current_user_can($capability, $post_id = 0) {{ return $GLOBALS['can_edit']; }}
        function wp_is_post_autosave($post_id) {{ return $GLOBALS['autosave']; }}
        function wp_is_post_revision($post_id) {{ return $GLOBALS['revision']; }}
        function get_post_meta($post_id, $key, $single = true) {{ return $GLOBALS['meta'][$post_id][$key] ?? ''; }}
        function update_post_meta($post_id, $key, $value) {{
            $GLOBALS['meta'][$post_id][$key] = $value;
            $GLOBALS['updates'][] = [$post_id, $key, $value];
        }}
        function delete_post_meta($post_id, $key) {{
            unset($GLOBALS['meta'][$post_id][$key]);
            $GLOBALS['deletes'][] = [$post_id, $key];
        }}
        function checked($checked, $current = true, $display = true) {{
            $result = $checked === $current ? 'checked="checked"' : '';
            if ($display) {{ echo $result; }}
            return $result;
        }}
        function esc_html($value) {{ return (string) $value; }}
        function esc_attr($value) {{ return (string) $value; }}
        function __($value, $domain = null) {{ return $value; }}
        function is_admin() {{ return false; }}
        function is_front_page() {{ return $GLOBALS['front_page']; }}

		if ($GLOBALS['tagdiv_ajax']) {{
			class tdc_state {{
				public static function is_td_block_ajax() {{ return true; }}
			}}
		}}

        require {json.dumps(str(PLUGIN))};

        foreach ($actions['add_meta_boxes_post'] ?? [] as $entry) {{
            call_user_func($entry[0]);
        }}
		$post = new WP_Post(77);
		ob_start();
		foreach ($actions['post_submitbox_misc_actions'] ?? [] as $entry) {{
			call_user_func($entry[0], $post);
		}}
		$publish_html = ob_get_clean();
		ob_start();
		foreach ($actions['wp_footer'] ?? [] as $entry) {{
			call_user_func($entry[0]);
		}}
		$footer_html = ob_get_clean();

        HS_Homepage_Visibility::save_meta_box(77, $post);
        $post_query = HS_Homepage_Visibility::filter_block_query([
            'post_type' => 'post',
            'meta_query' => [['key' => 'featured', 'value' => '1']],
        ], []);
        $page_query = HS_Homepage_Visibility::filter_block_query(['post_type' => 'page'], []);
		$ajax_query = HS_Homepage_Visibility::filter_block_query(
			['post_type' => 'post'],
			['hs_homepage_visibility' => '1']
		);
		$ajax_unmarked_query = HS_Homepage_Visibility::filter_block_query(
			['post_type' => 'post'],
			[]
		);
		$ajax_array_marker_query = HS_Homepage_Visibility::filter_block_query(
			['post_type' => 'post'],
			['hs_homepage_visibility' => ['1']]
		);

        echo json_encode([
            'actions' => $GLOBALS['actions'],
            'filters' => $GLOBALS['filters'],
            'meta_boxes' => $GLOBALS['meta_boxes'],
			'nonce_fields' => $GLOBALS['nonce_fields'],
			'footer_html' => $footer_html,
			'publish_html' => $publish_html,
            'meta' => $GLOBALS['meta'],
            'updates' => $GLOBALS['updates'],
            'deletes' => $GLOBALS['deletes'],
            'post_query' => $post_query,
            'page_query' => $page_query,
			'ajax_query' => $ajax_query,
			'ajax_unmarked_query' => $ajax_unmarked_query,
			'ajax_array_marker_query' => $ajax_array_marker_query,
        ]);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-r", textwrap.dedent(script)],
            check=False,
            capture_output=True,
            text=True,
        )
        self.assertEqual(completed.returncode, 0, completed.stderr)
        return json.loads(completed.stdout)

    def test_registers_a_publish_box_checkbox_and_a_scoped_tagdiv_filter(self) -> None:
        result = self.run_plugin()

        self.assertIn("post_submitbox_misc_actions", result["actions"])
        self.assertIn("save_post_post", result["actions"])
        self.assertIn("wp_footer", result["actions"])
        self.assertIn("td_data_source_blocks_query_args", result["filters"])
        self.assertNotIn("hs-homepage-visibility", result["meta_boxes"])
        self.assertIn('class="misc-pub-section hs-homepage-visibility"', result["publish_html"])
        self.assertIn('name="hs_show_on_homepage"', result["publish_html"])
        self.assertIn('checked="checked"', result["publish_html"])
        self.assertEqual(
            [["hs_homepage_visibility_save_77", "hs_homepage_visibility_nonce"]],
            result["nonce_fields"],
        )
        self.assertIn('id="hs-homepage-visibility-ajax"', result["footer_html"])
        self.assertIn("tdBlocksArray", result["footer_html"])
        self.assertIn("hs_homepage_visibility", result["footer_html"])

    def test_hidden_article_renders_with_the_control_unchecked(self) -> None:
        result = self.run_plugin(stored_meta="0")

        self.assertNotIn('checked="checked"', result["publish_html"])

    def test_block_editor_keeps_the_standard_meta_box_fallback(self) -> None:
        result = self.run_plugin(block_editor=True)

        self.assertIn("hs-homepage-visibility", result["meta_boxes"])
        self.assertEqual("", result["publish_html"])

    def test_front_page_post_queries_keep_existing_constraints_and_exclude_hidden_posts(self) -> None:
        result = self.run_plugin()
        meta_query = result["post_query"]["meta_query"]

        self.assertEqual("AND", meta_query["relation"])
        self.assertIn(("key", "featured"), [list(item.items())[0] for item in meta_query["1"]])
        visibility_clause = meta_query["0"]
        self.assertEqual(
            {"key": "_hs_show_on_homepage", "compare": "NOT EXISTS"},
            visibility_clause,
        )

    def test_archive_or_non_post_query_is_not_changed(self) -> None:
        archive_result = self.run_plugin(front_page=False)
        page_result = self.run_plugin()

        self.assertEqual(
            {"post_type": "post", "meta_query": [{"key": "featured", "value": "1"}]},
            archive_result["post_query"],
        )
        self.assertEqual({"post_type": "page"}, page_result["page_query"])
        self.assertEqual("", archive_result["footer_html"])
        self.assertEqual({"post_type": "post"}, archive_result["ajax_query"])

    def test_tagdiv_ajax_query_from_the_front_page_respects_visibility(self) -> None:
        result = self.run_plugin(front_page=False, tagdiv_ajax=True)

        self.assertEqual(
            {
                "post_type": "post",
                "meta_query": {
                    "relation": "AND",
                    "0": {"key": "_hs_show_on_homepage", "compare": "NOT EXISTS"},
                },
            },
            result["ajax_query"],
        )

        self.assertEqual({"post_type": "post"}, result["ajax_unmarked_query"])
        self.assertEqual({"post_type": "post"}, result["ajax_array_marker_query"])

    def test_unchecked_control_writes_a_hidden_marker_and_checked_control_restores_default_visibility(self) -> None:
        hidden = self.run_plugin(
            post_data={
                "hs_homepage_visibility_nonce": "valid",
            }
        )
        visible = self.run_plugin(
            stored_meta="0",
            post_data={
                "hs_homepage_visibility_nonce": "valid",
                "hs_show_on_homepage": "1",
            },
        )

        self.assertEqual([[77, "_hs_show_on_homepage", "0"]], hidden["updates"])
        self.assertEqual([], hidden["deletes"])
        self.assertEqual([], visible["updates"])
        self.assertEqual([[77, "_hs_show_on_homepage"]], visible["deletes"])

        unchanged_legacy_post = self.run_plugin(
            post_data={
                "hs_homepage_visibility_nonce": "valid",
                "hs_show_on_homepage": "1",
            }
        )
        self.assertEqual([], unchanged_legacy_post["updates"])
        self.assertEqual([], unchanged_legacy_post["deletes"])

    def test_missing_permission_nonce_or_editor_save_context_cannot_change_visibility(self) -> None:
        denied = self.run_plugin(
            can_edit=False,
            post_data={"hs_homepage_visibility_nonce": "valid"},
        )
        invalid_nonce = self.run_plugin(
            nonce_valid=False,
            post_data={"hs_homepage_visibility_nonce": "invalid"},
        )
        autosave = self.run_plugin(
            autosave=True,
            post_data={"hs_homepage_visibility_nonce": "valid"},
        )
        revision = self.run_plugin(
            revision=True,
            post_data={"hs_homepage_visibility_nonce": "valid"},
        )
        malformed = self.run_plugin(
            post_data={
                "hs_homepage_visibility_nonce": "valid",
                "hs_show_on_homepage": "invalid",
            }
        )
        array_input = self.run_plugin(
            post_data={
                "hs_homepage_visibility_nonce": "valid",
                "hs_show_on_homepage": ["0"],
            }
        )

        for result in (denied, invalid_nonce, autosave, revision, malformed, array_input):
            self.assertEqual([], result["updates"])
            self.assertEqual([], result["deletes"])


if __name__ == "__main__":
    unittest.main()
