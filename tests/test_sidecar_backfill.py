from __future__ import annotations

import json
import shutil
import subprocess
import textwrap
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
RUNNER = ROOT / "ops/media/backfill-sidecars.php"
PHP_BINARY = shutil.which("php") or "/usr/bin/php"
GB = 1024**3


class SidecarBackfillTest(unittest.TestCase):
    def php(self, body: str) -> object:
        script = f"""
        define('ABSPATH', '/fixture/');
        define('HS_SIDECAR_BACKFILL_NO_RUN', true);
        require {json.dumps(str(RUNNER))};
        echo json_encode((function () {{ {body} }})());
        """
        completed = subprocess.run(
            [PHP_BINARY, "-d", "display_errors=stderr", "-d", "error_reporting=E_ALL", "-r", textwrap.dedent(script)],
            check=True,
            capture_output=True,
            text=True,
        )
        self.assertEqual("", completed.stderr)
        return json.loads(completed.stdout)

    def options(self, *args: str) -> dict:
        return self.php(f"return HS_Sidecar_Backfill::options({json.dumps(list(args))});")

    def test_defaults_are_conservative(self) -> None:
        self.assertEqual(
            {"years": [2021, 2025], "ids": [], "limit": 20, "max_load": 10, "min_free_gb": 60, "max_minutes": 30, "dry_run": False},
            self.options(),
        )

    def test_options_are_bounded(self) -> None:
        options = self.options("limit=999999", "max_load=0", "min_free_gb=1", "max_minutes=99999", "ids=5,abc,5,-1,7", "dry_run=1")
        self.assertEqual(1000, options["limit"])
        self.assertEqual(1, options["max_load"])
        self.assertEqual(10, options["min_free_gb"])
        self.assertEqual(600, options["max_minutes"])
        self.assertEqual([5, 7], options["ids"])
        self.assertTrue(options["dry_run"])

    def test_years_outside_archive_scope_are_rejected(self) -> None:
        for years in ("2026", "2014-2016", "2025-2021"):
            with self.subTest(years=years):
                result = self.php(
                    "try { HS_Sidecar_Backfill::options(['years=" + years + "']); return 'accepted'; }"
                    " catch (InvalidArgumentException $e) { return 'rejected'; }"
                )
                self.assertEqual("rejected", result)
        self.assertEqual([2023, 2023], self.options("years=2023")["years"])

    def blocker(self, load: float, free_gb: float, lock: bool = False, due: int = 0) -> object:
        return self.php(
            f"return HS_Sidecar_Backfill::blocker({load}, {free_gb * GB}, {json.dumps(lock)}, {due},"
            " HS_Sidecar_Backfill::options(['max_load=8', 'min_free_gb=60']));"
        )

    def test_capacity_rules(self) -> None:
        self.assertIsNone(self.blocker(3.0, 120))
        self.assertEqual("wait_load", self.blocker(9.5, 120))
        self.assertEqual("wait_queue", self.blocker(1.0, 120, lock=True))
        self.assertEqual("wait_queue", self.blocker(1.0, 120, due=1))
        # Disk exhaustion stops the batch even while other work is waiting.
        self.assertEqual("stop_disk", self.blocker(20.0, 59, lock=True, due=3))

    def test_stale_backlog_without_runner_does_not_block(self) -> None:
        self.assertEqual(0, self.php("return HS_Sidecar_Backfill::fresh_due(48, 48);"))
        self.assertEqual(2, self.php("return HS_Sidecar_Backfill::fresh_due(50, 48);"))

    def test_only_restored_upload_sources_are_removed(self) -> None:
        paths = [
            "/up/2021/05/a.jpg",
            "/up/2021/05/b-300x200.png",
            "/up/2021/05/missing.jpg",
            "/up/2021/05/a.jpg.webp",
            "/elsewhere/2021/05/c.jpg",
            "/up/2021/../../etc/d.jpg",
            "/upload-other/e.jpg",
        ]
        existing = [p for p in paths if "missing" not in p]
        result = self.php(
            f"$existing = {json.dumps(existing)};"
            f" return HS_Sidecar_Backfill::hydrated({json.dumps(paths)}, '/up', static fn($p) => in_array($p, $existing, true));"
        )
        self.assertEqual(["/up/2021/05/a.jpg", "/up/2021/05/b-300x200.png"], result)


if __name__ == "__main__":
    unittest.main()
