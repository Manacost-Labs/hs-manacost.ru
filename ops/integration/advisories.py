"""Public WPVulnerability advisories; separate from the authenticated WPScan DB."""
import hashlib
import json
from pathlib import Path
import re
import subprocess
import time
import urllib.request

API = "https://www.wpvulnerability.net"
OPERATORS = {"lt", "le", "eq", "ne", "gt", "ge"}
MAX_BYTES = 2_000_000
# Verified from wordpress/themes/Newspaper_new/style.css: tagDiv, Text Domain newspaper.
# Exact project alias only; never guess identities by stripping directory suffixes.
ALIASES = {("theme", "Newspaper_new"): "newspaper"}


def version_matches(version, bounds):
    """Use PHP's documented version_compare semantics, including prereleases."""
    comparisons = []
    if not isinstance(bounds, dict):
        raise ValueError("Missing version range")
    for side in ("min", "max"):
        target, operator = bounds.get(side + "_version"), bounds.get(side + "_operator")
        if target is None and operator is None:
            continue
        if not isinstance(target, str) or not target or len(target) > 100 or operator not in OPERATORS:
            raise ValueError("Invalid advisory version range")
        comparisons.append([version, target, operator])
    if not comparisons and str(bounds.get("unfixed")) != "1":
        raise ValueError("Unbounded range without unfixed marker")
    code = '$rows=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR);foreach($rows as $r){if(!version_compare($r[0],$r[1],$r[2])){echo "0";exit;}}echo "1";'
    result = subprocess.run(["php", "-r", code], input=json.dumps(comparisons), text=True,
                            capture_output=True, timeout=5, check=True)
    if result.stdout not in ("0", "1"):
        raise ValueError("Unexpected PHP comparator response")
    return result.stdout == "1"


def classify(kind, slug, version, envelope):
    data = envelope.get("data")
    if envelope.get("error") != 0 or not isinstance(data, dict) or data.get(kind) != slug:
        raise ValueError("Component missing or mismatched in public database")
    if "vulnerability" not in data:
        raise ValueError("Missing advisory list")
    if kind != "core" and not data.get("name") and data.get("vulnerability") is None:
        raise ValueError("Unknown component in public database")
    entries = data.get("vulnerability")
    if entries is None:
        entries = []
    if not isinstance(entries, list):
        raise ValueError("Missing advisory list")
    findings = {}
    for entry in entries:
        if not isinstance(entry, dict) or not entry.get("uuid") or not isinstance(entry.get("name"), str):
            raise ValueError("Malformed advisory")
        # Core endpoint is already filtered to the exact requested version.
        affected = kind == "core" or version_matches(version, entry.get("operator"))
        if affected:
            findings[entry["uuid"]] = {"id": entry["uuid"], "name": entry["name"],
                "sources": sorted({s["link"] for s in entry.get("source", [])
                                   if isinstance(s, dict) and isinstance(s.get("link"), str)
                                   and s["link"].startswith("https://")})}
    return {"findings": list(findings.values()), "closed": str(data.get("closed", 0)) == "1",
            "advisories_available": True, "database_updated": envelope.get("updated")}


def fetch(kind, slug, timeout):
    request = urllib.request.Request(f"{API}/{kind}/{slug}/", headers={"User-Agent": "Manacost-local-quality/1.0"})
    with urllib.request.urlopen(request, timeout=timeout) as response:
        if response.geturl() != request.full_url:
            raise ValueError("Unexpected advisory redirect")
        raw = response.read(MAX_BYTES + 1)
    if len(raw) > MAX_BYTES:
        raise ValueError("Advisory response exceeded size bound")
    return raw


def check_inventory(inventory, directory, fetcher=fetch, budget_seconds=90):
    if not inventory or len(inventory) > 100:
        raise ValueError("Expected 1..100 installed components")
    directory = Path(directory)
    directory.mkdir(parents=True, exist_ok=True)
    deadline = time.monotonic() + budget_seconds
    results = []
    for item in inventory:
        item = dict(item)
        alias = ALIASES.get((item.get("kind"), item.get("slug")))
        if alias:
            item["installed_slug"], item["slug"] = item["slug"], alias
        kind, slug, version = (item.get(k, "") for k in ("kind", "slug", "version"))
        if kind not in ("core", "plugin", "theme") or not re.fullmatch(r"[A-Za-z0-9_.-]{1,100}", slug):
            raise ValueError("Invalid inventory identity")
        if not isinstance(version, str) or not re.fullmatch(r"[A-Za-z0-9_.+-]{1,100}", version):
            results.append({**item, "advisories_available": False, "error": "Version unavailable"})
            continue
        record = dict(item)
        try:
            remaining = deadline - time.monotonic()
            if remaining <= 0:
                raise ValueError("Total advisory time budget exhausted")
            raw = fetcher(kind, slug, min(10, remaining))
            if len(raw) > MAX_BYTES:
                raise ValueError("Advisory response exceeded size bound")
            record.update(classify(kind, slug, version, json.loads(raw)))
            record["response_sha256"] = hashlib.sha256(raw).hexdigest()
            (directory / f"{kind}-{slug}.json").write_bytes(raw)
        except (ValueError, OSError, subprocess.SubprocessError) as error:
            record.update(advisories_available=False, error=str(error)[:160])
        results.append(record)
    unavailable = sum(not row["advisories_available"] for row in results)
    findings = sum(len(row.get("findings", [])) for row in results)
    closed = sum(row.get("closed", False) for row in results)
    status = "failed" if findings or closed else "partial" if unavailable else "passed"
    report = {"source": "WPVulnerability", "api": API, "wpscan_database_checked": False,
              "status": status, "known_findings": findings, "closed_components": closed,
              "unavailable_components": unavailable, "components": results,
              "scope": "Installed local fixture versions only; absence of known advisories is not proof of security."}
    (directory / "summary.json").write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n")
    return report
