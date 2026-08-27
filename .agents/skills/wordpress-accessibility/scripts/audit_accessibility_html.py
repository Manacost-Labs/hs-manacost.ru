#!/usr/bin/env python3
"""Perform a small passive structural accessibility audit of saved HTML."""

from __future__ import annotations

import argparse
import json
from html.parser import HTMLParser
from pathlib import Path


class AuditParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.errors: list[str] = []
        self.heading_levels: list[int] = []
        self.html_has_lang = False
        self.inputs: list[tuple[str, dict[str, str]]] = []
        self.label_fors: set[str] = set()

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = {key: value or "" for key, value in attrs}
        if tag == "html":
            self.html_has_lang = bool(values.get("lang", "").strip())
        elif tag == "img" and "alt" not in values:
            self.errors.append("image missing alt attribute")
        elif tag in {"h1", "h2", "h3", "h4", "h5", "h6"}:
            self.heading_levels.append(int(tag[1]))
        elif tag == "label" and values.get("for"):
            self.label_fors.add(values["for"])
        elif tag in {"input", "select", "textarea"}:
            self.inputs.append((tag, values))
    def finish(self) -> list[str]:
        if not self.html_has_lang:
            self.errors.append("html element missing lang")
        if self.heading_levels.count(1) != 1:
            self.errors.append("document must contain exactly one h1")
        for previous, current in zip(self.heading_levels, self.heading_levels[1:]):
            if current > previous + 1:
                self.errors.append(f"heading level skips from h{previous} to h{current}")
        for tag, values in self.inputs:
            if values.get("type") == "hidden":
                continue
            field_id = values.get("id")
            named = bool(
                values.get("aria-label")
                or values.get("aria-labelledby")
                or (field_id and field_id in self.label_fors)
            )
            if not named:
                self.errors.append(f"{tag} missing accessible label")
        return self.errors


def audit(html: str) -> list[str]:
    parser = AuditParser()
    parser.feed(html)
    return parser.finish()


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("html", type=Path)
    args = parser.parse_args()
    errors = audit(args.html.read_text(encoding="utf-8"))
    print(json.dumps({"errors": errors}, ensure_ascii=False, indent=2))
    return 1 if errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
