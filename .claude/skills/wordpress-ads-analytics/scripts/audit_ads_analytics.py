#!/usr/bin/env python3
"""Passively inspect banner and Plausible markup without sending events."""

from __future__ import annotations

import argparse
import json
from html.parser import HTMLParser
from typing import Any
from urllib.request import Request, urlopen


class MeasurementParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.trackers: list[dict[str, str]] = []
        self.images: list[str] = []
        self.links: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = {name.lower(): value or "" for name, value in attrs}
        if tag.lower() == "script" and ("plausible" in values.get("src", "") or values.get("data-domain")):
            self.trackers.append(values)
        elif tag.lower() == "img" and values.get("src"):
            self.images.append(values["src"])
        elif tag.lower() == "a" and values.get("href"):
            self.links.append(values["href"])


def fetch(url: str, timeout: float) -> tuple[int, str, dict[str, str], str]:
    request = Request(url, headers={"User-Agent": "hs-manacost-ads-audit/1.0"})
    with urlopen(request, timeout=timeout) as response:  # noqa: S310 - explicit audit URL.
        body = response.read(5_000_000).decode("utf-8", errors="replace")
        return response.status, response.geturl(), dict(response.headers.items()), body


def audit(
    role: str,
    requested_url: str,
    expected_banner: str,
    forbidden_banner: str,
    timeout: float,
) -> dict[str, Any]:
    status, final_url, headers, body = fetch(requested_url, timeout)
    parser = MeasurementParser()
    parser.feed(body)
    errors: list[str] = []
    warnings: list[str] = []

    if status != 200:
        errors.append(f"unexpected HTTP status {status}")
    if expected_banner and not any(expected_banner in src for src in parser.images):
        errors.append(f"expected banner is missing: {expected_banner}")
    if forbidden_banner and any(forbidden_banner in src for src in parser.images):
        errors.append(f"obsolete banner is present: {forbidden_banner}")

    if role == "staging":
        if parser.trackers:
            errors.append("staging must not load a live Plausible tracker")
    else:
        if len(parser.trackers) != 1:
            errors.append(f"expected one Plausible tracker, found {len(parser.trackers)}")
        elif parser.trackers[0].get("data-domain") != "hs-manacost.ru":
            errors.append("Plausible data-domain must remain hs-manacost.ru")

    playerok_links = [link for link in parser.links if "plrk.co" in link or "playerok" in link.lower()]
    if not playerok_links:
        warnings.append("Playerok target link was not found")

    return {
        "role": role,
        "requested_url": requested_url,
        "final_url": final_url,
        "status": status,
        "cache": {
            name: headers.get(name, "")
            for name in ("Age", "Cache-Control", "CF-Cache-Status", "X-Cache", "X-Proxy-Region")
            if headers.get(name)
        },
        "tracker_count": len(parser.trackers),
        "playerok_links": playerok_links,
        "errors": errors,
        "warnings": warnings,
    }


def parse_target(value: str) -> tuple[str, str]:
    role, separator, url = value.partition("=")
    if not separator or role not in {"primary", "mirror", "staging"} or not url.startswith(("http://", "https://")):
        raise argparse.ArgumentTypeError("target must be primary|mirror|staging=https://...")
    return role, url


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--target", action="append", required=True, type=parse_target)
    parser.add_argument("--expected-banner", default="")
    parser.add_argument("--forbidden-banner", default="")
    parser.add_argument("--timeout", type=float, default=15.0)
    args = parser.parse_args()
    try:
        results = [
            audit(role, url, args.expected_banner, args.forbidden_banner, args.timeout)
            for role, url in args.target
        ]
    except Exception as error:  # Network failures must be reported as audit failures.
        print(json.dumps({"ok": False, "error": str(error)}, ensure_ascii=False, indent=2))
        return 1
    ok = all(not result["errors"] for result in results)
    print(json.dumps({"ok": ok, "results": results}, ensure_ascii=False, indent=2))
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
