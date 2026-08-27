#!/usr/bin/env python3
"""Passively audit rendered SEO host invariants without mutating WordPress."""

from __future__ import annotations

import argparse
import json
from html.parser import HTMLParser
from typing import Any
from urllib.parse import urlparse
from urllib.request import Request, urlopen


PRIMARY_HOST = "hs-manacost.ru"


class SeoParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.canonicals: list[str] = []
        self.robots: list[str] = []
        self.titles: list[str] = []
        self.schemas: list[str] = []
        self.images = 0
        self.images_without_alt = 0
        self._title = False
        self._schema = False

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = {name.lower(): value or "" for name, value in attrs}
        if tag.lower() == "link" and "canonical" in values.get("rel", "").lower().split():
            self.canonicals.append(values.get("href", ""))
        elif tag.lower() == "meta" and values.get("name", "").lower() in {"robots", "googlebot"}:
            self.robots.append(values.get("content", ""))
        elif tag.lower() == "title":
            self._title = True
            self.titles.append("")
        elif tag.lower() == "script" and values.get("type", "").lower() == "application/ld+json":
            self._schema = True
            self.schemas.append("")
        elif tag.lower() == "img":
            self.images += 1
            if "alt" not in values:
                self.images_without_alt += 1

    def handle_endtag(self, tag: str) -> None:
        if tag.lower() == "title":
            self._title = False
        elif tag.lower() == "script":
            self._schema = False

    def handle_data(self, data: str) -> None:
        if self._title and self.titles:
            self.titles[-1] += data
        if self._schema and self.schemas:
            self.schemas[-1] += data


def fetch(url: str, timeout: float) -> tuple[int, str, dict[str, str], str]:
    request = Request(url, headers={"User-Agent": "hs-manacost-seo-audit/1.0"})
    with urlopen(request, timeout=timeout) as response:  # noqa: S310 - explicit audit URL.
        body = response.read(5_000_000).decode("utf-8", errors="replace")
        return response.status, response.geturl(), dict(response.headers.items()), body


def audit(role: str, requested_url: str, timeout: float) -> dict[str, Any]:
    status, final_url, headers, body = fetch(requested_url, timeout)
    parser = SeoParser()
    parser.feed(body)
    errors: list[str] = []
    warnings: list[str] = []
    robots = " ".join(parser.robots + [headers.get("X-Robots-Tag", "")]).lower()

    if status != 200:
        errors.append(f"unexpected HTTP status {status}")
    if len(parser.titles) != 1 or not parser.titles[0].strip():
        errors.append("expected exactly one non-empty title")
    if role in {"primary", "mirror"}:
        if len(parser.canonicals) != 1:
            errors.append(f"expected exactly one canonical, found {len(parser.canonicals)}")
        elif urlparse(parser.canonicals[0]).hostname != PRIMARY_HOST:
            errors.append(f"canonical must use {PRIMARY_HOST}")
    elif len(parser.canonicals) > 1:
        errors.append(f"staging must not emit multiple canonicals, found {len(parser.canonicals)}")

    if role == "primary" and "noindex" in robots:
        errors.append("primary unexpectedly contains noindex")
    if role in {"mirror", "staging"} and "noindex" not in robots:
        errors.append(f"{role} must contain noindex in meta or HTTP header")
    if role == "staging" and ("nofollow" not in robots or "noarchive" not in robots):
        errors.append("staging must contain nofollow and noarchive")
    if role == "mirror" and headers.get("X-Manacost-Mirror", "").lower() != "active":
        errors.append("mirror marker header is missing")

    invalid_schema = 0
    for payload in parser.schemas:
        try:
            json.loads(payload)
        except json.JSONDecodeError:
            invalid_schema += 1
    if invalid_schema:
        errors.append(f"invalid JSON-LD blocks: {invalid_schema}")
    if parser.images_without_alt:
        warnings.append(f"images without alt attribute: {parser.images_without_alt}/{parser.images}")

    return {
        "role": role,
        "requested_url": requested_url,
        "final_url": final_url,
        "status": status,
        "canonical": parser.canonicals,
        "robots": robots.strip(),
        "schema_blocks": len(parser.schemas),
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
    parser.add_argument("--timeout", type=float, default=15.0)
    args = parser.parse_args()
    results = []
    try:
        results = [audit(role, url, args.timeout) for role, url in args.target]
    except Exception as error:  # Network failures must be reported as audit failures.
        print(json.dumps({"ok": False, "error": str(error)}, ensure_ascii=False, indent=2))
        return 1
    ok = all(not result["errors"] for result in results)
    print(json.dumps({"ok": ok, "results": results}, ensure_ascii=False, indent=2))
    return 0 if ok else 1


if __name__ == "__main__":
    raise SystemExit(main())
