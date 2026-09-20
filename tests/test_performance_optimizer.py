from __future__ import annotations

import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
OPTIMIZER = ROOT / "wordpress/mu-plugins/manacost-performance-optimizer.php"


class PerformanceOptimizerTest(unittest.TestCase):
    def test_responsive_page_does_not_have_a_user_agent_typography_variant(self) -> None:
        source = OPTIMIZER.read_text(encoding="utf-8")

        self.assertNotIn("remove_mobile_webfonts", source)
        self.assertNotIn("manacost-mobile-font-budget", source)
        self.assertNotIn('font-family:Arial,"Helvetica Neue",sans-serif!important', source)

    def test_mobile_request_detection_remains_limited_to_image_preload_selection(self) -> None:
        source = OPTIMIZER.read_text(encoding="utf-8")

        self.assertIn("$lcp_url = self::is_mobile_request() ? $mobile_url : $desktop_url;", source)
        self.assertNotIn("if ( self::is_mobile_request() ) {\n\t\t\t\t$html = self::remove_mobile_webfonts", source)


if __name__ == "__main__":
    unittest.main()
