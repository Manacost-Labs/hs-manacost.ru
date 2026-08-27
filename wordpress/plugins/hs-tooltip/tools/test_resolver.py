"""Тестирование резолвера ID на актуальном словаре.

Зеркалирует логику hs_smart_tooltip_resolve_card_id из PHP.
Прогоняем по списку известных проблемных форматов и сэмплам из словаря.
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path


def resolve(by_id: dict, needle: str) -> str | None:
    needle = (needle or "").strip()
    needle = re.sub(r"\s+", "", needle)
    if not needle:
        return None
    if needle in by_id:
        return needle
    upper = needle.upper()
    if upper in by_id:
        return upper
    m = re.match(r"^(\d+)[-_]", needle)
    if m and m.group(1) in by_id:
        return m.group(1)
    low = needle.lower()
    for k in by_id:
        if isinstance(k, str) and k.lower() == low:
            return k
    return None


def main() -> int:
    plugin = Path(__file__).resolve().parent.parent
    with (plugin / "hs_dictionary.json").open("r", encoding="utf-8") as f:
        d = json.load(f)
    by_id: dict = d["by_id"]
    print(f"by_id keys: {len(by_id)}")

    cases: list[tuple[str, str]] = [
        # (input, expected_outcome — "FOUND" or "NOT FOUND")
        ("EX1_116", "FOUND"),
        ("ex1_116", "FOUND"),  # lowercased
        ("Ex1_116", "FOUND"),  # mixed
        ("CATA_190h", "FOUND"),  # exact mixed-case
        ("CATA_190H", "FOUND"),  # uppercased mixed-case (case-insensitive)
        ("cata_190h", "FOUND"),  # lowercased mixed-case
        ("106638", "FOUND"),  # dbfId numeric
        ("106638-RANGER-GILLY", "FOUND"),  # legacy slug
        ("120074-BROXIGAR", "FOUND"),  # user's actual broken ID
        ("120999-THE-ETERNAL-HOLD", "FOUND"),  # user's actual broken ID
        ("MEND_041", "FOUND"),  # newest mini-set
        ("  EX1_116  ", "FOUND"),  # whitespace padding
        ("EX1 _ 116", "FOUND"),  # whitespace inside (we strip)
        ("FAKE_NOT_REAL", "NOT FOUND"),
        ("99999999", "NOT FOUND"),  # nonexistent dbfId
        ("99999999-FAKE-NAME", "NOT FOUND"),  # bad slug
    ]

    fails = 0
    for inp, expected in cases:
        result = resolve(by_id, inp)
        actual = "FOUND" if result is not None else "NOT FOUND"
        status = "OK " if actual == expected else "FAIL"
        if actual != expected:
            fails += 1
        ent_name = by_id.get(result, {}).get("name", "") if result else ""
        print(f"  [{status}] {inp!r:40s} -> {result!r:24s} {ent_name}")

    # Statistics on what's actually in the dict
    print()
    canon = sum(1 for k in by_id if re.match(r"^[A-Za-z][A-Za-z0-9_]+$", k))
    numeric = sum(1 for k in by_id if k.isdigit())
    other = len(by_id) - canon - numeric
    print(f"canonical IDs: {canon}, numeric dbfId aliases: {numeric}, other: {other}")

    mixed = [k for k in by_id if k != k.upper() and not k.isdigit()]
    print(f"mixed-case IDs: {len(mixed)}")
    print("  examples:", mixed[:8])

    return 1 if fails else 0


if __name__ == "__main__":
    sys.exit(main())
