"""Локальный rebuild hs_bgs_dictionary.json — зеркало логики
hs_smart_tooltip_rebuild_bgs_dictionary() из основного плагина.

Используется когда WP-Cron / админ-кнопка недоступны (dev-окружение).
"""

from __future__ import annotations

import json
import os
import shutil
import sys
import urllib.request
from pathlib import Path

API_URL = "https://api.hearthstonejson.com/v1/latest/ruRU/cards.json"
ART_BASE = "https://art.hearthstonejson.com/v1/bgs/latest/ruRU/256x/"

KEEP_TYPES = {
    "MINION",
    "HERO",
    "SPELL",
    "BATTLEGROUND_SPELL",
    "BATTLEGROUND_ANOMALY",
    "BATTLEGROUND_TRINKET",
    "BATTLEGROUND_QUEST_REWARD",
}

TECH_TO_RARITY = {
    1: "common",
    2: "common",
    3: "rare",
    4: "rare",
    5: "epic",
    6: "legendary",
    7: "legendary",
}


def main() -> int:
    plugin_dir = Path(__file__).resolve().parent.parent
    out = plugin_dir / "hs_bgs_dictionary.json"
    bak = plugin_dir / "hs_bgs_dictionary.bak.json"
    tmp = plugin_dir / "hs_bgs_dictionary.json.tmp"

    print(f"Fetching {API_URL} ...")
    req = urllib.request.Request(
        API_URL, headers={"User-Agent": "HS-Smart-Tooltip-rebuild/1.0"}
    )
    with urllib.request.urlopen(req, timeout=120) as resp:
        if resp.status != 200:
            print(f"HTTP {resp.status}", file=sys.stderr)
            return 2
        cards = json.load(resp)
    print(f"  got {len(cards)} cards")

    by_id: dict[str, dict] = {}
    for c in cards:
        if not isinstance(c, dict):
            continue
        if c.get("set") != "BATTLEGROUNDS":
            continue
        t = c.get("type") or ""
        if t not in KEEP_TYPES:
            continue
        cid = c.get("id") or ""
        name = c.get("name") or ""
        if not cid or not name:
            continue
        tech = int(c.get("techLevel") or 0)
        rarity = TECH_TO_RARITY.get(tech, "common")
        by_id[cid] = {
            "img": ART_BASE + cid + ".png",
            "rarity": rarity,
            "name": name,
            "type": t,
            "techLevel": tech,
        }

    if not by_id:
        print("no BG cards", file=sys.stderr)
        return 3

    print(f"BG cards after filter: {len(by_id)}")

    if out.exists():
        try:
            with out.open("r", encoding="utf-8") as f:
                prev = json.load(f)
            prev_count = len(prev.get("by_id", {})) if isinstance(prev, dict) else 0
        except Exception:
            prev_count = 0
        if prev_count > 0 and len(by_id) < int(prev_count * 0.5):
            print(
                f"health-check failed: {len(by_id)} < 50% of previous {prev_count}",
                file=sys.stderr,
            )
            return 4
        shutil.copyfile(out, bak)
        print(f"backup -> {bak.name}")

    with tmp.open("w", encoding="utf-8") as f:
        json.dump({"by_id": by_id}, f, ensure_ascii=False, separators=(",", ":"))
    os.replace(tmp, out)
    print(f"wrote {out.name} ({out.stat().st_size:,} bytes, {len(by_id)} cards)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
