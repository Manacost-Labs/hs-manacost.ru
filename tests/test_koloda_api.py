"""Exercise the real server-side client against a deterministic HTTP boundary."""
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


class KolodaApiTest(unittest.TestCase):
    def test_client_behaviour(self):
        for scenario in (
            "success", "empty", "warm", "query_order", "query_isolation",
            "disabled", "invalid_path", "invalid_query", "pagination",
            "timeout", "dns", "tls", "unauthorized", "forbidden", "not_found",
            "redirect", "rate_limit", "retry_date", "server_error", "malformed",
            "wrong_type", "too_large", "stale", "cooldown", "recovery",
        ):
            with self.subTest(scenario=scenario):
                result = subprocess.run(
                    ["php", str(ROOT / "tests/fixtures/koloda-api.php"), scenario],
                    cwd=ROOT, capture_output=True, text=True, timeout=10,
                )
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
                self.assertEqual(result.stdout.strip(), "PASS")


if __name__ == "__main__":
    unittest.main()
