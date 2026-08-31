import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BRIDGE = ROOT / "wordpress/mu-plugins/hs-local-image-optimizer.php"


class LocalImageOptimizerQueuePolicyTest(unittest.TestCase):
    def test_action_scheduler_does_not_collapse_attachment_jobs(self) -> None:
        source = BRIDGE.read_text(encoding="utf-8")

        self.assertNotIn("self::GROUP, true", source)
        self.assertEqual(source.count("self::GROUP, false"), 2)
        self.assertIn("hs_local_image_optimizer_should_queue", source)
        self.assertIn("array( __CLASS__, 'queue_after_metadata' ), 99, 3", source)


if __name__ == "__main__":
    unittest.main()
