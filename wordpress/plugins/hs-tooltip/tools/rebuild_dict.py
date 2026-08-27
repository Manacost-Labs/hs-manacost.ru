"""Локальный rebuild hs_dictionary.json — зеркало логики includes/auto-update.php.

Запускается, когда WP-Cron / админ-кнопка недоступны (например, в dev-окружении).
Пишет файл в тот же формат, что ожидает плагин: {"by_id": {...}, "by_name": {...}}.
"""

from __future__ import annotations

import json
import os
import re
import shutil
import sys
import urllib.request
from pathlib import Path

API_URL = "https://api.hearthstonejson.com/v1/latest/ruRU/cards.collectible.json"
RENDER_BASE = "https://art.hearthstonejson.com/v1/render/latest/ruRU/256x/"
EXCLUDED_SETS = {"BATTLEGROUNDS", "HERO_SKINS", "PLACEHOLDER_202204"}
ALLOWED_TYPES = {"MINION", "SPELL", "WEAPON", "HERO", "LOCATION"}
RARITY_MAP = {"LEGENDARY": "legendary", "EPIC": "epic", "RARE": "rare"}
RARITY_PRIORITY = {"common": 0, "rare": 1, "epic": 2, "legendary": 3}

SET_ICONS = {
    # CORE — ротационный базовый сет, в текущем году "Год Скарабея".
    "CORE":                      "Year_of_the_Scarab_-_SVG_logo.svg",
    "HOF":                       "Hall_of_Fame_-_SVG_logo.svg",
    "GVG":                       "Goblins_vs_Gnomes_-_SVG_logo.svg",
    "TGT":                       "The_Grand_Tournament_-_SVG_logo.svg",
    "OG":                        "Whispers_of_the_Old_Gods_-_SVG_logo.svg",
    "GANGS":                     "Mean_Streets_of_Gadgetzan_-_SVG_logo.svg",
    "UNGORO":                    "Journey_to_UnGoro_-_SVG_logo.svg",
    "THE_LOST_CITY":             "The_Lost_City_of_UnGoro_-_SVG_logo.svg",
    "ICECROWN":                  "Knights_of_the_Frozen_Throne_-_SVG_logo.svg",
    "LOOTAPALOOZA":              "Kobolds_and_Catacombs_-_SVG_logo.svg",
    "GILNEAS":                   "The_Witchwood_-_SVG_logo.svg",
    "BOOMSDAY":                  "The_Boomsday_Project_-_SVG_logo.svg",
    "TROLL":                     "Rastakhans_Rumble_-_SVG_logo.svg",
    "DALARAN":                   "Rise_of_Shadows_-_SVG_logo.svg",
    "ULDUM":                     "Saviors_of_Uldum_-_SVG_logo.svg",
    "DRAGONS":                   "Descent_of_Dragons_-_SVG_logo.svg",
    "BLACK_TEMPLE":              "Ashes_of_Outland_-_SVG_logo.svg",
    "DEMON_HUNTER_INITIATE":     "Demon_Hunter_Initiate_-_SVG_logo.svg",
    "SCHOLOMANCE":               "Scholomance_Academy_-_SVG_logo.svg",
    "DARKMOON_FAIRE":            "Madness_at_the_Darkmoon_Faire_-_SVG_logo.svg",
    "THE_BARRENS":               "Forged_in_the_Barrens_-_SVG_logo.svg",
    "STORMWIND":                 "United_in_Stormwind_-_SVG_logo.svg",
    "ALTERAC_VALLEY":            "Fractured_in_Alterac_Valley_-_SVG_logo.svg",
    "THE_SUNKEN_CITY":           "Voyage_to_the_Sunken_City_-_SVG_logo.svg",
    "REVENDRETH":                "Murder_at_Castle_Nathria_-_SVG_logo.svg",
    "RETURN_OF_THE_LICH_KING":   "March_of_the_Lich_King_-_SVG_logo.svg",
    "PATH_OF_ARTHAS":            "Path_of_Arthas_-_SVG_logo.svg",
    "BATTLE_OF_THE_BANDS":       "Festival_of_Legends_-_SVG_logo.svg",
    "TITANS":                    "TITANS_-_SVG_logo.svg",
    "WILD_WEST":                 "Showdown_in_the_Badlands_-_SVG_logo.svg",
    "BADLANDS":                  "Showdown_in_the_Badlands_-_SVG_logo.svg",
    "WHIZBANGS_WORKSHOP":        "Whizbangs_Workshop_-_SVG_logo.svg",
    "ISLAND_VACATION":           "Perils_in_Paradise_-_SVG_logo.svg",
    "PERILS_IN_PARADISE":        "Perils_in_Paradise_-_SVG_logo.svg",
    "EMERALD_DREAM":             "Into_the_Emerald_Dream_-_SVG_logo.svg",
    "ESCAPEFROM_VIOLET_HOLD":    "Escape_from_Violet_Hold_-_SVG_logo.svg",
    "GREAT_DARK_BEYOND":         "The_Great_Dark_Beyond_-_SVG_logo.svg",
    "SPACE":                     "The_Great_Dark_Beyond_-_SVG_logo.svg",
    "TIME_TRAVEL":               "Across_the_Timeways_-_SVG_logo.svg",
    "EVENT":                     "Event_-_SVG_logo.svg",
    "CAVERNS_OF_TIME":           "Caverns_of_Time_-_SVG_logo.svg",
    "CATACLYSM":                 "CATACLYSM_-_SVG_logo.svg",
}


