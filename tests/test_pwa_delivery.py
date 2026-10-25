from __future__ import annotations

import os
import shutil
import struct
import subprocess
import tempfile
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
DEPLOY = ROOT / "ops/ci/hs-manacost-ci-deploy"
SMOKE = ROOT / "ops/smoke-check.sh"


def shell_function(source: str, name: str) -> str:
    start = source.index(f"{name}() {{")
    return source[start:source.index("\n}\n", start) + 3]


class PwaDeliveryTest(unittest.TestCase):
    def test_deploy_copies_nested_icons_without_touching_wordpress(self) -> None:
        source = DEPLOY.read_text()
        # Execute the actual public-delivery block against disposable paths.
        block = source[source.index('for public_file in '):source.index('\nrun_wp()')]
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            workspace, target = root / "workspace", root / "site"
            shutil.copytree(ROOT / "public", workspace / "public")
            target.mkdir()
            (target / "wp-config.php").write_text("preserve-config")
            (target / "wp-content/uploads").mkdir(parents=True)
            (target / "wp-content/uploads/original.jpg").write_text("preserve-media")
            (target / "pwa-icons").mkdir()
            (target / "pwa-icons/stale.png").write_text("old-owned-icon")
            script = '''
install() { cp "${@: -2:1}" "${@: -1}"; }
rsync() {
  local filtered=() argument
  for argument in "$@"; do
    [[ "$argument" == '--chown=koloda:koloda' ]] || filtered+=("$argument")
  done
  command rsync "${filtered[@]}"
}
''' + shell_function(source, "sync_owned_tree") + '\n' + block
            subprocess.run(
                ["bash", "-eu", "-c", script], check=True, capture_output=True,
                env={**os.environ, "workspace_real": str(workspace), "target_root": str(target)},
            )
            for size in (192, 512):
                relative = f"pwa-icons/icon-{size}.png"
                self.assertTrue((target / relative).is_file(), relative)
                self.assertEqual((workspace / "public" / relative).read_bytes(), (target / relative).read_bytes())
            self.assertEqual("preserve-config", (target / "wp-config.php").read_text())
            self.assertEqual("preserve-media", (target / "wp-content/uploads/original.jpg").read_text())
            self.assertFalse((target / "pwa-icons/stale.png").exists())

    def test_missing_icon_rejects_payload_before_backup_or_writes(self) -> None:
        source = DEPLOY.read_text()
        start = source.index('for required_path in ')
        block = source[start:source.index('\nif [[ "$environment" == staging', start)]
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            workspace, target = root / "workspace", root / "site"
            for relative in ("wordpress/mu-plugins", "wordpress/plugins", "wordpress/themes/Newspaper_new"):
                (workspace / relative).mkdir(parents=True)
            shutil.copytree(ROOT / "public", workspace / "public")
            (target / "wp-content").mkdir(parents=True)
            (target / "wp-config.php").write_text("preserve-config")
            env = {**os.environ, "workspace_real": str(workspace), "target_root": str(target)}
            subprocess.run(["bash", "-eu", "-c", block], env=env, check=True, capture_output=True)
            for size in (192, 512):
                path = workspace / f"public/pwa-icons/icon-{size}.png"
                content = path.read_bytes()
                path.unlink()
                result = subprocess.run(["bash", "-eu", "-c", block], env=env, capture_output=True, text=True)
                self.assertNotEqual(0, result.returncode, f"missing icon-{size} accepted")
                self.assertIn(f"icon-{size}.png", result.stderr)
                path.write_bytes(content)

    def test_smoke_rejects_html_or_wrong_sized_icon(self) -> None:
        source = SMOKE.read_text()
        try:
            check = shell_function(source, "check_pwa_icons")
        except ValueError:
            self.fail("smoke check has no PWA icon verification")
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            for scenario in ("valid", "html", "wrong-size", "wrong-mime"):
                with self.subTest(scenario=scenario):
                    for size in (192, 512):
                        data = (ROOT / f"public/pwa-icons/icon-{size}.png").read_bytes()
                        if scenario == "html":
                            data = b"<html>soft 404</html>"
                        elif scenario == "wrong-size":
                            data = data[:16] + struct.pack(">I", size + 1) + data[20:]
                        (root / f"icon-{size}.png").write_bytes(data)
                    script = '''
request() { cp "$temporary_directory/$(basename "$3")" "$temporary_directory/body"; }
header_value() { printf '%s' "$mock_mime"; }
''' + check + '\ncheck_pwa_icons hs-manacost.ru 127.0.0.1 false\n'
                    result = subprocess.run(
                        ["bash", "-eu", "-c", script], capture_output=True, text=True,
                        env={**os.environ, "temporary_directory": str(root),
                             "mock_mime": "text/html" if scenario == "wrong-mime" else "image/png"},
                    )
                    self.assertEqual(scenario == "valid", result.returncode == 0, result.stderr)


if __name__ == "__main__":
    unittest.main()
