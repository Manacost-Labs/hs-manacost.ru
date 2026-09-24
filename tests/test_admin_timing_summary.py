"""Behavioral checks for privacy-safe wp-admin timing summaries."""

import json
import subprocess
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
COMMAND = ROOT / "ops/performance/summarize-admin-timing.py"


class AdminTimingSummaryTests(unittest.TestCase):
    def run_summary(self, records: list[dict[str, object]]) -> dict[str, object]:
        with tempfile.TemporaryDirectory() as directory:
            log_path = Path(directory) / "timing.log"
            log_path.write_text(
                "\n".join(json.dumps(record) for record in records) + "\n",
                encoding="utf-8",
            )
            result = subprocess.run(
                [
                    str(COMMAND),
                    str(log_path),
                    "--since", "2026-09-24T19:13:00+00:00",
                    "--until", "2026-09-24T20:00:00+00:00",
                ],
                cwd=ROOT,
                capture_output=True,
                text=True,
                check=False,
            )
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertNotIn("private@example.com", result.stdout)
            self.assertNotIn("192.0.2.123", result.stdout)
            self.assertNotIn("secret-token", result.stdout)
            return json.loads(result.stdout)

    def test_groups_successful_editor_requests_without_private_fields(self) -> None:
        common = {
            "time": "2026-09-24T19:20:00+00:00",
            "remote_addr": "192.0.2.123",
            "http_referer": "https://example.test/?token=secret-token",
            "http_user_agent": "private@example.com",
        }
        records = [
            {**common, "request": "GET /wp-admin/post.php HTTP/2.0", "status": 200, "request_time": 0.42},
            {**common, "request": "GET /wp-admin/post.php HTTP/2.0", "status": 200, "request_time": 0.58},
            {**common, "request": "POST /wp-admin/post.php HTTP/2.0", "status": 302, "request_time": 0.63},
            {**common, "request": "GET /wp-admin/post.php HTTP/2.0", "status": 503, "request_time": 0.03},
            {**common, "request": "GET /wp-admin/post.php?post=42&token=secret-token HTTP/2.0", "status": 200, "request_time": 0.9},
            {**common, "time": "2026-09-24T18:00:00+00:00", "request": "GET /wp-admin/post.php HTTP/2.0", "status": 200, "request_time": 9},
        ]
        summary = self.run_summary(records)
        routes = summary["routes"]
        self.assertEqual(2, routes["article-open"]["successful_samples"])
        self.assertEqual(1, routes["article-open"]["error_responses"])
        self.assertEqual(500, routes["article-open"]["median_ms"])
        self.assertIsNone(routes["article-open"]["p95_ms"])
        self.assertEqual(1, routes["article-save"]["successful_samples"])
        self.assertEqual(630, routes["article-save"]["median_ms"])

    def test_p95_needs_a_meaningful_sample(self) -> None:
        records = [
            {
                "time": "2026-09-24T19:20:00+00:00",
                "request": "GET /wp-admin/plugins.php HTTP/2.0",
                "status": 200,
                "request_time": number / 1000,
            }
            for number in range(1, 21)
        ]
        summary = self.run_summary(records)
        self.assertEqual(19, summary["routes"]["plugins"]["p95_ms"])

    def test_requires_a_bounded_interval(self) -> None:
        result = subprocess.run(
            [str(COMMAND), "/tmp/unused-admin-timing.log"],
            cwd=ROOT,
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertNotEqual(0, result.returncode)
        self.assertIn("--since", result.stderr)
        self.assertIn("--until", result.stderr)


if __name__ == "__main__":
    unittest.main()
