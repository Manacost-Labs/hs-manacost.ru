from configparser import ConfigParser
from pathlib import Path
from unittest import TestCase


ROOT = Path(__file__).resolve().parents[1]
ORDERING_DROP_IN = (
    ROOT / "ops" / "monitoring" / "hs-manacost-origin-firewall-ordering.conf"
)
RUNBOOK = ROOT / "docs" / "operations" / "stability-phase2.md"


class OriginFirewallOrderingTest(TestCase):
    def test_origin_firewall_runs_after_rule_restore(self) -> None:
        parser = ConfigParser(interpolation=None)
        parser.read(ORDERING_DROP_IN)

        self.assertEqual(parser.sections(), ["Unit"])
        self.assertEqual(
            set(parser["Unit"]["After"].split()), {"iptables-restore.service"}
        )
        self.assertNotIn("Wants", parser["Unit"])
        self.assertNotIn("Requires", parser["Unit"])

    def test_runbook_documents_install_validation_and_rollback(self) -> None:
        runbook = RUNBOOK.read_text()

        self.assertIn("20-ordering.conf", runbook)
        self.assertIn("systemctl restart hs-manacost-origin-firewall.service", runbook)
        self.assertIn("iptables -C INPUT", runbook)
        self.assertIn("ip6tables -C INPUT", runbook)
        self.assertIn(
            "`systemctl revert hs-manacost-origin-firewall.service`", runbook
        )
        self.assertIn("can remove unrelated", runbook)
