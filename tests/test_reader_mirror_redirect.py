"""Protect Reader surfaces from the mirror's separate cookie boundary."""
from __future__ import annotations

import json
from pathlib import Path
import shutil
import subprocess
import unittest


ROOT = Path(__file__).resolve().parents[1]
BOOTSTRAP = ROOT / "wordpress/mu-plugins/hs-manacost-reader/bootstrap.js"
NODE = shutil.which("node") or "/usr/bin/node"


class ReaderMirrorRedirectTest(unittest.TestCase):
    def test_mirror_reader_surface_moves_to_canonical_before_any_api_request(self) -> None:
        script = f"""
const fs = require('node:fs');
let destination = null;
global.window = {{
  location: {{
    hostname: 'hs-manacost.com', pathname: '/article/', search: '?source=mirror', hash: '#reader-comments',
    replace: value => {{ destination = value; }},
  }},
}};
global.fetch = () => {{ throw new Error('Reader API must not run on the mirror'); }};
new Function(fs.readFileSync({json.dumps(str(BOOTSTRAP))}, 'utf8'))();
process.stdout.write(JSON.stringify(destination));
"""
        result = subprocess.run([NODE, "-e", script], capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(
            json.loads(result.stdout),
            "https://hs-manacost.ru/article/?source=mirror#reader-comments",
        )

    def test_canonical_reader_surface_remains_in_place(self) -> None:
        script = f"""
const fs = require('node:fs');
let destination = null;
global.window = {{ location: {{ hostname: 'hs-manacost.ru', replace: value => {{ destination = value; }} }} }};
new Function(fs.readFileSync({json.dumps(str(BOOTSTRAP))}, 'utf8'))();
process.stdout.write(JSON.stringify(destination));
"""
        result = subprocess.run([NODE, "-e", script], capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIsNone(json.loads(result.stdout))


if __name__ == "__main__":
    unittest.main()