def normalize_rarity(raw: str) -> str:
    return RARITY_MAP.get((raw or "").upper(), "common")


def dict_key(name: str) -> str:
    return " ".join(name.lower().split())


def card_to_entry(card: dict) -> tuple[str, str, dict] | None:
    cid = card.get("id") or ""
    name = (card.get("name") or "").strip()
    if not cid or not name:
        return None
    s = card.get("set") or ""
    if s in EXCLUDED_SETS:
        return None
    t = card.get("type") or ""
    if t not in ALLOWED_TYPES:
        return None
    if card.get("battlegroundsNormalDbfId"):
        return None

    entry: dict = {
        "img": f"{RENDER_BASE}{cid}.png",
        "rarity": normalize_rarity(card.get("rarity") or ""),
        "name": name,
    }
    icon = SET_ICONS.get(s, "")
    if icon:
        entry["set_icon"] = icon
    dbf = str(card.get("dbfId") or "")
    return cid, dbf, entry


def build_dictionary(cards: list[dict]) -> dict:
    core_icon = SET_ICONS.get("CORE", "")

    # Pre-pass: ID оригиналов, перепечатанных в CORE.
    core_originals: set[str] = set()
    core_re = re.compile(r"^[Cc][Oo][Rr][Ee]_(.+)$")
    for c in cards:
        if not isinstance(c, dict):
            continue
        if c.get("set") != "CORE":
            continue
        cid = c.get("id") or ""
        m = core_re.match(cid)
        if m:
            core_originals.add(m.group(1))

    by_id: dict[str, dict] = {}
    by_name: dict[str, dict] = {}
    owners: dict[str, int] = {}

    for c in cards:
        if not isinstance(c, dict):
            continue
        out = card_to_entry(c)
        if out is None:
            continue
        cid, dbf, entry = out

        # CORE-перепечатка существует — оригинал подсвечиваем CORE-иконкой.
        if core_icon and cid in core_originals:
            entry = {**entry, "set_icon": core_icon}

        by_id[cid] = entry
        if dbf and dbf not in by_id:
            by_id[dbf] = entry
        key = dict_key(entry["name"])
        if not key:
            continue
        prio = RARITY_PRIORITY[entry["rarity"]]
        if key not in by_name or prio > owners.get(key, -1):
            by_name[key] = entry
            owners[key] = prio
    return {"by_id": by_id, "by_name": by_name}


def main() -> int:
    force = "--force" in sys.argv
    plugin_dir = Path(__file__).resolve().parent.parent
    dict_path = plugin_dir / "hs_dictionary.json"
    backup_path = plugin_dir / "hs_dictionary.bak.json"
    tmp_path = plugin_dir / "hs_dictionary.json.tmp"

    print(f"Fetching {API_URL} ...")
    req = urllib.request.Request(
        API_URL,
        headers={"User-Agent": "HS-Smart-Tooltip-rebuild/1.0"},
    )
    with urllib.request.urlopen(req, timeout=120) as resp:
        if resp.status != 200:
            print(f"HTTP {resp.status}", file=sys.stderr)
            return 2
        cards = json.load(resp)
    print(f"  got {len(cards)} cards from API")

    built = build_dictionary(cards)
    count = len(built["by_id"])
    print(f"after filter: {count} cards in by_id, {len(built['by_name'])} keys in by_name")

    if count < 2000:
        print(f"health-check failed: {count} < 2000 floor", file=sys.stderr)
        return 3

    if dict_path.exists():
        try:
            with dict_path.open("r", encoding="utf-8") as f:
                prev = json.load(f)
            prev_count = len(prev.get("by_id", {})) if isinstance(prev, dict) else 0
        except Exception:
            prev_count = 0
        if prev_count > 0 and count < int(prev_count * 0.8):
            msg = f"health-check: {count} < 80% of previous {prev_count}"
            if force:
                print(f"WARN (forced): {msg}")
            else:
                print(f"health-check failed: {msg}", file=sys.stderr)
                return 4
        shutil.copyfile(dict_path, backup_path)
        print(f"backup -> {backup_path.name}")

    with tmp_path.open("w", encoding="utf-8") as f:
        json.dump(built, f, ensure_ascii=False, separators=(",", ":"))
    tmp_path.chmod(0o644)
    os.replace(tmp_path, dict_path)
    size = dict_path.stat().st_size
    print(f"wrote {dict_path.name} ({size:,} bytes, {count} cards)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
