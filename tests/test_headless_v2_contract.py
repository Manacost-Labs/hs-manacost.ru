import json
import pathlib
import unittest


ROOT = pathlib.Path(__file__).resolve().parents[1]
APP = ROOT / "services" / "web-v2"


class HeadlessV2ContractTests(unittest.TestCase):
    def test_v2_is_noindex_at_app_and_nginx_layers(self):
        config = (APP / "next.config.ts").read_text()
        nginx = (ROOT / "ops" / "nginx" / "v2.conf").read_text()
        robots = (APP / "src" / "app" / "robots.ts").read_text()
        self.assertIn("X-Robots-Tag", config)
        self.assertIn("noindex, nofollow, noarchive", config)
        self.assertIn("noindex, nofollow, noarchive", nginx)
        self.assertIn('disallow: "/"', robots)

    def test_public_adapter_excludes_vip_and_has_no_mutation(self):
        source = (APP / "src" / "lib" / "wp.ts").read_text()
        self.assertIn("const VIP_CATEGORY_ID = 2006", source)
        self.assertIn("categories_exclude", source)
        for mutation in ("method: \"POST\"", "method: \"PUT\"", "method: \"PATCH\"", "method: \"DELETE\""):
            self.assertNotIn(mutation, source)

    def test_service_is_bound_to_loopback_and_isolated(self):
        unit = (ROOT / "ops" / "systemd" / "hs-manacost-v2.service").read_text()
        self.assertIn("Environment=HOSTNAME=127.0.0.1", unit)
        self.assertIn("User=hs-manacost-v2", unit)
        self.assertIn("ProtectSystem=strict", unit)
        self.assertIn("ReadOnlyPaths=/srv/hs-manacost-v2", unit)
        self.assertIn("ReadWritePaths=/srv/hs-manacost-v2/current/.next/cache", unit)

    def test_media_proxy_is_allowlisted_and_bounded(self):
        boundary = (APP / "src" / "lib" / "media.ts").read_text()
        self.assertIn('source.hostname !== ALLOWED_HOST', boundary)
        self.assertIn('source.pathname.startsWith(ALLOWED_PATH)', boundary)
        self.assertIn('redirect: "error"', boundary)
        self.assertIn('cache: "no-store"', boundary)
        self.assertIn("MAX_CONCURRENT_MEDIA_REQUESTS", boundary)

    def test_homepage_uses_only_the_dedicated_fetch_cache(self):
        homepage = (APP / "src" / "app" / "page.tsx").read_text()
        self.assertIn('export const dynamic = "force-dynamic"', homepage)

    def test_release_requires_remote_main_and_separates_mutable_cache(self):
        release = (ROOT / "ops" / "web-v2" / "release.sh").read_text()
        self.assertIn("fetch --quiet --no-tags origin main", release)
        self.assertIn("refs/remotes/origin/main", release)
        self.assertIn(
            'install -d -o hs-manacost-v2 -g hs-manacost-v2 -m 0700 "$build/.home" "$build/.npm-cache"',
            release,
        )
        self.assertIn(
            'runuser -u hs-manacost-v2 -- env \\\n'
            '  HOME="$build/.home" \\\n'
            '  npm_config_cache="$build/.npm-cache" \\\n'
            '  npm ci --prefix "$build" --ignore-scripts',
            release,
        )
        self.assertIn(
            'runuser -u hs-manacost-v2 -- env \\\n'
            '  HOME="$build/.home" \\\n'
            '  npm_config_cache="$build/.npm-cache" \\\n'
            '  NEXT_TELEMETRY_DISABLED=1 \\\n'
            "  WORDPRESS_API_URL='https://hs-manacost.ru/wp-json/wp/v2' \\\n"
            '  npm run build --prefix "$build"',
            release,
        )
        self.assertIn("WORDPRESS_API_URL='https://hs-manacost.ru/wp-json/wp/v2'", release)
        self.assertIn('test -w "$release/.next/cache"', release)
        self.assertIn('test -w "$release/server.js"', release)

    def test_design_contract_is_redacted_and_targets_v2(self):
        contract = json.loads((ROOT / "config" / "headless-v2-design-contract.json").read_text())
        self.assertEqual(contract["project"], "hs-manacost.ru headless v2 sandbox")
        self.assertIn("noindex", contract["quality"]["seo"])


if __name__ == "__main__":
    unittest.main()
