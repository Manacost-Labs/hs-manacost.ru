from __future__ import annotations

import json
import shutil
import struct
import subprocess
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / "wordpress/mu-plugins/hs-manacost-pwa.php"
MANIFEST = ROOT / "public/manifest.webmanifest"
SERVICE_WORKER = ROOT / "public/service-worker.js"
OFFLINE = ROOT / "public/offline.html"
STATIC_CACHE = ROOT / "ops/nginx/resources/static-cache.conf"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"


class PwaTest(unittest.TestCase):
    def render_plugin(self, host: str) -> dict[str, object]:
        script = f"""
        define('ABSPATH', '/');
        $_SERVER['HTTP_HOST'] = {json.dumps(host)};
        $actions = [];
        function add_action($hook, $callback, $priority = 10) {{
            global $actions;
            $actions[$hook][] = $callback;
        }}
        function wp_unslash($value) {{ return $value; }}
        function sanitize_text_field($value) {{ return trim(strip_tags($value)); }}
        function esc_url($value) {{ return $value; }}
        require {json.dumps(str(PLUGIN))};
        $rendered = [];
        foreach ($actions as $hook => $callbacks) {{
            ob_start();
            foreach ($callbacks as $callback) {{ call_user_func($callback); }}
            $rendered[$hook] = ob_get_clean();
        }}
        echo json_encode(['actions' => array_keys($actions), 'rendered' => $rendered]);
        """
        process = subprocess.run(
            [PHP_BINARY, "-r", script],
            cwd=ROOT,
            check=True,
            capture_output=True,
            text=True,
        )
        return json.loads(process.stdout)

    def test_canonical_host_gets_manifest_and_service_worker(self) -> None:
        result = self.render_plugin("hs-manacost.ru")
        head = result["rendered"]["wp_head"]
        footer = result["rendered"]["wp_footer"]

        self.assertIn('rel="manifest"', head)
        self.assertIn('/manifest.webmanifest', head)
        self.assertIn('name="theme-color"', head)
        self.assertIn("/service-worker.js", footer)

    def test_staging_is_installable_but_mirror_is_not(self) -> None:
        self.assertIn("wp_head", self.render_plugin("test.hs-manacost.ru")["actions"])
        self.assertEqual([], self.render_plugin("hs-manacost.com")["actions"])
        self.assertEqual([], self.render_plugin("www.hs-manacost.com:443")["actions"])

    def test_manifest_contract(self) -> None:
        manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))

        self.assertEqual("/", manifest["id"])
        self.assertEqual("/", manifest["start_url"])
        self.assertEqual("/", manifest["scope"])
        self.assertEqual("standalone", manifest["display"])
        self.assertEqual("ru", manifest["lang"])
        self.assertEqual({"192x192", "512x512"}, {icon["sizes"] for icon in manifest["icons"]})
        self.assertTrue(any("maskable" in icon["purpose"] for icon in manifest["icons"]))

    def test_service_worker_is_safe_and_valid_javascript(self) -> None:
        source = SERVICE_WORKER.read_text(encoding="utf-8")

        self.assertIn("request.mode === 'navigate'", source)
        self.assertIn("/offline.html", source)
        self.assertNotIn("cache.put(request", source)
        node = shutil.which("node")
        if node:
            subprocess.run([node, "--check", SERVICE_WORKER], cwd=ROOT, check=True, capture_output=True)

    def test_icons_are_real_rgba_pngs(self) -> None:
        for size in (192, 512):
            path = ROOT / f"public/pwa-icons/icon-{size}.png"
            data = path.read_bytes()
            width, height, bit_depth, color_type = struct.unpack(">IIBB", data[16:26])
            self.assertEqual((size, size), (width, height))
            self.assertEqual(8, bit_depth)
            self.assertIn(color_type, (4, 6), "PWA icons must retain transparency")

    def test_service_worker_cannot_be_immutably_cached(self) -> None:
        config = STATIC_CACHE.read_text(encoding="utf-8")

        self.assertIn("location = /service-worker.js", config)
        self.assertIn('Cache-Control "no-cache"', config)

    def test_offline_page_is_self_contained(self) -> None:
        source = OFFLINE.read_text(encoding="utf-8")

        self.assertIn('lang="ru"', source)
        self.assertNotIn("<script", source)
        self.assertNotIn("http://", source)
        self.assertNotIn("https://", source)


if __name__ == "__main__":
    unittest.main()
