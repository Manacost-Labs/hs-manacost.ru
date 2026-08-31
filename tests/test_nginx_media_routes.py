import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MEDIA_ROUTES = ROOT / "ops/nginx/resources/20-origin-guard.conf"
PROXY_UPLOAD = ROOT / "ops/nginx/proxy-upload-streaming.conf"


class NginxMediaRouteTests(unittest.TestCase):
    def test_admin_media_routes_use_the_current_php84_pool(self) -> None:
        config = MEDIA_ROUTES.read_text(encoding="utf-8")

        for endpoint in (
            "location = /wp-admin/admin-ajax.php",
            "location = /wp-admin/async-upload.php",
        ):
            start = config.index(endpoint)
            end = config.find("\n}", start)
            self.assertNotEqual(-1, end, endpoint)
            route = config[start : end + 2]
            self.assertIn(
                "fastcgi_pass unix:/var/www/php-fpm/hs-manacost-php84.sock;",
                route,
                endpoint,
            )
            self.assertNotIn("/var/www/php-fpm/6.sock", route, endpoint)

    def test_regional_proxies_stream_upload_bodies_to_the_origin(self) -> None:
        proxy_upload = PROXY_UPLOAD.read_text(encoding="utf-8")

        self.assertEqual(
            1,
            proxy_upload.count("proxy_request_buffering off;"),
        )

if __name__ == "__main__":
    unittest.main()
