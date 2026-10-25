from __future__ import annotations

import json
import shutil
import subprocess
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-edge-cache-guard.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
# Values match the production WP Rocket configuration on 2026-10-20.
REJECT_COOKIES = (
    "td_view|wordpress_logged_in_.+|wp-postpass_|wptouch_switch_toggle"
    "|comment_author_|comment_author_email_"
)
REJECT_UA = "facebookexternalhit|WhatsApp"
BROWSER_UA = "Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36 Chrome/128.0 Mobile Safari/537.36"


class EdgeCacheGuardTest(unittest.TestCase):
    def decide(
        self,
        *,
        method: str = "GET",
        cookies: dict[str, str] | None = None,
        user_agent: str = BROWSER_UA,
        sent: list[str] | None = None,
        rocket: bool = True,
        reject_cookies: str = REJECT_COOKIES,
        reject_ua: str = REJECT_UA,
        admin: bool = False,
    ) -> dict:
        rocket_functions = (
            f"""
            function get_rocket_cache_reject_cookies() {{ return {json.dumps(reject_cookies)}; }}
            function get_rocket_cache_reject_ua() {{ return {json.dumps(reject_ua)}; }}
            """
            if rocket
            else ""
        )
        script = f"""
        define('ABSPATH', '/fixture/');
        $GLOBALS['actions'] = [];
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {{
            $GLOBALS['actions'][$hook][] = [$callback, $priority];
        }}
        function is_admin() {{ return {json.dumps(admin)}; }}
        {rocket_functions}
        require {json.dumps(str(PLUGIN))};
        $server = ['REQUEST_METHOD' => {json.dumps(method)}, 'HTTP_USER_AGENT' => {json.dumps(user_agent)}];
        $cookies = json_decode({json.dumps(json.dumps(cookies or {}))}, true);
        $sent = json_decode({json.dumps(json.dumps(sent or []))}, true);
        echo json_encode([
            'header' => HS_Edge_Cache_Guard::header_for_request($server, $cookies, $sent),
            'hooks' => array_map(
                static fn($entries) => array_map(static fn($entry) => [is_array($entry[0]) ? implode('::', $entry[0]) : 'closure', $entry[1]], $entries),
                $GLOBALS['actions']
            ),
        ]);
        """
        completed = subprocess.run(
            [PHP_BINARY, "-d", "display_errors=stderr", "-d", "error_reporting=E_ALL", "-r", textwrap.dedent(script)],
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertEqual("", completed.stderr)
        return json.loads(completed.stdout)

    def test_registers_on_send_headers(self) -> None:
        hooks = self.decide()["hooks"]
        self.assertEqual([["HS_Edge_Cache_Guard::send_header", 10]], hooks["send_headers"])

    def test_ordinary_guest_html_stays_shared(self) -> None:
        self.assertIsNone(self.decide(cookies={"_ga": "1", "hs_reader_theme": "dark"})["header"])

    def test_comment_author_render_is_private(self) -> None:
        result = self.decide(cookies={"comment_author_0f1e": "Reader"})
        self.assertEqual("Cache-Control: private", result["header"])

    def test_every_configured_cookie_is_private(self) -> None:
        for name in ("td_view", "wordpress_logged_in_abc", "wp-postpass_abc", "comment_author_email_abc"):
            with self.subTest(name=name):
                self.assertEqual("Cache-Control: private", self.decide(cookies={name: "1"})["header"])

    def test_rejected_link_preview_agents_are_private(self) -> None:
        for agent in ("WhatsApp/2.23.20.0", "facebookexternalhit/1.1"):
            with self.subTest(agent=agent):
                self.assertEqual("Cache-Control: private", self.decide(user_agent=agent)["header"])

    def test_non_cacheable_methods_and_admin_are_ignored(self) -> None:
        self.assertIsNone(self.decide(method="POST", cookies={"comment_author_x": "1"})["header"])
        self.assertIsNone(self.decide(admin=True, cookies={"comment_author_x": "1"})["header"])

    def test_existing_private_policy_is_preserved(self) -> None:
        sent = ["Content-Type: text/html", "Cache-Control: no-cache, must-revalidate, max-age=0, no-store, private"]
        self.assertIsNone(self.decide(cookies={"wordpress_logged_in_x": "1"}, sent=sent)["header"])

    def test_shared_policy_is_replaced_for_rejected_render(self) -> None:
        sent = ["Cache-Control: public, max-age=600"]
        self.assertEqual("Cache-Control: private", self.decide(cookies={"comment_author_x": "1"}, sent=sent)["header"])

    def test_without_wp_rocket_nothing_changes(self) -> None:
        self.assertIsNone(self.decide(rocket=False, cookies={"comment_author_x": "1"}, user_agent="WhatsApp/2")["header"])

    def test_invalid_pattern_fails_open_without_warning(self) -> None:
        result = self.decide(reject_cookies="comment_author_(", reject_ua="WhatsApp(", cookies={"comment_author_x": "1"}, user_agent="WhatsApp/2")
        self.assertIsNone(result["header"])


if __name__ == "__main__":
    unittest.main()
