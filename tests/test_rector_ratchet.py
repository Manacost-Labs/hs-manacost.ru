import hashlib
import importlib.util
from pathlib import Path
import tempfile
import unittest

spec = importlib.util.spec_from_file_location('rector_ratchet', Path(__file__).resolve().parents[1] / 'ops/code-quality/rector-ratchet.py')
ratchet = importlib.util.module_from_spec(spec)
spec.loader.exec_module(ratchet)


class RectorRatchetTests(unittest.TestCase):
    def test_new_proposal_or_changed_source_cannot_reuse_legacy_exception(self):
        with tempfile.TemporaryDirectory() as directory:
            root = Path(directory)
            source = root / 'a.php'
            source.write_text('<?php echo 1;')
            proposal = {'file': 'a.php', 'diff': 'legacy proposal'}
            baseline = {'entries': [{'path': 'a.php', 'source_sha256': hashlib.sha256(source.read_bytes()).hexdigest(),
                                     'diff_sha256': hashlib.sha256(proposal['diff'].encode()).hexdigest()}]}
            self.assertEqual([], ratchet.compare(root, [proposal], baseline))
            self.assertEqual([], ratchet.compare(root, [], baseline))
            self.assertEqual(['a.php'], ratchet.compare(root, [{**proposal, 'diff': 'new debt'}], baseline))
            source.write_text('<?php echo 2;')
            self.assertEqual(['a.php'], ratchet.compare(root, [proposal], baseline))
