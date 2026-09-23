"""The cleanup command must target only the current worktree's test stack."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]


class IntegrationIsolationTests(unittest.TestCase):
    def test_two_worktrees_have_separate_cleanup_targets(self):
        names = []
        with tempfile.TemporaryDirectory() as temporary:
            base = Path(temporary)
            for number in range(2):
                root = base / str(number)
                (root / 'ops/integration').mkdir(parents=True)
                (root / '.artifacts/integration').mkdir(parents=True)
                (root / '.artifacts/integration/runtime.env').touch()
                for name in ['scope.sh', 'stop.sh']:
                    shutil.copyfile(ROOT / 'ops/integration' / name, root / 'ops/integration' / name)
                binary = root / 'bin'
                binary.mkdir()
                log = root / 'docker.json'
                fake = binary / 'docker'
                fake.write_text(f'#!{sys.executable}\nimport json,os,sys\nfrom pathlib import Path\n'
                                "if sys.argv[1:] != ['info']:\n"
                                " Path(os.environ['QUALITY_DOCKER_LOG']).write_text(json.dumps(sys.argv[1:]))\n")
                fake.chmod(0o700)
                subprocess.run(['bash', str(root / 'ops/integration/stop.sh')], check=True,
                               env={**os.environ, 'PATH': str(binary) + os.pathsep + os.environ['PATH'],
                                    'QUALITY_DOCKER_LOG': str(log)})
                argv = json.loads(log.read_text())
                name = argv[argv.index('--project-name') + 1]
                self.assertRegex(name, r'^hs-manacost-integration-[a-f0-9]{12}$')
                self.assertNotEqual(name, 'hs-manacost-integration')
                names.append(name)
            self.assertNotEqual(names[0], names[1])


if __name__ == '__main__':
    unittest.main()
