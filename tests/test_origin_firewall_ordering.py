from configparser import ConfigParser
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
ORDERING_DROP_IN = (
    ROOT / "ops" / "monitoring" / "hs-manacost-origin-firewall-ordering.conf"
)
RUNBOOK = ROOT / "docs" / "operations" / "stability-phase2.md"


def test_origin_firewall_runs_after_rule_restore_and_docker() -> None:
    parser = ConfigParser(interpolation=None)
    parser.read(ORDERING_DROP_IN)

    assert parser.sections() == ["Unit"]
    after = set(parser["Unit"]["After"].split())
    assert after == {"iptables-restore.service", "docker.service"}
    assert "Wants" not in parser["Unit"]
    assert "Requires" not in parser["Unit"]


def test_runbook_documents_install_validation_and_rollback() -> None:
    runbook = RUNBOOK.read_text()

    assert "20-ordering.conf" in runbook
    assert "systemctl restart hs-manacost-origin-firewall.service" in runbook
    assert "iptables -C INPUT" in runbook
    assert "ip6tables -C INPUT" in runbook
    assert "`systemctl revert hs-manacost-origin-firewall.service`" in runbook
    assert "can remove unrelated" in runbook
